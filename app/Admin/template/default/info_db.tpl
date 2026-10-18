<section class="adm-panel">
<h2>数据库状态</h2>
<div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>项目</th><th>值</th></tr></thead><tbody>
<!--loop:start key="info"--><tr><td><!--info_name|html--></td><td><!--info_value|html--></td></tr><!--loop:end-->
</tbody></table></div>
</section>
<section class="adm-panel">
<h2>数据表</h2>
<nav class="adm-options" aria-label="选择数据表">
<!--loop:start key="tables"--><a class="adm-option" href="<!--tables_url|html-->" aria-current="<!--tables_current|html-->"><span><!--tables_name|html--></span></a><!--loop:end-->
</nav>
</section>
<!--if:start key="selected"--><section class="adm-panel">
<h2><!--table|html--></h2>
<div class="adm-table-wrap"><table class="adm-table"><thead><tr><!--loop:start key="headings"--><th><!--headings_name|html--></th><!--loop:end--></tr></thead><tbody>
<!--loop:start key="records"--><tr><!--records_cells--></tr><!--loop:end-->
</tbody></table></div><a class="adm-link" href="<!--previous_url|html-->">上一页</a><a class="adm-link" href="<!--next_url|html-->">下一页</a>
</section>
<!--if:end-->
