<?PHP

/**
 * 文件：plugin/myBackup/index.php
 * 功能说明：myBackup 插件的宿主初始化入口
 *
 * 方法目录：
 *   本文件不直接声明具名方法或函数。
 *
 * 使用说明：
 *   1. 显式加载备份插件主类，不在普通初始化时执行数据库恢复。
 *   2. 由 myController::plugin 在宿主方法作用域包含，此时 $this 为当前控制器。
 *   3. 文件存在不等于已安装或启用；范围由注册状态、应用 plugin.php 和框架分支共同决定。
 *
 * 说明：PHPDoc 类型用于说明，不增加原生类型约束；源码保持 PHP 7.4 兼容。
 *       方法目录覆盖本文件直接声明；闭包参数及捕获变量说明见各回调旁 PHPDoc。
 */

require_once(__DIR__."/class.php");
