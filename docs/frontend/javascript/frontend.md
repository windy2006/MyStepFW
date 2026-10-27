# 前端加载

## 一次页面请求与浏览器执行的先后

| 阶段 | 实际执行内容 | 应用代码的使用方式 |
| --- | --- | --- |
| 服务器setPara | 收集公共、应用和风格脚本，在合并脚本后追加公开设置请求 | 此时只有服务器配置，不能当作浏览器setting对象 |
| 服务器show | 输出root_web、CSS/JS、页面主体和DOM就绪队列入口 | 模板按正常结构放置page_start/page_end |
| 浏览器解析资源 | 依次执行jQuery、global.js和应用脚本 | 顶层脚本只登记回调，避免立即读取setting |
| 异步设置响应 | ms_setting接口定义setting，随后请求ms_language接口 | setting可用不代表language已经返回 |
| DOM和语言均就绪 | checkNrun检查setting、language后执行回调 | 在回调中生成路由、设置文案和初始化依赖配置的组件 |

配置请求、DOM就绪和其他资源下载并行推进，顺序不靠固定sleep保证。默认框架页面已登记设置与语言加载；独立页面须先提供这些资源，再执行依赖它们的初始化代码。

```javascript
ms_func_reg(function () {
    checkNrun(function () {
        const button = $id('save-button');
        if (button) button.textContent = language.save || '保存';
        console.log(setting.app, setting.url_prefix);
    }, [], 15000, window, 100);
});
```

checkNrun返回取消等待函数；超时会停止轮询并输出警告。等待失败时应检查设置与语言请求的状态和地址，不用空setting对象冒充真实配置。

## 本篇目标与调用场景

本篇所有JavaScript示例在浏览器运行。先加载所需本地脚本，不把PHP变量或磁盘路径直接当成浏览器变量。

## 主要变量

| 变量 | 用途与约束 |
| --- | --- |
| `root_web/global.root` | 网站Web根前缀，用于公开资源 |
| `setting/language` | 异步装入的公开设置和语言，须等待就绪 |
| `document/window` | 当前浏览器页面上下文，不是PHP的全局变量 |

## 1. 页面如何取得前端资源

```text
框架确定当前应用和模板风格
→ 应用 preload（如有）
→ 按 web.css / web.js 收集公共资源
→ 收集公共 global.css / global.js
→ 收集应用 asset 和当前风格资源
→ 按需追加 checkForm 等脚本
→ show() 通过 page_start / page_end 输出资源
→ 完整 HTML 在缓存之外注入当前会话的 msSecurity 和 security.js
→ 浏览器解析页面、执行脚本及 DOM 就绪回调
→ setting 异步返回后再发起 language 加载
```

上图描述的是依赖关系；DOM 就绪、异步配置返回和资源请求完成的先后不能靠固定时长推断。生成资源带内容版本参数，框架会根据源文件维护合并缓存。页面缓存不得保存其他会话的 CSRF 令牌。

CSS 的公共配置项填写文件名，不含 `.css`；JS 配置同理，不含 `.js`。例如默认 `jquery.addon` 对应 `static/js/jquery.addon.js`。额外脚本可通过 `$mystep->addJS()` 登记，应用自身资源不移入公用 lib。

## 2. 变量作用域

框架当前按普通 script 加载，并非 ES Module：

- `root_web`、`global` 由顶层 const 声明，后续普通脚本可以直接使用标识符；不要假定 `window.root_web`、`window.global` 一定存在，也不要重复声明。
- `setting`、`language` 是接口返回的顶层 var，需要等异步加载完成。
- `window.msSecurity` 是显式写入 window 的会话配置；它可能在其他配置对象之前出现。没有经过完整 HTML 装饰的页面不保证存在该对象。
- 自己的页面数据放在应用命名空间中，不覆盖上述框架标识符。

## 6. 初始化和就绪流程

```js
// app/Hello/asset/function.js
ms_func_reg(function () {
    // 这里是 DOM 初始化入口，不保证配置已返回。
    checkNrun(function () {
        const link = $id('article-list');
        if (link) link.href = routeURL('article/list/', 'Hello/');
    });
});
```

示例假设当前路由上下文属于 Hello 应用；已绑定域名的地址由 routeURL 结合 setting.url_fix 处理。跨应用、跨域链接优先由服务端框架 URL 工具生成后赋给模板。

- ms_func_reg 只登记函数；框架在 page_end 通过 `$(ms_func_run)` 执行。
- ms_func_run 重复执行会再次调用已登记的业务回调，业务绑定应自行防重。
- checkNrun 等待 setting 与 language，默认最长 15 秒，返回取消函数。
- checkSetting 不传回调只检查，不会自动替调用者重试；传回调时仅在尚未就绪时安排调用。

```js
const cancelReady = checkNrun(function (label) {
    console.log(label, setting.app);
}, ['当前应用'], 15000);

// 页面组件已经销毁、不再需要初始化时调用。
// cancelReady();
```

## 明确调用示例

```javascript
ms_func_reg(function () { console.log('页面DOM已就绪'); });
```

## 验证与进一步阅读

在浏览器开发工具查看实际值及网络请求；先验证依赖和就绪状态，再检查返回值或Promise。DOM不存在、格式不合法及网络失败均应有反馈。服务端输入、权限和CSRF另外验证，客户端校验通过不等于业务授权。

[返回文档总览](../../README.md)
