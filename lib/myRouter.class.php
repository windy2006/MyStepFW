<?PHP
/********************************************
*                                           *
* Name    : Ruoter handler                  *
* Modifier: Windy2000                       *
* Time    : 2018-12-26                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/
/**
 * 文件：lib/myRouter.class.php
 * 功能说明：请求URL解析、匹配与路由调度器
 *   处理重写/查询/PATH_INFO模式、路径参数、路由规则与目标回调。
 *
 * 方法目录：
 *   public myRouter::init($setting=array()) — 初始化路由设置并解析当前请求，清空已注册规则及 API 列表
 *   public myRouter::parseQuery($qstr='') — 统一解析重写路径、PATH_INFO 及旧式 index.php?route 请求
 *   public static myRouter::hasPrefix($path, $prefix) — 按完整路径段判断前缀，避免 /app 错误匹配 /application
 *   public myRouter::format($name, $pattern) — 注册或覆盖一个命名占位符格式，每个占位符只占一个参数位置
 *   public myRouter::setFormats($formats) — 批量注册占位符格式，可递归处理多组格式数组
 *   public myRouter::rule($rule, $method, $idx = '') — 注册保持历史正则语义的路径规则
 *   public myRouter::literal($path, $handler, $idx = '') — 注册字面路径模板，只把已定义的命名占位符展开为正则
 *   protected myRouter::compilePattern($pattern) — 为已展开的规则选择无冲突分隔符，并编译为完整匹配正则
 *   public myRouter::setRules($rules, $idx = '') — 批量注册规则或加载可信 PHP 路由注册文件
 *   public myRouter::check($qstr='') — 按优先级匹配路由并执行处理器，命中后更新应用配置及全局应用信息
 *   protected myRouter::routeArguments($handler, array $captures) — 分离命名处理器的显式参数，并替换捕获参数引用
 *   public myRouter::parse() — 从当前 route 生成应用、模块路径及查询参数信息
 *   public myRouter::remove($file, $idx) — 加锁移除持久路由注册表中某个应用的格式、规则及 API
 *   public static myRouter::checkRoute($path_main, $path_this, $idx) — 同步单个应用的路由定义到共享注册表，内容不变时不重复写入
 *   public static myRouter::saveRegistry($file, array $formats, array $rules, array $apis) — 原子保存格式、规则及 API 注册表，并使对应 OPcache 失效
 *
 * 使用说明：
 *   1. 框架按配置初始化后登记规则并调用check；应用规则由myStep加载。
 *   2. 保留查询参数值及编码边界，嵌套回调与动态参数按规则占位符展开。
 *   3. 不使用任意用户字符串直接构造执行回调或正则规则。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名保持PHP 7.4兼容，PHPDoc类型用于说明，不额外强制运行时类型。
 */


/**
 * 功能说明：处理重写/查询/PATH_INFO模式、路径参数、路由规则与目标回调。
 *
 * 使用说明：
 *   1. 框架按配置初始化后登记规则并调用check；应用规则由myStep加载。
 *   2. 保留查询参数值及编码边界，嵌套回调与动态参数按规则占位符展开。
 *   3. 不使用任意用户字符串直接构造执行回调或正则规则。
 *
 * 补充说明：
 * 网站路由处理：解析请求路径、注册格式及规则，并执行首个匹配的处理器
 *
 *     $router = new myRouter($setting);                       // 初始化请求及应用信息
 *     $router->format('hex', '[a-fA-F0-9]+');                  // 注册单个捕获参数格式
 *     $router->rule('/item/[hex]', 'showItem', 'MyApp');        // 注册保留正则语义的规则
 *     $router->literal('/item/[int].json', 'showItem', 'MyApp'); // 注册字面路径模板
 *     $router->setRules($file);                               // 加载可信路由注册文件
 *     $router->check('/item/12.json');                         // 匹配并执行规则
 *
 * 使用说明：
 * 1. rule() 保留原始正则语义；literal() 仅展开已定义占位符，其他字符按字面匹配。
 * 2. 先检查直接路径，再检查绑定前缀路径；每轮按注册顺序使用首个匹配规则。
 * 3. parseQuery() 会重建 GET、REQUEST 及 QUERY_STRING；parse() 生成应用信息，
 *    check() 在命中后同步应用全局信息并调用处理器，不是纯粹的匹配查询。
 * 4. 内存规则可使用闭包/对象回调；持久注册文件只能保存普通数据及命名回调。
 *    路由文件会被 include，必须来自受信开发代码；正则本身不提供资源隔离。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 *
 * @example ../app/Document/module/myRouter.php 框架现有演示，仅作为文档引用，不在检查时执行
 */
class myRouter extends myBase {
    use myTrait;

    public
        $query = '',
        $apis = [],
        $rules = array(),
        $route = array(),
        $info = array('app'=>'', 'path'=>'', 'para'=>array());

    protected
        $setting = array(),
        $formats = array(
            'any' => '(.*)',
            'int' => '(\d+)',
            'str' => '(\w+)'
        );

    /**
     * 功能说明：初始化路由设置并解析当前请求，清空已注册规则及 API 列表
     *
     * @param array $setting 设置项：default_app 默认 myStep；delimiter_path 默认 /；
     *                       delimiter_para 默认 &；url_fix 为绑定路径前缀，默认空字符串（默认：array()）
     * @return void 初始化 route、query 和 info；格式表保留实例当前定义
     * @throws InvalidArgumentException 路径或查询分隔符不是非空字符串
     */
    public function init($setting=array()) {
        if(!isset($setting['default_app'])) $setting['default_app'] = 'myStep';
        if(!isset($setting['delimiter_path'])) $setting['delimiter_path'] = '/';
        if(!isset($setting['delimiter_para'])) $setting['delimiter_para'] = '&';
        if(!isset($setting['url_fix'])) $setting['url_fix'] = '';
        if(!is_string($setting['delimiter_path']) || $setting['delimiter_path'] === '' || !is_string($setting['delimiter_para']) || $setting['delimiter_para'] === '') throw new InvalidArgumentException('Route delimiters must be non-empty strings');
        $this->rules = []; $this->apis = [];
        $this->setting = $setting;
        $this->route = $this->parseQuery();
        $this->query = $this->route['p'];
        if(preg_match('#^/\w+$#', $this->query)) $this->query .= '/';
        $this->parse();
    }

    /**
     * 功能说明：统一解析重写路径、PATH_INFO 及旧式 index.php?route 请求
     *
     * 显式传入时按第一个 ? 或 & 分离路径与查询；空字符串表示读取当前服务器变量。
     * 规范化前导斜杠，纯单词路径沿用尾斜杠约定。覆盖 GET 和 QUERY_STRING，并依照
     * PHP request_order（缺省回退 variables_order）顺序重建 REQUEST，避免残留旧 GET。
     *
     * @param string $qstr 待解析的路径及查询；空字符串使用当前请求（默认：''）
     * @return array 包含 p（规范路径）、q（查询串）、qstr（p 与可选 &q）的数组
     */
    public function parseQuery($qstr='') {
        if($qstr !== '') {
            $parts = preg_split('/[?&]/', ltrim($qstr, '?'), 2);
            $path = $parts[0];
            $q = $parts[1] ?? '';
        } else {
            $query = (string)myReq::svr('QUERY_STRING');
            $script = (string)myReq::svr('SCRIPT_NAME');
            $request_path = parse_url((string)myReq::svr('REQUEST_URI'), PHP_URL_PATH) ?: '/';
            $base = rtrim(str_replace('\\', '/', dirname($script)), '/.');
            $path = (string)myReq::svr('PATH_INFO');
            if($path === '') {
                $original = (string)myReq::svr('ORIG_PATH_INFO');
                $path = strpos($original, $script) === 0 ? substr($original, strlen($script)) : $original;
            }
            if($path === '') {
                if($request_path === $script) $path = '/';
                elseif(strpos($request_path, $script.'/') === 0) $path = substr($request_path, strlen($script));
                else $path = $base !== '' && self::hasPrefix($request_path, $base) ? substr($request_path, strlen($base)) : $request_path;
            }
            if(strpos($query, 'redirect:') === 0) {
                $q = parse_url((string)myReq::svr('REQUEST_URI'), PHP_URL_QUERY) ?: '';
            } else {
                $parts = preg_split('/[?&]/', ltrim($query, '?'), 2);
                $head = $parts[0];
                // Legacy index.php?route and rewrite-to-query routes take precedence over REQUEST_URI.
                $legacy = $head !== '' && strpos($head, '=') === false
                    && ($request_path === $script || strpos($head, '/') !== false);
                if($legacy) { $path = $head; $q = $parts[1] ?? ''; }
                else $q = $query;
            }
        }
        $p = '/'.ltrim($path, '/');
        // Preserve the established trailing-slash convention for word-only routes.
        if(preg_match('#^/[\w/]+$#', $p) && substr($p, -1) !== '/') $p .= '/';
        $q = str_replace($this->setting['delimiter_para'], '&', $q);
        $_SERVER['QUERY_STRING'] = $q;
        parse_str($q, $_GET);
        $_REQUEST = [];
        foreach(str_split(strtoupper(ini_get('request_order') ?: ini_get('variables_order'))) as $source) {
            if($source === 'G') $_REQUEST = array_replace($_REQUEST, $_GET);
            elseif($source === 'P') $_REQUEST = array_replace($_REQUEST, $_POST ?? []);
            elseif($source === 'C') $_REQUEST = array_replace($_REQUEST, $_COOKIE ?? []);
        }
        $qstr = $p.($q === '' ? '' : '&'.$q);
        return compact('qstr', 'p', 'q');
    }

    /**
     * 功能说明：按完整路径段判断前缀，避免 /app 错误匹配 /application
     *
     * @param string $path 待检查路径
     * @param string $prefix 路径前缀；比较前移除其末尾斜杠
     * @return bool 路径等于前缀或以前缀加 / 开头时为 true
     */
    public static function hasPrefix($path, $prefix) {
        $prefix = rtrim($prefix, '/');
        return $path === $prefix || strpos($path, $prefix.'/') === 0;
    }

    /**
     * 功能说明：注册或覆盖一个命名占位符格式，每个占位符只占一个参数位置
     *
     * 内部普通捕获组转为非捕获组，整体再包一层捕获；不接受命名捕获及反向引用。
     * 格式在注册时校验，应用作用域格式可由 setRules() 临时加载。
     *
     * @param string $name 占位符名称，由字母、数字或下划线组成，例如 hex
     * @param string $pattern 非空 PCRE 片段，不包含两端分隔符，例如 [a-fA-F0-9]+
     * @return $this 当前路由对象，可继续注册
     * @throws InvalidArgumentException 名称、片段、捕获形式或编译结果非法
     */
    public function format($name, $pattern) {
        if(!is_string($name) || !preg_match('/\A\w+\z/D', $name) || !is_string($pattern) || $pattern === '') throw new InvalidArgumentException('Invalid route format');
        if(preg_match('/\(\?P?[<\'](?![=!])|\\\\[1-9]|\\\\[kg][<{\']|\(\?P=/', $pattern)) throw new InvalidArgumentException('Route formats cannot contain named captures or backreferences');
        $normalized = '';
        $escaped = $in_class = false;
        for($i=0, $length=strlen($pattern); $i<$length; $i++) {
            $char = $pattern[$i];
            if($escaped) { $normalized .= $char; $escaped = false; continue; }
            if($char === '\\') { $normalized .= $char; $escaped = true; continue; }
            if($char === '[') $in_class = true;
            if($char === ']') $in_class = false;
            if(!$in_class && $char === '(' && ($pattern[$i+1] ?? '') !== '?') $normalized .= '(?:';
            else $normalized .= $char;
        }
        $pattern = $normalized;
        $this->compilePattern('('.$pattern.')');
        $this->formats[$name] = '('.$pattern.')';
        return $this;
    }

    /**
     * 功能说明：批量注册占位符格式，可递归处理多组格式数组
     *
     * @param array $formats 名称到正则片段的映射，或由此类映射组成的嵌套数组
     * @return $this 当前路由对象；空数组不改变格式
     * @throws InvalidArgumentException 参数不是数组或其中格式非法
     */
    public function setFormats($formats) {
        if(!is_array($formats)) throw new InvalidArgumentException('Route formats must be an array');
        if(!$formats) return $this;
        reset($formats);
        if(is_string(current($formats))) {
            foreach($formats as $k => $v) {
                $this->format($k, $v);
            }
        } else {
            foreach($formats as $format) {
                $this->setFormats($format);
            }
        }
        return $this;
    }

    /**
     * 功能说明：注册保持历史正则语义的路径规则
     *
     * 已知 [name] 展开为对应格式；未知 [name] 沿用旧行为，成为捕获该名称的分组。
     * 整条规则由 compilePattern() 统一添加完整匹配边界，不修改处理器执行顺序。
     *
     * @param string $rule 非空路径正则片段，不包含分隔符，可使用 [int]/[str]/[any]
     * @param callable|string|array $method 回调、带逗号参数的命名处理器或中间件序列
     * @param string $idx 所属应用标识；空字符串沿用现有框架应用解析行为（默认：''）
     * @return $this 当前路由对象
     * @throws InvalidArgumentException 规则为空、类型错误或无法编译
     */
    public function rule($rule, $method, $idx = '') {
        if(!is_string($rule) || $rule === '') throw new InvalidArgumentException('Route pattern must be a non-empty string');
        $rule = preg_replace_callback('#\[(\w+)\]#', function($match) {
            return $this->formats[$match[1]] ?? '('.$match[1].')';
        }, $rule);
        $regex = $this->compilePattern($rule);
        $this->rules[] = array('pattern'=>$rule, 'regex'=>$regex, 'method'=>$method, 'idx'=>$idx);
        return $this;
    }

    /**
     * 功能说明：注册字面路径模板，只把已定义的命名占位符展开为正则
     *
     * 例如 /article/[int].json 中的点号按字面匹配；捕获值仍按声明顺序传给处理器。
     * 与 rule() 相比，未知占位符直接报错，不回退为捕获占位符名称。
     *
     * @param string $path 非空路径模板，例如 /article/[int].json
     * @param callable|string|array $handler 回调、带逗号参数的命名处理器或中间件序列
     * @param string $idx 所属应用标识，含义与 rule() 一致（默认：''）
     * @return $this 当前路由对象
     * @throws InvalidArgumentException 路径非法、占位符未定义或展开结果无法编译
     */
    public function literal($path, $handler, $idx = '') {
        if(!is_string($path) || $path === '') throw new InvalidArgumentException('Route path must be a non-empty string');
        $pattern = '';
        foreach(preg_split('/(\[\w+\])/', $path, -1, PREG_SPLIT_DELIM_CAPTURE) as $piece) {
            if(preg_match('/\A\[(\w+)\]\z/D', $piece, $match)) {
                if(!isset($this->formats[$match[1]])) throw new InvalidArgumentException('Undefined route placeholder');
                $pattern .= $this->formats[$match[1]];
            } else $pattern .= preg_quote($piece);
        }
        $this->rules[] = ['pattern'=>$path, 'regex'=>$this->compilePattern($pattern), 'method'=>$handler, 'idx'=>$idx];
        return $this;
    }

    /**
     * 功能说明：为已展开的规则选择无冲突分隔符，并编译为完整匹配正则
     *
     * 以非捕获组包围整个片段，避免顶层 | 越过匹配边界；允许一个可选末尾斜杠。
     *
     * @param string $pattern 已展开占位符的 PCRE 片段，不含两端分隔符
     * @return string 含分隔符、首尾边界及 D 修饰符的完整正则
     * @throws InvalidArgumentException 正则非法或所有候选分隔符均被占用
     */
    protected function compilePattern($pattern) {
        foreach(['#', '~', '%', '!', ';', '@', "\x1f"] as $delimiter) {
            if(strpos($pattern, $delimiter) === false) {
                $regex = $delimiter.'^(?:'.$pattern.')/?$'.$delimiter.'D';
                if(@preg_match($regex, '') === false) throw new InvalidArgumentException('Invalid route pattern');
                return $regex;
            }
        }
        throw new InvalidArgumentException('No available route pattern delimiter');
    }

    /**
     * 功能说明：批量注册规则或加载可信 PHP 路由注册文件
     *
     * 支持 [路径, 处理器]、规则列表、应用标识到规则列表的映射。文件可定义
     * format_list、rule_list、api_list；应用格式仅在编译所属应用规则时使用。
     *
     * @param array|string $rules 规则数组或可信注册文件路径；不存在的文件不添加规则
     * @param string $idx 数字下标规则默认所属的应用，关联下标可指定各自应用（默认：''）
     * @return $this 当前路由对象
     * @throws InvalidArgumentException 缺少处理器、规则或格式不合法
     */
    public function setRules($rules, $idx = '') {
        if(is_array($rules)) {
            if(isset($rules[0]) && is_string($rules[0])) {
                if(!array_key_exists(1, $rules)) throw new InvalidArgumentException('A route requires a handler');
                $this->rule($rules[0], $rules[1], $idx);
            } else {
                foreach($rules as $k => $rule) {
                    $this->setRules($rule, is_numeric($k)?$idx:$k);
                }
            }
        } elseif(is_file($rules)) {
            include($rules);
            if(isset($api_list)) $this->apis = $api_list;
            $scoped_formats = [];
            foreach($format_list ?? [] as $name => $format) {
                if(is_string($format)) $this->format($name, $format);
                else $scoped_formats[$name] = $format;
            }
            $base_formats = $this->formats;
            try {
                if(isset($rule_list[0]) && is_string($rule_list[0])) $this->setRules($rule_list, $idx);
                else foreach($rule_list ?? [] as $name => $rules) {
                    $scope = is_numeric($name) ? $idx : $name;
                    $this->formats = $base_formats;
                    if(isset($scoped_formats[$scope])) $this->setFormats($scoped_formats[$scope]);
                    $this->setRules($rules, $scope);
                }
            } finally { $this->formats = $base_formats; }
        }
        return $this;
    }

    /**
     * 功能说明：按优先级匹配路由并执行处理器，命中后更新应用配置及全局应用信息
     *
     * 可重复检查不同路径，保留实例绑定前缀；命名捕获只按数字顺序传递一次。
     * 支持普通回调、逗号参数中的 $1 等引用及中间件序列；中间件返回 false 时
     * 停止后续调用，但已命中的请求仍返回 true。实际处理器可输出、跳转或结束请求。
     *
     * @param string $qstr 非空时重新解析该路径及查询，空字符串检查当前 query（默认：''）
     * @return bool true 表示规则已命中并处理（含被中间件阻止）；false 表示无匹配
     * @throws RuntimeException PCRE 执行失败，不能按普通未匹配继续回退
     */
    public function check($qstr='') {
        if($qstr !== '') {
            $this->route = $this->parseQuery($qstr);
            $this->query = $this->route['p'];
            $this->parse();
        }
        $qstr = $this->query;
        $url_fix = $this->setting['url_fix'];
        $rule = '';
        $fix_mode = true;
        $match = [];
        // Direct rules always win. Within either pass, the first declared match wins.
        foreach(['', $url_fix] as $pass => $prefix) {
            if($pass === 1 && $prefix === '') break;
            foreach($this->rules as $the_rule) {
                $matched = preg_match($the_rule['regex'], $prefix.$qstr, $captures);
                if($matched === false) throw new RuntimeException('Route matching failed: PCRE '.preg_last_error());
                if($matched === 1) {
                    $rule = $the_rule;
                    $match = array_filter($captures, 'is_int', ARRAY_FILTER_USE_KEY);
                    $fix_mode = $pass === 1;
                    break 2;
                }
            }
        }
        if(!$fix_mode || !empty($url_fix) && self::hasPrefix($this->route['p'], $url_fix)) $url_fix = '';

        if(!empty($rule)) {
            $idx = trim(array_shift($match), '/');
            $idx = preg_replace('#/.*$#', '', $idx);
            if(preg_match('#^/api/#', $rule['pattern'])) {
                if(!is_dir(APP.$match[0])) {
                    array_unshift($match, 'myStep');
                }
                $rule['idx'] = $match[0];
                $match = [implode('/', $match)];
            }elseif(preg_match('#^/module/#', $rule['pattern'])) {
                $rule['idx'] = $this->setting['default_app'];
                if(defined('URL_FIX') && is_file(APP.URL_FIX.'/info.php')) $rule['idx'] = URL_FIX;
                $match = [implode('/', $match)];
            }
            if(!is_dir(APP.$rule['idx']) && strpos($rule['idx'], 'plugin_')!==0) {
                myStep::info('app_missing');
            }
            if(!file_exists(APP.$rule['idx'].'/info.php')) $rule['idx'] = 'myStep';
            $core = myStep::getCore();
            if(strpos($rule['pattern'], '/api/') === 0 && $rule['idx'] === 'myStep' && $core !== 'myStep' && is_file(APP.$core.'/info.php')) $rule['idx'] = $core;
            $info_app = include(APP.$rule['idx'].'/info.php');
            $info_app['path'] = explode('/', trim($url_fix.$this->route['p'], '/'));
            $info_app['para'] = $this->info['para'];
            $info_app['route'] = $url_fix.$this->route['p'];
            $this->info = $info_app;
            myReq::globals('info_app', $info_app);

            global $ms_setting;
            if(is_file(APP.$info_app['app'].'/config.php')) {
                $ms_setting->merge(APP.$info_app['app'].'/config.php');
            }
            if(is_file(APP.$info_app['app'].'/config_'.$idx.'.php')) {
                $ms_setting->merge(APP.$info_app['app'].'/config_'.$idx.'.php');
            }
            $core = myStep::getCore();
            if(isset($info_app['para']['core']) && $core !== $info_app['app'] && is_file(APP.$core.'/config.php')) {
                $ms_setting->merge(APP.$core.'/config.php');
            }
            myStep::checkBind($rule['pattern']);
            // 在应用/会话启动前处理API来源与预检；实际调用仍执行CSRF及业务权限。
            if(strpos($rule['pattern'],'/api/')===0)myApi::httpCors(null,$match[0]??'',$this->apis);
            myStep::setPara();
            $result = [];
            if(is_array($rule['method']) && !is_callable($rule['method'])) {
                $last = array_pop($rule['method']);
                $flag = true;
                foreach($rule['method'] as $each) {
                    list($method, $each) = $this->routeArguments($each, $match);
                    array_push($each, $flag);
                    if(!is_callable($method)) myStep::info('app_missing');
                    $flag = call_user_func_array($method, $each);
                    $result[] = $flag;
                    if($flag===false) break;
                }

                if($flag === false) return true;
                list($method, $last) = $this->routeArguments($last, $match);
                if(!is_callable($method)) myStep::info('app_missing');
                array_push($last, implode($this->setting['delimiter_path'], $match), $result);
                call_user_func_array($method, $last);
            } else {
                list($method, $paras) = $this->routeArguments($rule['method'], $match);
                $paras = array_merge($paras, $match);
                if(is_callable($method)) {
                    call_user_func_array($method, $paras);
                } else {
                    myStep::info('app_missing');
                }
            }
            return true;
        }
        return false;
    }

    /**
     * 功能说明：分离命名处理器的显式参数，并替换捕获参数引用
     *
     * @param callable|string|array $handler 非字符串回调原样保留；字符串按逗号分割
     * @param array $captures 从第一个捕获开始的零起始列表；$1 引用其第 0 项
     * @return array [处理器, 显式参数列表]；不存在的捕获引用保留原文本
     */
    protected function routeArguments($handler, array $captures) {
        if(!is_string($handler)) return [$handler, []];
        $arguments = array_map('trim', explode(',', $handler));
        $method = array_shift($arguments);
        foreach($arguments as $key => $value) {
            if(preg_match('/^\$(\d+)$/D', $value, $match)) $arguments[$key] = trim($captures[$match[1]-1] ?? $value, '/');
        }
        return [$method, $arguments];
    }

    /**
     * 功能说明：从当前 route 生成应用、模块路径及查询参数信息
     *
     * 重置旧 info，避免连续解析残留；绑定前缀只补一次。首段不是有效应用时使用
     * 默认应用，存在的 info.php 元数据与解析出的 app/path/para 合并。
     *
     * 参数说明：无显式参数。
     *
     * @return void 结果写入实例 info，不执行路由处理器
     */
    public function parse() {
        $this->info = ['app'=>'', 'path'=>[], 'para'=>[]];
        extract($this->route);
        $setting = $this->setting;
        $prefix = $this->setting['url_fix'];
        if($prefix !== '' && self::hasPrefix($p, $prefix)) $prefix = '';
        $p = explode($setting['delimiter_path'], trim($prefix.$p, '/'));
        parse_str($q, $this->info['para']);
        if(empty($p[0])) array_shift($p);
        if(count($p)==0 || !preg_match('/^\w+$/D', $p[0]) || !is_file(APP.$p[0].'/info.php')) {
            $this->info['app'] = $setting['default_app'];
        } else {
            $this->info['app'] = array_shift($p);
        }
        $this->info['path'] = $p;
        $info = array();
        if(is_file(APP.$this->info['app'].'/info.php')) {
            $info = include(APP.$this->info['app'].'/info.php');
        }
        $this->info = array_merge($info, $this->info);
    }

    /**
     * 功能说明：加锁移除持久路由注册表中某个应用的格式、规则及 API
     *
     * @param string $file 可信 PHP 注册文件路径；锁文件位于同目录的 .lock 路径
     * @param string $idx 需要删除的应用标识
     * @return bool 至少存在一项并已移除时为 true，文件或标识不存在时为 false
     * @throws RuntimeException 无法锁定或原子保存注册表
     */
    public function remove($file, $idx) {
        if(!is_file($file)) return false;
        $lock = fopen($file.'.lock', 'c');
        if(!$lock || !flock($lock, LOCK_EX)) throw new RuntimeException('Cannot lock route registry');
        try {
            $format_list = $rule_list = $api_list = [];
            include($file);
            $flag = isset($format_list[$idx]) || isset($rule_list[$idx]) || isset($api_list[$idx]);
            unset($format_list[$idx], $rule_list[$idx], $api_list[$idx]);
            if($flag) self::saveRegistry($file, $format_list, $rule_list, $api_list);
        } finally {
            flock($lock, LOCK_UN); fclose($lock);
        }
        return $flag;
    }

    /**
     * 功能说明：同步单个应用的路由定义到共享注册表，内容不变时不重复写入
     *
     * 源文件应定义 format、rule、api；空值或空数组表示移除该应用对应分组。
     * 整个读改写过程使用目标旁的锁文件，防止多个应用同时登记时覆盖彼此。
     *
     * @param string $path_main 目标 PHP 注册文件路径，不存在时创建所在目录及注册表
     * @param string $path_this 可信的应用路由定义文件路径，会被 include
     * @param string $idx 目标注册表中的应用标识
     * @return bool 源文件存在且同步完成为 true；源文件不存在为 false
     * @throws RuntimeException 无法锁定或保存注册表
     * @throws InvalidArgumentException 定义中包含不能持久化的对象或资源
     */
    public static function checkRoute($path_main, $path_this, $idx) {
        if(!is_file($path_this)) return false;
        $directory = dirname($path_main);
        if(!is_dir($directory)) mkdir($directory, 0777, true);
        $lock = fopen($path_main.'.lock', 'c');
        if(!$lock || !flock($lock, LOCK_EX)) throw new RuntimeException('Cannot lock route registry');
        try {
            $format_list = $rule_list = $api_list = [];
            if(is_file($path_main)) include($path_main);
            $before = [$format_list, $rule_list, $api_list];
            $format = $rule = $api = null;
            if(function_exists('opcache_invalidate')) opcache_invalidate($path_this, true);
            include($path_this);
            foreach(['format'=>'format_list', 'rule'=>'rule_list', 'api'=>'api_list'] as $source => $target) {
                if($$source === null || $$source === []) unset(${$target}[$idx]);
                else ${$target}[$idx] = $$source;
            }
            if($before !== [$format_list, $rule_list, $api_list] || !is_file($path_main)) self::saveRegistry($path_main, $format_list, $rule_list, $api_list);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
        return true;
    }

    /**
     * 功能说明：原子保存格式、规则及 API 注册表，并使对应 OPcache 失效
     *
     * 仅导出普通数据，拒绝所有对象（含闭包）和资源；myStep 规则分组排到最后。
     * 本方法不获取读改写锁；需要合并现有注册表时由调用方先持有 $file.lock。
     *
     * @param string $file 目标 PHP 文件路径，父目录须已存在且可写
     * @param array $formats 全部格式定义，写入 format_list
     * @param array $rules 全部规则定义，写入 rule_list；持久回调使用函数名或静态方法
     * @param array $apis 全部 API 定义，写入 api_list
     * @return void 成功时发布完整文件，不返回字节数
     * @throws InvalidArgumentException 数据中包含对象或资源
     * @throws RuntimeException 临时文件写入不完整或原子替换失败
     */
    public static function saveRegistry($file, array $formats, array $rules, array $apis) {
        if(isset($rules['myStep'])) { $framework = $rules['myStep']; unset($rules['myStep']); $rules['myStep'] = $framework; }
        $check = function($value) use (&$check) {
            if(is_object($value) || is_resource($value)) throw new InvalidArgumentException('Persisted routes require named callbacks; object callbacks may be registered in memory');
            if(is_array($value)) foreach($value as $part) $check($part);
        };
        $check([$formats, $rules, $apis]);
        $content = '<?PHP'.chr(10).'$format_list = '.var_export($formats, true).';'.chr(10)
            .'$rule_list = '.var_export($rules, true).';'.chr(10).'$api_list = '.var_export($apis, true).';'.chr(10);
        $temporary = $file.'.'.bin2hex(random_bytes(8)).'.tmp';
        if(file_put_contents($temporary, $content, LOCK_EX) !== strlen($content) || !rename($temporary, $file)) {
            if(is_file($temporary)) unlink($temporary);
            throw new RuntimeException('Cannot save route registry');
        }
        if(function_exists('opcache_invalidate')) opcache_invalidate($file, true);
    }
}
