<?php
$setting_tpl['name'] = 'frame';
if(count($info_app['path'])>=2 && !empty($info_app['path'][1])) $setting_tpl['name'] = $info_app['path'][1];
if(!file_exists($setting_tpl['path'].'/'.$setting_tpl['style'].'/'.$setting_tpl['name'].'.tpl')) {
    myStep::info($mystep->getLanguage('module_missing'));
}
$t = new myTemplate($setting_tpl, false, true);
$content = $mystep->parseTpl($t, '', false);