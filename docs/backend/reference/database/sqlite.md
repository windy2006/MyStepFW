# SQLite

## 职责与调用位置

本文件提供数据库底层契约或驱动实现，调用前核对目标是否声明相应能力；SQL方言、错误及事务语义按本页各方法说明使用。

源码：`lib/database/sqlite.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

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

### SQLite

功能说明：基于SQLite3提供参数绑定、受管事务、结构脚本与受限SQL兼容转换。

使用说明：
  1. 可使用内存数据库或受信SQLite文件，通常经myDb调用。
  2. execute/fetch*支持位置或命名参数；读取成功返回0也属于正常执行。
  3. 只声明实际实现的基础、参数绑定和事务协议，不冒充完整SQL管理接口。

补充说明：
SQLite3 驱动，提供原生参数绑定并复用 SQL 构建、缓存和事务辅助能力

    $sqlite = new SQLite();                         // 打开独立 :memory: 数据库
    $sqlite->init($file);                            // 打开或创建文件库，关闭原连接
    $sqlite->execute('INSERT INTO items(value) VALUES(?)', [$value]);
    $rows = $sqlite->fetchAll('SELECT * FROM items WHERE id=:id', ['id' => $id]);
    $sqlite->transaction(function ($tx) { ... });      // 嵌套调用采用保存点

使用说明：
1. 空参数构造即可使用独立内存库，重新 init() 不会复用原内存库缓存身份。
2. query() 委托 execute()，读查询成功返回 0；结果通过 getRS() 或 fetch*() 读取。
   写查询返回 SQLite changes()，失败返回 false 或按异常策略抛出。
3. execute() 支持位置和命名参数，SQL NULL、有限数字、文本及二进制流按类型绑定。
4. convertSQL() 仅做有限兼容，不能把任意 MySQL 方言转换为 SQLite；转义使用
   SQLite3 自身规则。事务、缓存和结果读取的具体约定参见 base_db/base_sql。

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
protected $active_statement = null;
```

```text
protected
        $obj = null,
        $err = false,
        $err_info = '',
        $file_db = '',
        $count = 0;
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `SQLite::__construct` | 打开 SQLite 数据库，无参数时创建独立内存库 |
| `SQLite::init` | 关闭旧连接，打开指定库并重置缓存、事务及内存库隔离身份 |
| `SQLite::connect` | 检查是否已持有 SQLite3 对象，用于兼容数据库接口 |
| `SQLite::selectDB` | 切换 SQLite 文件或内存库，委托 init() 关闭旧库并打开新库 |
| `SQLite::convertSQL` | 做有限的 MySQL 兼容转换，不修改字符串和注释中的 LOW_PRIORITY |
| `SQLite::safeValue` | 按 SQLite 规则转义单引号，避免使用 MySQL 反斜杠语义 |
| `SQLite::query` | 执行无参数 SQL，委托 execute() 并沿用其错误及资源管理策略 |
| `SQLite::execute` | 原生预处理并按类型绑定参数，成功后更新事务及共享缓存跟踪 |
| `SQLite::getRS` | 从当前结果读取下一行，空结果或结束时返回 false |
| `SQLite::record` | 复用 base_sql 的单条记录读取及缓存/资源清理规则 |
| `SQLite::result` | 复用 base_sql 的第一行第一列读取，保留 SQL NULL 及零值 |
| `SQLite::getFields` | 取得表字段名，表名作为单个标识符引用，支持空格和引号 |
| `SQLite::getTbls` | 列出普通表，读取完成后释放结果 |
| `SQLite::getCreateScript` | 从 sqlite_master 读取原始建表语句，保留复合主键、默认值、约束和生成列 |
| `SQLite::getDataScript` | 导出可回放 INSERT，保留 NULL、数字、文本及 BLOB，排除生成列 |
| `SQLite::getIdxScript` | 返回独立创建的索引脚本，表内 UNIQUE/PRIMARY KEY 已包含在建表语句中 |
| `SQLite::getInsertId` | 取得新插入的自增ID |
| `SQLite::free` | 释放当前 SQLite3Result 及关联 SQLite3Stmt，并清空两者引用 |
| `SQLite::close` | 释放结果并关闭当前库，重置事务、会话、临时表及构建器状态 |
| `SQLite::checkError` | 检查错误 |
| `SQLite::clearError` | 清空已有错误 |
| `SQLite::error` | 错误处理 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### SQLite::__construct

功能说明：打开 SQLite 数据库，无参数时创建独立内存库

```text
public function __construct();
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `...$args` | mixed；按init声明顺序传入的SQLite路径等设置（位置参数，由func_get_args读取） |
| `$file` | string；位置参数说明：可选文件路径或 :memory:，转给 init() |
| `$flag` | int；位置参数说明：可选 SQLite3 打开标志，默认 -1 自动判断 |
| `$key` | string\|null；位置参数说明：可选加密密钥，须由 SQLite 构建提供加密支持 |
| `$exception` | bool；位置参数说明：可选是否让 SQLite3 抛异常，默认 false |
| `$timeout` | int；位置参数说明：可选忙等待毫秒数，默认 5000 |

**返回值**：void 初始化或释放资源，无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/sqlite.class.php:104`。

### SQLite::init

功能说明：关闭旧连接，打开指定库并重置缓存、事务及内存库隔离身份

```text
public function init($file=':memory:', $flag=-1, $key=null, $exception=false, $timeout=5000);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；文件路径或 :memory:，默认独立内存库（默认：':memory:'） |
| `$flag` | int；SQLite3 打开标志；-1 自动选择读写，文件不存在时加 CREATE（默认：-1） |
| `$key` | string\|null；可选数据库加密密钥，是否支持由 SQLite 构建决定（默认：null） |
| `$exception` | bool；是否启用 SQLite3 异常，默认 false（默认：false） |
| `$timeout` | int；busyTimeout 等待毫秒数，默认 5000（默认：5000） |

**返回值**：void 打开失败由 SQLite3/框架错误策略处理

调用形式：

```php
// $instance为已初始化的SQLite实例。
$result = $instance->init($file, $flag, $key, $exception, $timeout);
```

定位：`lib/database/sqlite.class.php:118`。

### SQLite::connect

功能说明：检查是否已持有 SQLite3 对象，用于兼容数据库接口

参数说明：无显式参数。

```text
public function connect();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 已初始化为 SQLite3 对象时为 true；本方法不重新打开数据库

调用形式：

```php
// $instance为已初始化的SQLite实例。
$result = $instance->connect();
```

定位：`lib/database/sqlite.class.php:138`。

### SQLite::selectDB

功能说明：切换 SQLite 文件或内存库，委托 init() 关闭旧库并打开新库

```text
public function selectDB($file, $flag=-1, $key=null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；目标文件路径或 :memory: |
| `$flag` | int；打开标志，默认 -1 按是否存在自动决定（默认：-1） |
| `$key` | string\|null；可选数据库加密密钥（默认：null） |

**返回值**：bool 初始化完成为 true，错误由当前框架策略处理

调用形式：

```php
// $instance为已初始化的SQLite实例。
$result = $instance->selectDB($file, $flag, $key);
```

定位：`lib/database/sqlite.class.php:150`。

### SQLite::convertSQL

功能说明：做有限的 MySQL 兼容转换，不修改字符串和注释中的 LOW_PRIORITY

```text
public function convertSQL($sql);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；可信 SQL，移除实际 LOW_PRIORITY 单词并将起始 TRUNCATE TABLE 转 DELETE FROM |

**返回值**：string 转换后的 SQL；SQLite 原生支持 LIMIT offset,count，不额外重写

调用形式：

```php
// $instance为已初始化的SQLite实例。
$result = $instance->convertSQL($sql);
```

定位：`lib/database/sqlite.class.php:161`。

### SQLite::safeValue

功能说明：按 SQLite 规则转义单引号，避免使用 MySQL 反斜杠语义

```text
public function safeValue($value);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$value` | mixed；文本或标量，布尔先转整数；二进制值及外部输入优先参数绑定 |

**返回值**：string 转义后但不含两端 SQL 引号的文本

调用形式：

```php
// $instance为已初始化的SQLite实例。
$result = $instance->safeValue($value);
```

定位：`lib/database/sqlite.class.php:173`。

### SQLite::query

功能说明：执行无参数 SQL，委托 execute() 并沿用其错误及资源管理策略

```text
public function query($sql);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；非空完整 SQL，不能含未提供绑定值的占位符 |

**返回值**：int|false 读查询成功为 0，写查询为 changes()，失败为 false；结果通过 getRS() 取出

调用形式：

```php
// $instance为已初始化的SQLite实例。
$result = $instance->query($sql);
```

定位：`lib/database/sqlite.class.php:184`。

### SQLite::execute

功能说明：原生预处理并按类型绑定参数，成功后更新事务及共享缓存跟踪

位置参数按数组遍历顺序从 1 绑定；命名参数允许带 :/@/$ 前缀或使用裸名称。
文本含 NUL 或非法 UTF-8 时按 BLOB；流从当前位置读取为 BLOB，不按块保留流式内存。
读查询保留语句及结果供 getRS() 使用，free() 负责释放；失败或非读查询关闭语句。

```text
public function execute($sql, array $params = []);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；非空 SQL 模板，使用 ? 或命名占位符；参数不能替代标识符或 SQL 片段 |
| `$params` | array；标量、null 或可读流；数量须与 SQLite 参数数一致，不混用两种命名方式（默认：[]） |

**返回值**：int|false 读查询成功为 0，写查询为受影响行数，无连接或执行失败为 false

**异常与失败**：InvalidArgumentException SQL、参数个数/名称/类型非法或浮点数非有限；RuntimeException 无法读取二进制流；BadMethodCallException 写入 RETURNING 在执行前拒绝，避免 PHP 7.4 SQLite 结果读取重复执行写入；Throwable SQLite3 或框架错误策略产生的异常继续传递

调用形式：

```php
// $instance为已初始化的SQLite实例。
$result = $instance->execute($sql, $params);
```

定位：`lib/database/sqlite.class.php:204`。

### SQLite::getRS

功能说明：从当前结果读取下一行，空结果或结束时返回 false

```text
public function getRS($mode = 2);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$mode` | int；1 数字下标、2 字段名（默认）、3 两者；其他值回退字段名模式（默认：2） |

**返回值**：array|false 当前记录，保留字段原生 null/数字/文本类型；本方法不自动释放结果

调用形式：

```php
// $instance为已初始化的SQLite实例。
$result = $instance->getRS($mode);
```

定位：`lib/database/sqlite.class.php:259`。

### SQLite::record

功能说明：复用 base_sql 的单条记录读取及缓存/资源清理规则

```text
public function record($sql='', $mode = 2);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；完整 SQL，空字符串从当前构建器生成（默认：''） |
| `$mode` | int；结果下标：1 数字、2 字段名、3 两者，默认 2（默认：2） |

**返回值**：array|false 第一条记录；无记录或执行失败为 false

调用形式：

```php
// $instance为已初始化的SQLite实例。
$result = $instance->record($sql, $mode);
```

定位：`lib/database/sqlite.class.php:272`。

### SQLite::result

功能说明：复用 base_sql 的第一行第一列读取，保留 SQL NULL 及零值

```text
public function result($sql='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；完整 SQL，空字符串从当前构建器生成（默认：''） |

**返回值**：mixed 首列值；无记录或执行失败为 false，SQL NULL 为 null

调用形式：

```php
// $instance为已初始化的SQLite实例。
$result = $instance->result($sql);
```

定位：`lib/database/sqlite.class.php:282`。

### SQLite::getFields

功能说明：取得表字段名，表名作为单个标识符引用，支持空格和引号

```text
public function getFields($the_tbl);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$the_tbl` | string；当前库中的表名 |

**返回值**：array 按声明顺序排列的字段名称

调用形式：

```php
// $instance为已初始化的SQLite实例。
$result = $instance->getFields($the_tbl);
```

定位：`lib/database/sqlite.class.php:292`。

### SQLite::getTbls

功能说明：列出普通表，读取完成后释放结果

参数说明：无显式参数。

```text
public function getTbls();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：array 当前主库中的表名

调用形式：

```php
// $instance为已初始化的SQLite实例。
$result = $instance->getTbls();
```

定位：`lib/database/sqlite.class.php:304`。

### SQLite::getCreateScript

功能说明：从 sqlite_master 读取原始建表语句，保留复合主键、默认值、约束和生成列

```text
public function getCreateScript($the_tbl);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$the_tbl` | string；主库中的表名 |

**返回值**：string 建表 SQL（含尾分号）；不存在返回空串，不包含独立索引或触发器

调用形式：

```php
// $instance为已初始化的SQLite实例。
$result = $instance->getCreateScript($the_tbl);
```

定位：`lib/database/sqlite.class.php:314`。

### SQLite::getDataScript

功能说明：导出可回放 INSERT，保留 NULL、数字、文本及 BLOB，排除生成列

```text
public function getDataScript($the_tbl);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$the_tbl` | string；表名，明确引用为单个本地标识符 |

**返回值**：string INSERT 脚本；空表返回空串，较大导出建议使用专门流式备份工具

调用形式：

```php
// $instance为已初始化的SQLite实例。
$result = $instance->getDataScript($the_tbl);
```

定位：`lib/database/sqlite.class.php:325`。

### SQLite::getIdxScript

功能说明：返回独立创建的索引脚本，表内 UNIQUE/PRIMARY KEY 已包含在建表语句中

```text
public function getIdxScript($the_tbl);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$the_tbl` | string；主库中的表名 |

**返回值**：array 每项为一条带尾分号的 CREATE INDEX

调用形式：

```php
// $instance为已初始化的SQLite实例。
$result = $instance->getIdxScript($the_tbl);
```

定位：`lib/database/sqlite.class.php:355`。

### SQLite::getInsertId

功能说明：取得新插入的自增ID

参数说明：无显式参数。

```text
public function getInsertId();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：int|false 连接存在时返回lastInsertRowID，包括有效的0；未连接为false

调用形式：

```php
// $instance为已初始化的SQLite实例。
$result = $instance->getInsertId();
```

定位：`lib/database/sqlite.class.php:367`。

### SQLite::free

功能说明：释放当前 SQLite3Result 及关联 SQLite3Stmt，并清空两者引用

参数说明：无显式参数。

```text
public function free();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 可重复调用，不关闭数据库对象

调用形式：

```php
// $instance为已初始化的SQLite实例。
$result = $instance->free();
```

定位：`lib/database/sqlite.class.php:378`。

### SQLite::close

功能说明：释放结果并关闭当前库，重置事务、会话、临时表及构建器状态

```text
public function close(&$err_info = array());
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `&$err_info` | string\|array；引用输出：当前驱动的错误文本；空数组只是历史默认占位值（默认：array()） |

**返回值**：int 驱动累计执行次数；重复关闭安全，内存库身份会随关闭重置

调用形式：

```php
// $instance为已初始化的SQLite实例。
$result = $instance->close($err_info);
```

定位：`lib/database/sqlite.class.php:391`。

### SQLite::checkError

功能说明：检查错误

```text
public function checkError(&$err_info = array());
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `&$err_info` | string\|array；引用输出：当前驱动的错误文本；空数组只是历史默认占位值（默认：array()） |

**返回值**：int|false SQLite错误代码，0表示无错误；未连接时沿用已有错误状态

调用形式：

```php
// $instance为已初始化的SQLite实例。
$result = $instance->checkError($err_info);
```

定位：`lib/database/sqlite.class.php:407`。

### SQLite::clearError

功能说明：清空已有错误

参数说明：无显式参数。

```text
public function clearError();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的SQLite实例。
$result = $instance->clearError();
```

定位：`lib/database/sqlite.class.php:425`。

### SQLite::error

功能说明：错误处理

```text
protected function error($str, $exit=false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$str` | string；待处理的字符串 |
| `$exit` | bool；是否在处理完成后结束请求（默认：false） |

**返回值**：void 无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/sqlite.class.php:437`。
