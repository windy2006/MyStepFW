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
MySQL查询类
    $mysql->init($host, $user, $pass, $charse)   // Set the Database Class
    $mysql->connect($pconnect)                   // Build a Connection to MySQL to $mysql->DB_conn
    $mysql->reconnect($pconnect)                 // Rebuild a Connection to MySQL to $mysql->DB_conn
    $mysql->selectDB($the_db)                    // Select a Database of MySQL to $mysql->DB_select (Must Build Connect First)
    $mysql->setCharset($charset)                 // Set Charset for the database connection
    $mysql->changUser($user, $pass, $db)         // Change the Database User (Unusable in some versoin of MySQL)
    $mysql->query($sql)                          // Execute a Query of MySQL, Result into $mysql->DB_resut
    $mysql->getRS()                              // Return The Current Result as an Array and Set the Point of Result to the Next Result
    $mysql->record()                             // Get the first line of the record set
    $mysql->records()                            // Get the all of the lines of the query
    $mysql->result()                             // Get the single value of the query with the parameters of buildSel function
    $mysql->count()                              // Count the result number of current query
    $mysql->getData($line, $field)               // Get specified field or line of a query
    $mysql->GetFields($the_db, $the_tbl)         // Get the Columns List of a Table as an Array
    $mysql->getCreateScript($the_tbl, $the_db)   // Get the Whole structure of Current Selected Database as an Array
    $mysql->getDataScript($the_tbl, $the_db)     // Get All of The Data of a Table
    $mysql->getIdxScript($the_tbl, $the_db)      // Get the Indexes List of a Table as an Array
    $mysql->getDBs()                             // Get the Databases List of Current MySQL Server as an Array
    $mysql->getTbls($the_db)                     // Get the Tables List of Current Selected Database as an Array
    $mysql->getPri($the_tbl, $the_db)            // Get the Primary Keys of a Table as an Array
    $mysql->getInsertId()                        // Return auto increment id generate by insert query
    $mysql->optTabs()                            // Optimize the Tables of Selected Database
    $mysql->getStat()                            // Get the Current Status of MySQL
    $mysql->getProcesses($mode, $time_limit)     // Get the processes list of MySQL
    $mysql->build($tbl, $join)                   // Set or get a SQL builder to create a sql script
    $mysql->select()                             // Build a select query use the SQL builder and execute it
    $mysql->update()                             // Build a replace query use the SQL builder and execute it
    $mysql->delete()                             // Build a delete query use the SQL builder and execute it
    $mysql->replace()                            // Build a replace query use the SQL builder and execute it
    $mysql->batchExec($SQLs)                     // Execute Multi Query from an Array (Use HandleSQL First)
    $mysql->handleSQL($strSQL)                   // Split the SQL Query String into a array from a whole String (Maybe Read from a File)
    $mysql->file($file)                          // Read SQL File and execute it
    $mysql->check($obj, $type)                   // Check if a db object works
    $mysql->free()                               // Free the $mysql->DB_result in order to Release the System Resource
    $mysql->close(&$err_info)                    // Close Current MySQL Link
    $mysql->checkError()                         // Check if error occured
    $mysql->clearError()                         // Clear Errors Information
    $mysql->error($str)                          // Handle the Errors
*/
class MySQL extends myBase implements interface_db, interface_sql {
    use base_db, base_sql;

    protected
        $err = false,
        $err_info = '',
        $count = 0;

    /**
     * 构造函数
     */
    public function __construct() {
        if(count(func_get_args())>0) call_user_func_array(array($this, "init"), func_get_args());
    }

    /**
     * 初始化实例
     * @param $host
     * @param $user
     * @param $pwd
     * @param string $charset
     */
    public function init($host, $user, $pwd, $charset='utf8mb4') {
        $this->host = $host;
        $this->user = $user;
        $this->pwd = $pwd;
        if(strtolower($charset)=='utf-8') $charset = 'utf8mb4';
        $this->charset = $charset;
        return;
    }

    /**
     * 连接数据库服务器
     * @param bool $pconnect
     * @param null $the_db
     * @return bool
     */
    public function connect($pconnect = false, $the_db = null) {
        if($pconnect) {
            $this->connect = mysqli_connect('p:'.$this->host, $this->user, $this->pwd);
        } else {
            $this->connect = mysqli_connect($this->host, $this->user, $this->pwd);
        }
        $this->sql = 'none (Connect to MySQL Server)';
        if(mysqli_connect_errno()) {
            $this->err_info = mysqli_connect_errno().' - '.mysqli_connect_error();
            $this->err = true;
            $this->error('Could not connect to MySQL Server');
            return false;
        }
        if(version_compare(mysqli_get_server_info($this->connect), '5.0.1', '>')) {
            $this->query('SET sql_mode=""');
        }
        $this->query('SET SESSION wait_timeout=3');
        $this->setCharset();
        if(!empty($the_db)) $this->selectDB($the_db);
        return true;
    }

    /**
     * 重连数据库服务器
     * @param bool $pconnect
     * @param null $the_db
     */
    public function reconnect($pconnect = false, $the_db = null) {
        $this->close();
        return $this->connect($pconnect, $the_db);
    }

    /**
     * 选择数据库
     * @param $the_db
     * @return bool
     */
    public function selectDB($the_db) {
        if(!$this->check()) return false;
        $this->db = $the_db;
        mysqli_select_db($this->connect, $the_db);
        $this->sql = 'none (Select Database)';
        if($this->CheckError())    $this->Error('Could not connect to the Database');
        return true;
    }

    /**
     * 设置字符集
     * @param string $charset
     * @return string
     */
    public function setCharset($charset='') {
        if(empty($charset)) $charset = $this->charset;
        if(strtolower($charset)=='utf-8') $charset = 'utf8mb4';
        mysqli_set_charset($this->connect, $charset);
        if($this->checkError())    $this->error('Unknow CharSet Name');
        return mysqli_character_set_name($this->connect);
    }

    /**
     * 变更操作用户
     * @param $user
     * @param $pwd
     * @param null $db
     * @return bool
     */
    public function changUser($user, $pwd, $db=null) {
        $this->user = $user;
        $this->pwd = $pwd;
        if(empty($db)) $db = $this->db;
        return mysqli_change_user($this->connect, $user, $pwd, $db);
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
        $sql = str_replace('    ', ' ', $sql);
        $sql = str_replace('(1=1)', '1=1', $sql);
        $sql = str_replace('1=1 and', '', $sql);
        $sql = str_replace('where 1=1 order', 'order', $sql);
        $this->sql = $sql;
        if($this->result = mysqli_query($this->connect, $sql)) {
            if(strpos('selec|show |descr|expla|repai|check|optim', strtolower(substr(trim($sql), 0, 5)))!==false && $this->check('result')) {
                $num_rows = mysqli_num_rows($this->result);
            } elseif($this->check()) {
                $num_rows = mysqli_affected_rows($this->connect);
                $this->free();
            } else {
                $num_rows = 0;
            }
        }
        if($this->checkError())    {
            $this->error('Error Occur in Query !');
            $num_rows = false;
        }
        return $num_rows;
    }

    /**
     * 取得结果集
     * @param int $mode
     * @return array|bool|null
     */
    public function getRS($mode = 1) {
        if(!$this->check('result')) return false;
        switch($mode) {
            case 1:
                $flag = ($row = mysqli_fetch_assoc($this->result));
                break;
            case 2:
                $flag = ($row = mysqli_fetch_row($this->result));
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
     * 返回单一结果集
     * @param $sql
     * @param int $mode
     * @return array|bool|null
     */
    public function record($sql='', $mode = 1) {
        if(empty($sql)) $sql = $this->select(1);
        if(stripos($sql, 'select')===0 && stripos($sql, 'limit ')==false) $sql .= ' limit 1';
        $key = md5($sql);
        if(($result = $this->getCache($key))===false) {
            $row_num = $this->query($sql);
            if($row_num>0) {
                $result = $this->getRS($mode);
                $this->writeCache($key, $result);
            }
            $this->free();
        }
        $this->build('[reset]');
        return $result;
    }

    /**
     * 放回全部结果集
     * @param $sql
     * @param int $mode
     * @return array
     */
    public function records($sql='', $mode = 1) {
        if(!empty($sql) && strpos($sql, ' ')==false) {
            $this->build($sql);
            $sql = '';
        }
        if(empty($sql)) $sql = $this->select(1);
        $key = md5($sql);
        if(($result = $this->getCache($key))===false) {
            $this->query($sql);
            while($result[] = $this->getRS($mode)) {}
            array_pop($result);
            if(!empty($result)) $this->writeCache($key, $result);
            $this->free();
        }
        $this->build('[reset]');
        return $result;
    }

    /**
     * 返回单一结果值
     * @param string $sql
     * @return array|bool|mixed|null
     */
    public function result($sql='') {
        if(empty($sql)) $sql = $this->select(1);
        $key = md5($sql);
        if(($result = $this->getCache($key))===false) {
            if($result = $this->record($sql, 2)) {
                $result = $result[0];
                $this->writeCache($key, $result);
            } else {
                $result = false;
            }
            $this->free();
        }
        $this->build('[reset]');
        return $result;
    }

    /**
     * 返回当前查询的结果数
     */
    public function count() {
        $sql = $this->select(1);
        $sql = preg_replace('#limit[\s\d, ]+$#', '', $sql);
        return $this->result('select count(*) from ('.$sql.') as cnt');
    }

    /**
     * 获取结果集某一行某一列
     * @param int $line
     * @param string $field
     * @return array|bool|mixed|null
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
     * 获取某一数据表的所有列
     * @param $the_tbl
     * @param string $the_db
     * @return array
     */
    public function getFields($the_tbl, $the_db='') {
        if(empty($the_db)) $the_db = $this->db;
        $this->query('SHOW COLUMNS FROM `'.$this->safeName($the_db).'`.`'.$this->safeName($the_tbl).'`');
        $fields = array();
        while($record = $this->getRS()) {
            $fields[] = $record['Field'];
        }
        $this->free();
        return $fields;
    }

    /**
     * 取得建立数据表的脚本
     * @param $the_tbl
     * @param string $the_db
     * @return string
     */
    public function getCreateScript($the_tbl, $the_db='') {
        if(empty($the_db)) $the_db = $this->db;
        $this->query('SHOW TABLE STATUS FROM `'.$this->safeName($the_db).'` LIKE \''.$this->safeName($the_tbl).'\'');
        $row = $this->getRS(1);
        $result = '#Table Name: '.$row['Name'].chr(10).
                            '# Create Time: '.$row['Create_time'].chr(10).
                            '# Update Time: '.$row['Update_time'].chr(10);
        $tblInfo = array_values($this->record('show create table `'.$this->safeName($the_db).'`.`'.$this->safeName($the_tbl).'`'));
        $result .= $tblInfo[1].';'.chr(10);
        return $result;
    }

    /**
     * 取得插入数据的脚本
     * @param $the_tbl
     * @param string $the_db
     * @return mixed|string
     */
    public function getDataScript($the_tbl, $the_db='') {
        if(empty($the_db)) $the_db = $this->db;
        $this->query('SELECT * FROM `'.$this->safeName($the_db).'`.`'.$this->safeName($the_tbl).'`');
        $result = '';
        while($row = $this->getRS(2)) {

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
     * 取得设置索引项的脚本
     * @param $the_tbl
     * @param string $the_db
     * @return array
     */
    public function getIdxScript($the_tbl, $the_db='') {
        if(empty($the_db)) $the_db = $this->db;
        $idxes = array();
        $this->query('SHOW INDEX FROM `'.$this->safeName($the_db).'`.`'.$this->safeName($the_tbl).'`');
        while($row = $this->getRS(0)) {
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
     * 取得所有数据库名
     * @return array
     */
    public function getDBs() {
        $dbs = array();
        $this->query('SHOW DATABASES');
        while($row = $this->getRS(2)) {
            $dbs[] = $row[0];
        }
        $this->free();
        return $dbs;
    }

    /**
     * 取得所有表名
     * @param string $the_db
     * @param string $pattern
     * @return array
     */
    public function getTbls($the_db='', $pattern='') {
        if(empty($the_db)) $the_db = $this->db;
        $tabs = array();
        $this->query('SHOW TABLES FROM '.$this->safeName($the_db).(empty($pattern)?'':' like \'%'.$this->safeValue($pattern).'%\''));
        while($row = $this->getRS(2)) {
            $tabs[] = $row[0];
        }
        $this->free();
        return $tabs;
    }

    /**
     * 取得主键名
     * @param $the_tbl
     * @param string $the_db
     * @return mixed|string
     */
    public function getPri($the_tbl, $the_db='') {
        if(empty($the_db)) $the_db = $this->db;
        $this->query('SHOW FIELDS FROM `'.$this->safeName($the_db).'`.`'.$this->safeName($the_tbl).'`');
        $key = '';
        while($row = $this->getRS(0)) {
            if($row['Key']=='UNI') $key = $row['Field'];
            if($row['Key'] == 'PRI') {
                $key = $row['Field'];
                break;
            }
        }
        $this->free();
        return $key;
    }

    /**
     * 取得新插入的自增ID
     * @return array|bool|int|mixed|null|string
     */
    public function getInsertId() {
        $the_id = mysqli_insert_id($this->connect);
        if($the_id == 0) $the_id = $this->result('SELECT last_insert_id()');
        return ($the_id?$the_id:0);
    }

    /**
     * 优化数据表
     * @return bool
     */
    public function optTabs() {
        if(!$this->check()) return false;
        if(func_num_args()==0) {
            $tabs = $this->getTbl();
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
     * 取得数据库当前状态
     * @return array|string
     */
    public function getStat() {
        if(!$this->check()) return '';
        $result = array(
            'MySQL server version' => mysqli_get_server_info($this->connect),
            'MySQL protocol version' => mysqli_get_proto_info($this->connect),
            'MySQL host info' => mysqli_get_host_info($this->connect),
            'MySQL client info' => mysqli_get_client_info($this->connect),
            'More info' => str_replace('    ', "\n", mysqli_stat($this->connect)),
            'Process list' => implode("\n", $this->getProcesses()),
        );
        return $result;
    }

    /**
     * 取得当前进程
     * @param int $mode
     * @param int $time_limit
     * @return array|string
     */
    public function getProcesses($mode=0, $time_limit=0) {
        if(!$this->check()) return '';
        $result = array();
        $this->query('SHOW PROCESSLIST');
        while($row = $this->getRS(0)) {
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
     * 构建数据查询
     * @param $tbl
     * @param null $join
     * @return mixed|void
     */
    public function build($tbl, $join=null) {
        if($tbl=='[reset]') {
            foreach($this->builder as $k => $v) {
                $this->builder[$k]->reset();
                unset($this->builder[$k]);
            }
            return;
        }
        $tbl = $this->safeName($tbl);
        if(!isset($this->builder[$tbl])) {
            $this->builder[$tbl] = new SQLBuilder($tbl, $join, 't'.count($this->builder), '`');
        } else {
            if(!empty($join)) $this->builder[$tbl]->join = $join;
        }
        return $this->builder[$tbl];
    }

    /**
     * 插入数据
     * @param bool $show
     * @return bool|int
     */
    public function insert($show = false) {
        reset($this->builder);
        $sql = current($this->builder)->insert();
        $sql = preg_replace('/^(insert|replace)/i', '\1 LOW_PRIORITY', $sql);
        if($show) {
            return $sql;
        } else {
            return $this->query($sql);
        }
    }

    /**
     * 查询数据
     * @param bool $show
     * @return bool|int|string
     */
    public function select($show = false) {
        if(count($this->builder)==1) {
            $sql = current($this->builder)->select();
        } else {
            $sql = 'select ';
            if(!empty($this->builder[0]->sel_prefix)) $sql = $this->builder[0].$this->sel_prefix.' ';
            $fields = array();
            foreach($this->builder as $cur_tbl) {
                $the_field = $cur_tbl->field();
                if($the_field=='*') {
                    $the_field = $cur_tbl->dl.$cur_tbl->idx.$cur_tbl->dr.'.*';
                } elseif(substr(strtolower($the_field),0,6)=='count(') {
                    $fields = array($the_field);
                    break;
                }
                if(!empty($the_field)) $fields[] = $the_field;
            }
            $sql .= implode(',', $fields);

            reset($this->builder);
            $cur_tbl = current($this->builder);
            $sql .= ' from '.$cur_tbl->tbl.' as '.$cur_tbl->idx.' ';
            while(($cur_tbl=next($this->builder))!==false) {
                if(empty($cur_tbl->join)) continue;
                $sql .= $cur_tbl->join['mode'].' join '.$cur_tbl->tbl.' as '.$cur_tbl->idx;
                if(empty($cur_tbl->join['field_join'])) {
                    $sql .= ' using('.$cur_tbl->join['field'].')';
                } else {
                    $sql .= ' on '.$cur_tbl->idx.'.'.$cur_tbl->join['field'].'='.$cur_tbl->join['field_join'];
                }
            }

            $conditions = array();
            foreach($this->builder as $cur_tbl) {
                $the_condition = $cur_tbl->where();
                if(!empty($the_condition)) $conditions[] = $the_condition;
            }
            if(!empty($conditions)) $sql .= ' where '.implode(' and ', $conditions);

            $orders = array();
            foreach($this->builder as $cur_tbl) {
                $the_order = $cur_tbl->order();
                if(!empty($the_order)) $orders[] = $the_order;
            }
            if(!empty($orders)) $sql .= ' order by '.implode(',', $orders);

            reset($this->builder);
            $sql .= current($this->builder)->limit();
        }
        if($show) {
            return $sql;
        } else {
            return $this->query($sql);
        }
    }

    /**
     * 更新数据
     * @param bool $show
     * @return bool|int|string
     */
    public function update($show = false) {
        if(count($this->builder)==1) {
            $sql = current($this->builder)->update();
            $sql = preg_replace('/^update/i', 'update LOW_PRIORITY', $sql);
        } else {
            reset($this->builder);
            $cur_tbl = current($this->builder);
            $sql = 'update LOW_PRIORITY '.$cur_tbl->tbl.' as '.$cur_tbl->idx.' ';
            while(($cur_tbl=next($this->builder))!==false) {
                if(empty($cur_tbl->join)) continue;
                $sql .= $cur_tbl->join['mode'].' join '.$cur_tbl->tbl.' as '.$cur_tbl->idx;
                if(empty($cur_tbl->join['field_join'])) {
                    $sql .= ' using('.$cur_tbl->join['field'].')';
                } else {
                    $sql .= ' on '.$cur_tbl->idx.'.'.$cur_tbl->join['field'].'='.$cur_tbl->join['field_join'];
                }
            }

            $sql .= ' set ';
            $fields = array();
            foreach($this->builder as $cur_tbl) {
                $the_field = $cur_tbl->field('update');
                if(!empty($the_field)) $fields[] = $the_field;
            }
            $sql .= implode(',', $fields);

            $conditions = array();
            foreach($this->builder as $cur_tbl) {
                $the_condition = $cur_tbl->where();
                if(!empty($the_condition)) $conditions[] = $the_condition;
            }
            if(!empty($conditions)) $sql .= ' where '.implode(' and ', $conditions);

            /* don't suppor yet
            $orders = array();
            foreach($this->builder as $cur_tbl) {
                $the_order = $cur_tbl->order();
                if(!empty($the_order)) $orders[] = $the_order;
            }
            if(!empty($orders)) $sql .= ' order by '.implode(',', $orders);

            reset($this->builder);
            $sql .= current($this->builder)->limit();
            */
        }
        if($show) {
            return $sql;
        } else {
            return $this->query($sql);
        }
    }

    /**
     * 删除数据
     * @param bool $show
     * @return bool|int|mixed|string
     */
    public function delete($show = false) {
        if(count($this->builder)==1) {
            $sql = current($this->builder)->delete();
            $sql = preg_replace('/^delete/i', 'delete LOW_PRIORITY', $sql);
        } else {
            reset($this->builder);
            $cur_tbl = current($this->builder);
            $sql = 'delete LOW_PRIORITY [idx_list] from '.$cur_tbl->tbl.' as '.$cur_tbl->idx.' ';
            $idx = $cur_tbl->idx;
            while(($cur_tbl=next($this->builder))!==false) {
                $idx .= ', '.$cur_tbl->idx;
                $sql .= $cur_tbl->join['mode'].' join '.$cur_tbl->tbl.' as '.$cur_tbl->idx;
                $sql .= ' on '.$cur_tbl->idx.'.'.$cur_tbl->join['field'].'='.$cur_tbl->join['field_join'];
            }

            $conditions = array();
            foreach($this->builder as $cur_tbl) {
                $the_condition = $cur_tbl->where();
                if(!empty($the_condition)) $conditions[] = $the_condition;
            }
            if(!empty($conditions)) $sql .= ' where '.implode(' and ', $conditions);

            /* don't suppor yet
            $orders = array();
            foreach($this->builder as $cur_tbl) {
                $the_order = $cur_tbl->order();
                if(!empty($the_order)) $orders[] = $the_order;
            }
            if(!empty($orders)) $sql .= ' order by '.implode(',', $orders);

            reset($this->builder);
            $sql .= current($this->builder)->limit();
            */
            $sql = str_replace('[idx_list]', $idx, $sql);
        }
        if($show) {
            return $sql;
        } else {
            return $this->query($sql);
        }
    }

    /**
     * 更新替换数据
     * @return mixed
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
     * 批量执行查询语句
     * @param $SQLs
     * @return array|bool
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
     * 处理批量查询文本
     * @param $strSQL
     * @return array
     */
    public function handleSQL($strSQL) {
        $strSQL = trim($strSQL);
        $strSQL = preg_replace("/\/\*.*\*\//sU", '', $strSQL);
        $strSQL = preg_replace("/^(#|\-\-)[^\n]*\n?$/m", '', $strSQL);
        $strSQL = preg_replace("/\r/", "\n", $strSQL);
        $strSQL = preg_replace("/[\n]+/", "\n", $strSQL);
        $strSQL = preg_replace("/[\t ]+/", " ", $strSQL);
        $temp = preg_split("/;\s*\n/", $strSQL);
        $result = array();
        for($i=0,$m=count($temp); $i<$m; $i++) {
            if(str_replace("\n", '', $temp[$i]) != '') {
                $result[] = preg_replace("/^\n*(.*)\n*$/m", '\1', $temp[$i]);
            }
        }
        return $result;
    }

    /**
     * 执行数据查询文件
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
     * 检测数据连接状态
     * @param string $obj
     * @param string $type
     * @return bool|mixed|null
     */
    public function check($obj = 'connect', $type = 'object') {
        $result = false;
        if(isset($this->$obj)) {
            if($type=='object' && is_object($this->$obj)) {
                if($obj == 'result') {
                    $result = true;
                } elseif($obj == 'connect') {
                    if(empty($this->connect->errno)) {
                        $result = true;
                    } elseif(mysqli_errno($this->connect)==2006 || mysqli_errno($this->connect)==2013) {
                        $result = $this->reconnect();
                    }
                }
            } elseif($type=='bool' && is_bool($this->$obj)) {
                $result = $this->$obj;
            }
        }
        return $result;
    }

    /**
     * 释放数据查询
     */
    public function free() {
        if($this->check('result', 'object')) mysqli_free_result($this->result);
        $this->result = NULL;
        return;
    }

    /**
     * 关闭数据服务器连接
     * @param array $err_info
     * @return int
     */
    public function close(&$err_info = array()) {
        if($this->check('result')) $this->free();
        if($this->check()) mysqli_close($this->connect);
        return $this->count;
    }

    /**
     * 检查错误
     * @param array $err_info
     * @return bool
     */
    public function checkError(&$err_info = array()) {
        if(mysqli_errno($this->connect)) {
            $this->err_info = mysqli_errno($this->connect).' - '.mysqli_error($this->connect);
            $this->err = true;
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
        return;
    }

    /**
     * 错误处理
     * @param $str
     * @param bool $exit
     */
    protected function error($str, $exit=false) {
        if(!$this->err) {
            $this->err = true;
            $this->err_info .= mysqli_errno($this->connect).' - '.mysqli_error($this->connect);
        }
        $this->err_info .= ' ('.$str.')';
        $str = "\nQuery String: ".$this->sql."\n";
        $str .= 'MySQL Message: '.$this->err_info;
        parent::Error($str, $exit);
        return;
    }
}
