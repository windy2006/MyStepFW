# 扩展代码（xcode） <small>v2.1.0</small>

## 功能概述

在框架start/end钩子执行管理员受信PHP片段，按应用、实际路径、HTTP方法、阶段和顺序筛选。

## 安装与设置

需要MyStepFW 2.6.3、PHP >=8.5.12且<9、mbstring。后台安装、启用并绑定宿主应用，管理地址`xcode/`；片段本身还需启用。JSON导入新增片段，默认停用。

## 匹配与顺序

- 应用：填写`CMS`等标识，留空为所有已绑定宿主。
- 路径：匹配`info_app.route`，留空为全部，支持`*`等fnmatch通配符。使用实际路径，例如`/产品/*`；独立域名下通常没有`/CMS`前缀，不包含查询串。
- 请求方法：ALL、GET、POST、PUT、PATCH、DELETE、HEAD、OPTIONS。默认ALL；选择POST可只处理表单类请求，选择GET可只对展示页面生效。
- 阶段：0=start（服务、会话、认证准备后），1=end（普通页面结束时）。只筛选实际执行的框架钩子，不会让本来提前结束的API或文件响应补执行end。
- 顺序：数值从大到小，相同数值按记录编号从小到大。

```php
$rule = plugin_xcode::validate([
    'name' => '产品页面处理', 'app' => 'CMS', 'page' => '/产品/*',
    'http_method' => 'GET', 'position' => 0, 'order' => 10,
    'active' => true, 'code' => '$GLOBALS["product_page"] = true;'
]);
$matched = plugin_xcode::matches($rule, 'CMS', '/产品/示例', 0, 'GET');
```

## 编写片段

代码不写开始/结束标签，可用`$mystep`、`$ms_setting`（别名`$setting`，均为myConfig）、`$db`、`$cache`、`$info_app`。例如：

```php
$title = $ms_setting->value('web.title', '');
$route = $info_app['route'] ?? '/';
```

保存时进行PHP语法及词法检查；字符串中的XML声明允许，实际关闭PHP标签切出代码会被拒绝。普通异常记录私有日志，保留最近200条。管理入口跳过用户片段，便于修正代码。

片段具有服务器执行权限；主动exit、死循环等不属于可可靠隔离的普通异常。长耗时工作交给计划任务，避免放在所有页面的钩子中。

## 数据与模板

私有文件为`config/plugins/xcode/state.php`，稳定锁、结构验证及原子替换保护写入；卸载保留数据。后台代码在module、HTML在template，复用新旧后台布局、管理员认证、POST/CSRF与checkForm，不增加外部依赖。

## 设置说明

安装／设置页的“填写说明”会在独立插件指南中打开本文件对应章节。所有示例均为示范值，请按实际环境填写；不要复制示例密钥作为生产凭据。配置保存在本插件的 `config.php`。

本插件没有全局参数，具体内容在插件管理入口维护。

## 管理设置

以下项目在插件自己的管理或编辑页面中维护；隐藏的操作凭证与修订字段由程序生成。

### field-name

**名称**（`name`）

用于片段列表和维护。

示例：

```text
产品页处理
```

### field-app

**限制应用**（`app`）

填应用标识，留空适用于所有已绑定宿主。

示例：

```text
CMS
```

### field-page

**路径匹配**（`page`）

匹配实际info_app.route，支持fnmatch通配符，不含查询串；独立域名通常不带应用名前缀。

示例：

```text
/产品/*
```

### field-http_method

**请求方法**（`http_method`）

ALL或GET、POST、PUT、PATCH、DELETE、HEAD、OPTIONS。

示例：

```text
GET
```

### field-position

**执行阶段**（`position`）

0=start，服务与会话准备后；1=end，普通页面结束时。

示例：

```text
0
```

### field-order

**顺序**（`order`）

数值从大到小，相同数值按编号排序。

示例：

```text
10
```

### field-code

**PHP代码**（`code`）

不带PHP开始结束标签，可使用$mystep、$ms_setting、$db、$cache、$info_app；由框架管理员维护。

示例：

```text
$title = $ms_setting->value("web.title", "");
```

### field-active

**启用**（`active`）

关闭后片段不会执行。

示例：

```text
勾选
```

### field-json

**导入JSON**（`json`）

粘贴本插件导出的JSON内容，导入会新增记录而不是覆盖；PHP任务和代码片段导入后默认停用，确认内容后再启用。

示例：

```text
{"items": []}
```

## 更新历史

版本变更和需要调整的配置见[版本更新历史](CHANGELOG.md)。

## 版权信息

版权所有 2019-2026 [Windy2000](mailto:windy2006@gmail.com)。
