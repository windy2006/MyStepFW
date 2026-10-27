# 事务协议

接口说明：`interface_db_transaction`。

## 职责与调用位置

本文件提供数据库底层契约或驱动实现，调用前核对目标是否声明相应能力；SQL方言、错误及事务语义按本页各方法说明使用。

源码：`lib/database/interface_db_transaction.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
// 在应用已取得当前$db的上下文中，先检测能力再调用。
if ($db->supports(interface_db_prepared::class)) {
    $row = $db->fetchOne('SELECT id,title FROM items WHERE id=?', [$id]);
}
// items为业务示意表，$id必须先验证；连接参数由当前应用配置提供。
```

## 声明与类职责

### interface_db_transaction

功能说明：为数据库驱动提供可选的受管事务和嵌套保存点契约。

目前由 MySQL 和 SQLite 声明实现；不能仅凭存在 begin() 方法推断事务能力。
MySQL 表是否支持事务取决于存储引擎；本协议不接管已有原生 SQL 事务。
受管回调内不得自行提交事务或执行会导致隐式提交的 DDL。

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `interface_db_transaction::begin` | 开始一层受管事务，嵌套层使用保存点。 |
| `interface_db_transaction::commit` | 提交一层；内层释放保存点，最外层提交并发布缓存失效。 |
| `interface_db_transaction::rollback` | 回滚一层；内层回滚并释放保存点，最外层回滚事务。 |
| `interface_db_transaction::transaction` | 执行受管回调；正常返回（包括false）提交，异常回滚并继续抛出。 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### interface_db_transaction::begin

功能说明：开始一层受管事务，嵌套层使用保存点。

参数说明：无显式参数。

```text
public function begin();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 开启成功为true，驱动执行失败可为false

**异常与失败**：LogicException 已有外部事务、边界已被打断或回调内手动修改边界；Throwable 数据库执行异常继续传递

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_db_transaction.class.php:53`。

### interface_db_transaction::commit

功能说明：提交一层；内层释放保存点，最外层提交并发布缓存失效。

参数说明：无显式参数。

```text
public function commit();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 成功为true，驱动执行失败可为false

**异常与失败**：LogicException 没有有效受管事务或调用试图改变回调拥有的边界；Throwable 数据库执行异常继续传递

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_db_transaction.class.php:64`。

### interface_db_transaction::rollback

功能说明：回滚一层；内层回滚并释放保存点，最外层回滚事务。

参数说明：无显式参数。

```text
public function rollback();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 成功为true，驱动执行失败可为false

**异常与失败**：LogicException 没有有效受管事务或调用试图改变回调拥有的边界；Throwable 数据库执行异常继续传递

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_db_transaction.class.php:75`。

### interface_db_transaction::transaction

功能说明：执行受管回调；正常返回（包括false）提交，异常回滚并继续抛出。

```text
public function transaction(callable $callback);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$callback` | callable；接收当前驱动的回调；代理入口则接收当前myDb |

**返回值**：mixed 回调的原始返回值

**异常与失败**：Throwable 回调、事务边界或数据库操作异常继续向外传递

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_db_transaction.class.php:87`。
