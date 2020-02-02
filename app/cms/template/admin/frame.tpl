<header class="navbar navbar-expand-sm navbar-dark navbar-c fixed-top">
    <button class="navbar-toggler collapsed" type="button" data-toggle="collapse" data-target="#nav" aria-expanded="false">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
        <a id="brand" class="btn navbar-brand d-none d-sm-inline-block mr-3" href="#" data-toggle="dropdown" data-offset="10,100"><b>迈思 CMS</b></a>
        <div class="dropdown-menu ml-3">
            <a class="dropdown-item" theme="default" href="#">默认主题</a>
            <a class="dropdown-item" theme="purple" href="#">紫色主题</a>
            <a class="dropdown-item" theme="brown" href="#" target="_blank">棕色主题</a>
        </div>
        <ul class="navbar-nav mr-auto mt-2 mt-lg-0"></ul>
    </div>
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" href="manager/login?out"><span class="glyphicon glyphicon-log-out"></span> 退出</a>
        </li>
    </ul>
</header>
<div class="container-fluid mt-5 pt-3">
    <div class="row">
        <div id="list">
            <nav class="sidebar sidebar-offcanvas" id="sidebar">
                <span class="sidebar-gotop glyphicon glyphicon-circle-arrow-up"></span>
            </nav>
        </div>
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
                        <iframe src="admin_cms/info" class="frame" frameborder="0"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<footer class="border-top text-center fixed-bottom bg-light pt-2 font-sm" style="max-height:60px;overflow:hidden;">
    <p>Powered by 『 MyStep Framework 』&nbsp;Copyright&copy; 2010-2019 <a href="mailto:windy2006@gmail.com">windy2006@gmail.com</a></p>
</footer>
<script language="JavaScript">
    function resizeMain() {
        if($('#main').height()>$(window).height()) return;
        $('#main').css('height', 'auto');
        $('#main').css('min-height', 0);
        $('#main').css('min-height', $(window).height()-80);
        $('.frame').height($('#main').height()-42);
        if(navigator.userAgent.indexOf(".NET") != -1) {
            let top = ($('#list').css('display')=='none') ? '0px' : '65px';
            $('#main').parent().css('padding-top', top);
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
        if(url==$('base').attr('href')) idx = 'tab_main';
        let obj = $('#'+idx);
        if(obj.length==0) {
            let tab = $('' +
                '<li class="nav-item" idx="'+idx+'">\n' +
                '   <a class="nav-link" data-toggle="tab" href="#'+idx+'"></a>\n' +
                '</li>');
            let frame = $('' +
                '<div class="tab-pane fade" id="'+idx+'">\n' +
                '   <iframe src="" class="frame" frameborder="0"></iframe>\n' +
                '</div>');
            tab.find('a').html(name+'<span class="glyphicon glyphicon-remove"></span>');
            tab.appendTo('#list_tab');
            frame.find('iframe').attr('src', url);
            frame.appendTo('#list_frame');
            tab.find('a span').click(function(e) {
                e.preventDefault();
                e.stopPropagation();
                removeFrame(idx);
            });
            if(tab.position().top>0) setTab();
        } else {
            let tab = $('#list_tab a[href="#'+idx+'"]').parent();
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
            $('#list_tab a[href="#tab_main"]').tab('show');
            $('#tab_main').addClass('active show');
        }
        $('#list_tab a[href="#'+idx+'"]').tab('dispose');
        $('#list_tab li[idx="'+idx+'"]').remove();
        $('#'+idx).remove();
        resizeMain();
    }
    function setLink() {
        $('a').unbind('click').on('click', function(e) {
            if(this.href.indexOf('#')===-1 && this.href.indexOf('javascript:')!==0) {
                e.preventDefault();
                addFrame($(this).text().trim(), this.href);
            } else {
                if($(this).attr('data-toggle')) return true;
                if(this.href.indexOf('#')===-1) location.href = location.pathname + this.href.replace(/^.+(#.*)$/g, '$1');
            }
            return false;
        });
        $('header a').click(function(){
            if(!$('header button:first').hasClass('collapsed') && this.href.indexOf('#')==-1) {
                $('header .collapse').collapse('hide');
                setPos($('#list_tab .left'),0);
                setPos($('#list_tab .right'),0);
            } else if($(this).hasClass('dropdown-item')) {
                $(this).parentsUntil('.dropdown').prev().dropdown('toggle');
            } else if($(this).hasClass('nav-link') && !$(this).hasClass('dropdown-toggle')) {
                $(this).parentsUntil('.navbar-nav').parent().find('a[aria-expanded=true]').dropdown('toggle');
            }
            if(typeof($(this).attr('path'))!='undefined' && $(this).attr('path').length > 3) {
                addFrame($(this).text(), $(this).attr('path'));
            }
        });
        $('a[theme]').click(function(e){
            e.preventDefault();
            e.stopPropagation();
            setTheme($(this).attr('theme'));
        });
    }
    function setTheme(idx) {
        if(idx == null || idx == '') idx = 'default';
        $('#theme_css').attr('href', setting.path_app+'/asset/theme/'+idx+'.css');
        $.cookie('ms_theme', idx, {expires: 365});
        $('a[theme]').removeClass('active');
        $('a[theme="'+idx+'"]').addClass('active');
    }
    $(function(){
        $('#list_tab .arrow').hover(function(e){
            global.timer = setInterval(function(){
                setPos(e.target);
            }, 30);
        },function(){
            clearInterval(global.timer);
        });
        setTimeout(function(){
            let idx = $.cookie('ms_theme');
            if(typeof setting !== 'undefined') setTheme(idx)
        },1000);
        resizeMain();
        $(window).resize(resizeMain);
        $.getJSON('api/cms/get/admin_cat', function(data){
            if(typeof data.err=='undefined') {
                let list = data.admin_cat;
                let obj = null, obj_sub = null;
                let i = 0, j = 0, n = 0;
                for(i=list.length-1;i>=0;i--) {
                    obj = $('' +
                        '<li class="nav-item">\n' +
                        '    <a class="nav-link" href="'+list[i].path+'" title="'+list[i].comment+'"><span class="'+list[i].icon+'"></span> '+list[i].name+'</a>\n' +
                        '</li>');
                    if(typeof list[i].sub!='undefined' && list[i].sub.length>0) {
                        obj.addClass("dropdown").append('<div class="dropdown-menu"></div>');
                        obj.find('a').addClass("nav-item dropdown-toggle").attr({'href':'#', 'path':list[i].path,'data-toggle':'dropdown'});
                        obj_sub = obj.find('div');
                        for(j=0,n=list[i].sub.length;j<n;j++) {
                            obj_sub.append('<a class="dropdown-item" href="admin_cms/'+list[i].sub[j].path+'">'+list[i].sub[j].name+'</a>');
                        }
                    }
                    obj.prependTo('#nav > ul');
                }
                setLink();
            } else {
                alert(data.err);
            }
        });
        function getList(data) {
            let obj = $('<div class="nav sub-menu"></div>');
            let sub = null;
            let id = '';
            let list = null;
            for(let i=0,m=data.length;i<m;i++) {
                if(typeof data[i].link =='undefined' || data[i].link=='') data[i].link = '#';
                data[i].icon = 'fa fa-caret-right';
                sub = $('\n' +
                    '                    <div class="nav-item">\n' +
                    '                        <div class="nav-link">\n' +
                    '                            <a href="'+data[i].link+'"><i class="'+data[i].icon+'"></i> '+data[i].name+'</a>\n' +
                    '                        </div>\n' +
                    '                    </div>');
                if(typeof data[i].sub!='undefined' && data[i].sub.length>0) {
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
        $.getJSON('api/cms/get/news_cat', function(data){
            if(typeof data.err=='undefined') {
                $('#sidebar').append(getList(data).removeClass('sub-menu'));
                setLink();
            } else {
                alert(data.err);
            }
        });
        $('#sidebar').mousewheel(function(e){
            let top = $('#sidebar').position().top;
            let step = 5;
            let d = e.deltaY;
            if(d<0) {
                if($('#sidebar').height()>$(window).height()-$('header').height() && $('#sidebar').height()+$('#sidebar').position().top>$(window).height()-$('header').height()) {
                    $('#sidebar').css('top', top-step);
                }
                $('#sidebar .sidebar-gotop').show();
            } else {
                if($('#sidebar').position().top<0) {
                    if(top>-step) step = -top;
                    $('#sidebar').css('top', top+step);
                } else {
                    $('#sidebar .sidebar-gotop').hide();
                }
            }
            e.stopPropagation();
            return false;
        });
        $('#sidebar .sidebar-gotop').click(function(){
            $('#sidebar').animate({'top':0}, function(){
                $('#sidebar .sidebar-gotop').hide();
            });
        });
    });
</script>