<?PHP
$module = $info_app['path'][2] ?? 'app';
$script = __DIR__.'/function/'.$module.'.php';
if(!is_file($script)) {
    $module = 'app';
    $script = __DIR__.'/function/app.php';
}
if(isset($info_app['path'][3])) $module .= '_'.$info_app['path'][3];
if(!is_file(PATH.'template/function_'.$module.'.tpl')) {
    myStep::info('page_error_module');
}
$tpl_setting['name'] = 'function_'.$module;
$t = new myTemplate($tpl_setting);
include($script);
$t->assign('path_admin', $app_root);
$content = $mystep->render($t);
$mystep->setAddedContent('end', '
<script type="application/javascript" src="'.ROOT_WEB.'static/js/checkForm.js"></script>
');