<section class="adm-panel">
<h2>应用与路由</h2>
<!--loop:start key="apps"--><!--apps_content--><!--loop:end-->
</section>
<section class="adm-panel">
<h2>显式插件路由</h2>
<p>路由源相对于网站根，例如 plugin/manager/route.php。</p>
<form method="post" action="<!--page_url|html-->">
<input type="hidden" name="_action" value="mapping">
<input type="hidden" name="version" value="<!--version|html-->">
<!--loop:start key="routes"--><label class="adm-field"><span><!--routes_id|html--></span><input name="mapping[<!--routes_id|html-->]" value="<!--routes_value|html-->"></label><!--loop:end-->
<button class="adm-button" type="submit">保存</button>
</form>
</section>
<section class="adm-panel">
<h2>应用包（app_名称.app）</h2>
<form method="post" action="<!--page_url|html-->" enctype="multipart/form-data" data-confirm="确认部署此受信包中的PHP代码？">
<input type="hidden" name="_action" value="upload">
<label class="adm-field"><span>选择文件</span><input type="file" name="package" need="" required></label>
<button class="adm-button" type="submit">上传并解包</button>
</form>
</section>
