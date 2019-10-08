<!DOCTYPE html>
<html lang="zh-CN">
<head>
<title><!--web_title--></title>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<meta http-equiv="windows-Target" content="_top" />
<meta http-equiv="Content-Type" content="text/html; charset=<!--charset-->" />
<meta name="keywords" content="<!--page_keywords-->" />
<meta name="description" content="<!--page_description-->" />
<base href="<!--web_url--><!--path_root-->" />
<link rel="Shortcut Icon" href="favicon.ico" />
<!--page_start-->
</head>
<body>
<div class="container">
<!--main-->
</div>
<script src="vendor/syntaxhighlighter/shCore.js" type="text/javascript"></script>
<script src="vendor/syntaxhighlighter/shBrushPhp.js" type="text/javascript"></script>
<script type="text/javascript">SyntaxHighlighter.all();</script>
<script language="JavaScript">
    $(function() {
        $.setCSS([
            'vendor/syntaxhighlighter/shCore.css',
            'vendor/syntaxhighlighter/shThemeDefault.css'
        ]);
        return;
        //doesn't work ?
        $.setJS([
            "vendor/syntaxhighlighter/shCore.js",
            "vendor/syntaxhighlighter/shBrushPhp.js",
        ],function() {
            SyntaxHighlighter.all();
        });
    });
</script>
<!--page_end-->
</body>
</html>