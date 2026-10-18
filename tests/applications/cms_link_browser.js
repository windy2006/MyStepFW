/* Execute the real shared URL helpers against real rendered CMS templates; no browser/network. */
'use strict';
const fs=require('fs'), vm=require('vm'), assert=require('assert'), path=require('path'), cp=require('child_process');
const root=path.resolve(__dirname,'../..'), source=fs.readFileSync(path.join(root,'static/js/global.js'),'utf8');
const helpers=source.slice(source.indexOf('function queryURL('),source.indexOf('/** 定位页面内锚点'));
const php=process.env.MYSTEP_PHP || '/Applications/ServBay/package/php/7.4/current/bin/php';
let passed=0;
function test(name,run) { try {run();passed++;} catch(error) {console.error('FAIL '+name+': '+error.stack);process.exitCode=1;} }
function sandbox(setting,prefix) {
    const elements=[],body={};
    function $(query) {
        if(typeof query==='object') return {
            attr(name,value) {if(arguments.length===2) {query.attrs[name]=value;return this;} return query.attrs[name];},
            off() {return this;}, on(name,callback) {query.handler=callback;return this;}, serializeArray() {return query.fields || [];}
        };
        const [_,tag,attribute]=query.match(/^(\w+)\[(\w+)\]$/);
        return {each(run) {elements.filter(item=>item.tag===tag && attribute in item.attrs).forEach(item=>run.call(item));}};
    }
    const context={setting,global:{root_fix:prefix},location:{},window:{document:{body}},URLSearchParams,checkSetting:()=>true,$};
    vm.createContext(context);vm.runInContext(helpers,context);
    return {context,elements,route:context.routeURL,query:context.queryURL};
}
function decode(s) {return s.replace(/&amp;/g,'&').replace(/&quot;/g,'"').replace(/&#0?39;|&apos;/g,"'").replace(/&lt;/g,'<').replace(/&gt;/g,'>');}
function nodes(html) {
    const out=[]; for(const match of html.matchAll(/<(a|form|img)\b([^>]*(?:<!--.*?-->[^>]*)*)>/gs)) {
        const attrs={};for(const a of match[2].matchAll(/([\w-]+)\s*=\s*"([^"]*)"/g))attrs[a[1]]=decode(a[2]);
        if(/\bkeep-url(?:\s|\/|$)/.test(match[2]))attrs['keep-url']='';
        out.push({tag:match[1],attrs,method:attrs.method || 'get'});
    } return out;
}
function parseRoute(url) {
    const u=new URL(url,'https://cms.example.test/');let route=u.pathname;
    if(/\/index\.php$/.test(route)) {const parts=u.search.slice(1).split('&');route=parts.shift();return {route:'/'+route.replace(/^\//,''),params:new URLSearchParams(parts.join('&'))};}
    route=route.replace(/\/index\.php(?=\/)/,'');return {route,params:u.searchParams};
}
for(const base of ['/','/sub/']) for(const bound of ['0','1']) for(const mode of ['rewrite','path_info','query_string']) {
    const label=`${base} ${bound==='1'?'bound':'unbound'} ${mode}`;
    const data=JSON.parse(cp.execFileSync(php,[path.join(__dirname,'cms_link_regression.php'),'--fixtures'],{env:{...process.env,CMS_LINK_BASE:base,CMS_LINK_BOUND:bound,CMS_LINK_MODE:mode},encoding:'utf8'}));
    for(const fixture of data.fixtures) {
        const s=sandbox(data.setting,fixture.prefix);s.elements.push(...nodes(fixture.html));s.context.setURL();
        const before=JSON.stringify(s.elements.map(x=>x.attrs));s.context.setURL();
        test(label+' '+fixture.name+' idempotence',()=>assert.equal(JSON.stringify(s.elements.map(x=>x.attrs)),before));
        test(label+' '+fixture.name+' no route contains query delimiters',()=>{
            for(const el of s.elements) {const key={a:'href',form:'action',img:'src'}[el.tag],url=el.attrs[key];
                if(!url || /^(?:[a-z][a-z0-9+.-]*:|\/\/|#)/i.test(url) || el.attrs['keep-url']!==undefined)continue;
                const parsed=parseRoute(url);assert(!/[&?]/.test(parsed.route),url);
                assert(!/index\.php(?:admin|CMS|api)/.test(url),url);
            }
        });
        test(label+' '+fixture.name+' resource base paths',()=>{
            assert(!/(?:["']|url\(["']?)\/(?:static|vendor|app\/CMS)\//.test(base==='/sub/'?fixture.html:''),fixture.name);
        });
        if(fixture.name==='frame') test(label+' frame homepage exists before asynchronous settings load',()=>{
            const iframe=fixture.html.match(/<iframe\b[^>]*\bsrc="([^"]+)"/i);
            assert(iframe,'missing homepage iframe');
            const url=decode(iframe[1]), parsed=parseRoute(url);
            assert.equal(parsed.route,(mode==='query_string'?'/':base)+(bound==='1'?'':'CMS/')+'admin_cms/info/',url);
            assert(!fixture.html.includes("routeURL('info/'"),'startup must not require asynchronous setting');
            const inline=fixture.html.match(/<script[^>]*>([\s\S]*?)<\/script>/i);
            assert(inline,'missing frame script');new vm.Script(inline[1]);
        });
        if(['art_content_list','func_link_list','art_custom_list'].includes(fixture.name)) test(label+' '+fixture.name+' add target',()=>{
            const el=s.elements.find(x=>x.tag==='a' && /\/add\/?[?&]/.test(x.attrs.href));assert(el,'missing add link');
            const target=parseRoute(el.attrs.href);assert(target.route.includes('/admin_cms/'+({'art_content_list':'article/content','func_link_list':'function/link','art_custom_list':'article/custom'}[fixture.name])+'/add'),el.attrs.href);assert.equal(target.params.get('web_id'),'1');
        });
        if(fixture.name==='func_template_list') test(label+' template file query roundtrip',()=>{
            const el=s.elements.find(x=>x.tag==='a' && /\/edit[?&]/.test(x.attrs.href));const parsed=parseRoute(el.attrs.href);
            assert.equal(parsed.params.get('idx'),'A+B &中文?#');assert.equal(parsed.params.get('file'),'block/a+b &中文.tpl');
        });
        if(fixture.name==='default/main' && mode==='query_string') test(label+' native GET form retains route',()=>{
            const form=s.elements.find(x=>x.tag==='form');assert(form.handler);form.fields=[{name:'k',value:'A+B &中文?#'}];let blocked=false;form.handler.call(form,{preventDefault(){blocked=true;},isDefaultPrevented(){return false;}});
            assert(blocked);const parsed=parseRoute(s.context.location.href);assert(parsed.route.endsWith('/search'));assert.equal(parsed.params.get('k'),'A+B &中文?#');
        });
    }
    const s=sandbox(data.setting,data.fixtures[0].prefix);
    test(label+' direct search navigation encoding',()=>{
        const url=s.query(data.fixtures[0].prefix,{keyword:'A+B &中文?#',cat_id:'7',web_id:'2'}),parsed=parseRoute(url);
        assert(parsed.route.includes('/admin_cms/article/content/'));assert.equal(parsed.params.get('keyword'),'A+B &中文?#');assert.equal(parsed.params.get('cat_id'),'7');
    });
    test(label+' values containing slashes and colons stay intact',()=>{
        const url=s.route('edit?next=https://other.test/a//b#section');assert(url.includes('next=https://other.test/a//b#section'),url);
        for(const url of ['https://other.test/a//b?x=1','//other.test/a','mailto:one@example.test','#anchor',base+'static/a.png'])assert.equal(s.route(url),url);
    });
}
test('query update preserves fragment and deletes stale fields',()=>{
    const s=sandbox({},'');assert.equal(s.query('/index.php?/a/&page=1#part',{page:null,k:'A+B &'}),'/index.php?/a/&k=A%2BB+%26#part');
});
console.log(`Passed: ${passed}; Failed: ${process.exitCode?1:0}`);
