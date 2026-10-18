// Execute the actual backup form handlers without submitting forms or contacting servers.
'use strict';
const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict'),path=require('node:path');
const root=path.resolve(__dirname,'../..'),tpl=fs.readFileSync(path.join(root,'plugin/myBackup/template/myBackup.tpl'),'utf8');
const script=tpl.match(/<script[^>]*>([\s\S]*?)<\/script>/)[1];
let passed=0;
function test(name,run){run();passed++;console.log('PASS '+name);}
function fixture(mode,confirmed,valid){
    const handlers={},events={checks:0,confirms:0,loading:[]};
    function $(selector){if(typeof selector==='function'){selector();return;}return {
        submit(fn){handlers.submit=fn;return this;},
        change(fn){handlers[String(selector)]=fn;return this;},
        val(){return mode;},prop(){return this;},next(){return this;},text(){return this;}
    };}
    const context=vm.createContext({$,URLSearchParams,confirm(){events.confirms++;return confirmed;},checkForm(){events.checks++;return valid;},loadingShow(...args){events.loading.push(args);},location:{href:'https://example.test/sub/index.php?myBackup/&db=old#x'}});
    const helpers=fs.readFileSync(path.join(root,'static/js/global.js'),'utf8');
    vm.runInContext(helpers.slice(helpers.indexOf('function queryURL('),helpers.indexOf('function routeURL(')),context);
    vm.runInContext(script,context);return {handlers,events,context};
}
test('cancelled SQL import confirmation blocks submission',()=>{const f=fixture('import',false,true);assert.equal(f.handlers.submit.call({}),false);assert.equal(f.events.checks,0);assert.equal(f.events.loading.length,0);});
test('invalid import form blocks submission without overlay',()=>{const f=fixture('import',true,false);assert.equal(f.handlers.submit.call({}),false);assert.equal(f.events.loading.length,0);});
test('confirmed valid import shows overlay explicitly',()=>{const f=fixture('import',true,true);assert.equal(f.handlers.submit.call({}),true);assert.equal(f.events.loading.length,1);assert.equal(f.events.loading[0][1],true);});
test('export does not leave an overlay on the page',()=>{const f=fixture('export',false,true);assert.equal(f.handlers.submit.call({}),true);assert.equal(f.events.confirms,0);assert.equal(f.events.loading.length,0);});
test('database selector preserves query-string route and fragment',()=>{const f=fixture('',true,true);f.handlers['select[name=db]'].call({value:'db&name'});assert.equal(f.context.location.href,'https://example.test/sub/index.php?myBackup/&db=db%26name#x');});
test('remote difference links retain exactly one deployment prefix',()=>{
    const manager=fs.readFileSync(path.join(root,'plugin/manager/template/manager.tpl'),'utf8');
    const assignment=manager.match(/location\.href = new URL\(info\.link, [^;]+;/)[0];
    for(const [base,link,expected] of [['https://updates.test','/api/file','https://updates.test/api/file'],['https://updates.test/sub','/sub/index.php?api/plugin_manager/artifact/&name=x','https://updates.test/sub/index.php?api/plugin_manager/artifact/&name=x']]){
        const context={URL,info:{link},location:{}};vm.runInNewContext(assignment.replace('<!--update_url-->',base),context);assert.equal(context.location.href,expected);
    }
});

// Run the real manager template with controlled Ajax responses; no update request leaves this process.
function managerFixture(version,download,difference){
    const events={text:{},alerts:[],loading:[],posts:0,reloads:0,dialogs:[]},handlers={},ready=[];
    let $;
    const escape=value=>String(value).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    $=selector=>{
        if(typeof selector==='function'){ready.push(selector);return;}
        let value='';return {
            click(fn){handlers[selector]=fn;return this;},
            text(text){value=text;events.text[selector]=text;return this;},
            html(){return escape(value);},appendTo(target){events.text[target]=value;return this;},removeClass(){return this;},addClass(value){events.feedbackClass=value;return this;}
        };
    };
    $.vendor=()=>{};
    $.get=(url,callback)=>{
        const result=url.includes('/check/version')?version:(url.includes('/download?')?download:difference);
        if(result!==undefined && result!==null)callback(result);
        return {fail(fn){if(result===null)fn({status:503});return this;}};
    };
    $.post=()=>{events.posts++;throw new Error('Unexpected remote request');};
    const location={href:'',replace(url){this.href=url;}};const top={location:{reload(){events.reloads++;}}};
    const context=vm.createContext({$,jQuery:$,URL,location,window:{top,location},console:{log(){}},loadingShow(value){events.loading.push(value);},alert(value){events.alerts.push(value);},alert_org(value){events.alerts.push(value);},confirm(...args){events.dialogs.push(args);}});
    const template=fs.readFileSync(path.join(root,'plugin/manager/template/manager.tpl'),'utf8');
    const code=template.match(/<script[^>]*>([\s\S]*?)<\/script>/)[1].replaceAll('<!--url_prefix-->','/sub/').replaceAll('<!--update_url-->','https://updates.test/sub');
    vm.runInContext(code,context);ready.forEach(fn=>fn());return {events,context,location,handlers};
}
test('manager distinguishes latest version from failed version response',()=>{
    const latest=managerFixture({version:'',detail:[]});assert.equal(latest.events.text['#info'],'已是最新版本');
    const failed=managerFixture({error:1,version:''});assert.match(failed.events.text['#info'],/检查失败/);
});
test('manager reports network failure during version check',()=>{const f=managerFixture(null);assert.match(f.events.text['#info'],/检查失败/);});
test('manager update label uses text insertion for server version',()=>{const f=managerFixture({version:'2.0<img>',detail:{}});assert.equal(f.events.text['#info'],'点击更新（v2.0<img>）');});
test('manager failed upgrade remains on current page',()=>{const f=managerFixture({version:''},{error:1,info:'failed',link:''});f.context.applyUpdate(1);assert.equal(f.events.reloads,0);assert.equal(f.location.href,'');assert.equal(f.events.alerts[0],'failed');});
test('manager keeps permission feedback visible as text without following a download link',()=>{const info='Update permission check failed: lib/<blocked>.php; 请授权后重试。';const f=managerFixture({version:''},{error:1,info,link:'/should-not-download'});f.context.applyUpdate(1);assert.equal(f.events.text['#manager-feedback'],info);assert.equal(f.events.feedbackClass,'alert-danger');assert.equal(f.location.href,'');assert.equal(f.events.reloads,0);});
test('manager download follows authenticated archive link',()=>{const f=managerFixture({version:''},{error:0,info:'download',link:'/sub/manager/file/?name=x'});f.context.applyUpdate(0);assert.equal(f.location.href,'/sub/manager/file/?name=x');assert.equal(f.events.reloads,0);});
test('manager successful automatic update reloads page',()=>{const f=managerFixture({version:''},{error:0,info:'done',link:''});f.context.applyUpdate(1);assert.equal(f.events.reloads,1);});
test('manager upgrade network failure dismisses loading overlay',()=>{const f=managerFixture({version:''},null);f.context.applyUpdate(1);assert.equal(f.events.loading.at(-1),undefined);assert.equal(f.events.reloads,0);});
test('manager escapes server descriptions before opening HTML dialog',()=>{const f=managerFixture({version:'2.0',detail:{'2.0':'<img src=x onerror=x>\nnext'}});f.context.checkUpdate();const dialog=f.events.dialogs[0][0];assert.ok(dialog.includes('&lt;img'));assert.ok(!dialog.includes('<img'));assert.ok(dialog.includes('<br />'));});
test('empty difference list stops without contacting master packaging endpoint',()=>{const f=managerFixture({version:''},undefined,{new:[],mod:[],miss:[]});f.handlers['#link_6']();assert.equal(f.events.posts,0);assert.equal(f.events.loading.at(-1),undefined);});


function installerFixture(){
    const source=fs.readFileSync(path.join(root,'plugin/manager/install.php'),'utf8');
    const start=source.indexOf("const installToken =");
    const script=source.slice(start,source.indexOf('</script>',start)).replace(/\{\$lng\[\d+\]\}/g,'fixture-label').replaceAll('{$svr}','fixture-server').replaceAll('{$csrf}','a'.repeat(48));
    const events={cleared:[],shown:0,text:'',submitted:null,alerts:[],scheduled:0},handlers={};
    function $(selector){return {click(fn){handlers[selector==='#jump'?'finish':'click']=fn;return this;},hide(){return this;},show(){events.shown++;return this;},removeClass(){return this;},text(value){events.text=value;return this;},html(){return this;},scrollTop(){return this;},prop(){return 1;}};}
    $.post=(url,data,fn)=>{handlers.success=fn;return {fail(fn){handlers.failure=fn;return this;}};};
    $.get=(url,fn)=>fn('fixture-label: already unpacked');
    const document={body:{appendChild(){}},createElement(tag){return {children:[],appendChild(child){this.children.push(child);},submit(){events.submitted=this;}};}};
    const context=vm.createContext({$,document,navigator:{language:'en'},alert(value){events.alerts.push(value);},setTimeout(){events.scheduled++;},setInterval(fn){handlers.poll=fn;return 7;},clearInterval(value){events.cleared.push(value);}});
    vm.runInContext(script,context);return {context,events,handlers};
}
test('standalone installer failure stops polling and restores retry button',()=>{const f=installerFixture();f.handlers.click.call({});f.handlers.failure({responseText:'invalid package'});assert.deepEqual(f.events.cleared,[7]);assert.equal(f.events.shown,1);assert.equal(f.events.text,'invalid package');});
test('standalone installer finish submits the session token using POST',()=>{const f=installerFixture();f.context.finishInstall();assert.equal(f.events.submitted.method,'POST');assert.equal(f.events.submitted.action,'install.php');assert.deepEqual(f.events.submitted.children.map(v=>[v.name,v.value]),[['step','3'],['csrf','a'.repeat(48)]]);});
test('installer progress text cannot finish an installation before the POST succeeds',()=>{const f=installerFixture();f.handlers.click.call({});f.handlers.poll();assert.equal(f.events.alerts.length,0);assert.equal(f.events.scheduled,0);assert.equal(f.handlers.finish,undefined);f.handlers.success('done!');assert.equal(f.events.scheduled,1);assert.equal(typeof f.handlers.finish,'function');});
test('installer unexpected success response displays the reason and allows retry',()=>{const f=installerFixture();f.handlers.click.call({});f.handlers.success('config/route.php is not writable');assert.equal(f.events.text,'config/route.php is not writable');assert.equal(f.events.shown,1);assert.equal(f.events.scheduled,0);});

test('badReq jump handlers retain layout filters and the query-string route',()=>{
    const template=fs.readFileSync(path.join(root,'plugin/badReq/template/block_pages.tpl'),'utf8');
    const code=template.match(/<script[^>]*>([\s\S]*?)<\/script>/)[1].replace('<!--badreq_list_url_json-->',JSON.stringify('/sub/index.php?badReq/&mode=ip&keyword=a%26b&_plugin_app=Admin'));
    const handlers={};function $(selector){if(typeof selector==='function'){selector();return;}return {keypress(fn){handlers.keypress=fn;},click(fn){handlers.click=fn;},val(){return '3';}};}
    const context=vm.createContext({$,URLSearchParams,location:{href:''}});
    const helpers=fs.readFileSync(path.join(root,'static/js/global.js'),'utf8');vm.runInContext(helpers.slice(helpers.indexOf('function queryURL('),helpers.indexOf('function routeURL(')),context);vm.runInContext(code,context);
    handlers.keypress.call({value:'2'},{keyCode:13});assert.equal(context.location.href,'/sub/index.php?badReq/&mode=ip&keyword=a%26b&_plugin_app=Admin&page=2');
    handlers.click();assert.equal(context.location.href,'/sub/index.php?badReq/&mode=ip&keyword=a%26b&_plugin_app=Admin&page=3');
});
test('backup database changes preserve explicit Admin layout',()=>{
    const f=fixture('',true,true);f.context.location.href='https://example.test/sub/index.php?myBackup/&db=old&_plugin_app=Admin';f.handlers['select[name=db]'].call({value:'other'});assert.equal(f.context.location.href,'https://example.test/sub/index.php?myBackup/&_plugin_app=Admin&db=other');
});
console.log('Passed: '+passed+'; Failed: 0');
