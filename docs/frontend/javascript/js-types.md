# 字符串、日期、数组和金额扩展

修订日期：2026-10-23。浏览器脚本用法。

## 本篇目标与调用场景

本篇所有JavaScript示例在浏览器运行。先加载所需本地脚本，不把PHP变量或磁盘路径直接当成浏览器变量。

## 主要变量

| 变量 | 用途与约束 |
| --- | --- |
| `root_web/global.root` | 网站Web根前缀，用于公开资源 |
| `setting/language` | 异步装入的公开设置和语言，须等待就绪 |
| `document/window` | 当前浏览器页面上下文，不是PHP的全局变量 |

## 2. 字符串、日期、数组和金额扩展

这些属性在原型上以不可枚举方式定义，调用于值／实例，不是 `String.blen()` 等静态函数。

| 方法 | 功能及参数 | 返回值／边界 |
| --- | --- | --- |
| `text.blen()` | 传统双字节长度：U+0100 以上每个 UTF-16 码元加计 1 | number；汉字一般 2，常见 emoji 4，不是 UTF-8 字节数 |
| `text.printf(...values)` | 替换 `%1`、`%2` 等占位符；缺少参数的占位符保留 | string；单次替换，不把参数里的 `$&`、`%2` 再解释 |
| `text.trim()` | 仅在缺少原生 trim 时补充，去除首尾空白 | string；已有原生实现不覆盖 |
| `date.format(format='yyyy-MM-dd hh:mm:ss')` | 按本地时间格式化；支持 y、M、d、h、m、s、q、S 的重复标记 | string；无效日期返回空串；q 为季度，S 为毫秒 |
| `array.append(items)` | items 是数组则追加其内容，否则追加单值 | 返回原数组并修改它；允许把自身追加一次 |
| `number.formatMoney(places=2, symbol='$', thousand=',', decimal='.')` | 金额、小数位、货币符号、千分符、小数分隔符 | string；小数位取绝对值后向下取整，限制 0～20；非有限金额按 0 |

```js
console.log('中文A'.blen());                         // 5
console.log('你好，%1！订单 %2'.printf('张三', 12));  // 你好，张三！订单 12
console.log(new Date(2026, 9, 18, 9, 5, 6).format()); // 2026-10-18 09:05:06
console.log(new Date(2026, 9, 18).format('yyyy年M月d日 q季度'));
const items = [1];
items.append([2, 3]).append(4);
console.log(items);                                // [1, 2, 3, 4]
console.log((1234.5).formatMoney(2, '¥'));           // ¥1,234.50
```

日期没有引号转义格式片段的机制；自定义格式中的 y、M 等同样会被当作标记。不要把 formatMoney 的显示结果当作金融计算精度保证。


## 明确调用示例

```javascript
console.assert('中文'.blen() === 4);
console.log(new Date(2026, 9, 23).format('yyyy-MM-dd'));
console.log((1234.5).formatMoney(2, '¥', ',', '.'));
```

## 验证与进一步阅读

在浏览器开发工具查看实际值及网络请求；先验证依赖和就绪状态，再检查返回值或Promise。DOM不存在、格式不合法及网络失败均应有反馈。服务端输入、权限和CSRF另外验证，客户端校验通过不等于业务授权。

[返回文档总览](../../README.md)
