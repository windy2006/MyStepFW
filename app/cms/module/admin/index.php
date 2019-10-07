<?php
$tpl_setting['name'] = 'frame';
if(count($info_app['path'])>=2 && !empty($info_app['path'][1])) $tpl_setting['name'] = $info_app['path'][1];
if(!file_exists($tpl_setting['path'].'/'.$tpl_setting['style'].'/'.$tpl_setting['name'].'.tpl')) {
    myStep::info($mystep->getLanguage('module_missing'));
}
$t = new myTemplate($tpl_setting, false, true);
$content = $mystep->parseTpl($t, '', false);