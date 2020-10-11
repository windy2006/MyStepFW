<div class="card w-100 mb-5 mb-sm-2">
    <div class="card-body p-0">
        <textarea id="content" name="content" style="width:100%; height:400px;">
&lt;!DOCTYPE html&gt;
&lt;html&gt;
&lt;head&gt;
&lt;/head&gt;
&lt;body&gt;
&lt;p style=&quot;text-align: center;&quot;&gt;&lt;span style=&quot;color: #bdc3c7;&quot;&gt;&amp;rarr; This is a full-featured editor demo. Please explore! &amp;larr;&lt;/span&gt;&lt;/p&gt;
&lt;p style=&quot;text-align: center;&quot;&gt;&amp;nbsp;&lt;/p&gt;
&lt;h2 style=&quot;text-align: center;&quot;&gt;TinyMCE is &lt;span style=&quot;text-decoration: underline;&quot;&gt;your best choice&lt;/span&gt; for building modern, &lt;br /&gt;internationalized and accessible content creation experiences.&lt;/h2&gt;
&lt;p style=&quot;text-align: center;&quot;&gt;&amp;nbsp;&lt;/p&gt;
&lt;p style=&quot;text-align: center;&quot;&gt;&lt;span style=&quot;font-size: 14pt;&quot;&gt; &lt;strong&gt; &lt;span style=&quot;color: #7e8c8d;&quot;&gt;No matter what you're building, TinyMCE has got you covered.&lt;/span&gt; &lt;/strong&gt; &lt;/span&gt;&lt;/p&gt;
&lt;table style=&quot;border-collapse: collapse; width: 85%; margin-left: auto; margin-right: auto; border: 0;&quot;&gt;
&lt;tbody&gt;
&lt;tr&gt;
&lt;td style=&quot;width: 25%; text-align: center; padding: 7px;&quot;&gt;&lt;span style=&quot;color: #95a5a6;&quot;&gt;🛠 50+ Plugins&lt;/span&gt;&lt;/td&gt;
&lt;td style=&quot;width: 25%; text-align: center; padding: 7px;&quot;&gt;&lt;span style=&quot;color: #95a5a6;&quot;&gt;💡 Premium Support&lt;/span&gt;&lt;/td&gt;
&lt;td style=&quot;width: 25%; text-align: center; padding: 7px;&quot;&gt;&lt;span style=&quot;color: #95a5a6;&quot;&gt;🖍 Custom Skins&lt;/span&gt;&lt;/td&gt;
&lt;td style=&quot;width: 25%; text-align: center; padding: 7px;&quot;&gt;&lt;span style=&quot;color: #95a5a6;&quot;&gt;⚙ Full API Access&lt;/span&gt;&lt;/td&gt;
&lt;/tr&gt;
&lt;/tbody&gt;
&lt;/table&gt;
&lt;/body&gt;
&lt;/html&gt;
        </textarea>
    </div>
</div>
<script type="application/javascript" src="vendor/tinymce/tinymce.min.js"></script>
<script type="text/javascript">
function tinymceInit(obj){
    if(!checkSetting()) return;
    if(obj==null) obj = 'textarea';
    tinymce.init({
        language:'zh_CN',
        selector:obj,
        plugins: [
            "advlist autolink autosave link image lists charmap print preview hr anchor pagebreak spellchecker",
            "searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
            "table contextmenu directionality emoticons template textcolor paste fullpage textcolor",
            "importcss"
        ],

        toolbar1 : "code,fullscreen,|,undo,redo,searchreplace,hr,|,alignleft,aligncenter,alignright,|,bold,italic,underline,strikethrough,subscript,superscript,removeformat,|,forecolor,backcolor,|,table,charmap,|,fontselect,fontsizeselect",
        toolbar2 : "styleselect,formatselect,|,cut,copy,paste,pastetext,|,bullist,numlist,|,outdent,indent,blockquote,|,link,unlink,anchor,image,media,|,change,format",
        toolbar3 : "",
        toolbar_items_size: 'small',

        menubar: false,
        /*
        menubar: 'file edit insert view format table',
        menu: {
            file: {title: 'File', items: 'newdocument'},
            edit: {title: 'Edit', items: 'undo redo | cut copy paste pastetext | selectall'},
            insert: {title: 'Insert', items: 'link media | template hr'},
            view: {title: 'View', items: 'visualaid'},
            format: {title: 'Format', items: 'bold italic underline strikethrough superscript subscript | formats | removeformat'},
            table: {title: 'Table', items: 'inserttable tableprops deletetable | cell row column'},
        },
        */
        // Custom settings
        content_css : setting.path_root+"static/css/bootstrap.css",
        convert_urls: false,
        remove_script_host: false,
        preformatted : false,
        remove_linebreaks : false,
        apply_source_formatting : true,
        convert_fonts_to_spans : true,
        verify_html : true,
        paste_auto_cleanup_on_paste : true,
        dialog_type : "modal",
        relative_urls : true,
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
        entity_encoding : "raw",
        add_unload_trigger : false,
        fontsize_formats: "8pt 10pt 12pt 14pt 18pt 24pt 36pt",
        font_formats: "微软雅黑='微软雅黑';宋体='宋体';黑体='黑体';仿宋='仿宋';楷体='楷体';隶书='隶书';幼圆='幼圆';Arial='Arial';Arial Black='Arial Black';Times New Roman='Times New Roman';Impact='Impact';Webdings='Webdings';Wingdings='Wingdings';",

        // Custom Functions
        setup : function(ed) {
            ed.addButton('change', {
                title : 'Div/P 模式切换',
                image : setting.path_root+'static/images/div.png',
                onclick : function() {
                    let content = tinyMCE.activeEditor.getContent();
                    if(content.indexOf("<div")==-1) {
                        content = content.replace(/<p(.*?)>([\w\W]+?)<\/p>/ig, "<div$1>$2</div>");
                    } else {
                        content = content.replace(/<div(.*?)>([\w\W]+?)<\/div>/ig, "<p$1>$2</p>");
                    }
                    tinyMCE.activeEditor.setContent(content);
                }
            });
            ed.addButton('format', {
                title : '代码清理',
                image : 'static/images/format.png',
                onclick : function() {
                    let content = tinyMCE.activeEditor.getContent();
                    if(content.indexOf("<div")==-1) {
                        content = content.replace(/(<br(\s\/)?>)+/ig, "</p><p>");
                        content = content.replace(/<p(.*?)>[\xa0\r\n\s\u3000]+/ig, "<p$1>");
                        content = content.replace(/<\/p><p/g, "<\/p>\n<p");
                    } else {
                        content = content.replace(/(<br(\s\/)?>)+/ig, "</div><div>");
                        content = content.replace(/<div(.*?)>[\xa0\r\n\s\u3000]+/ig, "<div$1>");
                        content = content.replace(/<\/div><div/g, "<\/div>\n<div");
                    }
                    content = content.replace(/mso\-[^;]+?;/ig, "");
                    content = content.replace(/[\xa0]/g, "");
                    content = content.replace(/<\/td>/g, "&nbsp;</td>");
                    while(content.search(/<(\w+)[^>]*><\!\-\- pagebreak \-\-\><\/\1>[\r\n\s]*/)!=-1) content = content.replace(/<(\w+)[^>]*><\!\-\- pagebreak \-\-\><\/\1>[\r\n\s]*/g, "<!-- pagebreak -->");
                    while(content.search(/<(\w+)[^>]*>[\s\r\n]*<\/\1>[\r\n\s]*/)!=-1) content = content.replace(/<(\w+)[^>]*>[\s\r\n]*<\/\1>[\r\n\s]*/g, "");
                    while(content.search(/<\/(\w+)><\1([^>]*)>/g)!=-1) content = content.replace(/<\/(\w+)><\1([^>]*)>/g, "");
                    content = content.replace(/  /g, String.fromCharCode(160)+" ");
                    tinyMCE.activeEditor.setContent(content);
                }
            });
        }
    });
}
tinymceInit();
</script>