<?PHP
/********************************************
*                                           *
* Name    : Proxy for Database Module       *
* Modifier: Windy2000                       *
* Time    : 2018-11-02                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/

/**
    数据库代理类，无自身方法，全部根据interface调用数据库对象
    $myDb = new myDb('db_class', 'arg1', 'arg2', ..., 'argN')    // The arguments is those for the 'init' function of DB Class
    $myDb->function()                                            // Could be any function from specified DB module
*/
class myDb extends myProxy {
    protected
        $func_alias = array(
            'c' => 'connect',
            'i' => 'insert',
            's' => 'select',
            'u' => 'update',
            'd' => 'delete',
            'q' => 'query',
            'r' => 'record',
            'g' => 'result'
        );

    public function __construct() {
        spl_autoload_register(function ($class) {
            $file = __DIR__.'/database/'.$class.'.class.php';
            if(is_file($file)) require_once($file);
        });
        if(!empty(func_get_args())) call_user_func_array('parent::init', func_get_args());
    }
}

/**
 * 基础数据库函数
 */
trait base_db {
    protected
        $cache_mode = 255,
        $cache = null,
        $cache_ttl = 600;
    /**
     * 获取安全的名称（数据库、表及字段）
     * @param $name
     * @return null|string|string[]
     */
    public function safeName($name) {
        return preg_replace('/[^\w]+/', '', $name);
    }

    /**
     * 获取安全的字段值
     * @param $val
     * @return mixed
     */
    public function safeValue($val) {
        $search  = array('\\',      "\n",   "\r",   "'",    '"',    "\x1a", "\x00", chr(0xdf).chr(0x27));
        $replace = array('\\\\',    '\n',   '\r',   "\'",   '\"',   '\x1a', '\x00', '');
        return str_replace($search, $replace, $val);
    }


    /**
     * 设置外部缓存模式 0 - 关闭， 1 - 可读， 2 - 可写，3 - 可读写
     * @param null $mode
     * @return int|null
     */
    public function cache($mode=null) {
        if($mode!==null) {
            $this->cache_mode = $mode;
        }
        return $this->cache_mode;
    }

    /**
     * 设置外部缓存
     * @param $cache
     * @param int $ttl
     */
    public function setCache($cache, $ttl = 600) {
        $this->cache = $cache;
        $this->cache_ttl = $ttl;
    }

    /**
     * 检查缓存是否存在
     * @param $key
     * @return mixed
     */
    public function getCache($key) {
        return ($this->cache==null || ($this->cache_mode & 1) != 1) ? false : $this->cache->get($key);
    }

    /**
     * 写入缓存
     * @param $key
     * @param $result
     */
    public function writeCache($key, $result) {
        if($this->cache!=null && ($this->cache_mode & 2) == 2) $this->cache->set($key, $result, $this->cache_ttl);
    }
}

/**
 * SQL数据库变量
 */
trait base_sql {
    protected
        $host = '',
        $user = '',
        $pwd = '',
        $charset = 'utf8',
        $connect = NULL,
        $result = NULL,
        $sql = '',
        $db = '',
        $builder = array(),
        $delimiter = '';

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
            $this->builder[$tbl] = new SQLBuilder($tbl, $join, 't'.count($this->builder), $this->delimiter);
        } else {
            if(!empty($join)) $this->builder[$tbl]->join = $join;
        }
        return $this->builder[$tbl];
    }

    /**
     * 创建对象
     * @param $name
     * @param array $para
     * @param string $type
     * @param bool $show
     * @return bool|int
     */
    public function create($name, $para=[], $type='db', $show = false) {
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
                $sql = 'create table IF NOT EXISTS `'.$this->safeName($name).'` ('.chr(10);
                if(is_string($para['col'])) {
                    if(preg_match('#^\w+$#', $para['col'])) {
                        $sql = 'create table IF NOT EXISTS `'.$this->safeName($name).'` like `'.$para['col'].'`';
                        break;
                    } else {
                        $sql .= $para['col'];
                    }
                } else {
                    $cols =& $para['col'];
                    for($i=0,$m=count($cols);$i<$m;$i++) {
                        if(is_string($cols[$i])){
                            $cols[$i] = preg_replace('#^(\w+)#', '`\1`', $cols[$i]);
                            $sql .= $cols[$i].','.chr(10);
                        } else {
                            $sql .= '`'.$cols[$i]['name'].'` '.$cols[$i]['type'].' '.(isset($cols[$i]['null'])?'NULL':'NOT NULL');
                            if(!isset($cols[$i]['condition'])) $cols[$i]['condition'] = '';
                            if(is_string($cols[$i]['condition'])) {
                                $sql .= ' '.$cols[$i]['condition'];
                            } else {
                                foreach($cols[$i]['condition'] as $k => $v) {
                                    $sql .= $k.' \''.$v.'\'';
                                }
                            }
                            $sql .= chr(10);
                        }
                    }
                    if(isset($para['idx'])) {
                        if(is_string($para['idx'])) {
                            $sql .= 'INDEX idx_'.md5($para['idx']).'('.$para['idx'].'),'.chr(10);
                        } else {
                            $sql .= 'INDEX '.$para['idx'][0].'('.$para['idx'][1].'),'.chr(10);
                        }
                    }
                    if(isset($para['uni'])) $sql .= 'UNIQUE (`'.$para['uni'].'`),'.chr(10);
                    if(isset($para['pri'])) {
                        $sql .= 'PRIMARY KEY (`'.$para['pri'].'`)';
                    } else {
                        $sql = substr($sql, 0, -2);
                    }
                }
                $sql .= chr(10).')';
                if(!isset($para['engine'])) $para['engine'] = 'MyISAM';
                if(!isset($para['charset'])) $para['charset'] = $this->charset;
                $sql .= 'ENGINE='.$para['engine'].' DEFAULT CHARSET='.$para['charset'];
                if(isset($para['increment'])) $sql .= ' AUTO_INCREMENT='.$para['increment'];
                if(isset($para['comment'])) $sql .= ' COMMENT=\''.$para['comment'].'\'';
                break;
            case 'i':
            case 'idx':
            case 'index':
                if(is_string($para)) {
                    $para=['col'=>$para];
                }
                if(is_array($para['col'])) $para['col'] = implode(',', $para['col']);
                if(!isset($para['name'])) $para['name'] = 'idx_'.md5($para['col']);
                $sql = 'create index `'.$this->safeName($para['name']).'` on '.$this->safeName($name).'('.$para['col'].')';
                break;
            default:
                $sql = 'create database IF NOT EXISTS `'.$this->safeName($name).'`';
                if(empty($para)) $para = $this->charset;
                $sql .= ' default charset '.$para.' COLLATE '.$para.'_unicode_ci';
        }
        $sql = $this->correctDelimiter($sql);
        return $show ? $sql : $this->query($sql);
    }

    /**
     * 移除对象
     * @param $name
     * @param string $type
     * @param bool $show
     * @return bool|int|string
     */
    public function drop($name, $type='db', $show = false) {
        $sql = 'drop ';
        switch($type) {
            case 't':
            case 'tbl':
            case 'table':
                $sql .= 'table IF EXISTS `'.$this->safeName($name).'`';
                break;
            case 'i':
            case 'idx':
            case 'index':
                $sql .= 'index IF EXISTS `'.$this->safeName($name[0]).'` on `'.$this->safeName($name[1]).'`';
                break;
            default:
                $sql .= 'database IF EXISTS `'.$this->safeName($name).'`';
        }
        $sql = $this->correctDelimiter($sql);
        return $show ? $sql : $this->query($sql);
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
        $sql = $this->correctDelimiter($sql);
        return $show ? $sql : $this->query($sql);
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
                    if(empty($fields)) {
                        $the_field = $cur_tbl->dl.$cur_tbl->idx.$cur_tbl->dr.'.*';
                    } else {
                        $the_field = '';
                    }
                }
                if(!empty($the_field)) $fields[] = $the_field;
            }
            $sql .= implode(',', $fields);

            reset($this->builder);
            $cur_tbl = current($this->builder);
            $sql .= ' from '.$cur_tbl->tbl.' as '.$cur_tbl->idx.' ';
            while(($cur_tbl=next($this->builder))!==false) {
                if(empty($cur_tbl->join)) continue;
                $sql .= ' '.$cur_tbl->join['mode'].' join '.$cur_tbl->tbl.' as '.$cur_tbl->idx;
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

            $group_field = '';
            $group_having = '';
            foreach($this->builder as $cur_tbl) {
                $group = $cur_tbl->group();
                if(!empty($group)) {
                    if(empty($group_field)) {
                        $group_field = ' group by ';
                    } else {
                        $group_field .= ' , ';
                    }
                    $group_field .= $cur_tbl->idx.'.`'.str_replace(',', '`, '.$cur_tbl->idx.'.`', $group['field']).'`';
                }
                if(isset($group['having']) && !empty($group['having'])) {
                    if(empty($group_having)) {
                        $group_having = ' having ';
                    } else {
                        $group_having .= ' and ';
                    }
                    $tmp = new SQLBuilder('tmp', [], '', '`');
                    $tmp->where($group['having']);
                    $group['having'] = $tmp->where();
                    $group_having .= preg_replace('#(`\w+`)#', $cur_tbl->idx.'.\1', $group['having']);
                    unset($tmp);
                }
            }
            if(!empty($group_field)) $sql .= $group_field.$group_having.' ';

            $orders = array();
            foreach($this->builder as $cur_tbl) {
                $the_order = $cur_tbl->order();
                if(!empty($the_order)) $orders[] = $the_order;
            }
            if(!empty($orders)) $sql .= ' order by '.implode(',', $orders);

            reset($this->builder);
            $sql .= current($this->builder)->limit();
        }
        $sql = $this->correctDelimiter($sql);
        return $show ? $sql : $this->query($sql);
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
                $the_field = $cur_tbl->field();
                if($the_field!=='*') $fields[] = $the_field;
            }
            $sql .= implode(',', $fields);

            $conditions = array();
            foreach($this->builder as $cur_tbl) {
                $the_condition = $cur_tbl->where();
                if(!empty($the_condition)) $conditions[] = $the_condition;
            }
            if(!empty($conditions)) $sql .= ' where '.implode(' and ', $conditions);

            /* don't support yet
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
        $sql = $this->correctDelimiter($sql);
        return $show ? $sql : $this->query($sql);
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

            /* don't support yet
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
        $sql = $this->correctDelimiter($sql);
        return $show ? $sql : $this->query($sql);
    }

    /**
     * 返回单一结果集
     * @param $sql
     * @param int $mode
     * @return array|bool|null
     */
    public function record($sql='', $mode = 2) {
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
     * @param string $sql
     * @param int $mode
     * @return false|mixed
     */
    public function records($sql='', $mode = 2) {
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
            if($result = $this->record($sql, 1)) {
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
     * @param string $sql
     * @return array|bool|mixed|null
     */
    public function count($sql='') {
        if(empty($sql)) $sql = $this->select(1);
        if(stripos($sql, 'select')!==0) return 0;
        $sql = preg_replace('#limit[\s\d, ]+$#i', '', $sql);
        $sql = preg_replace('#^select(.+?)from#i', 'select 1 as t from', $sql);
        return $this->result('select count(*) from ('.$sql.') as cnt');
    }

    /**
     * 将mysql定界符更正为对应数据库的定界符
     * @param $sql
     * @return mixed
     */
    public function correctDelimiter($sql) {
        $dl = $dr = '';
        if(!empty($this->delimiter)) {
            if(is_string($this->delimiter)) {
                $dl = $dr = $this->delimiter;
            } elseif(is_array($this->delimiter)) {
                $dl = $this->delimiter[0];
                $dr = $this->delimiter[1];
            }
        }
        return preg_replace('#`(\w+)`#', $dl.'\1'.$dr, trim($sql,"\r\n"));
    }
}

/**
 * sql语句构建
 */
class SQLBuilder {
    use base_db;
    public 
        $join = array(
            'mode' => 'left',
            'field' => '',
            'field_join' => ''
        ),
        $dl = '',
        $dr = '',
        $tbl = '',
        $sel_prefix = '',
        $idx = '';
    protected
        $condition = array(),
        $fields = array(),
        $order = array(),
        $values = array(),
        $group = array(),
        $limit = '';

    /**
     * 构造函数
     * @param $tbl
     * @param array $join
     * @param string $idx
     * @param string $delimiter
     */
    public function __construct($tbl, $join = array(), $idx='', $delimiter='') {
        if(!empty($delimiter)) {
            if(is_string($delimiter)) {
                $this->dl = $this->dr = $delimiter;
            } elseif(is_array($delimiter)) {
                $this->dl = $delimiter[0];
                $this->dr = $delimiter[1];
            }
        }
        if(strpos($tbl, '.')) {
            $tbl = explode('.', $tbl);
            $this->tbl = $this->dl.$this->safeName($tbl[0]).$this->dr.'.'.$this->dl.$this->safeName($tbl[1]).$this->dr;
        } else {
            $this->tbl = $this->dl.$this->safeName($tbl).$this->dr;
        }
        if(!empty($join)) {
            if(!isset($join['mode'])) $join['mode'] = 'left';
            $join['field'] = str_replace(' ', '', $join['field']);
            $join['field'] = $this->dl.str_replace(',', $this->dr.'.'.$this->dl, $join['field']).$this->dr;
            if(!isset($join['field_join'])) {
                $join['field_join'] = '';
            } else {
                if(!is_array($join['field_join'])) {
                    if(strpos($join['field_join'], '.')===false) {
                        $join['field_join'] = 't0.'.$join['field_join'];
                    }
                    $join['field_join'] = explode('.', $join['field_join']);
                }
                $join['field_join'] = $this->dl.implode($this->dr.'.'.$this->dl, $join['field_join']).$this->dr;
            }
            $this->join = $join;
        }
        $this->idx = $idx;
    }

    /**
     * 复位所有参数
     * @param bool $complete
     */
    public function reset($complete = false) {
        if($complete) {
            $this->join = array();
            $this->tbl = '';
            $this->idx = '';
        }
        $this->sel_prefix = '';
        $this->condition = array();
        $this->fields = array();
        $this->values = array();
        $this->order = array();
        $this->group = array();
        $this->limit = '';
    }

    /**
     * 添加exists条件
     * @param $para
     * @param array $condition
     * @param bool $not
     * @param string $mode
     * @return $this
     */
    public function exists($para, $condition=[], $not = false, $mode='and') {
        $sql = ($not?' NOT ':' ').'EXISTS (';
        if(is_string($para)) {
            $sql .= $para;
        } else {
            list($tbl, $field) = $para;
            $tbl = $this->dl.$this->safeName($tbl).$this->dr;
            $field = $this->dl.$this->safeName($field).$this->dr;
            if(!empty($condition)) {
                $tmp = new self($tbl, '', '', '`');
                $tmp->where($condition);
                $condition = $tmp->where();
                unset($tmp);
            } else {
                $condition = '';
            }
            $sql .= 'select 1 from '.$tbl.' where '.$tbl.'.'.$field.'='.$this->idx.'.'.$field.' and '.$condition;
        }
        $sql .= ')';
        $mode = strtolower($mode);
        if($mode!='and') $mode = 'or';
        $this->condition[] = array($sql, null, null, $mode);
        return $this;
    }

    /**
     * 条件处理
     * @param $field
     * @param null $condition
     * @param null $value
     */
    protected function condition(&$field, &$condition=null, &$value=null) {
        if(
            !preg_match('/^\(.+\)$/', $field)
            &&
            !preg_match('/^[a-z]\w+\(.+\)$/i', $field)
            &&
            $condition != '!'
        ) $field = $this->safeName($field);
        $condition = strtolower(trim($condition));
        switch($condition) {
            case '=':
            case '<':
            case '<=':
            case '>':
            case '>=':
            case '<>':
            case '!=':
                $value = "'".$this->safeValue($value)."'";
                break;
            case 'b=':
                $condition = '=';
                $value = " binary '".$this->safeValue($value)."'";
                break;
            case 'n=':
            case 'n<':
            case 'n<=':
            case 'n>':
            case 'n>=':
            case 'n<>':
            case 'n!=':
                $condition = substr($condition, 1);
                $value = intval($value);
                break;
            case 'd=':
            case 'd<':
            case 'd<=':
            case 'd>':
            case 'd>=':
            case 'd<>':
            case 'd!=':
                $condition = substr($condition, 1);
                if(preg_match('/([ymdqwh])(\+|\-)(\d+)/i', $value[1], $match)) {
                    switch(strtolower($match[1])) {
                        case 'y': $match[1] = 'YEAR'; break;
                        case 'm': $match[1] = 'MONTH'; break;
                        case 'd': $match[1] = 'DAY'; break;
                        case 'q': $match[1] = 'QUARTER'; break;
                        case 'w': $match[1] = 'WEEK'; break;
                        default: $match[1] = 'HOUR';
                    }
                    if(preg_match('#^\w+$#', $value[0])) $value[0] = $this->dl.$value[0].$this->dr;
                    if($match[2]=='-') {
                        $value = 'DATE_SUB('.$value[0].', INTERVAL '.$match[3].' '.$match[1].')';
                    } else {
                        $value = 'DATE_ADD('.$value[0].', INTERVAL '.$match[3].' '.$match[1].') ';
                    }
                } else {
                    $value = "'".$this->safeValue($value)."'";
                }
                break;
            case 'f=':
            case 'f<':
            case 'f<=':
            case 'f>':
            case 'f>=':
            case 'f<>':
            case 'f!=':
                $condition = substr($condition, 1);
                break;
            case 'like':
            case 'like binary':
            case 'not like':
            case 'not like binary':
                if(strpos($value, '%')!==false) {
                    $value = "'".$this->safeValue($value)."'";
                } else {
                    $value = "'%".$this->safeValue($value)."%'";
                }
                break;
            case 'rlike':
            case 'regexp':
            case 'rlike binary':
            case 'regexp binary':
            case 'not rlike':
            case 'not regexp':
            case 'not rlike binary':
            case 'not regexp binary':
                $value = "'".$value."'";
                break;
            case 'in':
            case 'nin':
            case 'not in':
            case 'not nin':
                if(!is_string($value) || !preg_match('#^\((.+)\)$#', $value)) {
                    if(is_string($value)) {
                        $value = str_replace("'", '', $value);
                        $value = str_replace('"', '', $value);
                        $value = str_replace(', ', ',', $value);
                        $value = explode(',', $value);
                    }
                    if(strlen($condition)==2 || strlen($condition)==6) {
                        $value = array_map(array($this, 'safeValue'), $value);
                        $value = "('".implode("', '", $value)."')";
                    } else {
                        $value = array_map('intval', $value);
                        $value = '('.implode(',', $value).')';
                    }
                }
                $condition = str_replace('nin', 'in', $condition);
                break;
            case 'is':
            case 'is not':
                if(strtolower($value)!='null') $value = 'null';
                break;
            case '!':
                $condition = '';
                $value = '';
                break;
            case null:
                if(preg_match('/^\((.+)\)$/', $field, $match)) {
                    $field = $match[1];
                    if(preg_match('#^([a-z]\w+)\(([a-z]\w+)(.*)\)$#i', $field, $match)) {
                        $field = $match[1].'('.$this->idx.'.`'.$match[2].'`' . $match[3].')';
                    }
                    $condition = '';
                    $value = '';
                } else {
                    $condition = 'is not';
                    $value = 'NULL';
                }
                break;
            default:
                $condition = '=';
                $value = "'".$this->safeValue($value)."'";
                break;
        }
    }

    /**
     * 符合条件处理
     * @param $condition
     * @param int $layer
     * @return null|string|string[]
     */
    protected function condition_multi($condition, $layer=0) {
        if(empty($condition)) return '';
        $result = '';
        if(is_string($condition)) {
            $result .= $condition;
        }elseif(is_string($condition[0])) {
            $this->condition($condition[0], $condition[1], $condition[2]);
            $mode = isset($condition[3])?$condition[3]:'and';
            if(!preg_match('/^\(.+\)$/', $condition[0])) {
                $condition[0] = $this->dl.$condition[0].$this->dr;
            }
            return ' ' . $mode . ' ' . $condition[0] . ' ' . $condition[1] . ' ' . $condition[2];
        } else {
            $cur_mode = '';
            if($layer>0) { 
                if(is_string(end($condition))) {
                    $cur_mode = array_pop($condition);
                } else {
                    $cur_mode = 'and';
                }
            }
            $result .= ' ' . $cur_mode . ' (1=1';
            foreach($condition as $cur) {
                $result .= $this->condition_multi($cur, $layer++);
            }
            $result .= ')';
        }
        return preg_replace('/1=1\s+\w+ /', '', $result);
    }

    /**
     * 查询条件设置
     * @param null $field
     * @param null $condition
     * @param null $value
     * @param string $mode
     * @return $this|null|string|void
     */
    public function where($field=null, $condition=null, $value=null, $mode='and') {
        if(is_null($field)) {
            $condition = '';
            if(!empty($this->condition)) {
                $org = $this->condition;
                for($i=0,$m=count($this->condition);$i<$m;$i++) {
                    if($i>0) $condition .= ' '.$this->condition[$i][3];
                    if(empty($this->condition[$i][1])) {
                        if(!empty($this->idx)) $this->condition[$i][0] = preg_replace('/(\s|\()'.preg_quote($this->dl).'/', '\1'.$this->idx.'.'.$this->dl, $this->condition[$i][0]);
                    } else {
                        if(
                            !preg_match('/^[a-z]\w+\(.+\)/i', $this->condition[$i][0])
                            &&
                            !preg_match('/^\(.+\)/', $this->condition[$i][0])
                        ) {
                            $this->condition[$i][0] = $this->dl.$this->condition[$i][0].$this->dr;
                            if(!empty($this->idx)) $this->condition[$i][0] = $this->dl.$this->idx.$this->dr.'.'.$this->condition[$i][0];
                        }
                    }
                    $condition .= ' '.$this->condition[$i][0].' '.$this->condition[$i][1].' '.$this->condition[$i][2];
                }
                $this->condition = $org;
            }
            $condition = preg_replace('@^\s*and*@', ' ', $condition);
            return $condition;
        } elseif(is_array($field)) {
            if(empty($field)) return $this;
            $condition = '!';
            if(is_array($field[0]) && is_string(end($field))
                ||
                is_string($field[0]) && count($field)==4) {
                $mode = array_pop($field);
            } else {
                $mode = 'and';
            }
            $field = $this->condition_multi($field);
        } elseif($field=='[reset]') {
            $this->condition = array();
            return;
        }
        if($field!='') {
            if(is_null($value) && $condition!='!' &&
                preg_match('#^(.+?)([\=<>]+)(.+)$#', $field, $match)
            ) {
                $mode = $condition;
                $field = $match[1];
                $condition = 'f'.$match[2];
                $value = $match[3];
            }
            $this->condition($field, $condition, $value);
            $mode = strtolower($mode);
            if($mode!='or') $mode = 'and';
            $this->condition[] = array($field, $condition, $value, $mode);
        }
        return $this;
    }

    /**
     * 查询列设置
     * @param null $field
     * @return $this|string|void
     */
    public function field($field = null) {
        if(is_null($field)) {
            if(empty($this->fields)) return '*';
            if(isset($this->fields[0])) { //select
                if(substr(strtolower($this->fields[0]),0,6)=='count(') return $this->fields[0];
                if(!empty($this->idx)) {
                    for($i=0,$m=count($this->fields);$i<$m;$i++) {
                        if(!preg_match('/^([a-z]\w+)\((.+)\)/i', $this->fields[$i])) {
                            $this->fields[$i] = $this->dl.$this->idx.$this->dr.'.'.$this->fields[$i];
                        }
                    }
                }
                return implode(',', $this->fields);
            } else { //update
                $fields = array();
                foreach($this->fields as $key => $value) {
                    $key = $this->dl.$this->safeName($key).$this->dr;
                    if(!empty($this->idx)) $key = $this->dl.$this->idx.$this->dr.'.'.$key;
                    if(is_array($value)) {
                        $value = $this->dl.$this->safeName($value[0]).$this->dr.'.'.$this->dl.$this->safeName($value[1]).$this->dr;
                    } elseif(is_null($value) || strtolower($value)=='null') {
                        $value = 'NULL';
                    } elseif(preg_match('/^(\+|\-)(\d+)$/', $value, $match)) {
                        $value = $key.$match[1].$match[2];
                    } elseif(preg_match('/^\(.+\)$/', $value)) {
                        $value = preg_replace('/^\((.+)\)$/', '\1', $value);
                    } else {
                        $value = "'".$this->safeValue($value)."'";
                    }
                    $fields[] = $key.'='.$value;
                }
                return implode(',', $fields);
            }
        } elseif($field=='[reset]') {
            $this->fields = array();
            return $this;
        } elseif(is_string($field) && in_array(strtolower($field), ['all','distinct','distinctrow'])) {
            $this->sel_prefix = $field;
            return $this;
        }
        if(func_num_args()>1) $field = func_get_args();
        if(is_string($field)) $field = explode(',', preg_replace('#,\s+#', ',', $field));
        if(isset($field[0])) { //select
            for($i=count($field)-1;$i>=0;$i--) {
                if(is_array($field[$i])) {
                    $field[$i] = $field[$i][0].' as '.$this->dl.$field[$i][1].$this->dr;
                }
                if(preg_match('/^([a-z]\w+)\((.+)\)(.*)$/i', $field[$i], $match)) {
                    if(preg_match('/^[a-z]\w+$/', $match[2])) $match[2] = $this->dl.$match[2].$this->dr;
                    $field[$i] = $match[1].'('.$match[2].')';
                    if(preg_match('#^\s+as\s+([a-z]\w+)$#i', $match[3], $m2)) {
                        $field[$i] .= ' as '.$this->dl.$m2[1].$this->dr;
                    } else {
                        $field[$i] .= $match[3];
                    }
                } elseif(preg_match('/^([a-z]\w+)((:|=>)|\s+as\s+)([a-z]\w+)$/i', $field[$i], $match)) {
                    $field[$i] = $this->dl.$match[1].$this->dr.' as '.$this->dl.$match[4].$this->dr;
                } elseif($field[$i]!='*') {
                    $field[$i] = $this->dl.$this->safeName($field[$i]).$this->dr;
                }
            }
        } else { //update
            unset($field['submit']);
        }
        $this->fields = array_merge($this->fields, $field);
        return $this;
    }

    /**
     * 直接添加insert的插入值
     * @return $this
     */
    public function values() {
        $val = func_get_args();
        if(count($val)>0) {
            if(is_array($val[0])) {
                $this->values = array_merge($this->values, $val);
            } else {
                $this->values[] = $val;
            }
        }
        return $this;
    }

    /**
     * 排序条件设置
     * @param null $field
     * @param bool $desc
     * @return $this|string|void
     */
    public function order($field=null, $desc = false) {
        if(is_null($field)) {
            $order = '';
            if(!empty($this->order)) {
                $org = $this->order;
                for($i=0,$m=count($this->order);$i<$m;$i++) {
                    if($i>0 ) $order .= ', ';
                    if(!empty($this->idx)) $this->order[$i] = $this->dl.$this->idx.$this->dr.'.'.$this->order[$i];
                    $order .= $this->order[$i];
                }
                $this->order = $org;
            }
            return $order;
        } elseif($field=='[reset]') {
            $this->order = array();
            return;
        }
        if(is_array($field)) {
            $field = $field[0];
            $desc = $field[1];
        }
        if(preg_match('#^[\w\s]+\(.*\)$#', $field)) {
            $the_order = $field;
            $desc = false;
        } else {
            $the_order = $this->dl.$this->safeName($field).$this->dr;
        }
        if($desc) $the_order .= ' desc';
        $this->order[] = $the_order;
        return $this;
    }

    /**
     * 结果限制设置
     * @param null $start
     * @param null $count
     * @return $this|string|void
     */
    public function limit($start=null, $count=null) {
        if(is_null($start)) {
            if(empty($this->limit)) return '';
            return ' limit '.$this->limit;
        } elseif($start==='[reset]') {
            $this->limit = '';
            return;
        }
        if(is_numeric($start)) {
            $this->limit = intval($start);
            if(is_numeric($count)) $this->limit = $this->limit . ',' . $count;
        } else {
            if(is_string($start)) {
                $start = preg_split('#[\s,]+#', $start);
            }
            $this->limit = intval($start[0]);
            if(isset($start[1]) && is_numeric($start[1])) $this->limit = $this->limit . ',' . $start[1];
        }
        return $this;
    }

    /**
     * 添加 group 信息
     * @param null $group
     * @return $this|array
     */
    public function group($group=null) {
        if(is_null($group)) {
            return $this->group;
        } else {
            if(func_num_args()>1) $group = func_get_args();
            if(is_string($group)) $group = ['field'=>$group];
            if(isset($group[0])) {
                $group['field'] = $group[0];
                unset($group[0]);
            }
            if(is_array($group['field'])) $group['field'] = implode(',', $group['field']);
            $group['field'] = str_replace(' ', '', $group['field']);
            if(isset($group[1])) {
                $group['having'] = $group[1];
                unset($group[1]);
            }
            if(isset($group['field'])) $this->group = $group;
        }
        return $this;
    }

    /**
     * 生成选择查询语句
     * @return string
     */
    public function select() {
        $idx = $this->idx;
        $this->idx = '';
        $sql = 'select ';
        if(!empty($this->sel_prefix)) $sql = $sql.$this->sel_prefix.' ';
        if(empty($this->fields)) {
            $sql .= '*';
        } else {
            $sql .= $this->field();
        }
        $sql .= ' from '.$this->tbl;
        if(!empty($idx)) $sql .= ' as '.$idx;
        if(!empty($this->condition)) {
            $sql .= ' where '.$this->where();
        }
        if(!empty($this->group)) {
            $sql .= ' group by '.$this->dl.$this->safeName($this->group['field']).$this->dr;
            if(isset($this->group['having']) && !empty($this->group['having'])) {
                $tmp = new self('tmp', [], '', [$this->dl,$this->dr]);
                $tmp->where($this->group['having']);
                $sql .= ' having '.$tmp->where();
                unset($tmp);
            }
        }
        if(!empty($this->order)) {
            $sql .= ' order by '.$this->order();
        }
        $sql .= $this->limit();
        $this->idx = $idx;
        return $sql;
    }

    /**
     * 生成插入查询语句
     * @param bool $replace
     * @return string
     */
    public function insert($replace=false) {
        $sql = ($replace?'replace':'insert').' into '.$this->tbl;
        $fields = array();
        $values = array();
        foreach($this->fields as $key => $value) {
            if(is_numeric($key) || strtolower($key) == 'submit') continue;
            if(is_null($value) || strtolower($value)=='null') {
                $value = 'NULL';
            } elseif(!preg_match('/^\w{3,15}\(.*\)$/', $value)) {
                $value = "'".$this->safeValue($value)."'";
            }
            $fields[] = $this->dl.$this->safeName($key).$this->dr;
            $values[] = $value;
        }
        for($i=0,$m=count($this->values);$i<$m;$i++) {
            if(!is_array($this->values[$i])) continue;
            for($j=0,$m2=count($this->values[$i]);$j<$m2;$j++) {
                if(strtolower($this->values[$i][$j])=='null') {
                    $this->values[$i][$j] = 'NULL';
                } elseif(!preg_match('/^\w{3,15}\(.*\)$/', $this->values[$i][$j])) {
                    $this->values[$i][$j] = "'".$this->safeValue($this->values[$i][$j])."'";
                }
            }
            $this->values[$i] = '('.implode(',', $this->values[$i]).')';
        }
        if(!empty($fields)) $sql .= ' ('.implode(',', $fields).')';
        $sql .= ' values ';
        if(count($values)>0) $sql .= ' ('.implode(',', $values).')';
        if(count($this->values)>0) $sql .= ','.implode(',', $this->values);
        $sql = str_replace('values ,(', 'values (', $sql);
        return $sql;
    }

    /**
     * 生成更新查询语句
     * @return string
     */
    public function update() {
        $idx = $this->idx;
        $this->idx = '';
        $sql = 'update '.$this->tbl;
        $sql .= ' set '.$this->field();
        
        if(!empty($this->condition)) {
            $sql .= ' where '.$this->where();
        }
        if(!empty($this->order)) {
            $sql .= ' order by '.$this->order();
        }
        $sql .= $this->limit();
        $this->idx = $idx;
        return $sql;
    }

    /**
     * 生成删除查询语句
     * @return string
     */
    public function delete() {
        $idx = $this->idx;
        $this->idx = '';
        if(empty($this->condition) && empty($this->limit)) {
            $sql = 'truncate table '.$this->tbl;
        } else {
            $sql = 'delete from '.$this->tbl;
            if(!empty($this->condition)) {
                $sql .= ' where '.$this->where();
            }
            if(!empty($this->order)) {
                $sql .= ' order by '.$this->order();
            }
            $sql .= $this->limit();
        }
        $this->idx = $idx;
        return $sql;
    }
}