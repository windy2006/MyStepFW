<?PHP
/********************************************
*                                           *
* Name    : Controller of the Framework     *
* Author  : Windy2000                       *
* Time    : 2010-12-16                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT HOLD THIS ITEM PLEASE.      *
*                                           *
********************************************/
/**
 * 文件：lib/myStep.class.php
 * 功能说明：框架主入口及应用执行协调器
 *   协调配置、认证、实例、路由、应用/插件加载、资源输出与生命周期。
 *
 * 方法目录：
 *   public myStep::__construct() — 构造函数
 *   public static myStep::getInstance($calledClass = '') — 取得类实例
 *   public myStep::start($charset = 'UTF-8', $set_plugin = true) — 框架执行入口，初始化所有变量
 *   public myStep::ms_login($usr='', $pwd='') — 框架登录验证
 *   public myStep::rememberAdmin($ttl) — 设置管理员签名登录Cookie，非正有效期删除该Cookie
 *   public static myStep::auth_code() — 生成认证码，可扩展，可在添加任意个salt参数
 *   public myStep::setInstance() — 声明重要实例
 *   public static myStep::clearDataCache() — 清空框架数据文件及当前缓存后端，供后台和更新器共同使用。
 *   public myStep::show(myTemplate $tpl, $append_pare = []) — 通过模板类显示页面
 *   public myStep::render(myTemplate $tpl, $append_pare = []) — 通过模板类输出页面内容
 *   public myStep::checkCache(myTemplate $tpl) — 检查模版缓存并输出
 *   public myStep::end() — 框架终止，销毁相关变量
 *   public myStep::gzOut($level = 3, $query = 0, $time = 0, $mem = 0) — 页面压缩
 *   public myStep::addCSS($file, $code=false) — 添加页面CSS文件
 *   public myStep::CSS($cache='', $dummy_1='', $dummy_2='') — 生成整合CSS文件
 *   public myStep::addJS($file, $code=false) — 添加页面脚本文件
 *   public myStep::JS($cache='', $dummy_1='', $dummy_2='') — 整合页面脚本文件
 *   public myStep::editorSetPlugin($code, $btn='') — 富文本编辑器功能添加
 *   public myStep::editorGetPlugin() — 生成编辑器插件缓存脚本
 *   public static myStep::info($msg, $url = '') — 信息提示，需先声明mystep类
 *   public static myStep::captcha($len = 4, $scope = 3) — 生成验证码
 *   public static myStep::language($app_name, $type='default') — JS语言包接口
 *   public static myStep::setting($app_name) — JS设置信息接口
 *   public static myStep::apiOriginAllowed($origin, $current, array $domains, $cookie_domain = '', array $extra = []) — 按当前来源、绑定域名、Cookie域及额外白名单检查API来源
 *   public static myStep::api($path) — 框架API执行接口
 *   public static myStep::module($path, $dummy = '') — 框架模块调用接口
 *   public static myStep::appFunc($func) — 应用自定义功能接口（sitemap, atom, rss等）
 *   public static myStep::upload() — 文件上传
 *   public static myStep::download($idx='') — 文件下载
 *   public static myStep::remove_ul($idx='') — 删除上传文件
 *   public static myStep::header($idx, $para = '', $exit = true) — 处理头信息
 *   public static myStep::setURL($url='') — 链接处理
 *   public static myStep::redirect($url = '', $code = '302') — 链接跳转
 *   public static myStep::init() — 框架变量初始化
 *   public static myStep::storagePlugin($name) — 在缓存和会话初始化前校验并加载已启用的存储插件。
 *   public static myStep::configureStorage(myConfig $config,$probe = false) — 校验显式选择的插件存储，保存前可执行连接检查。
 *   public static myStep::activePlugins() — 按框架插件配置收集已启用插件并在当前请求内复用结果
 *   public static myStep::refreshRoutes() — 仅在路由定义或配置变化时重建注册表，否则加载已编译规则
 *   public static myStep::go() — 执行框架
 *   public static myStep::setPara() — 根据实际路由应用确定公共端点会话策略，并初始化应用模块参数
 *   public static myStep::checkBind($pattern) — 检测域名绑定
 *   public static myStep::checkClose() — 检测是否网站关闭
 *   public static myStep::getModule($m) — 应用模块调用
 *   public static myStep::vendor() — 调用第三方组件
 *   public static myStep::segment($str='') — 字符串分词
 *   public static myStep::checkPower($idx) — 权限检测接口
 *   public static myStep::getCore() — 获取指定核心类名称
 *
 * 使用说明：
 *   1. 网站index.php定义路径常量并调用myStep::init；应用通常继承本类。
 *   2. 路由/API/模块/自定义函数分支使用当前应用上下文及权限检查。
 *   3. 清理数据缓存使用clearDataCache保留活动锁；详细请求流程见docs/framework/execution.md。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名保持PHP 7.4兼容，PHPDoc类型用于说明，不额外强制运行时类型。
 */


/**
核心框架类，扩展于myController
    self::getInstance($calledClass)                  // 取得类实例
    self::clearDataCache()                           // 管理员清空数据缓存并保留活动锁
    $this->start()                                   // 框架执行入口，初始化所有变量
    $this->setInstance()                             // 声明重要实例
    $this->show(myTemplate $tpl)                     // 通过模板类显示页面
    $this->render(myTemplate $tpl)                   // 通过模板类输出页面内容
    $this->checkCache(myTemplate $tpl)               // 检查是否存在缓存并输出
    $this->end()                                     // 框架终止，销毁相关变量
    $this->gzOut($level, $query, $time)              // 压缩输出页面内容
    $this->addCSS($file)                             // 添加页面CSS文件
    $this->CSS($cache)                               // 生成整合CSS文件
    $this->addJS($file)                              // 添加页面脚本文件
    $this->JS($cache)                                // 整合页面脚本文件
    $this->editorSetPlugin($code, $btn)              // 富文本编辑器功能扩展
    $this->editorGetPlugin()                         // 生成编辑器插件缓存脚本
    self::checkClose()                               // 检测框架服务是否关闭
    self::info($msg, $url)                           // 信息提示，需先声明mystep类
    self::captcha($len, $scope)                      // 生成验证码
    self::language($app_name, $type)                 // JS语言包接口
    self::setting($app_name, $type)                  // JS设置信息接口
    self::api($para)                                 // 框架API执行接口
    self::module($m)                                 // 框架模块调用接口
    self::appFunc($func)                             // 应用自定义功能接口（sitemap, atom, rss等）
    self::upload()                                   // 文件上传接口
    self::download($idx)                             // 文件下载接口
    self::remove_ul($idx)                            // 删除文件下载接口
    self::setURL()                                   // 链接处理
    self::redirect()                                 // 链接跳转
    self::init()                                     // 框架变量初始化
    self::go()                                       // 执行框架
    self::setPara()                                  // 应用模块初始化参数设置
    self::getModule($m)                              // 应用模块调用
    self::vendor($class_name)                        // 调用第三方组件
    self::segment($str)                              // 字符串分词
*/

require_once('function.php');
require_once(VENDOR.'autoload.php');
require_once('myController.class.php');
/**
 * 功能说明：协调配置、认证、实例、路由、应用/插件加载、资源输出与生命周期。
 *
 * 使用说明：
 *   1. 网站index.php定义路径常量并调用myStep::init；应用通常继承本类。
 *   2. 路由/API/模块/自定义函数分支使用当前应用上下文及权限检查。
 *   3. 清理数据缓存使用clearDataCache保留活动锁；详细请求流程见docs/framework/execution.md。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 *
 * @example ../app/Document/module/myStep.php 框架现有演示，仅作为文档引用，不在检查时执行
 */
class myStep extends myController {
    public static
        $variant_alias = [],
        $url_prefix = '',
        $shown = false,
        $cache_js = '',
        $cache_css = '',
        $func_ignore = ['captcha', 'error', 'ms_language', 'ms_setting', 'sitemap', 'atom', 'rss'];
    public $setting;
    protected
        $editor_plugin = [],
        $editor_btn = [],
        $mem_start = 0,
        $time_start = 0,
        $time_css = 0,
        $time_js = 0;

    /**
     * 功能说明：构造函数
     *
     * 参数说明：无显式参数。
     *
     * @return void 初始化或释放资源，无返回值。
     */
    public function __construct() {
        $this->setting = &$GLOBALS['ms_setting'];
        date_default_timezone_set($this->setting->gen->timezone);
        set_time_limit(30);
        ini_set('default_socket_timeout', 300);
    }

    /**
     * 功能说明：取得类实例
     *
     * @param string $calledClass 待创建或获取实例的类名（默认：''）
     * @param mixed ...$args 传给目标类初始化入口的位置参数（历史位置参数，由func_get_args读取）
     * @return object 按父控制器规则取得并配置别名和错误回调的实例
     */
    public static function getInstance($calledClass = '') {
        $instance = call_user_func_array('parent::getInstance', func_get_args());
        if(is_callable([$instance, 'regAlias']) && is_file(CONFIG.'method_alias/'.$calledClass.'.php')) {
            $alias = include(CONFIG.'method_alias/'.$calledClass.'.php');
            $instance->regAlias($alias);
        }
        if($instance instanceof myBase) $instance->setErrHandler('myStep::info');
        return $instance;
    }

    /**
     * 功能说明：框架执行入口，初始化所有变量
     *
     * @param string $charset 继承兼容占位参数；实际使用当前setting->gen->charset（默认：'UTF-8'）
     * @param bool $set_plugin 继承兼容参数；执行时依据当前路由及func_ignore重新计算是否运行插件（默认：true）
     * @return void 初始化当前应用上下文并进入控制器启动流程
     * @throws RuntimeException 需要认证的请求无法启动会话存储
     * @throws Throwable 自定义解析器、初始化/反射目标或受信执行文件产生的异常继续传递
     */
    public function start($charset = 'UTF-8', $set_plugin = true) {
        $this->mem_start = memory_get_usage();
        $this->time_start = getMicrotime();
        $alias = include(CONFIG.'class_alias.php');
        self::setAlias($alias);

        myException::init(array(
            'log_mode' => 0,
            'log_type' => $this->setting->gen->debug ? E_ALL : (E_ALL & ~E_NOTICE),
            'log_file' => ROOT.'error.log',
            'callback_type' => $this->setting->gen->debug ? E_ALL : (E_ALL & ~(E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE | E_NOTICE)),
            'exit_on_error' => true
        ));

        if(defined('PATH') && is_file(PATH.'plugin.php')) {
            $plugin = include(PATH.'plugin.php');
            $active = self::activePlugins();
            // 应用绑定负责选择范围，注册表 order 负责执行顺序；重复绑定只初始化一次。
            $bindings = array_fill_keys(array_filter(is_array($plugin) ? $plugin : [], 'is_string'), true);
            foreach($active as $name => $unused) if(isset($bindings[$name])) $this->regPlugin(PLUGIN.$name);
        }

        if(empty($this->func_log)) {
            $this->func_log = [
                'login' => [$this, 'ms_login'],
                'logout' => function() {
                    myReq::removeCookie('ms_auth');
                    myReq::sessionEnd();
                    return true;
                },
                'chg_psw' => function($id, $psw_org, $psw_new) {
                    if(myReq::session('ms_user') !== $this->setting->gen->s_usr || !is_string($psw_new) || $psw_new === ''
                        || !myAuth::verifyPassword($psw_org, $this->setting->gen->s_pwd)) return false;
                    $config = new myConfig(CONFIG.'config.php');
                    $config->gen->s_pwd = myAuth::hashPassword($psw_new);
                    $config->save('php');
                    $this->setting->gen->s_pwd = $config->gen->s_pwd;
                    myReq::removeCookie('ms_auth');
                    myReq::session('ms_auth_version', hash('sha256', $config->gen->s_pwd));
                    myAuth::renewSession();
                    return true;
                }
            ];
        }

        $this->setInstance();
        $this->setting->cookie->prefix .= substr(md5(myReq::server('USERNAME').myReq::server('COMPUTERNAME').myReq::server('OS')), 0, 4).'_';
        if(in_array($this->setting->session->mode, ['sess_file','files'], true)) $this->setting->session->path = myPath::absolute($this->setting->session->path ?: 'cache/session/');

        $this->setting->info = new stdClass();
        $this->setting->info->time = myReq::server('REQUEST_TIME');
        $this->setting->info->host = myReq::server('HTTP_HOST');

        $this->setLanguagePack(APP.'myStep/language/', $this->setting->gen->language);
        if(PATH != APP.'myStep/') $this->setLanguagePack(PATH.'language', $this->setting->gen->language);

        if(count(ob_list_handlers())>0) {
            $content = ob_get_contents();
            ob_clean();
        }
        $_SERVER['REQUEST_URI'] = myString::setCharset($_SERVER['REQUEST_URI']);
        myReq::init((array)$this->setting->cookie, (array)$this->setting->session);
        if($this->setting->session->mode=='sess_mysql') {
            sess_mysql::set((array)$this->setting->db);
        }
        if($this->setting->session->mode==='sess_redis' && empty($GLOBALS['no_log'])) {
            $plugin=self::storagePlugin('redis');
            if(!sess_redis::set($plugin::options()))throw new RuntimeException('Redis会话配置无效，请检查插件设置。');
        }
        // 会话后端故障时停止认证流程；no_log公开端点按设计不启动会话。
        if(myReq::sessionStart($this->setting->session->mode) === false && !myReq::sessionCheck() && empty($GLOBALS['no_log'])) {
            throw new RuntimeException($this->setting->session->mode==='sess_redis' ? 'Redis会话启动失败，请检查Redis插件连接设置、服务状态和会话锁。' : 'Session storage unavailable.');
        }
        mySecurity::guard();
        $this->login();
        if(isset($content)) {
            echo $content;
            unset($content);
        }

        global $func_ignore;
        if(is_string($func_ignore)) $func_ignore = explode(',', str_replace(' ', '', $func_ignore));
        if(is_array($func_ignore)) self::$func_ignore = array_merge(self::$func_ignore, $func_ignore);
        $path = explode('/', trim(myReq::globals('info_app')['route'], '/'));
        $set_plugin = !in_array(array_shift($path), self::$func_ignore);

        $this->page_content['start'] = array();
        $this->page_content['end'] = array();
        // start钩子及插件初始化即可使用当前URL前缀。
        self::$url_prefix = self::setURL();
        parent::start($this->setting->gen->charset, $set_plugin);
    }

    /**
     * 功能说明：框架登录验证
     *
     * @param string $usr 登录用户名（默认：''）
     * @param string $pwd 账号密码，空串按调用入口的兼容约定处理（默认：''）
     * @return bool 凭据/当前会话通过框架管理员认证为true，否则为false
     */
    public function ms_login($usr='', $pwd='') {
        $username = $this->setting->gen->s_usr;
        $hash = $this->setting->gen->s_pwd;
        $explicit = $usr !== '' || $pwd !== '';
        if($explicit) {
            if(!is_string($usr) || !hash_equals($username, $usr) || !myAuth::verifyPassword($pwd, $hash)) return false;
            if(myAuth::needsUpgrade($hash)) {
                $hash = myAuth::hashPassword($pwd);
                $config = new myConfig(CONFIG.'config.php');
                $config->gen->s_pwd = $hash;
                $config->save('php');
                $this->setting->gen->s_pwd = $hash;
            }
        } else {
            $version = hash('sha256', $hash);
            $active = myReq::session('ms_user') === $username && myReq::session('ms_auth_version') === $version;
            if(!$active && !myAuth::verifyToken(myReq::cookie('ms_auth'), $username, $hash, 'framework', $this->setting->web->etag)) {
                if(myReq::session('ms_user') !== null) {
                    myReq::removeSession('ms_user');
                    myReq::removeSession('ms_auth_version');
                    myReq::removeSession('sysop');
                }
                if(myReq::cookie('ms_auth') !== null) myReq::removeCookie('ms_auth');
                return false;
            }
        }
        if(myReq::session('ms_user') !== $username) myAuth::renewSession();
        myReq::session('ms_user', $username);
        myReq::session('ms_auth_version', hash('sha256', $hash));
        myReq::session('sysop', 'y');
        return true;
    }

    /**
     * 功能说明：设置管理员签名登录Cookie，非正有效期删除该Cookie
     *
     * @param int $ttl 有效期秒数，非正值按入口约定删除或取消
     * @return bool 记住登录Cookie设置或删除是否成功
     */
    public function rememberAdmin($ttl) {
        if($ttl <= 0) return myReq::removeCookie('ms_auth');
        return myReq::setCookie('ms_auth', myAuth::makeToken($this->setting->gen->s_usr, $this->setting->gen->s_pwd, 'framework', $this->setting->web->etag, $ttl), min(31536000, (int)$ttl));
    }

    /**
     * 功能说明：生成认证码，可扩展，可在添加任意个salt参数
     *
     * @param scalar ...$args 参与SHA-256摘要的盐值片段，按顺序以竖线连接（历史位置参数，由func_get_args读取）
     * @return string 按盐片段顺序生成的64位SHA-256十六进制摘要
     */
    public static function auth_code() {
        $args = func_get_args();
        return hash('sha256', implode('|', $args));
    }

    /**
     * 功能说明：按驱动签名准备缓存与数据库实例
     *
     * SQLite 仅传入数据库文件路径（或 :memory:），相对路径按 ROOT 解析；
     * MySQL/MSSQL 沿用服务器、用户、密码及字符集参数；Redis缓存读取plugin/redis/config.php。
     * 关闭业务数据库自动连接时，仅数据库会话回退文件模式，Redis会话独立工作。已有全局实例继续复用。
     * 参数说明：无显式参数。
     *
     * @return void 无返回值。
     */
    public function setInstance() {
        global $cache, $db;
        if(empty($cache)) {
            switch(strtolower($this->setting->gen->cache_mode)) {
                case 'redis':
                    $plugin=self::storagePlugin('redis');
                    $cache_setting = $plugin::options();
                    break;
                case 'mysql':
                case 'mssql':
                    $cache_setting = myConfig::o2a($this->setting->db);
                    break;
                default:
                    $cache_setting = CACHE.'data';
            }
            $cache = self::getInstance('myCache', 'myCache_'.$this->setting->gen->cache_mode, $cache_setting);
        }
        if(empty($db)) {
            $sqlite = strtolower($this->setting->db->type) === 'sqlite';
            if($sqlite) {
                // SQLite 的第二个初始化参数是打开标志，不能传入 MySQL 用户名。
                $file = $this->setting->db->host === ':memory:' ? ':memory:' : myPath::absolute($this->setting->db->host);
                $db = self::getInstance('myDb', $this->setting->db->type, $file);
            } else {
                $db = self::getInstance('myDb', $this->setting->db->type, $this->setting->db->host, $this->setting->db->user, $this->setting->db->password, $this->setting->db->charset);
            }
            if($this->setting->db->auto) {
                if($sqlite) $db->connect();
                else $db->connect($this->setting->db->pconnect, $this->setting->db->name);
                $db->setCache($cache, 600);
            } elseif(in_array($this->setting->session->mode, ['sess_mysql','sess_mystep'], true)) {
                $this->setting->session->mode='sess_file';
            }
        }
    }

    /**
     * 功能说明：清空框架数据文件及当前缓存后端，供后台和更新器共同使用。
     *
     * 使用驱动clear保留活动锁；SQL/Memcached后端也执行显式清空。
     * Redis只清理当前站点及namespace的缓存值，保留Redis会话和业务键。
     * 同目录/同表/同Memcached服务的其他namespace可能受影响，并发请求可重建数据。
     *
     * 参数说明：无显式参数。
     *
     * @return bool 本地数据目录和当前后端均清空成功为true
     * @throws BadMethodCallException 自定义后端未提供显式clear能力
     * @throws Throwable 驱动异常原样传递，调用方不应提示清理成功
     */
    public static function clearDataCache() {
        global $cache;
        $files=new myCache('File', CACHE.'data', 0);
        $ok=$files->clear();
        if($cache instanceof myCache) $ok=$cache->clear() && $ok;
        return $ok;
    }

    /**
     * 功能说明：通过模板类显示页面
     *
     *
     * 输出约定：填充框架变量与资源标签后调用父类输出，并标记已展示；不自动调用end。仅需字符串时使用render。
     *
     * @param myTemplate $tpl 已经初始化的模板实例
     * @param array $append_pare 在渲染时补充的模板变量（默认：[]）
     * @return void 无返回值。
     */
    public function show(myTemplate $tpl, $append_pare = []) {
        global $info_app;
        $paras = [
            'web_title' => $this->setting->web->title,
            'web_url' => $this->setting->web->url,
            'page_keywords' => $this->setting->web->keyword,
            'page_description' => $this->setting->web->description,
            'charset' => $this->setting->gen->charset,
            'app' => $info_app['app'],
            'path_root' => ROOT_WEB,
            'path_app' => str_replace(myFile::rootPath(), '/', PATH),
            'url_prefix' => self::$url_prefix,
            'url_prefix_app' => self::$url_prefix.(defined('URL_FIX')?'':($info_app['app'].'/')),
        ];
        if(!empty($append_pare)) {
            $paras = array_merge($paras, $append_pare);
        }
        foreach($paras as $k => $v) {
            $tpl->assign($k, $v);
        }
        $this->setAddedContent('start', '<script type="application/javascript">const root_web="'.ROOT_WEB.'";</script>');
        if(gettype(self::$cache_css)=='string' && self::$cache_css!=='') {
            $this->CSS(self::$cache_css);
            $this->setAddedContent('start', '<link rel="stylesheet" media="screen" type="text/css" href="'.ROOT_WEB.'cache/script/'.basename(self::$cache_css).'?v='.substr(hash_file('sha256', self::$cache_css),0,16).'" />');
        }
        if(gettype(self::$cache_js)=='string' && self::$cache_js!=='') {
            $this->JS(self::$cache_js);
            $this->setAddedContent('start', '<script type="application/javascript" src="'.ROOT_WEB.'cache/script/'.basename(self::$cache_js).'?v='.substr(hash_file('sha256', self::$cache_js),0,16).'"></script>');
        }
        $this->setAddedContent('end', '<script type="application/javascript">$(ms_func_run);</script>');

        $variants = '';
        if(!empty(self::$variant_alias)) {
            $variants = implode(',', self::$variant_alias);
        }
        $variants .= (empty($variants) ? '' : ',').'ms_setting,db,cache,mystep';
        parent::show($tpl, $this->setting->web->minify, $variants);
        self::$shown = true;
    }

    /**
     * 功能说明：通过模板类输出页面内容
     *
     * @param myTemplate $tpl 已经初始化的模板实例
     * @param array $append_pare 在渲染时补充的模板变量（默认：[]）
     * @return string 模板最终输出文本，附加参数先按当前方法规则赋值
     */
    public function render(myTemplate $tpl, $append_pare = []) {
        global $info_app;
        $paras = [
            'path_root' => ROOT_WEB,
            'path_app' => str_replace(myFile::rootPath(), '/', PATH),
            'url_prefix' => self::$url_prefix,
            'url_prefix_app' => self::$url_prefix.(defined('URL_FIX')?'':($info_app['app'].'/')),
        ];
        if(!empty($append_pare)) {
            $paras = array_merge($paras, $append_pare);
        }
        foreach($paras as $k => $v) {
            $tpl->assign($k, $v);
        }
        $tpl->assign('lng', $this->language);
        $tpl->regTag($this->func_tag);

        $variants = '';
        if(!empty(self::$variant_alias)) {
            $variants = implode(',', self::$variant_alias);
        }
        $variants .= (empty($variants) ? '' : ',').'ms_setting,db,cache,mystep';
        return call_user_func_array([$tpl, 'render'], [$variants, false, false]);
    }

    /**
     * 功能说明：检查模版缓存并输出
     *
     *
     * 命中页面缓存时输出当前会话装饰后的HTML并调用end终止；未命中时继续返回业务流程。
     *
     * @param myTemplate $tpl 已经初始化的模板实例
     * @return void 无返回值。
     */
    public function checkCache(myTemplate $tpl) {
        if($tpl->checkCache()) {
            echo mySecurity::decorate($tpl->getCache());
            $this->end();
        }
    }

    /**
     * 功能说明：框架终止，销毁相关变量
     *
     * 参数说明：无显式参数。
     *
     *
     * 生命周期：关闭全局数据库、记录耗时/内存、按需压缩输出并exit；调用后不会回到业务代码。
     *
     * @return void 无返回值。
     */
    public function end() {
        parent::end();
        $this->editorGetPlugin();
        $query_count = is_object($GLOBALS['db']) ? $GLOBALS['db']->close() : 0;
        $time_exec = getTimeDiff($this->time_start);
        $mem_peak = memory_get_peak_usage();
        unset($GLOBALS['db'], $GLOBALS['cache']);
        if(self::$shown) $this->gzOut($this->setting->web->gzip_level, $query_count, $time_exec, $mem_peak);
        exit();
    }

    /**
     * 功能说明：页面压缩
     *
     * @param int $level gzip压缩级别，发送时限制到0至9；负数跳过压缩（默认：3）
     * @param int $query 本次请求的数据库查询次数（默认：0）
     * @param int|float $time 本次请求耗时，单位毫秒（默认：0）
     * @param int $mem 内存使用量，单位字节（默认：0）
     * @return void 无返回值。
     */
    public function gzOut($level = 3, $query = 0, $time = 0, $mem = 0) {
        $accepted = [];
        foreach(explode(',', strtolower(myReq::server('HTTP_ACCEPT_ENCODING') ?? '')) as $part) {
            $parameters = explode(';', trim($part));
            $name = trim(array_shift($parameters));
            $quality = 1.0;
            foreach($parameters as $parameter) {
                if(preg_match('/^\s*q\s*=\s*([0-9.]+)\s*$/', $parameter, $match)) $quality = (float)$match[1];
            }
            $accepted[$name] = $quality;
        }
        $quality = $accepted['gzip'] ?? $accepted['x-gzip'] ?? $accepted['*'] ?? 0;
        if(!headers_sent()) header('Vary: Accept-Encoding', false);
        if($level < 0 || $quality <= 0 || headers_sent() || connection_aborted()) {
            if(ob_get_level() > 0) ob_end_flush();
            return;
        }
        $content = ob_get_contents();
        if($content === false) return;
        ob_end_clean();
        $content .= '
<!--
Powered By MyStep Framework
'.$this->getLanguage('info_memory').myFile::formatSize($mem).'
'.$this->getLanguage('info_compressmode').'gzip'.'
'.$this->getLanguage('info_querycount').(empty($query)?0:$query).'
'.$this->getLanguage('info_exectime').$time.'ms'.'
'.$this->getLanguage('info_cacheuse').$this->setting->gen->cache_mode.'
-->';
        header('Content-Encoding: gzip');
        echo gzencode($content, max(0, min(9, (int)$level)));
    }

    /**
     * 功能说明：添加页面CSS文件
     *
     * @param string $file 目标文件路径
     * @param bool $code true把file视为CSS源码，false视为CSS文件路径（默认：false）
     * @return $this 登记有效CSS文件或直接源码后返回当前实例
     */
    public function addCSS($file, $code=false) {
        if($code) {
            $this->css[md5($file)] = $file;
        } else {
            if(is_file($file)) {
                $time = filemtime($file);
                if($time>$this->time_css) $this->time_css = $time;
                $this->css[md5_file($file)] = $file;
            }
        }
        return $this;
    }

    /**
     * 功能说明：生成整合CSS文件
     *
     * @param string $cache 压缩结果缓存文件路径，空串不写文件（默认：''）
     * @param mixed $dummy_1 继承兼容占位参数，本实现不使用（默认：''）
     * @param mixed $dummy_2 继承兼容占位参数，本实现不使用（默认：''）
     * @return $this 返回当前实例，支持链式调用。
     */
    public function CSS($cache='', $dummy_1='', $dummy_2='') {
        $minify = new myMinify('css', $cache);
        foreach($this->css as $file) $minify->add($file);
        $minify->get();
        return $this;
    }

    /**
     * 功能说明：添加页面脚本文件
     *
     * @param string $file 目标文件路径
     * @param bool $code true把file视为JS源码，false视为JS文件路径（默认：false）
     * @return $this 登记有效JavaScript文件或直接源码后返回当前实例
     */
    public function addJS($file, $code=false) {
        if($code) {
            $this->js[md5($file)] = $file;
        } else {
            if(is_file($file)) {
                $time = filemtime($file);
                if($time>$this->time_js) $this->time_js = $time;
                $this->js[md5_file($file)] = $file;
            }
        }
        return $this;
    }

    /**
     * 功能说明：整合页面脚本文件
     *
     * @param string $cache 压缩结果缓存文件路径，空串不写文件（默认：''）
     * @param mixed $dummy_1 继承兼容占位参数，本实现不使用（默认：''）
     * @param mixed $dummy_2 继承兼容占位参数，本实现不使用（默认：''）
     * @return $this 生成/复用整合脚本缓存后返回当前实例，不直接返回脚本文本
     */
    public function JS($cache='', $dummy_1='', $dummy_2='') {
        $minify = new myMinify('js', $cache);
        foreach($this->js as $code) $minify->add($code);
        $minify->get();
        return $this;
    }

    /**
     * 功能说明：富文本编辑器功能添加
     *
     * @param string $code 注册编辑器插件时插入init函数的受信JavaScript源码
     * @param string $btn 编辑器工具栏按钮配置（默认：''）
     * @return $this 返回当前实例，支持链式调用。
     */
    public function editorSetPlugin($code, $btn='') {
        if(is_file($code)) $code = myFile::getLocal($code);
        $this->editor_plugin[] = $code;
        if(!empty($btn)) $this->editor_btn[] = $btn;
        return $this;
    }

    /**
     * 功能说明：生成编辑器插件缓存脚本
     *
     * 参数说明：无显式参数。
     *
     * @return $this 返回当前实例，支持链式调用。
     */
    public function editorGetPlugin() {
        global $info_app;
        $cache = CACHE.'script/editor_plugin_'.$info_app['app'].'.js';
        if(!is_file($cache)) {
            $content = implode(chr(10), $this->editor_plugin);
            $btn = implode(' ', $this->editor_btn);
            $content = <<<code
tinymce.create('tinymce.plugins.myStep', {
    init : function(editor, url) {
        {$content}
    },
    createControl : function(n, cm) {
        return null;
    },
});
tinymce.PluginManager.add('myStep', tinymce.plugins.myStep);
global.editor_btn = '{$btn}';
code;
            myFile::saveFile($cache, $content);
        }
        return $this;
    }

    /**
     * 功能说明：信息提示，需先声明mystep类
     *
     *
     * 输出约定：清理当前缓冲并输出提示页，调用end后exit；不应把此入口当作可返回的错误字符串函数。
     *
     * @param string|array $msg 语言键、消息文本或错误结构
     * @param string $url 显示消息后的跳转目标，空串沿用当前策略（默认：''）
     * @return void 无返回值。
     */
    public static function info($msg, $url = '') {
        global $mystep, $ms_setting;
        ob_end_clean();
        if($mystep==null) {
            $GLOBALS['no_log'] = true;
            $mystep = new myController();
            $mystep->setLanguagePack(APP.'myStep/language/', $ms_setting->gen->language);
            $paras = [
                'web_title' => $ms_setting->web->title,
                'web_url' => (isHttps()?'https://':'http://').myReq::server('HTTP_HOST'),
                'charset' => $ms_setting->gen->charset,
                'path_root' => str_replace(myFile::rootPath(), '/', ROOT),
                'lng_page_info' => $mystep->getLanguage('page_info'),
                'lng_page_info_refresh' => $mystep->getLanguage('page_info_refresh'),
            ];
        } else {
            $paras = [];
        }
        $t = new myTemplate(array(
            'name' => 'info',
            'path' => APP.'myStep/template/',
            'path_compile' => CACHE.'/template/myStep/'
        ));
        if(empty($url)) {
            $url = myReq::server('HTTP_REFERER');
            if(is_null($url)) $url = '/';
        }
        $msg = $mystep->getLanguage($msg);
        foreach($paras as $k => $v) {
            $t->assign($k, $v);
        }
        $t->assign('msg', $msg);
        $t->assign('url', self::setURL($url));
        $mystep->show($t);
        $mystep->end();
        exit;
    }

    /**
     * 功能说明：生成验证码
     *
     *
     * 输出约定：需要Referer及Cookie才继续，写入当前会话captcha并输出图片；条件不满足时静默返回。
     *
     * @param int $len 生成验证码的字符数（默认：4）
     * @param int $scope 验证码字符池/生成范围选项（默认：3）
     * @return void 无返回值。
     * @see myString::rndKey() 字符池scope的取值约定
     */
    public static function captcha($len = 4, $scope = 3) {
        if(is_null(r::svr('referer')) || count($_COOKIE)==0) return;
        $str = myString::rndKey($len, $scope);
        myReq::session('captcha', $str);
        $img = new myImg();
        $img->captcha($str, STATICS.'fonts/font.ttc', 24);
    }

    /**
     * 功能说明：JS语言包接口
     *
     * @param string $app_name 已安装应用的名称
     * @param string $type 语言包名，默认由调用入口决定（默认：'default'）
     * @return void 无返回值。
     */
    public static function language($app_name, $type='default') {
        header('Content-Type: application/x-javascript');
        $type = preg_replace('#&.+$#', '', $type);
        $cache = CACHE.'language/'.$app_name.'_'.trim($type, '/').'.js';
        if(is_file($cache)) {
            $result = myFile::getLocal($cache);
        } else {
            $dir = APP.'myStep/language/';
            if(!is_file($dir.'/'.$type.'.php')) $type='default';
            if(is_file($dir.'/'.$type.'.php')) {
                $language = include($dir.'/'.$type.'.php');
                if($language==1) $language = array();
            }
            if($app_name!='myStep') {
                $dir = APP.$app_name.'/language/';
                if(!is_file($dir.'/'.$type.'.php')) $type='default';
                if(is_file($dir.'/'.$type.'.php')) {
                    $language = array_merge($language, include($dir.'/'.$type.'.php'));
                }
            }
            $result = 'var language = '.myString::toJson($language).';';
            myFile::saveFile($cache, $result, 'wb');
            unset($language);
        }
        header('Accept-Ranges: bytes');
        header('Accept-Length: '.strlen($result));
        echo $result;
        exit;
    }

    /**
     * 功能说明：JS设置信息接口
     *
     * 上传限制先按INI单位解析为字节，忽略表示无限制的非正值；max_size继续以MB提供给旧组件，
     * max_size_bytes为精确字节数（0表示PHP这两项均无限制）。均无限制时旧组件保留32MB默认值。
     * 实际上传还需通过服务端应用配置、文件类型和权限检查。
     *
     * @param string $app_name 已安装应用的名称
     * @return void 无返回值。
     */
    public static function setting($app_name) {
        $app_name = preg_replace('#&.+$#', '', $app_name);
        if(empty($app_name) || !is_dir(APP.$app_name)) $app_name='myStep';
        $setting = new myConfig(CONFIG.'config.php');
        if($app_name!='myStep') {
            $setting->merge(APP.$app_name.'/config.php');
        }
        $limits=[];
        foreach(['post_max_size','upload_max_filesize'] as $option) {
            $limit=myFile::getByte(ini_get($option));
            if($limit>0) $limits[]=$limit;
        }
        $max_bytes=$limits ? min($limits) : 0;
        $setting = myConfig::o2a($setting);
        $setting_js = array(
            'language' => $setting['setting']['gen']['language'],
            'debug' => $setting['setting']['gen']['debug'],
            'app' => trim($app_name, '/'),
            'path_root' => ROOT_WEB,
            'path_app' => str_replace(myFile::rootPath(), '/', APP.$app_name),
            'max_size' => $max_bytes>0 ? $max_bytes/1048576 : 32,
            'max_size_bytes' => $max_bytes,
            'url_fix' => defined('URL_FIX')?URL_FIX:'',
            'url_prefix' => self::$url_prefix,
            'url_prefix_app' => self::$url_prefix.(defined('URL_FIX')?'':$app_name),
        );
        if(isset($setting['setting']['js'])) $setting_js = array_merge($setting_js, $setting['setting']['js']);

        $result = 'var setting = '.myString::toJson($setting_js).';';
        header('Content-Type: application/x-javascript');
        header('Accept-Ranges: bytes');
        header('Accept-Length: '.strlen($result));
        echo $result;
        exit;
    }

    /**
     * 功能说明：按当前来源、绑定域名、Cookie域及额外白名单检查API来源
     *
     * @param string $origin 待检查的Origin请求头，必须为合法完整来源地址
     * @param string $current 当前请求的完整来源地址（协议、主机和端口）
     * @param array $domains 框架配置的域名绑定规则
     * @param string $cookie_domain 配置的Cookie域，参与同站来源判定（默认：''）
     * @param array $extra 额外明确允许的完整来源列表（默认：[]）
     * @return bool 来源符合允许规则为true，否则为false
     */
    public static function apiOriginAllowed($origin, $current, array $domains, $cookie_domain = '', array $extra = []) {
        if(!is_string($origin) || preg_match('/[\x00-\x20]/', $origin)) return false;
        $source = parse_url($origin);
        $target = parse_url($current);
        if(!$source || !$target || !in_array($source['scheme'] ?? '', ['http', 'https'], true)
            || isset($source['user']) || isset($source['pass']) || isset($source['query']) || isset($source['fragment'])
            || !empty($source['path']) || empty($source['host'])) return false;
        if(in_array($origin, $extra, true)) return true;
        if($source['scheme'] !== $target['scheme']) return false;
        $default_port = $source['scheme'] === 'https' ? 443 : 80;
        if(($source['port'] ?? $default_port) !== ($target['port'] ?? $default_port)) return false;
        $host = strtolower($source['host']);
        return $host === strtolower($target['host']) || isset($domains[$host]) || ($cookie_domain !== '' && $host === strtolower(ltrim($cookie_domain, '.')));
    }

    /**
     * 功能说明：框架API执行接口
     *
     * 已登记插件API按需加载标准class.php，不依赖当前宿主应用绑定；不额外执行插件index.php。
     * 活动插件API仍由路由注册表筛选；独立外部API映射的既有可调用处理器继续兼容。
     *
     * 未指定输出格式的插件接口默认使用JSON，避免无尾段路径被误判为非法格式。
     *
     * @param string $path 包含应用/接口/输出格式的API路由路径
     * @return void 无返回值。
     * @throws InvalidArgumentException 插件API主类路径或标识无效
     * @throws Throwable 插件主类加载或接口执行异常继续传递
     */
    public static function api($path) {
        global $ms_setting, $domain;
        $http=myApi::httpConfiguration();
        myApi::httpCors($http,$path);
        if(strpos($path, 'myStep/plugin_')===0) $path = substr($path, 7);
        $path = preg_replace('#&.+$#', '', $path);
        $path = explode('/', trim($path, '/'));
        $app_name = $path[0];
        global $router;
        if($router instanceof myRouter) $api_list = $router->apis;
        else include(CONFIG.'route.php');
        $result = '{"error":"Module is Missing!"}';
        if(isset($api_list) && isset($path[1])) {
            if(isset($api_list[$app_name])) {
                if(strpos($path[0], 'plugin_')!==0) {
                    $name = $path[1];
                    array_shift($path);
                } else {
                    $plugin = array_shift($path);
                    $name = array_shift($path);
                }
            } else {
                $name = $app_name;
                $app_name = 'myStep';
            }
            $flag = false;
            if(isset($plugin) && isset($api_list[$plugin][$name])) {
                // 独立API不依赖宿主plugin.php绑定；仅加载主类，不执行index.php或start钩子。
                $plugin_id=substr($plugin,7);
                if(is_file(PLUGIN.$plugin_id.'/class.php')) {
                    require_once PLUGIN.'interface_plugin.class.php';
                    $class_file=pluginDirectory($plugin_id).'class.php';
                    if(is_link($class_file)) throw new InvalidArgumentException('Invalid plugin API class');
                    require_once $class_file;
                }
                $ms_setting->merge(APP.$app_name.'/config.php');
                $method = $api_list[$plugin][$name];
                $type = end($path);
                if($type===false) $type=$http['default_format'];
                $flag = true;
            } elseif(isset($api_list[$app_name][$name])) {
                $ms_setting->merge(APP.$app_name.'/config.php');
                $method = $api_list[$app_name][$name];
                $type = end($path);
                if($type===false) $type=$http['default_format'];
                $path = array_slice($path, 1);
                $flag = true;
            }
            if($flag) {
                $policy=myApi::httpPolicy($plugin??$app_name,$name,$http);
                list($type,$path)=myApi::httpArguments($path,$policy,$http,$type);
                if(is_file(APP.$app_name.'/lib.php')) require_once(APP.$app_name.'/lib.php');
                if(is_callable($method)) {
                    $api = new myApi();
                    $api->regMethod($name, $method);
                    $result = call_user_func([$api, 'run'], $name, $path, $type, $ms_setting->gen->charset);
                }
            }
        }
        $tmp = getOB(true);
        if(!empty($tmp)) $result = $tmp;
        echo $result;
        exit;
    }

    /**
     * 功能说明：框架模块调用接口
     *
     * @param string $path 应用模块调用路径
     * @param mixed $dummy 继承兼容占位参数，本实现不使用（默认：''）
     * @return void 无返回值。
     */
    public static function module($path, $dummy = '') {
        global $mystep, $ms_setting;
        $path = explode('/', trim($path, '/'));
        if(isset($path[1]) && is_file(APP.$path[0].'/lib.php')) {
            $name = $path[0].'_'.$path[1];
            require_once(APP.$path[0].'/lib.php');
        } else {
            $name = $path[0];
        }
        if(isset(self::$modules[$name])) {
            global $db, $cache, $router, $info_app, $tpl_setting, $tpl_cache;
            if(is_string(self::$modules[$name]) && is_file(self::$modules[$name])) {
                require(self::$modules[$name]);
                if(isset($tpl) && ($tpl instanceof myTemplate)) {
                    $path_app = APP.$info_app['app'].'/';
                    if(is_file($path_app.'global.php')) include_once($path_app.'global.php');
                    if(is_file($path_app.'lib.php')) include_once($path_app.'lib.php');
                    if(is_file($path_app.'plugin_addon.php')) include_once($path_app.'plugin_addon.php');
                    $tpl->assign('main', $content??'');
                    $mystep->show($tpl);
                }
            } elseif(is_callable(self::$modules[$name])) {
                call_user_func(self::$modules[$name]);
            } else {
                self::redirect('/');
            }
            exit();
        } else {
            self::redirect('/');
        }
    }

    /**
     * 功能说明：应用自定义功能接口（sitemap, atom, rss等）
     *
     * @param string $func 当前核心应用下自定义PHP入口名，例如sitemap；会移除非单词字符
     * @return void 无返回值。
     */
    public static function appFunc($func) {
        $app = self::getCore();
        $func = preg_replace('#\W#', '', $func);
        $file = APP.$app.'/'.$func.'.php';
        if(is_file($file)) {
            global $info_app;
            $mystep = new $app();
            $info_app = include(APP.$app.'/info.php');
            if(is_file(APP.$app.'/lib.php')) include(APP.$app.'/lib.php');
            include($file);
        } else {
            self::redirect('/');
        }
    }

    /**
     * 功能说明：文件上传
     *
     * 参数说明：无显式参数。
     *
     * @return array 各上传项的结果，非POST请求返回错误结构
     */
    public static function upload() {
        if(isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            return ['error'=>'-1', 'message'=>'POST required'];
        }
        if(self::checkPower('upload') && myReq::check('files')) {
            global $ms_setting;
            $path = FILE.date($ms_setting->upload->path_mode);
            set_time_limit(0);
            $upload = new myUploader($path, true, $ms_setting->upload->ban_ext);
            $allowed = $ms_setting->upload->allowed_ext ?? 'jpg,jpeg,png,gif,webp,bmp,pdf,txt,csv,zip,7z,rar,doc,docx,xls,xlsx,ppt,pptx,mp3,mp4,wav,ogg,webm';
            $upload->setLimits((int)myFile::getByte($ms_setting->upload->max_size ?? '32M'), $allowed);
            $upload->do(false);
            foreach($upload->result as &$item) {
                if((string)$item['error'] !== '0') continue;
                $item['name'] = myString::substr(myString::setCharset($item['name'], $ms_setting->gen->charset), 0, 160);
                if(!myAttachment::append($path, $item['new_name'], $item['name'])) {
                    unlink($path.'/'.$item['new_name']);
                    $item = ['error'=>'-1', 'message'=>'Cannot record upload'];
                }
            }
            unset($item);
            $result = $upload->result[0] ?? ['error'=>'-1', 'message'=>'No upload received'];
        } else {
            $result = ['error' => '-1', 'message' => 'Upload Denied!'];
        }
        return $result;
    }

    /**
     * 功能说明：文件下载
     *
     * @param string $idx 附件唯一标识，允许完整存储名或框架兼容标识（默认：''）
     * @return void 无返回值。
     */
    public static function download($idx='') {
        global $ms_setting;
        if(get_called_class()::checkPower('download')) {
            $record = myAttachment::find(FILE, $idx, $ms_setting->upload->path_mode);
            if($record) {
                myController::file($record['file'], $record['name']);
                exit;
            }
        }
        self::header('404');
    }

    /**
     * 功能说明：删除上传文件
     *
     * @param string $idx 附件唯一标识，允许完整存储名或框架兼容标识（默认：''）
     * @return array 含error/message的删除结果；操作受请求方法、标识及路径验证约束
     * @phpstan-return array{error:string,message:string}
     */
    public static function remove_ul($idx='') {
        if(isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
            http_response_code(405); header('Allow: POST');
            return ['error'=>'-1', 'message'=>'POST required'];
        }
        global $ms_setting;
        if(self::checkPower('remove_ul') && myAttachment::remove(FILE, $idx, $ms_setting->upload->path_mode)) {
            return ['error'=>'0', 'message'=>'The File has been removed!'];
        }
        return ['error'=>'-1', 'message'=>'Cannot remove the file!'];
    }

    /**
     * 功能说明：处理头信息
     *
     * @param string|int $idx 状态码或已支持的响应头类别
     * @param string|array $para 响应头模板的替换值；数组用于refresh等多值模板（默认：''）
     * @param bool $exit 是否在发出响应头后结束请求（默认：true）
     * @return void 发送状态/响应头或进入错误提示流程；按exit决定是否结束请求
     */
    public static function header($idx, $para = '', $exit = true) {
        global $mystep;
        if(in_array((string)$idx, ['403', '404', '500'], true)) {
            http_response_code((int)$idx);
            if(!$exit || !$mystep) return parent::header((string)$idx, $para, $exit);
        }
        switch($idx) {
            case '404':
                myStep::info(sprintf($mystep->getLanguage('page_error_404'), myReq::server('REQUEST_URI')));
                break;
            case '403':
            case '500':
                myStep::info('page_error_'.$idx);
                break;
            default:
                parent::header($idx, $para, $exit);
        }
    }

    /**
     * 功能说明：链接处理
     *
     * @param string $url 应用内部路径或外部完整URL，按当前路由模式生成链接（默认：''）
     * @return string 按当前绑定/路由策略转换的URL文本；空路径返回可继续拼接功能路径的前缀
     */
    public static function setURL($url='') {
        global $ms_setting;
        if(preg_match('#^(?:[a-z][a-z0-9+.-]*:|//)#i', $url)) return $url;
        $length = strcspn($url, '?#');
        $path = substr($url, 0, $length);
        $suffix = substr($url, $length);
        if(preg_match('#(?:^|/)index\.php(?:/|$)#', $path)) return $url;
        $base = rtrim(ROOT_WEB, '/');
        if($base !== '' && myRouter::hasPrefix($path, $base)) $path = substr($path, strlen($base));
        $path = preg_replace('#/+#', '/', '/'.ltrim($path, '/'));
        if($url === '') $path = '';
        switch($ms_setting->router->mode) {
            case 'path_info': return ROOT_WEB.'index.php'.($path === '' ? '/' : $path).$suffix;
            case 'query_string':
                if(isset($suffix[0]) && $suffix[0] === '?') $suffix = '&'.substr($suffix, 1);
                return ROOT_WEB.'index.php?'.$path.$suffix;
            default: return $path === '' ? ROOT_WEB.$suffix : ROOT_WEB.ltrim($path, '/').$suffix;
        }
    }

    /**
     * 功能说明：链接跳转
     *
     * @param string $url 跳转目标；空串回到Referer或默认地址（默认：''）
     * @param string|int $code 重定向HTTP状态码，默认302（默认：'302'）
     * @return void 无返回值。
     */
    public static function redirect($url = '', $code = '302') {
        if(empty($url)) {
            $url = myReq::server('HTTP_REFERER');
            if (is_null($url)) $url = '/';
            $code = '302';
        } else {
            $url = self::setURL($url);
        }
        if(!empty($GLOBALS['db'])) $GLOBALS['db']->close();
        unset($GLOBALS['db'], $GLOBALS['cache']);
        header('location: ' . $url, true, $code);
        exit;
    }

    /**
     * 功能说明：框架变量初始化
     *
     * 参数说明：无显式参数。
     *
     * Web请求在类加载注册后、缓存清理和应用/会话启动前检查已解析输入预算；
     * PHP解析器已可能处理请求，此检查不能替代运行时补丁及Web服务器限制。
     *
     * @return void 设置核心路径、配置、自动加载及请求上下文；超预算请求返回400/413并结束
     * @throws InvalidArgumentException 受信的MYSTEP_REQUEST_LIMITS配置无效
     * @throws RuntimeException 无法完整发布类索引
     */
    public static function init() {
        if(count(ob_list_handlers())>0) ob_end_clean();
        ob_start();
        $registry = myClassLoader::load(CONFIG.'class.php');
        self::regClass($registry['rules']);
        // 这里只能检查PHP已解析的输入；服务器仍须在解析前限制请求并修补PHP。
        if(PHP_SAPI!=='cli' && PHP_SAPI!=='phpdbg') mySecurity::guardRequest();
        if($registry['reset']) {
            myFile::del(CONFIG.'route.php');
            myFile::del(CACHE.'template');
        }
        define('ROOT_WEB', str_replace(myFile::rootPath(), '/', ROOT));

        myException::init(array(
            'log_mode' => 0,
            'log_type' => E_ALL ^ E_NOTICE,
            'log_file' => ROOT.'/error.log',
            'callback_type' => E_ALL & ~(E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE | E_NOTICE),
            'exit_on_error' =>  true
        ));

        if(is_file(CONFIG.'config.php')) {
            self::go();
        } else {
            $initial_router = new myRouter(['mode'=>'rewrite']);
            $path = ltrim($initial_router->route['p'], '/');
            if(strpos($path, 'static/') === 0) {
                $file = myFile::resolveWithin(STATICS, substr($path, 7));
                if(!$file || !in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['js','css','png','jpg','jpeg','gif','ico','svg','woff','woff2','ttf','eot','otf','ttc'], true)) myController::header('404');
                myController::file($file);
            }
            require(APP.'myStep/module/init.php');
        }
    }

    protected static $active_plugins = null, $known_plugins = [], $plugin_signature = null;

    /**
     * 功能说明：在普通插件入口前加载显式选择的存储插件，不要求应用绑定。
     * @param string $name 插件目录标识，仅允许字母开头的字母、数字、下划线
     * @return string 已检查扩展并加载驱动的插件主类名
     * @throws InvalidArgumentException 插件目录缺失、未安装/未启用、扩展不满足或加载协议无效
     */
    public static function storagePlugin($name) {
        if(!is_string($name) || !preg_match('/\A[a-zA-Z][a-zA-Z0-9_]*\z/D',$name))throw new InvalidArgumentException('存储插件标识无效。');
        $dir=PLUGIN.$name.'/';
        if(is_link(rtrim($dir,'/')) || !is_file($dir.'class.php') || !is_file($dir.'info.php'))throw new InvalidArgumentException($name.'存储插件目录缺失，请恢复插件文件。');
        if(!isset(self::activePlugins()[$name]))throw new InvalidArgumentException($name.'存储插件未安装或未启用，请在插件管理中安装并启用后再选择缓存或Session。');
        require_once PLUGIN.'interface_plugin.class.php';
        require_once $dir.'class.php';
        $class='plugin_'.$name;$reason='';
        if(!is_subclass_of($class,'interface_plugin') || !is_callable([$class,'load']))throw new InvalidArgumentException($name.'存储插件缺少加载协议。');
        if(!$class::check($reason))throw new InvalidArgumentException($reason);
        $class::load();
        return $class;
    }

    /**
     * 功能说明：校验框架显式选择的Redis插件存储；未选用时不加载、不连接插件。
     * @param myConfig $config 待保存的框架或应用配置
     * @param bool $probe true在保存前PING验证连接，失败不保存（默认：false）
     * @return void 无返回值，不改写配置文件或切换活动会话
     * @throws InvalidArgumentException 所选Redis插件不可用或连接检查失败
     */
    public static function configureStorage(myConfig $config,$probe = false) {
        if(strtolower((string)$config->value('gen.cache_mode',''))!=='redis' && $config->value('session.mode','')!=='sess_redis')return;
        $class=self::storagePlugin('redis');
        if($probe){$options=new myConfig(PLUGIN.'redis/config.php');$class::validateConfig($options);}
    }

    /**
     * 功能说明：按注册表 order 从大到小收集活动插件，内容未变时复用结果
     *
     * 参数说明：无显式参数。
     *
     * 相同 order 保持注册表原顺序；文件内容变化后在当前请求内重新读取。
     *
     * @return array 按执行顺序排列的插件标识到 true 的字典
     */
    public static function activePlugins() {
        $file = PLUGIN.'plugin.db';
        clearstatcache(true, $file);
        $signature = is_file($file) ? hash_file('sha256', $file) : null;
        if(self::$active_plugins !== null && self::$plugin_signature === $signature) return self::$active_plugins;
        self::$plugin_signature = $signature;
        self::$active_plugins = self::$known_plugins = [];
        if($signature === null) return self::$active_plugins;
        $registry = new myDb('simpleDB', 'plugin', PLUGIN);
        try {$records = $registry->select() ?: [];} finally {$registry->close();}
        foreach($records as $position => &$record) $record['_position'] = $position;
        unset($record);
        usort($records, static function($left, $right) {
            $order = (int)($right['order'] ?? 0) <=> (int)($left['order'] ?? 0);
            return $order ?: $left['_position'] <=> $right['_position'];
        });
        foreach($records as $record) {
            $name = trim((string)($record['idx'] ?? ''));
            if(!preg_match('/\A[a-zA-Z][a-zA-Z0-9_]*\z/D', $name)) continue;
            self::$known_plugins[$name] = true;
            if(trim((string)($record['active'] ?? '')) === '1') self::$active_plugins[$name] = true;
        }
        return self::$active_plugins;
    }

    /**
     * 功能说明：内容指纹监测路由源、应用发现结果及配置，变更时加锁重建，否则复用注册表
     *
     * 参数说明：无显式参数。
     *
     * @return void 无返回值。
     * @throws UnexpectedValueException 显式插件路由映射或条目无效，原编译表保持不变
     * @throws RuntimeException 路由注册表无法加锁或完整写入
     * @throws Throwable 自定义解析器、初始化/反射目标或受信执行文件产生的异常继续传递
     */
    public static function refreshRoutes() {
        global $ms_setting;
        $main = CONFIG.'route.php';
        $metadata = $main.'.sources.json';
        $applications = [];
        $mapping = CONFIG.'route_plugin.php';
        $plugins = [];
        if(is_file($mapping)) {
            if(function_exists('opcache_invalidate')) opcache_invalidate($mapping, true);
            $plugins = (static function($file) { return include($file); })($mapping);
            if(!is_array($plugins)) throw new UnexpectedValueException('Invalid plugin route mapping');
            foreach($plugins as $name => $file) if(!is_string($name) || !preg_match('/\A[a-zA-Z][a-zA-Z0-9_]*\z/D', $name) || !is_string($file) || strpos($file, "\0") !== false) throw new UnexpectedValueException('Invalid plugin route mapping entry');
            foreach($plugins as &$file) $file = myPath::absolute($file);
            unset($file);
        }
        $watch = [CONFIG.'config.php', $mapping, PLUGIN.'plugin.db'];
        foreach(glob(APP.'*/info.php') as $info) {
            $path = dirname($info).'/';
            $name = basename(dirname($info));
            if(!preg_match('/^\w+$/D', $name)) continue;
            $applications[$name] = $path;
            foreach(['route.php', 'config.php', 'plugin.php'] as $file) $watch[] = $path.$file;
        }
        // 显式源文件可能位于插件目录之外，也要纳入指纹；缺失源稍后出现也能触发重建。
        $watch = array_unique(array_merge($watch, glob(PLUGIN.'*/route.php'), array_values($plugins)));
        sort($watch);
        $signature = [];
        foreach($watch as $file) $signature[$file] = is_file($file) ? hash_file('sha256', $file) : null;
        $signature = hash('sha256', 'plugin-policy:2'.serialize($signature));
        $current = is_file($metadata) ? json_decode(file_get_contents($metadata), true) : [];
        if(is_file($main) && ($current['signature'] ?? '') === $signature && ($current['registry'] ?? '') === hash_file('sha256', $main)) return;
        $lock = fopen($main.'.lock', 'c');
        if(!$lock) throw new RuntimeException('Cannot lock route registry');
        if(!flock($lock, LOCK_EX)) {fclose($lock);throw new RuntimeException('Cannot lock route registry');}
        $original = $ms_setting;
        try {
            $current = is_file($metadata) ? json_decode(file_get_contents($metadata), true) : [];
            if(is_file($main) && ($current['signature'] ?? '') === $signature && ($current['registry'] ?? '') === hash_file('sha256', $main)) return;
            $formats = $rules = $apis = [];
            $read = function($file, $idx) use (&$formats, &$rules, &$apis) {
                $format = $rule = $api = [];
                if(function_exists('opcache_invalidate')) opcache_invalidate($file, true);
                include($file);
                if($format !== []) $formats[$idx] = $format;
                if($rule !== []) $rules[$idx] = $rule;
                if($api !== []) $apis[$idx] = $api;
            };
            $base = new myConfig(CONFIG.'config.php');
            foreach($applications as $name => $path) {
                if(!is_file($path.'route.php')) continue;
                $ms_setting = clone $base;
                if(is_file($path.'config.php')) $ms_setting->merge($path.'config.php');
                $read($path.'route.php', $name);
            }
            $ms_setting = $original;
            $active = [];
            foreach(self::activePlugins() as $name => $unused) $active[$name] = PLUGIN.$name.'/route.php';
            foreach(array_replace($active, $plugins) as $name => $file) {
                // 显式映射不能绕过已知插件的停用状态；独立外部路由源继续兼容。
                $known = isset(self::$known_plugins[$name]) || is_file(PLUGIN.$name.'/info.php');
                if((!$known || isset($active[$name])) && is_file($file)) $read($file, 'plugin_'.$name);
            }
            myRouter::saveRegistry($main, $formats, $rules, $apis);
            $temporary = $metadata.'.'.bin2hex(random_bytes(8)).'.tmp';
            $content = json_encode(['signature'=>$signature, 'registry'=>hash_file('sha256', $main)]);
            if(file_put_contents($temporary, $content, LOCK_EX) !== strlen($content) || !rename($temporary, $metadata)) {
                if(is_file($temporary)) unlink($temporary);
                throw new RuntimeException('Cannot save route metadata');
            }
        } finally {
            $ms_setting = $original;
            flock($lock, LOCK_UN); fclose($lock);
        }
    }

    /**
     * 功能说明：执行框架
     *
     * 加载配置、优先脚本及路由并分流请求。服务器环境变量
     * MYSTEP_PRESERVE_MEMORY_LIMIT=1时保留启动期memory_limit，不写入gen.memory；
     * 该开关本身不改变内存上限，外部应用仍须遵循服务器锁定的管理策略。
     * 全局api分组由框架配置补齐；专属域名在当前请求内提供API虚拟前缀，不改绑定文件。
     * @throws RuntimeException API专属域名与现有应用绑定冲突
     *
     * 参数说明：无显式参数。
     *
     * @return void 无返回值。
     */
    public static function go() {
        global $ms_setting, $router, $info_app, $tpl_setting, $tpl_cache, $mystep, $db, $cache, $host, $domain;
        $ms_setting = new myConfig(CONFIG.'config.php');
        self::configureStorage($ms_setting);
        $api=$ms_setting->value('api',null);
        if($api===null){$api=myApi::httpConfiguration();unset($api['endpoints']);}
        else {$api=myApi::httpSettings(is_object($api)?myConfig::o2a($api):$api);}
        // 正常页面有完整全局分组时不读取逐接口策略；旧站点仍保留兼容回退。
        $ms_setting->merge(['api'=>$api]);

        $uri = parse_url((string)myReq::svr('REQUEST_URI'), PHP_URL_PATH) ?: '/';
        if(preg_match('#^'.preg_quote(ROOT_WEB, '#').'(sitemap|atom|rss)(?:\.xml|/)$#i', $uri, $match)) {
            $query = parse_url((string)myReq::svr('REQUEST_URI'), PHP_URL_QUERY);
            header('Location: '.ROOT_WEB.strtolower($match[1]).($query ? '?'.$query : ''), true, 302);
            exit;
        }

        $scripts = myFile::find('*.php', CONFIG.'priority/', false, myFile::FILE);
        $priority = [];
        $active = self::activePlugins();
        foreach($scripts as $file) {
            if(is_link($file) || !preg_match('/\A(app|plugin)_([a-zA-Z][a-zA-Z0-9_]*)\z/D', pathinfo($file, PATHINFO_FILENAME), $match)) continue;
            list($unused, $type, $name) = $match;
            $directory = ($type === 'plugin' ? PLUGIN : APP).$name.'/';
            if(is_link(rtrim($directory, '/')) || !is_file($directory.'info.php')) continue;
            if($type === 'app') include($file);
            elseif(isset($active[$name])) $priority[$name] = $file;
        }
        foreach($active as $name => $unused) if(isset($priority[$name])) include($priority[$name]);

        // 仅服务器显式启用时保留启动期内存策略，避免把应急的-1改回有限值。
        if(getenv('MYSTEP_PRESERVE_MEMORY_LIMIT')!=='1') ini_set('memory_limit', $ms_setting->gen->memory);
        if($ms_setting->gen->debug) self::setOp('debug');
        $host = strtolower(preg_replace('/:\d+$/', '', (string)myReq::server('HTTP_HOST')));
        $domain = [];
        $ms_setting->router->url_fix = '';

        if(is_file(CONFIG.'domain.php')) {
            $domain = include(CONFIG.'domain.php');
            if(isset($domain[$host])) {
                $rule = $domain[$host];
                if(!preg_match('@^\w+$@', $rule)) {
                    $rule_2 = preg_replace('@^/(\w+)/.*$@', '\1', $rule);
                    $rule = ($rule==$rule_2) ? '' : $rule_2;
                }
                if(!empty($rule)) {
                    define('URL_FIX', $rule);
                    $ms_setting->router->url_fix = '/'.$rule;
                }
            }
        }
        if($ms_setting->api->domain!=='') {
            $apiHost=parse_url('http://'.$ms_setting->api->domain,PHP_URL_HOST);
            if($host===$apiHost) {
                if(defined('URL_FIX') && URL_FIX!=='api')throw new RuntimeException('API domain conflicts with an existing binding');
                if(!defined('URL_FIX'))define('URL_FIX','api');
                $ms_setting->router->url_fix='/api';
            }
            if(!isset($domain[$apiHost]))$domain[$apiHost]='/api/(\\w+)/(.*)';
        }
        $router = new myRouter((array)$ms_setting->router);
        $the_file = ROOT.preg_replace('#&.+$#', '', $router->route['p']);
        
        if(isset($router->info['path'][1]) && $router->info['path'][1]=='static') $the_file = str_replace('/'.$router->info['path'][0], '', $the_file);
        $ext = strtolower(pathinfo($the_file, PATHINFO_EXTENSION));
        $ext_list = explode(',', $ms_setting->gen->static);
        $request_file = ltrim($router->route['p'], '/');
        if(strpos($request_file, 'static/') === 0) {
            $public_file = myFile::resolveWithin(STATICS, substr($request_file, 7));
            if(!$public_file || !in_array(strtolower(pathinfo($public_file, PATHINFO_EXTENSION)), array_merge($ext_list, ['otf', 'ttc']), true)) myController::header('404');
            myController::file($public_file);
        }
        $public_file = myFile::resolveWithin(ROOT, $request_file);
        $private_pattern = '#^(?:config|lib|tests|maintenance)(?:/|$)|^cache/(?!script/.+\.(?:css|js)$)#i';
        $private_path = preg_match($private_pattern, rawurldecode($request_file));
        if($public_file) $private_path = $private_path || preg_match($private_pattern, substr($public_file, strlen(rtrim(realpath(ROOT), '/'))+1));
        if($private_path || preg_match('#(?:^|/)\.[^/]*|(?:^|/)files/(?:.*/)?log\.txt$#i', rawurldecode($request_file))) myController::header('404');
        if($public_file && !$private_path && in_array($ext, $ext_list, true)) {
            if(!in_array(strtolower(pathinfo($public_file, PATHINFO_EXTENSION)), $ext_list, true)) myController::header('404');
            myController::file($public_file);
        }

        if(!is_file($the_file) &&
            in_array($ext, $ext_list) &&
            $ms_setting->router->url_fix != '/api' &&
            strpos($the_file, '/api/')===false &&
            strpos($the_file, '/asset/')===false
        ) {
            no_file:
            switch($ext) {
                case 'jpg':
                case 'png':
                case 'gif':
                    myController::file(ROOT.'static/images/noimage.gif');
                    break;
                case 'ico':
                    myController::file(ROOT.'favicon.ico');
                    break;
                case 'css':
                    echo '.none {display:none}';
                    break;
                case 'js':
                    echo 'console.log('.json_encode('JS file('.$the_file.') is missing!', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT).');';
                    break;
                case 'json':
                    echo '{};';
                    break;
                default:
                    echo '';
            }
            exit;
        }

        if(empty($ms_setting->cookie->domain) && ($ms_setting->cookie->domain = strstr($host, ':', true))===false) {
            $ms_setting->cookie->domain = $host;
        }
        $ms_setting->cookie->path = str_replace('\\', '/', dirname(myReq::server('SCRIPT_NAME')));
        $ms_setting->web->url = (isHttps()?'https://':'http://').myReq::server('HTTP_HOST');
        if(is_file(CONFIG.'variant_alias.php')) {
            $variant_alias = require CONFIG.'variant_alias.php';
            self::$variant_alias = array_keys($variant_alias);
            foreach ($variant_alias as $k => $v) {
                if(!isset($GLOBALS[$v])) $GLOBALS[$v] = '';
                $GLOBALS[$k] = &$GLOBALS[$v];
                global $$k;
            }
        }

        self::refreshRoutes();
        $router->setRules(CONFIG.'route.php');
        $info_app = $router->info;
        // 先保持会话开启；setPara在实际路由应用确定后判断公共框架端点。
        $GLOBALS['no_log'] = false;

        if(!$router->check()) {
            $info_app['route'] = $router->route['p'];
            $info_app['app'] = trim($info_app['app'], '.');
            if(empty($info_app['app']) || !is_dir(APP.$info_app['app'])) {
                myStep::info('app_missing', ROOT_WEB);
            }
            if(is_file(APP.$info_app['app'].'/config.php')) {
                $ms_setting->merge(APP.$info_app['app'].'/config.php');
            }
            myStep::checkBind($info_app['app']);
            if(isset($info_app['path'][1]) && $info_app['path'][0]=='asset') {
                $asset = implode('/', array_slice($info_app['path'], 1));
                foreach([APP.$info_app['app'].'/asset/'.$ms_setting->template->style, APP.$info_app['app'].'/asset', APP.$info_app['app'].'/asset/default'] as $base) {
                    $file = myFile::resolveWithin($base, $asset);
                    if($file && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), array_merge($ext_list, ['otf', 'ttc']), true)) myController::file($file);
                }
                goto no_file;
            }
            if(defined('URL_FIX')) {
                if(!myRouter::hasPrefix($info_app['route'], '/'.URL_FIX)) {
                    $info_app['route'] = '/'.URL_FIX.$info_app['route'];
                }
            }
            myStep::setPara();
            $m = $info_app['path'][0] ?? '';
            if(is_file(PATH.'global.php')) require_once(PATH.'global.php');
            require(PATH.'index.php');
            if(isset($tpl)) {
                if(isset($content)) $tpl->assign('main', $content);
                $mystep->show($tpl);
            }
        }
        $mystep->end();
    }

    /**
     * 功能说明：根据实际路由应用确定公共端点会话策略，并初始化应用模块参数
     *
     * 参数说明：无显式参数。
     *
     * @return void 无返回值。
     */
    public static function setPara() {
        global $mystep, $info_app, $tpl_setting, $tpl_cache, $ms_setting, $db, $cache;
        if(!empty($mystep)) return;
        // path在初步解析时不含应用名，命中规则后可能含应用名，统一后再判断。
        // 仅框架本身的公共端点跳过会话；Admin/error等同名应用页面仍需读取登录状态。
        $session_path = $info_app['path'] ?? [];
        if(($session_path[0] ?? '') === ($info_app['app'] ?? '')) array_shift($session_path);
        $session_entry = $session_path[0] ?? '';
        $GLOBALS['no_log'] = ($info_app['app'] ?? '') === 'myStep' && $session_entry !== 'captcha'
            && in_array($session_entry, self::$func_ignore, true);
        self::checkClose();
        define('PATH', APP.$info_app['app'].'/');
        //define('WEB_APP', defined('URL_FIX')?'':preg_replace('#^/(\w*).*$#', '\1', myReq::server('request_uri')).'/');
        if(is_file(PATH.$info_app['app'].'.class.php')) {
            require_once(PATH.$info_app['app'].'.class.php');
            $class = $info_app['app'];
            if(!class_exists($class) || !is_subclass_of($class,'myStep')) $class = __CLASS__;
        } else {
            $class = __CLASS__;
        }
        $mystep = new $class();
        if(is_file(PATH.'lib.php')) require_once(PATH.'lib.php');
        if(is_callable(array($mystep, 'preload'))) $mystep->preload();

        $ms_setting->web->css = explode(',', $ms_setting->web->css);
        foreach($ms_setting->web->css as $k) {
            $mystep->addCSS(STATICS.'css/'.$k.'.css');
        }
        $mystep->addCSS(STATICS.'css/global.css');
        $mystep->addCSS(PATH.'asset/style.css');
        $mystep->addCSS(PATH.'asset/'.$ms_setting->template->style.'/style.css');
        self::$cache_css = CACHE.'script/'.$info_app['app'].'_'.$ms_setting->template->style.'.css';

        $ms_setting->web->js = explode(',', $ms_setting->web->js);
        foreach($ms_setting->web->js as $k) {
            $mystep->addJS(STATICS.'js/'.$k.'.js');
        }
        $mystep->addJS(STATICS.'js/global.js');
        $mystep->addJS(PATH.'asset/function.js');
        $mystep->addJS(PATH.'asset/'.$ms_setting->template->style.'/function.js');
        $mystep->addJS('
$.getScript("'.ROOT_WEB.'index.php?ms_setting/'.$info_app['app'].'", function(){
    $.getScript("'.ROOT_WEB.'index.php?ms_language/'.$info_app['app'].'/"+setting.language);
    if(typeof setting.debug != "undefined" && setting.debug == true) {
        window.onerror = reportError;
    }
});
        ', true);
        self::$cache_js = CACHE.'script/'.$info_app['app'].'_'.$ms_setting->template->style.'.js';

        $tpl_setting = array(
            'name' => $ms_setting->template->name,
            'path' => myPath::absolute(myPath::settings(['template'=>['path'=>$ms_setting->template->path]], $info_app['app'])['template']['path']),
            'style' => $ms_setting->template->style,
            'path_compile' => CACHE.'template/'.$info_app['app'].'/'
        );

        $tpl_cache = ($ms_setting->gen->debug || !$ms_setting->gen->cache_page) ? false : array(
            'path' => CACHE.'app/'.$info_app['app'].'/html/',
            'expire' => 60*60*24
        );
        $mystep->start();
    }

    /**
     * 功能说明：检测域名绑定
     *
     * @param string $pattern 待验证的域名绑定表达式或规则
     * @return void 无返回值。
     */
    public static function checkBind($pattern) {
        global $host, $domain, $router, $ms_setting;
        if($ms_setting->gen->force_domain===false || myReq::check('files')) return;
        if(($bind = array_search($pattern, $domain, true)) !== false && !isset($domain[$host]) && $host !== $bind) {
            $path = $router->route['p'];
            $prefix = '/'.trim(preg_replace('#^([^\(]+).*$#', '\\1', $pattern), '/');
            if(myRouter::hasPrefix($path, $prefix)) $path = substr($path, strlen($prefix));
            $path = '/'.ltrim($path, '/');
            if($router->route['q'] !== '') $path .= '?'.$router->route['q'];
            $status = in_array(strtoupper((string)myReq::server('REQUEST_METHOD')), ['GET', 'HEAD', ''], true) ? 302 : 307;
            self::redirect((isHttps()?'https':'http').'://'.$bind.$path, $status);
        }
    }

    /**
     * 功能说明：检测是否网站关闭
     *
     * 参数说明：无显式参数。
     *
     * @return void 无返回值。
     */
    public static function checkClose() {
        global $ms_setting, $info_app;
        if($info_app['app']=='myStep') return;
        if(!$ms_setting->gen->debug && !empty($ms_setting->gen->close)) {
            if(stripos($ms_setting->gen->close, 'http')===0) {
                self::redirect($ms_setting->gen->close);
            } elseif(is_file(ROOT.$ms_setting->gen->close)) {
                myStep::file($ms_setting->gen->close);
            } else {
                myStep::info($ms_setting->gen->close, '###');
            }
        }
    }

    /**
     * 功能说明：应用模块调用
     *
     * @param string $m 需要分派的模块路径/名称
     * @return void 无返回值。
     */
    public static function getModule($m) {
        global $mystep, $tpl_setting, $tpl_cache, $info_app, $ms_setting, $db, $cache, $router;
        foreach(self::$variant_alias as $v) {
            global $$v;
        }
        $tpl = new myTemplate($tpl_setting, $tpl_cache);
        if(is_file(PATH.'global.php')) require_once(PATH.'global.php');
        $idx = preg_replace('#[/&?].*$#', '', $m);
        $files = [
            PATH.'module/'.$tpl_setting['style'].'/'.$idx.'.php',
            PATH.'module/'.$tpl_setting['style'].'/'.($info_app['path'][0]??'').'.php',
            PATH.'module/'.$tpl_setting['style'].'/index.php',
            PATH.'module/'.$idx.'.php',
            PATH.'module/'.($info_app['path'][0]??'').'.php',
            PATH.'module/index.php',
            ''
        ];
        foreach($files as $f) {
            if(is_file($f)) break;
            if(empty($f))  myStep::info('module_missing');
        }
        include($f);
        if(isset($content)) {
            $tpl->assign('main', $content);
            $mystep->show($tpl);
        }
        $mystep->end();
    }

    /**
     * 功能说明：调用第三方组件
     *
     * @param mixed ...$args 首项为组件名或dir/file/class/namespace数组，其余为构造参数（历史位置参数，由func_get_args读取）
     * @return object 按组件声明创建的实例；需附加参数时再调用构造/init
     * @throws ReflectionException 目标反射或底层调用产生的异常继续传递
     */
    public static function vendor() {
        $args = func_get_args();
        $name = array_shift($args);
        if(is_array($name)) {
            if(!isset($name['dir'])) $name['dir'] = $name['file'];
            if(!isset($name['file'])) $name['file'] = $name['dir'];
            $file = VENDOR.$name['dir'].'/'.$name['file'];
            $class = $name['class'] ?? $name['file'];
            $class = ($name['namespace'] ?? '').$class;
        } else {
            $file = VENDOR.$name.'/'.$name;
            $class = $name;
        }
        $file .= is_file($file.'.php') ? '.php':'.class.php';

        if(!is_file($file)) {
            myStep::info('module_missing');
        }
        require_once($file);
        if(!class_exists($class)) {
            myStep::info('module_missing');
        }
        $r = new ReflectionClass($class);
        $instance = $r->newInstanceWithoutConstructor();
        if(count($args) && is_callable([$instance, '__construct'])) {
            call_user_func_array([$instance, '__construct'], $args);
        } elseif(count($args) && is_callable([$instance, 'init'])) {
            call_user_func_array([$instance, 'init'], $args);
        }
        return $instance;
    }

    /**
     * 功能说明：字符串分词
     *
     * @param string $str 提交给分词服务的文本；空串不请求服务（默认：''）
     * @return string 分词服务返回文本经换行整理后的内容；空输入返回空串，不返回JSON结构
     */
    public static function segment($str='') {
        if(empty($str)) return '';
        $str = myString::setCharset($str, 'utf-8');
        $url = 'http://api.pullword.com/get.php?source='.$str.'&param1=1&param2=0';
        $list = file_get_contents($url);
        $list = preg_replace('/[\r\n\s]+$/', '', $list);
        if(strpos($list, 'error')>0) {
            $list = $str;
        } else {
            $list = str_replace(chr(10), ',', $list);
        }
        return $list;
    }

    /**
     * 功能说明：权限检测接口
     *
     * @param string $idx 待检查的能力名称，例如upload/download/remove_ul
     * @return bool 当前请求对指定能力检查通过为true，否则为false；子类继承或调用parent时使用框架默认检查，避免再次委派
     */
    public static function checkPower($idx) {
        $core = self::getCore();
        if($core == __CLASS__ || get_called_class() !== __CLASS__ || !is_callable([$core, 'checkPower'])) {
            global $ms_setting;
            $op = myReq::session('sysop');
            switch($idx) {
                case 'upload':
                case 'remove_ul':
                    $flag = $op === 'y';
                    break;
                default:
                    $referer = myReq::server('http_referer');
                    $flag = $op === 'y' || mySecurity::trustedReferer($referer) || $ms_setting->upload->free_dl;
            }
        } else {
            $flag = $core::checkPower($idx);
        }
        return $flag;
    }

    /**
     * 功能说明：获取指定核心类名称
     *
     * 参数说明：无显式参数。
     *
     * @return string 当前核心应用类/目录名称，按配置及框架回退规则选择
     */
    public static function getCore() {
        global $domain;
        $host = strtolower(preg_replace('/:\d+$/', '', (string)myReq::svr('HTTP_HOST')));
        $core = myReq::get('core', '!');
        if($core === null) $core = $domain[$host] ?? __CLASS__;
        if(!is_string($core) || !preg_match('/^\w+$/D', $core)) return __CLASS__;
        if($core === __CLASS__) return $core;
        $file = myFile::resolveWithin(APP, $core.'/'.$core.'.class.php');
        if(!$file) return __CLASS__;
        require_once($file);
        return class_exists($core, false) && is_subclass_of($core, __CLASS__) ? $core : __CLASS__;
    }
}
