# 升级管理

## 运行环境与更新通道

本版本使用`php74`更新通道，要求PHP>=7.4.0且<8.0.0。后台显示当前通道，更新请求自动带上本机PHP版本；不通过修改通道来跨越运行版本。

在插件设置中将更新网址指向提供同一PHP通道的更新服务。来源未声明运行环境、通道不匹配或PHP版本不满足时，停止更新并显示原因；先完成对应服务器运行环境的配置，再使用其更新源。

框架运行要求保存在`config/runtime.json`。在线更新与离线ZIP都携带运行声明；离线更新必须使用本通道生成的更新ZIP。应用和插件的manager组件包同时带有类型、标识、运行要求与负载摘要，校验通过才解包到目标目录。普通原生myPacker文件不直接作为manager组件更新上传。

完整安装包用于新目录，独立安装器在释放文件前检查PHP版本。运行要求检查不代替来源可信性、文件权限检查和业务数据备份。

## 包类型与用法

| 包 | 获取方式 | 使用位置 |
| --- | --- | --- |
| 完整安装ZIP | manager系统打包 | 新的安装目录，访问包内独立安装器 |
| 框架更新ZIP | manager下载离线更新 | 已安装站点的离线更新上传入口 |
| 应用／插件组件包 | 对应通道的manager组件下载 | manager指定的应用或插件安装流程 |

组件包包含类型、组件标识、运行声明、负载长度和SHA-256，目标与当前操作一致才交给myPacker发布。摘要用于检查内容完整性，包仍须来自可信更新服务。原生myPacker文件的直接读写用法见[打包管理](../../backend/tools/archives.md)，不混用两种包入口。

## 检查通道

管理界面自动携带通道和PHP版本，不需要管理员手工拼接参数。诊断更新服务时，可在浏览器查看类似以下请求：

```text
https://updates.example.test/api/plugin_manager/check/version?v=2.5.2&channel=php74&php=7.4.33
```

| 响应字段 | 含义 |
| --- | --- |
| server_version | 更新服务实际的系统版本 |
| version | 当前客户端可用的更新版本，没有更新时为空字符串 |
| compatible | 通道和实际PHP范围是否通过检查 |
| runtime | 更新服务的format、channel、php_min及php_max |
| message | 不匹配时的说明 |

版本相同且compatible为true时，version为空是正常结果。compatible为false时不列出可安装的应用和插件版本；运行信息缺失、通道不符或PHP不满足范围的下载请求返回409。先核对本地PHP、运行声明和manager更新网址，再重新检查；不要改包内声明来绕过检查。

## 定位和适用场景

负责文件／版本比较、应用插件包、在线／离线更新和独立安装包。更新使用统一预检、暂存、目标权限判断和失败反馈。

## 当前目录及主要文件

源码位于`plugin/manager/`。以下为当前源码层级，不表示所有文件都会自动执行：

```text
plugin/manager/
├── class.php
├── config.php
├── config/
├── ignore
├── index.php
├── info.php
├── install.php
├── language/
├── menu.json
├── module/
├── route.php
├── template/
├── version.php
```

## 入口与执行过程

route.php登记manager及pack入口和plugin_manager接口；module/manager.php与template/manager.tpl处理管理界面。

## 配置、模板和资源

设置、语言、模板与asset/files等资源保持在本项目内；共用框架服务通过当前应用上下文调用。配置值与后台字段结构分别维护，表单客户端用checkForm，服务端仍校验权限、方法和CSRF。

### 更新网址

新后台：插件管理 → manager → 设置；原后台也在插件管理中的 manager 设置修改。

```php
return [
    'pack' => true,
    'update' => true,
    'update_url' => 'https://updates.example.com',
];
```

- `update_url`：本站使用的更新源，适用于版本检查、远程文件校验、差异下载及框架／应用／插件在线更新。可填域名或 HTTP/HTTPS 基址，程序自动追加 `/api/plugin_manager/`。省略协议时使用 HTTPS 并验证证书；连接失败不自动降级到 HTTP。仅提供 HTTP 的服务器须显式填写 `http://`。
- `update`：本站是否向其他站点提供更新服务，与选择更新源无关。
- `pack`：本站是否开放打包服务，后台操作需要管理员权限，并遵循调试开关设置。

`update_url`设为空字符串时，管理页仍可打开，离线上传更新照常使用；联网操作提示先配置网址。
例如 `plugin_manager::updateServer()`读取有效配置原值，`plugin_manager::updateBase(plugin_manager::updateServer())`生成经校验的 HTTP/HTTPS 基址。

### 更新网址协议

| 设置值 | 实际基址 |
| --- | --- |
| `updates.example.com` | `https://updates.example.com` |
| `https://updates.example.com/framework/` | `https://updates.example.com/framework` |
| `http://updates.example.com:8080/framework/` | `http://updates.example.com:8080/framework` |

不根据当前网站协议推测更新服务器协议，也不会在 HTTPS 证书或连接失败后自动改用 HTTP。HTTP 是管理员显式选择的调用方式，更新传输不会经过 TLS 加密或服务器证书校验；可用时优先填写 HTTPS。

### 版本检查和表格刷新

管理页首次展示可读取 `check_app.php`、`check_plugin.php` 缓存。页面随后请求后台 `manager/check/version`，后台向配置的更新源获取实时组件版本，校验并更新缓存，同时返回应用／插件版本及 PHP `version_compare` 计算的更新资格。浏览器按组件标识立即更新版本列和按钮，无需再刷新整页。

没有缓存时显示“待检查”；更新源未列出该组件时显示“未提供”；请求失败显示“检查失败”，隐藏更新按钮。缺少缓存记录不代表组件没有发布版本，应以本次成功检查结果为准。两个后台共用这一机制。

### 同站检查与请求保护

更新源可以指向当前站点域名。后台版本检查写入本地缓存，使用 POST 和 CSRF 保护。jQuery 预处理与原生 XHR 包装同时启用时，只发送一个 `X-CSRF-Token`，避免浏览器合并成“令牌, 令牌”而被服务器拒绝；显式错误令牌仍会拒绝。

更新接口 `server_version` 返回更新源 `config/version.php` 的真实版本，`version` 保留发布清单中可用升级版本的含义。后台另返回 PHP 比较结果 `server_compare`：0 表示相同，正数表示服务器较新，负数表示本地较新。相同版本显示“已是最新版本（服务器 v…）”；服务器较新但无可用发布包时如实提示，不伪造升级包，也不将成功检查标为失败。

## 使用步骤

1. 在测试站核对本项目目录、元信息和所需扩展。
2. 在插件管理中检查安装条件、安装／活动状态，选择所需应用绑定及运行顺序。
3. 先执行只读展示，再在独占测试资源中验证保存或管理动作。
4. 检查模板风格、窄屏、资源地址、会话与失败反馈；不在正式数据上试验安装或恢复。

## 版本记录与升级包

框架在线更新的发布资料位于 `plugin/manager/version.php`，与系统版本声明 `config/version.php` 分工不同。更新源通过此文件提供版本、成员和迁移信息。

| 字段 | 说明 |
| --- | --- |
| 数组键 | 正式系统版本号，按版本先后累计读取 |
| `info` | 后台显示的本版中文更新说明 |
| `file` | 相对于网站根的新增、修改、删除文件；以单个 `/` 开头 |
| `setting` | 可选：合并到系统配置的迁移值，保留未涉及的站点配置 |
| `code` | 可选：需要执行的受信PHP迁移代码；不需要时使用空字符串 |

以下仅为格式示例，实际路径和版本必须对应真实发布差异：

```php
return array(
    '2.4.0' => array(
        'info' => '本版新增功能及修复说明',
        'file' => array(
            '/config/version.php',
            '/lib/Example.class.php',
            '/plugin/manager/version.php',
        ),
        'setting' => array(),
        'code' => '',
    ),
);
```

更新服务器将所有高于客户端版本的记录按版本排序，合并去重 `file`，合并配置迁移并依次收集迁移代码。清单中的文件存在时读取**服务器当前内容**；路径不存在时生成删除操作，不能因为文件已删除就从清单抹去。移动文件同时列旧、新路径。`config/version.php` 在文件和迁移处理完成后提交。

在线更新直接应用这份数据。离线更新将同样的数据及文件保存为带 `_update.json` 的ZIP，导入后进入统一更新流程。完整安装包用于新站安装，不能当作离线升级包导入。版本不能只保留最后一条，否则跨版本升级会漏文件或迁移；修改记录中的 `setting/code` 必须评估其对既有安装的影响。

`check_file.php`用于文件校验，`check_app.php`和`check_plugin.php`用于远程组件版本缓存；它们都不能代替本清单。更新源的站点配置和数据库不作为文件覆盖成员；需要改变配置时走明确的迁移步骤。

## 边界与维护

生成的check_*、pack/update/rollback是运行或发布资料，不能因为文档扫描而重建。管理员操作需要后台授权，公开更新源只提供已开放的版本与下载接口。

[框架指南总览](../../README.md)
