<section class="adm-panel">
<h2>插件清单</h2>
<div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>名称</th><th>标识</th><th>版本</th><th>状态</th><th>说明</th><th>操作</th></tr></thead><tbody>
<!--loop:start key="plugins"--><tr><td><!--plugins_name|html--></td><td><!--plugins_idx|html--></td><td><!--plugins_version|html--></td><td><!--plugins_state|html--></td><td><!--plugins_intro|html--></td><td><!--plugins_actions--></td></tr><!--loop:end-->
</tbody></table></div>
</section>
<!--if:start key="has_orders"--><section class="adm-panel">
<h2>运行顺序</h2>
<form method="post" action="<!--page_url|html-->">
<input type="hidden" name="_action" value="order">
<p class="adm-help">数值越大越先运行（0～99）。停用插件保留顺序，重新启用后按此顺序执行。</p>
<div class="adm-table-wrap"><table class="adm-table adm-plugin-orders"><thead><tr><th>插件</th><th>状态</th><th>运行顺序</th></tr></thead><tbody>
<!--loop:start key="orders"--><tr><td><strong><!--orders_name|html--></strong><small><!--orders_idx|html--></small></td><td><!--orders_state|html--></td><td><input type="number" name="order[<!--orders_idx|html-->]" aria-label="<!--orders_name|html--> 的运行顺序" min="0" max="99" need="digital" required value="<!--orders_order|html-->"></td></tr><!--loop:end-->
</tbody></table></div>
<div class="adm-actions adm-form-actions"><button class="adm-button" type="submit">保存运行顺序</button></div>
</form>
</section>
<!--if:end--><section class="adm-panel">
<h2>插件包（plugin_名称.plugin）</h2>
<form method="post" action="<!--page_url|html-->" enctype="multipart/form-data" data-confirm="确认部署此受信包中的PHP代码？">
<input type="hidden" name="_action" value="upload">
<label class="adm-field"><span>选择文件</span><input type="file" name="package" need="" required></label>
<button class="adm-button" type="submit">上传并解包</button>
</form>
</section>
