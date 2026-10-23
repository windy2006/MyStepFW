# Session生命周期、锁和并发

修订日期：2026-10-23。适用于当前框架。

## 本篇目标与调用场景

会话与凭据是框架辅助能力，业务仍独立校验身份、数据归属及操作权限。代码预算不替代PHP解析前的补丁与服务器限制。

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


## 主要方法：参数、返回和失败约定

### myReq::sessionStart

功能说明：启动SESSION，启用严格ID校验并接入可选validateId/updateTimestamp回调。


资源生命周期：成功后PHP在请求结束时写回并关闭会话；需要提前释放同ID请求锁时由调用方使用session_write_close。
处理器结构：字符串可为内置files或静态处理器类名；数组前6项依次为open/close/read/write/destroy/gc，9项模式追加create_sid/validate_sid/update_timestamp；空数组使用原生files。

```text
public static function sessionStart($handle = array(), $httponly = true);
```

| 参数说明 |
| --- |
| string\|array $handle 处理器类名、6个原回调或9个完整回调；空数组使用原生files（默认：array()） |
| bool $httponly Cookie是否禁止脚本读取（默认：true） |

**返回**：string|false 成功返回实际ID；no_log禁用、处理器无效或启动失败返回false，不静默切换存储

### myReq::sessionRestart

功能说明：轮换活动SESSION的ID并保留数据；不做多余的关闭/重读，避免严格模式丢失新ID。

```text
public static function sessionRestart($id = '', $delete_old_session = true);
```

| 参数说明 |
| --- |
| string $id 兼容指定目标ID；须有效且满足PHP严格模式，不保证采用未知ID（默认：''） |
| bool $delete_old_session 是否删除旧ID存储，默认true（默认：true） |

**返回**：string|false 非活动会话、非法ID或轮换失败返回false

### myReq::sessionEnd

功能说明：清除会话数据并销毁存储，使用原Cookie路径、域和安全属性删除浏览器Cookie。

参数说明：无显式参数。

```text
public static function sessionEnd();
```

**返回**：bool 非活动会话视为已关闭；存储或Cookie删除失败返回false

### myReq::session

功能说明：读取SESSION

沿用历史空键和null语义：session()返回序列化文本；session($key,null)读取该键。需要批量恢复会话源串时使用setSessions。

```text
public static function session($key='', $value=null);
```

| 参数说明 |
| --- |
| string\|null $key 会话键名；空串/null返回完整会话序列化文本；整数键被拒绝（默认：''） |
| mixed $value null表示读取，非null值写入；此入口不能用null作为待保存值（默认：null） |

**返回**：mixed 空键为会话序列化文本；有键且value为null时读取原值；写入/未启动/整数键返回null

### myReq::sessionCheck

功能说明：检测SESSION是否已启动

参数说明：无显式参数。

```text
public static function sessionCheck();
```

**返回**：bool PHP会话状态为PHP_SESSION_ACTIVE时为true

## 明确调用示例

```php
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1) throw new InvalidArgumentException('编号无效');
$agent = myReq::server('HTTP_USER_AGENT');
myReq::session('last_article', $id);
assert(myReq::session('last_article') === $id);
```

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
