<section class="adm-panel">
<h2><!--class|html--> 方法别名</h2>
<form method="post" action="<!--page_url|html-->">
<input type="hidden" name="_action" value="save">
<input type="hidden" name="class" value="<!--class|html-->">
<input type="hidden" name="version" value="<!--version|html-->">
<div class="adm-config-rows">
<!--loop:start key="aliases"--><label class="adm-field adm-config-row"><span><!--aliases_name|html--></span><input name="aliases[<!--aliases_name|html-->]" value="<!--aliases_alias|html-->"></label><!--loop:end-->
</div>
<button class="adm-button" type="submit">保存</button>
</form>
</section>
<section class="adm-panel">
<h2>方法说明</h2>
<table class="adm-table"><thead><tr><th>方法</th><th>说明</th></tr></thead><tbody><!--loop:start key="methods"--><tr><td><!--methods_name|html--></td><td><pre><!--methods_doc|html--></pre></td></tr><!--loop:end--></tbody></table>
</section>
