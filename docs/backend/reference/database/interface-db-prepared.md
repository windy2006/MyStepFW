# interface_db_prepared：interface_db_prepared 数据库驱动／协议

修订日期：2026-10-23。功能详解。

## 职责与调用位置

本文件提供数据库底层契约或驱动实现，调用前核对目标是否声明相应能力；SQL方言、错误及事务语义按本页各方法说明使用。

源码：`lib/database/interface_db_prepared.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

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

### interface_db_prepared

功能说明：为 SQL 驱动提供可选的原生参数绑定和统一结果读取契约。

该协议独立于旧管理接口，不强制文件数据库实现 SQL 功能。
实现接口表示提供对应 API，不证明数据库已连接、扩展已安装或 SQL 可跨库运行。
参数形式、数据类型和错误策略以具体驱动说明为准。

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `interface_db_prepared::execute` | 执行原生绑定SQL，结果留给驱动读取；不使用共享结果缓存或自动重放写入。 |
| `interface_db_prepared::fetchAll` | 绑定参数并读取全部记录，结束或异常时释放当前结果与构建状态。 |
| `interface_db_prepared::fetchOne` | 绑定参数读取第一条记录；不自动改写SQL，较大结果集应自行指定分页。 |
| `interface_db_prepared::fetchValue` | 绑定参数读取首行首列，并释放当前结果。 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### interface_db_prepared::execute

功能说明：执行原生绑定SQL，结果留给驱动读取；不使用共享结果缓存或自动重放写入。

```text
public function execute($sql, array $params = []);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；非空SQL模板；MySQL/MSSQL用?，SQLite还支持命名参数 |
| `$params` | array；数据值列表/映射，流、NULL及描述数组按驱动规则处理（默认：[]） |

**返回值**：int|false 行数或影响数；SQLite读查询为0，MSSQL未知行数可为-1；用===false判失败

**异常与失败**：InvalidArgumentException SQL或绑定参数不合法；Throwable 原生扩展、查询和框架错误策略的异常继续向外传递

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_db_prepared.class.php:53`。

### interface_db_prepared::fetchAll

功能说明：绑定参数并读取全部记录，结束或异常时释放当前结果与构建状态。

```text
public function fetchAll($sql, array $params = [], $mode = 2);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；原生SQL模板，参数形式依驱动决定 |
| `$params` | array；绑定的数据值，不能替代标识符（默认：[]） |
| `$mode` | int；1数字下标、2字段名（默认）、3两者（默认：2） |

**返回值**：array|false 空结果为[]，执行失败为false；字段保留原生类型

**异常与失败**：Throwable 参数、执行或结果读取异常继续向外传递

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_db_prepared.class.php:64`。

### interface_db_prepared::fetchOne

功能说明：绑定参数读取第一条记录；不自动改写SQL，较大结果集应自行指定分页。

```text
public function fetchOne($sql, array $params = [], $mode = 2);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；原生SQL模板 |
| `$params` | array；绑定的数据值（默认：[]） |
| `$mode` | int；1数字下标、2字段名（默认）、3两者（默认：2） |

**返回值**：array|false 首条记录；无记录或执行失败为false

**异常与失败**：Throwable 参数、执行或结果读取异常继续向外传递

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_db_prepared.class.php:75`。

### interface_db_prepared::fetchValue

功能说明：绑定参数读取首行首列，并释放当前结果。

```text
public function fetchValue($sql, array $params = []);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；原生SQL模板 |
| `$params` | array；绑定的数据值（默认：[]） |

**返回值**：mixed 原生标量（保留NULL、0、空字符串）；无记录或失败为false

**异常与失败**：Throwable 参数、执行或结果读取异常继续向外传递

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_db_prepared.class.php:85`。
