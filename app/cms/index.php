<?php
require_once(PATH.'lib.php');
$module = $info_app['path'][1] ?? 'index';
$tpl_setting['name'] = 'main';
app\cms\installCheck($module);

$tpl = new myTemplate($tpl_setting, $tpl_cache);

$tpl_setting['name'] = implode('_', $info_app['path']);
$t = new myTemplate($tpl_setting, false);

if(!is_file(PATH.'module/'.$module.'.php')) myStep::info($mystep->getLanguage('module_missing'), '/');
require(PATH.'module/'.$module.'.php');
$tpl->assign('main', $t->display('', false));

$mystep->show($tpl);
$mystep->end();