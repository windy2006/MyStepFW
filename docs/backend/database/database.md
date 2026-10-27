# 数据库代理、驱动与能力接口

## 本篇目标与调用场景

区分代理、驱动和可选能力；检查真实声明及返回值，不能把false、null、0或空集合统一当失败。

## 接口结构

| 接口 | 范围 | 内置实现 |
| --- | --- | --- |
| interface_db | 连接、CRUD、单条/全部/单值读取及字段信息，共 12 个方法 | MySQL、MSSQL、SQLite、simpleDB |
| interface_sql | 结果遍历、库表列表、脚本维护、缓存挂接等，共 14 个方法 | MySQL、MSSQL |
| interface_db_prepared | execute、fetchAll、fetchOne、fetchValue，共 4 个方法 | MySQL、MSSQL、SQLite |
| interface_db_transaction | begin、commit、rollback、transaction，共 4 个方法 | MySQL、SQLite |

能力接口彼此独立，也不继承混合了文件数据库或服务器管理方法的接口。SQLite 不会为了提供参数绑定就被迫实现数据库列表、状态和脚本文件导入；simpleDB 不会被要求实现 SQL 或事务。

## 能力检查与调用

```php
$db = new myDb('MySQL', $host, $user, $password, 'utf8mb4');
$db->connect(false, $database);

if ($db->supports(interface_db_prepared::class)) {
    $row = $db->fetchOne('SELECT * FROM items WHERE id=?', [$id]);
}

if ($db->supports(interface_db_transaction::class)) {
    $db->transaction(function ($tx) use ($id, $value) {
        $tx->execute('UPDATE items SET value=? WHERE id=?', [$value, $id]);
    });
}
```

supports 检查内部代理目标是否明确声明指定接口；未初始化、非法/未知名称、普通类名返回 false，也可用于可自动加载的自定义接口。不运行 SQL，不证明扩展已安装、连接健康、数据库权限充分或当前表是事务引擎。自定义自动加载器的异常继续传给调用方。

myDb 自身继续作为通用代理，不伪装成所有目标都具有参数绑定/事务能力的接口对象。因此 `$db->supports(interface_db_prepared::class)` 为 true，不代表 `$db instanceof interface_db_prepared` 为 true。需要接口类型约束时直接接收驱动对象：

```php
function readValue(interface_db_prepared $driver, $id) {
    return $driver->fetchValue('SELECT value FROM items WHERE id=?', [$id]);
}

$driver = new SQLite(':memory:');
// 创建/准备表后，可将 $driver 交给 readValue。
```

驱动 transaction 回调接收驱动自身；myDb 的 transaction 回调接收当前代理，保持调用方式。

## 操作步骤与注意事项

prepared占位符用于数据值，不用于表名、列名或SQL语法。受管事务正常返回（包括false）会提交，需要回滚时抛异常；文件及邮件不受数据库回滚保护。

## 配套完整示例

阅读[CRUD与SQLBuilder](db-example.md)：包含准备步骤、完整代码、预期输出及实际应用中的替换位置。先跑通示例，再查本页逐方法参数。

## 主要方法：参数、返回和失败约定

### myDb::supports

功能说明：检查代理驱动是否声明实现指定能力接口，不执行SQL或检测服务器健康。

例如supports(interface_db_prepared::class)判断参数绑定API；
supports(interface_db_transaction::class)判断受管事务API。MSSQL即使由trait
带有begin方法，也不会误报支持受管事务。第三方驱动可自愿实现对应协议。

```text
public function supports($interface);
```

| 参数说明 |
| --- |
| string $interface 接口名称；接受已加载或可自动加载的自定义接口 |

**返回**：bool 目标声明实现该接口时为true；未初始化、未知名称或类名为false

**异常**：Throwable 自定义自动加载器产生的异常继续传递

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
