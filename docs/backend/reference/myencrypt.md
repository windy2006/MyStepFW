# myEncrypt：字符串与文件认证加密

## 职责与调用位置

字符串使用seal/open，文件使用sealFile/openFile。两者都校验完整性；错误密钥和损坏文件不发布明文。密码摘要由myAuth管理。

源码：`lib/myEncrypt.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
$key = random_bytes(32); // 安全保存，不与密文一同公开。
$sealed = myEncrypt::seal('保密内容', $key);
$plain = myEncrypt::open($sealed, $key);
if ($plain === false) throw new RuntimeException('字符串认证失败');

$file = $directory.'/private.bin'; // 已准备好的独占测试文件。
if (!myEncrypt::sealFile($file, $key)) throw new RuntimeException('文件加密失败');
if (!myEncrypt::openFile($file, $key)) throw new RuntimeException('文件认证失败');
```

## 声明与类职责

### myEncrypt

功能说明：为字符串及任意大小本地文件提供认证加密，不回退到无认证算法。

字符串MSENC1封装与文件MSFILE1封装用途不同，不能交叉解密。
文件发布使用同目录rename及协作排他锁；不承诺进程崩溃后的持久化或阻止不遵守文件锁的外部写入。

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myEncrypt::seal` | 使用随机盐、随机nonce及AES-256-GCM生成字符串认证封装。 |
| `myEncrypt::open` | 校验并解密MSENC1字符串封装，不接受未知或异或格式。 |
| `myEncrypt::sealFile` | 使用Secretstream逐块加密文件，成功后原子替换原文件。 |
| `myEncrypt::openFile` | 逐块认证MSFILE1文件，确认FINAL及文件尾后原子发布明文。 |
| `myEncrypt::transformFile` | 持有非阻塞排他锁，将结果写入同目录暂存文件后校验目标并发布。 |
| `myEncrypt::sealStream` | 生成文件头，并用一块前瞻确定最后一个Secretstream认证块。 |
| `myEncrypt::openStream` | 按固定最大块读取并认证，要求最后一个块携带FINAL且不存在附加字节。 |
| `myEncrypt::readChunk` | 在固定预算内循环读取，直到取得要求字节数或正常到达文件尾。 |
| `myEncrypt::writeAll` | 循环写入剩余字节，拒绝短写后的零进度及底层失败。 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myEncrypt::seal

功能说明：使用随机盐、随机nonce及AES-256-GCM生成字符串认证封装。

通过HKDF-SHA256分离32字节密钥，使用12字节nonce及16字节认证标签。
同一明文和密钥重复加密会生成不同封装；明文可以为空或包含任意二进制字节。

```php
public static function seal($plaintext, $key);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$plaintext` | string；待加密的原始字节串 |
| `$key` | string；非空高熵密钥，建议random_bytes(32)生成并安全持久保存 |

**返回值**：string|false MSENC1开头的Base64认证封装；OpenSSL加密失败为false

**异常与失败**：InvalidArgumentException 明文不是字符串或密钥不是非空字符串；RuntimeException 当前PHP没有OpenSSL加密能力；Exception 无法生成安全随机数

调用形式：

```php
$result = myEncrypt::seal($plaintext, $key);
```

定位：`lib/myEncrypt.class.php:66`。

### myEncrypt::open

功能说明：校验并解密MSENC1字符串封装，不接受未知或异或格式。

```php
public static function open($envelope, $key);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$envelope` | string；seal生成的完整封装 |
| `$key` | string；加密时使用的同一高熵密钥 |

**返回值**：string|false 原始明文字节串，可能为空；格式、密钥或认证不正确时为false

**异常与失败**：RuntimeException 当前PHP没有OpenSSL解密能力

调用形式：

```php
$result = myEncrypt::open($envelope, $key);
```

定位：`lib/myEncrypt.class.php:85`。

### myEncrypt::sealFile

功能说明：使用Secretstream逐块加密文件，成功后原子替换原文件。

新文件格式MSFILE1使用随机盐、HKDF密钥分离和Secretstream生成的随机流头。
最后一个块带FINAL标记，空文件也包含最终认证块；不将整个文件载入内存。

```php
public static function sealFile($file, $key);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；已存在的本地普通文件路径，可为网站根解析后的绝对路径 |
| `$key` | string；非空高熵密钥，解密时须使用同一密钥 |

**返回值**：bool 转换成功为true；路径、文件锁、读写或发布失败为false且不替换原文件

**异常与失败**：InvalidArgumentException 密钥不是非空字符串；RuntimeException 当前PHP没有Sodium Secretstream能力；Exception 无法生成安全随机数或底层加密发生异常

调用形式：

```php
$result = myEncrypt::sealFile($file, $key);
```

定位：`lib/myEncrypt.class.php:106`。

### myEncrypt::openFile

功能说明：逐块认证MSFILE1文件，确认FINAL及文件尾后原子发布明文。

错误密钥、损坏、截断、块重排及尾部追加均不会发布暂存明文；不回退读取旧文件格式。

```php
public static function openFile($file, $key);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；sealFile生成的本地加密文件路径 |
| `$key` | string；加密时使用的同一高熵密钥 |

**返回值**：bool 全部认证并发布成功为true；格式、认证、文件锁或文件操作失败为false

**异常与失败**：InvalidArgumentException 密钥不是非空字符串；RuntimeException 当前PHP没有Sodium Secretstream能力；Exception 底层加密发生异常；原文件不被替换

调用形式：

```php
$result = myEncrypt::openFile($file, $key);
```

定位：`lib/myEncrypt.class.php:121`。

### myEncrypt::transformFile

功能说明：持有非阻塞排他锁，将结果写入同目录暂存文件后校验目标并发布。

暂存初始权限0600，完整成功后才恢复原文件权限；身份、大小及时间状态变化时拒绝覆盖。
不接受URL、流包装器及文件符号链接；finally负责释放资源及删除未发布暂存。

```php
private static function transformFile($file, $key, $decrypt);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；本地普通文件路径 |
| `$key` | string；非空高熵密钥 |
| `$decrypt` | bool；true解密，false加密 |

**返回值**：bool 完整转换并原子发布是否成功

**异常与失败**：InvalidArgumentException 密钥无效；RuntimeException 缺少Sodium扩展；Exception 随机数或加密异常继续传递，暂存仍会清理

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myEncrypt.class.php:138`。

### myEncrypt::sealStream

功能说明：生成文件头，并用一块前瞻确定最后一个Secretstream认证块。

```php
private static function sealStream($input, $output, $key);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$input` | resource；已锁定的本地输入流 |
| `$output` | resource；权限受限的暂存输出流 |
| `$key` | string；调用方密钥 |

**返回值**：bool 完整写入文件头和全部认证块是否成功

**异常与失败**：Exception 随机数或Sodium异常继续传递

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myEncrypt.class.php:185`。

### myEncrypt::openStream

功能说明：按固定最大块读取并认证，要求最后一个块携带FINAL且不存在附加字节。

```php
private static function openStream($input, $output, $key);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$input` | resource；已锁定的本地输入流 |
| `$output` | resource；尚未发布的明文暂存流 |
| `$key` | string；调用方密钥 |

**返回值**：bool 格式、所有块及最终结束条件均有效时为true

**异常与失败**：Exception Sodium初始化或解密异常继续传递

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myEncrypt.class.php:219`。

### myEncrypt::readChunk

功能说明：在固定预算内循环读取，直到取得要求字节数或正常到达文件尾。

```php
private static function readChunk($stream, $length);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$stream` | resource；本地可读文件流 |
| `$length` | int；本次最多读取的字节数 |

**返回值**：string|false 读取到的字节，正常文件尾可能为空；错误或零进度为false

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myEncrypt.class.php:251`。

### myEncrypt::writeAll

功能说明：循环写入剩余字节，拒绝短写后的零进度及底层失败。

```php
private static function writeAll($stream, $data);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$stream` | resource；本地可写暂存文件流 |
| `$data` | string；本次完整待写字节串，允许为空 |

**返回值**：bool 所有字节写入成功为true，否则false

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myEncrypt.class.php:267`。
