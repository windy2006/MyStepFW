# myCacheCodec：myCacheCodec 缓存驱动／协议

修订日期：2026-10-23。功能详解。

## 职责与调用位置

myCache通过此层处理缓存存储、命中或锁；不要从驱动名称推断它实现全部可选协议。编码预算、有效false/null和失败返回按本页逐项判断。

源码：`lib/cache/myCacheCodec.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

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

### myCacheCodec

功能说明：集中处理内置缓存驱动共用的键、TTL 和序列化规则。

默认保留受信对象兼容性，可通过 allowed_classes 禁用或限制对象实例化。
字节预算约束序列化结果和存储读取量，不代替业务输入的内存预算。

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
protected $max_bytes = 8388608, $depth = 128, $allowed = true, $namespace = '';
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myCacheCodec::__construct` | 验证公共选项。 |
| `myCacheCodec::key` | 转换逻辑键；保持无namespace时原有MD5布局，namespace不是访问权限边界。 |
| `myCacheCodec::expires` | 规范化TTL，防止NAN/INF、浮点转整数和到期加法溢出。 |
| `myCacheCodec::timeout` | 验证锁等待时间。 |
| `myCacheCodec::maxBytes` | 返回原始序列化字节预算。 |
| `myCacheCodec::maxDepth` | 返回序列化结构深度预算。 |
| `myCacheCodec::inspect` | 拒绝PHP会静默编码为0的资源，并对数组引用/对象循环去重。 |
| `myCacheCodec::pack` | 编码受信数据，超限或不可序列化时不发布新条目。 |
| `myCacheCodec::unpack` | 解码已确认未过期的受信数据，不执行超出预算的数据。 |
| `myCacheCodec::sqlUnpack` | 读取SQL缓存的MS_CACHE2 Base64封装或旧原始序列化值。 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myCacheCodec::__construct

功能说明：验证公共选项。

```text
public function __construct(array $options = []);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$options` | array；max_bytes默认8MiB（1..64MiB）、max_depth默认128（1..4096）、allowed_classes及namespace（默认：[]） |

**返回值**：void 初始化或释放资源，无返回值。

**异常与失败**：InvalidArgumentException 配置非法

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/cache/myCacheCodec.class.php:62`。

### myCacheCodec::key

功能说明：转换逻辑键；保持无namespace时原有MD5布局，namespace不是访问权限边界。

```text
public function key($key);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | scalar\|null；有限标量或null，转为字符串后计算摘要；不同类型但文本相同的键可能相同 |

**返回值**：string 固定32位摘要

**异常与失败**：InvalidArgumentException 键不是有限标量或null

调用形式：

```php
// $instance为已初始化的myCacheCodec实例。
$result = $instance->key($key);
```

定位：`lib/cache/myCacheCodec.class.php:83`。

### myCacheCodec::expires

功能说明：规范化TTL，防止NAN/INF、浮点转整数和到期加法溢出。

```text
public static function expires($ttl, $now = null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$ttl` | int\|float\|string；有效秒数，必须有限；正小数向上取整，非正值表示删除 |
| `$now` | int\|null；当前秒数，null使用time，测试时可传入时钟（默认：null） |

**返回值**：int 绝对到期时间；非正TTL为0

**异常与失败**：InvalidArgumentException TTL不是有限数值，或绝对到期时间会溢出

调用形式：

```php
$result = myCacheCodec::expires($ttl, $now);
```

定位：`lib/cache/myCacheCodec.class.php:97`。

### myCacheCodec::timeout

功能说明：验证锁等待时间。

```text
public static function timeout($timeout);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$timeout` | int\|float\|string；等待秒数，须为0至60的有限数值；0表示立即尝试 |

**返回值**：float 有限等待秒数

**异常与失败**：InvalidArgumentException 等待时间不是0至60秒内的有限数值

调用形式：

```php
$result = myCacheCodec::timeout($timeout);
```

定位：`lib/cache/myCacheCodec.class.php:112`。

### myCacheCodec::maxBytes

功能说明：返回原始序列化字节预算。

参数说明：无显式参数。

```text
public function maxBytes();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：int 允许序列化/解码的原始字节上限，单位字节

调用形式：

```php
// $instance为已初始化的myCacheCodec实例。
$result = $instance->maxBytes();
```

定位：`lib/cache/myCacheCodec.class.php:124`。

### myCacheCodec::maxDepth

功能说明：返回序列化结构深度预算。

参数说明：无显式参数。

```text
public function maxDepth();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：int 允许数组/对象嵌套的最大深度

调用形式：

```php
// $instance为已初始化的myCacheCodec实例。
$result = $instance->maxDepth();
```

定位：`lib/cache/myCacheCodec.class.php:133`。

### myCacheCodec::inspect

功能说明：拒绝PHP会静默编码为0的资源，并对数组引用/对象循环去重。

```text
protected function inspect($value, array &$references, SplObjectStorage $objects, $depth = 0);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$value` | mixed；待检查数据 |
| `&$references` | array；已访问数组引用ID |
| `$objects` | SplObjectStorage；已访问对象 |
| `$depth` | int；当前层级（默认：0） |

**返回值**：void 无返回值。

**异常与失败**：InvalidArgumentException 值含活动或已关闭的资源；LengthException 值的嵌套深度超过配置上限

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/cache/myCacheCodec.class.php:146`。

### myCacheCodec::pack

功能说明：编码受信数据，超限或不可序列化时不发布新条目。

```text
public function pack($value);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$value` | mixed；数据值 |

**返回值**：string PHP序列化字节串

**异常与失败**：LengthException 编码字节或结构深度超限；Throwable 对象序列化回调异常继续传递

调用形式：

```php
// $instance为已初始化的myCacheCodec实例。
$result = $instance->pack($value);
```

定位：`lib/cache/myCacheCodec.class.php:169`。

### myCacheCodec::unpack

功能说明：解码已确认未过期的受信数据，不执行超出预算的数据。

```text
public function unpack($data, &$valid);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$data` | string；PHP序列化字节串 |
| `&$valid` | bool；引用参数；解码有效时true，合法false也为true |

**返回值**：mixed 数据或失败标记false

调用形式：

```php
// $instance为已初始化的myCacheCodec实例。
$result = $instance->unpack($data, $valid);
```

定位：`lib/cache/myCacheCodec.class.php:183`。

### myCacheCodec::sqlUnpack

功能说明：读取SQL缓存的MS_CACHE2 Base64封装或旧原始序列化值。

```text
public function sqlUnpack($data, &$valid);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$data` | string；数据库列内容 |
| `&$valid` | bool；引用参数；有效性输出 |

**返回值**：mixed 解码结果，格式/预算失败为false

调用形式：

```php
// $instance为已初始化的myCacheCodec实例。
$result = $instance->sqlUnpack($data, $valid);
```

定位：`lib/cache/myCacheCodec.class.php:199`。

