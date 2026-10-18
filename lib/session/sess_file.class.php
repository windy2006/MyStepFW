<?PHP

/**
 * 文件：lib/session/sess_file.class.php
 * 功能说明：基于文件的Session处理器
 *   提供会话ID校验、持锁读写、过期判断、时间更新和安全垃圾回收。
 *
 * 方法目录：
 *   public static sess_file::open($sess_path, $sess_name) — 初始化存储目录；相对路径转为绝对路径，避免关闭阶段工作目录变化。
 *   protected static sess_file::validId($sid) — 校验ID字符集和长度；不接受路径分隔符或SQL/控制字符。
 *   protected static sess_file::release() — 释放当前文件锁及迁移状态；可重复调用。
 *   protected static sess_file::sameFile($handle, $file) — 判断已打开句柄仍对应原路径的普通文件，拒绝符号链接和已被删除的inode。
 *   protected static sess_file::lock($sid, $create = true) — 获取当前ID的独占锁；等待者在删除/回收后重新打开路径，避免写入失联文件。
 *   public static sess_file::close() — 关闭会话并释放锁；垃圾回收统一由PHP的gc概率控制。
 *   protected static sess_file::legacyFiles($sid) — 获取旧日期目录中的同ID文件；过滤链接目录，避免越出当前存储根目录。
 *   public static sess_file::read($sid) — 读取会话；已过期内容立即清空，不依赖概率GC；空文件可迁移有效旧日期数据。
 *   public static sess_file::write($sid, $sess_data) — 保存完整原始数据并刷新mtime；成功写完后截断旧尾部，处理短写和空会话。
 *   public static sess_file::destroy($sid) — 删除当前会话及旧日期副本；先清空锁内数据，阻止排队请求读到旧登录信息。
 *   public static sess_file::validateId($sid) — 严格模式校验现有且未过期的ID；未知ID不会生成空文件。
 *   public static sess_file::updateTimestamp($sid, $sess_data) — 惰性写入的续期回调；迁移数据仍执行完整保存，避免只touch后丢失旧内容。
 *   protected static sess_file::cleanDirectory($path, $maxlifetime) — 非阻塞回收单目录的过期普通文件；跳过正在使用的会话和符号链接。
 *   public static sess_file::gc($maxlifetime) — 回收稳定目录及历史日期目录；缺失目录视为无需清理。
 *
 * 使用说明：
 *   1. 经myReq::sessionStart选择，启动前配置路径及Session名称。
 *   2. 读锁在本请求会话存续期间保持，close负责释放；不要直接删除活动锁目录。
 *   3. no_log请求跳过持久化，已有资源仍在close阶段释放。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名保持PHP 7.4兼容，PHPDoc类型用于说明，不额外强制运行时类型。
 */
/**
 * 功能说明：提供会话ID校验、持锁读写、过期判断、时间更新和安全垃圾回收。
 *
 * 使用说明：
 *   1. 经myReq::sessionStart选择，启动前配置路径及Session名称。
 *   2. 读锁在本请求会话存续期间保持，close负责释放；不要直接删除活动锁目录。
 *   3. no_log请求跳过持久化，已有资源仍在close阶段释放。
 *
 * 补充说明：
 * 文件存储SESSION控制类。
 * read至close持有独占锁，支持旧日期目录迁移、严格ID校验与惰性续期；
 * 文件内容保持PHP原始会话格式，目录应位于不可通过HTTP下载的位置。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 */
class sess_file implements interface_session {
    public static $path;
    protected static $handle = null, $sid = '', $legacy_files = [];

    /**
     * 功能说明：初始化存储目录；相对路径转为绝对路径，避免关闭阶段工作目录变化。
     *
     * @param string $sess_path 会话目录（不支持原生files的N;MODE;分层格式）
     * @param string $sess_name 会话Cookie名称，保留回调协议参数
     * @return bool 存储初始化成功为true，目录/连接/结构准备失败为false
     */
    public static function open($sess_path, $sess_name) {
        self::release();
        self::$path = null;
        if(!empty($GLOBALS['no_log'])) return true;
        if(!is_string($sess_path) || $sess_path === '' || strpos($sess_path, "\0") !== false || strpos($sess_path, ';') !== false) return false;
        if(!is_dir($sess_path) && !@mkdir($sess_path, 0700, true) && !is_dir($sess_path)) return false;
        $path = realpath($sess_path);
        if($path === false || !is_writable($path)) return false;
        self::$path = rtrim($path, '/\\').'/';
        return true;
    }

    /**
     * 功能说明：校验ID字符集和长度；不接受路径分隔符或SQL/控制字符。
     *
     * @param string $sid 会话ID
     * @return bool ID字符和长度符合当前处理器要求为true；不证明存储记录已存在
     */
    protected static function validId($sid) {
        return is_string($sid) && preg_match('/^[a-zA-Z0-9,-]{1,128}$/D', $sid) === 1;
    }

    /**
     * 功能说明：释放当前文件锁及迁移状态；可重复调用。
     *
     * 参数说明：无显式参数。
     *
     * @return void 无返回值。
     */
    protected static function release() {
        if(is_resource(self::$handle)) {
            flock(self::$handle, LOCK_UN);
            fclose(self::$handle);
        }
        self::$handle = null;
        self::$sid = '';
        self::$legacy_files = [];
    }

    /**
     * 功能说明：判断已打开句柄仍对应原路径的普通文件，拒绝符号链接和已被删除的inode。
     *
     * @param resource $handle 文件句柄
     * @param string $file 完整文件名
     * @return bool 打开的句柄仍指向原路径的普通文件为true；链接、删除或替换为false
     */
    protected static function sameFile($handle, $file) {
        clearstatcache(true, $file);
        $actual = @lstat($file);
        $opened = fstat($handle);
        return $actual && $opened && ($actual['mode'] & 0170000) === 0100000
            && $actual['ino'] === $opened['ino'] && $actual['dev'] === $opened['dev'];
    }

    /**
     * 功能说明：获取当前ID的独占锁；等待者在删除/回收后重新打开路径，避免写入失联文件。
     *
     * @param string $sid 会话ID
     * @param bool $create 是否允许创建；严格校验使用false（默认：true）
     * @return bool 取得ID对应的独占锁并绑定有效文件句柄为true；失败为false
     */
    protected static function lock($sid, $create = true) {
        if(!self::validId($sid) || !is_string(self::$path) || !is_dir(self::$path)) return false;
        if(self::$sid === $sid && is_resource(self::$handle)) return true;
        self::release();
        $file = self::$path.'sess_'.$sid;
        for($attempt = 0; $attempt < 8; $attempt++) {
            clearstatcache(true, $file);
            if(is_link($file) || (file_exists($file) && !is_file($file))) return false;
            $handle = @fopen($file, $create ? 'c+b' : 'r+b');
            if($handle === false) return false;
            if(!flock($handle, LOCK_EX)) { fclose($handle); return false; }
            if(self::sameFile($handle, $file)) {
                @chmod($file, 0600);
                self::$handle = $handle;
                self::$sid = $sid;
                return true;
            }
            flock($handle, LOCK_UN);
            fclose($handle);
        }
        return false;
    }

    /**
     * 功能说明：关闭会话并释放锁；垃圾回收统一由PHP的gc概率控制。
     *
     * 参数说明：无显式参数。
     *
     * @return bool 连接及锁释放后的结果；内置实现允许重复关闭
     */
    public static function close() {
        self::release();
        return true;
    }

    /**
     * 功能说明：获取旧日期目录中的同ID文件；过滤链接目录，避免越出当前存储根目录。
     *
     * @param string $sid 会话ID（须已通过校验）
     * @return string[] 同ID的旧日期目录会话文件路径列表，不包含符号链接目录
     */
    protected static function legacyFiles($sid) {
        if(preg_match('/^\d{8}$/D', basename(self::$path))) return [];
        return array_values(array_filter(glob(self::$path.'[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]/sess_'.$sid) ?: [], function($file) {
            return !is_link(dirname($file)) && !is_link($file) && is_file($file);
        }));
    }

    /**
     * 功能说明：读取会话；已过期内容立即清空，不依赖概率GC；空文件可迁移有效旧日期数据。
     *
     * @param string $sid 会话ID
     * @return string|false 有效会话的原始序列化字节；记录不存在/过期为空串，后端或加锁失败为false
     */
    public static function read($sid) {
        if(!empty($GLOBALS['no_log'])) return '';
        if(!self::lock($sid)) return false;
        $stat = fstat(self::$handle);
        $ttl = max(0, (int)ini_get('session.gc_maxlifetime'));
        if(!$stat) return false;
        if($stat['mtime'] + $ttl <= time()) {
            if(!ftruncate(self::$handle, 0) || !fflush(self::$handle)) return false;
            self::$legacy_files = self::legacyFiles($sid);
            foreach(self::$legacy_files as $file) @unlink($file);
            self::$legacy_files = [];
            return '';
        }
        if(!rewind(self::$handle)) return false;
        $data = stream_get_contents(self::$handle);
        if($data === '') {
            $newest = 0;
            self::$legacy_files = self::legacyFiles($sid);
            foreach(self::$legacy_files as $legacy) {
                $handle = @fopen($legacy, 'rb');
                if($handle === false) continue;
                if(flock($handle, LOCK_SH)) {
                    $old = fstat($handle);
                    if(self::sameFile($handle, $legacy) && $old['mtime'] > $newest && $old['mtime'] + $ttl > time()) {
                        $value = stream_get_contents($handle);
                        if($value !== false) { $data = $value; $newest = $old['mtime']; }
                    }
                    flock($handle, LOCK_UN);
                }
                fclose($handle);
            }
        }
        return $data;
    }

    /**
     * 功能说明：保存完整原始数据并刷新mtime；成功写完后截断旧尾部，处理短写和空会话。
     *
     * @param string $sid 会话ID
     * @param string $sess_data PHP编码后的字节串
     * @return bool 完整会话字节及期限保存成功为true；I/O、锁或容量失败为false
     */
    public static function write($sid, $sess_data) {
        if(!empty($GLOBALS['no_log'])) return true;
        if(!is_string($sess_data) || !self::lock($sid) || !rewind(self::$handle)) return false;
        $length = strlen($sess_data);
        $written = 0;
        while($written < $length) {
            $size = fwrite(self::$handle, substr($sess_data, $written));
            if($size === false || $size === 0) return false;
            $written += $size;
        }
        if(!ftruncate(self::$handle, $length) || !fflush(self::$handle) || !touch(self::$path.'sess_'.$sid)) return false;
        foreach(self::$legacy_files as $legacy) @unlink($legacy);
        self::$legacy_files = [];
        return true;
    }

    /**
     * 功能说明：删除当前会话及旧日期副本；先清空锁内数据，阻止排队请求读到旧登录信息。
     *
     * @param string $sid 会话ID
     * @return bool 目标会话删除及相关一致性处理成功为true，失败为false
     */
    public static function destroy($sid) {
        if(!empty($GLOBALS['no_log'])) return true;
        if(!self::lock($sid)) return false;
        $result = ftruncate(self::$handle, 0) && fflush(self::$handle) && @unlink(self::$path.'sess_'.$sid);
        foreach(self::legacyFiles($sid) as $legacy) $result = @unlink($legacy) && $result;
        self::release();
        return $result;
    }

    /**
     * 功能说明：严格模式校验现有且未过期的ID；未知ID不会生成空文件。
     *
     * @param string $sid 客户端提交的会话ID
     * @return bool 当前ID实际存在且未过期为true；非法、未知、过期或检查失败为false
     */
    public static function validateId($sid) {
        if(!empty($GLOBALS['no_log']) || !self::validId($sid) || !is_string(self::$path)) return false;
        if(self::lock($sid, false)) {
            $stat = fstat(self::$handle);
            if($stat && $stat['mtime'] + max(0, (int)ini_get('session.gc_maxlifetime')) > time()) return true;
            self::release();
            return false;
        }
        foreach(self::legacyFiles($sid) as $legacy) {
            if(@filemtime($legacy) + max(0, (int)ini_get('session.gc_maxlifetime')) > time()) return self::read($sid) !== false;
        }
        return false;
    }

    /**
     * 功能说明：惰性写入的续期回调；迁移数据仍执行完整保存，避免只touch后丢失旧内容。
     *
     * @param string $sid 会话ID
     * @param string $sess_data 原始会话数据
     * @return bool 当前会话有效期更新/必要时恢复写入成功为true，否则为false
     */
    public static function updateTimestamp($sid, $sess_data) {
        if(!empty($GLOBALS['no_log'])) return true;
        if(!self::lock($sid)) return false;
        $stat = fstat(self::$handle);
        if(!$stat) return false;
        return self::$legacy_files || ($stat['size'] === 0 && $sess_data !== '') ? self::write($sid, $sess_data) : touch(self::$path.'sess_'.$sid);
    }

    /**
     * 功能说明：非阻塞回收单目录的过期普通文件；跳过正在使用的会话和符号链接。
     *
     * @param string $path 扫描目录
     * @param int $maxlifetime 有效秒数
     * @return void 无返回值。
     */
    protected static function cleanDirectory($path, $maxlifetime) {
        foreach(glob(rtrim($path, '/').'/sess_*') ?: [] as $file) {
            if(is_link($file) || !is_file($file)) continue;
            $handle = @fopen($file, 'r+b');
            if($handle === false) continue;
            if(flock($handle, LOCK_EX | LOCK_NB)) {
                $stat = fstat($handle);
                if(self::sameFile($handle, $file) && $stat['mtime'] + $maxlifetime <= time()) @unlink($file);
                flock($handle, LOCK_UN);
            }
            fclose($handle);
        }
    }

    /**
     * 功能说明：回收稳定目录及历史日期目录；缺失目录视为无需清理。
     *
     * @param int $maxlifetime 过期秒数，负值按0处理
     * @return bool 处理器报告的回收结果；文件处理器可能跳过忙文件，true不表示所有过期文件均已删除，也不是删除条数
     */
    public static function gc($maxlifetime) {
        if(!empty($GLOBALS['no_log']) || !is_string(self::$path) || !is_dir(self::$path)) return true;
        $maxlifetime = max(0, (int)$maxlifetime);
        self::cleanDirectory(self::$path, $maxlifetime);
        $root = preg_match('/^\d{8}$/D', basename(self::$path)) ? dirname(rtrim(self::$path, '/')) : self::$path;
        foreach(glob(rtrim($root, '/').'/[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]', GLOB_ONLYDIR) ?: [] as $directory) {
            if(is_link($directory)) continue;
            self::cleanDirectory($directory, $maxlifetime);
            $entries = @scandir($directory);
            if(is_array($entries) && count($entries) === 2) @rmdir($directory);
        }
        return true;
    }
}
