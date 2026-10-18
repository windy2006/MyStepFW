<?PHP
/********************************************
*                                           *
* Name    : My Config                       *
* Modifier: Windy2000                       *
* Time    : 2019-1-1                        *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/
/**
 * 文件：lib/myConfig.class.php
 * 功能说明：配置读取、合并与原子保存管理器
 *   解析PHP/JSON/INI配置，处理配置分组、结构表单、合并和并发保存冲突。
 *
 * 方法目录：
 *   public myConfig::init($file) — 绑定配置文件、读取内容版本，并寻找相邻的设置结构定义
 *   public myConfig::get($arr = true) — 获取全部配置，可选择递归数组或当前内部对象
 *   public myConfig::__get($idx) — 读取顶层配置，缺失时自动创建空分组以兼容链式赋值
 *   public myConfig::__set($idx, $value) — 直接设置顶层配置，不套用表单列表及密码转换
 *   public myConfig::__isset($idx) — 检查顶层配置是否存在且值非 null
 *   public myConfig::__unset($idx) — 删除顶层配置项，仅修改内存，持久化需另行 save()
 *   public myConfig::__clone() — 复制嵌套配置内容，避免克隆实例共享分组对象
 *   public myConfig::value($path, $default = null) — 无副作用地读取点分隔配置路径，保留已存在的 null
 *   public myConfig::__toString() — 将当前设置递归转换后编码为 JSON，便于展示及与脚本交互
 *   public static myConfig::a2o($arr) — 递归将数组及对象的可遍历属性复制到新的分组对象
 *   public static myConfig::o2a($obj) — 递归将分组对象及数组复制为数组，不猜测标量内容类型
 *   public static myConfig::recover($v) — 兼容历史表单/INI 的简单类型恢复，不用于 PHP/JSON 类型推断
 *   public myConfig::setParser($ext, $func_load, $func_save) — 登记自定义扩展名的加载及保存回调
 *   public myConfig::makeConstruction($file) — 编译并缓存表单结构元数据，只有生成内容改变时才原子写入
 *   public myConfig::load($file='', $return_object=false, &$snapshot = null) — 按文件格式读取配置，并返回本次稳定读取的内容摘要
 *   public myConfig::merge($setting, &$org = null) — 递归合并分组配置，同名标量覆盖，分组可替换原标量
 *   public myConfig::set($setting, $idx = '', $check = true) — 按表单规则批量更新设置，保留原值的明确类型及未提交分组
 *   protected myConfig::formValue($value, $current) — 依据已有字段的明确类型转换表单文本，不猜测文本字段类型
 *   public myConfig::save($type='', $file = '', $overwrite = false) — 编码完整配置并原子保存，默认防止原文件被并发更新覆盖
 *   protected myConfig::atomicWrite($file, $content, $expected = null, $check = false) — 锁定目标旁的锁文件，再以同目录临时文件原子发布完整内容
 *   public myConfig::build($detail, $idx = '', $ext_setting = array()) — 按可信结构定义生成设置表单项目及分组标题
 *
 * 使用说明：
 *   1. 初始化时传配置文件路径，value只读访问，get(true)导出数组。
 *   2. set用于兼容设置表单，merge递归覆盖；save默认检查加载版本避免覆盖并发编辑。
 *   3. PHP配置会被执行，路径和配置内容必须来自受信来源。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名保持PHP 7.4兼容，PHPDoc类型用于说明，不额外强制运行时类型。
 */


/**
 * 功能说明：解析PHP/JSON/INI配置，处理配置分组、结构表单、合并和并发保存冲突。
 *
 * 使用说明：
 *   1. 初始化时传配置文件路径，value只读访问，get(true)导出数组。
 *   2. set用于兼容设置表单，merge递归覆盖；save默认检查加载版本避免覆盖并发编辑。
 *   3. PHP配置会被执行，路径和配置内容必须来自受信来源。
 *
 * 补充说明：
 * 配置信息加载、分组合并、设置表单及原子保存
 *
 *     $config = new myConfig($file);                 // 加载并记录原文件内容版本
 *     $config->catalog->item = 'somevalue';          // 兼容链式读写，缺失分组自动创建
 *     $value = $config->value('db.port', 3306);       // 无副作用地读取点分隔路径
 *     $config->merge($overlay);                     // 递归覆盖分组
 *     $config->set($_POST['setting']);              // 依据结构处理设置表单
 *     $config->save();                              // 原子保存，拒绝覆盖并发修改
 *     $config->save('php', '', true);               // 显式允许覆盖冲突
 *
 * 使用说明：
 * 1. PHP/JSON 保留 null、布尔、数字及文本类型；内部数组/对象统一为分组对象，
 *    get(true) 再递归导出数组。INI 沿用文本配置约定，不保证复杂类型完整往返。
 * 2. value() 不创建节点；__get() 保留缺失节点自动创建行为。克隆会复制嵌套配置。
 * 3. set() 面向旧设置表单，列表转为 CSV；不是任意 JSON 数据的无损批量赋值接口。
 * 4. save() 保存原文件时检查加载版本，冲突抛异常；导出到不同文件不检查原版本。
 *    配置/结构 PHP 文件会被执行，只接受受信路径及结构定义。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 *
 * 动态配置字段随载入文件变化，不在通用框架中声明应用专用@property。缺失分组的__get会创建节点，纯读取优先value；新增注释不改变这一约定。
 *
 * @example ../app/Document/module/myConfig.php 框架现有演示，仅作为文档引用，不在检查时执行
 */
class myConfig extends myBase {
    protected
        $file = '',
        $construction = '',
        $type = '',
        $parser = [],
        $setting = array();
    protected $schema = [], $revision = null;

    /**
     * 功能说明：绑定配置文件、读取内容版本，并寻找相邻的设置结构定义
     *
     * 优先查找 config/default.php，再查找 construction/default.php；实际配置文件
     * 不存在时以空分组初始化，首次保存仍参与版本冲突检查。
     *
     * @param string $file 配置路径，扩展名决定加载格式；内置 php/ini/json
     * @return void 无返回值。
     * @throws InvalidArgumentException 不支持的格式
     * @throws UnexpectedValueException 配置根节点或结构定义不是数组/对象
     * @throws RuntimeException 读取期间文件持续变动、消失或结构保存失败
     */
    public function init($file) {
        $this->file = myFile::realPath($file);
        $this->type = strtolower(pathinfo($this->file, PATHINFO_EXTENSION));
        $this->schema = []; $this->construction = '';
        $this->setting = $this->load($this->file, true, $this->revision);
        $path = dirname($this->file);
        $list = [
            $path.'/config/default.php',
            $path.'/construction/default.php',
        ];
        foreach($list as $k) {
            if(is_file($k)) {
                $this->makeConstruction($k);
                break;
            }
        }
    }

    /**
     * 功能说明：获取全部配置，可选择递归数组或当前内部对象
     *
     * @param bool $arr true 返回递归转换的数组；false 返回内部配置对象引用（默认：true）
     * @return array|stdClass 全部设置；对象模式下修改嵌套属性会影响当前实例
     */
    public function get($arr = true) {
        $setting = $this->setting;
        if($arr) $setting = self::o2a($setting);
        return $setting;
    }

    /**
     * 功能说明：读取顶层配置，缺失时自动创建空分组以兼容链式赋值
     *
     * @param string $idx 顶层配置名；显式保存的 null 原样返回，不创建空对象
     * @return mixed 现有值或新建的 stdClass；只读且不希望创建节点时使用 value()
     */
    public function __get($idx) {
        if(!property_exists($this->setting, $idx)) $this->setting->$idx = new stdClass();
        return $this->setting->$idx;
    }

    /**
     * 功能说明：直接设置顶层配置，不套用表单列表及密码转换
     *
     * @param string $idx 顶层配置名
     * @param mixed $value 新值；直接赋入对象时调用方自行管理其引用
     * @return void 无返回值。
     */
    public function __set($idx, $value) {
        $this->setting->$idx = $value;
    }

    /**
     * 功能说明：检查顶层配置是否存在且值非 null
     *
     * @param string $idx 顶层配置名
     * @return bool 遵循 PHP isset 语义；区分缺失和 null 时使用 value() 或 get()
     */
    public function __isset($idx) { return isset($this->setting->$idx); }

    /**
     * 功能说明：删除顶层配置项，仅修改内存，持久化需另行 save()
     *
     * @param string $idx 顶层配置名，不解释为点分隔路径
     * @return void 无返回值。
     */
    public function __unset($idx) { unset($this->setting->$idx); }

    /**
     * 功能说明：复制嵌套配置内容，避免克隆实例共享分组对象
     *
     * 参数说明：无显式参数。
     *
     * @return void 文件路径、原内容版本及结构元数据仍沿用被克隆实例
     */
    public function __clone() { $this->setting = self::a2o(self::o2a($this->setting)); }

    /**
     * 功能说明：无副作用地读取点分隔配置路径，保留已存在的 null
     *
     *
     * 调用示例：
     *     $port = $config->value('db.port', 3306); // 不创建缺失分组
     *
     * @param string $path 例如 db.port；空字符串返回整个内部配置对象，点号作为分隔符
     * @param mixed $default 任一路径段不存在时的返回值，默认 null（默认：null）
     * @return mixed 已存在的配置值或默认值；返回对象仍是内部对象，不是副本
     * @throws InvalidArgumentException 路径不是字符串
     */
    public function value($path, $default = null) {
        if(!is_string($path)) throw new InvalidArgumentException('Configuration path must be a string');
        $value = $this->setting;
        if($path === '') return $value;
        foreach(explode('.', $path) as $key) {
            if(is_object($value) && property_exists($value, $key)) $value = $value->$key;
            elseif(is_array($value) && array_key_exists($key, $value)) $value = $value[$key];
            else return $default;
        }
        return $value;
    }

    /**
     * 功能说明：将当前设置递归转换后编码为 JSON，便于展示及与脚本交互
     *
     * 参数说明：无显式参数。
     *
     * @return string myString::toJson() 生成的文本；文件保存使用 save() 的编码流程
     */
    public function __toString() {
        return myString::toJson(self::o2a($this->setting));
    }

    /**
     * 功能说明：递归将数组及对象的可遍历属性复制到新的分组对象
     *
     * @param mixed $arr 待转换的数组、对象或标量；对象只复制可遍历属性，不保留原类
     * @return mixed 容器转换为 stdClass；null、数字、布尔及文本按原类型返回
     */
    public static function a2o($arr) {
        if(!is_array($arr) && !is_object($arr)) return $arr;
        $result = new stdClass();
        foreach($arr as $key => $value) $result->{(string)$key} = self::a2o($value);
        return $result;
    }

    /**
     * 功能说明：递归将分组对象及数组复制为数组，不猜测标量内容类型
     *
     * @param mixed $obj 分组对象、数组或标量
     * @return mixed 容器转为数组，嵌套列表保留数字键；标量及 null 原样返回
     */
    public static function o2a($obj) {
        if(!is_object($obj) && !is_array($obj)) return $obj;
        $result = [];
        foreach($obj as $key => $value) $result[$key] = self::o2a($value);
        return $result;
    }

    /**
     * 功能说明：兼容历史表单/INI 的简单类型恢复，不用于 PHP/JSON 类型推断
     *
     * @param mixed $v 数组转为逗号分隔文本；大小写不敏感的 true/false 文本转为布尔
     * @return mixed 转换结果；其他标量不变，不自动把数字文本转为数字
     */
    public static function recover($v) {
        // Compatibility helper only; typed PHP/JSON conversion never guesses scalar types.
        if(is_array($v)) return implode(',', $v);
        if(is_string($v)) {
            if(strtolower($v) === 'true') return true;
            if(strtolower($v) === 'false') return false;
        }
        return $v;
    }

    /**
     * 功能说明：登记自定义扩展名的加载及保存回调
     *
     * @param string $ext 扩展名，不含点号，建议使用小写以匹配 load()/save() 的格式名
     * @param callable $func_load 接收原文件文本，须返回数组或对象
     * @param callable $func_save 接收完整配置数组，须返回待写入字符串
     * @return void 名称为空或任一回调不可调用时不登记
     * @phpstan-param callable(string): (array|object) $func_load
     * @phpstan-param callable(array): string $func_save
     */
    public  function setParser($ext, $func_load, $func_save) {
        if(!empty($ext) && is_callable($func_load) && is_callable($func_save)) {
            $this->parser[$ext] = [
                'load' => $func_load,
                'save' => $func_save
            ];
        }
    }

    /**
     * 功能说明：编译并缓存表单结构元数据，只有生成内容改变时才原子写入
     *
     * 提取 checkbox/switch/password 等字段的点分隔路径及密码变换回调。
     * 直接传入 construction.php 时加载既有元数据；定义文件与元数据须为可信 PHP。
     *
     * @param string $file 设置结构定义路径，或可直接 include 返回数组的 construction.php
     * @return void 更新实例 schema 及 construction；找不到可加载结构时保持当前状态
     * @throws UnexpectedValueException 定义或元数据不是数组
     * @throws RuntimeException 派生结构文件无法原子保存
     */
    public function makeConstruction($file) {
        $construction_file = dirname($file).'/construction.php';
        if(basename($file) === 'construction.php' || !is_file($file)) {
            if(is_file($construction_file)) {
                $schema = include($construction_file);
                if(!is_array($schema)) throw new UnexpectedValueException('Invalid configuration construction');
                $this->schema = $schema; $this->construction = $construction_file;
            }
            return;
        }
        if(function_exists('opcache_invalidate')) opcache_invalidate($file, true);
        $detail = include($file);
        if(!is_array($detail)) throw new UnexpectedValueException('Invalid configuration schema');
        $construction = [];
        $walk = function(array $items, $prefix = '') use (&$walk, &$construction) {
            foreach($items as $key => $item) {
                if(!is_array($item)) continue;
                $name = $prefix.(string)$key;
                if(isset($item['list']) && is_array($item['list'])) { $walk($item['list'], $name.'.'); continue; }
                $type = $item['type'][0] ?? null;
                if(!is_string($type)) continue;
                $construction[$type][] = $name;
                if($type === 'password' && !empty($item['type'][1])) $construction[$type]['func'][$name] = $item['type'][1];
            }
        };
        if(isset($detail['name'], $detail['list'])) $walk($detail['list']);
        else $walk($detail);
        $this->schema = $construction; $this->construction = $construction_file;
        $old = is_file($construction_file) ? include($construction_file) : null;
        if($old !== $construction) $this->atomicWrite($construction_file, '<?PHP'."\n".myString::toScript($construction, 'construction').'return $construction;');
    }

    /**
     * 功能说明：按文件格式读取配置，并返回本次稳定读取的内容摘要
     *
     * 读取前后比较文件摘要，最多重试三次；PHP 在独立作用域 include，JSON 严格解码，
     * INI 保留兼容类型恢复。首次发现文件不存在返回空配置，不自动保存或替换实例设置。
     *
     * @param string $file 配置路径；空字符串使用实例绑定文件（默认：''）
     * @param bool $return_object true 将结果转换为分组对象；false 保留解析器结果（默认：false）
     * @param string|null &$snapshot 引用参数；输出稳定读取内容的 SHA-256 摘要；初始文件不存在为 null（默认：null）
     * @return array|object 配置根节点，具体容器取决于格式及 return_object
     * @throws InvalidArgumentException 文件名类型或格式不支持
     * @throws UnexpectedValueException 解析结果根节点不是数组或对象
     * @throws JsonException JSON 内容非法
     * @throws RuntimeException 文件在读取中消失或持续变化
     * @throws Throwable 自定义解析器、初始化/反射目标或受信执行文件产生的异常继续传递
     */
    public function load($file='', $return_object=false, &$snapshot = null) {
        if($file === '') $file = $this->file;
        if(!is_string($file)) throw new InvalidArgumentException('Configuration filename must be a string');
        $result = []; $snapshot = null;
        for($attempt=0; $attempt<3; $attempt++) {
            clearstatcache(true, $file);
            if(!is_file($file)) { if($attempt > 0) throw new RuntimeException('Configuration disappeared while reading'); break; }
            $before = hash_file('sha256', $file);
            $type = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            switch($type) {
                case 'php':
                    if(function_exists('opcache_invalidate')) opcache_invalidate($file, true);
                    $result = (static function($path) { return include($path); })($file);
                    break;
                case 'ini':
                    $result = myString::fromIni($file, true);
                    if(is_array($result)) array_walk_recursive($result, function(&$value) { $value = self::recover($value); });
                    break;
                case 'json':
                    $result = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
                    break;
                default:
                    if(!isset($this->parser[$type])) throw new InvalidArgumentException('Unsupported configuration format');
                    $result = call_user_func($this->parser[$type]['load'], file_get_contents($file));
            }
            if(!is_array($result) && !is_object($result)) throw new UnexpectedValueException('Configuration root must be an array or object');
            clearstatcache(true, $file);
            if(is_file($file) && $before === hash_file('sha256', $file)) { $snapshot = $before; break; }
            if($attempt === 2) throw new RuntimeException('Configuration changed while reading');
        }
        return $return_object ? self::a2o($result) : $result;
    }

    /**
     * 功能说明：递归合并分组配置，同名标量覆盖，分组可替换原标量
     *
     * 输入数组/对象会复制为分组对象，避免把其嵌套引用直接挂入当前配置。
     *
     * @param array|object|string $setting 待覆盖的设置或配置文件路径；不存在的文件跳过
     * @param object|null &$org 合并目标，null 使用实例设置；引用参数供递归及指定目标使用（默认：null）
     * @return void 只更新内存，不保存文件或改变原文件的版本基准
     * @throws InvalidArgumentException 输入不是数组、对象或文件路径
     */
    public function merge($setting, &$org = null) {
        if(is_string($setting)) {
            if(!is_file($setting)) return;
            $setting = $this->load($setting, true);
        } elseif(is_array($setting) || is_object($setting)) $setting = self::a2o($setting);
        else throw new InvalidArgumentException('Configuration overlay must be a map or filename');
        if($org === null) $org = $this->setting;
        if(!is_object($org)) $org = new stdClass();
        foreach($setting as $key => $value) {
            if(is_object($value)) {
                if(!property_exists($org, $key) || !is_object($org->$key)) $org->$key = new stdClass();
                $this->merge($value, $org->$key);
            } else $org->$key = $value;
        }
    }

    /**
     * 功能说明：按表单规则批量更新设置，保留原值的明确类型及未提交分组
     *
     * 只补齐已提交分组内缺失的 checkbox/switch；选择列表转 CSV。空密码不覆盖原值，
     * 字符串 0 可更新密码，已登记的变换回调继续执行；忽略以 _pwd_r 结束的确认字段。
     *
     * @param array|object $setting 旧式 setting 表单结构；嵌套映射表示分组，列表表示多选
     * @param string $idx 目标分组的点分隔路径；空字符串从根开始，缺失分组自动创建（默认：''）
     * @param bool $check 是否补齐本次提交范围内缺失的复选框/开关，递归调用时为 false（默认：true）
     * @return stdClass 本次更新的目标分组对象，修改尚未持久化
     * @throws InvalidArgumentException 数据不是映射、选择值不是标量、密码或类型转换非法
     */
    public function set($setting, $idx = '', $check = true) {
        if(is_object($setting)) $setting = self::o2a($setting);
        if(!is_array($setting)) throw new InvalidArgumentException('Configuration updates must be an array');
        $construction = $this->schema;
        if($check) foreach(['checkbox'=>[], 'switch'=>''] as $kind => $empty) {
            foreach($construction[$kind] ?? [] as $path) {
                if(!is_string($path)) continue;
                $relative = $path;
                if($idx !== '') {
                    if(strpos($path, $idx.'.') !== 0) continue;
                    $relative = substr($path, strlen($idx)+1);
                }
                $keys = explode('.', $relative);
                if(count($keys)>1 && !array_key_exists($keys[0], $setting)) continue;
                $item =& $setting;
                foreach($keys as $offset => $key) {
                    if($offset === count($keys)-1) { if(!array_key_exists($key, $item)) $item[$key] = $empty; }
                    else { if(!isset($item[$key]) || !is_array($item[$key])) $item[$key] = []; $item =& $item[$key]; }
                }
                unset($item);
            }
        }
        $item = $this->setting;
        if($idx !== '') foreach(explode('.', $idx) as $key) {
            if(!property_exists($item, $key) || !is_object($item->$key)) $item->$key = new stdClass();
            $item = $item->$key;
        }
        $prefix = $idx === '' ? '' : $idx.'.';
        foreach($setting as $key => $value) {
            if(preg_match('/_pwd_r$/', (string)$key)) continue;
            $path = $prefix.$key;
            if(is_array($value)) {
                if(in_array($path, $construction['checkbox'] ?? [], true) || in_array($path, $construction['switch'] ?? [], true) || isset($value[0]) || $value === []) {
                    foreach($value as $entry) if(!is_scalar($entry) && $entry !== null) throw new InvalidArgumentException('Form choices must be scalar values');
                    $value = implode(',', $value);
                } else { $this->set($value, $path, false); continue; }
            }
            if(in_array($path, $construction['password'] ?? [], true)) {
                if($value === '' || $value === null) continue;
                if(!is_string($value)) throw new InvalidArgumentException('Configuration passwords must be strings');
                $callback = $construction['password']['func'][$path] ?? null;
                if($callback !== null) {
                    if(!is_callable($callback)) throw new InvalidArgumentException('Invalid password transformation');
                    $value = call_user_func($callback, $value);
                }
            } else $value = $this->formValue($value, property_exists($item, (string)$key) ? $item->$key : null);
            $item->$key = $value;
        }
        return $item;
    }

    /**
     * 功能说明：依据已有字段的明确类型转换表单文本，不猜测文本字段类型
     *
     * @param mixed $value 新表单值；非字符串原样返回
     * @param mixed $current 旧值；布尔使用 FILTER_VALIDATE_BOOLEAN，数字类型在符合规则时转换
     * @return mixed 转换值；旧值为文本或 null 时保留前导零及 true/false 字面文本
     * @throws InvalidArgumentException 布尔文本不合法或整数超出允许转换范围
     */
    protected function formValue($value, $current) {
        if(!is_string($value)) return $value;
        if(is_bool($current)) {
            $boolean = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if($boolean === null) throw new InvalidArgumentException('Invalid boolean configuration value');
            return $boolean;
        }
        if(is_int($current) && preg_match('/\A[+-]?\d+\z/D', $value)) {
            $number = filter_var($value, FILTER_VALIDATE_INT);
            if($number === false && !preg_match('/\A[+-]?0*\d{1,18}\z/D', $value)) throw new InvalidArgumentException('Configuration integer out of range');
            return (int)$value;
        }
        if(is_float($current) && is_numeric($value) && is_finite((float)$value)) return (float)$value;
        return $value;
    }

    /**
     * 功能说明：编码完整配置并原子保存，默认防止原文件被并发更新覆盖
     *
     * 先编码后写入，编码失败保留原文件。保存原文件时比较加载摘要，成功后更新本实例
     * 版本；显式导出其他路径不套用原文件版本检查。新文件权限 0600，既有权限保留。
     *
     *
     * 调用示例：
     *     try {
     *         $config->save();
     *     } catch (RuntimeException $error) {
     *         // 重新加载并处理写入或版本冲突
     *     }
     *
     * @param string $type php/ini/json 或已登记格式；空字符串沿用原扩展名（默认：''）
     * @param string $file 目标路径；空字符串保存原路径，切换格式时自动替换原扩展名（默认：''）
     * @param bool $overwrite true 明确允许覆盖原文件的并发修改；默认 false 拒绝丢失更新（默认：false）
     * @return int 写入的完整字节数
     * @throws InvalidArgumentException 格式未登记
     * @throws UnexpectedValueException 编码器没有返回字符串（包括 JSON 编码失败）
     * @throws RuntimeException 目标不合法、无法加锁/写入/替换或内容版本冲突
     * @throws Throwable 自定义解析器、初始化/反射目标或受信执行文件产生的异常继续传递
     */
    public function save($type='', $file = '', $overwrite = false) {
        $type = strtolower($type === '' ? $this->type : $type);
        $setting = self::o2a($this->setting);
        switch($type) {
            case 'php': $result = '<?PHP'."\n".myString::toScript($setting, 'setting').'return $setting;'; break;
            case 'ini': $result = myString::toIni($setting); break;
            case 'json': $result = json_encode($setting, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION); break;
            default:
                if(!isset($this->parser[$type])) throw new InvalidArgumentException('Unsupported configuration format');
                $result = call_user_func($this->parser[$type]['save'], $setting);
        }
        if(!is_string($result)) throw new UnexpectedValueException('Configuration encoder must return a string');
        if($file === '') $file = $type === $this->type ? $this->file : preg_replace('/\.[^.\/]+$/', '.'.$type, $this->file);
        $file = myFile::realPath($file);
        $original = $file === $this->file;
        $written = $this->atomicWrite($file, $result, $this->revision, $original && !$overwrite);
        if($original) $this->revision = hash('sha256', $result);
        return $written;
    }

    /**
     * 功能说明：锁定目标旁的锁文件，再以同目录临时文件原子发布完整内容
     *
     * 版本检查在锁内进行；目标已经等于待保存内容也可成功。拒绝目标和锁文件为
     * 符号链接，保留已有权限，新文件 0600；清理临时文件并使 OPcache 失效。
     *
     * @param string $file 目标文件路径，父目录不存在时尝试创建
     * @param string $content 已完整编码的内容
     * @param string|null $expected 预期现有内容的 SHA-256 摘要；null 表示预期尚不存在（默认：null）
     * @param bool $check true 比较内容版本；false 不检查（用于导出及派生结构）（默认：false）
     * @return int 完整内容的字节数
     * @throws RuntimeException 目标不合法、锁定/写入/权限设置/替换失败或版本冲突
     */
    protected function atomicWrite($file, $content, $expected = null, $check = false) {
        if(!myFile::mkdir(dirname($file)) || is_link($file) || is_link($file.'.lock')) throw new RuntimeException('Invalid configuration destination');
        $lock = fopen($file.'.lock', 'c');
        if(!$lock) throw new RuntimeException('Cannot open configuration lock');
        $temporary = null;
        try {
            if(!flock($lock, LOCK_EX)) throw new RuntimeException('Cannot lock configuration');
            clearstatcache(true, $file);
            $current = is_file($file) ? hash_file('sha256', $file) : null;
            if($check && $current !== $expected && $current !== hash('sha256', $content)) throw new RuntimeException('Configuration changed; reload before saving');
            $mode = is_file($file) ? fileperms($file) & 0777 : 0600;
            $temporary = tempnam(dirname($file), '.config-');
            if($temporary === false || file_put_contents($temporary, $content) !== strlen($content) || !chmod($temporary, $mode) || !rename($temporary, $file)) throw new RuntimeException('Cannot publish configuration');
            if(function_exists('opcache_invalidate')) opcache_invalidate($file, true);
            return strlen($content);
        } finally {
            if(is_string($temporary) && is_file($temporary)) unlink($temporary);
            flock($lock, LOCK_UN); fclose($lock);
        }
    }

    /**
     * 功能说明：按可信结构定义生成设置表单项目及分组标题
     *
     * 实际配置值按 UTF-8 HTML 属性规则转义，保留反斜杠；选项在转义前比较。
     * 名称、说明及结构标记来自可信定义，不把整个结构视为不可信 HTML 的过滤器。
     *
     * @param array|string $detail 结构数组或返回结构数组的 PHP 文件路径
     * @param string|array $idx 分组路径；文件模式也可传扩展设置数组作为第二参数（默认：''）
     * @param array $ext_setting 分组到字段的 type 定义覆盖，仅在文件加载模式应用（默认：array()）
     * @return array|false 含 name/describe/html 的控件或 name/idx 分组标题列表；缺文件为 false
     */
    public function build($detail, $idx = '', $ext_setting = array()) {
        if(is_array($detail)) {
            $setting_detail = $detail;
        } else {
            if(!is_file($detail)) {
                $this->error('Cannot load the setting file ('.$detail.')');
                return false;
            }
            $setting_detail = include($detail);
            if(is_array($idx)) {
                $ext_setting = $idx;
                $idx = '';
            }
            foreach($ext_setting as $k1 => $v1) {
                foreach($v1 as $k2 => $v2) {
                    if(isset($setting_detail[$k1]['list'][$k2]['type'])) $setting_detail[$k1]['list'][$k2]['type'] = $v2;
                }
            }
        }
        if(!empty($idx)) $idx .='.';

        if(isset($setting_detail['name']) && is_string($setting_detail['name'])) {
            $setting_detail = ['single_setting'=>$setting_detail];
        }

        $result = array();
        foreach($setting_detail as $k => $v) {
            $cur_idx = $idx.$k;
            if(isset($v['list'])) {
                $result[] = ['name' => $v['name'], 'idx' => $cur_idx];
                $result = array_merge($result, $this->build($v['list'], $cur_idx));
                continue;
            }
            $item = array(
                'name' => $v['name'],
                'describe' => $v['describe'],
            );
            $keys = explode('.', $cur_idx);
            $the_value = $this->setting;
            foreach($keys as $key) {
                if($key=='single_setting') continue;
                $the_value = $the_value->$key ?? '';
            }
            $raw_typed_value = $the_value;
            $raw_value = is_null($the_value) ? '' : myString::fromAny($the_value);
            $the_value = htmlspecialchars($raw_value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $k = str_replace('.', "][", $cur_idx);
            $k = str_replace('single_setting][', '', $k);
            switch($v['type'][0]) {
                case 'text':
                    $item['html'] = '<input type="text" name="setting['.$k.']" value="'.$the_value.'" len="'.$v['type'][2].'"'.($v['type'][1]===false?'':(' need="'.$v['type'][1].'"')).' />';
                    break;
                case 'textarea':
                    $item['html'] = '<textarea name="setting['.$k.']" wrap="off" rows="'.$v['type'][2].'" '.($v['type'][1]===false?'':(' need="'.$v['type'][1].'"')).' >'.$the_value.'</textarea>';
                    break;
                case 'password':
                    $item['describe'] = '';
                    $item['html'] = '<input type="password" id="'.str_replace('][', '_', $k).'" name="setting['.$k.']" value="" len="'.$v['type'][2].'" />';
                    $result[] = $item;
                    $item = array(
                        'name' => $v['name2'],
                        'describe' => $v['describe'],
                    );
                    $item['html'] = '<input type="password" id="'.str_replace('][', '_', $k).'_r" name="setting['.$k.'_pwd_r]" value="" len="'.$v['type'][2].'" />';
                    break;
                case 'checkbox':
                    $cur_component = '';
                    $the_value = explode(',', $raw_value);
                    foreach($v['type'][1] as $k_c => $v_c) {
                        $v_c = myString::fromAny($v_c);
                        $checked = array_search($v_c, $the_value, true)!==false ?'checked':'';
                        $v_c = htmlspecialchars($v_c, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                        $cur_component .= '<label><input type="checkbox" name="setting['.$k.'][]" value="'.$v_c.'" '.$checked.' /> '.$k_c.'</label>'.chr(10);
                    }
                    $item['html'] = $cur_component;
                    break;
                case 'switch':
                    $checked = !empty($raw_typed_value) ?'checked':'';
                    if(!isset($v['type'][2])) $v['type'][2] = '';
                    $item['html'] = '<div type="switch"><input type="checkbox" id="setting['.$k.'][]" name="setting['.$k.'][]" value="'.$v['type'][1].'" '.$checked.' /><label for="setting['.$k.'][]">'.$v['type'][2].'</label></div>'.chr(10);
                    break;
                case 'radio':
                    $cur_component = '';
                    foreach($v['type'][1] as $k_r => $v_r) {
                        $v_r = myString::fromAny($v_r);
                        $checked = ($raw_value==$v_r)?'checked':'';
                        $v_r = htmlspecialchars($v_r, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                        $cur_component .= '<label><input type="radio" name="setting['.$k.']" value="'.$v_r.'" '.$checked.' /> '.$k_r.'</label> &nbsp; &nbsp;'.chr(10);
                    }
                    $item['html'] = $cur_component;
                    break;
                case 'select':
                    $cur_component = '<select name="setting['.$k.']">'.chr(10);
                    end($v['type'][1]);
                    $mode = key($v['type'][1]) == count($v['type'][1])-1;
                    foreach($v['type'][1] as $k_s => $v_s) {
                        $v_s = myString::fromAny($v_s);
                        if($mode) $k_s = $v_s;
                        $checked = ($raw_value==$v_s)?'selected':'';
                        $v_s = htmlspecialchars($v_s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                        $cur_component .= '<option value="'.$v_s.'" '.$checked.'>'.$k_s.'</option>'.chr(10);
                    }
                    $cur_component .= "</select>";
                    $item['html'] = $cur_component;
                    break;
                default:
                    $item['html'] = '<input name="setting['.$k.']" value="'.$the_value.'" />';
            }
            //$item['html'] = str_replace('[single_setting]', '', $item['html']);
            $result[] = $item;
        }
        return $result;
    }

}