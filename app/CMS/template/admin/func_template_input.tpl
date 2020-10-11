<div class="card border-bottom-0 bg-transparent mb-5">
    <div class="card-header bg-info text-white position-fixed w-100 title">
        <i class="glyphicon glyphicon-circle-arrow-right"></i> <b><!--title--></b>
    </div>
    <div class="card-body p-0 table-responsive mt-5">
        <form class="col-sm-12 col-md-10 offset-md-1 col-lg-8 offset-lg-2 px-0 py-2" method="post" action="<!--method-->_ok">
            <div class="input-group mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text item-name">所属模板</span>
                </div>
                <div class="form-control">
                    <!--file_idx--><input name="idx" type="hidden" value="<!--file_idx-->" />
                </div>
            </div>
            <div class="input-group mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text item-name">文件名称</span>
                </div>
                <input name="file_name" class="form-control" type="text" maxlength="20" value="<!--file_name-->" <!--readonly--> required />
            </div>
            <div class="w-100 m-0 border">
                <textarea id="file_content" name="file_content" class="w-100 border-0" style="height:300px;"><!--file_content--></textarea>
                <div class="progress" style="height:5px;">
                    <div id="progress_bar" class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%"></div>
                </div>
                <div id="info" class="text-muted small"></div>
            </div>
            <div class="position-fixed bg-white border-top w-100" style="right:0;bottom:0;z-index:9;">
                <div class="float-right p-2 border-0">
                    <button class="btn btn-primary btn-sm mr-3" type="submit"> 确 认 </button>
                    <button class="btn btn-primary btn-sm" type="button" onClick="location.href='<!--back_url-->'"> 返 回 </button>
                </div>
            </div>
        </form>
    </div>
</div>
<script  type="text/javascript">
$(function() {
    $.setCSS([
        'vendor/codemirror/lib/codemirror.css',
        'vendor/codemirror/addon/fold/foldgutter.css',
        'vendor/codemirror/addon/display/fullscreen.css'
    ]);
    $.setJS([
        "vendor/codemirror/lib/codemirror.js",
        "vendor/codemirror/mode/php/php.js",
        "vendor/codemirror/addon/edit/matchbrackets.js",
        "vendor/codemirror/addon/selection/active-line.js",
        "vendor/codemirror/mode/clike/clike.js",
        "vendor/codemirror/addon/fold/foldcode.js",
        "vendor/codemirror/addon/fold/foldgutter.js",
        "vendor/codemirror/addon/fold/brace-fold.js",
        "vendor/codemirror/addon/fold/xml-fold.js",
        "vendor/codemirror/addon/fold/indent-fold.js",
        "vendor/codemirror/addon/fold/comment-fold.js",
        "vendor/codemirror/addon/display/fullscreen.js",
        "vendor/codemirror/mode/javascript/javascript.js",
        "vendor/codemirror/mode/xml/xml.js",
        "vendor/codemirror/mode/css/css.js",
        "vendor/codemirror/mode/htmlmixed/htmlmixed.js",
    ], true, function() {
        let editor = CodeMirror.fromTextArea($id("file_content"), {
            mode: "<!--file_type-->",
            indentUnit: 4,
            indentWithTabs: true,
            matchBrackets: true,    //括号匹配
            styleActiveLine: true,  //高亮当前行
            lineNumbers: true,
            lineWrapping: false,
            foldGutter: true,
            gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"]
        });
        editor.setOption("extraKeys", {
            // Tab键换成4个空格
            Tab: function(cm) {
                let spaces = Array(cm.getOption("indentUnit") + 1).join(" ");
                cm.replaceSelection(spaces);
            },
            // Ctrl+F11键切换全屏
            "Ctrl-F11": function(cm) {
                cm.setOption("fullScreen", !cm.getOption("fullScreen"));
            },
            // Ctrl+Enter提交
            "Ctrl-Enter": function(cm) {
                $('form').submit();
            },
            // Esc键退出全屏
            "Esc": function(cm) {
                if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
            },
            // 折叠、展开标签
            "Ctrl-Q": function(cm) {
                cm.foldCode(cm.getCursor());
            }
        });
        $('#info').remove();
    }, function(num_done, num_total, script) {
        let obj = $('#progress_bar');
        obj.width(obj.parent().width() * Math.ceil(num_done/num_total));
        $('#info').html('脚本 ' + script + ' 已载入！');
        if(num_done===num_total) {
            $('#info').html('代码处理中。。。');
            obj.parent().remove();
        }
    });
    global.root_fix += 'function/template/';
});
</script>