# sess_mystep：sess_mystep 会话处理器

## 职责与调用位置

此文件实现PHP会话保存或查询相关能力，通常由框架按session.mode选择。应用内使用会话门面，不重复启动处理器；底层回调由PHP引擎调用。

源码：`lib/session/sess_mystep.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

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

### sess_mystep

功能说明：在独立InnoDB表保存权威会话，同时维护支持user_online展示记录。

使用说明：
  1. 由CMS会话配置选择；认证表与在线展示表分离，展示摘要不能作为登录凭据。
  2. 在线统计失败可单独查询，不应导致已保存的认证数据丢失。
  3. 不同应用/后端使用独立Session名称；no_log公开入口不启动/持久化会话。

补充说明：
自定义SESSION存储类（含在线访客监控）。
登录数据保存在{pre}session_data的独立InnoDB表中，在线统计继续使用原user_online表；
同IP访客、爬虫及API请求的统计策略不再控制认证数据的保存。

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
public static $cnt = null, $run = 0;
```

```text
private static $online = '', $storage = '', $tracking_error = false;
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `sess_mystep::open` | 从框架配置初始化独立会话表和在线统计；不自动改写旧user_online表结构。 |
| `sess_mystep::close` | 释放专属数据库连接和锁，清空监控状态；GC不再负责关闭连接。 |
| `sess_mystep::read` | 读取指定ID的有效认证数据。 |
| `sess_mystep::sessionTable` | 获取已校验的独立会话存储表名。 |
| `sess_mystep::write` | 可靠保存所有请求的认证数据，再尽力刷新在线统计；统计失败不伪装为认证失败。 |
| `sess_mystep::track` | 按访客策略刷新在线摘要；统计ID使用32位摘要，支持原CHAR(32)字段。 |
| `sess_mystep::trackingData` | 生成255字节内的展示摘要，支持原在线列表的PHP会话解码；不复制认证版本、 |
| `sess_mystep::destroy` | 删除认证及旧在线副本；旧副本删除失败时保留空会话屏障并报告失败，避免恢复旧身份。 |
| `sess_mystep::validateId` | 严格校验会话ID；必须实际存在有效会话，不能只检查ID格式。 |
| `sess_mystep::updateTimestamp` | 惰性写入也刷新认证期限及可选在线统计。 |
| `sess_mystep::gc` | 清理主会话和过期在线访客；保持连接与run状态，允许PHP随后继续write。 |
| `sess_mystep::hasTrackingError` | 本次打开以来在线统计是否失败；不包含敏感SQL及会话内容。 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### sess_mystep::open

功能说明：从框架配置初始化独立会话表和在线统计；不自动改写旧user_online表结构。

```text
public static function open($sess_path, $sess_name);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sess_path` | string；协议参数，忽略 |
| `$sess_name` | string；Cookie名称 |

**返回值**：bool 存储初始化成功为true，目录/连接/结构准备失败为false

调用形式：

```php
$result = sess_mystep::open($sess_path, $sess_name);
```

定位：`lib/session/sess_mystep.class.php:58`。

### sess_mystep::close

功能说明：释放专属数据库连接和锁，清空监控状态；GC不再负责关闭连接。

参数说明：无显式参数。

```text
public static function close();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 连接及锁释放后的结果；内置实现允许重复关闭

调用形式：

```php
$result = sess_mystep::close();
```

定位：`lib/session/sess_mystep.class.php:88`。

### sess_mystep::read

功能说明：读取指定ID的有效认证数据。

```text
public static function read($sid);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sid` | string；会话ID |

**返回值**：string|false 有效会话的原始序列化字节；记录不存在/过期为空串，后端或加锁失败为false

调用形式：

```php
$result = sess_mystep::read($sid);
```

定位：`lib/session/sess_mystep.class.php:101`。

### sess_mystep::sessionTable

功能说明：获取已校验的独立会话存储表名。

参数说明：无显式参数。

```text
private static function sessionTable();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string 包含经校验前缀的认证存储表名，不含任意用户SQL

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/session/sess_mystep.class.php:124`。

### sess_mystep::write

功能说明：可靠保存所有请求的认证数据，再尽力刷新在线统计；统计失败不伪装为认证失败。

```text
public static function write($sid, $sess_data);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sid` | string；会话ID |
| `$sess_data` | string；PHP编码后的字节串 |

**返回值**：bool 完整会话字节及期限保存成功为true；I/O、锁或容量失败为false

调用形式：

```php
$result = sess_mystep::write($sid, $sess_data);
```

定位：`lib/session/sess_mystep.class.php:135`。

### sess_mystep::track

功能说明：按访客策略刷新在线摘要；统计ID使用32位摘要，支持原CHAR(32)字段。

```text
private static function track($sid);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sid` | string；已由认证存储校验的会话ID |

**返回值**：bool 统计作为尽力执行的附属功能，错误另由hasTrackingError报告

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/session/sess_mystep.class.php:147`。

### sess_mystep::trackingData

功能说明：生成255字节内的展示摘要，支持原在线列表的PHP会话解码；不复制认证版本、

CSRF令牌等会话私密字段。统计标记仅用于展示，不作为认证凭据。

参数说明：无显式参数。

```text
private static function trackingData();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string 最多255字节的在线统计会话摘要，保留php会话编码，不含完整认证数据

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/session/sess_mystep.class.php:169`。

### sess_mystep::destroy

功能说明：删除认证及旧在线副本；旧副本删除失败时保留空会话屏障并报告失败，避免恢复旧身份。

```text
public static function destroy($sid);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sid` | string；会话ID |

**返回值**：bool 目标会话删除及相关一致性处理成功为true，失败为false

调用形式：

```php
$result = sess_mystep::destroy($sid);
```

定位：`lib/session/sess_mystep.class.php:186`。

### sess_mystep::validateId

功能说明：严格校验会话ID；必须实际存在有效会话，不能只检查ID格式。

```text
public static function validateId($sid);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sid` | string；客户端提交的会话ID |

**返回值**：bool 当前ID实际存在且未过期为true；非法、未知、过期或检查失败为false

调用形式：

```php
$result = sess_mystep::validateId($sid);
```

定位：`lib/session/sess_mystep.class.php:207`。

### sess_mystep::updateTimestamp

功能说明：惰性写入也刷新认证期限及可选在线统计。

```text
public static function updateTimestamp($sid, $sess_data);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sid` | string；会话ID |
| `$sess_data` | string；未变化的原始数据 |

**返回值**：bool 当前会话有效期更新/必要时恢复写入成功为true，否则为false

调用形式：

```php
$result = sess_mystep::updateTimestamp($sid, $sess_data);
```

定位：`lib/session/sess_mystep.class.php:220`。

### sess_mystep::gc

功能说明：清理主会话和过期在线访客；保持连接与run状态，允许PHP随后继续write。

```text
public static function gc($maxlifetime = 1800);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$maxlifetime` | int；过期秒数，默认1800（默认：1800） |

**返回值**：bool 本次过期回收是否成功；不返回删除条数，缺失存储可视为无需清理

调用形式：

```php
$result = sess_mystep::gc($maxlifetime);
```

定位：`lib/session/sess_mystep.class.php:231`。

### sess_mystep::hasTrackingError

功能说明：本次打开以来在线统计是否失败；不包含敏感SQL及会话内容。

参数说明：无显式参数。

```text
public static function hasTrackingError();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 本次open以来发生在线统计错误为true；不表示认证会话保存失败

调用形式：

```php
$result = sess_mystep::hasTrackingError();
```

定位：`lib/session/sess_mystep.class.php:245`。
