<?php
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
 * 框架变量初始化
 */
function initFW() {
	$class = is_file(CONFIG.'class.php') ? include((CONFIG.'class.php')) : array();
	if(empty($class) || !is_dir($class[0]['path'])) {
	    $old_root = preg_replace('#lib/$#', '', $class[0]['path']);
		$class[0] = array(
			'path' => ROOT . 'lib/',
			'ext' => '.php,.class.php',
			'idx' =>
				array(
					'jsMin' => 'myMinify.class.php',
					'cssMin' => 'myMinify.class.php',
					'JavaScriptPacker' => 'myMinify.class.php',
				),
		);
		for($i=1,$m=count($class);$i<$m;$i++) {
            $class[$i]['path'] = preg_replace('#^'.$old_root.'#', ROOT, $class[$i]['path']);
        }
		@unlink(CONFIG.'class.php');
		file_put_contents(CONFIG.'class.php', '<?php'.chr(10).'return '.var_export($class, true).';');

        $setting_class = include(CONFIG.'class.php');
		regClass($setting_class);

		myFile::del(CONFIG.'route.php');
		$dirs = myFile::find('',APP,false, myFile::DIR);
		$dirs = array_map(function($v){return basename($v);} ,$dirs);
		foreach($dirs as $k) {
			if(is_file(APP.$k.'/route.php')) {
				myRouter::checkRoute(CONFIG.'route.php', APP.$k.'/route.php', $k);
			}
		}
		myFile::del(CACHE.'template');
		myFile::del(CACHE.'session');
	} else {
		$setting_class = include(CONFIG.'class.php');
		regClass($setting_class);
	}
	myException::init(array(
		'log_mode' => 0,
		'log_type' => E_ALL ^ E_NOTICE,
		'log_file' => ROOT.'/error.log',
		'callback_type' => E_ALL,
		'exit_on_error' =>  false
	));

	global $s, $p, $q;
	$s = new myConfig(CONFIG.'config.php');
	$qstr = trim(myReq::svr('QUERY_STRING'));
	$the_file = ROOT.preg_replace('#&.+$#', '', $qstr);
	$ext = strtolower(pathinfo($the_file, PATHINFO_EXTENSION));
	$ext_list = explode(',', $s->gen->static);
	if(strpos($qstr,'static')===0 || (is_file($the_file) && in_array($ext, $ext_list))) myController::file($the_file);

	if(($s->cookie->domain = strstr(myReq::server('HTTP_HOST'), ':', true))===false) {
		$s->cookie->domain = myReq::server('HTTP_HOST');
	}
	$s->cookie->path = dirname(myReq::server('SCRIPT_NAME'));
	$s->web->url = 'http://'.myReq::server('HTTP_HOST');
	array_shift($_GET);
	preg_match('#^(.+?)(&(.+))?$#', $qstr, $match);
	$p = $match[1];
	$q = $match[3] ?? '';
	route();
	return;
}

/**
 * 应用路由
 */
function route() {
    global $lib_list, $info_app, $s, $setting_tpl, $mystep;
    $router = new myRouter((array)$s->router);
    $router->setRules(CONFIG.'route.php');
    if(!$router->check($lib_list)) {
        $info_app = $router->parse();
        if(!empty($info_app)) {
            if(!is_dir(APP.$info_app['app'])) {
                array_unshift($info_app['path'], $info_app['app']);
                $info_app['app'] = $s->router->default_app;
            }
            if(is_file(APP.$info_app['app'].'/config.php')) {
                $s->merge(APP.$info_app['app'].'/config.php');
            }
            require(APP.$info_app['app'].'/index.php');
        } else {
            myController::redirect('/');
        }
    }
}

/**
 * 应用模块初始化参数设置
 */
function initPara() {
	global $info_app, $mystep, $setting_tpl, $setPlugin;
	if($mystep!=null) return;
	if(!defined('PATH')) {
		define('PATH', APP.$info_app['app'].'/');
		define('ROOT_WEB', str_replace(myFile::rootPath(),'/',ROOT));
	}
	if(is_file(PATH.$info_app['app'].'.class.php')) {
		require_once(PATH.$info_app['app'].'.class.php');
		$mystep = new $info_app['app']();
	} else {
		$mystep = new myStep();
	}

	if(is_callable(array($mystep, 'preload'))) $mystep->preload();

	if(is_null($setPlugin)) $setPlugin = true;
	$mystep->start($setPlugin);

	if(is_file(PATH.'config.php')) $mystep->setting->merge(PATH.'config.php');

	$mystep->addCSS(STATICS.'css/bootstrap.css');
	$mystep->addCSS(STATICS.'css/font-awesome.css');
	$mystep->addCSS(STATICS.'css/glyphicons.css');
	$mystep->addCSS(STATICS.'css/global.css');
	$mystep->addCSS(PATH.'asset/style.css');
	$mystep->addCSS(PATH.'asset/'.$mystep->setting->template->style.'/style.css');
	$mystep->setting->css = CACHE.'script/'.$info_app['app'].'.css';

	$mystep->addJS(STATICS.'js/jquery.js');
	$mystep->addJS(STATICS.'js/jquery-ui.js');
	$mystep->addJS(STATICS.'js/jquery.addon.js');
	$mystep->addJS(STATICS.'js/bootstrap.bundle.js');
	$mystep->addJS(STATICS.'js/global.js');
	$mystep->addJS(PATH.'asset/function.js');
	$mystep->addJS(PATH.'asset'.$mystep->setting->template->style.'/function.js');
	$mystep->setting->js = CACHE.'script/'.$info_app['app'].'.js';

	$setting_tpl = array(
		'name' => $mystep->setting->template->name,
		'path' => PATH.$mystep->setting->template->path,
		'style' => $mystep->setting->template->style,
		'path_compile' => CACHE.'template/'.$info_app['app'].'/'
	);
}

/**
 * 应用模块调用
 */
function getModule($m) {
	global $mystep, $setting_tpl, $setting_cache, $info_app, $s, $q, $p, $db, $cache;
	$m = preg_replace('#/.*$#', '', $m);
	$style = $setting_tpl['style'];
	$files = [
		PATH.'module/'.$style.'/'.$m.'.php',
		PATH.'module/'.$style.'/'.($info_app['path'][1]??'').'.php',
		PATH.'module/'.$m.'.php',
		PATH.'module/'.($info_app['path'][1]??'').'.php',
		PATH.'module/'.$style.'/index.php',
		PATH.'module/index.php',
		''
	];
	foreach($files as $f) {
		if(file_exists($f)) break;
		if(empty($f))  myStep::info($mystep->getLanguage('module_missing'));
	}
	$tpl = new myTemplate($setting_tpl, $setting_cache);
	if(count($info_app['path'])==1) $info_app['path'][1]='';
	$tpl->assign('path', implode('/', $info_app['path']));
	include($f);
	if(isset($content)) $tpl->assign('main', $content);
	$mystep->show($tpl);
	$mystep->end();
}

/**
 * 调用第三放组件
 * @return mixed
 */
function vendor() {
    $args = func_get_args();
    $name = array_shift($args);
	$file = VENDOR.$name.'/'.$name.'.php';
	if(!file_exists($file)) $file = $file = VENDOR.$name.'/'.$name.'.class.php';
	if(!file_exists($file)) {
		global $mystep;
		myStep::info($mystep->getLanguage('module_missing'));
	}
	require_once($file);
	$instance = new $name();
	if(count($args) && is_callable([$instance, '__construct'])) {
		call_user_func_array([$instance, '__construct'], $args);
	}
	return $instance;
}

/**
 * opCache 设置
 * @param string $setting
 * @return array|bool
 */
function setOp($setting='check') {
	if($result = function_exists('opcache_compile_file')) {
		if($result = opcache_get_status()) {
			switch(true) {
				case is_array($setting):
					foreach($setting as $k => $v) {
						@ini_set('opcache.'.$k, $v);
					}
					if(isset($setting['file_cache'])) myFile::mkdir($setting['file_cache']);
				case $setting=='check':
					opcache_invalidate($_SERVER['SCRIPT_FILENAME'], false);
					if(($result=opcache_is_script_cached($_SERVER['SCRIPT_FILENAME']))===false)
						$result=opcache_compile_file($_SERVER['SCRIPT_FILENAME']);
					break;
				case $setting=='config':
					$result = opcache_get_configuration();
					break;
				case $setting=='reset':
					opcache_reset();
					break;
			}
		}
	}
	return $result;
}

/**
 * 设置类载入规则
 * @param $setting
 */
function regClass($setting) {
	$idx = array();
	foreach($setting as $current) {
		/*
		set_include_path(get_include_path().PATH_SEPARATOR.$current['path']);
		spl_autoload_extensions($current['ext']);
		spl_autoload_register();
		*/
		spl_autoload_register(function($class)use($current){
			$ext = explode(',', $current['ext']);
			foreach($ext as $e) {
				if(is_file($current['path'].$class.$e)) require_once($current['path'].$class.$e);
			}
		});
		if(isset($class['idx'])) $idx += $current['idx'];
	}
	if(!empty($idx)) {
		spl_autoload_register(function($class)use($idx){
			if(array_key_exists($class, $idx)) require_once($idx[$class]);
		});
	}
}

/**
 * 设置类别名
 * @param $list
 */
function setAlias($list) {
	foreach ($list as $k => $v) {
		if(class_exists($k)) class_alias($k, $v);
	}
	return;
}

/**
 * 获取微秒时间
 * @param int $rate
 * @return string
 */
function getMicrotime($rate = 5) {
	list($usec, $sec) = explode(' ',microtime());
	$time = (string)$sec.'.'.substr($usec,2,$rate);
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
	$num_cn[] = array('○','十','廿','卅');
	$num_cn[] = array('○','一','二','三','四','五','六','七','八','九');
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
	if(isset($_SERVER['HTTP_X_WAP_PROFILE'])){
		return true;
	}
	if(isset($_SERVER['HTTP_VIA'])){
		return stristr($_SERVER['HTTP_VIA'], 'wap') ? true : false;
	}
	if(isset($_SERVER['HTTP_USER_AGENT'])){
		$clientkeywords = array('iphone', 'android', 'phone', 'mobile', 'wap', 'netfront', 'java', 'opera mobi', 'opera mini',
				'ucweb', 'windows ce', 'symbian', 'series', 'webos', 'sony', 'blackberry', 'dopod', 'nokia', 'samsung',
				'palmsource', 'xda', 'pieplus', 'meizu', 'midp', 'cldc', 'motorola', 'foma', 'docomo', 'up.browser',
				'up.link', 'blazer', 'helio', 'hosin', 'huawei', 'novarra', 'coolpad', 'webos', 'techfaith', 'palmsource',
				'alcatel', 'amoi', 'ktouch', 'nexian', 'ericsson', 'philips', 'sagem', 'wellcom', 'bunjalloo', 'maui', 'smartphone',
				'iemobile', 'spice', 'bird', 'zte-', 'longcos', 'pantech', 'gionee', 'portalmmm', 'jig browser', 'hiptop',
				'benq', 'haier', '^lct', '320x320', '240x320', '176x220','windows phone',
				'cect', 'compal', 'ctl', 'lg', 'nec', 'tcl', 'alcatel', 'ericsson', 'bird', 'daxian', 'dbtel', 'eastcom',
				'pantech', 'dopod', 'philips', 'haier', 'konka', 'kejian', 'lenovo', 'benq', 'mot', 'soutec', 'nokia', 'sagem', 'sgh',
				'sed', 'capitel', 'panasonic', 'sonyericsson', 'sharp', 'amoi', 'panda', 'zte','sie-','ipod','windowsce','operamini',
				'operamobi','openwave','nexusone','pad', 'gt-p1000');
		if(preg_match('/('.implode('|',$clientkeywords).')/i',strtolower($_SERVER['HTTP_USER_AGENT']))){
			return true;
		}
	}
	if(isset($_SERVER['HTTP_ACCEPT'])){
		if((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) &&(strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false ||(strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))){
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
 * @return mixed
 */
function myEval($code) {
	if(($fp = tmpfile())===false) {
		$fp = fopen(tempnam(CACHE.'tmp', 'ms_'), 'w');
	}
	$file = stream_get_meta_data($fp)['uri'];
	fwrite($fp, '<?PHP'.chr(10).$code);
	$result = include($file);
	fclose($fp);
	unlink($file);
	return $result;
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