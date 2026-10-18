<section class="adm-panel">
<h2>域名 → 应用或路由规则</h2>
<form method="post" action="<!--page_url|html-->">
<input type="hidden" name="_action" value="save">
<input type="hidden" name="version" value="<!--version|html-->">
<div class="adm-table-wrap"><table class="adm-table adm-rows" id="domain-rows"><thead><tr><th>域名</th><th>应用或规则</th><th>操作</th></tr></thead><tbody data-allow-empty="true">
<!--loop:start key="bindings"--><tr><td><input name="rows[<!--bindings_index|html-->][domain]" value="<!--bindings_domain|html-->"></td><td><input name="rows[<!--bindings_index|html-->][rule]" value="<!--bindings_rule|html-->"></td><td><button type="button" data-remove-row>移除</button></td></tr><!--loop:end-->
</tbody></table></div>
<template id="domain-row-template"><tr><td><input name="rows[0][domain]" value=""></td><td><input name="rows[0][rule]" value=""></td><td><button type="button" data-remove-row>移除</button></td></tr></template>
<div class="adm-actions">
<button class="adm-button" type="button" data-add-row="domain-rows" data-row-template="domain-row-template">增加一行</button>
<button class="adm-button" type="submit">保存</button>
</div>
</form>
</section>
