# 字符串与文件认证加密

修订日期：2026-10-24。以下接口面向 PHP 7.4，使用框架现有的 `myEncrypt` 类。

## 职责与运行前提

`myEncrypt` 处理需要恢复原文的数据。字符串接口使用 OpenSSL 的 AES-256-GCM；文件接口使用 Sodium Secretstream 的 XChaCha20-Poly1305，按块校验并发布结果。文件接口需要启用 PHP Sodium 扩展及 Secretstream 能力，不存在时抛出明确异常，不回退到无认证加密。

账号密码使用 `myAuth::hashPassword()` 与 `verifyPassword()`。密码摘要不应使用可逆加密；CSRF 令牌由 `mySecurity` 管理。这三类工具的用途不同。

## 密钥、明文与返回值

| 变量 | 含义与要求 |
| --- | --- |
| `$key` | 非空高熵密钥，建议用 `random_bytes(32)` 生成，并在服务器安全持久保存；不要把密钥与密文一起输出或提交到仓库 |
| `$plaintext` | 字符串原始字节，可以为空、包含中文或二进制；不预先做 HTML 转义 |
| `$envelope` | `seal()` 返回的 `MSENC1:` 字符串封装，包含随机盐、nonce、认证标签和密文 |
| `$file` | 已存在的本地普通文件路径；文件接口原子转换该文件，不接受 URL、流包装器或文件符号链接 |

内部 HKDF 用于不同加密用途的密钥分离，不是低强度用户口令的慢速派生函数。实际业务须先准备自己的密钥管理方式；密钥丢失后无法恢复原文。

## 字符串加解密

```php
$key = random_bytes(32); // 示例临时密钥；跨请求使用时须安全持久保存。
$plaintext = 'MyStep 保密内容';
$envelope = myEncrypt::seal($plaintext, $key);
if ($envelope === false) {
    throw new RuntimeException('加密失败');
}

$opened = myEncrypt::open($envelope, $key);
if ($opened === false) {
    throw new RuntimeException('密钥错误或密文损坏');
}
assert($opened === $plaintext);
```

`seal()` 返回字符串或 `false`；`open()` 返回原始明文字节或 `false`。必须严格比较 `=== false`，空字符串和字符串 `'0'` 都是有效明文。相同明文重复加密会得到不同密文。

`seal()` 的明文类型或密钥无效时抛出 `InvalidArgumentException`。`open()` 对格式、密钥及认证错误返回 `false`，不返回未经认证的部分明文。缺少所需扩展会抛出 `RuntimeException`。

## 文件加解密

```php
// 配置中保存网站根相对路径，操作文件前转为运行时绝对路径。
$file = myPath::absolute('files/private/example.bin');
// 前提：文件已由受保护业务准备好，$key为该业务安全读取的持久密钥。
if (!myEncrypt::sealFile($file, $key)) {
    throw new RuntimeException('加密未完成，原文件保持不变');
}

// 此时同一路径保存MSFILE1加密文件。以后使用相同密钥解密：
if (!myEncrypt::openFile($file, $key)) {
    throw new RuntimeException('认证或发布失败，加密原文件保持不变');
}
// 成功后同一路径重新保存明文。
```

两个接口返回 `bool`，都只在完整成功后替换原文件。处理顺序为：验证本地路径、取得非阻塞排他锁、建立同目录暂存、逐块转换、校验目标状态、保留原访问权限、原子重命名。

暂存初始权限为 `0600`，异常或失败时清理。文件被其他协作进程锁定时直接返回 `false`。原子发布依赖本地文件系统的 `rename` 语义，不承诺抵抗不遵守文件锁的外部写入，也不提供断电后的持久化保证。

文件按 64 KiB 明文块处理，不受“必须将整个文件放入内存”的限制。空文件也有最终认证块。读取失败、错误密钥、数据篡改、缺少最终块、块删除或重排以及末尾追加，都会使解密失败；完整流认证通过前不会发布明文。

## 封装格式与互操作

字符串格式为 `MSENC1:` 加 Base64 数据：16 字节盐、12 字节 nonce、16 字节 GCM 标签、密文。HKDF 上下文为 `MyStepFW seal v1`，GCM 附加认证数据为 `MSENC1:`。

文件格式为二进制 `MSFILE1:` 头、16 字节盐、Sodium 原生 24 字节流头，以及连续认证块。HKDF 上下文为 `MyStepFW file seal v1`；完整文件头作为每块的附加认证数据。非末块对应 65536 字节明文及 17 字节认证开销；末块可更短，必须携带 `TAG_FINAL` 且后面没有任何字节。

文件状态、nonce 和密钥轮换由 [Sodium Secretstream](https://doc.libsodium.org/secret-key_cryptography/secretstream) 管理。字符串与文件格式不能交叉调用解密入口。

## 2.3 入口统一与迁移

本次移除已经能被正式入口完整替代的实现与拼写，不保留别名或旧加密算法回退。

| 已移除或替换的入口 | 当前入口 |
| --- | --- |
| `myEncrypt::encStr` / `decStr` | `myEncrypt::seal` / `open` |
| `myEncrypt::encFile` / `decFile` | `myEncrypt::sealFile` / `openFile` |
| `myEncrypt::keyED` | 已移除，不再直接调用无认证异或变换 |
| `simpleDB::destory` | `simpleDB::destroy` |
| `myTrait` 的 `instatnce` 属性 | `instance` 属性 |
| 图片示例的 `destory` 调用 | `myImg::destroy` |

旧 XOR 密文与新版格式不同。已有外部旧密文须在升级前用旧版本及原密钥解密，再用新版重新封装；新版不猜测或解码旧格式。框架内的旧调用、示例及测试已经统一替换；不会自动扫描、改写未知业务文件。

并非所有带“旧”字样的功能都能直接删除：

- 历史密码摘要无法在没有明文密码时转成新版摘要，因此 `myAuth` 仍保留校验及成功登录后的摘要升级流程。
- `myApi::invoke` 返回原始数据，`run` 返回编码响应并设置类型，两者职责不同。
- SQL 构建、参数绑定、缓存读取的返回语义不同，不能以删除旧入口代替业务迁移。
- `myPath` 的配置路径规范化与 `myFile` 的文件路径、访问边界检查用途不同。

在线样例路径为 `/Document/myEncrypt`，源码位于 `app/Document/module/myEncrypt.php`。完整参数、返回和内部方法说明见 [myEncrypt 方法参考](../reference/myencrypt.md)。
