<?php
$remote_ver = [];
$file = PLUGIN.'manager/check_app.php';
if(is_file($file)) {
    $remote_ver = include($file);
}
$apps = [];
$dirs = myFile::find('*', APP, false, myFile::DIR);
foreach($dirs as $k) {
    $k .= 'info.php';
    if(!is_file($k)) continue;
    $info = include($k);
    $info['remote'] = $remote_ver[$info['app']] ?? '';
    //$info['link'] = "javascript:alert('No update available!')";
    $info['link'] = '" style="display:none';
    if(!empty($info['remote']) && version_compare($info['remote'], $info['ver'])>0)
        $info['link'] = ROOT_WEB.'manager/app/'.$info['app'];
    $tpl_sub->setLoop('app', $info);
}

$remote_ver = [];
$file = PLUGIN.'manager/check_plugin.php';
if(is_file($file)) {
    $remote_ver = include($file);
}
$plugins = [];
$files = myFile::find('*', PLUGIN, false, myFile::DIR);
foreach($files as $k) {
    $k .= 'info.php';
    if(!is_file($k)) continue;
    $info = include($k);
    $info['remote'] = $remote_ver[$info['idx']] ?? '';
    //$info['link'] = "javascript:alert('No update available!')";
    $info['link'] = '" style="display:none';
    if(!empty($info['remote']) && version_compare($info['remote'], $info['ver'])>0)
        $info['link'] = ROOT_WEB.'manager/plugin/'.$info['idx'];
    $tpl_sub->setLoop('plugin', $info);
}

$tpl_sub->assign('path_admin', $ms_setting->gen->path_admin);

$paras = [
    'version' => include(CONFIG.'version.php'),
    'link'=> $mystep->setting->web->update
];
$tpl_sub->assign($paras);