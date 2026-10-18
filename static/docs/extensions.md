# 扩展脚本与样式资源

返回[前端文档入口](../README.md)。本篇说明现有资源如何接入，不新增第三方依赖；应用专属逻辑仍放在应用 asset 中。

## 1. jquery.addon.js 概览

[jquery.addon.js](../js/jquery.addon.js) 包含旧 JSON 辅助、鼠标滚轮事件、Cookie、资源加载器及几个 jQuery 实例扩展。先加载 jQuery；$.vendor 运行时需要 global，$.setCSS 运行时需要 md5，因此这些调用应发生在 global.js 已加载之后。

### JSON 工具

| 接口 | 参数及功能 | 返回值 |
| --- | --- | --- |
| `$.toJSON(value)` | 序列化值，有原生 JSON.stringify 时优先使用 | JSON string，部分不可序列化值为 undefined |
| `$.evalJSON(text)` | 解析 JSON，有原生 JSON.parse 时优先使用 | 解析后的值；非法输入抛异常 |
| `$.secureEvalJSON(text)` | 优先原生 JSON.parse；旧环境后备先检查基本语法再 eval | 解析后的值或异常 |
| `$.quoteString(text)` | 给字符串增加 JSON 引号及必要转义 | 带双引号的 string，不是 HTML 转义 |

```js
const text = $.toJSON({title: '中文', page: 1});
const data = $.secureEvalJSON(text);
console.log(data.title, $.quoteString('带有"引号"的文字'));
```

这些接口保留现有调用兼容。现代页面可直接使用 JSON.stringify／JSON.parse；旧后备路径中的 eval 不应被当成受信脚本执行接口，JSON 解析也不替代数据结构校验。

### Cookie

`$.cookie(name)` 读取并 decodeURIComponent，找不到时返回 null。`$.cookie(name, value, options)` 写入，返回 undefined；value 为 null 时将过期时间设为过去，从而删除。

| options 属性 | 类型 | 含义 |
| --- | --- | --- |
| `expires` | number 或 Date | 数字为天数；Date 为绝对过期日期 |
| `path` | string | Cookie 路径 |
| `domain` | string | Cookie 域，不需要跨子域时不要额外指定 |
| `secure` | boolean | 是否只通过 HTTPS 发送 |

```js
$.cookie('preferred_view', 'list', {expires: 7, path: global.root, secure: location.protocol === 'https:'});
console.log($.cookie('preferred_view'));
// $.cookie('preferred_view', null, {path: global.root});
```

删除时路径／域须和写入时一致。该旧接口没有 SameSite 参数，前端也不能设置 HttpOnly。登录身份和会话 Cookie 使用服务器机制，不用此接口代替。

### 批量加载脚本

`$.setJS(list, in_turn=true, callback, process)`：

- list：非空脚本 URL 数组，优先网站本地文件。
- in_turn：true 按顺序加载，false 并行加载；存在依赖时使用顺序模式。
- callback：结束回调，无参数。
- process：每个成功完成步骤调用，参数 `(num_done, num_total, scriptURL)`。
- 返回值：undefined，当前实现不返回可供 await 的 Promise。

下面只是两份已经随业务部署的脚本的调用写法，需替换为实际存在的应用文件：

```js
$.setJS([
    global.root + 'app/Hello/asset/editor-core.js',
    global.root + 'app/Hello/asset/editor-page.js'
], true, function () {
    console.log('脚本流程结束');
}, function (done, total, url) {
    console.log(done + '/' + total, url);
});
```

此旧加载器没有统一错误回调，内部没有完整衔接 $.getScript 的网络失败分支，加载失败时不保证调用最终回调，也不保证有准确的失败状态。不要传空数组，也不要写 `await $.setJS(...)` 并据此认为文件已经可用。核心依赖仍优先通过框架 addJS 收集。

### 动态加载 CSS

`$.setCSS(css)` 接受一个 CSS 地址或地址数组，空值直接返回；返回 undefined。内部用地址 MD5 生成 link id，并在约一秒后再设置 href，不提供“样式已加载”的回调，也不保证重复调用去重。

```js
// 已有本地样式的按需加载示例。
$.setCSS(global.root + 'static/css/font-awesome.css');
```

一般页面基础样式仍由 web.css／addCSS 管理，不依赖固定延时判断样式加载成功。

### 加载现有 vendor 组件

`$.vendor(name, option)` 按当前网站的本地布局加载 `vendor/名称/名称{后缀}.js`，成功后按需登记 CSS 并调用 callback；返回 undefined。

| option | 默认值 | 含义 |
| --- | --- | --- |
| `name_fix` | `''` | JS 文件名后缀；true 表示 .min，false 表示空串，也可自定义字符串 |
| `add_css` | false | false 不加 CSS；true 加同名 .css；字符串为 CSS 文件名后缀，例如 .min |
| `callback` | 空函数 | JS 成功返回后的回调；不代表额外 CSS 已完成加载 |

```js
// 对应当前已有 vendor/jquery.showImage/jquery.showImage.js。
$.vendor('jquery.showImage', {
    add_css: true,
    callback: function () {
        // 在此调用该组件自身 API；具体选项参照现有应用示例。
        console.log('图片组件脚本已加载');
    }
});
```

它不是任意 npm／Composer 包的通用解析器，也不会安装缺少的组件。使用前确认 vendor 中确有对应文件；错误处理和 CSS 就绪同样遵循旧加载器边界。

### jQuery 实例扩展

| 接口 | 参数和功能 | 返回值／说明 |
| --- | --- | --- |
| `$(...).serializeObject()` | 用 serializeArray 汇总表单字段，同名多值尝试转数组 | 普通对象；不会自动把 PHP 方括号字段名解析成嵌套结构；旧实现对空值重复字段和特殊对象键有边界，不用作可信数据转换器 |
| `$(...).outerHTML()` | 克隆集合首元素并取得其外层 HTML | string |
| `$(...).outerHTML(html)` | 在原元素前插入新 HTML 后移除原元素 | 被移除的原 jQuery 集合，不是新元素集合；只能传受信 HTML |
| `$(...).cssText(css)` | 解析分号分隔、单个冒号的简单样式声明并调用 css | 原 jQuery 集合；不是完整 CSS 解析器，复杂值优先使用 css 对象 |
| `$(...).disableSelection()` | 添加旧浏览器选择禁用属性、样式及 selectstart 处理 | 原 jQuery 集合 |
| `$(...).mousewheel(handler)` | 登记规范化的滚轮事件；无参数时触发事件 | jQuery 集合 |
| `$(...).unmousewheel(handler)` | 解绑滚轮处理器 | jQuery 集合 |

```js
const form = $('#search-form');
console.log(form.serializeObject());
const panel = $('#drag-handle');
panel.disableSelection().cssText('cursor: move; color: #333');
console.log(panel.outerHTML());

function onWheel(event, delta, deltaX, deltaY) {
    console.log(delta, deltaX, deltaY);
}
panel.mousewheel(onWheel);
// panel.unmousewheel(onWheel);
```

处理任意表单提交数据时，可优先使用 FormData 或按业务白名单读取字段；框架查询 GET 路由转换使用自己的安全参数字典，不依赖 serializeObject。

## 2. 简繁转换 chs2cht.js

[chs2cht.js](../js/chs2cht.js) 是旧式逐字对照转换脚本，不是多语言包系统。加载时调用 document.write 输出 id 为 TransLink 的切换链接，并读取当前主机对应的语言 Cookie。

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

[date.js](../js/date.js) 在加载时计算当前本地日期的农历信息，并通过 document.write 输出。它不是接收任意日期的通用日期组件。

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

## 4. CSS、图标和图片

| 资源 | 作用／示例 |
| --- | --- |
| `css/global.css` | font-sm 12px、font-md 16px、font-lg 24px；背景、通用表格文字处理等 |
| `.bg-transparent-half` | 半透明白背景 |
| `.rb` | 红色边框，便于临时调试 |
| `.noselect` | 禁止用户选择文字 |
| `.text-break` | 长文本换行 |
| `.nowrap` | 单行、省略溢出文字；需要容器宽度约束 |
| `.no-wrap` | 单行、隐藏溢出，不增加省略号 |
| `.watermark` | 隐藏 watermark 插入的干扰文字片段 |
| `.password` | 密码显示／隐藏控件图标，配合 global.js 的 DOM 初始化 |
| `css/font-awesome.css`、`css/glyphicons.css` | 既有本地图标字体样式及 fonts 内字体文件 |
| `css/bootstrap.css`、`js/bootstrap.bundle.js` | 当前已有 Bootstrap 的布局／组件基础，按现有页面使用 |
| `css/tinymce.css` | 既有编辑器内容展示相关样式，按编辑器页面需要加载 |
| `images/loading.gif`、`images/noimage.gif` | 加载提示和缺图资源 |

模板使用框架已赋值的 path_root，而不是服务器磁盘路径：

```html
<p class="font-md text-break">一段可以换行的文字。</p>
<span class="fa fa-star" aria-hidden="true"></span>
<img src="<!--path_root|html-->static/images/logo.png" alt="网站标志">
```

当前 global.css 的密码图标背景仍使用 `/static/images/icon_eye.png` 根绝对路径。子目录部署时需确认该地址可达；本次文档整理没有改变其样式行为。字体和 CSS 中的相对 URL 也需要与资源合并／输出位置配合验证，不能只检查 HTML 的图片地址。

## 5. 其他随附脚本

- `holder.min.js`：既有第三方占位图脚本，按示例页面需要使用；不是框架自定义图片接口。
- `ie10-viewport-bug-workaround.js`：仅针对 IEMobile/10.0 写入 viewport 补丁，无业务公共函数；它不提供其他现代 JS 能力的旧浏览器兼容。
- jQuery、Bootstrap 和 vendor 组件的完整选项以项目中实际保留的组件源码／随附文档为准。优先复用本地资源，不为普通后台表单另加 CDN 或外部校验框架。
