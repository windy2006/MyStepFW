<form method="post" action="<!--url|html-->" class="ms-api-form" data-ms-api-form="true">
<input type="hidden" name="_action" value="save">
<div class="ms-api-fields"><!--fields--></div>
<!--if:start key="registered"--><!--else--><p>当前接口未注册，仅可清理历史设置。</p><!--if:end-->
<div class="ms-api-actions"><!--if:start key="registered"--><button type="submit" class="ms-api-save">保存设置</button><!--if:end--><button type="button" data-api-reset>恢复默认</button><span class="ms-api-message" role="status" aria-live="polite"></span></div>
</form>
