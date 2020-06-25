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
<link rel="stylesheet" media="screen" type="text/css" href="<!--path_root-->app/CMS/asset/theme/default.css" id="theme_css" />
</head>
<body class="bg-transparent">
<!--main-->
<script type="application/javascript">
let p = window.parent;
$('html').bind('keydown',function(e){
    if(e.altKey && e.keyCode===81) { // ALT + Q
        p.closeTab();
    } else if(e.altKey && e.keyCode===39) { // ALT + right
        p.changeTab(1);
    } else if(e.altKey && e.keyCode===37) { // ALT + left
        p.changeTab(-1);
    }
});
$('body').on('click', function(){
    let obj = $('#nav .navbar-nav > li.show', p.document);
    obj.removeClass('show');
    obj.find('a[expanded=true]').attr('expanded', false);
    obj.find('div.show').removeClass('show');
});
$('.card-header:first').addClass('no-wrap').css({
    'height' : 42,
    'overflow-y' : 'hidden'
});
if(p!==window) {
    $('.card-header:first').dblclick(function(){
        let idx = new Date().getTime();
        $('body').attr("idx",idx);
        $(window.parent.document).find("iframe").each(function(){
            let cur_idx=$(this.contentWindow.document).find("body").attr("idx");
            if(cur_idx==idx){
                let obj = $(this);
                if(obj.hasClass('maximum')) {
                    obj.removeClass('maximum active');
                    obj.height('auto');
                    $('#main', window.parent.document.body).addClass('pt-2 main_width').removeClass('w-100');
                    $('.container-fluid:first', window.parent.document.body).addClass('mt-5 pt-3');
                    $('#list_tab', window.parent.document.body).show();
                    $('#list', window.parent.document.body).show();
                    $('footer', window.parent.document.body).show();
                    $('header', window.parent.document.body).show();
                    window.parent.resizeMain();
                } else {
                    $('header', window.parent.document.body).hide();
                    $('footer', window.parent.document.body).hide();
                    $('#list', window.parent.document.body).hide();
                    $('#list_tab', window.parent.document.body).hide();
                    $('.container-fluid:first', window.parent.document.body).removeClass('mt-5 pt-3');
                    $('#main', window.parent.document.body).removeClass('pt-2').addClass('w-100');
                    $('#main > .container-fluid', window.parent.document.body).height('100%');
                    obj.height($(window.parent.document).height());
                    obj.addClass('maximum active');
                }
            }
        });
    }).attr('title', '双击标题栏以最大化窗口<br />按 ALT+Q 关闭当前标签页<br />按 ALT+←或→ 切换标签页').attr('data-html', 'true');
    $('form[action$=_ok]').submit(function(){
        if($('iframe.maximum', p.document).length>0) $('.card-header:first').trigger('dblclick');
        return true;
    });
}
global.root_fix = '<!--path_admin-->';
$(function(){
    $('[title]').not('[data-placement]').attr('data-placement', 'bottom');
    $('[title]').not('[notip]').attr('data-trigger', 'hover').tooltip().on('show.bs.tooltip', function(){
        $('*').tooltip('hide');
    });
    setURL();
    $('select[id=web_id],select[name=web_id]').each(function(){
        if(this.options.length<2 || (this.options.length===2 && this.options[0].value==='')) {
            this.selectedIndex = this.options.length - 1;
            if(this.id==='web_id') {
                $(this).hide();
            } else {
                $(this).parent().hide();
            }
        }
    });
    if($('iframe').length>0) {
        if($('#web_id > option').length<=2) {
            $('#web_id').hide();
            $('.sidebar-go-top').css('top', 4);
            $('.sidebar-go-down').css('top', 22);
            $('#sidebar').css('padding-top', 30);
        }
    }
});
</script>
<!--page_end-->
</body>
</html>