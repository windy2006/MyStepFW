# 主要变量与公开设置

## 本篇目标与调用场景

本篇所有JavaScript示例在浏览器运行。先加载所需本地脚本，不把PHP变量或磁盘路径直接当成浏览器变量。

## 主要变量

| 变量 | 用途与约束 |
| --- | --- |
| `root_web/global.root` | 网站Web根前缀，用于公开资源 |
| `setting/language` | 完整框架页面在脚本前预置公开设置和语言；独立页面用checkNrun等待 |
| `document/window` | 当前浏览器页面上下文，不是PHP的全局变量 |

## 3. root_web 与 global

| 变量 | 类型／初值 | 功能和使用约定 |
| --- | --- | --- |
| `root_web` | string，例如 `/`、`/sub/` | 当前网站的 Web 根路径，由 PHP 输出；不是服务器磁盘绝对路径 |
| `global.root` | string | global.js 加载时从 root_web 取得，缺少有效 root_web 时回退 `/`；用于公共资源 |
| `global.root_fix` | string | 当前模块的工作路由前缀；从 pathname 及 index.php 查询路由初始化，并补尾斜杠；应用可能再次设置，不等同网站根 |
| `global.editor_btn` | string，初始 `''` | 编辑器集成预留的附加按钮文本，按具体编辑器代码解释 |
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

不要把 `global.root_fix + 'static/...'` 当作公共资源路径；root_fix 常包含应用或模块路由。setLocation 修改浏览器地址后，不会自动重新计算 global.root_fix 或重新初始化应用。

## 4. setting：允许公开的前端设置

完整页面在head中的`ms-browser-state`脚本同步定义setting和language，早于应用资源、DOM就绪及点击事件。公开设置仅包含框架白名单与显式js分组，语言来自当前页面已加载的语言包。HTML缓存命中时也会更新该脚本，避免配置修改后沿用旧值。原`index.php?ms_setting/应用名`和`index.php?ms_language/应用名/语言`接口继续供独立页面显式使用，调用方须通过checkNrun等就绪辅助函数后再初始化。

| 属性 | 类型 | 含义 |
| --- | --- | --- |
| `setting.language` | string | 配置语言，如 default、chs、en |
| `setting.debug` | boolean | 前端调试标志；为 true 时框架把 reportError 接入 window.onerror |
| `setting.app` | string | 当前配置所属应用名称 |
| `setting.path_root` | string | 网站 Web 根路径，与当前 ROOT_WEB 对应 |
| `setting.path_app` | string | 应用实际资源目录的 Web 路径，通常形如 `/sub/app/Hello`；不是业务路由 |
| `setting.max_size` | number | 供现有上传组件使用的MB数，可为小数；先将PHP post_max_size与upload_max_filesize按单位转为字节，再取正限制的较小值；两项均无限制时保留32MB支持默认值 |
| `setting.max_size_bytes` | number | 上述两项正限制的最小字节数；0表示这两项均未限制，不代表应用自身无上传限制 |
| `setting.url_fix` | string | 域名绑定带来的路由前缀标识，可为空，通常不带首斜杠 |
| `setting.url_prefix` | string | 当前路由模式的入口前缀，如 `/sub/`、`/sub/index.php/`、`/sub/index.php?` |
| `setting.url_prefix_app` | string | 面向当前应用的前缀，含域名绑定处理；JS 接口的值不保证有尾斜杠 |
| `setting.自定义项` | 由配置决定 | PHP 配置的 js 分组会合并到此对象，也可能覆盖同名默认项 |

例如 `upload_max_filesize=512K`、`post_max_size=2M` 时，max_size为0.5，max_size_bytes为524288；一项为0时不把它当作更小的上限。max_size的MB单位支持当前CMS编辑器和框架上传示例。上传是否允许以及真实大小、扩展名和权限仍由服务端myUploader和应用配置决定，POST请求的表单开销也会占用post_max_size。

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

## 明确调用示例

```javascript
checkNrun(function () { console.log(setting.app, setting.url_prefix, language.sending); }, [], 15000, window, 100);
```

## 验证与进一步阅读

在浏览器开发工具查看实际值及网络请求；先验证依赖和就绪状态，再检查返回值或Promise。DOM不存在、格式不合法及网络失败均应有反馈。服务端输入、权限和CSRF另外验证，客户端校验通过不等于业务授权。

[返回文档总览](../../README.md)
