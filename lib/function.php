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
 * 文件：lib/function.php
 * 功能说明：框架公共函数集合
 *   提供时间日期、链接、请求环境、受信代码执行、数组递归和调试辅助。
 *
 * 方法目录：
 *   getMicrotime($rate = 5) — 获取微秒时间
 *   getTimeDiff($time_start, $decimal = 2, $micro = true) — 取得时间差
 *   getDate_cn($date='') — 获取中文日期
 *   formatDate($date='', $format='Y-m-d') — 格式化日期
 *   shortUrl($url, $max_length = 40) — 缩略链接
 *   tinyUrl($url) — 获取短网址
 *   isMobile() — 判断是否为移动设备
 *   isHttps() — 判断当前是否为SSL链接
 *   myEval($code, $return = false) — 自定义代码执行
 *   checkPara(&$att_list, $parse = false) — 检测数据变量中是否有待解析的变量，并解析
 *   arrayMerge($arr_1, $arr_2) — 递归合并数组
 *   recursionFunction($func, $para) — 递归执行某一函数
 *   getOB($clean=true) — 获取缓存区内容并清空
 *   debug_show() — 变量情况查看
 *   debug() — 清空输出缓冲并展示任意个值，然后结束当前请求
 *   debug_set() — 开启当前请求的条件调试标记
 *   debug_check() — 已开启条件调试时展示传入值并结束请求
 *
 * 使用说明：
 *   1. 框架入口会加载本文件，全局函数可由核心类和应用直接调用。
 *   2. myEval及变量表达式处理只接受受信程序内容，不能当作输入过滤器。
 *   3. debug会结束请求，debug_show仅展示；会影响HTTP响应的调试调用仅用于开发。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名保持PHP 7.4兼容，PHPDoc类型用于说明，不额外强制运行时类型。
 */


/**
 * 功能说明：获取微秒时间
 *
 * @param int $rate 保留微秒部分的小数位数；返回秒.小数形式的时间文本（默认：5）
 * @return string 秒.小数形式的当前时间文本
 */
function getMicrotime($rate = 5) {
    list($usec, $sec) = explode(' ', microtime());
    $time = (string)$sec.'.'.substr($usec, 2, $rate);
    return $time;
}

/**
 * 功能说明：取得时间差
 *
 * @param int|float|string $time_start getMicrotime等取得的开始时间，单位秒
 * @param int $decimal 时间差保留的小数位数（默认：2）
 * @param bool $micro true将秒差换算为毫秒，false保留秒（默认：true）
 * @return float 按decimal舍入的耗时；micro为true时单位毫秒，否则为秒
 */
function getTimeDiff($time_start, $decimal = 2, $micro = true) {
    $time_end = getMicrotime();
    $time = ($time_end - $time_start);
    if($micro) $time *= 1000;
    $time = round($time, $decimal);
    return $time;
}

/**
 * 功能说明：获取中文日期
 *
 * @param string|int $date 待解析的日期文本或时间戳，空串采用当前日期（默认：''）
 * @return string 以中文数字表示的年月日文本，例如二○二六年十月十六日
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
 * 功能说明：格式化日期
 *
 * @param string|int $date 待解析的日期文本或时间戳，空串采用当前日期（默认：''）
 * @param string $format 输出或输入处理的格式规则（默认：'Y-m-d'）
 * @return string 按format格式化的日期；格式不符合历史要求时回退Y-m-d
 */
function formatDate($date='', $format='Y-m-d') {
    if(!is_numeric($date) && ($date=strtotime($date))===false) $date = time();
    $result = date($format, $date);
    if($result==$format || !preg_match('#^\d#', $result)) $result = date('Y-m-d', $date);
    return $result;
}

/**
 * 功能说明：缩略链接
 *
 * @param string $url 需要缩短展示的URL；会移除查询部分并压缩路径，不生成HTML链接
 * @param int $max_length 显示链接的最大长度（默认：40）
 * @return string 仅用于展示的缩短链接文本，不包含HTML标签
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
    $slices = explode('/', trim(($slices['path'] ?? ''), '/'));
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
 * 功能说明：获取短网址
 *
 * @param string $url 需要提交给短网址服务的原始URL
 * @return string 短网址服务的响应文本；传输失败或空响应均为空串，服务错误文本不自动转为false
 */
function tinyUrl($url) {
    return myFile::getRemote('https://tinyurl.com/api-create.php?url='.urlencode($url));
}

/**
 * 功能说明：判断是否为移动设备
 *
 * 参数说明：无显式参数。
 *
 * @return bool 请求头命中移动终端启发式规则为true，否则为false；不是可靠设备身份认证
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
 * 功能说明：判断当前是否为SSL链接
 *
 * 参数说明：无显式参数。
 *
 * @return bool 原生HTTPS标记或可信代理的HTTPS转发标记成立为true，否则为false
 */
function isHttps() {
    if(!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
        return true;
    } elseif(class_exists('myReq', false) && myReq::trustedProxy() && isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        return true;
    } elseif(class_exists('myReq', false) && myReq::trustedProxy() && !empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
        return true;
    }
    return false;
}

/**
 * 功能说明：自定义代码执行
 *
 * @param string $code 受信PHP代码文本，不接受不可信用户输入
 * @param bool $return true取得include/表达式的返回值，false捕获并返回执行输出；不直接发送捕获内容（默认：false）
 * @return mixed return为true时是代码返回值；为false时是捕获的输出字符串
 * @throws RuntimeException 无法创建或完整写入受信代码的临时文件
 */
function myEval($code, $return = false) {
    $file = tempnam(sys_get_temp_dir(), 'ms_');
    if($file === false) throw new RuntimeException('Cannot create evaluation file');
    if(!preg_match('#^<\?php#i', $code)) {
        if($return && !preg_match('#^[\r\n\s]*return\b#i', $code)) $code = 'return '.$code.';';
        $code = '<?php'."\n".$code;
    }
    $level = ob_get_level();
    try {
        if(file_put_contents($file, $code, LOCK_EX) !== strlen($code)) throw new RuntimeException('Cannot write evaluation file');
        if($return) return include($file);
        ob_start();
        include($file);
        return ob_get_contents();
    } finally {
        while(ob_get_level() > $level) ob_end_clean();
        unlink($file);
    }
}

/**
 * 功能说明：检测数据变量中是否有待解析的变量，并解析
 *
 * @param array &$att_list 引用传入的属性列表，解析时原地更新
 * @param bool $parse 是否将检测到的变量表达式替换为实际值（默认：false）
 * @return bool 检测到以$开头的待解析属性为true，否则为false；parse为true时原地更新属性
 */
function checkPara(&$att_list, $parse = false) {
    $flag = false;
    foreach($att_list as $k => $v) {
        if(is_string($v) && strpos($v, '$')===0) {
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
 * 功能说明：递归合并数组
 *
 * @param array $arr_1 用于合并的基础数组
 * @param mixed $arr_2 递归合并的数组；非数组值追加到第一个数组
 * @return array|false 按历史递归覆盖/追加规则合并的新数组；第一个参数不是数组为false
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
 * 功能说明：递归执行某一函数
 *
 * @param callable $func 应用到数组叶子或单个值上的回调
 * @param mixed $para 需要递归处理的值或数组
 * @return mixed 回调处理后的值或保持原键结构的数组
 */
function recursionFunction($func, $para) {
    if(is_callable($func)) {
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
 * 功能说明：获取缓存区内容并清空
 *
 * @param bool $clean 是否同时清空当前输出缓冲（默认：true）
 * @return string|false 当前输出缓冲内容，未启用缓冲为false；clean只清空内容，不关闭缓冲
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
 * 功能说明：变量情况查看
 *
 * @param mixed ...$args 需要依次输出的任意个调试值（历史位置参数，由func_get_args读取）
 * @return void 无返回值。
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
/**
 * 功能说明：清空输出缓冲并展示任意个值，然后结束当前请求
 *
 * @param mixed ...$args 需要依次展示的调试值，展示后结束请求（历史位置参数，由func_get_args读取）
 * @return void 无返回值。
 */
function debug() {
    getOB(true);
    call_user_func_array('debug_show', func_get_args());
    exit;
}
/**
 * 功能说明：开启当前请求的条件调试标记
 *
 * 参数说明：无显式参数。
 *
 * @return void 无返回值。
 */
function debug_set() {
    global $__sign__;
    $__sign__ = true;
}
/**
 * 功能说明：已开启条件调试时展示传入值并结束请求
 *
 * @param mixed ...$args 条件调试开启时交给debug的值（历史位置参数，由func_get_args读取）
 * @return void 无返回值。
 */
function debug_check() {
    global $__sign__;
    if(!empty($__sign__)) debug(...func_get_args());
}