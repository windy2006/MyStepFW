<section class="adm-panel">
<h2>选择应用及语言</h2>
<div class="adm-option-group">
<h3 class="adm-option-label">应用</h3>
<nav class="adm-options" aria-label="选择语言所属应用">
<!--loop:start key="apps"--><a class="adm-option" href="<!--apps_url|html-->" aria-current="<!--apps_current|html-->"><span><!--apps_name|html--></span><small><!--apps_detail|html--></small></a><!--loop:end-->
</nav>
</div>
<div class="adm-option-group">
<h3 class="adm-option-label">语言</h3>
<nav class="adm-options" aria-label="选择语言文件">
<!--loop:start key="types"--><a class="adm-option" href="<!--types_url|html-->" aria-current="<!--types_current|html-->"><span><!--types_name|html--></span></a><!--loop:end-->
</nav>
</div>
</section>
<section class="adm-panel">
<h2><!--app|html--> / <!--type|html--></h2>
<form method="post" action="<!--page_url|html-->">
<input type="hidden" name="_action" value="save">
<input type="hidden" name="app" value="<!--app|html-->">
<input type="hidden" name="type" value="<!--type|html-->">
<input type="hidden" name="version" value="<!--version|html-->">
<label class="adm-field"><span>另存为新的语言标识（可选）</span><input name="new"></label>
<div class="adm-config-rows">
<!--loop:start key="items"--><label class="adm-field adm-config-row"><span><!--items_key|html--></span><textarea name="language[<!--items_key|html-->]" rows="2"><!--items_value|html--></textarea></label><!--loop:end-->
</div>
<button class="adm-button" type="submit">保存</button>
</form>
</section>
