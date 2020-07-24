<?PHP
/*--- myCache_MSSQL ---*/
class myCache_MSSQL implements interface_cache {
    protected $cnt;

    public function __construct($setting) {
        $result = false;
        $connectionInfo = array(
            'Database' => $setting['name'],
            'UID' => $setting['user'],
            'PWD' => $setting['password'],
            'CharacterSet' => $setting['charset']
        );
        $this->cnt = sqlsrv_connect($setting['host'], $connectionInfo);
        if(is_resource($this->cnt)) {
            $sql = '
if not exists (select * from sysobjects where id = object_id(N\'[my_cache]\') and OBJECTPROPERTY(id, N\'IsUserTable\') = 1) CREATE TABLE my_cache (
    [key] char(32) NOT NULL,
    [expiration] int NOT NULL,
    [value] text NOT NULL,
    PRIMARY KEY ([key])
)';
            $result = $this->query($sql);
        }
        return $result;
    }

    public function set($key, $value = '', $ttl = 600) {
        $new_key = md5($key);
        if(empty($value)) {
            return $this->query("delete from my_cache where key='{$new_key}'");
        } else {
            $expiration = $_SERVER['REQUEST_TIME'] + $ttl;
            $value = str_replace("'",  "\\'", serialize($value));
            return $this->query("insert INTO my_cache ([key], [expiration], [value]) VALUES ('{$new_key}',{$expiration},'{$value}')");
        }
    }

    public function get($key) {
        $new_key = md5($key);
        if($result = $this->query("SELECT [value] FROM my_cache WHERE [key] = '{$new_key}' AND [expiration] > ".$_SERVER['REQUEST_TIME'])) {
            $record = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
            return unserialize($record['value']);
        }
        return false;
    }

    public function remove($key) {
        $new_key = md5($key);
        return $this->query("delete from my_cache where key='{$new_key}'");
    }

    public function clean() {
        return $this->query("DELETE FROM my_cache WHERE expiration < UNIX_TIMESTAMP()");
    }
    
    public function query($sql) {
        return sqlsrv_query($this->cnt, $sql, array(), array('Scrollable' => 'forward', 'QueryTimeout'=>3));
    }
}