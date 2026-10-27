# Redis设置

## 接入位置

Redis使用框架已有的缓存、Session和配置机制：`plugin/redis/myRedis.class.php`负责连接及键隔离，`plugin/redis/myCache_Redis.class.php`实现缓存协议，`plugin/redis/sess_redis.class.php`实现会话协议。两个后台都使用`myConfig`生成设置表单。应用仍通过全局`$cache`和`myReq::session()`调用，不另建一套应用缓存接口。

需要安装phpredis扩展5.3或更新版本并启动Redis服务。不使用Composer客户端，不自动安装、修改或重启Redis服务器。当前连接面向单实例，可连接服务提供的固定主节点地址；不执行Redis Cluster分片或Sentinel主节点发现。

## 后台设置与保存位置

两个后台均进入**插件管理 → Redis存储 → 安装/设置**。连接、认证、TLS、命名空间及锁参数统一保存在`plugin/redis/config.php`，表单结构位于`plugin/redis/config/chs.php`、`en.php`与`default.php`，多语种说明位于`language/`。框架参数设置仅保留缓存和Session的处理模式选择。

选择“基本设置 → 数据缓存 → Redis缓存”后，框架使用Redis数据缓存；选择“Session设置 → 处理模式 → Redis存储”后，框架使用Redis会话。两项独立选择。两个后台保存时，如选择了Redis缓存或会话，会先检查扩展、实际连接、认证、选库和PING，失败不保存，保留配置；插件安装/设置保存也会验证连接，失败保留原文件。保存连接配置不会迁移现有缓存或登录会话。切换会话存储后，用户通常需要重新登录。Redis会话不依赖业务MySQL自动连接，也不提供`sess_mystep`的数据库在线人数统计。

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

## 类和方法说明

- [Redis连接](redis/myredis.md)
- [Redis缓存](redis/mycache-redis.md)
- [Redis会话](redis/sess-redis.md)
- [缓存管理](../../backend/cache/cache.md)
- [Session会话](../../backend/session/session.md)

接口依据：[phpredis 5.3.7官方说明](https://github.com/phpredis/phpredis/blob/5.3.7/README.markdown)、[Redis锁及令牌校验说明](https://redis.io/docs/latest/develop/clients/patterns/distributed-locks/)。

## 安装、调用顺序及故障提示

1. 插件管理检查当前网站PHP是否安装phpredis >= 5.3。配置保存先验证真实连接，成功后才登记安装及发布配置。
2. 框架选择Redis缓存或Session时，`myStep::storagePlugin('redis')`在普通插件`index.php`之前验证目录、安装/启用状态和扩展，加载插件驱动；缓存随`setInstance()`建立，会话在登录认证前建立。
3. 全局存储是显式选择，独立于应用绑定。无需给每个应用绑定Redis；需要普通业务入口时仍可绑定。`index.php`只声明驱动，不覆盖全局缓存，也不主动连接。Memcached普通入口不会覆盖已明确选择的Redis缓存。
4. 未安装/未启用、插件文件缺失、扩展缺失分别有可读提示。保存框架处理模式时连接失败不保存；会话连接或锁失败停止认证，不自动切换文件会话。运行故障按框架错误策略返回失败，并将安全诊断写入日志；非调试页面不暴露内部配置。恢复Redis服务后重试，或由服务器管理员将故障配置中的缓存改为`File`、Session改为`sess_file`后重新登录。
5. 框架或任一应用仍选择Redis时，停用/卸载操作被拒绝，并提示先切换缓存和Session。卸载不清空Redis、不删除共享数据库、不自动搬运登录状态。

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
