<?PHP
/********************************************
*                                           *
* Name    : My Simple Datebase              *
* Author  : Windy2000                       *
* Time    : 2005-09-15                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT HOLD THIS ITEM PLEASE.      *
*                                           *
********************************************/

/**
    简单数据库
        $db = new simpleDB($name, $path, $tlen)           // Set the Text DB Class
        $db->connect($mode)                               // Connect with the text db with the specified mode (read, write or append)
        $db->selectDB($name, $path, $tlen)                // Set working db to another one
        $db->lock($mode)                                  // Lock/unlock current working db
        $db->write($content, $offse)                      // Update the content of the working db
        $db->get($file, $length, $offset)                 // Get a content part of the db file
        $db->check()                                      // check if the working db can be used
        $db->close()                                      // Close current db
        $db->create($setting)                             // Create text DB file in the specified directory
        $db->destory()                                    // delete db
        $db->empty()                                      // truncate db
        $db->insert($data, $mode)                         // insert data to db
        $db->update($data, $row, $mode)                   // update db data
        $db->query($condition, $single)                   // query data with some condition
        $db->select($condition, $single)                  // query data with some condition
        $db->delete($row)                                 // delete data in some row
        $db->record($row)                                 // query one record from some row
        $db->records()                                    // query all data from db
        $db->random($rows)                                // query random record from db
        $db->getData($line, $field)                       // get a field value from a specified line
        $db->result($condition, $field)                   // get a field value with the specified condition
        $db->getFields()                                  // get current db settings
        $db->setOrder($result, $col, $asc)                // order the result record set
*/
class simpleDB implements interface_db {
    private
        $name    = '',
        $path    = '',
        $file    = '',
        $tlen    = 500,
        $fp    = null,
        $maxlen    = 10,
        $separator    = "\0";
    public
        $pos = 0,
        $row = 0;

    /**
     * 构造函数
     */
    public function __construct() {
        if(count(func_get_args())>0) call_user_func_array(array($this, 'init'), func_get_args());
    }

    /**
     * 初始化数据文件
     * @param $name
     * @param string $path
     * @param int $tlen
     */
    public function init($name, $path='./', $tlen=500) {
        $this->name = $name;
        $this->path = $path;
        $this->tlen = $tlen;
        $this->file = $path.$name.'.db';
    }

    /**
     * 连接数据文件
     * @param string $mode
     * @return bool
     */
    public function connect($mode = 'r') {
        $this->close();
        switch($mode) {
            case 'read':
                $mode = 'r';
                break;
            case 'write':
                $mode = 'w';
                break;
            case 'append':
                $mode = 'a';
                break;
        }
        $this->fp = fopen($this->file, $mode);
        if($this->fp===false) trigger_error('Cannot open the simpleDB file!');
        return true;
    }

    /**
     * 打开数据表
     * @param $name
     * @param string $path
     * @param int $tlen
     */
    public function selectDB($name, $path='', $tlen=500) {
        $this->close();
        if(!empty($name)) $this->name = $name;
        if(!empty($path)) $this->path = $path;
        $this->file    = $this->path.'/'.$this->name.'.db';
        $this->err    = '';
        if(!empty($tlen)) $this->tlen = $tlen;
        $this->connect();
    }

    /**
     * 锁定数据表（更新时）
     * @param bool $mode
     * @return bool
     */
    private function lock($mode=false) {
        if($this->fp!=null) {
            return flock($this->fp, $mode?LOCK_EX:LOCK_UN);
        }
        return false;
    }

    /**
     * 写入数据表
     * @param $content
     * @param int $offset
     * @return bool
     */
    private function write($content, $offset=0) {
        if($this->fp==null) $this->connect('w');
        fseek($this->fp, $offset);
        fwrite($this->fp, $content);
        return true;
    }

    /**
     * 获取数据段
     * @param $file
     * @param int $length
     * @param int $offset
     * @return bool|string
     */
    private function get($file, $length=0, $offset=0) {
        if(!is_file($file)) return '';
        if($length==0 && $offset==0) {
            $data = file_get_contents($file);
        } else {
            if($length==0) $length = 8192;
            $fp = fopen($file, 'rb');
            fseek($fp, $offset);
            $data = fread($fp, $length);
            fclose($fp);
        }
        return $data;
    }

    /**
     * 检测数据表
     * @return bool
     */
    public function check() {
        if(!is_file($this->file)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 关闭数据表
     */
    public function close() {
        if($this->fp!=null) {
            fclose($this->fp);
            $this->fp = null;
        }
    }

    /**
     * 创建数据比表
     * @param $setting
     * @return bool
     */
    public function create($setting) {
        if($this->check()) {
            //trigger_error('The specified table already exist in the path!');
            return false;
        }
        $content = '';
        $record_max_len = 0;
        $m=count($setting);
        for($i=0; $i<$m; $i++) {
            if(is_array($setting[$i]) && count($setting[$i])==2 && preg_match("/^\w[\w\d]+$/", $setting[$i][0]) && preg_match("/^\d+$/", $setting[$i][1])) {
                if($setting[$i][1]>0) {
                    $record_max_len += $setting[$i][1]+1;
                    $content .= $this->separator.$setting[$i][0].':'.$setting[$i][1];
                }
            }
        }
        if(strlen($content)==0) {
            trigger_error('Unusable table setting!');
            return false;
        }
        $record_max_len += 1;
        $content = str_pad($this->tlen+1, $this->maxlen).$this->separator.$record_max_len.$content;
        if(strlen($content)>$this->tlen) {
            trigger_error('Beyond the max column length limit!');
            return false;
        }

        $content = str_pad($content, $this->tlen);
        $dir = dirname($this->file);
        if(!file_exists($dir)) mkdir($dir, 0777, true);
        $this->connect('w');
        $this->lock(1);
        $this->write($content.chr(10));
        $this->lock(0);
        $this->connect('r');
        return true;
    }

    /**
     * 删除数据表
     * @return bool
     */
    public function destory() {
        if(!$this->check()) return false;
        $this->close();
        unlink($this->file);
        return true;
    }

    /**
     * 清空数据表
     * @return bool
     */
    public function empty() {
        if(!$this->check()) return false;
        $str = $this->get($this->file, $this->tlen).chr(10);
        $this->connect('w');
        $this->lock(1);
        $this->write($str);
        $this->write(str_pad($this->tlen+1, $this->maxlen));
        $this->lock(0);
        $this->connect('r');
        return true;
    }

    /**
     * 查询数据表
     * @param array $condition
     * @param int $mode
     * @param int $limit
     * @return array|bool
     */
    public function query($condition = array(), $mode = 2, $limit = 1) {
        $this->pos = $this->row = 0;
        if(!$this->check()) return false;
        if(empty($condition)) return $this->records();
        $condition = explode('&&', $condition);
        if($limit == 'all') $limit = 9999;
        $m=count($condition);
        for($i=0; $i<$m; $i++) {
            if(strpos($condition[$i], '>=')>0) {
                $condition[$i] = explode('>=', $condition[$i]);
                $condition[$i][2] = '>=';
            } elseif(strpos($condition[$i], '<=')>0) {
                $condition[$i] = explode('<=', $condition[$i]);
                $condition[$i][2] = '<=';
            } elseif(strpos($condition[$i], '=')>0) {
                $condition[$i] = explode('=', $condition[$i]);
                $condition[$i][2] = '=';
            } elseif(strpos($condition[$i], '>')>0) {
                $condition[$i] = explode('>', $condition[$i]);
                $condition[$i][2] = '>';
            } elseif(strpos($condition[$i], '<')>0) {
                $condition[$i] = explode('<', $condition[$i]);
                $condition[$i][2] = '<';
            } elseif(strpos($condition[$i], '%')>0) {
                $condition[$i] = explode('%', $condition[$i]);
                $condition[$i][2] = '%';
            }
        }
        $setting = $this->getFields();
        if(!$setting) return false;
        $this->pos = $this->tlen + 1;
        if($this->fp==null) $this->connect();
        fseek($this->fp, $this->pos);
        $result = array();
        $this->row = 1;
        while (!feof($this->fp) && $limit>0) {
            $tmp = array();
            $date = fgets($this->fp, $setting['mydb_rec_length']);
            if(strlen($date)+1<$setting['mydb_rec_length']) continue;
            $date = explode($this->separator, $date);
            $i = 0;
            foreach($setting as $key => $value) {
                if($key == 'mydb_max_length' || $key == 'mydb_rec_length' || $key == 'max_row') continue;
                $tmp[$key] = trim($date[$i]);
                $i++;
            }
            $flag = true;
            $m=count($condition);
            for($i=0; $i<$m; $i++) {
                $condition[$i][0] = trim($condition[$i][0]);
                $condition[$i][1] = trim($condition[$i][1]);
                $flag &= isset($tmp[$condition[$i][0]]) && (
                            ($condition[$i][2]=='=' && $tmp[$condition[$i][0]]==$condition[$i][1]) ||
                            ($condition[$i][2]=='>' && $tmp[$condition[$i][0]]>$condition[$i][1]) ||
                            ($condition[$i][2]=='>=' && $tmp[$condition[$i][0]]>=$condition[$i][1]) ||
                            ($condition[$i][2]=='<' && $tmp[$condition[$i][0]]<$condition[$i][1]) ||
                            ($condition[$i][2]=='<=' && $tmp[$condition[$i][0]]<=$condition[$i][1]) ||
                            ($condition[$i][2]=='%' && strpos($tmp[$condition[$i][0]], $condition[$i][1])!==false)
                        );
                if(!$flag) break;
            }
            if($flag) {
                $limit--;
                switch($mode) {
                    case 1:
                        $tmp = array('db_row'=>$this->row, 'db_pos'=>ftell($this->fp));
                        break;
                    case 2:
                        $tmp['db_row'] = $this->row;
                        $tmp['db_pos'] = ftell($this->fp);
                        break;
                    case 3:
                        $tmp = $this->row;
                        break;
                }
                $result[] = $tmp;
            }
            $this->row++;
            $this->pos = ftell($this->fp);
        }
        $this->row--;
        return count($result)==0?false:$result;
    }

    /**
     * 依条件返回数据行
     * @param null $condition
     * @param null $limit
     * @return array|bool
     */
    public function select($condition=null, $limit=null) {
        return $this->query($condition, 0, 'all');
    }

    /**
     * 插入数据行
     * @param array $data
     * @param bool $mode
     * @return bool
     */
    public function insert($data = array(), $mode=false) {
        if(!$this->check()) return false;
        $setting = $this->getFields();
        if(!$setting) return false;
        if(!(isset($data[0]) && is_array($data[0]))) $data = array($data);
        $this->connect('r+');
        $this->lock(1);
        foreach($data as $current) {
            $i = 0;
            $content = '';
            foreach($setting as $key => $value) {
                if($key == 'mydb_max_length' || $key == 'mydb_rec_length' || $key == 'max_row') continue;
                if($mode) {
                    $content .= str_pad(isset($current[$key])?$current[$key]:'', (int)$value).$this->separator;
                } else {
                    $content .= str_pad(isset($current[$i])?$current[$i++]:'', (int)$value).$this->separator;
                }
            }
            $content .= chr(10);
            $this->write($content, $setting['mydb_max_length']);
            $setting['mydb_max_length'] += $setting['mydb_rec_length'];
        }
        $this->write(str_pad($setting['mydb_max_length'], $this->maxlen));
        $this->lock(0);
        $this->connect('r');
        return true;
    }

    /**
     * 更新数据行
     * @param null $condition
     * @param array $data
     * @param bool $mode
     * @return bool
     */
    public function update($condition=null, $data = array(), $mode=true) {
        if(!$this->check()) return false;
        $rows = $this->query($condition, 2, 'all');
        if($rows==false) return false;
        $setting = $this->getFields();
        if(!$setting) return false;
        for($n=0,$m=count($rows);$n<$m;$n++) {
            $content = '';
            $i = 0;
            $row = $rows[$n]['db_row'];
            if($mode) {
                unset($rows[$n]['db_row'], $rows[$n]['db_pos']);
                $cur_data = array_merge($rows[$n], $data);
            } else {
                $cur_data = $rows[$n];
            }

            foreach($setting as $key => $value) {
                if($key == 'mydb_max_length' || $key == 'mydb_rec_length' || $key == 'max_row') continue;
                if($mode) {
                    $content .= str_pad(isset($cur_data[$key])?$cur_data[$key]:'', (int)$value).$this->separator;
                } else {
                    $content .= str_pad(isset($data[$i])?$data[$i++]:'', (int)$value).$this->separator;
                }
            }
            $offset = ($this->tlen + 1) + $setting['mydb_rec_length'] * ($row-1);
            $this->connect('r+');
            $this->lock(1);
            $this->write($content.chr(10), $offset);
            $this->lock(0);
        }
        $this->connect('r');
        return true;
    }

    /**
     * 删除数据行
     * @param null $condition
     * @return bool
     */
    public function delete($condition=null) {
        if(!$this->check()) return false;
        $row_list = $this->query($condition, 3, 'all');
        if($row_list==false) return false;
        $setting = $this->getFields();
        if(!$setting) return false;
        $this->connect('r+');
        $this->lock(1);
        $m=count($row_list);
        for($i=0; $i<$m; $i++) {
            if(!is_numeric($row_list[$i]) || $row_list[$i]<1 || $row_list[$i]>$setting['max_row']) continue;
            $offset = ($this->tlen + 1) + $setting['mydb_rec_length'] * ($row_list[$i]-1);
            $this->write(str_pad('', $setting['mydb_rec_length']-1, ' '), $offset);
            $setting['mydb_max_length'] -= $setting['mydb_rec_length'];
        }
        $this->write(str_pad($setting['mydb_max_length'], $this->maxlen));
        $this->lock(0);
        $content = $this->get($this->file);
        $content = preg_replace("/\n[\s]*\n/", chr(10), $content);
        $this->connect('w');
        $this->lock(1);
        $this->write($content);
        $this->lock(0);
        $this->connect('r');
        $this->close();
        return true;
    }

    /**
     * 返回某行数据
     * @param int $row
     * @return array|false
     */
    public function record($row=0) {
        if(!$this->check()) return false;
        $setting = $this->getFields();
        if(!$setting) return false;
        if($setting['max_row']<1) return false;
        if(!is_numeric($row)) $rows = 1;
        if($row<0) $row = mt_rand(1, $setting['max_row']);
        if($row>$setting['max_row']) $row=$setting['max_row'];
        $offset = ($this->tlen+1) + $setting['mydb_rec_length']*($row-1) ;
        if($this->fp==null) $this->connect();
        fseek($this->fp, $offset);
        $date = fgets($this->fp, $setting['mydb_rec_length']);
        $date = explode($this->separator, $date);
        $i = 0;
        foreach($setting as $key => $value) {
            if($key == 'mydb_max_length' || $key == 'mydb_rec_length' || $key == 'max_row') continue;
            $tmp[$key] = trim($date[$i]);
            $i++;
        }
        return $tmp;
    }

    /**
     * 返回所有数据行
     * @param string $tmp
     * @return array|bool
     */
    public function records($tmp = '') {
        if(!$this->check()) return false;
        $setting = $this->getFields();
        if(!$setting) return false;
        $offset = $this->tlen + 1;
        $result = array();
        $tmp = array();
        if($this->fp==null) $this->connect();
        fseek($this->fp, $offset);
        while (!feof($this->fp) && $setting['mydb_rec_length']>0) {
            $date = fgets($this->fp, $setting['mydb_rec_length']);
            if(strlen(trim($date))<5) continue;
            //if(strlen($date)+1<$setting['mydb_rec_length']) continue;
            $date = explode($this->separator, $date);
            $i = 0;
            foreach($setting as $key => $value) {
                if($key == 'mydb_max_length' || $key == 'mydb_rec_length' || $key == 'max_row') continue;
                $tmp[$key] = isset($date[$i])?trim($date[$i]):'';
                $i++;
            }
            $result[] = $tmp;
        }
        return count($result)==0?array():$result;
    }

    /**
     * 返回随机数据行
     * @param int $rows
     * @return array|bool
     */
    public function random($rows=1) {
        if(!$this->check()) return false;
        $setting = $this->getFields();
        if(!$setting) return false;
        if($setting['max_row']<1) return false;
        if(!is_numeric($rows)) $rows=1;
        if($rows>$setting['max_row']) $rows=$setting['max_row'];
        $row_list = ':';
        $result = array();
        while($rows>0) {
            $rnd = mt_rand(1, $setting['max_row']);
            if(strpos($row_list, ":{$rnd}:")===false) {
                $row_list .= "{$rnd}:";
                $rows--;
                $result[] = $this->record($rnd);
            }
        }
        return $result;
    }

    /**
     * 返回某行的某项
     * @param int $line
     * @param string $field
     * @return bool|string
     */
    public function getData($line=0, $field='') {
        $row = $this->record($line);
        if(empty($field)) {
            return $row;
        } else {
            return isset($row[$field])?$row[$field]:'';
        }
    }

    /**
     * 返回某项数据值
     * @param $condition
     * @param string $field
     * @return string
     */
    public function result($condition, $field = '') {
        $result = $this->query($condition);
        return (!empty($field) && isset($result[0][$field])) ? $result[0][$field] : '';
    }

    /**
     * 获取字段设置
     * @param string $tmp
     * @return array|bool
     */
    public function getFields($tmp = '') {
        if(!$this->check()) return false;
        $str = trim($this->get($this->file, $this->tlen));
        $str = explode($this->separator, $str);
        $setting = array();
        $m=count($str);
        for($i=2; $i<$m; $i++) {
            $tmp = explode(':', $str[$i]);
            if(count($tmp)==2) $setting[$tmp[0]] =    $tmp[1];
        }
        if(count($str) < 2) return false;
        $setting['mydb_max_length'] = (int)trim($str[0]);
        $setting['mydb_rec_length'] = (int)trim($str[1]);
        if($setting['mydb_rec_length']==0) {
            $setting['max_row'] = 0;
        } else {
            $setting['max_row'] = ($setting['mydb_max_length'] - ($this->tlen + 1)) / $setting['mydb_rec_length'];
        }
        return $setting;
    }

    /**
     * 排序结果数据
     * @param $result
     * @param string $col
     * @param string $mode
     * @return array|void
     */
    public function setOrder($result, $col='rand', $mode = 'asc') {
        if(!is_array($result)) return;
        if(!isset($result[0][$col])) $mode = 'rand';
        $cmp_asc = function($a, $b) use ($col) {
            $a = $a[$col];
            $b = $b[$col];
            if($a == $b) return 0;
            return ($a < $b) ? -1 : 1;
        };
        $cmp_desc = function($a, $b) use ($col) {
            $a = $a[$col];
            $b = $b[$col];
            if($a == $b) return 0;
            return ($a > $b) ? -1 : 1;
        };
        $cmp_rand = function($a, $b) use ($col) {
            $a = rand();
            $b = rand();
            return ($a > $b) ? -1 : 1;
        };
        switch(strtolower($mode)) {
            case 'asc':
                usort($result, $cmp_asc);
                break;
            case 'desc':
                usort($result, $cmp_desc);
                break;
            default:
                usort($result, $cmp_rand);
        }
        return $result;
    }
}