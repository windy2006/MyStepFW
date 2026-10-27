# 事务、回调、保存点与失败

## 本篇目标与调用场景

区分代理、驱动和可选能力；检查真实声明及返回值，不能把false、null、0或空集合统一当失败。

## 返回值与方言边界

- execute/query 的成功计数不能统一按真假判断。SQLite 读查询成功返回 0，MSSQL 无法确定行数时可返回 -1；应以 `=== false` 判断显式执行失败，并按驱动错误策略处理异常。
- 新 fetchAll 空结果为 `[]`；fetchOne 无记录为 false；fetchValue 保留 SQL NULL、0、空字符串，无记录/显式失败为 false。布尔字段 false 与无记录需要区分时，使用 fetchOne 后检查记录是否存在。
- 绑定只替代数据值，不替代表名、字段、排序方向或 SQL 片段。MySQL/MSSQL 使用位置占位符，SQLite 支持其原生命名形式；流参数和 SQLSRV 描述数组仍遵循驱动各自约定。
- 受管事务正常返回，包括返回 false，仍会提交；需要回滚须抛异常或使用明确的事务控制。已有外部事务、回调边界与隐式提交 DDL 的限制继续生效。
- interface_db 仍支持 simpleDB 的文件/条件/行号语义，不能将其参数与 SQL 驱动互换。
- interface_sql 是管理能力集，不能等同所有 SQL 引擎。旧 getPri 不完整表达复合主键；getIdxScript 在 MySQL 中返回建表片段、MSSQL 返回完整语句，不能假设每项都是独立 SQL。
- 新接口不会让 SQL 方言、数据库异常、备份一致性和权限规则自动跨引擎统一。相关运行限制见 本文“数据库驱动改进记录”相关章节。

### 事务与缓存

- SQLite 原生 SAVEPOINT 在未 BEGIN 时也会开始事务，现在跟踪根保存点、重名嵌套、ROLLBACK TO 和 RELEASE。
- 根保存点未提交时不复用或发布共享结果；释放根保存点提交后更新共享代次。
- 回调事务拒绝手写事务控制及直接调用 begin/commit/rollback 改变边界，框架自身的嵌套 transaction 继续通过保存点运行。
- 已知会隐式提交的 MySQL DDL、事务控制及 AUTOCOMMIT 修改，在受管事务执行前报 LogicException，避免先提交再发现范围改变。
- 这些检查不分析存储过程内部，也不能阻止绕过驱动的原生连接操作；仍要求事务表，文件/邮件等副作用不受数据库回滚保障。
- MySQL 重新 connect 且未选库时清空之前的数据库身份，不再复用之前的数据库上下文的缓存。SQL mode 策略也进入命名空间，隔离相同 SQL 在不同模式下的语义。

## 操作步骤与注意事项

prepared占位符用于数据值，不用于表名、列名或SQL语法。受管事务正常返回（包括false）会提交，需要回滚时抛异常；文件及邮件不受数据库回滚保护。

## 配套完整示例

阅读[保存点、false返回与回滚](transaction-example.md)：包含准备步骤、完整代码、预期输出及实际应用中的替换位置。先跑通示例，再查本页逐方法参数。

## 主要方法：参数、返回和失败约定

### myDb::transaction

功能说明：委托驱动执行事务，使回调接收当前代理而不是内部驱动

正常返回（包括 false）提交；异常回滚并继续抛出，嵌套事务由驱动使用保存点。
回调不可自行提交或执行隐式提交 DDL；文件、邮件等外部副作用不受回滚保障。

调用示例：
    if ($db->supports(interface_db_transaction::class)) {
        $id = $db->transaction(function ($tx) {
            $tx->execute('INSERT INTO items(name) VALUES(?)', ['示例']);
            return $tx->getInsertId();
        });
    }

```text
public function transaction(callable $callback);
```

| 参数说明 |
| --- |
| callable $callback 接收当前 myDb 对象的业务回调，返回值作为本方法结果 |

**返回**：mixed 回调正常返回的值

**异常**：BadMethodCallException 目标没有可调用的事务入口或驱动不支持受管事务；Throwable 驱动启动/提交/回滚或业务回调异常原样向外传递

### base_db::transaction

功能说明：在受管事务中执行回调，正常返回即提交，抛异常时回滚到进入前层级

支持嵌套保存点，正常返回 false 也会提交。回调不得自行提交、回滚或执行隐式提交
DDL；检查到范围改变会抛异常，但已经发生的外部提交不能撤销。仅支持事务表，
文件、邮件等外部副作用也不能回滚；回滚自身失败会记录日志并继续抛原异常。

```text
public function transaction(callable $callback);
```

| 参数说明 |
| --- |
| callable $callback 接收当前驱动的业务回调；代理入口另行包装为接收 myDb |

**返回**：mixed 回调正常返回值

**异常**：RuntimeException 无法开始/提交事务或回调改变受管范围；LogicException 外部事务冲突或受管事务中断；Throwable 业务回调和驱动异常继续传递

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
