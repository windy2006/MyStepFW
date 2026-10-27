# 调试输出

说明浏览器调试输出、对象检查和错误报告，不将秘密数据输出到页面。

### 9. 调试与错误报告

| 函数 | 功能和参数 | 返回值 |
| --- | --- | --- |
| `debug(para, mode)` | 把值写入页面 #debug 文本区；mode===true 时把新内容放在旧内容前，其他情况覆盖 | undefined |
| `c(...values)` | 逐项 console.log | undefined |
| `a(...values)` | 逐项 alert | undefined |
| `checkObj(obj, func_show=alert)` | 遍历可枚举属性，输出 `键 : 值`；可传自己的输出函数 | undefined |
| `reportError(msg, url, line, column, error)` | 调试模式或 setting 尚未定义时报告；优先 global.err_report_func，否则 alert | false，保留浏览器默认错误处理 |

```javascript
debug('开始处理');
debug({step: 2}, true);
c('应用状态', {ready: true});
checkObj({page: 1, size: 20}, function (item) { console.log(item); });

global.err_report_func = function (text, verbose, error) {
    console.error(text, error);
};
// a('本地调试提示');
// reportError('示例错误', location.href, 1, 1, new Error('示例'));
```

这些函数输出到浏览器或页面，不自动写入服务器 error.log。调试值不要包含密码、会话令牌或其他不应展示的数据。

[文档总览](../../README.md)
