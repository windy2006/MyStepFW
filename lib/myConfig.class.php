<?PHP
/********************************************
*                                           *
* Name    : My Config                       *
* Modifier: Windy2000                       *
* Time    : 2019-1-1                        *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/

/**
设置信息处理类
    $config = new myConfig($file);
    $config->catalog1->item = 'somevalue';
    $config->catalog2->item = 'somevalue';
    $config->save('php|ini|json');
    $config->build($setting_detail);
    $config->set($_POST['setting']);
*/
class myConfig extends myBase {
    protected
        $file = '',
        $construction = '',
        $type = '',
        $parser = [],
        $setting = array();

    /**
     * 初始化类变量，指定配置文件
     * @param $file
     */
    public function init($file) {
        $this->file = myFile::realPath($file);
        $this->type = strtolower(pathinfo($this->file, PATHINFO_EXTENSION));
        $this->setting = $this->load($this->file, true);
        $path = dirname($this->file);
        $list = [
            $path.'/config/default.php',
            $path.'/construction/default.php',
        ];
        foreach($list as $k) {
            if(is_file($k)) {
                $this->makeConstruction($k);
                break;
            }
        }
    }

    /**
     * 获取全部设置
     * @param bool $arr
     * @return array
     */
    public function get($arr = true) {
        $setting = $this->setting;
        if($arr) $setting = self::o2a($setting);
        return $setting;
    }

    /**
     * 处理变量链式调用方法
     * @param $idx
     * @return mixed
     */
    public function __get($idx) {
        if(!isset($this->setting->$idx)) $this->setting->$idx = new stdClass();
        return $this->setting->$idx;
    }

    /**
     * 添加新的设置值
     * @param $idx
     * @param $value
     */
    public function __set($idx, $value) {
        $this->setting->$idx = $value;
    }

    /**
     * 直接显示为JSON编码（可用于与JS交互）
     * @return false|string
     */
    public function __toString() {
        return myString::toJson(self::o2a($this->setting));
    }

    /**
     * 将数组转换为对象
     * @param $arr
     * @return object
     */
    public static function a2o($arr) {
        if(gettype($arr) != 'array' && getType($arr) != 'object')  return $arr;
        foreach($arr as $k => $v) {
            if(gettype($v) == 'array' || getType($v) == 'object') {
                $arr[$k] = (object)self::a2o($v);
            } else {
                $arr[$k] = self::recover($v);
            }
        }
        return (object)$arr;
    }

    /**
     * 将对象转换为数据
     * @param $obj
     * @return array
     */
    public static function o2a($obj) {
        $result = array();
        foreach($obj as $k => $v) {
            if(gettype($v) == 'object') {
                $result[$k] = (array)self::o2a($v);
            } else {
                $result[$k] = self::recover($v);
            }
        }
        return $result;
    }

    /**
     * 回复数据类型
     * @param $v
     * @return bool|float|int
     */
    public static function recover($v) {
        switch(true) {
            case is_float($v):
                $v = (FLOAT)$v;
                break;
            case is_int($v):
                $v = (INT)$v;
                break;
            case is_array($v):
                $v = implode(',', $v);
                break;
            case strtolower($v) == 'true':
                $v = true;
                break;
            case strtolower($v) == 'false':
                $v = false;
                break;
        }
        return $v;
    }

    /**
     * 设置自定义解析函数
     * @param $ext
     * @param $func_load
     * @param $func_save
     */
    public  function setParser($ext, $func_load, $func_save) {
        if(!empty($ext) && is_callable($func_load) && is_callable($func_save)) {
            $this->parser[$ext] = [
                'load' => $func_load,
                'save' => $func_save
            ];
        }
    }

    /**
     * 生成配置信息
     * @param $file
     */
    public function makeConstruction($file) {
        $construction_file = dirname($file).'/construction.php';
        if(is_file($construction_file)) {
            $this->construction = $construction_file;
        } else {
            if(is_file($file)) {
                $detail = require($file);
                $construction = [];
                if(isset($detail['name']) && isset($detail['list'])) {
                    foreach($detail['list'] as $k => $v) {
                        if(!isset($construction[$v['type'][0]])) $construction[$v['type'][0]] = [];
                        $construction[$v['type'][0]][] = $k;
                        if($v['type'][0]==='password' && !empty($v['type'][1])) {
                            $construction[$v['type'][0]]['func'][$k] = $v['type'][1];
                        }
                    }
                } else {
                    foreach($detail as $k0 => $v0) {
                        foreach($v0['list'] as $k => $v) {
                            if(!isset($construction[$v['type'][0]])) $construction[$v['type'][0]] = [];
                            $construction[$v['type'][0]][] = $k0.'.'.$k;
                            if($v['type'][0]==='password' && !empty($v['type'][1])) {
                                $construction[$v['type'][0]]['func'][$k0.'.'.$k] = $v['type'][1];
                            }
                        }
                    }
                }
                $result = '<?PHP'.chr(10);
                $result .= myString::toScript($construction, 'construction');
                $result .= 'return $construction;';
                myFile::saveFile($construction_file, $result);
                $this->construction = $construction_file;
            }
        }
    }

    /**
     * 读取配置文件并转化为类对象
     * @param string $file
     * @param bool $return_object
     * @return array|mixed|object
     */
    public function load($file='', $return_object=false) {
        if(empty($file)) $file = $this->file;
        $result = array();
        if(is_file($file)) {
            $type = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            switch($type) {
                case 'php':
                    $result = include($file);
                    break;
                case 'ini':
                    $result = myString::fromIni($file, true);
                    break;
                case 'json':
                    $result = myString::fromJson(myFile::getLocal($file), true);
                    break;
                default:
                    if(isset($this->parser[$type])) {
                        $result = call_user_func($this->parser[$type]['load'], myFile::getLocal($file));
                    } else {
                        $this->error('Unsupported format - '. $type);
                        exit;
                    }
            }
        }
        if($return_object) $result = self::a2o($result);
        return $result;
    }

    /**
     * 整合两个设置
     * @param $setting
     * @param null $org
     */
    public function merge($setting, &$org = null) {
        if(!is_object($setting)) {
            if(is_array($setting)) {
                $setting = self::a2o($setting);
            } elseif(is_file($setting)) {
                $setting = $this->load($setting, true);
            }
        }
        if(empty($org)) $org = $this->setting;
        if(is_object($setting) && is_object($org)) {
            foreach($setting as $k => $v) {
                if(isset($org->$k) && is_object($v)) {
                    $this->merge($v, $org->$k);
                } else {
                    $org->$k = $v;
                }
            }
        }
    }

    /**
     * 批量更新所有设置
     * @param $setting
     * @param string $idx
     * @param bool $check
     * @return array|stdClass
     */
    public function set($setting, $idx = '', $check = true) {
        $construction = require($this->construction);
        if($check && isset($construction['checkbox'])) {
            foreach($construction['checkbox'] as $v) {
                $s =& $setting;
                $v = explode('.', $v);
                foreach($v as $v1) {
                    if(!isset($s[$v1])) $s[$v1] = [];
                    $s =& $s[$v1];
                }
                unset($s);
            }
        }
        if($check && isset($construction['switch'])) {
            foreach($construction['switch'] as $v) {
                $s =& $setting;
                $v = explode('.', $v);
                foreach($v as $v1) {
                    if(!isset($s[$v1])) $s[$v1] = '';
                    $s =& $s[$v1];
                }
                unset($s);
            }
        }
        $item = $this->setting;
        if(!empty($idx)) {
            $keys = explode('.', $idx);
            foreach ($keys as $key) {
                $item = $item->$key ?? (new stdClass());
            }
            $idx .= '.';
        }
        foreach($setting as $k => $v) {
            if(preg_match('/_pwd_r$/', $k)) continue;
            if(is_array($v)) {
                if(isset($v[0]) || empty($v)) {
                    $item->$k = implode(',', $v);
                } else {
                    $item->$k = $this->set($v, $idx.$k, false);
                }
            } else {
                if(isset($construction['password']) && in_array($idx.$k, $construction['password'])) {
                    if(!empty($setting[$k])) {
                        if(isset($construction['password']['func'][$idx.$k]) && function_exists($construction['password']['func'][$idx.$k]))
                            $v = $construction['password']['func'][$idx.$k]($v);
                        $item->$k = $v;
                    }
                } else {
                    if($v=='false') $v=false;
                    elseif($v=='true') $v=true;
                    elseif(is_numeric($v)) $v = $v + 0;
                    $item->$k = $v;
                }
            }
        }
        return $item;
    }

    /**
     * 保存配置文件为指定的格式
     * @param string $type
     * @param string $file
     * @return bool|int|resource
     */
    public function save($type='', $file = '') {
        $setting = self::o2a($this->setting);
        start:
        switch($type) {
            case 'php':
/*
                $result = '<?PHP
return '.var_export($setting, true).';';
*/
                $result = '<?PHP'.chr(10);
                $result .= myString::toScript($setting, 'setting');
                $result .= 'return $setting;';
                break;
            case 'ini':
                $result = myString::toIni($setting);
                break;
            case 'json':
                $result = myString::toJson($setting);
                break;
            default:
                if(isset($this->parser[$type])) {
                    $result = call_user_func($this->parser[$type]['save'], $setting);
                } else {
                    $type = $this->type;
                    goto start;
                }
        }
        if(empty($file)) {
            $file = $this->file;
            if($type!=$this->type) $file = preg_replace('/'.$this->type.'$/i', $type, $file);
        }
        myFile::del($this->construction);
        return myFile::saveFile($file, $result);
    }

    /**
     * 生成配置设置超文本
     * @param $detail
     * @param string $idx
     * @param array $ext_setting
     * @return array|false
     */
    public function build($detail, $idx = '', $ext_setting = array()) {
        if(is_array($detail)) {
            $setting_detail = $detail;
        } else {
            if(!is_file($detail)) {
                $this->error('Cannot load the setting file ('.$detail.')');
                return false;
            }
            $setting_detail = include($detail);
            if(is_array($idx)) {
                $ext_setting = $idx;
                $idx = '';
            }
            foreach($ext_setting as $k1 => $v1) {
                foreach($v1 as $k2 => $v2) {
                    if(isset($setting_detail[$k1]['list'][$k2]['type'])) $setting_detail[$k1]['list'][$k2]['type'] = $v2;
                }
            }
        }
        if(!empty($idx)) $idx .='.';

        if(isset($setting_detail['name']) && is_string($setting_detail['name'])) {
            $setting_detail = ['single_setting'=>$setting_detail];
        }

        $result = array();
        foreach($setting_detail as $k => $v) {
            $cur_idx = $idx.$k;
            if(isset($v['list'])) {
                $result[] = ['name' => $v['name'], 'idx' => $cur_idx];
                $result = array_merge($result, $this->build($v['list'], $cur_idx));
                continue;
            }
            $item = array(
                'name' => $v['name'],
                'describe' => $v['describe'],
            );
            $keys = explode('.', $cur_idx);
            $the_value = $this->setting;
            foreach($keys as $key) {
                if($key=='single_setting') continue;
                $the_value = $the_value->$key ?? '';
            }
            $the_value = is_null($the_value) ? '' : myString::fromAny($the_value);
            $k = str_replace('.', "][", $cur_idx);
            $k = str_replace('single_setting][', '', $k);
            switch($v['type'][0]) {
                case 'text':
                    $item['html'] = '<input type="text" name="setting['.$k.']" value="'.$the_value.'" len="'.$v['type'][2].'"'.($v['type'][1]===false?'':(' need="'.$v['type'][1].'"')).' />';
                    break;
                case 'textarea':
                    $item['html'] = '<textarea name="setting['.$k.']" wrap="off" rows="'.$v['type'][2].'" '.($v['type'][1]===false?'':(' need="'.$v['type'][1].'"')).' >'.$the_value.'</textarea>';
                    break;
                case 'password':
                    $item['describe'] = '';
                    $item['html'] = '<input type="password" id="'.str_replace('][', '_', $k).'" name="setting['.$k.']" value="" len="'.$v['type'][2].'" />';
                    $result[] = $item;
                    $item = array(
                        'name' => $v['name2'],
                        'describe' => $v['describe'],
                    );
                    $item['html'] = '<input type="password" id="'.str_replace('][', '_', $k).'_r" name="setting['.$k.'_pwd_r]" value="" len="'.$v['type'][2].'" />';
                    break;
                case 'checkbox':
                    $cur_component = '';
                    $the_value = explode(',', $the_value);
                    foreach($v['type'][1] as $k_c => $v_c) {
                        $v_c = myString::fromAny($v_c);
                        $checked = array_search($v_c, $the_value)!==false ?'checked':'';
                        $cur_component .= '<label><input type="checkbox" name="setting['.$k.'][]" value="'.$v_c.'" '.$checked.' /> '.$k_c.'</label>'.chr(10);
                    }
                    $item['html'] = $cur_component;
                    break;
                case 'switch':
                    $checked = !empty($the_value) ?'checked':'';
                    if(!isset($v['type'][2])) $v['type'][2] = '';
                    $item['html'] = '<div type="switch"><input type="checkbox" id="setting['.$k.'][]" name="setting['.$k.'][]" value="'.$v['type'][1].'" '.$checked.' /><label for="setting['.$k.'][]">'.$v['type'][2].'</label></div>'.chr(10);
                    break;
                case 'radio':
                    $cur_component = '';
                    foreach($v['type'][1] as $k_r => $v_r) {
                        $v_r = myString::fromAny($v_r);
                        $checked = ($the_value==$v_r)?'checked':'';
                        $cur_component .= '<label><input type="radio" name="setting['.$k.']" value="'.$v_r.'" '.$checked.' /> '.$k_r.'</label> &nbsp; &nbsp;'.chr(10);
                    }
                    $item['html'] = $cur_component;
                    break;
                case 'select':
                    $cur_component = '<select name="setting['.$k.']">'.chr(10);
                    end($v['type'][1]);
                    $mode = key($v['type'][1]) == count($v['type'][1])-1;
                    foreach($v['type'][1] as $k_s => $v_s) {
                        $v_s = myString::fromAny($v_s);
                        if($mode) $k_s = $v_s;
                        $checked = ($the_value==$v_s)?'selected':'';
                        $cur_component .= '<option value="'.$v_s.'" '.$checked.'>'.$k_s.'</option>'.chr(10);
                    }
                    $cur_component .= "</select>";
                    $item['html'] = $cur_component;
                    break;
                default:
                    $item['html'] = '<input name="setting['.$k.']" value="'.$the_value.'" />';
            }
            //$item['html'] = str_replace('[single_setting]', '', $item['html']);
            $result[] = $item;
        }
        return $result;
    }

}