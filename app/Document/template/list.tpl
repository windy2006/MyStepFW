<h1>功能类列表</h1>
<div class="col-12">
    <div class="input-group">
        <div class="input-group-prepend">
            <div class="input-group-text">模块：</div>
        </div>
        <select class="form-select" onchange="location.href=location.href.replace(/#.+$/,'')+'#'+this.value;">
            <!--loop:start key="item"-->
            <option><!--item_name--></option>
            <!--loop:end-->
        </select>
    </div>
</div>

<div class="row">&nbsp;</div>
<!--loop:start key="item"-->
<div class="row mb-4">
    <div class="card p-0 w-100">
        <div class="card-header">
            <b><a name="<!--item_name-->"><!--item_no--> - <!--item_name--></a></b>
            【<a href="<!--item_name-->" target="_blank">使用样例</a>】
            【<a href="<!--item_name-->/detail" target="_blank">方法说明</a>】
        </div>
        <div class="card-body">
            <pre class="card-text"><!--item_doc--></pre>
        </div>
    </div>
</div>
<!--loop:end-->