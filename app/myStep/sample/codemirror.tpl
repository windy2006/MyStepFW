<div class="card w-100 mb-5 mb-sm-2">
	<div class="card-body p-0">
		<textarea id="code"></textarea>
	</div>
</div>
<script language="JavaScript">
    $(function() {
        $.setCSS([
            'vendor/codemirror/lib/codemirror.css',
			'vendor/codemirror/addon/fold/foldgutter.css',
			'vendor/codemirror/addon/display/fullscreen.css'
		]);
        $.setJS([
            "vendor/codemirror/lib/codemirror.js",
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
        ],function() {
            $.get('Document/', function(data) {
                $("#code").val(data);
                var editor = CodeMirror.fromTextArea($id("code"), {
                    mode: "text/html",
                    lineNumbers: true,
                    lineWrapping: true,
                    foldGutter: true,
                    gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"]
                });
                editor.setOption("extraKeys", {
                    // Tab键换成4个空格
                    Tab: function(cm) {
                        var spaces = Array(cm.getOption("indentUnit") + 1).join(" ");
                        cm.replaceSelection(spaces);
                    },
                    // Ctrl+F11键切换全屏
                    "Ctrl-F11": function(cm) {
                        cm.setOption("fullScreen", !cm.getOption("fullScreen"));
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
            });
        });
    });
</script>