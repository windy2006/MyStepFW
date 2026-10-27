<?PHP
/**
 * 文件：app/CMS/template/guide/module/index.php
 * 功能说明：按当前手册子站栏目生成阅读首页，不把根readme当作手册正文。
 * 方法目录：本文件不声明方法或函数；目录读取使用app\CMS\guideContents。
 * 使用说明：由CMS页面入口在guide风格下加载，HTML交给index.tpl和block_manual_index.tpl。
 */
$guide_menu = \app\CMS\guideContents();
$t->assign('guide_article_count', count($guide_menu['articles']));
$t->assign('guide_framework_version', include CONFIG.'version.php');
foreach($guide_menu['groups'] as $guide_group) {
    $guide_block_setting = $tpl_setting;
    $guide_block_setting['name'] = 'block_manual_index';
    $guide_block = new myTemplate($guide_block_setting, false);
    $guide_block->setLoop('article', $guide_group['articles'], true);
    $t->setLoop('section', ['id'=>$guide_group['id'], 'name'=>$guide_group['name'], 'content'=>$mystep->render($guide_block)]);
}
