<?PHP
/**
 * 文件：app/CMS/template/guide/module/article.php
 * 功能说明：整理手册标题，并按本站栏目阅读顺序生成前后篇，避免新增专题后按文章ID跳到无关章节。
 * 方法目录：本文件不声明方法或函数。
 * 使用说明：CMS文章模块已完成生效期和阅读权限校验后加载；模板沿用guide/article.tpl。
 */
$S->web->title = preg_replace('#^(.+?)_\1_#', '\1_', $S->web->title);
$guide_menu = \app\CMS\guideContents();
$guide_articles = $guide_menu['articles'];
$guide_current = array_search((int)$id, array_column($guide_articles, 'id'), true);
$guide_previous = $guide_current !== false && $guide_current > 0 ? $guide_articles[$guide_current - 1] : null;
$guide_next = $guide_current !== false ? ($guide_articles[$guide_current + 1] ?? null) : null;
$t->assign([
    'guide_previous_url'=>$guide_previous['url'] ?? \app\CMS\getURL(''),
    'guide_previous_title'=>$guide_previous['title'] ?? '返回手册目录',
    'guide_next_url'=>$guide_next['url'] ?? \app\CMS\getURL(''),
    'guide_next_title'=>$guide_next['title'] ?? '返回手册目录',
]);
