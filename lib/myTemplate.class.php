<?PHP
/********************************************
*                                           *
* Name    : My Template                     *
* Author  : Windy2000                       *
* Time    : 2010-12-12                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT HOLD THIS ITEM PLEASE.      *
*                                           *
********************************************/

/**
模版解析:
    $tpl->init($setting, $cache, $allow_script)                 // Set the Template Class
    $tpl->setTplPara($setting)                                  // Set the parameter of templatee
    $tpl->setCacheMode($cache)                                  // Set the mode of template cache
    $tpl->getTemplate($file1, $file2)                           // Get template content
    $tpl->setLoop($key, $value, $fullset)                       // Set values for loop-blocks (in turn or batch)
    $tpl->setIf($key, $value)                                   // Set the judging conditions for if-blocks
    $tpl->setSwitch($key, $value)                               // Set the switch conditions for switch-blocks
    $tpl->assign($name, $value)                                 // Set value for one variable
    $tpl->loadSet($file, $prefix)                               // Read Batch setting file
    $tpl->removeCache()                                         // Remove template complied cache
    $tpl->regTag($tag_name, $tag_func)                          // Regist tag-parser for the custom tags
    $tpl->getBlock($tpl_content, $tag, $idx)                    // Analysis a custom tag
    $tpl->checkCache()                                          // Check if the current template with the define idx has been cached as a static html file
    $tpl->getCache()                                            // Directly get file content from cached html file
    $tpl->render($global_vars)                                  // Get result content compile by the template engine ('$global_vars' ara the parameters needed in php cache file)
*/
class myTemplate extends myBase {
    use myTrait;

    public static $tpl_para = array();

    public $allow_script = false;

    protected
        $delimiter_l = '<!--',
        $delimiter_r = '-->',
        $hash = '',
        $setting = array('name'=>'', 'style'=>'', 'path'=>'', 'path_compile'=>'', 'file'=>'', 'content'=>''),
        $tags = array(),
        $cache = array(
            'use' => false,
            'file' => '',
            'expire' => 300
        );

    /**
     * 变量初始化
     * @param $setting
     * @param false $cache
     * @param false $allow_script
     * @param null $delimiter
     */
    public function init($setting, $cache = false, $allow_script = false, $delimiter = null) {
        $this->allow_script = $allow_script;
        $this->setTplPara($setting);
        if($cache===true) $cache = [];
        $this->setCacheMode($cache);
        if(is_array($delimiter) && count($delimiter)==2) {
            list($this->delimiter_l, $this->delimiter_r) = $delimiter;
        }
    }

    /**
     * 设置模版参数
     * @param $setting
     * @return $this
     */
    public function setTplPara($setting) {
        if(!is_array($setting)) $setting = array();
        if(!isset($setting['name'])) $setting['name'] = 'main';
        if(!isset($setting['style'])) $setting['style'] = '';
        if(!isset($setting['path'])) $setting['path'] = './';
        if(!isset($setting['path_compile'])) $setting['path_compile'] = './complied';
        if(!isset($setting['ext'])) $setting['ext'] = 'tpl';
        $this->setting = $setting;
        $this->setting['file'] = $setting['path'].'/'.$setting['style'].'/'.$setting['name'].'.'.$setting['ext'];
        $this->setting['path'] = myFile::realPath($this->setting['path']);
        $this->setting['file'] = myFile::realPath($this->setting['file']);
        $this->setting['content'] = $this->getTemplate($this->setting['file'], $this->setting['file']);
        $this->hash = 't'.substr(md5($this->setting['file']), 0, 10);
        if(!isset(self::$tpl_para[$this->hash])) {
            self::$tpl_para[$this->hash] = array();
            self::$tpl_para[$this->hash]['para'] = array();
            self::$tpl_para[$this->hash]['loop'] = array();
            self::$tpl_para[$this->hash]['if'] = array();
        }
        return $this;
    }

    /**
     * 设置缓存模式
     * @param $cache
     * @return $this
     */
    public function setCacheMode($cache) {
        if($cache) {
            $this->cache['use'] = true;
            if(!isset($cache['name'])) $cache['name'] = $this->setting['name'];
            if(empty($cache['name'])) $cache['name'] = 'index';
            if(!isset($cache['path'])) $cache['path'] = $this->setting['path'].'/cache/'.$this->setting['style'];
            if(!isset($cache['ext'])) $cache['ext'] = 'html';
            if(!isset($cache['expire'])) $cache['expire'] = 300;
            if(preg_match('#/(\d+)$#', $cache['path'], $match)) {
                $cache['path'] = preg_replace('#/'.$match[1].'$#', '', $cache['path']);
                if($cache['name']=='index') {
                    $cache['name'] = $match[1];
                } else {
                    $cache['name'] = trim($match[1].'_'.$cache['name'], '_');
                }
            }
            $cache['name'] = preg_replace('#[^\w]#', '', $cache['name']);
            $this->cache['file'] = myFile::realPath($cache['path'].'/'.$cache['name'].'.'.$cache['ext']);
            $this->cache['expire'] = $cache['expire'];
        } else {
            $this->cache['use'] = false;
        }
        return $this;
    }

    /**
     * 获取模板文件代码
     * @param $file1
     * @param string $file2
     * @return string
     */
    public function getTemplate($file1, &$file2='') {
        if(is_file($file1)) {
            return myFile::getLocal($file1);
        } elseif(!empty($file2) && is_file($file2)) {
            return myFile::getLocal($file2);
        } else {
            $file1 = $this->setting['path'].'/default/'.basename($file1);
            if(is_file($file1)) {
                $file2 = $file1;
                return myFile::getLocal($file1);
            } else {
                if(!empty($file2)) {
                    $file2 = $this->setting['path'].'/default/'.basename($file2);
                    if(is_file($file2)) {
                        return myFile::getLocal($file2);
                    }
                }
            }
        }
        $this->error('Cannot find template file ['.basename($file1).'] !');
        return '';
    }

    /**
     * 设置循环变量
     * @param $key
     * @param $value
     * @param bool $fullset
     * @return $this
     */
    public function setLoop($key, $value, $fullset = false) {
        if($fullset) {
            self::$tpl_para[$this->hash]['loop'][$key] = $value;
        } else {
            if(!isset(self::$tpl_para[$this->hash]['loop'][$key])) self::$tpl_para[$this->hash]['loop'][$key] = array();
            self::$tpl_para[$this->hash]['loop'][$key][] = $value;
        }
        return $this;
    }

    /**
     * 设置判断变量
     * @param $key
     * @param bool $value
     * @return $this
     */
    public function setIf($key, $value=true) {
        self::$tpl_para[$this->hash]['if'][$key] = $value;
        return $this;
    }

    /**
     * 设置选择变量
     * @param $key
     * @param bool $value
     * @return $this
     */
    public function setSwitch($key, $value=true) {
        self::$tpl_para[$this->hash]['switch'][$key] = $value;
        return $this;
    }

    /**
     * 设置普通变量
     * @param $name
     * @param $value
     * @return $this
     */
    public function assign($name, $value='') {
        if(is_array($name) || is_object($name)) {
            $value = $name;
            $name = '';
        }
        if(is_array($value) || is_object($value)) {
            foreach ($value as $k => $v) {
                $k = empty($name) ? $k : ($name . '_' . $k);
                self::$tpl_para[$this->hash]['para'][$k] = $v;
            }
        } elseif(is_string($value)) {
            self::$tpl_para[$this->hash]['para'][$name] = $value;
        } else {
            self::$tpl_para[$this->hash]['para'][$name] = myString::fromAny($value);
        }
        return $this;
    }

    /**
     * 读取批量设置文件
     * @param $file
     * @param string $prefix
     * @param bool $mode
     * @return $this
     */
    public function loadSet($file, $prefix='', $mode = false) {
        function get($arr, $mode, $idx = '') {
            $result = array();
            foreach($arr as $k => $v) {
                if($mode) $k = (empty($idx) ? '' : $idx.'.').$k;
                if(is_array($v)) {
                    $result = array_merge($result, get($v, $mode, $k));
                } else {
                    $result[$k] = $v;
                }
            }
            return $result;
        }
        if(is_file($file)) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            switch(strtolower($ext)) {
                case 'php':
                    $vars = include($file);
                    $vars = get($vars, $mode);
                    break;
                case 'ini':
                    $vars = myString::fromIni($file, false);
                    break;
                case 'json':
                    $vars = myFile::getLocal($file);
                    $vars = myString::fromJson($vars);
                    $vars = get($vars, $mode);
                    break;
                default:
                    $vars = array();
            }
            $this->assign($prefix, $vars);
        }
        return $this;
    }

    /**
     * 模版缓存编译
     * @return bool|mixed|null|string|string[]
     */
    protected function compileTemplate() {
        $cache_file = $this->setting['path_compile'].str_replace('../', '', $this->setting['style']).'/'.$this->setting['name'].'.php';
        $cache_file = myFile::realPath($cache_file);
        if(is_file($cache_file)) {
            $tpl_time = preg_replace('/^.+?(\d+).+$/', '\1', myFile::getLocal($cache_file, 18));
            if($tpl_time==filemtime($this->setting['file'])) {
                return $cache_file;
            } else {
                myFile::del($cache_file);
            }
        }
        $tpl_cache = $this->setting['content'];
        if(!$this->allow_script) {
            $tpl_cache = preg_replace('/<\?=.+?\?>/', '', $tpl_cache);
            $tpl_cache = preg_replace('/<\?php.+?\?>/is', '', $tpl_cache);
            $tpl_cache = preg_replace('/<\?php.+$/is', '', $tpl_cache);
        }
        $tpl_cache = '<?PHP
$tpl_para = myTemplate::$tpl_para[\''.$this->hash.'\'];
?>
'.$tpl_cache;
        preg_match_all('/'.preg_quote($this->delimiter_l).'(\w+):start(\s+\w+\s*=\s*("|\')[^\3]+\3)*'.preg_quote($this->delimiter_r).'.*'.preg_quote($this->delimiter_l).'\1:end'.preg_quote($this->delimiter_r).'/isU', $tpl_cache, $block_all);
        for($i=0,$m=count($block_all[0]); $i<$m; $i++) {
            $cur_attrib = array();
            $cur_content = '';
            $cur_result = '';
            preg_replace_callback("/".preg_quote($this->delimiter_l)."(\w+):start((\s+\w+\s*=\s*(\"|')[^\\4]+\\4)*)".preg_quote($this->delimiter_r)."(.*)".preg_quote($this->delimiter_l)."\\1+:end".preg_quote($this->delimiter_r)."/isU", function($matches) use (&$cur_attrib, &$cur_content) {
               $this->parseBlock($matches[2], $matches[5], $cur_attrib, $cur_content);
            }, $block_all[0][$i]);
            switch($block_all[1][$i]) {
                case 'loop':
                    $time = isset($cur_attrib['time']) ? intval($cur_attrib['time']) : 0;
                    $key = isset($cur_attrib['key']) ? $cur_attrib['key'] : '';
                    $unit_blank = preg_replace('/'.preg_quote($this->delimiter_l).'.*?'.preg_quote($this->delimiter_r).'/is', '', $cur_content);
                    $unit_blank = preg_replace('/<(td|li|p|dd|dt)([^>]*?)>.*?<\/\1>/is', '<\1\2>&nbsp;</\1>', $unit_blank);
                    $unit = $cur_content;
                    if(isset(self::$tpl_para[$this->hash]['loop'][$cur_attrib['key']]) && count(self::$tpl_para[$this->hash]['loop'][$cur_attrib['key']])>0) {
                        foreach(self::$tpl_para[$this->hash]['loop'][$cur_attrib['key']][0] as $k => $v) {
                            $unit = str_replace($this->delimiter_l.$key.'_'.$k.$this->delimiter_r, '{$record[$i][\''.$k.'\']}', $unit);
                        }
                    } else {
                        self::$tpl_para[$this->hash]['loop'][$cur_attrib['key']] = array();
                        $unit = preg_replace('/'.preg_quote($this->delimiter_l).preg_quote($cur_attrib['key']).'_(\w+)'.preg_quote($this->delimiter_r).'/U', '{$record[\$i][\'\1\']}', $unit);
                    }
                    $cur_result = <<<'mytpl'
<?PHP
$time ={myTemplate::time} - 1;
$record = isset($tpl_para['loop']['{myTemplate::key}'])?$tpl_para['loop']['{myTemplate::key}']:array();
for($i=0,$m=count($record); $i<$m; $i++) {
    echo <<<content
{myTemplate::unit}

content;
}
for(;$i<=$time;$i++) {
    echo <<<content
{myTemplate::unit_blank}

content;
}
mytpl;
                    $cur_result .= chr(10).'?>';
                    $cur_result = str_replace('{myTemplate::key}', $cur_attrib['key'], $cur_result);
                    $cur_result = str_replace('{myTemplate::time}', $time, $cur_result);
                    $cur_result = str_replace('{myTemplate::unit}', $unit, $cur_result);
                    $cur_result = str_replace('{myTemplate::unit_blank}', $unit_blank, $cur_result);
                    break;
                case "if":
                    $part = explode($this->delimiter_l.'else'.$this->delimiter_r, $cur_content);
                    if(isset($cur_attrib['key'])) {
                        if(!isset(self::$tpl_para[$this->hash]['if'][$cur_attrib['key']])) self::$tpl_para[$this->hash]['if'][$cur_attrib['key']] = false;
                        $cur_result = <<<'mytpl'
<?PHP
if($tpl_para['if']['{myTemplate::key}']) {
    echo <<<content
{myTemplate::part_0}
content;
}
mytpl;
                        if(count($part)>1) {
                            $cur_result .= <<<'mytpl'
 else {
    echo <<<content
{myTemplate::part_1}
content;
}
mytpl;
                            $cur_result = str_replace('{myTemplate::part_1}', $part[1], $cur_result);
                        }
                        $cur_result .= chr(10).'?>';
                        $cur_result = str_replace('{myTemplate::key}', $cur_attrib['key'], $cur_result);
                        $cur_result = str_replace('{myTemplate::part_0}', $part[0], $cur_result);
                    }
                    break;
                case "switch":
                    $cur_content = preg_replace('#^[\s\r\n]+(.+[^\s\r\n])[\s\r\n]+$#s', '\1', $cur_content);
                    preg_match_all('#('.preg_quote($this->delimiter_l).'\((.*?)\)'.preg_quote($this->delimiter_r).'((.(?!'.preg_quote($this->delimiter_l).'))+))+#s', $cur_content, $part);
                    $cur_result = <<<'mytpl'
<?PHP
switch($tpl_para['switch']['{myTemplate::key}']) {

mytpl;
                    for($j=0, $m2=count($part[0]); $j<$m2; $j++) {
                        $cur_result .= <<<'mytpl'
    case "{myTemplate::part_1}":
        echo "{myTemplate::part_2}";
        break;

mytpl;
                        $cur_result = str_replace('{myTemplate::part_1}', addslashes($part[2][$j]), $cur_result);
                        $cur_result = str_replace('{myTemplate::part_2}', addslashes($part[3][$j]), $cur_result);
                    }
                    $cur_result .= <<<'mytpl'
    default:
        echo "";
}
?>
mytpl;
                    $cur_result = str_replace('{myTemplate::key}', $cur_attrib['key'], $cur_result);
                    break;
                case "random":
                    $cur_result =  <<<'mytpl'
<?PHP
$parts = explode("{myTemplate::separator}", "{myTemplate::content}");
echo $parts[rand(0, count($parts)-1)];
mytpl;
                    $cur_result .= chr(10).'?>';
                    $cur_result = str_replace('{myTemplate::separator}', $this->delimiter_l.'(s)'.$this->delimiter_r, $cur_result);
                    $cur_result = str_replace('{myTemplate::content}', addslashes($cur_content), $cur_result);
                    break;
                default:
                    $cur_result = '';
                    break;
            }
            $cur_result = preg_replace('/'.preg_quote($this->delimiter_l).'(\w+?)'.preg_quote($this->delimiter_r).'/', '{$tpl_para[\'para\'][\'\1\']}', $cur_result);
            $tpl_cache = str_replace($block_all[0][$i], $cur_result, $tpl_cache);
            unset($cur_attrib, $cur_content);
        }
        $tpl_cache = $this->parseTag($tpl_cache);
        $tpl_cache = preg_replace('/'.preg_quote($this->delimiter_l).'(\w+)'.preg_quote($this->delimiter_r).'/', '<?=$tpl_para[\'para\'][\'\1\']?>', $tpl_cache);
        $tpl_cache = preg_replace('/[\r\n]+/', chr(10), $tpl_cache);
        myFile::saveFile($cache_file, $tpl_cache, 'wb');
        return $cache_file;
    }

    /**
     * 删除模版编译缓存
     * @return mixed
     */
    public function removeCache() {
        $cache_file = $this->setting['path'].'/cache/'.str_replace('../', '', $this->setting['style']).'/'.$this->setting['name'].'.php';
        myFile::del($cache_file);
        return $this;
    }

    /**
     * 解析模版功能模块
     * @param $attrib
     * @param $content
     * @param $block_attrib
     * @param $block_content
     */
    protected function parseBlock($attrib, $content, &$block_attrib, &$block_content) {
        $block_content = stripslashes($content);
        $attrib = stripslashes($attrib);
        preg_match_all('/(\w+\s*=\s*(\'|")[^\2]+\2)/isU', $attrib, $att_list);
        $att_list = $att_list[1];
        for($i=0,$m=count($att_list); $i<$m; $i++) {
            if(empty($att_list[$i])) continue;
            $tmp = explode('=', trim($att_list[$i]));
            $block_attrib[strtolower(trim($tmp[0]))] = preg_replace('/^(\'|")(.+?)\1/', '\2', $tmp[1]);
        }
    }

    /**
     * 添加标签解析方法
     * @param $tag_name
     * @param string $tag_func
     * @return mixed
     */
    public function regTag($tag_name, $tag_func = '') {
        if(is_array($tag_name)) {
            foreach($tag_name as $key => $value) {
                if(is_callable($value)) $this->tags[$key] = $value;
            }
        } else {
            if(is_callable($tag_func)) $this->tags[$tag_name] = $tag_func;
        }
        return $this;
    }

    /**
     * 解析自定义标签
     * @param $content
     * @return mixed
     */
    protected function parseTag($content) {
        preg_match_all('/'.preg_quote($this->delimiter_l).'(\w+)((\s+\w+\s*=\s*(\'|")[^\4]+\4)+)'.preg_quote($this->delimiter_r).'/isU', $content, $tag_all);
        for($i=0,$m=count($tag_all[0]); $i<$m; $i++) {
            $cur_result = '';
            $cur_attrib = array();
            if(isset($this->tags[$tag_all[1][$i]])) {
                preg_match_all('/(\w+\s*=\s*(\'|")[^\2]+\2)/isU', $tag_all[2][$i], $matches);
                $att_list = ['unit=', 'unit_blank=', 'class='];
                $att_list = array_merge($att_list, $matches[1]);
                for($j=0, $m2=count($att_list); $j<$m2; $j++) {
                    if(empty($att_list[$j])) continue;
                    $tmp = explode('=', trim($att_list[$j]));
                    $tmp[1] = preg_replace('/^(\'|")(.+?)\1/', '\2', $tmp[1]);
                    if(preg_match('/^\$(\w+)$/', $tmp[1], $match)) {
                        $tmp[1] = '$GLOBALS[\''.$match[1].'\']';
                    } elseif(preg_match('/^\$_(\w+)(\[(.+?)\].*)$/', $tmp[1], $match)) {
                        $tmp[1] = '$_'.strtoupper($match[1]).$match[2];
                    } elseif(preg_match('/^\$(\w+)(\[(.+?)\].*)$/', $tmp[1], $match)) {
                        $tmp[1] = '$GLOBALS[\''.$match[1].'\']'.$match[2];
                    }
                    $cur_attrib[strtolower(trim($tmp[0]))] = $tmp[1];
                }
                $cur_result = call_user_func_array($this->tags[$tag_all[1][$i]], [&$this, &$cur_attrib]);
                $code = <<<'mytpl'
<?PHP
$tag_attrs = <<<'mystep'
{myTemplate::tpl_paras}
mystep;
$tag_attrs = unserialize(str_replace(chr(10),chr(13).chr(10), $tag_attrs));
checkPara($tag_attrs, true);
?>
mytpl;
                if(preg_match('#(<\?php(.+)/\*split\*/)#is', $cur_result, $match)) {
                    $cur_result = str_replace($match[0], '<?php', $cur_result);
                    $cur_result = '<?php'.$match[2].'?>'.chr(10).$cur_result;
                }
                $cur_result = $code.$cur_result;
                foreach($cur_attrib as $k => $v) {
                    if(strpos($v, '$')===0) {
                        $cur_result = str_replace($this->delimiter_l.$k.$this->delimiter_r, '<?='.$v.'?>', $cur_result);
                    } else {
                        $cur_result = str_replace($this->delimiter_l.$k.$this->delimiter_r, $v, $cur_result);
                    }
                    $cur_result = str_replace('{myTemplate::'.$k.'}', $v, $cur_result);
                }
                unset($cur_attrib['unit'],$cur_attrib['unit_blank'],$cur_attrib['sql']);
                $cur_result = str_replace('{myTemplate::tpl_paras}', serialize($cur_attrib), $cur_result);
                $cur_result = preg_replace('#[\r\n]+\?>[\r\n\s]*<\?php[\r\n]+#i',chr(10), $cur_result);
                $cur_result = preg_replace('#'.preg_quote($this->delimiter_l).'(\w+)'.preg_quote($this->delimiter_r).'#', '<?=$tag_attrs[\'\1\']?>', $cur_result);
            }
            $content = str_replace($tag_all[0][$i], $cur_result, $content);
        }
        return $content;
    }

    /**
     * 获取自定义标签相关信息
     * @param $tpl_content
     * @param string $tag
     * @param string $idx
     * @return array
     */
    public function getBlock($tpl_content, $tag = '', $idx = '') {
        if(preg_match('/'.preg_quote($this->delimiter_l).$tag.':start'.preg_quote($this->delimiter_r).'(.*)'.preg_quote($this->delimiter_l).$tag.':end'.preg_quote($this->delimiter_r).'/isU', $tpl_content, $block_all)) {
            $block = $block_all[0];
            $unit = $block_all[1];
            $addon = '';
            switch($tag) {
                case 'loop':
                    $addon = preg_replace('/'.preg_quote($this->delimiter_l).'.*?'.preg_quote($this->delimiter_r).'/is', '', $unit);
                    $addon = preg_replace('/<(td|li|p|dd|dt)([^>]*?)>.*?<\/\1>/is', '<\1\2>&nbsp;</\1>', $addon);
                    if(!empty($idx)) {
                        $unit = preg_replace('/'.preg_quote($this->delimiter_l).$idx.'_(\w+)'.preg_quote($this->delimiter_r).'/i', '{$'.$idx.'[\'\1\']}', $unit);
                    }
                    break;
                case 'if':
                    list($unit, $addon) = explode($this->delimiter_l.'else'.$this->delimiter_r, $unit);
                    $unit = addslashes($unit);
                    if(empty($addon)) $addon = '';
                    $addon = addslashes($addon);
                    break;
                case 'switch':
                    $block_t = str_replace($this->delimiter_l.'switch:start'.$this->delimiter_r, '', $block);
                    $block_t = str_replace($this->delimiter_l.'switch:end'.$this->delimiter_r, '', $block_t);
                    $block_t = preg_replace('#^[\s\r\n]+(.+[^\s\r\n])[\s\r\n]+$#s', '\1', $block_t);
                    preg_match_all('#('.preg_quote($this->delimiter_l).'\((.*?)\)'.preg_quote($this->delimiter_r).'((.(?!'.preg_quote($this->delimiter_l).'))+))+#s', $block_t, $matches);
                    $unit = array();
                    for($i=0,$m=count($matches[2]);$i<$m;$i++) {
                        $unit[$matches[2][$i]] = $matches[3][$i];
                    }
                    break;
            }
        } else {
            $block = '';
            $unit = '';
            $addon = '';
        }
        return [$block, $unit, $addon];
    }

    /**
     * 检测模版缓存是否存在
     * @return bool
     */
    public function checkCache() {
        if(!$this->cache['use']) return false;
        if(!is_file($this->cache['file'])) return false;
        if(filemtime($this->cache['file'])+$this->cache['expire'] < $_SERVER['REQUEST_TIME']) {
            unlink($this->cache['file']);
            return false;
        }
        return true;
    }

    /**
     * 取得已编译模版缓存
     * @return string
     */
    public function getCache() {
        $content = '';
        if($this->cache['use'] && $this->checkCache()) {
            $content = myFile::getLocal($this->cache['file']);
        }
        return $content;
    }

    /**
     * 获取最终超文本页面内容
     * @param string $global_vars
     * @param bool $tpl_show
     * @param bool $minify
     * @return mixed|string
     */
    public function render($global_vars = '', $tpl_show = true, $minify = false) {
        $content = '';
        if($this->cache['use']) $content = $this->getCache();
        if(empty($content)) {
            if(!empty($global_vars)) {
                if(is_string($global_vars)) $global_vars = explode(',', $global_vars);
                foreach($global_vars as $k) {
                    $k = str_replace('$', '', trim($k));
                    global $$k;
                }
            }
            if(headers_sent()) $this->error('Headers have already been sent, content create failed....');
            $script = $this->compileTemplate();
            if(is_file($script)) {
                if(count(ob_list_handlers())==0) {
                    ob_start();
                    include($script);
                    $content = ob_get_contents();
                    ob_end_clean();
                } else {
                    $temp = ob_get_contents();
                    ob_clean();
                    include($script);
                    $content = ob_get_contents();
                    ob_clean();
                    echo $temp;
                }
            } else {
                $this->error('Template compile failed !');
            }
            if($minify) {
                $content = str_replace('//<![CDATA[', '', $content);
                $content = str_replace('//]]> ', '', $content);
                $content = preg_replace('/'.preg_quote($this->delimiter_l).'.+?'.preg_quote($this->delimiter_r).'/', '', $content);
                $content = preg_replace('/\/\/[\w\s]+([\r\n]+)/', '\1', $content);
                $content = preg_replace('/\s+/', ' ', $content);
            }
            $content = preg_replace('/^'.preg_quote($this->delimiter_l).'\d+'.preg_quote($this->delimiter_r).'[\r\n]*/', '', $content);
            if($this->cache['use'] && $this->cache['expire'] > 0) myFile::saveFile($this->cache['file'], $content, 'wb');
        }
        if($tpl_show) echo $content;
        return $content;
    }
}
