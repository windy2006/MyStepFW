<div class="card mb-5 mb-sm-2">
    <div class="card-header bg-info text-white">
        <b><span class="glyphicon glyphicon-list-alt"></span> 标题栏 - <!--txt--></b>
    </div>
    <div class="card-body p-0 table-responsive">
        <table class="table table-striped table-hover m-0">
            <tr>
                <td width="100">模块测试</td>
                <td>
                    <a href="<!--url_prefix-->module/test1" target="_blank">独立页面</a> |
                    <a href="<!--url_prefix-->module/test2" target="_blank">模版融合</a>
                </td>
            </tr>
            <tr>
                <td width="100">API测试</td>
                <td>
                    <a href="<!--url_prefix-->api/plugin_sample/sample/json" target="_blank">JSON</a> |
                    <a href="<!--url_prefix-->api/plugin_sample/sample/xml" target="_blank">XML</a> |
                    <a href="<!--url_prefix-->api/plugin_sample/sample/script" target="_blank">SCRIPT</a> |
                    <a href="<!--url_prefix-->api/plugin_sample/sample/string" target="_blank">STRING</a> |
                    <a href="<!--url_prefix-->api/plugin_sample/sample/hex" target="_blank">HEX</a>
                </td>
            </tr>
            <tr>
                <td width="100">标签测试</td>
                <td>
                    <!--test_tag attr="自定义模版标签属性"-->
                </td>
            </tr>
            <tr>
                <td width="100">语言包测试</td>
                <td>
                    <!--lng_test_lng--> - <!--lng_plugin_sample_info_description-->
                </td>
            </tr>
            <tr>
                <td>编辑器测试<br />
                    <a href="<!--sample_cache_url|html-->">(如无法显示自定义功能请点击本链接清除缓存)</a></td>
                <td>
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
                </td>
            </tr>
        </table>
    </div>
</div>
<script type="application/javascript" src="<!--root_web-->vendor/tinymce/tinymce.min.js"></script>
<script type="text/javascript">
let setting_tinymce = {
    language:'zh_CN',
    selector:'#content',
    editor_encoding:'raw',
    entity_encoding:'raw',
    plugins: [
        "advlist autolink autosave link image imagetools lists charmap print preview hr anchor pagebreak",
        "searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
        "table contextmenu directionality emoticons template textcolor textcolor importcss myStep"
    ],

    toolbar1 : "code pastetext searchreplace | bold italic underline removeformat | alignleft aligncenter alignright | bullist numlist | outdent indent hr blockquote | forecolor backcolor | fullscreen",
    toolbar2 : "fontselect fontsizeselect formatselect alert | link anchor image media",
    toolbar3 : "",
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
    autosave_ask_before_unload: false,
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

$.getScript(global.root+'cache/script/editor_plugin_myStep.js', function(){
    setting_tinymce.toolbar3 = global.editor_btn;
    tinyMCE.init(setting_tinymce);
});
</script>