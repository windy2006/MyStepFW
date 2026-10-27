/**
 * 初始化本地代码高亮库；配置未就绪时由框架安排有限时重试，保留引擎和主题参数。
 * @param {number} [mode=1] 1 为 highlight.js，其他值为 SyntaxHighlighter。
 * @param {string} [style='default'] 对应本地高亮库的主题名称。
 * @returns {void} 配置就绪后异步加载依赖并处理代码块。
 */
function highlight(mode=1, style = 'default') {
    if(!checkSetting(100, highlight, [mode, style])) return;
    if(mode===1) {
        $.setJS([
            global.root+'vendor/highlight/highlightjs/highlight.pack.js',
            global.root+'vendor/highlight/clipboard.min.js'
        ], true, function(){
            $.setCSS([
                global.root+'vendor/highlight/highlightjs/styles/'+style+'.css',
                global.root+'vendor/highlight/clipboard.css',
            ]);
            highlightBlocks();
            setCopyBtn('pre.highlight');
        });
    } else {
        $.setJS([
            global.root+'vendor/highlight/syntaxhighlighter/scripts/XRegExp.js',
            global.root+'vendor/highlight/syntaxhighlighter/scripts/shCore.js',
            global.root+'vendor/highlight/syntaxhighlighter/scripts/shAutoloader.js',
            global.root+'vendor/highlight/clipboard.min.js',
        ], true, function(){
            $.setCSS([
                global.root+'vendor/highlight/syntaxhighlighter/styles/shCore'+style.replace(style[0],style[0].toUpperCase())+'.css',
                global.root+'vendor/highlight/clipboard.css',
            ]);
            SyntaxHighlighter.defaults['toolbar'] = false;
            SyntaxHighlighter.autoloader(
                'php                      '+global.root+'vendor/highlight/syntaxhighlighter/scripts/shBrushPhp.js',
                'js jscript javascript    '+global.root+'vendor/highlight/syntaxhighlighter/scripts/shBrushJScript.js',
                'htm html xml             '+global.root+'vendor/highlight/syntaxhighlighter/scripts/shBrushXml.js',
                'css                      '+global.root+'vendor/highlight/syntaxhighlighter/scripts/shBrushCss.js',
                'sql                      '+global.root+'vendor/highlight/syntaxhighlighter/scripts/shBrushSql.js',
            );
            SyntaxHighlighter.all();
            setTimeout(()=>{
                setCopyBtn('.syntaxhighlighter');
                $('.syntaxhighlighter').each(function(){
                    let style = $(this).attr('style');
                    $(this).attr('style', style+';overflow:hidden !important;');
                    $(this).find('div.container').attr('style', 'max-width:100%;');
                });
            }, 1000);
        });
    }
}

/**
 * 功能说明：按保存的语言标记高亮前台代码，保留原始空格/制表符/全角空格，不重复处理。
 * 参数说明：无显式参数；依赖已加载的本地hljs引擎。
 * @returns {void} 缺语言时由前台自动识别；不支持的语言显示为普通代码。
 */
function highlightBlocks() {
    document.querySelectorAll('pre.highlight[data-lng]').forEach(function (pre) {
        if (pre.querySelector('code')) return;
        const code = document.createElement('code');
        const copy = pre.cloneNode(true); copy.querySelectorAll('.btn-copy').forEach(function (button) { button.remove(); });
        code.textContent = copy.textContent;
        pre.replaceChildren(code);
    });
    document.querySelectorAll('pre code').forEach(function (block) {
        if (block.dataset.msCodeHighlighted) return;
        const pre = block.closest('pre');
        const match = block.className.match(/(?:^|\s)(?:language-|lang-)([a-z0-9_+-]+)/i);
        const declared = pre.getAttribute('data-lng');
        let language = String(declared || (match && match[1]) || 'auto').toLowerCase();
        if (!declared && /^(?:text|plain|none)$/.test(language)) language = 'auto';
        language = ({js:'javascript',jscript:'javascript',xml:'html',htm:'html',sh:'bash',shell:'bash',text:'plaintext',plain:'plaintext',none:'plaintext'})[language] || language;
        // class只保留普通展示类和有效语言类，避免language-auto/旧高亮span触发错误识别。
        Array.from(block.classList).filter(function (name) { return /^(?:language-|lang-)/i.test(name); }).forEach(function (name) { block.classList.remove(name); });
        if (language !== 'auto') {
            if (!hljs.getLanguage(language)) language = 'plaintext';
            block.classList.add(hljs.getLanguage(language) ? 'language-' + language : 'nohighlight');
        }
        // 旧文章可能保存了高亮span；先还原纯文本，避免把旧标记当作待高亮HTML。
        const plain = block.cloneNode(true);
        plain.querySelectorAll('br').forEach(function (br) { br.replaceWith(document.createTextNode('\n')); });
        block.textContent = plain.textContent;
        hljs.highlightBlock(block);
        pre.classList.add('highlight');
        block.dataset.msCodeHighlighted = 'true';
    });
}
/**
 * 功能说明：给前台代码段增加一个复制按钮，复用一份委托监听；只复制代码节点。
 * @param {string|Element|jQuery} obj PRE或旧SyntaxHighlighter容器的选择器/节点
 * @returns {void} 重复调用不重复添加按钮，保留旧SyntaxHighlighter分支。
 */
function setCopyBtn(obj) {
    $(obj).each(function () {
        if (this.querySelector('.btn-copy')) return;
        $(this).css('position', 'relative').append('<button type="button" class="btn-copy btn btn-light py-0" aria-label="复制代码">Copy</button>');
    });
    if (setCopyBtn.clipboard) return;
    setCopyBtn.clipboard = new ClipboardJS('.btn-copy', {
        target: function (trigger) {
            const container = trigger.parentElement;
            return container.tagName === 'PRE' ? (container.querySelector('code') || container) : container.querySelector('td.code');
        }
    });
    setCopyBtn.clipboard.on('success', function (event) { event.clearSelection(); });
}
