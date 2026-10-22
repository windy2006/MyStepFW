# MyStepFW 文档目录

整理日期：2026-10-22。同类文档已合并为单一主题；规范、开发教程与具体应用／插件说明分开维护。后续修改直接更新对应主题，不继续新增同题修复记录。

## 后台阅读入口

新后台 `/Admin/guide` 与原后台 `/console/guide`（原后台路径可配置）共用分类文档目录。默认显示本文总览，可切换执行流程、应用／插件规范与教程、核心机制、前端、维护及测试说明；具体应用、插件和本机部署资料单独分类。文档直接从现有Markdown读取，更新原文后重新打开即可。

目录项、正文内的跨文档链接及章节导航均保留当前后台和路由模式。源码引用显示为路径提示，不直接开放PHP源码；未知文档、非法参数和越界请求返回404。所有页面仍由后台登录流程保护，无文档编辑或保存功能。

## 通用编写规范

- [app 编写规范](../app/README.md)：目录归属、同名核心类继承、模块与模板对应、配置及脚本触发条件。
- [plugin 编写规范](../plugin/README.md)：主类、初始化、生命周期、钩子、路由和前置脚本。

## 框架核心与公共能力

| 主题 | 内容 |
| --- | --- |
| [执行流程](framework/execution.md) | 请求主干、路由、认证、输出及提前退出 |
| [路由、配置与代理](framework/routing-config-proxy.md) | 声明、字面路径、配置读写、代理初始化和引用 |
| [路径规范](framework/paths.md) | 网站根相对配置、运行时绝对路径与类加载 |
| [数据库](framework/database.md) | 历史／可选接口、参数化、事务、SQL 构建与驱动边界 |
| [缓存](framework/cache.md) | lookup、计算锁、序列化、后端、失效与清理 |
| [Session](framework/session.md) | 会话处理器、锁、过期、Cookie 与在线统计 |
| [模板](framework/templates.md) | 标签、嵌套、转义、编译／页面缓存和旧 switch 兼容 |
| [接口与扩展](framework/extensions.md) | myApi、myTrait、myBase、myExcel |
| [通用工具](framework/utilities.md) | 文件、请求、上传、图片、归档、字符集、邮件及加密 |
| [请求安全与附件](framework/security-attachments.md) | 输入预算、CSRF、方法、来源、附件索引及访问边界 |
| [PHP 运行时加固](framework/php-runtime-hardening.md) | 内核风险边界、输入预算、服务器内存策略及启动配置示例 |
| [前端开发文档](../static/README.md) | static 中的变量、加载流程、公共函数、表单规则、扩展与示例 |
| [公共 JavaScript 维护记录](framework/javascript.md) | global.js、checkForm.js 的历史修复、兼容性及验证说明 |
| [依赖维护](framework/dependencies.md) | Composer 锁定组件、vendor 用途及保留决定 |
| [lib 索引与 PHPDoc](framework/phpdoc.md) | 当前 52 文件索引、类型／回调／异常说明和检查工具 |
| [公共变更维护与执行链复核](framework/maintenance.md) | 跨框架、应用、插件调用同步，本轮修复与后续改进优先级 |

## 框架开发教程

1. [开发第一个应用](guides/first-application.md)
2. [开发第一个插件](guides/first-plugin.md)

Hello 和 hellonote 是教程标识，不代表站点已安装这些项目。

## 具体项目与本机资料

- [应用专属文档目录](applications/README.md)：Admin、CMS、myStep 及示例应用。
- [现有插件结构与维护](plugins/existing-plugins.md)：六个插件的模块、模板和特殊脚本。
- [当前站点部署与入口](site/deployment.md)：本机服务、域名与入口资料。

## 验证与本次合并

- [tests 文件汇总与运行说明](../tests/README.md)：按 15 个功能目录分类的测试／夹具逐项用途、运行命令、依赖、环境变量及真实服务／替身边界。
- [文档迁移与合并对照](consolidation.md)：旧文档去向、最新内容取舍及本次只读核验范围。

主题正文中保留的带日期测试记录属于历史证据，不作为当前全项目通过数；SQL Server 等未完成真实服务验证的限制继续保留。许可证、第三方组件随附文档和功能测试样本仍按各自用途存放。
