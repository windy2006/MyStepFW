# 浏览器前端

本栏目集中说明在浏览器中运行的 JavaScript，包括公共变量、函数、表单校验、页面初始化与异步资源加载和编辑器扩展。服务器上的配置读取、路由处理及页面模板生成在相应功能栏目说明。

## 采用的基础框架

- **jQuery**：用于 DOM 操作、事件、AJAX 及现有扩展。当前公共资源为本地 `static/js/jquery.js`，文件版本是 **3.6.1**。
- **Bootstrap**：用于布局、表单、按钮、导航和响应式界面。当前公共资源 `static/css/bootstrap.css`、`static/js/bootstrap.bundle.js` 的版本是 **4.6.2**；该版本的交互组件与本地 jQuery 配合使用。
- **框架公共脚本**：`global.js` 提供通用函数，`checkForm.js` 负责表单基本合法性检查，`security.js` 适配会话 CSRF。详细行为及失败处理见各篇说明。

以上版本来自当前网站公共资源的文件头，并不表示每个应用只能使用同一版本。`frontend` 插件可为应用选择已保留的本地 jQuery／Bootstrap 资源；具体应用以实际登记和加载结果为准，避免在同一页面重复加载不支持版本。

## 加载与使用约定

页面JavaScript的命名见[命名规范](../basics/09-naming.md#javascript名称)；服务器先生成页面，浏览器再执行脚本，两者的衔接见[脚本执行顺序](javascript/frontend.md#一次页面请求与浏览器执行的先后)。

先加载依赖，再执行应用脚本；DOM、公开设置和语言数据分别确认就绪。应用及插件专有脚本留在自己的 `asset/` 目录，通过既有资源收集机制加载。浏览器校验通过后，服务器仍须检查业务权限、输入和 CSRF。

## 章节目录

- [前端加载](javascript/frontend.md)
- [前端变量](javascript/frontend-runtime.md)
- [元素查找](javascript/js-dom.md)
- [类型扩展](javascript/js-types.md)
- [字符扩展](javascript/js-string.md)
- [日期格式](javascript/js-date.md)
- [数组扩展](javascript/js-array.md)
- [金额格式](javascript/js-money.md)
- [就绪队列](javascript/js-ready.md)
- [地址处理](javascript/js-urls.md)
- [页面交互](javascript/js-ui.md)
- [加载提示](javascript/js-loading.md)
- [窗口工具](javascript/js-window.md)
- [页面滚动](javascript/js-scroll.md)
- [剪贴复制](javascript/js-copy.md)
- [工具索引](javascript/js-data.md)
- [随机生成](javascript/js-random.md)
- [设备检测](javascript/js-device.md)
- [编码转换](javascript/js-base64.md)
- [文本摘要](javascript/js-digest.md)
- [页面水印](javascript/js-watermark.md)
- [页面提示](javascript/js-notice.md)
- [调试输出](javascript/js-debug.md)
- [自动行为](javascript/js-behavior.md)
- [表单校验](javascript/frontend-forms.md)
- [校验规则](javascript/js-need.md)
- [资源加载](javascript/js-addons.md)
- [文字工具](javascript/js-text-calendar.md)
- [简繁转换](javascript/js-chs.md)
- [农历日期](javascript/js-lunar.md)
- [请求令牌](javascript/security.md)
- [前端编辑器](javascript/js-editor.md)
- [全局脚本](javascript/global-reference.md)
- [校验函数](javascript/checkform-reference.md)

[文档总览](../README.md)
