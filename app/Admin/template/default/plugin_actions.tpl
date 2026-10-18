<div class="adm-actions adm-plugin-actions">
<!--if:start key="installed"--><a class="adm-button" href="<!--setting_url|html-->">设置</a><form method="post" action="<!--page_url|html-->">
<input type="hidden" name="_action" value="toggle">
<input type="hidden" name="idx" value="<!--idx|html-->">

<button class="adm-button" type="submit"><!--toggle_label|html--></button>
</form><form method="post" action="<!--page_url|html-->">
<input type="hidden" name="_action" value="pack">
<input type="hidden" name="idx" value="<!--idx|html-->">

<button class="adm-button" type="submit">导出</button>
</form><form method="post" action="<!--page_url|html-->" data-confirm="确认卸载插件及其注册资源？">
<input type="hidden" name="_action" value="uninstall">
<input type="hidden" name="idx" value="<!--idx|html-->">

<button class="adm-button" type="submit">卸载</button>
</form><!--else--><a class="adm-button" href="<!--setting_url|html-->">安装</a><form method="post" action="<!--page_url|html-->" data-confirm="确认删除这个未安装的插件目录？">
<input type="hidden" name="_action" value="delete">
<input type="hidden" name="idx" value="<!--idx|html-->">

<button class="adm-button" type="submit">删除文件</button>
</form><!--if:end-->
</div>
