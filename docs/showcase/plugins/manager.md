# manager：系统打包与更新

修订日期：2026-10-23。现有插件介绍专栏。

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

### 更新网址（manager 1.1.0 / 框架 2.0.1）

新后台：插件管理 → manager → 设置；原后台也在插件管理中的 manager 设置修改。
原“参数设置 → 站点设置”不再提供此项。实际保存文件为 `plugin/manager/config.php`：

```php
return [
    'pack' => true,
    'update' => true,
    'update_url' => 'https://updates.example.com',
];
```

- `update_url`：本站使用的更新源，适用于版本检查、远程文件校验、差异下载及框架／应用／插件在线更新。可填域名或 HTTP/HTTPS 基址，程序自动追加 `/api/plugin_manager/`。省略协议时使用 HTTPS 并验证证书；连接失败不自动降级到 HTTP。仅提供 HTTP 的服务器须显式填写 `http://`。
- `update`：本站是否向其他站点提供更新服务，与选择更新源无关。
- `pack`：本站是否开放打包服务，后台权限及调试规则沿用原机制。

`update_url`设为空字符串时，管理页仍可打开，离线上传更新照常使用；联网操作提示先配置网址。
对尚未迁移的旧安装，仅在插件配置中不存在 `update_url` 字段时兼容读取全局 `web.update`；显式空值不回退，不会在读取过程中自动写文件。
例如 `plugin_manager::updateServer()`读取有效配置原值，`plugin_manager::updateBase(plugin_manager::updateServer())`生成经校验的 HTTP/HTTPS 基址。
本次已迁移当前站点原有值；旧更新域名失效不会因此自动恢复服务，请在插件设置中填入可用的更新服务器。

### 更新网址协议（manager 1.1.1 / 框架 2.0.2）
+
+| 设置值 | 实际基址 |
+| --- | --- |
+| `updates.example.com` | `https://updates.example.com` |
+| `https://updates.example.com/framework/` | `https://updates.example.com/framework` |
+| `http://updates.example.com:8080/framework/` | `http://updates.example.com:8080/framework` |
+
+不根据当前网站协议推测更新服务器协议，也不会在 HTTPS 证书或连接失败后自动改用 HTTP。HTTP 是管理员显式选择的兼容方式，更新传输不会经过 TLS 加密或服务器证书校验；可用时优先填写 HTTPS。
+
+### 版本检查和表格刷新（manager 1.1.2 / 框架 2.0.3）
+
+管理页首次展示可读取 `check_app.php`、`check_plugin.php` 缓存。页面随后请求后台 `manager/check/version`，后台向配置的更新源获取实时组件版本，校验并更新缓存，同时返回应用／插件版本及 PHP `version_compare` 计算的更新资格。浏览器按组件标识立即更新版本列和按钮，无需再刷新整页。
+
+没有缓存时显示“待检查”；更新源未列出该组件时显示“未提供”；请求失败显示“检查失败”，隐藏更新按钮。缺少缓存记录不代表组件没有发布版本，应以本次成功检查结果为准。新旧后台共用这一机制。
+
+### 同站检查与请求保护（manager 1.1.3 / 框架 2.0.4）
+
+更新源可以指向当前站点域名。后台版本检查写入本地缓存，使用 POST 和 CSRF 保护。jQuery 预处理与原生 XHR 包装同时启用时，只发送一个 `X-CSRF-Token`，避免浏览器合并成“令牌, 令牌”而被服务器拒绝；显式错误令牌仍会拒绝。
+
+更新接口 `server_version` 返回更新源 `config/version.php` 的真实版本，`version` 保留发布清单中可用升级版本的原有含义。后台另返回 PHP 比较结果 `server_compare`：0 表示相同，正数表示服务器较新，负数表示本地较新。相同版本显示“已是最新版本（服务器 v…）”；服务器较新但无可用发布包时如实提示，不伪造升级包，也不将成功检查标为失败。
+
+## 使用步骤

1. 在测试站核对本项目目录、元信息和所需扩展。
2. 在插件管理中检查安装条件、安装／活动状态，选择所需应用绑定及运行顺序。
3. 先执行只读展示，再在独占测试资源中验证保存或管理动作。
4. 检查模板风格、窄屏、资源地址、会话与失败反馈；不在正式数据上试验安装或恢复。

## 边界与维护

生成的check_*、pack/update/rollback是运行或发布资料，不能因为文档扫描而重建。管理员动作与公开更新服务边界分开；当前测试使用模拟源，不依赖失效旧网址。

[框架指南总览](../../README.md)
