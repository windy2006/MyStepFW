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
MSSQL数据库查询
    $MSSQL = new MSSQL($host, $user, $pass, $charse)  // Set the Database Class
    $MSSQL->connect($the_db)                          // Build a Connection to MSSQL to $MSSQL->conn
    $MSSQL->reconnect($the_db)                        // Rebuild a Connection to MySQL to $mysql->DB_conn
    $MSSQL->SelectDB($the_db)                         // Select a Database of MSSQL to $MSSQL->select (Must Build Connect First)
    $MSSQL->convertSQL($sql)                          // Convert MySQL query to MSSQL
    $MSSQL->convertLimit($sql)                        // convert sql query string include limit grammar of mysql to mssql sql query string
    $MSSQL->query($sql)                               // Execute a Query of MSSQL, Result into $MSSQL->resut
    $MSSQL->getRS()                                   // Return The Current Result as an Array and Set the Point of Result to the Next Result
    $MSSQL->record($sql, $mode)                       // Get the first line of the recordset
    $MSSQL->records($sql, $mode)                      // Get all the lines of the recordset
    $MSSQL->result($line, $field)                     // The Same Use as sqlsrv_result
    $MSSQL->getDBs()                                  // Get the Databases List of Current MySQL Server as an Array
    $MSSQL->getTbls($the_db)                          // Get the Tables List of Current Selected Database as an Array
    $MSSQL->getInsertId()                             // Return auto increment id generate by insert query
    $MSSQL->getFields()                               // Get the Columns List of a table
    $MSSQL->getPri($the_tbl)                          // Get the Primary Keys of a Table as an Array
    $MSSQL->getCreateScript($the_tbl, $the_db)        // Get the Whole Struction of Current Selected Database as an Array
    $MSSQL->getDataScript($the_tbl)                   // Get All of The Data of a Table
    $MSSQL->getIdxScript($the_tbl)                    // Get the Indexes List of a Table as an Array
    $MSSQL->getStat()                                 // Get the Current Status of MSSQL
    $MSSQL->file($file)                               // Read SQL File and execute it
    $MSSQL->handleSQL($strSQL)                        // Split the SQL Query String into a array from a whole String
    $MSSQL->create($name, $para, $type)               // Create Object
    $MSSQL->drop($name, $para, $type)                 // Drop Object
    $MSSQL->select()                                  // Build a select query use the SQL builder and execute it
    $MSSQL->update()                                  // Build a replace query use the SQL builder and execute it
    $MSSQL->delete()                                  // Build a delete query use the SQL builder and execute it
    $MSSQL->replace()                                 // Build a replace query use the SQL builder and execute it
    $MSSQL->batchExec($SQLs)                          // Execute Multi Query from an Array (Use HandleSQL First)
    $MSSQL->check($obj, $type)                        // Check if a db object works
    $MSSQL->free()                                    // Free the $MSSQL->result in order to Release the System Resource
    $MSSQL->close()                                   // Close Current MSSQL Link
    $MSSQL->checkError()                              // Check if error occured
    $MSSQL->clearError()                              // Clear Errors Information
    $MSSQL->error($str)                               // Handle the Errors
 */
class MSSQL extends myBase implements interface_db, interface_sql {
    use base_db, base_sql;

    private
        $error    = false,
        $error_info    = '',
        $count    = 0;

    /**
     * 构造函数
     */
    public function __construct() {
        if(count(func_get_args())>0) call_user_func_array(array($this, 'init'), func_get_args());
    }

    /**
     * 参数初始化
     * @param $host
     * @param $user
     * @param $pwd
     * @param string $charset
     * @return $this
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
     * 连接数据库服务器
     * @param string $the_db
     * @param string $var
     * @return $this
     */
    public function connect($the_db = '', $var = '') {
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
     * 重连数据库服务器
     * @param string $the_db
     * @param string $var
     * @return $this
     */
    public function reconnect($the_db = '', $var = '') {
        $this->close();
        return $this->connect($the_db, $var);
    }

    /**
     * 更换默认数据库
     * @param $the_db
     * @return bool|MSSQL
     */
    public function selectDB($the_db) {
        $this->query( 'use '.$this->safeName($the_db));
        return $this;
    }

    /**
     * 转换mysql查询
     * @param $sql
     * @return mixed
     */
    public function convertSQL($sql) {
        $sql = str_ireplace('LOW_PRIORITY', '', $sql);

        if(preg_match('#^(\w+)\s+(\w+)\s+(if\s+(not\s+)?exists\s+)([\[\]\w]+)([\w\W]+)$#im', $sql, $match)) {
            $sql = $match[3].' ';
            $match[5] = trim($match[5], '[]');
            if(strtolower($match[2])=='table') {
                $sql .= '(select * from sysobjects where id = object_id(\''.$match[5].'\') and OBJECTPROPERTY(id, \'IsUserTable\')=1)';
                $sql = ' '.$match[1].' '.$match[2].' '.$match[5].' '.$match[6];
            } else {
                $sql .= '(select * from sys.databases where name = \''.$match[5].'\')';
                $sql .= ' '.$match[1].' '.$match[2].' ['.$match[5].'] '.$match[6];
            }
        }
        preg_match('#^(\w+)\s+\[?(\w+|\*)#', $sql, $match);
        if(strtolower($match[1])=='create' && strtolower($match[2])=='table') {
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
        } elseif(strtolower($match[1])=='replace') {
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
        } elseif(preg_match('#limit\s\d+#i', $sql)) {
            $sql = $this->convertLimit($sql);
        }
        $sql = str_ireplace(' NOW()', ' GETDATE()', $sql);
        $sql = str_ireplace('= binary', '=', $sql);
        $sql = str_ireplace(' like binary ', ' like ', $sql);
        $sql = str_ireplace(' regexp binary ', ' like ', $sql);
        $sql = str_ireplace(' rlike ', ' like ', $sql);
        return $sql;
    }

    /**
     * 转换返回行限制
     * @param $sql
     * @param string $the_order
     * @return null|string|string[]
     */
    public function convertLimit($sql) {
        if(stripos($sql, 'limit')!==false) {
            if(preg_match('/limit\s+(\d+)$/i', $sql, $matches)) {
                $sql = preg_replace('/limit\s+(\d+)$/i', '', $sql);
                $sql = preg_replace('/^(select|update|delete)/i', '\1 top('.$matches[1].')', $sql);
            } elseif(stripos($sql, "select")===0 && preg_match('/limit\s+(\d+)[\s, ]+(\d+)$/i', $sql, $matches)) {
                $start = $matches[1];
                $size = $matches[2];
                $the_order = $the_order_2 = '';
                if(preg_match('/order by\s+(.+?)\s+limit/i', $sql, $matches)) {
                    $the_order = $matches[1];
                    $the_order = preg_replace('/\s*,\s*/',',', $the_order);
                    $order_list = explode(',', $the_order);
                    $tmp_field = '';
                    for($i=0,$m=count($order_list);$i<$m;$i++) {
                        if(strpos($order_list[$i], ' ')===false) $order_list[$i] .= ' asc';
                        $tmp_field .= ', '.preg_replace('/\s\w+$/', ' as tmp_limit_'.$i, $order_list[$i]);
                        $order_list[$i] = preg_replace('/^.+(\s\w+)$/', 'tmp_limit_'.$i.'\1', $order_list[$i]);
                    }
                    $the_order = implode(',', $order_list);
                    $the_order_2 = str_ireplace('desc', '[xxxx]', $the_order);
                    $the_order_2 = str_ireplace('asc', 'desc', $the_order_2);
                    $the_order_2 = str_ireplace('[xxxx]', 'asc', $the_order_2);
                } else {
                    $the_order_2 = $the_order.' desc';
                }
                $sql = str_ireplace(' from', $tmp_field.' from', $sql);
                $sql = preg_replace('/limit\s+(\d+)[\s, ]+(\d+)$/i', '', $sql);
                $sql = preg_replace('/^select/i', 'select top('.($start+$size).')', $sql);
                $sql = 'select top('.$size.') * from ('.$sql.') as tmp_1 order by '.$the_order_2;
                $sql = 'select * from ('.$sql.') as tmp_2 order by '.$the_order;
            }
        }
        return $sql;
    }

    /**
     * 执行查询
     * @param $sql
     * @return bool|int
     */
    public function query($sql) {
        if(!$this->check()) return false;
        $this->build('[reset]');
        $this->free();
        $this->count++;
        $sql = $this->convertSQL($sql);
        $this->sql = $sql;
        $ifsel = strstr('select', strtolower(substr(trim($sql), 0, 6)));
        $this->result = sqlsrv_query($this->connect, $sql, array(), array('Scrollable' => $ifsel?'keyset':'forward', 'QueryTimeout'=>3));
        if($this->result===false) $this->error('Error Occur in Query !');
        if($ifsel) {
            $num_rows = sqlsrv_num_rows($this->result);
        } else {
            $num_rows = sqlsrv_rows_affected($this->result);
        }
        if($this->checkError()) $this->error('Error Occur in Query !');
        return $num_rows;
    }

    /**
     * 取得结果行
     * @param int $mode
     * @return array|false|null
     */
    public function getRS($mode = 2) {
        if(!$this->check('result')) return false;
        $row = sqlsrv_fetch_array($this->result, $mode);
        $this->sql    = 'none(Get Recordset)';
        if($this->checkError()) $this->error('Error Occur in Get Recordset !');
        $i = 0;
        while(isset($row['tmp_limit_'.$i])) unset($row['tmp_limit_'.$i++]);
        return $row;
    }

    /**
     * 取得所有数据库名
     * @return array
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
     * 取得所有表名
     * @param string $the_db
     * @return array
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
     * 取得最新插入ID
     * @return array|bool|false|int|mixed
     */
    public function getInsertId() {
        $the_id = $this->result('SELECT @@IDENTITY as new_id');
        return ($the_id?$the_id:0);
    }

    /**
     * 取得所有列名
     * @param $the_tbl
     * @return array
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
     * 取得主键名
     * @param $the_tbl
     * @return mixed|string
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
     * 取得建立数据表的脚本
     * @param $the_tbl
     * @param string $the_db
     * @return string
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
     * 取得插入数据的脚本
     * @param $the_tbl
     * @return string|string[]|null
     */
    public function getDataScript($the_tbl) {
        $the_tbl = $this->safeName($the_tbl);
        $fields = $this->getFields($the_tbl);
        $perfix = 'inser into ['.$the_tbl.'] (['.implode('], [', $fields).']) values '.chr(10);
        $pk = $this->getPri($the_tbl);
        $this->query('SELECT * FROM ['.$this->safeName($the_tbl).']'.(empty($pk)?'':' order by '.$pk));
        $result = '';
        $n = 0;
        while($row = $this->getRS(SQLSRV_FETCH_NUMERIC)) {
            if($n%10==0) $result .= $perfix;
            $result .= '(';
            for($i=0,$m=count($row); $i<$m; $i++) {
                if($row[$i] instanceof DateTime) $row[$i] = $row[$i]->format('Y-m-d H:i:s');
                $result .= "'".$this->safeValue($row[$i])."', ";
            }
            $result .= ')'.(($n++%10==9)?';':', ').chr(10);
        }
        $result = str_replace("', )", "')", $result);
        $result = preg_replace('/, \n$/', ";", $result);
        return $result;
    }

    /**
     * 取得设置索引项的脚本
     * @param $the_tbl
     * @return array
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
     * 获取数据库状态
     * @return array|string
     */
    public function getStat() {
        if(!$this->check()) return '';
        return sqlsrv_server_info($this->connect);
    }

    /**
     * 执行数据文件
     * @param $file
     * @param array $find
     * @param array $replace
     * @return array|bool
     */
    public function file($file, $find = array(), $replace = array()) {
        if(!$this->check()) return false;
        if(is_file($file)) {
            $results = array();
            $SQLs = $this->handleSQL(str_replace($find, $replace, file_get_contents($file)));
            for($i=0,$m=count($SQLs); $i<$m; $i++) {
                $theSQL = $SQLs[$i];
                $theSQL = strtolower($theSQL);
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
                    case strpos($theSQL, 'alter'===0):
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
     * 处理SQL文本
     * @param $strSQL
     * @return array
     */
    public function handleSQL($strSQL) {
        $strSQL    = trim($strSQL);
        $strSQL    = preg_replace('/^#[^\n]*\n?$/m', '', $strSQL);
        $strSQL    = preg_replace('/\r\n/',    '\n', $strSQL);
        $strSQL    = preg_replace('/[\n]+/', '\n', $strSQL);
        $strSQL    = preg_replace('/[\t ]+/', ' ', $strSQL);
        $strSQL    = preg_replace('/\/\*[^(\*\/)]*\*\//', '', $strSQL);
        $temp    = preg_split('/;\s*\n/', $strSQL);
        $result    = array();
        $m=count($temp);
        for($i=0; $i<$m; $i++) {
            if(str_replace('\n', '', $temp[$i]) != '') {
                $result[] = preg_replace('/^\n*(.*)\n*$/m', '\1', $temp[$i]);
            }
        }
        return $result;
    }

    /**
     * 创建对象
     * @param $name
     * @param array $para
     * @param string $type
     * @param bool $run
     * @return bool|int
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
     * 更新数据
     * @param bool $show
     * @return bool|int|string
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
     * 批量执行查询
     * @param $SQLs
     * @return array|bool
     */
    public function batchExe($SQLs) {
        if(!$this->check()) return false;
        if(is_string($SQLs)) $SQLs = array($SQLs);
        $m=count($SQLs);
        for($i=0; $i<$m; $i++) {
            if(empty($SQLs[$i])) continue;
            $result[] = array($SQLs[$i], $this->query($SQLs[$i]));
        }
        return $result;
    }

    /**
     * 对象检测
     * @param string $obj
     * @param string $type
     * @return bool|mixed|null
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
     * 释放结果集
     */
    public function free() {
        if($this->check('result')) sqlsrv_free_stmt($this->result);
        $this->result = NULL;
        return;
    }

    /**
     * 关闭数据库
     * @return int
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
     * 错误检测
     * @param $msg
     * @return bool
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
     * 清除错误信息
     */
    public function clearError() {
        $this->error = false;
        $this->error_info = '';
        return;
    }
    /**
     * 错误处理
     * @param $str
     * @param bool $exit
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