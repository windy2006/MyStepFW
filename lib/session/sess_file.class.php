<?PHP
/**
 * 文件存储SESSION控制类
 */
class sess_file implements interface_session {
    public static $path;
    public static function open($sess_path, $sess_name) {
        if($GLOBALS['no_log']) return true;
        if(!file_exists($sess_path)) mkdir($sess_path, 0777, true);
        self::$path = $sess_path;
        return true;
    }

    public static function close() {
        if($GLOBALS['no_log']) return true;
        if(rand(1,100)>95) self::gc(ini_get('session.gc_maxlifetime'));
        return true;
    }

    public static function read($sid) {
        if($GLOBALS['no_log']) return '';
        $result = '';
        if(is_file(self::$path.'/sess_'.$sid)) $result = file_get_contents(self::$path.'/sess_'.$sid);
        return $result;
    }

    public static function write($sid, $sess_data) {
        if($GLOBALS['no_log']) return true;
        if(!file_exists(self::$path)) mkdir(self::$path, 0777, true);
        $result = file_put_contents(self::$path.'/sess_'.$sid, $sess_data, LOCK_EX);
        return is_int($result);
    }

    public static function destroy($sid) {
        if($GLOBALS['no_log']) return true;
        @unlink(self::$path.'/sess_'.$sid);
        return true;
    }

    public static function gc($maxlifetime) {
        if($GLOBALS['no_log']) return true;
        $dirname = basename(self::$path);
        if(preg_match('#^[\d\-]+$#', $dirname)) {
            $dir = dirname(self::$path);
            $mydir = @opendir($dir);
            while($file = readdir($mydir)) {
                if($file=='.' || $file=='..' || $file==$dirname) continue;
                myFile::del($dir.'/'.$file);
            }
        }
        $mydir = @opendir(self::$path);
        while($file = readdir($mydir)) {
            if($file=='.' || $file=='..') continue;
            $the_file = self::$path.'/'.$file;
            if(!is_file($the_file)) continue;
            if(filemtime($the_file)+$maxlifetime < $_SERVER['REQUEST_TIME']) {
                myFile::del($the_file);
            }
        }
        return true;
    }
}