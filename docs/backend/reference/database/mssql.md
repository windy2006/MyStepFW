# MSSQL：mssql 数据库驱动／协议

修订日期：2026-10-23。功能详解。

## 职责与调用位置

本文件提供数据库底层契约或驱动实现，调用前核对目标是否声明相应能力；SQL方言、错误及事务语义按本页各方法说明使用。

源码：`lib/database/mssql.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

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

### MSSQL

功能说明：使用SQLSRV实现连接、参数执行、SQL方言转换和管理脚本操作。

使用说明：
  1. 通常经myDb选择MSSQL驱动，连接配置按该驱动的参数顺序传入。
  2. 需要SQLSRV扩展；execute/fetch*支持参数绑定。
  3. 不声明受管事务接口；不能以继承方法是否存在推断真实事务能力。

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
private
        $error    = false,
        $error_info    = '',
        $count    = 0;
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `MSSQL::__construct` | 构造函数 |
| `MSSQL::init` | 参数初始化 |
| `MSSQL::connect` | 连接数据库服务器 |
| `MSSQL::reconnect` | 重连数据库服务器 |
| `MSSQL::selectDB` | 更换默认数据库 |
| `MSSQL::convertSQL` | 转换mysql查询 |
| `MSSQL::safeValue` | 按 T-SQL 单引号规则转义文本，保留反斜杠；Unicode/二进制优先参数绑定 |
| `MSSQL::execute` | 原生参数化执行 T-SQL，不经过旧 MySQL 方言转换、不自动重试或使用共享缓存 |
| `MSSQL::query` | 执行旧接口 SQL，仅 query 沿用有限 MySQL 兼容转换；原生 SQL 使用 execute |
| `MSSQL::convertLimit` | 转换最外层 MySQL LIMIT，字符串、注释和子查询中的 LIMIT 不改写 |
| `MSSQL::getRS` | 取得结果行 |
| `MSSQL::getDBs` | 取得所有数据库名 |
| `MSSQL::getTbls` | 取得所有表名 |
| `MSSQL::getInsertId` | 取得最新插入ID |
| `MSSQL::getFields` | 取得所有列名 |
| `MSSQL::getPri` | 取得主键名 |
| `MSSQL::getCreateScript` | 取得建立数据表的脚本 |
| `MSSQL::getDataScript` | 导出基本 INSERT，修复历史 inser 拼写，保留 NULL/Unicode/流二进制 |
| `MSSQL::getIdxScript` | 取得设置索引项的脚本 |
| `MSSQL::getStat` | 获取数据库状态 |
| `MSSQL::file` | 执行数据文件 |
| `MSSQL::handleSQL` | 处理SQL文本 |
| `MSSQL::create` | 创建对象 |
| `MSSQL::update` | 更新数据 |
| `MSSQL::batchExe` | 批量执行查询 |
| `MSSQL::check` | 对象检测 |
| `MSSQL::free` | 释放结果集 |
| `MSSQL::close` | 关闭数据库 |
| `MSSQL::checkError` | 错误检测 |
| `MSSQL::clearError` | 清除错误信息 |
| `MSSQL::error` | 错误处理 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### MSSQL::__construct

功能说明：构造函数

```text
public function __construct();
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `...$args` | mixed；按init(host,user,pwd,charset)顺序传入的连接设置；构造不立即连接（历史位置参数，由func_get_args读取） |

**返回值**：void 初始化或释放资源，无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/mssql.class.php:88`。

### MSSQL::init

功能说明：参数初始化

```text
public function init($host, $user, $pwd, $charset='utf-8');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$host` | string；服务器主机名或地址 |
| `$user` | string；连接使用的用户名 |
| `$pwd` | string；账号密码，空串按调用入口的兼容约定处理 |
| `$charset` | string；字符编码名称，例如UTF-8；是否自动检测由具体入口决定（默认：'utf-8'） |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的MSSQL实例。
$result = $instance->init($host, $user, $pwd, $charset);
```

定位：`lib/database/mssql.class.php:101`。

### MSSQL::connect

功能说明：连接数据库服务器

```text
public function connect($the_db = '', $var = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$the_db` | string\|bool；数据库名称；兼容公共连接入口的首位占位参数（默认：''） |
| `$var` | string；非空时作为实际数据库名，兼容connect($pconnect,$database)调用（默认：''） |

**返回值**：$this 返回当前实例，支持链式调用。

**异常与失败**：RuntimeException 当前PHP未安装SQLSRV扩展

调用形式：

```php
// $instance为已初始化的MSSQL实例。
$result = $instance->connect($the_db, $var);
```

定位：`lib/database/mssql.class.php:118`。

### MSSQL::reconnect

功能说明：重连数据库服务器

```text
public function reconnect($the_db = '', $var = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$the_db` | string\|bool；数据库名称；兼容公共连接入口的首位占位参数（默认：''） |
| `$var` | string；非空时作为实际数据库名，兼容connect($pconnect,$database)调用（默认：''） |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的MSSQL实例。
$result = $instance->reconnect($the_db, $var);
```

定位：`lib/database/mssql.class.php:147`。

### MSSQL::selectDB

功能说明：更换默认数据库

```text
public function selectDB($the_db);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$the_db` | string；数据库名称，空串使用当前连接上下文 |

**返回值**：$this|false USE成功后返回当前驱动；失败为false，且不切换当前库名

调用形式：

```php
// $instance为已初始化的MSSQL实例。
$result = $instance->selectDB($the_db);
```

定位：`lib/database/mssql.class.php:158`。

### MSSQL::convertSQL

功能说明：转换mysql查询

```text
public function convertSQL($sql);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；待处理的SQL语句 |

**返回值**：string 经有限MySQL兼容规则转换的T-SQL文本，不执行语句

调用形式：

```php
// $instance为已初始化的MSSQL实例。
$result = $instance->convertSQL($sql);
```

定位：`lib/database/mssql.class.php:170`。

### MSSQL::safeValue

功能说明：按 T-SQL 单引号规则转义文本，保留反斜杠；Unicode/二进制优先参数绑定

```text
public function safeValue($value);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$value` | mixed；标量或 null |

**返回值**：string 不含外层引号的文本

调用形式：

```php
// $instance为已初始化的MSSQL实例。
$result = $instance->safeValue($value);
```

定位：`lib/database/mssql.class.php:235`。

### MSSQL::execute

功能说明：原生参数化执行 T-SQL，不经过旧 MySQL 方言转换、不自动重试或使用共享缓存


参数结构：普通值按位置绑定；数组项为SQLSRV原生[值,方向,PHP类型,SQL类型]描述符，后3项可按扩展约定省略。结果由驱动持有，后续用getRS读取或free释放。

```text
public function execute($sql,array $params=[]);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；T-SQL，数据占位符为 ? |
| `$params` | array；sqlsrv 参数顺序列表；支持原生 [值,方向,PHP类型,SQL类型] 描述（默认：[]） |

**返回值**：int|false 结果集行数或受影响行数；SQL Server 不能确定数量时沿用 -1

**异常与失败**：InvalidArgumentException SQL 或标量参数非法；RuntimeException 未安装 sqlsrv 扩展

调用形式：

```php
// $instance为已初始化的MSSQL实例。
$result = $instance->execute($sql, $params);
```

定位：`lib/database/mssql.class.php:250`。

### MSSQL::query

功能说明：执行旧接口 SQL，仅 query 沿用有限 MySQL 兼容转换；原生 SQL 使用 execute

```text
public function query($sql);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；非空 SQL |

**返回值**：int|false 行数或受影响行数

**异常与失败**：InvalidArgumentException SQL为空/非字符串，或兼容转换不支持写入偏移分页

调用形式：

```php
// $instance为已初始化的MSSQL实例。
$result = $instance->query($sql);
```

定位：`lib/database/mssql.class.php:275`。

### MSSQL::convertLimit

功能说明：转换最外层 MySQL LIMIT，字符串、注释和子查询中的 LIMIT 不改写

```text
public function convertLimit($sql);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；可信 SELECT 查询；偏移分页使用 SQL Server 2012+ OFFSET/FETCH |

**返回值**：string 无分页原样返回；无排序的偏移查询使用不保证稳定次序的 ORDER BY (SELECT NULL)

**异常与失败**：InvalidArgumentException UPDATE/DELETE 偏移分页不能安全转换

调用形式：

```php
// $instance为已初始化的MSSQL实例。
$result = $instance->convertLimit($sql);
```

定位：`lib/database/mssql.class.php:284`。

### MSSQL::getRS

功能说明：取得结果行

```text
public function getRS($mode = 2);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$mode` | int；结果行模式：1数字索引、2关联键、3同时保留两者（默认：2） |

**返回值**：array|false 按mode返回下一行；结果耗尽、没有结果集或读取失败为false

调用形式：

```php
// $instance为已初始化的MSSQL实例。
$result = $instance->getRS($mode);
```

定位：`lib/database/mssql.class.php:311`。

### MSSQL::getDBs

功能说明：取得所有数据库名

参数说明：无显式参数。

```text
public function getDBs();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string[] 当前账号可见且符合驱动查询条件的数据库名列表，可能为空

调用形式：

```php
// $instance为已初始化的MSSQL实例。
$result = $instance->getDBs();
```

定位：`lib/database/mssql.class.php:331`。

### MSSQL::getTbls

功能说明：取得所有表名

```text
public function getTbls($the_db='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$the_db` | string；数据库名称，空串使用当前连接上下文（默认：''） |

**返回值**：string[] 指定数据库中可见的用户表名称列表，可能为空

调用形式：

```php
// $instance为已初始化的MSSQL实例。
$result = $instance->getTbls($the_db);
```

定位：`lib/database/mssql.class.php:347`。

### MSSQL::getInsertId

功能说明：取得最新插入ID

参数说明：无显式参数。

```text
public function getInsertId();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：int|float|string 最近的@@IDENTITY数值；SQLSRV可将数值返回为字符串，无有效值时返回0

调用形式：

```php
// $instance为已初始化的MSSQL实例。
$result = $instance->getInsertId();
```

定位：`lib/database/mssql.class.php:365`。

### MSSQL::getFields

功能说明：取得所有列名

```text
public function getFields($the_tbl);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$the_tbl` | string；数据表名称 |

**返回值**：string[] 当前驱动查询得到的字段名列表，未取得字段时为空数组

调用形式：

```php
// $instance为已初始化的MSSQL实例。
$result = $instance->getFields($the_tbl);
```

定位：`lib/database/mssql.class.php:377`。

### MSSQL::getPri

功能说明：取得主键名

```text
public function getPri($the_tbl);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$the_tbl` | string；数据表名称 |

**返回值**：string|false 主键列名，未取得时回退自增列；仍无记录或查询失败为false

调用形式：

```php
// $instance为已初始化的MSSQL实例。
$result = $instance->getPri($the_tbl);
```

定位：`lib/database/mssql.class.php:393`。

### MSSQL::getCreateScript

功能说明：取得建立数据表的脚本

```text
public function getCreateScript($the_tbl, $the_db='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$the_tbl` | string；数据表名称 |
| `$the_db` | string；数据库名称，空串使用当前连接上下文（默认：''） |

**返回值**：string 当前表的CREATE TABLE脚本文本，按驱动支持的字段信息生成

调用形式：

```php
// $instance为已初始化的MSSQL实例。
$result = $instance->getCreateScript($the_tbl, $the_db);
```

定位：`lib/database/mssql.class.php:421`。

### MSSQL::getDataScript

功能说明：导出基本 INSERT，修复历史 inser 拼写，保留 NULL/Unicode/流二进制

```text
public function getDataScript($the_tbl);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$the_tbl` | string；表名；计算列、IDENTITY、日期精度等完整恢复仍应使用 SQL Server 专用备份 |

**返回值**：string 导出文本，整段保存在内存，不承诺完整 SQL Server 模式迁移

**异常与失败**：RuntimeException 流字段内容读取失败

调用形式：

```php
// $instance为已初始化的MSSQL实例。
$result = $instance->getDataScript($the_tbl);
```

定位：`lib/database/mssql.class.php:477`。

### MSSQL::getIdxScript

功能说明：取得设置索引项的脚本

```text
public function getIdxScript($the_tbl);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$the_tbl` | string；数据表名称 |

**返回值**：string[] 非主键索引的CREATE INDEX语句列表

调用形式：

```php
// $instance为已初始化的MSSQL实例。
$result = $instance->getIdxScript($the_tbl);
```

定位：`lib/database/mssql.class.php:502`。

### MSSQL::getStat

功能说明：获取数据库状态

参数说明：无显式参数。

```text
public function getStat();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：array|string|false 已连接时为SQLSRV服务器信息数组，底层失败为false；未连接时为空串

调用形式：

```php
// $instance为已初始化的MSSQL实例。
$result = $instance->getStat();
```

定位：`lib/database/mssql.class.php:538`。

### MSSQL::file

功能说明：执行数据文件

```text
public function file($file, $find = array(), $replace = array());
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；目标文件路径 |
| `$find` | array；导入SQL前需要替换的查找内容（默认：array()） |
| `$replace` | array；与查找列表对应的替换内容（默认：array()） |

**返回值**：array|false 逐语句处理摘要；文件不存在或连接不可用为false，合法空脚本可返回空数组

调用形式：

```php
// $instance为已初始化的MSSQL实例。
$result = $instance->file($file, $find, $replace);
```

定位：`lib/database/mssql.class.php:551`。

### MSSQL::handleSQL

功能说明：处理SQL文本

```text
public function handleSQL($strSQL);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$strSQL` | string；待拆分/转换的SQL脚本内容 |

**返回值**：string[] 按T-SQL引号/注释边界拆分的SQL列表，不执行这些语句

调用形式：

```php
// $instance为已初始化的MSSQL实例。
$result = $instance->handleSQL($strSQL);
```

定位：`lib/database/mssql.class.php:609`。

### MSSQL::create

功能说明：创建对象

```text
public function create($name, $para=[], $type='db', $run = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$name` | string；待创建的数据库/表等对象名称 |
| `$para` | array\|string；表/索引的col、pri、idx等结构选项；数据库场景为排序规则前缀（默认：[]） |
| `$type` | string；对象类别，例如db/table，按当前驱动支持范围使用（默认：'db'） |
| `$run` | bool；true立即执行，false返回生成的创建脚本（默认：true） |

**返回值**：string|int|bool run为false返回SQL，否则返回query执行结果

调用形式：

```php
// $instance为已初始化的MSSQL实例。
$result = $instance->create($name, $para, $type, $run);
```

定位：`lib/database/mssql.class.php:620`。

### MSSQL::update

功能说明：更新数据

```text
public function update($show = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$show` | bool；是否启用当前方法的预览/输出模式，具体返回见返回说明（默认：false） |

**返回值**：string|int|bool show为true返回SQL，否则返回query执行结果；关联更新按当前驱动支持范围生成

调用形式：

```php
// $instance为已初始化的MSSQL实例。
$result = $instance->update($show);
```

定位：`lib/database/mssql.class.php:690`。

### MSSQL::batchExe

功能说明：批量执行查询

```text
public function batchExe($SQLs);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$SQLs` | string\|string[]；单条SQL文本或按执行顺序排列的SQL列表；不自动开启事务 |

**返回值**：array|false 每项为[原SQL,query结果]；空批次为空数组，未连接为false

调用形式：

```php
// $instance为已初始化的MSSQL实例。
$result = $instance->batchExe($SQLs);
```

定位：`lib/database/mssql.class.php:735`。

### MSSQL::check

功能说明：对象检测

```text
public function check($obj = 'connect', $type = 'resource');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$obj` | string；内部连接或结果的属性名（默认：'connect'） |
| `$type` | string；期望的PHP资源类型标记，默认resource（默认：'resource'） |

**返回值**：bool 内部属性满足resource/object/bool检查规则为true，否则为false

调用形式：

```php
// $instance为已初始化的MSSQL实例。
$result = $instance->check($obj, $type);
```

定位：`lib/database/mssql.class.php:754`。

### MSSQL::free

功能说明：释放结果集

参数说明：无显式参数。

```text
public function free();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的MSSQL实例。
$result = $instance->free();
```

定位：`lib/database/mssql.class.php:775`。

### MSSQL::close

功能说明：关闭数据库

参数说明：无显式参数。

```text
public function close();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：int 本驱动累计查询次数；关闭操作本身不以此值表示成功/失败

调用形式：

```php
// $instance为已初始化的MSSQL实例。
$result = $instance->close();
```

定位：`lib/database/mssql.class.php:788`。

### MSSQL::checkError

功能说明：错误检测

```text
public function checkError(&$msg=array());
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `&$msg` | string\|array；引用输出：由SQLSRV错误组成的多行文本；空数组是默认占位值（默认：array()） |

**返回值**：bool SQLSRV错误/警告集合非空为true；msg引用接收可读错误文本

调用形式：

```php
// $instance为已初始化的MSSQL实例。
$result = $instance->checkError($msg);
```

定位：`lib/database/mssql.class.php:803`。

### MSSQL::clearError

功能说明：清除错误信息

参数说明：无显式参数。

```text
public function clearError();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的MSSQL实例。
$result = $instance->clearError();
```

定位：`lib/database/mssql.class.php:825`。

### MSSQL::error

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

定位：`lib/database/mssql.class.php:837`。
