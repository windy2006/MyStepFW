<?php
$tpl_setting['path'] = PATH.'sample/';
$tpl_setting['name'] = $info_app['path'][2] ?? '';
$content = '';
if(is_file($tpl_setting['path'].$tpl_setting['name'].'.tpl')) {
    $t = new myTemplate($tpl_setting, false, true);
    r::s('sign_upload', 'y');
    $content = $mystep->parseTpl($t, 's', false);
    unset($t);
}
$list = include(PATH.'sample/idx.php');
$title = $list[$tpl_setting['name']] ?? 'Samples';

$tpl_setting['name'] = 'index';
$t = new myTemplate($tpl_setting, false, true);
foreach($list as $k => $v) {
    $t->setLoop('idx', array($k, $v));
}

$t->assign('title', $title);
$t->assign('main', $content);
$content = $t->display('s', false);

$tpl->assign('path', 'manager/sample');