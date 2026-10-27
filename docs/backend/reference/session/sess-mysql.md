# sess_mysql：sess_mysql 会话处理器

## 职责与调用位置

此文件实现PHP会话保存或查询相关能力，通常由框架按session.mode选择。应用内使用会话门面，不重复启动处理器；底层回调由PHP引擎调用。

源码：`lib/session/sess_mysql.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
// 当前应用已由框架启动会话；不是直接调用底层处理器的read/write。
myReq::session('editor_id', 7);
assert(myReq::session('editor_id') === 7);
myReq::removeSession('editor_id');
```

## 声明与类职责

### sess_mysql

功能说明：提供InnoDB会话存储、参数化查询、命名锁及严格ID验证/过期回收。

使用说明：
  1. 经myReq配置数据库和Session设置后启动，表与连接由处理器管理。
  2. 认证会话需要可靠锁和严格验证；后端故障通过失败返回及安全错误摘要表达。
  3. 已有表会检查结构和容量，不在普通请求中擅自迁移旧表。

补充说明：
MySQL存储SESSION类。
使用InnoDB/MEDIUMBLOB保存原始字节，GET_LOCK串行化同ID请求；独立连接
避免业务事务影响会话。旧HEAP/CHAR表须在维护阶段显式upgradeSchema()。

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
private static $setting = [], $cnt = null, $table = 'my_session', $locked = '', $error = '', $validation_failed = false;
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `sess_mysql::set` | 设置独立数据库连接和存储选项；不使用业务连接或持久连接。 |
| `sess_mysql::getLastError` | 返回最近的安全错误摘要，不含凭据、SQL或会话正文。 |
| `sess_mysql::execute` | 执行原生参数化语句并关闭statement；SQL参数不经字符串拼接。 |
| `sess_mysql::connection` | 提供给派生处理器复用的专属连接；不得用于业务事务。 |
| `sess_mysql::connect` | 建立有限超时的独立连接，校验表名及字符集；连接失败不泄露认证信息。 |
| `sess_mysql::open` | 打开存储并检查旧表结构；不会在普通请求中ALTER已有表。 |
| `sess_mysql::upgradeSchema` | 显式升级现有表，保留原始数据并移除255字节/SID32位及大小写混淆限制。 |
| `sess_mysql::lock` | 校验会话ID并获取命名锁，覆盖read至close；超时返回false，防止并发丢写。 |
| `sess_mysql::unlock` | 释放当前ID的命名锁，ID轮换时允许锁定新ID。 |
| `sess_mysql::close` | 释放命名锁并关闭连接，不重复触发概率GC；可重复调用。 |
| `sess_mysql::read` | 读取未过期数据；SQL失败返回false而非伪装为空会话。 |
| `sess_mysql::write` | 保存会话并按实际写入时间续期；保留引号、NUL、中文和尾部空格。 |
| `sess_mysql::destroy` | 删除指定会话；始终绑定ID，成功后释放该ID的锁。 |
| `sess_mysql::validateId` | 严格模式只接受数据库中未过期的现有ID。 |
| `sess_mysql::updateTimestamp` | 惰性续期：只更新到期时间；若记录已被GC删除则用PHP提供的数据恢复。 |
| `sess_mysql::gc` | 按绝对到期时间回收；不再重复减去maxlifetime，不关闭仍在使用的连接。 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### sess_mysql::set

功能说明：设置独立数据库连接和存储选项；不使用业务连接或持久连接。

```text
public static function set($setting);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$setting` | array；host/user/password/name/charset，以及session_table、<br>session_lock_timeout（默认10秒，0~60）、session_max_bytes（默认1MiB，最大16777215） |

**返回值**：bool 活动连接期间拒绝重配

调用形式：

```php
$result = sess_mysql::set($setting);
```

定位：`lib/session/sess_mysql.class.php:60`。

### sess_mysql::getLastError

功能说明：返回最近的安全错误摘要，不含凭据、SQL或会话正文。

参数说明：无显式参数。

```text
public static function getLastError();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string 最近一次安全错误摘要；无已记录错误时为空串

调用形式：

```php
$result = sess_mysql::getLastError();
```

定位：`lib/session/sess_mysql.class.php:73`。

### sess_mysql::execute

功能说明：执行原生参数化语句并关闭statement；SQL参数不经字符串拼接。

异常处理：内部捕获连接/SQL执行异常并返回false，错误摘要保存在对应getLastError状态；调用方不应仅依赖catch判断失败。

```text
protected static function execute($sql, array $values = [], $row = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sql` | string；仅包含可信表名和占位符 |
| `$values` | array；绑定的字符串值（默认：[]） |
| `$row` | bool；是否返回首行（默认：false） |

**返回值**：array|null|bool 读取无记录返回null，失败返回false

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/session/sess_mysql.class.php:86`。

### sess_mysql::connection

功能说明：提供给派生处理器复用的专属连接；不得用于业务事务。

参数说明：无显式参数。

```text
protected static function connection();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：mysqli|null 当前处理器专属连接，尚未建立或已关闭为null；借用者不得关闭连接或开启业务事务

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/session/sess_mysql.class.php:113`。

### sess_mysql::connect

功能说明：建立有限超时的独立连接，校验表名及字符集；连接失败不泄露认证信息。

参数说明：无显式参数。

异常处理：内部捕获连接/SQL执行异常并返回false，错误摘要保存在对应getLastError状态；调用方不应仅依赖catch判断失败。

```text
private static function connect();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 独立连接和字符集设置成功为true；配置或连接失败为false，摘要见getLastError

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/session/sess_mysql.class.php:126`。

### sess_mysql::open

功能说明：打开存储并检查旧表结构；不会在普通请求中ALTER已有表。

```text
public static function open($sess_path, $sess_name);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sess_path` | string；协议参数，数据库模式忽略 |
| `$sess_name` | string；Cookie名称，表隔离由session_table控制 |

**返回值**：bool 存储初始化成功为true，目录/连接/结构准备失败为false

调用形式：

```php
$result = sess_mysql::open($sess_path, $sess_name);
```

定位：`lib/session/sess_mysql.class.php:161`。

### sess_mysql::upgradeSchema

功能说明：显式升级现有表，保留原始数据并移除255字节/SID32位及大小写混淆限制。

须在停止并发会话请求的维护阶段调用，账号需有ALTER权限；不自动回滚DDL。

参数说明：无显式参数。

```text
public static function upgradeSchema();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 表不存在或连接活动时失败；新表由open创建

调用形式：

```php
$result = sess_mysql::upgradeSchema();
```

定位：`lib/session/sess_mysql.class.php:189`。

### sess_mysql::lock

功能说明：校验会话ID并获取命名锁，覆盖read至close；超时返回false，防止并发丢写。

```text
private static function lock($sid);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sid` | string；会话ID |

**返回值**：bool 取得指定ID命名锁为true，非法ID、超时或后端失败为false

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/session/sess_mysql.class.php:203`。

### sess_mysql::unlock

功能说明：释放当前ID的命名锁，ID轮换时允许锁定新ID。

参数说明：无显式参数。

```text
private static function unlock();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 当前锁释放结果；没有持锁时按实现视为已释放

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/session/sess_mysql.class.php:223`。

### sess_mysql::close

功能说明：释放命名锁并关闭连接，不重复触发概率GC；可重复调用。

参数说明：无显式参数。

```text
public static function close();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 连接及锁释放后的结果；内置实现允许重复关闭

调用形式：

```php
$result = sess_mysql::close();
```

定位：`lib/session/sess_mysql.class.php:238`。

### sess_mysql::read

功能说明：读取未过期数据；SQL失败返回false而非伪装为空会话。

```text
public static function read($sid);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sid` | string；会话ID |

**返回值**：string|false 有效会话的原始序列化字节；记录不存在/过期为空串，后端或加锁失败为false

调用形式：

```php
$result = sess_mysql::read($sid);
```

定位：`lib/session/sess_mysql.class.php:254`。

### sess_mysql::write

功能说明：保存会话并按实际写入时间续期；保留引号、NUL、中文和尾部空格。

```text
public static function write($sid, $sess_data);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sid` | string；会话ID |
| `$sess_data` | string；PHP编码的原始字节串 |

**返回值**：bool 完整会话字节及期限保存成功为true；I/O、锁或容量失败为false

调用形式：

```php
$result = sess_mysql::write($sid, $sess_data);
```

定位：`lib/session/sess_mysql.class.php:269`。

### sess_mysql::destroy

功能说明：删除指定会话；始终绑定ID，成功后释放该ID的锁。

```text
public static function destroy($sid);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sid` | string；会话ID |

**返回值**：bool 目标会话删除及相关一致性处理成功为true，失败为false

调用形式：

```php
$result = sess_mysql::destroy($sid);
```

定位：`lib/session/sess_mysql.class.php:283`。

### sess_mysql::validateId

功能说明：严格模式只接受数据库中未过期的现有ID。

```text
public static function validateId($sid);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sid` | string；客户端提交的会话ID |

**返回值**：bool 当前ID实际存在且未过期为true；非法、未知、过期或检查失败为false

调用形式：

```php
$result = sess_mysql::validateId($sid);
```

定位：`lib/session/sess_mysql.class.php:296`。

### sess_mysql::updateTimestamp

功能说明：惰性续期：只更新到期时间；若记录已被GC删除则用PHP提供的数据恢复。

```text
public static function updateTimestamp($sid, $sess_data);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sid` | string；会话ID |
| `$sess_data` | string；未变化的数据 |

**返回值**：bool 当前会话有效期更新/必要时恢复写入成功为true，否则为false

调用形式：

```php
$result = sess_mysql::updateTimestamp($sid, $sess_data);
```

定位：`lib/session/sess_mysql.class.php:314`。

### sess_mysql::gc

功能说明：按绝对到期时间回收；不再重复减去maxlifetime，不关闭仍在使用的连接。

```text
public static function gc($maxlifetime);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$maxlifetime` | int；PHP协议参数，记录已包含绝对到期时间 |

**返回值**：bool 本次过期回收是否成功；不返回删除条数，缺失存储可视为无需清理

调用形式：

```php
$result = sess_mysql::gc($maxlifetime);
```

定位：`lib/session/sess_mysql.class.php:329`。
