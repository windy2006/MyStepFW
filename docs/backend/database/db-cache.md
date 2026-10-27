# 查询缓存

## 本篇目标与调用场景

区分代理、驱动和可选能力；检查真实声明及返回值，不能把false、null、0或空集合统一当失败。

## 参数化执行与共享缓存

参数只能代替值，标识符和 SQL 片段使用固定值、白名单及 quoteIdentifier。MySQL 参数化读取需要 mysqlnd，SQLite 支持自身命名形式；fetchOne 的大结果查询应显式 LIMIT。fetch* 不使用共享结果缓存，保留原生标量类型。

受管事务正常返回（包括 false）时提交；抛异常回滚。MySQL／SQLite 支持保存点嵌套；回调不自行提交或执行隐式提交 DDL。事务内不复用或发布共享查询缓存，提交后推进共享代次，回滚不发布未提交结果。

已知会话变量、临时表、锁定读取及非确定函数采用保守缓存策略。原生连接绕过驱动的 SQL 无法自动跟踪，调用者关闭缓存并自行失效。myCache::getData 只在数据库已 setCache 挂接失效后端时缓存，遵循读写位；未挂接则直接查询。跨进程可见性仍受后端故障和 TTL 限制，不承诺强一致。

MySQL 活动连接字符集决定文本转义，不主动修改wait_timeout；安全且无会话依赖的读取可断线后重连一次，写入和事务不自动重放。

### 事务与缓存

- SQLite 原生 SAVEPOINT 在未 BEGIN 时也会开始事务，现在跟踪根保存点、重名嵌套、ROLLBACK TO 和 RELEASE。
- 根保存点未提交时不复用或发布共享结果；释放根保存点提交后更新共享代次。
- 回调事务拒绝手写事务控制及直接调用 begin/commit/rollback 改变边界，框架自身的嵌套 transaction 继续通过保存点运行。
- 已知会隐式提交的 MySQL DDL、事务控制及 AUTOCOMMIT 修改，在受管事务执行前报 LogicException，避免先提交再发现范围改变。
- 这些检查不分析存储过程内部，也不能阻止绕过驱动的原生连接操作；仍要求事务表，文件/邮件等副作用不受数据库回滚保障。
- MySQL 重新 connect 且未选库时清空之前的数据库身份，不再复用之前的数据库上下文的缓存。SQL mode 策略也进入命名空间，隔离相同 SQL 在不同模式下的语义。

## 操作步骤与注意事项

prepared占位符用于数据值，不用于表名、列名或SQL语法。受管事务正常返回（包括false）会提交，需要回滚时抛异常；文件及邮件不受数据库回滚保护。

## 主要方法：参数、返回和失败约定

### base_db::setCache

功能说明：挂接共享缓存后端及查询条目的 TTL，后续写操作通过同后端传播失效

```text
public function setCache($cache, $ttl = 600);
```

| 参数说明 |
| --- |
| interface_cache\|myCache\|null $cache 提供 get()/set() 的共享后端，null 关闭挂接 |
| int $ttl 查询缓存有效秒数，默认 600；共享代次存活至少一天且长于该 TTL（默认：600） |

**返回**：void 不迁移原后端条目；不同连接需要相同后端及一致的命名空间才能传播失效

### base_db::invalidateCache

功能说明：更新共享缓存代次，并清空当前请求记录的读写键关联

参数说明：无显式参数。

```text
public function invalidateCache();
```

**返回**：void 不逐条删除旧查询结果；旧代次条目随后按各自 TTL 过期

### base_db::isCacheable

功能说明：保守判断 SQL 是否可复用共享结果，同时作为 MySQL 安全重试的筛选条件

仅允许普通 SELECT；事务/手动提交/会话状态、临时表、锁定读取、用户变量、
可执行注释和已知非确定函数绕过缓存。不能证明任意用户函数的确定性。

```text
public function isCacheable($sql);
```

| 参数说明 |
| --- |
| string $sql 完整 SQL 查询；非字符串直接判为不可缓存 |

**返回**：bool 通过已实现筛选时为 true；MSSQL 共享结果缓存保守关闭

## 明确调用示例

```php
$db = new myDb('SQLite'); // 独立内存库，不使用业务数据库。
$db->execute('CREATE TABLE items(id INTEGER PRIMARY KEY,title TEXT)');
$db->execute('INSERT INTO items(id,title) VALUES(?,?)', [1,'Hello']);
assert($db->fetchOne('SELECT * FROM items WHERE id=?', [1])['title'] === 'Hello');
$db->transaction(function ($tx) { $tx->execute('UPDATE items SET title=? WHERE id=?', ['World',1]); });
assert($db->fetchValue('SELECT title FROM items WHERE id=?',[1]) === 'World');
```

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
