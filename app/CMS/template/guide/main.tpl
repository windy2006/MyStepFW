<!DOCTYPE html>
<html lang="zh-CN">
<head>
<title><!--web_title--></title>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<meta http-equiv="windows-Target" content="_top" />
<meta http-equiv="Content-Type" content="text/html; charset=<!--charset-->" />
<base href="<!--path_root-->" />
<link rel="Shortcut Icon" href="favicon.ico" />
<!--page_start-->
</head>
<body class="py-5">
<header class="navbar navbar-expand-sm navbar-dark bd-navbar fixed-top">
    <a class="btn navbar-brand d-inline-block mr-3" href="#" onclick="return false;"><b>迈思指南</b></a>
    <button class="navbar-toggler collapsed" type="button" data-toggle="collapse" data-target="#nav" aria-expanded="false">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
        <ul id="top_nav" class="navbar-nav mr-auto mt-2 mt-lg-0">
            <li class="nav-item"><a class="nav-link" href="<!--url_prefix_app-->"><span class="glyphicon glyphicon-home"></span> <!--lng_page_main--></a></li>
            <!--loop:start key="news_cat"-->
            <li class="nav-item">
                <a class="nav-link" href="#" idx="<!--news_cat_idx-->">
                    <span class="glyphicon glyphicon-info-sign"></span> <!--news_cat_name-->
                </a>
            </li>
            <!--loop:end-->
        </ul>
        <form action="">
            <input id="search_input" type="text" class="form-control search-query" placeholder="检索" />
        </form>
    </div>
</header>
<div class="container-fluid">
    <div class="row" style="min-height:570px;">
        <div id="list">
            <div class="position-fixed" style="top:60px;width:200px;">
                <ul id="side_nav" class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="/"><span class="glyphicon glyphicon-home"></span> 首页</a>
                    </li>
                    <!--loop:start key="news_cat"-->
                    <li class="nav-item" idx="<!--news_cat_idx-->">
                        <a class="nav-link" href="#cat_<!--news_cat_idx-->">
                            <span class="glyphicon glyphicon-info-sign"></span> <!--news_cat_name-->
                        </a>
                    </li>
                    <!--loop:end-->
                </ul>
            </div>
        </div>
        <!--main-->
    </div>
</div>
<footer class="border-top text-center fixed-bottom bg-light pt-2 font-sm" style="max-height:60px;overflow:hidden;">
    <!--info idx="copyright"-->
</footer>
<script type="application/javascript">
if(self!==top) top.location.href = location.href;
let news_cat = <!--news_cat-->;
$(function(){
    $(document).off('click.bs.dropdown.data-api');
    let objs = $('#top_nav>li>a[idx]');
    for(let i=0,m=objs.length;i<m;i++) {
        let obj = $(objs[i]);
        let idx = obj.attr('idx');
        if(typeof news_cat[idx].sub != 'undefined') {
            let list = $('<div class="dropdown-menu"></div>').css('margin-top',1);
            obj.addClass('dropdown-toggle').attr('data-toggle','dropdown').parent().addClass('dropdown');
            obj = obj.parent();
            let obj2 = $('#side_nav li[idx="'+idx+'"]');
            let list2 = $('<div class="collapse"></div>').attr('id', 'cat_'+idx);
            obj2.find('a').attr('data-toggle','collapse');
            obj2.append('<i class="nav-arrow" data-toggle="collapse" href="#cat_'+idx+'"></i>');
            for(let j=0,n=news_cat[idx].sub.length;j<n;j++) {
                if((news_cat[idx].sub[j].show & 1) === 0) continue;
                if(news_cat[idx].sub[j].link.length===0) news_cat[idx].sub[j].link = 'catalog/' + news_cat[idx].sub[j].idx;
                list.append('<a class="dropdown-item" href="'+news_cat[idx].sub[j].link+'">'+news_cat[idx].sub[j].name+'</a>');
                list2.append('<a class="dropdown-item" href="'+news_cat[idx].sub[j].link+'">'+news_cat[idx].sub[j].name+'</a>');
            }
            obj.append(list);
            obj2.append(list2);
            setURL('<!--url_prefix_app-->', obj);
            setURL('<!--url_prefix_app-->', obj2);
        }
    }
    $('body').click(function(e){
        if($(e.target).parents('header').length) return;
        $('header > .collapse').collapse('hide');
        $('#top_nav>li.dropdown').removeClass('show')
            .find('div.dropdown-menu').removeClass('show');
    });
    $('#top_nav>li.dropdown>div.dropdown-menu>.dropdown-item').click(function(e) {
        e.stopPropagation();
        return true;
    });
    $('#list').mousewheel(function(e){
        let obj = $('#list > div');
        let top = parseInt(obj.css('top'));
        let step = 8;
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
    $('#list .collapse').on('show.bs.collapse', function () {
        $('.nav-arrow[aria-expanded=true]').each(function(){
            $(this).attr('aria-expanded','false');
            $(this).next().collapse('hide');
        });
    })
    let the_link = location.pathname.replace(/&.*$/, '');
    let obj = $('#side_nav a[href$="'+the_link+'"]');
    if(obj.length>0 && the_link!=='/') {
        obj.addClass('active').css('color','white');
        obj.parent().collapse('show');
    }
    setURL();
    resizeMain();
});
function resizeMain() {
    $('#main').css('min-height', 0);
    $('#main').css('min-height', $(document).height()-80);
    if(navigator.userAgent.indexOf(".NET") != -1) {
        let top = ($('#list').css('display')=='none') ? '0px' : '65px';
        $('#main').parent().css('padding-top', top);
    }
    $('body').trigger('click');
    if($('header > button.navbar-toggler').is(":visible")) {
        $('#top_nav>li.dropdown').unbind('mouseenter mouseleave click').click(function(e){
            if($(this).hasClass('show')) {
                $(this).removeClass('show');
                $(this).find('div.dropdown-menu').removeClass('show');
            } else {
                $(this).parent().find('li').removeClass('show');
                $(this).parent().find('div.dropdown-menu').removeClass('show');
                $(this).addClass('show');
                $(this).find('div.dropdown-menu').addClass('show');
            }
            return false;
        });
    } else {
        $('#top_nav>li.dropdown').unbind('mouseenter mouseleave click').hover(function(){
            $(this).addClass('show');
            $(this).find('div.dropdown-menu').addClass('show');
        }, function(){
            $(this).removeClass('show');
            $(this).find('div.dropdown-menu').removeClass('show');
        });
    }
}
$(window).resize(resizeMain);
</script>
<!--page_end-->
</body>
</html>