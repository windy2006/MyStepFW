<h2>MyStep Framework 开发示例</h2>
<?php
global $info_app;
?>
<h5 class="mt-4">URL信息解析：</h5>
<ul style="width:96%;height:auto;overflow-x:hidden;overflow-y:auto;border:1px gray dashed;padding:10px 20px;margin:20px auto;">
    <li>参数网址测试 1：<a href="/sample/dir1/dir2/para1=111&para2=222">/sample/dir1/dir2/para1=111&amp;para2=222</a> </li>
    <li>参数网址测试 2：<a href="/sample/dir1/dir2/?para1=111&para2=222">/sample/dir1/dir2/?para1=111&amp;para2=222</a> </li>
    <li>参数网址测试 3：<a href="/sample/dir1/dir2?para1=111&para2=222">/sample/dir1/dir2?para1=111&amp;para2=222</a> </li>
    <li>数组格式的路径信息：$info_app['path'] = <?=var_export($info_app['path'], 1)?> </li>
    <li>数组格式的url参数：$info_app['para'] = <?=var_export($info_app['para'],1)?> </li>
    <li>字符串格式的路径信息：$info_app['route'] = <?=$info_app['route']?> </li>
    <li>$_GET = <?=var_export($_GET, 1)?>
</ul>

<h5 class="mt-4">自定义路由测试：</h5>
<ul style="width:96%;height:auto;overflow-x:hidden;overflow-y:auto;border:1px gray dashed;padding:10px 20px;margin:20px auto;">
    <li>执行接口指向自定义函数：<a href="/mySample/" target="_blank">/mySample/</a> （路由规则为：/mySample/[any]）</li>
    <li>通过mystep::getModule()执行（推荐）：<a href="/mySample2/" target="_blank">/mySample2/</a> （路由规则为：/mySample2/[any]）</li>
    <li>多函数链接调用：<a href="/anyCamelCase" target="_blank">/anyCamelCase</a> （路由规则为：/[camel]，其中camel正则为：[a-z]+([A-Z][a-z]+)+，
        响应规则为：array('app\sample\perCheck,3', 'app\sample\routeTest')，表示先执行'app\sample\perCheck'，且输入参数为'3'，
        如果结果为false则终止执行，否则将返回值传递至'app\sample\routeTest'并执行。还可以根据需要设置更多的链接函数。）</li>
</ul>

<h5 class="mt-4">API接口测试：</h5>
<ul style="width:96%;height:auto;overflow-x:hidden;overflow-y:auto;border:1px gray dashed;padding:10px 20px;margin:20px auto;">
    <li>基础模式（默认json格式）：<a href="/sample/api/sample" target="_blank">/sample/api/sample</a> </li>
    <li>返回XML格式：<a href="/sample/api/sample/xml" target="_blank">/sample/api/sample/xml</a> </li>
    <li>返回PHP脚本格式：<a href="/sample/api/sample/code" target="_blank">/sample/api/sample/code</a> </li>
    <li>返回序列化字符串格式：<a href="/sample/api/sample/string" target="_blank">/sample/api/sample/string</a> </li>
    <li>返回十六进制编译格式（可通过myString::fromHex解码）：<a href="/sample/api/sample/hex" target="_blank">/sample/api/sample/hex</a> </li>
    <li>按路径参数返回：<a href="/sample/api/sample/gen/charset" target="_blank">/sample/api/sample/gen/charset</a> </li>
    <li>按路径参数返回指定格式：<a href="/sample/api/sample/gen/xml" target="_blank">/sample/api/sample/gen/xml</a> </li>
    <li>URL中的查询字符串优先于路径参数：<a href="/sample/api/sample/json/?p1=gen&p2=charset" target="_blank">/sample/api/sample/json/?p1=gen&p2=charset</a> </li>
</ul>

<h5 class="mt-4">脚本代码：</h5>
<div style="width:96%;height:auto;overflow-x:hidden;overflow-y:auto;border:1px gray dashed;padding:10px 20px;margin:20px auto;">
<pre class="brush:php;">
<!--code-->
</pre>
</div>

<h5 class="mt-4">文档说明：</h5>
<pre style="width:96%;max-height:400px;overflow-x:hidden;overflow-y:auto;border:1px gray dashed;padding:10px 20px;margin:20px auto;">
    通过对应app目录下的index.php执行路径模式的路由调用,有如下变量可被调用：
    $tpl_setting = array(
        'name' => 配置里的模版名称（默认是main），一般采用主模版（body标签或主内容区以外的内容）加主体部分（页面主体内容）
        'path' => 指向当前路径下的模版目录
        'style' => 模版样式，如为空则直接调用模版目录下的对应文件
        'path_compile' => 模版缓存存储路径，默认存储在框架缓存目录
    );
    $tpl_cache = array(
        'path' => 缓存存储路径，默认存储在框架缓存目录
        'expire' => 缓存存活时间，仅针对当前模版对象
    );
    此外还包括info_app、s、mystep、db、cache、route等，具体变量信息如下：
</pre>
<!--loop:start key="para"-->
<h6 class="ml-4"><!--para_name--> : <!--para_comment--></h6>
<div style="width:96%;max-height:200px;overflow-x:hidden;overflow-y:auto;border:1px gray dashed;padding:10px 20px;margin:20px auto;">
<pre class="brush:php;">
<!--para_detail-->
</pre>
</div>
<!--loop:end-->