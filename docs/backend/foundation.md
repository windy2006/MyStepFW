# 功能模块：基础能力与工作示例

这一组延续原手册的基础类讲解方式：先说明解决的问题和数据形状，再展示一次完整调用，最后查逐方法参数。框架已初始化后复用已有服务；独立示例另建测试资源。

## 章节目录

| 原主题 | 现行专题与方法 | 完整实例 |
| --- | --- | --- |
| 常量变量 | [路径](runtime/constants.md)、[请求上下文](runtime/variables.md) | [按流程使用变量](runtime/execution.md) |
| 功能函数 | [公共函数](runtime/functions.md) | [函数方法说明](reference/function.md) |
| 抽象类 | [myBase](reference/mybase.md)、[myProxy](reference/myproxy.md)、[myTrait](reference/mytrait.md) | [代理与引用](tools/proxy.md) |
| 设置管理 | [配置](config/config.md)、[表单](config/config-forms.md)、[保存](config/config-save.md) | [完整配置例](config/config-example.md) |
| 预定义变量 | [myReq](reference/myreq.md) | [应用读取请求参数](../development/app/first-app.md) |
| 数据库 | [代理和能力](database/database.md)、[预处理](database/db-prepared.md) | [CRUD](database/db-example.md)、[事务](database/transaction-example.md) |
| 错误 | [生命周期](runtime/errors.md)、[myException](reference/myexception.md) | 错误响应、日志及业务返回分别处理 |
| 文件与字符串 | [文件](tools/files.md)、[字符串](tools/strings.md) | [边界与JSON](tools/files-example.md) |
| 缓存 | [协议与驱动](cache/cache.md) | [命中、remember和清理](cache/cache-example.md) |
| Session和认证 | [会话](session/session.md)、[管理员](security/auth.md)、[会员](security/members.md) | [摘要与加密](security/auth-example.md) |
| 其他模块 | 图像、邮件、Excel、归档、上传、反射与压缩见左侧工具分类 | 使用对应方法页的显式准备条件 |

## 读懂返回值

false、null、0、空字符串和空数组可能具有不同意义。数据库fetchOne无记录可返回false，而fetchValue可以合法返回0或SQL NULL；缓存有独立命中标志；解密出来的字符串"0"也是有效结果。完整示例会直接验证这些差别，不用笼统的if(!$result)覆盖所有情况。

## 连接到核心模块

掌握独立能力后，进入[核心模块](core.md)，理解这些工具如何在调度、配置合并、模板与接口中组合。
