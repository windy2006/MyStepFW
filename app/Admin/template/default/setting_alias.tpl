<section class="adm-panel">
<h2>类别名</h2>
<form method="post" action="<!--page_url|html-->">
<input type="hidden" name="_action" value="save">
<!--if:start key="scoped"--><input type="hidden" name="directory" value="<!--directory|html-->"><!--if:end-->
<input type="hidden" name="version" value="<!--version|html-->">
<div class="adm-config-rows">
<!--loop:start key="classes"--><label class="adm-field adm-config-row"><span><!--classes_name|html--></span><input name="aliases[<!--classes_name|html-->]" value="<!--classes_alias|html-->"></label><!--loop:end-->
</div>
<button class="adm-button" type="submit">保存</button>
</form>
</section>
<section class="adm-panel">
<h2>方法说明与方法别名</h2>
<nav class="adm-options" aria-label="选择功能类查看方法说明与别名">
<!--loop:start key="classes"--><a class="adm-option" href="<!--classes_url|html-->"><span><!--classes_name|html--></span></a><!--loop:end-->
</nav>
</section>
