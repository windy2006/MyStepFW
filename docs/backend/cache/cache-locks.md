# 计算锁、防击穿与锁超时

## 本篇目标与调用场景

缓存键、后端、有效期和清理范围应明确。计算锁减少重复计算，不提供业务副作用恰好一次保证。

## 并发和故障语义

rememberLocked先读取，未命中才加锁，锁内再次检查；生产成功后发布再释放。测试中File和MySQL各8个同时冷启动进程只计算1次。锁超时/后端不可用时不执行生产回调，异常向调用方传递；回调异常不缓存，finally释放锁。

这用于减少重复计算，不承诺副作用恰好执行一次。存储失败、淘汰、过期、进程终止或管理清理之后可能重新计算；生产回调宜为可重复的读取/计算。查询缓存继续依赖已有共享失效后端、读写位和事务规则，不把缓存描述为跨节点强一致存储。

初始化时只自动调用无需必需参数的显式collectGarbage钩子，GC抛异常不替换原目标。自定义后端的clean含义保持其原规则。外部Memcached插件未纳入本次实库/服务验证，仍可按原四方法协议使用。

## 主要方法：参数、返回和失败约定

### myCache::synchronized

功能说明：显式转发计算锁，后端未实现计算锁时抛异常，不静默忽略业务回调。

```text
public function synchronized($key, callable $operation, $timeout = 5);
```

| 参数说明 |
| --- |
| mixed $key 逻辑锁键 |
| callable $operation 无参数回调 |
| int\|float $timeout 等待秒数，0..60（默认：5） |

**返回**：mixed 回调结果

**异常**：BadMethodCallException 后端未声明计算锁接口；Throwable 锁或回调异常继续传递

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

| 参数说明 |
| --- |
| mixed $key 含全部用户/租户及业务参数的逻辑键 |
| callable $producer 无参数计算回调；异常不缓存，锁仍释放 |
| int\|float $ttl 秒数，非正数直接计算，不读取/加锁/写入（默认：600） |
| int\|float $timeout 锁等待秒数，0..60（默认：5） |

**返回**：mixed 缓存值或计算结果；依赖同一后端和正常存储，非恰好一次副作用保证

**异常**：BadMethodCallException 后端未声明interface_cache_lock；Throwable 锁超时、参数或生产回调异常继续向外传递

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
