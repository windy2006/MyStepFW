# jquery.addon插件与资源加载

修订日期：2026-10-23。浏览器脚本用法。

## 本篇目标与调用场景

本篇所有JavaScript示例在浏览器运行。先加载所需本地脚本，不把PHP变量或磁盘路径直接当成浏览器变量。

## 主要变量

| 变量 | 用途与约束 |
| --- | --- |
| `root_web/global.root` | 网站Web根前缀，用于公开资源 |
| `setting/language` | 异步装入的公开设置和语言，须等待就绪 |
| `document/window` | 当前浏览器页面上下文，不是PHP的全局变量 |

## 1. jquery.addon.js 概览

[jquery.addon.js](../../../static/js/jquery.addon.js) 包含旧 JSON 辅助、鼠标滚轮事件、Cookie、资源加载器及几个 jQuery 实例扩展。先加载 jQuery；$.vendor 运行时需要 global，$.setCSS 运行时需要 md5，因此这些调用应发生在 global.js 已加载之后。

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


## 明确调用示例

```javascript
const json = $.toJSON({title: '中文'});
console.assert($.secureEvalJSON(json).title === '中文');
$.setCSS(global.root + 'static/css/global.css');
```

## 验证与进一步阅读

在浏览器开发工具查看实际值及网络请求；先验证依赖和就绪状态，再检查返回值或Promise。DOM不存在、格式不合法及网络失败均应有反馈。服务端输入、权限和CSRF另外验证，客户端校验通过不等于业务授权。

[返回文档总览](../../README.md)

## 本地代码高亮与配置就绪

Document 与 Sample 应用通过本地 `vendor/highlight/highlight.js` 适配器调用高亮库：

```html
<pre class="brush:php;">&lt;?php echo "Hello";</pre>
<script>
$.vendor('highlight', {callback: function () { highlight(0); }});
</script>
```

`highlight(0)`处理 SyntaxHighlighter 的 `brush:...` 代码块；`highlight(1)`处理 highlight.js 的 `pre code` 代码块，第二个参数为已有的本地主题名，默认 `default`。依赖脚本、语法和样式从网站本地加载，不需要 CDN。

`setting` 和 `language` 由异步请求提供，适配器必须使用 `checkSetting(100, highlight, [mode, style])`，在配置就绪后保留原参数继续执行。仅调用 `checkSetting()`只检查状态，不会自动重试；首次加载时可能导致整个高亮初始化提前结束。框架等待最多15秒，加载失败不会永久轮询。
