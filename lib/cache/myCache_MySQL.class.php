<?PHP
/*--- myCache_MySQL ---*/
class myCache_MySQL implements interface_cache {
    protected $cnt;

    public function __construct($setting) {
        $result = false;
        if(!isset($setting['charset']) || strtolower($setting['charset'])=='utf-8') $setting['charset'] = 'utf8';
        if($this->cnt = mysqli_connect($setting['host'], $setting['user'], $setting['password'], $setting['name'])) {
            $sql = '
CREATE TABLE IF NOT EXISTS `my_cache` (
    `key` char(32) NOT NULL,
    `expiration` int(10) NOT NULL,
    `value` text NOT NULL,
    INDEX `expiration` (`expiration`),
    PRIMARY KEY (`key`)
) ENGINE=MyISAM DEFAULT CHARSET='.$setting['charset'];
            $result = mysqli_query($this->cnt, $sql);
        }
        return $result;
    }

    public function set($key, $value = '', $ttl = 600) {
        $new_key = md5($key);
        if(empty($value)) {
            return mysqli_query($this->cnt, "delete from my_cache where key='{$new_key}'");
        } else {
            $expiration = $_SERVER['REQUEST_TIME'] + $ttl;
            $value = mysqli_real_escape_string($this->cnt, serialize($value));
            return mysqli_query($this->cnt, "REPLACE INTO my_cache (`key`, `expiration`, `value`) VALUES ('{$new_key}',{$expiration} , '{$value}')");
        }
    }

    public function get($key) {
        $new_key = md5($key);
        if($result = mysqli_query($this->cnt, "SELECT `value` FROM `my_cache` WHERE `key` = '{$new_key}' AND `expiration` > UNIX_TIMESTAMP()")) {
            if(mysqli_num_rows($result)) {
                $record = mysqli_fetch_assoc($result);
                return unserialize($record['value']);
            }
        }
        return false;
    }

    public function remove($key) {
        $new_key = md5($key);
        return mysqli_query($this->cnt, "delete from my_cache where key='{$new_key}'");
    }

    public function clean() {
        return mysqli_query($this->cnt, "DELETE FROM my_cache WHERE expiration < UNIX_TIMESTAMP()");
    }
}