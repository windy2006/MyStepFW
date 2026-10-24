<?PHP
/********************************************
*                                           *
* Name    : Functions 4 Encrypt & Decrypt   *
* Modifier: Windy2000                       *
* Time    : 2003-05-03                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.     *
*                                           *
********************************************/
/**
 * 文件：lib/myEncrypt.class.php
 * 功能说明：字符串与本地文件的认证加解密工具
 *   字符串使用AES-256-GCM，文件使用Sodium Secretstream分块处理；认证失败不发布明文。
 *
 * 方法目录：
 *   public static myEncrypt::seal($plaintext, $key) — 生成AES-256-GCM字符串认证封装
 *   public static myEncrypt::open($envelope, $key) — 校验并解密MSENC1字符串封装
 *   public static myEncrypt::sealFile($file, $key) — 分块加密本地文件并原子替换
 *   public static myEncrypt::openFile($file, $key) — 完整认证文件流后原子替换为明文
 *   private static myEncrypt::transformFile($file, $key, $decrypt) — 在文件锁及同目录暂存内完成转换
 *   private static myEncrypt::sealStream($input, $output, $key) — 生成带最终标记的Secretstream文件
 *   private static myEncrypt::openStream($input, $output, $key) — 校验文件头、每块认证及最终标记
 *   private static myEncrypt::readChunk($stream, $length) — 读取指定字节数或文件尾
 *   private static myEncrypt::writeAll($stream, $data) — 完整写入，拒绝零进度和写入失败
 *
 * 使用说明：
 *   1. seal/open需要OpenSSL，sealFile/openFile需要Sodium Secretstream；均兼容PHP 7.4。
 *   2. 调用方保管高熵密钥；HKDF用于密钥分离，不是低强度口令的慢速密码派生函数。
 *   3. 文件转换只接受本地普通文件，保留访问权限；错误密钥、损坏或并发冲突不覆盖原文件。
 *   4. 文件按64KiB处理，整个流认证完成后才发布；暂存文件在失败或异常时清理。
 *   5. 密码存储使用myAuth；本类的可逆加密不用于密码摘要。旧异或接口及格式已移除。
 *
 * 说明：目录覆盖本文件直接声明的全部方法；参数、返回值及异常详见各方法PHPDoc。
 *       原生签名保持PHP 7.4兼容，PHPDoc类型用于说明。
 */
/**
 * 功能说明：为字符串及任意大小本地文件提供认证加密，不回退到无认证算法。
 *
 * 字符串MSENC1封装与文件MSFILE1封装用途不同，不能交叉解密。
 * 文件发布使用同目录rename及协作排他锁；不承诺进程崩溃后的持久化或阻止不遵守文件锁的外部写入。
 *
 * 方法目录：参见本文件顶部。
 * @example ../app/Document/module/myEncrypt.php 字符串、文件及失败保留原文的完整样例
 */
class myEncrypt {
    /** @var string 文件格式版本，作为每个加密块的附加认证数据的一部分 */
    private const FILE_MAGIC = 'MSFILE1:';
    /** @var int 每个明文块的最大字节数 */
    private const FILE_CHUNK = 65536;

    /**
     * 功能说明：使用随机盐、随机nonce及AES-256-GCM生成字符串认证封装。
     *
     * 通过HKDF-SHA256分离32字节密钥，使用12字节nonce及16字节认证标签。
     * 同一明文和密钥重复加密会生成不同封装；明文可以为空或包含任意二进制字节。
     * @param string $plaintext 待加密的原始字节串
     * @param string $key 非空高熵密钥，建议random_bytes(32)生成并安全持久保存
     * @return string|false MSENC1开头的Base64认证封装；OpenSSL加密失败为false
     * @throws InvalidArgumentException 明文不是字符串或密钥不是非空字符串
     * @throws RuntimeException 当前PHP没有OpenSSL加密能力
     * @throws Exception 无法生成安全随机数
     */
    public static function seal($plaintext, $key) {
        if(!is_string($plaintext)) throw new InvalidArgumentException('Plaintext must be a string');
        if(!is_string($key) || $key === '') throw new InvalidArgumentException('Encryption key is required');
        if(!function_exists('openssl_encrypt')) throw new RuntimeException('The OpenSSL extension is required');
        $salt = random_bytes(16); $nonce = random_bytes(12); $tag = '';
        $derived = hash_hkdf('sha256', $key, 32, 'MyStepFW seal v1', $salt);
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $derived, OPENSSL_RAW_DATA, $nonce, $tag, 'MSENC1:', 16);
        if($ciphertext === false) return false;
        return 'MSENC1:'.base64_encode($salt.$nonce.$tag.$ciphertext);
    }

    /**
     * 功能说明：校验并解密MSENC1字符串封装，不接受未知或历史异或格式。
     *
     * @param string $envelope seal生成的完整封装
     * @param string $key 加密时使用的同一高熵密钥
     * @return string|false 原始明文字节串，可能为空；格式、密钥或认证不正确时为false
     * @throws RuntimeException 当前PHP没有OpenSSL解密能力
     */
    public static function open($envelope, $key) {
        if(!is_string($envelope) || !is_string($key) || $key === '' || strpos($envelope, 'MSENC1:') !== 0) return false;
        if(!function_exists('openssl_decrypt')) throw new RuntimeException('The OpenSSL extension is required');
        $data = base64_decode(substr($envelope, 7), true);
        if($data === false || strlen($data) < 44) return false;
        $derived = hash_hkdf('sha256', $key, 32, 'MyStepFW seal v1', substr($data, 0, 16));
        return openssl_decrypt(substr($data, 44), 'aes-256-gcm', $derived, OPENSSL_RAW_DATA, substr($data, 16, 12), substr($data, 28, 16), 'MSENC1:');
    }

    /**
     * 功能说明：使用Secretstream逐块加密文件，成功后原子替换原文件。
     *
     * 新文件格式MSFILE1使用随机盐、HKDF密钥分离和Secretstream生成的随机流头。
     * 最后一个块带FINAL标记，空文件也包含最终认证块；不将整个文件载入内存。
     * @param string $file 已存在的本地普通文件路径，可为网站根解析后的绝对路径
     * @param string $key 非空高熵密钥，解密时须使用同一密钥
     * @return bool 转换成功为true；路径、文件锁、读写或发布失败为false且不替换原文件
     * @throws InvalidArgumentException 密钥不是非空字符串
     * @throws RuntimeException 当前PHP没有Sodium Secretstream能力
     * @throws Exception 无法生成安全随机数或底层加密发生异常
     */
    public static function sealFile($file, $key) {
        return self::transformFile($file, $key, false);
    }

    /**
     * 功能说明：逐块认证MSFILE1文件，确认FINAL及文件尾后原子发布明文。
     *
     * 错误密钥、损坏、截断、块重排及尾部追加均不会发布暂存明文；不回退读取旧文件格式。
     * @param string $file sealFile生成的本地加密文件路径
     * @param string $key 加密时使用的同一高熵密钥
     * @return bool 全部认证并发布成功为true；格式、认证、文件锁或文件操作失败为false
     * @throws InvalidArgumentException 密钥不是非空字符串
     * @throws RuntimeException 当前PHP没有Sodium Secretstream能力
     * @throws Exception 底层加密发生异常；原文件不被替换
     */
    public static function openFile($file, $key) {
        return self::transformFile($file, $key, true);
    }

    /**
     * 功能说明：持有非阻塞排他锁，将结果写入同目录暂存文件后校验目标并发布。
     *
     * 暂存初始权限0600，完整成功后才恢复原文件权限；身份、大小及时间状态变化时拒绝覆盖。
     * 不接受URL、流包装器及文件符号链接；finally负责释放资源及删除未发布暂存。
     * @param string $file 本地普通文件路径
     * @param string $key 非空高熵密钥
     * @param bool $decrypt true解密，false加密
     * @return bool 完整转换并原子发布是否成功
     * @throws InvalidArgumentException 密钥无效
     * @throws RuntimeException 缺少Sodium扩展
     * @throws Exception 随机数或加密异常继续传递，暂存仍会清理
     */
    private static function transformFile($file, $key, $decrypt) {
        if(!is_string($key) || $key === '') throw new InvalidArgumentException('Encryption key is required');
        if(!function_exists('sodium_crypto_secretstream_xchacha20poly1305_init_push')) throw new RuntimeException('The Sodium Secretstream extension is required');
        if(!is_string($file) || $file === '' || preg_match('/[\x00-\x1f\x7f]/', $file) || preg_match('#^[a-z][a-z0-9+.-]*://#i', $file) || is_link($file) || !is_file($file)) return false;
        $path = realpath($file);
        if($path === false) return false;
        $input = @fopen($path, 'rb');
        if($input === false) return false;
        $output = null; $temporary = '';
        try {
            if(!flock($input, LOCK_EX | LOCK_NB)) return false;
            $before = fstat($input);
            clearstatcache(true, $path); $current = @lstat($path);
            if(!$before || !$current || ($current['mode'] & 0170000) !== 0100000 || $current['dev'] !== $before['dev'] || $current['ino'] !== $before['ino']) return false;
            $temporary = @tempnam(dirname($path), '.ms-crypt-');
            if($temporary === false) { $temporary = ''; return false; }
            if(dirname($temporary) !== dirname($path)) return false;
            $output = @fopen($temporary, 'wb');
            if($output === false) return false;
            $ok = $decrypt ? self::openStream($input, $output, $key) : self::sealStream($input, $output, $key);
            if(!$ok || !fflush($output) || !@chmod($temporary, $before['mode'] & 0777)) return false;
            $closed = fclose($output); $output = null;
            if(!$closed) return false;
            clearstatcache(true, $path); $current = @lstat($path); $after = fstat($input);
            if(!$current || !$after) return false;
            foreach(['dev','ino','mode','size','mtime','ctime'] as $field) {
                if($current[$field] !== $before[$field] || $after[$field] !== $before[$field]) return false;
            }
            if(!@rename($temporary, $path)) return false;
            $temporary = '';
            return true;
        } finally {
            if(is_resource($output)) fclose($output);
            flock($input, LOCK_UN); fclose($input);
            if($temporary !== '' && is_file($temporary)) unlink($temporary);
        }
    }

    /**
     * 功能说明：生成文件头，并用一块前瞻确定最后一个Secretstream认证块。
     *
     * @param resource $input 已锁定的本地输入流
     * @param resource $output 权限受限的暂存输出流
     * @param string $key 调用方密钥
     * @return bool 完整写入文件头和全部认证块是否成功
     * @throws Exception 随机数或Sodium异常继续传递
     */
    private static function sealStream($input, $output, $key) {
        $salt = random_bytes(16);
        $derived = hash_hkdf('sha256', $key, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES, 'MyStepFW file seal v1', $salt);
        $state = '';
        try {
            list($state, $streamHeader) = sodium_crypto_secretstream_xchacha20poly1305_init_push($derived);
            $header = self::FILE_MAGIC.$salt.$streamHeader;
            if(!self::writeAll($output, $header)) return false;
            $chunk = self::readChunk($input, self::FILE_CHUNK);
            if($chunk === false) return false;
            while(true) {
                $next = self::readChunk($input, self::FILE_CHUNK);
                if($next === false) return false;
                $final = $next === '';
                $cipher = sodium_crypto_secretstream_xchacha20poly1305_push($state, $chunk, $header, $final ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL : SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE);
                if(!self::writeAll($output, $cipher)) return false;
                if($final) return true;
                $chunk = $next;
            }
        } finally {
            if($state !== '') sodium_memzero($state);
            sodium_memzero($derived);
        }
    }

    /**
     * 功能说明：按固定最大块读取并认证，要求最后一个块携带FINAL且不存在附加字节。
     *
     * @param resource $input 已锁定的本地输入流
     * @param resource $output 尚未发布的明文暂存流
     * @param string $key 调用方密钥
     * @return bool 格式、所有块及最终结束条件均有效时为true
     * @throws Exception Sodium初始化或解密异常继续传递
     */
    private static function openStream($input, $output, $key) {
        $headerLength = strlen(self::FILE_MAGIC) + 16 + SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES;
        $header = self::readChunk($input, $headerLength);
        if($header === false || strlen($header) !== $headerLength || substr($header, 0, strlen(self::FILE_MAGIC)) !== self::FILE_MAGIC) return false;
        $derived = hash_hkdf('sha256', $key, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES, 'MyStepFW file seal v1', substr($header, strlen(self::FILE_MAGIC), 16));
        $state = '';
        try {
            $state = sodium_crypto_secretstream_xchacha20poly1305_init_pull(substr($header, strlen(self::FILE_MAGIC) + 16), $derived);
            $maximum = self::FILE_CHUNK + SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES;
            while(true) {
                $cipher = self::readChunk($input, $maximum);
                if($cipher === false || strlen($cipher) < SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES) return false;
                $result = sodium_crypto_secretstream_xchacha20poly1305_pull($state, $cipher, $header);
                if($result === false) return false;
                list($plaintext, $tag) = $result;
                if($tag === SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL) {
                    return self::readChunk($input, 1) === '' && self::writeAll($output, $plaintext);
                }
                if($tag !== SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE || strlen($cipher) !== $maximum || !self::writeAll($output, $plaintext)) return false;
            }
        } finally {
            if($state !== '') sodium_memzero($state);
            sodium_memzero($derived);
        }
    }

    /**
     * 功能说明：在固定预算内循环读取，直到取得要求字节数或正常到达文件尾。
     * @param resource $stream 本地可读文件流
     * @param int $length 本次最多读取的字节数
     * @return string|false 读取到的字节，正常文件尾可能为空；错误或零进度为false
     */
    private static function readChunk($stream, $length) {
        $buffer = '';
        while(strlen($buffer) < $length && !feof($stream)) {
            $chunk = fread($stream, $length - strlen($buffer));
            if($chunk === false || ($chunk === '' && !feof($stream))) return false;
            $buffer .= $chunk;
        }
        return $buffer;
    }

    /**
     * 功能说明：循环写入剩余字节，拒绝短写后的零进度及底层失败。
     * @param resource $stream 本地可写暂存文件流
     * @param string $data 本次完整待写字节串，允许为空
     * @return bool 所有字节写入成功为true，否则false
     */
    private static function writeAll($stream, $data) {
        $offset = 0; $length = strlen($data);
        while($offset < $length) {
            $written = fwrite($stream, substr($data, $offset));
            if($written === false || $written === 0) return false;
            $offset += $written;
        }
        return true;
    }
}
