<?PHP

/**
 * 文件：plugin/sample/module/show1.php
 * 功能说明：sample 插件的 show1 功能模块
 *
 * 方法目录：
 *   本文件不直接声明具名方法或函数。
 *
 * 使用说明：
 *   1. 为已登记的 test1 模块直接输出框架上下文示例。
 *   2. 对象只显示类名，其他展示值进行 HTML 转义；不输出连接对象内部凭据。
 *
 * 说明：PHPDoc 类型用于说明，不增加原生类型约束；源码保持 PHP 7.4 兼容。
 *       方法目录覆盖本文件直接声明；闭包参数及捕获变量说明见各回调旁 PHPDoc。
 */

echo '
<title>模块测试</title>
<div><b>模块内容测试</b> : </div>
<div>模块页面内有如下全局变量可直接调用</div>
';
foreach ([
         'info_app' => '当前app基本信息',
         'tpl_setting' => '模版设置变量，参见模版类【<a href="/Document/myTemplate" target="_blank">使用样例</a>】 【<a href="/Document/myTemplate/detail" target="_blank">方法说明</a>】',
         'tpl_cache' => '页面缓存设置，如设置为false则禁用缓存，参见模版类【<a href="/Document/myTemplate" target="_blank">使用样例</a>】 【<a href="/Document/myTemplate/detail" target="_blank">方法说明</a>】',
         'ms_setting' => '设置信息调用对象，详见【<a href="/console/setting/" target="_blank">框架设置</a>】',
         'mystep' => '核心控制函数，参见【<a href="/Document/myStep" target="_blank">使用样例</a>】 【<a href="/Document/myStep/detail" target="_blank">方法说明</a>】',
         'router' => '当前路由对象，参见【<a href="/Document/myRouter" target="_blank">使用样例</a>】 【<a href="/Document/myRouter/detail" target="_blank">方法说明</a>】',
         'db' => '数据库对象，参见MySQL【<a href="/Document/mysql" target="_blank">使用样例</a>】 【<a href="/Document/mysql/detail" target="_blank">方法说明</a>】',
         'cache' => '缓存对象，参见【<a href="/Document/myCache" target="_blank">使用样例</a>】 【<a href="/Document/myCache/detail" target="_blank">方法说明</a>】',
         ] as $k => $v) {
    $value=is_object($$k) ? ['class'=>get_class($$k)] : $$k;
    $detail=htmlspecialchars(print_r($value,true),ENT_QUOTES,'UTF-8');
    echo <<<mystep
<h6 class="ml-4">{$k} : {$v}</h6>
<div style="width:96%;max-height:200px;overflow-x:hidden;overflow-y:auto;border:1px gray dashed;padding:10px 20px;margin:20px auto;">
<pre class="brush:php;">
{$detail}
</pre>
</div>
mystep;
}
