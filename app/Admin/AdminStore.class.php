<?php
/**
 * 文件：app/Admin/AdminStore.class.php
 * 功能说明：后台配置版本、乐观锁校验与原子保存。
 * 方法目录：
 *   public static AdminStore::version($file) — 读取文件内容版本摘要
 *   public static AdminStore::locked($file, callable $callback) — 在独立排他锁内运行受信回调
 *   public static AdminStore::read($file, $default = []) — 读取受信PHP数组配置
 *   public static AdminStore::save($file, $content, $version) — 在文件锁内比较版本并原子发布完整内容
 *   public static AdminStore::saveData($file, array $data, $version) — 验证普通数据并保存PHP配置
 *   public static AdminStore::identifier($value) — 校验应用、插件或类标识
 *   public static AdminStore::directory($root, $name) — 解析根目录内不存在符号链接的项目目录
 *
 * 使用说明：受信服务构造路径，GET记录version，POST携带原版本；不提供强制覆盖。
 */
/**
 * 功能说明：为受信配置提供内容版本比较、文件锁和原子保存。
 * 使用说明：参见文件顶部；只接受服务端构造的路径和定义。
 * 方法目录：参见文件顶部。
 */
class AdminStore {
    /**
     * 功能说明：读取文件内容版本摘要。
     * @param string $file 受信路径
     * @return string 内容摘要，不存在为missing
     */
    public static function version($file) {
        return is_file($file)?hash_file('sha256', $file):'missing';
    }
    /**
     * 功能说明：在独立排他锁内运行受信回调。
     * @param string $file 可信旁路锁路径
     * @param callable $callback 持锁回调
     * @return mixed 回调结果
     * @throws RuntimeException 加锁失败
     */
    public static function locked($file, callable $callback) {
        if(!myFile::mkdir(dirname($file)))throw new RuntimeException('无法创建锁目录。');
        $lock = fopen($file, 'c');
        if(!$lock)throw new RuntimeException('无法打开操作锁。');
        if(!flock($lock, LOCK_EX)) {
            fclose($lock);
            throw new RuntimeException('无法锁定操作。');
        }
        try {
            return $callback();
        }
        finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
    /**
     * 功能说明：读取受信PHP数组配置。
     * @param string $file 可信PHP配置
     * @param array $default 缺失默认值
     * @return array 数据
     * @throws UnexpectedValueException 根节点无效
     */
    public static function read($file, $default = []) {
        if(!is_file($file))return $default;
        if(function_exists('opcache_invalidate'))opcache_invalidate($file, true);
        $value = (static function($path) {
            return include $path;
        }
        )($file);
        if(!is_array($value))throw new UnexpectedValueException('配置文件根节点必须为数组。');
        return $value;
    }
    /**
     * 功能说明：在文件锁内比较版本并原子发布完整内容。
     * @param string $file 可信目标，不接受用户直接提交的路径
     * @param string $content 完整字节串
     * @param string $version 浏览时版本
     * @return bool 成功true
     * @throws RuntimeException 冲突（code=409）或I/O失败
     * @throws InvalidArgumentException 类型或PHP语法无效
     */
    public static function save($file, $content, $version) {
        if(!is_string($content) || !is_string($version))throw new InvalidArgumentException('缺少有效内容或版本。');
        if(is_link($file))throw new InvalidArgumentException('不能写入符号链接。');
        if(strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'php') {
            try {
                token_get_all($content, TOKEN_PARSE);
            }
            catch(ParseError $e) {
                throw new InvalidArgumentException('PHP语法错误：' . $e->getMessage());
            }
            if(!preg_match('/\A\s*<\?php\b/i', $content))throw new InvalidArgumentException('PHP文件必须从PHP标签开始。');
        }
        if(!myFile::mkdir(dirname($file)))throw new RuntimeException('无法创建配置目录。');
        $lock = fopen($file . '.lock', 'c');
        if(!$lock)throw new RuntimeException('无法锁定配置。');
        if(!flock($lock, LOCK_EX)) {
            fclose($lock);
            throw new RuntimeException('无法锁定配置。');
        }
        $tmp = '';
        try {
            clearstatcache(true, $file);
            if(!hash_equals(self::version($file), $version))throw new RuntimeException('保存冲突：文件已被修改，请重新加载。', 409);
            $tmp = $file . '.' . bin2hex(random_bytes(8)) . '.tmp';
            if(file_put_contents($tmp, $content, LOCK_EX) !== strlen($content))throw new RuntimeException('文件写入不完整。');
            if(is_file($file))chmod($tmp, fileperms($file)&0777);
            if(!rename($tmp, $file))throw new RuntimeException('无法发布配置。');
            if(function_exists('opcache_invalidate'))opcache_invalidate($file, true);
            return true;
        }
        finally {
            if($tmp !== '' && is_file($tmp))unlink($tmp);
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
    /**
     * 功能说明：验证普通数据并保存PHP配置。
     * @param string $file PHP配置
     * @param array $data 普通数据
     * @param string $version 原版本
     * @return bool 保存结果
     * @throws InvalidArgumentException 包含对象或资源
     */
    public static function saveData($file, array $data, $version) {
        $check = function($v)use(&$check) {
            if(is_object($v) || is_resource($v))throw new InvalidArgumentException('只允许普通配置数据。');
            if(is_array($v))foreach($v as $part)$check($part);
        }
        ;
        $check($data);
        return self::save($file, "<?php\nreturn " . var_export($data, true) . ";\n", $version);
    }
    /**
     * 功能说明：校验应用、插件或类标识。
     * @param mixed $value 应用/插件/类标识
     * @return string 合法标识
     * @throws InvalidArgumentException 非法标识
     */
    public static function identifier($value) {
        if(!is_string($value) || !preg_match('/\A[a-zA-Z][a-zA-Z0-9_]*\z/D', $value))throw new InvalidArgumentException('无效标识。');
        return $value;
    }
    /**
     * 功能说明：解析根目录内不存在符号链接的项目目录。
     * @param string $root 受信根
     * @param mixed $name 名称
     * @return string 安全目录
     * @throws InvalidArgumentException 不存在或含符号链接
     */
    public static function directory($root, $name) {
        $file = myFile::extractionPath($root, self::identifier($name));
        if(!$file || !is_dir($file))throw new InvalidArgumentException('目录不存在或不安全。');
        return $file . '/';
    }
}
