# myCache_MSSQL：myCache_MSSQL 缓存驱动／协议

## 职责与调用位置

myCache通过此层处理缓存存储、命中或锁；不要从驱动名称推断它实现全部可选协议。编码预算、有效false/null和失败返回按本页逐项判断。

源码：`lib/cache/myCache_MSSQL.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
$cache = new myCache('File', ['path'=>$directory,'allowed_classes'=>false], 0);
$cache->set('counter', 0, 60);
$value = $cache->fetch('counter', $hit);
assert($hit && $value === 0);
if ($cache->supports(interface_cache_lock::class)) {
    $value = $cache->rememberLocked('key', function () { return ['ready'=>true]; }, 60);
}
```

## 声明与类职责

### myCache_MSSQL

功能说明：使用专用SQLSRV连接实现参数化读写、容量检查、精确命中及过期回收。

使用说明：
  1. 通过myCache的MSSQL驱动传入host/user/password/name等数据库配置。
  2. 需要SQLSRV扩展及支持的SQL Server服务；表结构在初始化时检查。
  3. 支持lookup协议，不声明计算锁；clear范围是选定缓存表的全部数据。

补充说明：
SQL Server缓存（SQL Server 2012+）：原生参数化读写、独立连接及精确命中。
使用BIGINT/VARCHAR(MAX)保存期限与数据；不声明计算锁接口。
本地无SQLSRV时明确报错，不静默切换后端；SQL Server的连接、字段类型及查询结果须在目标环境验证。

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
protected $cnt = null, $codec, $table = '[dbo].[my_cache]', $error = '', $capacity = 2147483647, $expiration_max = PHP_INT_MAX, $value_width = 1;
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myCache_MSSQL::__construct` | 建立专用连接，检查/创建表；CHARSET仅接受UTF-8或char。 |
| `myCache_MSSQL::columns` | 读取系统列说明并释放statement。 |
| `myCache_MSSQL::getLastError` | 返回安全错误摘要，不含SQL和认证信息。 |
| `myCache_MSSQL::query` | 执行原始查询或参数绑定查询；调用者负责释放返回的statement。 |
| `myCache_MSSQL::writeStatement` | 完整消费写批次，确认TRY/CATCH及COMMIT已执行；不忽略后续结果中的错误。 |
| `myCache_MSSQL::set` | 事务内UPDATE/INSERT覆盖同键；显式空值正常缓存，参数化数据不经字符串转义。 |
| `myCache_MSSQL::fetch` | SQL过滤过期条目后再解码，支持原始序列化及MS_CACHE2。 |
| `myCache_MSSQL::get` | 读取缓存值，未命中时返回false。 |
| `myCache_MSSQL::remove` | 参数化删除指定条目，始终引用[key]标识符。 |
| `myCache_MSSQL::clean` | 删除所有已过期条目，使用PHP实际Unix时间而非MySQL函数。 |
| `myCache_MSSQL::clear` | 显式删除当前缓存表全部条目，包含各namespace；保留表结构。 |
| `myCache_MSSQL::collectGarbage` | 有限预算删除过期数据。 |
| `myCache_MSSQL::close` | 关闭本驱动专属连接，重复调用安全。 |
| `myCache_MSSQL::__destruct` | 释放专属连接。 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myCache_MSSQL::__construct

功能说明：建立专用连接，检查/创建表；CHARSET仅接受UTF-8或char。

```text
public function __construct($setting);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$setting` | array；host/user/password/name，可选table/charset及公共编解码选项 |

**返回值**：void 初始化或释放资源，无返回值。

**异常与失败**：InvalidArgumentException 配置非法；RuntimeException SQLSRV缺失、连接或初始化失败

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/cache/myCache_MSSQL.class.php:61`。

### myCache_MSSQL::columns

功能说明：读取系统列说明并释放statement。

```text
protected function columns($sql, array $params);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；元数据模板 |
| `$params` | array；绑定参数 |

**返回值**：array|false 按name索引的字段列表

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/cache/myCache_MSSQL.class.php:100`。

### myCache_MSSQL::getLastError

功能说明：返回安全错误摘要，不含SQL和认证信息。

参数说明：无显式参数。

```text
public function getLastError();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string 最近一次安全错误摘要；无已记录错误时为空串

调用形式：

```php
// $instance为已初始化的myCache_MSSQL实例。
$result = $instance->getLastError();
```

定位：`lib/cache/myCache_MSSQL.class.php:113`。

### myCache_MSSQL::query

功能说明：执行原始查询或参数绑定查询；调用者负责释放返回的statement。

异常处理：内部捕获连接/SQL执行异常并返回false，错误摘要保存在对应getLastError状态；调用方不应仅依赖catch判断失败。

```text
public function query($sql, array $params = []);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；可信SQL模板 |
| `$params` | array；数据值参数（默认：[]） |

**返回值**：resource|false SQLSRV statement或失败

调用形式：

```php
// $instance为已初始化的myCache_MSSQL实例。
$result = $instance->query($sql, $params);
```

定位：`lib/cache/myCache_MSSQL.class.php:125`。

### myCache_MSSQL::writeStatement

功能说明：完整消费写批次，确认TRY/CATCH及COMMIT已执行；不忽略后续结果中的错误。

```text
protected function writeStatement($sql, array $params);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；可信模板 |
| `$params` | array；绑定参数 |

**返回值**：bool 写批次及其后续结果全部消费成功为true，SQL执行或批次错误为false

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/cache/myCache_MSSQL.class.php:138`。

### myCache_MSSQL::set

功能说明：事务内UPDATE/INSERT覆盖同键；显式空值正常缓存，参数化数据不经字符串转义。

```text
public function set($key, $value = '', $ttl = 600);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | mixed；有限标量键 |
| `$value` | mixed；受信可序列化数据，省略表示删除（默认：''） |
| `$ttl` | int\|float；秒数，非正数删除（默认：600） |

**返回值**：bool 条目保存成功为true；省略value或TTL非正时转为删除；超容量或存储失败为false

调用形式：

```php
// $instance为已初始化的myCache_MSSQL实例。
$result = $instance->set($key, $value, $ttl);
```

定位：`lib/cache/myCache_MSSQL.class.php:153`。

### myCache_MSSQL::fetch

功能说明：SQL过滤过期条目后再解码，支持原始序列化及MS_CACHE2。

```text
public function fetch($key, &$hit = null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | mixed；缓存键 |
| `&$hit` | bool\|null；引用参数；精确命中输出（默认：null） |

**返回值**：mixed 缓存值或false

调用形式：

```php
// $instance为已初始化的myCache_MSSQL实例。
$result = $instance->fetch($key, $hit);
```

定位：`lib/cache/myCache_MSSQL.class.php:169`。

### myCache_MSSQL::get

功能说明：读取缓存值，未命中时返回false。

```text
public function get($key);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | mixed；缓存键 |

**返回值**：mixed 有效false与未命中不区分

调用形式：

```php
// $instance为已初始化的myCache_MSSQL实例。
$result = $instance->get($key);
```

定位：`lib/cache/myCache_MSSQL.class.php:183`。

### myCache_MSSQL::remove

功能说明：参数化删除指定条目，始终引用[key]标识符。

```text
public function remove($key);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | mixed；缓存键 |

**返回值**：bool 删除语句执行成功为true，包括键不存在；执行失败为false

调用形式：

```php
// $instance为已初始化的myCache_MSSQL实例。
$result = $instance->remove($key);
```

定位：`lib/cache/myCache_MSSQL.class.php:191`。

### myCache_MSSQL::clean

功能说明：删除所有已过期条目，使用PHP实际Unix时间而非MySQL函数。

参数说明：无显式参数。

```text
public function clean();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 过期数据删除语句是否成功，不表示实际删除了多少条

调用形式：

```php
// $instance为已初始化的myCache_MSSQL实例。
$result = $instance->clean();
```

定位：`lib/cache/myCache_MSSQL.class.php:200`。

### myCache_MSSQL::clear

功能说明：显式删除当前缓存表全部条目，包含各namespace；保留表结构。

完整消费批次结果，后续结果失败不能报告成功。

参数说明：无显式参数。

```text
public function clear();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool DELETE及全部结果成功为true，连接或SQL失败为false

调用形式：

```php
// $instance为已初始化的myCache_MSSQL实例。
$result = $instance->clear();
```

定位：`lib/cache/myCache_MSSQL.class.php:211`。

### myCache_MSSQL::collectGarbage

功能说明：有限预算删除过期数据。

```text
public function collectGarbage($limit = 200);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$limit` | int；默认200，0不限，负数非法（默认：200） |

**返回值**：bool 本次预算内的过期回收语句是否成功

**异常与失败**：InvalidArgumentException 回收预算不是非负整数

调用形式：

```php
// $instance为已初始化的myCache_MSSQL实例。
$result = $instance->collectGarbage($limit);
```

定位：`lib/cache/myCache_MSSQL.class.php:220`。

### myCache_MSSQL::close

功能说明：关闭本驱动专属连接，重复调用安全。

参数说明：无显式参数。

```text
public function close();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的myCache_MSSQL实例。
$result = $instance->close();
```

定位：`lib/cache/myCache_MSSQL.class.php:232`。

### myCache_MSSQL::__destruct

功能说明：释放专属连接。

参数说明：无显式参数。

```text
public function __destruct();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 初始化或释放资源，无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/cache/myCache_MSSQL.class.php:241`。
