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
        <ul id="top_nav" class="navbar-nav mr-auto mt-2 mt-lg-0">
            <li class="nav-item">
                <a class="nav-link" href=""><span class="glyphicon glyphicon-home"></span> <!--lng_page_main--></a>
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
            <div class="position-fixed" style="top:60px;width:200px;"></div>
        </div>
        <div id="main" class="border-left">
            <!--main-->
        </div>
    </div>
</div>

<footer class="border-top text-center fixed-bottom bg-light pt-2 font-sm" style="max-height:60px;overflow:hidden;">
    <p>Powered by 『 MyStep Framework 』&nbsp;Copyright&copy; 2010-2020 <a href="mailto:windy2006@gmail.com">windy2006@gmail.com</a></p>
</footer>
<!--page_end-->
<script type="application/javascript">
if(self!=top) top.location.href = location.href;
$.get('app/myStep/menu.json?'+(new Date()).getTime(), function(data) {
    let obj_top = $("#top_nav");
    let obj_side = $("#list > div:first");
    let obj = {}, obj_sub = {};
    for(let i=0, m=data.length; i<m; i++) {
        if(typeof data[i].link == 'undefined') data[i].link = "#";
        if(typeof data[i].icon == 'undefined') data[i].icon = "glyphicon glyphicon-asterisk";
        obj = $('<li class="nav-item dropdown"></li>');
        obj.append($('<a class="nav-link" href="'+data[i].link+'"><span class="'+data[i].icon+'"></span> '+data[i].name+'</a>'));
        if(data[i].items.length==0) {
            obj_top.append(obj);
            continue;
        } else {
            obj.addClass('dropdown');
            obj.find('a:first').addClass("dropdown-toggle").attr("data-toggle", "dropdown");
            obj_sub = $('<div class="dropdown-menu dropdown-menu-left"></div>');
            for(let x=0, n=data[i].items.length;x<n;x++) {
                $('<a>').addClass("dropdown-item").attr("href", data[i].items[x].link).text(data[i].items[x].name).appendTo(obj_sub);
            }
            obj_sub.appendTo(obj);
        }
        obj_top.append(obj);
        for(let x=0, n=data[i].items.length;x<n;x++) {
            obj = $('<a>').addClass("dropdown-item").attr("href", data[i].items[x].link).text(" " + data[i].items[x].name);
            if(typeof data[i].items[x].addon != 'undefined') obj.attr(data[i].items[x].addon);
            if(typeof data[i].items[x].icon == 'undefined') data[i].items[x].icon = data[i].icon;
            obj.prepend($('<span>').addClass(data[i].items[x].icon)).appendTo(obj_side);
        }
        obj_side.append($('<div class="dropdown-divider"></div>'));
    }

    $('[data-toggle="tooltip"]').tooltip().on('show.bs.tooltip', function(){
        $('*').tooltip('hide');
    });
    $.get('<!--url_prefix-->api/myStep/error/'+Math.random(), function(data, status){
        if(typeof data == 'object' && data.count > 0) {
            let badge = $('<span>').addClass('badge badge-warning ml-1').css('vertical-align', 'super').text(data.count);
            $("a[href$='/error']").append(badge);
        }
    },'json');
    resizeMain();
    $('body').click(function(e){
        if(!$(e.target).hasClass('dropdown-toggle')) $('#nav').collapse('hide');
    });
    $("a[href$='<!--path-->']").addClass('active');
    if('<!--db-->'=='n') {
        $('a[href$="/db"]').hide();
    }
    setURL('<!--path_root-->');
}, "json");
$('#list').mousewheel(function(e){
    let obj = $('#list > div');
    let top = parseInt(obj.css('top'));
    let step = 5;
    if(e.deltaY<0) {
        if(obj.height()+top>$(window).height()-$('header').height()) {
            obj.css('top', top-step);
        }
    } else {
        if(top<60) {
            if(top-60>-step) step = -top;
            obj.css('top', top+step);
        }
    }
    e.stopPropagation();
    return false;
});
function resizeMain() {
    $('#main').css('min-height', 0);
    $('#main').css('min-height', $(document).height()-80);
    if(navigator.userAgent.indexOf(".NET") != -1) {
        let top = ($('#list').css('display')=='none') ? '0px' : '65px';
        $('#main').parent().css('padding-top', top);
    }
}
$(window).resize(resizeMain);
</script>
</body>
</html>