# 缓存协议、实例与命中状态

修订日期：2026-10-23。适用于当前框架。

## 本篇目标与调用场景

缓存键、后端、有效期和清理范围应明确。计算锁减少重复计算，不提供业务副作用恰好一次保证。

## 兼容接口与新入口

原 `interface_cache` 的四个声明及必需参数保持不变，不强制第三方驱动修改。`set($key)` 在内置驱动中继续表示删除；显式 `set($key, false/null/'')` 正常保存。TTL非正数仍删除。`clean()` 在内置驱动中只回收过期/损坏数据，不清空有效值；第三方clean可能是flush，框架不会自动调用它。

新增接口有各自的独立加载文件，旧 `interface_cache.class.php` 仍作为兼容入口加载它们：

| 接口/方法 | 用途 | 内置支持 |
| --- | --- | --- |
| interface_cache_lookup::fetch($key, &$hit) | 一次读取同时返回值和命中状态 | File、MySQL、MSSQL |
| interface_cache_lock::synchronized($key, $operation, $timeout=5) | 同后端计算互斥 | File、MySQL |
| myCache::supports($interface) | 检查目标声明的能力，不探测连接健康 | 所有代理目标 |
| myCache::rememberLocked($key, $producer, $ttl=600, $timeout=5) | 双重检查的防击穿入口 | 要求目标支持lock接口 |

```php
$cache = new myCache('File', [
    'path' => CACHE.'data',
    'namespace' => 'catalog',
    'max_bytes' => 8 * 1024 * 1024,
    'allowed_classes' => false,
], 0.1);

$value = $cache->fetch('empty-result', $hit);
if (!$hit) {
    // 只有未命中时才读取业务源；false和null也可能是有效值。
}

$value = $cache->rememberLocked('tenant:42:product:7', function () {
    return loadProduct(7);
}, 600, 5);
```

myCache显式转发fetch引用参数，避免魔术代理丢失hit输出。旧第三方后端没有lookup声明时，代理只能根据get是否返回false保守推断，无法恢复旧协议原本没有的信息；没有lock声明时rememberLocked/synchronized明确抛出BadMethodCallException。

普通remember保留原行为，不增加锁开销。非正TTL直接计算，不读写缓存；getData_func保留非正TTL只删除该回调键的历史约定。两个入口都会在执行回调前验证参数。函数缓存内部键升为func:v4，使旧资源碰撞产生的条目不再被复用；该部分会冷启动一次。对象回调身份仍只在当前代理及对象生命周期内稳定；跨请求缓存应使用包含全部业务上下文的显式键。


## 主要方法：参数、返回和失败约定

### myCache::init

功能说明：选择并初始化缓存适配器，仅登记一次目录自动加载器

验证新驱动后才替换当前目标；未知类名沿用历史行为回退文件驱动。
自动垃圾回收仅调用 collectGarbage()，不把第三方 clean() 当成回收方法。

```text
public function init($module = '', $setting = null, $gc_rate = 0.1);
```

| 参数说明 |
| --- |
| string\|interface_cache $module 驱动简称、类名或已初始化驱动；空字符串使用 File（默认：''） |
| mixed $setting 驱动构造参数：File 为目录/选项数组，SQL 为连接及编解码选项；对象模式忽略（默认：null） |
| float $gc_rate 本次初始化触发回收的概率，范围 0 到 1；0 禁止，默认 0.1（默认：0.1） |

**返回**：void 无返回值。

**异常**：InvalidArgumentException 驱动接口不符、名称类型非法或回收概率越界；Throwable 驱动构造及回收异常原样传递

### myCache::remember

功能说明：以显式业务键缓存回调结果，使用封装区分空值与未命中


回调约定：producer无参数，返回原始业务值；命中时不调用。键应覆盖用户/语言/租户等影响结果的上下文；失败后重试可能再次计算。
类型提示约定：TResult以同一业务键的已缓存数据与producer返回类型保持一致为前提；外部写入或业务类型改变时应变更键版本/清理该键。

调用示例：
    $value = $cache->remember('public:home:v1', function () { return ['title' => '首页']; }, 60);

```text
public function remember($key, callable $producer, $ttl = 600);
```

| 参数说明 |
| --- |
| string $key 含用户/租户/参数等所有结果上下文的键，内部再加命名空间并散列 |
| callable $producer 未命中时无参数调用；调用方使用闭包捕获所需参数 |
| int\|float $ttl 有效秒数，默认 600；非正数直接计算，不读写或删除现有条目（默认：600） |

**返回**：mixed 缓存值或回调结果，包括 false、null、空数组及空字符串

**异常**：Throwable 回调异常原样传递，不写入异常结果；不提供并发单次执行锁

### myCache::supports

功能说明：检查当前后端是否声明指定接口，不探测远程服务或调用扩展方法。

```text
public function supports($interface);
```

| 参数说明 |
| --- |
| string $interface 接口名，例如interface_cache_lookup或interface_cache_lock |

**返回**：bool 未初始化、非法/未知名称或普通类名为false

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

| 参数说明 |
| --- |
| mixed $key 缓存键 |
| bool\|null &$hit 引用参数；精确命中；旧get返回false时无法判断是否命中（默认：null） |

**返回**：mixed 缓存值或false

## 明确调用示例

```php
$cache = new myCache('File', ['path'=>$directory, 'allowed_classes'=>false], 0);
$cache->set('flag', false, 60);
$value = $cache->fetch('flag', $hit);
assert($hit && $value === false);
$result = $cache->rememberLocked('greeting', function () { return 'Hello'; }, 60);
assert($result === 'Hello');
```

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
