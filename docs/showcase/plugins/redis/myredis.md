# Redis连接

接口说明：`myRedis`。

## 功能与调用位置

源码：`plugin/redis/myRedis.class.php`。配置和完整示例见 [Redis支持](../redis.md)。

## 使用示例

```php
$redis = new myRedis(plugin_redis::options());
$counter = $redis->key('business', 'page:42:views');
$redis->client()->incr($counter);
$redis->client()->expire($counter, 3600);
$redis->close();
```

## 方法目录

| 方法 | 用途 |
| --- | --- |
| `myRedis::__construct` | 验证连接配置，延迟连接直到client或锁操作被调用。 |
| `myRedis::settings` | 补齐并验证Redis公共配置，不修改传入对象或保存站点文件。 |
| `myRedis::configure` | 规范Redis插件配置对象并按需验证实际连接。 |
| `myRedis::client` | 按需连接Redis，设置有限超时、原始字节模式、认证和逻辑数据库。 |
| `myRedis::prefix` | 生成用途前缀；相同配置在同一站点下共享，缓存/会话/业务键互相分离。 |
| `myRedis::key` | 将有限标量逻辑键转换为固定长度摘要，避免特殊字符或超长键。 |
| `myRedis::acquire` | 用SET NX PX获取有限租约锁，等待超时不继续执行受保护操作。 |
| `myRedis::release` | 通过Lua比较令牌再删除锁，不会释放后继请求持有的锁。 |
| `myRedis::close` | 关闭当前非持久连接，后续client调用会重新连接。 |

## 方法说明

### myRedis::__construct

```php
public myRedis::__construct($setting = [])
```

功能说明：验证连接配置，延迟连接直到client或锁操作被调用。

@param array|object $setting host/port/database/auth/username/password/prefix/site_id/timeout/read_timeout/tls/cafile/lock_wait/lock_ttl/max_bytes（默认：[]）

@return void 初始化完成；不执行网络请求

@throws InvalidArgumentException 配置类型或取值不合法

### myRedis::settings

```php
public static myRedis::settings($setting = [])
```

功能说明：补齐并验证Redis公共配置，不修改传入对象或保存站点文件。

@param array|object $setting 可省略的配置分组，表单数字字符串会规范为数字（默认：[]）

@return array 完整配置；空前缀拒绝，密码保留原始字节

@throws InvalidArgumentException 主机、超时、库编号、前缀、TLS或容量配置不合法

### myRedis::configure

```php
public static myRedis::configure(myConfig $config,$probe = false)
```

功能说明：规范Redis插件配置对象并按需验证实际连接。

@param myConfig $config 读取或保存中的Redis插件配置

@param bool $probe true时检查扩展及实际连接（默认：false）

@return void 只调整对象内存，不写文件；probe完成后关闭探测连接

@throws InvalidArgumentException Redis插件配置存在无效设置

### myRedis::client

```php
public myRedis::client()
```

功能说明：按需连接Redis，设置有限超时、原始字节模式、认证和逻辑数据库。

参数说明：无显式参数。

@return Redis 原生客户端；业务操作应配合key('business',...)隔离键

@throws RuntimeException 扩展缺失、连接/认证/选库失败；消息不包含凭据

### myRedis::prefix

```php
public myRedis::prefix($scope)
```

功能说明：生成用途前缀；相同配置在同一站点下共享，缓存/会话/业务键互相分离。

@param string $scope cache/session/business等字母数字用途；不得包含通配符

@return string 配置前缀、site_id摘要和用途前缀；site_id留空时使用ROOT摘要

@throws InvalidArgumentException 用途不合法

### myRedis::key

```php
public myRedis::key($scope,$key)
```

功能说明：将有限标量逻辑键转换为固定长度摘要，避免特殊字符或超长键。

@param string $scope 键用途

@param scalar|null $key 有限标量或null，类型不同但文本相同的值视为同键

@return string 带用途和站点前缀的物理键

@throws InvalidArgumentException 键为对象、数组、资源或非有限浮点数

### myRedis::acquire

```php
public myRedis::acquire($key,$wait=null)
```

功能说明：用SET NX PX获取有限租约锁，等待超时不继续执行受保护操作。

@param string $key 已生成的锁物理键

@param int|float|null $wait 等待秒数0..60；null使用配置lock_wait（默认：null）

@return string 随机所有者令牌

@throws RuntimeException 服务不可用或等待超时

@throws InvalidArgumentException 等待时间不合法

### myRedis::release

```php
public myRedis::release($key,$token)
```

功能说明：通过Lua比较令牌再删除锁，不会释放后继请求持有的锁。

@param string $key 锁物理键

@param string $token 本请求获取的所有者令牌

@return bool 当前令牌成功释放为true，过期或已转移所有权为false

@throws RedisException 连接故障

### myRedis::close

```php
public myRedis::close()
```

功能说明：关闭当前非持久连接，后续client调用会重新连接。

参数说明：无显式参数。

@return bool 已关闭或无需关闭为true，关闭异常为false

## 边界

Redis单实例锁具有有限租约；`lock_ttl`须大于最长受保护操作。缓存清理保留会话和业务键；会话故障不会回退其他存储。跨服务器共享数据时显式配置相同的`site_id`、前缀和逻辑库。

## 原生业务能力

队列、计数器、集合、排行榜等使用`myRedis::client()`返回的原生Redis客户端；用`key('business',...)`生成业务键，与框架缓存和会话区分。不要把原生客户端改成持久连接或修改框架驱动持有连接的序列化、前缀和数据库状态。

```php
$redis = new myRedis(plugin_redis::options());
$key = $redis->key('business', 'article:42:views');
$views = $redis->client()->incr($key);
$redis->client()->expire($key, 3600);
$redis->close();
```

原生客户端可以直接操作该账号允许访问的Redis数据；用途前缀不是权限隔离。需要账号级隔离时，在服务端配置ACL和独立凭据。
