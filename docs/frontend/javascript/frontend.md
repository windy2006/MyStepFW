# 脚本加载、依赖与就绪

## 本篇目标与调用场景

本篇所有JavaScript示例在浏览器运行。先加载所需本地脚本，不把PHP变量或磁盘路径直接当成浏览器变量。

## 主要变量

| 变量 | 用途与约束 |
| --- | --- |
| `root_web/global.root` | 网站Web根前缀，用于公开资源 |
| `setting/language` | 完整框架页面在脚本前预置公开设置和语言；独立页面用checkNrun等待 |
| `document/window` | 当前浏览器页面上下文，不是PHP的全局变量 |

## 1. 页面如何取得前端资源

```text
框架确定当前应用和模板风格
→ 应用 preload（如有）
→ 按 web.css / web.js 收集公共资源
→ 收集公共 global.css / global.js
→ 收集应用 asset 和当前风格资源
→ 按需追加 checkForm 等脚本
→ show() 在 page_start 首位预置 setting / language，再通过 page_start / page_end 输出资源
→ 完整 HTML 在缓存之外注入当前会话的 msSecurity 和 security.js
→ 浏览器解析页面、执行脚本及 DOM 就绪回调
→ DOM和配置均已就绪后运行页面初始化队列
```

上图描述的是依赖关系；完整框架页直接使用预置的配置对象；独立页面仍不能用固定时长推断配置或资源加载完成。生成资源带内容版本参数，框架会根据源文件维护合并缓存。页面缓存不得保存其他会话的 CSRF 令牌。

CSS 的公共配置项填写文件名，不含 `.css`；JS 配置同理，不含 `.js`。例如默认 `jquery.addon` 对应 `static/js/jquery.addon.js`。额外脚本可通过 `$mystep->addJS()` 登记，应用自身资源不移入公用 lib。

## 一次页面请求与浏览器执行的先后

服务器先完成请求调度、会话及权限检查、模板生成，再把HTML响应交给浏览器。完整服务器顺序见[执行流程](../../backend/runtime/execution.md)；本节只说明浏览器收到页面后的JavaScript顺序。

| 阶段 | 浏览器行为 | 应用脚本应做什么 |
| --- | --- | --- |
| 解析HTML | 按标签位置建立DOM；普通同步script按出现位置执行 | 不访问尚未建立的页面元素 |
| 公开状态 | 完整框架页面在资源脚本前定义setting/language | 只使用公开字段；不向浏览器暴露服务器密钥 |
| 公共依赖 | 读取本地jQuery、扩展、global及已登记应用资源 | 按依赖登记，不重复加载基础框架 |
| 脚本顶层 | 声明函数／应用对象，调用ms_func_reg登记回调 | 顶层声明不等于DOM已就绪 |
| DOM就绪 | page_end登记的`$(ms_func_run)`调用初始化队列 | 在回调内查找DOM、绑定事件；可能重复初始化的组件需防重 |
| 用户交互 | 触发点击、表单校验或AJAX | 输入检查、加载反馈、异常处理分别实现 |
| 异步完成 | 网络响应到达后才执行对应回调 | 不用固定延时猜测完成；局部插入内容显式初始化 |

异步资源、AJAX和DOM就绪彼此不是一条固定完成队列。独立页面没有经过完整框架输出时，用checkNrun等等待公开设置和语言；动态脚本还需等待自己的加载回调。仅把HTML片段插入DOM，不会重新触发整页DOM就绪事件。

```javascript
// 脚本被解析时：登记初始化，不立即查询尚未存在的元素。
ms_func_reg(function () {
    checkNrun(function () {
        const articleList = document.getElementById('article-list');
        if (!articleList || articleList.dataset.ready === '1') return;
        articleList.dataset.ready = '1';
        articleList.addEventListener('click', function (event) {
            // 在事件处理器中处理用户操作；服务器仍检查权限及输入。
        });
    });
});
```

## 2. 变量作用域

框架当前按普通 script 加载，并非 ES Module：

- `root_web`、`global` 由顶层 const 声明，后续普通脚本可以直接使用标识符；不要假定 `window.root_web`、`window.global` 一定存在，也不要重复声明。
- `setting`、`language` 是页面预置的顶层var，同时可通过window访问；独立页面自行加载时须等待完成。
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
