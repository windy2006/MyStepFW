/**
 * 文件：app/CMS/asset/admin/tinymce_init.js
 * 功能说明：CMS富文本编辑器配置、上传及myStep_cms扩展；代码段仅保存文本与语言标记。
 * 代码段方法目录：blockAt、languageOf、nodeText、codeText、selectedCodeText、normalize、normalizeAll、apply、paste、update。
 * 使用说明：保留框架myStep与CMS专用扩展的加载顺序；highlight按钮在本文件登记，前台单独高亮。
 */
// 页面提供资源版本，使编辑器脚本和iframe样式同步刷新。
const cmsEditorCSSRevision = typeof setting_tinymce_revision === 'string' ? '?v=' + encodeURIComponent(setting_tinymce_revision) : '';
let setting_tinymce = {
        language:'zh_CN',
        selector:'#content',
        editor_encoding:'raw',
        entity_encoding:'raw',
        plugins: [
            "advlist autolink autosave link image imagetools lists charmap print preview hr anchor pagebreak",
            "searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
            "table contextmenu directionality emoticons template textcolor textcolor importcss myStep myStep_cms"
        ],

        toolbar1 : "code pastetext searchreplace | bold italic underline removeformat | alignleft aligncenter alignright | bullist numlist | outdent indent hr blockquote | forecolor backcolor | fullscreen",
        toolbar2 : "fontselect fontsizeselect formatselect alert | link anchor image media",
        toolbar_items_size: 'small',

        //menubar: false,
        menubar: 'edit view insert format table',
        menu: {
            edit: {title: 'Edit', items: 'undo redo | cut copy paste pastetext | selectall'},
            view: {title: 'View', items: 'code | visualaid visualchars visualblocks | preview fullscreen'},
            insert: {title: 'Insert', items: 'link image media | insert charmap emoticons hr | pagebreak nonbreaking anchor | insertdatetime'},
            format: {title: 'Format', items: 'bold italic underline strikethrough superscript subscript | formats fonts'},
            table: {title: 'Table', items: 'inserttable tableprops deletetable | cell row column'},
        },

        // Custom settings
        content_css : [global.root + "static/css/bootstrap.css", global.root + "static/css/tinymce.css" + cmsEditorCSSRevision],
        // 标题字号由tinymce.css控制，选择标题只使用语义标签，不写入行内字号。
        block_formats: '段落=p;标题一=h1;标题二=h2;标题三=h3;标题四=h4;标题五=h5;预格式化=pre',
        // 格式菜单与段落下拉框保持一致，不再提供标题六；其余原生格式仍然保留。
        style_formats: [
            {title:'Headings', items:[
                {title:'标题一',format:'h1'}, {title:'标题二',format:'h2'},
                {title:'标题三',format:'h3'}, {title:'标题四',format:'h4'}, {title:'标题五',format:'h5'}
            ]},
            {title:'Inline', items:[
                {title:'Bold',icon:'bold',format:'bold'}, {title:'Italic',icon:'italic',format:'italic'},
                {title:'Underline',icon:'underline',format:'underline'}, {title:'Strikethrough',icon:'strikethrough',format:'strikethrough'},
                {title:'Superscript',icon:'superscript',format:'superscript'}, {title:'Subscript',icon:'subscript',format:'subscript'},
                {title:'Code',icon:'code',format:'code'}
            ]},
            {title:'Blocks', items:[
                {title:'Paragraph',format:'p'}, {title:'Blockquote',format:'blockquote'},
                {title:'Div',format:'div'}, {title:'Pre',format:'pre'}
            ]},
            {title:'Alignment', items:[
                {title:'Left',icon:'alignleft',format:'alignleft'}, {title:'Center',icon:'aligncenter',format:'aligncenter'},
                {title:'Right',icon:'alignright',format:'alignright'}, {title:'Justify',icon:'alignjustify',format:'alignjustify'}
            ]}
        ],
        convert_urls: false,
        remove_script_host: false,
        preformatted : false,
        remove_linebreaks : false,
        apply_source_formatting : true,
        convert_fonts_to_spans : true,
        verify_html : true,
        paste_auto_cleanup_on_paste : true,
        dialog_type : "modal",
        relative_urls : false,
        invalid_elements : "script",
        extended_valid_elements : "form[action|method|name],"+
            "textarea[class|type|title|name|rows|cols],"+
            "input[type|name|value|checked|src|alt|size|maxlength],"+
            "button[name|value|type],"+
            "select[name|size|multiple|onchange],"+
            "iframe[src|frameborder=0|width|height|align|scrolling|name],"+
            "center,"+
            "script[charset|defer|language|src|type]",
        forced_root_block : "p",
        flash_wmode : "transparent",
        flash_quality : "high",
        flash_menu : "false",
        add_unload_trigger : false,
        fontsize_formats: "8pt 10pt 12pt 14pt 16pt 18pt 20pt 24pt 36pt",
        font_formats: "微软雅黑='微软雅黑';宋体='宋体';黑体='黑体';仿宋='仿宋';楷体='楷体';隶书='隶书';幼圆='幼圆';Arial='Arial';Arial Black='Arial Black';Times New Roman='Times New Roman';Impact='Impact';Webdings='Webdings';Wingdings='Wingdings';",
};
function insertContent(str) {
    tinyMCE.activeEditor.execCommand("mceInsertContent", false, str);
}
function uploadInit() {
    if(!checkSetting(100, uploadInit)) return;
    $.vendor('jquery.powerUpload', {
        callback:function(){
            let opts = {
                url: queryURL(setting.url_prefix+'api/myStep/upload', {core:'CMS'}),
                title: '请选择需要上传的文件',
                mode: 'browse',
                max_files: 5,
                max_file_size: setting.max_size,
                errors: ["浏览器不支持", "上传文件过多", "每个文件必须小于"+setting.max_size+"MB", "未设置上传目标", "更新文件未选择"],
            }
            $('#upload').powerUpload($.extend({}, opts, {
                max_files: 1,
                uploadFinished:function(i,file,result,timeDiff,uploader){
                    uploader = uploader || $('#uploader');
                    if(String(result.error) !== '0') {
                        alert("上传失败！\n原因：" + result.message);
                    } else {
                        uploader.find(".modal-title > b").html("上传完成，请关闭本对话框！");
                        $("input[name=image]").val(setting.url_prefix+'api/CMS/attachment/'+result.new_name);
                        uploader.unbind('hidden.bs.modal').on('hidden.bs.modal', function(e){
                            $("input[name=image]").select();
                        });
                    }
                }
            }));
            opts = $.extend(opts, {
                title: '附件上传',
                uploadFinished:function(i,file,result,timeDiff,uploader){
                    uploader = uploader || $('#uploader');
                    let obj = uploader.find(".progress[data-idx="+i+"] > div");
                    if(String(result.error) === '0') {
                        let editor = tinyMCE.activeEditor;
                        let content = editor.getContent();
                        if(content.match(new RegExp('"(file://.+?/'+result.name+')"'))) {
                            content = content.replace(RegExp.$1, setting.url_prefix+'api/CMS/attachment/'+result.new_name);
                            editor.setContent(content);
                        } else {
                            let file = setting.url_prefix+'api/CMS/attachment/'+result.new_name;
                            if(result.type.match(/^image/) || result.name.match(/\.png$/i) || result.name.match(/\.gif$/i)) {
                                insertContent('<br /><img src="' + file + '" title="' + result.name + '" style="max-width:90%;" /><br />');
                            } else if(result.type.match(/^video/)) {
                                insertContent('<br />' +
                                    '<video class="mx-auto" controls="controls" autoplay="autoplay" width="100%" height="auto">' +
                                    '<source src="' + file + '" type="'+result.type+'" />\n' +
                                    'Your browser does not support the video tag.</video>' +
                                    '<br />');
                            } else {
                                insertContent('<br /><a href="'+file+'" />'+result.name+'</a><br />');
                            }
                        }
                    }
                },
                allDone:function(uploader){
                    uploader = uploader || $('#uploader');
                    uploader.find(".modal-title > b").html("全部文件上传完成，请关闭本对话框！");
                    $('#cover').hide();
                    uploader.unbind('hidden.bs.modal').on('hidden.bs.modal', function(e){
                        tinyMCE.activeEditor.focus();
                    });
                },
            });
            $('button[name=upload]').powerUpload(opts);
            $('#cover').powerUpload($.extend(opts, {
                mode: 'drop',
                error:function(msg){
                    alert(msg);
                    $('#cover').hide();
                    tinyMCE.activeEditor.focus();
                }
            }));
            $(tinyMCE.activeEditor.getBody()).powerUpload($.extend(opts, {
                mode: 'paste',
                max_files: 1,
            }));
        }
    });
}
$(function(){
    $('body').append('\n' +
        '<div id="attachment" class="modal fade">\n' +
        '    <div class="modal-dialog" role="document">\n' +
        '        <div class="modal-content">\n' +
        '            <div class="modal-header">\n' +
        '                <h5 class="modal-title" id="exampleModalLabel">附件管理</h5>\n' +
        '                <button type="button" class="close" data-dismiss="modal" aria-label="Close">\n' +
        '                    <span aria-hidden="true">&times;</span>\n' +
        '                </button>\n' +
        '            </div>\n' +
        '            <div class="modal-body">\n' +
        '                <ul class="list-group"></ul>\n' +
        '            </div>\n' +
        '            <div class="modal-footer">\n' +
        '                <button type="button" class="btn btn-secondary" data-dismiss="modal" name="upload"> 上传 </button>\n' +
        '                <button type="button" class="btn btn-secondary" data-dismiss="modal"> 关闭 </button>\n' +
        '            </div>\n' +
        '        </div>\n' +
        '    </div>\n' +
        '</div>');
    $('<div id="cover"></div>').insertBefore('#content').on('mouseout mouseleave dragleave', function(){
        $('#cover').hide();
    });
    tinyMCE.create('tinyMCE.plugins.myStep_cms', {
        init : function(editor, url) {
            (function () {
                'use strict';
                const labels = {auto:'自动识别（前台）',php:'PHP',html:'HTML',css:'CSS',javascript:'JavaScript',sql:'SQL',json:'JSON',python:'Python',bash:'Shell',plaintext:'普通代码'};
                /** 找到当前编辑器内的pre祖先，兼容光标落在code/span/text节点。@param {Node} node 起点 @returns {Element|null} 代码容器 */
                function blockAt(node) {
                    const body = editor.getBody();
                    while (node && node !== body) { if (node.nodeType === 1 && node.nodeName === 'PRE') return node; node = node.parentNode; }
                    return null;
                }
                /** 读取语言元数据；缺失时默认前台自动识别，保留合法但未列入菜单的语言。@param {Element} pre 代码容器 @returns {string} 规范语言名 */
                function languageOf(pre) {
                    const code = pre.querySelector('code');
                    const match = ((code && code.className) || pre.className || '').match(/(?:^|\s)(?:language-|lang-)([a-z0-9_+-]+)/i);
                    const declared = pre.getAttribute('data-lng');
                    let value = String(declared || (match && match[1]) || 'auto').toLowerCase();
                    // 旧手册的language-text是未分类占位；明确选择的data-lng=plaintext仍保留。
                    if (!declared && /^(?:text|plain|none)$/.test(value)) value = 'auto';
                    value = ({js:'javascript',jscript:'javascript',xml:'html',htm:'html',sh:'bash',shell:'bash',text:'plaintext',plain:'plaintext',none:'plaintext'})[value] || value;
                    return /^[a-z0-9_+-]{1,30}$/.test(value) ? value : 'auto';
                }
                /**
                 * 功能说明：从DOM片段提取代码原文，以真实换行表达br和段落边界。
                 * @param {Node} root 编辑器节点或Range.cloneContents产生的片段
                 * @returns {string} 保留缩进、空行及文本节点中的原始换行；不二次解码实体
                 */
                function nodeText(root) {
                    const parts = [];
                    const blockNames = /^(?:P|DIV|PRE|LI|UL|OL|DL|DT|DD|BLOCKQUOTE|H[1-6]|TR|TABLE|THEAD|TBODY|TFOOT|SECTION|ARTICLE|ADDRESS|HR)$/;
                    /** 判断元素是否产生段落边界。@param {Node} node 节点 @returns {boolean} 是否为块级结构 */
                    function isBlock(node) { return node && node.nodeType === 1 && blockNames.test(node.nodeName); }
                    /** 追加文本或结构换行，记录来源以免裁掉用户的真实换行。@param {string} value 内容 @param {boolean} [structural=false] 是否结构换行 @returns {void} */
                    function append(value, structural = false) { if (value) parts.push({value:value, structural:structural}); }
                    /** 检查已提取内容是否位于行尾。@returns {boolean} 无显式参数 */
                    function atLineEnd() { return parts.length > 0 && /\n$/.test(parts[parts.length - 1].value); }
                    /** 递归读取文本并跳过编辑器占位/前台按钮。@param {Node} node 节点 @param {boolean} preformatted 是否处于pre @returns {void} */
                    function visit(node, preformatted) {
                        if (node.nodeType === 3) {
                            // HTML排版用的块间空白不形成额外空行；pre中的真实空白始终保留。
                            if (!preformatted && /^[\t\r\n ]*$/.test(node.nodeValue)
                                && (isBlock(node.previousSibling) || isBlock(node.nextSibling))) return;
                            append(node.nodeValue.replace(/\r\n?/g, '\n')); return;
                        }
                        if (node.nodeType !== 1 && node.nodeType !== 11) return;
                        if (node.nodeType === 1) {
                            if (node.classList.contains('btn-copy') || node.getAttribute('data-mce-bogus') === 'all'
                                || node.getAttribute('data-mce-type') === 'bookmark') return;
                            if (node.nodeName === 'BR') { if (!node.hasAttribute('data-mce-bogus')) append('\n'); return; }
                        }
                        const block = isBlock(node);
                        if (block && parts.length && !atLineEnd()) append('\n', true);
                        const start = parts.length;
                        const children = Array.from(node.childNodes);
                        // TinyMCE的空段落通常只有一个br；它代表一条空行，不能重复计算两次。
                        const placeholder = block && children.length === 1 && children[0].nodeName === 'BR';
                        if (!placeholder) children.forEach(function (child) { visit(child, preformatted || node.nodeName === 'PRE'); });
                        if (block && (parts.length === start || !atLineEnd())) append('\n', true);
                    }
                    // 容器自身不额外引入段落；仅读取其内容，避免完整pre末尾平白多出一行。
                    Array.from(root.childNodes).forEach(function (node) { visit(node, root.nodeName === 'PRE'); });
                    if (parts.length && parts[parts.length - 1].structural) parts.pop();
                    return parts.map(function (part) { return part.value; }).join('');
                }
                /** 读取完整代码段，兼容旧span、br和段落布局，不丢弃code之外的文本。@param {Element} pre 容器 @returns {string} 原始代码 */
                function codeText(pre) { return nodeText(pre); }
                /**
                 * 功能说明：提取当前选择的DOM片段，保留TinyMCE纯文本接口会丢失的段落/软换行。
                 * 参数说明：无显式参数，使用当前编辑器Selection；不修改选择范围。
                 * @returns {string} 选中代码文本；旧Selection实现回退到HTML片段解析
                 */
                function selectedCodeText() {
                    const range = typeof editor.selection.getRng === 'function' ? editor.selection.getRng() : null;
                    if (range && typeof range.cloneContents === 'function') return nodeText(range.cloneContents());
                    const container = editor.getDoc().createElement('div');
                    container.innerHTML = editor.selection.getContent({format:'html'});
                    return nodeText(container);
                }
                /** 规范化为单个纯文本code节点；已规范的节点不重写文本，避免影响光标。@param {Element} pre 容器 @param {string} [language] 指定语言 @returns {Element} 代码节点 */
                function normalize(pre, language) {
                    const value = language || languageOf(pre);
                    let code = pre.querySelector('code');
                    const clean = code && code.parentNode === pre && pre.childNodes.length === 1 && code.children.length === 0;
                    if (!clean) {
                        const text = codeText(pre);
                        code = editor.getDoc().createElement('code'); code.textContent = text;
                        while (pre.firstChild) pre.removeChild(pre.firstChild);
                        pre.appendChild(code);
                    }
                    pre.classList.add('highlight'); pre.setAttribute('data-lng', value);
                    if (value === 'auto') code.removeAttribute('class');
                    else code.setAttribute('class', 'language-' + value);
                    return code;
                }
                /** 整理已有代码标记；仅处理明确的代码段，不改普通预格式化文本。@returns {void} 无显式参数 */
                function normalizeAll() {
                    const body = editor.getBody(); if (!body) return;
                    Array.from(body.querySelectorAll('pre')).forEach(function (pre) {
                        if (pre.classList.contains('highlight') || pre.querySelector('code')) normalize(pre);
                    });
                }
                /** 插入、设置语言或取消当前代码段，全部操作进入同一撤销步骤。@param {string} language 语言名，空字符串转换为正文 @returns {void} 不猜测后台语言 */
                function apply(language) {
                    if (language !== '' && !Object.prototype.hasOwnProperty.call(labels, language)) return;
                    const pre = blockAt(editor.selection.getNode()) || blockAt(editor.selection.getStart());
                    editor.undoManager.transact(function () {
                        if (pre) {
                            if (language === '') {
                                const p = editor.getDoc().createElement('p');
                                codeText(pre).split('\n').forEach(function (line, index) {
                                    if (index) p.appendChild(editor.getDoc().createElement('br'));
                                    p.appendChild(editor.getDoc().createTextNode(line));
                                });
                                editor.dom.replace(p, pre); editor.selection.select(p, true); editor.selection.collapse(false);
                            } else {
                                const code = normalize(pre, language); editor.selection.select(code, true); editor.selection.collapse(false);
                            }
                        } else if (language !== '') {
                            const text = selectedCodeText();
                            const block = editor.getDoc().createElement('pre'), code = editor.getDoc().createElement('code');
                            block.className = 'highlight'; block.setAttribute('data-lng', language);
                            if (language !== 'auto') code.className = 'language-' + language;
                            code.textContent = text || '\n'; block.appendChild(code);
                            editor.insertContent(block.outerHTML);
                        }
                        editor.nodeChanged();
                    });
                }
                /** 粘贴到代码段时只取纯文本，避免原正文粘贴器把换行和空格改成HTML。@param {ClipboardEvent} event 粘贴事件 @returns {void} 非代码/图片粘贴保留原流程 */
                function paste(event) {
                    const pre = blockAt(editor.selection.getNode()); if (!pre) return;
                    const clipboard = (event.originalEvent || event).clipboardData;
                    if (!clipboard || typeof clipboard.getData !== 'function') return;
                    const text = clipboard.getData('text/plain'); if (text === '') return;
                    event.preventDefault();
                    if (typeof event.stopImmediatePropagation === 'function') event.stopImmediatePropagation();
                    const node = editor.getDoc().createElement('div'); node.textContent = text.replace(/\r\n?/g, '\n');
                    editor.insertContent(node.innerHTML);
                }
                editor.addCommand('msCodeBlock', function (ui, language) { apply(typeof language === 'string' ? language : 'auto'); });
                editor.addButton('highlight', {
                    text:'代码段', title:'代码段及前台语言识别设置', type:'listbox',
                    values:Object.keys(labels).map(function (key) { return {text:labels[key],value:key}; }).concat([{text:'取消代码段',value:''}]),
                    onselect:function () { apply(this.value()); },
                    onPostRender:function () {
                        const control = this;
                        /** 根据实际光标位置更新菜单，缺语言标记不抛错。@returns {void} 无显式参数 */
                        function update() {
                            const pre = blockAt(editor.selection.getNode());
                            if (!pre) { control.value(null); control.text('代码段'); return; }
                            const language = languageOf(pre); control.value(labels[language] ? language : null);
                            control.text(labels[language] || '代码段');
                        }
                        editor.on('NodeChange SetContent', update);
                    }
                });
                editor.on('init SetContent BeforeGetContent', normalizeAll);
                editor.on('paste', paste, true);
            }());

            editor.addCommand("ms_format", function() {
                let content = editor.getContent();
                const codeBlocks = [], marker = 'MSCODE' + Date.now() + Math.random().toString(36).slice(2);
                content = content.replace(/<pre\b[^>]*>[\s\S]*?<\/pre>/gi, function (html) {
                    return '<!--' + marker + '-' + (codeBlocks.push(html)-1) + '-->';
                });
                let tag = 'p';
                if(/<(p|div)/i.test(content)) {
                    tag = RegExp.$1;
                }
                if(content.indexOf("<div")===-1) {
                    //content = content.replace(/(<br(\s\/)?>)+/ig, "</p><p>");
                    content = content.replace(/<p(.*?)>[\r\n]+/ig, "<p$1>");
                    content = content.replace(/<\/p><p/g, "<\/p>\n<p");
                    tag = 'p';
                } else {
                    //content = content.replace(/(<br(\s\/)?>)+/ig, "</div><div>");
                    content = content.replace(/<div(.*?)>[\r\n]+/ig, "<div$1>");
                    content = content.replace(/<\/div><div/g, "<\/div>\n<div");
                }
                content = content.replace(/><\/(td|iframe)>/g, ">xxx</$1>\n");
                content = content.replace(/(<a id=.+>)(<\/a>)/g, "$1xxx$2");
                while(content.search(/<(\w+)[^>]*><!-- pagebreak --><\/\1>[\r\n\s]*/)!==-1) content = content.replace(/<(\w+)[^>]*><!-- pagebreak --><\/\1>[\r\n\s]*/g, "<!-- pagebreak -->");
                while(content.search(/<(\w+)[^>]*>[\r\n]*<\/\1>[\r\n]*/)!==-1) content = content.replace(/<(\w+)[^>]*>[\r\n]*<\/\1>[\r\n\s]*/g, "");
                while(content.search(/<\/(\w+)><\1([^>]*)>/g)!==-1) content = content.replace(/<\/(\w+)><\1([^>]*)>/g, "");
                content = content.replace(/(<a id=.+>)xxx(<\/a>)/g, "$1$2");
                content = content.replace(/>xxx<\//g, "></");
                content = content.replace(/  /g, String.fromCharCode(160)+" ");
                content = content.replace(/(<(\w+)>[\s]*<\/\2>[\r\n]*)+$/g, "");
                content += "<"+tag+"></"+tag+">";
                content = content.replace(new RegExp('<!--' + marker + '-(\\d+)-->', 'g'), function (all, index) { return codeBlocks[Number(index)]; });
                editor.setContent(content);
            });
            editor.addCommand("ms_change", function() {
                let content = editor.getContent();
                if(content.indexOf("<div")===-1) {
                    content = content.replace(/<p(.*?)>(.+?)<\/p>/imsg, "<div$1>$2</div>");
                } else {
                    content = content.replace(/<div(.*?)>(.+?)<\/div>/imsg, "<p$1>$2</p>");
                }
                editor.setContent(content);
                editor.execCommand('ms_format');
            });
            editor.addButton('change', {
                title : 'Div/P 模式切换',
                image : global.root + 'static/images/div.png',
                cmd : 'ms_change'
            });
            editor.addButton('pastetext', {
                title : '无格式粘贴',
                icon : 'pastetext',
                onclick : function(e) {
                    let cd = (e.originalEvent || e).clipboardData || window.clipboardData;
                    if(cd!=null) {
                        insertContent(cd.getData('text'));
                    } else {
                        this.active(!this.active())
                        if(global.pastetext==null) global.pastetext = false;
                        global.pastetext = !global.pastetext;
                    }
                }
            });
            editor.addButton('upload', {
                title : '附件管理',
                image : global.root + 'static/images/file.gif',
                onclick : function(e) {
                    let content = tinyMCE.activeEditor.getContent();
                    let re1 = /<a href\="(.*?)api\/CMS\/attachment\/(.+?)".*?>(.+?)<\/a>/ig,
                        re2 = /<img.+?src\="(.*?)api\/CMS\/attachment\/(.+?)".*?>/ig;
                    let tags = [];
                    if(re1.test(content)) tags = content.match(re1);
                    if(re2.test(content)) tags = tags.concat(content.match(re2));
                    let m=tags.length;
                    if(m===0) {
                        //$('#attachment').modal('hide');
                        $('button[name=upload]').trigger('click');
                        return;
                    }
                    let container = $('#attachment').find('.modal-body');
                    container.empty();
                    for(let i=0;i<m;i++) {
                        re1.lastIndex = 0;
                        re2.lastIndex = 0;
                        let idx = '', name = '';
                        if(re1.test(tags[i])) {
                            idx = RegExp.$2;
                            name = RegExp.$3;
                        } else if(re2.test(tags[i])) {
                            idx = RegExp.$2;
                            name = RegExp.$2;
                        }
                        $('<li class="list-group-item d-flex justify-content-between align-items-center">\n' +
                            '<a href="'+setting.url_prefix+'api/myStep/download/'+idx+'">'+name+'</a>\n' +
                            '<a data-name="remove" data-idx="'+idx+'" data-file="'+name+'" href="javascript:" class="badge badge-danger badge-pill">X</a>\n' +
                            '</li>').appendTo(container);
                    }
                    container.find("a[data-name=remove]").click(function(e){
                        let idx = $(this).data('idx');
                        let name = $(this).data('file');
                        if(idx.length<10) return;
                        if(confirm('是否确认移除附件：'+name+' ?')) {
                            let obj = $(this).parent();
                            let re = new RegExp("<a.+?"+idx+".+?>.+?<\/a>");
                            re.global = true;
                            content = content.replace(re, '');
                            re = new RegExp("<img.+?"+idx+".+?>");
                            content = content.replace(re, '');
                            $.get(setting.url_prefix+'api/myStep/remove/'+idx, function(data, status){
                                if(data.error==='0' && status==='success') {
                                    tinyMCE.activeEditor.setContent(content);
                                    obj.remove();
                                    if(container.find('li').length===0) {
                                        $('#attachment').modal("hide");
                                    }
                                } else {
                                    alert(data.error);
                                }
                            }, 'json');
                        }
                        return false;
                    });
                    $('#attachment').modal("show");
                }
            });
            editor.addButton('alert', {
                text: '提示文字',
                title : '突出显示提示文字',
                type: 'listbox',
                values: [
                    { text: '主题蓝', value: 'primary' },
                    { text: '加重灰', value: 'secondary' },
                    { text: '成功绿', value: 'success' },
                    { text: '警告红', value: 'danger' },
                    { text: '提示黄', value: 'warning' },
                    { text: '信息青', value: 'info' },
                ],
                onselect: function(e) {
                    this.text('提示文字');
                    let brush = this.value();
                    let str = editor.selection.getContent();
                    if(str.length===0) return;
                    if(/^<div class="alert alert-.+?>(.*?)<\/div>$/ism.test(str)) {
                        str = RegExp.$1;
                    }
                    str = str.replace(/<(p|div).*?>/g, '');
                    str = str.replace(/<\/(p|div)>/g, '<br />');
                    str = str.replace(/<br \/>/g, '</div><div>');
                    insertContent('<div class="alert alert-'+brush+' my-3" data-type="'+brush+'"><div>'+str+'</div></div>');
                },
                onPostRender: function() {
                    let ctrl = this;
                    editor.on('click', function(e) {
                        e = e.target;
                        let list = {
                            'primary': '主题蓝',
                            'secondary': '加重灰',
                            'success': '成功绿',
                            'danger': '警告红',
                            'warning': '提示黄',
                            'info': '信息青'
                        };
                        if(e.parentNode.nodeName === 'DIV' && editor.dom.hasClass(e.parentNode, "alert")) e = e.parentNode;
                        if (e.nodeName === 'DIV' && editor.dom.hasClass(e, "alert")) {
                            let type = e.getAttribute('data-type').toLocaleLowerCase();
                            ctrl.value(type);
                            ctrl.text(list[type]);
                        } else {
                            ctrl.value(null);
                            ctrl.text('提示文字');
                        }
                    });
                }
            });
            editor.on('dragover',function(e){
                $('#cover').show().text('松开鼠标以上传！');
            });
            editor.on('init', function(){
                uploadInit();
            });
            editor.on('paste',function(e){
                if(e.defaultPrevented || editor.dom.getParent(editor.selection.getNode(), 'pre.highlight')) return;
                let cd = (e.originalEvent || e).clipboardData || window.clipboardData;
                let content = cd.getData('text/html');
                let re = null;
                if(content.length===0 || global.pastetext) {
                    content = cd.getData('text/plain').replace(/[\r\n]+/g, '<br />');
                    content = content.replace(/\t/g, ' &nbsp; &nbsp;');
                } else {
                    content = content.replace(/style=("|')(.+?)\1/sg, '');
                    if(content.indexOf('schemas-microsoft')>=0) {
                        global.content = content;
                        content = content.replace(/\r/g, '');
                        content = content.replace(/^.*<!--StartFragment-->(.+?)<!--EndFragment-->.*$/s, '$1');
                        content = content.replace(/<span[^>]*>/g, '');
                        content = content.replace(/<\/span>/g, '');
                        content = content.replace(/ class=Mso.+? /ig, " ");
                        content = content.replace(/<!.+?>/g, '');
                        content = content.replace(/<\w+:.+?\/>/g, '');
                        content = content.replace(/<v:(\w+).*?>.+?<\/v:\1>/sg, '');
                        content = content.replace(/style='(.+?)'/sg, "style='$1;'");
                        content = content.replace(/mso-[\w\-]+:.+?;/sg, '');
                        content = content.replace(/ style=''/g, '');
                        content = content.replace(/[\n\s]+lang=[\w\-]+/g, '');
                        content = content.replace(/[\n\s]+(\w+=)/g, ' $1');
                        content = content.replace(/[\n](['";])/g, '$1');
                        content = content.replace(/(['"])\n+/g, '$1 ');
                        content = content.replace(/([;:])\n+/g, '$1');
                        content = content.replace(/\w+:\w+=".+?"/g, '');
                        content = content.replace(/<a name.+?<\/a>/ig, '');
                        re = /<([\w:]+)>\s*<\/\1>/sg;
                        while(content.search(re)!==-1) content = content.replace(re, '');
                        re = /<\/([\w:]+)>\s*<\1>/sg;
                        while(content.search(re)!==-1) content = content.replace(re, '');
                        if(content.match(/src="(file:.+?)"/)) {
                            copy(RegExp.$1.replace(/[^\/\\]+$/,''));
                            let cmd = 'Win+R，Ctrl+V';
                            if(navigator.userAgent.indexOf('Macintosh')>0) cmd = 'Finder下按Cmd+K，Cmd+V';
                            alert('监测到所粘贴内容中含有本地文件，文件存放路径已经复制，\n' +
                                '请自行打开（'+cmd+'），\n' +
                                '并将对应文件拖拽到编辑框即可完成上传！' );
                        }
                    }
                }
                if(content.length>0) {
                    content = content.replace(/&#32;/g, " ");
                    content = content.replace(/  /g, String.fromCharCode(160)+" ");
                    insertContent(content);
                    e.preventDefault();
                    e.returnValue = false;
                    return false;
                }
                /*
                // items[0] - "text/plain"， items[1] - "text/html"
                let item = cd.items[0];
                if(item==null) return;
                if (item.kind === "string" && item.type === "text/plain") {
                    if(global.pastetext) {
                        item.getAsString(function (str) {
                            str = str.replace(/[\r\n]+/g, '<br />');
                            insertContent(str);
                        })
                        e.preventDefault();
                    }
                } else if (item.kind === "file") {
                    let pasteFile = item.getAsFile();
                    let reader = new FileReader();
                    reader.onload = function (event) {
                        insertContent('<img src="'+event.target.result+'" />');
                    };
                    reader.readAsDataURL(pasteFile);
                    e.preventDefault();
                } else {
                    c(item.kind, item.type);
                }
                */
            });
        }
    });
    tinyMCE.PluginManager.add('myStep_cms', tinyMCE.plugins.myStep_cms);
    $.getScript(global.root+'cache/script/editor_plugin_'+setting.app+'.js', function(){
        global.editor_btn += ' upload change'
        if(typeof(setting_tinymce_ext)!='undefined') $.extend(setting_tinymce, setting_tinymce_ext);
        if(typeof(setting_tinymce_btn)!='undefined') setting_tinymce.toolbar2 += ' | '+setting_tinymce_btn;
        if(typeof(setting_tinymce_css)!='undefined') setting_tinymce.content_css.push(setting_tinymce_css);
        setting_tinymce.toolbar2 = global.editor_btn + ' | ' + setting_tinymce.toolbar2;
        tinyMCE.init(setting_tinymce);
    });
});