<?php
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
 * 程序错误处理
 */
class myException extends ErrorException {
	public static
		$err_all = array(),
		$err_last = array(),
		$callback = array('myException','show'),
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
	 * 构造函数
	 * @param $err_msg
	 * @param int $code
	 * @param int $err_no
	 * @param string $err_file
	 * @param string $err_line
	 */
	public function __construct($err_msg, $code=0, $err_no=E_USER_ERROR, $err_file='', $err_line='') {
		parent::__construct($err_msg, $code, $err_no, $err_file, $err_line);
		$this->message = $err_msg;
		$this->code = $code;
		$this->severity = $err_no;
		$this->file = $err_file;
		$this->line = $err_line;
		self::errorHandle($err_no, $err_msg, $err_file, $err_line);
		return;
	}

	/**
	 * 初始化错误类，包含如下项目
	 * @param array $options
	 * err_all = array()									已有错误记录
	 * err_last = ''										最近的错误
	 * callback = array('myException','show')				 错误回调
	 * callback_type = E_ERROR|E_CORE_ERROR|E_CORE_WARNING	需要捕获的错误类型
	 * log_type = E_ALL ^ E_NOTICE							需要记录日志的错误类型
	 * log_file = 'error.log'								 日志文件位置
	 * log_mode = 0										 日志记录模式，0-系统，1-用户
	 * exit_on_error = true								 出错时终止执行
	 */
	public static function init($options=array()) {
		foreach($options as $k => $v) {
			if(!is_numeric($k) && isset(self::$$k)) self::$$k = $v;
		}
		ini_set('display_errors', 'off');
		//ini_set('track_errors', true);
		error_reporting(self::$log_type);
		
		if(!empty(self::$log_file)) {
			self::$log_file = str_replace(DIRECTORY_SEPARATOR, '/', self::$log_file);
			self::$log_file = str_replace('//', '/', self::$log_file);
			$path_root = str_replace(DIRECTORY_SEPARATOR, '/', $_SERVER['DOCUMENT_ROOT']);
			$path_root = str_replace('//', '/', $path_root);
			if(stripos(self::$log_file,$path_root)===false) {
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
		register_shutdown_function(array('myException', 'shutdownCheck'));
	}

	/**
	 * 错误信息处理
	 * @param $err_no
	 * @param $err_msg
	 * @param $err_file
	 * @param $err_line
	 */
	public static function errorHandle($err_no, $err_msg, $err_file, $err_line) {
		if(($err_no & self::$log_type) == 0) return;
		$root = dirname(dirname(__FILE__));
		if(is_file($err_file)) {
			$the_line = file($err_file);
			$the_line = preg_replace('/(^\s+|[\r\n]+)/', '', $the_line[$err_line-1]);
			if(strpos($the_line, '@')!==false) return;
		} else {
			$the_line = '';
		}
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
			E_ALL =>	'All errors and warnings',
		);
		if(is_array($err_msg)) $err_msg = implode(chr(10),$err_msg);
		$cur_err = array();
        $cur_err['Type'] = $err_no.' - '.($err_type[$err_no] ?? 'Unkown error');
        $cur_err['Info'] = $err_msg;
        $cur_err['Time'] = date('Y-m-d H:i:s');
        $cur_err['URL'] = 'http'.((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on')?'s':'').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];;
        $cur_err['File'] = str_replace($root, '', $err_file);
        $cur_err['Line'] = $err_line;
        //$cur_err['Code'] = $the_line;
        $cur_err['Code'] = self::getLines($err_file, $err_line);
        $cur_err['Trace'] = array();
		$trace_info = debug_backtrace();
		$n=0;
		for($i=count($trace_info)-1; $i>=0; $i--) {
			if(empty($trace_info[$i]['file'])) continue;
            $cur_err['Trace'][$n]['file'] = str_replace($root, '', $trace_info[$i]['file']);
            $cur_err['Trace'][$n]['line'] = $trace_info[$i]['line'];
            $cur_err['Trace'][$n]['function'] = $trace_info[$i]['function'];
            $n++;
		}
		self::$err_last = $cur_err;
		self::log();
		error_clear_last();
		if((self::$callback_type & $err_no) && !empty(self::$callback) && is_callable(self::$callback)) {
			call_user_func_array(self::$callback, array($err_no, $err_msg, $err_file, $err_line));
			self::clear();
			if(self::$exit_on_error) exit();
		}
		return;
	}

	/**
	 * 抛出异常处理
	 * @param $e
	 */
	public static function exceptionHandle($e) {
		if(!$e instanceof ErrorException) {
			$e = new ErrorException($e);
		}
		$err = $e->getMessage();
		$file = $e->getFile();
		$line = $e->getLine();
		if(preg_match('/Error: (.+?) in (.+?)\:(\d+)\n/', $e->getMessage(), $matches)) {
			$err = $matches[1];
			$file = $matches[2];
			$line = $matches[3];
		}
		self::errorHandle($e->getSeverity(), $err, $file, $line);
		return;
	}

	/**
	 * 程序终了错误检测
	 */
	public static function shutdownCheck(){
		if($error=error_get_last()){
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
		return;
	}

	/**
	 * 错误记录
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
                    $v = chr(10).implode(chr(10),$cur_item);
                } elseif(is_array($v)) {
                    $cur_item = array();
                    foreach($v as $k1 => $v1) {
                        $cur_item[] = chr(9).$k1.'.'.trim($v1, "\r\n");
                    }
                    $v = chr(10).implode(chr(10),$cur_item);
                } elseif(strpos($v, chr(10))) {
		            $v = chr(10).chr(9).str_replace(chr(10), chr(10).chr(9), $v);
                }
                $err_str .= $k.': '.$v.chr(10);
            }
            $err_str .= '-------------------------------------'.chr(10);
			fwrite($fp, $err_str);
			fclose($fp);	
			unset($fp);
		}
		return;
	}

	/**
	 * 错误清除
	 */
	public static function clear() {
		self::$err_last = array();
		self::$err_all = array();
		error_clear_last();
		return;
	}

	/**
	 * 错误检测
	 * @return bool
	 */
	public static function check() {
		return count(self::$err_all)>0;
	}

	/**
	 * 错误显示
	 * @return bool
	 */
	public static function show() {
		if(empty(self::$err_last)) return false;
		$title = 'MyStep Error: '.self::$err_last['Type'];
		unset(self::$err_last['Type']);
		echo <<<mystep
<link href="http://alexgorbatchev.com/pub/sh/current/styles/shCore.css" rel="stylesheet" type="text/css">
<link href="http://alexgorbatchev.com/pub/sh/current/styles/shThemeDefault.css" rel="stylesheet" type="text/css">
<div style='line-height:24px;border:#999 1px solid;white-space:nowrap;overflow:hidden;'>
<div style='background-color:#999;color:#FFF'>&nbsp;<strong>{$title}</strong></div>

mystep;
		$color='#fff';
		foreach(self::$err_last as $k => $v) {
            $color = ($color=='#fff')?'#eee':'#fff';
            if($k == 'Trace') {
                $cur_item = array();
                $cur_item[] = '<pre>';
                for($i=0,$m=count($v);$i<$m;$i++) {
                    $cur_item[] = chr(9).($i+1).'. '.$v[$i]['file'].' (line:'.$v[$i]['line'].', function:'.$v[$i]['function'].')';
                }
                $cur_item[] = '</pre>';
                $v = chr(10).implode(chr(10),$cur_item);
            } elseif(is_array($v)) {
                $cur_item = array();
                $keys = array_keys($v);
                if($k=='Code') $cur_item[] = '<pre class="brush:php;first-line:'.$keys[0].';highlight:'.self::$err_last['Line'].'">';
                foreach($v as $k1 => $v1) {
                    $cur_item[] = trim(htmlspecialchars($v1), "\r\n");
                }
                if($k=='Code') $cur_item[] = '</pre>';
                $v = chr(10).implode(chr(10),$cur_item);
            } elseif(strpos($v, chr(10))) {
                $v = chr(10).chr(9).str_replace(chr(10), chr(10).chr(9), $v);
                $v = nl2br($v);
            }
            echo '<div style="background-color:'.$color.'">&nbsp;<strong>'.$k.': </strong>'.str_replace(chr(9), ' &nbsp; &nbsp;', $v).'</div>'.chr(10);
        }
		echo '</div>'.chr(10);
		echo '<div>&nbsp;</div>'.chr(10);
        echo <<<mystep
<script src="http://alexgorbatchev.com/pub/sh/current/scripts/shCore.js" type="text/javascript"></script>
<script src="http://alexgorbatchev.com/pub/sh/current/scripts/shBrushPhp.js" type="text/javascript"></script>
<script type="text/javascript">SyntaxHighlighter.all();console.log(111)</script>


mystep;
		return true;
	}

    /**
     * 获取某段程序代码
     * @param $file
     * @param $line
     * @param int $scope
     * @return array
     */
	public static function getLines($file, $line, $scope = 3) {
	    $code = array();
	    if(file_exists($file)) {
            $rows = file($file);
            array_unshift($rows, '');
            $cnt = count($rows);
            $start = $line - $scope;
            $end = $line + $scope;
            if($start<1) $start = 1;
            if($end>$cnt) $end = $cnt;
            $code = array_slice($rows, $start, ($end-$start+1), true);
        }
	    return $code;
    }
}