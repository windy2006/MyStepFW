# sess_file：sess_file 会话处理器

## 职责与调用位置

此文件实现PHP会话保存或查询相关能力，通常由框架按session.mode选择。应用内使用会话门面，不重复启动处理器；底层回调由PHP引擎调用。

源码：`lib/session/sess_file.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

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

### sess_file

功能说明：提供会话ID校验、持锁读写、过期判断、时间更新和安全垃圾回收。

使用说明：
  1. 经myReq::sessionStart选择，启动前配置路径及Session名称。
  2. 读锁在本请求会话存续期间保持，close负责释放；不要直接删除活动锁目录。
  3. no_log请求跳过持久化，已有资源仍在close阶段释放。

补充说明：
文件存储SESSION控制类。
read至close持有独占锁，支持旧日期目录迁移、严格ID校验与惰性续期；
文件内容保持PHP原始会话格式，目录应位于不可通过HTTP下载的位置。

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
public static $path;
```

```text
protected static $handle = null, $sid = '', $legacy_files = [];
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `sess_file::open` | 初始化存储目录；相对路径转为绝对路径，避免关闭阶段工作目录变化。 |
| `sess_file::validId` | 校验ID字符集和长度；不接受路径分隔符或SQL/控制字符。 |
| `sess_file::release` | 释放当前文件锁及读取状态；可重复调用。 |
| `sess_file::sameFile` | 判断已打开句柄仍对应原路径的普通文件，拒绝符号链接和已被删除的inode。 |
| `sess_file::lock` | 获取当前ID的独占锁；等待者在删除/回收后重新打开路径，避免写入失联文件。 |
| `sess_file::close` | 关闭会话并释放锁；垃圾回收统一由PHP的gc概率控制。 |
| `sess_file::legacyFiles` | 获取旧日期目录中的同ID文件；过滤链接目录，避免越出当前存储根目录。 |
| `sess_file::read` | 读取会话；已过期内容立即清空，不依赖概率GC。 |
| `sess_file::write` | 保存完整原始数据并刷新mtime；成功写完后截断旧尾部，处理短写和空会话。 |
| `sess_file::destroy` | 删除当前会话及旧日期副本；先清空锁内数据，阻止排队请求读到旧登录信息。 |
| `sess_file::validateId` | 严格模式校验现有且未过期的ID；未知ID不会生成空文件。 |
| `sess_file::updateTimestamp` | 惰性写入的续期回调；按处理器协议保存访问时间。 |
| `sess_file::cleanDirectory` | 非阻塞回收单目录的过期普通文件；跳过正在使用的会话和符号链接。 |
| `sess_file::gc` | 回收稳定目录及日期目录；缺失目录视为无需清理。 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### sess_file::open

功能说明：初始化存储目录；相对路径转为绝对路径，避免关闭阶段工作目录变化。

```text
public static function open($sess_path, $sess_name);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sess_path` | string；会话目录（不支持原生files的N;MODE;分层格式） |
| `$sess_name` | string；会话Cookie名称，保留回调协议参数 |

**返回值**：bool 存储初始化成功为true，目录/连接/结构准备失败为false

调用形式：

```php
$result = sess_file::open($sess_path, $sess_name);
```

定位：`lib/session/sess_file.class.php:59`。

### sess_file::validId

功能说明：校验ID字符集和长度；不接受路径分隔符或SQL/控制字符。

```text
protected static function validId($sid);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sid` | string；会话ID |

**返回值**：bool ID字符和长度符合当前处理器要求为true；不证明存储记录已存在

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/session/sess_file.class.php:77`。

### sess_file::release

功能说明：释放当前文件锁及读取状态；可重复调用。

参数说明：无显式参数。

```text
protected static function release();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/session/sess_file.class.php:88`。

### sess_file::sameFile

功能说明：判断已打开句柄仍对应原路径的普通文件，拒绝符号链接和已被删除的inode。

```text
protected static function sameFile($handle, $file);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$handle` | resource；文件句柄 |
| `$file` | string；完整文件名 |

**返回值**：bool 打开的句柄仍指向原路径的普通文件为true；链接、删除或替换为false

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/session/sess_file.class.php:105`。

### sess_file::lock

功能说明：获取当前ID的独占锁；等待者在删除/回收后重新打开路径，避免写入失联文件。

```text
protected static function lock($sid, $create = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sid` | string；会话ID |
| `$create` | bool；是否允许创建；严格校验使用false（默认：true） |

**返回值**：bool 取得ID对应的独占锁并绑定有效文件句柄为true；失败为false

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/session/sess_file.class.php:120`。

### sess_file::close

功能说明：关闭会话并释放锁；垃圾回收统一由PHP的gc概率控制。

参数说明：无显式参数。

```text
public static function close();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 连接及锁释放后的结果；内置实现允许重复关闭

调用形式：

```php
$result = sess_file::close();
```

定位：`lib/session/sess_file.class.php:150`。

### sess_file::legacyFiles

功能说明：获取旧日期目录中的同ID文件；过滤链接目录，避免越出当前存储根目录。

```text
protected static function legacyFiles($sid);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sid` | string；会话ID（须已通过校验） |

**返回值**：string[] 同ID的旧日期目录会话文件路径列表，不包含符号链接目录

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/session/sess_file.class.php:161`。

### sess_file::read

功能说明：读取会话；已过期内容立即清空，不依赖概率GC。

```text
public static function read($sid);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sid` | string；会话ID |

**返回值**：string|false 有效会话的原始序列化字节；记录不存在/过期为空串，后端或加锁失败为false

调用形式：

```php
$result = sess_file::read($sid);
```

定位：`lib/session/sess_file.class.php:174`。

### sess_file::write

功能说明：保存完整原始数据并刷新mtime；成功写完后截断旧尾部，处理短写和空会话。

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
$result = sess_file::write($sid, $sess_data);
```

定位：`lib/session/sess_file.class.php:216`。

### sess_file::destroy

功能说明：删除当前会话及旧日期副本；先清空锁内数据，阻止排队请求读到旧登录信息。

```text
public static function destroy($sid);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sid` | string；会话ID |

**返回值**：bool 目标会话删除及相关一致性处理成功为true，失败为false

调用形式：

```php
$result = sess_file::destroy($sid);
```

定位：`lib/session/sess_file.class.php:238`。

### sess_file::validateId

功能说明：严格模式校验现有且未过期的ID；未知ID不会生成空文件。

```text
public static function validateId($sid);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sid` | string；客户端提交的会话ID |

**返回值**：bool 当前ID实际存在且未过期为true；非法、未知、过期或检查失败为false

调用形式：

```php
$result = sess_file::validateId($sid);
```

定位：`lib/session/sess_file.class.php:253`。

### sess_file::updateTimestamp

功能说明：惰性写入的续期回调；按处理器协议保存访问时间。

```text
public static function updateTimestamp($sid, $sess_data);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$sid` | string；会话ID |
| `$sess_data` | string；原始会话数据 |

**返回值**：bool 当前会话有效期更新/必要时恢复写入成功为true，否则为false

调用形式：

```php
$result = sess_file::updateTimestamp($sid, $sess_data);
```

定位：`lib/session/sess_file.class.php:274`。

### sess_file::cleanDirectory

功能说明：非阻塞回收单目录的过期普通文件；跳过正在使用的会话和符号链接。

```text
protected static function cleanDirectory($path, $maxlifetime);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$path` | string；扫描目录 |
| `$maxlifetime` | int；有效秒数 |

**返回值**：void 无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/session/sess_file.class.php:289`。

### sess_file::gc

功能说明：回收稳定目录及日期目录；缺失目录视为无需清理。

```text
public static function gc($maxlifetime);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$maxlifetime` | int；过期秒数，负值按0处理 |

**返回值**：bool 处理器报告的回收结果；文件处理器可能跳过忙文件，true不表示所有过期文件均已删除，也不是删除条数

调用形式：

```php
$result = sess_file::gc($maxlifetime);
```

定位：`lib/session/sess_file.class.php:309`。
