# 自动补全

为输入框提供本地或远程建议，返回补全实例。建议文本默认安全转义；自定义 `fnFormatResult` 的返回值按 HTML 显示，应由调用方保证可信。

```javascript
$.vendor('jquery.autocomplete', {add_css:true, callback:function () {
    const ac = $('#city').autocomplete({
        lookup: ['北京', '上海', '广州'], minChars:1,
        onSelect:function (text, data, input) { console.log(text, data, input.val()); }
    });
    // 改用服务器查询：清除 lookup 后设置 serviceUrl。
    ac.setOptions({lookup:null, serviceUrl:global.root+'api/myStep/autoComplete/json',
        params:{mode:'province'}, deferRequestBy:150});
}});
```

| 参数 | 含义 |
| --- | --- |
| `lookup` | 字符串数组，或 `{suggestions:[], data:[]}`；`null` 使用远程查询 |
| `serviceUrl`、`params` | 查询地址和附加参数；以 POST 发送 `query` |
| `minChars`、`deferRequestBy` | 最少字符数、查询延迟毫秒 |
| `maxHeight`、`width`、`zIndex` | 建议框尺寸和层级 |
| `delimiter` | 多项文本分隔符，可为正则表达式 |
| `noCache` | 为 true 时不缓存远程结果 |
| `onSelect(text,data,input)` | 选中回调，input 是对应输入框的 jQuery 对象 |
| `fnFormatResult(text,data,query)` | 自定义结果 HTML |
| `autoSubmit` | 选中后提交所属表单 |

服务器响应为合法 JSON，例如 `{"query":"北","suggestions":["北京"],"data":[11]}`。不要返回 JavaScript 表达式。中文输入在组合结束后查询；清空输入、切换设置或销毁实例会使旧请求失效。

```javascript
$('#city').autocomplete('disable');
$('#city').autocomplete('enable');
$('#city').autocomplete('clearCache');
$('#city').autocomplete('destroy');
```
