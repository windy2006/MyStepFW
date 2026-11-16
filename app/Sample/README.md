# 框架演示（Sample） <small>v1.0.1</small>

## 功能概述

用可读的示例展示默认应用入口、自定义路由、格式占位符、中间处理器、API、注册模块，以及主模板／子模板组合。首页显示当前请求路径、查询参数和经过筛选的框架对象信息，适合对照源码理解框架的调用方式。

本应用是公开演示。页面和setting API仅展示经过筛选的配置副本，不提供修改站点配置的功能。

## 安装与访问

使用框架当前运行环境：PHP >=8.5.12 且 <9。本文中的目录均从网站根计算，所需数据库、图片或存储扩展按具体功能准备。

1. 在已安装的框架中保留`app/Sample/`及其lib、路由、模块和模板。
2. 访问逻辑路径`Sample/`查看首页，在页面中点击路由、API和模块示例。
3. 需要在自己的应用中使用某个机制时，将相应的模块、模板及路由声明放入自己的应用目录，调整名称后调用。

```php
$home = myStep::setURL('Sample/');
$route = myStep::setURL('mySample/example');
$api = myStep::setURL('api/Sample/setting');
```

表中的路径都是逻辑路径，独立域名、子目录和URL模式以站点生成结果为准，不直接访问本应用PHP源码文件。

## 使用方法

### 默认入口和上下文

`Sample/`调用index.php，创建main主模板和sample子模板。页面展示`$info_app`、`$tpl_setting`、`$tpl_cache`、`$ms_setting`以及控制器、数据库、缓存等对象的说明；对象内部口令不作为公开调试信息输出。

可比较以下两种请求：

```text
Sample/section/item
Sample/section/item?keyword=示例&page=2
```

前者演示路径段，后者同时演示查询参数。PHP代码中可读取当前框架已经解析的`$info_app['path']`和`$info_app['para']`，不重新用字符串切割原始网址。

### 路由示例

| 逻辑路径 | 处理过程 |
| --- | --- |
| `mySample/example` | 调用`app\sample\route`并装配route模板 |
| `mySample2/example` | 调用`myStep::getModule`，示例模块自行选择route2模板 |
| `mySample3/anyCamelCase` | 先执行`preCheck(3)`，再执行`routeTest`观察前一步返回值 |

`camel`格式为`[a-z]+([A-Z][a-z]+)+`，例如anyCamelCase；它与test应用中以大写开始的Camel格式不同。第三个示例使用Cookie演示一分钟内连续访问限制，不是对全部框架请求设置统一限制。

```php
$format = ['camel'=>'[a-z]+([A-Z][a-z]+)+'];
$rule = [
    ['/mySample/[any]', 'app\\sample\\route'],
    ['/mySample2/[any]', 'myStep::getModule'],
    ['/mySample3/[camel]', ['app\\sample\\preCheck,3', 'app\\sample\\routeTest']],
];
```

以上为Sample已有声明的结构示例，不要再复制一份到全局路由中造成重复。

### API示例

`route.php`登记`setting => app\sample\api`。在允许该接口的全局和逐接口策略下，可按以下形式请求：

```text
api/Sample/setting
api/Sample/setting/xml
api/Sample/setting/gen/charset
```

接口返回筛选后的配置或指定层级，不返回数据库分组及密码、密钥等字段。响应格式、方法限制、专属域名和是否要求管理员由框架API设置决定；设置在后台“API管理”维护，不在Sample中另设账号。

### 注册模块示例

lib.php登记`Sample_test1`，通过框架模块入口调用，输出示例文字：

```php
$url = myStep::setURL('module/Sample/test1');
```

注册名由应用标识与模块名组成，不是磁盘文件路径。模块处理器可以输出自己的内容，不能假定所有模块都自动使用同一页面收尾过程。

### 模板组合

index.php中先按原设置创建主模板，再调整模板名构建子模板，渲染后赋给main：

```php
$tpl = new myTemplate($tpl_setting, false);
$subSetting = $tpl_setting;
$subSetting['name'] = 'sample';
$sub = new myTemplate($subSetting, false, true);
$sub->assign('root', myStep::setURL('Sample/'));
$tpl->assign('main', $sub->render('', false));
```

需要可信PHP标签的模板才将第三个参数设为true；普通数据按模板语法转义。自己的应用应通过框架链接方法生成示例中的业务地址，而非把Sample路径写进产品页面。

## 设置说明

当前`app/Sample/config.php`只覆盖模板路径，没有独立的后台设置字段。其余值继承主框架；lib.php中的示例初始化会关闭本次请求的数据库自动连接，页面展示对象时不为演示主动读取业务表。

### setting-template-path

**模板路径**（`template.path`）

Sample自己的模板根目录，相对于网站根；当前模板直接放在该目录中，没有额外风格目录。

示例：

```text
app/Sample/template
```

模板在根目录直接存放，`template.name`一般为main，`template.style`保持空串；如全局配置指定了其他风格，可在本应用配置里明确覆盖为空。

## 目录与扩展

| 位置 | 用途 |
| --- | --- |
| `index.php` | 请求上下文展示与主子模板组合 |
| `lib.php` | 路由处理、中间检查、API、注册模块及公开配置筛选 |
| `route.php` | camel格式、三个路由示例和setting接口 |
| `module/index.php` | getModule分支的示例脚本 |
| `template/main.tpl` | 页面外层 |
| `template/sample.tpl` | 首页说明、参数和各示例链接 |
| `template/route.tpl`／`route2.tpl` | 两种路由输出的主体 |
| `config.php`／`plugin.php` | 当前应用配置及页面插件清单 |

## 常见问题

- **第三个路由提示访问过多**：这是preCheck(3)的Cookie示例，等待对应Cookie期限后重试；不要以密集刷新验证其他功能。
- **驼峰路由不匹配**：按camel格式填写anyCamelCase这类小写起始、包含后续大写单词的路径。
- **API返回404或403**：核对全局API启用、Sample/setting条目的启用和允许方法、管理员要求及实际域名入口。
- **配置里缺少数据库或密码**：公开演示主动筛选此类信息，不能把它当成完整站点配置导出。

## 相关资料

- [应用编写规范](../README.md)
- [框架指南](../../docs/README.md)
- [路由规则](../../docs/backend/routing/routing.md)
- [模板使用](../../docs/backend/templates/templates.md)

## 版权信息

版权所有 2019-2026 [Windy2000](mailto:windy2006@gmail.com)。
