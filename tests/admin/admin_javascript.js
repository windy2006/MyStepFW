/* 新后台交互回归：真实脚本，隔离DOM替身；不请求网络或提交业务数据。 */
'use strict';
const fs=require('fs'),path=require('path'),vm=require('vm'),assert=require('assert');
const source=process.env.MYSTEP_ADMIN_STAGE||path.resolve(__dirname,'../..');
const script=fs.readFileSync(path.join(source,'app/Admin/asset/function.js'),'utf8');
let passed=0;
function test(name,run){run();passed++;console.log('PASS '+name);}
function fixture(href='https://fixture.test/sub/Admin/setting?target=Demo',ready='complete'){
 const events={},anchors=[],tables={};
 const document={readyState:ready,addEventListener(name,fn){events[name]=fn;},querySelectorAll(){return anchors;},getElementById(id){return tables[id];},querySelector(){return null;}};
 const alerts=[];
 const context={document,location:{href},URL,Date,Array,window:{confirm:()=>true,checkForm:form=>form.fixtureValid,alert:message=>alerts.push(message)}};
 vm.runInNewContext(script,context);
 return {events,anchors,tables,context,alerts};
}
function submit(f,options={}){
 const button={disabled:false};let prevented=false;
 const form={matches:()=>!options.outside,getAttribute:()=>options.confirm||'',fixtureValid:options.valid!==false,checkValidity(){throw Error('Native validation must be delegated to checkForm');},querySelectorAll:()=>[button]};
 f.events.submit({target:form,preventDefault(){prevented=true;}});
 return {button,prevented};
}
function row(body,n,value='value'){
 const inputs=[{name:'rows['+n+'][path]',value},{name:'rows['+n+'][ext]',value:'.php'}];
 const r={parentNode:body,inputs,querySelectorAll(selector){return selector==='[data-remove-row]'||selector==='[data-row-existing]'?[]:inputs;},cloneNode(){return row(body,n);},remove(){body.rows.splice(body.rows.indexOf(r),1);},closest(selector){return selector==='tr'?r:null;}};
 return r;
}
function click(f,selector,node){f.events.click({target:{closest:s=>s===selector?node:null}});}
test('unrelated form is left untouched',()=>{const r=submit(fixture(),{outside:true});assert(!r.button.disabled&&!r.prevented);});
test('confirmation cancellation prevents submit and preserves button',()=>{const f=fixture();f.context.window.confirm=()=>false;const r=submit(f,{confirm:'Delete?'});assert(r.prevented&&!r.button.disabled);});
test('shared checker handles invalid native constraints',()=>{const r=submit(fixture(),{valid:false});assert(r.prevented&&!r.button.disabled);});
test('shared checkForm rejection preserves form',()=>{const f=fixture();f.context.window.checkForm=()=>false;const r=submit(f);assert(r.prevented&&!r.button.disabled);});
test('valid submit disables buttons after validation',()=>{const r=submit(fixture());assert(!r.prevented&&r.button.disabled);});
test('missing shared checker blocks submit and asks for reload',()=>{const f=fixture();delete f.context.window.checkForm;const r=submit(f);assert(r.prevented&&!r.button.disabled);assert.equal(f.alerts.length,1);assert(f.alerts[0].includes('刷新'));});
test('captcha refresh preserves route parameters',()=>{const f=fixture(),img={src:'https://fixture.test/sub/index.php?/captcha/&size=4'};click(f,'[data-captcha]',img);const url=new URL(img.src);assert(url.searchParams.has('_'));assert(img.src.includes('/captcha/'));assert.equal(url.searchParams.get('size'),'4');});
test('row additions clear values and use consecutive indexes',()=>{const f=fixture(),body={rows:[],appendChild(r){this.rows.push(r);}};body.rows=[row(body,0),row(body,1)];f.tables.editor={tBodies:[body]};click(f,'[data-add-row]',{dataset:{addRow:'editor'}});assert.equal(body.rows.length,3);assert.equal(body.rows[2].inputs[0].name,'rows[2][path]');assert(body.rows[2].inputs.every(x=>x.value===''));});
test('class additions restore defaults and remove saved-row-only actions',()=>{
 const f=fixture(),body={rows:[],appendChild(r){this.rows.push(r);}};const original=row(body,0);let removed=false;
 original.cloneNode=()=>{const r=row(body,0);r.inputs[1].dataset={defaultValue:'.php,.class.php'};r.inputs.push({name:'rows[0][idx]',value:'old',dataset:{defaultValue:'array ()'}});r.button={disabled:true};r.querySelectorAll=s=>s==='[data-remove-row]'?[r.button]:s==='[data-row-existing]'?[{remove(){removed=true;}}]:r.inputs;return r;};
 body.rows=[original];f.tables.editor={tBodies:[body]};click(f,'[data-add-row]',{dataset:{addRow:'editor'}});
 assert.equal(body.rows[1].inputs[1].value,'.php,.class.php');assert.equal(body.rows[1].inputs[2].value,'array ()');assert(!body.rows[1].button.disabled);assert(removed);
});
test('remove then add never duplicates form row indexes',()=>{const f=fixture(),body={rows:[],appendChild(r){this.rows.push(r);}};body.rows=[row(body,0),row(body,1),row(body,2)];f.tables.editor={tBodies:[body]};click(f,'[data-remove-row]',body.rows[1]);assert.equal(body.rows[1].inputs[0].name,'rows[1][path]');click(f,'[data-add-row]',{dataset:{addRow:'editor'}});assert.deepEqual(body.rows.map(r=>r.inputs[0].name),['rows[0][path]','rows[1][path]','rows[2][path]']);});
test('empty domain tables add rows from their template',()=>{
 const f=fixture(),body={rows:[],dataset:{allowEmpty:'true'},appendChild(r){this.rows.push(r);}};
 f.tables['domain-rows']={tBodies:[body]};f.tables['domain-row-template']={content:{firstElementChild:row(body,0)}};
 const button={dataset:{addRow:'domain-rows',rowTemplate:'domain-row-template'}};
 click(f,'[data-add-row]',button);assert.equal(body.rows.length,1);assert.equal(body.rows[0].inputs[0].name,'rows[0][path]');assert(body.rows[0].inputs.every(x=>x.value===''));
 click(f,'[data-remove-row]',body.rows[0]);assert.equal(body.rows.length,0);
 click(f,'[data-add-row]',button);assert.equal(body.rows.length,1);
});
test('domain tables can remove their final row',()=>{const f=fixture(),body={rows:[],dataset:{allowEmpty:'true'}};body.rows=[row(body,0)];click(f,'[data-remove-row]',body.rows[0]);assert.equal(body.rows.length,0);});
test('last editable row remains available',()=>{const f=fixture(),body={rows:[]};body.rows=[row(body,0)];click(f,'[data-remove-row]',body.rows[0]);assert.equal(body.rows.length,1);});
test('local table filter is case insensitive and can reset',()=>{const f=fixture(),rows=[{textContent:'MyCache'},{textContent:'Router'}],target={value:'cache',matches:()=>true,closest:()=>({querySelectorAll:()=>rows})};f.events.input({target});assert(!rows[0].hidden&&rows[1].hidden);target.value='';f.events.input({target});assert(rows.every(r=>!r.hidden));});
for(const [href,nav]of [['https://fixture.test/sub/Admin/setting?target=Demo','/sub/Admin/setting'],['https://fixture.test/sub/index.php?/Admin/setting&target=Demo','/sub/index.php?/Admin/setting']])test('navigation ignores filter query '+nav,()=>{const f=fixture(href,'loading');let current='';f.anchors.push({href:nav,setAttribute(k,v){current=v;}});f.events.DOMContentLoaded();assert.equal(current,'page');});
function navigationFixture() {
 const f=fixture(),classes=new Set(),attributes={'aria-expanded':'false'};
 const panel={classList:{contains:c=>classes.has(c),toggle(c,on){if(on)classes.add(c);else classes.delete(c);}},contains:target=>!!target.inside};
 const toggle={getAttribute:k=>attributes[k],setAttribute(k,v){attributes[k]=v;},focus(){this.focused=true;}};
 f.tables['adm-navigation']=panel;f.context.document.querySelector=()=>toggle;
 return {f,panel,toggle,attributes};
}
test('mobile menu opens and closes with synchronized aria state',()=>{const {f,panel,toggle,attributes}=navigationFixture();click(f,'.adm-nav-toggle',toggle);assert(panel.classList.contains('is-open'));assert.equal(attributes['aria-expanded'],'true');click(f,'.adm-nav-toggle',toggle);assert(!panel.classList.contains('is-open'));assert.equal(attributes['aria-expanded'],'false');});
test('Escape closes the menu and restores toggle focus',()=>{const {f,panel,toggle}=navigationFixture();click(f,'.adm-nav-toggle',toggle);f.events.keydown({key:'Escape'});assert(!panel.classList.contains('is-open'));assert(toggle.focused);});
test('outside click closes an expanded navigation',()=>{const {f,panel,toggle}=navigationFixture();click(f,'.adm-nav-toggle',toggle);f.events.click({target:{closest:()=>null}});assert(!panel.classList.contains('is-open'));});
test('clicking inside navigation keeps it open until selecting a link',()=>{const {f,panel,toggle}=navigationFixture();click(f,'.adm-nav-toggle',toggle);f.events.click({target:{inside:true,closest:()=>null}});assert(panel.classList.contains('is-open'));f.events.click({target:{inside:true,closest:s=>s==='a[href]'?{}:null}});assert(!panel.classList.contains('is-open'));});
test('desktop breakpoint resets a previously expanded mobile menu',()=>{const {f,panel,toggle,attributes}=navigationFixture();let changed;const media={matches:false,addEventListener(name,fn){changed=fn;}};f.context.window.matchMedia=()=>media;f.context.document.readyState='loading';
 // Load fresh script so its real ready handler observes the matchMedia implementation.
 vm.runInNewContext(script,f.context);f.events.DOMContentLoaded();click(f,'.adm-nav-toggle',toggle);media.matches=true;changed();assert(!panel.classList.contains('is-open'));assert.equal(attributes['aria-expanded'],'false');});
console.log('Admin JavaScript: '+passed+' passed');
