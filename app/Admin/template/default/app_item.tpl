<article class="adm-app-item" data-app="<!--id|html-->">
<div class="adm-app-heading">
<h3><!--name|html--> / <!--id|html--> / v<!--version_label|html--></h3>
<div class="adm-actions">
<!--if:start key="has_settings"--><a class="adm-button" href="<!--settings_url|html-->">设置</a><!--else--><button class="adm-button" type="button" disabled title="此应用未提供配置文件或设置字段说明">设置（未提供）</button><!--if:end-->
<!--if:start key="has_language"--><a class="adm-button" href="<!--language_url|html-->">语言管理</a><!--else--><button class="adm-button" type="button" disabled title="此应用未提供可编辑的语言文件">语言管理（未提供）</button><!--if:end-->
<!--if:start key="has_help"--><a class="adm-button" href="<!--help_url|html-->" target="_blank" rel="noopener noreferrer">说明</a><!--if:end-->
</div>
</div>
<p><!--intro|html--></p>
<details><summary>路由与页面插件</summary><form method="post" action="<!--page_url|html-->">
<input type="hidden" name="_action" value="save">
<input type="hidden" name="name" value="<!--id|html-->">
<input type="hidden" name="version" value="<!--version|html-->">
<input type="hidden" name="plugin_version" value="<!--plugin_version|html-->">
<input type="hidden" name="registry_version" value="<!--registry_version|html-->">
<label class="adm-field"><span>route.php</span><textarea data-admin-code="php" name="route" rows="12"><!--route|html--></textarea></label>
<fieldset><legend>页面插件</legend><p class="adm-help">仅列出已安装并启用的插件。</p><!--if:start key="has_plugins"--><!--loop:start key="plugins"--><label><input type="checkbox" name="plugins[]" value="<!--plugins_id|html-->" <!--plugins_selected-->><!--plugins_name|html-->（<!--plugins_id|html-->）</label> <!--loop:end--><!--else--><p>暂无可选插件。</p><!--if:end--></fieldset>
<button class="adm-button" type="submit">保存</button>
</form></details>
</article>
