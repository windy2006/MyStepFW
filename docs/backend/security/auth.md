# 安全认证

## 本篇目标与调用场景

会话与凭据是框架辅助能力，业务仍独立校验身份、数据归属及操作权限。代码预算不替代PHP解析前的补丁与服务器限制。

## 配套完整示例

阅读[密码摘要与认证加密](auth-example.md)：包含准备步骤、完整代码、预期输出及实际应用中的替换位置。先跑通示例，再查本页逐方法参数。

## 主要方法：参数、返回和失败约定

### myAuth::hashPassword

功能说明：为非空明文密码生成带 v2 前缀的密码摘要

先对密码进行 SHA-384 二进制摘要，再将其 Base64 编码为固定 64 字节字符串，
最后交给 password_hash(PASSWORD_DEFAULT) 自动生成盐并计算摘要。
固定长度预处理避免当前 bcrypt 算法只使用密码前 72 字节的限制；返回值应完整
保存，包括 v2: 前缀。校验时必须使用 verifyPassword() 进行相同预处理。

```text
public static function hashPassword($password);
```

| 参数说明 |
| --- |
| string $password 待保存的非空明文密码，不应预先做 HTML 转义或 MD5 |

**返回**：string 包含 v2: 前缀、算法及盐信息的密码摘要

**异常**：InvalidArgumentException 密码不是字符串或为空字符串

### myAuth::protectLegacyHash

功能说明：在没有明文密码时，为32位十六进制 摘要增加 password_hash() 包装

仅处理恰好 32 位的十六进制字符串，将其转为小写后用 PASSWORD_DEFAULT
再次散列，并添加 v1: 前缀；已有版本摘要、其他字符串及非字符串输入原样返回。
由调用方将返回摘要保存到账户存储。

```text
public static function protectLegacyHash($hash);
```

| 参数说明 |
| --- |
| mixed $hash 待包装的密码摘要或需要保持原样的其他值 |

**返回**：mixed 包装后的 v1 摘要，或不符合裸 MD5 格式时的原始输入

### myAuth::verifyPassword

功能说明：根据已保存摘要的格式，支持校验用户提交的明文密码

v2: 使用 SHA-384/Base64 预处理后执行 password_verify()；
v1: 对密码计算 MD5，再与已包装的摘要比较；裸 MD5 使用 hash_equals() 比较；
其他非空字符串按无版本前缀的 password_hash() 摘要执行原生校验。
非字符串或空输入返回 false。本方法不会自动升级摘要或写入登录状态。

```text
public static function verifyPassword($password, $hash);
```

| 参数说明 |
| --- |
| mixed $password 用户提交的原始明文密码 |
| mixed $hash 账户中完整保存的密码摘要，包含可能存在的版本前缀 |

**返回**：bool 密码是否通过对应格式的校验

### myAuth::needsUpgrade

功能说明：判断现有密码摘要是否需要升级格式或重新散列

非 v2: 前缀一律视为需要升级；v2 摘要去掉前缀后，通过
password_needs_rehash() 检查是否符合当前 PASSWORD_DEFAULT 的算法和默认参数。
本方法只判断存储格式，不校验密码；应先验证登录密码成功，再调用本方法，
需要升级时用该明文密码生成新摘要并由业务保存。

```text
public static function needsUpgrade($hash);
```

| 参数说明 |
| --- |
| string $hash 已读取并确认类型的完整密码摘要 |

**返回**：bool 是否需要生成并保存新的密码摘要

### myAuth::encode

功能说明：将二进制或文本数据编码为不带填充的 Base64URL 字符串

在 Base64 结果中将 +、/ 替换为 -、_，并移除末尾 =，用于登录令牌的载荷段。
仅供类内部使用；编码可逆，不提供加密、签名或内容可信性保证。

```text
protected static function encode($data);
```

| 参数说明 |
| --- |
| string $data 待编码的原始字节串 |

**返回**：string 不带 = 填充的 Base64URL 编码结果

### myAuth::readToken

功能说明：解析登录令牌的结构和载荷，并检查是否已过期

仅接受不超过 2048 字节的“Base64URL 载荷.64 位小写十六进制签名”字符串。
解码 JSON 后要求 v 严格为整数 2、u/s 为字符串、e 为尚未到期的整数时间戳。
成功时返回完整载荷数组，其中 u 为用户名、s 为用途、e 为过期时间，
makeToken() 还会生成随机 n 字段；本方法不要求或校验 n，也不剔除额外字段。
此处不验证签名、预期用户名、用途或最长有效期，不能凭解析成功建立登录状态，
必须继续使用 verifyToken() 完成认证。

载荷结构：v版本号2、u用户名、s用途范围、e秒级Unix到期时间；可能含n等附加键。此入口只解析结构和时效，必须经verifyToken校验HMAC后才能作为登录凭据。

```text
public static function readToken($token);
```

| 参数说明 |
| --- |
| mixed $token 来自 Cookie 等输入渠道的完整登录令牌 |

**返回**：array|false 通过结构及过期检查的载荷数组；输入非法或已过期时返回 false

### myAuth::makeToken

功能说明：生成带有效期、用户和用途绑定的记住登录令牌

将有效时长转换为整数并限制在 1～31536000 秒，载荷包含版本 v=2、用户名 u、
用途 s、Unix 过期时间 e 及由 16 字节安全随机数生成的十六进制 n。
JSON 载荷经 Base64URL 编码后，以 SHA-256(secret + 空字节 + 密码摘要)
的二进制结果作为 HMAC-SHA256 密钥签名，返回“载荷.十六进制签名”。
密码摘要必须与账户当前持久化值完全一致；更改摘要或站点密钥会使此前签发的令牌失效。
参数内容应由调用方验证，用户名及用途应为可进行 JSON 编码的 UTF-8 文本；
本方法不设置 Cookie，也不保存随机值或单个令牌的撤销记录。

```text
public static function makeToken($username, $hash, $scope, $secret, $ttl);
```

| 参数说明 |
| --- |
| string $username 需要绑定的账户用户名 |
| string $hash 账户当前完整密码摘要，包含版本前缀 |
| string $scope 登录用途或应用标识，校验时必须完全一致 |
| string $secret 由服务器保管的高熵站点密钥，不应提供给客户端 |
| int $ttl 请求的有效时长，单位为秒，超出范围时自动截取到上述上下限 |

**返回**：string 可交由调用方保存到 Cookie 的完整登录令牌

**异常**：Exception 无法取得安全随机数时由 random_bytes() 抛出

### myAuth::verifyToken

功能说明：校验记住登录令牌的结构、时效、预期身份、用途和 HMAC 签名

先调用 readToken() 拒绝非法或过期令牌，再严格比较用户名和用途，
并拒绝过期时间超过当前服务器时间一年以上的载荷。以调用方提供的当前账户摘要
和站点密钥重新计算签名，使用 hash_equals() 比较，全部通过后返回 true。
不查询账户、不校验明文密码，也不恢复 Session；调用方须先取得真实账户记录，
校验成功后再设置登录状态，并按业务要求检查账户状态、角色及资源权限。

调用示例：
    $ok = myAuth::verifyToken($token, $username, $passwordHash, 'admin', $secret);

```text
public static function verifyToken($token, $username, $hash, $scope, $secret);
```

| 参数说明 |
| --- |
| mixed $token 待验证的完整登录令牌 |
| string $username 预期用户名，必须与载荷中的 u 严格一致 |
| string $hash 账户当前持久化的完整密码摘要 |
| string $scope 预期登录用途，必须与载荷中的 s 严格一致 |
| string $secret 当前服务器保管的站点密钥 |

**返回**：bool 令牌是否通过全部校验

### myAuth::renewSession

功能说明：为已经启动的会话重新生成 Session ID，降低会话固定攻击风险

仅在 session_status() 为 PHP_SESSION_ACTIVE 时调用
session_regenerate_id(true)，保留当前会话数据并请求删除轮换前的ID 对应的存储。
通常在登录成功或身份变更后、响应头发送前调用；未启动会话时不执行操作。
本方法不启动会话、不设置身份，也不返回底层 ID 更新是否成功的结果。

参数说明：无显式参数。

```text
public static function renewSession();
```

**返回**：void 无返回值。

## 明确调用示例

```php
$hash = myAuth::hashPassword('example-password');
assert(myAuth::verifyPassword('example-password', $hash));
assert(!myAuth::verifyPassword('wrong', $hash));
$token = myAuth::makeToken('editor', $hash, 'Hello', 'server-secret-for-test', 300);
assert(myAuth::verifyToken($token, 'editor', $hash, 'Hello', 'server-secret-for-test'));
```

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
