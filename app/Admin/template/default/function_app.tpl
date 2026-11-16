<section class="adm-panel">
<h2>应用与路由</h2>
<!--loop:start key="apps"--><!--apps_content--><!--loop:end-->
</section>
<section class="adm-panel">
<h2>路由扩展</h2>
<p class="adm-help" id="route-extension-help">用于增加新的路由规则（格式参考<a href="<!--routing_url|html-->" target="_blank" rel="noopener noreferrer">框架路由规则说明</a>）<br>
路由源相对于网站根，例如 <code>plugin/manager/route.php</code> 或自定义路由文件。</p>
<form method="post" action="<!--page_url|html-->">
<input type="hidden" name="_action" value="mapping">
<input type="hidden" name="version" value="<!--version|html-->">
<label class="adm-field"><span>路由配置</span>
<textarea data-admin-code="php" name="mapping" rows="8" aria-describedby="route-extension-help route-extension-format" spellcheck="false"><!--route_mapping|html--></textarea>
<small id="route-extension-format">PHP数组：扩展标识 =&gt; 路由文件路径。例如 <code>'manager' =&gt; 'plugin/manager/route.php'</code>。留空清除显式扩展，不影响启用插件的自动路由。</small>
</label>
<button class="adm-button" type="submit">保存</button>
</form>
</section>
<section class="adm-panel">
<h2>应用包（app_名称.app）</h2>
<form method="post" action="<!--page_url|html-->" enctype="multipart/form-data" data-confirm="确认部署此受信包中的PHP代码？">
<input type="hidden" name="_action" value="upload">
<label class="adm-field"><span>选择文件</span><input type="file" name="package" need="" required></label>
<button class="adm-button" type="submit">上传并解包</button>
</form>
</section>
