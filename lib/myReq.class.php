<?php
/********************************************
*                                           *
* Name    : Request Object Functions        *
* Modifier: Windy2000                       *
* Time    : 2003-05-03                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/

/**
  服务器响应及提交信息处理
        self::init($cookie_opt, $session_opt)           // Set the Request Object

        self::check($idx)                               // Check the variables (POST, GET, FILE, COOKIE, SESSION)
        self::getValue($type, $para)                    // Get any variables (GLOBAL, GET, SERVER, COOKIE, etc.)
        self::get($para)                                // Get variable from query string
        self::post($para)                               // Get variable from post data
        self::request($para)                            // Get variable from _REQUEST
        self::server($para)                             // Get variable from _SERVER
        self::env($para)                                // Get variable from _ENV
        self::globals($para)                            // Get or Set variable for GLOBAL
        self::ip()                                      // Get Client IP

        self::setCookieOpt($setting)                                                // Set cookie settings
        self::setCookie($name, $value, $expire, $path, $domain, $secure)            // Set a cookie
        self::setCookie_nopre($name, $value, $expire, $path, $domain, $secure)      // Set a cookie without prefix
        self::removeCookie($name, $pre)                                             // Remove a cookie
        self::cookie($para)                                                         // Get variable from _COOKIE

        self::setSessionOpt($setting)                   // Set session settings
        self::sessionStart($handle)                     // Start the session and set the handle functions
        self::sessionRestart($handle)                   // Restart Session
        self::sessionEnd()                              // Destroy Session
        self::session($para)                            // Get a session value or Get all session data with a encode string
        self::removeSession($key)                       // Unset a session item
        self::setSessions($data)                        // Decode and batch set sessions from a encode string
        self::sessionId()                               // Get the current session id
        self::setSessionPath($path)                     // Get or set the current session save path
        self::setSessionName($name)                     // Get or set the current session name
*/
class myReq extends myBase {
    use myTrait;

    public static
        $func_alias = array(
            'g' => 'get', 
            'p' => 'post', 
            'f' => 'files', 
            'r' => 'request', 
            'c' => 'cookie', 
            's' => 'session', 
            'e' => 'env', 
            'svr' => 'server', 
            'gl' => 'globals', 
        );
    protected static
        $cookie_opt = array(
            'path' => '', 
            'domain' => '', 
            'prefix' => ''
        ), 
        $session_opt = array(
            'id' => '', 
            'expire' => 30, 
            'gc' => false, 
            'mode' => 'files', 
            'trans_sid' => false
        );

    /**
     * Cookie（路径、域名和前缀）及Session（id、过期时间、回收、传送ID）初始化
     * @param array $cookie_opt
     * @param array $session_opt
     */
    public static function init($cookie_opt = array(), $session_opt = array()) {
        spl_autoload_register(function ($class) {
            $file = __DIR__.'/session/'.$class.'.class.php';
            if(is_file($file)) require_once($file);
        });
        self::setCookieOpt($cookie_opt);
        self::setSessionOpt($session_opt);
    }

    /**
     * 检测相关环境变量
     * @param $idx
     * @return int
     */
    public static function check($idx) {
        $idx = '_'.strtoupper($idx);
        global $$idx;
        return is_null($$idx) ? 0 : count($$idx);
    }

    /**
     * 获取相关相应值
     * @param string $type
     * @param null $para
     * @param string $format
     * @return bool|float|mixed|null|string|string[]
     */
    public static function getValue($type = 'get', $para = null, $format = '') {
        $type = strtolower($type);
        $var = null;
        switch($type) {
            case 'server':
                $var = &$_SERVER;
                break;
            case 'get':
                $var = &$_GET;
                break;
            case 'post':
                $var = &$_POST;
                break;
            case 'request':
                $var = &$_REQUEST;
                break;
            case 'files':
                $var = &$_FILES;
                break;
            case 'cookie':
                $var = &$_COOKIE;
                break;
            case 'session':
                $var = &$_SESSION;
                break;
            default:
                $var = &$GLOBALS;
                break;
        }
        if(is_null($para)) {
            foreach ($var as $key => $value) {
                if (!empty($format)) $value = htmlspecialchars($value);
                self::globals($key, $value);
            }
        }elseif($para=='[ALL]') {
            return recursionFunction('htmlspecialchars', $var);
        } else {
            $result = null;
            if(isset($var[$para])) {
                $result = $var[$para];
                if(is_string($result)) {
                    if(empty($format)) {
                        if(strtolower($para)=='id' || substr(strtolower($para), -3)=='_id') {
                            $format = 'int';
                        } else {
                            $format = '!';
                        }
                    }
                    switch($format) {
                        case '!':
                            break;
                        case 'int':
                            $result = floor(floatval($result));
                            break;
                        case 'url':
                            $result = preg_replace('/[^\w\-\.]/', '', $result);
                            break;
                        case 'char':
                            $result = preg_replace('/[^\w]/', '', $result);
                            break;
                        case 'str':
                            //$result = preg_replace("/[".preg_quote('"\'`~!@#$%^&*()[]{};:<>?\\=').']/', '', $result);
                            $result = str_ireplace('eval', 'eval!', $result);
                            $result = str_ireplace('assert', 'assert!', $result);
                            $result = str_ireplace('riny', 'riny!', $result);
                            $result = htmlspecialchars($result);
                            break;
                        default:
                            if(is_callable($format)) {
                                $result = call_user_func($format, $result);
                            }
                            $result = preg_replace('/[^'.$format.']/i', '', $result);
                            break;
                    }
                }
            }
            return $result;
        }
        return true;
    }

    /**
     * 读取_GET数据
     * @param string $para
     * @param string $format
     * @return bool|float|int|mixed|null|string|string[]
     */
    public static function get($para = '', $format = 'str') {
        if(empty($para)) {
            self::getValue("get");
            return count($_GET);
        } else {
            return self::getValue("get", $para, $format);
        }
    }

    /**
     * 读取_POST数据
     * @param string $para
     * @param string $format
     * @return bool|float|int|mixed|null|string|string[]
     */
    public static function post($para = '', $format = 'str') {
        if(empty($para)) {
            self::getValue("post");
            return count($_POST);
        } else {
            return self::getValue("post", $para, $format);
        }
    }

    /**
     * 读取_FILES数据
     * @param string $para
     * @return bool|float|int|mixed|null|string|string[]
     */
    public static function files($para = '') {
        if(empty($para)) {
            self::getValue("files");
            return count($_FILES);
        } else {
            return self::getValue("files", $para, '!');
        }
    }

    /**
     * 读取_REQUEST数据
     * @param string $para
     * @param string $format
     * @return bool|float|int|mixed|null|string|string[]
     */
    public static function request($para = '', $format = 'str') {
        if(empty($para)) {
            self::getValue("request");
            return count($_REQUEST);
        } else {
            return self::getValue("request", $para, $format);
        }
    }

    /**
     * 读取_SERVER数据
     * @param string $para
     * @param string $format
     * @return bool|float|mixed|null|string|string[]
     */
    public static function server($para = "", $format = '!') {
        if(empty($para)) return "";
        $para = strtoupper($para);
        $return = self::getValue("server", $para, $format);
        if(empty($return)) $return = self::getValue("server", 'HTTP_'.$para, $format);
        if(empty($return)) $return = self::getValue("env", $para, $format);
        return $return;
    }

    /**
     * 读取_ENV数据
     * @param $para
     * @return array|false|string
     */
    public static function env($para) {
        if(!isset($_ENV)) return getenv($para);
        return (isset($_ENV[$para])?$_ENV[$para]:'');
    }

    /**
     * 读取GLOABLS数据
     * @param $name
     * @param null $value
     * @return mixed
     */
    public static function globals($name, $value = null) {
        if(is_null($value)) return $GLOBALS[$name];
        $GLOBALS[$name] = $value;
        return true;
    }

    /**
     * 返回客户端IP
     * @return null|string|string[]
     */
    public static function ip() {
        $ip = $ip_org = $_SERVER["REMOTE_ADDR"];
        if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            $ip_list = explode(", ", $ip);
            if(count($ip_list)>1) $ip = $ip_list[0];
        } elseif(isset($_SERVER["HTTP_CLIENT_IP"])) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }
        if(!empty($ip) && $ip!=$ip_org) {
            $ip = $ip_org.", ".$ip;
        } else {
            $ip = $ip_org;
        }
        $ip = preg_replace("/[^\w\.\-, ]+/", "", $ip);
        return $ip;
    }

    /**
     * 设置COOKIE参数
     * @param $setting
     */
    public static function setCookieOpt($setting) {
        self::$cookie_opt = $setting + self::$cookie_opt;
    }

    /**
     * 设置COOKIE（带前缀）
     * @param $name
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @return bool
     */
    public static function setCookie($name, $value = '', $expire = 0, $path = '', $domain = '') {
        if(headers_sent($file, $line)) {
            trigger_error("Content is already been sent at $line line of '$file'!");
            return false;
        }
        if($expire<=0) {
            $expire = $_SERVER['REQUEST_TIME'] - 3600;
        } else {
            $expire = $_SERVER['REQUEST_TIME'] + $expire;
        }
        if(empty($path) && !empty(self::$cookie_opt['path'])) {
            $path = self::$cookie_opt['path'];
        } else {
            $path = "/";
        }
        if(empty($domain) && !empty(self::$cookie_opt['domain'])) $domain = self::$cookie_opt['domain'];
        return setcookie(self::$cookie_opt['prefix'].$name, $value, $expire, $path, $domain, isHttps());
    }

    /**
     * 设置COOKIE（不带前缀）
     * @param $name
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     */
    public static function setCookie_nopre($name, $value = '', $expire = 0, $path = '', $domain = '') {
        $pre = self::$cookie_opt['prefix'];
        self::$cookie_opt['prefix'] = '';
        self::setCookie($name, $value, $expire, $path, $domain);
        self::$cookie_opt['prefix'] = $pre;
    }

    /**
     * 删除COOKIE
     * @param $name
     * @param bool $pre
     */
    public static function removeCookie($name, $pre = true) {
        if($pre) $name = self::$cookie_opt['prefix'].$name;
        self::setCookie_nopre($name);
    }

    /**
     * 读取COOKIE
     * @param string $para
     * @param bool $pre
     * @return bool|float|int|mixed|null|string|string[]
     */
    public static function cookie($para = "", $pre = true) {
        if(empty($para)) {
            self::getValue("cookie");
            return count($_COOKIE);
        } else {
            if($pre) $para = self::$cookie_opt['prefix'].$para;
            return self::getValue("cookie", $para, "!");
        }
    }

    /**
     * 设置SESSION参数
     * @param $setting
     */
    public static function setSessionOpt($setting) {
        if(!isset($setting['mode']) || $setting['mode']!='user') $setting['mode'] = 'files';
        self::$session_opt = $setting + self::$session_opt;
        if(!empty(self::cookie($setting['name'])) && empty(self::$session_opt['id'])) self::$session_opt['id']=self::cookie($setting['name']);
        //if(isset($setting['mode'])) session_module_name($setting['mode']);
        if(isset($setting['path'])) self::setSessionPath($setting['path']);
        if(isset($setting['name'])) self::setSessionName($setting['name']);
    }

    /**
     * 启用SESSION
     * @param array $handle
     * @param bool $httponly
     * @return string|bool
     */
    public static function sessionStart($handle = array(), $httponly = true) {
        if(self::sessionCheck()) return false;
        $flag = false;
        if(is_string($handle) && class_exists($handle)) {
            $flag = session_set_save_handler(
                                array($handle, 'open'), 
                                array($handle, 'close'), 
                                array($handle, 'read'), 
                                array($handle, 'write'), 
                                array($handle, 'destroy'), 
                                array($handle, 'gc')
                            );
        }elseif(is_array($handle) && count($handle)==6) {
            $flag = session_set_save_handler($handle[0], $handle[1], $handle[2], $handle[3], $handle[4], $handle[5]);
        }
        if($flag) {
            //ini_set('session.save_handler', 'user');
            //session_module_name("user");
            self::$session_opt['mode']=='user';
        }else {
            //ini_set('session.save_handler', 'files');
            session_module_name("files");
            self::$session_opt['mode']=='files';
        }
        if(!empty(self::$session_opt['id'])) {
            session_id(self::$session_opt['id']);
        }
        if(self::$session_opt['gc']) {
            ini_set('session.gc_maxlifetime', self::$session_opt['expire'] * 3);
            ini_set('session.gc_probability', 5);
            ini_set('session.gc_divisor', 100);
        }
        ini_set('session.use_trans_sid', (self::$session_opt['trans_sid']?'1':'0'));
        if(self::$session_opt['trans_sid']) {
            ini_set("session.use_cookies", "0");
            ini_set("url_rewriter.tags", "a=href, area=href, script=src, link=href, frame=src, input=src, form=fakeentry");
        } else {
            ini_set("session.use_cookies", "1");
            $lifetime = self::$session_opt['expire']*60;
            $path = self::$cookie_opt['path'];
            $domain = self::$cookie_opt['domain'];
            $secure = isHttps();
            session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
        }
        session_cache_limiter('private, must-revalidate');
        session_cache_expire(self::$session_opt['expire']);
        session_start();
        if(!self::$session_opt['trans_sid']) setcookie(session_name(), session_id(), $_SERVER["REQUEST_TIME"]+self::$session_opt['expire']*60, self::$cookie_opt['path'], self::$cookie_opt['domain']);
        function_exists('session_register_shutdown') ? session_register_shutdown() : register_shutdown_function('session_write_close');
        self::setCookie_nopre(self::setSessionName(), session_id(), ini_get('session.gc_maxlifetime'));
        return session_id();
    }

    /**
     * 重启SESSION
     * @param string $id
     * @param bool $delete_old_session
     * @return string
     */
    public static function sessionRestart($id = "", $delete_old_session = true) {
        session_regenerate_id($delete_old_session);
        session_write_close();
        if(!empty($id)) {
            self::$session_opt['id'] = $id;
            session_id($id);
        }
        session_start();
        return session_id();
    }

    /**
     * 关闭SESSION
     */
    public static function sessionEnd() {
        if(!self::sessionCheck()) return;
        $_SESSION = array();
        session_unset();
        session_destroy();
        session_commit();
        if(!headers_sent()) setcookie(session_name(), '', 0);
        return;
    }

    /**
     * 读取SESSION
     * @param string $key
     * @param null $value
     * @return bool|float|mixed|null|string|string[]
     */
    public static function session($key="", $value=null) {
        if(!self::sessionCheck() || is_int($key)) {
            return null;
        } elseif(empty($key)) {
            return session_encode();
        } else {
            if(is_null($value)) {
                return self::getValue("session", $key, '!');
            } else {
                $_SESSION[$key] = $value;
            }
        }
        return null;
    }

    /**
     * 删除SESSION
     * @param $key
     */
    public static function removeSession($key) {
        if(!self::sessionCheck() || is_int($key)) return;
        unset($_SESSION[$key]);
    }

    /**
     * 读取SESSION源字串，并批量赋值
     * @param $data
     * @return bool|void
     */
    public static function setSessions($data) {
        if(!self::sessionCheck()) return;
        $old_session = $_SESSION;
        self::sessionRestart();
        if(session_decode($data)) {
            return $old_session;
        } else {
            $_SESSION = array();
            $_SESSION = $old_session;
            return false;
        }
    }

    /**
     * 获取SESSION ID
     * @return string
     */
    public static function sessionId() {
        return session_id();
    }

    /**
     * 检测SESSION是否已启动
     * @return bool
     */
    public static function sessionCheck() {
        return function_exists('session_status') ? (session_status()==PHP_SESSION_ACTIVE ) : ( !empty(session_id()) );
    }

    /**
     * 设置SESSION保存路径
     * @param string $path
     * @return string
     */
    public static function setSessionPath($path = "") {
        if(empty($path) || self::sessionCheck()) {
            return session_save_path();
        } else {
            if(self::$session_opt['mode']=='files' && (is_dir($path) || @mkdir($path, 0777, true))) {
                $path =    realpath($path);
                session_save_path($path);
            }
            return session_save_path();
        }
    }

    /**
     * 设置SESSION名称
     * @param string $name
     * @return string
     */
    public static function setSessionName($name = "") {
        if(empty($name) || self::sessionCheck()) {
            return session_name();
        } else {
            return session_name($name);
        }
    }
}
?>