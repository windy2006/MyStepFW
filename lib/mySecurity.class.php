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
 *   提供 CSRF 令牌、同源判定、危险请求拦截及 HTML 表单和链接保护。
 *
 * 方法目录：
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
 *   1. 在业务处理前调用 guard()；写请求通过 X-CSRF-Token 或 _ms_csrf 携带会话令牌。
 *   2. 调用前应已启动目标应用的会话，并准备好框架配置、域名及路由上下文。
 *   3. 在模板渲染或缓存读取后调用 decorate()；装饰结果不得写回共享页面缓存。
 *
 * 说明：目录列出本文件直接声明的全部方法；参数、返回值及异常详见各方法 PHPDoc。
 *       原生签名保持 PHP 7.4 兼容，PHPDoc 类型仅用于说明。
 */


/**
 * 功能说明：在请求入口和最终 HTML 输出阶段提供会话安全保护。
 *
 * 使用说明：
 *   1. 依赖已启动的 Session 以及框架配置、域名绑定和路由上下文。
 *   2. 写请求携带目标应用会话的 CSRF 令牌，不能假定不同应用共享同一令牌。
 *   3. decorate() 在页面缓存读取后执行，装饰结果不得写入共享缓存。
 *   4. 来源、动作识别及 CSRF 校验不替代登录、权限和资源归属检查。
 *   5. 自定义应用和插件应限制状态修改的 HTTP 方法，并完成业务授权。
 *
 * 方法目录：参见本文件顶部。
 */
class mySecurity {
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
     * 支持数组、配置对象或逗号分隔字符串。本方法仅汇总配置，不执行身份授权。
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
