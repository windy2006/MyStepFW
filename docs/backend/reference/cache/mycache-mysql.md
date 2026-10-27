# MySQL缓存

接口说明：`myCache_MySQL`。

## 职责与调用位置

myCache通过此层处理缓存存储、命中或锁；不要从驱动名称推断它实现全部可选协议。编码预算、有效false/null和失败返回按本页逐项判断。

源码：`lib/cache/myCache_MySQL.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

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

### myCache_MySQL

功能说明：使用专用mysqli连接、参数化UPSERT及命名锁提供数据库缓存。

使用说明：
  1. 通过myCache的MySQL驱动传入host/user/password/name等连接及编解码选项。
  2. 需要mysqli/mysqlnd；已有表会检查键唯一性、容量及时间列范围。
  3. 支持fetch和计算锁；业务数据库事务与缓存专用连接相互独立。

补充说明：
MySQL缓存：专用连接、参数化UPSERT、容量保护及精确命中。
兼容TEXT列/原始序列化和MS_CACHE2编码；普通请求不ALTER已有表。
可选table和namespace隔离业务键，synchronized用同一写实例的命名锁；需要mysqli/mysqlnd。

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
protected $cnt = null, $owned = false, $codec = null, $table = 'my_cache', $error = '', $capacity = 16777215, $expiration_max = PHP_INT_MAX, $identity = '', $locks = [];
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myCache_MySQL::__construct` | 连接并检查/创建缓存表；已有表不反复执行DDL，容量检查防止宽松SQL模式截断。 |
| `myCache_MySQL::codec` | 取得当前编解码策略，未初始化时采用默认配置。 |
| `myCache_MySQL::getLastError` | 返回最近错误摘要，不含SQL、凭据或正文。 |
| `myCache_MySQL::run` | 执行内部参数化模板；关闭连接/SQL错误均转换为false，及时释放statement。 |
| `myCache_MySQL::set` | 原子覆盖条目，拒绝超出配置/旧表列容量或时间列范围的写入，保留旧值。 |
| `myCache_MySQL::fetch` | 读取有效条目，SQL过滤过期记录后才解码对象。 |
| `myCache_MySQL::get` | 兼容旧读取入口。 |
| `myCache_MySQL::remove` | 删除单个键。 |
| `myCache_MySQL::clean` | 全量回收过期条目，不清空有效值。 |
| `myCache_MySQL::clear` | 显式删除当前缓存表全部条目，包含各namespace；不修改表结构或命名锁。 |
| `myCache_MySQL::collectGarbage` | 有预算的过期回收；同表所有namespace的过期值均可被清理。 |
| `myCache_MySQL::synchronized` | 同一MySQL写实例上的计算命名锁，超时/故障不执行回调。 |
| `myCache_MySQL::close` | 关闭自有连接，外部注入的借用连接不在此关闭；重复调用安全。 |
| `myCache_MySQL::__destruct` | 释放本驱动自有连接。 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myCache_MySQL::__construct

功能说明：连接并检查/创建缓存表；已有表不反复执行DDL，容量检查防止宽松SQL模式截断。

```text
public function __construct($setting);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$setting` | array；host/user/password/name，可选port/socket/charset/table及公共编解码选项 |

**返回值**：void 初始化或释放资源，无返回值。

**异常与失败**：InvalidArgumentException 配置非法；RuntimeException 连接、表初始化或结构检查失败，错误不含认证信息

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/cache/myCache_MySQL.class.php:61`。

### myCache_MySQL::codec

功能说明：取得当前编解码策略，未初始化时采用默认配置。

参数说明：无显式参数。

```text
protected function codec();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：myCacheCodec 本驱动当前使用的编解码策略实例，缺失时创建默认实例

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/cache/myCache_MySQL.class.php:103`。

### myCache_MySQL::getLastError

功能说明：返回最近错误摘要，不含SQL、凭据或正文。

参数说明：无显式参数。

```text
public function getLastError();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string 最近一次安全错误摘要；无已记录错误时为空串

调用形式：

```php
// $instance为已初始化的myCache_MySQL实例。
$result = $instance->getLastError();
```

定位：`lib/cache/myCache_MySQL.class.php:112`。

### myCache_MySQL::run

功能说明：执行内部参数化模板；关闭连接/SQL错误均转换为false，及时释放statement。

异常处理：内部捕获连接/SQL执行异常并返回false，错误摘要保存在对应getLastError状态；调用方不应仅依赖catch判断失败。

```text
protected function run($sql, array $params);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；可信模板，表名已在构造阶段校验 |
| `$params` | array；顺序绑定值 |

**返回值**：mysqli_result|bool 结果由调用者释放，非查询为bool

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/cache/myCache_MySQL.class.php:124`。

### myCache_MySQL::set

功能说明：原子覆盖条目，拒绝超出配置/旧表列容量或时间列范围的写入，保留旧值。

```text
public function set($key, $value = '', $ttl = 600);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | mixed；有限标量键 |
| `$value` | mixed；受信数据，省略时删除（默认：''） |
| `$ttl` | int\|float；有效秒数，非正数删除（默认：600） |

**返回值**：bool UPSERT成功为true；省略value或TTL非正时转为删除；编码/容量/后端失败为false

**异常与失败**：Throwable 编码或参数校验异常继续传递

调用形式：

```php
// $instance为已初始化的myCache_MySQL实例。
$result = $instance->set($key, $value, $ttl);
```

定位：`lib/cache/myCache_MySQL.class.php:145`。

### myCache_MySQL::fetch

功能说明：读取有效条目，SQL过滤过期记录后才解码对象。

```text
public function fetch($key, &$hit = null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | mixed；缓存键 |
| `&$hit` | bool\|null；引用参数；精确命中输出（默认：null） |

**返回值**：mixed 有效值或false

调用形式：

```php
// $instance为已初始化的myCache_MySQL实例。
$result = $instance->fetch($key, $hit);
```

定位：`lib/cache/myCache_MySQL.class.php:160`。

### myCache_MySQL::get

功能说明：兼容旧读取入口。

```text
public function get($key);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | mixed；缓存键 |

**返回值**：mixed 有效false与未命中不区分，需区分时使用fetch

调用形式：

```php
// $instance为已初始化的myCache_MySQL实例。
$result = $instance->get($key);
```

定位：`lib/cache/myCache_MySQL.class.php:174`。

### myCache_MySQL::remove

功能说明：删除单个键。

```text
public function remove($key);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | mixed；缓存键 |

**返回值**：bool 原本不存在也成功

调用形式：

```php
// $instance为已初始化的myCache_MySQL实例。
$result = $instance->remove($key);
```

定位：`lib/cache/myCache_MySQL.class.php:182`。

### myCache_MySQL::clean

功能说明：全量回收过期条目，不清空有效值。

参数说明：无显式参数。

```text
public function clean();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 过期记录删除语句是否成功；有效记录继续保留

调用形式：

```php
// $instance为已初始化的myCache_MySQL实例。
$result = $instance->clean();
```

定位：`lib/cache/myCache_MySQL.class.php:191`。

### myCache_MySQL::clear

功能说明：显式删除当前缓存表全部条目，包含各namespace；不修改表结构或命名锁。

并发请求仍可在清空后重新写入；不影响使用其他表的缓存实例。

参数说明：无显式参数。

```text
public function clear();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool DELETE执行成功为true，连接或SQL失败为false

调用形式：

```php
// $instance为已初始化的myCache_MySQL实例。
$result = $instance->clear();
```

定位：`lib/cache/myCache_MySQL.class.php:202`。

### myCache_MySQL::collectGarbage

功能说明：有预算的过期回收；同表所有namespace的过期值均可被清理。

```text
public function collectGarbage($limit = 200);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$limit` | int；最多删除条目数，默认200，0不限（默认：200） |

**返回值**：bool 预算内删除过期记录的语句是否成功，不表示清理条数

**异常与失败**：InvalidArgumentException 回收预算不是非负整数

调用形式：

```php
// $instance为已初始化的myCache_MySQL实例。
$result = $instance->collectGarbage($limit);
```

定位：`lib/cache/myCache_MySQL.class.php:211`。

### myCache_MySQL::synchronized

功能说明：同一MySQL写实例上的计算命名锁，超时/故障不执行回调。

```text
public function synchronized($key, callable $operation, $timeout = 5);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | mixed；逻辑锁键 |
| `$operation` | callable；无参数回调 |
| `$timeout` | int\|float；0..60秒（默认：5） |

**返回值**：mixed 回调结果

**异常与失败**：RuntimeException 锁不可用；LogicException 同一适配器重入同键

调用形式：

```php
// $instance为已初始化的myCache_MySQL实例。
$result = $instance->synchronized($key, $operation, $timeout);
```

定位：`lib/cache/myCache_MySQL.class.php:229`。

### myCache_MySQL::close

功能说明：关闭自有连接，外部注入的借用连接不在此关闭；重复调用安全。

参数说明：无显式参数。

```text
public function close();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的myCache_MySQL实例。
$result = $instance->close();
```

定位：`lib/cache/myCache_MySQL.class.php:253`。

### myCache_MySQL::__destruct

功能说明：释放本驱动自有连接。

参数说明：无显式参数。

```text
public function __destruct();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 初始化或释放资源，无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/cache/myCache_MySQL.class.php:265`。
