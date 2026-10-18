<?PHP

/**
 * 文件：plugin/manager/info.php
 * 功能说明：manager 插件的展示及应用关联元信息
 *
 * 方法目录：
 *   本文件不直接声明具名方法或函数。
 *
 * 使用说明：
 *   1. 后台发现和登记流程读取 return 的数组，idx 应与插件目录及主类后缀一致。
 *   2. 当前脚本使用全局 $mystep 装入语言包；不把配置、建表或安装写入放到元信息扫描中。
 *   3. app 是后台关联元信息，不是独立路由或 API 的权限白名单。
 *
 * 说明：PHPDoc 类型用于说明，不增加原生类型约束；源码保持 PHP 7.4 兼容。
 *       方法目录覆盖本文件直接声明；闭包参数及捕获变量说明见各回调旁 PHPDoc。
 */

global $mystep;
$mystep->setLanguagePack(dirname(__FILE__).'/language/', $mystep->setting->gen->language);
return array(
    'name' => $mystep->getLanguage('plugin_manager_info_name'),
    'idx' => basename(realpath(__DIR__)),
    'ver' => '1.0',
    'intro' => $mystep->getLanguage('plugin_manager_info_intro'),
    'copyright' => $mystep->getLanguage('plugin_manager_info_copyright'),
    'description' => $mystep->getLanguage('plugin_manager_info_description'),
    'app' => ['myStep']
);