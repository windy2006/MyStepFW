# 应用功能测试（test） <small>v1.0.0</small>

## 功能概述

演示应用核心继承、预执行与收尾、lib／global／index加载、module与template配对、自定义路由处理链和简单API。页面会输出带序号的阶段信息，便于观察一次请求进入了哪些脚本。

这是放在app目录里的交互演示应用，不是网站根测试脚本目录，也不是自动化测试运行器。使用当前站点的框架上下文；学习时在自己的演示站点访问。

## 安装与访问

使用框架当前运行环境：PHP >=8.5.12 且 <9。本文中的目录均从网站根计算，所需数据库、图片或存储扩展按具体功能准备。

1. 将完整应用保留在`app/test/`，名称大小写为test；同名核心类是`test.class.php`，继承myStep。
2. 访问逻辑路径`test/`观察默认执行过程。
3. 继续访问`test/list`和`test/detail/1`观察模块与模板组合。
4. 自定义路由从t1、t2、t3进入，不把路由处理器文件当成直接网页入口。

```php
$home = myStep::setURL('test/');
$list = myStep::setURL('test/list');
$detail = myStep::setURL('test/detail/1');
```

实际地址随rewrite、path_info、query_string、域名绑定和子目录改变，使用框架生成链接。

## 使用方法

### 观察执行过程

默认应用页面会经过核心类初始化、lib声明、preload、global、index、模块及收尾等阶段。屏幕中的序号由这些演示脚本输出，不是框架要求业务代码必须打印的内容。

`test::preload()`在开始阶段调整标题；`global.php`调用本应用的logCheck；`index.php`检查目标module与同名template是否同时存在，再选择相应内容。找不到配对时，使用module/index.php与template/index.tpl作为应用自己的回退规则。

### 页面示例

| 逻辑路径 | 用途 |
| --- | --- |
| `test/` | 默认入口及阶段输出 |
| `test/list` | 生成5条示例链接并填充列表循环 |
| `test/detail/1` | 向详情模板赋予示例记录，编号只用于演示路径 |
| `t1/example` | 直接调用`app\test\route` |
| `t2/example` | 进入`myStep::getModule`，展示模块输出与外层处理 |
| `t3/HelloWorld/example` | Camel格式捕获及f1、f2、f3的多处理器调用 |

`t2`是显式getModule入口，其模块查找过程与默认test/index.php自行配对模板的分支不同；对照输出判断实际经过的脚本。

### 多处理器示例

```php
$format = ['Camel'=>'([A-Z][a-z]+)+'];
$rule = [
    ['/t3/[Camel]/[any]', ['app\\test\\f1,$1', 'app\\test\\f2,$2', 'app\\test\\f3']],
];
```

Camel以大写单词开始，例如HelloWorld。f1、f2通过显式占位参数取得对应捕获；最终处理器f3展示合并路径和前置处理器的返回值。实际链路行为见[路由规则](../../docs/backend/routing/routing.md)。

### API示例

`route.php`登记api接口，可请求逻辑路径：

```text
api/test/api
```

返回值为包含aaa、bbb、ccc和嵌套sub数组的普通数据，由框架转换为响应格式。调用仍须满足框架API全局及逐接口设置；不是无条件绕过接口管理的测试入口。

### 浏览器参数

保存js.info后，在当前应用页面DOM就绪阶段读取框架已经预置的setting：

```javascript
jQuery(function () {
    console.log(setting.info);
});
```

js分组会合入公开浏览器设置，传递配置时只选可公开字段。

## 设置说明

当前值保存在`app/test/config.php`，后台字段说明位于`config/default.php`。可在框架“参数设置”中选择本应用调整web.title和js.info；模板路径直接在应用配置中维护。

### setting-web-title

**网站名称**（`web.title`）

本应用的基础标题。在后台可保存该字段；本演示的test::preload()会把本次请求标题设置为“标题调整”，因此最终页面可能显示预执行阶段的覆盖值。

示例：

```text
流程观察应用
```

### setting-js-info

**测试参数**（`js.info`）

通过框架浏览器公开设置传入JavaScript的示例文字。只填写可公开内容，不在js分组写入密码或令牌。

示例：

```text
你好，浏览器
```

### setting-template-path

**模板路径**（`template.path`）

模板根目录相对于网站根；这一项在config.php中声明，不是本应用设置页面独立列出的字段。当前模板直接在此目录中存放，风格保持空串。

示例：

```text
app/test/template
```

## 目录与扩展

| 位置 | 用途 |
| --- | --- |
| `test.class.php` | 同名应用核心，输出阶段标记并实现preload／shutdown示例 |
| `lib.php` | 命名空间常量、路由、处理链、登录占位检查和API |
| `global.php` | 默认页面公共检查 |
| `index.php` | 默认模块与模板配对，组合页面 |
| `route.php` | Camel格式、t1／t2／t3和API声明 |
| `module/index.php`／`template/index.tpl` | 默认或回退内容 |
| `module/list.php`／`template/list.tpl` | 列表数据和循环展示 |
| `module/detail.php`／`template/detail.tpl` | 详情记录和模板 |
| `module/t2.php` | getModule路由的内容演示 |
| `template/main.tpl` | 外层页面 |
| `asset/` | 此演示应用的浏览器样式和脚本 |
| `config/` | 设置字段和类型描述 |

复制为自己的应用时，需要同时调整目录名、核心类及文件名、info中的app标识、命名空间、路由、模板和相关链接。登录示例中的logCheck只是返回true的演示，不是可直接用于产品的权限实现。

## 常见问题

- **访问不同路径却出现默认内容**：默认index要求同名module和template都存在，缺少其一就进入默认分支。
- **看不到部分阶段标记**：先确认访问的是默认页面、显式路由、getModule还是API；不同分支不保证经过相同的index和收尾。
- **更改标题后仍显示“标题调整”**：本应用preload显式覆盖本次请求标题，需在自己的应用中决定采用配置还是预执行设置。
- **页面不是正式业务界面**：这里的数据、阶段输出和登录检查用于展示调用方式，应在自己的应用中实现业务权限和数据读取。

## 相关资料

- [应用编写规范](../README.md)
- [框架指南](../../docs/README.md)
- [路由规则](../../docs/backend/routing/routing.md)
- [模板使用](../../docs/backend/templates/templates.md)

## 版权信息

版权所有 2019-2026 [Windy2000](mailto:windy2006@gmail.com)。
