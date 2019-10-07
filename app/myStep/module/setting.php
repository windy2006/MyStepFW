<?php
$module = $info_app['path'][2] ?? 'main';
$script = dirname(__FILE__).'/setting/'.$module.'.php';
if(!is_file($script)) {
    $module = 'main';
    $script = dirname(__FILE__).'/setting/main.php';
}
$tpl_setting['name'] = 'setting_'.$module;
$t = new myTemplate($tpl_setting, false, true);
if(is_file($script)) {
    include($script);
} else {
    myStep::info($mystep->getLanguage('page_error_module'));
}
$content = $mystep->parseTpl($t, 's', false);
$mystep->setAddedContent('end', '
<script language="JavaScript" src="static/js/checkForm.js"></script>
');