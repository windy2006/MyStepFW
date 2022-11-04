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
SQLite 查询类
    $sqlite->init($file, $flag, $key)             // Set the Database Class
    $sqlite->connect()                            // Dummy function for the interface
    $sqlite->selectDB($file, $flag, $key)         // Set a SQLite connection
    $sqlite->query($sql)                          // Execute a Query, Result into $sqlite->DB_resut
    $sqlite->getRS()                              // Return The Current Result as an Array and Set the Point of Result to the Next Result
    $sqlite->record($sql)                         // Get the first line of the record set
    $sqlite->result($sql)                         // Get the single value of the query with the parameters of buildSel function
    $sqlite->GetFields($tbl)                      // Get the Columns List of a Table as an Array
    $sqlite->getTbls($the_db)                     // Get the Tables List of Current Selected Database as an Array
    $sqlite->getCreateScript($the_tbl)            // Get the Whole structure of Current Selected Database as an Array
    $sqlite->getDataScript($the_tbl)              // Get All of The Data of a Table
    $sqlite->getInsertId()                        // Return auto increment id generate by insert query
    $sqlite->create($name, $para, $type)          // Create Object
    $sqlite->drop($name, $para, $type)            // Drop Object
    $sqlite->select()                             // Build a select query use the SQL builder and execute it
    $sqlite->update()                             // Build a replace query use the SQL builder and execute it
    $sqlite->delete()                             // Build a delete query use the SQL builder and execute it
    $sqlite->replace()                            // Build a replace query use the SQL builder and execute it
    $sqlite->free()                               // Free the $sqlite->DB_result in order to Release the System Resource
    $sqlite->close(&$err_info)                    // Close Current MySQL Link
    $sqlite->checkError()                         // Check if error occured
    $sqlite->clearError()                         // Clear Errors Information
    $sqlite->error($str)                          // Handle the Errors
*/
class SQLite extends myBase implements interface_db {
    use base_db, base_sql;

    protected
        $obj = null,
        $err = false,
        $err_info = '',
        $file_db = '',
        $count = 0;

    /**
     * 构造函数
     */
    public function __construct() {
        if(count(func_get_args())>0) call_user_func_array(array($this, "init"), func_get_args());
    }

    /**
     * 方法调用
     * @param $func
     * @param array $args
     * @return mixed|null
     */
    public function __call($func, array $args) {
        if(is_callable(array($this->obj, $func))) {
            return call_user_func_array(array($this->obj, $func), $args);
        } else {
            return $this->__call_base($func, $args);
        }
    }

    /**
     * 初始化实例
     * @param string $file
     * @param int $flag
     * @param null $key
     */
    public function init($file=':memory:', $flag=-1, $key=null) {
        $this->delimiter = ['[',']'];
        if($flag==-1) $flag = (is_file($file) || $file==':memory:') ? SQLITE3_OPEN_READWRITE : SQLITE3_OPEN_CREATE;
        $this->file_db = $file;
        $this->obj = new SQLite3($file, $flag, $key);
        $this->obj->busyTimeout(5000);
        $this->obj->enableExceptions(true);
    }

    /**
     * 连接数据库服务器
     * @return bool
     */
    public function connect() {
        return true;
    }

    /**
     * 选择数据库
     * @param $file
     * @param int $flag
     * @param null $key
     * @return bool
     */
    public function selectDB($file, $flag=-1, $key=null) {
        $this->obj->close();
        $this->init($file, $flag, $key);
        if($this->CheckError()) $this->Error('Could not connect to the Database');
        return true;
    }
    /**
     * 转换mysql查询
     * @param $sql
     * @return mixed
     */
    public function convertSQL($sql) {
        if(preg_match('/limit\s+(\d+)[\s,]+(\d+)$/i', $sql, $matches)) {
            $sql = str_replace($matches[0], 'limit '.$matches[2].' offset '.$matches[1], $sql);
        }
        return $sql;
    }

    /**
     * 执行查询
     * @param $sql
     * @return bool|int
     */
    public function query($sql) {
        $this->build('[reset]');
        $this->count++;
        $sql = $this->convertSQL($sql);
        $this->sql = $sql;
        if(strpos('selec|pragm', strtolower(substr(trim($sql), 0, 5)))!==false) {
            if(is_object($this->result)) $this->result->finalize();
            $this->result = $this->obj->query($sql);
        } else {
            $this->obj->exec('BEGIN');
            $this->result = $this->obj->exec($sql);
            $this->obj->exec('COMMIT');
        }
        return $this->obj->changes();
    }

    /**
     * 取得结果集
     * @param int $mode
     * @return false
     */
    public function getRS($mode = 2) {
        switch($mode) {
            case 1:
                $result = $this->result->fetchArray(SQLITE3_NUM);
                break;
            case 2:
                $result = $this->result->fetchArray(SQLITE3_ASSOC);
                break;
            case 3:
                $result = $this->result->fetchArray(SQLITE3_BOTH);
                break;
            default:
                $result = $this->result->fetchArray(SQLITE3_ASSOC);
        }
        return $result;
    }

    /**
     * 返回单一结果集
     * @param $sql
     * @return array|bool|null
     */
    public function record($sql='') {
        if(empty($sql)) $sql = $this->select(1);
        return $this->obj->querySingle($sql, true);
    }

    /**
     * 返回单一结果值
     * @param string $sql
     * @return array|bool|mixed|null
     */
    public function result($sql='') {
        if(empty($sql)) $sql = $this->select(1);
        return $this->obj->querySingle($sql, false);
    }

    /**
     * 获取某一数据表的所有列
     * @param $tbl
     * @return array
     */
    public function getFields($the_tbl) {
        $this->query('PRAGMA table_info(['.$the_tbl.'])');
        $fields = array();
        while($record = $this->getRS()) {
            $fields[] = $record['name'];
        }
        $this->free();
        return $fields;
    }

    /**
     * 取得所有表名
     * @param string $the_db
     * @param string $pattern
     * @return array
     */
    public function getTbls() {
        $tabs = [];
        $this->query('select * from sqlite_master WHERE type = "table"');
        while($row = $this->getRS()) {
            $tabs[] = $row['name'];
        }
        return $tabs;
    }

    /**
     * 取得建立数据表的脚本
     * @param $the_tbl
     * @return string
     */
    public function getCreateScript($the_tbl) {
        $result = 'CREATE TABLE ['.$the_tbl.'] ('.chr(10);
        $table_info = [];
        $this->query('PRAGMA table_info(['.$this->safeName($the_tbl).'])');
        while($row = $this->getRS()) {
            $result .= '    ['.$row['name'].'] '.$row['type'].' ';
            if($row['pk']==1) $result .= 'PRIMARY KEY ';
            if($row['notnull']==1) $result .= 'NOT NULL';
            $result .= ','.chr(10);
        }
        $result = substr($result,0,-2).chr(10).');';
        return $result;
    }

    /**
     * 取得插入数据的脚本
     * @param $the_tbl
     * @return mixed|string
     */
    public function getDataScript($the_tbl) {
        $this->query('SELECT * FROM ['.$this->safeName($the_tbl).']');
        $result = '';
        while($row = $this->getRS(1)) {
            $result .= 'INSERT INTO `'.$the_tbl.'` VALUES (';
            for($i=0,$m=count($row); $i<$m; $i++) {
                $result .= '"'.$this->safeValue($row[$i]).'", ';
            }
            $result .= ");\n";
        }
        $result = str_replace('", );', '");', $result);
        return $result;
    }

    /**
     * 取得新插入的自增ID
     * @return mixed
     */
    public function getInsertId() {
        return $this->obj->lastInsertRowID();
    }

    /**
     * 释放数据查询
     */
    public function free() {
        $this->result = NULL;
    }

    /**
     * 关闭数据服务器连接
     * @param array $err_info
     * @return int
     */
    public function close(&$err_info = array()) {
        $this->obj->close();
        return $this->count;
    }

    /**
     * 检查错误
     * @param array $err_info
     * @return bool
     */
    public function checkError(&$err_info = array()) {
        $this->err = $this->obj->lastErrorCode();
        if($this->err>0) {
            $this->err_info = $this->err.' - '.$this->obj->lastErrorMsg();
        }
        $err_info = $this->err_info;
        return $this->err;
    }

    /**
     * 清空已有错误
     */
    public function clearError() {
        $this->err = false;
        $this->err_info = '';
    }

    /**
     * 错误处理
     * @param $str
     * @param bool $exit
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
