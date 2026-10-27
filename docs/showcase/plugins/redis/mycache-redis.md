# Redis缓存

接口说明：`myCache_Redis`。

## 功能与调用位置

源码：`plugin/redis/myCache_Redis.class.php`。Redis运行前提、配置及缓存/会话示例见 [Redis支持](../redis.md)。

## 使用示例

```php
$demo = new myCache('Redis', array_merge(
    myConfig::o2a(plugin_redis::options()),
    ['namespace'=>'reports', 'allowed_classes'=>false]
));
$demo->set('flag', false, 60);
$value = $demo->fetch('flag', $hit); // $hit为true，$value为false
$demo->clear(); // 仅本缓存namespace；会话和业务键保留
```

## 方法目录

| 方法 | 用途 |
| --- | --- |
| `myCache_Redis::__construct` | 验证配置并建立Redis连接，失败时保留代理原来的缓存后端。 |
| `myCache_Redis::set` | 保存统一编码的数据及TTL，省略value或非正TTL沿用删除约定。 |
| `myCache_Redis::fetch` | 在Redis端检查字节预算后读取并解码，同时返回精确命中状态。 |
| `myCache_Redis::get` | 读取入口；精确区分有效false时使用fetch。 |
| `myCache_Redis::remove` | 删除当前命名空间的单个缓存键，键不存在也算成功。 |
| `myCache_Redis::clean` | 沿用过期回收入口；Redis自动回收到期键，不清空有效数据。 |
| `myCache_Redis::clear` | 用SCAN分批删除当前站点、当前namespace的value键，保留锁、会话和业务键。 |
| `myCache_Redis::synchronized` | 持有逻辑键租约锁执行回调，异常也释放；令牌校验避免释放其他请求的锁。 |

## 方法说明

### myCache_Redis::__construct

```php
public myCache_Redis::__construct($setting=null)
```

功能说明：验证配置并建立Redis连接，失败时保留代理原来的缓存后端。

@param array|null $setting Redis公共设置及namespace/max_depth/allowed_classes，null使用默认设置

@return void 初始化完成

@throws InvalidArgumentException 配置或编解码选项不合法

@throws RuntimeException Redis扩展或服务不可用

### myCache_Redis::set

```php
public myCache_Redis::set($key,$value='',$ttl=600)
```

功能说明：保存统一编码的数据及TTL，省略value或非正TTL沿用删除约定。

@param scalar|null $key 逻辑缓存键

@param mixed $value 可序列化值，显式null/false有效；默认空字符串

@param int|float|string $ttl 秒数，正小数向上取整，默认600

@return bool 完整写入或删除成功为true，后端故障为false

@throws InvalidArgumentException 键、TTL或资源值不合法

@throws LengthException 序列化内容超过预算

### myCache_Redis::fetch

```php
public myCache_Redis::fetch($key,&$hit)
```

功能说明：在Redis端检查字节预算后读取并解码，同时返回精确命中状态。

@param scalar|null $key 逻辑缓存键

@param bool &$hit 有效数据为true；缺失、损坏、超限或服务故障为false

@return mixed 原始缓存值；未命中返回false

@throws InvalidArgumentException 键不合法

### myCache_Redis::get

```php
public myCache_Redis::get($key)
```

功能说明：读取入口；精确区分有效false时使用fetch。

@param scalar|null $key 逻辑缓存键

@return mixed 有效值或未命中false

### myCache_Redis::remove

```php
public myCache_Redis::remove($key)
```

功能说明：删除当前命名空间的单个缓存键，键不存在也算成功。

@param scalar|null $key 逻辑缓存键

@return bool 后端完成删除为true，连接故障为false

### myCache_Redis::clean

```php
public myCache_Redis::clean()
```

功能说明：沿用过期回收入口；Redis自动回收到期键，不清空有效数据。

参数说明：无显式参数。

@return bool Redis可响应PING为true，连接故障为false

### myCache_Redis::clear

```php
public myCache_Redis::clear()
```

功能说明：用SCAN分批删除当前站点、当前namespace的value键，保留锁、会话和业务键。

参数说明：无显式参数；并发请求可能在清理后重新生成缓存。

@return bool 扫描及删除成功为true，后端故障为false

### myCache_Redis::synchronized

```php
public myCache_Redis::synchronized($key,callable $operation,$timeout=5)
```

功能说明：持有逻辑键租约锁执行回调，异常也释放；令牌校验避免释放其他请求的锁。

@param scalar|null $key 与缓存逻辑键对应的锁键

@param callable $operation 无参数计算回调

@param int|float|string $timeout 最长等待秒数0..60，默认5

@return mixed 回调返回值

@throws LogicException 同一实例递归获取同一锁

@throws RuntimeException 等待超时、服务故障或执行后发现租约丢失

@throws Throwable 回调异常原样传递，释放操作不覆盖原异常

## 边界

Redis单实例锁具有有限租约；`lock_ttl`须大于最长受保护操作。缓存清理保留会话和业务键；会话故障不会回退其他存储。跨服务器共享数据时显式配置相同的`site_id`、前缀和逻辑库。

## 应用缓存示例

在已初始化的应用module中，直接使用框架提供的`$cache`。下面的键应补足实际用户、语言、权限及业务参数，不要把私人内容缓存为所有用户共用的键。

```php
$cache->set('article:42:published', false, 60);
$value = $cache->fetch('article:42:published', $hit);
// $hit === true，$value === false；null、0、空字符串也能精确命中。

$summary = $cache->rememberLocked('report:public:2026', function () {
    return ['count'=>100]; // 替换为允许重复计算的真实业务查询。
}, 60, 3);

$cache->remove('article:42:published');
```

已显式选择Redis缓存时，Memcached插件不会覆盖该缓存实例。Redis必须安装启用；框架存储选择不依赖应用绑定。

TTL单位为秒，正小数向上取整，非正数删除；`set($key)`保留内置缓存的删除约定。编解码使用`myCacheCodec`的字节、深度及对象恢复策略。独立缓存实例可传`namespace`、`max_depth`和`allowed_classes`：

```php
$options = myConfig::o2a(plugin_redis::options());
$options['namespace'] = 'public-reports';
$options['allowed_classes'] = false;
$reports = new myCache('redis', $options);
$reports->set('daily', ['total'=>20], 300);
```

`clean()`依赖Redis自动过期机制，仅确认连接可用；`clear()`用SCAN分批删除当前站点、当前namespace的缓存value键，保留活动锁、其他namespace、会话和业务数据。后台“清理数据缓存”“全部清理”沿用`myStep::clearDataCache()`，会调用当前驱动的`clear()`，不使用FLUSHDB或FLUSHALL。并发业务可以在清理后重新生成缓存。
