<section class="card ms-api"><h2 class="card-header">API 管理</h2><div class="card-body">
<!--if:start key="notice"--><p class="alert alert-success"><!--notice|html--></p><!--if:end-->
<!--if:start key="error"--><p class="alert alert-danger"><!--error|html--></p><!--if:end-->
<p>点击“设置”展开当前接口，保存后即时应用。<a href="<!--settings_url|html-->">框架 API 参数</a></p>
<div class="ms-api-scroll"><table class="ms-api-table"><thead><tr><th>所属项目</th><th>接口</th><th>代码处理器</th><th>调用地址</th><th>状态</th><th>操作</th></tr></thead><tbody>
<!--loop:start key="endpoints"-->
<tr data-api-entry="<!--endpoints_id|html-->"><td><!--endpoints_scope|html--></td><td><!--endpoints_name|html--></td><td><!--endpoints_callback|html--></td><td><code><!--endpoints_url|html--></code></td><td data-api-status><!--endpoints_status|html--></td><td><a href="#<!--endpoints_id|html-->" data-api-toggle aria-expanded="false" aria-controls="<!--endpoints_id|html-->">设置</a></td></tr>
<tr id="<!--endpoints_id|html-->" class="ms-api-detail" hidden><td colspan="6"><!--endpoints_editor--></td></tr>
<!--loop:end-->
</tbody></table></div>
</div></section>
