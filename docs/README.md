# 迈思PHP框架详细指南

本指南沿原手册的学习顺序组织，并保留独立主题和逐方法说明。首次阅读建议从[原手册学习路径](basics/08-handbook-reading.md)开始：先建立设计与执行的整体认识，再用完整示例验证，最后按类名或方法名查询接口。

编写功能前先阅读[执行流程](backend/runtime/execution.md)与[命名规范](basics/09-naming.md)，确定代码的执行位置及文件、变量和接口名称。

## 目录与阅读顺序

1. **前言与基础配置**：[功能选用](basics/07-design.md)、[安装](basics/02-installation.md)、[运行环境](basics/06-php85.md)、[开发规范](basics/05-standards.md)、[命名规范](basics/09-naming.md)。
2. **功能模块**：[目录](backend/foundation.md)，包括常量变量、公共函数、抽象类、配置、请求、数据库、错误、文件、字符串、缓存及其他工具。
3. **核心模块**：[目录](backend/core.md)，先读[执行流程](backend/runtime/execution.md)，再阅读路由、API、页面模板和控制核心。
4. **应用编写**：[章节目录](development/app/README.md)、[完整Hello应用](development/app/first-app.md)、[应用目录规范](../app/README.md)。
5. **插件制作**：[章节目录](development/plugins/README.md)、[完整hellonote插件](development/plugins/first-plugin.md)、[插件规范](../plugin/README.md)。
6. **浏览器前端**：[JavaScript总览](frontend/README.md)，统一介绍本地jQuery、Bootstrap、公开变量、DOM、链接、checkForm、AJAX与编辑器。
7. **项目介绍**：[应用](showcase/applications/README.md)、[插件](showcase/plugins/README.md)、[真实更新记录](history/README.md)。

功能模块和核心模块是学习层次，不是把应用代码搬进lib的理由。每个功能仍沿当前目录、同名核心、module/template以及受控入口组织。

## 从完整示例开始

以下例子都有准备条件、完整代码、预期结果和关键说明，使用独占临时目录或SQLite内存库：

| 学习目标 | 示例 |
| --- | --- |
| 配置读取、合并和并发保存 | [配置实例](backend/config/config-example.md) |
| 绑定查询、SQLBuilder和CRUD | [数据库实例](backend/database/db-example.md) |
| 嵌套事务、false返回和失败回滚 | [事务实例](backend/database/transaction-example.md) |
| 精确命中、remember和清理范围 | [缓存实例](backend/cache/cache-example.md) |
| 三层模板的布局及条件循环 | [主/子模板实例](backend/templates/template-example.md) |
| 编译期回调与渲染期数据 | [扩展标签实例](backend/templates/tag-example.md) |
| 钩子的顺序、逆序和正文管道 | [控制器钩子实例](backend/runtime/hooks-example.md) |
| 注册、原始调用与JSON编码 | [API实例](backend/api/api-example.md) |
| 文件边界及原始文本/HTML | [文件实例](backend/tools/files-example.md) |
| 密码摘要与可恢复加密 | [认证实例](backend/security/auth-example.md) |

示例是可复制的源代码，不提供直接在正式站点执行的按钮。框架已经初始化的模块复用当前请求对象；独立测试脚本需要先加载类库。不要为测试清空全站缓存、修改真实账户或复制未准备的业务表名。

## 方法说明与查找

左侧保留所有主题、公共类及驱动协议入口，逐项列出真实声明、参数、默认值、返回值、异常、前提与调用位置；当前参考资料覆盖59个源文件、977个直接声明的方法/函数。主题篇讲工作流，参考页用于查精确接口，两者配合使用。

输入搜索词后查找标题、分类与正文，支持中文、类名、方法名以及多个空格分隔关键词。点击目录、正文文档链接或结果时只更新右侧阅读区，保留左侧滚动和分类开合；浏览器前进/后退恢复文档及阅读位置。代码使用本地高亮引擎，复制按钮只复制代码文本；复制失败时仍可手动选择。

## 使用前提

当前框架最低运行版本为PHP8.5.12。服务器变量和模板编译与浏览器JavaScript分开；完整页面预置公开setting/language，独立页面另外等待就绪。API、文件、跳转、缓存命中不保证执行同一套页面钩子。具体项目和处理规则只在对应专栏说明，不混入通用框架的默认要求。
