# 框架功能

按功能逐篇阅读，先了解流程与工作示例，再查阅方法的参数、返回值和边界。

## 变量与执行

- [路径常量](runtime/constants.md)
- [全局变量](runtime/variables.md)
- [公共函数](runtime/functions.md)
- [执行流程](runtime/execution.md)
- [启动初始化](runtime/bootstrap.md)
- [请求调度](runtime/dispatch.md)
- [应用启动](runtime/application-start.md)
- [响应输出](runtime/response-lifecycle.md)
- [错误处理](runtime/errors.md)
- [类加载](runtime/class-loading.md)

## 设置管理

- [配置读取](config/config.md)
- [配置表单](config/config-forms.md)
- [配置保存](config/config-save.md)

## 路由与入口

- [路由机制](routing/routing.md)
- [路由格式](routing/route-formats.md)
- [模块入口](routing/route-modules.md)
- [地址生成](routing/links.md)
- [域名绑定](routing/domains.md)

## 数据库

- [数据库](database/database.md)
- [参数绑定](database/db-prepared.md)
- [事务控制](database/db-transactions.md)
- [SQL构造](database/db-builder.md)
- [查询缓存](database/db-cache.md)
- [数据库驱动](database/db-drivers.md)

## 缓存

- [缓存管理](cache/cache.md)
- [缓存锁](cache/cache-locks.md)
- [缓存编码](cache/cache-serialization.md)
- [缓存驱动](cache/cache-drivers.md)
- [缓存清理](cache/cache-cleanup.md)
- [Redis设置](../showcase/plugins/redis.md)

## 会话与安全

- [Session会话](session/session.md)
- [会话驱动](session/session-drivers.md)
- [安全认证](security/auth.md)
- [请求防护](security/security.md)
- [输入预算](security/input-budget.md)
- [附件管理](security/attachments.md)

## 应用接口

- [接口注册](api/api.md)
- [接口参数](api/api-arguments.md)
- [接口设置](api/api-settings.md)
- [接口域名](api/api-domains.md)
- [跨域访问](api/api-cors.md)
- [正文限制](api/api-body.md)

## 页面模板

- [页面模板](templates/templates.md)
- [模板变量](templates/template-values.md)
- [模板流程](templates/template-blocks.md)
- [扩展标签](templates/template-tags.md)
- [模板渲染](templates/template-render.md)
- [模板缓存](templates/template-cache.md)

## 文件与通用工具

- [文件管理](tools/files.md)
- [文件上传](tools/upload.md)
- [字符串处理](tools/strings.md)
- [图像处理](tools/images.md)
- [系统打包](tools/archives.md)
- [ZIP归档](tools/zip.md)
- [邮件发送](tools/email.md)
- [认证加密](tools/encrypt.md)
- [表格导出](tools/excel.md)
- [资源压缩](tools/minify.md)
- [反射机制](tools/reflection.md)
- [对象代理](tools/proxy.md)

## 公共类与函数

- [简繁字典](reference/chs2cht-dic.md)
- [函数目录](reference/function.md)
- [应用接口](reference/myapi.md)
- [解包工具](reference/myarchive.md)
- [附件索引](reference/myattachment.md)
- [登录凭据](reference/myauth.md)
- [基础抽象](reference/mybase.md)
- [缓存代理](reference/mycache.md)
- [Redis连接](../showcase/plugins/redis/myredis.md)
- [加载声明](reference/myclassloader.md)
- [设置管理](reference/myconfig.md)
- [基础控制](reference/mycontroller.md)
- [数据库代理](reference/mydb.md)
- [邮件工具](reference/myemail.md)
- [加密工具](reference/myencrypt.md)
- [表格工具](reference/myexcel.md)
- [异常处理](reference/myexception.md)
- [文件工具](reference/myfile.md)
- [图像工具](reference/myimg.md)
- [资源压缩](reference/myminify.md)
- [系统打包](reference/mypacker.md)
- [路径工具](reference/mypath.md)
- [对象代理](reference/myproxy.md)
- [反射工具](reference/myreflection.md)
- [请求读取](reference/myreq.md)
- [路由模块](reference/myrouter.md)
- [请求安全](reference/mysecurity.md)
- [控制核心](reference/mystep.md)
- [字符串](reference/mystring.md)
- [模板引擎](reference/mytemplate.md)
- [通用扩展](reference/mytrait.md)
- [上传工具](reference/myuploader.md)
- [归档工具](reference/myzip.md)
- [拼音转换](reference/pinyin.md)

## 数据库驱动

- [数据库协议](reference/database/interface-db.md)
- [参数化协议](reference/database/interface-db-prepared.md)
- [事务协议](reference/database/interface-db-transaction.md)
- [构造器协议](reference/database/interface-sql.md)
- [MSSQL](reference/database/mssql.md)
- [MySQL](reference/database/mysql.md)
- [simpleDB](reference/database/simpledb.md)
- [SQLite](reference/database/sqlite.md)

## 缓存驱动

- [缓存协议](reference/cache/interface-cache.md)
- [计算锁协议](reference/cache/interface-cache-lock.md)
- [命中协议](reference/cache/interface-cache-lookup.md)
- [缓存编码](reference/cache/mycachecodec.md)
- [文件缓存](reference/cache/mycache-file.md)
- [MSSQL缓存](reference/cache/mycache-mssql.md)
- [MySQL缓存](reference/cache/mycache-mysql.md)
- [Redis缓存](../showcase/plugins/redis/mycache-redis.md)

## Session处理器

- [会话协议](reference/session/interface-session.md)
- [文件会话](reference/session/sess-file.md)
- [MySQL会话](reference/session/sess-mysql.md)
- [框架会话](reference/session/sess-mystep.md)
- [Redis会话](../showcase/plugins/redis/sess-redis.md)

[文档总览](../README.md)

## 普通会员
