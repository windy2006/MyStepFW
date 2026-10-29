# 树形选择

基于原生 select 展示树形下拉列表；提交仍读取原 select，不另建隐藏值字段。

```html
<select id="category" name="category"><optgroup label="文章"><option value="news">新闻</option><option value="help" selected>帮助</option></optgroup></select>
```
```javascript
$('#category').treeSelection({multiple:false});
$('#category').on('change',function () {console.log($(this).val());});
$('#category').val('news').trigger('change');
$('#category').treeSelection('refresh');
$('#category').treeSelection('destroy');
```

multiple:true 开启多选，max 限制数量；multiple 为数字时同时表示开启多选和数量上限。多选时有名字的字段自动使用 name[]。禁用 select、option 或 optgroup 后同步禁止选择；表单 reset 恢复默认选中。Enter／空格展开，Escape 关闭。

```javascript
$('#category').treeSelection({multiple:2,data:[
    {name:'地区',items:[{name:'北京',value:'bj'},{name:'上海',value:'sh',selected:true}]},
    {name:'其他',value:'other',disabled:true}
],style:'max-height:300px;overflow:auto',class:'my-tree'});
```

data 为数组，每项可为文本或 `{name,value,selected,disabled,items}`；items 表示下级，文字按纯文本插入。未提供 data 时读取原 select 和 optgroup。refresh 同步现有选项的选中／禁用状态；新增或删除选项后再次初始化。destroy 清理生成界面并恢复字段名和样式；使用 data 初始化的实例还会恢复原选项结构。
