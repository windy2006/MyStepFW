# 文件缓存

接口说明：`myCache_File`。

## 职责与调用位置

myCache通过此层处理缓存存储、命中或锁；不要从驱动名称推断它实现全部可选协议。编码预算、有效false/null和失败返回按本页逐项判断。

源码：`lib/cache/myCache_File.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

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

### myCache_File

功能说明：提供V3校验帧、V2兼容、原子写入、精确命中、计算锁及有限预算回收。

使用说明：
  1. 通过new myCache('File',CACHE.'data')使用，或传入含path的选项数组。
  2. set/get/fetch/remove读写条目，rememberLocked经代理执行防击穿计算。
  3. clean仅回收过期/损坏项，clear清空有效数据但保留活动锁文件。

补充说明：
文件缓存：稳定v2路径、V3校验帧、原子发布及有限预算GC。
兼容V2序列化条目；过期判断先于对象实例化，目录须为受信私有存储。
fetch可区分有效false/null，synchronized提供显式防击穿锁；clean只回收过期，clear显式清空并保留锁。

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
protected $path = '', $codec, $error = '', $computing = [];
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myCache_File::__construct` | 初始化私有缓存根目录，不更改已有目录权限。 |
| `myCache_File::directory` | 创建私有目录；竞态下已创建目录也视为成功。 |
| `myCache_File::now` | 返回真实Unix时间，测试子类可覆盖。 |
| `myCache_File::getLastError` | 返回最近存储错误摘要，不含缓存正文。 |
| `myCache_File::filename` | 将标量键映射为分片文件，沿用空namespace的MD5布局。 |
| `myCache_File::locked` | 分片写锁内执行操作；非阻塞方式返回null表示繁忙。 |
| `myCache_File::set` | 写入有长度和SHA-256校验的V3帧，同目录原子替换；失败保留原值。 |
| `myCache_File::record` | 读取帧元数据及有效负载；旧格式先禁用对象解码，过期帧不实例化对象。 |
| `myCache_File::fetch` | 精确读取，命中状态独立于值；过期/损坏时尽力回收，繁忙时不阻塞。 |
| `myCache_File::get` | 兼容旧get，值false与未命中仍返回同一标记。 |
| `myCache_File::expire` | 非阻塞锁内复查，防止删除其他进程刚发布的新值，GC不运行对象生命周期。 |
| `myCache_File::remove` | 删除单个键，不删除锁文件，不跟随符号链接。 |
| `myCache_File::clean` | 全量回收过期及可识别损坏条目，保留有效值和无关文件。 |
| `myCache_File::clear` | 显式删除本目录所有namespace的v2/V3条目，保留分片写锁、计算锁及无关文件。 |
| `myCache_File::collectGarbage` | 分片轮转回收，持久化扫描位置，避免总是只检查同一批有效文件。 |
| `myCache_File::synchronized` | 独立的256分片计算锁；不复用数据写锁，可在回调内set/remember。 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myCache_File::__construct

功能说明：初始化私有缓存根目录，不更改已有目录权限。

```text
public function __construct($path = null, array $options = []);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$path` | string\|array\|null；目录或含path及公共编解码选项的数组，null使用框架/应用临时目录（默认：null） |
| `$options` | array；max_bytes/max_depth/allowed_classes/namespace，覆盖数组配置中的同名项（默认：[]） |

**返回值**：void 初始化或释放资源，无返回值。

**异常与失败**：InvalidArgumentException 配置或根目录非法

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/cache/myCache_File.class.php:66`。

### myCache_File::directory

功能说明：创建私有目录；竞态下已创建目录也视为成功。

```text
protected function directory($path);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$path` | string；受信目录 |

**返回值**：bool 目录已存在或成功创建为true，创建失败为false

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/cache/myCache_File.class.php:82`。

### myCache_File::now

功能说明：返回真实Unix时间，测试子类可覆盖。

参数说明：无显式参数。

```text
protected function now();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：int 当前Unix时间戳，单位秒

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/cache/myCache_File.class.php:91`。

### myCache_File::getLastError

功能说明：返回最近存储错误摘要，不含缓存正文。

参数说明：无显式参数。

```text
public function getLastError();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string 最近一次安全错误摘要；无已记录错误时为空串

调用形式：

```php
// $instance为已初始化的myCache_File实例。
$result = $instance->getLastError();
```

定位：`lib/cache/myCache_File.class.php:100`。

### myCache_File::filename

功能说明：将标量键映射为分片文件，沿用空namespace的MD5布局。

```text
protected function filename($key);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | mixed；有限标量或null |

**返回值**：string|false 安全路径或被符号链接等阻断时false

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/cache/myCache_File.class.php:108`。

### myCache_File::locked

功能说明：分片写锁内执行操作；非阻塞方式返回null表示繁忙。

```text
protected function locked($file, callable $operation, $blocking = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；已校验条目路径 |
| `$operation` | callable；无参数操作 |
| `$blocking` | bool；是否等待其他写操作（默认：true） |

**返回值**：mixed 回调结果，锁/路径故障为false，繁忙为null

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/cache/myCache_File.class.php:121`。

### myCache_File::set

功能说明：写入有长度和SHA-256校验的V3帧，同目录原子替换；失败保留原值。

```text
public function set($key, $value = '', $ttl = 600);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | mixed；缓存键 |
| `$value` | mixed；可序列化受信数据，省略时删除（默认：''） |
| `$ttl` | int\|float；秒数，非正数删除（默认：600） |

**返回值**：bool 原子发布成功为true；省略value或TTL非正时是remove的结果；校验/编码/存储失败为false

**异常与失败**：Throwable 非法参数或序列化回调异常；预算超限返回false

调用形式：

```php
// $instance为已初始化的myCache_File实例。
$result = $instance->set($key, $value, $ttl);
```

定位：`lib/cache/myCache_File.class.php:143`。

### myCache_File::record

功能说明：读取帧元数据及有效负载；旧格式先禁用对象解码，过期帧不实例化对象。

```text
protected function record($file);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；条目路径 |

**返回值**：array|false 含expire/payload或legacy/raw；损坏/超限/读取失败为false

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/cache/myCache_File.class.php:166`。

### myCache_File::fetch

功能说明：精确读取，命中状态独立于值；过期/损坏时尽力回收，繁忙时不阻塞。

```text
public function fetch($key, &$hit = null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | mixed；缓存键 |
| `&$hit` | bool\|null；引用参数；命中输出，false/null值同样可以命中（默认：null） |

**返回值**：mixed 命中时返回原值（含false/null），未命中/过期/损坏/后端失败为false；须结合hit判断

调用形式：

```php
// $instance为已初始化的myCache_File实例。
$result = $instance->fetch($key, $hit);
```

定位：`lib/cache/myCache_File.class.php:198`。

### myCache_File::get

功能说明：兼容旧get，值false与未命中仍返回同一标记。

```text
public function get($key);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | mixed；缓存键 |

**返回值**：mixed 有效缓存值；未命中或失败为false，不能区分已缓存的false

调用形式：

```php
// $instance为已初始化的myCache_File实例。
$result = $instance->get($key);
```

定位：`lib/cache/myCache_File.class.php:216`。

### myCache_File::expire

功能说明：非阻塞锁内复查，防止删除其他进程刚发布的新值，GC不运行对象生命周期。

```text
protected function expire($file);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；条目路径 |

**返回值**：bool 繁忙或已不存在视为跳过成功

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/cache/myCache_File.class.php:224`。

### myCache_File::remove

功能说明：删除单个键，不删除锁文件，不跟随符号链接。

```text
public function remove($key);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | mixed；缓存键 |

**返回值**：bool 不存在也为成功

调用形式：

```php
// $instance为已初始化的myCache_File实例。
$result = $instance->remove($key);
```

定位：`lib/cache/myCache_File.class.php:244`。

### myCache_File::clean

功能说明：全量回收过期及可识别损坏条目，保留有效值和无关文件。

参数说明：无显式参数。

```text
public function clean();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 全量回收是否成功；已有其他GC持锁时跳过并返回true，不表示实际回收数量

调用形式：

```php
// $instance为已初始化的myCache_File实例。
$result = $instance->clean();
```

定位：`lib/cache/myCache_File.class.php:257`。

### myCache_File::clear

功能说明：显式删除本目录所有namespace的v2/V3条目，保留分片写锁、计算锁及无关文件。

每个条目在原写锁内删除；不实例化缓存对象，不递归删除目录或跟随符号链接。
并发生产者可能在清理后重新发布，此操作不保证全局原子快照。

参数说明：无显式参数。

```text
public function clear();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 所有已发现条目删除成功为true，路径/锁/删除失败为false

调用形式：

```php
// $instance为已初始化的myCache_File实例。
$result = $instance->clear();
```

定位：`lib/cache/myCache_File.class.php:269`。

### myCache_File::collectGarbage

功能说明：分片轮转回收，持久化扫描位置，避免总是只检查同一批有效文件。

```text
public function collectGarbage($limit = 200);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$limit` | int；最多检查条目数，默认200；0不限，负数非法（默认：200） |

**返回值**：bool 回收/游标更新成功为true；GC锁已忙时跳过并返回true；路径、锁或I/O失败为false

**异常与失败**：InvalidArgumentException 回收预算不是非负整数

调用形式：

```php
// $instance为已初始化的myCache_File实例。
$result = $instance->collectGarbage($limit);
```

定位：`lib/cache/myCache_File.class.php:298`。

### myCache_File::synchronized

功能说明：独立的256分片计算锁；不复用数据写锁，可在回调内set/remember。

```text
public function synchronized($key, callable $operation, $timeout = 5);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | mixed；逻辑锁键 |
| `$operation` | callable；无参数回调 |
| `$timeout` | int\|float；等待秒数，0..60（默认：5） |

**返回值**：mixed 回调结果

**异常与失败**：RuntimeException 路径故障或锁超时；LogicException 同适配器递归请求同一逻辑键

调用形式：

```php
// $instance为已初始化的myCache_File实例。
$result = $instance->synchronized($key, $operation, $timeout);
```

定位：`lib/cache/myCache_File.class.php:340`。
