/**
 * 文件：tests/admin/guide_javascript.js (new)
 * 功能说明：真实指南模板及真实guide.js的DOM逻辑回归；模拟网络和History，不代表视觉浏览器验收。
 * 方法目录：fixture、click、drain、respond、page、test；参数见各声明。
 * 使用说明：Node及开发环境linkedom；MYSTEP_PHP可指定PHP7.4，不需要外部网络或业务数据库。
 */
'use strict';
const fs=require('fs'),path=require('path'),vm=require('vm'),os=require('os'),assert=require('assert'),child=require('child_process');
const {parseHTML}=require('linkedom');
const source=path.resolve(__dirname,'../..'), php=process.env.MYSTEP_PHP||'/Applications/ServBay/package/php/7.4/current/bin/php';
let script=fs.readFileSync(path.join(source,'app/myStep/asset/guide.js'),'utf8');
if(process.argv.includes('--minified'))script=JSON.parse(child.execFileSync(php,[path.join(source,'tests/dependencies/composer_regression.php'),'--minify'],{input:JSON.stringify({code:script}),encoding:'utf8'})).normal;
const globalScript=fs.readFileSync(path.join(source,'static/js/global.js'),'utf8');
const queryCode=globalScript.match(/function queryURL\([\s\S]*?(?=\n\/\*\*)/)[0];
const scratch=fs.mkdtempSync(path.join(os.tmpdir(),'guide-dom-'));
let passed=0;

/** 为一种真实路由模式建立模板DOM、请求队列及独立History。@param {string} base 后台指南URL */
function fixture(base='/sub/Admin/guide') {
    const html=child.execFileSync(php,[path.join(__dirname,'guide_dom_fixture.php'),source,base,scratch],{encoding:'utf8'});
    const {document,window:dom}=parseHTML(html);
    document.querySelectorAll('details').forEach(node=>{node.open=node.hasAttribute('open');});
    let focused=document.body;
    Object.defineProperty(document,'activeElement',{get:()=>focused});
    dom.HTMLElement.prototype.focus=function(options){focused=this;this.focusOptions=options;};
    dom.HTMLElement.prototype.getBoundingClientRect=function(){return {top:this.classList.contains('guide-article')?100:220,left:0,width:600,height:30};};
    const pending=[],timers=new Map(),handlers=new Map(),storage=new Map();let timer=0,index=0;
    const entries=[{url:'https://fixture.test'+base+'?doc=api',state:{other:'kept'}}];
    // 查询路由需要保留原有首段，此处直接用实际queryURL完成初始地址生成。
    const win={document,URL,URLSearchParams,NodeFilter:{SHOW_TEXT:4},AbortController,console,Number,Map,Set,
        setTimeout(fn,ms){timers.set(++timer,{fn,ms});return timer;},clearTimeout(id){timers.delete(id);},
        addEventListener(name,fn){if(!handlers.has(name))handlers.set(name,[]);handlers.get(name).push(fn);},
        sessionStorage:{getItem:key=>storage.get(key)||null,setItem:(key,value)=>storage.set(key,value)},
        fetch(url,options){return new Promise((resolve,reject)=>pending.push({url,options,resolve,reject}));},
        checkForm:()=>true,jQuery:fn=>fn()};
    win.window=win;
    Object.defineProperty(win,'location',{get:()=>new URL(entries[index].url)});
    win.history={get state(){return entries[index].state;},pushState(state,unused,url){entries.splice(index+1);entries.push({state,url:new URL(url,entries[index].url).href});index++;},replaceState(state,unused,url){entries[index]={state,url:new URL(url,entries[index].url).href};},back(){if(index>0){index--;for(const fn of handlers.get('popstate')||[])fn({state:entries[index].state});}},forward(){if(index+1<entries.length){index++;for(const fn of handlers.get('popstate')||[])fn({state:entries[index].state});}}};
    const context=vm.createContext(win);vm.runInContext(queryCode,context);entries[0].url=new URL(win.queryURL('https://fixture.test'+base,{doc:'api'})).href;
    vm.runInContext(script,context);
    const root=document.querySelector('[data-ms-guide]');
    const get=selector=>root.querySelector(selector);
    return {win,document,dom,pending,storage,timers,entries,root,get,base,flush(ms){for(const [id,item]of [...timers])if(item.ms===ms){timers.delete(id);item.fn();}}};
}

/** 派发可取消的真实DOM点击。@param {Object} f 夹具 @param {Element} link 链接 @param {Object} extra 修饰键 */
function click(f,link,extra={}) {const event=new f.dom.Event('click',{bubbles:true,cancelable:true});Object.assign(event,{button:0},extra);link.dispatchEvent(event);return event;}
/** 等待Promise链完成，不启动真实计时器。 */
async function drain(){for(let i=0;i<12;i++)await Promise.resolve();}
/** 完成队列中的JSON或登录HTML响应。@param {Object} request 请求 @param {Object} data 数据 @param {number} status HTTP码 @param {string} type MIME */
async function respond(request,data,status=200,type='application/json'){request.resolve({ok:status<400,headers:{get:()=>type},json:()=>Promise.resolve(data)});await drain();}
/** 构造受信服务器正文片段。@param {Object} f 夹具 @param {string} id 文档标识 */
function page(f,id){return {ok:true,type:'document',id,title:id,url:f.base+(f.base.includes('?')?'&':'?')+'doc='+id,article:'<header class="guide-meta"><h2>'+id+'</h2></header><details class="guide-outline"><summary>章节</summary></details><div class="guide-document"><h2 id="hello">Hello</h2><p>中文 myAuth 检查。&lt;img src=x&gt;</p><pre><code>myAuth::hashPassword()</code></pre></div>'};}
/** 记录一个有实际行为断言的异步用例。@param {string} name 名称 @param {Function} run 测试 */
async function test(name,run){await run();passed++;console.log('PASS '+name);}

(async()=>{
try {
    await test('new documents replace only article and keep left DOM scroll and details',async()=>{
        const f=fixture(),nav=f.get('.guide-directory nav'),groups=[...f.root.querySelectorAll('.guide-group')],article=f.get('.guide-article');nav.scrollTop=437;groups.forEach((g,i)=>g.open=i%2===0);const open=groups.map(g=>g.open);
        click(f,nav.querySelector('a[href*="doc=database"]'));assert.equal(f.pending.length,1);assert.equal(article.getAttribute('aria-busy'),'true');
        await respond(f.pending[0],page(f,'database'));assert.strictEqual(f.get('.guide-directory nav'),nav);assert.equal(nav.scrollTop,437);assert.deepEqual(groups.map(g=>g.open),open);assert.equal(article.querySelector('h2').textContent,'database');assert.equal(f.win.history.state.other,'kept');assert.equal(f.win.location.search,'?doc=database');assert.equal(article.focusOptions.preventScroll,true);
    });
    await test('older navigation response cannot overwrite newer content even if abort is ignored',async()=>{
        const f=fixture(),nav=f.get('nav');click(f,nav.querySelector('a[href*="doc=database"]'));click(f,nav.querySelector('a[href*="doc=templates"]'));
        assert(f.pending[0].options.signal.aborted);await respond(f.pending[1],page(f,'templates'));await respond(f.pending[0],page(f,'database'));assert.equal(f.root.dataset.guideDoc,'templates');
    });
    await test('back and forward reload the correct document and restore right scroll',async()=>{
        const f=fixture(),article=f.get('.guide-article');article.scrollTop=220;click(f,f.get('a[href*="doc=database"]'));await respond(f.pending[0],page(f,'database'));article.scrollTop=81;article.dispatchEvent(new f.dom.Event('scroll'));f.flush(100);
        f.win.history.back();await respond(f.pending[1],page(f,'api'));assert.equal(f.root.dataset.guideDoc,'api');assert.equal(article.scrollTop,220);f.win.history.forward();await respond(f.pending[2],page(f,'database'));assert.equal(article.scrollTop,81);
    });
    await test('same document section uses right scrolling without a network request',async()=>{
        const f=fixture(),article=f.get('.guide-article');article.innerHTML=page(f,'api').article;const link=f.document.createElement('a');link.href=f.base+'?doc=api#hello';article.append(link);click(f,link);assert.equal(f.pending.length,0);assert.equal(f.win.location.hash,'#hello');assert(article.scrollTop>0);
    });
    await test('search debounces input and displays plain text without injecting result markup',async()=>{
        const f=fixture(),input=f.get('[name="q"]');input.value='myAuth';input.dispatchEvent(new f.dom.Event('input'));assert.equal(f.pending.length,0);f.flush(350);assert.equal(f.pending.length,1);
        await respond(f.pending[0],{ok:true,type:'search',total:1,items:[{title:'<img src=x onerror=bad()>',group:'公共函数',url:f.base+'?doc=ref-myauth',snippet:'<script>bad()</script>'}]});assert.equal(f.get('.guide-results img'),null);assert.equal(f.get('.guide-results script'),null);assert.equal(f.get('.guide-results a').textContent,'<img src=x onerror=bad()>');
    });
    await test('stale search responses are ignored and Escape clears only search state',async()=>{
        const f=fixture(),input=f.get('[name="q"]'),form=f.get('form');input.value='old';form.dispatchEvent(new f.dom.Event('submit',{cancelable:true}));input.value='new';form.dispatchEvent(new f.dom.Event('submit',{cancelable:true}));await respond(f.pending[1],{ok:true,type:'search',total:0,items:[]});await respond(f.pending[0],{ok:true,type:'search',total:7,items:[]});assert(f.get('.guide-result-count').textContent.includes('没有找到'));
        const article=f.get('.guide-article'),nav=f.get('nav');article.scrollTop=56;nav.scrollTop=78;const event=new f.dom.Event('keydown',{cancelable:true});event.key='Escape';input.dispatchEvent(event);assert(f.get('.guide-results').hidden);assert.equal(article.scrollTop,56);assert.equal(nav.scrollTop,78);
    });
    await test('search highlighting preserves code and clearing removes only inserted marks',async()=>{
        const f=fixture(),article=f.get('.guide-article'),input=f.get('[name="q"]');article.innerHTML=page(f,'api').article;input.value='myAuth';f.get('form').dispatchEvent(new f.dom.Event('submit',{cancelable:true}));await respond(f.pending[0],{ok:true,type:'search',total:0,items:[]});assert(article.querySelector('code mark.guide-hit'));click(f,f.get('.guide-clear'));assert.equal(article.querySelectorAll('mark.guide-hit').length,0);assert.equal(article.querySelector('code').textContent,'myAuth::hashPassword()');
    });
    await test('failed requests keep original article and offer a normal reload link',async()=>{
        const f=fixture(),before=f.get('.guide-article').innerHTML;click(f,f.get('a[href*="doc=database"]'));await respond(f.pending[0],{},200,'text/html');assert.equal(f.get('.guide-article').innerHTML,before);assert(f.get('.guide-status').textContent.includes('登录可能'));assert(f.get('a[data-guide-reload]'));
    });
    await test('literal search metacharacters are highlighted without becoming a regular expression',async()=>{
        const f=fixture(),article=f.get('.guide-article');article.innerHTML='<div class="guide-document"><p>literal .* other words</p></div>';f.get('[name="q"]').value='.*';f.get('form').dispatchEvent(new f.dom.Event('submit',{cancelable:true}));await respond(f.pending[0],{ok:true,type:'search',total:0,items:[]});assert.equal(article.querySelectorAll('mark').length,1);assert.equal(article.querySelector('mark').textContent,'.*');
    });
    await test('invalid input cancels old results and does not leave the search busy',async()=>{
        const f=fixture(),input=f.get('[name="q"]');input.value='first';f.get('form').dispatchEvent(new f.dom.Event('submit',{cancelable:true}));f.win.checkForm=()=>false;input.value='invalid';f.get('form').dispatchEvent(new f.dom.Event('submit',{cancelable:true}));assert.equal(f.pending.length,1);assert.equal(f.get('.guide-results').getAttribute('aria-busy'),'false');await respond(f.pending[0],{ok:true,type:'search',total:55,items:[]});assert(f.get('.guide-result-count').textContent.includes('长度'));
    });
    await test('search result navigation locates the hit even when the browser did not focus the link',async()=>{
        const f=fixture(),input=f.get('[name="q"]');input.value='myAuth';input.focus();f.get('form').dispatchEvent(new f.dom.Event('submit',{cancelable:true}));await respond(f.pending[0],{ok:true,type:'search',total:1,items:[{title:'Authentication',url:f.base+'?doc=ref-myauth',group:'classes',snippet:'test'}]});click(f,f.get('.guide-results a'));await respond(f.pending[1],page(f,'ref-myauth'));assert(f.get('.guide-article').scrollTop>0);assert(f.get('.guide-article mark'));
    });
    await test('sidebar state is saved on scroll and group changes without document text',async()=>{
        const f=fixture(),nav=f.get('nav'),group=f.get('.guide-group');nav.scrollTop=142;nav.dispatchEvent(new f.dom.Event('scroll'));f.flush(100);group.open=false;group.dispatchEvent(new f.dom.Event('toggle'));const saved=JSON.parse([...f.storage.values()][0]);assert.equal(saved.top,142);assert.equal(saved.open[group.querySelector('summary').textContent],false);assert.equal(Object.hasOwn(saved,'article'),false);
    });
    await test('a trailing slash on the original URL still supports browser back',async()=>{
        const f=fixture();f.entries[0].url='https://fixture.test/sub/Admin/guide/?doc=api';click(f,f.get('a[href*="doc=database"]'));await respond(f.pending[0],page(f,'database'));f.win.history.back();await respond(f.pending[1],page(f,'api'));assert.equal(f.root.dataset.guideDoc,'api');
    });
    await test('equivalent query route spelling is recognized but other routes are not intercepted',async()=>{
        const f=fixture('/sub/index.php?/Admin/guide'),link=f.document.createElement('a');link.href='/sub/index.php?Admin/guide/&doc=database';f.root.append(link);click(f,link);assert.equal(f.pending.length,1);const outside=f.document.createElement('a');outside.href='/sub/index.php?/Admin/error&doc=database';f.root.append(outside);const event=click(f,outside);assert.equal(event.defaultPrevented,false);
    });
    await test('modified links retain native browser behavior',async()=>{
        const f=fixture(),link=f.get('a[href*="doc=database"]');const event=click(f,link,{ctrlKey:true});assert.equal(event.defaultPrevented,false);assert.equal(f.pending.length,0);
    });
    for(const base of ['/sub/Admin/guide','/sub/index.php/Admin/guide','/sub/index.php?/Admin/guide','/sub/index.php?/console/guide']){
        await test('framework route prefix is preserved: '+base,async()=>{const f=fixture(base);click(f,f.get('a[href*="doc=database"]'));assert(f.pending[0].url.startsWith('https://fixture.test'+base+(base.includes('?')?'&':'?')));await respond(f.pending[0],page(f,'database'));assert.equal(f.win.location.pathname+(base.includes('?')?f.win.location.search.split('&')[0]:''),base);});
    }
    console.log('Guide DOM logic: '+passed+' passed; visual browser verification: not performed');
}finally{fs.rmSync(scratch,{recursive:true,force:true});}
})().catch(error=>{console.error(error.stack);process.exitCode=1;});
