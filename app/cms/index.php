<?php
$module = $info_app['path'][1] ?? 'index';
app\cms\installCheck($module);
if(!is_file(PATH.'module/'.$module.'.php')) myStep::info('module_missing', '/');
$tpl = new myTemplate($tpl_setting, $tpl_cache);
$tpl_setting['name'] = $module;
$t = new myTemplate($tpl_setting, false);
require(PATH.'module/'.$module.'.php');
$tpl->assign('main', $t->display('', false));
$mystep->show($tpl);
$mystep->end();