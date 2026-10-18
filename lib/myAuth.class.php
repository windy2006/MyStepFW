<?PHP
/*******************************************
*                                          *
* Name    : Authentication Tools (new)     *
* Modifier: MyStep Framework               *
* Time    : 2026-10-15                     *
* Email   : windy2006@gmail.com            *
* HomePage: www.mysteps.cn                 *
* Notice  : U Can Use & Modify it freely,  *
*           BUT PLEASE HOLD THIS ITEM.     *
*                                          *
*******************************************/

/**
 * 文件：lib/myAuth.class.php
 * 功能说明：密码与签名登录凭据工具
 *   提供密码哈希、历史摘要校验、会话轮换及带用途和有效期的签名登录令牌。
 *
 * 方法目录：
 *   public static myAuth::hashPassword($password) — 为非空明文密码生成带 v2 前缀的新格式摘要
 *   public static myAuth::protectLegacyHash($hash) — 在没有明文密码时，为历史裸 MD5 摘要增加 password_hash() 包装
 *   public static myAuth::verifyPassword($password, $hash) — 根据已保存摘要的格式，兼容校验用户提交的明文密码
 *   public static myAuth::needsUpgrade($hash) — 判断现有密码摘要是否需要升级格式或重新散列
 *   protected static myAuth::encode($data) — 将二进制或文本数据编码为不带填充的 Base64URL 字符串
 *   public static myAuth::readToken($token) — 解析登录令牌的结构和载荷，并检查是否已过期
 *   public static myAuth::makeToken($username, $hash, $scope, $secret, $ttl) — 生成带有效期、用户和用途绑定的记住登录令牌
 *   public static myAuth::verifyToken($token, $username, $hash, $scope, $secret) — 校验记住登录令牌的结构、时效、预期身份、用途和 HMAC 签名
 *   public static myAuth::renewSession() — 为已经启动的会话重新生成 Session ID，降低会话固定攻击风险
 *
 * 使用说明：
 *   1. 新密码使用 hashPassword()；旧密码验证成功后通过 needsUpgrade() 判断是否迁移。
 *   2. 登录令牌先解析再执行 verifyToken()；读取载荷不代表认证成功。
 *   3. 账号状态、业务权限、凭据保存及 Cookie 属性由调用方管理。
 *
 * 说明：目录列出本文件直接声明的全部方法；参数、返回值及异常详见各方法 PHPDoc。
 *       原生签名保持 PHP 7.4 兼容，PHPDoc 类型仅用于说明。
 */


/**
 * 功能说明：封装密码摘要兼容校验及签名登录凭据处理。
 *
 * 使用说明：
 *   1. 校验成功后再决定是否生成并持久化新摘要；protectLegacyHash() 不能替代 v2 升级。
 *   2. 兼容 v2、v1、裸 MD5 及无版本前缀的 password_hash() 摘要。
 *   3. 登录令牌与 CSRF 令牌用途不同；载荷仅编码而不加密，不应包含秘密数据。
 *   4. 签名绑定当前密码摘要、站点密钥及用途；摘要或密钥改变后旧令牌失效。
 *   5. 有效期内令牌可重复使用；单令牌撤销和重放控制由应用实现。
 *   6. 本类不读写账号数据库或登录 Cookie；renewSession() 仅处理已启动的会话。
 *
 * 方法目录：参见本文件顶部。
 */
class myAuth {
    /**
     * 功能说明：为非空明文密码生成带 v2 前缀的新格式摘要
     *
     * 先对密码进行 SHA-384 二进制摘要，再将其 Base64 编码为固定 64 字节字符串，
     * 最后交给 password_hash(PASSWORD_DEFAULT) 自动生成盐并计算摘要。
     * 固定长度预处理避免当前 bcrypt 算法只使用密码前 72 字节的限制；返回值应完整
     * 保存，包括 v2: 前缀。校验时必须使用 verifyPassword() 进行相同预处理。
     *
     * @param string $password 待保存的非空明文密码，不应预先做 HTML 转义或 MD5
     * @return string 包含 v2: 前缀、算法及盐信息的密码摘要
     * @throws InvalidArgumentException 密码不是字符串或为空字符串
     */
    public static function hashPassword($password) {
        if(!is_string($password) || $password === '') throw new InvalidArgumentException('Password must not be empty');
        // 固定长度预摘要可避免 bcrypt 对超过 72 字节的原始密码进行截断。
        return 'v2:'.password_hash(base64_encode(hash('sha384', $password, true)), PASSWORD_DEFAULT);
    }

    /**
     * 功能说明：在没有明文密码时，为历史裸 MD5 摘要增加 password_hash() 包装
     *
     * 仅处理恰好 32 位的十六进制字符串，将其转为小写后用 PASSWORD_DEFAULT
     * 再次散列，并添加 v1: 前缀；已有版本摘要、其他字符串及非字符串输入原样返回。
     * 此操作保留旧密码的兼容校验能力，但不会恢复明文，也不生成 v2 格式。
     * 返回的新摘要是否写入账户存储由调用方决定，成功登录后仍可升级为 v2。
     *
     * @param mixed $hash 历史密码摘要或需要保持原样的其他值
     * @return mixed 包装后的 v1 摘要，或不符合裸 MD5 格式时的原始输入
     */
    public static function protectLegacyHash($hash) {
        return is_string($hash) && preg_match('/^[a-f0-9]{32}$/iD', $hash)
            ? 'v1:'.password_hash(strtolower($hash), PASSWORD_DEFAULT) : $hash;
    }

    /**
     * 功能说明：根据已保存摘要的格式，兼容校验用户提交的明文密码
     *
     * v2: 使用 SHA-384/Base64 预处理后执行 password_verify()；
     * v1: 对密码计算 MD5，再与已包装的旧摘要比较；裸 MD5 使用 hash_equals() 比较；
     * 其他非空字符串按无版本前缀的 password_hash() 摘要执行原生校验。
     * v1 和裸 MD5 还兼容旧 CMS 把 htmlspecialchars() 结果作为密码的历史行为，
     * 该分支沿用当前 PHP 的默认转义选项和字符集；v2 不尝试此兼容转换。
     * 非字符串或空输入返回 false。本方法不会自动升级摘要或写入登录状态。
     *
     * @param mixed $password 用户提交的原始明文密码
     * @param mixed $hash 账户中完整保存的密码摘要，包含可能存在的版本前缀
     * @return bool 密码是否通过对应格式的校验
     */
    public static function verifyPassword($password, $hash) {
        if(!is_string($password) || !is_string($hash) || $password === '' || $hash === '') return false;
        if(strpos($hash, 'v2:') === 0) return password_verify(base64_encode(hash('sha384', $password, true)), substr($hash, 3));
        // 仅在兼容旧摘要时尝试历史 CMS 的 HTML 转义密码形式，新 v2 摘要不走此分支。
        if(strpos($hash, 'v1:') === 0) return password_verify(md5($password), substr($hash, 3))
            || (htmlspecialchars($password) !== $password && password_verify(md5(htmlspecialchars($password)), substr($hash, 3)));
        if(preg_match('/^[a-f0-9]{32}$/iD', $hash)) return hash_equals(strtolower($hash), md5($password))
            || hash_equals(strtolower($hash), md5(htmlspecialchars($password)));
        return password_verify($password, $hash);
    }

    /**
     * 功能说明：判断现有密码摘要是否需要升级格式或重新散列
     *
     * 非 v2: 前缀一律视为需要升级；v2 摘要去掉前缀后，通过
     * password_needs_rehash() 检查是否符合当前 PASSWORD_DEFAULT 的算法和默认参数。
     * 本方法只判断存储格式，不校验密码；应先验证登录密码成功，再调用本方法，
     * 需要升级时用该明文密码生成新摘要并由业务保存。
     *
     * @param string $hash 已读取并确认类型的完整密码摘要
     * @return bool 是否需要生成并保存新的密码摘要
     */
    public static function needsUpgrade($hash) {
        return strpos($hash, 'v2:') !== 0 || password_needs_rehash(substr($hash, 3), PASSWORD_DEFAULT);
    }

    /**
     * 功能说明：将二进制或文本数据编码为不带填充的 Base64URL 字符串
     *
     * 在 Base64 结果中将 +、/ 替换为 -、_，并移除末尾 =，用于登录令牌的载荷段。
     * 仅供类内部使用；编码可逆，不提供加密、签名或内容可信性保证。
     *
     * @param string $data 待编码的原始字节串
     * @return string 不带 = 填充的 Base64URL 编码结果
     */
    protected static function encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * 功能说明：解析登录令牌的结构和载荷，并检查是否已过期
     *
     * 仅接受不超过 2048 字节的“Base64URL 载荷.64 位小写十六进制签名”字符串。
     * 解码 JSON 后要求 v 严格为整数 2、u/s 为字符串、e 为尚未到期的整数时间戳。
     * 成功时返回完整载荷数组，其中 u 为用户名、s 为用途、e 为过期时间，
     * makeToken() 还会生成随机 n 字段；本方法不要求或校验 n，也不剔除额外字段。
     * 此处不验证签名、预期用户名、用途或最长有效期，不能凭解析成功建立登录状态，
     * 必须继续使用 verifyToken() 完成认证。
     *
     * 载荷结构：v版本号2、u用户名、s用途范围、e秒级Unix到期时间；可能含n等附加键。此入口只解析结构和时效，必须经verifyToken校验HMAC后才能作为登录凭据。
     *
     * @param mixed $token 来自 Cookie 等输入渠道的完整登录令牌
     * @return array|false 通过结构及过期检查的载荷数组；输入非法或已过期时返回 false
     * @phpstan-return array{v:2,u:string,s:string,e:int}|false
     */
    public static function readToken($token) {
        if(!is_string($token) || strlen($token) > 2048 || !preg_match('/^[A-Za-z0-9_-]+\.[a-f0-9]{64}$/D', $token)) return false;
        $parts = explode('.', $token, 2);
        $data = json_decode(base64_decode(strtr($parts[0], '-_', '+/'), true), true);
        if(!is_array($data) || ($data['v'] ?? null) !== 2 || !is_string($data['u'] ?? null)
            || !is_string($data['s'] ?? null) || !is_int($data['e'] ?? null) || $data['e'] <= time()) return false;
        return $data;
    }

    /**
     * 功能说明：生成带有效期、用户和用途绑定的记住登录令牌
     *
     * 将有效时长转换为整数并限制在 1～31536000 秒，载荷包含版本 v=2、用户名 u、
     * 用途 s、Unix 过期时间 e 及由 16 字节安全随机数生成的十六进制 n。
     * JSON 载荷经 Base64URL 编码后，以 SHA-256(secret + 空字节 + 密码摘要)
     * 的二进制结果作为 HMAC-SHA256 密钥签名，返回“载荷.十六进制签名”。
     * 密码摘要必须与账户当前持久化值完全一致；更改摘要或站点密钥会使旧令牌失效。
     * 参数内容应由调用方验证，用户名及用途应为可进行 JSON 编码的 UTF-8 文本；
     * 本方法不设置 Cookie，也不保存随机值或单个令牌的撤销记录。
     *
     * @param string $username 需要绑定的账户用户名
     * @param string $hash 账户当前完整密码摘要，包含版本前缀
     * @param string $scope 登录用途或应用标识，校验时必须完全一致
     * @param string $secret 由服务器保管的高熵站点密钥，不应提供给客户端
     * @param int $ttl 请求的有效时长，单位为秒，超出范围时自动截取到上述上下限
     * @return string 可交由调用方保存到 Cookie 的完整登录令牌
     * @throws Exception 无法取得安全随机数时由 random_bytes() 抛出
     */
    public static function makeToken($username, $hash, $scope, $secret, $ttl) {
        $ttl = max(1, min(31536000, (int)$ttl));
        $payload = self::encode(json_encode(['v'=>2, 'u'=>$username, 's'=>$scope, 'e'=>time()+$ttl, 'n'=>bin2hex(random_bytes(16))]));
        return $payload.'.'.hash_hmac('sha256', $payload, hash('sha256', $secret."\0".$hash, true));
    }

    /**
     * 功能说明：校验记住登录令牌的结构、时效、预期身份、用途和 HMAC 签名
     *
     * 先调用 readToken() 拒绝非法或过期令牌，再严格比较用户名和用途，
     * 并拒绝过期时间超过当前服务器时间一年以上的载荷。以调用方提供的当前账户摘要
     * 和站点密钥重新计算签名，使用 hash_equals() 比较，全部通过后返回 true。
     * 不查询账户、不校验明文密码，也不恢复 Session；调用方须先取得真实账户记录，
     * 校验成功后再设置登录状态，并按业务要求检查账户状态、角色及资源权限。
     *
     * 调用示例：
     *     $ok = myAuth::verifyToken($token, $username, $passwordHash, 'admin', $secret);
     *
     * @param mixed $token 待验证的完整登录令牌
     * @param string $username 预期用户名，必须与载荷中的 u 严格一致
     * @param string $hash 账户当前持久化的完整密码摘要
     * @param string $scope 预期登录用途，必须与载荷中的 s 严格一致
     * @param string $secret 当前服务器保管的站点密钥
     * @return bool 令牌是否通过全部校验
     */
    public static function verifyToken($token, $username, $hash, $scope, $secret) {
        $data = self::readToken($token);
        if($data === false || $data['u'] !== $username || $data['s'] !== $scope || $data['e'] > time()+31536000) return false;
        list($payload, $signature) = explode('.', $token, 2);
        return hash_equals(hash_hmac('sha256', $payload, hash('sha256', $secret."\0".$hash, true)), $signature);
    }

    /**
     * 功能说明：为已经启动的会话重新生成 Session ID，降低会话固定攻击风险
     *
     * 仅在 session_status() 为 PHP_SESSION_ACTIVE 时调用
     * session_regenerate_id(true)，保留当前会话数据并请求删除旧 ID 对应的存储。
     * 通常在登录成功或身份变更后、响应头发送前调用；未启动会话时不执行操作。
     * 本方法不启动会话、不设置身份，也不返回底层 ID 更新是否成功的结果。
     *
     * 参数说明：无显式参数。
     *
     * @return void 无返回值。
     */
    public static function renewSession() {
        if(session_status() === PHP_SESSION_ACTIVE) session_regenerate_id(true);
    }
}
