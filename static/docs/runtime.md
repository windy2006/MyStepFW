# 加载流程与主要变量

返回[前端文档入口](../README.md)。依据 `lib/myStep.class.php`、`lib/mySecurity.class.php` 和当前公共脚本整理。

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

## 3. root_web 与 global

| 变量 | 类型／初值 | 功能和使用约定 |
| --- | --- | --- |
| `root_web` | string，例如 `/`、`/sub/` | 当前网站的 Web 根路径，由 PHP 输出；不是服务器磁盘绝对路径 |
| `global.root` | string | global.js 加载时从 root_web 取得，缺少有效 root_web 时回退 `/`；用于公共资源 |
| `global.root_fix` | string | 当前模块的工作路由前缀；从 pathname 及 index.php 查询路由初始化，并补尾斜杠；应用可能再次设置，不等同网站根 |
| `global.editor_btn` | string，初始 `''` | 旧编辑器集成预留的附加按钮文本，按具体编辑器代码解释 |
| `global.alert_leave` | boolean 或 string，初始 false | false 不提示；true 使用语言包或默认离页提示；字符串作为提示文字。浏览器可能使用自己的固定提示 |
| `global.func` | Array，初始 `[]` | 页面初始化回调列表，通过 ms_func_reg 登记；ms_func_run 执行当前快照 |
| `global.mobile` | boolean | 加载时 isMobile().any 的结果，不是窗口宽度或断点检测 |
| `global.err_report_func` | 可选 Function | 自定义错误报告器，接收 `(text, true, error)`；未设置时 reportError 使用 alert |
| `global.loadingVisible`、`global.loadingOverflow` | 延迟建立的状态 | 加载遮罩显隐及原始 body overflow，由 loadingShow 管理 |
| `global.anchorAnimating` | 延迟建立的 boolean | 锚点滚动状态，由 gotoAnchor 管理，不作为配置就绪定时器 |
| `window.myInfo` | 调用 info 后建立的 jQuery 对象 | 页面提示区缓存；无需在应用中预先创建 |

公共图片使用根路径：

```js
const logo = $id('site-logo');
if (logo) logo.src = global.root + 'static/images/logo.png';
```

不要把 `global.root_fix + 'static/...'` 当作公共资源路径；root_fix 常包含应用或模块路由。setLocation 修改浏览器历史地址后，不会自动重新计算 global.root_fix 或重新初始化应用。

## 4. setting：允许公开的前端设置

框架从 `index.php?ms_setting/应用名` 返回 JavaScript 定义，再请求 `index.php?ms_language/应用名/语言`。通常使用框架自动加载，不必自行拼接或重复请求接口。

| 属性 | 类型 | 含义 |
| --- | --- | --- |
| `setting.language` | string | 配置语言，如 default、chs、en |
| `setting.debug` | boolean | 前端调试标志；为 true 时框架把 reportError 接入 window.onerror |
| `setting.app` | string | 当前配置所属应用名称 |
| `setting.path_root` | string | 网站 Web 根路径，与当前 ROOT_WEB 对应 |
| `setting.path_app` | string | 应用实际资源目录的 Web 路径，通常形如 `/sub/app/Hello`；不是业务路由 |
| `setting.max_size` | number | 供现有上传组件使用的MB数，可为小数；先将PHP post_max_size与upload_max_filesize按单位转为字节，再取正限制的较小值；两项均无限制时保留32MB兼容默认值 |
| `setting.max_size_bytes` | number | 上述两项正限制的最小字节数；0表示这两项均未限制，不代表应用自身无上传限制 |
| `setting.url_fix` | string | 域名绑定带来的路由前缀标识，可为空，通常不带首斜杠 |
| `setting.url_prefix` | string | 当前路由模式的入口前缀，如 `/sub/`、`/sub/index.php/`、`/sub/index.php?` |
| `setting.url_prefix_app` | string | 面向当前应用的前缀，含域名绑定处理；JS 接口的值不保证有尾斜杠 |
| `setting.自定义项` | 由配置决定 | PHP 配置的 js 分组会合并到此对象，也可能覆盖同名默认项 |

例如 `upload_max_filesize=512K`、`post_max_size=2M` 时，max_size为0.5，max_size_bytes为524288；一项为0时不把它当作更小的上限。max_size的MB单位兼容当前CMS编辑器和框架上传示例。上传是否允许以及真实大小、扩展名和权限仍由服务端myUploader和应用配置决定，POST请求的表单开销也会占用post_max_size。

js 分组仅存放可以向所有页面访问者公开的数据；不要在其中放数据库密码、服务端密钥或认证凭据。

## 5. language：前端语言字典

language 是普通对象，先加载 myStep 公共语言，再合并当前应用语言，应用同名项覆盖公共项；实际条目取决于相应语言包，并非每个页面都有某个业务键。常用公共条目包括 `sending`、`alert_leave` 及 `checkform_*`。

```js
checkNrun(function () {
    const button = $id('save-button');
    if (button) button.textContent = language.save_button || '保存';
});
```

checkForm 自带中文后备提示，即使 language 尚未加载，也可以校验表单。不要把 DOM 中的文字直接拼成 HTML；普通文案用 textContent 或 jQuery.text。

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

## 7. msSecurity：会话安全适配

| 属性 | 类型 | 用途 |
| --- | --- | --- |
| `window.msSecurity.token` | string | 当前会话 CSRF 令牌；不写入共享缓存或日志 |
| `window.msSecurity.origins` | string[] | 框架明确允许的来源地址列表，含协议和必要端口 |
| `window.msSecurity.admin` | string[] | 后台路径识别前缀，用于旧式操作链接适配 |
| `window.msSecurity.adminContext` | boolean | 当前是否处于后台管理上下文 |
| `window.msSecurity.root` | string | 当前网站 Web 根路径 |

security.js 的 `allowed`、`mutates`、`field` 是闭包内部函数，不是可以在应用中调用的全局 API。脚本自动处理：

1. 对允许来源的 POST 表单添加／更新 `_ms_csrf` 隐藏字段，包括正常 submit 事件中的动态表单。
2. 对允许来源的非 GET／HEAD／OPTIONS jQuery、XMLHttpRequest、fetch 请求添加 `X-CSRF-Token`。
3. 将部分旧式后台修改链接转换为 POST，保留兼容入口；新功能仍直接使用 POST 表单，不依赖 URL 命名猜测。
4. 对显式 `data-ms-logout`、`data-ms-csrf` 的同源退出表单，先获取目标会话的当前令牌再提交。

浏览器原生 `form.submit()` 不触发 submit 事件，不会调用 checkForm，也不会经过普通动态表单的令牌注入；业务提交优先使用真实提交按钮或 requestSubmit。兼容脚本中的原生 submit 是在其已经准备好表单后执行的。

```js
async function submitProfile(form) {
    if (!checkForm(form)) return false;
    const response = await fetch(form.action, {
        method: 'POST',
        credentials: 'same-origin',
        body: new FormData(form)
    });
    if (!response.ok) throw new Error('保存失败：HTTP ' + response.status);
    return response;
}
```

示例只定义函数，由业务提交处理器按需调用；请求目标须是自己实现的 POST 接口。在正常经过安全装饰的页面中，令牌由 security.js 附加。CSRF 令牌不代表业务权限，也不代替服务端验证。

global.js 对 jQuery 的自动凭据策略只涵盖同源与 msSecurity 明确信任的来源，并保留调用者显式设置的 withCredentials。fetch 不会自动替你开启跨域凭据；只有确有需求且服务器已配置对应 CORS 时，才在业务中明确设置。
