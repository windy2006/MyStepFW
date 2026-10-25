# redis · 缓存、会话与业务连接插件

修订日期：2026-10-25。自系统2.5.0以插件提供，插件版本1.0.0，目标运行环境PHP7.4。

## 接入位置

Redis使用框架已有的缓存、Session和配置机制：`plugin/redis/myRedis.class.php`负责连接及键隔离，`plugin/redis/myCache_Redis.class.php`实现缓存协议，`plugin/redis/sess_redis.class.php`实现会话协议。新旧后台都使用`myConfig`生成设置表单。应用仍通过全局`$cache`和`myReq::session()`调用，不另建一套应用缓存接口。

需要安装phpredis扩展5.3或更新版本并启动Redis服务。不使用Composer客户端，不自动安装、修改或重启Redis服务器。当前连接面向单实例，可连接服务提供的固定主节点地址；不执行Redis Cluster分片或Sentinel主节点发现。

## 后台设置与保存位置

新旧后台均进入**插件管理 → Redis存储 → 安装/设置**。连接、认证、TLS、命名空间及锁参数统一保存在`plugin/redis/config.php`，表单结构位于`plugin/redis/config/chs.php`、`en.php`与`default.php`，多语种说明位于`language/`。框架参数设置仅保留缓存和Session的处理模式选择。

选择“基本设置 → 数据缓存 → Redis缓存”后，框架使用Redis数据缓存；选择“Session设置 → 处理模式 → Redis存储”后，框架使用Redis会话。两项独立选择。新旧后台保存时，如选择了Redis缓存或会话，会先检查扩展、实际连接、认证、选库和PING，失败不保存，保留原有配置；插件安装/设置保存也会验证连接，失败保留原文件。保存连接配置不会迁移现有缓存或登录会话。切换会话存储后，用户通常需要重新登录。Redis会话不依赖业务MySQL自动连接，也不提供`sess_mystep`的数据库在线人数统计。

| 参数 | 默认值 | 含义与范围 |
| --- | --- | --- |
| `host` | `127.0.0.1` | 主机名、IPv4或IPv6，不含协议、用户名和端口 |
| `port` | `6379` | TCP端口，1..65535 |
| `database` | `0` | 非负逻辑库编号，须在服务允许范围内 |
| `auth` | `false` | 是否认证；关闭后保留保存的密码 |
| `username` | 空串 | Redis ACL用户，可留空使用默认用户 |
| `password` | 空串 | 访问密码；表单留空保留原值，不输出已保存密码 |
| `prefix` | `mystep:` | 字母、数字、下划线、冒号、短横线，长度1..128 |
| `site_id` | 空串 | 站点逻辑标识；空时按网站ROOT隔离。同项目多服务器共享时设为相同非空值 |
| `timeout` | `1` | 连接超时秒数，0.01..60 |
| `read_timeout` | `2` | 读写超时秒数，0.01..60；阻塞队列命令应使用独立客户端 |
| `tls` | `false` | 启用TLS并验证证书和主机名，不在失败后降级明文 |
| `cafile` | 空串 | 可选PEM CA文件，相对于网站根，如`config/certs/redis-ca.pem`；空时使用系统信任 |
| `lock_wait` | `5` | 会话锁最长等待秒数，0..60；缓存显式锁可另传等待时间 |
| `lock_ttl` | `120` | 锁租约秒数，1..3600，必须大于最长请求或计算时间 |
| `max_bytes` | `1048576` | 单条缓存序列化值、单会话正文的字节预算，1..67108864 |

连接和读取均使用有限超时；认证信息不写入连接异常。TLS使用受信CA，CA文件按框架路径原则保存为网站根相对路径。配置内容属于受信管理设置，Redis密码不发送到浏览器公共设置接口。

## 配置示例

通过插件管理保存；程序中也可使用同一验证入口，成功后才保存：

```php
$class = myStep::storagePlugin('redis'); // 未安装/启用或缺少扩展时明确拒绝
$config = new myConfig(PLUGIN.'redis/config.php');
$config->set(['host'=>'127.0.0.1', 'port'=>'6379', 'database'=>'0']);
$class::validateConfig($config); // 检查参数、连接、认证、选库与PING
$config->save('php');
```

实际应用调用业务客户端：

```php
$class = myStep::storagePlugin('redis');
$redis = new myRedis($class::options());
$key = $redis->key('business', 'article:42:views');
$redis->client()->incr($key);
$redis->close();
```

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

## Session使用与并发

在后台选择Redis会话后，框架负责设置、启动和关闭处理器。应用代码保持原用法：

```php
myReq::session('editor_id', 7);
$editor = myReq::session('editor_id');
myReq::removeSession('editor_id');
// 请求结束时PHP自动保存；需要提前释放锁时使用session_write_close()。
```

会话按站点、前缀、Cookie名称和SID隔离。严格模式只接受实际存在且未过期的ID；未知ID不会创建空会话。到期时间来自`session.expire`，框架转换为`session.gc_maxlifetime`秒数；正常写入和惰性续期都刷新Redis键TTL。

同一SID的锁从读取持有至关闭，排队请求在锁内重新读取最新值。写入、续期和删除通过Lua原子检查所有者令牌；若请求超过`lock_ttl`，失去租约后不能覆盖后继请求的数据，也不能删除后继请求的锁。锁超时或Redis故障返回失败，框架停止认证流程；不静默切到文件会话产生第二套身份状态。

仅做持锁校验不能使长任务无限执行。请将`lock_ttl`设为大于最长请求，批处理、阻塞队列和长耗时工作移到独立进程。单实例租约锁不能代替数据库事务，也不承诺故障切换时业务副作用恰好发生一次。

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

## 类和方法说明

- [myRedis连接与租约工具](redis/myredis.md)
- [myCache_Redis缓存方法](redis/mycache-redis.md)
- [sess_redis会话回调](redis/sess-redis.md)
- [统一缓存与命中规则](../../backend/cache/cache.md)
- [Session生命周期](../../backend/session/session.md)

接口依据：[phpredis 5.3.7官方说明](https://github.com/phpredis/phpredis/blob/5.3.7/README.markdown)、[Redis锁及令牌校验说明](https://redis.io/docs/latest/develop/clients/patterns/distributed-locks/)。

## 安装、调用顺序及故障提示

1. 插件管理检查当前网站PHP是否安装phpredis >= 5.3。配置保存先验证真实连接，成功后才登记安装及发布配置。
2. 框架选择Redis缓存或Session时，`myStep::storagePlugin('redis')`在普通插件`index.php`之前验证目录、安装/启用状态和扩展，加载插件驱动；缓存随`setInstance()`建立，会话在登录认证前建立。
3. 全局存储是显式选择，独立于应用绑定。无需给每个应用绑定Redis；需要普通业务入口时仍可绑定。`index.php`只声明驱动，不覆盖全局缓存，也不主动连接。Memcached普通入口不会覆盖已明确选择的Redis缓存。
4. 未安装/未启用、插件文件缺失、扩展缺失分别有可读提示。保存框架处理模式时连接失败不保存；会话连接或锁失败停止认证，不自动切换文件会话。运行故障按框架错误策略返回失败，并将安全诊断写入日志；非调试页面不暴露内部配置。恢复Redis服务后重试，或由服务器管理员将故障配置中的缓存改为`File`、Session改为`sess_file`后重新登录。
5. 框架或任一应用仍选择Redis时，停用/卸载操作被拒绝，并提示先切换缓存和Session。卸载不清空Redis、不删除共享数据库、不自动搬运登录状态。
6. 2.4.0试用环境的`config/config.php`可能留有`redis`分组。升级前将对应连接值填入插件设置、安装启用，再恢复所需存储选择；保存并确认后可删除旧分组。默认使用File的站点无需迁移，升级不会自动启用Redis或改写现有绑定。

## 文件及入口

| 文件 | 作用 |
| --- | --- |
| `class.php` | 生命周期、驱动加载、参数验证和停用保护 |
| `info.php` / `language/` | 插件发现信息及语言包，不建立连接 |
| `index.php` | 应用绑定入口，只加载声明 |
| `config.php` / `config_default.php` / `config/` | 连接配置及myConfig/checkForm表单 |
| `myRedis.class.php` | 连接、站点隔离键及所有者租约锁 |
| `myCache_Redis.class.php` | 框架缓存协议，统一命中、TTL及命名空间清理 |
| `sess_redis.class.php` | 静态Session协议、严格ID校验、TTL及并发锁 |

[连接客户端方法与示例](redis/myredis.md) · [缓存驱动方法与示例](redis/mycache-redis.md) · [Session方法与示例](redis/sess-redis.md)
