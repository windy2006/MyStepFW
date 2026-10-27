# 日期格式

本页代码在浏览器中运行，先加载本地jQuery及global.js。

## 方法参数

`date.format(format='yyyy-MM-dd hh:mm:ss')`返回按浏览器本地时间生成的字符串。日期无效时返回空字符串。

| 标记 | 含义 |
| --- | --- |
| y | 年 |
| M、d | 月、日 |
| h、m、s | 时、分、秒 |
| q | 季度 |
| S | 毫秒 |

重复标记控制补位。格式文本没有引号转义机制，其中的标记字母也会参与处理。

## 明确示例

```javascript
const createdAt = new Date(2026, 9, 18, 9, 5, 6);
console.assert(createdAt.format() === '2026-10-18 09:05:06');
console.assert(createdAt.format('yyyy-MM-dd') === '2026-10-18');
console.assert(new Date('invalid').format() === '');
```

Date构造器的月份从0开始，本例9表示10月。服务端时间戳、时区和展示格式由业务明确约定，不把格式化字符串当作可排序的数值时间戳。

[前端目录](../README.md) · [函数目录](global-reference.md)
