<section class="adm-panel">
<h2>类别名</h2>
<p class="adm-help">类别名是类名的简写，原类名仍可使用，方法、参数和返回值不变。填写后保存，从下一次请求起生效；留空可移除该类的别名配置。别名不能与其他别名或已有类重名。</p>
<div class="adm-table-wrap">
    <table class="adm-table">
        <thead><tr><th>设置示例</th><th>原写法</th><th>别名写法</th></tr></thead>
        <tbody><tr><td><code>myReq → r</code></td><td><code>myReq::get('id')</code></td><td><code>r::get('id')</code></td></tr></tbody>
    </table>
</div>
<form method="post" action="<!--page_url|html-->">
<input type="hidden" name="_action" value="save">
<!--if:start key="scoped"--><input type="hidden" name="directory" value="<!--directory|html-->"><!--if:end-->
<input type="hidden" name="version" value="<!--version|html-->">
<div class="adm-config-rows">
<!--loop:start key="classes"-->
<div class="adm-field adm-config-row">
    <span><label for="class-alias-<!--classes_name|html-->"><!--classes_name|html--></label></span>
    <div class="adm-alias-control">
        <input id="class-alias-<!--classes_name|html-->" name="aliases[<!--classes_name|html-->]" value="<!--classes_alias|html-->">
        <a class="adm-button" href="<!--classes_url|html-->">查看详情</a>
    </div>
</div>
<!--loop:end-->
</div>
<button class="adm-button" type="submit">保存</button>
</form>
</section>
