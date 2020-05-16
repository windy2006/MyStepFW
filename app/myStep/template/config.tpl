<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <title>MyStep Framework</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta http-equiv="windows-Target" content="_top" />
    <meta http-equiv="Content-Type" content="text/html; charset=<!--charset-->" />
    <meta name="keywords" content="<!--page_keywords-->" />
    <meta name="description" content="<!--page_description-->" />
    <base href="<!--path_root-->" />
    <link rel="Shortcut Icon" href="favicon.ico" />
    <!--page_start-->
    <link rel="stylesheet" media="screen" type="text/css" href="static/css/bootstrap.css" />
    <link rel="stylesheet" media="screen" type="text/css" href="static/css/glyphicons.css" />
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body>

<div class="p-5 text-center">
    <img src="static/images/logo.png" />
</div>

<div id="main" class="container">
    <div class="row">&nbsp;</div>
    <div class="row">
        <div class="col-md-10 offset-md-1 col-sm-12">
            <div class="card mb-3">
                <div class="card-header p-2 bg-info">
                    <h5 class="text-white card-title m-0"><span class="glyphicon glyphicon-cog"></span> 框架参数初始化</h5>
                </div>
                <div class="card-body">
                    <form method="post" onsubmit="return myChecker(this)">
                        <table class="table table-sm table-striped table-hover m-0 border">
                            <tbody>
                            <!--loop:start key="setting"-->
                            <!--setting_content-->
                            <!--loop:end-->
                            </tbody>
                            <tfoot class="position-fixed bg-white border-top">
                                <tr class="float-right">
                                    <td colspan="2" class="p-2 border-0">
                                        <button class="btn btn-primary btn-sm mr-3" type="submit"> 提 交 </button>
                                        <button class="btn btn-primary btn-sm" type="reset"> 复 位 </button>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="border-top text-center mt-3 text-secondary fixed-bottom bg-light pt-2 font-sm">
    <p>Powered by 『 MyStep Framework 』&nbsp;Copyright&copy; 2010-2020 <a href="mailto:windy2006@gmail.com">windy2006@gmail.com</a></p>
</footer>
<script type="application/javascript" src="static/js/jquery.js"></script>
<script type="application/javascript" src="static/js/bootstrap.bundle.js"></script>
<script type="application/javascript" src="static/js/checkForm.js"></script>
<!--page_end-->
</body>
<script type="application/javascript">
function myChecker(theForm) {
    if($('#gen_s_pwd').val()=='') {
        alert('请输入管理员密码！');
        highlightIt($('#gen_s_pwd').get(0));
    } else if ($('#gen_s_pwd').val() == $('#gen_s_pwd_r').val() && $('#db_password').val() == $('#db_password_r').val()){
        return checkForm(theForm);
    } else {
        alert('两次输入的密码不一致！');
        if($('#gen_s_pwd').val() != $('#gen_s_pwd_r').val()) {
            highlightIt($('#gen_s_pwd').get(0));
        }
        if($('#db_password').val() != $('#db_password_r').val()) {
            highlightIt($('#db_password').get(0));
        }
    }
    return false;
}
function setPosition() {
    $("#main").css('padding-bottom', 100);
    $("tfoot").css({'right':0, 'bottom':40, 'width':'100%'});
    $("tfoot").height($(window).width()>530?60:80);
}
$(window).resize(setPosition);
$(setPosition);
$('[data-toggle="tooltip"]').tooltip();
$(function(){
    let srv = '<!--server-->';
    srv = srv.toLowerCase();
    let obj = document.getElementsByName("setting[router][mode]")[0];
    if(srv.indexOf('iis')!=-1 || srv.indexOf('apache')!=-1) {
        obj.selectedIndex = 2;
    } else {
        alert("检测到您的服务系统为'"+svr+"'，路径模式设定为'query_string'模式，建议改为'rewrite'模式。\nNginx可以根据根目录下的对应文件设置，其他系统可以参考对应设置。");
    }
});
</script>
</html>