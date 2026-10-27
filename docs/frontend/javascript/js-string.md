# 字符扩展

本页代码在浏览器中运行，先加载本地jQuery及global.js。

## 方法与返回值

| 调用 | 参数 | 返回值 |
| --- | --- | --- |
| `text.blen()` | 字符串实例，无参数 | number：按UTF-16码元估计双字节显示长度 |
| `text.printf(...values)` | 按位置提供替换值 | string：替换`%1`、`%2`等标记，缺少值的标记保留 |
| `text.trim()` | 字符串实例，无参数 | string：去除首尾空白 |

这些方法调用于字符串实例，不是`String.blen()`等静态方法。扩展以不可枚举属性登记；trim在浏览器没有原生实现时补充。

## 明确示例

```javascript
console.assert('中文A'.blen() === 5);
console.assert('😀'.blen() === 4);
console.assert('你好，%1；编号%2'.printf('小明', 12) === '你好，小明；编号12');
console.assert('%1 %2'.printf('$&') === '$& %2');
console.assert('  标题  '.trim() === '标题');
```

blen不是UTF-8字节数：汉字通常计2，emoji可能按两个UTF-16码元计4。printf单次替换，参数内的`$&`或`%2`不再次解释。输出到HTML时仍用textContent或jQuery.text。

[前端目录](../README.md) · [函数目录](global-reference.md)
