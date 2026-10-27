# 序列化预算、对象与命名空间

## 本篇目标与调用场景

缓存键、后端、有效期和清理范围应明确。计算锁减少重复计算，不提供业务副作用恰好一次保证。

## 配置、预算和序列化

File可使用原目录字符串，或含path的选项数组；SQL在现有连接配置中附加以下公共项：

| 配置 | 默认 | 约定 |
| --- | --- | --- |
| max_bytes | 8388608（8MiB） | 原始PHP序列化字节数，允许1..64MiB；不是整个PHP进程的内存上限 |
| max_depth | 128 | 结构深度，允许1..4096 |
| allowed_classes | true | 允许保存受信对象；可为false或非空类名数组 |
| namespace | 空字符串 | 非空字符串参与键摘要，避免业务间意外重名；不是权限边界 |
| table（仅SQL） | my_cache | 校验后的简单表名；MSSQL使用dbo schema |

内置get/set/remove的键统一接受有限标量或null，并沿用字符串化规则；数组、对象、资源和NAN/INF键拒绝。未配置namespace时，键采用MD5布局。要隔离清理/维护范围，应使用不同目录或表；同根目录/同表的过期记录可被GC一并回收。

编码预算超限时set返回false并提供安全错误摘要，原条目保留；remember仍返回已计算的业务结果，数据库查询仍能以未缓存结果继续。无效参数、资源值、闭包或自定义序列化回调异常仍按相应异常处理。明确或嵌套的资源不能被当作缓存值；自定义 `__serialize`/`Serializable` 实现也须自行遵守可序列化数据约定。

allowed_classes=false使对象成为PHP的 `__PHP_Incomplete_Class`，不是把对象自动变成普通数组；适用于只需要标量/数组的缓存。默认true用于支持已有受信对象。目录和数据库内容须由可信服务管理，SHA-256是完整性校验，不是带密钥的身份认证。对象唤醒异常视为缓存读取失败，不作为有效命中。

较小预算的读取器遇到其他读取器产生的有效大条目时返回未命中，不删除该有效值；无法在预算内检查元数据的条目会保留。预算涵盖实际序列化字节而非对象展开后的全部内存；业务源输入和自定义对象方法仍需要自己的资源控制。

## 主要方法：参数、返回和失败约定

### myCache::fetch

功能说明：显式转发引用参数，避免魔术代理丢失命中输出；未实现精确读取协议的适配器按get结果推断。

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
| bool\|null &$hit 引用参数；精确命中；仅通过get返回的false无法判断是否命中（默认：null） |

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
