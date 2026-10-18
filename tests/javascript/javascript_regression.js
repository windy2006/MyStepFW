/* Core JavaScript regression tests: full scripts, strict scope, deterministic timers; no network. */
'use strict';
const fs=require('fs'),path=require('path'),vm=require('vm'),assert=require('assert'),crypto=require('crypto');
const root=path.resolve(__dirname,'../..');
function loadSource(file) {
 const code=fs.readFileSync(file,'utf8');
 if(!process.argv.includes('--minified'))return code;
 const php=process.env.MYSTEP_PHP || '/Applications/ServBay/package/php/7.4/current/bin/php';
 const run=require('child_process').spawnSync(php,[path.join(__dirname,'../dependencies/composer_regression.php'),'--minify'],{input:JSON.stringify({code}),encoding:'utf8'});
 if(run.status!==0)throw Error('Minifier failed: '+run.stderr);
 const data=JSON.parse(run.stdout);assert.equal(data.normal,data.legacy);return data.normal;
}
const source=loadSource(process.env.MYSTEP_JS_SOURCE || path.join(root,'static/js/global.js'));
const formSource=loadSource(process.env.MYSTEP_FORM_SOURCE || path.join(root,'static/js/checkForm.js'));
let passed=0,failed=0;
function test(name,run) {try{run();passed++;console.log('PASS '+name);}catch(error){failed++;console.log('FAIL '+name+': '+error.stack);}}
function environment(options={}) {
 const ready=[],events={},filters=[],warnings=[],alerts=[],timers=new Map();let id=0,now=1000;
 const location={href:'https://cms.example.test:8443/catalog/',protocol:'https:',host:'cms.example.test:8443',hostname:'cms.example.test',port:'8443',pathname:'/catalog/',search:'',origin:'https://cms.example.test:8443'};
 if(options.location)Object.assign(location,options.location);
 function $(value) {if(typeof value==='function'){ready.push(value);return;}return {off(){return this;},on(name,...args){events[name]=args.at(-1);return this;}};}
 $.ajaxPrefilter=callback=>filters.push(callback);
 const document={baseURI:location.href,body:{},documentElement:{},getElementById:()=>null,getElementsByName:()=>[],getElementsByTagName:()=>[],getElementsByClassName:()=>[]};
 const context={location,document,navigator:{userAgent:''},$,URL,URLSearchParams,console:{log(){},warn(value){warnings.push(value);},error(value){warnings.push(value);}},alert:value=>alerts.push(value),setTimeout(callback,ms){timers.set(++id,{callback,time:now+ms});return id;},clearTimeout(key){timers.delete(key);}};
 context.window=context;
 if(!options.noRoot)context.root_web='/sub/';
 if(!options.noSettings){context.setting={debug:true,url_prefix:'/',url_fix:'CMS',app:'CMS'};context.language={};}
 vm.createContext(context);
 context.clockNow=()=>now;
 vm.runInContext((options.strict?'"use strict";\n':'')+'Date.now = () => clockNow();\n'+source+'\nthis.state=global;',context);
 vm.runInContext(formSource,context);
 return {context,events,filters,warnings,alerts,timers,ready,run:code=>vm.runInContext(code,context),tick(ms){now+=ms;let rounds=0;while([...timers.values()].some(x=>x.time<=now)){if(++rounds>1000)throw Error('unbounded timers');for(const [key,timer]of [...timers])if(timer.time<=now){timers.delete(key);timer.callback();}}}};
}
const e=environment({strict:true});
test('loads without a root_web declaration',()=>assert.equal(environment({noRoot:true}).context.state.root,'/'));
for(const mode of ['rewrite','path_info','query_string'])for(const query of ['?catalog&x=1','?x=1',''])test('entry URL prefix '+mode+' '+query,()=>{
 const x=environment({location:{pathname:'/sub/index.php',search:query}}),base=mode==='rewrite'?'/sub/':(mode==='path_info'?'/sub/index.php/':'/sub/index.php?');
 Object.assign(x.context.setting,{path_root:'/sub/',url_prefix:base});
 assert.equal(x.context.routeURL('next'),base+(query.startsWith('?catalog')?'catalog/':'')+'next');
});
test('array detection ignores forged toStringTag',()=>assert.equal(e.run("isArray({[Symbol.toStringTag]:'Array'})"),false));
test('prototypes do not add enumerable array keys',()=>assert.equal(e.run("Object.keys(Array.prototype).includes('append')"),false));
test('preserves native trim and Unicode whitespace',()=>assert.equal(e.run("'\\uFEFF\\u00a0 A \\u00a0'.trim()"),'A'));
test('legacy byte-like length remains stable',()=>assert.equal(e.run("'A中😀'.blen()"),7));
test('printf multi-digit placeholders',()=>assert.equal(e.run("'%1/%10'.printf(1,2,3,4,5,6,7,8,9,10)"),'1/10'));
test('printf literal replacement tokens',()=>assert.equal(e.run("'%1/%2'.printf('$&%2','done')"),'$&%2/done'));
test('self array append terminates',()=>assert.equal(e.run('const xs=[1,2]; xs.append(xs).join(",")'),'1,2,1,2'));
test('date quarter is integral',()=>assert.equal(e.run("new Date(2024,1,29,9,8,7,6).format('q qq SSS')"),'1 01 006'));
test('date replaces repeated tokens',()=>assert.equal(e.run("new Date(2024,10,9).format('yyyy/MM/dd yyyy yy M')"),'2024/11/09 2024 24 11'));
test('invalid date formatting is empty',()=>assert.equal(e.run("new Date(NaN).format('yyyy')"),''));
test('money decimal clamp and literal separators',()=>assert.equal(e.run("(1234.5).formatMoney(2,'$','$&','.')"),'$1$&234.50'));
test('money fractional places cannot desynchronize output',()=>assert.equal(e.run("(1234.5).formatMoney(2.5)"),'$1,234.50'));
test('large money never emits undefined decimals',()=>assert(!e.run('(1e22).formatMoney(2)').includes('undefined')));
test('NaN money has a predictable result',()=>assert.equal(e.run('(NaN).formatMoney(2)'),'$0.00'));
for(const input of ['', 'abc','a'.repeat(55),'a'.repeat(56),'a'.repeat(64),'a'.repeat(1000),'中文','A😀B','é','\x00a'])test('UTF-8 MD5 '+JSON.stringify(input.slice(0,20)),()=>assert.equal(e.context.md5(input),crypto.createHash('md5').update(input).digest('hex')));
test('hashing does not leak working registers',()=>assert(['olda','oldb','oldc','oldd','the_length'].every(key=>!(key in e.context))));
for(const input of ['', 'Hello','中文😀','+/0','é'])test('UTF-8 Base64 roundtrip '+JSON.stringify(input),()=>{
 e.context.btoa=btoa;e.context.atob=atob;
 const encoded=e.context.base64(input,false);assert.equal(encoded,Buffer.from(input).toString('base64'));assert.equal(e.context.base64(encoded,true),input);
});
test('Base64 auto mode recognizes slash and plus',()=>{const input='¿ÿ';const encoded=Buffer.from(input).toString('base64');assert.equal(e.context.base64(encoded),input);});
test('ambiguous non-UTF8 Base64 falls back to encode',()=>assert.equal(e.context.base64('test'),Buffer.from('test').toString('base64')));
test('explicit invalid Base64 decoding rejects',()=>assert.throws(()=>e.context.base64('@@',true)));
test('uniform integer buckets and numeric coercion',()=>{e.run('Math.random=()=>0.49');assert.equal(e.context.rndNum('0','2'),1);e.run('Math.random=()=>0');assert.equal(e.context.rndNum(5,2),2);});
test('random text has requested length for Chinese-only mode',()=>assert.equal(e.context.rndStr(40,'0001').length,40));
test('random text custom-only pool works',()=>assert.equal(e.context.rndStr(4,'0000',['x']),'xxxx'));
test('random text empty alphabet returns empty',()=>assert.equal(e.context.rndStr(4,'0000'),''));
test('random invalid bounds fail promptly',()=>{assert.throws(()=>e.context.rndNum(Infinity,2));assert.throws(()=>e.context.rndStr(Infinity));});
test('watermark defaults do not throw',()=>assert.equal(typeof e.context.watermark('abc'),'string'));
test('watermark trusted HTML tags retain structure',()=>{const value=e.context.watermark('<b>Hello &amp; world</b>',10);assert.equal(value,'<b>Hello &amp; world</b>');});
test('watermark generated copyright is text',()=>{const value=e.context.watermark('\n',10,'<img src=x>');assert(value.includes('&lt;img src=x&gt;'));assert(!value.includes('<img'));});
test('getPath handles ports and bad levels',()=>{assert.equal(e.context.getPath(1),'https://cms.example.test:8443/catalog');assert.equal(e.context.getPath(-2),'https://cms.example.test:8443');});
test('null DOM contexts fall back to document',()=>{assert.deepEqual(e.context.$tag('div',null),[]);assert.deepEqual(e.context.$class('x',null),[]);});
test('beforeunload language guard and event handling',()=>{e.context.state.alert_leave=true;const event={};assert.equal(typeof e.events['beforeunload.msLeave'](event),'string');assert.equal(typeof event.returnValue,'string');});
test('beforeunload uses loaded message',()=>{e.context.language.alert_leave='unsaved';e.context.state.alert_leave=true;assert.equal(e.events['beforeunload.msLeave']({}),'unsaved');});
test('debug error reports do not suppress native errors',()=>assert.equal(e.context.reportError('failure','x.js',2,3),false));
test('release mode does not pop up errors',()=>{e.context.setting.debug=false;const before=e.alerts.length;e.context.reportError('failure');assert.equal(e.alerts.length,before);});
test('cross-site Ajax never gets automatic credentials',()=>{const request={url:'https://other.test/x'};e.filters[0](request);assert.equal(request.xhrFields,undefined);assert.equal(request.crossDomain,undefined);});
test('trusted cross-site API gets credentials',()=>{e.context.msSecurity={origins:['https://api.example.test']};const request={url:'https://api.example.test/x'};e.filters[0](request);assert.equal(request.xhrFields.withCredentials,true);});
test('explicit Ajax credential policy is preserved',()=>{const request={url:'/x',xhrFields:{withCredentials:false}};e.filters[0](request);assert.equal(request.xhrFields.withCredentials,false);});
test('same-origin Ajax uses native crossDomain detection',()=>{const request={url:'/x'};e.filters[0](request);assert.equal(request.xhrFields.withCredentials,true);assert.equal(request.crossDomain,undefined);});
test('strict callers can await configuration with explicit callbacks',()=>{const x=environment({noSettings:true,strict:true});let seen;assert.equal(x.context.checkSetting(10,(value)=>seen=value,['ok']),false);x.context.setting={};x.context.language={};x.tick(10);assert.equal(seen,'ok');assert.equal(x.timers.size,0);});
test('same callback can have independent parameter sets',()=>{const x=environment({noSettings:true}),seen=[],f=value=>seen.push(value);x.context.checkNrun(f,[1]);x.context.checkNrun(f,[2]);x.context.setting={};x.context.language={};x.tick(100);assert.deepEqual(seen,[1,2]);});
test('readiness cancellation releases its timer',()=>{const x=environment({noSettings:true});const cancel=x.context.checkNrun(()=>assert.fail('cancelled callback'));cancel();assert.equal(x.timers.size,0);});
test('readiness timeout is finite',()=>{const x=environment({noSettings:true});x.context.checkNrun(()=>assert.fail('timeout callback'),[],10);x.tick(11);assert.equal(x.timers.size,0);assert.equal(x.warnings.length,1);});
test('boolean checkSetting alone never starts polling',()=>{const x=environment({noSettings:true});assert.equal(x.context.checkSetting(),false);assert.equal(x.timers.size,0);});
test('readiness does not commandeer anchor state',()=>{const x=environment({noSettings:true});x.context.checkNrun(()=>{});assert.equal(x.context.state.timer,undefined);assert.equal(x.context.state.anchorAnimating,undefined);});
const validators=[
 ['digital','0',true],['digital','0012',true],['digital','+12',false],['digital','1.2',false],
 ['number','1e3',true],['number','-1.5',true],['number','NaN',false],['number','Infinity',false],
 ['alpha','中文',false],['word','Hello world-1.0',true],['word','中文',false],['name','A.B',false],
 ['time','9:05',true],['date','2024/2/29 12:30:00',true],['tel','12345',false],['fax','+86 138-0013-8000',true],
 ['false','false',true],['false','https://example.com',false],
 ['_','https://www.baidu.com/s',true],['_','',true],['_','/local/path?x=1',true],['_','任意文本',true],['^_$','text',false],['^_$','_',true],
 ['email','user+tag@example.technology',true],['email','a..b@example.com',false],['email','user@-bad.test',false],['email','a@example.com/path',false],['email','a@中文.中国',true],
 ['url','http://localhost:8080/a?x=1',true],['url','https://[::1]:443/',true],['url','https://example.com bad',false],['url','http://example.com:99999',false],['url','javascript:alert(1)',false],
 ['date','2024-02-29',true],['date','2023-02-29',false],['date','2024-04-31',false],['date','2024-01-01 24:00:00',false],['date','0001-01-01',true],['date','2024-01-01T12:30',true],
 ['time','23:59:59',true],['time','12:00:60',false],['number','.5',true],['number','1e309',false],['digital','-1',false],['alpha','item_1',true],['name','名字A-1',true],['tel','+86 138-0013-8000',true],
 ['^\\D+$','abc',true],['^\\D+$','123',false],['^[A-Z_]+$','AB_CD',true],['/^[A-Z]+$/','abc',false],['[','any',false]
];
for(const [rule,value,valid]of validators)test('validator '+rule+' '+value,()=>assert.equal(e.context.formCheckValue(value,rule)===null,valid));
function legacyText(value,attrs={}) {
 const x=environment();
 x.context.highlightIt=()=>{};
 x.context.$=element=>({is:selector=>selector===':disabled' && !!element.disabled});
 const attributes=Object.assign({need:'_',len:'100'},attrs);
 const element={tagName:'INPUT',type:'text',value,required:!!attributes.required,willValidate:true,
  getAttribute:name=>Object.prototype.hasOwnProperty.call(attributes,name)?String(attributes[name]):null,
  checkValidity(){return !this.required || String(this.value).trim()!=='';},validity:{valueMissing:value===''}};
 return x.context.checkForm({elements:[element],noValidate:false});
}
test('legacy free text accepts the badReq default URL',()=>assert.equal(legacyText('https://www.baidu.com/s'),true));
test('legacy free text accepts an empty value',()=>assert.equal(legacyText(''),true));
test('legacy free text still enforces maximum length',()=>assert.equal(legacyText('https://www.baidu.com/s',{len:'10'}),false));
test('legacy free text still enforces native required',()=>assert.equal(legacyText('',{required:true}),false));
test('legacy free text still enforces nonempty minimum length',()=>assert.equal(legacyText('ab',{len:'3-100'}),false));
test('explicit underscore regex keeps its original meaning',()=>assert.equal(legacyText('text',{need:'^_$'}),false));
for(const rule of ['email','url','digital','number','alpha','word','name','date','time','tel','fax']) {
 test('bare built-in rejects empty '+rule,()=>assert.equal(legacyText('',{need:rule}),false));
 for(const optional of [rule+'_','_'+rule,'_'+rule+'_'])test('optional built-in permits empty '+optional,()=>assert.equal(legacyText('',{need:optional}),true));
 test('native required overrides optional '+rule,()=>assert.equal(legacyText('',{need:rule+'_',required:true}),false));
}
test('optional URL still rejects a nonempty relative path',()=>assert.equal(legacyText('/local/path',{need:'_url_'}),false));
test('built-in names ignore case',()=>assert.equal(legacyText('user+tag@example.com',{need:'EMAIL'}),true));
test('custom regex controls its own empty value semantics',()=>{assert.equal(legacyText('',{need:'^.*$'}),true);assert.equal(legacyText('',{need:'^.+$'}),false);});
test('language fallback works without prototype helpers',()=>{const x=environment({noSettings:true});assert.equal(x.context.formCheckMessage('checkform_lenth_limit1',['$&']), '请将字符数限制在 $& 个字符以内！');});
(async()=>{try{const x=environment();let done=false;const pending=x.context.sleep(50).then(()=>{done=true;});assert.equal(done,false);x.tick(50);await pending;assert.equal(done,true);passed++;console.log('PASS sleep yields to the event loop');}catch(error){failed++;console.log('FAIL async sleep '+error.stack);}try{const x=environment();x.context.copy=()=>false;x.context.navigator.clipboard={writeText(){throw Error('denied');}};assert.equal(await x.context.copyAsync('plain'),false);passed++;console.log('PASS clipboard sync rejection falls back without throwing');}catch(error){failed++;console.log('FAIL clipboard rejection '+error.stack);}
console.log(`Passed: ${passed}; Failed: ${failed}`);process.exitCode=failed?1:0;})();
