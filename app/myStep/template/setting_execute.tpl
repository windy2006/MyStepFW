<div class="card w-100 mb-3 mb-sm-2">
    <div class="card-header bg-info text-white">
        <b><span class="glyphicon glyphicon-cog"></span> 代码测试</b>
    </div>
    <div class="card-body p-0 pt-3">
        <div class="col-12 px-3">
            <h3>PHP代码<span class="small text-muted">（可修改部分代码后按 "Ctrl+Enter" 提交运行）</span><button type="submit" class="float-right btn btn-sm btn-primary" onclick="$('form').submit()">运行代码</button></h3>
            <form class="w-100 m-0 border" method="post">
                <textarea id="code" name="code" class="w-100 border-0" title="highlight code" style="height:270px"><!--code--></textarea>
                <div class="progress" style="height:5px;">
                    <div id="progress_bar" class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%"></div>
                </div>
                <div id="info" class="text-muted small"></div>
            </form>
        </div>
        <div class="col-12 p-3">
            <h3>执行结果</h3>
            <div class="p-1 border no-wrap" style="height:302px;overflow:auto;">
                <!--result-->
            </div>
        </div>
    </div>
</div>
<script type="application/javascript">
    $(function() {
        $.setCSS([
            '/vendor/codemirror/lib/codemirror.css',
            '/vendor/codemirror/addon/fold/foldgutter.css',
            '/vendor/codemirror/addon/display/fullscreen.css'
        ]);
        $.setJS([
            "/vendor/codemirror/lib/codemirror.js",
            "/vendor/codemirror/mode/php/php.js",
            "/vendor/codemirror/addon/edit/matchbrackets.js",
            "/vendor/codemirror/addon/selection/active-line.js",
            "/vendor/codemirror/mode/clike/clike.js",
            "/vendor/codemirror/addon/fold/foldcode.js",
            "/vendor/codemirror/addon/fold/foldgutter.js",
            "/vendor/codemirror/addon/fold/brace-fold.js",
            "/vendor/codemirror/addon/fold/xml-fold.js",
            "/vendor/codemirror/addon/fold/indent-fold.js",
            "/vendor/codemirror/addon/fold/comment-fold.js",
            "/vendor/codemirror/addon/display/fullscreen.js",
            "/vendor/codemirror/mode/javascript/javascript.js",
            "/vendor/codemirror/mode/xml/xml.js",
            "/vendor/codemirror/mode/css/css.js",
            "/vendor/codemirror/mode/htmlmixed/htmlmixed.js",
        ], true, function() {
            let editor = CodeMirror.fromTextArea($id("code"), {
                mode: "application/x-httpd-php",
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
    });
</script>