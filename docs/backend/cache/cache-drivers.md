# File、MySQL和MSSQL缓存驱动

修订日期：2026-10-23。适用于当前框架。

## 本篇目标与调用场景

缓存键、后端、有效期和清理范围应明确。计算锁减少重复计算，不提供业务副作用恰好一次保证。

## 文件驱动

物理地址继续是 `v2/摘要前两位/完整摘要`，跨日不变。目录名保留兼容布局；新内容格式为V3帧，包含到期时间、负载长度、SHA-256和PHP序列化负载。旧V2数组格式在有效期内仍可读取，下一次写入使用新帧。回滚旧代码时新帧会被当作未命中，缓存需重新生成。

新建目录0700、条目0600；已有目录权限不自动改动。写入继续使用独立分片锁、同目录临时文件和原子替换；损坏/过期回收在锁内复查，避免误删刚更新的值。拒绝符号链接目标，计算锁会核对加锁后路径仍对应同一普通文件。并发原子发布不等于断电后持久性保证。

collectGarbage默认检查200项，0表示全量；持久化 `.gc.cursor` 避免有效前缀造成饥饿，GC之间互斥，繁忙的数据写锁不阻塞回收。预算指检查条目数，不承诺总耗时上限。旧日期目录和无关文件保留。

计算锁单独放在256个稳定分片中，不复用数据写锁，因此生产回调可保存缓存。同一适配器嵌套的不同键可以复用已持有分片，同键递归被拒绝。稳定锁文件不随条目过期删除，数量有界；不能把活动锁文件当作普通过期数据清除。文件锁只适用于共享同一存储且具备所需flock语义的进程。


## MySQL驱动

需要mysqli/mysqlnd，使用专用非持久连接，可选port/socket；utf-8/utf8规范为utf8mb4。已有表先检查结构，不重复执行CREATE；不存在时建立InnoDB/MEDIUMBLOB/BIGINT表。已有表不自动ALTER，检查值列容量、时间列范围、至少32字符的键和覆盖完整摘要的唯一索引，拒绝可能截断/混淆键的表结构。

保存使用参数化UPSERT，不再用REPLACE删除旧行。新值继续使用兼容TEXT的MS_CACHE2 Base64格式，旧原始序列化仍可读；编码存在约三分之一的额外体积，容量检查以实际编码后的列内容为准。SQL在返回大列之前通过OCTET_LENGTH限制读取体积。表容量、服务器包大小等仍可能使存储失败，错误返回false，不自动重放写入。

GET_LOCK计算锁按数据库/表/namespace/键隔离；等待默认5秒，可配置0..60秒。要求所有竞争请求连接同一MySQL写实例，不提供跨独立节点锁。锁释放失败会关闭专用连接以释放会话锁，并保留错误摘要。业务事务和缓存连接保持独立。


## MSSQL驱动及验证范围

新表使用dbo下的CHAR(32)主键、BIGINT到期时间、VARCHAR(MAX)值和过期索引。旧TEXT可读，表结构不自动迁移；旧INT时间列范围有检查。写入参数绑定大文本为VARCHAR(MAX)，到期时间以精确十进制字符串绑定；UPDATE/INSERT位于专用连接的TRY/CATCH事务中，使用UPDLOCK/HOLDLOCK处理并发。写操作消费后续结果，防止把批次中的延后错误当作成功；资源在finally中释放。

本机没有SQLSRV扩展或真实SQL Server环境。本轮只用独立替身验证13个绑定、空值、覆盖、时间、错误传播及资源释放子场景，不能代替真实SQL Server的语法、锁及并发集成测试。MSSQL不声明计算锁能力；数据库层此前对MSSQL共享SQL结果缓存的保守限制保持原样。参考：[原生参数查询](https://learn.microsoft.com/en-us/sql/connect/php/sqlsrv-query?view=sql-server-ver17)、[锁提示语义](https://learn.microsoft.com/en-us/sql/t-sql/queries/hints-transact-sql-table?view=sql-server-ver17)、[默认参数类型](https://learn.microsoft.com/en-us/sql/connect/php/default-sql-server-data-types?view=sql-server-2017)。


## 主要方法：参数、返回和失败约定

### myCache::change

功能说明：切换缓存适配器，沿用 init() 的初始化及默认回收概率

```text
public function change($module, $setting = null);
```

| 参数说明 |
| --- |
| string\|interface_cache $module 驱动简称、类名或已初始化适配器 |
| mixed $setting 传给新驱动构造函数的设置（默认：null） |

**返回**：void 新驱动构造、接口校验或自动回收异常时原目标仍可用

**异常**：Throwable 被调用回调/后端的异常按当前入口继续传递，不包装为成功结果

## 明确调用示例

```php
$cache = new myCache('File', ['path'=>$directory, 'allowed_classes'=>false], 0);
$cache->set('flag', false, 60);
$value = $cache->fetch('flag', $hit);
assert($hit && $value === false);
$result = $cache->rememberLocked('greeting', function () { return 'Hello'; }, 60);
assert($result === 'Hello');
```

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)

## Redis插件支持（2.5.0）

Redis连接、缓存及会话驱动由`plugin/redis/`提供。先安装、启用并配置插件，再在框架参数设置选择Redis缓存或Session；保存前验证插件及实际连接。框架显式选择的存储不依赖应用绑定。缓存清理只删除当前站点及namespace的缓存值，会话故障不回退文件存储。详见[Redis插件](../../showcase/plugins/redis.md)。
