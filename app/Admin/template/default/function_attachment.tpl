<section class="adm-panel">
<h2>附件筛选</h2>
<div class="adm-option-group">
<h3 class="adm-option-label">年份</h3>
<nav class="adm-options" aria-label="选择附件年份">
<!--loop:start key="years"--><a class="adm-option" href="<!--years_url|html-->" aria-current="<!--years_current|html-->"><span><!--years_name|html--></span></a><!--loop:end-->
</nav>
</div>
<div class="adm-option-group">
<h3 class="adm-option-label">月份</h3>
<nav class="adm-options" aria-label="选择附件月份">
<!--loop:start key="months"--><a class="adm-option" href="<!--months_url|html-->" aria-current="<!--months_current|html-->"><span><!--months_name|html--></span></a><!--loop:end-->
</nav>
</div>
</section>
<section class="adm-panel">
<h2>附件列表</h2>
<div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>文件名</th><th>记录</th><th>大小</th><th>时间</th><th>操作</th></tr></thead><tbody>
<!--loop:start key="files"--><tr><td><!--files_name|html--></td><td><!--files_state|html--></td><td><!--files_size|html--></td><td><!--files_date|html--></td><td><a class="adm-link" href="<!--files_download|html-->">下载</a><form method="post" action="<!--page_url|html-->" data-confirm="确认删除该附件？">
<input type="hidden" name="_action" value="delete">
<input type="hidden" name="idx" value="<!--files_idx|html-->"><input type="hidden" name="logged" value="<!--files_logged|html-->">
<button class="adm-button" type="submit">删除</button>
</form></td></tr><!--loop:end-->
</tbody></table></div>
</section>
