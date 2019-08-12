<?php
myStep::setPara();
require_once(PATH.'lib.php');

$module = $info_app['path'][1] ?? 'index';
$setting_tpl['name'] = 'main';
app\cms\installCheck($module);

$setting_cache = array(
    'path' => CACHE.'/app/cms/html/',
    'expire' => 60*60*24
);

$tpl = new myTemplate($setting_tpl, $setting_cache);

$setting_tpl['name'] = implode('_', $info_app['path']);
$t = new myTemplate($setting_tpl, false);

if(!is_file(PATH.'module/'.$module.'.php')) myStep::info($mystep->getLanguage('module_missing'), '/');
require(PATH.'module/'.$module.'.php');
$tpl->assign('main', $t->display('', false));

$mystep->show($tpl);
$mystep->end();