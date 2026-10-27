<?PHP
/********************************************
*                                           *
* Name    : My Template                     *
* Author  : Windy2000                       *
* Time    : 2010-12-12                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT HOLD THIS ITEM PLEASE.      *
*                                           *
********************************************/
/**
 * 文件：lib/myTemplate.class.php
 * 功能说明：模板读取、编译、渲染与缓存引擎
 *   解析变量、循环、条件、switch、子模板及自定义标签，管理依赖和页面缓存。
 *
 * 方法目录：
 *   public myTemplate::init($setting, $cache = false, $allow_script = false, $delimiter = null) — 初始化模板、静态页面缓存和标签分隔符
 *   protected myTemplate::relativeName($value, $empty = false) — 校验模板名称或风格子目录，禁止绝对路径、包装器和上级目录
 *   public myTemplate::setTplPara($setting) — 设置模板位置并建立当前对象的数据空间，同一对象切换模板会清空赋值
 *   public myTemplate::__destruct() — 对象释放时删除所属变量空间，避免长驻进程累积模板数据
 *   public myTemplate::__clone() — 克隆时复制变量空间，后续赋值彼此独立
 *   public myTemplate::__serialize() — 序列化时携带当前对象的数据，避免恢复对象借用原实例静态空间
 *   public myTemplate::__unserialize(array $data) — 恢复时建立新变量空间，不重新运行 init 或清除原对象的赋值
 *   public myTemplate::setCacheMode($cache) — 启用或关闭页面缓存；登录态/语言等差异必须由调用方通过 vary 或独立路径区分
 *   public myTemplate::getTemplate($file1, &$file2 = '') — 获取模板或默认风格模板，并登记编译依赖（含不存在的候选路径）
 *   public myTemplate::setLoop($key, $value, $fullset = false) — 设置循环记录，支持数组或可遍历对象
 *   public myTemplate::setIf($key, $value = true) — 设置条件值，缺省条件为 false
 *   public myTemplate::setSwitch($key, $value) — 设置分支值，未赋值时按 null 处理
 *   public myTemplate::assign($name, $value = '') — 赋值普通变量，数组/对象展开一层为 name_key；旧占位符仍原样输出
 *   public myTemplate::assignEscaped($name, $value = '') — 将变量按 HTML 文本/引号属性上下文转义后赋值；不要再叠加 |html
 *   public myTemplate::loadSet($file, $prefix = '', $mode = false) — 加载可信 PHP 返回数组、INI 或 JSON 配置，支持重复调用
 *   protected myTemplate::attributes($text) — 解析引号属性，完整保留等号、反斜杠、空串及相反种类的引号
 *   protected myTemplate::parseBlock($attrib, $content, &$block_attrib, &$block_content) — 解析旧扩展接口使用的块属性，正文不再 stripslashes
 *   public myTemplate::regTag($tag_name, $tag_func = '') — 登记可信的编译期标签回调，扩展结果可包含 PHP
 *   protected myTemplate::identity() — 计算基础编译身份，模板值不写入编译文件
 *   protected myTemplate::tagIdentity() — 计算已登记标签的实现身份；闭包和对象回调按模板实例隔离；具名回调的外部状态变化请更新 compile_version
 *   protected myTemplate::dependenciesValid(array $files) — 校验元数据中所有依赖的内容摘要，同时检查原先不存在的模板是否已出现
 *   protected myTemplate::metadata($file) — 读取缓存元数据，损坏文件作为未命中处理
 *   protected myTemplate::atomicWrite($file, $content) — 在目标目录写临时文件后原子替换，避免读到半个 PHP/HTML 文件
 *   protected myTemplate::compiledFile() — 取得旧接口使用的编译入口位置，风格目录总是正确连接
 *   protected myTemplate::protectPHP($text, $keep) — 用 PHP 词法器隔离脚本，防止模板正文被编译器当成 PHP 字符串插值
 *   protected myTemplate::literalCode($text) — 将正文编译为安全的字面量输出，内部脚本标记恢复为可信代码
 *   protected myTemplate::templateTokens($text) — 将模板分隔标签转为词元，PHP 部分已经隔离
 *   protected myTemplate::compileSequence(array $tokens, &$i, array $stops = [], $depth = 0) — 递归编译标准块，嵌套块和循环使用独立局部变量
 *   protected myTemplate::displayValue($value) — 将占位值转为可显示字符串；不存在/不可显示对象返回空串
 *   protected myTemplate::templateValue($name, array $data, array $rows, $filter = '') — 读取最内层匹配的循环字段或普通变量，缺失字段安静输出空串
 *   protected myTemplate::compileTemplate() — 按内容和编译选项复用缓存，生成不可变版本文件供当前渲染使用
 *   public myTemplate::removeCache($html = false) — 清除当前模板编译版本，可选择同时删除当前页面缓存
 *   public myTemplate::checkCache() — 检测静态页面是否有效，核对引擎身份、依赖、页面摘要及实际当前时间
 *   public myTemplate::getCache() — 读取有效的静态页面
 *   public myTemplate::render($global_vars = '', $tpl_show = true, $minify = false) — 获取最终 HTML；独立缓冲区保证异常时不清空调用者已有内容
 *   protected myTemplate::minifyHTML($content) — 保守压缩 HTML，去除普通标签行首缩进；特殊内容整段保护
 *   protected myTemplate::tagPlaceholders($result, array &$attrs) — 在自定义标签返回文本中按 PHP 词法上下文输出属性占位符
 *   protected myTemplate::parseTag($content) — 编译自定义标签，保留既有 PHP 代码生成及 split 标记约定
 *   public myTemplate::getBlock($tpl_content, $tag = '', $idx = '') — 提取首个完整块，兼容可信扩展生成 PHP 时使用的历史返回格式
 *
 * 使用说明：
 *   1. 传模板路径/名称/编译目录初始化，通过assign/setLoop填充再render。
 *   2. 模板及标签回调是受信程序资源；不把用户任意文本作为可执行模板。
 *   3. 继承、编译指纹、页面缓存及旧标签兼容规则见docs/framework/templates.md。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名以PHP 8.5.12为运行基线，PHPDoc类型用于说明，不额外强制运行时类型。
 */


/**
模版解析:
    $tpl->init($setting, $cache, $allow_script)                 // Set the Template Class
    $tpl->setTplPara($setting)                                  // Set the parameter of templatee
    $tpl->setCacheMode($cache)                                  // Set the mode of template cache
    $tpl->getTemplate($file1, $file2)                           // Get template content
    $tpl->setLoop($key, $value, $fullset)                       // Set values for loop-blocks (in turn or batch)
    $tpl->setIf($key, $value)                                   // Set the judging conditions for if-blocks
    $tpl->setSwitch($key, $value)                               // Set the switch conditions for switch-blocks
    $tpl->assign($name, $value)                                 // Set value for one variable
    $tpl->loadSet($file, $prefix)                               // Read Batch setting file
    $tpl->removeCache()                                         // Remove template complied cache
    $tpl->regTag($tag_name, $tag_func)                          // Regist tag-parser for the custom tags
    $tpl->getBlock($tpl_content, $tag, $idx)                    // Analysis a custom tag
    $tpl->checkCache()                                          // Check if the current template with the define idx has been cached as a static html file
    $tpl->getCache()                                            // Directly get file content from cached html file
    $tpl->render($global_vars)                                  // Get result content compile by the template engine ('$global_vars' ara the parameters needed in php cache file)
*/
/**
 * 功能说明：解析变量、循环、条件、switch、子模板及自定义标签，管理依赖和页面缓存。
 *
 * 使用说明：
 *   1. 传模板路径/名称/编译目录初始化，通过assign/setLoop填充再render。
 *   2. 模板及标签回调是受信程序资源；不把用户任意文本作为可执行模板。
 *   3. 继承、编译指纹、页面缓存及旧标签兼容规则见docs/framework/templates.md。
 *
 * 补充说明：
 * 前台模板引擎：独立变量空间、嵌套块、可信标签扩展及按内容校验的编译/页面缓存。
 * 普通占位符兼容原样输出；<!--name|html--> 用于 HTML 文本或引号属性，
 * <!--name|url--> 用于 URL 组件。模板、标签回调及编译目录必须由可信应用控制。
 * 页面缓存不会自动区分用户，请使用 cache.vary 或按用户/语言分目录。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 *
 * @phpstan-type TemplateSettings array{name?:string,style?:string,path?:string,path_compile?:string,ext?:string,compile_version?:string}
 * @phpstan-type PageCacheSettings array{name?:string,path?:string,ext?:string,expire?:int,vary?:mixed}
 * @example ../app/Document/module/myTemplate.php 框架现有演示，仅作为文档引用，不在检查时执行
 */
class myTemplate extends myBase {
    use myTrait;
    /** @var array 模板对象拥有独立变量空间；保留此表以兼容旧扩展读取 */
    public static $tpl_para = [];
    /** @var bool 是否允许可信模板中的 PHP，不能用作不可信模板的沙箱 */
    public $allow_script = false;
    protected $delimiter_l = '<!--', $delimiter_r = '-->', $hash = '';
    protected $setting = [], $tags = [], $dependencies = [], $tag_dependencies = [], $php_parts = [], $sequence = 0;
    protected $cache = ['use'=>false, 'file'=>'', 'expire'=>300];

    /**
     * 功能说明：初始化模板、静态页面缓存和标签分隔符
     *
     *
     * 调用示例：
     *     $tpl = new myTemplate(['path' => $views, 'path_compile' => $compiled, 'name' => 'home']);
     *     $tpl->assign('title', '示例'); $html = $tpl->render('', false);
     *
     * @param array $setting name/style/path/path_compile/ext；compile_version 可显式更新扩展编译版本
     * @param bool|array|null $cache false/null关闭，true或数组启用；选项含name/path/ext/expire/vary，expire单位秒（默认：false）
     * @param bool $allow_script 仅对可信模板开启，注册标签回调始终属于可信 PHP 扩展（默认：false）
     * @param array|null $delimiter 两个非空字符串，默认 HTML 注释分隔符（默认：null）
     * @return void 无返回值。
     * @throws InvalidArgumentException 参数或相对模板名称不合法
     * @phpstan-param TemplateSettings $setting
     * @phpstan-param bool|PageCacheSettings|null $cache
     * @phpstan-param array{string,string}|null $delimiter
     */
    public function init($setting, $cache = false, $allow_script = false, $delimiter = null) {
        if($delimiter !== null) {
            if(!is_array($delimiter) || count($delimiter)!==2) throw new InvalidArgumentException('Two template delimiters are required');
            $delimiter = array_values($delimiter);
            foreach($delimiter as $v) if(!is_string($v) || $v === '' || strpos($v, "\0") !== false) throw new InvalidArgumentException('Invalid template delimiter');
            list($this->delimiter_l, $this->delimiter_r) = $delimiter;
        }
        $this->allow_script = (bool)$allow_script;
        $this->setTplPara($setting);
        $this->setCacheMode($cache);
    }

    /**
     * 功能说明：校验模板名称或风格子目录，禁止绝对路径、包装器和上级目录
     *
     * @param string $value 相对路径，允许 a/b 形式
     * @param bool $empty 是否允许空值（默认：false）
     * @return string 去掉末尾目录分隔符后的相对路径
     * @throws InvalidArgumentException 路径非法
     */
    protected function relativeName($value, $empty = false) {
        if(!is_string($value) || strpos($value, "\0")!==false || strpos($value, '\\')!==false || preg_match('/[\x00-\x1f\x7f*?\[\]{}]/', $value) || strpos($value, ':')!==false || substr($value, 0, 1)==='/') throw new InvalidArgumentException('Invalid template name');
        $value = rtrim($value, '/');
        if($value === '' && $empty) return '';
        if($value === '' || preg_match('#(^|/)(?:\.|\.\.)(/|$)|//#', $value)) throw new InvalidArgumentException('Invalid template name');
        return $value;
    }

    /**
     * 功能说明：设置模板位置并建立当前对象的数据空间，同一对象切换模板会清空赋值
     *
     *
     * 设置结构：name默认main，style默认空串，path默认./，path_compile默认./complied（沿用历史拼写），ext默认tpl，compile_version默认空串；路径名必须来自受信配置。
     *
     * @param array $setting name 默认 main，ext 默认 tpl；path_compile 默认 ./complied
     * @return $this 返回当前实例，支持链式调用。
     * @throws InvalidArgumentException 配置非法
     * @phpstan-param TemplateSettings $setting
     */
    public function setTplPara($setting) {
        if(!is_array($setting)) throw new InvalidArgumentException('Template settings must be an array');
        $setting += ['name'=>'main','style'=>'','path'=>'./','path_compile'=>'./complied','ext'=>'tpl','compile_version'=>''];
        $setting['name'] = $this->relativeName($setting['name']);
        $setting['style'] = $this->relativeName($setting['style'], true);
        if(!is_string($setting['ext']) || !preg_match('/^\w+$/D', $setting['ext'])) throw new InvalidArgumentException('Invalid template extension');
        foreach(['path','path_compile'] as $key) {
            if(!is_string($setting[$key]) || $setting[$key] === '' || strpos($setting[$key], "\0")!==false || strpos($setting[$key], '://')!==false) throw new InvalidArgumentException('Invalid template directory');
            $setting[$key] = rtrim(myFile::realPath($setting[$key]), '/');
        }
        if($this->hash !== '') unset(self::$tpl_para[$this->hash]);
        $this->hash = 't'.bin2hex(random_bytes(16));
        self::$tpl_para[$this->hash] = ['para'=>[], 'loop'=>[], 'if'=>[], 'switch'=>[]];
        $this->dependencies = [];
        $this->setting = $setting;
        $this->setting['requested'] = $setting['path'].'/'.($setting['style'] === '' ? '' : $setting['style'].'/').$setting['name'].'.'.$setting['ext'];
        $this->setting['file'] = $this->setting['requested'];
        $this->setting['content'] = $this->getTemplate($this->setting['requested'], $this->setting['file']);
        return $this;
    }

    /**
     * 功能说明：对象释放时删除所属变量空间，避免长驻进程累积模板数据
     *
     * 参数说明：无显式参数。
     *
     * @return void 初始化或释放资源，无返回值。
     */
    public function __destruct() {
        unset(self::$tpl_para[$this->hash]);
        $this->methods = []; $this->vars = [];
    }

    /**
     * 功能说明：克隆时复制变量空间，后续赋值彼此独立
     *
     * 参数说明：无显式参数。
     *
     * @return void 无返回值。
     */
    public function __clone() {
        $data = self::$tpl_para[$this->hash] ?? ['para'=>[], 'loop'=>[], 'if'=>[], 'switch'=>[]];
        $this->hash = 't'.bin2hex(random_bytes(16)); self::$tpl_para[$this->hash] = $data;
    }

    /**
     * 功能说明：序列化时携带当前对象的数据，避免恢复对象借用原实例静态空间
     *
     * 参数说明：无显式参数。
     *
     * @return array 包含子类私有属性的实例状态和模板数据；闭包/资源限制仍遵循 PHP 原生行为
     */
    public function __serialize() {
        return ['state'=>(array)$this, 'data'=>self::$tpl_para[$this->hash] ?? ['para'=>[], 'loop'=>[], 'if'=>[], 'switch'=>[]]];
    }

    /**
     * 功能说明：恢复时建立新变量空间，不重新运行 init 或清除原对象的赋值
     *
     * @param array $data __serialize 返回的可信对象状态
     * @return void 无返回值。
     */
    public function __unserialize(array $data) {
        $class = new ReflectionObject($this);
        do {
            foreach($class->getProperties() as $property) {
                if($property->isStatic()) continue;
                $name = $property->getName();
                $key = $property->isPrivate() ? "\0".$property->getDeclaringClass()->getName()."\0".$name : ($property->isProtected() ? "\0*\0".$name : $name);
                if(!array_key_exists($key, $data['state'] ?? [])) continue;
                $property->setValue($this, $data['state'][$key]);
            }
        } while($class = $class->getParentClass());
        $this->hash = 't'.bin2hex(random_bytes(16));
        self::$tpl_para[$this->hash] = $data['data'] ?? ['para'=>[], 'loop'=>[], 'if'=>[], 'switch'=>[]];
    }

    /**
     * 功能说明：启用或关闭页面缓存；登录态/语言等差异必须由调用方通过 vary 或独立路径区分
     *
     *
     * 缓存结构：name默认当前模板名，path默认模板目录/cache/风格，ext默认html，expire默认300秒；expire非正不复用页面，vary用于区分用户/语言等上下文。
     *
     * @param bool|array|null $cache false/null关闭页面缓存，true使用默认选项；数组可设置name/path/ext/expire/vary
     * @return $this 返回当前实例，支持链式调用。
     * @throws InvalidArgumentException 参数或扩展名非法
     * @phpstan-param bool|PageCacheSettings|null $cache
     */
    public function setCacheMode($cache) {
        if($cache === false || $cache === null) { $this->cache['use'] = false; return $this; }
        if($cache === true) $cache = [];
        if(!is_array($cache)) throw new InvalidArgumentException('Template cache settings must be an array or bool');
        $cache += ['name'=>$this->setting['name'], 'path'=>$this->setting['path'].'/cache/'.$this->setting['style'], 'ext'=>'html', 'expire'=>300];
        if(empty($cache['name'])) $cache['name'] = 'index';
        if(!is_string($cache['ext']) || !preg_match('/^\w+$/D', $cache['ext'])) throw new InvalidArgumentException('Invalid cache extension');
        if(!is_string($cache['path']) || strpos($cache['path'], "\0")!==false || strpos($cache['path'], '://')!==false) throw new InvalidArgumentException('Invalid cache directory');
        if(preg_match('#/(\d+)$#', $cache['path'], $match)) {
            $cache['path'] = substr($cache['path'], 0, -strlen($match[0]));
            $cache['name'] = $cache['name']==='index' ? $match[1] : trim($match[1].'_'.$cache['name'], '_');
        }
        $name = preg_replace('/[^\w]/', '', (string)$cache['name']);
        if($name === '' || $name !== (string)$cache['name']) $name .= '~n'.substr(hash('sha256', (string)$cache['name']), 0, 16);
        if(array_key_exists('vary', $cache)) $name .= '~v'.hash('sha256', serialize($cache['vary']));
        $this->cache = ['use'=>true, 'file'=>myFile::realPath(rtrim($cache['path'], '/').'/'.$name.'.'.$cache['ext']), 'expire'=>(int)$cache['expire']];
        return $this;
    }

    /**
     * 功能说明：获取模板或默认风格模板，并登记编译依赖（含不存在的候选路径）
     *
     * @param string $file1 首选本地文件；只能由可信应用代码构造
     * @param string &$file2 引用参数；备用文件，成功后回填实际位置（默认：''）
     * @return string 原始模板内容，不转换换行或反斜杠
     * @throws RuntimeException 模板不存在或不能读取
     */
    public function getTemplate($file1, &$file2 = '') {
        $candidates = [$file1];
        if($file2 !== '') $candidates[] = $file2;
        $candidates[] = $this->setting['path'].'/default/'.basename($file1);
        if($file2 !== '') $candidates[] = $this->setting['path'].'/default/'.basename($file2);
        foreach(array_unique($candidates) as $file) {
            if(strpos($file, "\0")!==false || strpos($file, '://')!==false) throw new InvalidArgumentException('Local template file required');
            $this->dependencies[$file] = is_file($file) ? hash_file('sha256', $file) : null;
            if(is_file($file)) {
                $content = file_get_contents($file);
                if($content === false) throw new RuntimeException('Cannot read template');
                $file2 = $file; return $content;
            }
        }
        throw new RuntimeException('Cannot find template file ['.basename($file1).']');
    }

    /**
     * 功能说明：设置循环记录，支持数组或可遍历对象
     *
     * @param string $key 循环名称，对应 key_field 占位符
     * @param array|object $value 单条记录；fullset=true 时为完整记录集合
     * @param bool $fullset true 替换集合，false 追加记录；time 仍只作最少行数补齐（默认：false）
     * @return $this 返回当前实例，支持链式调用。
     * @throws InvalidArgumentException 集合或记录类型非法
     */
    public function setLoop($key, $value, $fullset = false) {
        if($fullset) {
            if($value instanceof Traversable) $value = iterator_to_array($value);
            if(!is_array($value)) throw new InvalidArgumentException('Loop collection must be iterable');
            foreach($value as $row) if(!is_array($row) && !is_object($row)) throw new InvalidArgumentException('Loop record must be an array or object');
            self::$tpl_para[$this->hash]['loop'][$key] = array_values($value);
        } else {
            if(!is_array($value) && !is_object($value)) throw new InvalidArgumentException('Loop record must be an array or object');
            self::$tpl_para[$this->hash]['loop'][$key][] = $value;
        }
        return $this;
    }

    /**
     * 功能说明：设置条件值，缺省条件为 false
     *
     * @param string $key 条件名称
     * @param mixed $value 按 PHP 布尔规则判断（默认：true）
     * @return $this 返回当前实例，支持链式调用。
     */
    public function setIf($key, $value = true) { self::$tpl_para[$this->hash]['if'][$key] = $value; return $this; }

    /**
     * 功能说明：设置分支值，未赋值时按 null 处理
     *
     * @param string $key 分支名称
     * @param mixed $value 保留旧 switch 的宽松比较规则
     * @return $this 返回当前实例，支持链式调用。
     */
    public function setSwitch($key, $value) { self::$tpl_para[$this->hash]['switch'][$key] = $value; return $this; }

    /**
     * 功能说明：赋值普通变量，数组/对象展开一层为 name_key；旧占位符仍原样输出
     *
     * @param string|array|object $name 名称或无前缀批量变量
     * @param mixed $value 值；非字符串标量保留 myString::fromAny 的历史表示（默认：''）
     * @return $this 返回当前实例，支持链式调用。
     * @see myTemplate::assignEscaped() HTML文本/属性的转义赋值
     */
    public function assign($name, $value = '') {
        if(is_array($name) || is_object($name)) { $value = $name; $name = ''; }
        if(is_array($value) || is_object($value)) {
            foreach($value as $k => $v) self::$tpl_para[$this->hash]['para'][$name === '' ? $k : $name.'_'.$k] = $v;
        } else self::$tpl_para[$this->hash]['para'][$name] = is_string($value) ? $value : myString::fromAny($value);
        return $this;
    }

    /**
     * 功能说明：将变量按 HTML 文本/引号属性上下文转义后赋值；不要再叠加 |html
     *
     * @param string|array|object $name 名称或批量映射
     * @param mixed $value 值，批量赋值沿用 assign 的一层展开规则（默认：''）
     * @return $this 返回当前实例，支持链式调用。
     */
    public function assignEscaped($name, $value = '') {
        if(is_array($name) || is_object($name)) { $value = $name; $name = ''; }
        if(is_array($value) || is_object($value)) foreach($value as $k => $v) $this->assign($name === '' ? $k : $name.'_'.$k, htmlspecialchars($this->displayValue($v), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        else $this->assign($name, htmlspecialchars($this->displayValue($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        return $this;
    }

    /**
     * 功能说明：加载可信 PHP 返回数组、INI 或 JSON 配置，支持重复调用
     *
     * @param string $file 本地配置位置；PHP 文件会执行，只能来自可信应用
     * @param string $prefix 赋值前缀（默认：''）
     * @param bool $mode true 用下划线保留嵌套键路径，false 仅保留叶子键（默认：false）
     * @return $this 不存在文件沿用旧行为忽略
     * @throws UnexpectedValueException 配置不是数组或深度超过 64
     */
    public function loadSet($file, $prefix = '', $mode = false) {
        if(!is_file($file)) return $this;
        switch(strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
            case 'php': $vars = (static function($path) { return include $path; })($file); break;
            case 'ini': $vars = myString::fromIni($file, true); break;
            case 'json': $vars = json_decode(file_get_contents($file), true, 64, JSON_THROW_ON_ERROR); break;
            default: return $this;
        }
        if(!is_array($vars)) throw new UnexpectedValueException('Template settings must return an array');
        $flatten = function(array $arr, $idx = '', $depth = 0) use (&$flatten, $mode) {
            if($depth > 64) throw new UnexpectedValueException('Template settings too deeply nested');
            $result = [];
            foreach($arr as $k => $v) {
                $key = $mode && $idx !== '' ? $idx.'_'.$k : $k;
                if(is_array($v)) $result = array_replace($result, $flatten($v, $key, $depth+1));
                else $result[$key] = $v;
            }
            return $result;
        };
        return $this->assign($prefix, $flatten($vars));
    }

    /**
     * 功能说明：解析引号属性，完整保留等号、反斜杠、空串及相反种类的引号
     *
     * @param string $text 属性列表
     * @return array 小写属性名到原始字符串值的映射
     * @throws UnexpectedValueException 属性语法错误
     */
    protected function attributes($text) {
        $attrs = []; $offset = 0; $length = strlen($text);
        while($offset < $length) {
            if(preg_match('/\G\s*$/s', $text, $m, 0, $offset)) break;
            if(!preg_match('/\G\s+(\w+)\s*=\s*(["\'])(.*?)\2/s', $text, $m, 0, $offset)) throw new UnexpectedValueException('Invalid template tag attributes');
            $attrs[strtolower($m[1])] = $m[3]; $offset += strlen($m[0]);
        }
        return $attrs;
    }

    /**
     * 功能说明：解析旧扩展接口使用的块属性，正文不再 stripslashes
     *
     * @param string $attrib 引号属性串
     * @param string $content 正文
     * @param array &$block_attrib 引用参数；回填属性
     * @param string &$block_content 引用参数；回填原样正文
     * @return void 无返回值。
     */
    protected function parseBlock($attrib, $content, &$block_attrib, &$block_content) {
        $block_attrib = $this->attributes($attrib); $block_content = $content;
    }

    /**
     * 功能说明：登记可信的编译期标签回调，扩展结果可包含 PHP
     *
     *
     * 回调签名：function (myTemplate &$tpl, array &$attrs): string；两项按引用传入，允许回调忽略不需要的形参。attrs以解析值为基础并含unit/unit_blank/class，回调可补充占位参数；在编译期执行，返回的PHP仅在渲染时按所处模板分支执行。
     *
     * 调用示例：
     *     $tpl->regTag('hello', function ($current, &$attrs) {
     *         return htmlspecialchars((string)($attrs['name'] ?? '访客'), ENT_QUOTES, 'UTF-8');
     *     });
     *
     * @param string|array $tag_name 标签名或名称到回调的映射
     * @param callable|string $tag_func 接收当前模板和属性引用，返回模板/PHP 文本；允许无属性标签（默认：''）
     * @return $this 返回当前实例，支持链式调用。
     * @throws InvalidArgumentException 标签名或回调非法
     * @phpstan-param string|array<string,callable> $tag_name
     * @phpstan-param callable|'' $tag_func
     */
    public function regTag($tag_name, $tag_func = '') {
        if(is_array($tag_name)) { foreach($tag_name as $name => $func) $this->regTag($name, $func); return $this; }
        if(!is_string($tag_name) || !preg_match('/^\w+$/D', $tag_name) || !is_callable($tag_func)) throw new InvalidArgumentException('Invalid template tag callback');
        $this->tags[strtolower($tag_name)] = $tag_func; return $this;
    }

    /**
     * 功能说明：计算基础编译身份，模板值不写入编译文件
     *
     * 参数说明：无显式参数。
     *
     * @return string 包含引擎、文件路径、脚本开关、分隔符及显式版本的摘要
     */
    protected function identity() {
        return hash('sha256', serialize([hash_file('sha256', __FILE__), $this->setting['requested'], $this->allow_script, $this->delimiter_l, $this->delimiter_r, $this->setting['compile_version'], PHP_VERSION_ID, (bool)ini_get('short_open_tag')]));
    }

    /**
     * 功能说明：计算已登记标签的实现身份；闭包和对象回调按模板实例隔离；具名回调的外部状态变化请更新 compile_version
     *
     * 参数说明：无显式参数。
     *
     * @return string 名称、源码文件摘要及定义位置的摘要；闭包和对象回调包含实例身份
     */
    protected function tagIdentity() {
        $tags = []; $this->tag_dependencies = [];
        foreach($this->tags as $name => $func) {
            if(is_array($func)) $r = new ReflectionMethod($func[0], $func[1]);
            elseif(is_string($func) && strpos($func, '::')!==false) $r = ReflectionMethod::createFromMethodName($func);
            elseif(is_object($func) && !($func instanceof Closure)) $r = new ReflectionMethod($func, '__invoke');
            else $r = new ReflectionFunction($func);
            $file = $r->getFileName();
            if($file) $this->tag_dependencies[$file] = hash_file('sha256', $file);
            $tags[$name] = [$r->getName(), $r instanceof ReflectionMethod ? $r->getDeclaringClass()->getName() : '', $file, $file ? hash_file('sha256', $file) : '', $r->getStartLine(), $r->getEndLine(), is_object($func) || (is_array($func) && is_object($func[0])) ? $this->hash.spl_object_hash(is_array($func) ? $func[0] : $func) : ''];
        }
        ksort($tags); return hash('sha256', serialize($tags));
    }

    /**
     * 功能说明：校验元数据中所有依赖的内容摘要，同时检查原先不存在的模板是否已出现
     *
     * @param array $files 路径到 SHA256/null 的映射
     * @return bool 全部一致才允许复用缓存
     */
    protected function dependenciesValid(array $files) {
        if(!$files) return false;
        foreach($files as $file => $hash) {
            clearstatcache(true, $file);
            if((is_file($file) ? hash_file('sha256', $file) : null) !== $hash) return false;
        }
        return true;
    }

    /**
     * 功能说明：读取缓存元数据，损坏文件作为未命中处理
     *
     * @param string $file 元数据位置
     * @return array 缺失/格式错误时为空数组
     */
    protected function metadata($file) {
        if(!is_file($file)) return [];
        $data = json_decode(file_get_contents($file), true);
        return is_array($data) ? $data : [];
    }

    /**
     * 功能说明：在目标目录写临时文件后原子替换，避免读到半个 PHP/HTML 文件
     *
     * @param string $file 目标位置，目录由可信应用配置
     * @param string $content 完整文件内容
     * @return void 无返回值。
     * @throws RuntimeException 目录或写入/替换失败
     */
    protected function atomicWrite($file, $content) {
        $dir = dirname($file);
        if(!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) throw new RuntimeException('Cannot create template cache directory');
        $temp = tempnam($dir, '.tpl-');
        if($temp === false) throw new RuntimeException('Cannot create template cache file');
        try {
            if(file_put_contents($temp, $content, LOCK_EX)!==strlen($content) || !rename($temp, $file)) throw new RuntimeException('Cannot save template cache');
            if(function_exists('opcache_invalidate')) opcache_invalidate($file, true);
        } finally { if(is_file($temp)) unlink($temp); }
    }

    /**
     * 功能说明：取得旧接口使用的编译入口位置，风格目录总是正确连接
     *
     * 参数说明：无显式参数。
     *
     * @return string 编译入口的本地绝对路径
     */
    protected function compiledFile() {
        return $this->setting['path_compile'].'/'.($this->setting['style'] === '' ? '' : $this->setting['style'].'/').$this->setting['name'].'.php';
    }

    /**
     * 功能说明：用 PHP 词法器隔离脚本，防止模板正文被编译器当成 PHP 字符串插值
     *
     * @param string $text 模板内容
     * @param bool $keep true 保存可信 PHP 为内部标记；false 去掉脚本
     * @return string 只含正文和内部标记的文本
     */
    protected function protectPHP($text, $keep) {
        $xml = [];
        $text = preg_replace_callback('/<\?xml\b/i', function($m) use (&$xml) { $key = "\x1aXML".count($xml)."\x1a"; $xml[$key] = $m[0]; return $key; }, $text);
        $text = preg_replace('/<\?(?!php(?:\s|$)|=)/i', '<?php ', $text);
        $result = ''; $code = '';
        foreach(token_get_all($text) as $token) {
            if(is_array($token) && $token[0] === T_INLINE_HTML) {
                if($code !== '') { if($keep) { $id = count($this->php_parts); $this->php_parts[$id] = $code; $result .= "\x1aPHP".$id."\x1a"; } $code = ''; }
                $result .= $token[1];
            } else $code .= is_array($token) ? $token[1] : $token;
        }
        if($code !== '' && $keep) { $id = count($this->php_parts); $this->php_parts[$id] = $code; $result .= "\x1aPHP".$id."\x1a"; }
        return strtr($result, $xml);
    }

    /**
     * 功能说明：将正文编译为安全的字面量输出，内部脚本标记恢复为可信代码
     *
     * @param string $text 正文
     * @return string PHP 语句，不改变美元符、反斜杠、换行或 heredoc 结束字样
     */
    protected function literalCode($text) {
        $code = '';
        foreach(preg_split('/(\x1aPHP\d+\x1a)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE) as $part) {
            if(preg_match('/^\x1aPHP(\d+)\x1a$/D', $part, $m) && isset($this->php_parts[(int)$m[1]])) {
                $php = $this->php_parts[(int)$m[1]];
                // 添加关闭标签兼容可信脚本在文件末尾省略关闭标签的情况。
                $code .= '?>'.$php.(preg_match('/\?>\s*$/D', $php) ? '' : '?>').'<?php '."\n";
            } elseif($part !== '') $code .= 'echo '.var_export($part, true).';'."\n";
        }
        return $code;
    }

    /**
     * 功能说明：将模板分隔标签转为词元，PHP 部分已经隔离
     *
     * @param string $text 正文
     * @return array 交替的正文及标签原文
     */
    protected function templateTokens($text) {
        return preg_split('#('.preg_quote($this->delimiter_l, '#').'.*?'.preg_quote($this->delimiter_r, '#').')#s', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    }

    /**
     * 功能说明：递归编译标准块，嵌套块和循环使用独立局部变量
     *
     * @param array $tokens 词元
     * @param int &$i 引用参数；当前下标，返回时停在调用方要求的结束/分支标签
     * @param array $stops 当前层允许的结束标记；switch:default 为默认分支，(default) 保持普通字符串分支（默认：[]）
     * @param int $depth 嵌套深度，上限 64（默认：0）
     * @return string PHP 输出语句
     * @throws UnexpectedValueException 块未闭合、交叉闭合或嵌套过深
     */
    protected function compileSequence(array $tokens, &$i, array $stops = [], $depth = 0) {
        if($depth > 64) throw new UnexpectedValueException('Template nesting exceeds 64');
        $code = '';
        while($i < count($tokens)) {
            $token = $tokens[$i];
            if(substr($token, 0, strlen($this->delimiter_l)) !== $this->delimiter_l) { $code .= $this->literalCode($token); $i++; continue; }
            $tag = substr($token, strlen($this->delimiter_l), -strlen($this->delimiter_r));
            $lower = strtolower($tag);
            if(in_array($lower, $stops, true) || (in_array('case', $stops, true) && preg_match('/^\(.*\)$/sD', $tag))) break;
            if(preg_match('/^(loop|if|switch|random):start(\s.*)?$/isD', $tag, $m)) {
                $type = strtolower($m[1]); $attrs = $this->attributes($m[2] ?? ''); $key = var_export($attrs['key'] ?? '', true); $id = ++$this->sequence; $i++;
                if($type === 'loop') {
                    $start = $i; $body = $this->compileSequence($tokens, $i, ['loop:end'], $depth+1);
                    $blank = implode('', array_slice($tokens, $start, $i-$start));
                    $blank = preg_replace('#'.preg_quote($this->delimiter_l, '#').'.*?'.preg_quote($this->delimiter_r, '#').'#s', '', $blank);
                    $blank = preg_replace('/<(td|li|p|dd|dt)([^>]*?)>.*?<\/\1>/is', '<$1$2>&nbsp;</$1>', $blank);
                    $blank = preg_replace('/\x1aPHP\d+\x1a/', '', $blank);
                    $time = max(0, (int)($attrs['time'] ?? 0));
                    if($time > 100000) throw new UnexpectedValueException('Loop padding exceeds 100000');
                    $code .= '$__ms_n'.$id.' = 0; foreach(($tpl_para[\'loop\']['.$key.'] ?? []) as $__ms_row'.$id.') { $__ms_rows[] = ['.$key.', $__ms_row'.$id.'];'."\n".$body.'array_pop($__ms_rows); $__ms_n'.$id.'++; }'."\n";
                    $code .= 'for(; $__ms_n'.$id.' < '.$time.'; $__ms_n'.$id.'++) {'.$this->literalCode($blank).'}'."\n";
                } elseif($type === 'if') {
                    $yes = $this->compileSequence($tokens, $i, ['else','if:end'], $depth+1); $no = '';
                    if($i < count($tokens) && strtolower($tokens[$i]) === strtolower($this->delimiter_l.'else'.$this->delimiter_r)) { $i++; $no = $this->compileSequence($tokens, $i, ['if:end'], $depth+1); }
                    $code .= 'if($tpl_para[\'if\']['.$key.'] ?? false) {'."\n".$yes.'} else {'."\n".$no.'}'."\n";
                } elseif($type === 'switch') {
                    $this->compileSequence($tokens, $i, ['case','switch:default','switch:end'], $depth+1);
                    $code .= 'switch($tpl_para[\'switch\']['.$key.'] ?? null) {'."\n";
                    $has_default = false;
                    while($i < count($tokens)) {
                        $is_default = strtolower($tokens[$i]) === strtolower($this->delimiter_l.'switch:default'.$this->delimiter_r);
                        if(!$is_default && !preg_match('#^'.preg_quote($this->delimiter_l, '#').'\((.*?)\)'.preg_quote($this->delimiter_r, '#').'$#sD', $tokens[$i], $case)) break;
                        if($is_default && $has_default) throw new UnexpectedValueException('Duplicate switch default branch');
                        if($is_default) $has_default = true;
                        $i++; $body = $this->compileSequence($tokens, $i, ['case','switch:default','switch:end'], $depth+1);
                        $code .= ($is_default ? 'default:' : 'case '.var_export($case[1], true).':')."\n".$body.'break;'."\n";
                    }
                    $code .= '}'."\n";
                } else {
                    $parts = [];
                    do { $parts[] = $this->compileSequence($tokens, $i, ['(s)','random:end'], $depth+1); if(($tokens[$i] ?? '') === $this->delimiter_l.'(s)'.$this->delimiter_r) $i++; else break; } while(true);
                    $code .= 'switch(random_int(0, '.(count($parts)-1).')) {'."\n";
                    foreach($parts as $n => $body) $code .= 'case '.$n.':'.$body.'break;'."\n";
                    $code .= '}'."\n";
                }
                if(!isset($tokens[$i]) || strtolower($tokens[$i]) !== strtolower($this->delimiter_l.$type.':end'.$this->delimiter_r)) throw new UnexpectedValueException('Unclosed template '.$type.' block');
                $i++; continue;
            }
            if(preg_match('/^(loop|if|switch|random):end$|^else$|^switch:default$/iD', $tag)) throw new UnexpectedValueException('Unexpected template block end');
            if(preg_match('/^(\w+)(?:\|(html|url))?$/D', $tag, $m)) $code .= 'echo $this->templateValue('.var_export($m[1], true).', $tpl_para, $__ms_rows, '.var_export($m[2] ?? '', true).');'."\n";
            else $code .= $this->literalCode($token);
            $i++;
        }
        return $code;
    }

    /**
     * 功能说明：将占位值转为可显示字符串；不存在/不可显示对象返回空串
     *
     * @param mixed $value 原始值
     * @return string 不隐式打印数组或无 __toString 的对象
     */
    protected function displayValue($value) {
        return is_scalar($value) || (is_object($value) && method_exists($value, '__toString')) ? (string)$value : '';
    }

    /**
     * 功能说明：读取最内层匹配的循环字段或普通变量，缺失字段安静输出空串
     *
     * @param string $name 完整占位名称
     * @param array $data 当前对象变量空间
     * @param array $rows 外到内的循环记录栈
     * @param string $filter 空串原样、html 为 UTF-8 HTML 转义、url 为 URL 组件编码（默认：''）
     * @return string 最终输出；HTML 转义不适用于 JS/CSS 或危险 URL 协议
     */
    protected function templateValue($name, array $data, array $rows, $filter = '') {
        $value = $data['para'][$name] ?? '';
        for($n = count($rows)-1; $n >= 0; $n--) {
            list($key, $row) = $rows[$n]; $prefix = $key.'_';
            if(strpos($name, $prefix) === 0) { $field = substr($name, strlen($prefix)); $value = is_array($row) ? ($row[$field] ?? '') : ($row->$field ?? ''); break; }
        }
        $value = $this->displayValue($value);
        if($filter === 'html') return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return $filter === 'url' ? rawurlencode($value) : $value;
    }

    /**
     * 功能说明：按内容和编译选项复用缓存，生成不可变版本文件供当前渲染使用
     *
     * 参数说明：无显式参数。
     *
     * @return string 当前版本的 PHP 文件路径；旧 name.php 保留为最新版本入口
     * @throws RuntimeException 文件操作失败
     * @throws UnexpectedValueException 模板语法非法
     */
    protected function compileTemplate() {
        $file = $this->compiledFile(); $identity = $this->identity(); $tags = $this->tagIdentity(); $meta = $this->metadata($file.'.meta.json');
        if(($meta['identity'] ?? '') === $identity && ($meta['tags'] ?? '') === $tags && isset($meta['files'], $meta['artifact']) && is_array($meta['files']) && $this->dependenciesValid($meta['files'])) {
            $artifact = $file.'.'.$meta['artifact'].'.php';
            if(preg_match('/^[a-f0-9]{64}$/D', $meta['artifact']) && is_file($artifact) && hash_file('sha256', $artifact) === $meta['artifact']) { $this->dependencies = $meta['files']; return $artifact; }
        }
        $this->dependencies = []; $this->php_parts = []; $this->sequence = 0;
        $this->setting['file'] = $this->setting['requested'];
        $text = $this->getTemplate($this->setting['requested'], $this->setting['file']);
        $this->dependencies = array_replace($this->dependencies, $this->tag_dependencies);
        if(strpos($text, "\x1a")!==false) throw new UnexpectedValueException('Reserved template control character');
        $text = $this->protectPHP($text, $this->allow_script);
        $text = $this->parseTag($text);
        $text = $this->protectPHP($text, true);
        $tokens = $this->templateTokens($text); $i = 0;
        $script = '<?php $tpl_para = myTemplate::$tpl_para[$this->hash]; $__ms_rows = [];'."\n".$this->compileSequence($tokens, $i);
        $digest = hash('sha256', $script); $artifact = $file.'.'.$digest.'.php';
        if(!is_file($artifact) || hash_file('sha256', $artifact) !== $digest) $this->atomicWrite($artifact, $script);
        $this->atomicWrite($file, '<?php return include __DIR__.'.var_export('/'.basename($artifact), true).';');
        $this->atomicWrite($file.'.meta.json', json_encode(['identity'=>$identity, 'tags'=>$tags, 'files'=>$this->dependencies, 'artifact'=>$digest], JSON_THROW_ON_ERROR));
        return $artifact;
    }

    /**
     * 功能说明：清除当前模板编译版本，可选择同时删除当前页面缓存
     *
     * @param bool $html true 连同当前 vary 对应的静态页面一起删除，默认保持历史行为（默认：false）
     * @return $this 返回当前实例，支持链式调用。
     */
    public function removeCache($html = false) {
        $file = $this->compiledFile();
        foreach(glob($file.'.*.php') ?: [] as $variant) if(preg_match('/\.[a-f0-9]{64}\.php$/D', $variant)) unlink($variant);
        foreach([$file, $file.'.meta.json'] as $path) if(is_file($path)) unlink($path);
        if($html) foreach([$this->cache['file'], $this->cache['file'].'.meta.json'] as $path) if(is_file($path)) unlink($path);
        return $this;
    }

    /**
     * 功能说明：检测静态页面是否有效，核对引擎身份、依赖、页面摘要及实际当前时间
     *
     * 参数说明：无显式参数。
     *
     * @return bool 空串及字符串 0 也是有效缓存；无元数据的旧页面会重新生成
     */
    public function checkCache() {
        if(!$this->cache['use'] || $this->cache['expire'] <= 0 || !is_file($this->cache['file'])) return false;
        clearstatcache(true, $this->cache['file']);
        if(filemtime($this->cache['file'])+$this->cache['expire'] <= time()) return false;
        $meta = $this->metadata($this->cache['file'].'.meta.json');
        return ($meta['identity'] ?? '') === $this->identity() && (!$this->tags || ($meta['tags'] ?? '') === $this->tagIdentity()) && isset($meta['files']) && is_array($meta['files']) && $this->dependenciesValid($meta['files']) && ($meta['output'] ?? '') === hash_file('sha256', $this->cache['file']);
    }

    /**
     * 功能说明：读取有效的静态页面
     *
     * 参数说明：无显式参数。
     *
     * @return string 未命中返回空串；需区分空页面时先调用 checkCache
     */
    public function getCache() { return $this->checkCache() ? file_get_contents($this->cache['file']) : ''; }

    /**
     * 功能说明：获取最终 HTML；独立缓冲区保证异常时不清空调用者已有内容
     *
     * @param string|array $global_vars 供可信扩展 PHP 使用的全局变量名，逗号分隔或数组（默认：''）
     * @param bool $tpl_show true 输出并返回，false 只返回（默认：true）
     * @param bool $minify 保守去除 HTML 行首缩进，保留 pre/textarea/script/style 内容（默认：false）
     * @return string 渲染后的内容
     * @throws Throwable 编译或扩展异常继续传播，渲染缓冲区始终清理
     */
    public function render($global_vars = '', $tpl_show = true, $minify = false) {
        if($this->checkCache() && (bool)($this->metadata($this->cache['file'].'.meta.json')['minify'] ?? false) === (bool)$minify) $content = file_get_contents($this->cache['file']);
        else {
            $script = $this->compileTemplate();
            $level = ob_get_level(); ob_start();
            try {
                // 通过闭包限定导入范围，避免 global_vars 覆盖渲染器自身的变量。
                $execute = function($template_script, $names) {
                    foreach(is_array($names) ? $names : explode(',', $names) as $name) {
                        $name = ltrim(trim($name), '$');
                        if(preg_match('/^[a-zA-Z_]\w*$/D', $name) && strpos($name, '__ms_') !== 0 && !in_array($name, ['this','template_script','names','name','GLOBALS','tpl_para'], true)) $$name =& $GLOBALS[$name];
                    }
                    include $template_script;
                };
                $execute($script, $global_vars);
                $content = ob_get_contents();
            } finally { while(ob_get_level() > $level) ob_end_clean(); }
            if($minify) $content = $this->minifyHTML($content);
            if($this->cache['use'] && $this->cache['expire'] > 0) {
                $this->atomicWrite($this->cache['file'], $content);
                $this->atomicWrite($this->cache['file'].'.meta.json', json_encode(['identity'=>$this->identity(), 'files'=>$this->dependencies, 'output'=>hash('sha256', $content), 'tags'=>$this->tagIdentity(), 'minify'=>(bool)$minify], JSON_THROW_ON_ERROR));
            }
        }
        if($tpl_show) echo $content;
        return $content;
    }

    /**
     * 功能说明：保守压缩 HTML，去除普通标签行首缩进；特殊内容整段保护
     *
     * @param string $content 完整 HTML
     * @return string 保留注释、文本空格、脚本与样式的结果
     */
    protected function minifyHTML($content) {
        // 跨标签空白也可能影响 inline 元素；仅移除行首非显示缩进，不合并文本。
        $parts = preg_split('#(<(?:pre|textarea|script|style)\b[^>]*>.*?</(?:pre|textarea|script|style)\s*>)#is', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach($parts as $i => &$part) if($i % 2 === 0) $part = preg_replace('/^[\t ]+(?=<)/m', '', $part);
        return implode('', $parts);
    }

    /**
     * 功能说明：在自定义标签返回文本中按 PHP 词法上下文输出属性占位符
     *
     * @param string $result 可信扩展生成的 HTML/PHP
     * @param array &$attrs 引用参数；标签属性，回填未提供的占位键为空串
     * @return string HTML 中使用输出语句，PHP 字符串中使用连接或插值，避免嵌套 PHP 标签
     * @throws UnexpectedValueException nowdoc占位值是动态变量表达式，无法按该上下文展开
     */
    protected function tagPlaceholders($result, array &$attrs) {
        $pattern = '#'.preg_quote($this->delimiter_l, '#').'(\w+)'.preg_quote($this->delimiter_r, '#').'#';
        $code = ''; $nowdoc = false;
        foreach(token_get_all($result) as $token) {
            if(!is_array($token)) { $code .= $token; continue; }
            list($type, $text) = $token;
            if($type === T_START_HEREDOC) $nowdoc = strpos($text, "'") !== false;
            if($type === T_END_HEREDOC) $nowdoc = false;
            if(in_array($type, [T_INLINE_HTML, T_ENCAPSED_AND_WHITESPACE, T_CONSTANT_ENCAPSED_STRING], true)) {
                $quote = substr($text, 0, 1);
                $text = preg_replace_callback($pattern, function($m) use (&$attrs, $type, $quote, $nowdoc) {
                    $attrs += [$m[1]=>'']; $key = var_export($m[1], true);
                    if($type === T_INLINE_HTML) return '<?=$this->displayValue($tag_attrs['.$key.'] ?? \'\')?>';
                    if($type === T_CONSTANT_ENCAPSED_STRING) return $quote.'.$this->displayValue($tag_attrs['.$key.'] ?? \'\').'.$quote;
                    if($nowdoc) {
                        if(is_string($attrs[$m[1]]) && strpos($attrs[$m[1]], '$')===0) throw new UnexpectedValueException('Dynamic tag placeholder requires heredoc rather than nowdoc');
                        return $this->displayValue($attrs[$m[1]]);
                    }
                    return '{$tag_attrs['.$key.']}';
                }, $text);
            }
            $code .= $text;
        }
        return $code;
    }

    /**
     * 功能说明：编译自定义标签，保留既有 PHP 代码生成及 split 标记约定
     *
     * @param string $content 已隔离模板内原始 PHP 的内容
     * @return string 未知标签原样保留，登记标签由可信回调展开
     * @throws UnexpectedValueException 属性格式不合法
     * @throws Throwable 被调用回调/后端的异常按当前入口继续传递，不包装为成功结果
     */
    protected function parseTag($content) {
        return preg_replace_callback('#'.preg_quote($this->delimiter_l, '#').'(\w+)(\s+.*?)?'.preg_quote($this->delimiter_r, '#').'#s', function($match) {
            $name = strtolower($match[1]);
            if(!isset($this->tags[$name])) return $match[0];
            $attrs = $this->attributes($match[2] ?? '');
            $attrs += ['unit'=>'', 'unit_blank'=>'', 'class'=>''];
            foreach($attrs as &$v) {
                if(preg_match('/^\$(\w+)$/D', $v, $m)) $v = '$GLOBALS['.var_export($m[1], true).']';
                elseif(preg_match('/^\$_(\w+)((?:\[[^\r\n]+\])+)$/D', $v, $m)) $v = '$_'.strtoupper($m[1]).$m[2];
                elseif(preg_match('/^\$(\w+)((?:\[[^\r\n]+\])+)$/D', $v, $m)) $v = '$GLOBALS['.var_export($m[1], true).']'.$m[2];
            }
            unset($v);
            $tpl = $this;
            $result = (string)call_user_func_array($this->tags[$name], [&$tpl, &$attrs]);
            if(preg_match('#(<\?php(.+)/\*split\*/)#is', $result, $split)) {
                $result = str_replace($split[0], '<?php', $result);
                $result = '<?php'.$split[2].'?>'."\n".$result;
            }
            $parameters = [];
            foreach($attrs as $k => $v) if(is_scalar($v) || $v === null) $parameters['{myTemplate::'.$k.'}'] = (string)$v;
            $result = strtr($result, $parameters);
            $serialized = $attrs;
            unset($serialized['unit'], $serialized['unit_blank'], $serialized['sql']);
            $result = str_replace('{myTemplate::tpl_paras}', serialize($serialized), $result);
            $result = $this->tagPlaceholders($result, $attrs);
            $code = '<?php $tag_attrs = '.var_export($attrs, true).'; checkPara($tag_attrs, true); ?>';
            return $code.$result;
        }, $content);
    }

    /**
     * 功能说明：提取首个完整块，兼容可信扩展生成 PHP 时使用的历史返回格式
     *
     * @param string $tpl_content 子模板正文
     * @param string $tag loop/if/switch，支持同类嵌套且名称按字面匹配（默认：''）
     * @param string $idx loop 记录变量名，替换为旧 heredoc 字段表达式（默认：''）
     * @return array [块原文, 单元或分支映射, 补白/else/switch 默认正文]；(default) 仍为普通分支键，未找到时均为空串
     * @throws InvalidArgumentException 非法 loop PHP 变量名
     * @throws UnexpectedValueException 找到的块未闭合
     */
    public function getBlock($tpl_content, $tag = '', $idx = '') {
        $tag = strtolower($tag);
        if($idx !== '' && !preg_match('/^[a-zA-Z_]\w*$/D', $idx)) throw new InvalidArgumentException('Invalid loop variable');
        $tokens = $this->templateTokens($tpl_content); $start = null; $end = null; $stack = []; $markers = [];
        foreach($tokens as $i => $token) {
            if(substr($token, 0, strlen($this->delimiter_l)) !== $this->delimiter_l) continue;
            $text = substr($token, strlen($this->delimiter_l), -strlen($this->delimiter_r));
            if(preg_match('/^(loop|if|switch|random):start(?:\s.*)?$/isD', $text, $m)) {
                $type = strtolower($m[1]);
                if($start === null) { if($type !== $tag) continue; $start = $i; }
                $stack[] = $type;
            } elseif($start !== null && preg_match('/^(loop|if|switch|random):end$/iD', $text, $m)) {
                if(array_pop($stack) !== strtolower($m[1])) throw new UnexpectedValueException('Crossed template block');
                if(!$stack) { $end = $i; break; }
            } elseif($start !== null && count($stack) === 1) $markers[$i] = $text;
        }
        if($start === null) return ['', '', ''];
        if($end === null) throw new UnexpectedValueException('Unclosed template block');
        $block = implode('', array_slice($tokens, $start, $end-$start+1));
        $unit = implode('', array_slice($tokens, $start+1, $end-$start-1)); $addon = '';
        if($tag === 'loop') {
            $addon = preg_replace('#'.preg_quote($this->delimiter_l, '#').'.*?'.preg_quote($this->delimiter_r, '#').'#s', '', $unit);
            $addon = preg_replace('/<(td|li|p|dd|dt)([^>]*?)>.*?<\/\1>/is', '<$1$2>&nbsp;</$1>', $addon);
            if($idx !== '') $unit = preg_replace_callback('#'.preg_quote($this->delimiter_l, '#').preg_quote($idx, '#').'_(\w+)'.preg_quote($this->delimiter_r, '#').'#', function($m) use ($idx) { return '{$'.$idx.'['.var_export($m[1], true).']}'; }, $unit);
        } elseif($tag === 'if') {
            foreach($markers as $i => $text) if(strtolower($text) === 'else') {
                $unit = implode('', array_slice($tokens, $start+1, $i-$start-1));
                $addon = implode('', array_slice($tokens, $i+1, $end-$i-1)); break;
            }
            $unit = addslashes($unit); $addon = addslashes($addon);
        } elseif($tag === 'switch') {
            $unit = []; $cases = [];
            $default_position = null;
            foreach($markers as $i => $text) {
                if(preg_match('/^\((.*?)\)$/sD', $text, $m)) $cases[$i] = $m[1];
                elseif(strtolower($text) === 'switch:default') {
                    if($default_position !== null) throw new UnexpectedValueException('Duplicate switch default branch');
                    $default_position = $i; $cases[$i] = null;
                }
            }
            $positions = array_keys($cases); $positions[] = $end;
            foreach($cases as $i => $name) {
                $next = $positions[array_search($i, $positions, true)+1];
                $body = implode('', array_slice($tokens, $i+1, $next-$i-1));
                if($name === null) $addon = $body; else $unit[$name] = $body;
            }
        }
        return [$block, $unit, $addon];
    }
}
