<?PHP
/********************************************
*                                           *
* Name    : MySQL Manager                   *
* Author  : Windy2000                       *
* Time    : 2003-05-10                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT HOLD THIS ITEM PLEASE.      *
*                                           *
********************************************/
/**
 * 文件：lib/database/mysql.class.php
 * 功能说明：MySQL数据库适配器
 *   结合mysqli、通用SQL构造及事务/缓存逻辑实现MySQL数据访问。
 *
 * 方法目录：
 *   public MySQL::__construct() — 按可选位置参数设置驱动，构造本身不建立 MySQL 连接
 *   public MySQL::init($host, $user, $pwd, $charset='utf8', array $options=[]) — 保存连接设置并使用 MySQL 反引号定界符，不建立或重建连接
 *   public MySQL::connect($pconnect = false, $the_db = null) — 建立新连接、应用字符集和配置的会话 SQL 模式，并重置数据库/缓存/事务跟踪状态
 *   public MySQL::reconnect($pconnect = 'keep', $the_db = null) — 关闭当前连接并重新连接，默认保留连接模式及所选数据库
 *   public MySQL::selectDB($the_db) — 选择数据库，成功后更新当前库及清空查询构建状态
 *   public MySQL::setCharset($charset='') — 设置字符集
 *   public MySQL::safeValue($value) — 按活动连接字符集转义标量文本，未连接时采用历史独立转义规则
 *   public MySQL::changUser($user, $pwd, $db=null) — 通过 mysqli 切换用户及数据库，只有成功时才更新驱动身份状态
 *   public MySQL::query($sql, $reset = true) — 执行完整 SQL，保留结果集供 getRS() 使用并更新共享缓存/事务状态
 *   public MySQL::execute($sql, array $params = [], $types = '') — 以原生预处理及参数绑定执行 SQL，不自动重试或使用共享结果缓存
 *   public MySQL::getRS($mode = 2) — 取得结果集
 *   public MySQL::getData($line=0, $field='') — 获取结果集某一行某一列
 *   public MySQL::getFields($the_tbl, $the_db='') — 获取某一数据表的所有列
 *   public MySQL::getCreateScript($the_tbl, $the_db='') — 导出原生建表 SQL，保留索引、默认值及约束
 *   public MySQL::getDataScript($the_tbl, $the_db='') — 导出可回放 INSERT，使用十六进制字符串保留引号、换行、二进制及字符集字节
 *   public MySQL::getIdxScript($the_tbl, $the_db='') — 取得设置索引项的脚本
 *   public MySQL::getDBs() — 取得所有数据库名
 *   public MySQL::getTbls($the_db='', $pattern='', $detail=false) — 取得所有表名，及详细信息
 *   public MySQL::getPri($the_tbl, $the_db='') — 取得主键名
 *   public MySQL::getInsertId() — 取得新插入的自增ID
 *   public MySQL::optTabs() — 列出当前数据库的表并逐一执行 OPTIMIZE TABLE
 *   public MySQL::getStat() — 取得数据库当前状态
 *   public MySQL::getProcesses($mode=0, $time_limit=0) — 取得当前进程
 *   public MySQL::replace($show = false) — 更新替换数据
 *   public MySQL::batchExe($SQLs) — 批量执行查询语句
 *   public MySQL::handleSQL($strSQL) — 按引号及注释边界分割脚本，保留数据中的空白/分号，支持独立行 DELIMITER
 *   public MySQL::file($file, $find = array(), $replace = array()) — 执行数据查询文件
 *   public MySQL::check($obj = 'connect', $type = 'object') — 检查本地连接/结果属性的类型，不通过探测查询或隐式重连掩盖故障
 *   public MySQL::free() — 释放当前 mysqli_result 并将结果引用清空，可重复调用
 *   public MySQL::close(&$err_info = array()) — 释放结果和连接，并重置事务、会话、临时表及构建状态
 *   public MySQL::checkError(&$err_info = array()) — 读取当前 mysqli 错误并刷新驱动错误状态，成功后移除残留旧错误
 *   public MySQL::clearError() — 清空驱动保存的错误标志及说明，不查询或重建连接
 *   protected MySQL::error($str, $exit=false) — 错误处理
 *
 * 使用说明：
 *   1. 通常经myDb选择MySQL驱动，再connect选择数据库。
 *   2. execute/fetch*使用绑定值；复杂结构操作和导入导出需按MySQL语义使用。
 *   3. 查询缓存及事务公共实现来自myDb.class.php；连接丢失不自动重放写入。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名以PHP 8.5.12为运行基线，PHPDoc类型用于说明，不额外强制运行时类型。
 */


/**
 * 功能说明：结合mysqli、通用SQL构造及事务/缓存逻辑实现MySQL数据访问。
 *
 * 使用说明：
 *   1. 通常经myDb选择MySQL驱动，再connect选择数据库。
 *   2. execute/fetch*使用绑定值；复杂结构操作和导入导出需按MySQL语义使用。
 *   3. 查询缓存及事务公共实现来自myDb.class.php；连接丢失不自动重放写入。
 *
 * 补充说明：
 * MySQL 驱动，使用 mysqli 执行普通 SQL、参数绑定及共享构建/事务接口
 *
 *     $mysql = new MySQL($host, $user, $pwd, 'utf8mb4'); // 仅设置连接参数
 *     $mysql->connect(false, $database);                // 建立连接及选择库
 *     $mysql->execute('UPDATE items SET value=? WHERE id=?', [$value, $id]);
 *     $rows = $mysql->fetchAll('SELECT * FROM items WHERE id=?', [$id]);
 *     $mysql->transaction(function ($tx) { ... });      // 继承 base_db，支持嵌套保存点
 *
 * 使用说明：
 * 1. 新连接沿用服务端 wait_timeout，不再强制改为 3 秒；初始化仍沿用历史 sql_mode 设置。
 * 2. query() 只在已筛选的无事务/会话依赖 SELECT 遭遇 2006 断线时重连并重试一次；
 *    不重放写入。execute() 不自动重试，也不使用共享查询结果缓存。
 * 3. 参数化结果读取需要 mysqlnd；参数只能代替数据值，标识符与 SQL 片段由应用限定。
 * 4. record/records/result/count/fetchAll/One/Value 由 base_sql 提供；缓存和事务由
 *    base_db 提供。直接使用原生连接会绕过事务状态及共享缓存失效跟踪。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 *
 * @example ../../app/Document/module/mysql.php 框架现有演示，仅作为文档引用，不在检查时执行
 */
class MySQL extends myBase implements interface_db, interface_sql, interface_db_prepared, interface_db_transaction {
    use base_db, base_sql;

    protected
        $session_sql_mode = '',
        $persistent = false,
        $err = false,
        $err_info = '',
        $count = 0;

    /**
     * 功能说明：按可选位置参数设置驱动，构造本身不建立 MySQL 连接
     *
     * @param mixed ...$args 按init声明顺序传入的连接设置；构造不立即连接（历史位置参数，由func_get_args读取）
     * @param string $host 历史位置参数说明：可选第一参数，服务器地址，传入时须同时提供 user/pwd
     * @param string $user 历史位置参数说明：数据库用户名，传给 init()
     * @param string $pwd 历史位置参数说明：数据库密码，传给 init()
     * @param string $charset 历史位置参数说明：字符集，默认 utf8；建议明确使用 utf8mb4
     * @param array $options 历史位置参数说明：可选 sql_mode 策略，转给 init
     * @return void 初始化或释放资源，无返回值。
     */
    public function __construct() {
        if(count(func_get_args())>0) call_user_func_array(array($this, "init"), func_get_args());
    }

    /**
     * 功能说明：保存连接设置并使用 MySQL 反引号定界符，不建立或重建连接
     *
     * @param string $host mysqli 服务器地址
     * @param string $user 数据库用户名
     * @param string $pwd 数据库密码
     * @param string $charset 字符集名称，默认 utf8；连接时由 setCharset() 应用（默认：'utf8'）
     * @param array $options sql_mode 默认空串兼容旧模式；null 保留服务器默认，字符串明确设置会话模式（默认：[]）
     * @return void 修改活动连接的初始化设置后，调用方须明确决定是否重新连接
     * @throws InvalidArgumentException 连接选项或sql_mode设置非法
     */
    public function init($host, $user, $pwd, $charset='utf8', array $options=[]) {
        $mode=array_key_exists('sql_mode',$options)?$options['sql_mode']:'';
        if($mode!==null&&!is_string($mode))throw new InvalidArgumentException('sql_mode must be a string or null');
        $this->session_sql_mode=$mode;
        $this->host = $host;
        $this->user = $user;
        $this->pwd = $pwd;
        $this->charset = $charset;
        $this->delimiter = '`';
    }

    /**
     * 功能说明：建立新连接、应用字符集和配置的会话 SQL 模式，并重置数据库/缓存/事务跟踪状态
     *
     * @param bool $pconnect true 通过 p: 主机前缀使用 mysqli 持久连接，默认 false（默认：false）
     * @param string|null $the_db 非空时连接后选择该库；null 不主动选择（默认：null）
     * @return bool 建连流程成功为 true；失败按框架错误策略处理并可返回 false
     */
    public function connect($pconnect = false, $the_db = null) {
        if($this->connect instanceof mysqli)$this->close();
        $this->db='';
        $this->persistent = $pconnect;
        try {
            $this->connect = @mysqli_connect(($pconnect?'p:':'').$this->host, $this->user, $this->pwd);
        } catch(mysqli_sql_exception $error) { $this->connect = false; }
        $this->sql = 'none (Connect to MySQL Server)';
        if(mysqli_connect_errno()) {
            $this->err_info = mysqli_connect_errno().' - '.mysqli_connect_error();
            $this->err = true;
            $this->error('Could not connect to MySQL Server');
            return false;
        }
        if($this->session_sql_mode!==null && version_compare(mysqli_get_server_info($this->connect), '5.0.1', '>')) {
            $this->execute('SET SESSION sql_mode=?',[$this->session_sql_mode]);
        }
        $this->setCharset();
        $this->cache_transaction = $this->cache_manual = $this->cache_dirty = $this->cache_session = false;
        $this->cache_temporary = []; $this->managed_depth = 0; $this->cache_savepoints=[]; $this->cache_savepoint_root=false;
        if(!empty($the_db) && !$this->selectDB($the_db))return false;
        return true;
    }

    /**
     * 功能说明：关闭当前连接并重新连接，默认保留连接模式及所选数据库
     *
     * 不会重放事务或业务 SQL，也不会恢复 SET、临时表等会话状态。
     *
     * @param bool|string $pconnect keep 沿用原持久连接设置；true/false 显式指定（默认：'keep'）
     * @param string|null $the_db null 沿用此前所选数据库，非空字符串明确选择新库（默认：null）
     * @return bool connect() 的返回值，不含旧有的固定延迟重试
     */
    public function reconnect($pconnect = 'keep', $the_db = null) {
        if($pconnect === 'keep') $pconnect = $this->persistent;
        if($the_db === null) $the_db = $this->db;
        $this->close();
        return $this->connect($pconnect, $the_db);
    }

    /**
     * 功能说明：选择数据库，成功后更新当前库及清空查询构建状态
     *
     * @param string $the_db 数据库名称，作为 mysqli_select_db() 参数而非拼接 SQL
     * @return bool 成功为 true；无连接或选择失败为 false，失败不更新当前库标识
     */
    public function selectDB($the_db) {
        if(!$this->check()) return false;
        $this->clearError();
        try { $selected = mysqli_select_db($this->connect, $the_db); }
        catch(mysqli_sql_exception $error) { $selected = false; }
        if(!$selected) { $this->checkError(); $this->error('Could not select the database'); return false; }
        $this->db = $the_db; $this->build('[reset]');
        $this->sql = 'none (Select Database)';
        return true;
    }

    /**
     * 功能说明：设置字符集
     *
     * @param string $charset 字符编码名称，例如UTF-8；是否自动检测由具体入口决定（默认：''）
     * @return string 连接实际采用的字符集名称，不只是传入值；失败按驱动错误策略处理
     */
    public function setCharset($charset='') {
        if(empty($charset)) $charset = $this->charset;
        if(strtolower($charset)=='utf-8') {
            if(version_compare(mysqli_get_server_info($this->connect), '5.5.3', '>')) {
                $charset = 'utf8mb4';
            } else {
                $charset = 'utf8';
            }
        }
        try { $changed = mysqli_set_charset($this->connect, $charset); }
        catch(mysqli_sql_exception $error) { $changed = false; }
        if(!$changed || $this->checkError()) {
            $this->checkError();$this->error('Unknown character set');
            return mysqli_character_set_name($this->connect);
        }
        $this->charset = $charset;
        return mysqli_character_set_name($this->connect);
    }

    /**
     * 功能说明：按活动连接字符集转义标量文本，未连接时采用历史独立转义规则
     *
     * @param mixed $value 标量值；布尔先转整数，最终转字符串；二进制或外部输入优先绑定
     * @return string 转义后的文本，不含两端 SQL 引号；不能用来转义表名或任意 SQL 片段
     */
    public function safeValue($value) {
        if(is_bool($value)) $value = (int)$value;
        if($this->connect instanceof mysqli) return mysqli_real_escape_string($this->connect, (string)$value);
        $search = ['\\', "\n", "\r", "'", '"', "\x1a", "\0"];
        return str_replace($search, ['\\\\', '\\n', '\\r', "\\'", '\\"', '\\x1a', '\\0'], (string)$value);
    }

    /**
     * 功能说明：通过 mysqli 切换用户及数据库，只有成功时才更新驱动身份状态
     *
     * @param string $user 新用户名
     * @param string $pwd 新密码
     * @param string|null $db 新库名；null 沿用当前库（默认：null）
     * @return bool 成功为 true，并清空构建器、事务/临时表/会话跟踪；失败保留原身份记录
     */
    public function changUser($user, $pwd, $db=null) {
        if(!$this->check()) return false;
        if($db === null) $db = $this->db;
        try { $changed = mysqli_change_user($this->connect, $user, $pwd, $db); }
        catch(mysqli_sql_exception $error) { $changed = false; }
        if(!$changed) { $this->checkError(); return false; }
        $this->user = $user; $this->pwd = $pwd; $this->db = $db;
        $this->setCharset();
        if($this->session_sql_mode!==null)$this->execute('SET SESSION sql_mode=?',[$this->session_sql_mode]);
        $this->cache_transaction = $this->cache_manual = $this->cache_dirty = $this->cache_session = false;
        $this->cache_temporary = []; $this->managed_depth = 0; $this->cache_savepoints=[]; $this->cache_savepoint_root=false; $this->build('[reset]');
        return true;
    }

    /**
     * 功能说明：执行完整 SQL，保留结果集供 getRS() 使用并更新共享缓存/事务状态
     *
     * 执行前释放旧结果并清空旧错误。只有符合 isCacheable() 保守条件的 SELECT 在
     * 错误码 2006 时允许重连一次；事务、会话相关查询和写入均不自动重放。
     *
     * @param string $sql 非空完整 SQL，由调用方提供可信模板及正确数据处理
     * @param bool $reset 是否清空现有构建器，默认 true；只影响构建器，不控制结果释放（默认：true）
     * @return int|false SELECT 返回结果行数，其他语句返回受影响行数；失败为 false
     * @throws InvalidArgumentException SQL 不是非空字符串
     * @throws Throwable 框架错误处理或 mysqli 抛出的异常可继续向外传递
     */
    public function query($sql, $reset = true) {
        if(!$this->check()) return false;
        if(!is_string($sql) || trim($sql) === '') throw new InvalidArgumentException('SQL must be a non-empty string');
        $this->guardTransaction($sql);
        if($reset) $this->build('[reset]');
        $this->free(); $this->clearError(); $this->count++;
        $retry = 0;
        do {
            $this->sql = $sql;
            try { $this->result = @mysqli_query($this->connect, $sql); }
            catch(mysqli_sql_exception $error) { $this->result = false; }
            $errno = mysqli_errno($this->connect);
            if($this->result !== false) {
                $rows = $this->result instanceof mysqli_result;
                $count = $rows ? mysqli_num_rows($this->result) : mysqli_affected_rows($this->connect);
                $this->noteStatement($sql, $rows);
                if(!$rows) $this->free();
                return $count;
            }
            // Never replay writes, session-dependent queries or a lost transaction.
            if($errno === 2006 && $retry++ === 0 && $this->isCacheable($sql)) {
                if($this->reconnect()) continue;
            }
            $this->checkError(); $this->error('Error Occur in Query !');
            return false;
        } while(true);
    }

    /**
     * 功能说明：以原生预处理及参数绑定执行 SQL，不自动重试或使用共享结果缓存
     *
     * 按 PHP 类型推断绑定类型，null 保持 SQL NULL；流参数使用分块 send_long_data()。
     * 结果集保留给 getRS()/fetch*()，预处理语句在 finally 中关闭，成功后跟踪缓存失效。
     *
     *
     * 流所有权：BLOB从当前流位置读取到末尾，不负责rewind或关闭调用方的流；statement在finally关闭，读取结果集由驱动free/后续读取入口释放。
     *
     * @param string $sql 非空 SQL 模板，使用 ? 占位符；一个占位符只代替一个数据值
     * @param array $params 按出现顺序的标量、null 或可读取二进制流，数组键不作为参数名称（默认：[]）
     * @param string $types 可选 mysqli 类型串 i/d/s/b，长度须等于参数数目；空字符串自动推断；
     *                      b 可接收文本或流，流从当前位置读取；null 仍表示 SQL NULL（默认：''）
     * @return int|false 读查询为结果行数，写查询为受影响行数；无连接或失败返回 false
     * @throws InvalidArgumentException SQL、参数数目/类型、非有限浮点数或类型串非法
     * @throws RuntimeException 读取结果需要 mysqlnd 而当前未提供，或 BLOB 流读取失败/无进展
     * @throws Throwable 驱动或框架错误策略产生的异常继续传递
     * @phpstan-param array<array-key,scalar|null|resource> $params
     */
    public function execute($sql, array $params = [], $types = '') {
        if(!$this->check()) return false;
        if(!is_string($sql) || trim($sql) === '') throw new InvalidArgumentException('SQL must be a non-empty string');
        $this->guardTransaction($sql);
        $this->build('[reset]'); $this->free(); $this->clearError();
        $this->sql = $sql; $this->count++;
        $statement = null;
        try {
            $statement = mysqli_prepare($this->connect, $sql);
            if(!$statement) { $this->checkError(); $this->error('Cannot prepare query'); return false; }
            $values = array_values($params);
            if(mysqli_stmt_param_count($statement) !== count($values)) throw new InvalidArgumentException('SQL parameter count mismatch');
            if($types === '') foreach($values as $value) $types .= is_int($value) || is_bool($value) ? 'i' : (is_float($value) ? 'd' : (is_resource($value) ? 'b' : 's'));
            if(strlen($types) !== count($values) || preg_match('/[^idsb]/', $types)) throw new InvalidArgumentException('Invalid SQL parameter types');
            $blobs = [];
            foreach($values as $index => &$value) {
                if(!is_scalar($value) && $value !== null && !is_resource($value)) throw new InvalidArgumentException('SQL parameters must be scalar, null or streams');
                if(is_resource($value) && $types[$index]!=='b')throw new InvalidArgumentException('Stream parameters require blob type');
                if(is_float($value) && !is_finite($value)) throw new InvalidArgumentException('Non-finite SQL number');
                if($types[$index] === 'b' && $value !== null) { $blobs[$index] = $value; $value = null; }
            }
            unset($value);
            if($values) {
                $arguments = [$types]; foreach($values as &$value) $arguments[] =& $value; unset($value);
                if(!call_user_func_array([$statement, 'bind_param'], $arguments)) return false;
                foreach($blobs as $index => $blob) {
                    if(is_resource($blob)) {
                        // 即使调用方的流已在 EOF，也先明确发送空 BLOB，保留与 SQL NULL 的区别。
                        if(!mysqli_stmt_send_long_data($statement,$index,''))return false;
                        while(!feof($blob)) {
                            $chunk=fread($blob,65536);
                            if($chunk===false)throw new RuntimeException('Cannot read MySQL blob stream');
                            if($chunk===''&&!feof($blob))throw new RuntimeException('MySQL blob stream made no progress');
                            if($chunk!==''&&!mysqli_stmt_send_long_data($statement,$index,$chunk))return false;
                        }
                    } elseif(!mysqli_stmt_send_long_data($statement, $index, (string)$blob)) return false;
                }
            }
            if(!mysqli_stmt_execute($statement)) {
                $this->err = mysqli_stmt_errno($statement); $this->err_info = mysqli_stmt_error($statement);
                $this->error('Cannot execute prepared query'); return false;
            }
            $rows = mysqli_stmt_field_count($statement) > 0;
            if($rows) {
                if(!function_exists('mysqli_stmt_get_result')) throw new RuntimeException('Prepared result fetching requires mysqlnd');
                $this->result = mysqli_stmt_get_result($statement);
                $count = mysqli_num_rows($this->result);
            } else $count = mysqli_stmt_affected_rows($statement);
            $this->noteStatement($sql, $rows);
            return $count;
        } catch(mysqli_sql_exception $error) {
            $this->checkError();$this->error('Cannot execute prepared query');return false;
        } finally { if($statement instanceof mysqli_stmt)mysqli_stmt_close($statement); }
    }

    /**
     * 功能说明：取得结果集
     *
     * @param int $mode 结果行模式：1数字索引、2关联键、3同时保留两者（默认：2）
     * @return array|false 按mode读取的下一行；没有结果、耗尽或读取失败为false
     */
    public function getRS($mode = 2) {
        if(!$this->check('result')) return false;
        switch($mode) {
            case 1:
                $flag = ($row = mysqli_fetch_row($this->result));
                break;
            case 2:
                $flag = ($row = mysqli_fetch_assoc($this->result));
                break;
            case 3:
                $flag = ($row = mysqli_fetch_array($this->result));
                break;
            default:
                $flag = ($row = mysqli_fetch_assoc($this->result));
        }
        $this->sql = 'none(Get Recordset)';
        if($this->checkError())    $this->error('Error Occur in Get Recordset !');
        return ($flag?$row:false);
    }

    /**
     * 功能说明：获取结果集某一行某一列
     *
     * @param int $line 从0开始的结果行位置（默认：0）
     * @param string $field 指定字段名；空串返回该行全部字段（默认：''）
     * @return mixed field为空时返回包含数字及字段名键的一行，否则返回指定字段原值；无结果集为false
     */
    public function getData($line=0, $field='') {
        if(!$this->check('result')) return false;
        mysqli_data_seek($this->result, $line);
        if($this->checkError())    $this->error('Error Occur in Query !');
        $row = $this->getRS(3);
        if(empty($field)) {
            return $row;
        } else {
            return $row[$field];
        }
    }

    /**
     * 功能说明：获取某一数据表的所有列
     *
     * @param string $the_tbl 数据表名称
     * @param string $the_db 数据库名称，空串使用当前连接上下文（默认：''）
     * @return string[] 指定库表的字段名列表，可能为空
     * @phpstan-return list<string>
     */
    public function getFields($the_tbl, $the_db='') {
        if(empty($the_db)) $the_db = $this->db;
        $this->query('SHOW COLUMNS FROM `'.$this->safeName($the_db).'`.`'.$this->safeName($the_tbl).'`', false);
        $fields = array();
        while($record = $this->getRS()) {
            $fields[] = $record['Field'];
        }
        $this->free();
        return $fields;
    }

    /**
     * 功能说明：导出原生建表 SQL，保留索引、默认值及约束
     *
     * @param string $the_tbl 表名，作为一个完整标识符引用
     * @param string $the_db 库名，空值使用当前库（默认：''）
     * @return string 带尾分号的建表语句；不存在/查询失败按驱动错误策略处理
     */
    public function getCreateScript($the_tbl, $the_db='') {
        if($the_db==='')$the_db=$this->db;
        $row=$this->record('SHOW CREATE TABLE '.$this->quoteIdentifier([$the_db,$the_tbl]),1);
        return is_array($row)&&isset($row[1]) ? rtrim($row[1],';').";\n" : '';
    }

    /**
     * 功能说明：导出可回放 INSERT，使用十六进制字符串保留引号、换行、二进制及字符集字节
     *
     * @param string $the_tbl 表名
     * @param string $the_db 库名，空值使用当前库；导入脚本写入当前选定库中的同名表（默认：''）
     * @return string 带明确列名的 INSERT，NULL 保持 NULL，跳过生成列；整段导出占用内存
     */
    public function getDataScript($the_tbl, $the_db='') {
        if($the_db==='')$the_db=$this->db;
        $table=$this->quoteIdentifier([$the_db,$the_tbl]);$columns=$this->records('SHOW COLUMNS FROM '.$table);$names=[];$expressions=[];
        foreach($columns as $column)if(!preg_match('/\b(?:VIRTUAL|STORED)\s+GENERATED\b/i',$column['Extra']??'')) {
            $names[]=$column['Field'];$name=$this->quoteIdentifier($column['Field']);
            $expressions[]=preg_match('/^bit\b/i',$column['Type'])?'CAST('.$name.' AS UNSIGNED) AS '.$name:$name;
        }
        if(!$names)return '';
        $list=implode(',',array_map([$this,'quoteIdentifier'],$names));$script='';$this->query('SELECT '.implode(',',$expressions).' FROM '.$table);$metadata=mysqli_fetch_fields($this->result);
        try {
            while($row=$this->getRS(1)) {
                $values=[];
                foreach($row as $i=>$value) {
                    if($value===null)$values[]='NULL';
                    elseif(in_array($metadata[$i]->type,[MYSQLI_TYPE_TINY,MYSQLI_TYPE_SHORT,MYSQLI_TYPE_LONG,MYSQLI_TYPE_INT24,MYSQLI_TYPE_LONGLONG,MYSQLI_TYPE_DECIMAL,MYSQLI_TYPE_NEWDECIMAL,MYSQLI_TYPE_FLOAT,MYSQLI_TYPE_DOUBLE,MYSQLI_TYPE_YEAR],true)&&preg_match('/\A[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+)?\z/D',(string)$value))$values[]=(string)$value;
                    else $values[]="X'".bin2hex((string)$value)."'";
                }
                $script.='INSERT INTO '.$this->quoteIdentifier($the_tbl).' ('.$list.') VALUES ('.implode(',',$values).");\n";
            }
            return $script;
        } finally {$this->free();}
    }

    /**
     * 功能说明：取得设置索引项的脚本
     *
     * @param string $the_tbl 数据表名称
     * @param string $the_db 数据库名称，空串使用当前连接上下文（默认：''）
     * @return string[] 非主键索引的定义片段，供建表脚本组合使用
     * @phpstan-return list<string>
     */
    public function getIdxScript($the_tbl, $the_db='') {
        if(empty($the_db)) $the_db = $this->db;
        $idxes = array();
        $this->query('SHOW INDEX FROM `'.$this->safeName($the_db).'`.`'.$this->safeName($the_tbl).'`');
        while($row = $this->getRS(2)) {
            if($row['Key_name'] != 'PRIMARY') {
                $tmp = '`'.$row['Column_name'].'`';
                if($row['Sub_part'] != '') $tmp .= '('.$row['Sub_part'].')';
                if($row['Seq_in_index'] == 1) {
                    if(count($idxes) != 0) $idxes[count($idxes)-1] .= ')';
                    $idxes[] = 'INDEX `'.$row['Key_name'].'` ('.$tmp;
                } else {
                    $idxes[count($idxes)-1] .= ', '.$tmp;
                }
            }
        }
        if(count($idxes) != 0) $idxes[count($idxes)-1] .= ')';
        $this->free();
        return $idxes;
    }

    /**
     * 功能说明：取得所有数据库名
     *
     * 参数说明：无显式参数。
     *
     * @return string[] 当前连接账号可见的数据库名称列表
     * @phpstan-return list<string>
     */
    public function getDBs() {
        $dbs = array();
        $this->query('SHOW DATABASES');
        while($row = $this->getRS(1)) {
            $dbs[] = $row[0];
        }
        $this->free();
        return $dbs;
    }

    /**
     * 功能说明：取得所有表名，及详细信息
     *
     * @param string $the_db 数据库名称，空串使用当前连接上下文（默认：''）
     * @param string $pattern 表名LIKE筛选模式，空串不筛选（默认：''）
     * @param bool $detail 是否返回表状态等完整信息（默认：false）
     * @return array detail为false时为表名列表，为true时为SHOW TABLE STATUS记录列表
     */
    public function getTbls($the_db='', $pattern='', $detail=false) {
        if(empty($the_db)) $the_db = $this->db;
        $tabs = array();
        $sql = 'SHOW TABLE'.($detail ? ' status' : 'S').' FROM '.$this->safeName($the_db).(empty($pattern)?'':' like \'%'.$this->safeValue($pattern).'%\'');
        $this->query($sql);
        while($row = $this->getRS($detail ? 2 : 1)) {
            $tabs[] = $detail ? $row : $row[0];
        }
        $this->free();
        return $tabs;
    }

    /**
     * 功能说明：取得主键名
     *
     * @param string $the_tbl 数据表名称
     * @param string $the_db 数据库名称，空串使用当前连接上下文（默认：''）
     * @return string 首个主键字段名；无主键时兼容回退唯一键，均不存在时为空串
     */
    public function getPri($the_tbl, $the_db='') {
        if(empty($the_db)) $the_db = $this->db;
        $this->query('SHOW FIELDS FROM `'.$this->safeName($the_db).'`.`'.$this->safeName($the_tbl).'`');
        $key = '';
        while($row = $this->getRS(2)) {
            if($row['Key']=='UNI') $key = $row['Field'];
            if($row['Key'] == 'PRI') {
                $key = $row['Field'];
                break;
            }
        }
        $this->free();
        /*
        $key = $this->result('SELECT k.column_name
FROM information_schema.table_constraints t
JOIN information_schema.key_column_usage k
USING (constraint_name,table_schema,table_name)
WHERE t.constraint_type=\'PRIMARY KEY\'
        AND t.table_schema=\''.$this->safeName($the_db).'\'
        AND t.table_name=\''.$this->safeName($the_tbl).'\'');
        */
        return $key;
    }

    /**
     * 功能说明：取得新插入的自增ID
     *
     * 参数说明：无显式参数。
     *
     * @return int|string 最近自增标识，超出PHP整数表示范围时可为字符串；无有效值为0
     */
    public function getInsertId() {
        $the_id = mysqli_insert_id($this->connect);
        if($the_id == 0) $the_id = $this->result('SELECT last_insert_id()');
        return ($the_id?$the_id:0);
    }

    /**
     * 功能说明：列出当前数据库的表并逐一执行 OPTIMIZE TABLE
     *
     * @param string ...$args 可选表名列表；省略时优化当前库的所有表（历史位置参数，由func_get_args读取）
     * @return void 使用正确的 getTbls() 入口；会执行数据库维护操作，调用方自行选择维护时机
     */
    public function optTabs() {
        if(!$this->check()) return false;
        if(func_num_args()==0) {
            $tabs = $this->getTbls();
        } else {
            $tabs = func_get_args();
        }
        for($i=0,$m=count($tabs); $i<$m; $i++) {
            $this->query('OPTIMIZE TABLE '.$tabs[$i]);
        }
        $this->free();
        return true;
    }

    /**
     * 功能说明：取得数据库当前状态；客户端信息采用无连接参数的PHP8接口
     *
     * 参数说明：无显式参数。
     *
     * @return array|string 已连接时为服务器/客户端状态映射；未连接返回空串
     */
    public function getStat() {
        if(!$this->check()) return '';
        $result = array(
            'MySQL server version' => mysqli_get_server_info($this->connect),
            'MySQL protocol version' => mysqli_get_proto_info($this->connect),
            'MySQL host info' => mysqli_get_host_info($this->connect),
            'MySQL client info' => mysqli_get_client_info(),
            'More info' => str_replace('    ', "\n", mysqli_stat($this->connect)),
            'Process list' => implode("\n", $this->getProcesses()),
        );
        return $result;
    }

    /**
     * 功能说明：取得当前进程
     *
     * @param int $mode 0返回进程摘要，1返回完整行，2仅返回进程ID；不会终止进程（默认：0）
     * @param int $time_limit 仅处理运行时间达到此秒数的进程，0不按时长过滤（默认：0）
     * @return array|string mode为0返回摘要列表，1返回进程记录，2返回ID列表；未连接为空串
     */
    public function getProcesses($mode=0, $time_limit=0) {
        if(!$this->check()) return '';
        $result = array();
        $this->query('SHOW PROCESSLIST');
        while($row = $this->getRS(2)) {
            if($row['Time']<$time_limit) continue;
            if($mode==1) {
                $result[] = $row;
            } elseif($mode==2) {
                $result[] = $row['Id'];
            } else {
                $result[] = sprintf('%s - %s (%s)', $row['Id'], empty($row['Info'])?$row['Command']:$row['Info'], $row['Time']);
            }
        }
        $this->free();
        return $result;
    }

    /**
     * 功能说明：更新替换数据
     *
     * @param bool $show 是否启用当前方法的预览/输出模式，具体返回见返回说明（默认：false）
     * @return string|int|false show为true返回REPLACE语句，否则返回query结果；失败为false，成功写入可返回0
     */
    public function replace($show = false) {
        reset($this->builder);
        $sql = current($this->builder)->insert(true);
        if($show) {
            return $sql;
        } else {
            return $this->query($sql);
        }
    }

    /**
     * 功能说明：批量执行查询语句
     *
     * @param string|string[] $SQLs 单条SQL文本或按执行顺序排列的SQL列表；不自动开启事务
     * @return array|false 每项为[原SQL,query结果]；空批次为空数组，未连接为false
     */
    public function batchExe($SQLs) {
        if(!$this->check()) return false;
        if(is_string($SQLs)) $SQLs = array($SQLs);
        $result = array();
        for($i=0,$m=count($SQLs); $i<$m; $i++) {
            if(empty($SQLs[$i])) continue;
            $result[] = array($SQLs[$i], $this->query($SQLs[$i]));
        }
        return $result;
    }

    /**
     * 功能说明：按引号及注释边界分割脚本，保留数据中的空白/分号，支持独立行 DELIMITER
     *
     * @param string $strSQL 待拆分/转换的SQL脚本内容
     * @return string[] 按引号/注释及DELIMITER规则切分的SQL列表，不执行语句
     * @phpstan-return list<string>
     */
    public function handleSQL($strSQL) {
        return SQLStatement::split($strSQL,true);
    }

    /**
     * 功能说明：执行数据查询文件
     *
     * @param string $file 目标文件路径
     * @param array $find 导入SQL前需要替换的查找内容（默认：array()）
     * @param array $replace 与查找列表对应的替换内容（默认：array()）
     * @return array|false 已识别语句的执行摘要列表；文件不存在或未连接为false；单项失败见对应结果字段
     */
    public function file($file, $find = array(), $replace = array()) {
        if(!$this->check()) return false;
        if(is_file($file)) {
            $results = array();
            $SQLs = $this->handleSQL(str_replace($find, $replace, file_get_contents($file)));
            for($i=0,$m=count($SQLs); $i<$m; $i++) {
                $theSQL = $SQLs[$i];
                $theSQL = strtolower(SQLStatement::leadingSQL($theSQL,true));
                $theSQL = str_replace('if not exists', '', $theSQL);
                $theSQL = str_replace('if exists', '', $theSQL);
                $theSQL = str_replace('`', '', $theSQL);
                $result = $this->query($SQLs[$i]);
                switch(true) {
                    case strpos($theSQL, 'select')===0:
                        if(preg_match("/^select.+into\s+(\w+).+/", $theSQL, $match)) {
                            $results[] = array('table', 'select into', $match[1], $result);
                        }elseif(preg_match("/^select.+from\s+(\w+).+/", $theSQL, $match)) {
                            $results[] = array('table', 'select', $match[1], $result);
                        }
                        break;
                    case strpos($theSQL, 'create')===0:
                        preg_match("/^create\s+(\w+)\s+(\w+)/m", $theSQL, $match);
                        $results[] = array($match[1], 'create', $match[2], $result);
                        break;
                    case strpos($theSQL, 'drop')===0:
                        preg_match("/^drop\s+(\w+)\s+(\w+).*/m", $theSQL, $match);
                        $results[] = array($match[1], 'drop', $match[2], $result);
                        break;
                    case strpos($theSQL, 'alter')===0:
                        preg_match("/^alter\s+table\s+(\w+).+/m", $theSQL, $match);
                        $results[] = array('table', 'alter', $match[1], $result);
                        break;
                    case strpos($theSQL, 'delete')===0:
                        preg_match("/^delete\s+from\s+(\w+).+/m", $theSQL, $match);
                        $results[] = array('table', 'delete', $match[1], $result);
                        break;
                    case strpos($theSQL, 'truncate')===0:
                        preg_match("/^truncate\s+(table\s+)?(\w+).+/m", $theSQL, $match);
                        $results[] = array('table', 'truncate', $match[1], $result);
                        break;
                    case strpos($theSQL, 'insert')===0:
                        preg_match("/^insert\s+into\s+(\w+).+/m", $theSQL, $match);
                        $results[] = array('table', 'insert', $match[1], $result);
                        break;
                    case strpos($theSQL, 'update')===0:
                        preg_match("/^update\s+(\w+).+/m", $theSQL, $match);
                        $results[] = array('table', 'update', $match[1], $result);
                        break;
                    case strpos($theSQL, 'show')===0:
                        preg_match("/^show\s+(.+?)\s*($|like.+$)/", $theSQL, $match);
                        $results[] = array('other', 'show', $match[1], $result);
                        break;
                    case strpos($theSQL, 'use')===0:
                        break;
                    default:
                        preg_match("/^(\w+)\s+.+$/", $theSQL, $match);
                        $results[] = array('other', $match[1], 'unknow', $theSQL);
                        continue 2;
                }
            }
            return $results;
        }
        return false;
    }

    /**
     * 功能说明：检查本地连接/结果属性的类型，不通过探测查询或隐式重连掩盖故障
     *
     * @param string $obj 属性名，默认 connect；result 用于检查 mysqli_result（默认：'connect'）
     * @param string $type object 检查对应对象，bool 检查布尔真值；其他值按对象存在处理（默认：'object'）
     * @return bool 属性符合检查条件时为 true；连接对象存在不代表网络一定可用
     */
    public function check($obj = 'connect', $type = 'object') {
        if($obj === 'connect' && $type === 'object') return $this->connect instanceof mysqli;
        if($obj === 'result' && $type === 'object') return $this->result instanceof mysqli_result;
        if($type === 'bool') return isset($this->$obj) && is_bool($this->$obj) && $this->$obj;
        return isset($this->$obj) && is_object($this->$obj);
    }

    /**
     * 功能说明：释放当前 mysqli_result 并将结果引用清空，可重复调用
     *
     * 参数说明：无显式参数。
     *
     * @return void 释放当前结果及 CALL 等语句的后续结果，避免下一条查询 commands out of sync
     */
    public function free() {
        if($this->check('result', 'object')) mysqli_free_result($this->result);
        $this->result = NULL;
        if($this->connect instanceof mysqli) while(mysqli_more_results($this->connect)) {
            try { if(!@mysqli_next_result($this->connect)) { if($this->checkError())$this->error('Cannot drain pending query results');break; } }
            catch(mysqli_sql_exception $error) { $this->checkError();$this->error('Cannot drain pending query results');break; }
            $extra=mysqli_store_result($this->connect);if($extra instanceof mysqli_result)mysqli_free_result($extra);
        }
    }

    /**
     * 功能说明：释放结果和连接，并重置事务、会话、临时表及构建状态
     *
     * @param string|array &$err_info 引用输出：当前驱动的错误文本；空数组只是历史默认占位值（默认：array()）
     * @return int 此驱动累计执行次数；重复关闭安全，不自动重连
     */
    public function close(&$err_info = array()) {
        $this->free();
        if($this->connect instanceof mysqli) mysqli_close($this->connect);
        $this->connect = null;
        $this->cache_transaction = $this->cache_manual = $this->cache_dirty = $this->cache_session = false;
        $this->cache_temporary = []; $this->managed_depth = 0; $this->cache_savepoints=[]; $this->cache_savepoint_root=false; $this->build('[reset]');
        $err_info = $this->err_info;
        return $this->count;
    }

    /**
     * 功能说明：读取当前 mysqli 错误并刷新驱动错误状态，成功后移除残留旧错误
     *
     * @param string|array &$err_info 引用输出：当前驱动的错误文本；空数组只是历史默认占位值（默认：array()）
     * @return int|false 当前mysqli错误编号；没有错误为false，同时刷新err_info引用文本
     */
    public function checkError(&$err_info = array()) {
        $errno = $this->connect instanceof mysqli ? mysqli_errno($this->connect) : 0;
        $this->err = $errno ?: false;
        $this->err_info = $errno ? $errno.' - '.mysqli_error($this->connect) : '';
        $err_info = $this->err_info;
        return $this->err;
    }

    /**
     * 功能说明：清空驱动保存的错误标志及说明，不查询或重建连接
     *
     * 参数说明：无显式参数。
     *
     * @return void 无返回值。
     */
    public function clearError() {
        $this->err = false;
        $this->err_info = '';
    }

    /**
     * 功能说明：错误处理
     *
     * @param string $str 待处理的字符串
     * @param bool $exit 是否在处理完成后结束请求（默认：false）
     * @return void 无返回值。
     */
    protected function error($str, $exit=false) {
        if(!$this->err) {
            $this->err = true;
            if($this->check()) $this->err_info .= mysqli_errno($this->connect).' - '.mysqli_error($this->connect);
        }
        $this->err_info .= ' ('.$str.')';
        $str = "\nQuery String: ".$this->sql."\n";
        $str .= 'MySQL Message: '.$this->err_info;
        parent::Error($str, $exit);
    }
}
