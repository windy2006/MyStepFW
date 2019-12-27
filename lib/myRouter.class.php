<?php
/********************************************
*                                           *
* Name    : Ruoter handler                  *
* Modifier: Windy2000                       *
* Time    : 2018-12-26                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/

/**
网站路由处理
    $router = new myRouter();
    $router->format('hex','[a-fA-F0-9]+');
    $router->rule('/test/[any]/[str]/[hex]/[yyy]/[int]', function(){var_dump(func_get_args());});
    $router->check('/test/哈哈/string/aB123f/yyy/123456');
    $router->parse()
 */
class myRouter extends myBase {
	use myTrait;

	public
		$query = '',
		$route = array();

	protected
		$formats = array(
			'any' => '(.*?)',
			'int' => '(\d+)',
			'str' => '(\w+)'
		),
		$setting = array(),
		$rules = array(),
		$info = array('app'=>'','path'=>'','para'=>array());

	/**
	 * 参数初始化
	 * @param array $setting
	 * @param string $p
	 * @param string $q
	 */
	public function init($setting=array()) {
		if(!isset($setting['mode'])) $setting['mode'] = 'rewrite';
		if(!isset($setting['default_app'])) $setting['default_app'] = 'myStep';
		if(!isset($setting['delimiter_path'])) $setting['delimiter_path'] = '/';
		if(!isset($setting['delimiter_para'])) $setting['delimiter_para'] = '&';

		$qstr = trim(myReq::svr('QUERY_STRING'));
		$path_info = myReq::svr('PATH_INFO');
		if(empty($path_info)) {
			$path_info = myReq::server('ORIG_PATH_INFO');
			$path_info = str_replace(myReq::server('SCRIPT_NAME'), '', $path_info);
		}
		if(empty($qstr)) {
			$qstr = $path_info;
		} else {
			$qstr = $path_info.'?'.$qstr;
		}
		$qstr = trim($qstr, '?');
		if(strpos($qstr, '/')!==0) $qstr = '/'.$qstr;

		array_shift($_GET);
		preg_match('#^(.+?)((&|\?)(.+))?$#', $qstr, $match);
		$p = $match[1] ?? '';
		$q = $match[4] ?? '';
		parse_str($q, $_GET);

		$_SERVER["QUERY_STRING"] = preg_replace('#^.+(\?|&)(.+)$#', '\2', $qstr);
		$this->route = compact('qstr','p', 'q');
		$this->query = $qstr;
		$this->setting = $setting;

		$this->parse();
	}

	/**
	 * 设置路由辨别格式
	 * @param $name
	 * @param $pattern
	 * @return $this
	 */
	public function format($name, $pattern) {
		$this->formats[$name] = '('.$pattern.')';
		return $this;
	}

	/**
	 * 批量设置路由辨别格式
	 * @param $formats
	 * @return $this
	 */
	public function setFormats($formats) {
		reset($formats);
		if(is_string(current($formats))) {
			foreach($formats as $k => $v) {
				$this->format($k, $v);
			}
		} else {
			foreach($formats as $format) {
				$this->setFormats($format);
			}
		}
		return $this;
	}

	/**
	 * 设置路由规则
	 * @param $rule
	 * @param $method
	 * @return $this
	 */
	public function rule($rule, $method, $idx = '') {
		preg_match_all('#\[(\w+)\]#', $rule,$match);
		for($i=0,$m=count($match[0]);$i<$m;$i++) {
			if(isset($this->formats[$match[1][$i]])) {
				$rule = str_replace($match[0][$i], $this->formats[$match[1][$i]], $rule);
			} else {
				$rule = str_replace($match[0][$i], '('.$match[1][$i].')', $rule);
			}
		}
		$this->rules[] = array('pattern'=>$rule, 'method'=>$method, 'idx'=>$idx);
		return $this;
	}

	/**
	 * 批量设置路由规则
	 * @param $rules
	 * @param string $idx
	 * @return $this
	 */
	public function setRules($rules, $idx = '') {
		if(is_array($rules)) {
			if(isset($rules[0]) && is_string($rules[0])) {
				$this->rule($rules[0], $rules[1], $idx);
			} else {
				foreach($rules as $k => $rule) {
					$this->setRules($rule, is_numeric($k)?$idx:$k);
				}
			}
		} elseif(is_file($rules)) {
			include($rules);
			if(isset($format_list)) {
				$this->setFormats($format_list);
			}
			if(isset($rule_list)) {
				$this->setRules($rule_list, $idx);
			}
		}
		return $this;
	}

	/**
	 * 路由规则检测
	 * @return bool
	 */
	public function check() {
		foreach($this->rules as $rule) {
			if(preg_match('#^'.$rule['pattern'].'$#', $this->query,$match)) {
				array_shift($match);
				if(preg_match('#^/\(\\\w\+\)/[a-zA-Z]+/\(.+#', $rule['pattern'])) {
					$rule['idx'] = array_shift($match);
				}
				if(strpos($rule['idx'], 'plugin_')===0) $rule['idx'] = 'myStep';
				if(!is_dir(APP.$rule['idx'])) {
					myStep::info('app_missing');
				}
				$info_app = include(APP.$rule['idx'].'/info.php');
				$info_app['path'] = explode('/', trim($this->route['p'],'/'));
				$info_app['para'] = $this->info['para'];
				$info_app['route'] = $this->route['p'];
				myReq::globals('info_app', $info_app);
				if(is_file(APP.$rule['idx'].'/lib.php')) require_once(APP.$rule['idx'].'/lib.php');
				myStep::setPara();
				if(is_array($rule['method'])) {
					$match = array_slice($match,0,1);
					$last = array_pop($rule['method']);
					$flag = true;
					foreach($rule['method'] as $each) {
						$each = explode(',', $each);
						$method = array_shift($each);
						array_push($each, $flag);
						if(is_callable($method)) {
							$flag = call_user_func_array($method, $each);
						} else {
							$flag = false;
						}
						if($flag===false) break;
					}
					array_push($match, $flag);
					if($flag!==false && is_callable($last)) {
						call_user_func_array($last, $match);
						exit;
					}
					return true;
				} else {
					if(gettype($rule['method'])=='object') {
						$paras = array();
						$method = $rule['method'];
					} else {
						$paras = explode(',', $rule['method']);
						$method = array_shift($paras);
					}
					$paras = array_merge($paras, $match);
					if(is_callable($method)) {
						call_user_func_array($method, $paras);
					} else {
						return false;
					}
				}
				return true;
			}
		}
		return false;
	}

	/**
	 * 路由解析
	 * @param string $q
	 * @return array
	 */
	public function parse($q='') {
		if(empty($q)) $q = $this->query;
		$setting = $this->setting;
		$q = explode($setting['delimiter_path'], trim($q,'/'));
		$path = array();
		if(end($q) && (strpos(end($q), $setting['delimiter_para'])!==false || strpos(end($q), '=')!==false)) {
			$para = explode($setting['delimiter_para'], array_pop($q));
			foreach($para as $k => $v) {
				if(strpos($v, '=')) {
					list($k,$v) = explode('=', $v);
					$k = trim($k, '?&');
					$this->info['para'][$k] = $v;
				} else {
					if(!empty($v)) $path[] = $v;
				}
			}
		}
		if(empty(reset($q))) array_shift($q);
		$this->info['app'] = (count($q)>0) ? array_shift($q) : $setting['default_app'];
		$this->info['path'] = array_merge([$this->info['app']], $q, $path);
		$info = array();
		if(is_file(APP.$this->info['app'].'/info.php')) {
			$info = include(APP.$this->info['app'].'/info.php');
		}
		$this->info = array_merge($info, $this->info);
		return $this->info;
	}

	/**
	 * 移除某条规则
	 * @param $file
	 * @param $idx
	 * @return bool
	 */
	public function remove($file, $idx) {
		$flag = false;
		if(is_file($file)) {
			include($file);
			if(isset($format_list[$idx])) {
				unset($format_list[$idx]);
				$flag = true;
			}
			if(isset($rule_list[$idx])) {
				unset($rule_list[$idx]);
				$flag = true;
			}
			if(isset($api_list[$idx])) {
				unset($api_list[$idx]);
				$flag = true;
			}
			if($flag) {
				$result = '<?PHP' . chr(10);
				if (isset($format_list)) {
					$result .= '$format_list = ' . var_export($format_list, true) . ';' . chr(10) . chr(10);
				}
				if (isset($rule_list)) {
					$result .= '$rule_list = ' . var_export($rule_list, true) . ';' . chr(10) . chr(10);
				}
				if (isset($api_list)) {
					$result .= '$api_list = ' . var_export($api_list, true) . ';' . chr(10) . chr(10);
				}
				while (preg_match('/\'(function\(.+\})\'/sm', $result, $match)) {
					$result = str_replace($match[0], stripcslashes($match[1]), $result);
				}
				myFile::saveFile($file, $result);
			}
		}
		return $flag;
	}

	/**
	 * 检查并添加路由
	 * @param $path_main
	 * @param $path_this
	 * @param $idx
	 * @return bool
	 */
	public static function checkRoute($path_main, $path_this, $idx) {
		if($check = is_file($path_this)) {
			if(is_file($path_main)) include($path_main);
			if(!isset($format_list)) $format_list = array();
			if(!isset($rule_list)) $rule_list = array();
			if(!isset($api_list)) $rule_list = array();
			include($path_this);
			$flag = false;
			if(isset($format)) {
				if(!isset($format_list[$idx])) {
					$format_list[$idx] = $format;
					$flag = true;
				}
			}
			if(isset($rule)) {
				if(!isset($rule_list[$idx])) {
					$rule_list[$idx] = $rule;
					$flag = true;
				}
			}
			if(isset($api)) {
				if(!isset($api_list[$idx])) {
					$api_list[$idx] = $api;
					$flag = true;
				}
			}
			if($flag) {
				$result = '<?PHP' . chr(10);
				if (isset($format_list)) {
					//$result .= myString::toScript($format_list, 'format_list').chr(10).chr(10);
					$result .= '$format_list = ' . var_export($format_list, true) . ';' . chr(10) . chr(10);
				}
				if (isset($rule_list)) {
					if(isset($rule_list['myStep'])) {
						$tmp = $rule_list['myStep'];
						unset($rule_list['myStep']);
						$rule_list['myStep'] = $tmp;
					}
					//$result .= myString::toScript($rule_list, 'rule_list').chr(10).chr(10);
					$result .= '$rule_list = ' . var_export($rule_list, true) . ';' . chr(10) . chr(10);
				}
				if (isset($api_list)) {
					//$result .= myString::toScript($api_list, 'rule_list').chr(10).chr(10);
					$result .= '$api_list = ' . var_export($api_list, true) . ';' . chr(10) . chr(10);
				}
				while (preg_match('/\'(function\(.+\})\'/sm', $result, $match)) {
					$result = str_replace($match[0], stripcslashes($match[1]), $result);
				}
				myFile::saveFile($path_main, $result);
			}
		}
		return $check;
	}
}