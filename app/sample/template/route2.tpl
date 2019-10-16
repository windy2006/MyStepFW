<h2>MyStep Framework 自定义路由示例 2 - 通过mystep::getModule接口调用</h2>

<h5 class="mt-4">文档说明：</h5>
<pre style="width:96%;max-height:400px;overflow-x:hidden;overflow-y:auto;border:1px gray dashed;padding:10px 20px;margin:20px auto;">
mystep::getModule($m) 函数处理机制如下：
   - 传入参数 $m - 本参数传递路由外的路径信息，如路由为 /manager/[any]，URI 为 /manager/path1/path2，则 $m 为 path1/path2，即[any]部分，但需要注意的是在本方法中，$m 被截取为 path1。此参数可直接在自定义的路由处理脚本内调用，但如需在下级函数中调用，需要先进行global处理。
   - 本方法将通过 myStep::setPara 方法调用当前 app 设置中的模版参数设置（可继承于全局设置，存储于全局变量 $setting_tpl 中）
   - 本方法将按照如下顺序调用处理脚本（发现可用脚本后将立即调用并停止试探）
      - app路径/module/模版样式/$m.php（$m 为输入参数）
      - app路径/module/模版样式/路由名称.php （如路由为 /manager/[any]，路由名称为 manager）
      - app路径/module/$m.php（$m 为输入参数）
      - app路径/module/路由名称.php （如路由为 /manager/[any]，路由名称为 manager）
      - app路径/module/模版样式/index.php（模版样式为设置中对应的内容）
      - app路径/module/index.php
如下变量可直接调用：info_app、s、mystep、db、cache、route、tpl_setting、tpl_cache
</pre>

<?php
global $info_app;
?>
<h5 class="mt-4">路径引用测试：</h5>
<ul style="width:96%;height:auto;overflow-x:hidden;overflow-y:auto;border:1px gray dashed;padding:10px 20px;margin:20px auto;">
    <li>自定义路由：<a href="<!--root-->mySample/"><!--root-->mySample/</a> </li>
    <li>带路径：<a href="<!--root-->mySample/dir1/dir2/"><!--root-->mySample/dir1/dir2/</a> </li>
    <li>带查询字串：<a href="<!--root-->mySample/?para1=aaa&para2=bbb"><!--root-->mySample/?para1=aaa&amp;para2=bbb</a> </li>
    <li>带路径和查询字串：<a href="<!--root-->mySample/dir1/dir2/?para1=aaa&para2=bbb"><!--root-->mySample/dir1/dir2/?para1=aaa&amp;para2=bbb</a> </li>
</ul>

<h5 class="mt-4">URL信息解析：</h5>
<ul style="width:96%;height:auto;overflow-x:hidden;overflow-y:auto;border:1px gray dashed;padding:10px 20px;margin:20px auto;">
    <li>数组格式的路径信息：$info_app['path'] = <?=var_export($info_app['path'], 1)?> </li>
    <li>数组格式的url参数：$info_app['para'] = <?=var_export($info_app['para'],1)?> </li>
    <li>字符串格式的路径信息：$info_app['route'] = <?=$info_app['route']?> </li>
    <li>$_GET = <?=var_export($_GET, 1)?> </li>
    <li>$m = <!--m--> </li>
</ul>

<h5 class="mt-4">route.php 代码：</h5>
<div style="width:96%;height:auto;overflow-x:hidden;overflow-y:auto;border:1px gray dashed;padding:10px 20px;margin:20px auto;">
<pre class="brush:php;">
<!--code2-->
</pre>
</div>

<h5 class="mt-4">module/index.php 代码：</h5>
<div style="width:96%;height:auto;overflow-x:hidden;overflow-y:auto;border:1px gray dashed;padding:10px 20px;margin:20px auto;">
<pre class="brush:php;">
<!--code-->
</pre>
</div>