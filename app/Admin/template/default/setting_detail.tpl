<section class="adm-panel">
<h2><!--class|html--> 方法说明及别名</h2>
<p class="adm-help">方法别名是当前类中公开普通方法的简写，原方法名仍可使用，参数和返回值不变；魔术方法不能设置别名。后台保存的配置通过 <code>myStep::getInstance()</code> 取得实例时自动加载。</p>
<p class="adm-help">以 <code>myFile</code> 为例，在 <code>rootPath</code> 后填写 <code>siteRoot</code> 并保存，再取得实例：<code>$file = myStep::getInstance('myFile');</code></p>
<div class="adm-table-wrap">
    <table class="adm-table">
        <thead><tr><th>设置示例</th><th>原写法</th><th>别名写法</th></tr></thead>
        <tbody><tr><td><code>rootPath → siteRoot</code></td><td><code>$file-&gt;rootPath()</code></td><td><code>$file-&gt;siteRoot()</code></td></tr></tbody>
    </table>
</div>
<p class="adm-help">直接使用 <code>new</code> 或静态方式调用时，需先通过该类的 <code>regAlias()</code> 加载方法别名；实例方法仍使用 <code>-&gt;</code> 调用。修改已被程序使用的别名时，请同步修改调用代码。</p>
<form method="post" action="<!--page_url|html-->">
<input type="hidden" name="_action" value="save">
<input type="hidden" name="class" value="<!--class|html-->">
<input type="hidden" name="version" value="<!--version|html-->">
<div class="adm-config-rows">
<!--loop:start key="methods"-->
<article class="adm-config-row adm-method-row">
    <div class="adm-method-heading">
        <h3><code><!--methods_name|html--></code></h3>
        <label class="adm-field"><span>调用别名</span><input name="aliases[<!--methods_name|html-->]" value="<!--methods_alias|html-->" <!--methods_disabled-->></label>
    </div>
    <pre><!--methods_doc|html--></pre>
</article>
<!--loop:end-->
</div>
<button class="adm-button" type="submit">保存</button>
</form>
</section>
