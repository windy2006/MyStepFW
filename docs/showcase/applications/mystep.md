# myStep：框架原管理应用

## 定位和适用场景

框架登录、参数、类加载、域名、应用、插件、缓存、附件及指南管理。它是框架核心特例：执行引擎在lib/myStep，管理模块在本目录。

## 当前目录及主要文件

源码位于`app/myStep/`。以下为当前源码层级，不表示所有文件都会自动执行：

```text
app/myStep/
├── asset/
├── config.php
├── config/
├── global.php
├── index.php
├── info.php
├── language/
├── lib.php
├── menu.json
├── module/
├── plugin.php
├── route.php
├── sample/
├── setting_expand.php
├── template/
```

## 入口与执行过程

route.php → 框架后台登录检查 → module/功能脚本 → template/对应模板。后台路径来自gen.path_admin。

## 配置、模板和资源

设置、语言、模板与asset/files等资源保持在本项目内；共用框架服务通过当前应用上下文调用。配置值与后台字段结构分别维护，表单客户端用checkForm，服务端仍校验权限、方法和CSRF。

## 使用步骤

1. 在测试站核对本项目目录、元信息和所需扩展。
2. 按已登记路由访问应用入口，并选择对应功能。
3. 先执行只读展示，再在独占测试资源中验证保存或管理动作。
4. 检查模板风格、窄屏、资源地址、会话与失败反馈；不在正式数据上试验安装或恢复。

## 指南阅读与搜索

两个后台共用本应用 lib.php 内的 MyStepGuide 辅助声明及 asset/guide.js。API 管理装配直接位于 module/function/api.php，MyStepApiManager 公共声明也在 lib.php，不另设根目录功能文件。文档展示继续使用各自的guide、guide_group与guide_article模板，遵循风格和default回退；应用可覆盖正文片段模板。普通链接保留原地址，启用浏览器脚本后改为局部加载。

搜索只读取当前受信目录中的文档，返回当前后台的链接和纯文本摘要；不会搜索旧备份或使用请求参数选择磁盘文件。局部阅读与搜索均经过原后台登录校验。并发请求采用取消与序号校验，失效登录、网络错误和不存在的文档保留当前正文并显示反馈。

## 边界与维护

模块与模板按功能对应。应用实例和数据作用域独立；此项目的专属目录、表结构或页面清单不自动成为其他应用的通用要求。

[框架指南总览](../../README.md)
