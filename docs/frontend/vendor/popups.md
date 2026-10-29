# 弹出窗口

先加载 `jquery.jmpopups` 及同名 CSS。`showPop(name,title,type,content,width,height,no_close)` 创建窗口；name 是由字母、数字、下划线或连字符组成的唯一名称。

```javascript
showPop('help','帮助','text','<p>这里是可信 HTML。</p>',500,300);
// type: text（HTML）、id（页面元素 ID）、url（iframe 地址）、img（图片地址）
showPop('preview','图片','img',global.root+'app/myStep/asset/samples/lake.jpg',600,400);
$.closePopupLayer('help');
$.closePopupLayer_now('preview'); // 立即关闭
```

自定义生命周期使用 `$.openPopupLayer({name:'help',title:'帮助',content:'内容',success:function(){},beforeClose:function(){},afterClose:function(){}})`；`$.reloadPopupLayer('help',callback)` 重载内容；`no_close:true` 隐藏关闭按钮并阻止 Escape 关闭，但代码仍可关闭。

## 确认与输入

```javascript
confirm('请选择操作',function (index) {
    if(index===0)console.log('确定');
},['确定','取消'],'操作确认');
prompt('请输入名称',function (value) {
    console.log(value);
},'初始名称','名称输入');
```

函数回调为异步调用；也可传入已有全局函数名称，例如 `'saveName'`。confirm 的回调参数为按钮下标，prompt 的回调参数为输入字符串。prompt 默认按钮为确认、取消、重置。末尾 `nl` 参数默认把换行转成 `<br>`；传 `false` 可直接显示调用方提供的可信 HTML。

普通 `confirm('确定删除？')` 仍返回原生 boolean；普通 `prompt('名称','默认值')` 仍返回原生字符串或 null。`alert_org`、`confirm_org`、`prompt_org` 可直接调用原生弹窗。`alert` 使用页面信息框时无需回调。标题和正文的 HTML 只应来自可信页面模板。
