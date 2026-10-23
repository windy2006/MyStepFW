# CMS：内容管理系统

修订日期：2026-10-23。现有应用介绍专栏。

## 定位和适用场景

文章、栏目、链接、会员、多站点、前后台模板与业务管理。其表结构和专属配置不是所有框架应用的必选项。

## 当前目录及主要文件

源码位于`app/CMS/`。以下为当前源码层级，不表示所有文件都会自动执行：

```text
app/CMS/
├── CMS.class.php
├── asset/
├── atom.php
├── config.php
├── config/
├── config_admin_cms.php
├── config_default.php
├── global.php
├── index.php
├── info.php
├── install/
├── language/
├── lib.php
├── module/
├── plugin.php
├── plugin_addon.php
├── route.php
├── rss.php
├── setting_expand.php
├── sitemap.php
├── template/
├── website/
```

## 入口与执行过程

CMS.class.php与lib.php建立本应用能力；route.php选择前台／admin_cms入口，module中按业务功能选择对应template。

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
