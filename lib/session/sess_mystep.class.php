<?PHP
/**
 * 自定义SESSION存储类（含在线访客监控）
 */
class sess_mystep implements interface_session {
    public static $cnt, $run;

    public static function open($sess_path, $sess_name) {
        self::$run = 3; // 1 - read ; 2 - write
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']??'null');
        if(strpos($agent, 'spider')!==false || strpos($agent, 'bot')!==false) self::$run = 0;
        global $info_app;
        if(isset($info_app['path'][0]) && in_array($info_app['path'][0],['api','module','ms_language','ms_setting','pack'])) self::$run ^= 2;
        if(self::$run>0) {
            global $ms_setting;
            self::$cnt = mysqli_connect($ms_setting->db->host, $ms_setting->db->user, $ms_setting->db->password, $ms_setting->db->name);
            mysqli_query(self::$cnt, 'SET NAMES "'.$ms_setting->db->charset.'"');
        }
        return true;
    }
    public static function close() {
        if(self::$run==0) return true;
        if(rand(1,100)>95) {
            self::gc(ini_get('session.gc_maxlifetime'));
        } else {
            mysqli_close(self::$cnt);
        }
        self::$run = 0;
        return true;
    }

    public static function read($sid) {
        if((self::$run & 1) == 1) {
            global $ms_setting;
            if($result = mysqli_query(self::$cnt, 'SELECT data FROM '.$ms_setting->db->pre.'user_online WHERE sid="'.$sid.'" AND refresh>'.($_SERVER['REQUEST_TIME']-($ms_setting->session->expire*60)))) {
                if(mysqli_num_rows($result)) {
                    $record = mysqli_fetch_assoc($result);
                    //$record['data'] = gzinflate($record['data']);
                    return $record['data'];
                }
            }
        }
        return '';
    }

    public static function write($sid, $sess_data) {
        if((self::$run & 2) == 2) {
            global $ms_setting;
            //$sess_data = gzdeflate($sess_data, 9);
            $sess_data = mysqli_real_escape_string(self::$cnt, $sess_data);
            $ip = mysqli_real_escape_string(self::$cnt, r::ip());
            $refresh = $ms_setting->info->time;
            $url = mysqli_real_escape_string(self::$cnt, 'http://'.r::svr('SERVER_NAME').r::svr('REQUEST_URI'));
            return mysqli_query(self::$cnt, 'REPLACE INTO '.$ms_setting->db->pre.'user_online (sid, ip, refresh, url, data) VALUES ("'.$sid.'", "'.$ip.'", "'.$refresh.'", "'.$url.'", "'.$sess_data.'")');
        }
        return true;
    }

    public static function destroy($sid) {
        if(self::$run==0) return true;
        global $ms_setting;
        return mysqli_query(self::$cnt, 'DELETE FROM '.$ms_setting->db->pre.'user_online WHERE sid="'.$sid.'"');
    }

    public static function gc($maxlifetime=60) {
        if(self::$run==0) return true;
        global $ms_setting;
        mysqli_query(self::$cnt, 'DELETE FROM '.$ms_setting->db->pre.'user_online WHERE refresh<'.($_SERVER['REQUEST_TIME'] - $ms_setting->session->expire * 60));
        mysqli_close(self::$cnt);
        self::$run = 0;
        return true;
    }
}