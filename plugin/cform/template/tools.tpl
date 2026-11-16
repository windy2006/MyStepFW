<div>
    <a class="btn btn-light" href="<!--back|html-->">表单列表</a>
    <a class="btn btn-light" href="<!--design|html-->">设计表单</a>
    <a class="btn btn-primary" href="<!--add|html-->">添加记录</a>
</div>
<form method="get" action="<!--action|html-->">
    <input type="hidden" name="mid" value="<!--mid|html-->" />
    <input name="keyword" aria-label="检索内容" placeholder="检索内容" />
    <button>检索</button>
</form>
<form method="post" action="<!--action|html-->">
    <!--csrf-->
    <input type="hidden" name="action" value="export" />
    <button class="btn">导出CSV</button>
</form>
<details>
    <summary>导入CSV</summary>
    <p>UTF-8，首行为字段英文名，最多5000条；新建记录，不覆盖原记录。附件不通过CSV导入。</p>
    <form method="post" enctype="multipart/form-data" action="<!--action|html-->">
        <!--csrf-->
        <input type="hidden" name="action" value="import" />
        <input type="file" name="csv" accept=".csv" required />
        <button class="btn">导入</button>
    </form>
</details>
<form
    method="post"
    action="<!--action|html-->"
    onsubmit="return confirm('停用并移入保留区？提交记录仍保留。')"
>
    <!--csrf-->
    <input type="hidden" name="action" value="delete_form" />
    <button class="btn btn-outline-danger">删除表单（保留数据）</button>
</form>
