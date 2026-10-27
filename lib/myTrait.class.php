<?PHP

/**
 * 文件：lib/myTrait.class.php
 * 功能说明：动态属性、方法扩展与别名公共trait
 *   提供辅助属性、扩展方法登记、别名解析和可恢复调用状态。
 *
 * 方法目录：
 *   public myTrait::__get($para) — 读取辅助属性，并通过 instance 新建同类实例
 *   public myTrait::__set($para, $value) — 写入辅助属性，不以外部魔术调用修改受保护/私有成员
 *   public myTrait::__destruct() — 释放扩展方法及辅助属性引用，其他成员由 PHP 正常释放
 *   public myTrait::__isset($para) — 按 PHP isset 约定检查辅助属性
 *   public myTrait::__unset($para) — 移除辅助属性，不删除对象自身受保护成员
 *   protected static myTrait::extensionAliases($instance = null) — 汇总实例/静态默认别名与实际类的扩展别名，统一为小写索引
 *   protected static myTrait::resolveExtensionMethod($func, array $aliases) — 解析多级别名并拒绝循环，避免魔术分发无限递归
 *   public myTrait::__call($func, array $args) — 按别名、实例扩展、历史全局函数回退的顺序处理未知方法
 *   public static myTrait::__callStatic($func, $args) — 解析静态别名或历史全局函数回退，不创建实例、不调用实例方法
 *   public myTrait::__sleep() — 返回全部实际实例属性，包含父类私有状态，不包含静态表
 *   public myTrait::__wakeup() — 恢复序列化状态，不重新调用构造函数或 init()
 *   public myTrait::__toString() — 兼容旧对象文本快照，无法序列化调试状态时回退类名
 *   public myTrait::__invoke($para) — 兼容对象函数式调用：文本生成扰动摘要，数值生成确定整数，映射批量赋值
 *   public static myTrait::__set_state($para_arr) — 从 var_export() 状态重建实际调用类，不触发构造及初始化副作用
 *   public myTrait::__debugInfo() — 返回可供 var_dump() 使用的实例快照，不读取静态属性或再次触发魔术 getter
 *   public myTrait::addMethod($func, $name='') — 登记实例扩展方法，支持闭包、函数名、方法数组及可调用对象
 *   public static myTrait::regAlias($alias = []) — 原子合并类方法别名，支持未声明静态 func_alias 的普通 trait 使用类
 *
 * 使用说明：
 *   1. 由myProxy等类使用；静态/实例扩展和别名按登记范围分离。
 *   2. 依赖声明元数据的调用应通过对应入口，避免把魔术方法存在当作实际能力。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名以PHP 8.5.12为运行基线，PHPDoc类型用于说明，不额外强制运行时类型。
 */
/**
 * 功能说明：提供辅助属性、扩展方法登记、别名解析和可恢复调用状态。
 *
 * 使用说明：
 *   1. 由myProxy等类使用；静态/实例扩展和别名按登记范围分离。
 *   2. 依赖声明元数据的调用应通过对应入口，避免把魔术方法存在当作实际能力。
 *
 * 补充说明：
 * 通用魔术扩展：辅助属性、实例方法、类方法别名及对象状态展示
 *
 *     $object->addMethod(function ($v) { return $this->value + $v; }, 'sum');
 *     ClassName::regAlias(['short' => 'fullName']); // 多级别名会先校验循环
 *     $fresh = $object->instance;                 // 新建实际调用类，不复制当前状态
 *
 * 扩展方法优先于历史全局函数回退，别名优先于扩展方法；全局函数回退为兼容保留，
 * 不能把未知方法名直接视为 API 白名单。序列化只保存实例状态，恢复时不重跑构造；
 * 闭包及资源仍受 PHP 原生序列化限制，含这些内容的对象不承诺可持久化。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 *
 * @property-read object $instance 新建实际调用类实例；是否自动初始化遵循该类构造约定
 */
trait myTrait {
    /** @var array 实例扩展方法，键为规范化的方法名 */
    protected $methods = [];
    /** @var array 魔术属性的辅助存储 */
    protected $vars = [];
    /** @var array 没有静态 func_alias 表时，按实际类隔离的别名覆盖 */
    protected static $extension_aliases = [];

    /**
     * 功能说明：读取辅助属性，并通过 instance 新建同类实例
     *
     * @param string $para 属性名；instance 新建实际调用类，不复制当前状态
     * @return mixed 辅助属性值（保留 null）或新实例；不存在时为 null
     */
    public function __get($para) {
        if($para === 'instance') { $class = get_class($this); return new $class(); }
        return array_key_exists($para, $this->vars) ? $this->vars[$para] : null;
    }

    /**
     * 功能说明：写入辅助属性，不以外部魔术调用修改受保护/私有成员
     *
     * @param string $para 辅助属性名
     * @param mixed $value 保存的值
     * @return void 无返回值。
     */
    public function __set($para, $value) { $this->vars[$para] = $value; }

    /**
     * 功能说明：释放扩展方法及辅助属性引用，其他成员由 PHP 正常释放
     *
     * 参数说明：无显式参数。
     *
     * @return void 初始化或释放资源，无返回值。
     */
    public function __destruct() { $this->methods = []; $this->vars = []; }

    /**
     * 功能说明：按 PHP isset 约定检查辅助属性
     *
     * @param string $para 属性名；不向外暴露受保护成员的存在状态
     * @return bool 辅助属性存在且非 null 时为 true
     */
    public function __isset($para) { return isset($this->vars[$para]); }

    /**
     * 功能说明：移除辅助属性，不删除对象自身受保护成员
     *
     * @param string $para 辅助属性名
     * @return void 无返回值。
     */
    public function __unset($para) { unset($this->vars[$para]); }

    /**
     * 功能说明：汇总实例/静态默认别名与实际类的扩展别名，统一为小写索引
     *
     * @param object|null $instance 实例调用时传入对象，静态调用为 null（默认：null）
     * @return array 方法名到目标方法名的映射，不改写静态表为实例属性
     * @throws UnexpectedValueException 已声明 func_alias 不是数组
     */
    protected static function extensionAliases($instance = null) {
        $class = $instance === null ? get_called_class() : get_class($instance);
        $aliases = [];
        if(property_exists($class, 'func_alias')) {
            $property = new ReflectionProperty($class, 'func_alias');
            if($property->isStatic()) $aliases = $property->getValue();
            elseif($instance !== null) $aliases = $property->getValue($instance);
            else $aliases = (new ReflectionClass($class))->getDefaultProperties()['func_alias'] ?? [];
            if(!is_array($aliases)) throw new UnexpectedValueException('Method aliases must be an array');
        }
        $aliases = array_change_key_case($aliases, CASE_LOWER);
        foreach(array_reverse(array_values(class_parents($class))) as $parent) if(isset(self::$extension_aliases[$parent])) $aliases = array_replace($aliases, self::$extension_aliases[$parent]);
        return array_replace($aliases, self::$extension_aliases[$class] ?? []);
    }

    /**
     * 功能说明：解析多级别名并拒绝循环，避免魔术分发无限递归
     *
     * @param string $func 原始方法名
     * @param array $aliases 小写索引别名表
     * @return string 最终目标方法名；没有别名时保留原名称
     * @throws LogicException 别名表存在运行时引入的循环或非法目标
     */
    protected static function resolveExtensionMethod($func, array $aliases) {
        $seen = [];
        while(array_key_exists(strtolower($func), $aliases)) {
            $key = strtolower($func);
            if(isset($seen[$key]) || !is_string($aliases[$key]) || $aliases[$key] === '') throw new LogicException('Invalid or cyclic method alias');
            $seen[$key] = true; $func = $aliases[$key];
        }
        return $func;
    }

    /**
     * 功能说明：按别名、实例扩展、历史全局函数回退的顺序处理未知方法
     *
     * @param string $func 方法名；别名和新登记扩展按大小写不敏感匹配
     * @param array $args 按原顺序传递的位置参数
     * @return mixed 目标结果；没有可调用目标时沿用旧行为返回 null
     * @throws LogicException 别名循环；业务回调异常继续传递
     */
    public function __call($func, array $args) {
        $target = static::resolveExtensionMethod($func, static::extensionAliases($this));
        if($target !== $func && is_callable([$this, $target])) return call_user_func_array([$this, $target], $args);
        $key = strtolower($target);
        if(isset($this->methods[$key])) return call_user_func_array($this->methods[$key], $args);
        // 兼容旧序列化对象中保留的大小写方法键。
        if(isset($this->methods[$target])) return call_user_func_array($this->methods[$target], $args);
        if(is_callable($target)) return call_user_func_array($target, $args);
        if(is_callable('\\'.$target)) return call_user_func_array('\\'.$target, $args);
        return null;
    }

    /**
     * 功能说明：解析静态别名或历史全局函数回退，不创建实例、不调用实例方法
     *
     * @param string $func 静态方法名或别名
     * @param array $args 位置参数
     * @return mixed 静态方法/全局函数结果；没有目标时为 null
     * @throws BadMethodCallException 静态别名指向实例方法或不可访问的方法
     * @throws LogicException 运行时别名表包含循环
     */
    public static function __callStatic($func, $args) {
        $class = get_called_class(); $target = static::resolveExtensionMethod($func, static::extensionAliases());
        if($target !== $func && method_exists($class, $target)) {
            $method = new ReflectionMethod($class, $target);
            if(!$method->isStatic() || !is_callable([$class, $target])) throw new BadMethodCallException('Static alias requires an accessible static method');
            return forward_static_call_array([$class, $target], $args);
        }
        if(is_callable($target)) return call_user_func_array($target, $args);
        if(is_callable('\\'.$target)) return call_user_func_array('\\'.$target, $args);
        return null;
    }

    /**
     * 功能说明：返回全部实际实例属性，包含父类私有状态，不包含静态表
     *
     * 参数说明：无显式参数。
     *
     * @return array 原始属性键列表；闭包仍由原生 serialize() 明确拒绝，不静默丢弃
     */
    public function __sleep() { return array_keys((array)$this); }

    /**
     * 功能说明：恢复序列化状态，不重新调用构造函数或 init()
     *
     * 参数说明：无显式参数。
     *
     * @return void 需要恢复连接等外部资源的类应自行覆盖本方法
     */
    public function __wakeup() {}

    /**
     * 功能说明：兼容旧对象文本快照，无法序列化调试状态时回退类名
     *
     * 参数说明：无显式参数。
     *
     * @return string 序列化的 name/vars/methods 信息，或类名；不会因闭包抛出转换异常
     */
    public function __toString() {
        try { return serialize($this->__debugInfo()); }
        catch(Throwable $error) { return get_class($this); }
    }

    /**
     * 功能说明：兼容对象函数式调用：文本生成扰动摘要，数值生成确定整数，映射批量赋值
     *
     * @param mixed $para 字符串、数字、数组/对象；其他值返回调用栈（不含参数）
     * @return string|int|bool|array 对应计算结果；本方法不重置或消耗 mt_rand 全局状态
     *
     * 映射批量赋值保留当前作用域可写成员的旧语义，只接受受信字段；本类不提供权限隔离。
     * 文本结果不是认证令牌；数值结果的算法与旧 mt_srand 版本不同，不用于持久标识。
     */
    public function __invoke($para) {
        if(is_string($para)) {
            $chars = str_split(md5($para));
            for($i=count($chars)-1; $i>0; $i--) { $j = random_int(0, $i); $tmp = $chars[$i]; $chars[$i] = $chars[$j]; $chars[$j] = $tmp; }
            return implode('', $chars);
        }
        if(is_int($para) || is_float($para)) return hexdec(substr(hash('sha256', serialize($para)), 0, 8)) & 0x7fffffff;
        if(is_array($para) || is_object($para)) { foreach($para as $key => $value) $this->$key = $value; return true; }
        return debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    }

    /**
     * 功能说明：从 var_export() 状态重建实际调用类，不触发构造及初始化副作用
     *
     * @param array $para_arr 实例属性映射；静态属性不恢复，声明属性通过反射写入
     * @return object 当前实际调用类的新实例
     * @throws Throwable 类不可实例化或属性类型不匹配等异常继续传递
     */
    public static function __set_state($para_arr) {
        if(!is_array($para_arr)) throw new InvalidArgumentException('Object state must be an array');
        $reflection = new ReflectionClass(get_called_class()); $instance = $reflection->newInstanceWithoutConstructor();
        foreach($para_arr as $key => $value) {
            if(!is_string($key)) throw new InvalidArgumentException('Object state keys must be property names');
            $scope = $reflection; $property = null;
            do { if($scope->hasProperty($key)) { $property = $scope->getProperty($key); break; } } while($scope = $scope->getParentClass());
            if($property !== null) { if(!$property->isStatic()) { $property->setValue($instance, $value); } }
            else $instance->$key = $value;
        }
        return $instance;
    }

    /**
     * 功能说明：返回可供 var_dump() 使用的实例快照，不读取静态属性或再次触发魔术 getter
     *
     * 参数说明：无显式参数。
     *
     * @return array 包含 name、当前作用域可见的实例 vars、可见 methods；不要记录秘密数据
     */
    public function __debugInfo() { return ['name'=>get_class($this), 'vars'=>get_object_vars($this), 'methods'=>get_class_methods($this)]; }

    /**
     * 功能说明：登记实例扩展方法，支持闭包、函数名、方法数组及可调用对象
     *
     * 未绑定的非静态普通闭包绑定到当前实例及其作用域；静态/已绑定闭包及其他回调保持身份，
     * 不把函数名/数组传入 Closure::bind()，也不强制改绑其他对象的方法。
     *
     *
     * 回调参数由扩展方法自身定义，__call按调用位置顺序传入并返回原值。普通未绑定闭包可读取当前$this，其他可调用对象保持原绑定。
     *
     * @param callable $func 新方法实现，非静态普通闭包可使用当前 $this
     * @param string $name 扩展名称；函数名/方法数组可省略，闭包/可调用对象须明确命名（默认：''）
     * @return $this 当前对象，登记失败不改写已有方法
     * @throws InvalidArgumentException 不可调用、名称非法
     */
    public function addMethod($func, $name='') {
        if(!is_callable($func)) throw new InvalidArgumentException('The function must be callable');
        if($name === '') {
            if(is_string($func)) {
                $name = strpos($func, '::') === false ? $func : substr($func, strrpos($func, '::')+2);
                if(strpos($name, '\\') !== false) $name = substr($name, strrpos($name, '\\')+1);
            }
            elseif(is_array($func)) $name = $func[1];
            else throw new InvalidArgumentException('Closures and callable objects require an explicit method name');
        }
        if(!is_string($name) || !preg_match('/\A[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*\z/D', $name)) throw new InvalidArgumentException('Invalid extension method name');
        $callback = $func;
        if($func instanceof Closure) {
            $reflection = new ReflectionFunction($func);
            if(!$reflection->getClosureThis()) {
                // PHP 7.4 没有 ReflectionFunction::isStatic()；静态/内部闭包不能绑定时保持原回调。
                $bound = @$func->bindTo($this, get_class($this));
                if($bound !== null) $callback = $bound;
            }
        }
        $this->methods[strtolower($name)] = $callback;
        return $this;
    }

    /**
     * 功能说明：原子合并类方法别名，支持未声明静态 func_alias 的普通 trait 使用类
     *
     * @param array $alias 别名到目标方法名的非空字符串映射；空数组读取当前别名（默认：[]）
     * @return array 合并后的完整别名表；原有静态表继续同步，否则保存到实际类覆盖表
     * @throws InvalidArgumentException 映射或名称非法、存在循环；失败保留原表
     */
    public static function regAlias($alias = []) {
        if(!is_array($alias)) throw new InvalidArgumentException('Method aliases must be an array');
        $next = static::extensionAliases();
        foreach($alias as $name => $target) {
            if(!is_string($name) || !is_string($target) || $name === '' || $target === '') throw new InvalidArgumentException('Alias names and targets must be non-empty strings');
            $next[strtolower($name)] = $target;
        }
        try { foreach(array_keys($next) as $name) static::resolveExtensionMethod($name, $next); }
        catch(LogicException $error) { throw new InvalidArgumentException('Cyclic method alias', 0, $error); }
        $class = get_called_class();
        if(property_exists($class, 'func_alias')) {
            $property = new ReflectionProperty($class, 'func_alias');
            if($property->isStatic()) { $property->setValue(null, $next); return $next; }
        }
        self::$extension_aliases[$class] = $next;
        return $next;
    }
}
