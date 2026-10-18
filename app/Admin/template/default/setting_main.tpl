<section class="adm-panel">
<h2>选择配置</h2>
<nav class="adm-options" aria-label="选择配置">
<!--loop:start key="apps"--><a class="adm-option" href="<!--apps_url|html-->" aria-current="<!--apps_current|html-->"><span><!--apps_name|html--></span><small><!--apps_detail|html--></small></a><!--loop:end-->
</nav>
</section>
<section class="adm-panel">
<h2>参数设置</h2>
<form method="post" action="<!--page_url|html-->">
<input type="hidden" name="_action" value="save">
<input type="hidden" name="target" value="<!--target|html-->">
<input type="hidden" name="version" value="<!--version|html-->">
<!--config_fields-->
<button class="adm-button" type="submit">保存</button>
</form>
</section>
