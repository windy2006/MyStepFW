# 会话驱动

## 本篇目标与调用场景

会话与凭据是框架辅助能力，业务仍独立校验身份、数据归属及操作权限。代码预算不替代PHP解析前的补丁与服务器限制。

## 配置和错误接口

`sess_mysql::set($db)` 保留 host/user/password/name/charset，支持可选 port/socket。`utf-8` 和 `utf8` 规范为 utf8mb4；数据列为二进制，不受文本尾部空格和 NUL 影响。

| 可选数据库配置 | 默认 | 用途 |
| --- | --- | --- |
| session_table | my_session；sess_mystep 默认 `{pre}session_data` | 同库不同应用可设置独立表；仅字母、数字和下划线，最长 64 字符 |
| session_lock_timeout | 10 | 命名锁等待秒数，限制为 0～60 |
| session_max_bytes | 1048576 | 原始会话字节预算，限制为 1～16777215；超限失败，不截断旧数据 |

`getLastError()` 返回不含凭据、SQL和会话正文的错误摘要。回调失败返回 false，调用端应检查 `myReq::sessionStart()` 的结果；框架 myStep 在非 no_log 请求启动失败且无活动会话时停止认证流程，防止后端故障被当作正常空会话。在线表是附属统计，缺失/写入失败不影响独立认证数据，`sess_mystep::hasTrackingError()` 可查询本次请求的统计错误。

myReq 的 expire 单位仍为分钟，处理器 TTL 单位是秒；gc=false 只禁止概率清理，不延长过期数据的可用时间。trans_sid 默认关闭；关闭时只从 Cookie 接受原生客户端 ID，防止 URL 参数注入身份。

`interface_session` 不增加必需方法；第三方六回调处理器可继续使用。类提供 validateId/updateTimestamp 时会自动注册；九回调数组须全部可调用。PHP8.5由mySessionHandler转换为原生SessionHandlerInterface；框架的六/九回调回调协议仍要求对应项为实际callable，不能传null。旧六回调如果未实现 ID 存在性校验，不能仅靠开启 use_strict_mode 获得同等保护。参见 [PHP 回调文档](https://www.php.net/manual/en/function.session-set-save-handler.php) 和 [严格模式说明](https://www.php.net/manual/en/session.security.ini.php)。

`sessionRestart()` 无参数保留内存数据并轮换 ID；明确传目标 ID 使用“切换会话”语义，目标不存在时严格模式会分配其他 ID，不能将该参数作为任意指定新 ID 的接口。`sessionEnd()` 现在返回 bool。`setSessions()` 成功返回原数组并轮换 ID；常用 php/php_serialize 编码先做不实例化对象的合法性检查，坏数据保留原状态；输入仍应来自可信内部来源。

## 在线监控

认证数据保存于独立会话表；在线统计表仅记录会话ID的MD5展示摘要、显示用户名及组号。统计摘要不能作为浏览器会话ID使用，在线表不保存密码凭据或CSRF令牌。

不同存储后端和认证域使用独立的session.name。CMS会话名为MyStepCMSSession；其他应用按配置设置自己的名称。

## 主要方法：参数、返回和失败约定

### myReq::setSessionOpt

功能说明：设置SESSION选项；缺失name时使用当前原生名称，不读取未定义键。

单位约定：expire为分钟，启动时乘60交给gc_maxlifetime及Cookie生命周期；数据库/缓存的TTL仍采用各自方法说明中的秒。

```text
public static function setSessionOpt($setting);
```

| 参数说明 |
| --- |
| array $setting id/expire（分钟）/gc/mode/trans_sid/path/name |

**返回**：void 无返回值。

**异常**：InvalidArgumentException 会话设置不是数组

### myReq::setSessionPath

功能说明：设置SESSION保存路径

```text
public static function setSessionPath($path = '');
```

| 参数说明 |
| --- |
| string $path 目标目录或路径（默认：''） |

**返回**：string 设置完成后读取的当前Session目录；会话已启动、非files模式或目录准备失败时保留现有路径

### myReq::setSessionName

功能说明：设置SESSION名称

```text
public static function setSessionName($name = '');
```

| 参数说明 |
| --- |
| string $name 新的Session名称；空串只读取当前名称（默认：''） |

**返回**：string|false 空名称或活动会话时读取当前名称；设置新名称成功返回旧名称，PHP拒绝设置时为false

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
