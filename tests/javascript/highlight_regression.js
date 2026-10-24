/**
 * Execute actual readiness helpers, highlight adapter and bundled renderers in a DOM.
 * Node + development-only linkedom; deterministic asynchronous loads, no browser or network.
 */
'use strict';
const fs=require('node:fs'),path=require('node:path'),vm=require('node:vm'),assert=require('node:assert/strict');
const {parseHTML}=require('linkedom');
const root=path.resolve(__dirname,'../..');
const globalSource=fs.readFileSync(path.join(root,'static/js/global.js'),'utf8');
const readiness=globalSource.slice(globalSource.indexOf('function checkNrun('),globalSource.indexOf('function queryURL('));
const adapter=fs.readFileSync(path.join(root,'vendor/highlight/highlight.js'),'utf8');
let passed=0;
function fixture(mode) {
    const {document,window}=parseHTML('<html><head></head><body>'+(mode===0?'<pre class="brush:php;">&lt;?php echo "SQLite";</pre>':'<pre><code class="language-php">&lt;?php echo "SQLite";</code></pre>')+'</body></html>');
    let now=0,serial=0;const timers=new Map(),requests=[],styles=[],warnings=[];
    function later(fn,delay=0){timers.set(++serial,{fn,time:now+delay});return serial;}
    function selection(value){const nodes=typeof value==='string'?[...document.querySelectorAll(value)]:[value];return {
        css(){return this;},append(){return this;},each(fn){nodes.forEach(node=>fn.call(node));return this;},
        attr(name,value){if(value===undefined)return nodes[0]?.getAttribute(name)||'';nodes.forEach(node=>node.setAttribute(name,value));return this;},
        find(query){return selection(nodes[0]?.querySelector(query));},parent(){return selection(nodes[0]?.parentNode);},addClass(name){nodes.forEach(node=>node.classList.add(name));return this;}
    };}
    const context={window:null,document,navigator:{userAgent:'fixture'},global:{root:'/sub/'},console:{log(){},warn(value){warnings.push(value);},error(value){warnings.push(value);}},setTimeout:later,clearTimeout:id=>timers.delete(id),$:selection,ClipboardJS:class{on(){return this;}}};context.window=context;context.addEventListener=window.addEventListener.bind(window);
    vm.createContext(context);context.clockNow=()=>now;vm.runInContext('Date.now=()=>clockNow();\n'+readiness+'\n'+adapter,context);
    function execute(url){requests.push(url);const member=url.replace(/^\/sub\//,'');assert.ok(!member.startsWith('/')&&!member.includes('..'));if(member==='vendor/highlight/clipboard.min.js')return;vm.runInContext(fs.readFileSync(path.join(root,member),'utf8'),context,{filename:member});}
    selection.setCSS=urls=>styles.push(...urls);
    selection.setJS=(urls,sequential,done)=>{assert.equal(sequential,true);urls.forEach(execute);done();};
    const append=document.body.appendChild.bind(document.body);
    document.body.appendChild=node=>{const result=append(node);if(node.tagName==='SCRIPT')later(()=>{execute(node.getAttribute('src'));node.onload.call(node);});return result;};
    return {context,document,requests,styles,warnings,timers,tick(ms){now+=ms;let rounds=0;while([...timers.values()].some(t=>t.time<=now)){if(++rounds>1000)throw Error('Unbounded timers');for(const [key,timer]of [...timers])if(timer.time<=now){timers.delete(key);timer.fn();}}}};
}
for(const mode of [0,1]) {
    const f=fixture(mode);f.context.highlight(mode);
    assert.equal(f.requests.length,0);assert.equal(f.timers.size,1,'Highlight must retry after configuration becomes ready');
    f.context.language={};f.context.setting={};f.tick(100);
    assert.ok(f.document.querySelector(mode===0?'.syntaxhighlighter .functions':'.hljs-keyword'),'Actual renderer must create colored PHP tokens');
    assert.ok(f.requests.some(url=>url.includes(mode===0?'shBrushPhp.js':'highlight.pack.js')));
    assert.ok(f.styles.every(url=>url.startsWith('/sub/vendor/highlight/')));
    passed++;process.stdout.write('PASS delayed configuration renders actual '+(mode===0?'SyntaxHighlighter':'highlight.js')+' tokens\n');
}
const timeout=fixture(0);timeout.context.highlight(0);timeout.tick(15000);assert.equal(timeout.requests.length,0);assert.equal(timeout.timers.size,0);assert.equal(timeout.warnings.length,1);passed++;
process.stdout.write('PASS missing configuration stops retries after the framework deadline\nPassed: '+passed+'; Failed: 0\n');
