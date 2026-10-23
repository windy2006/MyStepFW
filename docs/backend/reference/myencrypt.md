# myEncrypt：加密与兼容数据

修订日期：2026-10-23。功能详解。

## 职责与调用位置

新数据使用seal/open认证加密；encStr/decStr等只兼容旧格式，不作为新密码存储。

源码：`lib/myEncrypt.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
$key = bin2hex(random_bytes(32));
$sealed = myEncrypt::seal('保密内容', $key);
$plain = myEncrypt::open($sealed, $key);
assert($plain === '保密内容');
```

## 声明与类职责

### myEncrypt

功能说明：提供历史异或兼容接口，以及带认证的现代封装与文件处理。

使用说明：
  1. 新数据优先使用seal/open认证封装；旧encStr/decStr等仅用于既有格式兼容。
  2. 文件入口在临时文件中转换后发布，失败不覆盖原文件。
  3. 认证密钥由调用方安全管理；不能把可逆加密当成密码哈希。

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myEncrypt::keyED` | 按密钥摘要执行历史异或变换，供旧格式加解密复用 |
| `myEncrypt::encStr` | 将字符串编码为历史可逆加密格式，兼容既有数据 |
| `myEncrypt::encFile` | 在临时文件中按历史格式加密本地文件并原子替换 |
| `myEncrypt::decStr` | 解码历史加密字符串，不能据此判断数据完整性 |
| `myEncrypt::decFile` | 在临时文件中解密历史文件格式并原子替换 |
| `myEncrypt::transformFile` | 执行历史文件格式转换，失败时保留原文件 |
| `myEncrypt::seal` | 使用AES-256-GCM生成带版本及认证信息的加密封装 |
| `myEncrypt::open` | 校验并解密MSENC1封装，认证失败返回false |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myEncrypt::keyED

功能说明：按密钥摘要执行历史异或变换，供旧格式加解密复用

```text
public static function keyED($str, $encrypt_key);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$str` | string；待处理的字符串 |
| `$encrypt_key` | string；用于历史异或变换的密钥 |

**返回值**：string 历史异或变换后的原始字节串；相同密钥可执行逆变换

**相关入口**：myEncrypt::seal() 新数据的认证加密封装

调用形式：

```php
$result = myEncrypt::keyED($str, $encrypt_key);
```

定位：`lib/myEncrypt.class.php:60`。

### myEncrypt::encStr

功能说明：将字符串编码为历史可逆加密格式，兼容既有数据

```text
public static function encStr($str, $key);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$str` | string；待处理的字符串 |
| `$key` | string；加解密密钥 |

**返回值**：string 历史加密格式文本，用于已有数据兼容，不提供认证完整性

**相关入口**：myEncrypt::seal() 新数据的认证加密封装

调用形式：

```php
$result = myEncrypt::encStr($str, $key);
```

定位：`lib/myEncrypt.class.php:80`。

### myEncrypt::encFile

功能说明：在临时文件中按历史格式加密本地文件并原子替换

```text
public static function encFile($file, $key);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；目标文件路径 |
| `$key` | string；加解密密钥 |

**返回值**：bool 转换并替换原文件成功为true，失败为false

**相关入口**：myEncrypt::seal() 新数据的认证加密封装

调用形式：

```php
$result = myEncrypt::encFile($file, $key);
```

定位：`lib/myEncrypt.class.php:100`。

### myEncrypt::decStr

功能说明：解码历史加密字符串，不能据此判断数据完整性

```text
public static function decStr($str, $key);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$str` | string；待处理的字符串 |
| `$key` | string；加解密密钥 |

**返回值**：string 从历史加密格式解出的明文字节串

**相关入口**：myEncrypt::open() 新封装的认证解密

调用形式：

```php
$result = myEncrypt::decStr($str, $key);
```

定位：`lib/myEncrypt.class.php:112`。

### myEncrypt::decFile

功能说明：在临时文件中解密历史文件格式并原子替换

```text
public static function decFile($file, $key);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；目标文件路径 |
| `$key` | string；加解密密钥 |

**返回值**：bool 转换并替换原文件成功为true，失败为false

**相关入口**：myEncrypt::open() 新封装的认证解密

调用形式：

```php
$result = myEncrypt::decFile($file, $key);
```

定位：`lib/myEncrypt.class.php:132`。

### myEncrypt::transformFile

功能说明：执行历史文件格式转换，失败时保留原文件

```text
private static function transformFile($file, $key, $decrypt);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；目标文件路径 |
| `$key` | string；加解密密钥 |
| `$decrypt` | bool；true执行文件解密，false执行文件加密 |

**返回值**：bool 完整转换并原子发布成功为true，失败为false

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myEncrypt.class.php:145`。

### myEncrypt::seal

功能说明：使用AES-256-GCM生成带版本及认证信息的加密封装


调用示例：
    $envelope = myEncrypt::seal('示例文本', $secret);
    if ($envelope !== false) { $plaintext = myEncrypt::open($envelope, $secret); }

```text
public static function seal($plaintext, $key);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$plaintext` | string；待加密的明文字节串 |
| `$key` | string；加解密密钥 |

**返回值**：string|false MSENC1开头的认证封装；加密失败为false

**异常与失败**：InvalidArgumentException 密钥不是非空字符串

调用形式：

```php
$result = myEncrypt::seal($plaintext, $key);
```

定位：`lib/myEncrypt.class.php:186`。

### myEncrypt::open

功能说明：校验并解密MSENC1封装，认证失败返回false

```text
public static function open($envelope, $key);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$envelope` | string；带版本、随机量与认证信息的加密封装 |
| `$key` | string；加解密密钥 |

**返回值**：string|false 解密后的原始明文字节；结构错误或认证失败为false

调用形式：

```php
$result = myEncrypt::open($envelope, $key);
```

定位：`lib/myEncrypt.class.php:202`。

