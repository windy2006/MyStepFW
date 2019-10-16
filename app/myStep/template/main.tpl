<!DOCTYPE html>
<html lang="zh-CN">
<head>
<title><!--web_title--></title>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<meta http-equiv="windows-Target" content="_top" />
<meta http-equiv="Content-Type" content="text/html; charset=<!--charset-->" />
<meta name="keywords" content="<!--page_keywords-->" />
<meta name="description" content="<!--page_description-->" />
<base href="<!--path_root-->" />
<link rel="Shortcut Icon" href="favicon.ico" />
<!--page_start-->
</head>
<body class="py-5">
<header class="navbar navbar-expand-sm navbar-dark bd-navbar fixed-top">
    <button class="navbar-toggler collapsed" type="button" data-toggle="collapse" data-target="#nav" aria-expanded="false">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="navbar-collapse collapse" id="nav">
        <a class="btn navbar-brand d-none d-sm-inline-block mr-3" href="#" onclick="return false;"><b>迈思框架</b></a>
        <ul class="navbar-nav mr-auto mt-2 mt-lg-0">
            <li class="nav-item">
                <a class="nav-link" href="./"><span class="glyphicon glyphicon-home"></span> <!--lng_page_main--></a>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown" aria-expanded="false"><span class="glyphicon glyphicon-info-sign"></span> 信息</a>
                <div class="dropdown-menu dropdown-menu-left">
                    <a class="dropdown-item" href="manager/">基本信息</a>
                    <a class="dropdown-item" href="manager/db">数据库信息</a>
                    <a class="dropdown-item" href="manager/php">PHP信息</a>
                    <a class="dropdown-item" href="manager/phpinfo">phpinfo()</a>
                    <a class="dropdown-item" href="manager/error">错误日志 </a>
                </div>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown" aria-expanded="false"><span class="glyphicon glyphicon-wrench"></span> 设置</a>
                <div class="dropdown-menu dropdown-menu-left">
                    <a class="dropdown-item" href="manager/setting/">参数设置</a>
                    <a class="dropdown-item" href="manager/setting/class">类模块设置</a>
                </div>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown" aria-expanded="false"><span class="glyphicon glyphicon-cog"></span> 功能</a>
                <div class="dropdown-menu dropdown-menu-left">
                    <a class="dropdown-item" href="manager/function/app">应用管理</a>
                    <a class="dropdown-item" href="manager/function/cache">缓存管理</a>
                    <a class="dropdown-item" href="manager/function/plugin">插件管理</a>
                </div>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown" aria-expanded="false"><span class="glyphicon glyphicon-book"></span> 文档</a>
                <div class="dropdown-menu dropdown-menu-left">
                    <a class="dropdown-item" href="manager/guide">框架指南</a>
                    <a class="dropdown-item" href="manager/sample">功能示例</a>
                    <a class="dropdown-item" href="sample/" target="_blank">应用示例</a>
                    <a class="dropdown-item" href="Document/" target="_blank">功能类说明</a>
                </div>
            </li>
        </ul>
    </div>
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" href="manager/login?out"><span class="glyphicon glyphicon-log-out"></span> <!--lng_page_logout--></a>
        </li>
    </ul>
</header>

<div class="container-fluid">
    <div class="row">
        <div id="list">
            <div class="position-sticky" style="top:60px;">
                <a class="dropdown-item" href="manager/"><span class="glyphicon glyphicon-info-sign"></span> 基本信息</a>
                <a class="dropdown-item" href="manager/db"><span class="glyphicon glyphicon-info-sign"></span> 数据库信息</a>
                <a class="dropdown-item" href="manager/php"><span class="glyphicon glyphicon-info-sign"></span> PHP信息</a>
                <a class="dropdown-item" href="manager/phpinfo"><span class="glyphicon glyphicon-info-sign"></span> phpinfo()</a>
                <a class="dropdown-item" href="manager/error"><span class="glyphicon glyphicon-info-sign"></span> 错误日志 </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="manager/setting/"><span class="glyphicon glyphicon-wrench"></span> 参数设置</a>
                <a class="dropdown-item" href="manager/setting/class"><span class="glyphicon glyphicon-wrench"></span> 类模块设置</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="manager/function/app"><span class="glyphicon glyphicon-cog"></span> 应用管理</a>
                <a class="dropdown-item" href="manager/function/cache"><span class="glyphicon glyphicon-cog"></span> 缓存管理</a>
                <a class="dropdown-item" href="manager/function/plugin"><span class="glyphicon glyphicon-cog"></span> 插件管理</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="manager/guide"><span class="glyphicon glyphicon-book"></span> 框架指南</a>
                <a class="dropdown-item" href="manager/sample"><span class="glyphicon glyphicon-book"></span> 功能示例</a>
                <a class="dropdown-item" href="sample/" target="_blank"><span class="glyphicon glyphicon-book"></span> 应用示例</a>
                <a class="dropdown-item" href="Document/" target="_blank"><span class="glyphicon glyphicon-book"></span> 功能类说明</a>
            </div>
        </div>
        <div id="main" class="border-left">
            <!--main-->
        </div>
    </div>
</div>

<footer class="border-top text-center fixed-bottom bg-light pt-2 font-sm" style="max-height:60px;overflow:hidden;">
    <p>Powered by 『 MyStep Framework 』&nbsp;Copyright&copy; 2010-2019 <a href="mailto:windy2006@gmail.com">windy2006@gmail.com</a></p>
</footer>
<!--page_end-->
<script language="JavaScript">
function resizeMain() {
    $('#main').css('min-height', 0);
    $('#main').css('min-height', $(document).height()-80);
    if(navigator.userAgent.indexOf(".NET") != -1) {
        var top = ($('#list').css('display')=='none') ? '0px' : '65px';
        $('#main').parent().css('padding-top', top);
    }
}
$(function(){
    $('[data-toggle="tooltip"]').tooltip();
    $("a[href='<!--path-->']").addClass('active');
    $.get('index.php?myStep/api/error/'+Math.random(), function(data, status){
        if(typeof data == 'object' && data.count > 0) {
            var badge = $('<span>').addClass('badge badge-warning').css('vertical-align', 'super').text(data.count);
            $("a[href='manager/error']").append(badge);
        }
    },'json');
    resizeMain();
    $('input[type=file]').change(function(){
        $(this).next().text(this.value.replace(/^.+[\/\\]([^\/\\]+)$/, '$1'));
    });
    $('body').click(function(e){
        if(!$(e.target).hasClass('dropdown-toggle')) $('#nav').collapse('hide');
    });
});
$(window).resize(resizeMain);
</script>
</body>
</html>