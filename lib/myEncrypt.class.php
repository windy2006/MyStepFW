<?PHP
/********************************************
*                                           *
* Name    : Functions 4 Encrypt & Decrypt   *
* Modifier: Windy2000                       *
* Time    : 2003-05-03                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/
/**
 * 文件：lib/myEncrypt.class.php
 * 功能说明：字符串与文件加解密工具
 *   提供历史异或兼容接口，以及带认证的现代封装与文件处理。
 *
 * 方法目录：
 *   public static myEncrypt::keyED($str, $encrypt_key) — 按密钥摘要执行历史异或变换，供旧格式加解密复用
 *   public static myEncrypt::encStr($str, $key) — 将字符串编码为历史可逆加密格式，兼容既有数据
 *   public static myEncrypt::encFile($file, $key) — 在临时文件中按历史格式加密本地文件并原子替换
 *   public static myEncrypt::decStr($str, $key) — 解码历史加密字符串，不能据此判断数据完整性
 *   public static myEncrypt::decFile($file, $key) — 在临时文件中解密历史文件格式并原子替换
 *   private static myEncrypt::transformFile($file, $key, $decrypt) — 执行历史文件格式转换，失败时保留原文件
 *   public static myEncrypt::seal($plaintext, $key) — 使用AES-256-GCM生成带版本及认证信息的加密封装
 *   public static myEncrypt::open($envelope, $key) — 校验并解密MSENC1封装，认证失败返回false
 *
 * 使用说明：
 *   1. 新数据优先使用seal/open认证封装；旧encStr/decStr等仅用于既有格式兼容。
 *   2. 文件入口在临时文件中转换后发布，失败不覆盖原文件。
 *   3. 认证密钥由调用方安全管理；不能把可逆加密当成密码哈希。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名保持PHP 7.4兼容，PHPDoc类型用于说明，不额外强制运行时类型。
 */


/**
 * 功能说明：提供历史异或兼容接口，以及带认证的现代封装与文件处理。
 *
 * 使用说明：
 *   1. 新数据优先使用seal/open认证封装；旧encStr/decStr等仅用于既有格式兼容。
 *   2. 文件入口在临时文件中转换后发布，失败不覆盖原文件。
 *   3. 认证密钥由调用方安全管理；不能把可逆加密当成密码哈希。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 *
 * @example ../app/Document/module/myEncrypt.php 框架现有演示，仅作为文档引用，不在检查时执行
 */
class myEncrypt {
    /**
     * 功能说明：按密钥摘要执行历史异或变换，供旧格式加解密复用
     *
     * @param string $str 待处理的字符串
     * @param string $encrypt_key 用于历史异或变换的密钥
     * @return string 历史异或变换后的原始字节串；相同密钥可执行逆变换
     * @see myEncrypt::seal() 新数据的认证加密封装
     */
    public static function keyED($str, $encrypt_key) {
        $encrypt_key = md5($encrypt_key);
        $ctr=0;
        $tmp = '';
        for($i=0;$i<strlen($str);$i++) {
            if($ctr==strlen($encrypt_key)) $ctr=0;
            $tmp.= substr($str, $i, 1) ^ substr($encrypt_key, $ctr, 1);
            $ctr++;
        }
        return $tmp;
    }

    /**
     * 功能说明：将字符串编码为历史可逆加密格式，兼容既有数据
     *
     * @param string $str 待处理的字符串
     * @param string $key 加解密密钥
     * @return string 历史加密格式文本，用于已有数据兼容，不提供认证完整性
     * @see myEncrypt::seal() 新数据的认证加密封装
     */
    public static function encStr($str, $key) {
        $encrypt_key = bin2hex(random_bytes(16));
        $ctr=0;
        $tmp = '';
        for($i=0;$i<strlen($str);$i++) {
            if($ctr==strlen($encrypt_key)) $ctr=0;
            $tmp.= substr($encrypt_key, $ctr, 1) . (substr($str, $i, 1) ^ substr($encrypt_key, $ctr, 1));
            $ctr++;
        }
        return self::keyED($tmp, $key);
    }

    /**
     * 功能说明：在临时文件中按历史格式加密本地文件并原子替换
     *
     * @param string $file 目标文件路径
     * @param string $key 加解密密钥
     * @return bool 转换并替换原文件成功为true，失败为false
     * @see myEncrypt::seal() 新数据的认证加密封装
     */
    public static function encFile($file, $key) {
        return self::transformFile($file, $key, false);
    }

    /**
     * 功能说明：解码历史加密字符串，不能据此判断数据完整性
     *
     * @param string $str 待处理的字符串
     * @param string $key 加解密密钥
     * @return string 从历史加密格式解出的明文字节串
     * @see myEncrypt::open() 新封装的认证解密
     */
    public static function decStr($str, $key) {
        if(strlen($str) % 2) return false;
        $str = self::keyED($str, $key);
        $tmp = '';
        for($i=0;$i<strlen($str);$i++) {
            $md5 = substr($str, $i, 1);
            $i++;
            $tmp.= (substr($str, $i, 1) ^ $md5);
        }
        return $tmp;
    }

    /**
     * 功能说明：在临时文件中解密历史文件格式并原子替换
     *
     * @param string $file 目标文件路径
     * @param string $key 加解密密钥
     * @return bool 转换并替换原文件成功为true，失败为false
     * @see myEncrypt::open() 新封装的认证解密
     */
    public static function decFile($file, $key) {
        return self::transformFile($file, $key, true);
    }

    /**
     * 功能说明：执行历史文件格式转换，失败时保留原文件
     *
     * @param string $file 目标文件路径
     * @param string $key 加解密密钥
     * @param bool $decrypt true执行文件解密，false执行文件加密
     * @return bool 完整转换并原子发布成功为true，失败为false
     * @internal 框架内部实现入口；应用优先调用对应公开方法
     */
    private static function transformFile($file, $key, $decrypt) {
        if(!is_file($file) || is_link($file)) return false;
        $input = fopen($file, 'rb');
        if(!$input) return false;
        if($decrypt && !hash_equals(md5($key), fread($input, 32))) { fclose($input); return false; }
        $temporary = tempnam(dirname($file), '.crypt-');
        if($temporary === false) { fclose($input); return false; }
        $output = fopen($temporary, 'wb');
        if(!$output) { fclose($input); unlink($temporary); return false; }
        try {
            if(!$decrypt && fwrite($output, md5($key)) !== 32) return false;
            while(!feof($input)) {
                $chunk = fread($input, $decrypt ? 2048 : 1024);
                if($chunk === false || ($decrypt && strlen($chunk) % 2)) return false;
                $data = $decrypt ? self::decStr($chunk, $key) : self::encStr($chunk, $key);
                if(fwrite($output, $data) !== strlen($data)) return false;
            }
            fclose($input); $input = null;
            fclose($output); $output = null;
            chmod($temporary, fileperms($file) & 0777);
            return rename($temporary, $file);
        } finally {
            if(is_resource($input)) fclose($input);
            if(is_resource($output)) fclose($output);
            if(is_file($temporary)) unlink($temporary);
        }
    }

    /**
     * 功能说明：使用AES-256-GCM生成带版本及认证信息的加密封装
     *
     *
     * 调用示例：
     *     $envelope = myEncrypt::seal('示例文本', $secret);
     *     if ($envelope !== false) { $plaintext = myEncrypt::open($envelope, $secret); }
     *
     * @param string $plaintext 待加密的明文字节串
     * @param string $key 加解密密钥
     * @return string|false MSENC1开头的认证封装；加密失败为false
     * @throws InvalidArgumentException 密钥不是非空字符串
     */
    public static function seal($plaintext, $key) {
        if(!is_string($key) || $key === '') throw new InvalidArgumentException('Encryption key is required');
        $salt = random_bytes(16); $nonce = random_bytes(12); $tag = '';
        $derived = hash_hkdf('sha256', $key, 32, 'MyStepFW seal v1', $salt);
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $derived, OPENSSL_RAW_DATA, $nonce, $tag, 'MSENC1:', 16);
        if($ciphertext === false) return false;
        return 'MSENC1:'.base64_encode($salt.$nonce.$tag.$ciphertext);
    }

    /**
     * 功能说明：校验并解密MSENC1封装，认证失败返回false
     *
     * @param string $envelope 带版本、随机量与认证信息的加密封装
     * @param string $key 加解密密钥
     * @return string|false 解密后的原始明文字节；结构错误或认证失败为false
     */
    public static function open($envelope, $key) {
        if(!is_string($envelope) || !is_string($key) || $key === '' || strpos($envelope, 'MSENC1:') !== 0) return false;
        $data = base64_decode(substr($envelope, 7), true);
        if($data === false || strlen($data) < 44) return false;
        $derived = hash_hkdf('sha256', $key, 32, 'MyStepFW seal v1', substr($data, 0, 16));
        return openssl_decrypt(substr($data, 44), 'aes-256-gcm', $derived, OPENSSL_RAW_DATA, substr($data, 16, 12), substr($data, 28, 16), 'MSENC1:');
    }

}