# 迈思PHP框架详细指南

本指南按功能分类，每个主题独立文件存放。标题直接说明功能；所属栏目区分框架功能、浏览器前端、应用开发、插件开发及现有项目介绍。

## 阅读与搜索

点击目录、正文中的其他文档链接或搜索结果，只更新右侧阅读区；左侧目录的滚动位置和分类展开状态保持不变。右侧正文单独滚动，浏览器前进／后退可以恢复文档和阅读位置，带章节锚点的链接直接定位对应小节。目录开合及位置也会在当前标签页内记住。

左侧“搜索文档”同时查找标题、分类和正文，输入后自动搜索，也可以按回车。支持中文、类名、方法名和多个空格分隔的关键词；多个关键词需同时命中，标题匹配优先。点击结果打开正文并标记关键词；按Esc或“清除”取消搜索。搜索只包含当前指南，不包含备份资料。

## 阅读方式

第一次使用先看基础说明与执行流程，再完成第一个应用或插件实例。功能篇解释生命周期、设置、输入输出和组合用法；类与函数篇逐项给出声明、参数、默认值、返回值、异常及明确示例。公共类和插件共享接口覆盖53个源文件、899个直接声明的方法／函数。

“浏览器前端”统一介绍 JavaScript，并说明所采用的本地 jQuery 3.6.1 和 Bootstrap 4.6.2。各应用可以按实际配置选择其他已保留版本，详见该栏的总体介绍。框架的页面模板由服务器生成，归入“页面模板”。

示例分别注明当前应用上下文、临时文件、数据库或外部服务的前提。调用形式与完整工作流配合阅读；不要将整页片段直接运行在正式业务数据上。

## 基础说明

- [基础说明](basics/README.md)
- [整体介绍与框架组成](basics/01-guide-basics.md)
- [安装环境与基础配置](basics/02-installation.md)
- [功能设置与管理入口](basics/03-management.md)
- [框架概要与执行主线](basics/04-architecture.md)
- [开发规范与目录归属](basics/05-standards.md)

## 变量与执行

- [框架功能总览](backend/README.md)
- [目录常量与路径](backend/runtime/constants.md)
- [主要变量与请求上下文](backend/runtime/variables.md)
- [公共函数逐项用法](backend/runtime/functions.md)
- [请求总流程与分支](backend/runtime/execution.md)
- [init：类加载、预算与安装](backend/runtime/bootstrap.md)
- [go：前置脚本、域名与资源](backend/runtime/dispatch.md)
- [setPara与start：服务、会话和认证](backend/runtime/application-start.md)
- [show、end与提前退出](backend/runtime/response-lifecycle.md)
- [错误、异常与日志生命周期](backend/runtime/errors.md)
- [类加载目录、索引与特别指定](backend/runtime/class-loading.md)

## 设置管理

- [myConfig装载、读取与分组合并](backend/config/config.md)
- [配置表单结构、多语种与字段转换](backend/config/config-forms.md)
- [配置原子保存、版本比较与冲突](backend/config/config-save.md)

## 路由与入口

- [路由声明、编译及匹配优先级](backend/routing/routing.md)
- [命名格式、捕获参数与字面路径](backend/routing/route-formats.md)
- [注册模块、页面模块与专项入口](backend/routing/route-modules.md)
- [三种URL模式、查询参数与子目录](backend/routing/links.md)
- [域名绑定、虚拟前缀与核心选择](backend/routing/domains.md)

## 数据库

- [数据库代理、驱动与能力接口](backend/database/database.md)
- [参数化查询、读取方式与返回值](backend/database/db-prepared.md)
- [事务、回调、保存点与失败](backend/database/db-transactions.md)
- [SQLBuilder条件、字段和分页](backend/database/db-builder.md)
- [SQL查询缓存与写后失效](backend/database/db-cache.md)
- [数据库驱动差异与真实环境边界](backend/database/db-drivers.md)

## 缓存

- [缓存协议、实例与命中状态](backend/cache/cache.md)
- [计算锁、防击穿与锁超时](backend/cache/cache-locks.md)
- [序列化预算、对象与命名空间](backend/cache/cache-serialization.md)
- [File、MySQL和MSSQL缓存驱动](backend/cache/cache-drivers.md)
- [过期回收、全量清理与引用保留](backend/cache/cache-cleanup.md)

## 会话与安全

- [Session生命周期、锁和并发](backend/session/session.md)
- [Session驱动、表结构与兼容](backend/session/session-drivers.md)
- [密码、旧散列迁移与记住登录](backend/security/auth.md)
- [会话CSRF、来源、方法与后台判定](backend/security/security.md)
- [输入预算、请求体积与启动保护](backend/security/input-budget.md)
- [附件落盘索引、下载与删除](backend/security/attachments.md)

## 应用接口

- [HTTP API登记与对象接口](backend/api/api.md)
- [API参数位置、保留字段与格式](backend/api/api-arguments.md)
- [API全局参数与逐接口行内管理](backend/api/api-settings.md)
- [API专属域名与地址生成](backend/api/api-domains.md)
- [API跨域开关、预检和CSRF边界](backend/api/api-cors.md)
- [API全部正文类型的统一上限](backend/api/api-body.md)

## 页面模板

- [模板实例、风格和模块对应](backend/templates/templates.md)
- [模板变量、HTML与URL转义](backend/templates/template-values.md)
- [条件、循环、switch和随机块](backend/templates/template-blocks.md)
- [扩展标签、子模板和依赖](backend/templates/template-tags.md)
- [页面渲染、缓冲与输出](backend/templates/template-render.md)
- [模板编译、页面缓存和vary](backend/templates/template-cache.md)

## 文件与通用工具

- [文件路径、读写和流式响应](backend/tools/files.md)
- [真实上传、容量和结果检查](backend/tools/upload.md)
- [字符集、JSON、XML和文本处理](backend/tools/strings.md)
- [图片解码预算、缩略图和绘图](backend/tools/images.md)
- [打包、暂存、解包和失败处理](backend/tools/archives.md)
- [ZIP创建与受控解包](backend/tools/zip.md)
- [邮件组装、附件和SMTP](backend/tools/email.md)
- [认证加密与旧格式兼容](backend/tools/encrypt.md)
- [XML Excel工作簿与流式导出](backend/tools/excel.md)
- [资源整合、压缩与依赖指纹](backend/tools/minify.md)
- [类、方法及注释反射](backend/tools/reflection.md)
- [对象代理、别名和引用属性](backend/tools/proxy.md)

## 公共类与函数

- [chs2cht.dic · PHP简繁字典](backend/reference/chs2cht-dic.md)
- [function · PHP公共函数](backend/reference/function.md)
- [myApi · 注册接口与HTTP协议](backend/reference/myapi.md)
- [myArchive · 解包暂存与发布](backend/reference/myarchive.md)
- [myAttachment · 附件索引和定位](backend/reference/myattachment.md)
- [myAuth · 密码和登录凭据](backend/reference/myauth.md)
- [myBase · 基础抽象类](backend/reference/mybase.md)
- [myCache · 缓存代理与能力](backend/reference/mycache.md)
- [myClassLoader · 类加载声明](backend/reference/myclassloader.md)
- [myConfig · 配置读取、合并和保存](backend/reference/myconfig.md)
- [myController · 控制器基础资源](backend/reference/mycontroller.md)
- [myDb · 数据库代理与SQL构建](backend/reference/mydb.md)
- [myEmail · 邮件组装和发送](backend/reference/myemail.md)
- [myEncrypt · 字符串与文件认证加密](backend/reference/myencrypt.md)
- [myExcel · XML Spreadsheet导出](backend/reference/myexcel.md)
- [myException · 错误处理与日志](backend/reference/myexception.md)
- [myFile · 本地文件、路径和远程读取](backend/reference/myfile.md)
- [myImg · 图片处理与绘图](backend/reference/myimg.md)
- [myMinify · JS/CSS整合](backend/reference/myminify.md)
- [myPacker · 框架包打包与安装](backend/reference/mypacker.md)
- [myPath · 路径规范化](backend/reference/mypath.md)
- [myProxy · 代理对象与别名](backend/reference/myproxy.md)
- [myReflection · 声明和注释反射](backend/reference/myreflection.md)
- [myReq · 请求、Cookie和Session](backend/reference/myreq.md)
- [myRouter · 规则、格式与匹配](backend/reference/myrouter.md)
- [mySecurity · 输入预算和CSRF](backend/reference/mysecurity.md)
- [myStep · 框架请求控制](backend/reference/mystep.md)
- [myString · 字符串与字符集](backend/reference/mystring.md)
- [myTemplate · 模板实例和编译](backend/reference/mytemplate.md)
- [myTrait · 类的通用扩展](backend/reference/mytrait.md)
- [myUploader · HTTP上传](backend/reference/myuploader.md)
- [myZip · ZIP归档](backend/reference/myzip.md)
- [pinyin · 拼音和缩写](backend/reference/pinyin.md)

## 数据库驱动

- [interface_db · interface_db 数据库驱动／协议](backend/reference/database/interface-db.md)
- [interface_db_prepared · interface_db_prepared 数据库驱动／协议](backend/reference/database/interface-db-prepared.md)
- [interface_db_transaction · interface_db_transaction 数据库驱动／协议](backend/reference/database/interface-db-transaction.md)
- [interface_sql · interface_sql 数据库驱动／协议](backend/reference/database/interface-sql.md)
- [MSSQL · mssql 数据库驱动／协议](backend/reference/database/mssql.md)
- [MySQL · mysql 数据库驱动／协议](backend/reference/database/mysql.md)
- [simpleDB · simpleDB 数据库驱动／协议](backend/reference/database/simpledb.md)
- [SQLite · sqlite 数据库驱动／协议](backend/reference/database/sqlite.md)

## 缓存驱动

- [interface_cache · interface_cache 缓存驱动／协议](backend/reference/cache/interface-cache.md)
- [interface_cache_lock · interface_cache_lock 缓存驱动／协议](backend/reference/cache/interface-cache-lock.md)
- [interface_cache_lookup · interface_cache_lookup 缓存驱动／协议](backend/reference/cache/interface-cache-lookup.md)
- [myCacheCodec · myCacheCodec 缓存驱动／协议](backend/reference/cache/mycachecodec.md)
- [myCache_File · myCache_File 缓存驱动／协议](backend/reference/cache/mycache-file.md)
- [myCache_MSSQL · myCache_MSSQL 缓存驱动／协议](backend/reference/cache/mycache-mssql.md)
- [myCache_MySQL · myCache_MySQL 缓存驱动／协议](backend/reference/cache/mycache-mysql.md)

## Session处理器

- [interface_session · interface_session 会话处理器](backend/reference/session/interface-session.md)
- [sess_file · sess_file 会话处理器](backend/reference/session/sess-file.md)
- [sess_mysql · sess_mysql 会话处理器](backend/reference/session/sess-mysql.md)
- [sess_mystep · sess_mystep 会话处理器](backend/reference/session/sess-mystep.md)

## 浏览器前端

- [浏览器前端](frontend/README.md)
- [脚本加载、依赖与就绪](frontend/javascript/frontend.md)
- [主要变量与公开设置](frontend/javascript/frontend-runtime.md)
- [DOM查找与类型判断](frontend/javascript/js-dom.md)
- [字符串、日期、数组和金额扩展](frontend/javascript/js-types.md)
- [异步就绪、初始化队列与重试](frontend/javascript/js-ready.md)
- [路由地址、参数和批量链接](frontend/javascript/js-urls.md)
- [加载提示、窗口、滚动与复制](frontend/javascript/js-ui.md)
- [随机值、编码、水印和调试](frontend/javascript/js-data.md)
- [checkForm整体流程](frontend/javascript/frontend-forms.md)
- [need规则、len与字段示例](frontend/javascript/js-need.md)
- [jquery.addon插件与资源加载](frontend/javascript/js-addons.md)
- [简繁转换与日期辅助](frontend/javascript/js-text-calendar.md)
- [security.js与AJAX CSRF](frontend/javascript/security.md)
- [编辑器初始化与插件按钮](frontend/javascript/js-editor.md)
- [global.js函数详解](frontend/javascript/global-reference.md)
- [checkForm.js函数详解](frontend/javascript/checkform-reference.md)

## 应用开发

- [应用开发目录](development/app/README.md)
- [建立应用](development/app/app-01.md)
- [应用设置](development/app/app-02.md)
- [应用设置补充](development/app/app-03.md)
- [核心扩展](development/app/app-04.md)
- [功能脚本与模板对应](development/app/app-05.md)
- [框架路由与API](development/app/app-06.md)
- [执行顺序](development/app/app-07.md)
- [脚本加载](development/app/app-08.md)
- [模板体系](development/app/app-09.md)
- [缓存系统](development/app/app-10.md)
- [框架钩子](development/app/app-11.md)
- [链接设定](development/app/app-12.md)
- [域名绑定](development/app/app-13.md)
- [应用文件、目录和脚本规范](../app/README.md)
- [完整实例：第一个应用](development/app/first-app.md)

## 插件开发

- [插件开发目录](development/plugins/README.md)
- [基础说明与生效条件](development/plugins/plugin-01.md)
- [组成结构和命名](development/plugins/plugin-02.md)
- [安装检查与卸载](development/plugins/plugin-03.md)
- [前台交互和资源](development/plugins/plugin-04.md)
- [API与模板模块](development/plugins/plugin-05.md)
- [钩子调用](development/plugins/plugin-06.md)
- [顺序、优先脚本、宿主布局与升级](development/plugins/plugin-07.md)
- [编辑器扩展](development/plugins/plugin-08.md)
- [插件文件、目录和生命周期规范](../plugin/README.md)
- [完整实例：第一个插件](development/plugins/first-plugin.md)

## 插件接口

- [interface_plugin · 插件公共生命周期和辅助函数](development/plugins/reference/interface-plugin.md)

## 现有应用介绍

- [现有应用介绍目录](showcase/applications/README.md)
- [myStep · 框架原管理应用](showcase/applications/mystep.md)
- [Admin · 新管理后台](showcase/applications/admin.md)
- [CMS · 内容管理系统](showcase/applications/cms.md)
- [Document · 公共类演示与声明说明](showcase/applications/document.md)
- [Sample · 路由与模板组合示例](showcase/applications/sample.md)
- [test · 执行流程观察应用](showcase/applications/test.md)

## 现有插件介绍

- [现有插件介绍目录](showcase/plugins/README.md)
- [manager · 系统打包与更新](showcase/plugins/manager.md)
- [badReq · 前置请求过滤](showcase/plugins/badreq.md)
- [myBackup · 数据库备份与维护](showcase/plugins/mybackup.md)
- [frontend · 前端组件版本选择](showcase/plugins/frontend.md)
- [memcached · 应用缓存后端替换](showcase/plugins/memcached.md)
- [sample · 插件机制演示](showcase/plugins/sample.md)

## 更新历史

- [更新历史](history/README.md)
- [2026年更新历史](history/2026.md)
- [2023年更新历史](history/2023.md)
- [2022年更新历史](history/2022.md)
- [2021年更新历史](history/2021.md)
- [2020年更新历史](history/2020.md)
- [2019年更新历史](history/2019.md)

## 文档存放约定

各分类目录页位于对应项目的首位。应用和插件规范仍分别存放于app/README.md、plugin/README.md。旧版资料单独保存在docs/backup/，不进入本指南，也不随GitHub发布。更新历史按真实发布提交整理，日期与提交链接可追溯。
