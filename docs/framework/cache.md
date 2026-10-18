# 缓存接口、驱动与清理规则

整理日期：2026-10-18。目标运行环境：PHP 7.4。统一采用最新 V3 帧、lookup／计算锁接口及当前清理约定。

## 复现及修复

| 问题 | 改前复现/影响 | 现在的处理 |
| --- | --- | --- |
| 文件先反序列化再判断到期 | 已过期对象仍执行一次 `__wakeup()` | 新帧先读过期头，旧格式先禁用对象解析元数据；有效记录才按策略实例化 |
| 回调参数含资源 | 两个不同资源均序列化成 `i:0;`，第二次误命中第一次结果 | 拒绝资源及嵌套资源参数，要求明确业务键并缓存实际数据 |
| MySQL 旧TEXT容量 | 宽松SQL模式下超限写入返回true，读回却失败，旧值已被覆盖 | 按实际列容量、编码后字节数及时间列范围提前拒绝，保留旧值 |
| 读取无预算 | 大/损坏缓存文件或数据库列可能占用大量内存 | 序列化、文件读取、SQL读取均有字节预算；SQL在返回列前过滤过长记录 |
| 固定GC前缀 | 有检查预算时反复扫描同一批有效文件，后部过期条目可能长期不被检查 | 保存分片扫描位置，轮转检查；活跃写锁使用非阻塞方式跳过 |
| 缓存值false与未命中混淆 | 旧get协议无法区分 | 增加可选lookup协议和fetch的引用输出，兼容旧get |
| 并发冷启动重复计算 | 普通remember未命中时每个请求都计算 | 新增显式rememberLocked：锁内再次检查，再计算和发布 |
| TTL/回收概率异常值 | NAN可绕过概率范围检查；非法TTL可能在生产回调之后才报错；巨大TTL溢出 | 统一提前校验有限数值与可表示范围；正小数秒向上取整 |
| 切换后端时状态不一致 | 初始化GC抛异常后目标已更换；生产回调change可能把旧结果写到新后端 | GC异常时保留旧目标；每次remember/查询缓存捕获同一后端完成读写 |
| MSSQL旧实现 | false/null/0被删除、重复INSERT冲突、错误引号转义、MySQL时间函数、statement泄漏 | 原生参数绑定、事务内UPDATE/INSERT、实际Unix时间、显式释放及完整消费批次错误 |

前三项使用修改前代码在临时目录及独立MySQL库中确认：过期对象唤醒计数为1；资源回调依次返回first/first；旧TEXT超限写入报告true而后续读取失败。

## 兼容接口与新入口

原 `interface_cache` 的四个声明及必需参数保持不变，不强制第三方驱动修改。`set($key)` 在内置驱动中继续表示删除；显式 `set($key, false/null/'')` 正常保存。TTL非正数仍删除。`clean()` 在内置驱动中只回收过期/损坏数据，不清空有效值；第三方clean可能是flush，框架不会自动调用它。

新增接口有各自的独立加载文件，旧 `interface_cache.class.php` 仍作为兼容入口加载它们：

| 接口/方法 | 用途 | 内置支持 |
| --- | --- | --- |
| interface_cache_lookup::fetch($key, &$hit) | 一次读取同时返回值和命中状态 | File、MySQL、MSSQL |
| interface_cache_lock::synchronized($key, $operation, $timeout=5) | 同后端计算互斥 | File、MySQL |
| myCache::supports($interface) | 检查目标声明的能力，不探测连接健康 | 所有代理目标 |
| myCache::rememberLocked($key, $producer, $ttl=600, $timeout=5) | 双重检查的防击穿入口 | 要求目标支持lock接口 |

```php
$cache = new myCache('File', [
    'path' => CACHE.'data',
    'namespace' => 'catalog',
    'max_bytes' => 8 * 1024 * 1024,
    'allowed_classes' => false,
], 0.1);

$value = $cache->fetch('empty-result', $hit);
if (!$hit) {
    // 只有未命中时才读取业务源；false和null也可能是有效值。
}

$value = $cache->rememberLocked('tenant:42:product:7', function () {
    return loadProduct(7);
}, 600, 5);
```

myCache显式转发fetch引用参数，避免魔术代理丢失hit输出。旧第三方后端没有lookup声明时，代理只能根据get是否返回false保守推断，无法恢复旧协议原本没有的信息；没有lock声明时rememberLocked/synchronized明确抛出BadMethodCallException。

普通remember保留原行为，不增加锁开销。非正TTL直接计算，不读写缓存；getData_func保留非正TTL只删除该回调键的历史约定。两个入口都会在执行回调前验证参数。函数缓存内部键升为func:v4，使旧资源碰撞产生的条目不再被复用；该部分会冷启动一次。对象回调身份仍只在当前代理及对象生命周期内稳定；跨请求缓存应使用包含全部业务上下文的显式键。

## 配置、预算和序列化

File可使用原目录字符串，或含path的选项数组；SQL在现有连接配置中附加以下公共项：

| 配置 | 默认 | 约定 |
| --- | --- | --- |
| max_bytes | 8388608（8MiB） | 原始PHP序列化字节数，允许1..64MiB；不是整个PHP进程的内存上限 |
| max_depth | 128 | 结构深度，允许1..4096 |
| allowed_classes | true | 保留受信对象兼容性；可为false或非空类名数组 |
| namespace | 空字符串 | 非空字符串参与键摘要，避免业务间意外重名；不是权限边界 |
| table（仅SQL） | my_cache | 校验后的简单表名；MSSQL使用dbo schema |

内置get/set/remove的键统一接受有限标量或null，并沿用字符串化规则；数组、对象、资源和NAN/INF键拒绝。无namespace时保留旧MD5键布局。要隔离清理/维护范围，应使用不同目录或表；同根目录/同表的过期记录可被GC一并回收。

编码预算超限时set返回false并提供安全错误摘要，原条目保留；remember仍返回已计算的业务结果，数据库查询仍能以未缓存结果继续。无效参数、资源值、闭包或自定义序列化回调异常仍按相应异常处理。明确或嵌套的资源不能被当作缓存值；自定义 `__serialize`/`Serializable` 实现也须自行遵守可序列化数据约定。

allowed_classes=false使对象成为PHP的 `__PHP_Incomplete_Class`，不是把对象自动变成普通数组；适用于只需要标量/数组的缓存。默认true用于兼容已有受信对象。目录和数据库内容须由可信服务管理，SHA-256是完整性校验，不是带密钥的身份认证。对象唤醒异常视为缓存读取失败，不作为有效命中。

较小预算的读取器遇到其他读取器产生的有效大条目时返回未命中，不删除该有效值；旧V2超限条目无法在预算内检查元数据时会保留。预算涵盖实际序列化字节而非对象展开后的全部内存；业务源输入和自定义对象方法仍需要自己的资源控制。

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

## 并发和故障语义

rememberLocked先读取，未命中才加锁，锁内再次检查；生产成功后发布再释放。测试中File和MySQL各8个同时冷启动进程只计算1次。锁超时/后端不可用时不执行生产回调，异常向调用方传递；回调异常不缓存，finally释放锁。

这用于减少重复计算，不承诺副作用恰好执行一次。存储失败、淘汰、过期、进程终止或管理清理之后可能重新计算；生产回调宜为可重复的读取/计算。查询缓存继续依赖已有共享失效后端、读写位和事务规则，不把缓存描述为跨节点强一致存储。

初始化时只自动调用无需必需参数的显式collectGarbage钩子，GC抛异常不替换原目标。自定义后端的clean含义保持其原规则。外部Memcached插件未纳入本次实库/服务验证，仍可按原四方法协议使用。

## 验证入口

```sh
php tests/cache/cache_regression.php
php tests/cache/cache_regression.php --database
```

测试使用私有临时目录、PHP子进程和随机命名 `mystep_cache_review_*` MySQL库；finally删除独立测试库，不以正式缓存表作为夹具。包含格式兼容/过期对象、资源误命中、容量保护、GC轮转、符号链接、精确命中、故障恢复、类策略和两种8进程并发。SQLSRV夹具只在原生扩展不存在时运行，不打开网络连接。新增/调整方法均已补充功能和参数说明。

接口、公共编解码器、三个驱动及myCache共69个声明方法已核对功能、参数与返回说明。旧四方法协议保持原签名，新增能力为可选协议；函数缓存键升为func:v4，会冷启动一次。完整PHP语法检查文件清单另保存在本轮验证产物中。

## 调用处同步与验证

框架初始化按大小写无关方式识别 MySQL／MSSQL 缓存并传数据库配置。切换后端时，同时更新数据库查询缓存等持有旧对象的调用方。应用的 TTL 参数调整、演示隔离及插件后端挂接分别见 [CMS](../applications/CMS.md)、[示例应用](../applications/examples.md) 与 [现有插件](../plugins/existing-plugins.md)。

验证入口：`php tests/integration/integration_regression.php --database`，使用临时目录、独立 MySQL 测试库及隔离替身；维护规则见 [公共变更维护](maintenance.md)。

## 全量清理、过期回收及后端引用

- 内置 clean()：只回收过期/损坏数据，保留有效值。
- clear()：显式清空当前存储中的数据。File 保留写锁和计算锁；SQL 保留表结构。相同目录或表的所有 namespace 均受影响。
- myStep::clearDataCache()：后台及更新器使用的共享入口，处理框架默认数据文件与当前缓存后端。第三方后端没有明确 clear 方法时报告不支持，不推断 clean 的含义。
- Memcached clear()：沿用该插件原有手动 flush 的服务级范围，可能影响共用服务的其他业务。不会在插件初始化时执行。
- 并发计算或新请求可在清空后重新写入；clear 不承诺全局原子清空或业务副作用恰好执行一次。

原 interface_cache 四方法、interface_db/interface_sql 历史签名不变。现有 CRUD/SQL 构造调用保留兼容入口，无需仅因新增 prepared 接口而批量改写；绑定、事务及缓存挂接仍由原专项测试覆盖。

## 维护入口补充

Admin 的“全部清理”覆盖已登记的七类缓存（包含会话）以及当前数据缓存驱动，保留管理锁和审计目录。数据清理统一通过 myStep::clearDataCache，不能用递归删除数据目录代替驱动 clear。具体页面和确认提示见 [Admin](../applications/Admin.md)。
