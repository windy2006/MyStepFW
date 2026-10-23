<?php
/*******************************************
*                                          *
* Name    : Request Security Tools (new)   *
* Modifier: MyStep Framework               *
* Time    : 2026-10-15                     *
* Email   : windy2006@gmail.com            *
* HomePage: www.mysteps.cn                 *
* Notice  : U Can Use & Modify it freely,  *
*           BUT PLEASE HOLD THIS ITEM.     *
*                                          *
*******************************************/

/**
 * 文件：lib/mySecurity.class.php
 * 功能说明：请求边界与输出安全辅助
 *   提供启动期输入预算、CSRF令牌、同源判定、危险请求拦截及HTML表单和链接保护。
 *
 * 方法目录：
 *   public static mySecurity::inspectRequest(array $server, array $input, array $limits = []) — 有预算地检查已解析输入和声明体积，返回拒绝原因。
 *   private static mySecurity::requestShape($value, $depth, array &$remaining, array $limits) — 在固定深度和累计预算内检查输入，不复制或序列化请求内容。
 *   public static mySecurity::guardRequest() — 在框架应用和会话启动前拒绝超预算请求，不把它当作PHP解析器补丁。
 *   public static mySecurity::token() — 获取当前会话的 CSRF 令牌，缺失或格式异常时重新生成
 *   public static mySecurity::valid($token) — 校验提交的 CSRF 令牌是否与当前会话中保存的令牌一致
 *   public static mySecurity::origins() — 汇总允许访问当前应用的来源地址，并去重返回
 *   public static mySecurity::trustedReferer($referer) — 检查 Referer 中的来源是否与允许列表精确匹配
 *   public static mySecurity::originAllowed() — 根据 Origin 和浏览器 Fetch Metadata 判断当前请求来源是否允许
 *   public static mySecurity::adminPaths() — 获取用于识别内置后台入口的路径前缀
 *   public static mySecurity::administrativeContext() — 判断当前路由是否从已知后台路径前缀开始
 *   public static mySecurity::mutates($url) — 识别 URL 是否对应已知的内置状态修改操作
 *   public static mySecurity::guard() — 在业务处理前执行请求方法、来源和会话 CSRF 令牌检查
 *   public static mySecurity::respond() — 向客户端提供当前应用会话的 CSRF 令牌，并结束请求
 *   public static mySecurity::decorate($html) — 为已渲染的完整 HTML 页面注入会话令牌和前端安全适配脚本
 *
 * 使用说明：
 *   1. 框架init()自动调用guardRequest()检查已解析输入；启动前的PHP/Web限制另行配置。
 *   2. 在业务处理前调用 guard()；写请求通过 X-CSRF-Token 或 _ms_csrf 携带会话令牌。
 *   3. CSRF检查前应已启动目标应用会话，并准备好框架配置、域名及路由上下文。
 *   4. 在模板渲染或缓存读取后调用 decorate()；装饰结果不得写回共享页面缓存。
 *
 * 说明：目录列出本文件直接声明的全部方法；参数、返回值及异常详见各方法 PHPDoc。
 *       原生签名保持 PHP 7.4 兼容，PHPDoc 类型仅用于说明。
 */


/**
 * 功能说明：提供启动期输入预算及请求入口和最终HTML输出阶段的会话安全保护。
 *
 * 使用说明：
 *   1. guardRequest()不依赖会话；CSRF等方法依赖已启动的Session及框架配置、域名和路由。
 *   2. 写请求携带目标应用会话的 CSRF 令牌，不能假定不同应用共享同一令牌。
 *   3. decorate() 在页面缓存读取后执行，装饰结果不得写入共享缓存。
 *   4. 来源、动作识别及 CSRF 校验不替代登录、权限和资源归属检查。
 *   5. 自定义应用和插件应限制状态修改的 HTTP 方法，并完成业务授权。
 *
 * 方法目录：参见本文件顶部。
 */
class mySecurity {
    /**
     * 功能说明：有预算地检查已解析输入和声明体积，返回拒绝原因。
     *
     * 仅检查Content-Length及PHP已经生成的输入数组，不读取php://input、临时上传文件或
     * 反序列化内容。缺少声明长度时仍检查输入结构，但无法据此限制原始或解码请求体。
     * PHP解析请求可能早于此检查；本方法不修复PHP内核漏洞，也不能计算原始multipart分段数。
     *
     * @param array $server 请求服务器变量，只读取CONTENT_LENGTH
     * @param array $input GET、POST、COOKIE、FILES等来源数组组成的集合，不修改原数组
     * @param array $limits depth/nodes/bytes/body_bytes的正整数覆盖值（默认：[]）
     * @return array 无问题为[]；拒绝时返回status及固定error文字，不回显输入
     * @throws InvalidArgumentException 受信预算配置无效；深度不得超过64
     */
    public static function inspectRequest(array $server, array $input, array $limits = []) {
        $defaults=['depth'=>32,'nodes'=>4096,'bytes'=>8388608,'body_bytes'=>33554432];
        if(array_diff_key($limits,$defaults)) throw new InvalidArgumentException('Unknown request limit');
        $limits=array_replace($defaults,$limits);
        foreach($limits as $value) if(!is_int($value) || $value<1) throw new InvalidArgumentException('Request limits require positive integers');
        if($limits['depth']>64) throw new InvalidArgumentException('Request depth exceeds supported limit');
        if(isset($server['CONTENT_LENGTH']) && $server['CONTENT_LENGTH']!=='') {
            $length=$server['CONTENT_LENGTH'];
            if((!is_int($length) && !is_string($length)) || !preg_match('/\A[0-9]+\z/D',(string)$length)) return ['status'=>400,'error'=>'Invalid request length'];
            $length=ltrim((string)$length,'0');if($length==='')$length='0';$maximum=(string)$limits['body_bytes'];
            if(strlen($length)>strlen($maximum) || (strlen($length)===strlen($maximum) && strcmp($length,$maximum)>0)) return ['status'=>413,'error'=>'Request body exceeds configured limit'];
        }
        $remaining=['nodes'=>$limits['nodes'],'bytes'=>$limits['bytes']];
        foreach($input as $value) if(!self::requestShape($value,0,$remaining,$limits)) return ['status'=>413,'error'=>'Request input exceeds configured limits'];
        return [];
    }

    /**
     * 功能说明：在固定深度和累计预算内检查输入，不复制或序列化请求内容。
     *
     * 使用strlen取得字节长度，先与剩余额度比较再相减，避免累计整数溢出；循环引用
     * 同样受到深度预算限制。只接受数组、字符串及普通标量，不调用对象的字符串转换。
     *
     * @param mixed $value 当前输入节点，数组按写时复制语义只读遍历
     * @param int $depth 当前数组深度，来源数组从0开始
     * @param array &$remaining 剩余nodes与bytes额度，由各来源共同消耗
     * @param array $limits 已验证预算，包含depth等字段
     * @return bool 输入结构及字节数均在预算内时为true
     */
    private static function requestShape($value, $depth, array &$remaining, array $limits) {
        if(is_array($value)) {
            if($depth>$limits['depth']) return false;
            foreach($value as $key=>$item) {
                if($remaining['nodes']<1) return false;
                $remaining['nodes']--;$bytes=is_string($key)?strlen($key):0;
                if($bytes>$remaining['bytes']) return false;
                $remaining['bytes']-=$bytes;
                if(!self::requestShape($item,$depth+1,$remaining,$limits)) return false;
            }
            return true;
        }
        if(is_string($value)) {
            $bytes=strlen($value);if($bytes>$remaining['bytes'])return false;
            $remaining['bytes']-=$bytes;return true;
        }
        return $value===null || is_int($value) || is_float($value) || is_bool($value);
    }

    /**
     * 功能说明：在框架应用和会话启动前拒绝超预算请求，不把它当作PHP解析器补丁。
     *
     * 预算可由入口受信常量MYSTEP_REQUEST_LIMITS覆盖；POST还受实际post_max_size的
     * 较小正值限制。拒绝时直接输出不缓存的固定JSON，不创建会话、路由或应用对象。
     * 请求体解析可能已在本方法之前完成；无Content-Length的请求仍需Web服务器限制。
     *
     * 参数说明：无显式参数。
     * @return void 合法请求返回；400/413响应后结束请求，HEAD不输出正文
     * @throws InvalidArgumentException 入口预算常量不是数组或预算配置无效
     */
    public static function guardRequest() {
        $limits=defined('MYSTEP_REQUEST_LIMITS')?MYSTEP_REQUEST_LIMITS:[];
        if(!is_array($limits)) throw new InvalidArgumentException('Request limits must be an array');
        if(($_SERVER['REQUEST_METHOD']??'GET')==='POST') {
            $post=myFile::getByte(ini_get('post_max_size'));
            if($post>0 && $post<=PHP_INT_MAX && (!array_key_exists('body_bytes',$limits) || is_int($limits['body_bytes']))) $limits['body_bytes']=min($limits['body_bytes']??33554432,(int)$post);
        }
        $problem=self::inspectRequest($_SERVER,[$_GET,$_POST,$_COOKIE,$_FILES],$limits);
        if(!$problem)return;
        http_response_code($problem['status']);header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
        if(($_SERVER['REQUEST_METHOD']??'GET')!=='HEAD')echo json_encode(['error'=>$problem['error']]);
        exit;
    }

    /**
     * 功能说明：获取当前会话的 CSRF 令牌，缺失或格式异常时重新生成
     *
     * 令牌由 32 字节安全随机数转为 64 位小写十六进制字符串，保存在
     * $_SESSION['_ms_csrf'] 中。有效令牌在同一会话内复用，不自行设置过期时间；
     * 调用前应已启动目标应用的会话，应用间不得假定共享同一令牌。
     *
     * 参数说明：无显式参数。
     *
     * @return string 当前会话的 64 位 CSRF 令牌
     * @throws Exception 无法取得安全随机数时由 random_bytes() 抛出
     */
    public static function token() {
        if(!isset($_SESSION['_ms_csrf']) || !is_string($_SESSION['_ms_csrf']) || !preg_match('/\A[a-f0-9]{64}\z/D', $_SESSION['_ms_csrf'])) $_SESSION['_ms_csrf'] = bin2hex(random_bytes(32));
        return $_SESSION['_ms_csrf'];
    }

    /**
     * 功能说明：校验提交的 CSRF 令牌是否与当前会话中保存的令牌一致
     *
     * 仅接受字符串，并使用 hash_equals() 进行常量时间比较；不会创建或更新令牌。
     * 未保存会话令牌、值类型异常或内容不一致时返回 false。
     *
     * @param mixed $token 来自请求头或表单的待校验令牌
     * @return bool 是否匹配当前会话中保存的令牌
     */
    public static function valid($token) {
        return is_string($token) && isset($_SESSION['_ms_csrf']) && is_string($_SESSION['_ms_csrf']) && hash_equals($_SESSION['_ms_csrf'], $token);
    }

    /**
     * 功能说明：汇总允许访问当前应用的来源地址，并去重返回
     *
     * 来源由当前请求的协议和 Host、绑定域名、Cookie 域名及 web.cors_origins 组成。
     * 绑定域名和 Cookie 域名沿用当前请求的协议、显式端口；额外来源按配置原值加入，
     * 支持数组、配置对象或逗号分隔字符串。API上下文由逐接口跨域开关决定是否接纳当前合法Origin；普通页面继续使用原名单。本方法不执行身份授权。
     *
     * 参数说明：无显式参数。
     *
     * @return string[] 允许的来源列表，格式为“协议://主机[:端口]”
     * @global array $domain 域名绑定表，以域名为键
     * @global myConfig $ms_setting 当前应用配置，使用 cookie.domain 和 web.cors_origins
     */
    public static function origins() {
        global $domain, $ms_setting;
        $scheme = isHttps() ? 'https://' : 'http://';
        $current = $scheme.myReq::server('HTTP_HOST');
        $parts = parse_url($current); $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $origins = [$current];
        foreach(array_keys($domain ?? []) as $host) $origins[] = $scheme.$host.$port;
        $cookie = ltrim((string)($ms_setting->cookie->domain ?? ''), '.');
        if($cookie !== '') $origins[] = $scheme.$cookie.$port;
        $extra = $ms_setting->web->cors_origins ?? [];
        if(is_object($extra)) $extra = (array)$extra;
        if(is_string($extra)) $extra = array_filter(array_map('trim', explode(',', $extra)));
        $route=$GLOBALS['info_app']['route']??'';
        // API关闭跨域时只接受同源；开启时仅为当前已登记接口加入本次合法Origin。
        if(strpos($route,'/api/')===0)return array_values(array_unique(array_merge([$current],myApi::httpOrigins(substr($route,5)))));
        return array_values(array_unique(array_merge($origins, is_array($extra) ? $extra : [])));
    }

    /**
     * 功能说明：检查 Referer 中的来源是否与允许列表精确匹配
     *
     * 解析协议、主机和显式端口，将协议及主机转为小写后与 origins() 比较。
     * 不参与比较的路径、查询及片段不影响结果；拒绝缺少协议或主机、包含账号密码
     * 的地址及非字符串输入。不会按域名子串放行，也不自动统一默认端口的不同写法。
     * Referer 可由客户端伪造，本方法适用于来源或防盗链检查，不能作为用户授权。
     *
     * @param mixed $referer 待检查的完整 Referer URL
     * @return bool Referer 是否来自允许列表中的来源
     */
    public static function trustedReferer($referer) {
        if(!is_string($referer)) return false;
        $parts = parse_url($referer);
        if(!$parts || empty($parts['scheme']) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) return false;
        $origin = strtolower($parts['scheme']).'://'.strtolower($parts['host']).(isset($parts['port']) ? ':'.$parts['port'] : '');
        return in_array($origin, self::origins(), true);
    }

    /**
     * 功能说明：根据 Origin 和浏览器 Fetch Metadata 判断当前请求来源是否允许
     *
     * 提供 Origin 时，与 origins() 列表进行严格匹配；未提供 Origin 时，只有
     * Sec-Fetch-Site 明确为 cross-site 才拒绝。两者都缺失时返回 true，后续写请求
     * 仍须由 guard() 校验会话令牌；本方法本身不校验令牌，也不执行权限检查。
     *
     * 参数说明：无显式参数。
     *
     * @return bool 当前请求的来源信息是否通过检查
     */
    public static function originAllowed() {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
        if($origin !== null) return in_array($origin, self::origins(), true);
        return strtolower($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '') !== 'cross-site';
    }

    /**
     * 功能说明：获取用于识别内置后台入口的路径前缀
     *
     * 合并 gen.path_admin（缺省 console）、当前应用的 web.path_admin 和兼容入口
     * admin_cms，移除空值及重复项。返回配置中的前缀原值，匹配时再处理首尾斜杠。
     *
     * 参数说明：无显式参数。
     *
     * @return string[] 去重后的后台路径前缀列表
     * @global myConfig $ms_setting 当前应用配置
     */
    public static function adminPaths() {
        global $ms_setting;
        return array_values(array_unique(array_filter([(string)($ms_setting->gen->path_admin ?? 'console'), (string)($ms_setting->web->path_admin ?? ''), 'admin_cms'])));
    }

    /**
     * 功能说明：判断当前路由是否从已知后台路径前缀开始
     *
     * 使用域名绑定和路由处理后的 info_app.route，而不是仅判断浏览器中的原始地址；
     * 以完整路径段为边界匹配，供前端适配脚本识别后台域名下省略前缀的操作链接。
     * 该结果只描述页面上下文，不表示当前访问者具有管理员身份。
     *
     * 参数说明：无显式参数。
     *
     * @return bool 当前路由是否位于已知后台入口
     * @global array $info_app 当前应用信息，使用 route 字段
     */
    public static function administrativeContext() {
        global $info_app;
        $route = '/'.trim($info_app['route'] ?? '', '/').'/';
        foreach(self::adminPaths() as $prefix) if(strpos($route, '/'.trim($prefix, '/').'/') === 0) return true;
        return false;
    }

    /**
     * 功能说明：识别 URL 是否对应已知的内置状态修改操作
     *
     * 支持普通路径及 index.php 查询字符串路由，并解码查询字符串后检查操作参数。
     * 退出参数 out 和末尾 logout 不限于后台路径；后台路径内另识别删除、启停、
     * 卸载、解锁、整理、排序、设定、打包、保存、清缓存及未记录附件删除等动作。
     * 插件管理器的更新/打包/上传及badReq的屏蔽、解除、删除、重置也必须使用POST。
     * 此处仅识别内置 URL 约定，不执行修改，也不能自动发现第三方代码中的 GET 副作用。
     *
     * @param string $url 待检查的绝对 URL 或带可选查询字符串的路径
     * @return bool 是否命中已知状态修改规则；无法解析或未命中时返回 false
     */
    public static function mutates($url) {
        $parts = parse_url($url);
        if(!$parts) return false;
        $query = rawurldecode($parts['query'] ?? '');
        $path = $parts['path'] ?? '';
        // 查询字符串路由的路径位于第一个 & 之前，合并后再识别内置操作。
        if(preg_match('#(?:^|/)index\.php$#i', $path) && strpos($query, '=') !== 0) $path .= '/'.explode('&', $query)[0];
        if(preg_match('/(?:^|&)out(?:[=&]|$)/', $query) || preg_match('#/logout/?$#i', $path)) return true;
        $pluginPath=$path;$base=defined('ROOT_WEB') ? ROOT_WEB : '/';
        if(strpos($pluginPath,$base)===0) $pluginPath=substr($pluginPath,strlen($base));
        $pluginPath=ltrim(preg_replace('#^/?index\.php/+#i','',$pluginPath),'/');
        if(preg_match('#^(?:manager/(?:build|check|empty|upload|download|plugin|app|pack)|pack)(?:/|$)#i',$pluginPath)) return true;
        if(preg_match('#^badReq(?:/|$)#i',$pluginPath) && (preg_match('/(?:^|&)m=(?:ban|unban|del|reset)(?:&|$)/i',$query) || preg_match('#^badReq/(?:ban|unban|del|reset)(?:/|$)#i',$pluginPath))) return true;
        $administrative = false;
        foreach(self::adminPaths() as $prefix) if(preg_match('#(?:^|/)'.preg_quote(trim($prefix, '/'), '#').'(?:/|$)#', $path)) $administrative = true;
        if(!$administrative) return false;
        if(preg_match('#/(?:delete|remove|active|uninstall|unlock|rebuild|order|set|pack|add_ok|edit_ok)(?:/|$)#i', $path)) return true;
        return (preg_match('#/cache/?$#', $path) && preg_match('/(?:^|&)id=/', $query)) || (preg_match('#/attachment/?$#', $path) && preg_match('/(?:^|&)m=del(?:&|$)/', $query));
    }

    /**
     * 功能说明：在业务处理前执行请求方法、来源和会话 CSRF 令牌检查
     *
     * 同时检查原始请求地址和路由改写后的地址：已知状态修改操作只接受 POST，
     * 其他方法返回 405、Allow: POST 及 JSON 错误后立即结束请求。
     * 其余 GET、HEAD、OPTIONS 请求直接放行；其他方法必须同时通过来源和令牌校验。
     * 令牌优先取 X-CSRF-Token 请求头，否则取 _ms_csrf 表单字段；失败返回 403
     * 及禁止缓存的 JSON 错误并结束请求。成功后移除 POST/REQUEST 中的保留字段，
     * 防止旧业务把令牌作为普通数据入库。放行后仍需执行登录及业务权限检查。
     *
     * 参数说明：无显式参数。
     *
     * @return void 检查通过时返回；拒绝请求时输出响应并 exit
     * @global array $info_app 当前应用信息，使用 route 字段兼容绑定域名的路径改写
     */
    public static function guard() {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        global $info_app;
        $url = $_SERVER['REQUEST_URI'] ?? '/';
        // 绑定域名可能在分发前将 / 改写为 /console/，需同时检查实际路由。
        $route = $info_app['route'] ?? '';
        if($method !== 'POST' && (self::mutates($url) || self::mutates($route.(strpos($url, '?') !== false ? '?'.explode('?', $url, 2)[1] : '')))) {
            http_response_code(405); header('Allow: POST');
            header('Content-Type: application/json; charset=utf-8');
            echo '{"error":"POST required"}'; exit;
        }
        if(in_array($method, ['GET','HEAD','OPTIONS'], true)) return;
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_ms_csrf'] ?? null);
        if(!self::originAllowed() || !self::valid($token)) {
            http_response_code(403); header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store');
            echo '{"error":"Invalid CSRF token; reload the page and retry"}'; exit;
        }
        unset($_POST['_ms_csrf'], $_REQUEST['_ms_csrf']);
    }

    /**
     * 功能说明：向客户端提供当前应用会话的 CSRF 令牌，并结束请求
     *
     * 仅接受 GET，其他方法返回 405 和 Allow: GET。来源检查失败时返回 403 JSON；
     * 成功时输出 {"token":"当前会话令牌"}，并发送 private, no-store 及
     * Vary: Cookie, Origin 响应头。本方法不自行设置跨域允许头，API 场景由外层
     * 框架处理 CORS；客户端应保留同一目标应用的会话 Cookie 用于后续写请求。
     *
     * 参数说明：无显式参数。
     *
     * @return void 始终结束请求，成功时输出令牌 JSON，失败时输出对应错误响应
     * @throws Exception 生成令牌时无法取得安全随机数
     */
    public static function respond() {
        if(($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') { http_response_code(405); header('Allow: GET'); exit; }
        header('Cache-Control: private, no-store'); header('Vary: Cookie, Origin'); header('Content-Type: application/json; charset=utf-8');
        if(!self::originAllowed()) { http_response_code(403); echo '{"error":"Origin is not allowed"}'; exit; }
        echo json_encode(['token'=>self::token()]); exit;
    }

    /**
     * 功能说明：为已渲染的完整 HTML 页面注入会话令牌和前端安全适配脚本
     *
     * 非字符串或不含 <head 的输入原样返回。处理时设置 private, no-store，跳过
     * script/style/textarea 内容及 HTML 注释，为普通 POST 表单追加隐藏令牌；
     * 显式指向未允许来源或非 HTTP(S) 协议的表单不注入令牌。
     * 在首个 head 标签之后注入 window.msSecurity 配置和带内容版本号的 security.js，
     * 由脚本适配动态表单、Ajax 及旧式后台操作链接。
     * 必须在页面缓存读取或模板渲染之后调用，避免把会话令牌存入共享缓存；输入应为
     * 框架生成的可信页面，本方法不是 HTML 净化器或浏览器级的完整 HTML 解析器。
     *
     * @param mixed $html 待处理的完整 HTML；非字符串或无 head 的输入不作处理
     * @return mixed 注入后的 HTML 字符串，或不符合处理条件时的原始输入
     * @throws Exception 生成令牌时无法取得安全随机数
     */
    public static function decorate($html) {
        if(!is_string($html) || stripos($html, '<head') === false) return $html;
        // 在共享模板缓存之外注入当前会话令牌，避免不同会话复用同一令牌。
        header('Cache-Control: private, no-store');
        $token = self::token();
        $segments = preg_split('#(<(?:script|style|textarea)\b[^>]*>.*?</(?:script|style|textarea)\s*>|<!--.*?-->)#is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach($segments as $index => $segment) {
        if($index % 2) continue;
        $segments[$index] = preg_replace_callback('/<form\b[^>]*>/i', function($match) use ($token) {
            $tag = $match[0];
            if(!preg_match('/\bmethod\s*=\s*[\'\"]?post(?:[\'\"\s>])/i', $tag)) return $tag;
            if(preg_match('/\baction\s*=\s*(?:([\'\"])(.*?)\1|([^\s>]+))/i', $tag, $action)) {
                $url = html_entity_decode($action[2] !== '' ? $action[2] : ($action[3] ?? ''), ENT_QUOTES, 'UTF-8');
                if(preg_match('#^(?:[a-z][a-z0-9+.-]*:)?//#i', $url) && !self::trustedReferer(strpos($url, '//') === 0 ? (isHttps() ? 'https:' : 'http:').$url : $url)) return $tag;
                if(preg_match('#^(?!https?:)[a-z][a-z0-9+.-]*:#i', $url)) return $tag;
            }
            return $tag.'<input type="hidden" name="_ms_csrf" value="'.$token.'">';
        }, $segment);
        }
        $html = implode('', $segments);
        $data = json_encode(['token'=>self::token(), 'origins'=>self::origins(), 'admin'=>self::adminPaths(), 'adminContext'=>self::administrativeContext(), 'root'=>ROOT_WEB], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
        $script = '<script>window.msSecurity='.$data.';</script><script src="'.ROOT_WEB.'static/js/security.js?v='.substr(hash_file('sha256', ROOT.'static/js/security.js'), 0, 16).'"></script>';
        return preg_replace_callback('/<head\b[^>]*>/i', function($match) use ($script) { return $match[0].$script; }, $html, 1);
    }
}
