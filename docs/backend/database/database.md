# 数据库代理、驱动与能力接口

修订日期：2026-10-23。适用于当前框架。

## 本篇目标与调用场景

区分代理、驱动和可选能力；检查真实声明及返回值，不能把false、null、0或空集合统一当失败。

## 现在的接口结构

| 接口 | 范围 | 内置实现 |
| --- | --- | --- |
| interface_db | 历史连接、CRUD、单条/全部/单值读取及字段信息，共 12 个方法 | MySQL、MSSQL、SQLite、simpleDB |
| interface_sql | 历史结果遍历、库表列表、脚本维护、缓存挂接等，共 14 个方法 | MySQL、MSSQL |
| interface_db_prepared | execute、fetchAll、fetchOne、fetchValue，共 4 个方法 | MySQL、MSSQL、SQLite |
| interface_db_transaction | begin、commit、rollback、transaction，共 4 个方法 | MySQL、SQLite |

四个接口均有与名称对应的独立 `.class.php` 文件。旧的 `require_once 'interface_db.class.php'` 会继续载入原来两个接口，并载入两个可选能力接口；先加载独立文件、再加载旧入口也不会重复声明。

新能力接口彼此独立，也不继承混合了文件数据库或服务器管理方法的旧接口。SQLite 不会为了提供参数绑定就被迫实现数据库列表、状态和脚本文件导入；simpleDB 不会被要求实现 SQL 或事务。

所有接口均补充类说明、方法功能、参数、返回及必要的错误/行为边界，覆盖 34 个声明方法。myDb 新增 supports 方法也补充功能说明和参数备注。


## 保留旧签名的原因

两个旧接口的 26 个方法均保留原来的名称、参数名称、必需参数个数、引用规则及无原生类型声明。没有在旧接口中直接追加 execute/事务，也没有添加形式上“可选”的新参数。

接口增加可选参数仍可能使原先不接收该参数的第三方实现无法通过 PHP 的兼容性检查；把 record 的参数改成可选，也可能使原本保留必需参数的实现不再兼容。强制统一返回类型则会与现有连接返回 bool/驱动对象、关闭返回计数/无返回值等差异冲突。因此用 PHPDoc 说明历史行为，用可选接口表达新增能力。

专项用两个保留原始最小签名的第三方替身分别实现旧接口，并核对所有旧签名；只有方法存在、没有声明新接口的旧驱动仍能调用原方法，但不会被自动认定为已满足新协议。需要能力声明的第三方驱动可自愿实现新接口，并按 PHP 检查的签名完成实现。


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

驱动 transaction 回调接收驱动自身；myDb 的 transaction 回调接收当前代理，保持原有调用方式。


## 操作步骤与注意事项

prepared占位符用于数据值，不用于表名、列名或SQL语法。受管事务正常返回（包括false）会提交，需要回滚时抛异常；文件及邮件不受数据库回滚保护。

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
