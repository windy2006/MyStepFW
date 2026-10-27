<?PHP
/********************************************
 *                                           *
 * Name    : Controller for the application  *
 * Modifier: Windy2000                       *
 * Time    : 2018-12-28                      *
 * Email   : windy2006@gmail.com             *
 * HomePage: www.mysteps.cn                  *
 * Notice  : U Can Use & Modify it freely,   *
 *           BUT PLEASE HOLD THIS ITEM.      *
 *                                           *
 ********************************************/
/**
 * 文件：lib/myController.class.php
 * 功能说明：控制器公共能力与输出生命周期
 *   管理实例、语言、插件、模块、API、标签、钩子及CSS/JS合并输出。
 *
 * 方法目录：
 *   public myController::__clone() — 单实例克隆控制
 *   public myController::setSingleton($singleton = false) — 单实例模式设置
 *   public static myController::getInstance($calledClass) — 依照单实例模式引用其他实例
 *   public myController::setAddedContent($position, $content) — 设置指定页面内容
 *   public myController::pushAddedContent(myTemplate $tpl) — 将内容添加到指定位置
 *   public myController::setLanguage($language, $charset = 'utf-8') — 设置页面语言
 *   public myController::setLanguagePack($dir, $lng = 'default', $charset = 'utf-8') — 获取语言包
 *   public myController::getLanguage($idx) — 获取语言项目
 *   public myController::regApi($name, $method = '') — 注册 API 处理函数
 *   public myController::runApi($name, $para = array(), $return = 'json') — 获取 API 数据
 *   public myController::regPlugin($plugin, &$info = array()) — 注册插件
 *   public myController::plugin() — 设置插件
 *   public static myController::regModule($module, $func) — 注册功能模块
 *   public static myController::module($module, $global_vars = '') — 调用已注册模块
 *   public myController::regTag($tag_name, $tag_func) — 注册模版标签处理函数
 *   public myController::regUrl($mode, $func) — 注册 URL 生成函数
 *   public myController::url($mode) — 获取 URL
 *   public myController::regLog($login, $logout = '', $chg_psw = '') — 注册登录接口
 *   public myController::login($usr='', $pwd='') — 用户登录
 *   public myController::logout() — 用户退出
 *   public myController::chg_psw($id, $psw_org, $psw_new) — 变更密码
 *   public myController::addCSS($code) — 添加 CSS
 *   public myController::removeCSS($idx) — 移除 CSS
 *   public myController::clearCSS() — 清空已添加样式
 *   public myController::CSS($show = true, $cache_path = '', $expires = 604800) — 获取合成样式表
 *   public myController::addJS($code) — 添加 JS
 *   public myController::removeJS($idx) — 移除 JS
 *   public myController::clearJS() — 清空已添加 JS
 *   public myController::JS($show = true, $cache_path = '', $expires = 604800) — 获取合成脚本
 *   public static myController::etag($etag = '', $expires = 604800) — 检测过期标签
 *   public static myController::file($file, $name = '') — 显示文件
 *   public static myController::guid($para = '') — 生成GUID
 *   public myController::setFunction($position, $func) — 添加钩子代码
 *   public myController::run($position, $desc = false, $para = '') — 执行钩子代码
 *   public myController::start($charset = 'UTF-8', $set_plugin = true) — 页面起始
 *   public myController::show(myTemplate $tpl, $minify = false) — 显示页面
 *   public myController::end() — 页面结束
 *   public static myController::redirect($url = '', $code = '302') — 链接跳转
 *   public static myController::setOp($setting='check') — opCache 设置
 *   public static myController::regClass($setting) — 设置类载入规则
 *   public static myController::setAlias($list) — 设置类别名
 *   public static myController::header($idx, $para = '', $exit = true) — 发送信息头
 *
 * 使用说明：
 *   1. 由myStep及应用控制器继承；先登记资源和回调，再执行生命周期。
 *   2. show/end可能输出或结束请求；需要字符串时使用相应非输出入口。
 *   3. 本文件包含可继承公共接口，注释描述实际兼容行为，不改变签名。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名以PHP 8.5.12为运行基线，PHPDoc类型用于说明，不额外强制运行时类型。
 */


/**
控制器基础类，含单实例控制
    self::getInstance($calledClass)                     // 取得类实例
    $this->setSingleton($singleton)                     // 设置单实例模式
    $this->setAddedContent($position, $content)         // 设置页面附加内容
    $this->pushAddedContent(myTemplate $tpl)            // 插入页面附加内容
    $this->setLanguage($language)                       // 设置语言
    $this->setLanguagePack($dir, $lng)                  // 设置语言包
    $this->getLanguage($idx)                            // 获取语言项目
    $this->regApi($name, $method)                       // 设置应用接口（用于数据传输）
    $this->runApi($name)                                // 调用应用接口
    $this->regPlugin($plugin)                           // 注册插件
    $this->plugin()                                     // 执行现有插件
    $this->regModule($module, $func)                    // 设置模块脚本（用于功能页面）
    $this->module($module, $global_vars)                // 调用模块
    $this->regTag($tag_name, $tag_func)                 // 添加模版标签解析方法
    $this->regUrl($mode, $func)                         // 添加URL生成规则
    $this->url($mode)                                   // 生成URL
    $this->regLog($login, $logout, $chg_psw)            // 设置用户登录接口
    $this->login($user_name, $user_pwd)                 // 用户登录
    $this->logout()                                     // 用户退出
    $this->chg_psw($id, $psw_old, $psw_new)             // 变更密码
    $this->addCSS($code)                                // 添加样式表（代码或文件）
    $this->removeCSS($idx)                              // 去除样式表
    $this->clearCSS()                                   // 清空现有样式表
    $this->CSS($show, $cache_path, $expires)            // 获取样式表
    $this->addJS($code)                                 // 添加JS脚本（代码或文件）
    $this->removeJS($idx)                               // 去除JS脚本
    $this->clearJS()                                    // 清空现有JS脚本
    $this->JS($show, $cache_path, $expires)             // 获取JS脚本
    self::etag($etag)                                   // 通过过期标签显示内容
    self::file($file)                                   // 显示文件
    self::guid($para)                                   // 生成唯一ID
    $this->setFunction($func, $position)                // 添加钩子程序
    $this->run($position, $desc)                        // 执行钩子代码
    $this->start($charset)                              // 页面脚本执行初始化
    $this->show(myTemplate $tpl)                        // 显示页面
    $this->end()                                        // 页面脚本执行结束
    self::redirect($url, $code)                         // 链接跳转
    self::setOp($setting)                               // opCache 设置
    self::regClass($setting)                            // 设置类载入规则
    self::setAlias($list)                               // 设置类别名
    self::header($idx, $para, $exit)                    // 发送信息头
*/
require_once('myBase.class.php');
require_once __DIR__.'/myPath.class.php';
require_once __DIR__.'/myClassLoader.class.php';
/**
 * 功能说明：管理实例、语言、插件、模块、API、标签、钩子及CSS/JS合并输出。
 *
 * 使用说明：
 *   1. 由myStep及应用控制器继承；先登记资源和回调，再执行生命周期。
 *   2. show/end可能输出或结束请求；需要字符串时使用相应非输出入口。
 *   3. 本文件包含可继承公共接口，注释描述实际兼容行为，不改变签名。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 *
 * @example ../app/Document/module/myController.php 框架现有演示，仅作为文档引用，不在检查时执行
 */
class myController extends myBase {
    public static
        $modules = array(),
        $singleton = false,
        $goto_url = '';

    protected
        $functions = array(),
        $func_tag = array(),
        $func_api = array(),
        $func_log = array(),
        $plugins = array(),
        $language = array(),
        $page_content = array(),
        $url = array(),
        $css = array(),
        $js = array();

    /**
     * 功能说明：单实例克隆控制
     *
     * 参数说明：无显式参数。
     *
     * @return bool 单例模式下触发错误并返回false，否则为true；PHP的clone表达式仍返回克隆对象
     */
    public function __clone() {
        if (self::$singleton) {
            $this->error('Clone is not allowed.', E_USER_ERROR);
            return false;
        } else {
            foreach ($this as $key => $val) {
                if (is_array($val)) {
                    $this->$key = unserialize(serialize($val));
                } elseif (is_object($val)) {
                    $this->$key = clone($this->$key);
                }
            }
        }
        return true;
    }

    /**
     * 功能说明：单实例模式设置
     *
     * @param bool $singleton 是否启用按类复用实例的单例模式（默认：false）
     * @return void 无返回值。
     */
    public function setSingleton($singleton = false) {
        self::$singleton = $singleton;
    }

    /**
     * 功能说明：按类名取得实例，新实例执行构造初始化，单例复用不隐式重新初始化
     *
     * @param string $calledClass 待创建或获取实例的类名
     * @param mixed ...$args 传给目标类init或构造函数的位置参数（历史位置参数，由func_get_args读取）
     * @return object 新实例优先执行真实构造，所需参数缺省时延迟初始化；已有单例无参复用，显式传参重新初始化
     * @throws ReflectionException 目标反射或底层调用产生的异常继续传递
     * @throws Throwable 自定义解析器、初始化/反射目标或受信执行文件产生的异常继续传递
     */
    public static function getInstance($calledClass) {
        $argList = func_get_args();
        array_shift($argList);
        static $instanceList = [];
        $r = new ReflectionClass($calledClass);
        $key = strtolower($r->getName());
        $created = !self::$singleton || !isset($instanceList[$key]);
        $instance = $created ? $r->newInstanceWithoutConstructor() : $instanceList[$key];
        if($argList) {
            if($created && ($constructor = $r->getConstructor()) && $constructor->isPublic()) {
                $constructor->invokeArgs($instance, $argList);
            } elseif(method_exists($instance, 'init') && is_callable([$instance, 'init'])) {
                $instance->init(...$argList);
            } elseif(($constructor = $r->getConstructor()) && $constructor->isPublic()) {
                $constructor->invokeArgs($instance, $argList);
            }
        } elseif($created) {
            $constructor = $r->getConstructor();
            // 同 new myBase()：无参且仍需要必需参数时允许延迟初始化。
            if($constructor && $constructor->isPublic() && $constructor->getNumberOfRequiredParameters() === 0) {
                $constructor->invoke($instance);
            } elseif(!$constructor && method_exists($instance, 'init') && is_callable([$instance, 'init'])
                && (new ReflectionMethod($instance, 'init'))->getNumberOfRequiredParameters() === 0) {
                $instance->init();
            }
        }
        // 初始化失败不把半成品放入单例表；已存在实例显式传参仍沿用重新初始化约定。
        if(self::$singleton && $created) $instanceList[$key] = $instance;
        return $instance;
    }

    /**
     * 功能说明：设置指定页面内容
     *
     * @param string $position 页面插入位置，例如head/start/end等既有位置
     * @param string $content 要追加到该位置的HTML/脚本内容
     * @return $this 返回当前实例，支持链式调用。
     */
    public function setAddedContent($position, $content) {
        $this->page_content[$position][] = $content;
        return $this;
    }

    /**
     * 功能说明：将内容添加到指定位置
     *
     * @param myTemplate $tpl 已经初始化的模板实例
     * @param string ...$args 可选页面位置名称列表；省略时一次赋值全部位置（历史位置参数，由func_get_args读取）
     * @return void 将内容写入模板变量，不返回拼接结果
     */
    public function pushAddedContent(myTemplate $tpl) {
        $argList = func_get_args();
        $m = count($argList);
        if ($m == 1) {
            $join = function ($content) {
                return join(chr(10), $content).chr(10);
            };
            $tpl->assign('page', array_map($join, $this->page_content));
        } else {
            for ($i = 1; $i < $m; $i++) {
                if (isset($this->page_content[$argList[$i]])) {
                    $tpl->assign('page_' . $argList[$i], join(chr(10), $this->page_content[$argList[$i]]).chr(10));
                }
            }
        }
    }

    /**
     * 功能说明：设置页面语言
     *
     * @param array $language 语言键到文本的映射；非数组不会合并
     * @param string $charset 语言文本需要转换到的目标编码（默认：'utf-8'）
     * @return $this 返回当前实例，支持链式调用。
     */
    public function setLanguage($language, $charset = 'utf-8') {
        if (is_array($language)) {
            $this->language = array_merge($this->language, myString::setCharset($language, $charset));
        }
        return $this;
    }

    /**
     * 功能说明：获取语言包
     *
     * @param string $dir 目标目录
     * @param string $lng 语言包名称，不含文件扩展名（默认：'default'）
     * @param string $charset 语言包文本转换到的目标编码（默认：'utf-8'）
     * @return $this 返回当前实例，支持链式调用。
     */
    public function setLanguagePack($dir, $lng = 'default', $charset = 'utf-8') {
        $dir = myFile::realPath($dir);
        if (is_file($dir . '/' . $lng . '.php')) {
            $language = include($dir . '/' . $lng . '.php');
            if ($language !== 1) $this->setLanguage($language, $charset);
            unset($language);
        }
        return $this;
    }

    /**
     * 功能说明：获取语言项目
     *
     * @param string $idx 语言键名；缺失时按当前语言策略返回原键或默认值
     * @return mixed 已登记语言键的原值；键不存在时原样返回idx
     */
    public function getLanguage($idx) {
        return isset($this->language[$idx]) ? $this->language[$idx] : $idx;
    }

    /**
     * 功能说明：注册 API 处理函数
     *
     * @param string $name 对外登记的API名称
     * @param callable|string $method 接口回调或方法名，空串使用名称约定（默认：''）
     * @return $this 返回当前实例，支持链式调用。
     */
    public function regApi($name, $method = '') {
        if (empty($method) && is_callable($name)) {
            $this->func_api[$name] = $name;
        } elseif (is_callable($method)) {
            $this->func_api[$name] = $method;
        }
        return $this;
    }

    /**
     * 功能说明：获取 API 数据
     *
     * @param string $name 已登记的API名称
     * @param array $para 依位置顺序传给接口的参数（默认：array()）
     * @param string $return 结果输出格式，例如json/xml等已支持格式（默认：'json'）
     * @return string 已登记接口的编码正文；名称未登记时返回空串，不调用接口
     */
    public function runApi($name, $para = array(), $return = 'json') {
        $result = '';
        if (isset($this->func_api[$name])) {
            $api = new myApi();
            $api->regMethod($name, $this->func_api[$name]);
            $result = call_user_func([$api, 'run'], $name, $para, $return);
        }
        return $result;
    }

    /**
     * 功能说明：注册插件
     *
     * @param string $plugin 插件目录名称或路径
     * @param array &$info 引用输出的插件元信息（默认：array()）
     * @return $this 返回当前实例，支持链式调用。
     */
    public function regPlugin($plugin, &$info = array()) {
        $plugin = myFile::realPath($plugin);
        if (is_dir($plugin) && is_file($plugin . '/index.php')) {
            $this->plugins[] = $plugin;
            if (is_file($plugin . '/info.php')) $info = include($plugin . '/info.php');
        }
        return $this;
    }

    /**
     * 功能说明：设置插件
     *
     * 参数说明：无显式参数。
     *
     * @return void 无返回值。
     */
    public function plugin() {
        for ($i = 0, $m = count($this->plugins); $i < $m; $i++) {
            include($this->plugins[$i] . '/index.php');
        }
    }

    /**
     * 功能说明：注册功能模块
     *
     * @param string $module 登记的模块名称
     * @param callable|string $func 处理该模块的回调或执行入口
     * @return void 只登记模块，不立即执行
     */
    public static function regModule($module, $func) {
        if(is_callable($func)) {
            self::$modules[$module] = $func;
        } else {
            $func = myFile::realPath($func);
            if (is_file($func)) {
                self::$modules[$module] = $func;
            }
        }
    }

    /**
     * 功能说明：调用已注册模块
     *
     * @param string $module 模块名称
     * @param string|string[] $global_vars 模块执行前需要导入的全局变量名列表（默认：''）
     * @return void 无返回值。
     */
    public static function module($module, $global_vars = '') {
        if (isset(self::$modules[$module])) {
            if (!empty($global_vars)) {
                if (is_string($global_vars)) $global_vars = explode(',', $global_vars);
                foreach ($global_vars as $k) {
                    $k = str_replace('$', '', trim($k));
                    global $$k;
                }
            }
            $handler = self::$modules[$module];
            if(is_string($handler) && is_file($handler)) include($handler);
            elseif(is_callable($handler)) call_user_func($handler);
            else self::$goto_url = '/';
        } else {
            self::$goto_url = '/';
        }
    }

    /**
     * 功能说明：注册模版标签处理函数
     *
     * @param string $tag_name 模板标签名称
     * @param callable $tag_func 处理模板标签的回调
     * @return $this 返回当前实例，支持链式调用。
     */
    public function regTag($tag_name, $tag_func) {
        $this->func_tag[$tag_name] = $tag_func;
        return $this;
    }

    /**
     * 功能说明：注册 URL 生成函数
     *
     * @param string $mode URL生成策略名称
     * @param callable $func 执行该策略的URL构造回调
     * @return $this 返回当前实例，支持链式调用。
     */
    public function regUrl($mode, $func) {
        if (is_callable($func)) {
            $this->url[$mode] = $func;
        }
        return $this;
    }

    /**
     * 功能说明：获取 URL
     *
     * @param string $mode 已登记的URL生成策略；附加参数传给该策略回调
     * @param mixed ...$args 依顺序转交已登记URL回调的位置参数（历史位置参数，由func_get_args读取）
     * @return mixed 已登记URL回调的原始返回值；未登记模式返回#
     * @throws Throwable 被调用回调/后端的异常按当前入口继续传递，不包装为成功结果
     */
    public function url($mode) {
        $url = '#';
        if (isset($this->url[$mode])) {
            $argList = func_get_args();
            array_shift($argList);
            $url = call_user_func_array($this->url[$mode], $argList);
        }
        return $url;
    }

    /**
     * 功能说明：注册登录接口
     *
     *
     * 回调签名：login($usr,$pwd)、logout()、chg_psw($id,$psw_org,$psw_new)；原始返回值由对应登录入口直接返回。
     *
     * @param callable $login 登录处理回调
     * @param callable|string $logout 退出处理回调，空串不登记（默认：''）
     * @param callable|string $chg_psw 密码修改回调，空串不登记（默认：''）
     * @return $this 返回当前实例，支持链式调用。
     */
    public function regLog($login, $logout = '', $chg_psw = '') {
        $this->func_log = array();
        if (is_callable($login)) $this->func_log['login'] = $login;
        if (is_callable($logout)) $this->func_log['logout'] = $logout;
        if (is_callable($chg_psw)) $this->func_log['chg_psw'] = $chg_psw;
        return $this;
    }

    /**
     * 功能说明：用户登录
     *
     * @param string $usr 登录用户名（默认：''）
     * @param string $pwd 账号密码，空串按调用入口的兼容约定处理（默认：''）
     * @return mixed 登录回调的原始返回值；未登记回调时返回false
     * @throws Throwable 被调用回调/后端的异常按当前入口继续传递，不包装为成功结果
     */
    public function login($usr='', $pwd='') {
        $result = false;
        if (isset($this->func_log['login'])) {
            $result = call_user_func($this->func_log['login'], $usr, $pwd);
        }
        return $result;
    }

    /**
     * 功能说明：用户退出
     *
     * 参数说明：无显式参数。
     *
     * @return mixed 退出回调的原始结果；未登记回调时返回false
     * @throws Throwable 被调用回调/后端的异常按当前入口继续传递，不包装为成功结果
     */
    public function logout() {
        $result = false;
        if (isset($this->func_log['logout'])) {
            $result = call_user_func($this->func_log['logout']);
        }
        return $result;
    }

    /**
     * 功能说明：变更密码
     *
     * @param string $id 操作对象的标识
     * @param string $psw_org 原密码
     * @param string $psw_new 新密码
     * @return mixed 密码修改回调的原始结果；未登记时返回false
     * @throws Throwable 被调用回调/后端的异常按当前入口继续传递，不包装为成功结果
     */
    public function chg_psw($id, $psw_org, $psw_new) {
        $result = false;
        if (isset($this->func_log['chg_psw'])) {
            $result = call_user_func($this->func_log['chg_psw'], $id, $psw_org, $psw_new);
        }
        return $result;
    }

    /**
     * 功能说明：添加 CSS
     *
     * @param string $code CSS文件路径或CSS源码；文件路径会立即读取并按内容去重
     * @return $this 返回当前实例，支持链式调用。
     */
    public function addCSS($code) {
        if (is_file($code)) {
            $md5 = md5_file($code);
            $code = myFile::getLocal($code);
        } else {
            $md5 = md5($code);
        }
        $this->css[$md5] = $code;
        return $this;
    }

    /**
     * 功能说明：移除 CSS
     *
     * @param string $idx 项目、属性或资源的索引名称
     * @return $this 返回当前实例，支持链式调用。
     */
    public function removeCSS($idx) {
        if (is_file($idx)) {
            $idx = md5_file($idx);
        } else if(strpos($idx, ' ')>0) {
            $idx = md5($idx);
        }
        unset($this->css[$idx]);
        return $this;
    }

    /**
     * 功能说明：清空已添加样式
     *
     * 参数说明：无显式参数。
     *
     * @return $this 返回当前实例，支持链式调用。
     */
    public function clearCSS() {
        $this->css = array();
        return $this;
    }

    /**
     * 功能说明：获取合成样式表
     *
     * @param bool $show true输出CSS并结束请求，false返回压缩结果字符串（默认：true）
     * @param string $cache_path 生成资源缓存文件的目录，空串采用默认位置（默认：''）
     * @param int $expires 缓存有效秒数（默认：604800）
     * @return string show为false返回压缩CSS文本；show为true发送响应并结束请求
     */
    public function CSS($show = true, $cache_path = '', $expires = 604800) {
        $css = implode(chr(10), $this->css);
        if (!$show) return myMinify::minify($css, 'css');
        $md5 = md5($css);
        $this->etag($md5 . '.css', $expires);
        if(empty($cache_path)) $cache_path = dirname(__DIR__) . '/cache';
        $minify = new myMinify('css', $cache_path. '/' . $md5 . '.css');
        if (!$minify->check($expires)) {
            $minify->add($css);
        }
        $minify->show();
        return '';
    }

    /**
     * 功能说明：添加 JS
     *
     * @param string $code JavaScript文件路径或源码；文件路径会立即读取并按内容去重
     * @return $this 返回当前实例，支持链式调用。
     */
    public function addJS($code) {
        if (is_file($code)) {
            $md5 = md5_file($code);
            $code = myFile::getLocal($code);
        } else {
            $md5 = md5($code);
        }
        $this->js[$md5] = $code;
        return $this;
    }

    /**
     * 功能说明：移除 JS
     *
     * @param string $idx 项目、属性或资源的索引名称
     * @return $this 返回当前实例，支持链式调用。
     */
    public function removeJS($idx) {
        if (is_file($idx)) {
            $idx = md5_file($idx);
        } else if(strpos($idx, ' ')>0) {
            $idx = md5($idx);
        }
        unset($this->js[$idx]);
        return $this;
    }

    /**
     * 功能说明：清空已添加 JS
     *
     * 参数说明：无显式参数。
     *
     * @return $this 返回当前实例，支持链式调用。
     */
    public function clearJS() {
        $this->js = array();
        return $this;
    }

    /**
     * 功能说明：获取合成脚本
     *
     * @param bool $show true输出JavaScript并结束请求，false返回压缩结果字符串（默认：true）
     * @param string $cache_path 生成资源缓存文件的目录，空串采用默认位置（默认：''）
     * @param int $expires 缓存有效秒数（默认：604800）
     * @return string show为false返回压缩JavaScript文本；show为true发送响应并结束请求
     */
    public function JS($show = true, $cache_path = '', $expires = 604800) {
        $js = implode("\n;\n", $this->js);
        if (!$show) return myMinify::minify($js, 'js');
        $md5 = md5($js);
        $this->etag($md5 . '.js', $expires);
        if(empty($cache_path)) $cache_path = dirname(__DIR__) . '/cache';
        $minify = new myMinify('js', $cache_path . '/' . $md5 . '.js');
        if (!$minify->check($expires)) {
            $minify->add($js);
        }
        $minify->show();
        return '';
    }

    /**
     * 功能说明：检测过期标签
     *
     *
     * 输出约定：写入缓存响应头；GET/HEAD的If-None-Match命中时发送304并exit，支持列表、弱标签及星号；其他方法不返回304。空etag直接返回。
     *
     * @param string $etag 用于条件请求比较的资源标识（默认：''）
     * @param int $expires 缓存有效秒数，限制为非负数（默认：604800）
     * @return void 未命中时返回，命中时终止请求
     * @throws InvalidArgumentException 标签含控制字符
     */
    public static function etag($etag = '', $expires = 604800) {
        if ($etag === '') return;
        $etag = (string)$etag;
        if(preg_match('/[\x00-\x1f\x7f]/', $etag)) throw new InvalidArgumentException('Invalid ETag');
        if(!preg_match('/\A(?:W\/)?"[^"\r\n]*"\z/D', $etag)) $etag = '"'.str_replace('"', '', $etag).'"';
        $expires = max(0, (int)$expires);
        header('Pragma: public');
        header('Cache-Control: private, max-age=' . $expires);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');
        header('ETag: ' . $etag);
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if(in_array($method, ['GET', 'HEAD'], true)) {
            $current = preg_replace('/^W\//', '', $etag);
            preg_match_all('/(?:\A|,)\s*((?:W\/)?"[^"\r\n]*"|\*)\s*(?=,|\z)/D', $_SERVER['HTTP_IF_NONE_MATCH'] ?? '', $matches);
            foreach($matches[1] as $candidate) if($candidate === '*' || preg_replace('/^W\//', '', $candidate) === $current) {
                http_response_code(304);
                if(ob_get_level() > 0) ob_clean();
                exit;
            }
        }
    }

    /**
     * 功能说明：在框架默认及内置gzip缓冲下分块输出文件，保留原始字节并终止请求
     *
     * @param string $file 要发送的本地文件路径
     * @param string $name 下载文件名；空串使用文件名，响应头会过滤控制字符并编码非ASCII字符（默认：''）
     * @return void 成功输出、HEAD或文件不存在（404）均终止请求；不执行页面end钩子
     * @throws RuntimeException 无法移除框架默认响应缓冲或文件读取失败
     */
    public static function file($file, $name = '') {
        $handlers = ob_list_handlers();
        if(!array_diff($handlers, ['default output handler', 'ob_gzhandler'])) {
            $gzip = in_array('ob_gzhandler', $handlers, true);
            while(ob_get_level() > 0) if(!@ob_end_clean()) throw new RuntimeException('Cannot reset response buffer');
            if($gzip) ob_start('ob_gzhandler', 65536);
        } elseif(ob_get_level() > 0) ob_clean();
        if(!is_file($file) || !is_readable($file)) { http_response_code(404); exit; }
        $stream = @fopen($file, 'rb');
        if(!$stream) { http_response_code(404); exit; }
        try {
            $stat = fstat($stream);
            // 内容指纹避免同秒、同长度替换仍误回304；流式哈希不把大文件读入内存。
            $context = hash_init('sha256');
            hash_update_stream($context, $stream);
            $digest = hash_final($context);
            rewind($stream);
            self::etag($digest);
            $name = $name === '' ? basename($file) : basename(str_replace('\\', '/', $name));
            $name = preg_replace('/[\x00-\x1f\x7f]/', '', $name);
            $fallback = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
            if($fallback === '') $fallback = 'download';
            $type = myFile::getMime($file);
            header('Content-Description: File Transfer');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $stat['mtime']) . ' GMT');
            header('Content-Type: ' . $type);
            if($type !== 'text/html') {
                header('Content-Disposition: attachment; filename="'.$fallback.'"; filename*=UTF-8'."''".rawurlencode($name));
                header('Content-Transfer-Encoding: binary');
            }
            // 压缩处理器可能改变字节数，此时由处理器决定传输长度。
            $compression = strtolower((string)ini_get('zlib.output_compression'));
            $transformed = !in_array($compression, ['', '0', 'off'], true);
            foreach(ob_list_handlers() as $handler) if($handler !== 'default output handler') $transformed = true;
            if(!$transformed) header('Content-Length: ' . $stat['size']);
            if(ob_get_level() > 0) ob_clean();
            // 框架默认缓冲不能继续累积整个文件；已有压缩/自定义处理器保留。
            while(ob_get_level() > 0) {
                $handlers = ob_list_handlers();
                if($handlers[count($handlers)-1] !== 'default output handler') break;
                if(!@ob_end_clean()) throw new RuntimeException('Cannot reset response buffer');
            }
            if(strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') {
                // fpassthru可能把mmap文件作为单个大块交给压缩缓冲，显式分块限制峰值。
                while(!feof($stream)) {
                    $chunk = fread($stream, 65536);
                    if($chunk === false) throw new RuntimeException('Cannot read response file');
                    echo $chunk;
                }
            }
        } finally { fclose($stream); }
        // 小文件和大文件均在此结束，防止继续进入路由、模板或追加生命周期输出。
        exit;
    }

    /**
     * 功能说明：生成GUID
     *
     * @param string $para 参与GUID摘要生成的附加文本（默认：''）
     * @return string 32位十六进制GUID摘要，供兼容标识使用，不作为认证令牌
     */
    public static function guid($para = '') {
        $guid = '';
        $uid = uniqid('', true);
        $data = $para;
        $data .= $_SERVER['REQUEST_TIME'];
        $data .= $_SERVER['HTTP_USER_AGENT'];
        $data .= $_SERVER['LOCAL_ADDR'];
        $data .= $_SERVER['LOCAL_PORT'];
        $data .= $_SERVER['REMOTE_ADDR'];
        $data .= $_SERVER['REMOTE_PORT'];
        $hash = strtoupper(hash('ripemd128', $uid . $guid . md5($data)));
        $guid = '' .
            substr($hash, 0, 8) .
            '-' .
            substr($hash, 8, 4) .
            '-' .
            substr($hash, 12, 4) .
            '-' .
            substr($hash, 16, 4) .
            '-' .
            substr($hash, 20, 12) .
            '';
        return $guid;
    }

    /**
     * 功能说明：添加钩子代码
     *
     *
     * 回调签名取决于run：显式传入第三参数时按管道传递，保留空串、0、false及null；省略第三参数时只传当前控制器。
     *
     * @param string $position 内容/钩子插入位置名称
     * @param callable|callable[] $func 单个回调（包括可调用数组）或按登记顺序执行的回调列表
     * @return $this 返回当前实例，支持链式调用。
     */
    public function setFunction($position, $func) {
        if (!isset($this->functions[$position])) $this->functions[$position] = array();
        if(is_callable($func)) {
            $this->functions[$position][] = $func;
        } elseif(is_array($func)) {
            foreach($func as $callback) if(is_callable($callback)) $this->functions[$position][] = $callback;
        }
        return $this;
    }

    /**
     * 功能说明：执行钩子代码
     *
     * @param string $position 内容/钩子插入位置名称
     * @param bool $desc true按登记顺序的逆序执行回调（默认：false）
     * @param mixed $para 显式传入时启动管道；数组展开为位置参数，其他值作为单参数；省略时传当前控制器（默认：''）
     * @return mixed 依次执行回调后的最终值；初始参数及回调返回值按已有管道规则传递
     * @throws Throwable 被调用回调/后端的异常按当前入口继续传递，不包装为成功结果
     */
    public function run($position, $desc = false, $para = '') {
        $pipeline = func_num_args() >= 3;
        if (isset($this->functions[$position])) {
            $m = count($this->functions[$position]);
            for ($i = 0; $i < $m; $i++) {
                $n = $desc ? ($m - $i - 1) : $i;
                if ($pipeline) {
                    $arguments = is_array($para) ? array_values($para) : [$para];
                    $para = call_user_func_array($this->functions[$position][$n], $arguments);
                } else {
                    call_user_func($this->functions[$position][$n], $this);
                }
            }
        }
        return $para;
    }

    /**
     * 功能说明：页面起始
     *
     * @param string $charset 字符编码名称，例如UTF-8；是否自动检测由具体入口决定（默认：'UTF-8'）
     * @param bool $set_plugin 是否加载当前配置中启用的插件（默认：true）
     * @return void 无返回值。
     */
    public function start($charset = 'UTF-8', $set_plugin = true) {
        if(count(ob_list_handlers())) {
            $content = ob_get_contents();
            ob_end_clean();
        }
        ob_implicit_flush(false);
        header('Powered-by: MyStep Framework');
        header_remove('x-powered-by');
        mb_internal_encoding($charset);
        mb_http_output($charset);
        mb_regex_encoding($charset);
        header('Content-Type: text/html; charset=' . $charset);

        // OPcache由服务器统一管理，请求启动不覆盖php.ini的内存、开关及重验频率。
        if(count(ob_list_handlers()) == 0 && !headers_sent()) ob_start();

        if ($set_plugin) $this->plugin();
        $this->run('start');
        if(isset($content)) {
            echo $content;
            unset($content);
        }
    }

    /**
     * 功能说明：显示页面
     *
     *
     * 输出约定：渲染并echo页面，可能开启缓冲；本方法本身不结束请求，生命周期收尾由调用方负责。
     *
     * @param myTemplate $tpl 已经初始化的模板实例
     * @param bool $minify 是否压缩最终HTML输出（默认：false）
     * @param string ...$args 兼容第三位置参数：传给模板render的子模板名称（历史位置参数，由func_get_args读取）
     * @return void 无返回值。
     */
    public function show(myTemplate $tpl, $minify = false) {
        $this->pushAddedContent($tpl);
        $tpl->assign('lng', $this->language);
        $tpl->regTag($this->func_tag);
        if(count(ob_list_handlers()) == 0 && !headers_sent()) ob_start();
        $page = $tpl->render((func_num_args()==3 ? func_get_arg(2) : ''), false, $minify);
        $page = $this->run('page', false, $page);
        echo $this instanceof myStep ? mySecurity::decorate($page) : $page;
        unset($tpl);
    }

    /**
     * 功能说明：页面结束
     *
     * 参数说明：无显式参数。
     *
     *
     * 生命周期：执行end钩子及可调用shutdown；通常返回调用方，只有满足预设goto跳转条件时才结束请求。
     *
     * @return void 无返回值。
     */
    public function end() {
        $this->run('end', true);
        if(is_callable(array($this, 'shutdown'))) $this->shutdown();
        if (!empty(self::$goto_url) && ob_get_length() == 0) {
            $this->redirect(self::$goto_url);
            exit();
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
        if (empty($url)) {
            $url = myReq::server('HTTP_REFERER');
            if (is_null($url)) $url = '/';
            $code = '302';
        }
        header('location: ' . $url, true, $code);
        exit;
    }

    /**
     * 功能说明：opCache 设置
     *
     * @param array|string $setting opcache配置数组，或check/config/debug/reset命令（默认：'check'）
     * @return array|bool config返回OPcache配置，其他命令返回当前操作/状态结果；扩展不可用为false
     */
    public static function setOp($setting='check') {
        if($result = function_exists('opcache_compile_file')) {
            if($result = opcache_get_status()) {
                switch(true) {
                    case is_array($setting):
                        foreach($setting as $k => $v) {
                            @ini_set('opcache.'.$k, $v);
                        }
                    case $setting=='check':
                        opcache_invalidate($_SERVER['SCRIPT_FILENAME'], false);
                        if(($result=opcache_is_script_cached($_SERVER['SCRIPT_FILENAME']))===false)
                            $result=opcache_compile_file($_SERVER['SCRIPT_FILENAME']);
                        break;
                    case $setting=='config':
                        $result = opcache_get_configuration();
                        break;
                    case $setting=='debug':
                        @ini_set('opcache.revalidate_freq', '0');
                        break;
                    case $setting=='reset':
                        opcache_reset();
                        break;
                }
            }
        }
        return $result;
    }

    /**
     * 功能说明：设置类载入规则
     *
     * @param array $setting 加载规则列表；path相对于ROOT，运行时兼容历史绝对路径；ext逗号分隔，idx文件相对于该path
     * @return void 登记自动加载回调，不立即创建实例
     */
    public static function regClass($setting) {
        $idx = array();
        foreach($setting as $current) {
            $current['path'] = rtrim(myPath::absolute($current['path']), '/').'/';
            /*
            set_include_path(get_include_path().PATH_SEPARATOR.$current['path']);
            spl_autoload_extensions($current['ext']);
            spl_autoload_register();
            */
            spl_autoload_register(function ($class) use ($current) {
                $ext = explode(',', $current['ext']);
                foreach($ext as $e) {
                    if(is_file($current['path'].$class.$e)) {
                        require_once($current['path'].$class.$e);
                        break;
                    }
                }
            });
            if(isset($current['idx'])) {
                $idx += array_map(function ($str) use ($current) {
                    return $current['path'].$str;
                }, $current['idx']);
            }
        }
        if(!empty($idx)) {
            spl_autoload_register(function ($class) use ($idx) {
                if(array_key_exists($class, $idx)) require_once($idx[$class]);
            });
        }
    }

    /**
     * 功能说明：设置类别名
     *
     * @param array $list 原类名与别名的映射
     * @return void 无返回值。
     */
    public static function setAlias($list) {
        foreach ($list as $k => $v) {
            if(class_exists($k)) class_alias($k, $v);
        }
    }

    /**
     * 功能说明：发送信息头
     *
     * @param string|int $idx 状态码或已支持的响应头类别
     * @param string|array $para 响应头模板的替换值；数组用于refresh等多值模板（默认：''）
     * @param bool $exit 是否在发出响应头后结束请求（默认：true）
     * @return void 发送响应头；exit为true时结束请求
     */
    public static function header($idx, $para = '', $exit = true) {
        //http_response_code()
        $headers = [
            '100' => 'HTTP/1.1 100 Continue',
            '101' => 'HTTP/1.1 101 Switching Protocols',
            '200' => 'HTTP/1.1 200 OK',
            '201' => 'HTTP/1.1 201 Created',
            '202' => 'HTTP/1.1 202 Accepted',
            '203' => 'HTTP/1.1 203 Non-Authoritative Information',
            '204' => 'HTTP/1.1 204 No Content',
            '205' => 'HTTP/1.1 205 Reset Content',
            '206' => 'HTTP/1.1 206 Partial Content',
            '300' => 'HTTP/1.1 300 Multiple Choices',
            '301' => 'HTTP/1.1 301 Moved Permanently',
            '302' => 'HTTP/1.1 302 Moved Temporarily',
            '303' => 'HTTP/1.1 303 See Other',
            '304' => 'HTTP/1.1 304 Not Modified',
            '305' => 'HTTP/1.1 305 Use Proxy',
            '400' => 'HTTP/1.1 400 Bad Request',
            '401' => 'HTTP/1.1 401 Unauthorized',
            '402' => 'HTTP/1.1 402 Payment Required',
            '403' => 'HTTP/1.1 403 Forbidden',
            '404' => 'HTTP/1.1 404 Not Found',
            '405' => 'HTTP/1.1 405 Method Not Allowed',
            '406' => 'HTTP/1.1 406 Not Acceptable',
            '407' => 'HTTP/1.1 407 Proxy Authentication Required',
            '408' => 'HTTP/1.1 408 Request Time-out',
            '409' => 'HTTP/1.1 409 Conflict',
            '410' => 'HTTP/1.1 410 Gone',
            '411' => 'HTTP/1.1 411 Length Required',
            '412' => 'HTTP/1.1 412 Precondition Failed',
            '413' => 'HTTP/1.1 413 Request Entity Too Large',
            '414' => 'HTTP/1.1 414 Request-URI Too Large',
            '415' => 'HTTP/1.1 415 Unsupported Media Type',
            '500' => 'HTTP/1.1 500 Internal Server Error',
            '501' => 'HTTP/1.1 501 Not Implemented',
            '502' => 'HTTP/1.1 502 Bad Gateway',
            '503' => 'HTTP/1.1 503 Service Unavailable',
            '504' => 'HTTP/1.1 504 Gateway Time-out',
            '505' => 'HTTP/1.1 505 HTTP Version not supported',
            'charset' => 'Content-Type:text/html;charset=$charset',
            'atom' => 'Content-type: application/atom+xml',
            'rss' => 'Content-Type: application/rss+xml; charset=ISO-8859-1',
            'type' => 'Content-Type: $type',
            'url' => 'Location: $url',
            'refresh' => 'Refresh: $refresh_0; url=$refresh_1',
            'powered' => 'X-Powered-By: $powered',
            'mod' => 'Last-Modified: $mod GMT', //gmdate('D, d M Y H:i:s', $time)
            'no_cache' => [
                'Cache-Control: no-cache, no-store, max-age=0, must-revalidate',
                'Expires: Thu, 01 Jan 1970 00:00:00 GMT',
                'Pragma: no-cache',
            ],
            'auth' => [
                'HTTP/1.1 401 Unauthorized',
                'WWW-Authenticate: Basic realm="Top Secret"',
            ]
        ];
        $set_para = function($str, $idx, $para='') {
            if(!empty($para)) {
                if(is_array($para)) {
                    foreach($para as $k => $v) {
                        $str = str_replace('$'.$idx.'_'.$k, $v, $str);
                    }
                } else {
                    $str = str_replace('$'.$idx, $para, $str);
                }
            }
            return $str;
        };
        if(isset($headers[$idx])) {
            if(is_array($headers[$idx])) {
                foreach($headers[$idx] as $h) {
                    header($set_para($h, $idx, $para));
                }
            } else {
                header($set_para($headers[$idx], $idx, $para));
            }
        } else {
            trigger_error('Unknown header - '.$idx);
        }
        if($exit) exit;
    }
}