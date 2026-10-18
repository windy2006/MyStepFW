<?PHP

/**
 * 文件：plugin/sample/module/show2.php
 * 功能说明：sample 插件的 show2 功能模块
 *
 * 方法目录：
 *   本文件不直接声明具名方法或函数。
 *
 * 使用说明：
 *   1. 为已登记的 test2 模块创建应用外层及 template/show.tpl 子模板。
 *   2. setPluginTemplate 第三参 false 复用应用模板配置，模块输出交给框架继续装配。
 *
 * 说明：PHPDoc 类型用于说明，不增加原生类型约束；源码保持 PHP 7.4 兼容。
 *       方法目录覆盖本文件直接声明；闭包参数及捕获变量说明见各回调旁 PHPDoc。
 */

list($tpl, $t) = setPluginTemplate('sample', 'show', false);
$t->assign('topic', 'Bootstrap 4 Dashboard');
$content = $mystep->render($t);