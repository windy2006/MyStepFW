# interface_cache_lock：interface_cache_lock 缓存驱动／协议

修订日期：2026-10-23。功能详解。

## 职责与调用位置

myCache通过此层处理缓存存储、命中或锁；不要从驱动名称推断它实现全部可选协议。编码预算、有效false/null和失败返回按本页逐项判断。

源码：`lib/cache/interface_cache_lock.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

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

### interface_cache_lock

功能说明：为缓存驱动提供可选的跨进程计算互斥契约。

不扩展基础缓存接口；调用方应先检查能力再使用。
锁范围和重入规则由具体后端决定，不能替代会话锁或业务事务。

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `interface_cache_lock::synchronized` | 在同一后端的逻辑键锁内执行回调，finally释放；锁失败时不调用回调。 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### interface_cache_lock::synchronized

功能说明：在同一后端的逻辑键锁内执行回调，finally释放；锁失败时不调用回调。

```text
public function synchronized($key, callable $operation, $timeout = 5);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | string\|int；锁键，不能与会话/业务事务锁互相替代 |
| `$operation` | callable；无参数计算回调 |
| `$timeout` | int\|float；最长等待秒数，0为非阻塞，范围0到60（默认：5） |

**返回值**：mixed 回调原始结果

**异常与失败**：RuntimeException 后端不可用或等待超时；LogicException 不允许重入的锁被同一适配器递归请求；Throwable 回调异常原样传递，锁仍被释放

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/cache/interface_cache_lock.class.php:54`。
