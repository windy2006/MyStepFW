<?PHP
/********************************************
*                                           *
* Name    : Functions 4 PHP                 *
* Modifier: Windy2000                       *
* Time    : 2018-11-03                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/

/**
 * 获取微秒时间
 * @param int $rate
 * @return string
 */
function getMicrotime($rate = 5) {
    list($usec, $sec) = explode(' ', microtime());
    $time = (string)$sec.'.'.substr($usec, 2, $rate);
    return $time;
}

/**
 * 取得时间差
 * @param $time_start
 * @param int $decimal
 * @param bool $micro
 * @return int|null|string|string[]
 */
function getTimeDiff($time_start, $decimal = 2, $micro = true) {
    $time_end = getMicrotime();
    $time = ($time_end - $time_start);
    if($micro) $time *= 1000;
    $time = round($time, $decimal);
    return $time;
}

/**
 * 获取中文日期
 * @param string $date
 * @return string
 */
function getDate_cn($date='') {
    if(empty($date)) $date=time();
    if(!is_numeric($date)) $date = strtotime($date);
    $the_year = (STRING)date('Y', $date);
    $the_month = (STRING)date('n', $date);
    $the_day = (STRING)date('j', $date);
    $num_cn = array();
    $num_cn[] = array('○', '十', '廿', '卅');
    $num_cn[] = array('○', '一', '二', '三', '四', '五', '六', '七', '八', '九');
    $result = '';
    for($i=0,$m=strlen($the_year);$i<$m;$i++) {
        $result .= $num_cn[1][$the_year[$i]];
    }
    $result .= '年';
    for($i=0,$m=strlen($the_month);$i<$m;$i++) {
        if($m==1 && $i==0) {
            $result .= $num_cn[1][$the_month[$i]];
            break;
        } else {
            $result .= $num_cn[$i][$the_month[$i]];
        }
    }
    $result .= '月';
    for($i=0,$m=strlen($the_day);$i<$m;$i++) {
        if($m==1 && $i==0) {
            $result .= $num_cn[1][$the_day[$i]];
            break;
        } else {
            $result .= $num_cn[$i][$the_day[$i]];
        }
    }
    $result .= '日';
    return $result;
}

/**
 * 格式化日期
 * @param string $date
 * @param string $format
 * @return false|string
 */
function formatDate($date='', $format='Y-m-d') {
    if(!is_numeric($date) && ($date=strtotime($date))===false) $date = time();
    $result = date($format, $date);
    if($result==$format || !preg_match('#^\d#', $result)) $result = date('Y-m-d', $date);
    return $result;
}

/**
 * 缩略链接
 * @param $url
 * @param int $max_length
 * @return mixed
 */
function shortUrl($url, $max_length = 40) {
    $url = preg_replace('#[&\?].*$#', '', $url);
    $slices = parse_url($url);
    $link = '';
    $tail = '';
    if(isset($slices['scheme'])) $link = $slices['scheme'].'://';
    if(isset($slices['host'])) $link .= $slices['host'].'/';
    if(strlen($link)>$max_length) return substr($link, 0, $max_length);
    $break = false;
    $slices = explode('/', trim($slices['path'], '/'));
    while(count($slices)>0) {
        $link_2 = '/'.array_pop($slices);
        $link_1 = count($slices)>0 ? (array_shift($slices).'/') : '';
        if(strlen($link.$link_2.$tail)>$max_length) {
            $link .= '...'.$tail;
            $break = true;
            break;
        } else {
            $tail = $link_2.$tail;
            if(strlen($link.$link_1.$tail)>$max_length) {
                $link .= '...'.$tail;
                $break = true;
                break;
            } else {
                $link .= $link_1;
            }
        }
    }
    if(count($slices)==0 && !$break) $link .= trim($tail, '/');
    return $link;
}

/**
 * 获取短网址
 * @param $url
 * @return bool|string
 */
function tinyUrl($url) {
    return file_get_contents('http://tinyurl.com/api-create.php?url='.urlencode($url));
}

/**
 * 判断是否为移动设备
 * @return bool
 */
function isMobile() {
    if(isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
        return true;
    }
    if(isset($_SERVER['HTTP_VIA'])) {
        return stristr($_SERVER['HTTP_VIA'], 'wap') ? true : false;
    }
    if(isset($_SERVER['HTTP_USER_AGENT'])) {
        $clientkeywords = array('iphone', 'android', 'phone', 'mobile', 'wap', 'netfront', 'java', 'opera mobi', 'opera mini',
                'ucweb', 'windows ce', 'symbian', 'series', 'webos', 'sony', 'blackberry', 'dopod', 'nokia', 'samsung',
                'palmsource', 'xda', 'pieplus', 'meizu', 'midp', 'cldc', 'motorola', 'foma', 'docomo', 'up.browser',
                'up.link', 'blazer', 'helio', 'hosin', 'huawei', 'novarra', 'coolpad', 'webos', 'techfaith', 'palmsource',
                'alcatel', 'amoi', 'ktouch', 'nexian', 'ericsson', 'philips', 'sagem', 'wellcom', 'bunjalloo', 'maui', 'smartphone',
                'iemobile', 'spice', 'bird', 'zte-', 'longcos', 'pantech', 'gionee', 'portalmmm', 'jig browser', 'hiptop',
                'benq', 'haier', '^lct', '320x320', '240x320', '176x220', 'windows phone',
                'cect', 'compal', 'ctl', 'lg', 'nec', 'tcl', 'alcatel', 'ericsson', 'bird', 'daxian', 'dbtel', 'eastcom',
                'pantech', 'dopod', 'philips', 'haier', 'konka', 'kejian', 'lenovo', 'benq', 'mot', 'soutec', 'nokia', 'sagem', 'sgh',
                'sed', 'capitel', 'panasonic', 'sonyericsson', 'sharp', 'amoi', 'panda', 'zte', 'sie-', 'ipod', 'windowsce', 'operamini',
                'operamobi', 'openwave', 'nexusone', 'pad', 'gt-p1000');
        if(preg_match('/('.implode('|', $clientkeywords).')/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
            return true;
        }
    }
    if(isset($_SERVER['HTTP_ACCEPT'])) {
        if((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) &&(strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false ||(strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
            return true;
        }
    }
    return false;
}

/**
 * 判断当前是否为SSL链接
 * @return bool
 */
function isHttps() {
    if(!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
        return true;
    } elseif(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        return true;
    } elseif(!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
        return true;
    }
    return false;
}

/**
 * 自定义代码执行
 * @param $code
 * @param bool $return
 * @return false|mixed|string
 */
function myEval($code, $return = false) {
    $file = tempnam(sys_get_temp_dir(), 'ms_');
    if($file==false) {
        f::mkdir(CACHE.'temp/');
        $file = CACHE.'temp/'.getMicrotime();
    }
    $fp = fopen($file, 'w');
    if(!preg_match('#^<\?PHP#i', $code)) {
        if($return && !preg_match('#^[\r\n\s]*return#i', $code)) {
            $code = 'return '.$code.';';
        }
        $code = '<?PHP'.chr(10).$code;
    }
    fwrite($fp, $code);
    if($return) {
        $result = include($file);
    } else {
        ob_clean();
        include($file);
        $result = ob_get_contents();
        ob_clean();
    }
    fclose($fp);
    @unlink($file);
    return $result;
}

/**
 * 检测数据变量中是否有待解析的变量，并解析
 * @param $att_list
 * @param bool $parse
 * @return bool
 */
function checkPara(&$att_list, $parse = false) {
    $flag = false;
    foreach($att_list as $k => $v) {
        if(strpos($v, '$')===0) {
            $flag = true;
            if($parse) {
                $att_list[$k] = myEval($v, true);
            } else {
                break;
            }
        }
    }
    return $flag;
}

/**
 * 递归合并数组
 * @param $arr_1
 * @param $arr_2
 * @return array|bool
 */
function arrayMerge($arr_1, $arr_2) {
    if(!is_array($arr_1)) return false;
    if(!is_array($arr_2)) {
        $arr_1[] = $arr_2;
    } else {
        foreach($arr_1 as $key => $value) {
            if(isset($arr_2[$key])) {
                if(is_array($arr_1[$key])) {
                    if(is_array($arr_2[$key])) {
                        $arr_1[$key] = arrayMerge($arr_1[$key], $arr_2[$key]);
                    } else {
                        $arr_1[$key][] = $arr_2[$key];
                    }
                } else {
                    if(is_array($arr_2[$key])) {
                        $arr_1[$key] = arrayMerge(array($arr_1[$key]), $arr_2[$key]);
                    } else {
                        $arr_1[$key] = $arr_2[$key];
                    }
                }
            }
        }
        foreach($arr_2 as $key => $value) {
            if(!isset($arr_1[$key])) {
                $arr_1[$key] = $arr_2[$key];
            }
        }
    }
    return $arr_1;
}

/**
 * 递归执行某一函数
 * @param $func
 * @param $para
 * @return array
 */
function recursionFunction($func, $para) {
    if(function_exists($func)) {
        if(is_array($para)) {
            foreach($para as $key => $value) {
                $para[$key] = recursionFunction($func, $value);
            }
        } else {
            $para = $func($para);
        }
    }
    return $para;
}

/**
 * 获取缓存区内容并清空
 * @param bool $clean
 * @return false|string
 */
function getOB($clean=true) {
    $result = '';
    if(count(ob_list_handlers())>0 && ob_get_length()!==false) {
        $result = ob_get_contents();
        if($clean) ob_clean();
    }
    return $result;
}

/**
 * 变量情况查看
 */
function debug_show() {
    echo '<pre>';
    for($i = 0; $i < func_num_args(); $i++) {
        if(class_exists('myReflection') && (is_object(func_get_arg($i)))) {
            $t = new myReflection(func_get_arg($i));
            var_dump($t->info());
        } else {
            var_dump(func_get_arg($i));
        }
    }
    echo '</pre>';
}
function debug() {
    getOB(true);
    call_user_func_array('debug_show', func_get_args());
    exit;
}
function debug_set() {
    global $__sign__;
    $__sign__ = true;
}
function debug_check() {
    global $__sign__;
    if($__sign__) debug(func_get_args());
}