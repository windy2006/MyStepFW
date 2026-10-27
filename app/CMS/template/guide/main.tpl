<!DOCTYPE html>
<html lang="zh-CN">
<head>
<title><!--web_title|html--></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Content-Type" content="text/html; charset=<!--charset-->" />
<meta name="description" content="<!--web_description|html-->">
<base href="<!--path_root-->" />
<link rel="Shortcut Icon" href="favicon.ico" />
<!--page_start-->
</head>
<body class="py-5">
<header class="navbar navbar-expand-xl navbar-dark bd-navbar fixed-top">
    <a class="navbar-brand mr-3" href="<!--url_prefix_app-->"><b>迈思手册</b></a>
    <button class="navbar-toggler collapsed" type="button" data-toggle="collapse" data-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="展开手册导航"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav">
        <ul id="top_nav" class="navbar-nav mr-auto">
            <!--loop:start key="news_cat"-->
            <li class="nav-item"><a class="nav-link" href="<!--news_cat_link|html-->" data-guide-index="<!--news_cat_idx-->"><!--news_cat_name|html--></a></li>
            <!--loop:end-->
        </ul>
        <form action="<!--url_prefix_app-->search" role="search" class="guide-search">
            <label class="sr-only" for="search_input">搜索手册</label>
            <div class="input-group"><input id="search_input" type="search" name="k" class="form-control" placeholder="搜索手册" minlength="2" required><div class="input-group-append"><button class="btn btn-outline-light" type="submit">搜索</button></div></div>
        </form>
    </div>
</header>
<div class="container-fluid">
    <div class="row guide-layout">
        <aside id="list" aria-label="手册栏目">
            <nav class="guide-sidebar">
                <ul id="side_nav" class="navbar-nav list-group">
                    <li class="nav-item"><a class="nav-link" href="<!--url_prefix_app-->">手册目录</a></li>
                    <!--loop:start key="news_cat"-->
                    <li class="nav-item" data-guide-index="<!--news_cat_idx-->" data-category="<!--news_cat_cat_id-->"><a class="nav-link" href="<!--news_cat_link|html-->"><!--news_cat_name|html--></a></li>
                    <!--loop:end-->
                </ul>
            </nav>
        </aside>
        <!--main-->
    </div>
</div>
<footer class="border-top text-center bg-light"><!--info idx="copyright"--></footer>
<script type="application/javascript">
/** 手册栏目只用本地jQuery/Bootstrap，名称用文本节点装配，左侧只展开一个栏目，并在当前标签页保留状态。 */
$(function () {
    const categories = <!--news_cat-->;
    const current = typeof cat_id === 'undefined' ? 0 : Number(cat_id);
    const stateKey = 'mystep:cms-guide:' + location.host + ':' + setting.path_root;
    let saved = {};
    try { saved = JSON.parse(sessionStorage.getItem(stateKey) || '{}'); } catch (error) { saved = {}; }
    if (!saved || typeof saved !== 'object' || Array.isArray(saved)) saved = {};
    let activeGroup = '', savedGroup = '';
    /** 保存唯一展开栏目；空字符串表示全部收起，旧的多项记录在初始化时归并。
     * @param {string} id 合法栏目面板id或空字符串。
     * @returns {void} 存储不可用时仍维持当前页面的手风琴行为。
     */
    function rememberOpen(id) {
        saved = id ? {[id]: true} : {};
        try { sessionStorage.setItem(stateKey, JSON.stringify(saved)); } catch (error) { /* 存储不可用时仍可正常阅读。 */ }
    }
    $('#top_nav a[data-guide-index]').each(function () {
        const group = categories[Number(this.dataset.guideIndex)];
        if (!group) return;
        if (Number(group.cat_id) === current) $(this).addClass('active').attr('aria-current','page');
        if (!Array.isArray(group.sub) || !group.sub.length) return;
        const button = $(this).addClass('dropdown-toggle').attr({'data-toggle':'dropdown','aria-haspopup':'true','aria-expanded':'false'});
        const menu = $('<div class="dropdown-menu"></div>');
        const side = $('#side_nav > li').filter(function () { return this.dataset.guideIndex === String(button[0].dataset.guideIndex); });
        const collapseId = 'guide-group-' + Number(group.cat_id);
        const sideList = $('<div class="collapse guide-submenu"></div>').attr({'id':collapseId,'data-parent':'#side_nav'});
        side.children('a').attr({'data-toggle':'collapse','href':'#'+collapseId,'aria-controls':collapseId,'aria-expanded':'false','data-parent':'#side_nav'});
        group.sub.forEach(function (entry) {
            if ((Number(entry.show) & 1) === 0) return;
            const url = entry.link || 'catalog/' + encodeURIComponent(entry.idx);
            $('<a class="dropdown-item"></a>').attr({'href':url,'data-id':entry.cat_id}).text(entry.name).appendTo(menu);
            $('<a class="dropdown-item"></a>').attr({'href':url,'data-id':entry.cat_id}).text(entry.name).appendTo(sideList);
        });
        button.parent().addClass('dropdown').append(menu);
        side.addClass('guide-has-children').append(sideList);
        setURL('<!--url_prefix_app-->', menu);
        setURL('<!--url_prefix_app-->', sideList);
        const active = sideList.find('a').filter(function () { return Number(this.dataset.id) === current; });
        if (active.length) { active.addClass('active').attr('aria-current','page'); side.addClass('active'); button.addClass('active'); }
        if (active.length || Number(group.cat_id) === current) activeGroup = collapseId;
        if (!savedGroup && saved[collapseId] === true) savedGroup = collapseId;
    });
    $('#side_nav > li').filter(function () { return Number(this.dataset.category) === current; }).addClass('active');
    const opened = activeGroup || savedGroup;
    $('#side_nav .guide-submenu').each(function () {
        const expanded = this.id === opened;
        $(this).toggleClass('show', expanded).siblings('a').toggleClass('collapsed', !expanded).attr('aria-expanded', expanded ? 'true' : 'false');
    }).on('show.bs.collapse', function (event) {
        if (event.target === this) rememberOpen(this.id);
    }).on('hide.bs.collapse', function (event) {
        // 展开新项先保存新id，再收起旧项；旧项的hide不能抹去新项状态。
        if (event.target === this && saved[this.id] === true) rememberOpen('');
    });
    rememberOpen(opened);
    setURL('<!--url_prefix_app-->');
});
</script>
<!--page_end-->
</body>
</html>
