# 简繁转换

说明chs2cht.js的独立文字转换、页面转换及加载限制。

### 2. 简繁转换 chs2cht.js

chs2cht.js 是逐字对照转换脚本，不是多语言包系统。加载时调用 document.write 输出 id 为 TransLink 的切换链接，并读取当前主机对应的语言 Cookie。

| 变量 | 默认值／用途 |
| --- | --- |
| `Default_isFT` | 0，简体默认；1 为繁体 |
| `TransIt_Delay` | 500，延迟转换毫秒数 |
| `TransLink_Obj` | 切换链接 DOM 元素 |
| `JF_cn` | 按主机名构造的 Cookie 名 |
| `BodyIsFt` | 当前转换方向／状态；受 Cookie 字符串影响 |

| 函数 | 参数、返回值和作用 |
| --- | --- |
| `TransText(txt, toFT, chgTxt)` | toFT 为真转繁体、假转简体，省略沿用 BodyIsFt；chgTxt 为真时替换提示文字中的简／繁；返回 string |
| `Traditionalized(text)` | 按内置对照表逐字转繁体，返回 string |
| `Simplized(text)` | 按内置对照表逐字转简体，返回 string |
| `TransBody(fobj)` | 有 DOM 对象时递归处理其子节点；不传时切换页面状态并处理 body，修改文本／部分属性，返回 undefined |
| `JTPYStr()`、`FTPYStr()` | 返回内置简体／繁体对照字符串，属于字典辅助函数 |
| `getCookieVal(offset)` | 从 document.cookie 指定偏移读取并 unescape，返回 string |
| `getCookie(name)` | 读取 Cookie，返回 string 或 null |
| `setCookie(name, value, expires=365, path=null, domain=null, secure=false)` | 后四项通过位置参数读取；expires 为天数，写入 Cookie，返回 undefined |
| `deleteCookie(name)` | 使同名 Cookie 过期，返回 undefined；该接口不接收路径／域参数 |

下面的代码只能在该脚本按原方式加载之后调用：

```javascript
console.log(Traditionalized('网页开发')); // 網頁開發
console.log(Simplized('網頁開發'));       // 网页开发
console.log(TransText('网页', true, false));
```

脚本加载本身具有页面写入副作用，不能当作只有纯函数的工具包：

- 不在 DOM 就绪后用动态 getScript／异步脚本随意加载，document.write 可能影响现有文档；沿用已有页面解析期加载位置。

- 包含 with 和字符串定时器，不放入严格模式或 ES Module 环境。

- 逐字映射不解决一简多繁、词义或地区用语差异，不能代替框架 language 语言包。

- 会遍历页面文本与部分属性；不是只处理某个文本框的输入转换器。独立文字转换优先调用 Traditionalized／Simplized。

Cookie 函数采用 escape／unescape，和 jquery.addon 的 URI 编码策略不同，不把两个接口的编码格式当成一致。应用自己的业务 Cookie 名避免覆盖其已有全局函数名。

### 明确调用示例

```javascript
const element = $id('article');
if (element) {
    // 首先加载本地chs2cht.js；元素应是当前业务明确允许转换的区域。
    TransBody(element);
}
```

[文档总览](../../README.md)
