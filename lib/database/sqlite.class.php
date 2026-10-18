<?PHP
/********************************************
*                                           *
* Name    : SQLite Manager                  *
* Author  : Windy2000                       *
* Time    : 2022-07-10                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT HOLD THIS ITEM PLEASE.      *
*                                           *
********************************************/
/**
 * 文件：lib/database/sqlite.class.php
 * 功能说明：SQLite数据库适配器
 *   基于SQLite3提供参数绑定、受管事务、结构脚本与受限SQL兼容转换。
 *
 * 方法目录：
 *   public SQLite::__construct() — 打开 SQLite 数据库，无参数时创建独立内存库
 *   public SQLite::init($file=':memory:', $flag=-1, $key=null, $exception=false, $timeout=5000) — 关闭旧连接，打开指定库并重置缓存、事务及内存库隔离身份
 *   public SQLite::connect() — 检查是否已持有 SQLite3 对象，用于兼容数据库接口
 *   public SQLite::selectDB($file, $flag=-1, $key=null) — 切换 SQLite 文件或内存库，委托 init() 关闭旧库并打开新库
 *   public SQLite::convertSQL($sql) — 做有限的 MySQL 兼容转换，不修改字符串和注释中的 LOW_PRIORITY
 *   public SQLite::safeValue($value) — 按 SQLite 规则转义单引号，避免使用 MySQL 反斜杠语义
 *   public SQLite::query($sql) — 执行无参数 SQL，委托 execute() 并沿用其错误及资源管理策略
 *   public SQLite::execute($sql, array $params = []) — 原生预处理并按类型绑定参数，成功后更新事务及共享缓存跟踪
 *   public SQLite::getRS($mode = 2) — 从当前结果读取下一行，空结果或结束时返回 false
 *   public SQLite::record($sql='', $mode = 2) — 复用 base_sql 的单条记录读取及缓存/资源清理规则
 *   public SQLite::result($sql='') — 复用 base_sql 的第一行第一列读取，保留 SQL NULL 及零值
 *   public SQLite::getFields($the_tbl) — 取得表字段名，表名作为单个标识符引用，支持空格和引号
 *   public SQLite::getTbls() — 列出普通表，读取完成后释放结果
 *   public SQLite::getCreateScript($the_tbl) — 从 sqlite_master 读取原始建表语句，保留复合主键、默认值、约束和生成列
 *   public SQLite::getDataScript($the_tbl) — 导出可回放 INSERT，保留 NULL、数字、文本及 BLOB，排除生成列
 *   public SQLite::getIdxScript($the_tbl) — 返回独立创建的索引脚本，表内 UNIQUE/PRIMARY KEY 已包含在建表语句中
 *   public SQLite::getInsertId() — 取得新插入的自增ID
 *   public SQLite::free() — 释放当前 SQLite3Result 及关联 SQLite3Stmt，并清空两者引用
 *   public SQLite::close(&$err_info = array()) — 释放结果并关闭当前库，重置事务、会话、临时表及构建器状态
 *   public SQLite::checkError(&$err_info = array()) — 检查错误
 *   public SQLite::clearError() — 清空已有错误
 *   protected SQLite::error($str, $exit=false) — 错误处理
 *
 * 使用说明：
 *   1. 可使用内存数据库或受信SQLite文件，通常经myDb调用。
 *   2. execute/fetch*支持位置或命名参数；读取成功返回0也属于正常执行。
 *   3. 只声明实际实现的基础、参数绑定和事务协议，不冒充完整SQL管理接口。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名保持PHP 7.4兼容，PHPDoc类型用于说明，不额外强制运行时类型。
 */


/**
 * 功能说明：基于SQLite3提供参数绑定、受管事务、结构脚本与受限SQL兼容转换。
 *
 * 使用说明：
 *   1. 可使用内存数据库或受信SQLite文件，通常经myDb调用。
 *   2. execute/fetch*支持位置或命名参数；读取成功返回0也属于正常执行。
 *   3. 只声明实际实现的基础、参数绑定和事务协议，不冒充完整SQL管理接口。
 *
 * 补充说明：
 * SQLite3 驱动，提供原生参数绑定并复用 SQL 构建、缓存和事务辅助能力
 *
 *     $sqlite = new SQLite();                         // 打开独立 :memory: 数据库
 *     $sqlite->init($file);                            // 打开或创建文件库，关闭原连接
 *     $sqlite->execute('INSERT INTO items(value) VALUES(?)', [$value]);
 *     $rows = $sqlite->fetchAll('SELECT * FROM items WHERE id=:id', ['id' => $id]);
 *     $sqlite->transaction(function ($tx) { ... });      // 嵌套调用采用保存点
 *
 * 使用说明：
 * 1. 空参数构造即可使用独立内存库，重新 init() 不会复用原内存库缓存身份。
 * 2. query() 委托 execute()，读查询成功返回 0；结果通过 getRS() 或 fetch*() 读取。
 *    写查询返回 SQLite changes()，失败返回 false 或按异常策略抛出。
 * 3. execute() 支持位置和命名参数，SQL NULL、有限数字、文本及二进制流按类型绑定。
 * 4. convertSQL() 仅做有限兼容，不能把任意 MySQL 方言转换为 SQLite；转义使用
 *    SQLite3 自身规则。事务、缓存和结果读取的具体约定参见 base_db/base_sql。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 *
 * @example ../../app/Document/module/SQLite.php 框架现有演示，仅作为文档引用，不在检查时执行
 */
class SQLite extends myBase implements interface_db, interface_db_prepared, interface_db_transaction {
    use base_db, base_sql { base_sql::record as protected sqlRecord; base_sql::result as protected sqlResult; }
    protected $active_statement = null;

    protected
        $obj = null,
        $err = false,
        $err_info = '',
        $file_db = '',
        $count = 0;

    /**
     * 功能说明：打开 SQLite 数据库，无参数时创建独立内存库
     *
     * @param mixed ...$args 按init声明顺序传入的SQLite路径等设置（历史位置参数，由func_get_args读取）
     * @param string $file 历史位置参数说明：可选文件路径或 :memory:，转给 init()
     * @param int $flag 历史位置参数说明：可选 SQLite3 打开标志，默认 -1 自动判断
     * @param string|null $key 历史位置参数说明：可选加密密钥，须由 SQLite 构建提供加密支持
     * @param bool $exception 历史位置参数说明：可选是否让 SQLite3 抛异常，默认 false
     * @param int $timeout 历史位置参数说明：可选忙等待毫秒数，默认 5000
     * @return void 初始化或释放资源，无返回值。
     */
    public function __construct() {
        $this->init(...func_get_args());
    }

    /**
     * 功能说明：关闭旧连接，打开指定库并重置缓存、事务及内存库隔离身份
     *
     * @param string $file 文件路径或 :memory:，默认独立内存库（默认：':memory:'）
     * @param int $flag SQLite3 打开标志；-1 自动选择读写，文件不存在时加 CREATE（默认：-1）
     * @param string|null $key 可选数据库加密密钥，是否支持由 SQLite 构建决定（默认：null）
     * @param bool $exception 是否启用 SQLite3 异常，默认 false（默认：false）
     * @param int $timeout busyTimeout 等待毫秒数，默认 5000（默认：5000）
     * @return void 打开失败由 SQLite3/框架错误策略处理
     */
    public function init($file=':memory:', $flag=-1, $key=null, $exception=false, $timeout=5000) {
        if(defined('ROOT') && $file !== ':memory:' && $file !== '') { require_once dirname(__DIR__).'/myPath.class.php'; $file = myPath::absolute($file); }
        if($this->obj instanceof SQLite3) $this->close();
        $this->cache_transaction = $this->cache_manual = $this->cache_dirty = $this->cache_session = false;
        $this->cache_temporary = []; $this->managed_depth = 0; $this->cache_savepoints=[]; $this->cache_savepoint_root=false; $this->cache_memory_identity = '';
        $this->delimiter = ['[',']'];
        if($flag==-1) $flag = (is_file($file) || $file==':memory:') ? SQLITE3_OPEN_READWRITE : (SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
        $this->file_db = $file;
        $this->obj = new SQLite3($file, $flag, $key);
        $this->obj->busyTimeout($timeout);
        $this->obj->enableExceptions($exception);
    }

    /**
     * 功能说明：检查是否已持有 SQLite3 对象，用于兼容数据库接口
     *
     * 参数说明：无显式参数。
     *
     * @return bool 已初始化为 SQLite3 对象时为 true；本方法不重新打开数据库
     */
    public function connect() {
        return $this->obj instanceof SQLite3;
    }

    /**
     * 功能说明：切换 SQLite 文件或内存库，委托 init() 关闭旧库并打开新库
     *
     * @param string $file 目标文件路径或 :memory:
     * @param int $flag 打开标志，默认 -1 按是否存在自动决定（默认：-1）
     * @param string|null $key 可选数据库加密密钥（默认：null）
     * @return bool 初始化完成为 true，错误由当前框架策略处理
     */
    public function selectDB($file, $flag=-1, $key=null) {
        $this->init($file, $flag, $key);
        if($this->CheckError()) $this->Error('Could not connect to the Database');
        return true;
    }
    /**
     * 功能说明：做有限的 MySQL 兼容转换，不修改字符串和注释中的 LOW_PRIORITY
     *
     * @param string $sql 可信 SQL，移除实际 LOW_PRIORITY 单词并将起始 TRUNCATE TABLE 转 DELETE FROM
     * @return string 转换后的 SQL；SQLite 原生支持 LIMIT offset,count，不额外重写
     */
    public function convertSQL($sql) {
        // SQLite already accepts LIMIT offset,count; never rewrite literal text.
        foreach(array_reverse(SQLStatement::tokens($sql, false)) as $token) if($token['word'] === 'LOW_PRIORITY') $sql = substr_replace($sql, '', $token['offset'], strlen('LOW_PRIORITY'));
        return preg_replace('/\A\s*TRUNCATE\s+TABLE\s+/i', 'DELETE FROM ', $sql);
    }

    /**
     * 功能说明：按 SQLite 规则转义单引号，避免使用 MySQL 反斜杠语义
     *
     * @param mixed $value 文本或标量，布尔先转整数；二进制值及外部输入优先参数绑定
     * @return string 转义后但不含两端 SQL 引号的文本
     */
    public function safeValue($value) {
        if(is_bool($value)) $value = (int)$value;
        return SQLite3::escapeString((string)$value);
    }

    /**
     * 功能说明：执行无参数 SQL，委托 execute() 并沿用其错误及资源管理策略
     *
     * @param string $sql 非空完整 SQL，不能含未提供绑定值的占位符
     * @return int|false 读查询成功为 0，写查询为 changes()，失败为 false；结果通过 getRS() 取出
     */
    public function query($sql) {
        return $this->execute($sql);
    }

    /**
     * 功能说明：原生预处理并按类型绑定参数，成功后更新事务及共享缓存跟踪
     *
     * 位置参数按数组遍历顺序从 1 绑定；命名参数允许带 :/@/$ 前缀或使用裸名称。
     * 文本含 NUL 或非法 UTF-8 时按 BLOB；流从当前位置读取为 BLOB，不按块保留流式内存。
     * 读查询保留语句及结果供 getRS() 使用，free() 负责释放；失败或非读查询关闭语句。
     *
     * @param string $sql 非空 SQL 模板，使用 ? 或命名占位符；参数不能替代标识符或 SQL 片段
     * @param array $params 标量、null 或可读流；数量须与 SQLite 参数数一致，不混用两种命名方式（默认：[]）
     * @return int|false 读查询成功为 0，写查询为受影响行数，无连接或执行失败为 false
     * @throws InvalidArgumentException SQL、参数个数/名称/类型非法或浮点数非有限
     * @throws RuntimeException 无法读取二进制流
     * @throws BadMethodCallException 写入 RETURNING 在执行前拒绝，避免 PHP 7.4 SQLite 结果读取重复执行写入
     * @throws Throwable SQLite3 或框架错误策略产生的异常继续传递
     * @phpstan-param array<array-key,scalar|null|resource> $params
     */
    public function execute($sql, array $params = []) {
        if(!($this->obj instanceof SQLite3)) return false;
        if(!is_string($sql) || trim($sql) === '') throw new InvalidArgumentException('SQL must be a non-empty string');
        $this->guardTransaction($sql);
        $this->build('[reset]'); $this->free(); $this->clearError(); $this->count++;
        $this->sql = $sql = $this->convertSQL($sql);
        // PHP 7.4 bindValue 自动为 $name 再补冒号；只改写代码区中的已提供 $ 参数。
        $binding_sql=$sql;$dollar_names=[];$prefix=null;
        foreach(array_keys($params) as $key)if(is_string($key)&&substr($key,0,1)==='$') {
            if($prefix===null)do{$prefix=':__ms_'.bin2hex(random_bytes(8)).'_';}while(strpos($sql,$prefix)!==false);
            $dollar_names[$key]=$prefix.count($dollar_names);
        }
        if($dollar_names)$binding_sql=SQLStatement::mapCode($sql,function($code)use($dollar_names){return preg_replace_callback('/(?<![\w$])\$[\w\x80-\xff]+/',function($m)use($dollar_names){return $dollar_names[$m[0]]??$m[0];},$code);},false);
        $statement = @$this->obj->prepare($binding_sql);
        if(!$statement) { $this->checkError(); $this->error('Cannot prepare SQLite query'); return false; }
        try {
            if($statement->paramCount() !== count($params)) throw new InvalidArgumentException('SQL parameter count mismatch');
            $position = 0; $seen=[];$kind=null;
            foreach(array_keys($params) as $key) {
                $current=is_int($key)?'position':'name';
                if($kind!==null&&$kind!==$current)throw new InvalidArgumentException('Cannot mix positional and named SQLite parameters');
                $kind=$current;
                if($current==='name') {
                    $name=preg_match('/\A[:@$]/',$key)?$key:':'.$key;
                    if(isset($seen[$name]))throw new InvalidArgumentException('Duplicate SQLite parameter name');
                    $seen[$name]=true;
                }
            }
            foreach($params as $key => $value) {
                $name = is_int($key) ? ++$position : ($dollar_names[$key] ?? (preg_match('/\A[:@$]/', $key) ? $key : ':'.$key));
                if($value === null) $type = SQLITE3_NULL;
                elseif(is_int($value) || is_bool($value)) { $type = SQLITE3_INTEGER; $value = (int)$value; }
                elseif(is_float($value) && is_finite($value)) $type = SQLITE3_FLOAT;
                elseif(is_resource($value)) { $value = stream_get_contents($value); if($value === false) throw new RuntimeException('Cannot read SQLite blob stream'); $type = SQLITE3_BLOB; }
                elseif(is_string($value)) $type = strpos($value, "\0") !== false || !preg_match('//u', $value) ? SQLITE3_BLOB : SQLITE3_TEXT;
                else throw new InvalidArgumentException('SQL parameters must be finite scalar values, null or streams');
                if(!@$statement->bindValue($name, $value, $type)) throw new InvalidArgumentException('Invalid SQLite parameter');
            }
            $rows = $statement->readOnly();
            if(!$rows)foreach(SQLStatement::tokens($sql,false) as $token)if($token['depth']===0&&$token['word']==='RETURNING')throw new BadMethodCallException('SQLite RETURNING is unsafe with this PHP 7.4 result API; use getInsertId or a separate SELECT');
            $result = @$statement->execute();
            if($result === false) { $this->checkError(); $this->error('Cannot execute SQLite query'); return false; }
            $this->noteStatement($sql, $rows);
            if($rows) { $this->result = $result; $this->active_statement = $statement; $statement = null; return 0; }
            $result->finalize();
            return $this->obj->changes();
        } finally { if($statement instanceof SQLite3Stmt) $statement->close(); }
    }

    /**
     * 功能说明：从当前结果读取下一行，空结果或结束时返回 false
     *
     * @param int $mode 1 数字下标、2 字段名（默认）、3 两者；其他值回退字段名模式（默认：2）
     * @return array|false 当前记录，保留字段原生 null/数字/文本类型；本方法不自动释放结果
     */
    public function getRS($mode = 2) {
        if(!($this->result instanceof SQLite3Result)) return false;
        $type = [1=>SQLITE3_NUM, 2=>SQLITE3_ASSOC, 3=>SQLITE3_BOTH][$mode] ?? SQLITE3_ASSOC;
        return $this->result->fetchArray($type);
    }

    /**
     * 功能说明：复用 base_sql 的单条记录读取及缓存/资源清理规则
     *
     * @param string $sql 完整 SQL，空字符串从当前构建器生成（默认：''）
     * @param int $mode 结果下标：1 数字、2 字段名、3 两者，默认 2（默认：2）
     * @return array|false 第一条记录；无记录或执行失败为 false
     */
    public function record($sql='', $mode = 2) {
        return $this->sqlRecord($sql, $mode);
    }

    /**
     * 功能说明：复用 base_sql 的第一行第一列读取，保留 SQL NULL 及零值
     *
     * @param string $sql 完整 SQL，空字符串从当前构建器生成（默认：''）
     * @return mixed 首列值；无记录或执行失败为 false，SQL NULL 为 null
     */
    public function result($sql='') {
        return $this->sqlResult($sql);
    }

    /**
     * 功能说明：取得表字段名，表名作为单个标识符引用，支持空格和引号
     *
     * @param string $the_tbl 当前库中的表名
     * @return array 按声明顺序排列的字段名称
     */
    public function getFields($the_tbl) {
        $this->query('PRAGMA table_info('.$this->quoteIdentifier($the_tbl).')');$fields=[];
        try { while($row=$this->getRS())$fields[]=$row['name'];return $fields; } finally {$this->free();}
    }

    /**
     * 功能说明：列出普通表，读取完成后释放结果
     *
     * 参数说明：无显式参数。
     *
     * @return array 当前主库中的表名
     */
    public function getTbls() {
        return array_column($this->fetchAll("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name"),'name');
    }

    /**
     * 功能说明：从 sqlite_master 读取原始建表语句，保留复合主键、默认值、约束和生成列
     *
     * @param string $the_tbl 主库中的表名
     * @return string 建表 SQL（含尾分号）；不存在返回空串，不包含独立索引或触发器
     */
    public function getCreateScript($the_tbl) {
        $sql=$this->fetchValue("SELECT sql FROM sqlite_master WHERE type='table' AND name=?",[$the_tbl]);
        return is_string($sql)&&$sql!=='' ? rtrim($sql,';').";\n" : '';
    }

    /**
     * 功能说明：导出可回放 INSERT，保留 NULL、数字、文本及 BLOB，排除生成列
     *
     * @param string $the_tbl 表名，明确引用为单个本地标识符
     * @return string INSERT 脚本；空表返回空串，较大导出建议使用专门流式备份工具
     */
    public function getDataScript($the_tbl) {
        $columns=$this->fetchAll('PRAGMA table_xinfo('.$this->quoteIdentifier($the_tbl).')');$names=[];
        foreach($columns as $col)if((int)($col['hidden']??0)===0)$names[]=$col['name'];
        if(!$names)return '';
        $list=implode(',',array_map([$this,'quoteIdentifier'],$names));$table=$this->quoteIdentifier($the_tbl);
        $this->query('SELECT '.$list.' FROM '.$table);$script='';
        try {
            while($row=$this->getRS(1)) {
                $values=[];
                foreach($row as $i=>$value) {
                    $type=$this->result->columnType($i);
                    if($value===null)$values[]='NULL';
                    elseif($type===SQLITE3_INTEGER)$values[]=(string)$value;
                    elseif($type===SQLITE3_FLOAT)$values[]=is_finite($value)?json_encode($value,JSON_PRESERVE_ZERO_FRACTION):($value>0?'9e999':'-9e999');
                    elseif($type===SQLITE3_BLOB)$values[]="X'".bin2hex($value)."'";
                    elseif(strpos($value,"\0")!==false)$values[]="CAST(X'".bin2hex($value)."' AS TEXT)";
                    else $values[]="'".$this->safeValue($value)."'";
                }
                $script.='INSERT INTO '.$table.' ('.$list.') VALUES ('.implode(',',$values).");\n";
            }
            return $script;
        } finally {$this->free();}
    }

    /**
     * 功能说明：返回独立创建的索引脚本，表内 UNIQUE/PRIMARY KEY 已包含在建表语句中
     *
     * @param string $the_tbl 主库中的表名
     * @return array 每项为一条带尾分号的 CREATE INDEX
     */
    public function getIdxScript($the_tbl) {
        $rows=$this->fetchAll("SELECT sql FROM sqlite_master WHERE type='index' AND tbl_name=? AND sql IS NOT NULL ORDER BY name",[$the_tbl]);
        return array_map(function($row){return rtrim($row['sql'],';').';';},$rows);
    }

    /**
     * 功能说明：取得新插入的自增ID
     *
     * 参数说明：无显式参数。
     *
     * @return int|false 连接存在时返回lastInsertRowID，包括有效的0；未连接为false
     */
    public function getInsertId() {
        return $this->obj instanceof SQLite3 ? $this->obj->lastInsertRowID() : false;
    }

    /**
     * 功能说明：释放当前 SQLite3Result 及关联 SQLite3Stmt，并清空两者引用
     *
     * 参数说明：无显式参数。
     *
     * @return void 可重复调用，不关闭数据库对象
     */
    public function free() {
        if($this->result instanceof SQLite3Result) $this->result->finalize();
        $this->result = null;
        if($this->active_statement instanceof SQLite3Stmt) $this->active_statement->close();
        $this->active_statement = null;
    }

    /**
     * 功能说明：释放结果并关闭当前库，重置事务、会话、临时表及构建器状态
     *
     * @param string|array &$err_info 引用输出：当前驱动的错误文本；空数组只是历史默认占位值（默认：array()）
     * @return int 驱动累计执行次数；重复关闭安全，内存库身份会随关闭重置
     */
    public function close(&$err_info = array()) {
        $this->free();
        if($this->obj instanceof SQLite3) $this->obj->close();
        $this->obj = null;
        $this->cache_transaction = $this->cache_manual = $this->cache_dirty = $this->cache_session = false;
        $this->cache_temporary = []; $this->managed_depth = 0; $this->cache_savepoints=[]; $this->cache_savepoint_root=false; $this->cache_memory_identity = ''; $this->build('[reset]');
        $err_info = $this->err_info;
        return $this->count;
    }

    /**
     * 功能说明：检查错误
     *
     * @param string|array &$err_info 引用输出：当前驱动的错误文本；空数组只是历史默认占位值（默认：array()）
     * @return int|false SQLite错误代码，0表示无错误；未连接时沿用已有错误状态
     */
    public function checkError(&$err_info = array()) {
        if(!($this->obj instanceof SQLite3)) { $err_info=$this->err_info;return $this->err; }
        $this->err = $this->obj->lastErrorCode();
        if($this->err>0) {
            $this->err_info = $this->err.' - '.$this->obj->lastErrorMsg();
        }
        if(!$this->err)$this->err_info='';
        $err_info = $this->err_info;
        return $this->err;
    }

    /**
     * 功能说明：清空已有错误
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
            $this->err_info .= $this->err.' - '.$this->obj->lastErrorMsg();
        }
        $this->err_info .= ' ('.$str.')';
        $str = "\nQuery String: ".$this->sql."\n";
        $str .= 'DB Message: '.$this->err_info;
        parent::Error($str, $exit);
    }
}
