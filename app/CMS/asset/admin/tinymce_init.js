let setting_tinymce = {
        language:'zh_CN',
        selector:'#content',
        editor_encoding:'raw',
        entity_encoding:'raw',
        plugins: [
            "advlist autolink autosave link image imagetools lists charmap print preview hr anchor pagebreak spellchecker",
            "searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
            "table contextmenu directionality emoticons template textcolor paste textcolor importcss myStep myStep_cms"
        ],

        toolbar1 : "code pastetext searchreplace | bold italic underline removeformat | alignleft aligncenter alignright | bullist numlist | outdent indent hr blockquote | forecolor backcolor | fullscreen",
        toolbar2 : "fontselect fontsizeselect formatselect | link anchor image media",
        toolbar_items_size: 'small',

        //menubar: false,
        menubar: 'edit view insert format table',
        menu: {
            edit: {title: 'Edit', items: 'undo redo | cut copy paste pastetext | selectall'},
            view: {title: 'View', items: 'code | visualaid visualchars visualblocks | spellchecker | preview fullscreen'},
            insert: {title: 'Insert', items: 'link image media | insert charmap emoticons hr | pagebreak nonbreaking anchor | insertdatetime'},
            format: {title: 'Format', items: 'bold italic underline strikethrough superscript subscript | formats fonts'},
            table: {title: 'Table', items: 'inserttable tableprops deletetable | cell row column'},
        },

        // Custom settings
        content_css : global.root + "static/css/bootstrap.css",
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
tinymce.create('tinymce.plugins.myStep_cms', {
    init : function(editor, url) {
        editor.addButton('change', {
            title : 'Div/P 模式切换',
            image : global.root + 'static/images/div.png',
            onclick : function() {
                let content = editor.selection.getContent();
                if(content.length===0) {
                    content = editor.getContent();
                    editor.execCommand("SelectAll");
                }
                if(content.indexOf("<div")===-1) {
                    content = content.replace(/<p(.*?)>(.+?)<\/p>/imsg, "<div$1>$2</div>");
                } else {
                    content = content.replace(/<div(.*?)>(.+?)<\/div>/imsg, "<p$1>$2</p>");
                }
                editor.execCommand("mceInsertContent", false, content);
                content = editor.getContent().replace(/(<\/?\w+>)[\r\n\s]*\1/g, "$1");
                editor.setContent(content);
            }
        });
        editor.addButton('format', {
            title : '代码清理',
            image : global.root + 'static/images/format.png',
            onclick : function() {
                let content = editor.getContent();
                if(content.indexOf("<div")===-1) {
                    content = content.replace(/(<br(\s\/)?>)+/ig, "</p><p>");
                    content = content.replace(/<p(.*?)>[\xa0\r\n\s\u3000]+/ig, "<p$1>");
                    content = content.replace(/<\/p><p/g, "<\/p>\n<p");
                } else {
                    content = content.replace(/(<br(\s\/)?>)+/ig, "</div><div>");
                    content = content.replace(/<div(.*?)>[\xa0\r\n\s\u3000]+/ig, "<div$1>");
                    content = content.replace(/<\/div><div/g, "<\/div>\n<div");
                }
                content = content.replace(/mso-[^;]+?;/ig, "");
                content = content.replace(/[\xa0]/g, "");
                content = content.replace(/<\/td>/g, "&nbsp;</td>");
                content = content.replace(/(<a id=.+>)(<\/a>)/g, "$1&nbsp;$2");
                while(content.search(/<(\w+)[^>]*><!-- pagebreak --><\/\1>[\r\n\s]*/)!==-1) content = content.replace(/<(\w+)[^>]*><!-- pagebreak --><\/\1>[\r\n\s]*/g, "<!-- pagebreak -->");
                while(content.search(/<(\w+)[^>]*>[\s\r\n]*<\/\1>[\r\n\s]*/)!==-1) content = content.replace(/<(\w+)[^>]*>[\s\r\n]*<\/\1>[\r\n\s]*/g, "");
                while(content.search(/<\/(\w+)><\1([^>]*)>/g)!==-1) content = content.replace(/<\/(\w+)><\1([^>]*)>/g, "");
                content = content.replace(/  /g, String.fromCharCode(160)+" ");
                editor.setContent(content);
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
                { text: '取消高亮', value: '' },
            ],
            onselect: function(e) {
                setHighlight(editor, this.value());
                this.text('高亮代码');
            },
            onPostRender: function() {
                let ctrl = this;
                editor.on('click', function(e) {
                    e = e.target;
                    if (e.nodeName === 'PRE' && editor.dom.hasClass(e, "highlight")) {
                        let cls = e.getAttribute('class').replace(/^.+brush:(.+)$/, '$1');
                        editor.selection.select(e);
                        ctrl.value(cls.toLocaleLowerCase());
                    } else {
                        ctrl.text('高亮代码');
                    }
                });
            }
        });
        editor.on('dragover',function(e){
            $('#cover').show().text('松开鼠标以上传！');
        });
        editor.on('init', function(){
            editor.dom.loadCSS(global.root + "static/css/tinymce.css");
        });
    },
    createControl : function(n, cm) {
        return null;
    },
});
tinymce.PluginManager.add('myStep_cms', tinymce.plugins.myStep_cms);
$.getScript(global.root+'cache/script/editor_plugin_cms.js', function(){
    global.editor_btn += ' upload change format'
    if(typeof(setting_tinymce_ext)!='undefined') $.extend(setting_tinymce, setting_tinymce_ext);
    if(typeof(setting_tinymce_btn)!='undefined') setting_tinymce.toolbar2 += ' | '+setting_tinymce_btn;
    setting_tinymce.toolbar2 = global.editor_btn + ' | ' + setting_tinymce.toolbar2;
    tinymce.init(setting_tinymce);
});
function insertContent(str) {
    tinyMCE.execCommand("mceInsertContent", false, str);
}
function setHighlight(editor, brush) {
    let sel = editor.selection.getContent();
    if(sel.length===0) return;
    let str = sel.replace(/<br.*?>/g, '\n');
    str = str.replace(/\s\s/g, '&emsp;&emsp;');
    str = str.replace(/<.+?>/g, '');
    if(str.length>0) {
        if(/^<pre class="highlight brush:(.+?)">(.+?)<\/pre>$/ism.test(sel)) {
            str = RegExp.$2;
        }
        if(brush!=='') {
            str = '<pre class="highlight brush:'+brush+'" data-type="'+brush+'">\n'+str+'\n</pre>';
            str += '<div></div>';
        } else {
            str = '<div>' + str.replace(/\n+/g, '</div><div>') + '</div>';
        }
        editor.execCommand('mceInsertContent',false, str);
    }
}
function uploadInit() {
    if(!checkSetting()) return;
    $.vendor('jquery.powerupload', {
        callback:function(){
            $('#upload').powerUpload({
                url: setting.url_prefix+'api/myStep/upload',
                title: '请选择需要上传的文件',
                mode: 'browse',
                max_files: 1,
                max_file_size: 8,
                errors: ["浏览器不支持", "一次只能上传1个文件", "每个文件必须小于8MB", "未设置上传目标", "更新文件未选择"],
                uploadFinished:function(i,file,result,timeDiff){
                    if(result.error!=0) {
                        alert("上传失败！\n原因：" + result.message);
                    } else {
                        $('#uploader').find(".modal-title > b").html("上传完成，请关闭本对话框！");
                        $("input[name=image]").val('<!--url_prefix-->api/myStep/download/'+result.new_name.split('.').slice(0,2).join('.'));
                        $('#uploader').unbind('hidden.bs.modal').on('hidden.bs.modal', function(e){
                            $("input[name=image]").select();
                        });
                    }
                }
            });
            $('#cover').powerUpload({
                url: setting.url_prefix+'api/myStep/upload',
                title: '附件上传',
                mode: 'drop',
                max_files: 5,
                max_file_size: 8,
                errors: ["浏览器不支持", "同时上传最多5个文件", "每个文件必须小于8MB", "未设置上传目标", "更新文件未选择"],

                uploadFinished:function(i,file,result,timeDiff){
                    let obj = $('#uploader').find(".progress[data-idx="+i+"] > div");
                    if(result.error!=0) {
                        obj.html(obj.html()+' - upload failed! ('+result.message+')');
                    } else {
                        obj.html(obj.html()+' - uploaded!');
                        let file = '<!--url_prefix-->api/CMS/attachment/'+result.new_name.split('.').slice(0,2).join('.');
                        if(result.type.match(/^image/)) {
                            insertContent('<br /><img src="'+file+'" title="'+result.name+'" style="max-width:90%;" /><br />');
                        } else {
                            insertContent('<br /><a href="'+file+'" />'+result.name+'</a><br />');
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

                error:function(msg){
                    alert(msg);
                    $('#cover').hide();
                    tinyMCE.activeEditor.focus();
                }
            });
            $('button[name=upload]').powerUpload({
                url: setting.url_prefix+'api/myStep/upload',
                title: '附件上传',
                mode: 'browse',
                max_files: 5,
                max_file_size: 8,
                errors: ["浏览器不支持", "同时上传最多5个文件", "每个文件必须小于8MB", "未设置上传目标", "更新文件未选择"],
                uploadFinished:function(i,file,result,timeDiff){
                    let obj = $('#uploader').find(".progress[data-idx="+i+"] > div");
                    if(result.error!=0) {
                        obj.html(obj.html()+' - upload failed! ('+result.message+')');
                    } else {
                        obj.html(obj.html()+' - uploaded!');
                        let file = '<!--url_prefix-->api/CMS/attachment/'+result.new_name.split('.').slice(0,2).join('.');
                        if(result.type.match(/^image/)) {
                            insertContent('<br /><img src="'+file+'" title="'+result.name+'" style="max-width:90%;" /><br />');
                        } else {
                            insertContent('<br /><a href="'+file+'" />'+result.name+'</a><br />');
                        }
                    }
                },
                allDone:function(){
                    $('#uploader').find(".modal-title > b").html("全部文件上传完成，请关闭本对话框！");
                    $('#uploader').unbind('hidden.bs.modal').on('hidden.bs.modal', function(e) {
                        tinyMCE.activeEditor.focus();
                    })
                }
            });
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
    uploadInit();
});
