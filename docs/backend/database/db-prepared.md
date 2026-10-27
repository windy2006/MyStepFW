# 参数绑定

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

## 操作步骤与注意事项

prepared占位符用于数据值，不用于表名、列名或SQL语法。受管事务正常返回（包括false）会提交，需要回滚时抛异常；文件及邮件不受数据库回滚保护。

## 主要方法：参数、返回和失败约定

### base_sql::fetchAll

功能说明：绑定参数读取全部记录，不参与共享结果缓存

无论成功还是异常都释放结果与构建状态；需要驱动提供 execute()。

```text
public function fetchAll($sql, array $params = [], $mode = 2);
```

| 参数说明 |
| --- |
| string $sql SQL 模板；MySQL 用 ?，SQLite 用 ? 或命名参数 |
| array $params 绑定数据值；不能替代表名、字段名、排序方向及 SQL 片段（默认：[]） |
| int $mode 结果下标：1 数字、2 字段名（默认）、3 两者（默认：2） |

**返回**：array|false 所有记录，空结果为 []；驱动执行失败为 false，保留原生标量类型

**异常**：Throwable 驱动参数校验及查询异常继续传递

### base_sql::fetchOne

功能说明：绑定参数读取第一条记录，不参与共享结果缓存

不自动改写 SQL，较大结果集应在模板中显式 LIMIT；结束或异常时释放结果及构建状态。

```text
public function fetchOne($sql, array $params = [], $mode = 2);
```

| 参数说明 |
| --- |
| string $sql SQL 模板，参数形式由具体驱动决定 |
| array $params 按驱动约定绑定的数据值，不代替标识符（默认：[]） |
| int $mode 结果下标：1 数字、2 字段名（默认）、3 两者（默认：2） |

**返回**：array|false 首条记录；无记录或执行失败为 false

**异常**：Throwable 驱动参数校验及查询异常继续传递

### base_sql::fetchValue

功能说明：绑定参数读取第一行第一列，保留 SQL NULL、0 及空字符串

调用示例：
    $value = $db->fetchValue('SELECT value FROM items WHERE id=?', [1]);
    if ($value === false) {
        // 无记录或执行失败；不要把0、空串或SQL NULL当作false
    }

```text
public function fetchValue($sql, array $params = []);
```

| 参数说明 |
| --- |
| string $sql SQL 模板，按驱动约定提供占位符 |
| array $params 数据值参数，不代替表名、字段名或 SQL 片段（默认：[]） |

**返回**：mixed 第一列值；无记录或执行失败返回 false，SQL NULL 返回 null

**异常**：Throwable 驱动参数校验及查询异常继续传递

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
