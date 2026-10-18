<?PHP

/**
 * 文件：lib/session/sess_mysql.class.php
 * 功能说明：基于MySQL的Session处理器
 *   提供InnoDB会话存储、参数化查询、命名锁及严格ID验证/过期回收。
 *
 * 方法目录：
 *   public static sess_mysql::set($setting) — 设置独立数据库连接和存储选项；不使用业务连接或持久连接。
 *   public static sess_mysql::getLastError() — 返回最近的安全错误摘要，不含凭据、SQL或会话正文。
 *   protected static sess_mysql::execute($sql, array $values = [], $row = false) — 执行原生参数化语句并关闭statement；SQL参数不经字符串拼接。
 *   protected static sess_mysql::connection() — 提供给派生处理器复用的专属连接；不得用于业务事务。
 *   private static sess_mysql::connect() — 建立有限超时的独立连接，校验表名及字符集；连接失败不泄露认证信息。
 *   public static sess_mysql::open($sess_path, $sess_name) — 打开存储并检查旧表结构；不会在普通请求中ALTER已有表。
 *   public static sess_mysql::upgradeSchema() — 显式升级现有表，保留原始数据并移除255字节/SID32位及大小写混淆限制。
 *   private static sess_mysql::lock($sid) — 校验会话ID并获取命名锁，覆盖read至close；超时返回false，防止并发丢写。
 *   private static sess_mysql::unlock() — 释放当前ID的命名锁，ID轮换时允许锁定新ID。
 *   public static sess_mysql::close() — 释放命名锁并关闭连接，不重复触发概率GC；可重复调用。
 *   public static sess_mysql::read($sid) — 读取未过期数据；SQL失败返回false而非伪装为空会话。
 *   public static sess_mysql::write($sid, $sess_data) — 保存会话并按实际写入时间续期；保留引号、NUL、中文和尾部空格。
 *   public static sess_mysql::destroy($sid) — 删除指定会话；始终绑定ID，成功后释放该ID的锁。
 *   public static sess_mysql::validateId($sid) — 严格模式只接受数据库中未过期的现有ID。
 *   public static sess_mysql::updateTimestamp($sid, $sess_data) — 惰性续期：只更新到期时间；若记录已被GC删除则用PHP提供的数据恢复。
 *   public static sess_mysql::gc($maxlifetime) — 按绝对到期时间回收；不再重复减去maxlifetime，不关闭仍在使用的连接。
 *
 * 使用说明：
 *   1. 经myReq配置数据库和Session设置后启动，表与连接由处理器管理。
 *   2. 认证会话需要可靠锁和严格验证；后端故障通过失败返回及安全错误摘要表达。
 *   3. 已有表会检查结构和容量，不在普通请求中擅自迁移旧表。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名保持PHP 7.4兼容，PHPDoc类型用于说明，不额外强制运行时类型。
 */
/**
 * 功能说明：提供InnoDB会话存储、参数化查询、命名锁及严格ID验证/过期回收。
 *
 * 使用说明：
 *   1. 经myReq配置数据库和Session设置后启动，表与连接由处理器管理。
 *   2. 认证会话需要可靠锁和严格验证；后端故障通过失败返回及安全错误摘要表达。
 *   3. 已有表会检查结构和容量，不在普通请求中擅自迁移旧表。
 *
 * 补充说明：
 * MySQL存储SESSION类。
 * 使用InnoDB/MEDIUMBLOB保存原始字节，GET_LOCK串行化同ID请求；独立连接
 * 避免业务事务影响会话。旧HEAP/CHAR表须在维护阶段显式upgradeSchema()。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 */
class sess_mysql implements interface_session {
    private static $setting = [], $cnt = null, $table = 'my_session', $locked = '', $error = '', $validation_failed = false;

    /**
     * 功能说明：设置独立数据库连接和存储选项；不使用业务连接或持久连接。
     *
     * @param array $setting host/user/password/name/charset，以及session_table、
     * session_lock_timeout（默认10秒，0~60）、session_max_bytes（默认1MiB，最大16777215）
     * @return bool 活动连接期间拒绝重配
     */
    public static function set($setting) {
        if(self::$cnt !== null || !is_array($setting)) return false;
        self::$setting = $setting;
        return true;
    }

    /**
     * 功能说明：返回最近的安全错误摘要，不含凭据、SQL或会话正文。
     *
     * 参数说明：无显式参数。
     *
     * @return string 最近一次安全错误摘要；无已记录错误时为空串
     */
    public static function getLastError() { return self::$error; }

    /**
     * 功能说明：执行原生参数化语句并关闭statement；SQL参数不经字符串拼接。
     *
     *
     * 异常处理：内部捕获连接/SQL执行异常并返回false，错误摘要保存在对应getLastError状态；调用方不应仅依赖catch判断失败。
     *
     * @param string $sql 仅包含可信表名和占位符
     * @param array $values 绑定的字符串值（默认：[]）
     * @param bool $row 是否返回首行（默认：false）
     * @return array|null|bool 读取无记录返回null，失败返回false
     */
    protected static function execute($sql, array $values = [], $row = false) {
        if(self::$cnt === null) return false;
        $stmt = null;
        try {
            $stmt = @self::$cnt->prepare($sql);
            if(!$stmt) throw new RuntimeException();
            if($values && !$stmt->bind_param(str_repeat('s', count($values)), ...$values)) throw new RuntimeException();
            if(!@$stmt->execute()) throw new RuntimeException();
            if(!$row) return true;
            $result = $stmt->get_result();
            if(!$result) throw new RuntimeException();
            try { return $result->fetch_assoc(); } finally { $result->free(); }
        } catch(Throwable $e) {
            self::$error = 'Session database operation failed ('.(int)self::$cnt->errno.').';
            return false;
        } finally {
            if($stmt) $stmt->close();
        }
    }

    /**
     * 功能说明：提供给派生处理器复用的专属连接；不得用于业务事务。
     *
     * 参数说明：无显式参数。
     *
     * @return mysqli|null 当前处理器专属连接，尚未建立或已关闭为null；借用者不得关闭连接或开启业务事务
     */
    protected static function connection() { return self::$cnt; }

    /**
     * 功能说明：建立有限超时的独立连接，校验表名及字符集；连接失败不泄露认证信息。
     *
     * 参数说明：无显式参数。
     *
     *
     * 异常处理：内部捕获连接/SQL执行异常并返回false，错误摘要保存在对应getLastError状态；调用方不应仅依赖catch判断失败。
     *
     * @return bool 独立连接和字符集设置成功为true；配置或连接失败为false，摘要见getLastError
     * @internal 框架内部实现入口；应用优先调用对应公开方法
     */
    private static function connect() {
        self::$error = '';
        $s = self::$setting;
        self::$table = $s['session_table'] ?? 'my_session';
        if(!is_string(self::$table) || !preg_match('/^[a-zA-Z0-9_]{1,64}$/D', self::$table)) {
            self::$error = 'Invalid session table name.'; return false;
        }
        if(!extension_loaded('mysqli') || empty($s['host']) || empty($s['user']) || empty($s['name'])) {
            self::$error = 'Missing session database configuration or mysqli extension.'; return false;
        }
        $charset = strtolower(str_replace('-', '', $s['charset'] ?? 'utf8mb4'));
        if($charset === 'utf8') $charset = 'utf8mb4';
        $cnt = null;
        try {
            $host = strpos($s['host'], 'p:') === 0 ? substr($s['host'], 2) : $s['host'];
            $cnt = mysqli_init();
            $cnt->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
            if(!@$cnt->real_connect($host, $s['user'], $s['password'] ?? '', $s['name'], (int)($s['port'] ?? ini_get('mysqli.default_port')), $s['socket'] ?? null)
                || !@$cnt->set_charset($charset)) throw new RuntimeException();
            self::$cnt = $cnt;
            return true;
        } catch(Throwable $e) {
            if($cnt) @$cnt->close();
            self::$error = 'Session database connection failed.';
            return false;
        }
    }

    /**
     * 功能说明：打开存储并检查旧表结构；不会在普通请求中ALTER已有表。
     *
     * @param string $sess_path 协议参数，数据库模式忽略
     * @param string $sess_name Cookie名称，表隔离由session_table控制
     * @return bool 存储初始化成功为true，目录/连接/结构准备失败为false
     */
    public static function open($sess_path, $sess_name) {
        self::close();
        self::$validation_failed = false;
        if(!empty($GLOBALS['no_log'])) return true;
        if(!self::connect()) return false;
        $table = '`'.self::$table.'`';
        $inspect = 'SELECT t.ENGINE,c.DATA_TYPE,c.CHARACTER_MAXIMUM_LENGTH,s.CHARACTER_MAXIMUM_LENGTH AS sid_length,s.COLLATION_NAME AS sid_collation FROM information_schema.TABLES t JOIN information_schema.COLUMNS c ON c.TABLE_SCHEMA=t.TABLE_SCHEMA AND c.TABLE_NAME=t.TABLE_NAME AND c.COLUMN_NAME=? JOIN information_schema.COLUMNS s ON s.TABLE_SCHEMA=t.TABLE_SCHEMA AND s.TABLE_NAME=t.TABLE_NAME AND s.COLUMN_NAME=? WHERE t.TABLE_SCHEMA=DATABASE() AND t.TABLE_NAME=?';
        $meta = self::execute($inspect, ['value','SID',self::$table], true);
        // 已有合格表只做结构检查，不重复DDL；运行账号无需CREATE权限。
        if($meta === null && self::execute('CREATE TABLE IF NOT EXISTS '.$table.' (`SID` varchar(128) CHARACTER SET ascii COLLATE ascii_bin NOT NULL, `expiration` bigint unsigned NOT NULL, `value` mediumblob NOT NULL, PRIMARY KEY (`SID`), INDEX `expiration` (`expiration`)) ENGINE=InnoDB')) {
            $meta = self::execute($inspect, ['value','SID',self::$table], true);
        }
        $ok = is_array($meta) && strcasecmp($meta['ENGINE'], 'InnoDB') === 0 && in_array($meta['DATA_TYPE'], ['mediumblob','longblob'], true) && (int)$meta['sid_length'] >= 128 && $meta['sid_collation'] === 'ascii_bin';
        if(!$ok && is_array($meta)) self::$error = 'Legacy session schema: run sess_mysql::upgradeSchema() during maintenance.';
        if(!$ok && self::$error === '') self::$error = 'Invalid session table schema.';
        if(!$ok) self::close();
        return $ok;
    }

    /**
     * 功能说明：显式升级现有表，保留原始数据并移除255字节/SID32位及大小写混淆限制。
     *
     * 须在停止并发会话请求的维护阶段调用，账号需有ALTER权限；不自动回滚DDL。
     *
     * 参数说明：无显式参数。
     *
     * @return bool 表不存在或连接活动时失败；新表由open创建
     */
    public static function upgradeSchema() {
        if(self::$cnt !== null || !self::connect()) return false;
        try {
            return self::execute('ALTER TABLE `'.self::$table.'` ENGINE=InnoDB, MODIFY `SID` varchar(128) CHARACTER SET ascii COLLATE ascii_bin NOT NULL, MODIFY `expiration` bigint unsigned NOT NULL, MODIFY `value` mediumblob NOT NULL');
        } finally { self::close(); }
    }

    /**
     * 功能说明：校验会话ID并获取命名锁，覆盖read至close；超时返回false，防止并发丢写。
     *
     * @param string $sid 会话ID
     * @return bool 取得指定ID命名锁为true，非法ID、超时或后端失败为false
     * @internal 框架内部实现入口；应用优先调用对应公开方法
     */
    private static function lock($sid) {
        if(!is_string($sid) || preg_match('/^[a-zA-Z0-9,-]{1,128}$/D', $sid) !== 1 || self::$cnt === null) return false;
        $key = hash('sha256', strtolower(self::$setting['name'])."\0".strtolower(self::$table)."\0".$sid);
        if(self::$locked === $key) return true;
        if(!self::unlock()) return false;
        $wait = max(0, min(60, (int)(self::$setting['session_lock_timeout'] ?? 10)));
        $row = self::execute('SELECT GET_LOCK(?, ?) AS acquired', [$key, (string)$wait], true);
        if(!is_array($row) || (int)$row['acquired'] !== 1) { self::$error = 'Session lock unavailable.'; return false; }
        self::$locked = $key;
        return true;
    }

    /**
     * 功能说明：释放当前ID的命名锁，ID轮换时允许锁定新ID。
     *
     * 参数说明：无显式参数。
     *
     * @return bool 当前锁释放结果；没有持锁时按实现视为已释放
     * @internal 框架内部实现入口；应用优先调用对应公开方法
     */
    private static function unlock() {
        if(self::$locked === '') return true;
        $key = self::$locked;
        self::$locked = '';
        $row = self::execute('SELECT RELEASE_LOCK(?) AS released', [$key], true);
        return is_array($row) && (int)$row['released'] === 1;
    }

    /**
     * 功能说明：释放命名锁并关闭连接，不重复触发概率GC；可重复调用。
     *
     * 参数说明：无显式参数。
     *
     * @return bool 连接及锁释放后的结果；内置实现允许重复关闭
     */
    public static function close() {
        $ok = self::unlock();
        if(self::$cnt !== null) {
            try { $ok = self::$cnt->close() && $ok; }
            catch(Throwable $e) { $ok = false; }
            self::$cnt = null;
        }
        return $ok;
    }

    /**
     * 功能说明：读取未过期数据；SQL失败返回false而非伪装为空会话。
     *
     * @param string $sid 会话ID
     * @return string|false 有效会话的原始序列化字节；记录不存在/过期为空串，后端或加锁失败为false
     */
    public static function read($sid) {
        if(!empty($GLOBALS['no_log'])) return '';
        if(self::$validation_failed) return false;
        if(!self::lock($sid)) return false;
        $row = self::execute('SELECT `value` FROM `'.self::$table.'` WHERE `SID`=? AND `expiration`>?', [$sid, (string)time()], true);
        return $row === false ? false : ($row['value'] ?? '');
    }

    /**
     * 功能说明：保存会话并按实际写入时间续期；保留引号、NUL、中文和尾部空格。
     *
     * @param string $sid 会话ID
     * @param string $sess_data PHP编码的原始字节串
     * @return bool 完整会话字节及期限保存成功为true；I/O、锁或容量失败为false
     */
    public static function write($sid, $sess_data) {
        if(!empty($GLOBALS['no_log'])) return true;
        $max = max(1, min(16777215, (int)(self::$setting['session_max_bytes'] ?? 1048576)));
        if(!is_string($sess_data) || strlen($sess_data) > $max) { self::$error = 'Session payload exceeds byte limit.'; return false; }
        if(!self::lock($sid)) return false;
        return self::execute('INSERT INTO `'.self::$table.'` (`SID`,`expiration`,`value`) VALUES(?,?,?) ON DUPLICATE KEY UPDATE `expiration`=VALUES(`expiration`),`value`=VALUES(`value`)', [$sid, (string)(time() + max(0, (int)ini_get('session.gc_maxlifetime'))), $sess_data]);
    }

    /**
     * 功能说明：删除指定会话；始终绑定ID，成功后释放该ID的锁。
     *
     * @param string $sid 会话ID
     * @return bool 目标会话删除及相关一致性处理成功为true，失败为false
     */
    public static function destroy($sid) {
        if(!empty($GLOBALS['no_log'])) return true;
        if(!self::lock($sid)) return false;
        $ok = self::execute('DELETE FROM `'.self::$table.'` WHERE `SID`=?', [$sid]);
        return self::unlock() && $ok;
    }

    /**
     * 功能说明：严格模式只接受数据库中未过期的现有ID。
     *
     * @param string $sid 客户端提交的会话ID
     * @return bool 当前ID实际存在且未过期为true；非法、未知、过期或检查失败为false
     */
    public static function validateId($sid) {
        if(!empty($GLOBALS['no_log']) || !is_string($sid) || preg_match('/^[a-zA-Z0-9,-]{1,128}$/D', $sid) !== 1) return false;
        if(!self::lock($sid)) { self::$validation_failed = true; return false; }
        $row = self::execute('SELECT 1 AS present FROM `'.self::$table.'` WHERE `SID`=? AND `expiration`>?', [$sid, (string)time()], true);
        if(is_array($row)) return true;
        // PHP只接受bool校验结果；记录存储故障，让随后read明确失败而非启动空会话。
        if($row === false) self::$validation_failed = true;
        if(!self::unlock()) self::$validation_failed = true;
        return false;
    }

    /**
     * 功能说明：惰性续期：只更新到期时间；若记录已被GC删除则用PHP提供的数据恢复。
     *
     * @param string $sid 会话ID
     * @param string $sess_data 未变化的数据
     * @return bool 当前会话有效期更新/必要时恢复写入成功为true，否则为false
     */
    public static function updateTimestamp($sid, $sess_data) {
        if(!empty($GLOBALS['no_log'])) return true;
        $max = max(1, min(16777215, (int)(self::$setting['session_max_bytes'] ?? 1048576)));
        if(!is_string($sess_data) || strlen($sess_data) > $max) { self::$error = 'Session payload exceeds byte limit.'; return false; }
        if(!self::lock($sid)) return false;
        // 已有行只改expiration，避免重写BLOB；缺失行由PHP提供的原始数据恢复。
        return self::execute('INSERT INTO `'.self::$table.'` (`SID`,`expiration`,`value`) VALUES(?,?,?) ON DUPLICATE KEY UPDATE `expiration`=VALUES(`expiration`)', [$sid, (string)(time() + max(0, (int)ini_get('session.gc_maxlifetime'))), $sess_data]);
    }

    /**
     * 功能说明：按绝对到期时间回收；不再重复减去maxlifetime，不关闭仍在使用的连接。
     *
     * @param int $maxlifetime PHP协议参数，记录已包含绝对到期时间
     * @return bool 本次过期回收是否成功；不返回删除条数，缺失存储可视为无需清理
     */
    public static function gc($maxlifetime) {
        if(!empty($GLOBALS['no_log'])) return true;
        return self::execute('DELETE FROM `'.self::$table.'` WHERE `expiration`<=?', [(string)time()]);
    }
}
