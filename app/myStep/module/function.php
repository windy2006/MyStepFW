<?php
$module = $info_app['path'][2];
$script = dirname(__FILE__).'/function/'.$module.'.php';
if(!is_file($script)) {
    $module = 'app';
    $script = dirname(__FILE__).'/function/app.php';
}
if(isset($info_app['path'][3])) $module .= '_'.$info_app['path'][3];
if(!is_file(PATH.'template/function_'.$module.'.tpl')) {
    myStep::info($mystep->getLanguage('page_error_module'));
}
$tpl_setting['name'] = 'function_'.$module;
$t = new myTemplate($tpl_setting, false, true);
include($script);
$content = $mystep->parseTpl($t, 's', false);
$mystep->setAddedContent('end', '
<script language="JavaScript" src="static/js/checkForm.js"></script>
');