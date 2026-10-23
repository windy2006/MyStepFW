<?php
/**
 * 文件：app/Admin/Admin.class.php
 * 功能说明：Admin应用核心，直接继承myStep，处理认证、模块分发、校验、模板和请求反馈。
 * 方法目录：
 *   protected Admin::definition() — 声明页面、菜单、视图和动作
 *   protected Admin::service() — 创建当前应用业务服务
 *   public Admin::preload() — 准备页面清单及服务，不执行业务动作
 *   public Admin::setInstance() — 按需准备数据库代理，保留已配置的会话后端
 *   public Admin::start($charset = 'UTF-8', $set_plugin = true) — 登记表单校验脚本并进入框架启动流程
 *   public static Admin::dispatch($path = '') — 将路由捕获交给当前后台控制器
 *   public Admin::authorized() — 同时验证管理员用户名和权限标志
 *   public Admin::handle($path = '') — 执行白名单分发、统一异常处理和POST重定向
 *   public Admin::url($path = '', array $query = []) — 生成支持三种路由模式的后台URL
 *   public Admin::input($key, $default = null) — 读取POST优先的原始输入
 *   public Admin::validate(array $rules, array $source) — 按动作字段声明取值并校验
 *   public static Admin::e($v) — 将普通显示值转义为HTML文本
 *   public Admin::view($name, array $values = [], array $loops = [], array $conditions = []) — 按当前应用模板配置创建子模板，赋值、填充循环/条件后调用框架render
 *   public Admin::link($path, $label, array $query = []) — 生成已转义的导航链接
 *   public Admin::field(array $f) — 按字段声明生成控件
 *   public Admin::form($action, array $fields, array $options = []) — 生成POST表单，完整页面由mySecurity装饰CSRF字段
 *   public Admin::table(array $columns, array $rows) — 按列声明生成默认转义的表格
 *   public Admin::panel($title, $body) — 生成统一内容卡片
 *   public Admin::success($message = '保存成功。', $page = null, array $query = []) — 建立成功提示与重定向结果
 *   protected Admin::audit($page, $action, $ok) — 记录不含业务参数和口令的操作摘要
 *   public Admin::page($title, $body, $notice = '') — 通过myTemplate输出完整后台布局
 *   public Admin::layout($title, $body, $notice = '') — 创建完整后台模板，允许共用布局而由原控制器输出
 *   public static Admin::pluginLayout($body, array $context) — 为所有后台插件提供Admin布局与资源
 *   protected Admin::loginPage() — 验证验证码及凭据并显示登录表单
 *
 * 使用说明：本类直接继承myStep；definition/service装配本应用；route.php登记dispatch，页面使用view创建myTemplate并赋值/循环后render。
 * 布局和控件HTML保存在应用template/default中；沿用path/style与default风格回退。
 * 写操作只允许POST，自动CSRF；普通字段转义，显式html字段仅接受受信服务生成的标记。
 */
/**
 * 功能说明：集中执行页面白名单、管理员校验、表单与列表渲染及POST动作。
 * 使用说明：参见文件顶部；只接受服务端构造的路径和定义。
 * 方法目录：参见文件顶部。
 */
require_once __DIR__.'/lib.php';
class Admin extends myStep {
    /** @var array 页面路径到受信页面定义 */
    protected $adminPages = [];
    /** @var object 当前应用业务服务 */
    protected $adminService;
    /** @var string 当前匹配的页面路径 */
    protected $adminPage = '';
    /**
     * 功能说明：声明页面、菜单、视图和动作。
     * 参数说明：无显式参数。
     * @return array 页面路径到title/group/view/actions定义
     */
    protected function definition() {
        return include __DIR__.'/pages.php';
    }
    /**
     * 功能说明：创建当前应用业务服务。
     * 参数说明：无显式参数。
     * @return object 页面/动作服务
     */
    protected function service() {
        return new AdminService($this);
    }
    /**
     * 功能说明：准备页面清单及服务，不执行业务动作。
     * 参数说明：无显式参数。
     * @return void preload只准备定义和服务，不连接数据库
     */
    public function preload() {
        $this->adminPages = $this->definition();
        $this->adminService = $this->service();
    }
    /**
     * 功能说明：按需准备数据库代理，保留已配置的会话后端。
     * 参数说明：无显式参数。
     * @return void 保留配置的会话后端，普通SQL连接仍按需建立
     */
    public function setInstance() {
        $mode = $this->setting->session->mode;
        if(empty($GLOBALS['db']) && strtolower($this->setting->db->type) === 'sqlite')$GLOBALS['db'] = new myDb('SQLite', $this->setting->db->host);
        parent::setInstance();
        $this->setting->session->mode = $mode;
    }
    /**
     * 功能说明：登记表单校验脚本并进入框架启动流程。
     * @param string $charset 继承占位参数（默认值见方法签名）
     * @param bool $set_plugin 继承参数（默认值见方法签名）
     * @return void 收集表单校验脚本后进入框架启动
     */
    public function start($charset = 'UTF-8', $set_plugin = true) {
        $this->addJS(STATICS . 'js/checkForm.js');
        parent::start($charset, $set_plugin);
    }
    /**
     * 功能说明：将路由捕获交给当前后台控制器。
     * @param string $path 捕获的页面路径（默认值见方法签名）
     * @return void 分发页面
     * @throws Throwable 校验、回调或文件操作异常继续传递
     */
    public static function dispatch($path = '') {
        $o = $GLOBALS['mystep'];
        if(!($o instanceof self))throw new LogicException('后台未初始化。');
        $o->handle(trim($path, '/'));
    }
    /**
     * 功能说明：同时验证管理员用户名和权限标志。
     * 参数说明：无显式参数。
     * @return bool 同时验证框架管理员用户名及权限标志
     */
    public function authorized() {
        return myReq::session('ms_user') === $this->setting->gen->s_usr && myReq::session('sysop') === 'y';
    }
    /**
     * 功能说明：执行白名单分发、统一异常处理和POST重定向。
     * @param string $path 页面路径（默认值见方法签名）
     * @return void 输出页面；动作成功303；API管理AJAX返回JSON，校验422、冲突409、异常500
     * @throws Throwable 校验、回调或文件操作异常继续传递
     */
    public function handle($path = '') {
        $this->adminPage = $path;
        $ajax=$path==='function/api' && ($_SERVER['REQUEST_METHOD']??'GET')==='POST' && ($_SERVER['HTTP_X_REQUESTED_WITH']??'')==='XMLHttpRequest';
        if(!$this->authorized()) {
            if($ajax){http_response_code(401);header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: no-store');echo json_encode(['ok'=>false,'message'=>'登录已失效，请重新登录。'],JSON_UNESCAPED_UNICODE);return;}
            $this->loginPage();
            return;
        }
        if($path === 'logout') {
            if(($_SERVER['REQUEST_METHOD']??'GET') !== 'POST') {
                http_response_code(405);
                header('Allow: POST');
                $this->page('请求方法错误', '请使用退出表单。');
                return;
            }
            $this->logout();
            self::redirect($this->url(), 303);
        }
        $entry = $this->adminPages[$path]??null;
        if(!$entry) {
            http_response_code(404);
            $this->page('页面不存在', $this->view('message', ['message' => '请选择菜单中的页面。', 'back_url' => $this->url()]));
            return;
        }
        $notice = myReq::session('_admin_notice');
        myReq::removeSession('_admin_notice');
        try {
            if(($_SERVER['REQUEST_METHOD']??'GET') === 'POST') {
                $action = $this->input('_action', '');
                $spec = is_string($action)?($entry['actions'][$action]??null):null;
                if(!$spec)throw new InvalidArgumentException('没有登记此操作。');
                $params = $this->validate($spec['fields']??[], $_POST);
                $call = function()use($spec, $params) {
                    return call_user_func([$this->adminService, $spec['handler']], $params);
                }
                ;
                $result = empty($spec['mutex'])?$call():AdminStore::locked(CACHE . 'admin/' . AdminStore::identifier($spec['mutex']) . '.lock', $call);
                $this->audit($path, $action, true);
                if(isset($result['download'])) {
                    myController::file($result['download']['file'], $result['download']['name']);
                    return;
                }
                if($ajax){header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: no-store');echo json_encode($result,JSON_UNESCAPED_UNICODE);return;}
                myReq::session('_admin_notice', $result['message']??'操作完成。');
                self::redirect($this->url($result['page']??$path, $result['query']??[]), 303);
            }
            if(!in_array($_SERVER['REQUEST_METHOD']??'GET', ['GET', 'HEAD'], true)) {
                http_response_code(405);
                header('Allow: GET, HEAD, POST');
                return;
            }
            $content = call_user_func([$this->adminService, $entry['view']]);
        }
        catch(Throwable $error) {
            $this->audit($path, $this->input('_action', 'view'), false);
            $expected = $error instanceof InvalidArgumentException || $error instanceof UnexpectedValueException || $error->getCode() === 409;
            http_response_code($error->getCode() === 409?409:($expected?422:500));
            if($ajax){header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: no-store');echo json_encode(['ok'=>false,'message'=>$expected?$error->getMessage():'处理失败，请检查服务器日志。'],JSON_UNESCAPED_UNICODE);if(!$expected)error_log('Admin '.$path.': '.$error->getMessage());return;}
            $content = $this->view('message', ['message_title' => '操作未完成', 'message' => $expected?$error->getMessage():'处理失败，请检查服务器日志。', 'back_url' => $this->url($path)]);
            if(!$expected)error_log('Admin ' . $path . ': ' . $error->getMessage());
        }
        $this->page($entry['title'], $content, is_string($notice)?$notice:'');
    }
    /**
     * 功能说明：生成支持三种路由模式的后台URL。
     * @param string $path 相对页面（默认值见方法签名）
     * @param array $query 查询参数（默认值见方法签名）
     * @return string 三种路由模式的URL
     */
    public function url($path = '', array $query = []) {
        $app = $GLOBALS['info_app']['app'];
        $route = defined('URL_FIX') && URL_FIX === $app?$path:$app . '/' . ltrim($path, '/');
        $url = self::setURL($route);
        $q = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        return $url . ($q === ''?'':(strpos($url, '?') === false?'?':'&') . $q);
    }
    /**
     * 功能说明：读取POST优先的原始输入。
     * @param string $key 参数名
     * @param mixed $default 默认
     * @return mixed POST优先的原值
     */
    public function input($key, $default = null) {
        return $_POST[$key]??($_GET[$key]??$default);
    }
    /**
     * 功能说明：按动作字段声明取值并校验。
     * @param array $rules 字段到type/required/default/min/max/pattern/choices规则
     * @param array $source 输入
     * @return array 已校验字段
     * @throws InvalidArgumentException 校验失败
     */
    public function validate(array $rules, array $source) {
        $out = [];
        foreach($rules as $name => $r) {
            if(is_string($r))$r = ['type' => $r];
            $v = $source[$name]??($r['default']??null);
            if(($r['required']??false) && ($v === null || $v === ''))throw new InvalidArgumentException('缺少字段：' . $name);
            if($v === null) {
                $out[$name] = null;
                continue;
            }
            switch($r['type']??'string') {
                case 'array':if(!is_array($v))throw new InvalidArgumentException('字段须为数组：' . $name);
                break;
                case 'int':if(filter_var($v, FILTER_VALIDATE_INT) === false)throw new InvalidArgumentException('字段须为整数：' . $name);
                $v = (int)$v;
                if($v < ($r['min']??PHP_INT_MIN) || $v > ($r['max']??PHP_INT_MAX))throw new InvalidArgumentException('数值超界：' . $name);
                break;
                default:if(!is_string($v) || strlen($v) > ($r['max']??2097152))throw new InvalidArgumentException('文本无效或过长：' . $name);
            }
            if(isset($r['pattern']) && (!is_string($v) || !preg_match($r['pattern'], $v)))throw new InvalidArgumentException('字段格式不正确：' . $name);
            if(isset($r['choices']) && !in_array($v, $r['choices'], true))throw new InvalidArgumentException('未知选项：' . $name);
            $out[$name] = $v;
        }
        return $out;
    }
    /**
     * 功能说明：将普通显示值转义为HTML文本。
     * @param mixed $v 显示值
     * @return string HTML转义
     */
    public static function e($v) {
        return htmlspecialchars(is_scalar($v)?(string)$v:'', ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
    }
    /**
     * 功能说明：按当前应用模板配置创建子模板，赋值、填充循环/条件后调用框架render。
     * @param string $name 受信模板名，沿用当前path/style及框架default风格回退
     * @param array $values 普通变量；默认空数组，显示时由模板选择html转义
     * @param array $loops 循环名到完整记录集合；默认空数组
     * @param array $conditions 条件名到布尔值；默认空数组
     * @return string 模板渲染内容，完整页面仍交给show统一装饰CSRF
     * @throws Throwable 模板定位、编译或渲染异常继续传递
     */
    public function view($name, array $values = [], array $loops = [], array $conditions = []) {
        $setting = $GLOBALS['tpl_setting'];
        $setting['name'] = $name;
        $tpl = new myTemplate($setting, false);
        $tpl->assign(['page_url' => $this->url($this->adminPage)]);
        $tpl->assign($values);
        foreach($loops as $key => $rows) $tpl->setLoop($key, $rows, true);
        foreach($conditions as $key => $value) $tpl->setIf($key, $value);
        return $this->render($tpl);
    }

    /**
     * 功能说明：生成已转义的导航链接。
     * @param string $path 页面或HTTP URL
     * @param string $label 文本
     * @param array $query 查询（默认值见方法签名）
     * @return string 安全链接
     */
    public function link($path, $label, array $query = []) {
        $url = preg_match('#^(?:https?:)?//#i', $path)?$path:$this->url($path, $query);
        return $this->view('component_link', ['url' => $url, 'label' => $label]);
    }
    /**
     * 功能说明：按字段声明生成控件，必填项同时输出框架 need="" 与原生 required。
     * @param array $f name/type/label/value/options/help/required/rows
     * @return string 控件；html为受信标记
     */
    public function field(array $f) {
        $f += ['name' => '', 'type' => 'text', 'label' => '', 'value' => '', 'help' => '', 'required' => false, 'rows' => 8];
        $f['rows'] = max(2, (int)$f['rows']);
        $type = in_array($f['type'], ['html','hidden','textarea','select','checkbox','text','password','number','file','email'], true)?$f['type']:'text';
        $options = [];
        foreach($f['options']??[] as $value => $label) $options[] = ['value' => $value, 'label' => $label, 'selected' => (string)$value === (string)$f['value']?'selected':''];
        return $this->view('component_field', $f+['required_attr' => $f['required']?'need="" required':'', 'checked' => $f['value']?'checked':'', 'input_type' => $type], ['options' => $options], ['html' => $type === 'html', 'hidden' => $type === 'hidden', 'textarea' => $type === 'textarea', 'select' => $type === 'select', 'checkbox' => $type === 'checkbox', 'file' => $type === 'file']);
    }
    /**
     * 功能说明：生成POST表单，完整页面由mySecurity装饰CSRF字段。
     * @param string $action 白名单动作
     * @param array $fields 字段定义
     * @param array $options page/query/label/confirm/multipart（默认值见方法签名）
     * @return string 自动CSRF的POST表单
     */
    public function form($action, array $fields, array $options = []) {
        $items = [];
        foreach($fields as $f) $items[] = ['content' => $this->field($f)];
        return $this->view('component_form', ['action' => $action, 'url' => $this->url($options['page']??$this->adminPage, $options['query']??[]), 'label' => $options['label']??'保存', 'confirm' => $options['confirm']??''], ['fields' => $items], ['multipart' => !empty($options['multipart'])]);
    }
    /**
     * 功能说明：按列声明生成默认转义的表格。
     * @param array $columns 键到标题
     * @param array $rows 数据，受信单元格用['html'=>...]
     * @return string 默认转义的表格
     */
    public function table(array $columns, array $rows) {
        $headings = [];
        foreach($columns as $label) $headings[] = ['label' => $label];
        $records = [];
        foreach($rows as $row) {
            $cells = '';
            foreach($columns as $key => $label) {
                $v = $row[$key]??'';
                $html = is_array($v) && array_key_exists('html', $v);
                $cells .= $this->view('component_cell', ['value' => $html?$v['html']:$v], [], ['html' => $html]);
            }
            $records[] = ['cells' => $cells];
        }
        return $this->view('component_table', ['colspan' => count($columns)], ['headings' => $headings, 'records' => $records], ['empty' => !$records]);
    }
    /**
     * 功能说明：生成统一内容卡片。
     * @param string $title 标题
     * @param string $body 受信HTML
     * @return string 卡片
     */
    public function panel($title, $body) {
        return $this->view('component_panel', ['title' => $title, 'body' => $body]);
    }
    /**
     * 功能说明：建立成功提示与重定向结果。
     * @param string $message 提示（默认值见方法签名）
     * @param string|null $page 目标，null当前页（默认值见方法签名）
     * @param array $query 查询（默认值见方法签名）
     * @return array PRG结果
     */
    public function success($message = '保存成功。', $page = null, array $query = []) {
        return ['message' => $message, 'page' => $page??$this->adminPage, 'query' => $query];
    }
    /**
     * 功能说明：记录不含业务参数和口令的操作摘要。
     * @param string $page 页面
     * @param mixed $action 动作
     * @param bool $ok 结果
     * @return void 仅记录动作与身份，不记录配置值和口令
     */
    protected function audit($page, $action, $ok) {
        $file = CACHE . 'admin/audit-' . date('Y-m') . '.jsonl';
        myFile::mkdir(dirname($file));
        file_put_contents($file, json_encode(['time' => date(DATE_ATOM), 'user' => myReq::session('ms_user'), 'page' => $page, 'action' => is_scalar($action)?$action:'invalid', 'ok' => $ok, 'ip' => $_SERVER['REMOTE_ADDR']??''], JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND|LOCK_EX);
    }
    /**
     * 功能说明：通过myTemplate输出完整后台布局。
     * @param string $title 标题
     * @param string $body 受信HTML
     * @param string $notice 提示（默认值见方法签名）
     * @return void 标准myTemplate布局及myStep输出
     */
    public function page($title, $body, $notice = '') {
        $this->show($this->layout($title,$body,$notice));
    }
    /**
     * 功能说明：准备导航、账号及主体，返回应用配置指定的外层模板。
     * @param string $title 页面标题
     * @param string $body 已渲染的可信主体HTML
     * @param string $notice 普通提示文字（默认：''）
     * @return myTemplate 完整后台模板，页面缓存关闭，由调用者选择控制器输出
     * @throws Throwable 模板或导航渲染失败
     */
    public function layout($title, $body, $notice = '') {
        $groups = [];
        $active = '';
        foreach($this->adminPages as $path => $entry) {
            if(!empty($entry['group']) && ($path === $this->adminPage || ($path !== '' && myRouter::hasPrefix('/'.$this->adminPage, '/'.$path))) && strlen($path) >= strlen($active)) $active = $path;
        }
        if($this->authorized()) {
            foreach($this->adminPages as $path => $entry) {
                if(empty($entry['group'])) continue;
                $groups[$entry['group']][] = ['url' => $this->url($path), 'label' => $entry['title'], 'current' => $path === $active?'page':'false'];
            }
            $menu = json_decode(myFile::getLocal(APP . 'myStep/menu.json'), true);
            foreach(is_array($menu)?($menu[array_key_last($menu)]['items']??[]):[] as $item) $groups['扩展'][] = ['url' => self::setURL($item['link']), 'label' => $item['name'], 'current' => 'false'];
        }
        $navigation = [];
        foreach($groups as $titleGroup => $links) $navigation[] = ['content' => $this->view('navigation_group', ['title' => $titleGroup], ['links' => $links])];
        $legacy = AdminStore::read(APP . 'myStep/config.php');
        $legacyPath = $legacy['gen']['path_admin']??($this->setting->gen->path_admin??'console');
        $tpl = new myTemplate($GLOBALS['tpl_setting'], false);
        $tpl->assign(['admin_title' => $title, 'main' => $body, 'admin_notice' => $notice, 'admin_home' => $this->url(), 'admin_user' => myReq::session('ms_user')??'访客', 'logout_url' => $this->url('logout'), 'admin_legacy' => self::setURL($legacyPath . '/'), 'php_version' => PHP_VERSION]);
        $tpl->setLoop('navigation', $navigation, true);
        $tpl->setIf('authenticated', $this->authorized());
        return $tpl;
    }
    /**
     * 功能说明：为任意已验证插件提供Admin外层，业务仍由原请求的控制器执行和输出。
     *
     * 仅读取应用模板配置，构建导航与通用plugin功能模板；不启动会话、插件或数据库。
     * 构建期间临时使用Admin模板上下文，正常返回或异常时均恢复原全局应用和模板设置。
     * CSS/JS登记到原控制器，编译及资源缓存隔离由公共插件展示函数负责。
     *
     * @param string $body 插件自身已渲染的可信HTML
     * @param array $context plugin、page、app、title组成的已校验展示信息
     * @return myTemplate Admin完整页面模板
     * @throws Throwable 配置、模板或导航装配失败
     * @global myStep $mystep 原业务控制器
     * @global array $info_app 原请求应用及路由信息
     * @global array $tpl_setting 原应用模板配置
     */
    public static function pluginLayout($body, array $context) {
        global $mystep,$info_app,$tpl_setting;
        $config=new myConfig(__DIR__.'/config.php');
        $settings=myPath::settings($config->get(true),'Admin');
        $template=array_replace(['name'=>'main','path'=>'app/Admin/template','style'=>'default'],$settings['template']??[]);
        $previousApp=$info_app;$previousTemplate=$tpl_setting;
        try {
            $info_app['app']='Admin';
            $tpl_setting=['name'=>$template['name'],'path'=>myPath::absolute($template['path']),'style'=>$template['style'],'path_compile'=>CACHE.'template/plugin/'.$context['plugin'].'/Admin/layout/'];
            $ui=new self();$ui->preload();$ui->adminPage='plugin';
            $content=$ui->view('plugin',['content'=>$body,'plugin'=>$context['plugin']]);
            $tpl=$ui->layout($context['title'],$content);
            foreach(['asset/style.css','asset/'.$template['style'].'/style.css'] as $file) $mystep->addCSS(APP.'Admin/'.$file);
            foreach(['asset/function.js','asset/'.$template['style'].'/function.js'] as $file) $mystep->addJS(APP.'Admin/'.$file);
            return $tpl;
        } finally {$info_app=$previousApp;$tpl_setting=$previousTemplate;}
    }
    /**
     * 功能说明：验证验证码及凭据并显示登录表单。
     * 参数说明：无显式参数。
     * @return void 登录及验证码；口令保持原始输入，成功后303回首页
     */
    protected function loginPage() {
        $error = '';
        if(($_SERVER['REQUEST_METHOD']??'GET') === 'POST') {
            $captcha = $this->input('captcha', '');
            $expected = myReq::session('captcha');
            myReq::removeSession('captcha');
            if(!is_string($captcha) || !is_string($expected) || $expected === '' || strcasecmp($expected, $captcha) !== 0)$error = '验证码不正确，请重新输入。';
            elseif(!is_string($this->input('username')) || !is_string($this->input('password')) || !$this->ms_login($this->input('username'), $this->input('password')))$error = '用户名或密码不正确。';
            else {
                $this->rememberAdmin(86400*max(0, min(365, (int)$this->input('expire', 0))));
                self::redirect($this->url(), 303);
            }
            http_response_code(422);
        }
        $this->page('管理员登录', $this->view('login', ['error' => $error, 'login_url' => $this->url(), 'captcha_url' => self::setURL('captcha/')]));
    }
}
