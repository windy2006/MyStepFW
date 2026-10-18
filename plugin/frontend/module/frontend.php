<?php

/**
 * 文件：plugin/frontend/module/frontend.php
 * 功能说明：frontend 插件的 frontend 功能模块
 *
 * 方法目录：
 *   本文件不直接声明具名方法或函数。
 *
 * 回调目录：
 *   回调 1：从应用目录路径提取配置选项标识。
 *   回调 2：从 jQuery 文件路径提取版本文件名。
 *   回调 3：从 Bootstrap 目录路径提取版本号。
 *
 * 使用说明：
 *   1. 校验并保存应用资源选择，准备应用及组件版本循环数据。
 *   2. 对应 template/frontend.tpl；保存严格校验，读取展示逐项容错，成功保存后清理脚本缓存。
 *
 * 说明：PHPDoc 类型用于说明，不增加原生类型约束；源码保持 PHP 7.4 兼容。
 *       方法目录覆盖本文件直接声明；闭包参数及捕获变量说明见各回调旁 PHPDoc。
 */

$setting = new myConfig(__DIR__.'/../config.php');
if(myReq::check('post')) {
    try {$detail=plugin_frontend::selections(myReq::post('detail','!'));} catch(InvalidArgumentException $error) {myStep::info('error_para');}
    $setting->detail = json_encode($detail ?: new stdClass(),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    $setting->save();
    myFile::del(CACHE.'script');
}

$tpl_sub->assign('title', '应用前端框架调整');
try {$detail=plugin_frontend::selections($setting->detail, false);} catch(InvalidArgumentException $error) {$detail=[];}
$tpl_sub->assign('detail', htmlspecialchars(json_encode($detail ?: new stdClass(),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),ENT_QUOTES,'UTF-8'));

$dirs = myFile::find('', APP, false, myFile::DIR);
$dirs = array_map(
/**
 * 功能说明：从应用目录路径提取配置选项标识。
 *
 * @param string $v 应用目录路径
 * @return string 路径最后一级名称
 */
function ($v) {return basename($v);} , $dirs);
foreach($dirs as $k) {
    if(!is_file(APP.$k.'/info.php')) continue;
    $tpl_sub->setLoop('app', include(APP.$k.'/info.php'));
}

$dirs = myFile::find('', __DIR__.'/../files/jquery/', false, myFile::FILE);
$dirs = array_map(
/**
 * 功能说明：从 jQuery 文件路径提取版本文件名。
 *
 * @param string $v jQuery 资源文件路径
 * @return string 不含目录的文件名
 */
function ($v) {return basename($v);} , $dirs);
foreach($dirs as $k) {
    if(strpos($k, 'migrate')) continue;
    $tpl_sub->setLoop('jq', ['ver'=>$k]);
}

$dirs = myFile::find('', __DIR__.'/../files/bootstrap/', false, myFile::DIR);
$dirs = array_map(
/**
 * 功能说明：从 Bootstrap 目录路径提取版本号。
 *
 * @param string $v Bootstrap 版本目录路径
 * @return string 版本目录名称
 */
function ($v) {return basename($v);} , $dirs);
foreach($dirs as $k) {
    $tpl_sub->setLoop('bs', ['ver'=>$k]);
}