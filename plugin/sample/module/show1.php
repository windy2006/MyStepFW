<?PHP
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
    $detail = var_export($$k, true);
    echo <<<mystep
<h6 class="ml-4">{$k} : {$v}</h6>
<div style="width:96%;max-height:200px;overflow-x:hidden;overflow-y:auto;border:1px gray dashed;padding:10px 20px;margin:20px auto;">
<pre class="brush:php;">
{$detail}
</pre>
</div>
mystep;
}
