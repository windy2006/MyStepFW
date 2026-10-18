<?php

/**
 * 文件：plugin/manager/module/manager.php
 * 功能说明：manager 插件的 manager 功能模块
 *
 * 方法目录：
 *   本文件不直接声明具名方法或函数。
 *
 * 使用说明：
 *   1. 读取本地版本比较资料，准备应用/插件设置与更新链接及模板状态。
 *   2. 对应 template/manager.tpl；模块自身不发起远端升级，管理页脚本仍可触发后续请求。
 *
 * 说明：PHPDoc 类型用于说明，不增加原生类型约束；源码保持 PHP 7.4 兼容。
 *       方法目录覆盖本文件直接声明；闭包参数及捕获变量说明见各回调旁 PHPDoc。
 */

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
    $info['link'] = '#';
    $info['update_class'] = 'd-none';
    if(!empty($info['remote']) && version_compare($info['remote'], $info['ver'])>0) {
        $info['link'] = myStep::setURL('manager/app/'.rawurlencode($info['app']));
        $info['update_class'] = '';
    }
    $info['setting_url'] = ($page_app??'myStep')==='Admin'
        ? pluginQueryURL(myStep::setURL('Admin/setting'),['target'=>$info['app']])
        : myStep::setURL(trim($ms_setting->gen->path_admin,'/').'/setting/'.rawurlencode($info['app']));
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
    $info['link'] = '#';
    $info['update_class'] = 'd-none';
    if(!empty($info['remote']) && version_compare($info['remote'], $info['ver'])>0) {
        $info['link'] = myStep::setURL('manager/plugin/'.rawurlencode($info['idx']));
        $info['update_class'] = '';
    }
    $info['setting_url'] = pluginAdminURL('function/plugin/setting/',['idx'=>$info['idx']],$page_app??'myStep');
    $tpl_sub->setLoop('plugin', $info);
}


$paras = [
    'version' => include(CONFIG.'version.php'),
    'link'=> $mystep->setting->web->update
];
$tpl_sub->assign($paras);
$tpl_sub->assign('update_url',htmlspecialchars(plugin_manager::updateBase($ms_setting->web->update),ENT_QUOTES,'UTF-8'));
