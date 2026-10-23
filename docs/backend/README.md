# 框架功能总览

按功能逐篇阅读，先了解流程与工作示例，再查阅方法的参数、返回值和边界。

## 变量与执行

- [目录常量与路径](runtime/constants.md)
- [主要变量与请求上下文](runtime/variables.md)
- [公共函数逐项用法](runtime/functions.md)
- [请求总流程与分支](runtime/execution.md)
- [init：类加载、预算与安装](runtime/bootstrap.md)
- [go：前置脚本、域名与资源](runtime/dispatch.md)
- [setPara与start：服务、会话和认证](runtime/application-start.md)
- [show、end与提前退出](runtime/response-lifecycle.md)
- [错误、异常与日志生命周期](runtime/errors.md)
- [类加载目录、索引与特别指定](runtime/class-loading.md)

## 设置管理

- [myConfig装载、读取与分组合并](config/config.md)
- [配置表单结构、多语种与字段转换](config/config-forms.md)
- [配置原子保存、版本比较与冲突](config/config-save.md)

## 路由与入口

- [路由声明、编译及匹配优先级](routing/routing.md)
- [命名格式、捕获参数与字面路径](routing/route-formats.md)
- [注册模块、页面模块与专项入口](routing/route-modules.md)
- [三种URL模式、查询参数与子目录](routing/links.md)
- [域名绑定、虚拟前缀与核心选择](routing/domains.md)

## 数据库

- [数据库代理、驱动与能力接口](database/database.md)
- [参数化查询、读取方式与返回值](database/db-prepared.md)
- [事务、回调、保存点与失败](database/db-transactions.md)
- [SQLBuilder条件、字段和分页](database/db-builder.md)
- [SQL查询缓存与写后失效](database/db-cache.md)
- [数据库驱动差异与真实环境边界](database/db-drivers.md)

## 缓存

- [缓存协议、实例与命中状态](cache/cache.md)
- [计算锁、防击穿与锁超时](cache/cache-locks.md)
- [序列化预算、对象与命名空间](cache/cache-serialization.md)
- [File、MySQL和MSSQL缓存驱动](cache/cache-drivers.md)
- [过期回收、全量清理与引用保留](cache/cache-cleanup.md)

## 会话与安全

- [Session生命周期、锁和并发](session/session.md)
- [Session驱动、表结构与兼容](session/session-drivers.md)
- [密码、旧散列迁移与记住登录](security/auth.md)
- [会话CSRF、来源、方法与后台判定](security/security.md)
- [输入预算、请求体积与启动保护](security/input-budget.md)
- [附件落盘索引、下载与删除](security/attachments.md)

## 应用接口

- [HTTP API登记与对象接口](api/api.md)
- [API参数位置、保留字段与格式](api/api-arguments.md)
- [API全局参数与逐接口行内管理](api/api-settings.md)
- [API专属域名与地址生成](api/api-domains.md)
- [API跨域开关、预检和CSRF边界](api/api-cors.md)
- [API全部正文类型的统一上限](api/api-body.md)

## 页面模板

- [模板实例、风格和模块对应](templates/templates.md)
- [模板变量、HTML与URL转义](templates/template-values.md)
- [条件、循环、switch和随机块](templates/template-blocks.md)
- [扩展标签、子模板和依赖](templates/template-tags.md)
- [页面渲染、缓冲与输出](templates/template-render.md)
- [模板编译、页面缓存和vary](templates/template-cache.md)

## 文件与通用工具

- [文件路径、读写和流式响应](tools/files.md)
- [真实上传、容量和结果检查](tools/upload.md)
- [字符集、JSON、XML和文本处理](tools/strings.md)
- [图片解码预算、缩略图和绘图](tools/images.md)
- [打包、暂存、解包和失败处理](tools/archives.md)
- [ZIP创建与受控解包](tools/zip.md)
- [邮件组装、附件和SMTP](tools/email.md)
- [认证加密与旧格式兼容](tools/encrypt.md)
- [XML Excel工作簿与流式导出](tools/excel.md)
- [资源整合、压缩与依赖指纹](tools/minify.md)
- [类、方法及注释反射](tools/reflection.md)
- [对象代理、别名和引用属性](tools/proxy.md)

## 公共类与函数

- [chs2cht.dic · PHP简繁字典](reference/chs2cht-dic.md)
- [function · PHP公共函数](reference/function.md)
- [myApi · 注册接口与HTTP协议](reference/myapi.md)
- [myArchive · 解包暂存与发布](reference/myarchive.md)
- [myAttachment · 附件索引和定位](reference/myattachment.md)
- [myAuth · 密码和登录凭据](reference/myauth.md)
- [myBase · 基础抽象类](reference/mybase.md)
- [myCache · 缓存代理与能力](reference/mycache.md)
- [myClassLoader · 类加载声明](reference/myclassloader.md)
- [myConfig · 配置读取、合并和保存](reference/myconfig.md)
- [myController · 控制器基础资源](reference/mycontroller.md)
- [myDb · 数据库代理与SQL构建](reference/mydb.md)
- [myEmail · 邮件组装和发送](reference/myemail.md)
- [myEncrypt · 加密与兼容数据](reference/myencrypt.md)
- [myExcel · XML Spreadsheet导出](reference/myexcel.md)
- [myException · 错误处理与日志](reference/myexception.md)
- [myFile · 本地文件、路径和远程读取](reference/myfile.md)
- [myImg · 图片处理与绘图](reference/myimg.md)
- [myMinify · JS/CSS整合](reference/myminify.md)
- [myPacker · 框架包打包与安装](reference/mypacker.md)
- [myPath · 路径规范化](reference/mypath.md)
- [myProxy · 代理对象与别名](reference/myproxy.md)
- [myReflection · 声明和注释反射](reference/myreflection.md)
- [myReq · 请求、Cookie和Session](reference/myreq.md)
- [myRouter · 规则、格式与匹配](reference/myrouter.md)
- [mySecurity · 输入预算和CSRF](reference/mysecurity.md)
- [myStep · 框架请求控制](reference/mystep.md)
- [myString · 字符串与字符集](reference/mystring.md)
- [myTemplate · 模板实例和编译](reference/mytemplate.md)
- [myTrait · 类的通用扩展](reference/mytrait.md)
- [myUploader · HTTP上传](reference/myuploader.md)
- [myZip · ZIP归档](reference/myzip.md)
- [pinyin · 拼音和缩写](reference/pinyin.md)

## 数据库驱动

- [interface_db · interface_db 数据库驱动／协议](reference/database/interface-db.md)
- [interface_db_prepared · interface_db_prepared 数据库驱动／协议](reference/database/interface-db-prepared.md)
- [interface_db_transaction · interface_db_transaction 数据库驱动／协议](reference/database/interface-db-transaction.md)
- [interface_sql · interface_sql 数据库驱动／协议](reference/database/interface-sql.md)
- [MSSQL · mssql 数据库驱动／协议](reference/database/mssql.md)
- [MySQL · mysql 数据库驱动／协议](reference/database/mysql.md)
- [simpleDB · simpleDB 数据库驱动／协议](reference/database/simpledb.md)
- [SQLite · sqlite 数据库驱动／协议](reference/database/sqlite.md)

## 缓存驱动

- [interface_cache · interface_cache 缓存驱动／协议](reference/cache/interface-cache.md)
- [interface_cache_lock · interface_cache_lock 缓存驱动／协议](reference/cache/interface-cache-lock.md)
- [interface_cache_lookup · interface_cache_lookup 缓存驱动／协议](reference/cache/interface-cache-lookup.md)
- [myCacheCodec · myCacheCodec 缓存驱动／协议](reference/cache/mycachecodec.md)
- [myCache_File · myCache_File 缓存驱动／协议](reference/cache/mycache-file.md)
- [myCache_MSSQL · myCache_MSSQL 缓存驱动／协议](reference/cache/mycache-mssql.md)
- [myCache_MySQL · myCache_MySQL 缓存驱动／协议](reference/cache/mycache-mysql.md)

## Session处理器

- [interface_session · interface_session 会话处理器](reference/session/interface-session.md)
- [sess_file · sess_file 会话处理器](reference/session/sess-file.md)
- [sess_mysql · sess_mysql 会话处理器](reference/session/sess-mysql.md)
- [sess_mystep · sess_mystep 会话处理器](reference/session/sess-mystep.md)

[文档总览](../README.md)
