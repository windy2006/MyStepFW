<h1>功能类列表</h1>
<div class="row4">
	<b>模块：</b>
	<select onchange="location.href='Document/#'+this.value">
		<!--loop:start key="item"-->
		<option><!--item_name--></option>
		<!--loop:end-->
	</select>
</div>
<div class="row">&nbsp;</div>
<!--loop:start key="item"-->
<div class="row mb-4">
	<div class="card w-100">
		<div class="card-header">
			<b><a name="<!--item_name-->"><!--item_no--> - <!--item_name--></a></b>
			【<a href="Document/<!--item_name-->" target="_blank">使用样例</a>】
			【<a href="Document/<!--item_name-->/detail" target="_blank">方法说明</a>】
		</div>
		<div class="card-body">
			<pre class="card-text"><!--item_doc--></pre>
		</div>
	</div>
</div>
<!--loop:end-->