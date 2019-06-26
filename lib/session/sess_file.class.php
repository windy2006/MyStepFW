<?php
/**
 * 文件存储SESSION控制类
 */
class sess_file implements interface_session {
	public static $path;
	public static function open($sess_path, $sess_name) {
		if(!file_exists($sess_path)) mkdir($sess_path, 0777, true);
		self::$path = $sess_path;
		return true;
	}
	
	public static function close() {
		self::gc(ini_get('session.gc_maxlifetime'));
		return true;
	}
	
	public static function read($sid) {
		$result = '';
		if(is_file(self::$path.'/sess_'.$sid)) $result = file_get_contents(self::$path.'/sess_'.$sid);
		return $result;
	}
	
	public static function write($sid, $sess_data) {
		if(!file_exists(self::$path)) mkdir(self::$path, 0777, true);
		$result = file_put_contents(self::$path.'/sess_'.$sid, $sess_data, LOCK_EX);
		return is_int($result);
	}
	
	public static function destroy($sid) {
		@unlink(self::$path.'/sess_'.$sid);
		return true;
	}
	
	public static function gc($maxlifetime) {
		$mydir = opendir(self::$path);
		while($file = readdir($mydir)) {
			if($file!='.' && $file!='..') {
				$the_file = self::$path.'/'.$file;
				if(filemtime($the_file)+$maxlifetime < $_SERVER['REQUEST_TIME']) {
					@unlink($the_file);
				}
			}
		}
		return true;
	}
}