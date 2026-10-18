# MyStepFW 前端开发文档

整理日期：2026-10-18。依据当前框架源码整理，适用于 PHP 7.4 站点输出的浏览器页面。

本目录保存网站公共前端资源。本说明覆盖框架注入的主要变量、公共函数、表单检查、安全适配和已有扩展；应用与插件自己的业务变量、页面菜单和第三方组件完整 API 不作为框架公共接口。

## 阅读入口

| 文档 | 内容 |
| --- | --- |
| [加载流程与主要变量](docs/runtime.md) | 模板和资源加载、root_web、setting、language、global、msSecurity、页面初始化与请求安全 |
| [公共函数与使用示例](docs/functions.md) | global.js 的 DOM、路由、字符串、日期、界面、复制、随机值、编码、调试及生命周期函数 |
| [表单校验与配置控件](docs/forms.md) | checkForm、need 全部规则、len、密码确认、单选／复选、myConfig 字段定义和完整示例 |
| [扩展脚本与样式资源](docs/extensions.md) | jquery.addon、简繁转换、农历日期、CSS、图片、字体和第三方资源的调用边界 |

配套文档：[应用编写规范](../app/README.md)、[插件编写规范](../plugin/README.md)、[模板机制](../docs/framework/templates.md)、[JavaScript 维护记录](../docs/framework/javascript.md)。

## 资源与职责

| 路径 | 职责 | 使用方式 |
| --- | --- | --- |
| `js/jquery.js` | 现有本地 jQuery | 框架配置收集；多数公共脚本的基础依赖 |
| `js/global.js` | 公共函数、原型扩展、路由和页面事件 | 框架自动收集 |
| `js/checkForm.js` | 表单格式、长度和密码确认检查 | 需要的页面通过 addJS 收集；Admin 已登记 |
| `js/security.js` | 表单及 Ajax 的会话 CSRF 适配 | 完整 HTML 经框架安全装饰后注入 |
| `js/jquery.addon.js` | 已有 jQuery 扩展 | 按站点 web.js 配置加载 |
| `js/chs2cht.js`、`js/date.js` | 旧式简繁转换和农历日期展示 | 按需使用，先阅读扩展说明中的副作用和范围 |
| `js/bootstrap.bundle.js`、`js/holder.min.js` | 已有第三方界面／占位图资源 | 按页面需要使用，不视为框架自定义 API |
| `css/global.css` | 公共字体尺寸、水印、密码显示图标等 | 框架自动收集 |
| `css/bootstrap.css`、字体图标样式 | 现有本地第三方样式 | 按站点 web.css 配置收集 |
| `images/`、`fonts/` | 公共图片、图标和字体 | 使用网站根路径前缀引用 |

默认配置的 JS 顺序是 `jquery,jquery.addon,bootstrap.bundle`；框架随后收集 `global.js` 和应用 asset 脚本。当前站点可通过配置调整这些列表，不应把默认值当成固定依赖清单。

## 最小接入示例

在应用模块或其他已经取得框架实例的 PHP 上下文中登记需要的公共脚本：

```php
$mystep->addJS(STATICS.'js/checkForm.js');
```

在应用的主模板中保留资源输出槽：

```html
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="<!--charset|html-->">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <!--page_start-->
</head>
<body>
    <!--main-->
    <!--page_end-->
</body>
</html>
```

应用专属行为保存在 `app/应用名/asset/function.js` 或当前风格的 `asset/风格/function.js`：

```js
ms_func_reg(function () {
    // DOM 就绪不代表 setting 和 language 已经加载完成。
    checkNrun(function () {
        const title = $id('page-title');
        if (title) title.textContent = language.page_title || setting.app;
    });
});
```

不要在已由框架输出的页面中重复手工加载 jQuery、global.js 或重新声明 root_web。生成的 `cache/script/*.js`、`*.css` 属于框架合并资源，修改原始文件并通过框架输出，不直接维护缓存文件。

## 开发约定

- 复用框架的资源收集、URL 生成、模板、表单和安全机制；公共资源优先使用 static，应用资源放回应用 asset，已有第三方组件沿用本地 vendor。
- 页面需要配置／语言时使用 checkNrun；普通 DOM 初始化可用 ms_func_reg。不要通过任意固定延时猜测配置是否已加载。
- 路由与查询参数分开生成：先 routeURL，再 queryURL。根目录静态资源地址与应用业务路由不是同一种前缀。
- 客户端表单调用 checkForm；服务端继续检查输入、权限、请求方法与 CSRF。
- PHP 7.4 是服务端版本约束，不代表旧浏览器兼容保证。公共脚本使用 const/let、Promise、URL、Map 等现代浏览器能力；原型补丁不等于完整旧浏览器 polyfill。

## 维护与验证

接口行为变化时，同步更新本目录对应主题及相关应用调用；JavaScript 历史修复记录继续放在 `docs/framework/javascript.md`，本目录作为前端使用说明入口。

在网站根执行已有检查：

```sh
node tests/javascript/javascript_regression.js
node tests/javascript/javascript_regression.js --minified
node tests/security/security_browser.js
node tests/admin/admin_javascript.js
```

真实浏览器 DOM 测试服务的启动方式见[测试目录说明](../tests/README.md)。这些测试不是加载所有旧扩展或第三方资源的兼容保证。
