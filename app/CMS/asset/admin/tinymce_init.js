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
        content_css : [global.root + "static/css/bootstrap.css"],
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
        fontsize_formats: "8pt 10pt 12pt 14pt 18pt 24pt 36pt",
        font_formats: "微软雅黑='微软雅黑';宋体='宋体';黑体='黑体';仿宋='仿宋';楷体='楷体';隶书='隶书';幼圆='幼圆';Arial='Arial';Arial Black='Arial Black';Times New Roman='Times New Roman';Impact='Impact';Webdings='Webdings';Wingdings='Wingdings';",
};
function insertContent(str) {
    tinyMCE.activeEditor.execCommand("mceInsertContent", false, str);
}
function setHighlight(editor, brush) {
    let str = editor.selection.getContent({format:'html'});
    if(str.length===0) return;
    if(/^<pre class="highlight".+?><code.+?>(.+?)<\/code><\/pre>$/ism.test(str)) {
        str = RegExp.$1;
    }
    let symbol = '　　';
    str = str.replace(new RegExp(symbol, 'g'), '');
    str = str.replace(/<br.*?>/g, '\n');
    str = str.replace(/\r/g, '\n');
    str = str.replace(/<.+?>/g, '');
    str = str.replace(/[\n]+/g, symbol+'\n');
    str = str.replace(/ /g, '&nbsp;');
    str = str.replace(/\t/g, '&nbsp; &nbsp; ');
    if(brush!=='') {
        str = '<pre class="highlight" data-lng="'+brush+'"><code class="language-'+brush+'">'+str+'<\/code></pre>';
    } else {
        str = str.replace(new RegExp(symbol, 'g'), '<br />\n');
        str = '<div>' + str + '</div>';
    }
    insertContent(str);
}
function uploadInit() {
    if(!checkSetting()) return;
    $.vendor('jquery.powerUpload', {
        callback:function(){
            let opts = {
                url: setting.url_prefix+'api/myStep/upload',
                title: '请选择需要上传的文件',
                mode: 'browse',
                max_files: 5,
                max_file_size: setting.max_size,
                errors: ["浏览器不支持", "上传文件过多", "每个文件必须小于"+setting.max_size+"MB", "未设置上传目标", "更新文件未选择"],
            }
            $('#upload').powerUpload($.extend({}, opts, {
                max_files: 1,
                uploadFinished:function(i,file,result,timeDiff){
                    if(result.error!=="0") {
                        alert("上传失败！\n原因：" + result.message);
                    } else {
                        $('#uploader').find(".modal-title > b").html("上传完成，请关闭本对话框！");
                        $("input[name=image]").val(setting.url_prefix+'api/myStep/download/'+result.new_name.split('.').slice(0,2).join('.'));
                        $('#uploader').unbind('hidden.bs.modal').on('hidden.bs.modal', function(e){
                            $("input[name=image]").select();
                        });
                    }
                }
            }));
            opts = $.extend(opts, {
                title: '附件上传',
                uploadFinished:function(i,file,result,timeDiff){
                    let obj = $('#uploader').find(".progress[data-idx="+i+"] > div");
                    if(result.error!=="0") {
                        obj.html(obj.html()+' - upload failed! ('+result.message+')');
                    } else {
                        obj.html(obj.html()+' - uploaded!');
                        let editor = tinyMCE.activeEditor;
                        let content = editor.getContent();
                        if(content.match(new RegExp('"(file://.+?/'+result.name+')"'))) {
                            content = content.replace(RegExp.$1, setting.url_prefix+'api/CMS/attachment/'+result.new_name);
                            editor.setContent(content);
                        } else {
                            let file = setting.url_prefix+'api/CMS/attachment/'+result.new_name;
                            if(result.type.match(/^image/) || result.name.match(/\.png$/)) {
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
                allDone:function(){
                    $('#uploader').find(".modal-title > b").html("全部文件上传完成，请关闭本对话框！");
                    $('#cover').hide();
                    $('#uploader').unbind('hidden.bs.modal').on('hidden.bs.modal', function(e){
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
            editor.addCommand("ms_format", function() {
                let content = editor.getContent();
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
                            '<a href='+setting.url_prefix+'"api/myStep/download/'+idx+'">'+name+'</a>\n' +
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
            editor.addButton('highlight', {
                text: '高亮代码',
                title : '代码高亮显示',
                type: 'listbox',
                values: [
                    { text: 'PHP', value: 'php' },
                    { text: 'HTML', value: 'html' },
                    { text: 'CSS', value: 'css' },
                    { text: 'JAVASCRIPT', value: 'javascript' },
                    { text: 'SQL', value: 'sql' },
                    { text: '取消高亮', value: '' },
                ],
                onselect: function(e) {
                    let brush = this.value();
                    let obj = editor.selection.getNode();
                    if(obj.tagName==='CODE') {
                        editor.selection.select(obj.parentNode);
                    }
                    setHighlight(editor, brush);
                    this.text('高亮代码');
                },
                onPostRender: function() {
                    let ctrl = this;
                    editor.on('click', function(e) {
                        e.cancelBubble = true;
                        e.stopPropagation();
                        e = e.target;
                        if(e.nodeName === 'CODE') e = e.parentNode;
                        if (e.nodeName === 'PRE' && editor.dom.hasClass(e, "highlight")) {
                            let type = e.getAttribute('data-lng').toLocaleLowerCase();
                            //editor.selection.select(e);
                            ctrl.value(type);
                            ctrl.text(type.toUpperCase());
                        } else {
                            ctrl.value(null);
                            ctrl.text('高亮代码');
                        }
                    });
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
                editor.dom.loadCSS(global.root + "static/css/tinymce.css");
                uploadInit();
            });
            editor.on('paste',function(e){
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
    $.getScript(global.root+'cache/script/editor_plugin_cms.js', function(){
        global.editor_btn += ' upload change'
        if(typeof(setting_tinymce_ext)!='undefined') $.extend(setting_tinymce, setting_tinymce_ext);
        if(typeof(setting_tinymce_btn)!='undefined') setting_tinymce.toolbar2 += ' | '+setting_tinymce_btn;
        if(typeof(setting_tinymce_css)!='undefined') setting_tinymce.content_css.push(setting_tinymce_css);
        setting_tinymce.toolbar2 = global.editor_btn + ' | ' + setting_tinymce.toolbar2;
        tinyMCE.init(setting_tinymce);
    });
});