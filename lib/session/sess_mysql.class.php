<?PHP
/**
 * MySQL存储SESSION类
 */
class sess_mysql implements interface_session {
    private static
        $setting,
        $cnt;

    public static function set($setting) {
        self::$setting = $setting;
    }

    public static function open($sess_path, $sess_name) {
        $result = false;
        if(!isset(self::$setting['charset'])) self::$setting['charset'] = 'utf8';
        if(self::$cnt = mysqli_connect(self::$setting['host'], self::$setting['user'], self::$setting['password'], self::$setting['name'])) {
            $sql = '
CREATE TABLE IF NOT EXISTS `my_session` (
    `SID` char(32) NOT NULL,
    `expiration` int(10) NOT NULL,
    `value` char(255) NOT NULL,
    INDEX `expiration` (`expiration`),
    PRIMARY KEY (`SID`)
) ENGINE=heap DEFAULT CHARSET='.self::$setting['charset'];
            $result = mysqli_query(self::$cnt, $sql);
        }
        return $result;
    }

    public static function close() {
        if(rand(1,100)>95) self::gc(ini_get('session.gc_maxlifetime'));
        return true;
    }

    public static function read($sid) {
        if($result = mysqli_query(self::$cnt, 'SELECT value FROM my_session WHERE SID = "'.$sid.'" AND expiration > '.$_SERVER['REQUEST_TIME'])) {
            if(mysqli_num_rows($result)) {
                $record = mysqli_fetch_assoc($result);
                return $record['value'];
            }
        }
        return '';
    }

    public static function write($sid, $sess_data) {
        if($GLOBALS['no_log']) return true;
        $expiration = $_SERVER['REQUEST_TIME'] + ini_get('session.gc_maxlifetime');
        $sess_data = mysqli_real_escape_string(self::$cnt, $sess_data);
        $result = false;
        if(strlen($sess_data)>255) {
            trigger_error('Session is too long to handle, please change the table to MyISAM Engine.');
        } else {
            $result = mysqli_query(self::$cnt, 'REPLACE INTO my_session (SID, expiration, value) VALUES ("'.$sid.'","'.$expiration.'","'.$sess_data.'")');
        }
        return $result;
    }

    public static function destroy($sid) {
        return mysqli_query(self::$cnt, 'DELETE FROM my_session WHERE SID='.$sid);
    }

    public static function gc($maxlifetime) {
        return mysqli_query(self::$cnt, 'DELETE FROM my_session WHERE expiration < ' . ($_SERVER['REQUEST_TIME'] - $maxlifetime));
    }
}