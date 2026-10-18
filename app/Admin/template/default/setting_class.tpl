<section class="adm-panel">
<h2>类加载设置</h2>
<p class="adm-help">所在目录相对于网站根，例如 lib/；载入类型用逗号分隔，例如 .php,.class.php。特别指定填写 PHP 数组，文件路径相对于所在目录，例如 array('interface_plugin' =&gt; '../plugin/interface_plugin.class.php')。</p>
<a class="adm-link" href="<!--alias_url|html-->">类别名</a><form method="post" action="<!--page_url|html-->">
<input type="hidden" name="_action" value="save">
<input type="hidden" name="version" value="<!--version|html-->">
<div class="adm-table-wrap"><table class="adm-table adm-rows" id="class-rows"><thead><tr><th>所在目录</th><th>载入类型</th><th>特别指定</th><th>操作</th></tr></thead><tbody>
<!--loop:start key="rules"--><tr>
<td><input name="rows[<!--rules_index|html-->][path]" value="<!--rules_path|html-->"></td>
<td><input name="rows[<!--rules_index|html-->][ext]" data-default-value=".php,.class.php" value="<!--rules_ext|html-->"></td>
<td><textarea name="rows[<!--rules_index|html-->][idx]" rows="4" data-default-value="array ()"><!--rules_idx|html--></textarea></td>
<td><a class="adm-link" href="<!--rules_view_url|html-->" data-row-existing>查看</a><button type="button" data-remove-row <!--rules_remove_disabled-->>移除</button></td></tr><!--loop:end-->
</tbody></table></div>
<div class="adm-actions">
<button class="adm-button" type="button" data-add-row="class-rows">增加一行</button>
<button class="adm-button" type="submit">保存</button>
<button class="adm-button" type="reset">复位</button>
</div>
</form>
</section>
