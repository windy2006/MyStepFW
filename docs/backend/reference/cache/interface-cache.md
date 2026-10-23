# interface_cache：interface_cache 缓存驱动／协议

修订日期：2026-10-23。功能详解。

## 职责与调用位置

myCache通过此层处理缓存存储、命中或锁；不要从驱动名称推断它实现全部可选协议。编码预算、有效false/null和失败返回按本页逐项判断。

源码：`lib/cache/interface_cache.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

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

### interface_cache

功能说明：声明set/get/remove/clean四个历史入口，并加载精确命中和计算锁可选接口。

使用说明：
  1. 供内置与第三方缓存驱动实现；应用通常通过myCache代理调用。
  2. get返回false可能表示未命中或有效false；需要精确区分时使用可选fetch协议。
  3. clean沿用驱动语义，内置实现用于过期回收；全量清空使用显式clear入口。

补充说明：
历史缓存协议，保持原四个方法及必需参数签名，兼容第三方适配器。
内置驱动TTL为秒，非正数删除；clean只回收过期数据，不代表flush。
第三方get返回false仍可能表示有效值，精确命中检查采用可选lookup接口。

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `interface_cache::set` | 保存可序列化值；内置驱动省略第二参数时沿用删除约定。 |
| `interface_cache::get` | 读取有效值。 |
| `interface_cache::remove` | 删除单个键。 |
| `interface_cache::clean` | 显式清理；内置驱动只清理过期/损坏条目，第三方含义须查其实现。 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### interface_cache::set

功能说明：保存可序列化值；内置驱动省略第二参数时沿用删除约定。

```text
public function set($key, $value, $ttl);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | string\|int；缓存键，内置驱动也支持其他有限标量及null的字符串化 |
| `$value` | mixed；缓存值，显式false/null/空字符串均有效 |
| `$ttl` | int\|float；有效秒数，正的小数向上取整，非正数删除 |

**返回值**：bool 保存或删除是否成功；无缓存保证时调用者仍须能直接获取业务数据

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/cache/interface_cache.class.php:47`。

### interface_cache::get

功能说明：读取有效值。

```text
public function get($key);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | string\|int；缓存键 |

**返回值**：mixed 不存在/过期/故障为false，旧协议不能区分有效false

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/cache/interface_cache.class.php:54`。

### interface_cache::remove

功能说明：删除单个键。

```text
public function remove($key);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | string\|int；缓存键 |

**返回值**：bool 键原本不存在也视为成功

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/cache/interface_cache.class.php:61`。

### interface_cache::clean

功能说明：显式清理；内置驱动只清理过期/损坏条目，第三方含义须查其实现。

参数说明：无显式参数。

```text
public function clean();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 是否成功；框架不自动把本方法当成GC调用

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/cache/interface_cache.class.php:69`。

