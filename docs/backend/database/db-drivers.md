# 数据库驱动

先选择目标驱动并准备相应扩展，再连接数据库。通过supports()检查参数绑定与事务能力；simpleDB使用文件表接口，不能直接执行SQL。

```php
$db = new myDb('MySQL', $host, $user, $password, 'utf8mb4');
$db->connect(false, $database);
$rows = $db->fetchAll('SELECT id,title FROM items WHERE active=?', [1]);
```

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
