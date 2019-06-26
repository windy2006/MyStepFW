<?php
/********************************************
 *                                           *
 * Name    : Controller for the application  *
 * Modifier: Windy2000                       *
 * Time    : 2018-12-28                      *
 * Email   : windy2006@gmail.com             *
 * HomePage: www.mysteps.cn                  *
 * Notice  : U Can Use & Modify it freely,   *
 *           BUT PLEASE HOLD THIS ITEM.      *
 *                                           *
 ********************************************/

/**
 * 控制器基础类，含单实例控制
 * $this->setSingleton($singleton)                     // 设置单实例模式
 * $this->getInstance($calledClass)                    // 取得类实例
 * $this->setAddedContent($position, $content)         // 设置页面附加内容
 * $this->pushAddedContent(myTemplate $tpl)            // 插入页面附加内容
 * $this->setLanguage($language)                       // 设置语言
 * $this->setLanguagePack($dir, $lng)                  // 设置语言包
 * $this->getLanguage($idx)                            // 获取语言项目
 * $this->regApi($name, $method)                       // 设置应用接口（用于数据传输）
 * $this->api($name)                                   // 调用应用接口
 * $this->regModule($module, $page)                    // 设置模块脚本（用于功能页面）
 * $this->module($module, $global_vars)                // 调用模块
 * $this->regTag($tag_name, $tag_func)                 // 添加模版标签解析方法
 * $this->regUrl($mode, $func)                         // 添加URL生成规则
 * $this->url($mode)                                   // 生成URL
 * $this->regLog($login, $logout, $chg_psw)            // 设置用户登录接口
 * $this->login($user_name, $user_pwd)                 // 用户登录
 * $this->logout()                                     // 用户退出
 * $this->chg_psw($id, $psw_old, $psw_new)             // 变更密码
 * $this->addCSS($code)                                // 添加样式表（代码或文件）
 * $this->removeCSS($idx)                              // 去除样式表
 * $this->clearCSS()                                   // 清空现有样式表
 * $this->CSS($show, $expires)                         // 获取样式表
 * $this->addJS($code)                                 // 添加JS脚本（代码或文件）
 * $this->removeJS($idx)                               // 去除JS脚本
 * $this->clearJS()                                    // 清空现有JS脚本
 * $this->JS($show, $expires)                          // 获取JS脚本
 * $this->etag($etag)                                  // 通过过期标签显示内容
 * $this->file($file)                                  // 显示文件
 * $this->guid($para)                                  // 生成唯一ID
 * $this->regPlugin($plugin)                           // 注册插件
 * $this->plugin()                                     // 执行现有插件
 * $this->setFunction($func, $position)                // 添加钩子程序
 * $this->run($position, $desc)                        // 执行钩子代码
 * $this->start($charset)                              // 页面脚本执行初始化
 * $this->show(myTemplate $tpl)                        // 显示页面
 * $this->end()                                        // 页面脚本执行结束
 * $this->redirect($url, $code)                        // 链接跳转
 */
class myController extends myBase {
	public static
		$modules = array(),
		$goto_url = '';

	protected
		$singleton = false,
		$functions = array(),
		$func_tag = array(),
		$func_api = array(),
		$func_log = array(),
		$plugins = array(),
		$language = array(),
		$page_content = array(),
		$url = array(),
		$css = array(),
		$js = array();

	/**
	 * 单实例克隆控制
	 * @return bool
	 */
	public function __clone() {
		if ($this->singleton) {
			$this->error('Clone is not allowed.', E_USER_ERROR);
			return false;
		} else {
			foreach ($this as $key => $val) {
				if (is_array($val)) {
					$this->$key = unserialize(serialize($val));
				} elseif (is_object($val)) {
					$this->$key = clone($this->$key);
				}
			}
		}
		return true;
	}

	/**
	 * 单实例模式设置
	 * @param bool $singleton
	 */
	public function setSingleton($singleton = false)
	{
		$this->singleton = $singleton;
	}

	/**
	 * 依照单实例模式引用其他实例
	 * @param string $calledClass
	 * @return mixed
	 */
	public function getInstance($calledClass = '')
	{
		if (empty($calledClass)) $calledClass = get_class($this);
		$argList = func_get_args();
		array_shift($argList);
		if ($this->singleton) {
			static $instanceList = array();
			if (!isset($instanceList[$calledClass])) {
				$instanceList[$calledClass] = new $calledClass();
				if (count($argList) > 0) {
					if (is_callable(array($calledClass, 'init'))) {
						call_user_func_array(array($instanceList[$calledClass], 'init'), $argList);
					} else {
						call_user_func_array(array($instanceList[$calledClass], '__construct'), $argList);
					}
				}
			} else {
				if (is_callable(array($calledClass, 'init')) && count($argList) > 0) {
					call_user_func_array(array($instanceList[$calledClass], 'init'), $argList);
				}
			}
			return $instanceList[$calledClass];
		} else {
			$instance = new $calledClass();
			if (count($argList) > 0) {
				if (method_exists($calledClass, 'init')) {
					call_user_func_array(array($instance, 'init'), $argList);
				} else {
					call_user_func_array(array($instance, '__construct'), $argList);
				}
			}
			return $instance;
		}
	}

	/**
	 * 设置指定页面内容
	 * @param $position
	 * @param $content
	 * @return $this
	 */
	public function setAddedContent($position, $content)
	{
		$this->page_content[$position][] = $content;
		return $this;
	}

	/**
	 * 将内容添加到指定位置
	 * @param myTemplate $tpl
	 */
	public function pushAddedContent(myTemplate $tpl)
	{
		$argList = func_get_args();
		$m = count($argList);
		if ($m == 1) {
			$join = function ($content) {
				return join(chr(10), $content);
			};
			$tpl->assign('page', array_map($join, $this->page_content));
		} else {
			for ($i = 1; $i < $m; $i++) {
				if (isset($this->page_content[$argList[$i]])) {
					$tpl->assign('page_' . $argList[$i], join(chr(10), $this->page_content[$argList[$i]]));
				}
			}
		}
	}

	/**
	 * 设置页面语言
	 * @param $language
	 * @param $charset
	 * @return $this
	 */
	public function setLanguage($language, $charset = 'utf-8')
	{
		if (is_array($language)) {
			$this->language = array_merge($this->language, myString::setCharset($language, $charset));
		}
		return $this;
	}

	/**
	 * 获取语言包
	 * @param $dir
	 * @param string $lng
	 * @param string $charset
	 * @return $this
	 */
	public function setLanguagePack($dir, $lng = 'default', $charset = 'utf-8')
	{
		$dir = myFile::realPath($dir);
		if (is_file($dir . '/' . $lng . '.php')) {
			$language = include($dir . '/' . $lng . '.php');
			if ($language !== 1) $this->setLanguage($language, $charset);
			unset($language);
		}
		return $this;
	}

	/**
	 * 获取语言项目
	 * @param $idx
	 * @return mixed|string
	 */
	public function getLanguage($idx)
	{
		return isset($this->language[$idx]) ? $this->language[$idx] : $idx;
	}

	/**
	 * 注册 API 处理函数
	 * @param $name
	 * @param string $method
	 * @return $this
	 */
	public function regApi($name, $method = '')
	{
		if (empty($method) && is_callable($name)) {
			$this->func_api[$name] = $name;
		} elseif (is_callable($method)) {
			$this->func_api[$name] = $method;
		}
		return $this;
	}

	/**
	 * 获取 API 数据
	 * @param $name
	 * @param array $para
	 * @param string $return
	 * @return mixed|string
	 */
	public function api($name, $para = array(), $return = 'json')
	{
		$result = '';
		if (isset($this->func_api[$name])) {
			$api = new myApi();
			$api->regMethod($name, $this->func_api[$name]);
			$result = call_user_func([$api, 'run'], $name, $para, $return);
		}
		return $result;
	}

	/**
	 * 注册功能模块
	 * @param $module
	 * @param $page
	 * @return $this
	 */
	public function regModule($module, $page)
	{
		$page = myFile::realPath($page);
		if (is_file($page)) {
			self::$modules[$module] = $page;
		}
		return $this;
	}

	/**
	 * 调用已注册模块
	 * @param $module
	 * @param string $global_vars
	 */
	public static function module($module, $global_vars = '')
	{
		if (isset(self::$modules[$module])) {
			if (!empty($global_vars)) {
				if (is_string($global_vars)) $global_vars = explode(',', $global_vars);
				foreach ($global_vars as $k) {
					$k = str_replace('$', '', trim($k));
					global $$k;
				}
			}
			include(self::$modules[$module]);
		} else {
			self::$goto_url = '/';
		}
	}

	/**
	 * 注册模版标签处理函数
	 * @param $tag_name
	 * @param $tag_func
	 * @return $this
	 */
	public function regTag($tag_name, $tag_func)
	{
		$this->func_tag[$tag_name] = $tag_func;
		return $this;
	}

	/**
	 * 注册 URL 生成函数
	 * @param $mode
	 * @param $func
	 * @return $this
	 */
	public function regUrl($mode, $func)
	{
		if (is_callable($func)) {
			$this->url[$mode] = $func;
		}
		return $this;
	}

	/**
	 * 获取 URL
	 * @param $mode
	 * @return mixed|string
	 */
	public function url($mode)
	{
		$url = '#';
		if (isset($this->url[$mode])) {
			$argList = func_get_args();
			array_shift($argList);
			$url = call_user_func_array($this->url[$mode], $argList);
		}
		return $url;
	}

	/**
	 * 注册登录接口
	 * @param $login
	 * @param string $logout
	 * @param string $chg_psw
	 * @return $this
	 */
	public function regLog($login, $logout = '', $chg_psw = '')
	{
		$this->func_log = array();
		if (is_callable($login)) $this->func_log['login'] = $login;
		if (is_callable($logout)) $this->func_log['logout'] = $logout;
		if (is_callable($chg_psw)) $this->func_log['chg_psw'] = $chg_psw;
		return $this;
	}

	/**
	 * 用户登录
	 * @param $user_name
	 * @param $user_pwd
	 * @return bool
	 */
	public function login($user_name, $user_pwd)
	{
		$result = false;
		if (isset($this->func_log['login'])) {
			$result = call_user_func($this->func_log['login'], $user_name, $user_pwd);
		}
		return $result;
	}

	/**
	 * 用户退出
	 * @return bool
	 */
	public function logout()
	{
		$result = false;
		if (isset($this->func_log['logout'])) {
			$result = call_user_func($this->func_log['logout']);
		}
		return $result;
	}

	/**
	 * 变更密码
	 * @param $id
	 * @param $psw_old
	 * @param $psw_new
	 * @return bool
	 */
	public function chg_psw($id, $psw_old, $psw_new)
	{
		$result = false;
		if (isset($this->func_log['chg_psw'])) {
			$result = call_user_func($this->func_log['chg_psw'], $id, $psw_old, $psw_new);
		}
		return $result;
	}

	/**
	 * 添加 CSS
	 * @param $code
	 * @return $this
	 */
	public function addCSS($code)
	{
		if (is_file($code)) {
			$md5 = md5_file($code);
			$code = myFile::getLocal($code);
		} else {
			$md5 = md5($code);
		}
		$this->css[$md5] = $code;
		return $this;
	}

	/**
	 * 移除 CSS
	 * @param $idx
	 * @return $this
	 */
	public function removeCSS($idx)
	{
		if (is_file($idx)) {
			$idx = md5_file($idx);
		}
		unset($this->css[$idx]);
		return $this;
	}

	/**
	 * 清空已添加样式
	 * @return $this
	 */
	public function clearCSS()
	{
		$this->css = array();
		return $this;
	}

	/**
	 * 获取合成样式表
	 * @param bool $show
	 * @param int $expires
	 * @return string
	 */
	public function CSS($show = true, $expires = 604800)
	{
		$css = implode(chr(10), $this->css);
		if (!$show) return cssMin::minify($css);
		$md5 = md5($css);
		$this->etag($md5 . '.css');
		$minify = new myMinify('css', dirname(dirname(__FILE__)) . '/cache/' . $md5 . '.css');
		if (!$minify->check($expires)) {
			$minify->add($css);
		}
		$minify->show();
		return '';
	}

	/**
	 * 添加 JS
	 * @param $code
	 * @return $this
	 */
	public function addJS($code)
	{
		if (is_file($code)) {
			$md5 = md5_file($code);
			$code = myFile::getLocal($code);
		} else {
			$md5 = md5($code);
		}
		$this->js[$md5] = $code;
		return $this;
	}

	/**
	 * 移除 JS
	 * @param $idx
	 * @return $this
	 */
	public function removeJS($idx)
	{
		if (is_file($idx)) {
			$idx = md5_file($idx);
		}
		unset($this->js[$idx]);
		return $this;
	}

	/**
	 * 清空已添加 JS
	 * @return $this
	 */
	public function clearJS()
	{
		$this->js = array();
		return $this;
	}

	/**
	 * 获取合成脚本
	 * @param bool $show
	 * @param int $expires
	 * @return string
	 */
	public function JS($show = true, $expires = 604800)
	{
		$js = implode(chr(10), $this->js);
		if (!$show) return jsMin::minify($js);
		$md5 = md5($js);
		$this->etag($md5 . '.js');
		$minify = new myMinify('js', dirname(dirname(__FILE__)) . '/cache/' . $md5 . '.js');
		if (!$minify->check($expires)) {
			$minify->add($js);
		}
		$minify->show(true);
		return '';
	}

	/**
	 * 检测过期标签
	 * @param string $etag
	 * @param int $expires
	 */
	public static function etag($etag = '', $expires = 604800)
	{
		if (empty($etag)) return;
		header("Pragma: public");
		header("Cache-Control: private, max-age=" . $expires);
		if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag) {
			header('Etag:' . $etag, true, 304);
			exit;
		} else {
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
			header("Expires: " . gmdate('D, d M Y H:i:s', time() + $expires) . " GMT");
			header('Etag:' . $etag);
		}
	}

	/**
	 * 显示文件
	 * @param $file
	 * @param string $name
	 */
	public static function file($file, $name = '')
	{
		if (is_file($file)) {
			$name = $name ?? basename($file);
			self::etag(md5(filemtime($file)));
			$content = myFile::getLocal($file);
			$type = myFile::getMime($file);
			header("Date: " . date("D, j M Y H:i:s", strtotime("now")) . " GMT");
			header("Expires: " . date("D, j M Y H:i:s", strtotime("now + 10 years")) . " GMT");
			header('Content-Type: ' . $type);
			if ($type !== 'text/html') {
				header("Accept-Ranges: bytes");
				header("Accept-Length: " . strlen($content));
				header("Content-Disposition: attachment; filename=" . $name);
			}
			echo $content;
			exit;
		}
	}

	/**
	 * 生成GUID
	 * @param string $para
	 * @return string
	 */
	public static function guid($para = '')
	{
		$guid = '';
		$uid = uniqid('', true);
		$data = $para;
		$data .= $_SERVER['REQUEST_TIME'];
		$data .= $_SERVER['HTTP_USER_AGENT'];
		$data .= $_SERVER['LOCAL_ADDR'];
		$data .= $_SERVER['LOCAL_PORT'];
		$data .= $_SERVER['REMOTE_ADDR'];
		$data .= $_SERVER['REMOTE_PORT'];
		$hash = strtoupper(hash('ripemd128', $uid . $guid . md5($data)));
		$guid = '' .
			substr($hash, 0, 8) .
			'-' .
			substr($hash, 8, 4) .
			'-' .
			substr($hash, 12, 4) .
			'-' .
			substr($hash, 16, 4) .
			'-' .
			substr($hash, 20, 12) .
			'';
		return $guid;
	}

	/**
	 * 注册插件
	 * @param $plugin
	 * @return $this
	 */
	public function regPlugin($plugin, &$info = array())
	{
		$plugin = myFile::realPath($plugin);
		if (is_dir($plugin) && is_file($plugin . '/index.php')) {
			$this->plugins[] = $plugin;
			if (is_file($plugin . '/info.php')) $info = include($plugin . '/info.php');
		}
		return $this;
	}

	/**
	 * 设置插件
	 */
	public function plugin()
	{
		for ($i = 0, $m = count($this->plugins); $i < $m; $i++) {
			include($this->plugins[$i] . '/index.php');
		}
		return;
	}

	/**
	 * 添加钩子代码
	 * @param string $position
	 * @param $func
	 * @return $this
	 */
	public function setFunction($position, $func)
	{
		if (!isset($this->functions[$position])) $this->functions[$position] = array();
		if (is_array($func)) {
			for ($i = 0, $m = count($func); $i < $m; $i++) {
				if (is_callable($func[$i])) $this->functions[$position][] = $func[$i];
			}
		} elseif (is_callable($func)) {
			$this->functions[$position][] = $func;
		}
		return $this;
	}

	/**
	 * 执行钩子代码
	 * @param $position
	 * @param bool $desc
	 * @param string $para
	 * @return mixed|string
	 */
	public function run($position, $desc = false, $para = '')
	{
		if (isset($this->functions[$position])) {
			$m = count($this->functions[$position]);
			for ($i = 0; $i < $m; $i++) {
				$n = $desc ? ($m - $i - 1) : $i;
				if (!empty($para)) {
					$para = call_user_func($this->functions[$position][$n], $para);
				} else {
					call_user_func($this->functions[$position][$n], $this);
				}
			}
		}
		return $para;
	}

	/**
	 * 页面起始
	 * @param string $charset
	 * @param bool $setPlugin
	 */
	public function start($charset = 'UTF-8', $setPlugin = true)
	{
		ob_start();
		ob_implicit_flush(false);
		header('Powered-by: MyStep Framework');
		header_remove('x-powered-by');
		mb_internal_encoding($charset);
		mb_http_output($charset);
		mb_http_input($charset);
		mb_regex_encoding($charset);
		header('Content-Type: text/html; charset=' . $charset);

		setOp(array(
			'enable_cli' => 1,
			'memory_consumption' => 128,
			'interned_strings_buffer' => 8,
			'max_accelerated_files' => 4000,
			'fast_shutdown' => 1,
			'validate_timestamps' => 1,
			'revalidate_freq' => 120,
			'huge_code_pages' => 1,
			'file_cache' => dirname(dirname(__FILE__)) . '/cache/op/'
		));

		if ($setPlugin) $this->plugin();
		$this->run('start');
	}

	/**
	 * 显示页面
	 * @param myTemplate $tpl
	 */
	public function show(myTemplate $tpl, $minify = false)
	{
		$this->pushAddedContent($tpl);
		$tpl->assign('lng', $this->language);
		$tpl->regTag($this->func_tag);
		if (count(ob_list_handlers()) == 0 && !headers_sent()) ob_start();
		$page = $tpl->display('$tpl', false, $minify);
		echo $this->run('page', false, $page);
		unset($tpl);
	}

	/**
	 * 页面结束
	 */
	public function end()
	{
		$this->run('end', true);
		if (!empty(self::$goto_url) && ob_get_length() == 0) {
			$this->redirect(self::$goto_url);
		}
	}

	/**
	 * 链接跳转
	 * @param $url
	 * @param string $code
	 */
	public static function redirect($url = '', $code = '302')
	{
		if (empty($url)) {
			$url = myReq::server('HTTP_REFERER');
			if (is_null($url)) $url = '/';
			$code = '302';
		}
		header('location: ' . $url, true, $code);
		exit;
	}
}