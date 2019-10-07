<h2>MyStep Framework 自定义路由示例 1 - 自定义函数控制</h2>

<h5 class="mt-4">文档说明：</h5>
<pre style="width:96%;max-height:400px;overflow-x:hidden;overflow-y:auto;border:1px gray dashed;padding:10px 20px;margin:20px auto;">
本例通过自定义函数（lib.php 文件中的 route()）执行，如需引用全局变量，需通过global声明：
</pre>

<?php
global $info_app;
?>
<h5 class="mt-4">路径引用测试：</h5>
<div style="width:96%;height:auto;overflow-x:hidden;overflow-y:auto;border:1px gray dashed;padding:10px 20px;margin:20px auto;">
    自定义路由：<a href="/mySample/">/mySample/</a> <br />
    带路径：<a href="/mySample/dir1/dir2/">/mySample/dir1/dir2/</a> <br />
    带查询字串：<a href="/mySample/?para1=aaa&para2=bbb">/mySample/?para1=aaa&amp;para2=bbb</a> <br />
    带路径和查询字串：<a href="/mySample/dir1/dir2/?para1=aaa&para2=bbb">/mySample/dir1/dir2/?para1=aaa&amp;para2=bbb</a> <br />
</div>

<h5 class="mt-4">URL信息解析：</h5>
<div style="width:96%;height:auto;overflow-x:hidden;overflow-y:auto;border:1px gray dashed;padding:10px 20px;margin:20px auto;">
    数组格式的路径信息：$info_app['path'] = <?=var_export($info_app['path'], 1)?> <br />
    数组格式的url参数：$info_app['para'] = <?=var_export($info_app['para'],1)?> <br />
    字符串格式的路径信息：$info_app['route'] = <?=$info_app['route']?> <br />
    $_GET = <?=var_export($_GET, 1)?>
</div>

<h5 class="mt-4">route.php 代码：</h5>
<div style="width:96%;height:auto;overflow-x:hidden;overflow-y:auto;border:1px gray dashed;padding:10px 20px;margin:20px auto;">
<pre class="brush:php;">
<!--code2-->
</pre>
</div>

<h5 class="mt-4">lib.php 代码：</h5>
<div style="width:96%;height:auto;overflow-x:hidden;overflow-y:auto;border:1px gray dashed;padding:10px 20px;margin:20px auto;">
<pre class="brush:php;">
<!--code-->
</pre>
</div>