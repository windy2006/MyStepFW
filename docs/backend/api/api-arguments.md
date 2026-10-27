# 接口参数

## 本篇目标与调用场景

HTTP分发仅调用已登记处理器。接口提供启用、允许方法、跨域、仅限后台四项设置；参数按处理器签名的位置传入。

## 6. 保留参数与输出

接口统一按位置参数约定执行：GET按顺序取值，非GET/HEAD再合并POST，同名项由POST覆盖；移除core后追加路径段，包括格式尾参。参数名不会匹配函数形参，GET/POST全量读取沿用HTML转义。不再提供结构化参数模式或逐接口返回格式限制。

```php
namespace app\Hello;
function detail($id = '', $format = 'json') {
    // /api/Hello/detail/json?id=123 依次传入123与json。
    // 业务需要验证输入、身份和资源归属，数据库查询使用参数绑定。
    return ['id' => $id];
}
```

JSON、XML、二进制等原始正文由业务自行通过php://input读取；框架前置正文上限仍生效，业务负责格式解析、深度与解码资源预算。

输出支持json/j、xml/x、string/s、hex/h、code/c/script，JSON固定UTF-8。普通回调return数据；echo、下载和CSRF等直接输出接口保留原行为。未指定格式时使用框架API默认格式。

## 主要方法：参数、返回和失败约定

### myApi::httpFormat

功能说明：归一化支持的输出格式别名，未知值使用指定默认格式。

```text
public static function httpFormat($value, $default = 'json');
```

| 参数说明 |
| --- |
| mixed $value 格式候选 |
| string $default 未知时的回退值（默认：'json'） |

**返回**：string 规范格式或显式的空回退值

### myApi::httpArguments

功能说明：按位置参数约定构造接口参数；各内容类型已先执行正文上限。

```text
public static function httpArguments(array $tail, array $policy, array $config, $legacy_format);
```

| 参数说明 |
| --- |
| array $tail 接口之后的路径段 |
| array $policy 当前接口策略；请求参数按处理器签名的位置传入 |
| array $config 已验证配置 |
| mixed $legacy_format 旧分发器的格式候选 |

**返回**：array [规范格式,位置参数列表]；GET/POST合并后追加路径参数

**异常**：RuntimeException 读取请求参数失败；正文预算由前置httpBodyLimit检查

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
