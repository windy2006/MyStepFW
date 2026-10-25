# sess_redis：Redis会话处理器

修订日期：2026-10-24。适用于系统2.5.0 / Redis插件1.0.0、PHP7.4。

## 功能与调用位置

源码：`plugin/redis/sess_redis.class.php`。Redis运行前提、配置及缓存/会话示例见 [Redis支持](../redis.md)。

## 使用示例

```php
// 在后台选择session.mode = sess_redis，由框架启动处理器。
myReq::session('editor_id', 7);
$editor = myReq::session('editor_id');
myReq::removeSession('editor_id');
// 应用内不再直接调用sess_redis::open/read/write启动第二套会话。
```

## 方法目录

| 方法 | 用途 |
| --- | --- |
| `sess_redis::set` | 验证并设置Redis会话参数，活动连接期间拒绝重配。 |
| `sess_redis::getLastError` | 读取不包含凭据、会话ID和正文的最近错误摘要。 |
| `sess_redis::open` | 建立会话专用连接并按Cookie名称隔离存储。 |
| `sess_redis::validId` | 验证PHP会话ID的长度和字符集，拒绝控制字符及分隔符。 |
| `sess_redis::lock` | 获取指定ID锁；会话ID轮换时释放旧锁，再获取新锁。 |
| `sess_redis::unlock` | 核对所有者后释放当前锁，清空本请求锁状态。 |
| `sess_redis::operate` | 在一次Lua操作内核对锁令牌，再读取、保存、删除或续期会话。 |
| `sess_redis::read` | 持锁读取原始会话字节，不存在返回空串，故障返回false。 |
| `sess_redis::write` | 持锁完整保存会话数据，并使用session.gc_maxlifetime续期。 |
| `sess_redis::validateId` | 严格模式只接受实际存在的未过期会话，未知ID不会写入空会话。 |
| `sess_redis::updateTimestamp` | 惰性写入续期；记录被回收时在持锁状态下恢复PHP提供的数据。 |
| `sess_redis::destroy` | 持锁删除会话并释放锁，避免排队请求再次读取旧身份。 |
| `sess_redis::gc` | Redis通过键TTL自动回收，不扫描或删除其他会话，不关闭当前连接。 |
| `sess_redis::close` | 释放本请求的会话锁并关闭专用连接，可重复调用。 |

## 方法说明

### sess_redis::set

```php
public static sess_redis::set($setting)
```

功能说明：验证并设置Redis会话参数，活动连接期间拒绝重配。

@param array|object $setting myRedis公共参数；max_bytes为单会话字节上限

@return bool 参数合法且未打开连接为true，否则false

### sess_redis::getLastError

```php
public static sess_redis::getLastError()
```

功能说明：读取不包含凭据、会话ID和正文的最近错误摘要。

参数说明：无显式参数。

@return string 最近错误摘要，无错误时为空字符串

### sess_redis::open

```php
public static sess_redis::open($sess_path,$sess_name)
```

功能说明：建立会话专用连接并按Cookie名称隔离存储。

@param string $sess_path PHP协议参数，Redis不使用文件路径

@param string $sess_name PHP会话Cookie名称

@return bool 配置和连接成功为true，失败false

### sess_redis::validId

```php
private static sess_redis::validId($sid)
```

功能说明：验证PHP会话ID的长度和字符集，拒绝控制字符及分隔符。

@param mixed $sid 会话ID

@return bool 1..128个字母、数字、逗号或短横线为true

### sess_redis::lock

```php
private static sess_redis::lock($sid)
```

功能说明：获取指定ID锁；会话ID轮换时释放旧锁，再获取新锁。

@param string $sid 已校验或待校验的会话ID

@return bool 锁可用为true；未打开、ID非法或等待失败false

### sess_redis::unlock

```php
private static sess_redis::unlock()
```

功能说明：核对所有者后释放当前锁，清空本请求锁状态。

参数说明：无显式参数。

@return bool 无锁或释放成功为true，锁丢失/连接失败false

### sess_redis::operate

```php
private static sess_redis::operate($sid,$action,$data='')
```

功能说明：在一次Lua操作内核对锁令牌，再读取、保存、删除或续期会话。

@param string $sid 会话ID

@param string $action read/exists/write/touch/delete

@param string $data PHP会话原始字节，默认空串

@return string|bool read返回原始字节，其余返回操作是否成功；所有后端故障返回false

### sess_redis::read

```php
public static sess_redis::read($sid)
```

功能说明：持锁读取原始会话字节，不存在返回空串，故障返回false。

@param string $sid 会话ID

@return string|false 会话正文或失败false

### sess_redis::write

```php
public static sess_redis::write($sid,$sess_data)
```

功能说明：持锁完整保存会话数据，并使用session.gc_maxlifetime续期。

@param string $sid 会话ID

@param string $sess_data 原始数据，包含空串、中文和NUL均保留

@return bool 原子保存成功为true

### sess_redis::validateId

```php
public static sess_redis::validateId($sid)
```

功能说明：严格模式只接受实际存在的未过期会话，未知ID不会写入空会话。

@param string $sid 客户端提供的会话ID

@return bool 有效现存会话为true，未知、过期或故障false

### sess_redis::updateTimestamp

```php
public static sess_redis::updateTimestamp($sid,$sess_data)
```

功能说明：惰性写入续期；记录被回收时在持锁状态下恢复PHP提供的数据。

@param string $sid 会话ID

@param string $sess_data PHP提供的未变化会话字节

@return bool 续期或恢复成功为true

### sess_redis::destroy

```php
public static sess_redis::destroy($sid)
```

功能说明：持锁删除会话并释放锁，避免排队请求再次读取旧身份。

@param string $sid 待销毁的会话ID

@return bool 删除及释放均成功为true

### sess_redis::gc

```php
public static sess_redis::gc($maxlifetime)
```

功能说明：Redis通过键TTL自动回收，不扫描或删除其他会话，不关闭当前连接。

@param int $maxlifetime PHP协议参数；到期时间已经由write/touch写入

@return bool 当前连接可响应为true，否则false

### sess_redis::close

```php
public static sess_redis::close()
```

功能说明：释放本请求的会话锁并关闭专用连接，可重复调用。

参数说明：无显式参数。

@return bool 锁和连接均完成释放为true，否则false

## 边界

Redis单实例锁具有有限租约；`lock_ttl`须大于最长受保护操作。缓存清理保留会话和业务键；会话故障不会回退其他存储。跨服务器共享数据时显式配置相同的`site_id`、前缀和逻辑库。
