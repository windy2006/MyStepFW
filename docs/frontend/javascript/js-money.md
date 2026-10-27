# 金额格式

本页代码在浏览器中运行，先加载本地jQuery及global.js。

## 方法参数

`number.formatMoney(places=2, symbol='$', thousand=',', decimal='.')`返回展示字符串。

| 参数 | 含义 |
| --- | --- |
| places | 小数位数，取绝对值、向下取整并限制0至20 |
| symbol | 货币符号，可以为空 |
| thousand | 千位分隔符 |
| decimal | 小数分隔符 |

## 明确示例

```javascript
console.assert((1234.5).formatMoney(2, '¥') === '¥1,234.50');
console.assert((1234.5).formatMoney(2, '', '', '.') === '1234.50');
console.assert((NaN).formatMoney(2, '¥') === '¥0.00');
```

非有限数按0展示；返回值带符号及分隔符，不作为后续计算的数值。需要精确金额计算时，业务使用约定的整数最小货币单位或专门的十进制计算方式。

[前端目录](../README.md) · [函数目录](global-reference.md)
