# 功能类说明（Document） <small>v1.5.1</small>

## 功能概述

展示框架公共类、接口、Trait及数据库／缓存／会话驱动的使用示例和方法说明。首页列出具有示例脚本的项目；具体页面可查看可运行示例、源代码和PHPDoc方法说明。

Document使用框架基础控制器，由自己的`index.php`选择示例和模板。它不是框架后台指南的Markdown阅读器，也不要求为每篇类说明建立一个应用。

## 安装与访问

使用框架当前运行环境：PHP >=8.5.12 且 <9。本文中的目录均从网站根计算，所需数据库、图片或存储扩展按具体功能准备。

1. 在完整框架中保留`app/Document/`、`module/`、`template/`和`data/`示例资源。
2. 访问逻辑路径`Document/`查看当前示例清单。
3. 选择类查看用法；追加`/detail`查看方法说明。
4. 数据库、图片、归档或网络示例还需要各自的PHP扩展、测试文件和服务。先阅读该页源码，再在适当环境运行。

```php
$listURL = myStep::setURL('Document/');
$sampleURL = myStep::setURL('Document/myString');
$detailURL = myStep::setURL('Document/myString/detail');
```

上面是逻辑路径，rewrite根目录部署时分别表现为`/Document/`等；其他URL模式或子目录由框架生成。类名大小写按`module/`现有文件名填写，例如`SQLite`。

## 使用方法

| 入口 | 内容 |
| --- | --- |
| `Document/` | 由实际module示例文件生成的项目清单 |
| `Document/myString` | 运行字符串示例，同时展示示例源码 |
| `Document/myTemplate` | 查看模板声明、赋值及渲染示例 |
| `Document/myDb/detail` | 反射展示类中带文档注释的方法 |
| `Document/interface_user/detail` | 查看已提供示例的接口说明 |
| `Document/SQLite` | SQLite驱动示例，需要对应扩展 |
| `Document/myImg` | 图片直接响应示例，响应不是普通HTML说明页 |
| `Document/myExcel` | Excel直接输出示例，可能触发文件下载 |

项目必须先在`module/`中有对应示例，且类、接口或Trait可以加载，才会被入口接受。不能通过URL传入任意类名或磁盘文件路径执行。

### 阅读方法说明

方法目录来自PHPDoc与反射，包含方法名称和注释。以当前源码声明的可见性、参数和返回值为准；示例不是给所有方法自动生成的完整测试覆盖。

### 使用示例

普通示例按以下过程组合：读取`module/名称.php`作为展示源码，执行该示例收集输出，再交给`template/sample.tpl`显示；方法说明使用`detail.tpl`。图片和Excel项目直接响应并结束，不再套普通HTML外层。

示例可复制到自己的应用模块中调整。例如在已有应用模块内使用字符串类：

```php
$data = ['title'=>'示例', 'count'=>3];
$json = myString::toJson($data);
```

业务数据不要直接替换Document的共享示例资源。部分示例会生成文件、读取网络或测试连接；需要运行时，使用自己的样例路径和测试账号。

## 设置说明

当前`app/Document/config.php`只覆盖下面的模板路径，没有专属`config/`字段页面。其他框架参数按全局配置继承；需要变更时，在应用配置中显式覆盖相应分组。

### setting-template-path

**模板路径**（`template.path`）

本应用的模板目录，相对于网站根。目录直接含main、list、sample、detail等模板；运行时与ROOT组合为绝对路径。

示例：

```text
app/Document/template
```

模板直接放在当前目录，没有风格子目录时，让继承后的`template.style`保持空串。完整配置示例：

```php
<?php
return [
    'template' => [
        'name' => 'main',
        'path' => 'app/Document/template',
        'style' => '',
    ],
];
```

## 目录与扩展

| 位置 | 用途 |
| --- | --- |
| `info.php` | 应用名称、标识与版本 |
| `config.php` | 应用配置覆盖 |
| `index.php` | 示例白名单、反射和页面组合 |
| `module/` | 每个公共类／驱动的独立使用示例 |
| `template/main.tpl` | 外层页面 |
| `template/list.tpl` | 示例目录 |
| `template/sample.tpl` | 源码及运行结果 |
| `template/detail.tpl` | 方法说明 |
| `data/` | 配置、模板、图片和控制器等示例输入 |
| `route.php` | 本应用当前不额外声明自定义路由 |
| `plugin.php` | 应用页面的插件调用清单 |

增加一个类示例时，在module中新建对应名称的PHP文件，确认该类可以按框架规则加载；首页按实际文件收集条目。示例脚本不得把URL参数直接拼成任意include路径。

## 常见问题

- **出现404**：先核对类名大小写、对应module文件，以及类／接口／Trait能否加载。
- **只显示方法说明，没有具体示例输出**：使用不带detail的项目路径，并确认module脚本存在。
- **图片页或Excel页没有普通页面外框**：这类示例直接返回图片或文件响应，属于各自输出方式。
- **方法缺少说明**：检查源码是否为该方法提供PHPDoc；阅读页不会替没有注释的方法编造参数说明。
- **样例连接失败**：检查该驱动要求的扩展、测试服务与示例参数，不在公开页面填写真实服务口令。

## 相关资料

- [应用编写规范](../README.md)
- [框架指南](../../docs/README.md)
- [路由规则](../../docs/backend/routing/routing.md)
- [模板使用](../../docs/backend/templates/templates.md)

## 版权信息

版权所有 2019-2026 [Windy2000](mailto:windy2006@gmail.com)。
