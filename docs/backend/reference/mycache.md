# myCache：缓存代理与能力

修订日期：2026-10-23。功能详解。

## 职责与调用位置

fetch用命中标记区分false/null；计算锁是可选接口。目录/表隔离与namespace不是同一种边界。

源码：`lib/myCache.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
$cache = new myCache('File', ['path'=>$directory, 'allowed_classes'=>false], 0);
$cache->set('flag', false, 60);
$value = $cache->fetch('flag', $hit);
assert($hit && $value === false);
$result = $cache->rememberLocked('greeting', function () { return 'Hello'; }, 60);
assert($result === 'Hello');
```

## 声明与类职责

### myCache

功能说明：选择缓存驱动，封装查询/函数结果、显式业务键、能力检查和计算锁。

使用说明：
  1. 通过new myCache('File',CACHE.'data')或数据库缓存配置初始化。
  2. fetch区分有效空值与未命中；remember/rememberLocked接收可重复计算回调。
  3. getData遵循数据库查询缓存资格/失效规则；getData_func的非正TTL或历史null用于删除。
  4. clear显式清空，clean保留驱动过期回收语义；切换后端须同步相关持有者。

补充说明：
缓存代理及数据库/回调结果缓存，兼容旧set/get/remove/clean。

    $cache->init('File', $path, 0.1);              // 选择适配器及自动回收概率
    $value = $cache->fetch($key, $hit);            // 精确区分false/null与未命中
    $cache->getData($sql, 'all', 600);             // 共享失效的查询缓存
    $cache->getData_func($callback, $args, 600);    // 按回调身份及可序列化参数缓存
    $cache->remember($key, $producer, 600);        // 普通显式业务键缓存
    $cache->rememberLocked($key, $producer, 600);  // 支持锁时防止并发重复重建
    $cache->clear();                            // 管理员显式清空；clean仅回收过期

使用说明：
1. File/MySQL/MSSQL简称不区分大小写；第三方仍只须实现原interface_cache。
2. 只有无需必需参数的显式collectGarbage钩子参与自动回收，不自动调用clean。
3. 查询缓存遵循数据库读写位、事务和共享失效；参数化读取本身不使用共享缓存。
4. 对象回调身份仅在当前代理及对象生命周期内稳定；跨请求使用显式remember键，
   键须包含用户、租户及影响结果的全部上下文。资源参数不能作为稳定缓存参数。
5. 普通remember不加计算锁；rememberLocked要求后端声明interface_cache_lock。
   锁不保证业务副作用恰好执行一次，写入失败时可能再次计算。

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
protected static $cache_autoload_registered = false;
```

```text
protected $callable_identities = [];
```

```text
protected
        $func_alias = array(
            's' => 'set',
            'g' => 'get',
            'r' => 'remove',
            'c' => 'clean',
            'db' => 'getData',
            'func' => 'getData_func',
        );
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myCache::init` | 选择并初始化缓存适配器，仅登记一次目录自动加载器 |
| `myCache::change` | 切换缓存适配器，沿用 init() 的初始化及默认回收概率 |
| `myCache::getData` | 缓存全局 myDb 查询结果，支持空记录、false 和 SQL NULL |
| `myCache::getData_func` | 按可调用对象身份及序列化参数缓存结果，兼容旧 TTL 删除约定 |
| `myCache::callableIdentity` | 取得当前对象生命周期内的唯一身份，避免对象句柄复用导致键冲突 |
| `myCache::remember` | 以显式业务键缓存回调结果，使用封装区分空值与未命中 |
| `myCache::clear` | 显式清空当前后端全部数据，用于管理员清理；不同于只回收过期项的clean。 |
| `myCache::supports` | 检查当前后端是否声明指定接口，不探测远程服务或调用扩展方法。 |
| `myCache::synchronized` | 显式转发计算锁，旧后端不支持时抛异常，不静默忽略业务回调。 |
| `myCache::fetch` | 显式转发引用参数，避免魔术代理丢失命中输出；旧适配器按get结果保守推断。 |
| `myCache::rememberKey` | 生成remember的兼容物理键，提前拒绝资源/数组/对象键。 |
| `myCache::rememberUsing` | 在同一个后端上读取、计算和写入，回调中change不会把旧上下文写进新后端。 |
| `myCache::rememberLocked` | 防并发重建的显式入口：未命中后加锁，再次检查，成功发布后释放。 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myCache::init

功能说明：选择并初始化缓存适配器，仅登记一次目录自动加载器

验证新驱动后才替换当前目标；未知类名沿用历史行为回退文件驱动。
自动垃圾回收仅调用 collectGarbage()，不把第三方 clean() 当成回收方法。

```text
public function init($module = '', $setting = null, $gc_rate = 0.1);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$module` | string\|interface_cache；驱动简称、类名或已初始化驱动；空字符串使用 File（默认：''） |
| `$setting` | mixed；驱动构造参数：File 为目录/选项数组，SQL 为连接及编解码选项；对象模式忽略（默认：null） |
| `$gc_rate` | float；本次初始化触发回收的概率，范围 0 到 1；0 禁止，默认 0.1（默认：0.1） |

**返回值**：void 无返回值。

**异常与失败**：InvalidArgumentException 驱动接口不符、名称类型非法或回收概率越界；Throwable 驱动构造及回收异常原样传递

调用形式：

```php
// $instance为已初始化的myCache实例。
$result = $instance->init($module, $setting, $gc_rate);
```

定位：`lib/myCache.class.php:111`。

### myCache::change

功能说明：切换缓存适配器，沿用 init() 的初始化及默认回收概率

```text
public function change($module, $setting = null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$module` | string\|interface_cache；驱动简称、类名或已初始化适配器 |
| `$setting` | mixed；传给新驱动构造函数的设置（默认：null） |

**返回值**：void 新驱动构造、接口校验或自动回收异常时原目标仍可用

**异常与失败**：Throwable 被调用回调/后端的异常按当前入口继续传递，不包装为成功结果

调用形式：

```php
// $instance为已初始化的myCache实例。
$result = $instance->change($module, $setting);
```

定位：`lib/myCache.class.php:152`。

### myCache::getData

功能说明：缓存全局 myDb 查询结果，支持空记录、false 和 SQL NULL

仅当数据库 setCache() 已挂接共享失效后端且查询可缓存时读写；遵循 cache() 位。
实际查询期间临时关闭底层结果缓存并恢复，避免下层 TTL 遮挡本包装器 TTL。

```text
public function getData($query, $mode='all', $ttl = 600);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$query` | string；完整 SQL 查询，不能直接拼接不可信外部输入 |
| `$mode` | string\|int；all 全部记录、record 首条、result 首值、remove 删除各模式；<br>数字时兼容为 TTL，模式按 all 处理（默认：'all'） |
| `$ttl` | int\|float；有效秒数，默认 600；非正数删除当前模式条目，不执行查询（默认：600） |

**返回值**：mixed all为记录列表、record为单条/空值、result为首值；删除或无全局myDb时为空串，按驱动约定保留false/null

**异常与失败**：InvalidArgumentException 不支持的结果模式

调用形式：

```php
// $instance为已初始化的myCache实例。
$result = $instance->getData($query, $mode, $ttl);
```

定位：`lib/myCache.class.php:170`。

### myCache::getData_func

功能说明：按可调用对象身份及序列化参数缓存结果，兼容旧 TTL 删除约定

闭包/对象方法使用弱引用及随机身份，防止 PHP 复用对象句柄后误命中。
不可序列化参数应改用 remember() 并由调用方指定包含全部上下文的稳定键。

```text
public function getData_func($func, $args = array(), $ttl = 600);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$func` | callable；函数名、静态方法、对象方法、闭包或可调用对象 |
| `$args` | mixed；按顺序传给回调的参数，非数组自动包装为单个参数（默认：array()） |
| `$ttl` | int\|float\|null；有效秒数；非正数或历史null仅删除该键并返回null，不调用回调（默认：600） |

**返回值**：mixed 已缓存或新计算的结果，false/null/空数组均可命中

**异常与失败**：InvalidArgumentException 回调不可调用或参数无法序列化；Throwable 回调异常向外传递，不缓存异常结果

调用形式：

```php
// $instance为已初始化的myCache实例。
$result = $instance->getData_func($func, $args, $ttl);
```

定位：`lib/myCache.class.php:211`。

### myCache::callableIdentity

功能说明：取得当前对象生命周期内的唯一身份，避免对象句柄复用导致键冲突

```text
protected function callableIdentity($object);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$object` | object；闭包、可调用对象或对象方法所属实例 |

**返回值**：string 当前缓存代理分配的随机身份；跨请求不稳定，条目多时清理已失效弱引用

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myCache.class.php:231`。

### myCache::remember

功能说明：以显式业务键缓存回调结果，使用封装区分空值与未命中


回调约定：producer无参数，返回原始业务值；命中时不调用。键应覆盖用户/语言/租户等影响结果的上下文；失败后重试可能再次计算。
类型提示约定：TResult以同一业务键的已缓存数据与producer返回类型保持一致为前提；外部写入或业务类型改变时应变更键版本/清理该键。

调用示例：
    $value = $cache->remember('public:home:v1', function () { return ['title' => '首页']; }, 60);

```text
public function remember($key, callable $producer, $ttl = 600);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | string；含用户/租户/参数等所有结果上下文的键，内部再加命名空间并散列 |
| `$producer` | callable；未命中时无参数调用；调用方使用闭包捕获所需参数 |
| `$ttl` | int\|float；有效秒数，默认 600；非正数直接计算，不读写或删除现有条目（默认：600） |

**返回值**：mixed 缓存值或回调结果，包括 false、null、空数组及空字符串

**异常与失败**：Throwable 回调异常原样传递，不写入异常结果；不提供并发单次执行锁

调用形式：

```php
// $instance为已初始化的myCache实例。
$result = $instance->remember($key, $producer, $ttl);
```

定位：`lib/myCache.class.php:258`。

### myCache::clear

功能说明：显式清空当前后端全部数据，用于管理员清理；不同于只回收过期项的clean。

同目录/同表的所有namespace均受影响，文件锁基础设施保留；并发请求仍可重建值。
第三方后端须显式提供clear，不把含义不确定的clean当作清空入口。

参数说明：无显式参数。

```text
public function clear();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 存储清空成功为true，后端返回失败为false

**异常与失败**：BadMethodCallException 后端没有公开的clear方法；Throwable 后端异常原样传递

调用形式：

```php
// $instance为已初始化的myCache实例。
$result = $instance->clear();
```

定位：`lib/myCache.class.php:277`。

### myCache::supports

功能说明：检查当前后端是否声明指定接口，不探测远程服务或调用扩展方法。

```text
public function supports($interface);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$interface` | string；接口名，例如interface_cache_lookup或interface_cache_lock |

**返回值**：bool 未初始化、非法/未知名称或普通类名为false

调用形式：

```php
// $instance为已初始化的myCache实例。
$result = $instance->supports($interface);
```

定位：`lib/myCache.class.php:288`。

### myCache::synchronized

功能说明：显式转发计算锁，旧后端不支持时抛异常，不静默忽略业务回调。

```text
public function synchronized($key, callable $operation, $timeout = 5);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | mixed；逻辑锁键 |
| `$operation` | callable；无参数回调 |
| `$timeout` | int\|float；等待秒数，0..60（默认：5） |

**返回值**：mixed 回调结果

**异常与失败**：BadMethodCallException 后端未声明计算锁接口；Throwable 锁或回调异常继续传递

调用形式：

```php
// $instance为已初始化的myCache实例。
$result = $instance->synchronized($key, $operation, $timeout);
```

定位：`lib/myCache.class.php:306`。

### myCache::fetch

功能说明：显式转发引用参数，避免魔术代理丢失命中输出；旧适配器按get结果保守推断。


调用示例：
    $value = $cache->fetch('counter', $hit);
    if (!$hit) {
        // 处理未命中；有效false/null仍可能命中
    }

```text
public function fetch($key, &$hit = null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | mixed；缓存键 |
| `&$hit` | bool\|null；引用参数；精确命中；旧get返回false时无法判断是否命中（默认：null） |

**返回值**：mixed 缓存值或false

调用形式：

```php
// $instance为已初始化的myCache实例。
$result = $instance->fetch($key, $hit);
```

定位：`lib/myCache.class.php:326`。

### myCache::rememberKey

功能说明：生成remember的兼容物理键，提前拒绝资源/数组/对象键。

```text
protected function rememberKey($key);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | mixed；有限标量或null |

**返回值**：string 带版本的SHA-256键

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myCache.class.php:337`。

### myCache::rememberUsing

功能说明：在同一个后端上读取、计算和写入，回调中change不会把旧上下文写进新后端。


类型提示约定：TResult以同一业务键的已缓存数据与producer返回类型保持一致为前提；外部写入或业务类型改变时应变更键版本/清理该键。

```text
protected function rememberUsing(interface_cache $backend, $key, callable $producer, $ttl);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$backend` | interface_cache；本次捕获的后端 |
| `$key` | string；物理remember键 |
| `$producer` | callable；无参数回调 |
| `$ttl` | int\|float；保存秒数 |

**返回值**：mixed 已有或新计算的值，写失败仍返回已计算的业务结果

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myCache.class.php:357`。

### myCache::rememberLocked

功能说明：防并发重建的显式入口：未命中后加锁，再次检查，成功发布后释放。


回调约定：producer无参数，返回原始业务值；命中时不调用。键应覆盖用户/语言/租户等影响结果的上下文；失败后重试可能再次计算。
类型提示约定：TResult以同一业务键的已缓存数据与producer返回类型保持一致为前提；外部写入或业务类型改变时应变更键版本/清理该键。

调用示例：
    if ($cache->supports(interface_cache_lock::class)) {
        $value = $cache->rememberLocked('report:2026', $producer, 120, 3);
    }

```text
public function rememberLocked($key, callable $producer, $ttl = 600, $timeout = 5);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | mixed；含全部用户/租户及业务参数的逻辑键 |
| `$producer` | callable；无参数计算回调；异常不缓存，锁仍释放 |
| `$ttl` | int\|float；秒数，非正数直接计算，不读取/加锁/写入（默认：600） |
| `$timeout` | int\|float；锁等待秒数，0..60（默认：5） |

**返回值**：mixed 缓存值或计算结果；依赖同一后端和正常存储，非恰好一次副作用保证

**异常与失败**：BadMethodCallException 后端未声明interface_cache_lock；Throwable 锁超时、参数或生产回调异常继续向外传递

调用形式：

```php
// $instance为已初始化的myCache实例。
$result = $instance->rememberLocked($key, $producer, $ttl, $timeout);
```

定位：`lib/myCache.class.php:386`。
