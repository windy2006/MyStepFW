<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <title><!--lng_page_info--></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta http-equiv="windows-Target" content="_top" />
    <base href="<!--path_root-->" />
    <link rel="Shortcut Icon" href="favicon.ico" />
    <link rel="stylesheet" media="screen" type="text/css" href="static/css/bootstrap.css" />
    <link rel="stylesheet" media="screen" type="text/css" href="static/css/glyphicons.css" />
    <script language="JavaScript" src="static/js/jquery.js"></script>
</head>
<body>
<div class="container">
    <div class="row my-5 mx-auto w-75">
        <div class="card w-100 bg-light">
            <div class="card-header text-dark">
                <b><span class="glyphicon glyphicon-info-sign"></span> <!--lng_page_info--></b>
            </div>
            <div class="card-body">
                <p class="card-text"><!--msg--></p>
            </div>
            <div class="card-footer text-muted">
                <!--lng_page_info_refresh-->
            </div>
        </div>
    </div>
</div>
</body>
<script language="JavaScript">
$(function() {
    let sec = 5;
    let url = '<!--url-->';
    $('#countdown').html(sec--);
    //url = url.replace(/^\//, '<!--path_root-->');
    $('#url').attr('href', url);
    setInterval(function(){
        if(sec<=0) {
            location.href = url;
        } else {
            $('#countdown').html(sec--);
        }
    }, 1000);
});
</script>
</html>