<?PHP
$module = $info_app['path'][2] ?? 'main';
$script = __DIR__.'/setting/'.$module.'.php';
if(!is_file($script)) {
    $module = 'main';
    $script = __DIR__.'/setting/main.php';
}
$tpl_setting['name'] = 'setting_'.$module;
$t = new myTemplate($tpl_setting);
if(is_file($script)) {
    include($script);
} else {
    myStep::info('page_error_module');
}
$content = $mystep->render($t);
$mystep->setAddedContent('end', '
<script type="application/javascript" src="static/js/checkForm.js"></script>
');