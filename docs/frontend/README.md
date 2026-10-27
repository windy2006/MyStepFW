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

- [脚本加载、依赖与就绪](javascript/frontend.md)
- [主要变量与公开设置](javascript/frontend-runtime.md)
- [DOM查找与类型判断](javascript/js-dom.md)
- [字符串、日期、数组和金额扩展](javascript/js-types.md)
- [异步就绪、初始化队列与重试](javascript/js-ready.md)
- [路由地址、参数和批量链接](javascript/js-urls.md)
- [加载提示、窗口、滚动与复制](javascript/js-ui.md)
- [随机值、编码、水印和调试](javascript/js-data.md)
- [checkForm整体流程](javascript/frontend-forms.md)
- [need规则、len与字段示例](javascript/js-need.md)
- [jquery.addon插件与资源加载](javascript/js-addons.md)
- [简繁转换与日期辅助](javascript/js-text-calendar.md)
- [security.js与AJAX CSRF](javascript/security.md)
- [编辑器初始化与插件按钮](javascript/js-editor.md)
- [global.js函数详解](javascript/global-reference.md)
- [checkForm.js函数详解](javascript/checkform-reference.md)

[文档总览](../README.md)
