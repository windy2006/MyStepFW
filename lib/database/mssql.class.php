<?PHP
/********************************************
 *                                           *
 * Name    : MSSQL Manager                   *
 * Author  : Windy2000                       *
 * Time    : 2005-04-14                      *
 * Email   : windy2006@gmail.com             *
 * HomePage: www.mysteps.cn                  *
 * Notice  : U Can Use & Modify it freely,   *
 *           BUT PLEASE HOLD THIS ITEM.      *
 *                                           *
 ********************************************/
/**
 * 文件：lib/database/mssql.class.php
 * 功能说明：SQL Server数据库适配器
 *   使用SQLSRV实现连接、参数执行、SQL方言转换和管理脚本操作。
 *
 * 方法目录：
 *   public MSSQL::__construct() — 构造函数
 *   public MSSQL::init($host, $user, $pwd, $charset='utf-8') — 参数初始化
 *   public MSSQL::connect($the_db = '', $var = '') — 连接数据库服务器
 *   public MSSQL::reconnect($the_db = '', $var = '') — 重连数据库服务器
 *   public MSSQL::selectDB($the_db) — 更换默认数据库
 *   public MSSQL::convertSQL($sql) — 转换mysql查询
 *   public MSSQL::safeValue($value) — 按 T-SQL 单引号规则转义文本，保留反斜杠；Unicode/二进制优先参数绑定
 *   public MSSQL::execute($sql,array $params=[]) — 原生参数化执行 T-SQL，不经过旧 MySQL 方言转换、不自动重试或使用共享缓存
 *   public MSSQL::query($sql) — 执行旧接口 SQL，仅 query 沿用有限 MySQL 兼容转换；原生 SQL 使用 execute
 *   public MSSQL::convertLimit($sql) — 转换最外层 MySQL LIMIT，字符串、注释和子查询中的 LIMIT 不改写
 *   public MSSQL::getRS($mode = 2) — 取得结果行
 *   public MSSQL::getDBs() — 取得所有数据库名
 *   public MSSQL::getTbls($the_db='') — 取得所有表名
 *   public MSSQL::getInsertId() — 取得最新插入ID
 *   public MSSQL::getFields($the_tbl) — 取得所有列名
 *   public MSSQL::getPri($the_tbl) — 取得主键名
 *   public MSSQL::getCreateScript($the_tbl, $the_db='') — 取得建立数据表的脚本
 *   public MSSQL::getDataScript($the_tbl) — 导出基本 INSERT，修复历史 inser 拼写，保留 NULL/Unicode/流二进制
 *   public MSSQL::getIdxScript($the_tbl) — 取得设置索引项的脚本
 *   public MSSQL::getStat() — 获取数据库状态
 *   public MSSQL::file($file, $find = array(), $replace = array()) — 执行数据文件
 *   public MSSQL::handleSQL($strSQL) — 处理SQL文本
 *   public MSSQL::create($name, $para=[], $type='db', $run = true) — 创建对象
 *   public MSSQL::update($show = false) — 更新数据
 *   public MSSQL::batchExe($SQLs) — 批量执行查询
 *   public MSSQL::check($obj = 'connect', $type = 'resource') — 对象检测
 *   public MSSQL::free() — 释放结果集
 *   public MSSQL::close() — 关闭数据库
 *   public MSSQL::checkError(&$msg=array()) — 错误检测
 *   public MSSQL::clearError() — 清除错误信息
 *   protected MSSQL::error($str, $exit=false) — 错误处理
 *
 * 使用说明：
 *   1. 通常经myDb选择MSSQL驱动，连接配置按该驱动的参数顺序传入。
 *   2. 需要SQLSRV扩展；execute/fetch*支持参数绑定。
 *   3. 不声明受管事务接口；不能以继承方法是否存在推断真实事务能力。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名保持PHP 7.4兼容，PHPDoc类型用于说明，不额外强制运行时类型。
 */


/**
 * 功能说明：使用SQLSRV实现连接、参数执行、SQL方言转换和管理脚本操作。
 *
 * 使用说明：
 *   1. 通常经myDb选择MSSQL驱动，连接配置按该驱动的参数顺序传入。
 *   2. 需要SQLSRV扩展；execute/fetch*支持参数绑定。
 *   3. 不声明受管事务接口；不能以继承方法是否存在推断真实事务能力。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 *
 * @example ../../app/Document/module/mssql.php 框架现有演示，仅作为文档引用，不在检查时执行
 */
class MSSQL extends myBase implements interface_db, interface_sql, interface_db_prepared {
    use base_db, base_sql;

    private
        $error    = false,
        $error_info    = '',
        $count    = 0;

    /**
     * 功能说明：构造函数
     *
     * @param mixed ...$args 按init(host,user,pwd,charset)顺序传入的连接设置；构造不立即连接（历史位置参数，由func_get_args读取）
     * @return void 初始化或释放资源，无返回值。
     */
    public function __construct() {
        if(count(func_get_args())>0) call_user_func_array(array($this, 'init'), func_get_args());
    }

    /**
     * 功能说明：参数初始化
     *
     * @param string $host 服务器主机名或地址
     * @param string $user 连接使用的用户名
     * @param string $pwd 账号密码，空串按调用入口的兼容约定处理
     * @param string $charset 字符编码名称，例如UTF-8；是否自动检测由具体入口决定（默认：'utf-8'）
     * @return $this 返回当前实例，支持链式调用。
     */
    public function init($host, $user, $pwd, $charset='utf-8') {
        $this->host = $host;
        $this->user = $user;
        $this->pwd = $pwd;
        $this->charset = $charset;
        $this->delimiter = ['[', ']'];
        return $this;
    }

    /**
     * 功能说明：连接数据库服务器
     *
     * @param string|bool $the_db 数据库名称；兼容公共连接入口的首位占位参数（默认：''）
     * @param string $var 非空时作为实际数据库名，兼容connect($pconnect,$database)调用（默认：''）
     * @return $this 返回当前实例，支持链式调用。
     * @throws RuntimeException 当前PHP未安装SQLSRV扩展
     */
    public function connect($the_db = '', $var = '') {
        if(!function_exists('sqlsrv_connect'))throw new RuntimeException('The sqlsrv extension is required');
        if($this->connect!==null)$this->close();
        if(!empty($var)) $the_db = $var;
        if(!is_string($the_db)) $the_db = '';
        $connectionInfo = array(
            'Database' => $the_db,
            'UID' => $this->user,
            'PWD' => $this->pwd,
            'CharacterSet' => $this->charset
        );
        $this->connect = sqlsrv_connect($this->host, $connectionInfo);
        if(!$this->check()) {
            $this->sql = 'none (Connect to MSSQL Server)';
            $this->error('Could not connect to MSSQL Server');
        }
        sqlsrv_configure('WarningsReturnAsErrors', false);
        $this->db = $the_db;
        $this->query( 'SET TEXTSIZE 1024000');
        return $this;
    }

    /**
     * 功能说明：重连数据库服务器
     *
     * @param string|bool $the_db 数据库名称；兼容公共连接入口的首位占位参数（默认：''）
     * @param string $var 非空时作为实际数据库名，兼容connect($pconnect,$database)调用（默认：''）
     * @return $this 返回当前实例，支持链式调用。
     */
    public function reconnect($the_db = '', $var = '') {
        $this->close();
        return $this->connect($the_db, $var);
    }

    /**
     * 功能说明：更换默认数据库
     *
     * @param string $the_db 数据库名称，空串使用当前连接上下文
     * @return $this|false USE成功后返回当前驱动；失败为false，且不切换当前库名
     */
    public function selectDB($the_db) {
        if($this->query('USE '.$this->quoteIdentifier($the_db))===false)return false;
        $this->db=$the_db;
        return $this;
    }

    /**
     * 功能说明：转换mysql查询
     *
     * @param string $sql 待处理的SQL语句
     * @return string 经有限MySQL兼容规则转换的T-SQL文本，不执行语句
     */
    public function convertSQL($sql) {
        $sql = SQLStatement::leadingSQL($sql,false);
        $sql = SQLStatement::mapCode($sql,function($code){return preg_replace('/\bLOW_PRIORITY\b/i','',$code);},false);

        if(preg_match('#^(\w+)\s+(\w+)\s+(if\s+(not\s+)?exists\s+)([\[\]\w]+)([\w\W]+)$#im', $sql, $match)) {
            $sql = $match[3].' ';
            $match[5] = trim($match[5], '[]');
            if(strtolower($match[2] ?? '')=='table') {
                $sql .= '(select * from sysobjects where id = object_id(\''.$match[5].'\') and OBJECTPROPERTY(id, \'IsUserTable\')=1)';
                $sql .= ' '.$match[1].' '.$match[2].' '.$match[5].' '.$match[6];
            } else {
                $sql .= '(select * from sys.databases where name = \''.$match[5].'\')';
                $sql .= ' '.$match[1].' '.$match[2].' ['.$match[5].'] '.$match[6];
            }
        }
        preg_match('#^(\w+)\s+\[?(\w+|\*)#', $sql, $match);
        if(strtolower($match[1] ?? '')=='create' && strtolower($match[2] ?? '')=='table') {
            $sql = str_ireplace(' AUTO_INCREMENT', ' identity(1,1)', $sql);
            $sql = str_ireplace(' UNSIGNED', '', $sql);
            $sql = str_ireplace(' MEDIUMINT', ' INT ', $sql);
            $sql = str_ireplace(' DOUBLE', ' float', $sql);
            $sql = str_ireplace(' REAL', ' float', $sql);
            $sql = str_ireplace(' TIMESTAMP', ' smalldatetime', $sql);
            $sql = str_ireplace(' YEAR', ' smallint', $sql);
            $sql = str_ireplace(' VARBINARY', ' varbinary', $sql);
            $sql = str_ireplace(' TINYBLOB', ' varbinary', $sql);
            $sql = str_ireplace(' BLOB', ' varbinary', $sql);
            $sql = str_ireplace(' MEDIUMBLOB', ' varbinary', $sql);
            $sql = str_ireplace(' LONGBLOB', ' varbinary', $sql);
            $sql = str_ireplace(['TINYTEXT', 'MEDIUMTEXT', 'LONGTEXT'], 'NTEXT', $sql);
            $sql = preg_replace('#Char\((\d+)\)#i', 'nchar(\1)', $sql);
            $sql = preg_replace('#DEFAULT\s(\'|").+\1#i', '', $sql);
            $sql = preg_replace('#DEFAULT\s[^\s]+\s#i', '', $sql);
            $sql = preg_replace('#COMMENT\s+(\'|").+\1#i', '', $sql);
            $sql = preg_replace('#ENGINE=\w+#i', '', $sql);
            $sql = preg_replace('#\[?(\w+)\]?\s+enum\s*(\(.+?\))#i', '\1 NVARCHAR(20) NOT NULL CHECK(\1 IN\2)', $sql);
            $sql = preg_replace('#COMMENT=[^\s]+#i', '', $sql);
        } elseif(strtolower($match[1] ?? '')=='replace') {
            if(preg_match('#.+\s+set\s+.+#i', $sql)) {
                preg_match('#replace\s+into\s+\[?(\w+)#', $sql, $match);
                $pk = $this->getPri($match[1]);
                $sql = preg_replace('/^replace\s+into/i', 'update', $sql);
                if(preg_match('#\[?'.$this->safeName($pk).'\]?\s*\=(.+),#', $sql, $match)) {
                    $sql = str_replace($match[0], '', $sql).' where ['.$pk.']='.$match[1];
                }
            } else {
                $sql = preg_replace('/^replace/i', 'insert', $sql);
            }
        } else {
            $sql = $this->convertLimit($sql);
        }
        $sql = SQLStatement::mapCode($sql,function($code){
            $code=preg_replace('/\bNOW\s*\(\s*\)/i','GETDATE()',$code);
            $code=str_ireplace(['= binary',' like binary ',' regexp binary ',' rlike '],['=',' like ',' like ',' like '],$code);
            return $code;
        },false);
        return $sql;
    }

    /**
     * 功能说明：按 T-SQL 单引号规则转义文本，保留反斜杠；Unicode/二进制优先参数绑定
     *
     * @param mixed $value 标量或 null
     * @return string 不含外层引号的文本
     */
    public function safeValue($value) { return str_replace("'","''",(string)$value); }

    /**
     * 功能说明：原生参数化执行 T-SQL，不经过旧 MySQL 方言转换、不自动重试或使用共享缓存
     *
     *
     * 参数结构：普通值按位置绑定；数组项为SQLSRV原生[值,方向,PHP类型,SQL类型]描述符，后3项可按扩展约定省略。结果由驱动持有，后续用getRS读取或free释放。
     *
     * @param string $sql T-SQL，数据占位符为 ?
     * @param array $params sqlsrv 参数顺序列表；支持原生 [值,方向,PHP类型,SQL类型] 描述（默认：[]）
     * @return int|false 结果集行数或受影响行数；SQL Server 不能确定数量时沿用 -1
     * @throws InvalidArgumentException SQL 或标量参数非法
     * @throws RuntimeException 未安装 sqlsrv 扩展
     * @phpstan-param array<array-key,scalar|null|resource|array{0:mixed,1?:int,2?:mixed,3?:mixed}> $params
     */
    public function execute($sql,array $params=[]) {
        if(!function_exists('sqlsrv_query'))throw new RuntimeException('The sqlsrv extension is required');
        if(!is_string($sql)||trim($sql)==='')throw new InvalidArgumentException('SQL must be a non-empty string');
        foreach($params as $value) {
            if(is_array($value)){if(!array_key_exists(0,$value)||count($value)>4)throw new InvalidArgumentException('Invalid sqlsrv parameter descriptor');}
            elseif((!is_scalar($value)&&$value!==null&&!is_resource($value))||(is_float($value)&&!is_finite($value)))throw new InvalidArgumentException('Invalid sqlsrv parameter');
        }
        if(!$this->check())return false;
        $this->build('[reset]');$this->free();$this->clearError();$this->sql=$sql;$this->count++;
        $this->result=sqlsrv_query($this->connect,$sql,array_values($params),['Scrollable'=>'static','QueryTimeout'=>30]);
        if($this->result===false){$this->error('Cannot execute SQL Server query');return false;}
        $fields=sqlsrv_num_fields($this->result);
        if($fields===false){$this->error('Cannot inspect SQL Server result');return false;}
        $rows=$fields>0;$count=$rows?sqlsrv_num_rows($this->result):sqlsrv_rows_affected($this->result);
        $this->noteStatement($sql,$rows);
        return $count;
    }

    /**
     * 功能说明：执行旧接口 SQL，仅 query 沿用有限 MySQL 兼容转换；原生 SQL 使用 execute
     *
     * @param string $sql 非空 SQL
     * @return int|false 行数或受影响行数
     * @throws InvalidArgumentException SQL为空/非字符串，或兼容转换不支持写入偏移分页
     */
    public function query($sql) { if(!is_string($sql)||trim($sql)==='')throw new InvalidArgumentException('SQL must be a non-empty string');return $this->execute($this->convertSQL($sql)); }

    /**
     * 功能说明：转换最外层 MySQL LIMIT，字符串、注释和子查询中的 LIMIT 不改写
     *
     * @param string $sql 可信 SELECT 查询；偏移分页使用 SQL Server 2012+ OFFSET/FETCH
     * @return string 无分页原样返回；无排序的偏移查询使用不保证稳定次序的 ORDER BY (SELECT NULL)
     * @throws InvalidArgumentException UPDATE/DELETE 偏移分页不能安全转换
     */
    public function convertLimit($sql) {
        $top=array_values(array_filter(SQLStatement::tokens($sql,false),function($t){return $t['depth']===0;}));$limit=null;
        foreach($top as $token)if($token['word']==='LIMIT')$limit=$token;
        if($limit===null)return $sql;
        $tail=substr($sql,$limit['offset']);
        if(!preg_match('/\ALIMIT\s+(\d+)(?:\s*,\s*(\d+)|\s+OFFSET\s+(\d+))?\s*;?\s*\z/iD',$tail,$m))return $sql;
        $offset=isset($m[2])&&$m[2]!==''?(int)$m[1]:(int)($m[3]??0);$count=isset($m[2])&&$m[2]!==''?(int)$m[2]:(int)$m[1];
        $base=rtrim(substr($sql,0,$limit['offset']));$command=$top[0]['word']??'';
        if($offset>0&&$count>0){
            if(!in_array($command,['SELECT','WITH'],true))throw new InvalidArgumentException('SQL Server write offset pagination is unsupported');
            $ordered=false;foreach($top as$i=>$t)if($t['word']==='ORDER'&&($top[$i+1]['word']??'')==='BY')$ordered=true;
            return $base.($ordered?'':' ORDER BY (SELECT NULL)').' OFFSET '.$offset.' ROWS FETCH NEXT '.$count.' ROWS ONLY';
        }
        foreach($top as$t)if(in_array($t['word'],['SELECT','UPDATE','DELETE'],true)){
            $at=$t['offset']+strlen($t['word']);
            if($t['word']==='SELECT'&&preg_match('/\G\s+(?:DISTINCT|ALL)\b/i',$base,$prefix,0,$at))$at+=strlen($prefix[0]);
            return substr_replace($base,' TOP ('.$count.')',$at,0);
        }
        return $sql;
    }

    /**
     * 功能说明：取得结果行
     *
     * @param int $mode 结果行模式：1数字索引、2关联键、3同时保留两者（默认：2）
     * @return array|false 按mode返回下一行；结果耗尽、没有结果集或读取失败为false
     */
    public function getRS($mode = 2) {
        if(!$this->check('result')) return false;
        $row = sqlsrv_fetch_array($this->result, $mode);
        if($row===false){$this->error('Cannot fetch SQL Server record');return false;}
        if($row===null)return false;
        $this->sql    = 'none(Get Recordset)';
        if($this->checkError()) $this->error('Error Occur in Get Recordset !');
        $i = 0;
        while(isset($row['tmp_limit_'.$i])) unset($row['tmp_limit_'.$i++]);
        return $row;
    }

    /**
     * 功能说明：取得所有数据库名
     *
     * 参数说明：无显式参数。
     *
     * @return string[] 当前账号可见且符合驱动查询条件的数据库名列表，可能为空
     * @phpstan-return list<string>
     */
    public function getDBs() {
        $dbs = array();
        $this->query('SELECT name FROM master.dbo.sysdatabases where dbid > 5 ORDER BY dbid DESC');
        while($row = $this->getRS(1)) {
            $dbs[] = $row[0];
        }
        $this->free();
        return $dbs;
    }

    /**
     * 功能说明：取得所有表名
     *
     * @param string $the_db 数据库名称，空串使用当前连接上下文（默认：''）
     * @return string[] 指定数据库中可见的用户表名称列表，可能为空
     */
    public function getTbls($the_db='') {
        if(empty($the_db)) $the_db = $this->db;
        $tabs = array();
        $this->query('SELECT a.name as name FROM ['.$this->safeName($the_db).'].dbo.sysobjects a left join ['.$this->safeName($the_db).'].dbo.sysusers b on a.uid=b.uid where a.xtype=\'u\' order by a.name asc');
        while($row = $this->getRS(1)) {
            $tabs[] = $row[0];
        }
        $this->free();
        return $tabs;
    }

    /**
     * 功能说明：取得最新插入ID
     *
     * 参数说明：无显式参数。
     *
     * @return int|float|string 最近的@@IDENTITY数值；SQLSRV可将数值返回为字符串，无有效值时返回0
     */
    public function getInsertId() {
        $the_id = $this->result('SELECT @@IDENTITY as new_id');
        return ($the_id?$the_id:0);
    }

    /**
     * 功能说明：取得所有列名
     *
     * @param string $the_tbl 数据表名称
     * @return string[] 当前驱动查询得到的字段名列表，未取得字段时为空数组
     * @phpstan-return list<string>
     */
    public function getFields($the_tbl) {
        $this->query('SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.columns WHERE TABLE_NAME=\''.$this->safeName($the_tbl).'\'');
        $fields = array();
        while($record = $this->getRS(1)) {
            $fields[] = $record[0];
        }
        $this->free();
        return $fields;
    }

    /**
     * 功能说明：取得主键名
     *
     * @param string $the_tbl 数据表名称
     * @return string|false 主键列名，未取得时回退自增列；仍无记录或查询失败为false
     */
    public function getPri($the_tbl) {
        $the_tbl = $this->safeName($the_tbl);
        $key = $this->result('SELECT a.name
FROM sys.syscolumns AS a 
INNER JOIN sys.sysobjects AS d ON a.id = d.id
WHERE (d.name = \''.$the_tbl.'\') AND EXISTS (
    SELECT 1 AS tmp FROM sys.sysobjects WHERE (xtype = \'PK\') AND (parent_obj = a.id) AND (name IN (
    SELECT name FROM sys.sysindexes WHERE (indid IN (
        SELECT indid FROM sys.sysindexkeys WHERE (id = a.id) AND (colid = a.colid)
    ))
    ))
)');
        if(empty($key)) {
            $key = $this->result('SELECT a.name FROM sys.syscolumns AS a 
INNER JOIN sys.sysobjects AS d ON a.id = d.id AND d.xtype = \'U\' AND d.name <> \'dtproperties\' 
WHERE (d.name = \''.$the_tbl.'\') AND (COLUMNPROPERTY(a.id, a.name, \'IsIdentity\') = 1)
ORDER BY a.id');
        }
        return $key;
    }

    /**
     * 功能说明：取得建立数据表的脚本
     *
     * @param string $the_tbl 数据表名称
     * @param string $the_db 数据库名称，空串使用当前连接上下文（默认：''）
     * @return string 当前表的CREATE TABLE脚本文本，按驱动支持的字段信息生成
     */
    public function getCreateScript($the_tbl, $the_db='') {
        if(empty($the_db)) $the_db = $this->db;
        $the_db = $this->safeName($the_db);
        $the_tbl = $this->safeName($the_tbl);
        $this->query('SELECT a.name,
(CASE WHEN COLUMNPROPERTY(a.id, a.name, \'IsIdentity\')    = 1 THEN \'y\' ELSE \'\' END) AS [identity],
(CASE WHEN (
    SELECT COUNT(*) FROM sysobjects WHERE (name IN (
    SELECT name FROM sysindexes WHERE (id = a.id) AND (indid IN (
        SELECT indid FROM sysindexkeys WHERE (id = a.id) AND (colid IN (
        SELECT colid FROM syscolumns WHERE (id = a.id) AND (name = a.name)
        ))
    ))
    )) AND (xtype = \'PK\')
) > 0 THEN \'y\' ELSE \'\' END) AS [primary],
b.name AS [type],
a.length AS [bytes],
COLUMNPROPERTY(a.id, a.name, \'PRECISION\') AS [length],
ISNULL(COLUMNPROPERTY(a.id, a.name, \'Scale\'), 0) AS [float],
(CASE WHEN a.isnullable = 1 THEN \'y\' ELSE \'\' END) AS [null],
ISNULL(e.text, \'\') AS [default],
ISNULL(g.value, \' \') AS [comment]
FROM sys.syscolumns AS a 
LEFT OUTER JOIN sys.systypes AS b ON a.xtype = b.xusertype 
INNER JOIN sys.sysobjects AS d ON a.id = d.id AND d.xtype = \'U\' AND d.name <> \'dtproperties\' 
LEFT OUTER JOIN sys.syscomments AS e ON a.cdefault = e.id 
LEFT OUTER JOIN sys.extended_properties AS g ON a.id = g.major_id AND a.colid = g.minor_id 
LEFT OUTER JOIN sys.extended_properties AS f ON d.id = f.class AND f.minor_id = 0
WHERE (d.name = \''.$the_tbl.'\')
ORDER BY a.id');
        $result = '/****** Object:    Table ['.$the_db.'].['.$the_tbl.']    ******/'.chr(10);
        $result .= 'CREATE TABLE ['.$the_tbl.']('.chr(10);
        while($line = $this->getRS()) {
            $result .= '    ['.$line['name'].'] ';
            if($line['identity']=='y') {
                $result .= '[int] IDENTITY(1, 1) NOT NULL';
            } else {
                $result .= '['.$line['type'].']';
                if($line['type']=='nvarchar') $result .= '('.$line['length'].')';
                if(empty($line['null'])) $result .= ' NOT';
                $result .= ' NULL';
            }
            if($line['primary']=='y') $result .= 'PRIMARY KEY';
            $result .= ', '.chr(10);
        }
        $result .= ') ON [PRIMARY]';
        return $result;
    }

    /**
     * 功能说明：导出基本 INSERT，修复历史 inser 拼写，保留 NULL/Unicode/流二进制
     *
     * @param string $the_tbl 表名；计算列、IDENTITY、日期精度等完整恢复仍应使用 SQL Server 专用备份
     * @return string 导出文本，整段保存在内存，不承诺完整 SQL Server 模式迁移
     * @throws RuntimeException 流字段内容读取失败
     */
    public function getDataScript($the_tbl) {
        $fields=$this->getFields($the_tbl);if(!$fields)return '';
        $table=$this->quoteIdentifier($the_tbl);$list=implode(',',array_map([$this,'quoteIdentifier'],$fields));
        $this->execute('SELECT '.$list.' FROM '.$table);$script='';
        try {
            while($row=$this->getRS(1)) {
                $values=[];
                foreach($row as $value) {
                    if($value===null)$values[]='NULL';
                    elseif(is_resource($value)){$bytes=stream_get_contents($value);if($bytes===false)throw new RuntimeException('Cannot read SQL Server blob');$values[]='0x'.bin2hex($bytes);}
                    else {if($value instanceof DateTimeInterface)$value=$value->format('Y-m-d H:i:s.u');$values[]="N'".$this->safeValue($value)."'";}
                }
                $script.='INSERT INTO '.$table.' ('.$list.') VALUES ('.implode(',',$values).");\n";
            }
            return $script;
        } finally {$this->free();}
    }

    /**
     * 功能说明：取得设置索引项的脚本
     *
     * @param string $the_tbl 数据表名称
     * @return string[] 非主键索引的CREATE INDEX语句列表
     * @phpstan-return list<string>
     */
    public function getIdxScript($the_tbl) {
        $the_tbl = $this->safeName($the_tbl);
        $this->query( 'SP_HELPINDEX '.$the_tbl);
        $idxes = array();
        while($current = $this->getRS()) {
            if(strpos($current['index_name'], 'PK_')===0) continue;
            $script = 'CREATE ';
            if(strpos($current['index_description'], 'unique')!==false) $script .= 'UNIQUE ';
            if(strpos($current['index_description'], 'nonclustered')!==false) {
                $script .= 'NONCLUSTERED ';
            } else {
                $script .= 'CLUSTERED ';
            }
            $script .= 'INDEX ['.$current['index_name'].'] ON ['.$the_tbl.']('.chr(10);
            $keys = explode(',', $current['index_keys']);
            foreach ($keys as $k) {
                if(strpos($k, '(-)')) {
                    $k = str_replace('(-)', '', $k);
                    $script .= '['.$k.'] DESC, '.chr(10);
                } else {
                    $script .= '['.$k.'] ASC, '.chr(10);
                }
            }
            $script .= ')';
            $idxes[] = $script;
        }
        return $idxes;
    }

    /**
     * 功能说明：获取数据库状态
     *
     * 参数说明：无显式参数。
     *
     * @return array|string|false 已连接时为SQLSRV服务器信息数组，底层失败为false；未连接时为空串
     */
    public function getStat() {
        if(!$this->check()) return '';
        return sqlsrv_server_info($this->connect);
    }

    /**
     * 功能说明：执行数据文件
     *
     * @param string $file 目标文件路径
     * @param array $find 导入SQL前需要替换的查找内容（默认：array()）
     * @param array $replace 与查找列表对应的替换内容（默认：array()）
     * @return array|false 逐语句处理摘要；文件不存在或连接不可用为false，合法空脚本可返回空数组
     */
    public function file($file, $find = array(), $replace = array()) {
        if(!$this->check()) return false;
        if(is_file($file)) {
            $results = array();
            $SQLs = $this->handleSQL(str_replace($find, $replace, file_get_contents($file)));
            for($i=0,$m=count($SQLs); $i<$m; $i++) {
                $theSQL = $SQLs[$i];
                $theSQL = strtolower(SQLStatement::leadingSQL($theSQL,false));
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
                    case strpos($theSQL, 'insert')===0:
                        preg_match("/^insert\s+into\s+(\w+).+/m", $theSQL, $match);
                        $results[] = array('table', 'insert', $match[1], $result);
                        break;
                    case strpos($theSQL, 'update')===0:
                        preg_match("/^update\s+(\w+).+/m", $theSQL, $match);
                        $results[] = array('table', 'update', $match[1], $result);
                        break;
                    default:
                        $results[] = array('other', 'unknow', 'unknow', $theSQL);
                        continue 2;
                }
            }
            return $results;
        }
        return false;
    }

    /**
     * 功能说明：处理SQL文本
     *
     * @param string $strSQL 待拆分/转换的SQL脚本内容
     * @return string[] 按T-SQL引号/注释边界拆分的SQL列表，不执行这些语句
     * @phpstan-return list<string>
     */
    public function handleSQL($strSQL) { return SQLStatement::split($strSQL,false); }

    /**
     * 功能说明：创建对象
     *
     * @param string $name 待创建的数据库/表等对象名称
     * @param array|string $para 表/索引的col、pri、idx等结构选项；数据库场景为排序规则前缀（默认：[]）
     * @param string $type 对象类别，例如db/table，按当前驱动支持范围使用（默认：'db'）
     * @param bool $run true立即执行，false返回生成的创建脚本（默认：true）
     * @return string|int|bool run为false返回SQL，否则返回query执行结果
     */
    public function create($name, $para=[], $type='db', $run = true) {
        switch($type) {
            case 't':
            case 'tbl':
            case 'table':
                if(is_string($para)) {
                    $para=['col'=>$para];
                }
                if(!isset($para['col'])) {
                    $this->error('Column data for table is missing!');
                }
                $sql = 'create table IF NOT EXISTS ['.$this->safeName($name).'] ('.chr(10);
                if(is_string($para['col'])) {
                    if(preg_match('#^\w+$#', $para['col'])) {
                        $sql = 'select * into ['.$this->safeName($name).'] from ['.$para['col'].'] where 1=0';
                        break;
                    } else {
                        $sql .= $para['col'];
                    }
                } else {
                    $cols =& $para['col'];
                    for($i=0,$m=count($cols);$i<$m;$i++) {
                        if(is_string($cols[$i])){
                            $cols[$i] = preg_replace('#^(\w+)#', '[\1]', $cols[$i]);
                            $sql .= $cols[$i].','.chr(10);
                        } else {
                            $sql .= '['.$cols[$i]['name'].'] '.$cols[$i]['type'].' '.(isset($cols[$i]['null'])?'NULL':'NOT NULL').chr(10);
                        }
                    }
                    if(isset($para['idx'])) {
                        if(is_string($para['idx'])) {
                            $sql .= 'INDEX idx_'.md5($para['idx']).'('.$para['idx'].'),'.chr(10);
                        } else {
                            $sql .= 'INDEX '.$para['idx'][0].'('.$para['idx'][1].'),'.chr(10);
                        }
                    }
                    if(isset($para['uni'])) $sql .= 'UNIQUE (['.$para['uni'].']),'.chr(10);
                    if(isset($para['pri'])) {
                        $sql .= 'PRIMARY KEY (['.$para['pri'].'])';
                    } else {
                        $sql = substr($sql, 0, -2);
                    }
                }
                $sql .= chr(10).')';
                if(isset($para['charset'])) $sql .= ' COLLATE '.$para['charset'].'_CI_AS';
                break;
            case 'i':
            case 'idx':
            case 'index':
                if(is_string($para)) {
                    $para=['col'=>$para];
                }
                if(is_array($para['col'])) $para['col'] = implode(',', $para['col']);
                if(!isset($para['name'])) $para['name'] = 'idx_'.md5($para['col']);
                $sql = 'create index ['.$this->safeName($para['name']).'] on '.$this->safeName($name).'('.$para['col'].')';
                break;
            default:
                $sql = 'create database IF NOT EXISTS ['.$this->safeName($name).']';
                if(empty($para)) $para = 'Chinese_PRC';
                $sql .= ' COLLATE '.strtoupper($para).'_CI_AS';
        }
        return $run ? $this->query($sql) : $sql;
    }

    /**
     * 功能说明：更新数据
     *
     * @param bool $show 是否启用当前方法的预览/输出模式，具体返回见返回说明（默认：false）
     * @return string|int|bool show为true返回SQL，否则返回query执行结果；关联更新按当前驱动支持范围生成
     */
    public function update($show = false) {
        if(count($this->builder)==1) {
            $sql = current($this->builder)->update();
        } else {
            reset($this->builder);
            $cur_tbl = current($this->builder);
            $sql = 'update '.$cur_tbl->idx;
            $sql .= ' set ';
            $fields = array();
            $the_field = $cur_tbl->field();
            if($the_field!=='*') $fields[] = $the_field;
            $sql .= implode(',', $fields);

            reset($this->builder);
            $cur_tbl = current($this->builder);
            $sql .= ' from '. $cur_tbl->tbl.' as '.$cur_tbl->idx. ' ';
            while(($cur_tbl=next($this->builder))!==false) {
                if(empty($cur_tbl->join)) continue;
                $sql .= $cur_tbl->join['mode'].' join '.$cur_tbl->tbl.' as '.$cur_tbl->idx;
                if(empty($cur_tbl->join['field_join'])) {
                    $cur_tbl->join['field_join'] ='['.$cur_tbl->idx.']'.$cur_tbl->join['field'];
                }
                $sql .= ' on '.$cur_tbl->idx.'.'.$cur_tbl->join['field'].'='.$cur_tbl->join['field_join'];
            }

            $conditions = array();
            foreach($this->builder as $cur_tbl) {
                $the_condition = $cur_tbl->where();
                if(!empty($the_condition)) $conditions[] = $the_condition;
            }
            if(!empty($conditions)) $sql .= ' where '.implode(' and ', $conditions);
        }
        if($show) {
            return $sql;
        } else {
            return $this->query($sql);
        }
    }

    /**
     * 功能说明：批量执行查询
     *
     * @param string|string[] $SQLs 单条SQL文本或按执行顺序排列的SQL列表；不自动开启事务
     * @return array|false 每项为[原SQL,query结果]；空批次为空数组，未连接为false
     */
    public function batchExe($SQLs) {
        if(!$this->check()) return false;
        if(is_string($SQLs)) $SQLs = array($SQLs);
        $m=count($SQLs);
        $result = []; // 空批次同样返回数组，遵循interface_sql的结果约定。
        for($i=0; $i<$m; $i++) {
            if(empty($SQLs[$i])) continue;
            $result[] = array($SQLs[$i], $this->query($SQLs[$i]));
        }
        return $result;
    }

    /**
     * 功能说明：对象检测
     *
     * @param string $obj 内部连接或结果的属性名（默认：'connect'）
     * @param string $type 期望的PHP资源类型标记，默认resource（默认：'resource'）
     * @return bool 内部属性满足resource/object/bool检查规则为true，否则为false
     */
    public function check($obj = 'connect', $type = 'resource') {
        $result = false;
        if(isset($this->$obj)) {
            if($type=='resource' && is_resource($this->$obj)) {
                $result = true;
            } elseif($type=='object' && is_object($this->$obj)) {
                $result = true;
            } elseif($type=='bool' && is_bool($this->$obj)) {
                $result = $this->$obj;
            }
        }
        return $result;
    }

    /**
     * 功能说明：释放结果集
     *
     * 参数说明：无显式参数。
     *
     * @return void 无返回值。
     */
    public function free() {
        if($this->check('result')) sqlsrv_free_stmt($this->result);
        $this->result = NULL;
        return;
    }

    /**
     * 功能说明：关闭数据库
     *
     * 参数说明：无显式参数。
     *
     * @return int 本驱动累计查询次数；关闭操作本身不以此值表示成功/失败
     */
    public function close() {
        if($this->result != NULL) $this->free();
        if($this->connect != NULL) {
            sqlsrv_close($this->connect);
            $this->connect = NULL;
        }
        return $this->count;
    }

    /**
     * 功能说明：错误检测
     *
     * @param string|array &$msg 引用输出：由SQLSRV错误组成的多行文本；空数组是默认占位值（默认：array()）
     * @return bool SQLSRV错误/警告集合非空为true；msg引用接收可读错误文本
     */
    public function checkError(&$msg=array()) {
        $this->error = false;
        $err_info = sqlsrv_errors(SQLSRV_ERR_ALL);
        $msg = array();
        if($err_info != null) {
            $this->error_info = $err_info;
            $this->error = true;
            foreach($err_info as $error) {
                $msg[] = $error['code'] . ' : ' . $error['message'] .'(.'.$error['SQLSTATE'].'.)';
            }
        }
        $msg = "\n".implode("\n", $msg);
        return $this->error;
    }

    /**
     * 功能说明：清除错误信息
     *
     * 参数说明：无显式参数。
     *
     * @return void 无返回值。
     */
    public function clearError() {
        $this->error = false;
        $this->error_info = '';
        return;
    }
    /**
     * 功能说明：错误处理
     *
     * @param string $str 待处理的字符串
     * @param bool $exit 是否在处理完成后结束请求（默认：false）
     * @return void 无返回值。
     */
    protected function error($str, $exit=false) {
        $msg = '';
        $this->checkError($msg);
        $str .= "\n";
        $str .= 'Query String: '.$this->sql."\n";
        $str .= 'MSSQL Message: '.$msg;
        parent::Error($str, $exit);
        return;
    }
}