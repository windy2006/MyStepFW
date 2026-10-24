# Admin：新管理后台

修订日期：2026-10-23。现有应用介绍专栏。

## 定位和适用场景

使用同一框架机制的管理应用，核心类在应用根目录，公共辅助声明在已有 lib.php，功能模块与模板分别归入 module/、template/。与原后台共用框架设置、插件注册和API策略。

## 当前目录及主要文件

源码位于`app/Admin/`。以下为当前源码层级，不表示所有文件都会自动执行：

```text
app/Admin/
├── Admin.class.php
├── README.md
├── asset/
├── config.php
├── config/
├── index.php
├── info.php
├── language/
├── lib.php
├── module/
├── plugin.php
├── plugin_layout.php
├── route.php
├── setting_expand.php
├── template/
```

## 入口与执行过程

Admin.class.php 使用 lib.php 中的受信页面定义选择 module/ 回调；POST动作验证字段、CSRF和管理员，再调用module中的处理。

## 功能脚本与模板对应

| 功能脚本 | 默认风格模板 |
| --- | --- |
| `module/info/error.php` | `template/default/info_error.tpl` |
| `module/setting/main.php` | `template/default/setting_main.tpl` |
| `module/function/api.php` | `template/default/function_api.tpl` |
| `module/guide.php` | `template/default/guide.tpl` |

页面的读取、动作与模板赋值在对应模块，复用的模板片段仍保留各自文件。AdminStore、AdminService 是 lib.php 内的辅助声明，不再是根目录下的独立文件。

## 配置、模板和资源

设置、语言、模板与asset/files等资源保持在本项目内；共用框架服务通过当前应用上下文调用。配置值与后台字段结构分别维护，表单客户端用checkForm，服务端仍校验权限、方法和CSRF。

## 使用步骤

1. 在测试站核对本项目目录、元信息和所需扩展。
2. 按已登记路由访问应用入口，并选择对应功能。
3. 先执行只读展示，再在独占测试资源中验证保存或管理动作。
4. 检查模板风格、窄屏、资源地址、会话与失败反馈；不在正式数据上试验安装或恢复。

## 边界与维护

模块与模板按功能对应。应用实例和数据作用域独立；此项目的专属目录、表结构或页面清单不自动成为其他应用的通用要求。

[框架指南总览](../../README.md)
