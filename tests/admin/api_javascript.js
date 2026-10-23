/* API行内交互回归：执行真实脚本与可控DOM/网络替身，不提交正式配置。 */
'use strict';
const fs=require('fs'),path=require('path'),vm=require('vm'),assert=require('assert');
const source=process.env.MYSTEP_ADMIN_STAGE||path.resolve(__dirname,'../..');
const script=fs.readFileSync(path.join(source,'app/myStep/asset/api.js'),'utf8');
let passed=0;
function fixture(){
 const events={},forms=[],requests=[],buttons=[],versions=[];
 const document={baseURI:'https://fixture.test/sub/Admin/function/api',addEventListener(n,fn){events[n]=fn;},querySelectorAll(s){return s.includes('version')?versions:buttons;},getElementById(id){return forms.find(f=>f.detail.id===id)?.detail;}};
 const context={document,location:{origin:'https://fixture.test'},URL,URLSearchParams,Array,Error,window:{checkForm:()=>true,confirm:()=>true,fetch(url,options){return new Promise(resolve=>requests.push({url,options,resolve}));}},FormData:class{constructor(form){this.form=form;}*[Symbol.iterator](){yield ['version',this.form.version.value];yield ['scope','Fixture'];yield ['name',this.form.detail.id];}}};
 vm.runInNewContext(script,context);
 function form(id){
  const classes=new Set(),status={textContent:'已启用'},message={textContent:'',classList:{toggle(c,on){if(on)classes.add(c);else classes.delete(c);}}},policy={enabled:{checked:true},cors:{checked:false},admin:{checked:false},methods:{value:'GET'}};
  const detail={id,hidden:true,previousElementSibling:{querySelector(){return status;},remove(){this.removed=true;}},remove(){this.removed=true;}};
  const f={detail,status,message,classes,policy,version:{value:'v1'},action:document.baseURI,matches:s=>s==='.ms-api-form',closest:()=>detail,querySelector(s){if(s.includes('message'))return message;if(s.includes('_ms_csrf'))return {value:'token'};const key=s.match(/policy\[([a-z]+)\]/);return key?policy[key[1]]:null;}};
  forms.push(f);versions.push(f.version);buttons.push({disabled:false});return f;
 }
 function submit(f){let prevented=false;events.submit({target:f,preventDefault(){prevented=true;}});return prevented;}
 async function response(status,data,jsonType=true){const req=requests.find(r=>!r.done);req.done=true;req.resolve({ok:status>=200&&status<300,headers:{get:()=>jsonType?'application/json':'text/html'},json:async()=>data});await new Promise(r=>setImmediate(r));}
 return {events,forms,requests,buttons,context,form,submit,response};
}
async function test(n,run){await run();passed++;console.log('PASS '+n);}
(async()=>{
 await test('toggle opens and closes corresponding row without navigation',()=>{const f=fixture(),form=f.form('one'),attrs={'aria-controls':'one'},link={getAttribute:k=>attrs[k],setAttribute(k,v){attrs[k]=v;}};let prevented=0;const click=()=>f.events.click({target:{closest:s=>s==='[data-api-toggle]'?link:null},preventDefault(){prevented++;}});click();assert(!form.detail.hidden);assert.equal(attrs['aria-expanded'],'true');click();assert(form.detail.hidden);assert.equal(prevented,2);});
 await test('save uses same-origin AJAX with CSRF and updates all versions but only current policy',async()=>{const f=fixture(),a=f.form('a'),b=f.form('b');b.policy.methods.value='PATCH';assert(f.submit(a));assert(f.buttons.every(b=>b.disabled));const req=f.requests[0];assert.equal(req.options.method,'POST');assert.equal(req.options.credentials,'same-origin');assert.equal(req.options.headers['X-CSRF-Token'],'token');assert.equal(req.options.body.get('_action'),'save');await f.response(200,{ok:true,version:'v2',message:'已应用',entry:{status:'已停用',policy:{enabled:false,methods:['POST'],cors:true,admin:true}}});assert.equal(a.status.textContent,'已停用');assert(!a.policy.enabled.checked&&a.policy.cors.checked&&a.policy.admin.checked);assert.equal(a.policy.methods.value,'POST');assert.equal(b.policy.methods.value,'PATCH');assert.equal(a.version.value,'v2');assert.equal(b.version.value,'v2');assert(f.buttons.every(b=>!b.disabled));});
 await test('page serializes concurrent saves and next request uses updated version',async()=>{const f=fixture(),a=f.form('a'),b=f.form('b');f.submit(a);f.submit(b);assert.equal(f.requests.length,1);await f.response(200,{ok:true,version:'v2',entry:{policy:{enabled:true,methods:[],cors:false,admin:false}}});f.submit(b);assert.equal(f.requests[1].options.body.get('version'),'v2');await f.response(200,{ok:true,version:'v3',entry:{}});});
 await test('conflict keeps local changes and old version, displays text and releases buttons',async()=>{const f=fixture(),a=f.form('a');a.policy.methods.value='PUT';f.submit(a);await f.response(409,{ok:false,message:'请刷新 <script>bad</script>'});assert.equal(a.version.value,'v1');assert.equal(a.policy.methods.value,'PUT');assert.equal(a.message.textContent,'请刷新 <script>bad</script>');assert(a.classes.has('is-error'));assert(f.buttons.every(b=>!b.disabled));});
 await test('HTML login responses are treated as expired session and never applied',async()=>{const f=fixture(),a=f.form('a');f.submit(a);await f.response(200,{},false);assert(a.message.textContent.includes('登录'));assert.equal(a.version.value,'v1');assert(f.buttons.every(b=>!b.disabled));});
 await test('shared checkForm rejection prevents requests',()=>{const f=fixture(),a=f.form('a');f.context.window.checkForm=()=>false;f.submit(a);assert.equal(f.requests.length,0);});
 await test('missing checker and external form action cannot submit',()=>{const f=fixture(),a=f.form('a');delete f.context.window.checkForm;f.submit(a);assert(a.message.textContent.includes('校验'));assert.equal(f.requests.length,0);f.context.window.checkForm=()=>true;a.action='https://other.test/';f.submit(a);assert.equal(f.requests.length,0);});
 await test('reset cancellation preserves form and accepted reset applies without reload',async()=>{const f=fixture(),a=f.form('a'),reset={closest:()=>a};const click=()=>f.events.click({target:{closest:s=>s==='[data-api-reset]'?reset:null},preventDefault(){}});f.context.window.confirm=()=>false;click();assert.equal(f.requests.length,0);f.context.window.confirm=()=>true;click();assert.equal(f.requests[0].options.body.get('_action'),'reset');await f.response(200,{ok:true,version:'v2',entry:{status:'已启用',policy:{enabled:true,methods:[],cors:false,admin:false}}});assert.equal(a.policy.methods.value,'');});
 await test('cleaning historical policy removes both its entry and detail',async()=>{const f=fixture(),a=f.form('a');f.submit(a);await f.response(200,{ok:true,version:'v2',entry:null});assert(a.detail.removed&&a.detail.previousElementSibling.removed);});
 console.log('API JavaScript: '+passed+' passed');
})().catch(e=>{console.error(e);process.exit(1);});
