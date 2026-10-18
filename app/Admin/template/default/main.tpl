<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="<!--charset|html-->">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><!--admin_title|html--> · MyStep Admin</title>
<!--page_start-->
</head>
<body class="adm-body">
<div class="adm-shell">
    <aside class="adm-sidebar" id="adm-navigation" aria-label="后台导航">
        <a class="adm-brand" href="<!--admin_home|html-->"><b>M</b><span>MyStep<small>ADMIN CONSOLE</small></span></a>
        <nav><!--loop:start key="navigation"--><!--navigation_content--><!--loop:end--></nav>
        <a class="adm-legacy" href="<!--admin_legacy|html-->">打开原管理后台 ↗</a>
    </aside>
    <div class="adm-workspace">
        <header class="adm-topbar">
            <div class="adm-topbar-start">
                <!--if:start key="authenticated"--><button class="adm-nav-toggle" type="button" aria-controls="adm-navigation" aria-expanded="false" aria-label="展开导航菜单"><span aria-hidden="true">☰</span><span>菜单</span></button><!--if:end-->
                <a class="adm-mobile-brand" href="<!--admin_home|html-->">MyStep</a>
                <span class="adm-breadcrumb">框架管理 / <!--admin_title|html--></span>
            </div>
            <div class="adm-account"><span><!--admin_user|html--></span>
                <!--if:start key="authenticated"-->
                <form method="post" action="<!--logout_url|html-->"><input type="hidden" name="_action" value="logout"><button type="submit" class="adm-logout">退出</button></form>
                <!--if:end-->
            </div>
        </header>
        <main class="adm-main" id="adm-main">
            <div class="adm-heading"><div><p>MY STEP FRAMEWORK</p><h1><!--admin_title|html--></h1></div><span class="adm-status">PHP <!--php_version|html--></span></div>
            <p class="adm-notice" role="status"><!--admin_notice|html--></p>
            <!--main-->
        </main>
        <footer class="adm-footer">MyStep · 管理后台</footer>
    </div>
</div>
<!--page_end-->
</body>
</html>
