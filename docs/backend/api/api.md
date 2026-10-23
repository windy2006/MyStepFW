# HTTP API登记与对象接口

修订日期：2026-10-23。适用于当前框架。

## 本篇目标与调用场景

HTTP分发仅调用已登记处理器。当前接口只有启用、方法、跨域、仅限后台四项额外设置；参数统一沿用兼容位置模式。

## 1. 注册与调用

应用在自身`route.php`中声明：

```php
$api = array('detail' => 'app\Hello\detail');
```

普通入口为`/api/Hello/detail/json`，同时兼容重写、`index.php/api/...`与`index.php?api/...`。插件使用`plugin_插件名`作为调用范围。框架只调用登记处理器，不开放任意PHP函数或文件；后台也不能修改回调或新增任意目标。

接口声明按内容指纹编译到`config/route.php`。插件停用后接口不再登记；覆盖策略作为历史记录保留，不能借后台重新启用插件。


## 主要方法：参数、返回和失败约定

### myApi::regMethod

功能说明：登记或覆盖单个接口，失败时保留原有登记

```text
public function regMethod($method, $func);
```

| 参数说明 |
| --- |
| string\|int $method 非空接口标识 |
| callable $func 函数、静态/对象方法、闭包或可调用对象 |

**返回**：$this 当前 API 对象，可连续登记

**异常**：InvalidArgumentException 名称非法或回调不可调用

### myApi::regMethods

功能说明：先验证整批回调，再一次性合并，避免错误批次只登记了一部分

```text
public function regMethods($method_list);
```

| 参数说明 |
| --- |
| array\|Traversable $method_list 接口名称到回调的映射 |

**返回**：$this 当前 API 对象；同名接口覆盖旧回调

**异常**：InvalidArgumentException 列表类型、名称或任一回调非法

### myApi::invoke

功能说明：执行已登记接口并返回原始结果，不编码、不修改响应头


调用示例：
    $api->regMethod('sum', function ($a, $b) { return $a + $b; });
    $sum = $api->invoke('sum', [2, 3]); // 原始整数结果5

```text
public function invoke($method, array $para = []);
```

| 参数说明 |
| --- |
| string\|int $method 明确登记的接口名 |
| array $para 按遍历顺序作为位置参数传递，关联键不作为参数名（默认：[]） |

**返回**：mixed 回调结果，保留 false/null/0/空数组等类型

**异常**：BadMethodCallException 接口未登记，不自动寻找同名全局函数；Throwable 业务回调异常继续传递

### myApi::run

功能说明：执行接口并返回编码响应，仅在编码成功且头未发送时设置内容类型

JSON 不猜测或改写对象编码，避免修改回调返回对象；非法 UTF-8/循环会明确报错。
XML 字符串需要有效 UTF-8，非法 XML 控制字符移除；charset 仅控制文本类响应
的输出标签及 XML 转码，不用于把任意二进制猜测为 Unicode。

```text
public function run($method, $para=[], $return='json', $charset='utf-8');
```

| 参数说明 |
| --- |
| string\|int $method 已登记接口；未知接口沿用编码空字符串的旧行为 |
| array $para 位置参数数组，关联键按遍历顺序传递（默认：[]） |
| string $return json/j、xml/x、string/s、hex/h、code/c/script；未知值回退 JSON（默认：'json'） |
| string $charset 文本输出字符集，空值使用 UTF-8；JSON 恒为 UTF-8（默认：'utf-8'） |

**返回**：string 编码后的内容，不 echo、不结束请求

**异常**：InvalidArgumentException 参数/字符集标签非法或 XML 文本非法；JsonException JSON 编码失败，不静默返回 false；RuntimeException XML 超限/循环、字符转码失败或编码不可用；Throwable 业务回调异常继续传递

## 明确调用示例

```php
$api = new myApi();
$api->regMethod('sum', function ($a, $b) { return $a + $b; });
assert($api->invoke('sum', [2, 3]) === 5);
assert(json_decode($api->run('sum', [2, 3]), true) === 5);
```

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
