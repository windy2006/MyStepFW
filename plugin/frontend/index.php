<?PHP

/**
 * 文件：plugin/frontend/index.php
 * 功能说明：frontend 插件的宿主初始化入口
 *
 * 方法目录：
 *   本文件不直接声明具名方法或函数。
 *
 * 回调目录：
 *   回调 1：在 start 阶段按当前应用替换前端资源列表。
 *
 * 使用说明：
 *   1. 加载主类并登记应用资源替换 start 钩子。
 *   2. 由 myController::plugin 在宿主方法作用域包含，此时 $this 为当前控制器。
 *   3. 文件存在不等于已安装或启用；范围由注册状态、应用 plugin.php 和框架分支共同决定。
 *
 * 说明：PHPDoc 类型用于说明，不增加原生类型约束；源码保持 PHP 7.4 兼容。
 *       方法目录覆盖本文件直接声明；闭包参数及捕获变量说明见各回调旁 PHPDoc。
 */

require_once(__DIR__."/class.php");
$this->setFunction('start', 
/**
 * 功能说明：在 start 阶段按当前应用替换前端资源列表。
 *
 * 闭包依赖：绑定宿主控制器 $this，读取全局 info_app 和插件配置。
 *
 * 参数说明：无显式参数。
 *
 * @return void 未选择或配置不可用时保留当前资源；成功时更新宿主 CSS/JS 列表
 */
function() {
    global $info_app;
    $I=$info_app;
    $setting = new myConfig(__DIR__.'/config.php');
    try {$selection=plugin_frontend::selections($setting->detail, false);} catch(InvalidArgumentException $error) {return;}
    if($setting = $selection) {
        if(isset($setting[$I['app']])) {
            foreach($this->css as $k => $v) {
                if(basename($v)=='bootstrap.css') {
                    $this->css[$k] = __DIR__.'/files/bootstrap/'.$setting[$I['app']]['bs'].'/css/bootstrap.css';
                    break;
                }
            }
            $result = [];
            foreach($this->js as $k => $v) {
                if(basename($v)=='jquery.js') {
                    $file = __DIR__.'/files/jquery/'.$setting[$I['app']]['jq'];
                    $result[md5($file)] = $file;
                    $file=plugin_frontend::migrate($setting[$I['app']]['jq']);
                    if($file!=='') $result[md5($file)]=$file;
                }elseif(in_array(basename($v),['bootstrap.bundle.js','bootstrap.js'],true)) {
                    $file = __DIR__.'/files/bootstrap/'.$setting[$I['app']]['bs'].'/js/bootstrap.bundle.js';
                    if(!is_file($file)) $file=dirname($file).'/bootstrap.js';
                    $result[md5($file)] = $file;
                } else {
                    $result[$k] = $v;
                }
            }
            $this->js = $result;
            unset($result);
        }
    }
});