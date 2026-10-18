/* Run with Node; no browser/account state or network required. */
'use strict';
const fs = require('fs'), vm = require('vm'), assert = require('assert');
const source = fs.readFileSync(require('path').join(__dirname, '../../static/js/security.js'), 'utf8');
const events = {}, forms = [], sent = [], requests = [];
function form(action='http://mystep.com/console/login', method='post', attrs={}) {
    return {action, method, input:null, hasAttribute(name){return Object.hasOwn(attrs,name);},getAttribute(name){return attrs[name]??null;}, querySelector(){return this.input;}, appendChild(input){this.input=input;}, submit(){sent.push(this);}};
}
const login=form(), external=form('https://evil.test/receive'); forms.push(login, external);
class XHR {open(method,url){this.method=method;this.url=url;} setRequestHeader(name,value){this.header=[name,value];} send(){sent.push(this);}}
let prefilter;
const window={msSecurity:{token:'fixture-token',origins:['http://mystep.com','http://api.mystep.com'],admin:['console','admin_cms'],adminContext:false},jQuery:{ajaxPrefilter(callback){prefilter=callback;}},fetch(input,options){requests.push({input,options});return Promise.resolve();}};
const document={baseURI:'http://mystep.com/console/',forms,body:{appendChild(value){forms.push(value);}},createElement(type){return type==='form'?form():{};},addEventListener(name,callback){events[name]=callback;}};
vm.runInNewContext(source,{window,document,location:{href:document.baseURI},XMLHttpRequest:XHR,URL,Headers,Request});
let passed=0;
function test(name,run){run();passed++;console.log('PASS '+name);}
events.DOMContentLoaded();
test('existing POST form gets a hidden token',()=>assert.equal(login.input.value,'fixture-token'));
test('external forms never receive a token',()=>assert.equal(external.input,null));
test('dynamic POST forms get a token on submit',()=>{const f=form();events.submit({target:f});assert.equal(f.input.value,'fixture-token');});
test('GET Ajax stays GET',()=>{const o={url:'/console/list',type:'GET'};prefilter(o);assert.equal(o.type,'GET');assert.equal(o.headers,undefined);});
test('legacy destructive Ajax switches to POST with token',()=>{const o={url:'/console/function/plugin/delete/?idx=sample',type:'GET'};prefilter(o);assert.equal(o.type,'POST');assert.equal(o.headers['X-CSRF-Token'],'fixture-token');});
test('external Ajax never receives a token',()=>{const o={url:'https://evil.test/console/delete',type:'POST'};prefilter(o);assert.equal(o.headers,undefined);});
test('bound-domain POST XHR gets token',()=>{const x=new XHR();x.open('POST','http://api.mystep.com/upload');x.send();assert.deepEqual(x.header,['X-CSRF-Token','fixture-token']);});
test('external XHR never receives a token',()=>{const x=new XHR();x.open('POST','https://evil.test/receive');x.send();assert.equal(x.header,undefined);});
test('fetch POST gets token',()=>{window.fetch('/api/upload',{method:'POST'});assert.equal(requests.at(-1).options.headers.get('X-CSRF-Token'),'fixture-token');});
test('external fetch never receives a token',()=>{window.fetch('https://evil.test/receive',{method:'POST'});assert.equal(requests.at(-1).options.headers,undefined);});
function click(href,prevented=false){const before=sent.length;let blocked=false;const e={defaultPrevented:prevented,button:0,target:{closest(){return {href,target:''};}},preventDefault(){blocked=true;}};events.click(e);return {before,blocked,last:sent.at(-1)};}
test('destructive links submit POST forms',()=>{const result=click('http://mystep.com/console/function/plugin/active/?idx=sample');assert.equal(result.blocked,true);assert.equal(result.last.method,'POST');assert.equal(result.last.input.value,'fixture-token');});
test('cancelled confirmation stays cancelled',()=>{const result=click('http://mystep.com/console/function/plugin/delete/',true);assert.equal(result.blocked,false);assert.equal(result.before,sent.length);});
test('ordinary links keep navigation',()=>assert.equal(click('http://mystep.com/console/function/plugin/list').blocked,false));
test('bound admin domain aliases keep POST protection',()=>{window.msSecurity.adminContext=true;assert.equal(click('http://mystep.com/function/plugin/delete/').blocked,true);});
test('plugin update operations use POST and CSRF',()=>{for(const url of ['/manager/build','/manager/download?m=1','/manager/app/CMS','/pack/']){const o={url,type:'GET'};prefilter(o);assert.equal(o.type,'POST');assert.equal(o.headers['X-CSRF-Token'],'fixture-token');}});
test('badReq mutation links become POST',()=>{for(const action of ['ban','unban','del','reset'])assert.equal(click('http://mystep.com/badReq?m='+action).blocked,true);});
test('plugin read-only checks keep GET',()=>{for(const url of ['/manager/check_local','/manager/check_server','/badReq?keyword=ban']){const o={url,type:'GET'};prefilter(o);assert.equal(o.type,'GET');}});
test('plugin rules respect route boundaries and subdirectories',()=>{
    window.msSecurity.adminContext=false;
    for(const url of ['/CMS/catalog/pack/','/CMS/article/manager/build']){const o={url,type:'GET'};prefilter(o);assert.equal(o.type,'GET');}
    window.msSecurity.root='/sub/';
    const o={url:'/sub/index.php?manager/build/',type:'GET'};prefilter(o);assert.equal(o.type,'POST');
    window.msSecurity.root='/';
});
// 真实异步退出令牌刷新；只模拟网络响应，不发送请求。
(async function () {
 const attrs={'data-ms-logout':'','data-ms-csrf':'/api/myStep/csrf?core=myStep'}, fresh='b'.repeat(64);
 let fetches=[],alerts=[];window.alert=message=>alerts.push(message);
 function dispatch(f,prevented=false){let blocked=false;events.submit({target:f,defaultPrevented:prevented,preventDefault(){blocked=true;}});return blocked;}
 const flush=()=>new Promise(resolve=>setImmediate(resolve));
 window.fetch=(url,options)=>{fetches.push({url,options});return Promise.resolve({ok:true,json:()=>Promise.resolve({token:fresh})});};
 const logout=form(undefined,'post',attrs);window.msSecurity.token='stale-token';
 assert.equal(dispatch(logout),true);await flush();
 test('logout refreshes stale token before native POST',()=>{assert.equal(sent.at(-1),logout);assert.equal(logout.input.value,fresh);assert.equal(fetches[0].options.credentials,'same-origin');assert.equal(fetches[0].options.cache,'no-store');});
 const twice=form(undefined,'post',attrs),before=fetches.length;dispatch(twice);dispatch(twice);await flush();
 test('repeated logout clicks refresh and submit only once',()=>assert.equal(fetches.length,before+1));
 const sentBefore=sent.length;
 window.fetch=()=>Promise.resolve({ok:false});const failed=form(undefined,'post',attrs);dispatch(failed);await flush();
 test('failed logout refresh never submits with an old token',()=>{assert.equal(sent.length,sentBefore);assert.equal(failed._msLogoutPending,false);assert.equal(alerts.length,1);});
 window.fetch=()=>Promise.resolve({ok:true,json:()=>Promise.resolve({token:'invalid'})});dispatch(form(undefined,'post',attrs));await flush();
 test('malformed refreshed token is rejected',()=>assert.equal(sent.length,sentBefore));
 let externalCalls=0;window.fetch=()=>{externalCalls++;return Promise.resolve();};dispatch(form(undefined,'post',{'data-ms-logout':'','data-ms-csrf':'https://evil.test/token'}));await flush();
 test('logout never refreshes tokens at an external origin',()=>assert.equal(externalCalls,0));
 dispatch(form(undefined,'post',attrs),true);await flush();
 test('cancelled logout submit does not request a fresh token',()=>assert.equal(externalCalls,0));
 delete window.fetch;const offline=form(undefined,'post',attrs);
 test('without fetch a normal token protected POST remains available',()=>{assert.equal(dispatch(offline),false);assert.equal(offline.input.value,window.msSecurity.token);});
 console.log('Passed: '+passed+'; Failed: 0');
}()).catch(error=>{console.error(error);process.exitCode=1;});
