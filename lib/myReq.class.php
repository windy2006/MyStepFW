<?PHP
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
 * 文件：lib/myReq.class.php
 * 功能说明：HTTP请求、Cookie与Session访问工具
 *   统一请求过滤、环境读取、可信代理IP判断及会话生命周期处理。
 *
 * 方法目录：
 *   public static myReq::init($cookie_opt = array(), $session_opt = array()) — Cookie（路径、域名和前缀）及Session（id、过期时间、回收、传送ID）初始化
 *   public static myReq::check($idx) — 检测相关环境变量
 *   public static myReq::getValue($type = 'get', $para = null, $format = '') — 获取相关相应值
 *   public static myReq::get($para = '', $format = 'str') — 读取_GET数据
 *   public static myReq::post($para = '', $format = 'str') — 读取_POST数据
 *   public static myReq::files($para = '') — 读取_FILES数据
 *   public static myReq::request($para = '', $format = 'str') — 读取_REQUEST数据
 *   public static myReq::server($para = '', $format = '!') — 读取_SERVER数据
 *   public static myReq::env($para) — 读取_ENV数据
 *   public static myReq::globals($name, $value = null) — 读取GLOABLS数据
 *   public static myReq::ip() — 返回客户端IP
 *   public static myReq::setTrustedProxies(array $addresses) — 登记可采信转发头的代理IP列表
 *   public static myReq::trustedProxy() — 检查当前REMOTE_ADDR是否精确命中可信代理列表
 *   public static myReq::ping($address, &$info = '', $timeout = 3) — IP地址连通测试 (IP:PORT)
 *   public static myReq::pingURL($url, &$detail) — 获取某个URL的链接时间，$detail返回相关时间参数（其他参数请自行var_export）如下：
 *   public static myReq::setCookieOpt($setting) — 设置COOKIE参数
 *   public static myReq::setCookie($name, $value = '', $expire = 0, $path = '', $domain = '', $httponly = true) — 设置COOKIE（带前缀）
 *   public static myReq::setCookie_nopre($name, $value = '', $expire = 0, $path = '', $domain = '') — 设置COOKIE（不带前缀）
 *   public static myReq::removeCookie($name, $pre = true) — 删除COOKIE
 *   public static myReq::cookie($para = '', $pre = true) — 读取COOKIE
 *   public static myReq::setSessionOpt($setting) — 设置SESSION选项；缺失name时使用当前原生名称，不读取未定义键。
 *   public static myReq::sessionStart($handle = array(), $httponly = true) — 启动SESSION，启用严格ID校验并接入可选validateId/updateTimestamp回调。
 *   public static myReq::sessionRestart($id = '', $delete_old_session = true) — 轮换活动SESSION的ID并保留数据；不做多余的关闭/重读，避免严格模式丢失新ID。
 *   public static myReq::sessionEnd() — 清除会话数据并销毁存储，使用原Cookie路径、域和安全属性删除浏览器Cookie。
 *   public static myReq::session($key='', $value=null) — 读取SESSION
 *   public static myReq::removeSession($key) — 删除SESSION
 *   public static myReq::setSessions($data) — 将当前编码器的会话源串批量合并到活动会话，并成功后轮换ID。
 *   public static myReq::sessionId() — 获取SESSION ID
 *   public static myReq::sessionCheck() — 检测SESSION是否已启动
 *   public static myReq::setSessionPath($path = '') — 设置SESSION保存路径
 *   public static myReq::setSessionName($name = '') — 设置SESSION名称
 *   public static myReq::sessionEncode($array, $safe = true) — Session 编码
 *   public static myReq::sessionDecode($str, $allowed_classes = false) — Session 解码
 *
 * 使用说明：
 *   1. 应用通过get/post/request/cookie/session等入口读取和设置数据。
 *   2. 过滤格式和特殊键按方法说明使用，返回值可能保留数组或null。
 *   3. Session处理器选择与Cookie选项应在启动前配置；no_log入口按设计跳过会话。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名以PHP 8.5.12为运行基线，PHPDoc类型用于说明，不额外强制运行时类型。
 */


/**
 * 功能说明：统一请求过滤、环境读取、可信代理IP判断及会话生命周期处理。
 *
 * 使用说明：
 *   1. 应用通过get/post/request/cookie/session等入口读取和设置数据。
 *   2. 过滤格式和特殊键按方法说明使用，返回值可能保留数组或null。
 *   3. Session处理器选择与Cookie选项应在启动前配置；no_log入口按设计跳过会话。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 *
 * @phpstan-type CookieOptions array{prefix?:string,path?:string,domain?:string}
 * @phpstan-type SessionOptions array{mode?:string,id?:string,expire?:int,gc?:bool,trans_sid?:bool,path?:string,name?:string}
 * @example ../app/Document/module/myReq.php 框架现有演示，仅作为文档引用，不在检查时执行
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
     * 功能说明：Cookie（路径、域名和前缀）及Session（id、过期时间、回收、传送ID）初始化
     *
     * @param array $cookie_opt Cookie前缀、有效期、路径、域及安全属性等默认选项（默认：array()）
     * @param array $session_opt 会话处理器、Cookie、名称、路径和生命周期等选项（默认：array()）
     * @return void 无返回值。
     * @phpstan-param CookieOptions $cookie_opt
     * @phpstan-param SessionOptions $session_opt
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
     * 功能说明：检测相关环境变量
     *
     * @param string $idx 要检查的请求来源/条件标记，例如post
     * @return int 指定超全局数组/Countable对象的元素数量；不存在或不可计数时为0
     */
    public static function check($idx) {
        $idx = '_'.strtoupper($idx);
        global $$idx;
        return is_array($$idx) || $$idx instanceof Countable ? count($$idx) : 0;
    }

    /**
     * 功能说明：获取相关相应值
     *
     *
     * 参数约定：空format对id或以_id结尾的字符串字段采用数值转换；ALL分支除!外使用递归HTML转义，不执行单项回调。GLOBAL分支将值导入$GLOBALS并返回true。
     *
     * @param string $type 输入来源：get/post/request/files/cookie/session/server/env等（默认：'get'）
     * @param string|int|null $para 字段名或[A]/[ALL]/[G]/[GLOBAL]特殊键，null读取全部（默认：null）
     * @param string|callable $format !/int/url/char/str、字符白名单或转换回调；回调接收单项字符串并返回任意值；ALL分支另按HTML规则处理（默认：''）
     * @return mixed 单项为过滤后的原值/回调结果，缺失为null；ALL返回数组，GLOBAL导入分支返回true
     */
    public static function getValue($type = 'get', $para = null, $format = '') {
        $type = strtolower($type);
        $var = null;
        switch($type) {
            case 'env':
                $var = &$_ENV;
                break;
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
                $var = $GLOBALS; // PHP8禁止引用整个GLOBALS；此处只读取快照，写入仍通过globals()。
                break;
        }
        if(is_null($para)) $para = '[ALL]';
        if($para=='[G]' || $para=='[GLOBAL]') {
            foreach (($var ?? []) as $key => $value) {
                if (!empty($format)) $value = recursionFunction('htmlspecialchars', $value);
                self::globals($key, $value);
            }
        }elseif($para=='[A]' || $para=='[ALL]') {
            return $format === '!' ? ($var ?? []) : recursionFunction('htmlspecialchars', $var ?? []);
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
                        case '':
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
                            } else {
                                $result = preg_replace('/[^'.$format.']/i', '', $result);
                            }
                            break;
                    }
                }
            }
            return $result;
        }
        return true;
    }

    /**
     * 功能说明：读取_GET数据
     *
     * @param string|int|null $para 请求字段名称；null或[ALL]按入口约定读取全部（默认：''）
     * @param string|callable $format !/int/url/char/str、字符白名单或转换回调；回调接收单项字符串并返回任意值；ALL分支另按HTML规则处理（默认：'str'）
     * @return mixed GET单项过滤结果，缺失为null；空键/ALL返回数组，GLOBAL模式可返回true
     */
    public static function get($para = '', $format = 'str') {
        if($para === '') {
            return self::getValue('get', '[ALL]', $format);
        } else {
            return self::getValue('get', $para, $format);
        }
    }

    /**
     * 功能说明：读取_POST数据
     *
     * @param string|int|null $para 请求字段名称；null或[ALL]按入口约定读取全部（默认：''）
     * @param string|callable $format !/int/url/char/str、字符白名单或转换回调；回调接收单项字符串并返回任意值；ALL分支另按HTML规则处理（默认：'str'）
     * @return mixed POST单项过滤结果，缺失为null；空键/ALL返回数组，GLOBAL模式可返回true
     */
    public static function post($para = '', $format = 'str') {
        if($para === '') {
            return self::getValue('post', '[ALL]', $format);
        } else {
            return self::getValue('post', $para, $format);
        }
    }

    /**
     * 功能说明：读取_FILES数据
     *
     * @param string|int|null $para 条目名称，null按入口约定读取全部（默认：''）
     * @return mixed 上传字段的原始结构；空键返回全部字段数组，缺失字段为null
     */
    public static function files($para = '') {
        if(empty($para)) {
            return self::getValue('files', '[ALL]', '!');
        } else {
            return self::getValue('files', $para, '!');
        }
    }

    /**
     * 功能说明：读取_REQUEST数据
     *
     * @param string|int|null $para 请求字段名称；null或[ALL]按入口约定读取全部（默认：''）
     * @param string|callable $format !/int/url/char/str、字符白名单或转换回调；回调接收单项字符串并返回任意值；ALL分支另按HTML规则处理（默认：'str'）
     * @return mixed REQUEST单项过滤结果，缺失为null；空键/ALL返回数组，GLOBAL模式可返回true
     */
    public static function request($para = '', $format = 'str') {
        if($para === '') {
            return self::getValue('request', '[ALL]', $format);
        } else {
            return self::getValue('request', $para, $format);
        }
    }

    /**
     * 功能说明：读取_SERVER数据
     *
     * @param string|int|null $para 请求字段名称；null或[ALL]按入口约定读取全部（默认：''）
     * @param string|callable $format !/int/url/char/str、字符白名单或转换回调；回调接收单项字符串并返回任意值；ALL分支另按HTML规则处理（默认：'!'）
     * @return mixed SERVER字段的过滤结果，缺失为null；空键/ALL返回全部数据
     */
    public static function server($para = '', $format = '!') {
        if(empty($para)) return '';
        $para = strtoupper($para);
        $return = self::getValue('server', $para, $format);
        if($return === null) $return = self::getValue('server', 'HTTP_'.$para, $format);
        if($return === null) { $value = self::env($para); $return = $value === false || $value === '' ? null : $value; }
        return $return;
    }

    /**
     * 功能说明：读取_ENV数据
     *
     * @param string $para 环境变量名称；优先读取$_ENV，缺失时调用getenv
     * @return string|array|false 环境变量值；getenv支持的形式可返回数组，变量不存在为false
     */
    public static function env($para) {
        return $_ENV[$para] ?? getenv($para);
    }

    /**
     * 功能说明：读取GLOABLS数据
     *
     * null为读取标记，因此此入口不用于把全局变量设置为null。
     *
     * @param string $name 全局变量名
     * @param mixed $value null表示读取；非null值写入$GLOBALS，包括false和0（默认：null）
     * @return mixed 读取时为全局变量原值/缺失null；非null写入完成返回true
     */
    public static function globals($name, $value = null) {
        if(is_null($value)) return self::getValue('globals', $name);
        $GLOBALS[$name] = $value;
        return true;
    }

    /**
     * 功能说明：返回客户端IP
     *
     * 参数说明：无显式参数。
     *
     * @return string 验证后的客户端IP；来自可信代理时检查转发链，REMOTE_ADDR无效时返回空串
     */
    public static function ip() {
        // Forwarded addresses are accepted only from explicitly trusted peers.
        $peer = $_SERVER['REMOTE_ADDR'] ?? '';
        if(!filter_var($peer, FILTER_VALIDATE_IP)) return '';
        if(in_array($peer, self::$trusted_proxies, true)) {
            $chain = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['HTTP_X_REAL_IP'] ?? ''));
            for($i=count($chain)-1; $i>=0; $i--) {
                $address = trim($chain[$i]);
                if(!filter_var($address, FILTER_VALIDATE_IP)) return $peer;
                if(!in_array($address, self::$trusted_proxies, true)) return $address;
            }
        }
        return $peer;
    }

    protected static $trusted_proxies = [];

    /**
     * 功能说明：登记可采信转发头的代理IP列表
     *
     * @param array $addresses 可信代理的完整IPv4/IPv6地址列表；不接受CIDR网段
     * @return void 无返回值。
     * @throws InvalidArgumentException 列表包含非法IP地址
     */
    public static function setTrustedProxies(array $addresses) {
        foreach($addresses as $address) if(!filter_var($address, FILTER_VALIDATE_IP)) throw new InvalidArgumentException('Invalid trusted proxy address');
        self::$trusted_proxies = $addresses;
    }

    /**
     * 功能说明：检查当前REMOTE_ADDR是否精确命中可信代理列表
     *
     * 参数说明：无显式参数。
     *
     * @return bool REMOTE_ADDR与可信代理列表中的IP完全相同为true
     */
    public static function trustedProxy() {
        return in_array($_SERVER['REMOTE_ADDR'] ?? '', self::$trusted_proxies, true);
    }

    /**
     * 功能说明：IP地址连通测试 (IP:PORT)
     *
     * @param string $address 要探测的主机名或IP
     * @param string &$info 引用输出：连接失败信息；成功时清空（默认：''）
     * @param int|float $timeout 最长等待/执行时间，单位秒（默认：3）
     * @return float|false TCP连接耗时秒数；连接失败为false，详情写入info
     */
    public static function ping($address, &$info = '', $timeout = 3) {
        $value = strpos($address, '://') === false ? 'tcp://'.$address : $address;
        $parts = parse_url($value);
        if(!$parts || empty($parts['host']) || preg_match('/[\r\n\x00]/', $parts['host'])) { $info = 'Invalid address'; return false; }
        $host = trim($parts['host'], '[]');
        $port = $parts['port'] ?? (($parts['scheme'] ?? '') === 'https' ? 443 : 80);
        $target = 'tcp://'.(strpos($host, ':') !== false ? '['.$host.']' : $host).':'.$port;
        $start = microtime(true);
        $socket = @stream_socket_client($target, $code, $info, max(0.1, (float)$timeout));
        if(!$socket) return false;
        fclose($socket); $info = '';
        return microtime(true)-$start;
    }

    /**
     * 功能说明：获取某个URL的链接时间，$detail返回相关时间参数（其他参数请自行var_export）如下：
     *
     *  total_time - 获得用秒表示的上一次传输总共的时间，包括DNS解析、TCP连接等。
     *  namelookup_time - 获得用秒表示的从最开始到域名解析完毕的时间。
     *  connect_time - 获得用秒表示的从最开始直到对远程主机（或代理）的连接完毕的时间。
     *  pretransfer_time - 获得用秒表示的从最开始直到文件刚刚开始传输的时间。
     *  starttransfer_time - 获得用秒表示的从最开始到第一个字节被curl收到的时间。
     *  redirect_time - 获得所有用秒表示的包含了所有重定向步骤的时间，包括DNS解析、连接、传输前（pretransfer)和在最后的一次传输开始之前。
     *
     * @param string $url 目标URL或需要处理的链接
     * @param array &$detail 引用输出：curl_getinfo完整连接统计，含http_code/total_time等
     * @return float|false 连接总耗时秒数；HTTP代码为0时返回false，统计通过detail输出
     * @phpstan-param array<string,mixed> $detail
     */
    public static function pingURL($url, &$detail) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER , 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_exec($ch);
        $detail = curl_getinfo($ch);
        return $detail['http_code']==0?false:$detail['total_time'];
    }

    /**
     * 功能说明：设置COOKIE参数
     *
     * @param array $setting 当前功能使用的设置项
     * @return void 无返回值。
     * @phpstan-param CookieOptions $setting
     */
    public static function setCookieOpt($setting) {
        self::$cookie_opt = $setting + self::$cookie_opt;
    }

    /**
     * 功能说明：设置COOKIE（带前缀）
     *
     * @param string $name Cookie名称，是否添加框架前缀由入口/参数决定
     * @param mixed $value 需要写入或设置的数据值（默认：''）
     * @param int $expire 相对当前请求时间的有效秒数；非正值发送过期Cookie用于删除（默认：0）
     * @param string $path Cookie路径；空串采用配置路径，仍为空时使用/（默认：''）
     * @param string $domain Cookie作用域域名，空串使用当前默认值（默认：''）
     * @param bool $httponly 是否禁止浏览器脚本访问Cookie（默认：true）
     * @return bool Set-Cookie是否成功发送，不代表浏览器最终保存；已发送响应头时为false
     */
    public static function setCookie($name, $value = '', $expire = 0, $path = '', $domain = '', $httponly = true) {
        if(headers_sent($file, $line)) {
            trigger_error('Content is already been sent at $line line of "'.$file.'"!');
            return false;
        }
        if($expire<=0) {
            $expire = self::server('REQUEST_TIME') - 3600;
        } else {
            $expire = self::server('REQUEST_TIME') + $expire;
        }
        if(empty($path) && !empty(self::$cookie_opt['path'])) {
            $path = self::$cookie_opt['path'];
        } elseif(empty($path)) {
            $path = '/';
        }
        if(empty($domain) && !empty(self::$cookie_opt['domain'])) $domain = self::$cookie_opt['domain'];
        $_COOKIE[self::$cookie_opt['prefix'].$name] = $value;
        return setcookie(self::$cookie_opt['prefix'].$name, $value, ['expires'=>$expire, 'path'=>$path, 'domain'=>$domain, 'secure'=>isHttps(), 'httponly'=>$httponly, 'samesite'=>'Lax']);
    }

    /**
     * 功能说明：设置COOKIE（不带前缀）
     *
     * @param string $name Cookie名称，是否添加框架前缀由入口/参数决定
     * @param mixed $value 需要写入或设置的数据值（默认：''）
     * @param int $expire 相对当前请求时间的有效秒数；非正值发送过期Cookie用于删除（默认：0）
     * @param string $path Cookie路径；空串采用配置路径，仍为空时使用/（默认：''）
     * @param string $domain Cookie作用域域名，空串使用当前默认值（默认：''）
     * @return bool 底层Cookie设置是否成功
     */
    public static function setCookie_nopre($name, $value = '', $expire = 0, $path = '', $domain = '') {
        $pre = self::$cookie_opt['prefix'];
        self::$cookie_opt['prefix'] = '';
        try { return self::setCookie($name, $value, $expire, $path, $domain); }
        finally { self::$cookie_opt['prefix'] = $pre; }
    }

    /**
     * 功能说明：删除COOKIE
     *
     * @param string $name Cookie名称，是否添加框架前缀由入口/参数决定
     * @param bool $pre 是否自动添加已配置的Cookie名前缀（默认：true）
     * @return bool 过期Cookie是否成功发送
     */
    public static function removeCookie($name, $pre = true) {
        if($pre) $name = self::$cookie_opt['prefix'].$name;
        return self::setCookie_nopre($name);
    }

    /**
     * 功能说明：读取COOKIE
     *
     * @param string|int|null $para 条目名称，null按入口约定读取全部（默认：''）
     * @param bool $pre 是否自动添加已配置的Cookie名前缀（默认：true）
     * @return mixed 指定Cookie值或全部Cookie数组；字段不存在为null，默认读取原始值
     */
    public static function cookie($para = '', $pre = true) {
        if(empty($para)) {
            self::getValue('cookie');
            return count($_COOKIE);
        } else {
            if($pre) $para = self::$cookie_opt['prefix'].$para;
            return self::getValue('cookie', $para, '!');
        }
    }

    /**
     * 功能说明：设置SESSION选项；缺失name时使用当前原生名称，不读取未定义键。
     *
     *
     * 单位约定：expire为分钟，启动时乘60交给gc_maxlifetime及Cookie生命周期；数据库/缓存的TTL仍采用各自方法说明中的秒。
     *
     * @param array $setting id/expire（分钟）/gc/mode/trans_sid/path/name
     * @return void 无返回值。
     * @throws InvalidArgumentException 会话设置不是数组
     * @phpstan-param SessionOptions $setting
     */
    public static function setSessionOpt($setting) {
        if(!is_array($setting)) throw new InvalidArgumentException('Session options must be an array.');
        if(!isset($setting['mode']) || $setting['mode'] != 'user') $setting['mode'] = 'files';
        self::$session_opt = $setting + self::$session_opt;
        self::$session_opt['expire'] = max(1, (int)self::$session_opt['expire']);
        $name = $setting['name'] ?? session_name();
        $cookie = self::$cookie_opt['prefix'] !== '' ? self::cookie($name) : null; // 原生未加前缀Cookie由PHP自行读取
        if(is_string($cookie) && preg_match('/^[a-zA-Z0-9,-]{1,128}$/D', $cookie) && empty(self::$session_opt['id'])) self::$session_opt['id'] = $cookie;
        if(isset($setting['path'])) self::setSessionPath($setting['path']);
        if(isset($setting['name'])) self::setSessionName($setting['name']);
    }

    /**
     * 功能说明：启动SESSION，启用严格ID校验并接入可选validateId/updateTimestamp回调。
     *
     *
     * 资源生命周期：成功后PHP在请求结束时写回并关闭会话；需要提前释放同ID请求锁时由调用方使用session_write_close。
     * 处理器结构：可传原生SessionHandlerInterface对象；字符串可为内置files或静态处理器类名；数组前6项依次为open/close/read/write/destroy/gc，9项模式追加create_sid/validate_sid/update_timestamp；空数组使用原生files；静态/回调协议经mySessionHandler桥接后注册原生对象。
     *
     * @param string|array|SessionHandlerInterface $handle 处理器类名、6个原回调或9个完整回调；空数组使用原生files（默认：array()）
     * @param bool $httponly Cookie是否禁止脚本读取（默认：true）
     * @return string|false 成功返回实际ID；no_log禁用、处理器无效或启动失败返回false，不静默切换存储
     * @throws InvalidArgumentException 显式选择的Redis存储插件未安装、未启用或PHP扩展不可用
     * @phpstan-param string|list<callable>|SessionHandlerInterface $handle
     */
    public static function sessionStart($handle = array(), $httponly = true) {
        if(!empty($GLOBALS['no_log']) || self::sessionCheck() || headers_sent()) return false;
        $callbacks = [];
        if(is_string($handle) && $handle !== '' && $handle !== 'files') {
            if(strtolower($handle)==='sess_redis')myStep::storagePlugin('redis');
            if(!class_exists($handle)) return false;
            foreach(['open','close','read','write','destroy','gc'] as $method) {
                if(!is_callable([$handle, $method])) return false;
                $callbacks[] = [$handle, $method];
            }
            if(is_callable([$handle, 'validateId'])) {
                $callbacks[] = function() { return bin2hex(random_bytes(24)); }; // PHP 7.4要求第7项为有效回调
                $callbacks[] = [$handle, 'validateId'];
                $callbacks[] = is_callable([$handle, 'updateTimestamp']) ? [$handle, 'updateTimestamp'] : [$handle, 'write'];
            }
        } elseif(is_array($handle) && $handle) {
            $callbacks = array_values($handle);
            if(!in_array(count($callbacks), [6,9], true)) return false;
            foreach($callbacks as $index => $callback) {
                if(!is_callable($callback)) return false;
            }
        } elseif(!is_array($handle) && !($handle instanceof SessionHandlerInterface) && $handle !== 'files' && $handle !== '') return false;
        if($callbacks || $handle instanceof SessionHandlerInterface) {
            require_once __DIR__.'/session/mySessionHandler.class.php';
            $handler=$handle instanceof SessionHandlerInterface ? $handle : new mySessionHandler($callbacks);
            if(!session_set_save_handler($handler,false)) return false;
            self::$session_opt['mode'] = 'user';
        } else {
            session_module_name('files');
            self::$session_opt['mode'] = 'files';
        }
        $id = self::$session_opt['id'];
        if(is_string($id) && preg_match('/^[a-zA-Z0-9,-]{1,128}$/D', $id)) session_id($id);
        self::$session_opt['id'] = ''; // 客户端ID仅用于本次启动，轮换后不能恢复旧ID
        ini_set('session.use_strict_mode', '1');
        ini_set('session.gc_maxlifetime', self::$session_opt['expire'] * 60);
        ini_set('session.gc_probability', self::$session_opt['gc'] ? '5' : '0');
        ini_set('session.gc_divisor', '100');
        ini_set('session.use_trans_sid', self::$session_opt['trans_sid'] ? '1' : '0');
        ini_set('session.use_only_cookies', self::$session_opt['trans_sid'] ? '0' : '1');
        if(self::$session_opt['trans_sid']) {
            ini_set('session.use_cookies', '0');
            ini_set('url_rewriter.tags', 'a=href,area=href,script=src,link=href,frame=src,input=src,form=fakeentry');
        } else {
            ini_set('session.use_cookies', '1');
            session_set_cookie_params(['lifetime'=>self::$session_opt['expire']*60, 'path'=>self::$cookie_opt['path'] ?: '/', 'domain'=>self::$cookie_opt['domain'], 'secure'=>isHttps(), 'httponly'=>$httponly, 'samesite'=>'Lax']);
        }
        session_cache_limiter('private,must-revalidate');
        session_cache_expire(self::$session_opt['expire']);
        // Redis处理器已保留安全错误摘要；将PHP存储警告交给上层统一提示，避免原始警告提前中断认证流程。
        $started=is_string($handle) && strtolower($handle)==='sess_redis' ? @session_start() : session_start();
        if(!$started) return false;
        function_exists('session_register_shutdown') ? session_register_shutdown() : register_shutdown_function('session_write_close');
        return session_id();
    }

    /**
     * 功能说明：轮换活动SESSION的ID并保留数据；不做多余的关闭/重读，避免严格模式丢失新ID。
     *
     * @param string $id 兼容指定目标ID；须有效且满足PHP严格模式，不保证采用未知ID（默认：''）
     * @param bool $delete_old_session 是否删除旧ID存储，默认true（默认：true）
     * @return string|false 非活动会话、非法ID或轮换失败返回false
     */
    public static function sessionRestart($id = '', $delete_old_session = true) {
        if(!self::sessionCheck() || headers_sent()) return false;
        if($id !== '' && (!is_string($id) || !preg_match('/^[a-zA-Z0-9,-]{1,128}$/D', $id))) return false;
        if(!session_regenerate_id($delete_old_session)) return false;
        if($id === '') return session_id();
        // 指定目标沿用历史“切换到该会话”的语义；严格模式可能替换不存在的ID。
        if(!session_write_close()) return false;
        session_id($id);
        return session_start() ? session_id() : false;
    }

    /**
     * 功能说明：清除会话数据并销毁存储，使用原Cookie路径、域和安全属性删除浏览器Cookie。
     *
     * 参数说明：无显式参数。
     *
     * @return bool 非活动会话视为已关闭；存储或Cookie删除失败返回false
     */
    public static function sessionEnd() {
        if(!self::sessionCheck()) return true;
        $name = session_name();
        $params = session_get_cookie_params();
        $_SESSION = array();
        $ok = session_destroy();
        self::$session_opt['id'] = '';
        unset($_COOKIE[$name]);
        if(ini_get('session.use_cookies') && !headers_sent()) {
            $cookie = ['expires'=>time()-42000, 'path'=>$params['path'], 'domain'=>$params['domain'], 'secure'=>$params['secure'], 'httponly'=>$params['httponly'], 'samesite'=>$params['samesite'] ?? 'Lax'];
            $ok = setcookie($name, '', $cookie) && $ok;
        }
        session_id('');
        return $ok;
    }

    /**
     * 功能说明：读取SESSION
     *
     * 沿用历史空键和null语义：session()返回序列化文本；session($key,null)读取该键。需要批量恢复会话源串时使用setSessions。
     *
     * @param string|null $key 会话键名；空串/null返回完整会话序列化文本；整数键被拒绝（默认：''）
     * @param mixed $value null表示读取，非null值写入；此入口不能用null作为待保存值（默认：null）
     * @return mixed 空键为会话序列化文本；有键且value为null时读取原值；写入/未启动/整数键返回null
     */
    public static function session($key='', $value=null) {
        if(!self::sessionCheck() || is_int($key)) {
            return null;
        } elseif(empty($key)) {
            return session_encode();
        } else {
            if(is_null($value)) {
                return self::getValue('session', $key, '!');
            } else {
                $_SESSION[$key] = $value;
            }
        }
        return null;
    }

    /**
     * 功能说明：删除SESSION
     *
     * @param string $key 需要删除的会话键名
     * @return void 无返回值。
     */
    public static function removeSession($key) {
        if(!self::sessionCheck() || is_int($key)) return;
        unset($_SESSION[$key]);
    }

    /**
     * 功能说明：将当前编码器的会话源串批量合并到活动会话，并成功后轮换ID。
     *
     * @param string $data 与session.serialize_handler一致的原始数据
     * @return array|false|null 成功返回原会话数组；坏数据返回false并保留原状态
     */
    public static function setSessions($data) {
        if(!self::sessionCheck()) return;
        if(!is_string($data)) return false;
        $old_session = $_SESSION;
        $format = ini_get('session.serialize_handler');
        // 原生session_decode遇到损坏数据会销毁活动会话，常用格式先做无对象实例化校验。
        if($format === 'php' && self::sessionDecode($data) === false) return false;
        if($format === 'php_serialize' && !is_array(@unserialize($data, ['allowed_classes'=>false]))) return false;
        if(!@session_decode($data)) {
            if(!self::sessionCheck()) {
                session_id('');
                if(!session_start()) return false;
            }
            $_SESSION = $old_session;
            return false;
        }
        if(self::sessionRestart() === false) {
            $_SESSION = $old_session;
            return false;
        }
        return $old_session;
    }

    /**
     * 功能说明：获取SESSION ID
     *
     * 参数说明：无显式参数。
     *
     * @return string 当前PHP Session ID，未分配时为空串
     */
    public static function sessionId() {
        return session_id();
    }

    /**
     * 功能说明：检测SESSION是否已启动
     *
     * 参数说明：无显式参数。
     *
     * @return bool PHP会话状态为PHP_SESSION_ACTIVE时为true
     */
    public static function sessionCheck() {
        return function_exists('session_status') ? (session_status()==PHP_SESSION_ACTIVE ) : ( !empty(session_id()) );
    }

    /**
     * 功能说明：设置SESSION保存路径
     *
     * @param string $path 目标目录或路径（默认：''）
     * @return string 设置完成后读取的当前Session目录；会话已启动、非files模式或目录准备失败时保留现有路径
     */
    public static function setSessionPath($path = '') {
        if($path !== '' && defined('ROOT')) { require_once __DIR__.'/myPath.class.php'; $path = myPath::absolute($path); }
        if(empty($path) || self::sessionCheck()) {
            return session_save_path();
        } else {
            if(self::$session_opt['mode']=='files' && (is_dir($path) || @mkdir($path, 0700, true))) {
                $path = realpath($path);
                session_save_path($path);
            }
            return session_save_path();
        }
    }

    /**
     * 功能说明：设置SESSION名称
     *
     * @param string $name 新的Session名称；空串只读取当前名称（默认：''）
     * @return string|false 空名称或活动会话时读取当前名称；设置新名称成功返回旧名称，PHP拒绝设置时为false
     */
    public static function setSessionName($name = '') {
        if(empty($name) || self::sessionCheck()) {
            return session_name();
        } else {
            return session_name($name);
        }
    }

    /**
     * 功能说明：Session 编码
     *
     * @param array $array 需要编码或转换的数组
     * @param bool $safe true先通过PHP序列化复制输入数组，再编码为php会话格式（默认：true）
     * @return string php会话编码字节串；数组键作为会话字段名，不是JSON或php_serialize格式
     */
    public static function sessionEncode($array, $safe = true) {
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

    /**
     * 功能说明：Session 解码
     *
     * 解析php会话序列化格式；默认禁止恢复对象，不等同于PHP原生任意序列化格式的解码。
     *
     * @param string $str 待处理的字符串
     * @param bool|array $allowed_classes 反序列化允许恢复的类策略，false禁用对象恢复（默认：false）
     * @return array|false 解码后的会话键值；格式错误为false
     */
    public static function sessionDecode($str, $allowed_classes = false) {
        $str = (string)$str;
        $endptr = strlen($str);
        $p = 0;
        $serialized = '';
        $items = 0;
        $level = 0;
        while($p < $endptr) {
            $q = $p;
            while($str[$q] != '|') {
                if(++$q >= $endptr) return false;
            }
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
                    if($q >= $endptr || $level > 128) return false;
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
                        case 'r':
                        case 'R': /* reference    */
                            $q+= 2;
                            for($id = ''; ($q < $endptr) && ($str[$q] != ';'); $q++) $id .= $str[$q];
                            $q++;
                            if(!ctype_digit($id)) return false;
                            $serialized .= $str[$p].':' . ($id + 1) . ';'; /* increment pointer because of outer array */
                            if($level == 0) break 2;
                            break;
                        case 's': /* string */
                            $q+=2;
                            for($length=''; ($q < $endptr) && ($str[$q] != ':'); $q++) $length .= $str[$q];
                            $q+=2;
                            if(!ctype_digit($length) || (float)$length > $endptr-$q) return false;
                            $q+= (int)$length + 2;
                            $serialized .= substr($str, $p, $q - $p);
                            if($level == 0) break 2;
                            break;
                        case 'a': /* array */
                        case 'O': /* object */
                            do $q++;
                            while ( ($q < $endptr) && ($str[$q] != '{') );
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
            if($q > $endptr || $level !== 0) return false;
            $items++;
            $p = $q;
        }
        return @unserialize('a:' . $items . ':{' . $serialized . '}', ['allowed_classes'=>$allowed_classes]);
    }
}