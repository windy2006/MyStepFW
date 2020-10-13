<?PHP
/********************************************
*                                           *
* Name    : Controller of the Frameword     *
* Author  : Windy2000                       *
* Time    : 2010-12-16                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT HOLD THIS ITEM PLEASE.      *
*                                           *
********************************************/

/**
核心框架类，扩展于myController
    $this->getInstance($calledClass)                 // 取得类实例
    $this->start()                                   // 框架执行入口，初始化所有变量
    $this->setInstance()                             // 声明重要实例
    $this->show(myTemplate $tpl)                     // 通过模板类显示页面
    $this->render(myTemplate $tpl)                   // 通过模板类输出页面内容
    $this->checkCache(myTemplate $tpl)               // 检查是否存在缓存并输出
    $this->end()                                     // 框架终止，销毁相关变量
    $this->login($user_id, $user_pwd)                // 登录接口
    $this->logout()                                  // 退出登录接口
    $this->chg_psw($id, $psw_org, $psw_new)          // 变更密码接口
    $this->gzOut($level, $query, $time)              // 压缩输出页面内容
    $this->addCSS($file)                             // 添加页面CSS文件
    $this->CSS($cache)                               // 生成整合CSS文件
    $this->addJS($file)                              // 添加页面脚本文件
    $this->JS($cache)                                // 整合页面脚本文件
    $this->editorSetPlugin($code, $btn)              // 富文本编辑器功能扩展
    $this->editorGetPlugin()                         // 生成编辑器插件缓存脚本
    self::info($msg, $url)                           // 信息提示，需先声明mystep类
    self::captcha($len, $scope)                      // 生成验证码
    self::language($module, $type)                   // JS语言包接口
    self::setting($module, $type)                    // JS设置信息接口
    self::api($para)                                 // 框架API执行接口
    self::module($m)                                 // 框架模块调用接口
    self::upload()                                   // 文件上传接口
    self::download($idx)                             // 文件下载接口
    self::remove_ul($idx)                            // 删除文件下载接口
    self::setURL()                                   // 链接处理
    self::redirect()                                 // 链接跳转
    self::init()                                     // 框架变量初始化
    self::go()                                       // 执行框架
    self::setPara()                                  // 应用模块初始化参数设置
    self::getModule($m)                              // 应用模块调用
    self::vendor($class_name)                        // 调用第三放组件
    self::segment($str)                              // 字符串分词
*/

require_once('function.php');
require_once(VENDOR.'autoload.php');
require_once('myController.class.php');
class myStep extends myController {
    public static
        $url_prefix = '',
        $plugin_ignore = ['ms_language', 'ms_setting', 'captcha'];
    public $setting;
    protected
        $editor_plugin = [],
        $editor_btn = [],
        $mem_start = 0,
        $time_start = 0,
        $time_css = 0,
        $time_js = 0;

    /**
     * 构造函数
     */
    public function __construct() {
        $this->setting = &$GLOBALS['s'];
        date_default_timezone_set($this->setting->gen->timezone);
        set_time_limit(30);
        ini_set('memory_limit', '128M');
        ini_set('default_socket_timeout', 300);
        return;
    }

    /**
     * 取得类实例
     * @param string $calledClass
     * @return mixed
     */
    public function getInstance($calledClass = '') {
        $instance = call_user_func_array('parent::getInstance', func_get_args());
        if(is_callable($instance, 'regAlias') && is_file(CONFIG.'method_alias/'.$calledClass.'.php')) {
            $alias = include(CONFIG.'method_alias/'.$calledClass.'.php');
            $instance->regAlias($alias);
        }
        $instance->setErrHandler('myStep::info');
        return $instance;
    }

    /**
     * 框架执行入口，初始化所有变量
     * @param string $dummy1
     * @param string $dummy2
     */
    public function start($dummy1 = '', $dummy2 = '') {
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
            $mydb = new myDb('simpleDB', 'plugin', PLUGIN);
            foreach($plugin as $p) {
                if($record = $mydb->select('idx='.$p)) {
                    if($record[0]['active']=='1') $this->regPlugin(PLUGIN.$p);
                }
            }
        }
        $this->setInstance();
        $this->setting->cookie->prefix .= substr(md5(myReq::server('USERNAME').myReq::server('COMPUTERNAME').myReq::server('OS')), 0, 4).'_';
        if($this->setting->session->mode=='sess_file') $this->setting->session->path = CACHE.'session/'.date('Ymd').'/';

        $this->setting->info = new stdClass();
        $this->setting->info->time = myReq::server('REQUEST_TIME');
        $this->setting->info->host = myReq::server('HTTP_HOST');

        $this->setLanguagePack(APP.'myStep/language/', $this->setting->gen->language);
        if(PATH != APP.'myStep/') $this->setLanguagePack(PATH.'language', $this->setting->gen->language);

        myReq::init((array)$this->setting->cookie, (array)$this->setting->session);
        if($this->setting->session->mode=='sess_mysql') {
            sess_mysql::set((array)$this->setting->db);
        }
        myReq::sessionStart($this->setting->session->mode, true);
        myReq::setCookie('sign_local', 'yes', 600);
        $this->login();

        global $plugin_ignore;
        if(is_string($plugin_ignore)) $plugin_ignore = explode(',', str_replace(' ', '', $plugin_ignore));
        if(is_array($plugin_ignore)) self::$plugin_ignore = array_merge(self::$plugin_ignore, $plugin_ignore);
        $path = explode('/', trim(r::gl('info_app')['route'], '/'));
        $set_plugin = !in_array(array_shift($path), self::$plugin_ignore);

        parent::start($this->setting->gen->charset, $set_plugin);
        $this->page_content['start'] = array();
        $this->page_content['end'] = array();
        self::$url_prefix = self::setURL();
    }

    /**
     * 声明重要实例
     */
    public function setInstance() {
        global $cache, $db;
        if(is_null($cache)) {
            switch($this->setting->gen->cache_mode) {
                case 'MySQL':
                    $cache_setting = myConfig::o2a($this->setting->db);
                    break;
                default:
                    $cache_setting = CACHE.'data';
            }
            $cache = $this->getInstance('myCache', 'myCache_'.$this->setting->gen->cache_mode, $cache_setting);
        }
        if(is_null($db)) {
            $db = $this->getInstance('myDb', $this->setting->db->type, $this->setting->db->host, $this->setting->db->user, $this->setting->db->password, $this->setting->db->charset);
            if($this->setting->db->auto) {
                $db->connect($this->setting->db->pconnect, $this->setting->db->name);
                $db->setCache($cache, 600);
            } else {
                $this->setting->session->mode='sess_file';
            }
        }
    }

    /**
     * 通过模板类显示页面
     * @param myTemplate $tpl
     * @param string $append_pare
     */
    public function show(myTemplate $tpl, $append_pare = '') {
        global $info_app;
        $paras = [
            'web_title' => $this->setting->web->title,
            'web_url' => $this->setting->web->url,
            'page_keywords' => $this->setting->web->keyword,
            'page_description' => $this->setting->web->description,
            'charset' => $this->setting->gen->charset,
            'path_root' => ROOT_WEB,
            'path_app' => str_replace(myFile::rootPath(), '/', PATH),
            'url_prefix' => self::$url_prefix,
            'url_prefix_app' => self::$url_prefix.(defined('URL_FIX')?'':($info_app['app'].'/')),
        ];
        if(is_array($append_pare)) {
            $paras = array_merge($paras, $append_pare);
        }
        foreach($paras as $k => $v) {
            $tpl->assign($k, $v);
        }
        if(gettype($this->setting->css)=='string') {
            $this->CSS($this->setting->css);
            $this->setAddedContent('start', '<link rel="stylesheet" media="screen" type="text/css" href="'.ROOT_WEB.'cache/script/'.basename($this->setting->css).'" />');
        }
        if(gettype($this->setting->js)=='string') {
            $this->JS($this->setting->js);
            $this->setAddedContent('start', '<script type="application/javascript" src="'.ROOT_WEB.'cache/script/'.basename($this->setting->js).'"></script>');
        }
        $this->setAddedContent('end', '<script type="application/javascript">$(ms_func_run);</script>');
        parent::show($tpl, $this->setting->web->minify, 's,db,cache,mystep');
    }

    /**
     * 通过模板类输出页面内容
     * @param myTemplate $tpl
     * @return mixed|string
     */
    public function render(myTemplate $tpl) {
        global $info_app;
        $args = func_get_args();
        array_shift($args);
        $paras = [
            'path_root' => ROOT_WEB,
            'path_app' => str_replace(myFile::rootPath(), '/', PATH),
            'url_prefix' => self::$url_prefix,
            'url_prefix_app' => self::$url_prefix.(defined('URL_FIX')?'':($info_app['app'].'/')),
        ];
        if(isset($args[0]) && is_array($args[0])) {
            $paras = array_merge($paras, array_shift($args));
        }
        foreach($paras as $k => $v) {
            $tpl->assign($k, $v);
        }
        $tpl->assign('lng', $this->language);
        $tpl->regTag($this->func_tag);
        return call_user_func_array([$tpl, 'render'], ['s,db,cache,mystep', false, false]);
    }

    /**
     * 检查模版缓存并输出
     * @param myTemplate $tpl
     */
    public function checkCache(myTemplate $tpl) {
        if($tpl->checkCache()) {
            echo $tpl->getCache();
            $this->end();
        }
    }

    /**
     * 框架终止，销毁相关变量
     */
    public function end() {
        parent::end();
        $this->editorGetPlugin();
        $query_count = is_null($GLOBALS['db']) ? 0 : $GLOBALS['db']->close();
        $time_exec = getTimeDiff($this->time_start);
        $mem_peak = memory_get_peak_usage();
        unset($GLOBALS['db'], $GLOBALS['cache']);
        $this->gzOut($this->setting->web->gzip_level, $query_count, $time_exec, $mem_peak);
        exit();
    }

    /**
     * 登录接口
     * @param string $usr
     * @param string $pwd
     * @return bool
     */
    public function login($usr='', $pwd='') {
        if(!empty($usr) && !empty($pwd)) {
            $pwd = md5($pwd);
            $ms_user = $usr.chr(9).$pwd;
        } else {
            $ms_user = myReq::cookie('ms_user');
        }
        $result = false;
        if(!empty($ms_user)) {
            list($usr, $pwd) = explode(chr(9), $ms_user);
            if($usr == $this->setting->gen->s_usr && $pwd == $this->setting->gen->s_pwd) {
                myReq::session('ms_user', $usr);
                $result = true;
            }
        }
        return $result;
    }

    /**
     * 退出登录接口
     * @return bool
     */
    public function logout() {
        myReq::removeCookie('ms_user');
        myReq::sessionEnd();
        return true;
    }

    /**
     * 变更密码接口
     * @param string $id
     * @param $psw_org
     * @param $psw_new
     * @return bool
     */
    public function chg_psw($id='', $psw_org, $psw_new) {
        $result = false;
        $username = myReq::session('username');
        if(!empty($username) && $psw_org!=$psw_new) {
            if($psw_org==$this->setting->gen->s_pwd) {
                $config = new myConfig(CONFIG.'config.php');
                $config->gen->s_pwd = $psw_new;
                $config->save('php');
                $result = true;
            }
        }
        return $result;
    }

    /**
     * 页面压缩
     * @param int $level
     * @param int $query
     * @param int $time
     */
    public function gzOut($level = 3, $query = 0, $time = 0, $mem = 0) {
        $encoding = myReq::server('HTTP_ACCEPT_ENCODING');
        if($level<0 || empty($encoding) || headers_sent() || connection_aborted()) {
            if(!empty($content)) ob_end_flush();
        } else {
            if (strpos($encoding, 'x-gzip')!==false) $encoding = 'x-gzip';
            if (strpos($encoding, 'gzip')!==false) $encoding = 'gzip';
            $content  = ob_get_contents();
            if(count(ob_list_handlers())>0) ob_end_clean();
            if(is_bool($this->setting->show)) {
                $rate = ceil(strlen(gzcompress($content, $level)) * 100 / (strlen($content)==0?1:strlen($content))). '%';
                $content = str_ireplace('</body>', '
<div class="text-right text-secondary my-2 pr-5" style="font-size:12px;">
<span class="nowrap font-sm">'.$this->getLanguage('info_memory').myFile::formatSize($mem).'</span>&nbsp; | &nbsp;
<span class="nowrap font-sm">'.$this->getLanguage('info_compressmode').$rate.'</span>&nbsp; | &nbsp;
<span class="nowrap font-sm">'.$this->getLanguage('info_querycount').(empty($query)?0:$query).'</span>&nbsp; | &nbsp;
<span class="nowrap font-sm">'.$this->getLanguage('info_exectime').$time.'ms</span>&nbsp; | &nbsp;
<span class="nowrap font-sm">'.$this->getLanguage('info_cacheuse').$this->setting->gen->cache_mode.'</span>
</div>
</body>
', $content);
            }

            header('Content-Encoding: '.$encoding);
            echo "\x1f\x8b\x08\x00\x00\x00\x00\x00";
            $Size = strlen($content);
            $Crc = crc32($content);
            $content = gzcompress($content, $level);
            $content = substr($content, 0, strlen($content) - 4);
            echo $content;
            echo pack('V', $Crc);
            echo pack('V', $Size);
        }
    }

    /**
     * 添加页面CSS文件
     * @param $file
     * @return $this|myController
     */
    public function addCSS($file) {
        if(is_file($file)) {
            $time = filemtime($file);
            if($time>$this->time_css) $this->time_css = $time;
            $this->css[md5_file($file)] = $file;
        }
        return $this;
    }

    /**
     * 生成整合CSS文件
     * @param string $cache
     * @param string $dummy
     * @return string|void
     */
    public function CSS($cache='', $dummy='') {
        if(!is_file($cache) || filemtime($cache)<$this->time_css) {
            foreach($this->css as $k => $v) {
                $this->css[$k] = myFile::getLocal($v);
            }
            $code = parent::CSS(false);
            myFile::saveFile($cache, $code);
        }
    }

    /**
     * 添加页面脚本文件
     * @param $file
     * @param bool $code
     * @return $this|myController
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
     * 整合页面脚本文件
     * @param string $cache
     * @param string $dummy
     * @return string|void
     */
    public function JS($cache='', $dummy='') {
        if(!is_file($cache) || filemtime($cache)<$this->time_js) {
            foreach($this->js as $k => $v) {
                $this->js[$k] = is_file($v)?myFile::getLocal($v):$v;
            }
            $code = parent::JS(false);
            myFile::saveFile($cache, $code);
        }
    }

    /**
     * 富文本编辑器功能添加
     * @param $code
     * @param string $btn
     * @return $this
     */
    public function editorSetPlugin($code, $btn='') {
        if(is_file($code)) $code = f::g($code);
        $this->editor_plugin[] = $code;
        if(!empty($btn)) $this->editor_btn[] = $btn;
        return $this;
    }

    /**
     * 生成编辑器插件缓存脚本
     * @return $this
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
     * 信息提示，需先声明mystep类
     * @param $msg
     * @param string $url
     */
    public static function info($msg, $url = '') {
        global $mystep, $s;
        ob_end_clean();
        if($mystep==null) {
            $mystep = new myController();
            $mystep->setLanguagePack(APP.'myStep/language/', $s->gen->language);
            $paras = [
                'web_title' => $s->web->title,
                'web_url' => $s->web->url,
                'charset' => $s->gen->charset,
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
     * 生成验证码
     * @param int $len
     * @param int $scope
     */
    public static function captcha($len = 4, $scope = 3) {
        $str = myString::rndKey($len, $scope);
        myReq::session('captcha', $str);
        $img = new myImg();
        $img->captcha($str, STATICS.'fonts/font.ttc', 24);
    }

    /**
     * JS语言包接口
     * @param $module
     * @param string $type
     */
    public static function language($module, $type='default') {
        header('Content-Type: application/x-javascript');
        $type = preg_replace('#&.+$#', '', $type);
        $cache = ROOT.'cache/language/'.$module.'_'.$type.'.js';
        if(is_file($cache)) {
            $result = myFile::getLocal($cache);
        } else {
            $dir = APP.'myStep/language/';
            if(!is_file($dir.'/'.$type.'.php')) $type='default';
            if(is_file($dir.'/'.$type.'.php')) {
                $language = include($dir.'/'.$type.'.php');
                if($language==1) $language = array();
            }
            if($module!='myStep') {
                $dir = APP.$module.'/language/';
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
     * JS设置信息接口
     * @param $module
     * @param string $type
     */
    public static function setting($module) {
        $module = preg_replace('#&.+$#', '', $module);
        if(empty($module) || !is_dir(APP.$module)) $module='myStep';
        $setting = new myConfig(CONFIG.'config.php');
        if($module!='myStep') {
            $setting->merge(APP.$module.'/config.php');
        }
        $setting = myConfig::o2a($setting);
        $setting_js = array(
            'language' => $setting['setting']['gen']['language'],
            'debug' => $setting['setting']['gen']['debug'],
            'app' => $module,
            'path_root' => ROOT_WEB,
            'path_app' => str_replace(myFile::rootPath(), '/', APP.$module),
            'url_fix' => defined('URL_FIX')?URL_FIX:'',
            'url_prefix' => self::$url_prefix,
            'url_prefix_app' => self::$url_prefix.(defined('URL_FIX')?'':$module),
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
     * 框架API执行接口
     * @param $para
     */
    public static function api($para) {
        global $s, $info_app;
        $para = preg_replace('#&.+$#', '', $para);
        $para = explode('/', trim($para, '/'));
        $module = $info_app['app'];
        include(CONFIG.'route.php');
        $result = '{"error":"Module is Missing!"}';
        if(isset($api_list)) {
            if(isset($api_list[$module])) {
                if(strpos($para[0], 'plugin_')!==0) {
                    $name = $para[1];
                    array_shift($para);
                } else {
                    $plugin = array_shift($para);
                    $name = array_shift($para);
                }
            } else {
                $name = $module;
                $module = 'myStep';
            }
            $flag = false;
            if(isset($plugin) && isset($api_list[$plugin][$name])) {
                $s->merge(APP.$module.'/config.php');
                $method = $api_list[$plugin][$name];
                $type = end($para);
                $flag = true;
            } elseif(isset($api_list[$module][$name])) {
                $s->merge(APP.$module.'/config.php');
                $method = $api_list[$module][$name];
                $type = end($para);
                $para = array_slice($para, 1);
                $flag = true;
            }
            if($flag) {
                $para = array_merge(myReq::getValue(myReq::check('get')?'get':'post', '[ALL]'), $para);
                if(is_file(APP.$module.'/lib.php')) require_once(APP.$module.'/lib.php');
                if(is_callable($method)) {
                    $api = new myApi();
                    $api->regMethod($name, $method);
                    $result = call_user_func([$api, 'run'], $name, $para, $type, $s->gen->charset);
                }
            }
        }
        $tmp = getOB(true);
        if(!empty($tmp)) $result = $tmp;
        echo $result;
        exit;
    }

    /**
     * 框架模块调用接口
     * @param $m
     * @param string $dummy
     */
    public static function module($m, $dummy = '') {
        $path = explode('/', trim($m, '/'));
        $module = array_shift($path);
        if(isset(self::$modules[$module])) {
            global $mystep, $db, $cache;
            require(self::$modules[$module]);
            exit();
        } else {
            self::redirect('/');
        }
    }

    /**
     * 文件上传
     */
    public static function upload() {
        if(self::checkPower('upload')) {
            global $s;
            $path = FILE.date($s->upload->path_mode);
            set_time_limit(0);
            $upload = new myUploader($path, true, $s->upload->ban_ext);
            $upload->do(false);
            if($upload->result[0]['error'] == 0) {
                $upload->result[0]['name'] = myString::sc(urldecode($upload->result[0]['name']), $s->gen->charset);
                $ext = strtolower(strrchr($upload->result[0]['name'], '.'));
                $name = str_replace($ext, '', $upload->result[0]['name']);
                $upload->result[0]['name'] = myString::substr($name, 0, 80).$ext;
                myFile::saveFile($path.'/log.txt', $upload->result[0]['new_name'].'::'.$upload->result[0]['name'].'::'.chr(10), 'a');
            }
            $result = $upload->result[0];
        } else {
            $result = ['error' => '-1', 'message' => 'Upload Denied!'];
        }
        return $result;
    }

    /**
     * 文件下载
     * @param $idx
     */
    public static function download($idx) {
        global $s;
        if($s->upload->free_dl || self::checkPower('download')) {
            $idx = explode('.', $idx);
            $path = FILE.date($s->upload->path_mode, $idx[0]);
            set_time_limit(0);
            $file = $path.'/log.txt';
            if(!file_exists($file)) {
                self::header('404');
            }
            $list = file($file);
            for($i=0,$m=count($list);$i<$m;$i++) {
                if(strpos($list[$i], implode('.', $idx))===0) {
                    $list[$i] = explode('::', $list[$i]);
                    $file = $path.'/'.$list[$i][0];
                    $name = $list[$i][1];
                    if(is_file($file.'.upload')) $file = $file.'.upload';
                    if(is_file($file)) {
                        myController::file($file, $name);
                        exit;
                    }
                }
            }
        }
        self::header('404');
    }

    /**
     * 删除上传文件
     * @param $idx
     * @return array|string
     */
    public static function remove_ul($idx) {
        global $s;
        $result = ['error' => '-1', 'message' => 'Cannot remove the file!'];
        if(self::checkPower('remove_ul')) {
            $idx = explode('.', $idx);
            $path = FILE.date($s->upload->path_mode, $idx[0]);
            $log = $path.'/log.txt';
            $list = file($log);
            for($i=0,$m=count($list);$i<$m;$i++) {
                if(strpos($list[$i], implode('.', $idx))===0) {
                    $list[$i] = explode('::', $list[$i]);
                    $file = $path.'/'.$list[$i][0];
                    if(file_exists($file.'.upload')) $file = $file.'.upload';
                    if(myFile::del($file)===false) return $result;
                    break;
                }
            }
            if($i<$m) {
                $content = myFile::getLocal($log);
                $content = str_replace(implode('::', $list[$i]), '', $content);
                if(strlen($content)<5) {
                    myFile::del(dirname($log));
                } else {
                    myFile::saveFile($log, $content);
                }
                $result = ['error' => '0', 'message' => 'The File has been removed!'];
            }
        }
        return $result;
    }

    /**
     * 处理头信息
     * @param $idx
     * @param string $para
     * @param bool $exit
     */
    public static function header($idx, $para = '', $exit = true) {
        global $mystep;
        switch($idx) {
            case '404':
                myStep::info(sprintf($mystep->getLanguage('page_error_404'), r::svr('REQUEST_URI')));
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
     * 链接处理
     * @param $url
     * @return string
     */
    public static function setURL($url='') {
        global $s;
        if(strpos($url, '://')===false && strpos($url, 'index.php')===false) {
            $url = preg_replace('@^'.preg_quote(ROOT_WEB).'@', '/', $url);
            switch($s->router->mode) {
                case 'path_info':
                    $url = 'index.php'.$url;
                    break;
                case 'query_string':
                    $url = 'index.php?'.$url;
                    break;
                default:
                    break;
            }
            $url = ROOT_WEB.$url;
            $url = preg_replace('#/+#', '/', $url);
        }
        return $url;
    }

    /**
     * 链接跳转
     * @param string $url
     * @param string $code
     */
    public static function redirect($url = '', $code = '302') {
        if(empty($url)) {
            $url = myReq::server('HTTP_REFERER');
            if (is_null($url)) $url = '/';
            $code = '302';
        } else {
            $url = self::setURL($url);
        }
        if(!is_null($GLOBALS['db'])) $GLOBALS['db']->close();
        unset($GLOBALS['db'], $GLOBALS['cache']);
        header('location: ' . $url, true, $code);
        exit;
    }

    /**
     * 框架变量初始化
     */
    public static function init() {
        if(count(ob_list_handlers())) ob_end_clean();
        $class = is_file(CONFIG.'class.php') ? include((CONFIG.'class.php')) : array();
        if(empty($class) || !is_dir($class[0]['path'])) {
            $old_root = empty($class) ? '' : preg_replace('#lib/$#', '', $class[0]['path']);
            $class[0] = array(
                'path' => ROOT . 'lib/',
                'ext' => '.php,.class.php',
                'idx' => array(
                        'interface_plugin' => '../plugin/interface_plugin.class.php'
                    ),
            );
            for($i=1,$m=count($class);$i<$m;$i++) {
                $class[$i]['path'] = preg_replace('#^'.$old_root.'#', ROOT, $class[$i]['path']);
            }
            @unlink(CONFIG.'class.php');
            file_put_contents(CONFIG.'class.php', '<?PHP'.chr(10).'return '.var_export($class, true).';');
            $setting_class = include(CONFIG.'class.php');
            self::regClass($setting_class);
            myFile::del(CONFIG.'route.php');
            myFile::del(CACHE.'template');
            myFile::del(CACHE.'session');
        } else {
            $setting_class = include(CONFIG.'class.php');
            self::regClass($setting_class);
        }
        define('ROOT_WEB', str_replace(myFile::rootPath(), '/', ROOT));
        myException::init(array(
            'log_mode' => 0,
            'log_type' => E_ALL ^ E_NOTICE,
            'log_file' => ROOT.'/error.log',
            'callback_type' => E_ALL & ~(E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE | E_NOTICE),
            'exit_on_error' =>  true
        ));
        $path = trim(str_replace(ROOT_WEB, '/', myReq::svr('REQUEST_URI')), '/');
        $the_file = ROOT.preg_replace('#(&|\?).+$#', '', $path);
        $ext = strtolower(pathinfo($the_file, PATHINFO_EXTENSION));
        if(strpos($path, 'static')===0 || in_array($ext, ['js','css'])) myController::file($the_file);

        if(is_file(CONFIG.'config.php')) {
            self::go();
        } else {
            require(APP.'myStep/module/init.php');
        }
        return;
    }

    /**
     * 执行框架
     */
    public static function go() {
        global $s, $router, $info_app, $tpl_setting, $tpl_cache, $mystep, $db, $cache;
        $s = new myConfig(CONFIG.'config.php');
        if($s->gen->debug) self::setOp('reset');
        $host = myReq::server('HTTP_HOST');
        if(is_file(CONFIG.'domain.php')) {
            $domain = include(CONFIG.'domain.php');
            if(isset($domain[$host])) {
                $rule = $domain[$host];
                if(preg_match('@^\w+$@', $rule)) {
                    $s->router->default_app = $rule;
                    define('URL_FIX', $rule);
                } else {
                    $rule = preg_replace('@^/(\w+)/.*$@', '\1', $rule);
                    define('URL_FIX', $rule);
                }
            }
        }
        $router = new myRouter((array)$s->router);
        $the_file = ROOT.preg_replace('#&.+$#', '', $router->route['p']);
        $ext = strtolower(pathinfo($the_file, PATHINFO_EXTENSION));
        $ext_list = explode(',', $s->gen->static);
        if(strpos(trim($the_file, '/'), 'static')===0 || (is_file($the_file) && in_array($ext, $ext_list))) myController::file($the_file);

        if(($s->cookie->domain = strstr($host, ':', true))===false) {
            $s->cookie->domain = $host;
        }
        $s->cookie->path = str_replace('\\', '/', dirname(myReq::server('SCRIPT_NAME')));
        $s->web->url = 'http://'.$host;
        $router->setRules(CONFIG.'route.php');
        if(!$router->check()) {
            $info_app = $router->info;
            $info_app['route'] = $router->route['p'];
            $info_app['app'] = trim($info_app['app'], '.');
            if(empty($info_app['app']) || !is_dir(APP.$info_app['app'])) {
                myStep::info('app_missing', ROOT_WEB);
            }
            if(is_file(APP.$info_app['app'].'/config.php')) {
                $s->merge(APP.$info_app['app'].'/config.php');
            }
            if(defined('URL_FIX')) {
                if(strpos($info_app["route"], '/'.URL_FIX)!==0) {
                    $info_app['route'] = '/'.URL_FIX.$info_app['route'];
                }
            }
            if(isset($info_app['path'][1]) && $info_app['path'][0]=='asset') {
                $file = APP.$info_app['app'].'/asset/'. $s->template->style.'/'.$info_app['path'][1];
                if(is_file($file)) {
                    myController::file($file);
                    exit;
                }
            }
            myStep::setPara();
            if(is_file(PATH.'/global.php')) require_once(PATH.'/global.php');
            require(PATH.'/index.php');
        }
    }

    /**
     * 应用模块初始化参数设置
     */
    public static function setPara() {
        global $mystep, $info_app, $tpl_setting, $tpl_cache, $s;
        if($mystep!=null) return;
        define('PATH', APP.$info_app['app'].'/');
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
        if(!$s->gen->debug && !empty($s->gen->close)) self::redirect($s->gen->close);
        $s->web->css = explode(',', $s->web->css);
        foreach($s->web->css as $k) {
            $mystep->addCSS(STATICS.'css/'.$k.'.css');
        }
        $mystep->addCSS(STATICS.'css/global.css');
        $mystep->addCSS(PATH.'asset/style.css');
        $mystep->addCSS(PATH.'asset/'.$s->template->style.'/style.css');
        $s->css = CACHE.'script/'.$info_app['app'].'_'.$s->template->style.'.css';

        $s->web->js = explode(',', $s->web->js);
        foreach($s->web->js as $k) {
            $mystep->addJS(STATICS.'js/'.$k.'.js');
        }
        $mystep->addJS(STATICS.'js/global.js');
        $mystep->addJS(PATH.'asset/function.js');
        $mystep->addJS(PATH.'asset/'.$s->template->style.'/function.js');
        $mystep->addJS('
$.getScript("'.ROOT_WEB.'index.php?ms_setting/'.$info_app['app'].'", function(){
    $.getScript("'.ROOT_WEB.'index.php?ms_language/'.$info_app['app'].'/"+setting.language);
    if(typeof setting.debug != "undefined" && setting.debug == true) {
        window.onerror = reportError;
    }
});
        ', true);
        $s->js = CACHE.'script/'.$info_app['app'].'_'.$s->template->style.'.js';

        $tpl_setting = array(
            'name' => $s->template->name,
            'path' => PATH.$s->template->path,
            'style' => $s->template->style,
            'path_compile' => CACHE.'template/'.$info_app['app'].'/'
        );

        $tpl_cache = ($s->gen->debug || !$s->gen->cache_page) ? false : array(
            'path' => CACHE.'app/'.$info_app['app'].'/html/',
            'expire' => 60*60*24
        );
        $mystep->start();
    }

    /**
     * 应用模块调用
     */
    public static function getModule($m) {
        global $mystep, $tpl_setting, $tpl_cache, $info_app, $s, $db, $cache, $router;
        $tpl = new myTemplate($tpl_setting, $tpl_cache);
        if(is_file(PATH.'/global.php')) require_once(PATH.'/global.php');
        $idx = preg_replace('#(/|&|\?).*$#', '', $m);
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
     * 调用第三方组件
     * @return object
     * @throws ReflectionException
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
            global $mystep;
            myStep::info('module_missing');
        }
        require_once($file);

        if(!class_exists($class)) {
            global $mystep;
            myStep::info('module_missing');
        }
        $r = new ReflectionClass($class);
        $instance = $r->newInstanceWithoutConstructor();
        //$instance = new $class();
        if(count($args) && is_callable([$instance, '__construct'])) {
            call_user_func_array([$instance, '__construct'], $args);
        } elseif(count($args) && is_callable([$instance, 'init'])) {
            call_user_func_array([$instance, 'init'], $args);
        }
        return $instance;
    }

    /**
     * 字符串分词
     * @param $str
     * @return array|mixed|null
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
     * 权限检测接口
     * @param $idx
     * @return bool
     */
    public static function checkPower($idx) {
        return myReq::cookie('sign_local')!='';
    }
}
