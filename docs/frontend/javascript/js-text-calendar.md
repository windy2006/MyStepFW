# 简繁转换与日期辅助

修订日期：2026-10-23。浏览器脚本用法。

## 本篇目标与调用场景

本篇所有JavaScript示例在浏览器运行。先加载所需本地脚本，不把PHP变量或磁盘路径直接当成浏览器变量。

## 主要变量

| 变量 | 用途与约束 |
| --- | --- |
| `root_web/global.root` | 网站Web根前缀，用于公开资源 |
| `setting/language` | 异步装入的公开设置和语言，须等待就绪 |
| `document/window` | 当前浏览器页面上下文，不是PHP的全局变量 |

## 2. 简繁转换 chs2cht.js

[chs2cht.js](../../../static/js/chs2cht.js) 是旧式逐字对照转换脚本，不是多语言包系统。加载时调用 document.write 输出 id 为 TransLink 的切换链接，并读取当前主机对应的语言 Cookie。

| 变量 | 默认值／用途 |
| --- | --- |
| `Default_isFT` | 0，简体默认；1 为繁体 |
| `TransIt_Delay` | 500，延迟转换毫秒数 |
| `TransLink_Obj` | 切换链接 DOM 元素 |
| `JF_cn` | 按主机名构造的 Cookie 名 |
| `BodyIsFt` | 当前转换方向／状态；受旧式 Cookie 字符串影响 |

| 函数 | 参数、返回值和作用 |
| --- | --- |
| `TransText(txt, toFT, chgTxt)` | toFT 为真转繁体、假转简体，省略沿用 BodyIsFt；chgTxt 为真时替换提示文字中的简／繁；返回 string |
| `Traditionalized(text)` | 按内置对照表逐字转繁体，返回 string |
| `Simplized(text)` | 按内置对照表逐字转简体，返回 string |
| `TransBody(fobj)` | 有 DOM 对象时递归处理其子节点；不传时切换页面状态并处理 body，修改文本／部分属性，返回 undefined |
| `JTPYStr()`、`FTPYStr()` | 返回内置简体／繁体对照字符串，属于字典辅助函数 |
| `getCookieVal(offset)` | 从 document.cookie 指定偏移读取并 unescape，返回 string |
| `getCookie(name)` | 读取旧式 Cookie，返回 string 或 null |
| `setCookie(name, value, expires=365, path=null, domain=null, secure=false)` | 后四项通过历史位置参数读取；expires 为天数，写入 Cookie，返回 undefined |
| `deleteCookie(name)` | 使同名 Cookie 过期，返回 undefined；该接口不接收路径／域参数 |

下面的代码只能在该脚本按原方式加载之后调用：

```js
console.log(Traditionalized('网页开发')); // 網頁開發
console.log(Simplized('網頁開發'));       // 网页开发
console.log(TransText('网页', true, false));
```

脚本加载本身具有页面写入副作用，不能当作只有纯函数的工具包：

- 不在 DOM 就绪后用动态 getScript／异步脚本随意加载，document.write 可能影响现有文档；沿用已有页面解析期加载位置。
- 包含 with 和字符串定时器，不放入严格模式或 ES Module 环境。
- 逐字映射不解决一简多繁、词义或地区用语差异，不能代替框架 language 语言包。
- 会遍历页面文本与部分属性；不是只处理某个文本框的输入转换器。独立文字转换优先调用 Traditionalized／Simplized。

旧 Cookie 函数采用 escape／unescape，和 jquery.addon 的 URI 编码策略不同，不把两个接口的编码格式当成一致。应用自己的业务 Cookie 名避免覆盖其已有全局函数名。


## 3. 农历日期 date.js

[date.js](../../../static/js/date.js) 在加载时计算当前本地日期的农历信息，并通过 document.write 输出。它不是接收任意日期的通用日期组件。

| 接口 | 参数和结果 |
| --- | --- |
| `CalConv()` | 使用 new Date 的当前日期；成功返回四项数组：公历文字、星期、农历文字、节气 HTML；年份范围等异常时返回数字状态码 |
| `GetLeap(year)` | 公历闰年判断，返回 1 或 0 |
| `TagLunarCal(d, i, w, k, m1...m13)` | 内部历表构造器；设置 BaseDays、Intercalation、BaseWeekday、BaseKanChih、MonthDays |
| `CnEra(yearOffset)` | 返回天干地支文字；参数是以 1864 为起点的年份偏移，不能直接传公历年份 |
| `SolarTerm(date)` | 根据内置常数推算当前／后续节气，返回含 b 标签的展示 HTML |
| `cal_info` | 加载时 CalConv 的结果，顶层 let，不保证是 window 属性 |

```js
console.log(GetLeap(2024));         // 1
console.log(CnEra(2026 - 1864));    // 丙午
const todayInfo = CalConv();
if (Array.isArray(todayInfo)) {
    console.log(todayInfo[0], todayInfo[1], todayInfo[2]);
}
```

当前源码的年份判断是 `SolarYear <= 1998 || SolarYear > 2031` 时返回 1，也就是仅接受 1999～2031；月份／日检查分别可能返回 2／3。历表和节气算法是历史展示实现，不承诺任意年份的精确历法结果。脚本中的 document.write 不适合在页面完成后动态加载；普通日期格式化使用 global.js 的 Date.format。


## 明确调用示例

```javascript
const element = $id('article');
if (element) {
    // 首先加载本地chs2cht.js；元素应是当前业务明确允许转换的区域。
    TransBody(element);
}
```

## 验证与进一步阅读

在浏览器开发工具查看实际值及网络请求；先验证依赖和就绪状态，再检查返回值或Promise。DOM不存在、格式不合法及网络失败均应有反馈。服务端输入、权限和CSRF另外验证，客户端校验通过不等于业务授权。

[返回文档总览](../../README.md)
