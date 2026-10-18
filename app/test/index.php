<?php
echo $GLOBALS['the_order']++.' - app/test/index.php<br />';

$tpl = new myTemplate($tpl_setting, $tpl_cache);
$m = $m ?: 'index';
$file = __DIR__.'/module/'.$m.'.php';
if(is_file($file) && is_file(__DIR__.'/template/'.$m.'.tpl')) {
    $tpl_setting['name'] = $m;
    $tpl_sub = new myTemplate($tpl_setting, $tpl_cache);
    include($file);
} else {
    $tpl_setting['name'] = 'index';
    $tpl_sub = new myTemplate($tpl_setting, $tpl_cache);
    include(__DIR__.'/module/index.php');
}
if(!isset($content)) $content = $mystep->render($tpl_sub);