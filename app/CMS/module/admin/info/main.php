<?PHP
$tpl_setting['name'] = 'info_'.($info_app['path'][2]??'main');
$t = new myTemplate($tpl_setting, false, true);
$content = $mystep->render($t);