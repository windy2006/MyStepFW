# 缓存清理

## 本篇目标与调用场景

缓存键、后端、有效期和清理范围应明确。计算锁减少重复计算，不提供业务副作用恰好一次保证。

## 全量清理、过期回收及后端引用

- 内置 clean()：只回收过期/损坏数据，保留有效值。
- clear()：显式清空当前存储中的数据。File 保留写锁和计算锁；SQL 保留表结构。相同目录或表的所有 namespace 均受影响。
- myStep::clearDataCache()：后台及更新器使用的共享入口，处理框架默认数据文件与当前缓存后端。第三方后端没有明确 clear 方法时报告不支持，不推断 clean 的含义。
- Redis clear()：SCAN分批清理当前站点、当前namespace的缓存值，保留Redis会话、业务键和活动锁，不执行FLUSHDB/FLUSHALL。
- Memcached clear()：沿用该插件手动 flush 的服务级范围，可能影响共用服务的其他业务。不会在插件初始化时执行。
- 并发计算或新请求可在清空后重新写入；clear 不承诺全局原子清空或业务副作用恰好执行一次。

## 维护入口补充

Admin 的“全部清理”覆盖已登记的七类缓存（包含会话）以及当前数据缓存驱动，保留管理锁和审计目录。数据清理统一通过 myStep::clearDataCache，不能用递归删除数据目录代替驱动 clear。具体页面和确认提示见 [Admin](../../showcase/applications/admin.md)。

## 主要方法：参数、返回和失败约定

### myCache::clear

功能说明：显式清空当前后端全部数据，用于管理员清理；不同于只回收过期项的clean。

同目录/同表的所有namespace均受影响，文件锁基础设施保留；并发请求仍可重建值。
第三方后端须显式提供clear，不把含义不确定的clean当作清空入口。

参数说明：无显式参数。

```text
public function clear();
```

**返回**：bool 存储清空成功为true，后端返回失败为false

**异常**：BadMethodCallException 后端没有公开的clear方法；Throwable 后端异常原样传递

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
