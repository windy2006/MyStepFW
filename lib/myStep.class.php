<?php
/********************************************
*                                           *
* Name    : Controller of the Frameword     *
* Author  : Windy2000                       *
* Time    : 2010-12-16                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT HOLD THIS ITEM PLEASE.      *
*                                           *
********************************************/

/**
核心框架类，扩展于myController
	$this->getInstance($calledClass)                 // 取得类实例
	$this->start($setPlugin)                         // 框架执行入口，初始化所有变量
	$this->show(myTemplate $tpl)                     // 通过模板类显示页面
	$this->parseTpl(myTemplate $tpl)                 // 通过模板类输出页面内容
	$this->end()                                     // 框架终止，销毁相关变量
	$this->login($user_id, $user_pwd)                // 登录接口
	$this->logout()                                  // 退出登录接口
	$this->chg_psw($id,$psw_org,$psw_new)            // 变更密码接口
	$this->gzOut($level, $query, $time)              // 压缩输出页面内容
	$this->addCSS($file)                             // 添加页面CSS文件
	$this->CSS($cache)                               // 生成整合CSS文件
	$this->addJS($file)                              // 添加页面脚本文件
	$this->JS($cache)                                // 整合页面脚本文件
	self::setLink()                                  // 处理页面链接
	self::info($msg, $url)                           // 信息提示，需先声明mystep类
	self::captcha($len, $scope)                      // 生成验证码
	self::language($module, $type)                   // JS语言包接口
	self::setting($module, $type)                    // JS设置信息接口
	self::api($para)                                 // 框架API执行接口
	self::module($m)                                 // 框架模块调用接口
	self::upload()                                   // 文件上传接口
	self::download($idx)                             // 文件下载接口
	self::remove_ul($idx)                            // 删除文件下载接口
	self::setURL()                                   // 链接处理
	self::redirect()                                 // 链接跳转
	self::init()                                     // 框架变量初始化
	self::go()                                       // 执行框架
	self::setPara()                                  // 应用模块初始化参数设置
	self::getModule($m)                              // 应用模块调用
	self::vendor()                                   // 调用第三放组件
*/

require_once('function.php');
require_once('myController.class.php');
class myStep extends myController {
	public $setting;
	protected
		$mem_start = 0,
		$time_start = 0,
		$time_css = 0,
		$time_js = 0;

	/**
	 * 构造函数
	 */
	public function __construct() {
		$this->setting = & $GLOBALS['s'];
		date_default_timezone_set($this->setting->gen->timezone);
		set_time_limit(30);
		ini_set('memory_limit', '128M');
		ini_set('default_socket_timeout', 300);
		return;
	}

	/**
	 * 取得类实例
	 * @param string $calledClass
	 * @return mixed
	 */
	public function getInstance($calledClass = '') {
		$instance = call_user_func_array('parent::getInstance', func_get_args());
		if(is_callable($instance, 'regAlias') && is_file(CONFIG.'method_alias/'.$calledClass.'.php')) {
			$alias = include(CONFIG.'method_alias/'.$calledClass.'.php');
			$instance->regAlias($alias);
		}
		$instance->setErrHandler('myStep::info');
		return $instance;
	}

	/**
	 * 框架执行入口，初始化所有变量
	 * @param bool $setPlugin
	 * @param string $dummy
	 */
	public function start($setPlugin = false, $dummy = '') {
		$this->mem_start = memory_get_usage();
		$this->time_start = getMicrotime();
		$alias = include(CONFIG.'class_alias.php');
		self::setAlias($alias);
		
		myException::init(array(
			'log_mode' => 0,
			'log_type' => $this->setting->gen->debug ? E_ALL : (E_ALL & ~E_NOTICE),
			'log_file' => ROOT.'error.log',
			'callback_type' => $this->setting->gen->debug ? E_ALL : (E_ALL & ~(E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE | E_NOTICE)),
			'exit_on_error' => true
		));

		if(defined('PATH') && is_file(PATH.'plugin.php')) {
			$plugin = include(PATH.'plugin.php');
			$mydb = new myDb('simpleDB', 'plugin', PLUGIN);
			foreach($plugin as $p) {
				if($record = $mydb->select('idx='.$p)) {
					if($record[0]['active']=='1') $this->regPlugin(PLUGIN.$p);
				}
			}
		}

		$this->setting->cookie->prefix .= substr(md5(myReq::server('USERNAME').myReq::server('COMPUTERNAME').myReq::server('OS')), 0, 4).'_';
		if($this->setting->session->mode=='sess_file') $this->setting->session->path = CACHE.'/session/'.date('Ymd').'/';

		global $db, $cache, $no_db;
		switch($this->setting->gen->cache_mode) {
			case 'memoryCache':
				$cache_setting = myCache::o2a($this->setting->memcached);
				break;
			case 'myCache_MySQL':
				$cache_setting = myCache::o2a($this->setting->db);
				break;
			default:
				$cache_setting = CACHE.'data';
		}
		$cache = $this->getInstance('myCache', $this->setting->gen->cache_mode, $cache_setting);

		$db = $this->getInstance('myDb', $this->setting->db->type, $this->setting->db->host, $this->setting->db->user, $this->setting->db->password, $this->setting->db->charset);
		if(empty($no_db)) {
			$db->connect($this->setting->db->pconnect, $this->setting->db->name);
			$db->setCache($cache, 600);
		}

		$this->setting->info = new stdClass();
		$this->setting->info->time = myReq::server('REQUEST_TIME');
		$this->setting->info->host = myReq::server('HTTP_HOST');

		$this->setLanguagePack(APP.'myStep/language/', $this->setting->gen->language);
		if(PATH != APP.'myStep/') $this->setLanguagePack(PATH.'language', $this->setting->gen->language);

		myReq::init((array)$this->setting->cookie, (array)$this->setting->session);
		myReq::sessionStart($this->setting->session->mode, true);

		myReq::setCookie('sign_local', 'yes', 600);

		$this->login();
		parent::start($this->setting->gen->charset, $setPlugin);
		$this->page_content['start'] = array();
		$this->page_content['end'] = array();
	}

	/**
	 * 通过模板类显示页面
	 * @param myTemplate $tpl
	 * @param string $dummy
	 */
	public function show(myTemplate $tpl, $dummy = '') {
		$paras = [
			'web_title' => $this->setting->web->title,
			'web_url' => $this->setting->web->url,
			'page_keywords' => $this->setting->web->keyword,
			'page_description' => $this->setting->web->description,
			'charset' => $this->setting->gen->charset,
			'path_root' => ROOT_WEB,
			'path_app' => str_replace(myFile::rootPath(), '/', PATH),
		];
		if(is_array($dummy)) {
			$paras = array_merge($paras, $dummy);
		}
		foreach($paras as $k => $v) {
			$tpl->assign($k, $v);
		}
		if(gettype($this->setting->css)=='string') {
			$this->CSS($this->setting->css);
			$this->setAddedContent('start', '<link rel="stylesheet" media="screen" type="text/css" href="cache/script/'.basename($this->setting->css).'" />');
		}
		if(gettype($this->setting->js)=='string') {
			$this->JS($this->setting->js);
			$this->setAddedContent('start', '<script language="JavaScript" src="cache/script/'.basename($this->setting->js).'"></script>');
		}
		//$this->setFunction('page', 'myStep::setLink');
		parent::show($tpl, $this->setting->web->minify);
	}

	/**
	 * 通过模板类输出页面内容
	 * @param myTemplate $tpl
	 * @return mixed|string
	 */
	public function parseTpl(myTemplate $tpl) {
		$args = func_get_args();
		array_shift($args);
		$paras = [
			'path_root' => ROOT_WEB,
			'path_app' => str_replace(myFile::rootPath(), '/', PATH)
		];
		if(is_array($args[0])) {
			$paras = array_merge($paras, array_shift($args));
		}
		foreach($paras as $k => $v) {
			$tpl->assign($k, $v);
		}
		return call_user_func_array([$tpl, 'display'], $args);
	}

	/**
	 * 设置链接模式
	 * @param $content
	 * @return mixed
	 */
	static public function setLink($content) {
		global $s;
		$seperator = '';
		switch($s->router->mode) {
			case 'path_info':
				$seperator = '/';
				break;
			case 'query_string':
				$seperator = '?';
				break;
			default:
				break;
		}
		if(!empty($seperator)) {
			/*
			if(preg_match('@<head>[\s\S]*(<base\s+href=(\'|")(.+)\2.*>)[\s\S]*</head>@im', $content,$match)) {
				$content = str_replace($match[1], '<base href="index.php'.$seperator.$match[3].'" />', $content);
			} else {
				$content = str_replace('<head>', '<head><base href="index.php'.$seperator.'" />', $content);
			}
			*/
			$content = preg_replace('@<base\s+href@i', '<base xxx', $content);
			$content = preg_replace('@(href|src|action)\s*\=\s*(\'|")(.+?)\2@i', '\1=\2index.php'.$seperator.'\3\2', $content);
			$content = preg_replace('@(\'|")index.php'.preg_quote($seperator).'(#|http|//|static|data\:)@', '\1\2', $content);
			$content = str_replace('<base xxx', '<base href', $content);
		}
		return $content;
	}

	/**
	 * 框架终止，销毁相关变量
	 */
	public function end() {
		parent::end();
		$query_count = $GLOBALS['db']->close();
		$time_exec = getTimeDiff($this->time_start);
		$mem_peak = memory_get_peak_usage();
		$this->gzOut($this->setting->web->gzip_level, $query_count, $time_exec, $mem_peak);
		unset($GLOBALS['db'], $GLOBALS['cache']);
		if(is_callable(array($this, 'shutdown'))) $this->shutdown();
		exit();
	}

	/**
	 * 登录接口
	 * @param string $user_id
	 * @param string $user_pwd
	 * @return bool
	 */
	public function login($user_id='', $user_pwd='') {
		if(!empty($user_id) && !empty($user_pwd)) {
			$user_pwd = md5($user_pwd);
			$ms_user = $user_id.chr(9).$user_pwd;
		} else {
			$ms_user = myReq::cookie('ms_user');
		}
		$result = false;
		if(!empty($ms_user)) {
			list($user_id, $user_pwd) = explode(chr(9), $ms_user);
			if($user_id == $this->setting->gen->s_usr && $user_pwd == $this->setting->gen->s_pwd) {
				myReq::session('ms_user', $user_id);
				$result = true;
			}
		}
		return $result;
	}

	/**
	 * 退出登录接口
	 * @return bool
	 */
	public function logout() {
		myReq::removeCookie('ms_user');
		myReq::sessionEnd();
		return true;
	}

	/**
	 * 变更密码接口
	 * @param string $id
	 * @param $psw_org
	 * @param $psw_new
	 * @return bool
	 */
	public function chg_psw($id='', $psw_org, $psw_new) {
		$result = false;
		$username = myReq::session('username');
		if(!empty($username) && $psw_org!=$psw_new) {
			if($psw_org==$this->setting->gen->s_pwd) {
				$config = new myConfig(CONFIG.'config.php');
				$config->gen->s_pwd = $psw_new;
				$config->save('php');
				$result = true;
			}
		}
		return $result;
	}

	/**
	 * 页面压缩
	 * @param int $level
	 * @param int $query
	 * @param int $time
	 */
	public function gzOut($level = 3, $query = 0, $time = 0, $mem = 0) {
		$encoding = myReq::server('HTTP_ACCEPT_ENCODING');
		if($level<0 || empty($encoding) || headers_sent() || connection_aborted()) {
			if(!empty($content)) ob_end_flush();
		} else {
			if (strpos($encoding, 'x-gzip')!==false) $encoding = 'x-gzip';
			if (strpos($encoding, 'gzip')!==false) $encoding = 'gzip';
			$content  = ob_get_contents();
			if(count(ob_list_handlers())>0) ob_end_clean();
			if(is_bool($this->setting->show)) {
				$rate = ceil(strlen(gzcompress($content,$level)) * 100 / (strlen($content)==0?1:strlen($content))). '%';
				$content = str_ireplace('</body>', '
<div class="text-right text-secondary my-2 pr-3" style="font-size:12px;">
Memory Usage : '.$mem.' &nbsp; | &nbsp;
'.$this->getLanguage('info_compressmode').$rate.' &nbsp; | &nbsp;
'.$this->getLanguage('info_querycount').$query.' &nbsp; | &nbsp;
'.$this->getLanguage('info_exectime').$time.'ms &nbsp; | &nbsp;
'.$this->getLanguage('info_cacheuse').$this->setting->gen->cache_mode.'
</div>
</body>
', $content);
			}

			header('Content-Encoding: '.$encoding);
			echo "\x1f\x8b\x08\x00\x00\x00\x00\x00";
			$Size = strlen($content);
			$Crc = crc32($content);
			$content = gzcompress($content,$level);
			$content = substr($content, 0, strlen($content) - 4);
			echo $content;
			echo pack('V',$Crc);
			echo pack('V',$Size);
		}
	}

	/**
	 * 添加页面CSS文件
	 * @param $file
	 * @return $this|myController
	 */
	public function addCSS($file) {
		if(is_file($file)) {
			$time = filemtime($file);
			if($time>$this->time_css) $this->time_css = $time;
			$this->css[md5_file($file)] = $file;
		}
		return $this;
	}

	/**
	 * 生成整合CSS文件
	 * @param string $cache
	 * @param string $dummy
	 * @return string|void
	 */
	public function CSS($cache='', $dummy='') {
		if(!is_file($cache) || filemtime($cache)<$this->time_css) {
			foreach($this->css as $k => $v) {
				$this->css[$k] = myFile::getLocal($v);
			}
			$code = parent::CSS(false);
			myFile::saveFile($cache, $code);
		}
	}

	/**
	 * 添加页面脚本文件
	 * @param $file
	 * @return $this|myController
	 */
	public function addJS($file) {
		if(is_file($file)) {
			$time = filemtime($file);
			if($time>$this->time_js) $this->time_js = $time;
			$this->js[md5_file($file)] = $file;
		}
		return $this;
	}

	/**整合页面脚本文件
	 * @param string $cache
	 * @param string $dummy
	 * @return string|void
	 */
	public function JS($cache='', $dummy='') {
		if(!is_file($cache) || filemtime($cache)<$this->time_js) {
			foreach($this->js as $k => $v) {
				$this->js[$k] = myFile::getLocal($v);
			}
			$code = parent::JS(false);
			myFile::saveFile($cache, $code);
		}
	}

	/**
	 * 信息提示，需先声明mystep类
	 * @param $msg
	 * @param string $url
	 */
	public static function info($msg, $url = '') {
		global $mystep, $s;
		ob_end_clean();
		if($mystep==null) {
			$mystep = new myController();
			$mystep->setLanguagePack(APP.'myStep/language/', $s->gen->language);
			$paras = [
				'web_title' => $s->web->title,
				'web_url' => $s->web->url,
				'charset' => $s->gen->charset,
				'path_root' => str_replace(myFile::rootPath(),'/',ROOT),
				'lng_page_info' => $mystep->getLanguage('page_info'),
				'lng_page_info_refresh' => $mystep->getLanguage('page_info_refresh'),
			];
		} else {
			$paras = [];
		}
		$t = new myTemplate(array(
			'name' => 'info',
			'path' => APP.'myStep/template/',
			'path_compile' => CACHE.'/template/myStep/'
		), false);
		if(empty($url)) {
			$url = myReq::server('HTTP_REFERER');
			if(is_null($url)) $url = '/';
		}
		$msg = $mystep->getLanguage($msg);
		foreach($paras as $k => $v) {
			$t->assign($k, $v);
		}
		$t->assign('msg', $msg);
		$t->assign('url', self::setURL($url));
		$mystep->show($t);
		$mystep->end();
		exit;
	}

	/**
	 * 生成验证码
	 * @param int $len
	 * @param int $scope
	 */
	public static function captcha($len = 4, $scope = 3) {
		$str = myString::rndKey($len, $scope);
		myReq::session('captcha', $str);
		$img = new myImg();
		$img->captcha($str, STATICS.'fonts/font.ttc', 24);
	}

	/**
	 * JS语言包接口
	 * @param $module
	 * @param string $type
	 */
	public static function language($module, $type='default') {
		header('Content-Type: application/x-javascript');
		$type = preg_replace('#&.+$#', '', $type);
		$cache = ROOT.'cache/language/'.$module.'_'.$type.'.js';
		if(is_file($cache)) {
			$result = myFile::getLocal($cache);
		} else {
			$dir = APP.'myStep/language/';
			if(!is_file($dir.'/'.$type.'.php')) $type='default';
			if(is_file($dir.'/'.$type.'.php')) {
				$language = include($dir.'/'.$type.'.php');
				if($language==1) $language = array();
			}
			if($module!='myStep') {
				$dir = APP.$module.'/language/';
				if(!is_file($dir.'/'.$type.'.php')) $type='default';
				if(is_file($dir.'/'.$type.'.php')) {
					$language = array_merge($language, include($dir.'/'.$type.'.php'));
				}
			}
			$result = 'var language = '.myString::toJson($language).';';
			myFile::saveFile($cache, $result, 'wb');
			unset($language);
		}
		header('Accept-Ranges: bytes');
		header('Accept-Length: '.strlen($result));
		echo $result;
		exit;
	}

	/**
	 * JS设置信息接口
	 * @param $module
	 * @param string $type
	 */
	public static function setting($module) {
		header('Content-Type: application/x-javascript');
		$module = preg_replace('#&.+$#', '', $module);
		$cache = ROOT.'cache/setting/'.$module.'.js';
		if(is_file($cache)) {
			$result = myFile::getLocal($cache);
		} else {
			$setting = new myConfig(CONFIG.'config.php');
			if($module!='myStep') {
				$setting->merge(APP.$module.'/config.php');
			}
			$url_prefix = '';
			switch($setting->router->mode) {
				case 'path_info':
					$url_prefix = 'index.php/';
					break;
				case 'query_string':
					$url_prefix  = 'index.php?';
					break;
				default:
					break;
			}
			$setting = myConfig::o2a($setting);
			$setting_js = array(
				'language' => $setting['setting']['gen']['language'],
				'charset' => $setting['setting']['gen']['charset'],
				'timezone' => $setting['setting']['gen']['timezone'],
				'router' => $setting['setting']['router']['mode'],
				'debug' => $setting['setting']['gen']['debug'],
				'title' => $setting['setting']['web']['title'],
				'keyword' => $setting['setting']['web']['keyword'],
				'description' => $setting['setting']['web']['description'],
				'update' => $setting['setting']['web']['update'],
				'path_layer' => count(explode('/',trim(ROOT_WEB, '/'))),
				'path_root' => str_replace(myFile::rootPath(),'/',ROOT),
				'path_app' => str_replace(myFile::rootPath(),'/',APP.$module),
				'url_prefix' => $url_prefix,
			);
			if(isset($setting['setting']['js'])) $setting_js = array_merge($setting_js, $setting['setting']['js']);

			$result = 'var setting = '.myString::toJson($setting_js).';';
			myFile::saveFile($cache, $result, 'wb');
			unset($setting, $setting_js);
		}
		header('Accept-Ranges: bytes');
		header('Accept-Length: '.strlen($result));
		echo $result;
		exit;
	}

	/**
	 * 框架API执行接口
	 * @param $para
	 */
	public static function api($para) {
		global $s, $info_app;
		$para = preg_replace('#&.+$#', '', $para);
		$para = explode('/', trim($para, '/'));
		$module = $info_app['app'];
		include(CONFIG.'route.php');
		$result = '{"err":"Module is Missing!"}';
		if(isset($api_list)) {
			if(isset($api_list[$module])){
				if(strpos($para[0],'plugin_')!==0) {
					$name = array_shift($para);
				} else {
					$plugin = array_shift($para);
					$name = array_shift($para);
				}
			} else {
				$name = $module;
				$module = 'myStep';
			}
			if(isset($plugin) && isset($api_list[$plugin][$name])) {
				$s->merge(APP.$module.'/config.php');
				$method = $api_list[$plugin][$name];
				if(is_file(APP.$module.'/lib.php')) require_once(APP.$module.'/lib.php');
				$type = end($para);
				$para = array_merge(myReq::getValue(myReq::check('get')?'get':'post', '[ALL]'), $para);
				if(is_callable($method)) {
					$api = new myApi();
					$api->regMethod($name, $method);
					$result = call_user_func([$api, 'run'], $name, $para, $type, $s->gen->charset);
				}
			} elseif(isset($api_list[$module][$name])) {
				$s->merge(APP.$module.'/config.php');
				$method = $api_list[$module][$name];
				if(is_file(APP.$module.'/lib.php')) require_once(APP.$module.'/lib.php');
				$type = end($para);
				$para = array_merge(myReq::getValue(count($_GET)>0?'get':'post', '[ALL]'), $para);
				if(is_callable($method)) {
					$api = new myApi();
					$api->regMethod($name, $method);
					$result = call_user_func([$api, 'run'], $name, $para, $type, $s->gen->charset);
				}
			}
		}
		echo $result;
		exit;
	}

	/**
	 * 框架模块调用接口
	 * @param $m
	 * @param string $dummy
	 */
	public static function module($m, $dummy = '') {
		$path = explode('/', trim($m, '/'));
		$module = array_shift($path);
		if(isset(self::$modules[$module])) {
			global $mystep, $db, $cache;
			require(self::$modules[$module]);
			exit();
		} else {
			self::redirect('/');
		}
	}

	/**
	 * 文件上传
	 */
	public static function upload() {
		if(r::s('sign_upload')==='') {
			echo '{"error":999,"message":"Upload Denied!"}';
		} else {
			global $s;
			$path = FILE.date($s->upload->path_mode);
			set_time_limit(0);

			$upload = new myUploader($path, true, $s->upload->ban_ext);
			$upload->do(false);

			if($upload->result[0]['error'] == 0) {
				$upload->result[0]['name'] = myString::sc(urldecode($upload->result[0]['name']), $s->gen->charset);
				$ext = strtolower(strrchr($upload->result[0]['name'], "."));
				$name = str_replace($ext, "", $upload->result[0]['name']);
				$upload->result[0]['name'] = myString::substr($name, 0, 80).$ext;
				$upload->result[0]['new_name'] = str_replace(".upload", "", $upload->result[0]['new_name']);
			}

			myFile::saveFile(FILE.date($s->upload->path_mode).'/log.txt', $upload->result[0]['new_name'].'::'.$upload->result[0]['name'].'::'.chr(10), 'a');
			echo myString::toJson($upload->result[0], $s->gen->charset);
		}
		exit;
	}

	/**
	 * 文件下载
	 */
	public static function download($idx) {
		global $s;
		if($s->upload->free_dl || myReq::cookie('sign_local')!='') {
			$idx = explode('.', $idx);
			$path = FILE.date($s->upload->path_mode, $idx[0]);
			set_time_limit(0);
			$list = file(FILE.date($s->upload->path_mode).'/log.txt');
			for($i=0,$m=count($list);$i<$m;$i++) {
				if(strpos($list[$i],implode('.', $idx))===0) {
					$list[$i] = explode('::', $list[$i]);
					$file = $path.'/'.$list[$i][0];
					$name = $list[$i][1];
					if(is_file($file.'.upload')) $file = $file.'.upload';
					if(is_file($file)) {
						myController::file($file, $name);
						exit;
					}
				}
			}
		}
		self::header('404');
	}

	/**
	 * 删除上传文件
	 */
	public static function remove_ul($idx) {
		global $s;
		$result = '{"statusCode": 0}';
		if($s->upload->free_dl || myReq::cookie('sign_local')!='') {
			$idx = explode('.', $idx);
			$path = FILE.date($s->upload->path_mode, $idx[0]);
			$log = FILE.date($s->upload->path_mode).'/log.txt';
			$list = file($log);
			for($i=0,$m=count($list);$i<$m;$i++) {
				if(strpos($list[$i],implode('.', $idx))===0) {
					$list[$i] = explode('::', $list[$i]);
					$file = $path.'/'.$list[$i][0];
					if(file_exists($file.'.upload')) $file = $file.'.upload';
					if(myFile::del($file)) $result = '{"statusCode": 1}';
					break;
				}
			}
			$content = myFile::getLocal($log);
			$content = str_replace(implode('::', $list[$i]), '', $content);
			myFile::saveFile($log, $content);
		}
		echo $result;
		exit;
	}

	/**
	 * 链接处理
	 * @param $url
	 * @return string
	 */
	public static function setURL($url) {
		global $s;
		$url = preg_replace('@^'.preg_quote(ROOT_WEB).'@', '/', $url);
		if(strpos($url, 'index.php')===false && strpos($url, 'http://')!==0) {
			switch($s->router->mode) {
				case 'path_info':
					$url = 'index.php'.$url;
					break;
				case 'query_string':
					$url = 'index.php?'.$url;
					break;
				default:
					break;
			}
		}
		$url = preg_replace('#/+#', '/', $url);
		$url = str_replace(':/', '://', $url);
		return $url;
	}

	/**
	 * 链接跳转
	 * @param string $url
	 * @param string $code
	 */
	public static function redirect($url = '', $code = '302') {
		if(empty($url)) {
			$url = myReq::server('HTTP_REFERER');
			if (is_null($url)) $url = '/';
			$code = '302';
		} else {
			$url = self::setURL($url);
		}
		header('location: ' . $url, true, $code);
		exit;
	}

	/**
	 * 框架变量初始化
	 */
	public static function init() {
		$class = is_file(CONFIG.'class.php') ? include((CONFIG.'class.php')) : array();
		if(empty($class) || !is_dir($class[0]['path'])) {
			$old_root = empty($class) ? '' : preg_replace('#lib/$#', '', $class[0]['path']);
			$class[0] = array(
				'path' => ROOT . 'lib/',
				'ext' => '.php,.class.php',
				'idx' => array(
						'jsMin' => 'myMinify.class.php',
						'cssMin' => 'myMinify.class.php',
						'JavaScriptPacker' => 'myMinify.class.php',
						'interface_plugin' => '../plugin/interface_plugin.class.php'
					),
			);
			for($i=1,$m=count($class);$i<$m;$i++) {
				$class[$i]['path'] = preg_replace('#^'.$old_root.'#', ROOT, $class[$i]['path']);
			}
			@unlink(CONFIG.'class.php');
			file_put_contents(CONFIG.'class.php', '<?php'.chr(10).'return '.var_export($class, true).';');

			$setting_class = include(CONFIG.'class.php');
			self::regClass($setting_class);

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
			self::regClass($setting_class);
		}
		define('ROOT_WEB', str_replace(myFile::rootPath(),'/',ROOT));
		myException::init(array(
			'log_mode' => 0,
			'log_type' => E_ALL ^ E_NOTICE,
			'log_file' => ROOT.'/error.log',
			'callback_type' => E_ALL & ~(E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE | E_NOTICE),
			'exit_on_error' =>  false
		));
		
		if(is_file(CONFIG.'config.php')) {
			self::go();
		} else {
			$qstr = trim(myReq::svr('REQUEST_URI'), '/');
			$the_file = ROOT.preg_replace('#(&|\?).+$#', '', $qstr);
			if(strpos($qstr,'static')===0) {
				myController::file($the_file);
			} else {
				require(APP.'myStep/module/config.php');
			}
		}
		return;
	}

	/**
	 * 执行框架
	 */
	public static function go() {
		global $s, $router;
		$s = new myConfig(CONFIG.'config.php');
		$router = new myRouter((array)$s->router);
		extract($router->route);

		$the_file = ROOT.preg_replace('#&.+$#', '', $p);
		$ext = strtolower(pathinfo($the_file, PATHINFO_EXTENSION));
		$ext_list = explode(',', $s->gen->static);
		if(strpos(trim($the_file,'/'),'static')===0 || (is_file($the_file) && in_array($ext, $ext_list))) myController::file($the_file);

		if(($s->cookie->domain = strstr(myReq::server('HTTP_HOST'), ':', true))===false) {
			$s->cookie->domain = myReq::server('HTTP_HOST');
		}
		$s->cookie->path = dirname(myReq::server('SCRIPT_NAME'));
		$s->web->url = 'http://'.myReq::server('HTTP_HOST');

		global $info_app, $s, $router, $tpl_setting, $tpl_cache, $mystep, $db, $cache;
		$router->setRules(CONFIG.'route.php');
		if(!$router->check()) {
			$info_app = $router->parse();
			$info_app['route'] = $router->route['p'];
			$info_app['app'] = trim($info_app['app'],'.');
			if(empty($info_app['app']) || !is_dir(APP.$info_app['app'])) {
				myStep::info('app_missing', ROOT_WEB);
			}
			if(is_file(APP.$info_app['app'].'/config.php')) {
				$s->merge(APP.$info_app['app'].'/config.php');
			}
			define('PATH', APP.$info_app['app'].'/');
			if(is_file(PATH.'/lib.php')) require_once(PATH.'/lib.php');
			myStep::setPara();
			require(APP.$info_app['app'].'/index.php');
		}
	}

	/**
	 * 应用模块初始化参数设置
	 */
	public static function setPara() {
		global $mystep, $info_app, $tpl_setting, $tpl_cache, $setPlugin;
		if($mystep!=null) return;
		if(!defined('PATH')) define('PATH', APP.$info_app['app'].'/');
		if(is_file(PATH.$info_app['app'].'.class.php')) {
			require_once(PATH.$info_app['app'].'.class.php');
			$mystep = new $info_app['app']();
		} else {
			$class = __CLASS__;
			$mystep = new $class();
		}

		if(is_callable(array($mystep, 'preload'))) $mystep->preload();
		if(is_null($setPlugin)) $setPlugin = true;
		if(is_file(PATH.'config.php')) $mystep->setting->merge(PATH.'config.php');
		if(!$mystep->setting->gen->debug && !empty($mystep->setting->gen->close)) self::redirect($mystep->setting->gen->close);
		$mystep->start($setPlugin);

		$mystep->setting->web->css = explode(',', $mystep->setting->web->css);
		foreach($mystep->setting->web->css as $k) {
			$mystep->addCSS(STATICS.'css/'.$k.'.css');
		}
		$mystep->addCSS(STATICS.'css/global.css');
		$mystep->addCSS(PATH.'asset/style.css');
		$mystep->addCSS(PATH.'asset/'.$mystep->setting->template->style.'/style.css');
		$mystep->setting->css = CACHE.'script/'.$info_app['app'].'.css';

		$mystep->setting->web->js = explode(',', $mystep->setting->web->js);
		foreach($mystep->setting->web->js as $k) {
			$mystep->addJS(STATICS.'js/'.$k.'.js');
		}
		$mystep->addJS(STATICS.'js/global.js');
		$mystep->addJS(PATH.'asset/function.js');
		$mystep->addJS(PATH.'asset'.$mystep->setting->template->style.'/function.js');
		$mystep->setting->js = CACHE.'script/'.$info_app['app'].'.js';

		$tpl_setting = array(
			'name' => $mystep->setting->template->name,
			'path' => PATH.$mystep->setting->template->path,
			'style' => $mystep->setting->template->style,
			'path_compile' => CACHE.'template/'.$info_app['app'].'/'
		);

		$tpl_cache = $mystep->setting->gen->debug ? false : array(
			'path' => CACHE.'app/'.$info_app['app'].'/html/',
			'expire' => 60*60*24
		);
	}

	/**
	 * 应用模块调用
	 */
	public static function getModule($m) {
		global $mystep, $tpl_setting, $tpl_cache, $info_app, $s, $db, $cache, $router;
		$idx = preg_replace('#(/|&|\?).*$#', '', $m);
		$files = [
			PATH.'module/'.$tpl_setting['style'].'/'.$idx.'.php',
			PATH.'module/'.$tpl_setting['style'].'/'.($info_app['path'][0]??'').'.php',
			PATH.'module/'.$idx.'.php',
			PATH.'module/'.($info_app['path'][0]??'').'.php',
			PATH.'module/'.$tpl_setting['style'].'/index.php',
			PATH.'module/index.php',
			''
		];
		foreach($files as $f) {
			if(is_file($f)) break;
			if(empty($f))  myStep::info('module_missing');
		}
		$tpl = new myTemplate($tpl_setting, $tpl_cache);
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
	public static function vendor() {
		$args = func_get_args();
		$name = array_shift($args);
		$file = VENDOR.$name.'/'.$name.'.php';
		if(!is_file($file)) $file = VENDOR.$name.'/'.$name.'.class.php';
		if(!is_file($file)) {
			global $mystep;
			myStep::info('module_missing');
		}
		require_once($file);
		$instance = new $name();
		if(count($args) && is_callable([$instance, '__construct'])) {
			call_user_func_array([$instance, '__construct'], $args);
		}
		return $instance;
	}
}
