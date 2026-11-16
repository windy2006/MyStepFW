<section class="adm-panel" data-plugin-help data-plugin-help-url="<!--plugin_help_url|html-->" data-plugin-help-map="<!--plugin_help_map|html-->">
<h2><!--name|html--></h2>
<p><!--intro|html--></p>
<h3>功能说明</h3>
<div class="adm-plugin-description"><!--description--></div>
<p><strong class="plugin-check-status plugin-check-<!--check_state|html-->" role="status">条件检查：<!--check_label|html--></strong></p>
<pre class="plugin-check-details"><!--check_result|html--></pre>
<!--if:start key="can_install"--><form method="post" action="<!--page_url|html-->">
<input type="hidden" name="_action" value="save">
<input type="hidden" name="idx" value="<!--idx|html-->">
<input type="hidden" name="version" value="<!--version|html-->">
<input type="hidden" name="registry_version" value="<!--registry_version|html-->">
<!--config_fields--><fieldset><legend>生效应用</legend><!--loop:start key="apps"--><label><input type="checkbox" name="apps[]" value="<!--apps_id|html-->" <!--apps_selected-->><!--apps_name|html--></label> <!--loop:end--></fieldset>
<div class="adm-actions adm-form-actions plugin-settings-actions">
<button class="adm-button" type="submit">保存插件设置</button>
<a class="adm-button" href="<!--plugin_help_url|html-->" target="_blank" rel="noopener noreferrer">插件说明</a>
</div>
</form><!--else--><p>条件检查未通过，不能安装。</p><!--if:end-->
</section>
