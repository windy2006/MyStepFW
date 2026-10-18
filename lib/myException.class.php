<?PHP
/********************************************
*                                           *
* Name    : Exception Class For Errors      *
* Modifier: Windy2000                       *
* Time    : 2018-10-19                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/
/**
 * 文件：lib/myException.class.php
 * 功能说明：框架错误、异常及日志处理器
 *   登记PHP错误/异常/关闭回调，控制详情显示、日志输出及开发调试页面。
 *
 * 方法目录：
 *   public myException::__construct($err_msg, $code=0, $err_no=E_USER_ERROR, $err_file='', $err_line='') — 构造函数
 *   public static myException::init($options=array()) — 合并错误处理选项并登记错误、异常及关闭阶段回调
 *   public static myException::errorHandle($err_no, $err_msg, $err_file, $err_line) — 错误信息处理
 *   public static myException::exceptionHandle($e) — 抛出异常处理
 *   public static myException::shutdownCheck() — 程序终了错误检测
 *   public static myException::log() — 错误记录
 *   public static myException::clear() — 错误清除
 *   public static myException::check() — 错误检测
 *   public static myException::show() — 错误显示
 *   public static myException::getLines($file, $line, $scope = 3) — 获取某段程序代码
 *
 * 使用说明：
 *   1. 由框架启动时配置init，生产环境关闭详细输出。
 *   2. 日志与页面包含的错误上下文按配置筛选；不要通过公开页面暴露敏感调试信息。
 *   3. 错误页依赖vendor/highlight高亮资源；show/日志清理等入口遵循当前静态状态。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名保持PHP 7.4兼容，PHPDoc类型用于说明，不额外强制运行时类型。
 */


/**
 * 功能说明：登记PHP错误/异常/关闭回调，控制详情显示、日志输出及开发调试页面。
 *
 * 使用说明：
 *   1. 由框架启动时配置init，生产环境关闭详细输出。
 *   2. 日志与页面包含的错误上下文按配置筛选；不要通过公开页面暴露敏感调试信息。
 *   3. 错误页依赖vendor/highlight高亮资源；show/日志清理等入口遵循当前静态状态。
 *
 * 补充说明：
 * 程序错误处理
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 *
 * @example ../app/Document/module/myException.php 框架现有演示，仅作为文档引用，不在检查时执行
 */
class myException extends ErrorException {
    public static
        $err_all = array(),
        $err_last = array(),
        $callback = array('myException', 'show'),
        $callback_type = E_ERROR|E_CORE_ERROR|E_CORE_WARNING|E_USER_ERROR,
        $log_type = E_ALL ^ E_NOTICE,
        $log_file = 'error.log',
        $log_mode = 0,
        $exit_on_error = true;

/*
    final public string Exception::getMessage(void)
    final public Throwable Exception::getPrevious(void)
    final public int Exception::getCode(void)
    final public string Exception::getFile(void)
    final public int Exception::getLine(void)
    final public array Exception::getTrace(void)
    final public string Exception::getTraceAsString(void)
    final public string ErrorException::getSeverity(void)
*/

    /**
     * 功能说明：构造函数
     *
     * @param string $err_msg 错误或异常消息
     * @param int $code 传递给父异常对象的错误代码（默认：0）
     * @param int $err_no PHP错误级别或错误编号（默认：E_USER_ERROR）
     * @param string $err_file 错误发生的源文件路径（默认：''）
     * @param int $err_line 错误源文件行号（默认：''）
     * @return void 初始化或释放资源，无返回值。
     */
    public function __construct($err_msg, $code=0, $err_no=E_USER_ERROR, $err_file='', $err_line='') {
        parent::__construct($err_msg, $code, $err_no, $err_file, $err_line);
        $this->message = $err_msg;
        $this->code = $code;
        $this->severity = $err_no;
        $this->file = $err_file;
        $this->line = $err_line;
        self::errorHandle($err_no, $err_msg, $err_file, $err_line);
    }

    /**
     * 初始化错误类，包含如下项目
     * @param array $options
     * err_all = array()                                    已有错误记录
     * err_last = ''                                        最近的错误
     * callback = array('myException', 'show')              错误回调
     * callback_type = E_ERROR|E_CORE_ERROR|E_CORE_WARNING  需要捕获的错误类型
     * log_type = E_ALL ^ E_NOTICE                          需要记录日志的错误类型
     * log_file = 'error.log'                               日志文件位置
     * log_mode = 0                                         日志记录模式，0-用户，1-系统
     * exit_on_error = true                                 出错时终止执行
     */
    public static $display_details = false;
    protected static $shutdown_registered = false;

    /**
     * 功能说明：合并错误处理选项并登记错误、异常及关闭阶段回调
     *
     * @param array $options 功能配置选项（默认：array()）
     * @return void 无返回值。
     */
    public static function init($options=array()) {
        foreach($options as $k => $v) {
            if(!is_numeric($k) && isset(self::$$k)) self::$$k = $v;
        }
        if(!array_key_exists('display_details', $options) && isset($GLOBALS['ms_setting']->gen->debug)) self::$display_details = (bool)$GLOBALS['ms_setting']->gen->debug;
        ini_set('display_errors', 'off');
        error_reporting(self::$log_type);

        if(!empty(self::$log_file)) {
            self::$log_file = str_replace(DIRECTORY_SEPARATOR, '/', self::$log_file);
            self::$log_file = str_replace('//', '/', self::$log_file);
            $path_root = str_replace(DIRECTORY_SEPARATOR, '/', $_SERVER['DOCUMENT_ROOT']);
            $path_root = str_replace('//', '/', $path_root);
            if(stripos(self::$log_file, $path_root)===false) {
                self::$log_file = $path_root.'/'.basename(self::$log_file);
            }
        } else {
            self::$log_mode = 1;
        }
        if(self::$log_mode == 1 && !empty(self::$log_file)) {
            ini_set('log_errors', true);
            ini_set('error_log', self::$log_file);
        }
        set_error_handler(array('myException', 'errorHandle'), self::$log_type);
        set_exception_handler(array('myException', 'exceptionHandle'));
        if(!self::$shutdown_registered) { register_shutdown_function(array('myException', 'shutdownCheck')); self::$shutdown_registered = true; }
    }

    /**
     * 功能说明：错误信息处理
     *
     * @param int $err_no PHP错误级别或错误编号
     * @param string $err_msg 错误或异常消息
     * @param string $err_file 错误发生的源文件路径
     * @param int $err_line 错误源文件行号
     * @return false|null 被忽略的错误返回false，已处理分支无返回值；配置可要求终止请求
     */
    public static function errorHandle($err_no, $err_msg, $err_file, $err_line) {
        if(($err_no & self::$log_type) == 0 || !(error_reporting() & $err_no)) return false;
        if(preg_match('#^(.+) on line (\d+)#', $err_file, $match)) {
            $err_file = $match[1];
            $err_line = $match[2];
        }
        $root = dirname(__DIR__);
        $err_file = str_replace('object context in ', '', $err_file);
        $idx = count(self::$err_all);
        self::$err_all[$idx] = array(
            'err_no' => $err_no,
            'err_msg' => $err_msg,
            'err_file' => $err_file,
            'err_line' => $err_line
        );
        $err_type = array(
            E_ERROR => 'Fatal run-time errors',
            E_WARNING => 'Run-time warnings',
            E_PARSE => 'Compile-time parse error',
            E_NOTICE => 'Run-time notice',
            E_CORE_ERROR => 'Fatal Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compilation Error',
            E_COMPILE_WARNING => 'Compilation Warning',
            E_USER_ERROR => 'User-generated error',
            E_USER_WARNING => 'User-generated warning',
            E_USER_NOTICE => 'User-generated notice',
            E_STRICT => 'Suggestion notice',
            E_RECOVERABLE_ERROR => 'Catchable Fatal Error',
            E_DEPRECATED => 'Will not work Notice',
            E_USER_DEPRECATED => 'User-generated Deprecated',
            E_ALL =>    'All errors and warnings',
        );
        if(is_array($err_msg)) $err_msg = implode(chr(10), $err_msg);
        $cur_err = array();
        $cur_err['Type'] = $err_no.' - '.($err_type[$err_no] ?? 'Unkown error');
        $cur_err['IP'] = myReq::ip();
        $cur_err['Info'] = $err_msg;
        $cur_err['Time'] = date('Y-m-d H:i:s');
        $cur_err['URL'] = 'http'.((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on')?'s':'').'://'.($_SERVER['HTTP_HOST'] ?? 'localhost').($_SERVER['REQUEST_URI'] ?? '');
        $cur_err['Query'] = myReq::server('query_string');
        $cur_err['File'] = str_replace($root, '', $err_file);
        $cur_err['Line'] = $err_line;
        $cur_err['Code'] = self::getLines($err_file, $err_line);
        $cur_err['Trace'] = array();
        $trace_info = debug_backtrace();
        $n=0;
        $idx=0;
        for($i=count($trace_info)-1; $i>=0; $i--) {
            if(empty($trace_info[$i]['file'])) continue;
            $cur_err['Trace'][$n]['file'] = str_replace($root, '', $trace_info[$i]['file']);
            $cur_err['Trace'][$n]['line'] = $trace_info[$i]['line'];
            $cur_err['Trace'][$n]['function'] = $trace_info[$i]['function'];
            if($trace_info[$i]['function']=='__call') $idx = $n;
            $n++;
        }
        if($idx>0) {
            $the_file = $cur_err['Trace'][$idx];
            $err_file = $cur_err['File'] = $the_file['file'];
            $err_line = $cur_err['Line'] = $the_file['line'];
            $cur_err['Code'] = self::getLines($root.$the_file['file'], $cur_err['Line']);
        }
        self::$err_last = $cur_err;
        self::log();
        error_clear_last();
        if((self::$callback_type & $err_no) && !empty(self::$callback) && is_callable(self::$callback)) {
            call_user_func_array(self::$callback, array($err_no, $err_msg, $err_file, $err_line));
            self::clear();
            if(self::$exit_on_error) exit();
        }
    }

    /**
     * 功能说明：抛出异常处理
     *
     * @param Throwable $e 未捕获的异常对象
     * @return void 无返回值。
     */
    public static function exceptionHandle($e) {
        if(!$e instanceof Throwable) return;
        $severity = $e instanceof ErrorException ? $e->getSeverity() : E_ERROR;
        self::errorHandle($severity, $e->getMessage(), $e->getFile(), $e->getLine());
    }

    /**
     * 功能说明：程序终了错误检测
     *
     * 参数说明：无显式参数。
     *
     * @return void 无返回值。
     */
    public static function shutdownCheck() {
        if(($error=error_get_last()) && in_array($error['type'], [E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR], true)) {
            $err = $error['message'];
            $file = $error['file'];
            $line = $error['line'];
            if(preg_match('/Error: (.+?) in (.+?)\:(\d+)\n/', $error['message'], $matches)) {
                $err = $matches[1];
                $file = $matches[2];
                $line = $matches[3];
            }
            self::errorHandle($error['type'], $err, $file, $line);
        }
    }

    /**
     * 功能说明：错误记录
     *
     * 参数说明：无显式参数。
     *
     * @return void 无返回值。
     */
    public static function log() {
        if(self::$log_mode===1 || empty(self::$log_file) || empty(self::$err_last)) return;
        if($fp = fopen(self::$log_file, 'ab')) {
            $err_str = 'MyStep Error'.chr(10);
            foreach(self::$err_last as $k => $v) {
                if($k == 'Trace') {
                    $cur_item = array();
                    for($i=0,$m=count($v);$i<$m;$i++) {
                        $cur_item[] = chr(9).($i+1).'. '.$v[$i]['file'].' (line:'.$v[$i]['line'].', function:'.$v[$i]['function'].')';
                    }
                    $v = chr(10).implode(chr(10), $cur_item);
                } elseif(is_array($v)) {
                    $cur_item = array();
                    foreach($v as $k1 => $v1) {
                        if($v1 == '//') $v1 = '';
                        $cur_item[] = chr(9).$k1.'.'.trim($v1, "\r\n");
                    }
                    $v = chr(10).implode(chr(10), $cur_item);
                } elseif(strpos($v, chr(10))!==false) {
                    $v = chr(10).chr(9).str_replace(chr(10), chr(10).chr(9), trim($v, chr(10)));
                }
                $err_str .= $k.': '.$v.chr(10);
            }
            $err_str .= '-------------------------------------'.chr(10);
            fwrite($fp, $err_str);
            fclose($fp);
            unset($fp);
        }
    }

    /**
     * 功能说明：错误清除
     *
     * 参数说明：无显式参数。
     *
     * @return void 无返回值。
     */
    public static function clear() {
        self::$err_last = array();
        self::$err_all = array();
        error_clear_last();
    }

    /**
     * 功能说明：错误检测
     *
     * 参数说明：无显式参数。
     *
     * @return bool 当前是否记录了框架错误，具体基于静态错误状态
     */
    public static function check() {
        return count(self::$err_all)>0;
    }

    /**
     * 功能说明：错误显示
     *
     * 参数说明：无显式参数。
     *
     * @return bool 无待显示错误为false，已输出通用错误/详情为true；本方法本身不执行exit
     */
    public static function show() {
        if(empty(self::$err_last)) return false;
        if(!self::$display_details) {
            http_response_code(500);
            if(!headers_sent()) header('Content-Type: text/plain; charset=utf-8');
            echo 'Internal server error';
            return true;
        }
        $escape = function($value) { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
        $title = $escape('MyStep Error: '.self::$err_last['Type']);
        unset(self::$err_last['Type']);
        $root = $escape(defined('ROOT_WEB') ? ROOT_WEB : '/');
        if(self::$exit_on_error && ob_get_length()!==false) ob_clean();
        if(ob_get_length()==0) {
            echo <<<mystep
<html>
<head>
<title>Error</title>
<base href="{$root}" />
<link href="vendor/highlight/syntaxhighlighter/styles/shCoreDefault.css" rel="stylesheet" type="text/css">
<script src="vendor/highlight/syntaxhighlighter/scripts/XRegExp.js" type="text/javascript"></script>
<script src="vendor/highlight/syntaxhighlighter/scripts/shCore.js" type="text/javascript"></script>
<script src="vendor/highlight/syntaxhighlighter/scripts/shBrushPhp.js" type="text/javascript"></script>
<script type="text/javascript">SyntaxHighlighter.all();</script>
</head>

mystep;
        }
        echo <<<mystep
<div style='line-height:24px;border:1px #999 solid;white-space:nowrap;overflow:hidden;margin-bottom: 10px;'>
    <div style='background-color:#999;color:#FFF'>&nbsp;<strong>{$title}</strong></div>

mystep;
        $color='#fff';
        foreach(self::$err_last as $k => $v) {
            $color = ($color=='#fff')?'#eee':'#fff';
            if($k == 'Trace') {
                $cur_item = array();
                $cur_item[] = '<pre style="margin:0;">';
                for($i=0,$m=count($v);$i<$m;$i++) {
                    $cur_item[] = chr(9).($i+1).'. '.$escape($v[$i]['file']).' (line:'.$escape($v[$i]['line']).', function:'.$escape($v[$i]['function']).')';
                }
                $cur_item[] = '</pre>';
                $v = chr(10).implode(chr(10), $cur_item);
            } elseif(is_array($v)) {
                $cur_item = array();
                if(!empty($v)) {
                    $keys = array_keys($v);
                    if($k=='Code') $cur_item[] = '<pre class="brush:php;first-line:'.$keys[0].';highlight:'.self::$err_last['Line'].';toolbar:false;">';
                    foreach($v as $k1 => $v1) {
                        if($v1=='//') $v1='/* ----------- */';
                        $cur_item[] = trim(htmlspecialchars($v1), chr(13).chr(10));
                    }
                    if($k=='Code') $cur_item[] = '</pre>';
                }
                $v = chr(10).implode(chr(10), $cur_item);
            } else {
                $v = $escape($v);
                if(strpos($v, chr(10))!==false) {
                $v = chr(10).chr(9).str_replace(chr(10), chr(10).chr(9), trim($v, chr(10)));
                $v = nl2br($v);
                }
            }
            echo '<div style="background-color:'.$color.'">&nbsp;<strong>'.$k.': </strong>'.str_replace(chr(9), ' &nbsp; &nbsp;', $v).'</div>'.chr(10);
        }
        echo '</div>';
        return true;
    }

    /**
     * 功能说明：获取某段程序代码
     *
     * @param string $file 目标文件路径
     * @param int $line 定位的源文件行号，从1开始
     * @param int $scope 中心行前后读取的上下文范围（默认：3）
     * @return array 以源码行号为键的上下文行列表；文件不可用时返回空数组
     */
    public static function getLines($file, $line, $scope = 3) {
        $code = array();
        if(is_file($file)) {
            $rows = file($file);
            array_unshift($rows, '');
            $cnt = count($rows);
            $start = $line - $scope;
            $end = $line + $scope;
            if($start<1) $start = 1;
            if($end>$cnt) $end = $cnt;
            $code = array_slice($rows, $start, ($end-$start+1), true);
            foreach($code as $k => $v) {
                if(trim($v)=='') $code[$k] = '//';
            }
        }
        return $code;
    }
}