<header class="navbar navbar-expand-sm navbar-dark navbar-c fixed-top">
    <button class="navbar-toggler collapsed" type="button" data-toggle="collapse" data-target="#nav" aria-expanded="false">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
        <a id="brand" class="btn navbar-brand d-none d-sm-inline-block mr-3" href="#" data-toggle="dropdown" data-offset="10,100"><b>迈思 CMS</b></a>
        <div class="dropdown-menu ml-3">
            <a class="dropdown-item" theme="default" href="#">默认主题</a>
            <a class="dropdown-item" theme="purple" href="#">紫色主题</a>
            <a class="dropdown-item" theme="brown" href="#">棕色主题</a>
        </div>
        <ul id="main_nav" class="navbar-nav mr-auto mt-2 mt-lg-0"></ul>
    </div>
    <ul class="nav">
        <li class="nav-item d-inline d-sm-none d-md-inline">
            <div class="text-white-50"><span class="glyphicon glyphicon-user"></span> <!--username-->（<!--groupname-->）</div>
        </li>
        <li class="nav-item">
            <a class="p-0 nav-link text-white" href="login?out" target="_top"><span class="glyphicon glyphicon-log-out"></span> 退出</a>
        </li>
    </ul>
</header>
<div class="container-fluid mt-5 pt-3">
    <div class="row">
        <div id="list">
            <div class="position-absolute w-100 bg-white" style="z-index:20;top:0px;">
                <select name="web_id" id="web_id" class="custom-select">
                    <option value="">显示全部</option>
                    <!--loop:start key="website"-->
                    <option value="<!--website_web_id-->"><!--website_name--></option>
                    <!--loop:end-->
                </select>
                <div class="py-2 border-right pl-2">
                    <button type="button" name="expand" class="font-sm btn btn-light btn-sm border"><span class="glyphicon glyphicon-plus-sign"></span> 展开所有</button>
                    <button type="button" name="collapse" class="font-sm btn btn-light btn-sm border"><span class="glyphicon glyphicon-minus-sign"></span> 合并所有</button>
                </div>
                <span class="sidebar-go sidebar-go-top glyphicon glyphicon-circle-arrow-up" title="滚动到顶部" data-placement="right"></span>
                <span class="sidebar-go sidebar-go-down glyphicon glyphicon-circle-arrow-down" title="滚动到底部" data-placement="right"></span>
            </div>
            <nav id="sidebar"></nav>
        </div>
        <div id="bar" class="px-2 border bg-light"><span class="fa fa-angle-double-right"></span></div>
        <div id="main" class="p-0 pt-2">
            <div class="container-fluid p-0 position-relative" style="overflow: hidden;">
                <ul id="list_tab" class="nav nav-tabs nav-hack pl-2 noselect border-0">
                    <span class="arrow left glyphicon glyphicon-circle-arrow-left position-absolute"></span>
                    <span class="arrow right glyphicon glyphicon-circle-arrow-right position-absolute"></span>
                    <li class="nav-item" idx="tab_main">
                        <a class="nav-link active" data-toggle="tab" href="#tab_main">首页</a>
                    </li>
                </ul>
                <div id="list_frame" class="tab-content border-top">
                    <div class="tab-pane fade show active" id="tab_main">
                        <iframe src="about:blank" class="frame" frameborder="0" allowtransparency="true"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<footer class="border-top text-center fixed-bottom bg-light pt-2" style="max-height:60px;overflow:hidden;">
    <p>Powered by 『 MyStep Framework 』&nbsp;Copyright&copy; 2010-2022 <a href="mailto:windy2006@gmail.com">windy2006@gmail.com</a></p>
</footer>
<script type="application/javascript">
if(self!=top) top.location.href = location.href;
let list_func = ',<!--list_func-->,';
let list_web = ',<!--list_web-->,';
function resizeMain() {
    let frame = $('iframe.active');
    if(frame.length) {
        frame.height($(window).height());
        return;
    }
    $('#main').css('height', 'auto')
              .css('min-height', $(window).height()-80);
    $('.frame').height($('#main').height()-$('footer').height()-20);
    if(navigator.userAgent.indexOf(".NET") !== -1) {
        let top = ($('#list').css('display')==='none') ? '0px' : '65px';
        $('#main').parent().css('padding-top', top);
    }
    if($('#bar').is(":hidden")) $('#list').removeAttr('style');
    if($('#list').css('position')==='relative') {
        $('#bar').css({left:0,opacity:0.5});
        $('#bar').find('span').css({
            'transform':'rotate(0deg)',
            'webkitTransform':'rotate(0deg)',
            'mozTransform':'rotate(0deg)',
            'msTransform':'rotate(0deg)'
        });
    }
    setTab();
}
function setTab() {
    $('#list_tab .arrow').hide();
    $('#list_tab').css('width', '100%');
    let len = $('#list_tab').width();
    let tabs = $('#list_tab li');
    let cur = null;
    let flag = false;
    for(let i=0,m=tabs.length;i<m;i++) {
        cur = $(tabs.get(i));
        if(cur.position().top>0) {
            len += cur.width();
            flag = true;
        }
    }
    if(flag) {
        $('#list_tab').width(len);
        $('#list_tab .right').css('right', len-$('#list_frame').width()+10).show();
    }
    $('#list_tab').css('left', 0);
    setPos($('#list_tab .left'),0);
    setPos($('#list_tab .right'),0);
}
function setPos(obj, unit){
    let l1 = $('#list_frame').width();
    let l2 = $('#list_tab').width();
    let left = $('#list_tab').position().left;
    let step = 0;
    if(unit==null) unit = 5;
    if($(obj).hasClass('right')) {
        step = l2-l1+left;
        if(step>unit) {
            step = unit;
            $('#list_tab .left').show();
        } else {
            step = 0;
            $('#list_tab .right').hide();
            clearInterval(global.timer);
        }
        step = -step;
    } else {
        if(-left>unit) {
            step = unit;
            $('#list_tab .right').show();
        } else {
            step = -left;
            $('#list_tab .left').hide();
            clearInterval(global.timer);
        }
    }
    $('#list_tab').css('left', left+step);
    $('#list_tab .right').css('right', $('#list_tab').width()-l1+$('#list_tab').position().left+10);
    $('#list_tab .left').css('left',-$('#list_tab').position().left);
}
function addFrame(name, url) {
    let idx = 'tab_'+md5(url);
    if(url===$('base').attr('href')) idx = 'tab_main';
    let obj = $('#'+idx);
    let tab = null;
    if(obj.length===0) {
        let frame = $('' +
            '<div class="tab-pane fade" id="'+idx+'">\n' +
            '   <iframe src="" name="'+idx+'" class="frame" frameborder="0" allowtransparency="true"></iframe>\n' +
            '</div>');
        tab = $('' +
            '<li class="nav-item" idx="'+idx+'">\n' +
            '   <a class="nav-link" data-toggle="tab" href="#'+idx+'"></a>\n' +
            '</li>');
        tab.find('a').html(name+'<span class="glyphicon glyphicon-remove"></span><span class="glyphicon glyphicon-refresh"></span>');
        tab.appendTo('#list_tab');
        frame.find('iframe').css('opacity', '1').attr('src', url);
        frame.appendTo('#list_frame');
        tab.find('a span.glyphicon-remove').click(function(e) {
            e.preventDefault();
            e.stopPropagation();
            removeFrame(idx);
        });
        tab.find('a span.glyphicon-refresh').click(function(e) {
            if($(this).parent().parent().find('a').hasClass('active')) {
                frame.find('iframe').get(0).contentWindow.location.reload(true);
            }
        });
        if(tab.position().top>0) setTab();
    } else {
        tab = $('#list_tab a[href="#'+idx+'"]').parent();
    }
    resizeMain();
    $('#list_tab a[href="#'+idx+'"]').tab('show');
    if(tab.position().left+$('#list_tab').position().left>$('#list_frame').width()-tab.width()) {
        $('#list_tab').css('left', $('#list_frame').width()-tab.width()-tab.position().left-40);
        setPos($('#list_tab .left'),0);
        setPos($('#list_tab .right'),0);
    }
}
function removeFrame(idx) {
    if($('#list_tab a[href="#'+idx+'"]').hasClass('active')) {
        let obj = $('#list_tab a[href="#'+idx+'"]').parent();
        let idx_next = (obj.next().length===0?obj.prev():obj.next()).attr('idx');
        $('#list_tab a[href="#'+idx_next+'"]').tab('show');
        $('#'+idx_next).addClass('active show');
    }
    $('#list_tab a[href="#'+idx+'"]').tab('dispose');
    $('#list_tab li[idx="'+idx+'"]').remove();
    $('#'+idx).remove();
    resizeMain();
}
function closeTab() {
    let tab = $('#list_tab a.active').parent();
    let idx = tab.attr('idx');
    if(idx!=='tab_main') {
        removeFrame(idx);
    }
}
function changeTab(para) {
    switch(typeof para) {
        case 'string':
            if(para.indexOf('/')===0) para = md5(para);
            $('li[idx=tab_'+para.replace('tab_','')+']').find('a').click();
            break;
        case 'number':
            let tab = $('#list_tab a.active').parent();
            if(para>0) {
                tab = tab.next();
            } else if(para<0) {
                tab = tab.prev();
            }
            if(tab.length) tab.find('a').click();
            break;
        case 'object':
            if(typeof($(para).attr('idx'))!=='undefined') {
                $(para).find('a').click();
            } else if(typeof($(para).attr('href'))!=='undefined' && $(para).attr('href').indexOf('#tab_')===0) {
                $(para).click();
            }
            break;
        default:
            return;
    }
}
function setLink() {
    $('a').unbind('click');
    $('#main_nav a').on('click', function(e) {
        let flag = false;
        switch (true) {
            case $(this).attr('data-toggle') !== undefined:
                if(typeof($(this).attr('path'))!='undefined' && $(this).attr('path').length > 3) {
                    addFrame($(this).text(), $(this).attr('path'));
                }
            case this.href.indexOf('javascript:')===0:
            case $(this).attr('target') !== undefined:
                flag = true;
                break;
            case this.href.indexOf('#')!==-1:
                location.href = location.pathname + this.href.replace(/^.+(#.*)$/g, '$1');
                break;
            default:
                e.preventDefault();
                addFrame($(this).text().trim(), $(this).attr('href'));
        }
        if(!$('header button:first').hasClass('collapsed') && this.href.indexOf('#')==-1) {
            $('header .collapse').collapse('hide');
            setPos($('#list_tab .left'),0);
            setPos($('#list_tab .right'),0);
        } else if($(this).hasClass('dropdown-item')) {
            $(this).parentsUntil('.dropdown').prev().dropdown('toggle');
        }
        return flag;
    });
    $('#sidebar a.hover-text').click(function(){
        let idx = getContentFrame($(this).attr('href'));
        if(typeof $(this).attr('target')==='undefined' && idx!=='') {
            $(this).attr('target', idx);
        }
        return true;
    });
    $('a[theme]').click(function(e){
        e.preventDefault();
        e.stopPropagation();
        setTheme($(this).attr('theme'));
        $(this).parentsUntil('.dropdown').prev().dropdown('toggle');
    });
    $('#list').find('a').click(function(){
        if($('#list').css('position')==='absolute') {
            $('#bar').trigger('click');
        }
    });
}
function setTheme(idx) {
    if(idx == null || idx === '') idx = 'default';
    $('#theme_css').attr('href', '<!--path_app-->asset/theme/'+idx+'.css');
    $.cookie('ms_theme', idx, {expires: 365});
    $('a[theme]').removeClass('active');
    $('a[theme="'+idx+'"]').addClass('active');
}
function getContentFrame(url) {
    let obj = $('header').find("a[href*='article/content']");
    let idx = '';
    if(obj.length>0) {
        obj.trigger('click');
        idx = 'tab_'+md5(obj.attr('href'));
    }
    return idx;
}
function getList(data) {
    let obj = $('<div class="nav sub-menu"></div>');
    let sub = null;
    let id = '';
    let list = null;
    for(let i=0,m=data.length;i<m;i++) {
        if(typeof data[i].link ==='undefined' || data[i].link==='') {
            data[i].link = 'article/content/?cat_id='+data[i].cat_id;
        }
        data[i].link = '<!--path_admin-->article/content/?cat_id='+data[i].cat_id;
        if(list_web!==',all,' && list_web.indexOf(','+data[i].web_id+',')===-1) continue;
        data[i].icon = 'fa fa-caret-right';
        sub = $('\n' +
            '<div class="nav-item" data-idx="'+data[i].cat_id+'" data-web="'+data[i].web_id+'">\n' +
            '    <div class="nav-link hover-bg">\n' +
            '        <a class="hover-text" href="'+data[i].link+'"><i class="'+data[i].icon+'"></i> '+data[i].name+'</a>\n' +
            '    </div>\n' +
            '</div>');
        if(typeof data[i].sub!=='undefined' && data[i].sub.length>0) {
            id = 'cat_'+data[i]['cat_id'];
            $('<i class="menu-arrow" data-toggle="collapse" href="#'+id+'" aria-expanded="false" aria-controls="'+id+'"></i>').appendTo(sub.find('.nav-link'));
            list = $('<div class="collapse" id="'+id+'"></div>');
            list.append(getList(data[i].sub));
            list.appendTo(sub);
        }
        sub.appendTo(obj);
    }
    return obj;
}
$(function(){
    let web_id = '<!--web_id-->';
    let websites = <!--websites-->;
    $.getJSON('<!--url_prefix-->api/CMS/get/admin_cat', function(data){
        if(typeof data.error==='undefined') {
            if(web_id==='1') {
                let list = data.admin_cat;
                let obj = null, obj_sub = null;
                let i = 0, j = 0, n = 0;
                for(i=list.length-1;i>=0;i--) {
                    if(list_func!==',all,' && list_func.indexOf(','+list[i].id+',')===-1) continue;
                    obj = $('' +
                        '<li class="nav-item">\n' +
                        '    <a class="nav-link" href="'+global.root_fix+list[i].path+'" title="'+list[i].comment+'">'+list[i].name+'</a>\n' +
                        '</li>');
                    if(typeof list[i].sub!='undefined' && list[i].sub.length>0) {
                        obj.addClass("dropdown").append('<div class="dropdown-menu"></div>');
                        obj.find('a').addClass("dropdown-toggle").attr({'href':'#','path':list[i].path,'data-toggle':'dropdown'});
                        obj_sub = obj.find('div');
                        for(j=0,n=list[i].sub.length;j<n;j++) {
                            if(list_func!==',all,' && list_func.indexOf(','+list[i].sub[j].id+',')===-1) continue;
                            obj_sub.append('<a class="dropdown-item" href="'+list[i].sub[j].path+'">'+list[i].sub[j].name+'</a>');
                        }
                    }
                    obj.prependTo('#nav > ul');
                }
                obj = $('' +
                    '<li class="nav-item dropdown">\n' +
                    '    <a class="nav-link dropdown-toggle" href="#" path="###" data-toggle="dropdown"><!--lng_admin_web--></a>\n' +
                    '    <div class="dropdown-menu"></div>\n' +
                    '</li>');
                obj_sub = obj.find('div');
                for(i=websites.length-1;i>=0;i--) {
                    if(websites[i].domain.length < 5) {
                        websites[i].domain = '/';
                    } else {
                        if(websites[i].domain.indexOf(',')) {
                            websites[i].domain = websites[i].domain.replace(/,.+$/, '');
                        }
                        websites[i].domain = 'http://'+websites[i].domain;
                    }
                    obj_sub.prepend('<a class="dropdown-item" href="'+websites[i].domain+'" target="_blank">'+websites[i].name+'</a>');
                }
                obj.appendTo('#nav > ul');
            } else {
                let list = data.admin_cat_plat;
                for(let i=list.length-1; i>=0; i--) {
                    if(list_func!==',all,' && list_func.indexOf(','+list[i].id+',')===-1) continue;
                    if(list[i].public==='0') continue;
                    $('' +
                    '<li class="nav-item">\n' +
                    '    <a class="nav-link" href="'+global.root_fix+list[i].path+'" title="'+list[i].comment+'">'+list[i].name+'</a>\n' +
                    '</li>').prependTo('#nav > ul');
                }
                $('' +
                    '<li class="nav-item">\n' +
                    '    <a class="nav-link" href="/" title="<!--lng_admin_web-->" target="_blank"><!--lng_admin_web--></a>\n' +
                    '</li>').appendTo('#nav > ul');
            }
            setLink();
        } else {
            alert(data.error+' - '+data.message);
        }
    });
    let sidebar_top = 70;
    $.getJSON('<!--url_prefix-->api/CMS/get/news_cat', function(data){
        if(typeof data.error==='undefined') {
            let obj = $('#sidebar');
            obj.append(getList(data).removeClass('sub-menu'));
            obj.css('height', '0');
            setLink();
            obj.find('.collapse').addClass('show');
            obj.find('.menu-arrow').removeClass('collapsed').attr('aria-expanded', true);
            if(web_id !== '1') {
                $('#web_id').val(web_id).trigger('change').hide();
                $('.sidebar-go-top').css('top', 4);
                $('.sidebar-go-down').css('top', 22);
                sidebar_top -= 28;
            }
            if($('#sidebar').height() > $("#sidebar > .nav").height()) {
                $("#list > div").hide();
                sidebar_top = 0;
            } else {
                $('button[name=collapse]').trigger('click');
            }
            $('#sidebar').css('padding-top', sidebar_top);
        } else {
            alert(data.error+' - '+data.message);
        }
    });
    $('iframe').attr('src', location.href.replace(/#.+$/, '')+'/info');
    $('#list_tab .arrow').hover(function(e){
        global.timer = setInterval(function(){
            setPos(e.target);
        }, 30);
    },function(){
        clearInterval(global.timer);
    });
    $('#sidebar').mousewheel(function(e){
        if($('#sidebar').height() > $('#sidebar > .nav').height() + sidebar_top) return;
        let obj = $(this);
        let top = obj.position().top;
        let step = 5;
        let d = e.deltaY;
        if(d<0) {
            if(obj.find(".nav").height()+top > $(window).height()-$('header').height()-$('body').height()/5) {
                obj.css('top', top-step);
            }
        } else {
            if(obj.position().top < 0) {
                if(top>-step) step = -top;
                obj.css('top', top+step);
            }
        }
        e.stopPropagation();
        return false;
    });
    $('#list .sidebar-go-top').click(function(){
        $('#sidebar').animate({'top':0});
    });
    $('#list .sidebar-go-down').click(function(){
        let obj = $('#sidebar');
        obj.find('.collapse').addClass('show');
        obj.find('.menu-arrow').removeClass('collapsed').attr('aria-expanded', true);
        obj.animate({'top': $(window).height() - $('header').height() - $('#sidebar > .nav').height() - $('body').height()/5});
    });
    $('body').css('overflow','hidden');
    $('#bar').click(function(e){
        e.preventDefault();
        e.stopPropagation();
        if($('#list').position().left===0) {
            $('#bar').css('opacity',0.5).animate({left:0}, function(){
                $(this).find('span').css({
                    'transform':'rotate(0deg)',
                    'webkitTransform':'rotate(0deg)',
                    'mozTransform':'rotate(0deg)',
                    'msTransform':'rotate(0deg)'
                });
            }).hover(function(){
                $('#bar').css('opacity',1);
            }, function(){
                $('#bar').css('opacity',0.5);
            });
            $('#list').animate({left:-200});
        } else {
            $('#bar').unbind("mouseenter").unbind("mouseleave").css('opacity',1).animate({left:200},function(){
                $(this).find('span').css({
                    'transform':'rotate(180deg)',
                    'webkitTransform':'rotate(180deg)',
                    'mozTransform':'rotate(180deg)',
                    'msTransform':'rotate(180deg)'
                });
            });
            $('#list').animate({left:0});
        }
    });
    $('#web_id').change(function(){
        let idx = this.value;
        if(idx==='') {
            $('div[data-web]').show();
        } else {
            $('div[data-web]').hide();
            $('div[data-web='+idx+']').show();
        }
        $('button[name=expand]').trigger('click');
    });
    $('button[name=expand]').click(function(){
        let obj = $('#sidebar');
        $('#sidebar').animate({'top':0});
        obj.find('.collapse').addClass('show');
        obj.find('.menu-arrow').removeClass('collapsed').attr('aria-expanded', true);
    });
    $('button[name=collapse]').click(function(){
        let obj = $('#sidebar');
        $('#sidebar').animate({'top':0});
        obj.find('.collapse').removeClass('show');
        obj.find('.menu-arrow').addClass('collapsed').attr('aria-expanded', false);
    });
    $('#sidebar').css('padding-top', sidebar_top);
    setTheme($.cookie('ms_theme'));
    $('body').disableSelection();
    $(window).resize(function(){
        resizeMain();
        setTimeout(resizeMain, 2000);
    });
    $(window).trigger('resize')
});
</script>