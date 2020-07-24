function highlight(mode=1, style = 'default') {
    if(!checkSetting()) return;
    if(mode===1) {
        $.setJS([
            global.root+'vendor/highlight/highlightjs/highlight.pack.js',
            global.root+'vendor/highlight/clipboard.min.js'
        ], true, function(){
            $.setCSS([
                global.root+'vendor/highlight/highlightjs/styles/'+style+'.css',
                global.root+'vendor/highlight/clipboard.css',
            ]);
            document.querySelectorAll('pre code').forEach((block) => {
                hljs.highlightBlock(block);
                $(block).parent().addClass('highlight');
            });
            setCopyBtn('pre.highlight')
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

function setCopyBtn(obj) {
    $(obj).css('position', 'relative')
        .append('<button class="btn-copy btn btn-light py-0">Copy</button>');
    (new ClipboardJS('.btn-copy', {
        target: function(trigger) {
            return $(trigger).parent().get(0).tagName==='PRE' ?
                    trigger.parentElement.firstChild
                    :
                    $(trigger).prev().find('td.code').get(0);
        }
    })).on('success', function(e) {
        e.clearSelection();
    });
}