<?PHP

/**
 * 文件：lib/session/sess_mystep.class.php
 * 功能说明：CMS认证会话与在线统计处理器
 *   在独立InnoDB表保存权威会话，同时维护兼容user_online展示记录。
 *
 * 方法目录：
 *   public static sess_mystep::open($sess_path, $sess_name) — 从框架配置初始化独立会话表和在线统计；不自动改写旧user_online表结构。
 *   public static sess_mystep::close() — 释放专属数据库连接和锁，清空监控状态；GC不再负责关闭连接。
 *   public static sess_mystep::read($sid) — 读取认证数据，首次访问可迁移旧在线表中未过期且非空的会话。
 *   private static sess_mystep::sessionTable() — 获取已校验的独立存储表名，供兼容迁移查询使用。
 *   public static sess_mystep::write($sid, $sess_data) — 可靠保存所有请求的认证数据，再尽力刷新在线统计；统计失败不伪装为认证失败。
 *   private static sess_mystep::track($sid) — 按访客策略刷新在线摘要；统计ID使用32位摘要，兼容原CHAR(32)字段。
 *   private static sess_mystep::trackingData() — 生成255字节内的展示摘要，兼容原在线列表的PHP会话解码；不复制认证版本、
 *   public static sess_mystep::destroy($sid) — 删除认证及旧在线副本；旧副本删除失败时保留空会话屏障并报告失败，避免恢复旧身份。
 *   public static sess_mystep::validateId($sid) — 严格ID校验兼容首次迁移；必须实际存在有效会话，不能只检查ID格式。
 *   public static sess_mystep::updateTimestamp($sid, $sess_data) — 惰性写入也刷新认证期限及可选在线统计。
 *   public static sess_mystep::gc($maxlifetime = 1800) — 清理主会话和过期在线访客；保持连接与run状态，允许PHP随后继续write。
 *   public static sess_mystep::hasTrackingError() — 本次打开以来在线统计是否失败；不包含敏感SQL及会话内容。
 *
 * 使用说明：
 *   1. 由CMS会话配置选择；认证表与在线展示表分离，展示摘要不能作为登录凭据。
 *   2. 在线统计失败可单独查询，不应导致已保存的认证数据丢失。
 *   3. 不同应用/后端使用独立Session名称；no_log公开入口不启动/持久化会话。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名保持PHP 7.4兼容，PHPDoc类型用于说明，不额外强制运行时类型。
 */
require_once __DIR__.'/sess_mysql.class.php';
/**
 * 功能说明：在独立InnoDB表保存权威会话，同时维护兼容user_online展示记录。
 *
 * 使用说明：
 *   1. 由CMS会话配置选择；认证表与在线展示表分离，展示摘要不能作为登录凭据。
 *   2. 在线统计失败可单独查询，不应导致已保存的认证数据丢失。
 *   3. 不同应用/后端使用独立Session名称；no_log公开入口不启动/持久化会话。
 *
 * 补充说明：
 * 自定义SESSION存储类（含在线访客监控）。
 * 登录数据保存在{pre}session_data的独立InnoDB表中，在线统计继续使用原user_online表；
 * 同IP访客、爬虫及API请求的统计策略不再控制认证数据的保存。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 */
class sess_mystep extends sess_mysql {
    public static $cnt = null, $run = 0;
    private static $online = '', $storage = '', $tracking_error = false;

    /**
     * 功能说明：从框架配置初始化独立会话表和在线统计；不自动改写旧user_online表结构。
     *
     * @param string $sess_path 协议参数，忽略
     * @param string $sess_name Cookie名称
     * @return bool 存储初始化成功为true，目录/连接/结构准备失败为false
     */
    public static function open($sess_path, $sess_name) {
        self::$cnt = null;
        self::$run = 0;
        self::$tracking_error = false;
        parent::close();
        if(!empty($GLOBALS['no_log'])) return true;
        if(!isset($GLOBALS['ms_setting']->db)) return false;
        $setting = (array)$GLOBALS['ms_setting']->db;
        $pre = $setting['pre'] ?? '';
        if(!is_string($pre) || !preg_match('/^[a-zA-Z0-9_]*$/D', $pre) || strlen($pre) > 52) return false;
        self::$online = '`'.$pre.'user_online`';
        $setting['session_table'] = $setting['session_table'] ?? $pre.'session_data';
        self::$storage = $setting['session_table'];
        if(!parent::set($setting) || !parent::open($sess_path, $sess_name)) return false;
        self::$cnt = parent::connection();
        self::$run = 3; // 仅用于在线统计：1允许读旧记录，2允许刷新统计。
        $agent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
        if(strpos($agent, 'spider') !== false || strpos($agent, 'bot') !== false) self::$run = 0;
        $route = $GLOBALS['info_app']['path'][0] ?? '';
        if(in_array($route, ['api','module','ms_language','ms_setting','pack'], true)) self::$run &= 1;
        return true;
    }

    /**
     * 功能说明：释放专属数据库连接和锁，清空监控状态；GC不再负责关闭连接。
     *
     * 参数说明：无显式参数。
     *
     * @return bool 连接及锁释放后的结果；内置实现允许重复关闭
     */
    public static function close() {
        $ok = parent::close();
        self::$cnt = null;
        self::$run = 0;
        return $ok;
    }

    /**
     * 功能说明：读取认证数据，首次访问可迁移旧在线表中未过期且非空的会话。
     *
     * @param string $sid 会话ID
     * @return string|false 有效会话的原始序列化字节；记录不存在/过期为空串，后端或加锁失败为false
     */
    public static function read($sid) {
        $data = parent::read($sid);
        if(!empty($GLOBALS['no_log']) || $data === false || $data !== '') return $data;
        // 包括已过期记录：已迁移过的ID不得回退到旧表恢复身份。
        $exists = parent::execute('SELECT 1 AS present FROM `'.self::sessionTable().'` WHERE `SID`=?', [$sid], true);
        if($exists === false) return false;
        if(is_array($exists)) return '';
        $legacy = parent::execute('SELECT `data` FROM '.self::$online.' WHERE `sid`=? AND `refresh`>?', [$sid, (string)(time() - max(0, (int)ini_get('session.gc_maxlifetime')))], true);
        if($legacy === false) { self::$tracking_error = true; return ''; }
        if(!is_array($legacy) || $legacy['data'] === '' || $legacy['data'] === '0' || strpos($legacy['data'], '_mystep_online|') === 0) return '';
        if(!parent::write($sid, $legacy['data'])) return false;
        if(!parent::execute('UPDATE '.self::$online.' SET `data`=? WHERE `sid`=?', ['', $sid])) self::$tracking_error = true;
        return $legacy['data'];
    }

    /**
     * 功能说明：获取已校验的独立存储表名，供兼容迁移查询使用。
     *
     * 参数说明：无显式参数。
     *
     * @return string 包含经校验前缀的认证存储表名，不含任意用户SQL
     * @internal 框架内部实现入口；应用优先调用对应公开方法
     */
    private static function sessionTable() {
        return self::$storage;
    }

    /**
     * 功能说明：可靠保存所有请求的认证数据，再尽力刷新在线统计；统计失败不伪装为认证失败。
     *
     * @param string $sid 会话ID
     * @param string $sess_data PHP编码后的字节串
     * @return bool 完整会话字节及期限保存成功为true；I/O、锁或容量失败为false
     */
    public static function write($sid, $sess_data) {
        if(!parent::write($sid, $sess_data)) return false;
        return self::track($sid);
    }

    /**
     * 功能说明：按访客策略刷新在线摘要；统计ID使用32位摘要，兼容原CHAR(32)字段。
     *
     * @param string $sid 已由认证存储校验的会话ID
     * @return bool 统计作为尽力执行的附属功能，错误另由hasTrackingError报告
     * @internal 框架内部实现入口；应用优先调用对应公开方法
     */
    private static function track($sid) {
        if(!empty($GLOBALS['no_log']) || !(self::$run & 2)) return true;
        $https = function_exists('isHttps') ? isHttps() : (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $url = ($https ? 'https://' : 'http://').($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost').($_SERVER['REQUEST_URI'] ?? '/');
        // 旧表url为CHAR(200)，按字符截断，保留UTF-8完整性。
        $url = function_exists('mb_substr') ? mb_substr($url, 0, 198, 'UTF-8') : substr($url, 0, 198);
        $ip = class_exists('myReq', false) ? myReq::ip() : ($_SERVER['REMOTE_ADDR'] ?? '');
        $ok = parent::execute('REPLACE INTO '.self::$online.' (`sid`,`ip`,`refresh`,`url`,`data`) VALUES(?,?,?,?,?)', [md5($sid), substr((string)$ip, 0, 50), (string)time(), $url, self::trackingData()]);
        if(!$ok) self::$tracking_error = true;
        return true;
    }

    /**
     * 功能说明：生成255字节内的展示摘要，兼容原在线列表的PHP会话解码；不复制认证版本、
     *
     * CSRF令牌等会话私密字段。标记前缀阻止摘要作为旧认证数据迁移。
     *
     * 参数说明：无显式参数。
     *
     * @return string 最多255字节的在线统计会话摘要，保留php会话编码，不含完整认证数据
     * @internal 框架内部实现入口；应用优先调用对应公开方法
     */
    private static function trackingData() {
        $data = '_mystep_online|b:1;';
        if(session_status() !== PHP_SESSION_ACTIVE) return $data;
        foreach(['ms_cms_op'=>'ms_cms_group', 'ms_cms_user'=>'ms_cms_user_group'] as $key => $group) {
            if(!isset($_SESSION[$key]) || !is_string($_SESSION[$key])) continue;
            $user = function_exists('mb_substr') ? mb_substr($_SESSION[$key], 0, 32, 'UTF-8') : substr($_SESSION[$key], 0, 32);
            return $data.$key.'|'.serialize($user).$group.'|'.serialize((int)($_SESSION[$group] ?? 0));
        }
        return $data;
    }

    /**
     * 功能说明：删除认证及旧在线副本；旧副本删除失败时保留空会话屏障并报告失败，避免恢复旧身份。
     *
     * @param string $sid 会话ID
     * @return bool 目标会话删除及相关一致性处理成功为true，失败为false
     */
    public static function destroy($sid) {
        if(!empty($GLOBALS['no_log'])) return true;
        // parent::read先取得相同的命名锁，旧数据清理与主记录删除保持一个临界区。
        if(parent::read($sid) === false) return false;
        if(!parent::execute('DELETE FROM '.self::$online.' WHERE `sid`=? OR `sid`=?', [$sid, md5($sid)])) {
            self::$tracking_error = true;
            // 缺失统计表时没有可恢复的旧副本，但仍须销毁主认证数据。
            if(self::$cnt->errno === 1146) return parent::destroy($sid);
            // 保留空认证记录作为迁移屏障，避免旧在线副本恢复身份。
            parent::write($sid, '');
            return false;
        }
        return parent::destroy($sid);
    }

    /**
     * 功能说明：严格ID校验兼容首次迁移；必须实际存在有效会话，不能只检查ID格式。
     *
     * @param string $sid 客户端提交的会话ID
     * @return bool 当前ID实际存在且未过期为true；非法、未知、过期或检查失败为false
     */
    public static function validateId($sid) {
        if(parent::validateId($sid)) return true;
        if(self::read($sid) === false) return false;
        return parent::validateId($sid);
    }

    /**
     * 功能说明：惰性写入也刷新认证期限及可选在线统计。
     *
     * @param string $sid 会话ID
     * @param string $sess_data 未变化的原始数据
     * @return bool 当前会话有效期更新/必要时恢复写入成功为true，否则为false
     */
    public static function updateTimestamp($sid, $sess_data) {
        if(!parent::updateTimestamp($sid, $sess_data)) return false;
        return self::track($sid);
    }

    /**
     * 功能说明：清理主会话和过期在线访客；保持连接与run状态，允许PHP随后继续write。
     *
     * @param int $maxlifetime 过期秒数，默认1800（默认：1800）
     * @return bool 本次过期回收是否成功；不返回删除条数，缺失存储可视为无需清理
     */
    public static function gc($maxlifetime = 1800) {
        if(!empty($GLOBALS['no_log'])) return true;
        $ok = parent::gc($maxlifetime);
        if(self::$cnt && !parent::execute('DELETE FROM '.self::$online.' WHERE `refresh`<=?', [(string)(time() - max(0, (int)$maxlifetime))])) self::$tracking_error = true;
        return $ok;
    }

    /**
     * 功能说明：本次打开以来在线统计是否失败；不包含敏感SQL及会话内容。
     *
     * 参数说明：无显式参数。
     *
     * @return bool 本次open以来发生在线统计错误为true；不表示认证会话保存失败
     */
    public static function hasTrackingError() { return self::$tracking_error; }
}
