<section class="adm-panel">
<div class="adm-panel-heading"><h2>缓存管理</h2>
<form method="post" action="<!--page_url|html-->" data-confirm="确认清理全部七类缓存（含会话）？清理后可能需要重新登录。">
<input type="hidden" name="_action" value="clear"><input type="hidden" name="areas[]" value="all">
<button class="adm-button" type="submit">全部清理</button>
</form></div>
<p class="adm-help">全部清理包含下列七类缓存及当前缓存驱动中的数据缓存；清理会话可能需要重新登录。</p>
<div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>类别</th><th>容量</th><th>操作</th></tr></thead><tbody>
<!--loop:start key="caches"--><tr><td><!--caches_name|html--></td><td><!--caches_size|html--></td><td><form method="post" action="<!--page_url|html-->" data-confirm="确认清理<!--caches_name|html-->？">
<input type="hidden" name="_action" value="clear">
<input type="hidden" name="areas[]" value="<!--caches_id|html-->">
<button class="adm-button" type="submit">清理</button>
</form></td></tr><!--loop:end-->
</tbody></table></div>
</section>
