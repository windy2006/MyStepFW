<?php
$setting_tpl['path'] = PATH.'sample/';
$setting_tpl['name'] = $info_app['path'][2] ?? '';
$content = '';
if(is_file($setting_tpl['path'].$setting_tpl['name'].'.tpl')) {
    $t = new myTemplate($setting_tpl, false, true);
    r::s('sign_upload', 'y');
    $content = $mystep->display($t, 's', false);
    unset($t);
}
$list = include(PATH.'sample/idx.php');
$title = $list[$setting_tpl['name']] ?? 'Samples';

$setting_tpl['name'] = 'index';
$t = new myTemplate($setting_tpl, false, true);
foreach($list as $k => $v) {
    $t->setLoop('idx', array($k,$v));
}

$t->assign('title', $title);
$t->assign('main', $content);
$content = $t->display('s', false);

$tpl->assign('path', 'manager/sample');