# 正文限制

## 本篇目标与调用场景

HTTP分发仅调用已登记处理器。接口提供启用、允许方法、跨域、仅限后台四项设置；参数按处理器签名的位置传入。

## 4. 统一正文上限

正文检查在API应用/会话启动之前执行，覆盖JSON、XML、纯文本、二进制、URL编码表单、multipart上传，也覆盖支持模式中自行读取正文的接口。此处限制请求输入，返回XML/JSON等响应格式不影响额度。

- 有Content-Length时按十进制字符串检查，避免整数溢出，超限返回413，非法长度返回400。
- 没有声明长度的非multipart请求，按固定小块读取`php://input`并累计，最多核验额度加一个字节；业务仍可重新读取PHP缓存的原始输入流。
- multipart已可能在PHP启动时完成解析，原始流不一定可读取。没有可用声明长度时返回411；不能将最终POST/FILES数组当作完整multipart体积。

上传额度包括表单及multipart封装开销，并不只是文件大小。API上限与PHP的post_max_size、upload_max_filesize、框架入口预算及Web服务器限制共同生效，更小的限制优先。压缩正文的业务解码、图片解码及归档解包仍需各自资源预算。

PHP可能早于网站代码解析请求，以上不是内核漏洞补丁；解析前限制和运行时补丁见[PHP运行时加固](../security/input-budget.md)。

## 主要方法：参数、返回和失败约定

### myApi::httpError

功能说明：输出固定JSON错误并结束HTTP请求，不暴露内部配置或异常。

```text
public static function httpError($status, $code, $message);
```

| 参数说明 |
| --- |
| int $status HTTP错误状态 |
| string $code 固定错误标识 |
| string $message 固定说明 |

**返回**：void HEAD无正文；其他方法输出后结束

### myApi::httpBodyLimit

功能说明：对所有模式和内容类型执行统一请求正文上限，不改变原输入流和上传元数据。

```text
public static function httpBodyLimit(array $config);
```

| 参数说明 |
| --- |
| array $config 已验证的API配置 |

**返回**：void 合法请求继续；超限413、声明无效400、无长度multipart为411

**异常**：RuntimeException 无法读取请求正文

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
