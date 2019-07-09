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
self::getInstance($calledClass)                 // 取得类实例
self::start($setPlugin)                         // 框架执行入口，初始化所有变量
self::show(myTemplate $tpl)                     // 通过模板类显示页面
self::display(myTemplate $tpl)                  // 通过模板类输出页面内容
self::end()                                     // 框架终止，销毁相关变量
self::login($user_id, $user_pwd)                // 登录接口
self::logout()                                  // 退出登录接口
self::chg_psw($id,$psw_org,$psw_new)            // 变更密码接口
self::info($msg, $url)                          // 信息提示，需先声明mystep类
self::captcha($len, $scope)                     // 生成验证码
self::language($module, $type)                  // JS语言包接口
self::setting($module, $type)                   // JS设置信息接口
self::getApi($para)                             // 框架API执行接口
self::module($m)                                // 框架模块调用接口
self::addCSS($file)                             // 添加页面CSS文件
self::CSS($cache)                               // 生成整合CSS文件
self::addJS($file)                              // 添加页面脚本文件
self::JS($cache)                                // 整合页面脚本文件
self::upload()                                  // 文件上传接口
self::download($idx)                            // 文件下载接口
*/
class myStep extends myController {
	public $setting;
	protected
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
		$this->time_start = getMicrotime();
		$alias = include(CONFIG.'class_alias.php');
		setAlias($alias);
		
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

		global $db, $cache;
		$db = $this->getInstance('myDb', $this->setting->db->type, $this->setting->db->host, $this->setting->db->user, $this->setting->db->password, $this->setting->db->charset);
		$db->connect($this->setting->db->pconnect, $this->setting->db->name);

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
		$db->setCache($cache, 600);

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
		parent::show($tpl, $this->setting->web->minify);
	}

    /**
     * 通过模板类输出页面内容
     * @param myTemplate $tpl
     * @return mixed|string
     */
    public function display(myTemplate $tpl) {
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
	 * 框架终止，销毁相关变量
	 */
	public function end() {
		parent::end();
		$query_count = $GLOBALS['db']->close();
		$time_exec = getTimeDiff($this->time_start);
		$this->gzOut($this->setting->web->gzip_level, $query_count, $time_exec);
		unset($GLOBALS['db'],$GLOBALS['cache']);
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
	public function gzOut($level = 3, $query = 0, $time = 0) {
		$encoding = myReq::server('HTTP_ACCEPT_ENCODING');
		if($level<1 || empty($encoding) || headers_sent() || connection_aborted()) {
			if(!empty($content)) ob_end_flush();
		} else {
			if (strpos($encoding, 'x-gzip')!==false) $encoding = 'x-gzip';
			if (strpos($encoding, 'gzip')!==false) $encoding = 'gzip';
			$content  = ob_get_contents();
			if(count(ob_list_handlers())>0) ob_end_clean();

			if(is_bool($this->setting->show)) {
				$rate = ceil(strlen(gzcompress($content,$level)) * 100 / (strlen($content)==0?1:strlen($content))). '%';
				$content = str_replace('</body>', '
<div class="text-right text-secondary my-2 pr-3" style="font-size:12px;">
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
	 * 信息提示，需先声明mystep类
	 * @param $msg
	 * @param string $url
	 */
	public static function info($msg, $url = '') {
		global $mystep;
		ob_end_clean();
		if($mystep==null) $mystep = new myController();
		$t = new myTemplate(array(
			'name' => 'info',
			'path' => APP.'myStep/template/',
			'path_compile' => CACHE.'/template/myStep/'
		), false);
		if(empty($url)) {
			$url = myReq::server('HTTP_REFERER');
			if(is_null($url)) $url = '/';
		}
		$t->assign('msg', $msg);
		$t->assign('url', $url);
		$mystep->show($t);
		$mystep->end();
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
			$setting = myConfig::o2a($setting);
			$setting_js = array(
				'language' => $setting['setting']['gen']['language'],
				'charset' => $setting['setting']['gen']['charset'],
				'timezone' => $setting['setting']['gen']['timezone'],
				'debug' => $setting['setting']['gen']['debug'],
				'title' => $setting['setting']['web']['title'],
				'keyword' => $setting['setting']['web']['keyword'],
				'description' => $setting['setting']['web']['description'],
				'update' => $setting['setting']['web']['update'],
                'path_layer' => count(explode('/',trim(ROOT_WEB, '/'))),
				'path_root' => str_replace(myFile::rootPath(),'/',ROOT),
				'path_app' => str_replace(myFile::rootPath(),'/',APP.$module),
				'js' => $setting['setting']['js'] ?? array(),
			);

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
	public static function getApi($para) {
		global $s;
		$para = preg_replace('#&.+$#', '', $para);
		$para = explode('/', trim($para, '/'));
		$module = array_shift($para);
		include(CONFIG.'route.php');
		$result = '{"err":"Module is Missing!"}';
		if(isset($api_list)) {
			if(isset($api_list[$module])){
				$name = array_shift($para);
			} else {
				$name = $module;
				$module = 'myStep';
			}
			if(isset($api_list[$module][$name])) {
				$s->merge(APP.$module.'/config.php');
				$method = $api_list[$module][$name];
				if(isset($preload_list[$module]) && is_file($preload_list[$module])) require_once($preload_list[$module]);
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
			foreach($path as $k) {
				if(strpos($k, '=')) break;
				global $$k;
			}
			include(self::$modules[$module]);
		} else {
			self::$goto_url = '/';
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
					if(file_exists($file.'.upload')) $file = $file.'.upload';
					if(file_exists($file)) {
						myController::file($file, $name);
						exit;
					}
				}
			}
		}
		header('HTTP/1.1 404 Not Found');
		exit;
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
}
