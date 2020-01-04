<?php
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
		spl_autoload_register(function($class) {
			$file = __DIR__.'/database/'.$class.'.class.php';
			if(is_file($file)) require_once($file);
		});
		if(!empty(func_get_args())) call_user_func_array('parent::init', func_get_args());
	}
}

/**
 * 基础数据库函数
 */
trait base_db{
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
		$search = array("\x00", "\n", "\r", '\\', "'", "'", "\x1a", chr(0xdf).chr(0x27));
		$replace = array('\x00', '\n', '\r', '\\\\' ,"\'", '\"', '\x1a', '');
		return str_replace($search, $replace, $val);
	}
}

/**
 * SQL数据库变量
 */
trait base_sql {
	protected
		$host = "",
		$user = "",
		$pwd	= "",
		$charset = "utf8",
		$connect = NULL,
		$result	= NULL,
		$sql = "",
		$db	= "",
		$builder = array();
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
		$idx = '';
	protected 
		$condition = array(),
		$fields = array(),
		$order = array(),
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
		if(strpos($tbl,'.')) {
			$tbl = explode('.', $tbl);
			$this->tbl = $this->dl.$this->safeName($tbl[0]).$this->dr.'.'.$this->dl.$this->safeName($tbl[1]).$this->dr;
		} else {
			$this->tbl = $this->dl.$this->safeName($tbl).$this->dr;
		}
		if(!empty($join)) {
			if(!isset($join['mode'])) $join['mode'] = 'left';
			$join['field'] = str_replace(' ','', $join['field']);
			$join['field'] = $this->dl.str_replace(',', $this->dr.'.'.$this->dl,$join['field']).$this->dr;
			if(!isset($join['field_join'])) {
				$join['field_join'] = '';
			} else {
				if(!is_array($join['field_join'])) {
					if(strpos($join['field_join'],'.')==false) {
						$join['field_join'] = 't0.'.$join['field_join'];
					}
					$join['field_join'] = explode('.', $join['field_join']);
				}
				$join['field_join'] = $this->dl.implode($this->dr.'.'.$this->dl,$join['field_join']).$this->dr;
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
		$this->condition = array();
		$this->fields = array();
		$this->order = array();
		$this->limit = '';
	}

	/**
	 * 条件处理
	 * @param $field
	 * @param null $condition
	 * @param null $value
	 */
	public function condition(&$field, &$condition=null, &$value=null) {
		if(!preg_match("/^\(.+\)$/", $field) && $condition!='!') $field = $this->safeName($field);
		$condition = strtolower(trim($condition));
		switch($condition) {
			case "=":
			case "<":
			case "<=":
			case ">":
			case ">=":
			case "<>":
			case "!=":
				$value = "'".$this->safeValue($value)."'";
				break;
			case "n=":
			case "n<":
			case "n<=":
			case "n>":
			case "n>=":
			case "n<>":
			case "n!=":
				$condition = substr($condition, 1);
				$value = intval($value);
				break;
			case "d=":
			case "d<":
			case "d<=":
			case "d>":
			case "d>=":
			case "d<>":
			case "d!=":
				$condition = substr($condition, 1);
				if(preg_match("/([ymdqwh])(\+|\-)(\d+)/i",$value[1],$match)) {
					switch(strtolower($match[1])) {
						case "y": $match[1] = "YEAR"; break;
						case "m": $match[1] = "MONTH"; break;
						case "d": $match[1] = "DAY"; break;
						case "q": $match[1] = "QUARTER"; break;
						case "w": $match[1] = "WEEK"; break;
						default: $match[1] = "HOUR";
					}
					if($match[2]=="-") {
						$value = 'DATE_SUB('.$value[0].',INTERVAL '.$match[3].' '.$match[1].')';
					} else {
						$value = 'DATE_ADD('.$value[0].',INTERVAL '.$match[3].' '.$match[1].') ';
					}
				} else {
					$value = "'".$this->safeValue($value)."'";
				}
				break;
			case "f=":
			case "f<":
			case "f<=":
			case "f>":
			case "f>=":
			case "f<>":
			case "f!=":
				$condition = substr($condition, 1);
				break;
			case "like":
			case "like binary":
			case "not like":
			case "not like binary":
				if(strpos($value, "%")!==false) {
					$value = "'".$this->safeValue($value)."'";
				} else {
					$value = "'%".$this->safeValue($value)."%'";
				}
				break;
			case "rlike":
			case "regexp":
			case "rlike binary":
			case "regexp binary":
			case "not rlike":
			case "not regexp":
			case "not rlike binary":
			case "not regexp binary":
				$value = "'".$value."'";
				break;
			case "in":
			case "nin":
			case "not in":
			case "not nin":
				if(is_string($value)) {
					$value = str_replace("'", '', $value);
					$value = str_replace("'", '', $value);
					$value = str_replace(", ", ",", $value);
					$value = explode(",", $value);
				}
				if(strlen($condition)==2 || strlen($condition)==6) {
					$value = array_map(array($this, 'safeValue'), $value);
					$value = "('".implode("','", $value)."')";
				} else {
					$value = array_map("intval", $value);
					$value = "(".implode(",", $value).")";
				}
				$condition = str_replace("nin", "in", $condition);
				break;
			case "is":
			case "is not":
				if(empty($value)) $value = "NULL";
				break;
			case "!":
				$condition = '';
				$value = '';
				break;
			case null:
				if(!preg_match("/^\(.+\)$/", $field)){
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
	public function condition_multi($condition, $layer=0) {
		$result = '';
		if(is_string($condition)) {
			$result .= $condition;
		}elseif(is_string($condition[0])) {
			$this->condition($condition[0], $condition[1], $condition[2]);
			$mode = isset($condition[3])?$condition[3]:'and';
			if(!preg_match("/^\(.+\)$/", $condition[0])) {
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
						if(!empty($this->idx)) $this->condition[$i][0] = preg_replace('/(\s|\()'.preg_quote($this->dl).'/','\1'.$this->idx.'.'.$this->dl,$this->condition[$i][0]);
					} elseif(!preg_match("/^\(.+\)$/", $this->condition[$i][0])) {
						$this->condition[$i][0] = $this->dl.$this->condition[$i][0].$this->dr;
						if(!empty($this->idx)) $this->condition[$i][0] = $this->dl.$this->idx.$this->dr.'.'.$this->condition[$i][0];
					}
					$condition .= ' '.$this->condition[$i][0].' '.$this->condition[$i][1].' '.$this->condition[$i][2];
				}
				$this->condition = $org;
			}
			return $condition;
		} elseif(is_array($field)) {
			$condition = '!';
			if(is_string(end($field))) {
				$mode = array_pop($field);
			} else {
				$mode = 'and';
			}
			$field = $this->condition_multi($field);
		} elseif($field=='[reset]') {
			$this->condition = array();
			return;
		}
		$this->condition($field, $condition, $value);
		$mode = strtolower($mode);
		if($mode!='and') $mode = 'or';
		$this->condition[] = array($field, $condition, $value, $mode);
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
		$the_order = $this->dl.$this->safeName($field).$this->dr;
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
		$this->limit = intval($start);
		if(is_numeric($count)) $this->limit = $this->limit . ',' . $count;
		return $this;
	}

	/**
	 * 查询列设置
	 * @param null $field
	 * @return $this|string|void
	 */
	public function field($field = null) {
		if(is_null($field) || $field=='select') {
			if(empty($this->fields)) return '';
			return empty($this->idx)?implode(', ', $this->fields):($this->dl.$this->idx.$this->dr.'.'.implode(', '.$this->dl.$this->idx.$this->dr.'.', $this->fields));
		} elseif($field=='update') {
			if(empty($this->fields)) return '';
			$fields = array();
			foreach($this->fields as $key => $value) {
				$key = $this->dl.$this->safeName($key).$this->dr;
				if(!empty($this->idx)) $key = $this->dl.$this->idx.$this->dr.'.'.$key;
				if(is_array($value)) {
					$value = $this->dl.$this->safeName($value[0]).$this->dr.'.'.$this->dl.$this->safeName($value[1]).$this->dr;
				} elseif(is_null($value) || strtolower($value)=="null") {
					$value = "NULL";
				} elseif(preg_match("/^(\+|\-)(\d+)$/", $value, $match)) {
					$value = $key.$match[1].$match[2];
				} elseif(preg_match("/^\(.+\)$/", $value)) {
					$value = preg_replace("/^\((.+)\)$/", '\1', $value);
				} else {
					$value = "'".$this->safeValue($value)."'";
				}
				$fields[] = $key." = ".$value;
			}
			return implode(',',$fields);
		} elseif($field=='[reset]') {
			$this->fields = array();
			return;
		}
		if(is_string($field)) $field = explode(',', str_replace(' ','',$field));
		if(isset($field[0])) {
			for($i=count($field)-1;$i>=0;$i--) {
				if(is_array($field[$i])) {
					if(!preg_match('/^\(.+\)$/', $field[$i][0])) $field[$i][0] = $this->dl.$this->safeName($field[$i][0]).$this->dr;
					$field[$i] = $this->dl.$this->safeName($field[$i][0]).$this->dr.' as '.$this->dl.$this->safeName($field[$i][1]).$this->dr;
				} else {
					if($field[$i]!='*') $field[$i] = $this->dl.$this->safeName($field[$i]).$this->dr;
				}
			}
		} else {
			unset($field['submit']);
		}
		$this->fields = array_merge($this->fields, $field);
		return $this;
	}

	/**
	 * 生成选择查询语句
	 * @param array $group
	 * @return string
	 */
	public function select($group=array()) {
		$idx = $this->idx;
		$this->idx = '';
		$sql = 'select ';
		if(empty($this->fields)) {
			$sql .= '*';
		} else {
			$sql .= $this->field();
		}
		$sql .= ' from '.$this->tbl;
		if(!empty($this->idx)) $sql .= ' as '.$this->idx;
		if(!empty($this->condition)) {
			$sql .= ' where '.$this->where();
		}
		if(!empty($group)) {
			$sql .= ' group by '.$this->dl.$this->safeName($group['field']).$this->dr;
			if(isset($group['having'])) $sql .= ' having '.$this->dl.$this->safeName($group['having']).$this->dr;
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
		if(!empty($fields)) $sql .= " (".implode(",",$fields).")";
		$sql .= " values (".implode(",",$values).")";
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
		$sql .= ' set '.$this->field('update');
		
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