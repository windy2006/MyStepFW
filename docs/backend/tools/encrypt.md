# 认证加密与旧格式兼容

修订日期：2026-10-23。适用于当前框架。

## 本篇目标与调用场景

新数据使用seal/open认证加密；encStr/decStr等只兼容旧格式，不作为新密码存储。

## 操作步骤与注意事项

先准备示例变量和目标资源，再执行方法。明确区分返回内容、直接输出、落盘或网络传输；后端PHP代码不要作为JavaScript放进浏览器脚本。完整方法参数见对应公共类参考页。

## 主要方法：参数、返回和失败约定

### myEncrypt::keyED

功能说明：按密钥摘要执行历史异或变换，供旧格式加解密复用

```text
public static function keyED($str, $encrypt_key);
```

| 参数说明 |
| --- |
| string $str 待处理的字符串 |
| string $encrypt_key 用于历史异或变换的密钥 |

**返回**：string 历史异或变换后的原始字节串；相同密钥可执行逆变换

### myEncrypt::encStr

功能说明：将字符串编码为历史可逆加密格式，兼容既有数据

```text
public static function encStr($str, $key);
```

| 参数说明 |
| --- |
| string $str 待处理的字符串 |
| string $key 加解密密钥 |

**返回**：string 历史加密格式文本，用于已有数据兼容，不提供认证完整性

### myEncrypt::encFile

功能说明：在临时文件中按历史格式加密本地文件并原子替换

```text
public static function encFile($file, $key);
```

| 参数说明 |
| --- |
| string $file 目标文件路径 |
| string $key 加解密密钥 |

**返回**：bool 转换并替换原文件成功为true，失败为false

### myEncrypt::decStr

功能说明：解码历史加密字符串，不能据此判断数据完整性

```text
public static function decStr($str, $key);
```

| 参数说明 |
| --- |
| string $str 待处理的字符串 |
| string $key 加解密密钥 |

**返回**：string 从历史加密格式解出的明文字节串

### myEncrypt::decFile

功能说明：在临时文件中解密历史文件格式并原子替换

```text
public static function decFile($file, $key);
```

| 参数说明 |
| --- |
| string $file 目标文件路径 |
| string $key 加解密密钥 |

**返回**：bool 转换并替换原文件成功为true，失败为false

### myEncrypt::seal

功能说明：使用AES-256-GCM生成带版本及认证信息的加密封装


调用示例：
    $envelope = myEncrypt::seal('示例文本', $secret);
    if ($envelope !== false) { $plaintext = myEncrypt::open($envelope, $secret); }

```text
public static function seal($plaintext, $key);
```

| 参数说明 |
| --- |
| string $plaintext 待加密的明文字节串 |
| string $key 加解密密钥 |

**返回**：string|false MSENC1开头的认证封装；加密失败为false

**异常**：InvalidArgumentException 密钥不是非空字符串

## 明确调用示例

```php
$key = bin2hex(random_bytes(32));
$sealed = myEncrypt::seal('保密内容', $key);
$plain = myEncrypt::open($sealed, $key);
assert($plain === '保密内容');
```

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
