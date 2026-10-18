<section class="adm-panel">
<h2><!--name|html--></h2>
<p><!--intro|html--></p><p>条件检查：<!--check_label|html--></p><pre><!--check_result|html--></pre>
<!--if:start key="can_install"--><form method="post" action="<!--page_url|html-->" data-confirm="确认安装此插件？">
<input type="hidden" name="_action" value="install">
<input type="hidden" name="idx" value="<!--idx|html-->">
<input type="hidden" name="version" value="<!--version|html-->">
<input type="hidden" name="registry_version" value="<!--registry_version|html-->">
<!--config_fields--><fieldset><legend>生效应用</legend><!--loop:start key="apps"--><label><input type="checkbox" name="apps[]" value="<!--apps_id|html-->" <!--apps_selected-->><!--apps_name|html--></label> <!--loop:end--></fieldset>
<button class="adm-button" type="submit">安装插件</button>
</form><!--else--><p>条件检查未通过，不能安装。</p><!--if:end-->
</section>
