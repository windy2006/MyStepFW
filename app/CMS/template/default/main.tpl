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
<body>
<div class="top-banner d-none d-md-block p-4">
    <div class="w-50 mx-auto text-center my-title">
        <h1>迈思内容管理系统</h1>
    </div>
</div>
<header class="navbar navbar-expand-sm navbar-dark bg-dark sticky-top">
    <button class="navbar-toggler collapsed m-2" type="button" data-toggle="collapse" data-target="#nav" aria-expanded="false">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
        <a class="btn navbar-brand d-none d-sm-inline-block mr-3" href="#"><b>迈思 CMS</b></a>
        <ul class="navbar-nav mr-auto">
            <li class="nav-item"><a class="nav-link" href="<!--url_prefix_app-->"><!--lng_page_main--></a></li>
            <!--loop:start key="news_cat"-->
            <li class="nav-item"><a class="nav-link" href="<!--news_cat_link-->" idx="<!--news_cat_idx-->"><!--news_cat_name--></a></li>
            <!--loop:end-->
        </ul>
    </div>
    <ul id="user_info" class="nav">
        <li class="nav-item"><a id="btn_search" class="nav-link text-white" href="javascript:"><span class="glyphicon glyphicon-search"></span> 检索</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="<!--url_prefix_app-->user/login"><span class="glyphicon glyphicon-log-in"></span> 登录</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="<!--url_prefix_app-->user/register"><span class="glyphicon glyphicon-registration-mark"></span> 注册</a></li>
    </ul>
</header>
<!--main-->
<footer class="footer fixed-bottom">
    <!--info idx="copyright"-->
</footer>
<div id="search">
    <form class="search" method="get" action="<!--url_prefix_app-->search">
        <div class="input-group">
            <input type="text" name="k" class="form-control form-control-lg" style="border-right-width: 0;" placeholder="检索" required>
            <div class="input-group-append">
                <span class="input-group-text"><button type="submit"><span class="fa fa-search"></span></button></span>
            </div>
        </div>
    </form>
</div>
<script type="application/javascript">
let news_cat = <!--news_cat-->;
$('img').on('error', function(){
    this.src = '/static/images/noimage.gif';
});
function showSearch() {
    let obj = $('#search');
    if(obj.is(":visible")) {
        $('body').css('overflow-y', 'auto');
        obj.slideUp(500);
    } else {
        $('body').css('overflow-y', 'hidden');
        obj.css('top', $(window).scrollTop());
        obj.slideDown(500);
        $('body').keyup(function(e){
            if(e.keyCode===27) {
                showSearch();
                $('body').unbind('keyup');
            }
        });
    }
}
$('#btn_search').click(showSearch);
$('#search').click(showSearch);
$('#search > form').click(function(e){
    e.stopPropagation();
});
$(function(){
    $(document).off('click.bs.dropdown.data-api');
    let objs = $('.navbar-nav').find('[idx]');
    for(let i=0,m=objs.length;i<m;i++) {
        let obj = $(objs[i]);
        let idx = obj.attr('idx');
        if(typeof news_cat[idx].sub != 'undefined') {
            let list = $('<div class="dropdown-menu"></div>').css('margin-top',1);
            obj.addClass('dropdown-toggle').attr('data-toggle','dropdown').parent().addClass('dropdown');
            obj = obj.parent();
            for(let j=0,n=news_cat[idx].sub.length;j<n;j++) {
                if((news_cat[idx].sub[j].show & 1) === 0) continue;
                if(news_cat[idx].sub[j].link.length===0) news_cat[idx].sub[j].link = 'catalog/' + news_cat[idx].sub[j].idx;
                list.append('<a class="dropdown-item" href="'+news_cat[idx].sub[j].link+'">'+news_cat[idx].sub[j].name+'</a>');
            }
            obj.append(list);
            setURL('<!--url_prefix_app-->', obj);
        }
        obj.hover(function(){
            $(this).addClass('show');
            $(this).find('div.dropdown-menu').addClass('show');
        }, function(){
            $(this).removeClass('show');
            $(this).find('div.dropdown-menu').removeClass('show');
        });
    }
    $.get("<!--url_prefix-->api/CMS/user", function(user_info){
        if(user_info.name!=='') {
            let objs = $('#user_info').find('li');
            $(objs[1]).attr('title', user_info.group).html('<a class="nav-link text-white" href="<!--url_prefix_app-->user/profile"><span class="glyphicon glyphicon-user"></span> '+user_info.name+'</a>');
            $(objs[2]).html('<a class="nav-link text-white" href="<!--url_prefix_app-->user/logout"><span class="glyphicon glyphicon-log-out"></span> 退出</a>');
        };
    },'json');
    setURL();
});
</script>
<!--page_end-->
</body>
</html>