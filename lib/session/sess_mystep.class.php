<?php
/**
 * 自定义SESSION存储类（含在线访客监控）
 */
class sess_mystep implements interface_session {
    public static $cnt;
    public static $skip;
    public static function open($sess_path, $sess_name) {
        self::$skip = false;
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        if(strpos($agent, "spider")!==false || strpos($agent, "bot")!==false || checkSign(1)) self::$skip = true;
        if(!self::$skip) {
            include(ROOT."/include/config.php");
            self::$cnt = mysqli_connect($setting['db']['host'], $setting['db']['user'], $setting['db']['pass'], $setting['db']['name']);
            mysqli_query(self::$cnt, "SET NAMES '".$setting['db']['charset']."'");
        };
        return true;
    }
    public static function close() {
        if(self::$skip) return true;
        self::sess_gc();
        return true;
    }

    public static function read($sid) {
        if(self::$skip) return "";
        global $setting;
        if($result = mysqli_query(self::$cnt, "SELECT * FROM ".$setting['db']['pre']."user_online WHERE sid = '{$sid}' AND reflash > ".($_SERVER["REQUEST_TIME"]-($setting['session']['expire']*60)))) {
            if(mysqli_num_rows($result)) {
                $record = mysqli_fetch_assoc($result);
                $record['userinfo'] = unserialize($record['userinfo']);
                return self::sessEncode($record);
            }
        }
        return "";
    }

    public static function write($sid, $sess_data) {
        if(self::$skip || checkSign(2)) return true;

        $sess_data = self::sessDecode($sess_data);
        $sess_data['ip'] = mysqli_real_escape_string(self::$cnt, $sess_data['ip']);
        $sess_data['userinfo'] = mysqli_real_escape_string(self::$cnt, serialize($sess_data['userinfo']));
        extract($sess_data);

        include(ROOT."/include/config.php");
        $reflash = $_SERVER["REQUEST_TIME"];
        if(empty($username)) $username = "Guest";
        if(empty($usertype)) $usertype = 1;
        if(empty($usergroup)) $usergroup = 0;
        $result = mysqli_query(self::$cnt, "REPLACE INTO ".$setting['db']['pre']."user_online (sid, ip, username, usertype, usergroup, reflash, url, userinfo) VALUES ('{$sid}', '{$ip}', '{$username}', '{$usertype}', '{$usergroup}', '{$reflash}', '{$url}', '{$userinfo}')");
        return $result;
    }

    public static function destroy($sid) {
        if(self::$skip) return true;
        global $setting;
        return mysqli_query(self::$cnt, "DELETE FROM ".$setting['db']['pre']."user_online WHERE sid='".$sid."'");
    }

    public static function gc() {
        if(self::$skip) return true;
        include(ROOT."/include/config.php");
        if(is_object(self::$cnt)) {
            mysqli_query(self::$cnt, "DELETE FROM ".$setting['db']['pre']."user_online WHERE reflash < " . ($_SERVER["REQUEST_TIME"] - $setting['session']['expire'] * 60));
            mysqli_close(self::$cnt);
        }
        unset($setting);
        return true;
    }

    public static function sessEncode($array, $safe = true) {
        if($safe) $array = unserialize(serialize($array));
        $raw = '';
        $line = 0;
        foreach($array as $key => $value) {
            $line ++ ;
            $raw .= $key .'|' ;
            if(is_array($value) && isset($value['MySessSign'])) {
                $raw .= 'R:'. $value['MySessSign'] . ';' ;
            } else {
                $raw .= serialize($value);
            }
            $array[$key] = Array('MySessSign' => $line) ;
        }
        return $raw;
    }

    public static function sessDecode($str) {
        $str = (string)$str;
        $endptr = strlen($str);
        $p = 0;
        $serialized = '';
        $items = 0;
        $level = 0;
        while ($p < $endptr) {
            $q = $p;
            while ($str[$q] != '|')
                if(++$q >= $endptr) break 2;

            if($str[$p] == '!') {
                $p++;
                $has_value = false;
            } else {
                $has_value = true;
            }
            $name = substr($str, $p, $q - $p);
            $q++;
            $serialized .= 's:' . strlen($name) . ':"' . $name . '";';
            if($has_value) {
                for(;;) {
                    $p = $q;
                    switch ($str[$q]) {
                        case 'N': /* null */
                        case 'b': /* boolean */
                        case 'i': /* integer */
                        case 'd': /* decimal */
                            do $q++;
                            while ( ($q < $endptr) && ($str[$q] != ';') );
                            $q++;
                            $serialized .= substr($str, $p, $q - $p);
                            if($level == 0) break 2;
                            break;
                        case 'R': /* reference    */
                            $q+= 2;
                            for($id = ''; ($q < $endptr) && ($str[$q] != ';'); $q++) $id .= $str[$q];
                            $q++;
                            $serialized .= 'R:' . ($id + 1) . ';'; /* increment pointer because of outer array */
                            if($level == 0) break 2;
                            break;
                        case 's': /* string */
                            $q+=2;
                            for($length=''; ($q < $endptr) && ($str[$q] != ':'); $q++) $length .= $str[$q];
                            $q+=2;
                            $q+= (int)$length + 2;
                            $serialized .= substr($str, $p, $q - $p);
                            if($level == 0) break 2;
                            break;
                        case 'a': /* array */
                        case 'O': /* object */
                            do $q++;
                            while ( ($q < $endptr) && ($str[$q] != ' {') );
                            $q++;
                            $level++;
                            $serialized .= substr($str, $p, $q - $p);
                            break;
                        case '}': /* end of array|object */
                            $q++;
                            $serialized .= substr($str, $p, $q - $p);
                            if(--$level == 0) break 2;
                            break;
                        default:
                            return false;
                    }
                }
            } else {
                $serialized .= 'N;';
                $q+= 2;
            }
            $items++;
            $p = $q;
        }
        return unserialize( 'a:' . $items . ': {' . $serialized . '}' );
    }
}