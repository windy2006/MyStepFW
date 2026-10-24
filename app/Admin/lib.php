<?php
/**
 * 文件：app/Admin/lib.php
 * 功能说明：Admin 应用内公共声明；集中页面白名单、模块加载和原子配置保存辅助能力。
 * 方法目录：app\Admin\pages；AdminStore、AdminService 的完整目录见各类前的说明。
 * 使用说明：由核心类 require_once；此文件只声明通用辅助能力，不渲染页面，不执行保存。
 * 页面业务在 module/，展示在对应 template/；根目录不另设页面装配或辅助类文件。
 */
namespace app\Admin {
    /**
     * 功能说明：返回后台页面、模块、动作字段及菜单的受信白名单。
     * 参数说明：无显式参数。
     * @return array 页面路径到模块、模板及动作的定义；仅供 Admin 内部读取
     */
    function pages() {
        $required = ['type' => 'string', 'required' => true];
        $array = ['type' => 'array', 'default' => []];
        $action = function ($handler, $fields = []) {
            $mutex = in_array($handler, ['installPlugin', 'savePlugin', 'togglePlugin', 'uninstallPlugin', 'deletePlugin', 'orderPlugins'], true)
                ? 'plugins' : ($handler === 'saveSettings' ? 'settings' : (in_array($handler, ['uploadApp', 'uploadPlugin'], true) ? 'packages' : ''));
            return ['handler' => $handler, 'fields' => $fields, 'mutex' => $mutex];
        };
        return [
            'manager' => [
                'title' => '框架更新', 'module' => 'manager', 'view' => 'manager',
            ],
            '' => [
                'title' => '基本信息', 'group' => '信息', 'module' => 'info/main', 'view' => 'dashboard',
            ],
            'db' => [
                'title' => '数据库信息', 'group' => '信息', 'module' => 'info/db', 'view' => 'database',
            ],
            'php' => [
                'title' => 'PHP 信息', 'group' => '信息', 'module' => 'info/php', 'view' => 'php',
            ],
            'phpinfo' => [
                'title' => 'phpinfo()', 'group' => '信息', 'module' => 'info/phpinfo', 'view' => 'phpinfo',
            ],
            'error' => [
                'title' => '错误日志', 'group' => '信息', 'module' => 'info/error', 'view' => 'errors',
                'actions' => [
                    'clear' => $action('clearErrors'),
                    'download' => $action('downloadErrors'),
                ],
            ],
            'setting' => [
                'title' => '参数设置', 'group' => '设置', 'module' => 'setting/main', 'view' => 'settings',
                'actions' => [
                    'save' => $action('saveSettings', ['target' => 'string', 'version' => $required, 'setting' => $array]),
                ],
            ],
            'setting/api' => [
                'title' => 'API 参数设置', 'module' => 'setting/api', 'view' => 'apiSettings',
            ],
            'setting/class' => [
                'title' => '类加载设置', 'group' => '设置', 'module' => 'setting/class', 'view' => 'classes',
                'actions' => [
                    'save' => $action('saveClasses', ['rows' => $array, 'version' => $required]),
                ],
            ],
            'setting/domain' => [
                'title' => '域名绑定', 'group' => '设置', 'module' => 'setting/domain', 'view' => 'domains',
                'actions' => [
                    'save' => $action('saveDomains', ['rows' => $array, 'version' => $required]),
                ],
            ],
            'setting/alias' => [
                'title' => '类别名', 'module' => 'setting/alias', 'view' => 'aliases',
                'actions' => [
                    'save' => $action('saveAliases', ['aliases' => $array, 'version' => $required, 'directory' => ['type'=>'int','min'=>0]]),
                ],
            ],
            'setting/detail' => [
                'title' => '方法说明及别名', 'module' => 'setting/detail', 'view' => 'methods',
                'actions' => [
                    'save' => $action('saveMethods', ['class' => $required, 'aliases' => $array, 'version' => $required]),
                ],
            ],
            'setting/execute' => [
                'title' => '代码测试', 'group' => '设置', 'module' => 'setting/execute', 'view' => 'execute',
                'actions' => [
                    'run' => $action('runCode', ['code' => $required]),
                ],
            ],
            'function/app' => [
                'title' => '应用管理', 'group' => '功能', 'module' => 'function/app', 'view' => 'apps',
                'actions' => [
                    'save' => $action('saveApp', ['name' => $required, 'route' => 'string', 'plugins' => $array, 'version' => $required, 'plugin_version' => $required]),
                    'mapping' => $action('saveMapping', ['mapping' => $array, 'version' => $required]),
                    'upload' => $action('uploadApp'),
                ],
            ],
            'function/cache' => [
                'title' => '缓存管理', 'group' => '功能', 'module' => 'function/cache', 'view' => 'caches',
                'actions' => [
                    'clear' => $action('clearCaches', ['areas' => $array]),
                ],
            ],
            'function/plugin' => [
                'title' => '插件管理', 'group' => '功能', 'module' => 'function/plugin', 'view' => 'plugins',
                'actions' => [
                    'toggle' => $action('togglePlugin', ['idx' => $required]),
                    'uninstall' => $action('uninstallPlugin', ['idx' => $required]),
                    'delete' => $action('deletePlugin', ['idx' => $required]),
                    'pack' => $action('packPlugin', ['idx' => $required]),
                    'order' => $action('orderPlugins', ['order' => $array]),
                    'upload' => $action('uploadPlugin'),
                ],
            ],
            'function/api' => [
                'title' => 'API 管理', 'group' => '功能', 'module' => 'function/api', 'view' => 'apiEndpoints',
                'actions' => [
                    'save' => $action('saveApiEndpoint', ['scope'=>$required,'name'=>$required,'policy'=>$array,'version'=>$required]),
                    'reset' => $action('resetApiEndpoint', ['scope'=>$required,'name'=>$required,'version'=>$required]),
                ],
            ],
            'function/plugin/view' => [
                'title' => '插件安装', 'module' => 'function/plugin_setting', 'view' => 'pluginSettings',
                'actions' => [
                    'install' => $action('installPlugin', ['idx' => $required, 'setting' => $array, 'apps' => $array, 'version' => $required, 'registry_version' => $required]),
                ],
            ],
            'function/plugin/setting' => [
                'title' => '插件设置', 'module' => 'function/plugin_setting', 'view' => 'pluginSettings',
                'actions' => [
                    'save' => $action('savePlugin', ['idx' => $required, 'setting' => $array, 'apps' => $array, 'version' => $required, 'registry_version' => $required]),
                ],
            ],
            'function/attachment' => [
                'title' => '附件管理', 'group' => '功能', 'module' => 'function/attachment', 'view' => 'attachments',
                'actions' => [
                    'delete' => $action('deleteAttachment', ['idx' => $required, 'logged' => ['type' => 'int', 'choices' => [0, 1], 'required' => true]]),
                ],
            ],
            'function/language' => [
                'title' => '语言管理', 'group' => '功能', 'module' => 'function/language', 'view' => 'languages',
                'actions' => [
                    'save' => $action('saveLanguage', ['app' => $required, 'type' => $required, 'new' => 'string', 'language' => $array, 'version' => $required]),
                ],
            ],
            'guide' => [
                'title' => '框架指南', 'group' => '文档', 'module' => 'guide', 'view' => 'guide',
            ],
            'samples' => [
                'title' => '前端示例', 'group' => '文档', 'module' => 'samples', 'view' => 'samples',
            ],
            'references' => [
                'title' => '应用与类说明', 'group' => '文档', 'module' => 'references', 'view' => 'references',
                'menu_links' => [
                    ['title' => '应用示例', 'path' => 'Sample/'],
                    ['title' => '功能类说明', 'path' => 'Document/'],
                ],
            ],
        ];
    }
}
namespace {
/**
 * 辅助声明：AdminStore.class（位于 app/Admin/lib.php）
 * 功能说明：后台配置版本、乐观锁校验与原子保存。
 * 方法目录：
 *   public static AdminStore::version($file) — 读取文件内容版本摘要
 *   public static AdminStore::locked($file, callable $callback) — 在独立排他锁内运行受信回调
 *   public static AdminStore::read($file, $default = []) — 读取受信PHP数组配置
 *   public static AdminStore::save($file, $content, $version) — 在文件锁内比较版本并原子发布完整内容
 *   public static AdminStore::saveData($file, array $data, $version) — 验证普通数据并保存PHP配置
 *   public static AdminStore::identifier($value) — 校验应用、插件或类标识
 *   public static AdminStore::directory($root, $name) — 解析根目录内不存在符号链接的项目目录
 *
 * 使用说明：受信服务构造路径，GET记录version，POST携带原版本；不提供强制覆盖。
 */
/**
 * 功能说明：为受信配置提供内容版本比较、文件锁和原子保存。
 * 使用说明：参见文件顶部；只接受服务端构造的路径和定义。
 * 方法目录：参见文件顶部。
 */
class AdminStore {
    /**
     * 功能说明：读取文件内容版本摘要。
     * @param string $file 受信路径
     * @return string 内容摘要，不存在为missing
     */
    public static function version($file) {
        return is_file($file)?hash_file('sha256', $file):'missing';
    }
    /**
     * 功能说明：在独立排他锁内运行受信回调。
     * @param string $file 可信旁路锁路径
     * @param callable $callback 持锁回调
     * @return mixed 回调结果
     * @throws RuntimeException 加锁失败
     */
    public static function locked($file, callable $callback) {
        if(!myFile::mkdir(dirname($file)))throw new RuntimeException('无法创建锁目录。');
        $lock = fopen($file, 'c');
        if(!$lock)throw new RuntimeException('无法打开操作锁。');
        if(!flock($lock, LOCK_EX)) {
            fclose($lock);
            throw new RuntimeException('无法锁定操作。');
        }
        try {
            return $callback();
        }
        finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
    /**
     * 功能说明：读取受信PHP数组配置。
     * @param string $file 可信PHP配置
     * @param array $default 缺失默认值
     * @return array 数据
     * @throws UnexpectedValueException 根节点无效
     */
    public static function read($file, $default = []) {
        if(!is_file($file))return $default;
        if(function_exists('opcache_invalidate'))opcache_invalidate($file, true);
        $value = (static function($path) {
            return include $path;
        }
        )($file);
        if(!is_array($value))throw new UnexpectedValueException('配置文件根节点必须为数组。');
        return $value;
    }
    /**
     * 功能说明：在文件锁内比较版本并原子发布完整内容。
     * @param string $file 可信目标，不接受用户直接提交的路径
     * @param string $content 完整字节串
     * @param string $version 浏览时版本
     * @return bool 成功true
     * @throws RuntimeException 冲突（code=409）或I/O失败
     * @throws InvalidArgumentException 类型或PHP语法无效
     */
    public static function save($file, $content, $version) {
        if(!is_string($content) || !is_string($version))throw new InvalidArgumentException('缺少有效内容或版本。');
        if(is_link($file))throw new InvalidArgumentException('不能写入符号链接。');
        if(strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'php') {
            try {
                token_get_all($content, TOKEN_PARSE);
            }
            catch(ParseError $e) {
                throw new InvalidArgumentException('PHP语法错误：' . $e->getMessage());
            }
            if(!preg_match('/\A\s*<\?php\b/i', $content))throw new InvalidArgumentException('PHP文件必须从PHP标签开始。');
        }
        if(!myFile::mkdir(dirname($file)))throw new RuntimeException('无法创建配置目录。');
        $lock = fopen($file . '.lock', 'c');
        if(!$lock)throw new RuntimeException('无法锁定配置。');
        if(!flock($lock, LOCK_EX)) {
            fclose($lock);
            throw new RuntimeException('无法锁定配置。');
        }
        $tmp = '';
        try {
            clearstatcache(true, $file);
            if(!hash_equals(self::version($file), $version))throw new RuntimeException('保存冲突：文件已被修改，请重新加载。', 409);
            $tmp = $file . '.' . bin2hex(random_bytes(8)) . '.tmp';
            if(file_put_contents($tmp, $content, LOCK_EX) !== strlen($content))throw new RuntimeException('文件写入不完整。');
            if(is_file($file))chmod($tmp, fileperms($file)&0777);
            if(!rename($tmp, $file))throw new RuntimeException('无法发布配置。');
            if(function_exists('opcache_invalidate'))opcache_invalidate($file, true);
            return true;
        }
        finally {
            if($tmp !== '' && is_file($tmp))unlink($tmp);
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
    /**
     * 功能说明：验证普通数据并保存PHP配置。
     * @param string $file PHP配置
     * @param array $data 普通数据
     * @param string $version 原版本
     * @return bool 保存结果
     * @throws InvalidArgumentException 包含对象或资源
     */
    public static function saveData($file, array $data, $version) {
        $check = function($v)use(&$check) {
            if(is_object($v) || is_resource($v))throw new InvalidArgumentException('只允许普通配置数据。');
            if(is_array($v))foreach($v as $part)$check($part);
        }
        ;
        $check($data);
        return self::save($file, "<?php\nreturn " . var_export($data, true) . ";\n", $version);
    }
    /**
     * 功能说明：校验应用、插件或类标识。
     * @param mixed $value 应用/插件/类标识
     * @return string 合法标识
     * @throws InvalidArgumentException 非法标识
     */
    public static function identifier($value) {
        if(!is_string($value) || !preg_match('/\A[a-zA-Z][a-zA-Z0-9_]*\z/D', $value))throw new InvalidArgumentException('无效标识。');
        return $value;
    }
    /**
     * 功能说明：解析根目录内不存在符号链接的项目目录。
     * @param string $root 受信根
     * @param mixed $name 名称
     * @return string 安全目录
     * @throws InvalidArgumentException 不存在或含符号链接
     */
    public static function directory($root, $name) {
        $file = myFile::extractionPath($root, self::identifier($name));
        if(!$file || !is_dir($file))throw new InvalidArgumentException('目录不存在或不安全。');
        return $file . '/';
    }
}

/**
 * 辅助声明：AdminService.class（位于 app/Admin/lib.php）
 * 功能说明：按固定清单加载Admin功能模块，并提供跨模块复用的应用辅助方法。
 * 方法目录：__construct、supports、__call，以及下列私有辅助方法：
 *   catalog、languageTypes、pairs、configTarget、configFields、passwords、classesList、registry、installed、plugin、bindPlugin、writePlugin、uploadPackage、directories.
 * @method string dashboard() 对应module中的已登记功能回调
 * @method string manager() 使用当前应用模板展示已启用manager插件的功能主体
 * @method string database() 对应module中的已登记功能回调
 * @method string php() 对应module中的已登记功能回调
 * @method string phpinfo() 对应module中的已登记功能回调
 * @method string errors() 对应module中的已登记功能回调
 * @method array clearErrors($p) 对应module中的已登记功能回调
 * @method array downloadErrors($p) 对应module中的已登记功能回调
 * @method string apiSettings() 兼容旧入口并跳转统一参数设置
 * @method string apiEndpoints() API接口功能目录
 * @method array saveApiEndpoint($p) 保存API接口策略
 * @method array resetApiEndpoint($p) 恢复API接口默认策略
 * @method string settings() 对应module中的已登记功能回调
 * @method array saveSettings($p) 对应module中的已登记功能回调
 * @method string classes() 对应module中的已登记功能回调
 * @method array saveClasses($p) 对应module中的已登记功能回调
 * @method string domains() 对应module中的已登记功能回调
 * @method array saveDomains($p) 对应module中的已登记功能回调
 * @method string aliases() 对应module中的已登记功能回调
 * @method array saveAliases($p) 对应module中的已登记功能回调
 * @method string methods() 对应module中的已登记功能回调
 * @method array saveMethods($p) 对应module中的已登记功能回调
 * @method string execute() 对应module中的已登记功能回调
 * @method array runCode($p) 对应module中的已登记功能回调
 * @method string apps() 对应module中的已登记功能回调
 * @method array saveApp($p) 对应module中的已登记功能回调
 * @method array saveMapping($p) 对应module中的已登记功能回调
 * @method string caches() 对应module中的已登记功能回调
 * @method array clearCaches($p) 对应module中的已登记功能回调
 * @method string plugins() 对应module中的已登记功能回调
 * @method string pluginSettings() 对应module中的已登记功能回调
 * @method array installPlugin($p) 对应module中的已登记功能回调
 * @method array savePlugin($p) 对应module中的已登记功能回调
 * @method array togglePlugin($p) 对应module中的已登记功能回调
 * @method array uninstallPlugin($p) 对应module中的已登记功能回调
 * @method array deletePlugin($p) 对应module中的已登记功能回调
 * @method array orderPlugins($p) 对应module中的已登记功能回调
 * @method array packPlugin($p) 对应module中的已登记功能回调
 * @method array uploadApp($p) 对应module中的已登记功能回调
 * @method array uploadPlugin($p) 对应module中的已登记功能回调
 * @method string attachments() 对应module中的已登记功能回调
 * @method array deleteAttachment($p) 对应module中的已登记功能回调
 * @method string languages() 对应module中的已登记功能回调
 * @method array saveLanguage($p) 对应module中的已登记功能回调
 * @method string guide() 对应module中的已登记功能回调
 * @method string samples() 对应module中的已登记功能回调
 * @method string references() 对应module中的已登记功能回调
 * 使用说明：Admin创建服务；页面及动作实现位于module/，由lib.php的pages()白名单登记。未登记的方法拒绝调用。
 * 功能回调的参数、返回和对应模板说明见各模块文件；仅在需要时加载该模块。
 */
class AdminService {
    /** @var Admin 当前应用核心实例 */
    private $ui;
    /** @var array<string,string> 已登记回调名到应用模块相对路径 */
    private $handlers = [];
    /** @var array<string,array<string,Closure>> 当前请求已加载的模块回调 */
    private $modules = [];
    /**
     * 功能说明：记录控制器并从受信页面定义建立模块回调白名单。
     * @param Admin $ui 当前应用核心实例
     * @return void 初始化服务，不执行页面或动作
     * @throws LogicException 声明缺少模块或同名回调指向不同模块
     */
    public function __construct(Admin $ui) {
        $this->ui = $ui;
        foreach(\app\Admin\pages() as $entry) {
            $module = $entry['module']??'';
            if(!is_string($module) || !preg_match('#\A[a-zA-Z0-9_]+(?:/[a-zA-Z0-9_]+)*\z#D', $module))throw new LogicException('后台模块声明无效。');
            $names = [$entry['view']];
            foreach($entry['actions']??[] as $action)$names[] = $action['handler'];
            foreach($names as $name) {
                if(isset($this->handlers[$name]) && $this->handlers[$name] !== $module)throw new LogicException('后台回调重复登记。');
                $this->handlers[$name] = $module;
            }
        }
    }
    /**
     * 功能说明：检查回调是否存在于页面和动作白名单。
     * @param string $name 回调名
     * @return bool 是否已登记
     */
    public function supports($name) {
        return is_string($name) && isset($this->handlers[$name]);
    }
    /**
     * 功能说明：按需加载受信功能模块并执行已登记的页面或动作回调。
     * @param string $name 受信回调名，请求参数不能直接指定
     * @param array $arguments 视图无参数；动作传入已校验字段数组
     * @return mixed 对应模块定义的HTML或动作结果
     * @throws BadMethodCallException 回调未登记
     * @throws LogicException 模块缺失或没有提供声明的回调
     */
    public function __call($name, $arguments) {
        if(!$this->supports($name))throw new BadMethodCallException('后台回调未登记。');
        $module = $this->handlers[$name];
        if(!isset($this->modules[$module])) {
            $file = myFile::resolveWithin(__DIR__.'/module/', $module.'.php');
            if(!$file)throw new LogicException('后台模块不存在。');
            $callbacks = include $file;
            if(!is_array($callbacks))throw new LogicException('后台模块必须返回回调数组。');
            $this->modules[$module] = $callbacks;
        }
        $callback = $this->modules[$module][$name]??null;
        if(!($callback instanceof Closure))throw new LogicException('后台模块未提供声明的回调。');
        return call_user_func_array($callback, $arguments);
    }
    /**
     * 功能说明：只读取安全项目目录的元信息，不加载业务实现。
     * @param string $root 应用或插件根
     * @return array 标识到元信息，不加载业务类
     */
    private function catalog($root) {
        $out = [];
        foreach(glob($root . '*', GLOB_ONLYDIR)?:[] as $dir) {
            $id = basename($dir);
            if(is_link($dir) || !preg_match('/\A[a-zA-Z][a-zA-Z0-9_]*\z/D', $id))continue;
            $info = AdminStore::read($dir . '/info.php');
            $out[$id] = $info+['app' => $id, 'idx' => $id, 'name' => $id, 'ver' => '', 'intro' => is_file($dir . '/info.php')?'':'信息文件缺失'];
        }
        return $out;
    }
    /**
     * 功能说明：枚举应用中可管理的语言文件，供应用入口和语言编辑使用同一清单。
     * @param string $app 应用目录标识
     * @return array 语言标识到文件路径；目录不存在、为空或为符号链接时返回空数组
     * @throws InvalidArgumentException 应用标识或目录无效
     */
    private function languageTypes($app) {
        $dir = AdminStore::directory(APP, $app).'language/';
        $types = [];
        if(!is_dir($dir) || is_link(rtrim($dir, '/')))return $types;
        foreach(glob($dir.'*.php')?:[] as $file) {
            $type = basename($file, '.php');
            if(is_file($file) && !is_link($file) && preg_match('/\A[a-zA-Z][a-zA-Z0-9_]*\z/D', $type))$types[$type] = $file;
        }
        return $types;
    }
    /**
     * 功能说明：整理名称和值为信息表循环数据。
     * @param array $data 名称到值
     * @return array 信息表循环记录
     */
    private function pairs(array $data) {
        $rows = [];
        foreach($data as $key => $value) $rows[] = ['name' => $key, 'value' => is_scalar($value)?(string)$value:json_encode($value, JSON_UNESCAPED_UNICODE)];
        return $rows;
    }
    /**
     * 功能说明：将配置目标映射为受信配置文件和结构文件。
     * @param string $target 空串表示全局配置，否则为应用
     * @return array [配置路径,结构路径]
     */
    private function configTarget($target) {
        $lang = $this->ui->setting->gen->language;
        if($target === '')return [CONFIG . 'config.php', CONFIG . 'construction/' . $lang . '.php'];
        $dir = AdminStore::directory(APP, $target);
        $builder = $dir . 'config/' . $lang . '.php';
        if(!is_file($builder))$builder = $dir . 'config/default.php';
        if(!is_file($dir . 'config.php') || !is_file($builder))throw new InvalidArgumentException('该应用未提供可编辑配置。');
        return [$dir . 'config.php', $builder];
    }
    /**
     * 功能说明：根据myConfig结构生成带斑马纹行和层级标题的配置字段组，保留原字段顺序及控件。
     * @param myConfig $config 配置对象
     * @param string $builder 结构文件
     * @return string 统一字段组，复用已有myConfig结构
     */
    private function configFields(myConfig $config, $builder) {
        $apps = array_keys($this->catalog(APP));
        $list = $config->build($builder, '', ['gen' => ['language' => ['select', ['default','chs','en']]], 'router' => ['default_app' => ['select', $apps]]]);
        $groups = $titles = $definitions = [];
        foreach($list as $field) {
            if(isset($field['idx'])) {
                // myConfig返回顺序字段；保留原顺序，并通过索引前缀显示子设置所属分组。
                $titles[$field['idx']] = $field['name'];
                $parents = [];
                $parts = explode('.', $field['idx']);
                array_pop($parts);
                while($parts) {
                    $parent = implode('.', $parts);
                    if(isset($titles[$parent]))array_unshift($parents, $titles[$parent]);
                    array_pop($parts);
                }
                $definitions[$field['idx']] = ['idx' => $field['idx'], 'name' => $field['name'], 'parent' => implode(' / ', $parents), 'rows' => []];
                $groups[] = $definitions[$field['idx']];
                continue;
            }
            // 子组之后仍可声明父组字段，按生成控件的标准name恢复所属组，避免误归入最后一个子组。
            if(preg_match('/\bname="setting((?:\[[^\]]*\])+?)"/', $field['html']??'', $match)) {
                preg_match_all('/\[([^\]]+)\]/', $match[1], $keys);
                array_pop($keys[1]);
                $owner = implode('.', $keys[1]);
                if(isset($definitions[$owner]) && (!$groups || $groups[count($groups)-1]['idx'] !== $owner))$groups[] = $definitions[$owner];
            }
            if(!$groups)$groups[] = ['idx' => '', 'name' => '设置项', 'parent' => '', 'rows' => []];
            $groups[count($groups)-1]['rows'][] = ['content' => $this->ui->view('config_item', ['name' => $field['name'], 'control' => $field['html']??'', 'description' => $field['describe']??''])];
        }
        $fields = [];
        foreach($groups as $group)$fields[] = ['content' => $this->ui->view('config_section', ['name' => $group['name'], 'parent' => $group['parent'], 'section_id'=>$group['idx']], ['fields' => $group['rows']], ['nested' => $group['parent'] !== '', 'has_fields' => (bool)$group['rows']])];
        return $this->ui->view('config_fields', [], ['fields' => $fields]);
    }
    /**
     * 功能说明：递归验证配置表单中的密码确认字段。
     * @param array $values 配置表单
     * @return void 递归验证password确认字段
     * @throws InvalidArgumentException 确认不匹配
     */
    private function passwords(array $values) {
        foreach($values as $key => $value) {
            if(is_array($value)) {
                $this->passwords($value);
                continue;
            }
            if(is_string($key) && substr($key, -6) === '_pwd_r') {
                $original = substr($key, 0, -6);
                if(($values[$original]??'') !== $value)throw new InvalidArgumentException('两次输入的密码不一致。');
            }
        }
    }
    /**
     * 功能说明：收集可供查看和配置别名的已登记类白名单。
     * @param int|string|null $directory 类目录数字索引；null枚举全部登记目录及特别指定
     * @return array 可供别名及方法说明的已登记类白名单
     */
    private function classesList($directory = null) {
        $rules = AdminStore::read(CONFIG.'class.php');
        if($directory !== null) {
            if(filter_var($directory, FILTER_VALIDATE_INT) === false || !isset($rules[(int)$directory])) throw new InvalidArgumentException('未知类目录。');
            $rules = [$rules[(int)$directory]];
        }
        $out = [];
        foreach($rules as $rule) {
            $names = array_keys($rule['idx']??[]);
            foreach(explode(',', $rule['ext']) as $ext) foreach(glob(rtrim(myPath::absolute($rule['path']), '/').'/*'.$ext) as $file) $names[] = substr(basename($file), 0, -strlen($ext));
            foreach(array_unique($names) as $name) if(preg_match('/\A[a-zA-Z_][a-zA-Z0-9_\\\\]*\z/D', $name) && class_exists($name)) $out[$name] = $name;
        }
        ksort($out);
        return $out;
    }
    /**
     * 功能说明：按需打开或创建共享simpleDB插件注册表。
     * @param bool $create 是否需要创建注册表
     * @return myDb|null simpleDB代理或缺失
     */
    private function registry($create = false) {
        if(!$create && !is_file(PLUGIN . 'plugin.db'))return null;
        $db = new myDb('simpleDB', 'plugin', PLUGIN);
        if(!$db->check()) {
            if(!$create)return null;
            if(!$db->create([['order', 2], ['active', 1], ['idx', 40], ['ver', 20], ['name', 100], ['intro', 1000]]))throw new RuntimeException('无法建立插件注册表。');
        }
        return $db;
    }
    /**
     * 功能说明：读取并按插件标识索引已安装记录。
     * 参数说明：无显式参数。
     * @return array idx到已安装记录
     */
    private function installed() {
        $db = $this->registry();
        if(!$db)return [];
        $rows = $db->records();
        $db->close();
        $out = [];
        foreach($rows as $row)$out[trim($row['idx'])] = $row;
        return $out;
    }
    /**
     * 功能说明：定位安全插件目录并检查插件接口实现。
     * @param string $id 插件名
     * @return array [目录,类名,元信息]
     * @throws InvalidArgumentException 插件不符合接口
     */
    private function plugin($id) {
        $dir = AdminStore::directory(PLUGIN, $id);
        require_once PLUGIN . 'interface_plugin.class.php';
        $class = 'plugin_' . $id;
        if(!class_exists($class, false) && is_file($dir . 'class.php'))require_once $dir . 'class.php';
        if(!class_exists($class) || !in_array('interface_plugin', class_implements($class), true))throw new InvalidArgumentException('插件没有实现框架接口。');
        return [$dir, $class, AdminStore::read($dir . 'info.php')];
    }
    /**
     * 功能说明：委托插件公共绑定接口，在有序文件锁内同步生效列表并保留强制绑定。
     * @param string $id 插件
     * @param array $apps 已提交应用
     * @param array $mandatory 必需应用
     * @return void 更新绑定列表
     */
    private function bindPlugin($id, array $apps, array $mandatory = []) {
        require_once PLUGIN.'interface_plugin.class.php';
        setPluginApps($id,$apps,$mandatory);
    }
    /**
     * 功能说明：检查注册表版本和安装条件，保存配置并执行插件安装或设置。
     * @param array $p idx/setting/apps/version/registry_version
     * @param bool $install 是否安装
     * @return array 操作反馈
     */
    private function writePlugin($p, $install) {
        $id = $p['idx'];
        list($dir, $class, $info) = $this->plugin($id);
        $records = $this->installed();
        if($install === isset($records[$id]))throw new InvalidArgumentException($install?'插件已安装。':'插件尚未安装。');
        if(AdminStore::version(PLUGIN . 'plugin.db') !== $p['registry_version'])throw new RuntimeException('插件清单已改变，请刷新。', 409);
        $apps = $this->catalog(APP);
        foreach(array_merge($p['apps'],is_array($info['app']??null)?$info['app']:[]) as $app)if(!is_string($app) || !isset($apps[$app]))throw new InvalidArgumentException('生效应用无效。');
        if($install && !$class::check($reason))throw new InvalidArgumentException('安装条件未通过：' . strip_tags($reason));
        $file = $dir . 'config.php';
        if(is_file($file)) {
            $config = new myConfig($file);
            $this->passwords($p['setting']);
            $config->set($p['setting']);
            AdminStore::saveData($file, $config->get(true), $p['version']);
        }
        if($install) {
            $class::install();
            $db = $this->registry(true);
            $db->insert(['order' => 1, 'active' => 1, 'idx' => $id, 'ver' => $info['ver']??'', 'name' => $info['name']??$id, 'intro' => $info['intro']??''], true);
            $db->close();
        }
        $this->bindPlugin($id, $p['apps'], is_array($info['app']??null)?$info['app']:[]);
        return $this->ui->success($install?'插件已安装。':'插件设置已保存。', 'function/plugin');
    }
    /**
     * 功能说明：暂存PHP上传文件，预检解包并发布不存在的项目目录。
     * @param string $root APP或PLUGIN
     * @param string $prefix 包名称前缀
     * @return array 上传结果；先解到临时目录再发布
     */
    private function uploadPackage($root, $prefix) {
        $file = $_FILES['package']??null;
        if(!is_array($file) || ($file['error']??UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name']??''))throw new InvalidArgumentException('请选择有效上传文件。');
        if(!preg_match('/\A' . preg_quote($prefix, '/') . '_([a-zA-Z][a-zA-Z0-9_]*)\.' . $prefix . '\z/D', $file['name'], $match))throw new InvalidArgumentException('包名称格式不正确。');
        $id = $match[1];
        if(file_exists($root . $id) || is_link($root . $id))throw new InvalidArgumentException('目标项目已经存在。');
        $work = CACHE . 'tmp/admin-upload-' . bin2hex(random_bytes(8));
        myFile::mkdir($work);
        register_shutdown_function(function()use($work) {
            myFile::del($work);
        }
        );
        $destination = $work . '/' . $id;
        // myPacker按站点根规范路径，先将PHP上传临时文件移入站点内受控工作目录。
        $archive = $work . '/package.upload';
        if(!move_uploaded_file($file['tmp_name'], $archive))throw new RuntimeException('无法暂存上传包。');
        $packer = new myPacker($destination, $archive);
        if(!$packer->unpack(false) || !is_file($destination . '/info.php'))throw new InvalidArgumentException('包内容无效或缺少info.php。');
        if(is_file($destination . '/config_new.php'))rename($destination . '/config_new.php', $destination . '/config.php');
        if(!rename($destination, $root . $id))throw new RuntimeException('无法发布项目目录。');
        return $this->ui->success('项目已部署，应用路由下次请求自动发现。');
    }
    /**
     * 功能说明：枚举符合规则且不是符号链接的子目录。
     * @param string $directory 目录
     * @param string $pattern 子目录规则
     * @return array 倒序安全子目录
     */
    private function directories($directory, $pattern) {
        $out = [];
        if(!is_dir($directory) || is_link($directory))return $out;
        foreach(new DirectoryIterator($directory) as $entry)if(!$entry->isDot() && !$entry->isLink() && $entry->isDir() && preg_match($pattern, $entry->getFilename()))$out[] = $entry->getFilename();
        rsort($out);
        return $out;
    }
}

}
