# 数据库协议

接口说明：`interface_db`。

## 职责与调用位置

本文件提供数据库底层契约或驱动实现，调用前核对目标是否声明相应能力；SQL方言、错误及事务语义按本页各方法说明使用。

源码：`lib/database/interface_db.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

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

### interface_db

功能说明：声明连接、CRUD、结果读取及字段查询的基础入口，并加载SQL与可选能力接口。

使用说明：
  1. 由MySQL/MSSQL/SQLite/simpleDB实现，保留旧方法签名。
  2. SQL驱动和文件驱动对部分参数/返回值的解释不同，应查阅具体实现。
  3. 参数绑定、受管事务等能力通过独立接口及myDb::supports()检查。

补充说明：
兼容SQL驱动和simpleDB的历史数据库入口协议。

保留原方法、参数个数及无类型签名，允许已有第三方驱动继续实现；这不是统一的
SQL语义协议：simpleDB使用文件、条件数组及行号，SQL驱动使用连接与构建器。
驱动可提供更多可选参数，具体格式见其方法说明。参数化查询和受管事务分别由
interface_db_prepared、interface_db_transaction表达，不强加给所有旧驱动。
历史上载入本文件会同时定义interface_sql，该加载方式继续有效。

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `interface_db::connect` | 建立或检查连接；连接参数由构造/init及具体驱动的可选参数决定。 |
| `interface_db::selectDB` | 选择数据库或切换本地文件；不保证不同驱动的连接状态切换过程相同。 |
| `interface_db::insert` | 插入记录；SQL驱动使用build/field/values状态，simpleDB通过额外参数接收记录。 |
| `interface_db::select` | 查询当前表；SQL驱动执行/预览构建的SELECT，simpleDB返回筛选记录。 |
| `interface_db::update` | 更新记录；SQL驱动使用当前构建条件，simpleDB额外接收条件和字段数据。 |
| `interface_db::delete` | 删除匹配记录；空条件、事务和预览行为以具体驱动说明为准。 |
| `interface_db::query` | 执行历史查询入口；不提供跨驱动参数绑定或统一行数语义。 |
| `interface_db::record` | 读取单条记录；SQL驱动可用当前构建器，simpleDB按行号读取。 |
| `interface_db::records` | 读取全部记录；SQL执行查询，simpleDB读取当前文件的全部数据。 |
| `interface_db::result` | 读取单个字段；SQL驱动取第一行第一列，simpleDB使用条件及可选字段参数。 |
| `interface_db::getFields` | 获取表结构；SQL驱动返回字段名称，simpleDB返回字段宽度及记录统计元数据。 |
| `interface_db::close` | 释放结果及连接/文件句柄；各驱动可以额外接收可选错误输出参数。 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### interface_db::connect

功能说明：建立或检查连接；连接参数由构造/init及具体驱动的可选参数决定。

参数说明：无显式参数。

```text
public function connect();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool|object 内置MySQL/SQLite/simpleDB返回bool，MSSQL成功返回自身

**异常与失败**：Throwable 原生扩展、连接或框架错误策略的异常继续传递

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_db.class.php:59`。

### interface_db::selectDB

功能说明：选择数据库或切换本地文件；不保证不同驱动的连接状态切换过程相同。

```text
public function selectDB($the_db);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$the_db` | string；SQL数据库名；SQLite文件路径；simpleDB表名 |

**返回值**：bool|object 是否成功，或驱动自身，依实现约定

**异常与失败**：Throwable 参数校验或存储失败时依驱动错误策略处理

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_db.class.php:68`。

### interface_db::insert

功能说明：插入记录；SQL驱动使用build/field/values状态，simpleDB通过额外参数接收记录。

参数说明：无显式参数。

```text
public function insert();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：int|bool|string 执行结果；SQL驱动的可选预览模式返回SQL文本

**异常与失败**：Throwable 参数及执行异常依具体驱动处理

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_db.class.php:78`。

### interface_db::select

功能说明：查询当前表；SQL驱动执行/预览构建的SELECT，simpleDB返回筛选记录。

参数说明：无显式参数。

```text
public function select();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：int|array|false|string 行数、记录、失败或预览SQL，依驱动及模式决定

**异常与失败**：Throwable 参数及执行异常依具体驱动处理

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_db.class.php:88`。

### interface_db::update

功能说明：更新记录；SQL驱动使用当前构建条件，simpleDB额外接收条件和字段数据。

参数说明：无显式参数。

```text
public function update();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：int|bool|string 受影响数、匹配结果或预览SQL；0不等于失败

**异常与失败**：Throwable 参数及执行异常依具体驱动处理

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_db.class.php:98`。

### interface_db::delete

功能说明：删除匹配记录；空条件、事务和预览行为以具体驱动说明为准。

参数说明：无显式参数。

```text
public function delete();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：int|bool|string 执行结果或预览SQL；空条件可能删除整表数据

**异常与失败**：Throwable 参数及执行异常依具体驱动处理

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_db.class.php:108`。

### interface_db::query

功能说明：执行历史查询入口；不提供跨驱动参数绑定或统一行数语义。

```text
public function query($sql);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string\|array；SQL驱动接收可信SQL；simpleDB接收条件字符串/数组 |

**返回值**：int|array|false SQL行数/影响数或文件记录；SQLite读查询成功为0

**异常与失败**：Throwable SQL、条件或存储异常依驱动策略处理

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_db.class.php:117`。

### interface_db::record

功能说明：读取单条记录；SQL驱动可用当前构建器，simpleDB按行号读取。

```text
public function record($sql);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string\|int；SQL或表名；simpleDB为行号，0取首行 |

**返回值**：array|false 一条记录，无记录或失败为false

**异常与失败**：Throwable 查询及存储异常依驱动策略处理

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_db.class.php:126`。

### interface_db::records

功能说明：读取全部记录；SQL执行查询，simpleDB读取当前文件的全部数据。

```text
public function records($sql);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；SQL或表名；simpleDB仅保留该兼容占位参数 |

**返回值**：array|false 空结果为数组，失败为false

**异常与失败**：Throwable 查询及存储异常依驱动策略处理

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_db.class.php:135`。

### interface_db::result

功能说明：读取单个字段；SQL驱动取第一行第一列，simpleDB使用条件及可选字段参数。

```text
public function result($sql);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string\|array；SQL或文件筛选条件 |

**返回值**：mixed 保留字段类型；SQL无记录为false，simpleDB无记录为空字符串

**异常与失败**：Throwable 查询及存储异常依驱动策略处理

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_db.class.php:144`。

### interface_db::getFields

功能说明：获取表结构；SQL驱动返回字段名称，simpleDB返回字段宽度及记录统计元数据。

```text
public function getFields($the_tbl);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$the_tbl` | string；SQL表名；simpleDB忽略本参数并使用当前文件 |

**返回值**：array 具体字段结构依驱动约定，不承诺统一列元数据格式

**异常与失败**：Throwable 表查询或文件读取异常依驱动策略处理

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_db.class.php:153`。

### interface_db::close

功能说明：释放结果及连接/文件句柄；各驱动可以额外接收可选错误输出参数。

参数说明：无显式参数。

```text
public function close();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：int|null 内置SQL驱动返回累计执行次数，simpleDB无返回值

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/interface_db.class.php:162`。
