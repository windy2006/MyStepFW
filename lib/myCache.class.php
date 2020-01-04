<?php
/********************************************
*                                           *
* Name    : My Cache                        *
* Modifier: Windy2000                       *
* Time    : 2010-12-12                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/

/**
	缓存代理类
		$mycache->init($mode, $setting)                  // 类对象初始化
		$mycache->set($key, $value, $ttl)                // 设置缓存
		$mycache->get($key)                              // 获取缓存
		$mycache->remove($key)                           // 删除缓存
		$mycache->clean()                                // 清除缓存
		$mycache->change($module, $setting)              // 变更缓存模块
		$mycache->getData($query, $mode, $ttl)           // 数据库查询缓存
		$mycache->getData_func($func, $args, $ttl)       // 函数结果缓存
*/
class myCache extends myProxy {
	protected
		$func_alias = array(
			's' => 'set',
			'g' => 'get',
			'r' => 'remove',
			'c' => 'clean',
			'db' => 'getData_DB',
			'func' => 'getData_func',
		);

	/**
	 * 类对象初始化
	 * @param string $module
	 * @param null $setting
	 */
	public function init($module = '', $setting = null){
		spl_autoload_register(function($class) {
			$file = __DIR__.'/cache/'.$class.'.class.php';
			if(is_file($file)) require_once($file);
		});
		if(class_exists($module)) {
			$flag = $this->obj = new $module($setting);
		} else {
			$flag = $this->obj = new myCache_File($setting);
		}
		if(!$flag) $this->error('Cannot initialize cache module!', E_USER_ERROR);
	}

	/**
	 * 变更缓存模块
	 * @param $module
	 * @param null $setting
	 */
	public function change($module, $setting = null) {
		$this->obj = null;
		$this->init($module, $setting);
	}

	/**
	 * 数据库查询缓存
	 * @param $query
	 * @param string $mode
	 * @param int $ttl
	 * @return array|mixed|myDb|string
	 */
	public function getData($query, $mode='all', $ttl = 600) {
		global $db;
		if($db==null || !($db instanceof myDb)) return '';
		$key = md5($query);
		if($mode=='remove' || $ttl==0) {
			$this->set($key);
			return '';
		}
		$result = $this->get($key);
		if(!$result) {
			switch($mode) {
				case 'record':
					$result = $db->record($query);
					break;
				case 'result':
					$result = $db->result($query);
					break;
				default:
					$result = $db->records($query);
					break;
			}
			$db->free();
			$this->set($key, $result, $ttl);
		}
		return $result;
	}

	/**
	 * 函数结果缓存
	 * @param $func
	 * @param array $args
	 * @param int $ttl
	 * @return mixed|void
	 */
	public function getData_func($func, $args = array(), $ttl = 600) {
		$key = md5(serialize(array($func, $args)));
		if($ttl==0) {
			$result = $this->set($key);
		} else {
			$result = $this->get($key);
			if(!$result) {
				if(!is_array($args)) $args = array($args);
				$result = call_user_func_array($func, $args);
				$this->set($key, $result, $ttl);
			}
		}
		return $result;
	}
}