# 页面提示

说明info提示区的显示、更新与隐藏，以及受信HTML要求。

## 页面提示

| 接口 | 参数与功能 | 返回约定 |
| --- | --- | --- |
| `info(msg)` | 创建／复用页面提示区；msg 为可信 HTML，null／undefined 隐藏 | undefined |

```javascript
info('<strong>提示：</strong>请检查填写内容。');
// info(null); // 隐藏提示区
```

info使用HTML输出，必须提供可信内容，不能直接拼入未转义的用户输入。

[文档总览](../../README.md)
