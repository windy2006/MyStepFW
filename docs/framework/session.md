# Session 处理器与会话约定

整理日期：2026-10-18。目标运行环境：PHP 7.4。下文中的检查及验证记录保留原日期和适用边界。

适用 PHP 7.4。范围：`lib/session/` 的兼容接口、文件/MySQL/MyStep 三个处理器，以及 `myReq` 的会话生命周期调用和 `myStep` 启动故障处理。

## 已确认的问题

| 位置 | 原行为 | 修正 |
| --- | --- | --- |
| sess_file | 即使文件已经过期，GC 尚未运行时仍能恢复旧登录数据 | read 和严格 ID 校验直接检查有效期 |
| sess_file | 请求等待文件锁期间，退出/GC 可能删除该文件；等待者随后操作失联的 inode | 加锁后核对路径、inode、设备及普通文件类型，必要时重新打开 |
| sess_file | 文件权限偏宽，未明确拒绝会话文件/日期子目录符号链接 | 新目录 0700，文件 0600，拒绝链接与非法 ID；相对路径转绝对路径 |
| sess_mysql | HEAP + CHAR(255) 限制大数据，CHAR(32) 不支持更长 ID，字符排序规则混淆大小写 | 新表使用 InnoDB、MEDIUMBLOB、ASCII 二进制比较的 VARCHAR(128) |
| sess_mysql | destroy 拼接未加引号的 ID；SQL 拼接依赖转义及 sql_mode | 查询、写入、删除、锁和 GC 全部绑定参数 |
| sess_mysql | 到期时间已是绝对时间，GC 又减去一次 TTL；长请求用旧 REQUEST_TIME 续期 | 按实际 time() 读写和清理绝对到期时间 |
| 数据库会话 | 无 read→write 锁，并发更新可能丢失；错误被当作空会话 | 独立连接和同 ID 命名锁；存储失败返回 false |
| sess_mystep | 同 IP 的 REPLACE 删除其他访客的认证数据；接口/爬虫策略跳过保存 | 认证独立存储，在线表仅保留展示摘要，统计策略不再控制认证保存 |
| sess_mystep | gc 关闭连接并重置 run，PHP 随后的 write 可能失效 | GC 仅清理记录，close 统一释放连接和锁 |
| 三个处理器 | close 内再次随机 GC，与 PHP 概率叠加 | 只使用 PHP 配置的 GC 概率 |
| myReq | 没有注册严格 ID 校验；初始化引用缺失 name；gc=false 时寿命仍沿用默认值 | 接入可选回调，严格模式，始终设置 TTL，显式控制 GC 开关 |
| myReq | 轮换时多余关闭/重读；退出未按原 Cookie 域和路径删除；坏批量数据可能销毁当前会话 | 原生活动会话轮换；匹配原 Cookie 属性删除；常用编码先校验，失败保留原数据 |

改前已用独立临时目录/随机数据库复现：过期文件读出 `old-login`；512 字节数据库写入失败；非数字 ID 删除失败；GC 后仍有过期行。测试未连接任何远程数据库。

## 执行与并发规则

1. PHP 调用 open；处理器准备目录或独立数据库连接。
2. myReq 注册的 validateId 在严格模式下只接受现有且未过期的 ID，未知客户端 ID 改用 24 字节随机数的十六进制新 ID。
3. read 对当前 ID 持锁，锁覆盖读取、业务代码及 write/updateTimestamp。
4. 数据未变化时，文件处理器只刷新 mtime；存在旧目录迁移数据时仍完整保存。MySQL 对已有行只更新 expiration，不覆盖 BLOB；缺失行用 PHP 提供的数据恢复。
5. write 保存完整原始字节；destroy 清除旧身份及迁移副本；close 释放锁和连接。
6. PHP 按 gc_probability/gc_divisor 调用 GC。文件回收使用非阻塞锁，跳过活跃会话；MySQL 使用 expiration 索引。

no_log 的语言包/设置等公开端点由 myReq 跳过整个会话启动，不发送或轮换会话 Cookie，此时 sessionStart 返回 false。

较慢的业务在完成所有会话写入后可尽早 `session_write_close()`，释放同用户请求的串行锁；关闭后对 `$_SESSION` 的修改不会自动保存。只读 `read_and_close` 不执行续期回调，不能用于需要延长登录寿命的请求。

文件格式仍为 `sess_<ID>` 的原始 PHP 会话字节串，保留旧日期目录的首次迁移；不承诺断电/磁盘写失败时的原子保存。目录须由可信账号管理、不可通过 HTTP 下载；符号链接检查不替代目录访问控制。文件锁采用本机 flock，分布式文件系统语义须另行验证。

MySQL 命名锁最长等待默认为 10 秒，失败返回 false，不退回文件存储。严格 ID 校验中的锁/查询失败会使随后的 read 明确失败，防止 PHP 误将故障解释为不存在的 ID 并启动空会话。锁按数据库、表及区分大小写的 ID 隔离；数据库/表名保守转小写后参与锁命名，以兼容大小写不敏感的服务器。适用于全部请求连接同一 MySQL 写实例的部署，不承诺跨独立数据库节点的锁一致性。连接为专用非持久连接，业务事务不会提交/回滚会话。

## 配置和错误接口

`sess_mysql::set($db)` 保留 host/user/password/name/charset，支持可选 port/socket。`utf-8` 和 `utf8` 规范为 utf8mb4；数据列为二进制，不受文本尾部空格和 NUL 影响。

| 可选数据库配置 | 默认 | 用途 |
| --- | --- | --- |
| session_table | my_session；sess_mystep 默认 `{pre}session_data` | 同库不同应用可设置独立表；仅字母、数字和下划线，最长 64 字符 |
| session_lock_timeout | 10 | 命名锁等待秒数，限制为 0～60 |
| session_max_bytes | 1048576 | 原始会话字节预算，限制为 1～16777215；超限失败，不截断旧数据 |

`getLastError()` 返回不含凭据、SQL和会话正文的错误摘要。回调失败返回 false，调用端应检查 `myReq::sessionStart()` 的结果；框架 myStep 在非 no_log 请求启动失败且无活动会话时停止认证流程，防止后端故障被当作正常空会话。在线表是附属统计，缺失/写入失败不影响独立认证数据，`sess_mystep::hasTrackingError()` 可查询本次请求的统计错误。

myReq 的 expire 单位仍为分钟，处理器 TTL 单位是秒；gc=false 只禁止概率清理，不延长过期数据的可用时间。trans_sid 默认关闭；关闭时只从 Cookie 接受原生客户端 ID，防止 URL 参数注入身份。

`interface_session` 不增加必需方法；第三方六回调处理器可继续使用。类提供 validateId/updateTimestamp 时会自动注册；九回调数组须全部可调用。PHP 7.4 的扩展回调位置要求实际 callable，不能按新版文档签名直接传 null。旧六回调如果未实现 ID 存在性校验，不能仅靠开启 use_strict_mode 获得同等保护。参见 [PHP 回调文档](https://www.php.net/manual/en/function.session-set-save-handler.php) 和 [严格模式说明](https://www.php.net/manual/en/session.security.ini.php)。

`sessionRestart()` 无参数保留内存数据并轮换 ID；明确传目标 ID 沿用历史“切换会话”语义，目标不存在时严格模式会分配其他 ID，不能将该参数作为任意指定新 ID 的接口。`sessionEnd()` 现在返回 bool。`setSessions()` 成功返回原数组并轮换 ID；常用 php/php_serialize 编码先做不实例化对象的合法性检查，坏数据保留原状态；输入仍应来自可信内部来源。

## 旧 MySQL 表的维护升级

新表由 open 按新结构创建。已有 HEAP/MEMORY/CHAR 表不在普通请求中 ALTER：open 返回 false，并在 getLastError 中提示维护升级，防止在访问高峰执行阻塞 DDL 或静默截断。

维护时停止并发会话请求，备份数据库，然后用站点加载器载入接口及处理器，使用同一数据库配置：

```php
sess_mysql::set($dbSettings); // 可含 session_table 指定目标；凭据取现有配置
if (!sess_mysql::upgradeSchema()) {
    throw new RuntimeException(sess_mysql::getLastError());
}
```

方法保留记录并将引擎/列调整为 InnoDB、VARCHAR(128) ASCII 二进制 ID、BIGINT 到期时间、MEDIUMBLOB 数据。执行账号需要 ALTER 权限；DDL 不提供自动回滚；旧 CHAR 字段曾经截断的数据和空格无法凭升级恢复。新安装账号需要创建表权限；稳定部署可由维护账号预建表，再配置所需运行权限。GET_LOCK/RELEASE_LOCK 的行为参见 [MySQL 文档](https://dev.mysql.com/doc/refman/8.4/en/locking-functions.html)。

## MyStep 在线监控兼容

认证表不再受在线表的 IP 唯一约束、255 字节字段或爬虫过滤影响。旧在线表仍可保持现有结构。新统计行 SID 是真实会话 ID 的 32 位 MD5 展示摘要，兼容 CHAR(32)，不能拿它当作浏览器会话 ID；data 仅含 `_mystep_online` 标记、CMS 显示用户名及组号，原在线列表可继续解码显示，不复制密码版本、CSRF 等私密字段。带标记的摘要永不作为旧认证数据迁移。统计仍按原 IP 唯一性表示当前访客，不承担逐会话审计职责。

未过期旧在线记录可在首次访问时迁移到独立认证表；成功后清空原始 data，避免重复保存完整认证数据。销毁会话同时清理旧 SID 和新展示摘要；若旧副本删除失败，会保留空认证记录阻止旧身份重新迁移并报告失败。

不同存储后端/不同认证域应配置独立 session.name。当前 CMS 在实际及默认配置中使用 `MyStepCMSSession`，避免与根站/后台/文档应用的文件会话 `MyStepSession` 相互替换；升级后 CMS 需重新登录一次，已有签名记住登录 Cookie 仍按认证模块规则校验。当前站点原 my_session 表不存在，无须执行历史表 ALTER；CMS 使用新建 `mystep_cms.cms_session_data`，原在线表不做结构变更。

## 验证

```sh
php tests/session/session_regression.php --database
python3 tests/session/session_http_fixture.py
```

专项使用私有临时目录、独立 PHP 子进程、回环 HTTP 及随机命名 `mystep_session_review_*` MySQL 测试库，finally 清理测试资源。覆盖 ID/二进制数据/预算/过期/迁移/符号链接、8 进程文件与数据库并发、等待锁期间退出、数据库故障、实际原生严格模式/惰性写入/轮换/退出、坏批量解码、长 SID 统计摘要、Cookie 属性及启动失败。HTTP 夹具中的 HTTPS 标记仅用于测试 Cookie 标志，不声称提供回环 TLS 服务；实际站点另用受信 CA 校验 HTTPS。

实际 HTTPS 增补覆盖同一 CookieJar 在根站、CMS 与 CMS API 间切换、语言/设置 no_log 请求后的 CSRF 连续性，共 11 次请求。CMS 实际创建的独立会话表已确认为 InnoDB，未对原在线表执行 ALTER。
