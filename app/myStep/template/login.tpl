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
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body>
<div class="p-5 text-center">
    <img src="static/images/logo.png" keep-url />
</div>
<div class="container">
    <div class="row">&nbsp;</div>
    <div class="row">
        <div class="col-md-6 offset-md-3 col-sm-8 offset-sm-2">
            <div class="card mb-3">
                <div class="card-header p-2 bg-info">
                    <h5 class="text-white card-title m-0"><span class="glyphicon glyphicon-blackboard"></span> <!--lng_page_login--></h5>
                </div>
                <div class="card-body">
                    <form action="<!--path_admin-->/login" method="post" onsubmit="return checkForm(this)">
                        <div class="input-group mb-2">
                            <div class="input-group-prepend">
                                <div class="input-group-text"><span class="glyphicon glyphicon-user"></span> &nbsp; <!--lng_login_username--></div>
                            </div>
                            <input name="username" value="" type="text" class="form-control" placeholder="<!--lng_login_username-->" need="" />
                        </div>
                        <div class="input-group mb-2">
                            <div class="input-group-prepend">
                                <div class="input-group-text"><span class="glyphicon glyphicon-asterisk"></span> &nbsp; <!--lng_login_password--></div>
                            </div>
                            <input name="password" value="" type="password" class="form-control" placeholder="<!--lng_login_password-->" need="" />
                        </div>
                        <div class="input-group mb-2">
                            <div class="input-group-prepend">
                                <div class="input-group-text"><span class="glyphicon glyphicon-edit"></span> &nbsp; <!--lng_login_captcha--></div>
                            </div>
                            <input name="captcha" type="text" class="form-control" placeholder="<!--lng_login_captcha_msg-->" need="" />
                            <div class="input-group-append">
                                <img id="captcha" src="captcha/" height="33" />
                            </div>
                            <div class="input-group-append">
                                <span class="input-group-text"><a href="#" title="<!--lng_login_refresh-->" onclick="document.getElementById('captcha').src+=(new Date().getTime());return false;"><span class="glyphicon glyphicon-refresh"></span></a></span>
                            </div>
                        </div>
                        <div class="text-right">
                            <button class="btn btn-info" type="submit"><!--lng_link_confirm--></button>&emsp;
                            <button class="btn btn-info" type="reset"><!--lng_link_reset--></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<footer class="border-top text-center mt-3 text-secondary fixed-bottom bg-light pt-2 font-sm">
    <p>Powered by 『 MyStep Framework 』&nbsp;Copyright&copy; 2010-2020 <a href="mailto:windy2006@gmail.com">windy2006@gmail.com</a></p>
</footer>
<!--page_end-->
<script type="application/javascript" src="static/js/checkForm.js"></script>
</body>
</html>