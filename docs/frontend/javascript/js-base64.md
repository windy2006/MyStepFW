# 编码转换

说明base64的显式编码、解码与非法输入处理。

## 编码转换

| 接口 | 参数与功能 | 返回约定 |
| --- | --- | --- |
| `base64(code, mode)` | true 解码，false 编码；省略时尝试按合法 UTF-8 Base64 解码，失败则编码 | string；显式解码非法数据会抛异常，不是加密 |

```javascript
const encoded = base64('中文', false); // 5Lit5paH
console.log(base64(encoded, true)); // 中文
```

显式选择编码或解码；非法解码输入会抛异常。Base64只是编码，不提供加密或身份认证。

[文档总览](../../README.md)
