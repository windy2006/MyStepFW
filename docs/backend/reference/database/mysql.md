# MySQL：mysql 数据库驱动／协议

修订日期：2026-10-23。功能详解。

## 职责与调用位置

本文件提供数据库底层契约或驱动实现，调用前核对目标是否声明相应能力；SQL方言、错误及事务语义按本页各方法说明使用。

源码：`lib/database/mysql.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

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

### MySQL

功能说明：结合mysqli、通用SQL构造及事务/缓存逻辑实现MySQL数据访问。

使用说明：
  1. 通常经myDb选择MySQL驱动，再connect选择数据库。
  2. execute/fetch*使用绑定值；复杂结构操作和导入导出需按MySQL语义使用。
  3. 查询缓存及事务公共实现来自myDb.class.php；连接丢失不自动重放写入。

补充说明：
MySQL 驱动，使用 mysqli 执行普通 SQL、参数绑定及共享构建/事务接口

    $mysql = new MySQL($host, $user, $pwd, 'utf8mb4'); // 仅设置连接参数
    $mysql->connect(false, $database);                // 建立连接及选择库
    $mysql->execute('UPDATE items SET value=? WHERE id=?', [$value, $id]);
    $rows = $mysql->fetchAll('SELECT * FROM items WHERE id=?', [$id]);
    $mysql->transaction(function ($tx) { ... });      // 继承 base_db，支持嵌套保存点

使用说明：
1. 新连接沿用服务端 wait_timeout，不再强制改为 3 秒；初始化仍沿用历史 sql_mode 设置。
2. query() 只在已筛选的无事务/会话依赖 SELECT 遭遇 2006 断线时重连并重试一次；
   不重放写入。execute() 不自动重试，也不使用共享查询结果缓存。
3. 参数化结果读取需要 mysqlnd；参数只能代替数据值，标识符与 SQL 片段由应用限定。
4. record/records/result/count/fetchAll/One/Value 由 base_sql 提供；缓存和事务由
   base_db 提供。直接使用原生连接会绕过事务状态及共享缓存失效跟踪。

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
protected
        $session_sql_mode = '',
        $persistent = false,
        $err = false,
        $err_info = '',
        $count = 0;
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `MySQL::__construct` | 按可选位置参数设置驱动，构造本身不建立 MySQL 连接 |
| `MySQL::init` | 保存连接设置并使用 MySQL 反引号定界符，不建立或重建连接 |
| `MySQL::connect` | 建立新连接、应用字符集和配置的会话 SQL 模式，并重置数据库/缓存/事务跟踪状态 |
| `MySQL::reconnect` | 关闭当前连接并重新连接，默认保留连接模式及所选数据库 |
| `MySQL::selectDB` | 选择数据库，成功后更新当前库及清空查询构建状态 |
| `MySQL::setCharset` | 设置字符集 |
| `MySQL::safeValue` | 按活动连接字符集转义标量文本，未连接时采用历史独立转义规则 |
| `MySQL::changUser` | 通过 mysqli 切换用户及数据库，只有成功时才更新驱动身份状态 |
| `MySQL::query` | 执行完整 SQL，保留结果集供 getRS() 使用并更新共享缓存/事务状态 |
| `MySQL::execute` | 以原生预处理及参数绑定执行 SQL，不自动重试或使用共享结果缓存 |
| `MySQL::getRS` | 取得结果集 |
| `MySQL::getData` | 获取结果集某一行某一列 |
| `MySQL::getFields` | 获取某一数据表的所有列 |
| `MySQL::getCreateScript` | 导出原生建表 SQL，保留索引、默认值及约束 |
| `MySQL::getDataScript` | 导出可回放 INSERT，使用十六进制字符串保留引号、换行、二进制及字符集字节 |
| `MySQL::getIdxScript` | 取得设置索引项的脚本 |
| `MySQL::getDBs` | 取得所有数据库名 |
| `MySQL::getTbls` | 取得所有表名，及详细信息 |
| `MySQL::getPri` | 取得主键名 |
| `MySQL::getInsertId` | 取得新插入的自增ID |
| `MySQL::optTabs` | 列出当前数据库的表并逐一执行 OPTIMIZE TABLE |
| `MySQL::getStat` | 取得数据库当前状态 |
| `MySQL::getProcesses` | 取得当前进程 |
| `MySQL::replace` | 更新替换数据 |
| `MySQL::batchExe` | 批量执行查询语句 |
| `MySQL::handleSQL` | 按引号及注释边界分割脚本，保留数据中的空白/分号，支持独立行 DELIMITER |
| `MySQL::file` | 执行数据查询文件 |
| `MySQL::check` | 检查本地连接/结果属性的类型，不通过探测查询或隐式重连掩盖故障 |
| `MySQL::free` | 释放当前 mysqli_result 并将结果引用清空，可重复调用 |
| `MySQL::close` | 释放结果和连接，并重置事务、会话、临时表及构建状态 |
| `MySQL::checkError` | 读取当前 mysqli 错误并刷新驱动错误状态，成功后移除残留旧错误 |
| `MySQL::clearError` | 清空驱动保存的错误标志及说明，不查询或重建连接 |
| `MySQL::error` | 错误处理 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### MySQL::__construct

功能说明：按可选位置参数设置驱动，构造本身不建立 MySQL 连接

```text
public function __construct();
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `...$args` | mixed；按init声明顺序传入的连接设置；构造不立即连接（历史位置参数，由func_get_args读取） |
| `$host` | string；历史位置参数说明：可选第一参数，服务器地址，传入时须同时提供 user/pwd |
| `$user` | string；历史位置参数说明：数据库用户名，传给 init() |
| `$pwd` | string；历史位置参数说明：数据库密码，传给 init() |
| `$charset` | string；历史位置参数说明：字符集，默认 utf8；建议明确使用 utf8mb4 |
| `$options` | array；历史位置参数说明：可选 sql_mode 策略，转给 init |

**返回值**：void 初始化或释放资源，无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/database/mysql.class.php:114`。

### MySQL::init

功能说明：保存连接设置并使用 MySQL 反引号定界符，不建立或重建连接

```text
public function init($host, $user, $pwd, $charset='utf8', array $options=[]);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$host` | string；mysqli 服务器地址 |
| `$user` | string；数据库用户名 |
| `$pwd` | string；数据库密码 |
| `$charset` | string；字符集名称，默认 utf8；连接时由 setCharset() 应用（默认：'utf8'） |
| `$options` | array；sql_mode 默认空串兼容旧模式；null 保留服务器默认，字符串明确设置会话模式（默认：[]） |

**返回值**：void 修改活动连接的初始化设置后，调用方须明确决定是否重新连接

**异常与失败**：InvalidArgumentException 连接选项或sql_mode设置非法

调用形式：

```php
// $instance为已初始化的MySQL实例。
$result = $instance->init($host, $user, $pwd, $charset, $options);
```

定位：`lib/database/mysql.class.php:129`。

### MySQL::connect

功能说明：建立新连接、应用字符集和配置的会话 SQL 模式，并重置数据库/缓存/事务跟踪状态

```text
public function connect($pconnect = false, $the_db = null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$pconnect` | bool；true 通过 p: 主机前缀使用 mysqli 持久连接，默认 false（默认：false） |
| `$the_db` | string\|null；非空时连接后选择该库；null 不主动选择（默认：null） |

**返回值**：bool 建连流程成功为 true；失败按框架错误策略处理并可返回 false

调用形式：

```php
// $instance为已初始化的MySQL实例。
$result = $instance->connect($pconnect, $the_db);
```

定位：`lib/database/mysql.class.php:147`。

### MySQL::reconnect

功能说明：关闭当前连接并重新连接，默认保留连接模式及所选数据库

不会重放事务或业务 SQL，也不会恢复 SET、临时表等会话状态。

```text
public function reconnect($pconnect = 'keep', $the_db = null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$pconnect` | bool\|string；keep 沿用原持久连接设置；true/false 显式指定（默认：'keep'） |
| `$the_db` | string\|null；null 沿用此前所选数据库，非空字符串明确选择新库（默认：null） |

**返回值**：bool connect() 的返回值，不含旧有的固定延迟重试

调用形式：

```php
// $instance为已初始化的MySQL实例。
$result = $instance->reconnect($pconnect, $the_db);
```

定位：`lib/database/mysql.class.php:182`。

### MySQL::selectDB

功能说明：选择数据库，成功后更新当前库及清空查询构建状态

```text
public function selectDB($the_db);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$the_db` | string；数据库名称，作为 mysqli_select_db() 参数而非拼接 SQL |

**返回值**：bool 成功为 true；无连接或选择失败为 false，失败不更新当前库标识

调用形式：

```php
// $instance为已初始化的MySQL实例。
$result = $instance->selectDB($the_db);
```

定位：`lib/database/mysql.class.php:195`。

### MySQL::setCharset

功能说明：设置字符集

```text
public function setCharset($charset='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$charset` | string；字符编码名称，例如UTF-8；是否自动检测由具体入口决定（默认：''） |

**返回值**：string 连接实际采用的字符集名称，不只是传入值；失败按驱动错误策略处理

调用形式：

```php
// $instance为已初始化的MySQL实例。
$result = $instance->setCharset($charset);
```

定位：`lib/database/mysql.class.php:210`。

### MySQL::safeValue

功能说明：按活动连接字符集转义标量文本，未连接时采用历史独立转义规则

```text
public function safeValue($value);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$value` | mixed；标量值；布尔先转整数，最终转字符串；二进制或外部输入优先绑定 |

**返回值**：string 转义后的文本，不含两端 SQL 引号；不能用来转义表名或任意 SQL 片段

调用形式：

```php
// $instance为已初始化的MySQL实例。
$result = $instance->safeValue($value);
```

定位：`lib/database/mysql.class.php:231`。

### MySQL::changUser

功能说明：通过 mysqli 切换用户及数据库，只有成功时才更新驱动身份状态

```text
public function changUser($user, $pwd, $db=null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$user` | string；新用户名 |
| `$pwd` | string；新密码 |
| `$db` | string\|null；新库名；null 沿用当前库（默认：null） |

**返回值**：bool 成功为 true，并清空构建器、事务/临时表/会话跟踪；失败保留原身份记录

调用形式：

```php
// $instance为已初始化的MySQL实例。
$result = $instance->changUser($user, $pwd, $db);
```

定位：`lib/database/mysql.class.php:246`。

### MySQL::query

功能说明：执行完整 SQL，保留结果集供 getRS() 使用并更新共享缓存/事务状态

执行前释放旧结果并清空旧错误。只有符合 isCacheable() 保守条件的 SELECT 在
错误码 2006 时允许重连一次；事务、会话相关查询和写入均不自动重放。

```text
public function query($sql, $reset = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；非空完整 SQL，由调用方提供可信模板及正确数据处理 |
| `$reset` | bool；是否清空现有构建器，默认 true；只影响构建器，不控制结果释放（默认：true） |

**返回值**：int|false SELECT 返回结果行数，其他语句返回受影响行数；失败为 false

**异常与失败**：InvalidArgumentException SQL 不是非空字符串；Throwable 框架错误处理或 mysqli 抛出的异常可继续向外传递

调用形式：

```php
// $instance为已初始化的MySQL实例。
$result = $instance->query($sql, $reset);
```

定位：`lib/database/mysql.class.php:270`。

### MySQL::execute

功能说明：以原生预处理及参数绑定执行 SQL，不自动重试或使用共享结果缓存

按 PHP 类型推断绑定类型，null 保持 SQL NULL；流参数使用分块 send_long_data()。
结果集保留给 getRS()/fetch*()，预处理语句在 finally 中关闭，成功后跟踪缓存失效。


流所有权：BLOB从当前流位置读取到末尾，不负责rewind或关闭调用方的流；statement在finally关闭，读取结果集由驱动free/后续读取入口释放。

```text
public function execute($sql, array $params = [], $types = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；非空 SQL 模板，使用 ? 占位符；一个占位符只代替一个数据值 |
| `$params` | array；按出现顺序的标量、null 或可读取二进制流，数组键不作为参数名称（默认：[]） |
| `$types` | string；可选 mysqli 类型串 i/d/s/b，长度须等于参数数目；空字符串自动推断；<br>                     b 可接收文本或流，流从当前位置读取；null 仍表示 SQL NULL（默认：''） |

**返回值**：int|false 读查询为结果行数，写查询为受影响行数；无连接或失败返回 false

**异常与失败**：InvalidArgumentException SQL、参数数目/类型、非有限浮点数或类型串非法；RuntimeException 读取结果需要 mysqlnd 而当前未提供，或 BLOB 流读取失败/无进展；Throwable 驱动或框架错误策略产生的异常继续传递

调用形式：

```php
// $instance为已初始化的MySQL实例。
$result = $instance->execute($sql, $params, $types);
```

定位：`lib/database/mysql.class.php:317`。

### MySQL::getRS

功能说明：取得结果集

```text
public function getRS($mode = 2);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$mode` | int；结果行模式：1数字索引、2关联键、3同时保留两者（默认：2） |

**返回值**：array|false 按mode读取的下一行；没有结果、耗尽或读取失败为false

调用形式：

```php
// $instance为已初始化的MySQL实例。
$result = $instance->getRS($mode);
```

定位：`lib/database/mysql.class.php:375`。

### MySQL::getData

功能说明：获取结果集某一行某一列

```text
public function getData($line=0, $field='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$line` | int；从0开始的结果行位置（默认：0） |
| `$field` | string；指定字段名；空串返回该行全部字段（默认：''） |

**返回值**：mixed field为空时返回包含数字及字段名键的一行，否则返回指定字段原值；无结果集为false

调用形式：

```php
// $instance为已初始化的MySQL实例。
$result = $instance->getData($line, $field);
```

定位：`lib/database/mysql.class.php:402`。

### MySQL::getFields

功能说明：获取某一数据表的所有列

```text
public function getFields($the_tbl, $the_db='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$the_tbl` | string；数据表名称 |
| `$the_db` | string；数据库名称，空串使用当前连接上下文（默认：''） |

**返回值**：string[] 指定库表的字段名列表，可能为空

调用形式：

```php
// $instance为已初始化的MySQL实例。
$result = $instance->getFields($the_tbl, $the_db);
```

定位：`lib/database/mysql.class.php:422`。

### MySQL::getCreateScript

功能说明：导出原生建表 SQL，保留索引、默认值及约束

```text
public function getCreateScript($the_tbl, $the_db='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$the_tbl` | string；表名，作为一个完整标识符引用 |
| `$the_db` | string；库名，空值使用当前库（默认：''） |

**返回值**：string 带尾分号的建表语句；不存在/查询失败按驱动错误策略处理

调用形式：

```php
// $instance为已初始化的MySQL实例。
$result = $instance->getCreateScript($the_tbl, $the_db);
```

定位：`lib/database/mysql.class.php:440`。

### MySQL::getDataScript

功能说明：导出可回放 INSERT，使用十六进制字符串保留引号、换行、二进制及字符集字节

```text
public function getDataScript($the_tbl, $the_db='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$the_tbl` | string；表名 |
| `$the_db` | string；库名，空值使用当前库；导入脚本写入当前选定库中的同名表（默认：''） |

**返回值**：string 带明确列名的 INSERT，NULL 保持 NULL，跳过生成列；整段导出占用内存

调用形式：

```php
// $instance为已初始化的MySQL实例。
$result = $instance->getDataScript($the_tbl, $the_db);
```

定位：`lib/database/mysql.class.php:453`。

### MySQL::getIdxScript

功能说明：取得设置索引项的脚本

```text
public function getIdxScript($the_tbl, $the_db='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$the_tbl` | string；数据表名称 |
| `$the_db` | string；数据库名称，空串使用当前连接上下文（默认：''） |

**返回值**：string[] 非主键索引的定义片段，供建表脚本组合使用

调用形式：

```php
// $instance为已初始化的MySQL实例。
$result = $instance->getIdxScript($the_tbl, $the_db);
```

定位：`lib/database/mysql.class.php:484`。

### MySQL::getDBs

功能说明：取得所有数据库名

参数说明：无显式参数。

```text
public function getDBs();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string[] 当前连接账号可见的数据库名称列表

调用形式：

```php
// $instance为已初始化的MySQL实例。
$result = $instance->getDBs();
```

定位：`lib/database/mysql.class.php:513`。

### MySQL::getTbls

功能说明：取得所有表名，及详细信息

```text
public function getTbls($the_db='', $pattern='', $detail=false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$the_db` | string；数据库名称，空串使用当前连接上下文（默认：''） |
| `$pattern` | string；表名LIKE筛选模式，空串不筛选（默认：''） |
| `$detail` | bool；是否返回表状态等完整信息（默认：false） |

**返回值**：array detail为false时为表名列表，为true时为SHOW TABLE STATUS记录列表

调用形式：

```php
// $instance为已初始化的MySQL实例。
$result = $instance->getTbls($the_db, $pattern, $detail);
```

定位：`lib/database/mysql.class.php:531`。

### MySQL::getPri

功能说明：取得主键名

```text
public function getPri($the_tbl, $the_db='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$the_tbl` | string；数据表名称 |
| `$the_db` | string；数据库名称，空串使用当前连接上下文（默认：''） |

**返回值**：string 首个主键字段名；无主键时兼容回退唯一键，均不存在时为空串

调用形式：

```php
// $instance为已初始化的MySQL实例。
$result = $instance->getPri($the_tbl, $the_db);
```

定位：`lib/database/mysql.class.php:550`。

### MySQL::getInsertId

功能说明：取得新插入的自增ID

参数说明：无显式参数。

```text
public function getInsertId();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：int|string 最近自增标识，超出PHP整数表示范围时可为字符串；无有效值为0

调用形式：

```php
// $instance为已初始化的MySQL实例。
$result = $instance->getInsertId();
```

定位：`lib/database/mysql.class.php:581`。

### MySQL::optTabs

功能说明：列出当前数据库的表并逐一执行 OPTIMIZE TABLE

```text
public function optTabs();
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `...$args` | string；可选表名列表；省略时优化当前库的所有表（历史位置参数，由func_get_args读取） |

**返回值**：void 使用正确的 getTbls() 入口；会执行数据库维护操作，调用方自行选择维护时机

调用形式：

```php
// $instance为已初始化的MySQL实例。
$result = $instance->optTabs();
```

定位：`lib/database/mysql.class.php:593`。

### MySQL::getStat

功能说明：取得数据库当前状态

参数说明：无显式参数。

```text
public function getStat();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：array|string 已连接时为服务器/客户端状态映射；未连接返回空串

调用形式：

```php
// $instance为已初始化的MySQL实例。
$result = $instance->getStat();
```

定位：`lib/database/mysql.class.php:614`。

### MySQL::getProcesses

功能说明：取得当前进程

```text
public function getProcesses($mode=0, $time_limit=0);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$mode` | int；0返回进程摘要，1返回完整行，2仅返回进程ID；不会终止进程（默认：0） |
| `$time_limit` | int；仅处理运行时间达到此秒数的进程，0不按时长过滤（默认：0） |

**返回值**：array|string mode为0返回摘要列表，1返回进程记录，2返回ID列表；未连接为空串

调用形式：

```php
// $instance为已初始化的MySQL实例。
$result = $instance->getProcesses($mode, $time_limit);
```

定位：`lib/database/mysql.class.php:634`。

### MySQL::replace

功能说明：更新替换数据

```text
public function replace($show = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$show` | bool；是否启用当前方法的预览/输出模式，具体返回见返回说明（默认：false） |

**返回值**：string|int|false show为true返回REPLACE语句，否则返回query结果；失败为false，成功写入可返回0

调用形式：

```php
// $instance为已初始化的MySQL实例。
$result = $instance->replace($show);
```

定位：`lib/database/mysql.class.php:658`。

### MySQL::batchExe

功能说明：批量执行查询语句

```text
public function batchExe($SQLs);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$SQLs` | string\|string[]；单条SQL文本或按执行顺序排列的SQL列表；不自动开启事务 |

**返回值**：array|false 每项为[原SQL,query结果]；空批次为空数组，未连接为false

调用形式：

```php
// $instance为已初始化的MySQL实例。
$result = $instance->batchExe($SQLs);
```

定位：`lib/database/mysql.class.php:674`。

### MySQL::handleSQL

功能说明：按引号及注释边界分割脚本，保留数据中的空白/分号，支持独立行 DELIMITER

```text
public function handleSQL($strSQL);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$strSQL` | string；待拆分/转换的SQL脚本内容 |

**返回值**：string[] 按引号/注释及DELIMITER规则切分的SQL列表，不执行语句

调用形式：

```php
// $instance为已初始化的MySQL实例。
$result = $instance->handleSQL($strSQL);
```

定位：`lib/database/mysql.class.php:692`。

### MySQL::file

功能说明：执行数据查询文件

```text
public function file($file, $find = array(), $replace = array());
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；目标文件路径 |
| `$find` | array；导入SQL前需要替换的查找内容（默认：array()） |
| `$replace` | array；与查找列表对应的替换内容（默认：array()） |

**返回值**：array|false 已识别语句的执行摘要列表；文件不存在或未连接为false；单项失败见对应结果字段

调用形式：

```php
// $instance为已初始化的MySQL实例。
$result = $instance->file($file, $find, $replace);
```

定位：`lib/database/mysql.class.php:704`。

### MySQL::check

功能说明：检查本地连接/结果属性的类型，不通过探测查询或隐式重连掩盖故障

```text
public function check($obj = 'connect', $type = 'object');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$obj` | string；属性名，默认 connect；result 用于检查 mysqli_result（默认：'connect'） |
| `$type` | string；object 检查对应对象，bool 检查布尔真值；其他值按对象存在处理（默认：'object'） |

**返回值**：bool 属性符合检查条件时为 true；连接对象存在不代表网络一定可用

调用形式：

```php
// $instance为已初始化的MySQL实例。
$result = $instance->check($obj, $type);
```

定位：`lib/database/mysql.class.php:776`。

### MySQL::free

功能说明：释放当前 mysqli_result 并将结果引用清空，可重复调用

参数说明：无显式参数。

```text
public function free();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 释放当前结果及 CALL 等语句的后续结果，避免下一条查询 commands out of sync

调用形式：

```php
// $instance为已初始化的MySQL实例。
$result = $instance->free();
```

定位：`lib/database/mysql.class.php:790`。

### MySQL::close

功能说明：释放结果和连接，并重置事务、会话、临时表及构建状态

```text
public function close(&$err_info = array());
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `&$err_info` | string\|array；引用输出：当前驱动的错误文本；空数组只是历史默认占位值（默认：array()） |

**返回值**：int 此驱动累计执行次数；重复关闭安全，不自动重连

调用形式：

```php
// $instance为已初始化的MySQL实例。
$result = $instance->close($err_info);
```

定位：`lib/database/mysql.class.php:806`。

### MySQL::checkError

功能说明：读取当前 mysqli 错误并刷新驱动错误状态，成功后移除残留旧错误

```text
public function checkError(&$err_info = array());
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `&$err_info` | string\|array；引用输出：当前驱动的错误文本；空数组只是历史默认占位值（默认：array()） |

**返回值**：int|false 当前mysqli错误编号；没有错误为false，同时刷新err_info引用文本

调用形式：

```php
// $instance为已初始化的MySQL实例。
$result = $instance->checkError($err_info);
```

定位：`lib/database/mysql.class.php:822`。

### MySQL::clearError

功能说明：清空驱动保存的错误标志及说明，不查询或重建连接

参数说明：无显式参数。

```text
public function clearError();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的MySQL实例。
$result = $instance->clearError();
```

定位：`lib/database/mysql.class.php:837`。

### MySQL::error

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

定位：`lib/database/mysql.class.php:849`。

