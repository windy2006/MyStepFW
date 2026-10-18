<?PHP

/**
 * 文件：plugin/sample/route.php
 * 功能说明：sample 插件的路由/API 声明源
 *
 * 方法目录：
 *   本文件不直接声明具名方法或函数。
 *
 * 使用说明：
 *   1. 由路由登记和重建流程读取 $rule/$api 等声明，不是每次请求的业务入口。
 *   2. 声明可能被编译缓存；类加载、处理器身份、方法与 CSRF 检查不能依赖该文件的 include。
 *   3. 插件停用后不再编译已知插件的路由和 API；业务处理器仍负责授权。
 *
 * 说明：PHPDoc 类型用于说明，不增加原生类型约束；源码保持 PHP 7.4 兼容。
 *       方法目录覆盖本文件直接声明；闭包参数及捕获变量说明见各回调旁 PHPDoc。
 */

$rule = array(
    array('/sample_route/[any]', array('plugin_sample::main')),
);
$api = array(
    'sample' => 'plugin_sample::api',
);