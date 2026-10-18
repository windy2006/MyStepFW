<?PHP
/********************************************
*                                           *
* Name    : My Proxy                        *
* Modifier: Windy2000                       *
* Time    : 2018-11-2                       *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/
/**
 * 文件：lib/myProxy.class.php
 * 功能说明：对象代理与兼容方法转发基类
 *   代理目标构造、公开属性、引用访问及方法调用，并支持别名和trait扩展。
 *
 * 方法目录：
 *   public myProxy::init($module = "") — 初始化代理目标，成功后才替换原实例
 *   public myProxy::__get($para) — 读取目标公开或虚拟属性，缺少时回退到代理辅助变量
 *   public &myProxy::propertyReference($para) — 显式取得目标属性引用，用于公开数组的追加或原地修改
 *   public myProxy::__set($para, $value) — 写入目标公开或虚拟属性，否则写入 myTrait 辅助变量
 *   public myProxy::__isset($para) — 检查目标属性或代理辅助变量是否已设置且非 null
 *   public myProxy::__unset($para) — 移除目标公开或虚拟属性，并移除同名代理辅助变量
 *   public myProxy::__call($func, array $args) — 转发方法调用，显式别名优先于目标的普通及魔术方法
 *   public myProxy::__destruct() — 释放目标及辅助变量/扩展方法引用，避免析构时再次触发属性转发
 *   public myProxy::regAlias($alias = array()) — 合并并校验方法别名，检查通过后一次性替换别名表
 *
 * 使用说明：
 *   1. 继承后通过init选择目标类或对象；类名模式使用构造函数或init。
 *   2. __get保留非引用语义；需要原地修改数组时使用propertyReference。
 *   3. 目标返回自身时的链式调用、别名及魔术方法按实现处理，不作为权限边界。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名保持PHP 7.4兼容，PHPDoc类型用于说明，不额外强制运行时类型。
 */


/**
 * 功能说明：代理目标构造、公开属性、引用访问及方法调用，并支持别名和trait扩展。
 *
 * 使用说明：
 *   1. 继承后通过init选择目标类或对象；类名模式使用构造函数或init。
 *   2. __get保留非引用语义；需要原地修改数组时使用propertyReference。
 *   3. 目标返回自身时的链式调用、别名及魔术方法按实现处理，不作为权限边界。
 *
 * 补充说明：
 * 通用对象代理，转发方法及公开/虚拟属性，并支持显式方法别名
 *
 *     $proxy->init('TargetClass', $arg1, $arg2);  // 构造目标并传入初始化参数
 *     $proxy->init($existingObject);             // 接管现有实例引用，不重新初始化
 *     $proxy->regAlias(['short' => 'fullName']); // 注册方法别名
 *     $items =& $proxy->propertyReference('items'); // 显式取得可引用属性
 *
 * 使用说明：
 * 1. 目标存在构造函数时只调用构造函数；没有构造函数才调用可调用的 init()。
 * 2. 初始化失败不会替换原有目标；本代理不提供权限隔离，目标须来自受信代码。
 * 3. __get() 保留非引用返回签名，兼容现有子类；修改数组属性须使用
 *    propertyReference()，或由目标提供明确的修改方法。
 * 4. 别名优先于目标魔术分发；没有目标方法时回退到 myTrait 的扩展方法机制。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 */
abstract class myProxy extends myBase {
    use myTrait {
        myTrait::__get as public __get_base;
        myTrait::__set as public __set_base;
        myTrait::__call as public __call_base;
    }

    /**
     * 被代理实例
     * @var object|string
     */
    protected
        $obj = '';
    protected $func_alias = [];

    /**
     * 功能说明：初始化代理目标，成功后才替换原实例
     *
     * 类名模式依次采用构造函数或无构造函数时的 init()，不会重复初始化。
     * 现有对象模式仅保存引用，不接受额外初始化参数；额外参数通过 func_get_args()
     * 获取，以保持原有方法签名和子类兼容。
     *
     * @param string|object $module 可实例化类名或已初始化对象；默认空值不能创建目标（默认：""）
     * @param mixed ...$args 目标类构造/init的位置参数；直接传对象时不可附加（历史位置参数，由func_get_args读取）
     * @return void 无返回值。
     * @throws InvalidArgumentException 类不可用、无法实例化或无法接收提供的参数
     * @throws Throwable 目标构造或 init() 抛出的异常原样传递
     */
    public function init($module = "") {
        $args = func_get_args(); array_shift($args);
        if(is_object($module)) {
            if($args) throw new InvalidArgumentException('An existing proxy target does not accept initialization arguments');
            $target = $module;
        } else {
            if(!is_string($module) || $module === '' || !class_exists($module)) throw new InvalidArgumentException('Cannot initialize proxy module');
            $reflection = new ReflectionClass($module);
            if(!$reflection->isInstantiable()) throw new InvalidArgumentException('Proxy module must be instantiable');
            // Constructors own initialization when present; never call init() a second time.
            if($reflection->getConstructor()) $target = $reflection->newInstanceArgs($args);
            else {
                $target = $reflection->newInstance();
                if(is_callable([$target, 'init'])) call_user_func_array([$target, 'init'], $args);
                elseif($args) throw new InvalidArgumentException('Proxy module cannot consume initialization arguments');
            }
        }
        $this->obj = $target;
    }

    /**
     * 功能说明：读取目标公开或虚拟属性，缺少时回退到代理辅助变量
     *
     * @param string $para 属性名；值为 null 的已存在公开属性仍交由目标读取
     * @return mixed 目标属性或 myTrait 辅助变量值；本方法不按引用返回
     */
    public function __get($para) {
        if(is_object($this->obj) && (array_key_exists($para, (array)$this->obj) || is_callable([$this->obj, '__get']))) return $this->obj->$para;
        return $this->__get_base($para);
    }

    /**
     * 功能说明：显式取得目标属性引用，用于公开数组的追加或原地修改
     *
     * 不改变旧 __get() 的非引用签名。虚拟属性只有在目标 __get() 声明为引用返回时
     * 才允许使用；普通 getter 的返回值不能据此获得可写引用。
     *
     * @param string $para 目标公开属性或支持引用返回的虚拟属性名
     * @return mixed 目标属性的引用；调用方使用 $value =& $proxy->propertyReference(...)
     * @throws LogicException 没有可用目标、属性不可公开访问或 getter 不支持引用
     */
    public function &propertyReference($para) {
        if(is_object($this->obj)) {
            if(array_key_exists($para, (array)$this->obj)) return $this->obj->$para;
            if(is_callable([$this->obj, '__get']) && (new ReflectionMethod($this->obj, '__get'))->returnsReference()) return $this->obj->$para;
        }
        throw new LogicException('Proxy property does not expose a reference');
    }

    /**
     * 功能说明：写入目标公开或虚拟属性，否则写入 myTrait 辅助变量
     *
     * @param string $para 属性名
     * @param mixed $value 待赋值内容；目标 setter 自行负责验证
     * @return void 无返回值。
     */
    public function __set($para, $value) {
        if(is_object($this->obj) && (array_key_exists($para, (array)$this->obj) || is_callable([$this->obj, '__set']))) $this->obj->$para = $value;
        else $this->__set_base($para, $value);
    }

    /**
     * 功能说明：检查目标属性或代理辅助变量是否已设置且非 null
     *
     * @param string $para 属性名；目标虚拟属性遵循其 __isset() 的语义
     * @return bool 遵循 PHP isset 约定，null 不视为已设置
     */
    public function __isset($para) {
        return (is_object($this->obj) && isset($this->obj->$para)) || isset($this->vars[$para]);
    }

    /**
     * 功能说明：移除目标公开或虚拟属性，并移除同名代理辅助变量
     *
     * @param string $para 属性名；不直接访问目标的受保护成员
     * @return void 无返回值。
     */
    public function __unset($para) {
        if(is_object($this->obj) && (array_key_exists($para, (array)$this->obj) || is_callable([$this->obj, '__unset']))) unset($this->obj->$para);
        unset($this->vars[$para]);
    }

    /**
     * 功能说明：转发方法调用，显式别名优先于目标的普通及魔术方法
     *
     * @param string $func 调用的方法名或已登记别名
     * @param array $args 按原顺序传递的参数列表
     * @return mixed 目标方法或 myTrait 扩展方法的执行结果
     */
    public function __call($func, array $args) {
        if(isset($this->func_alias[$func])) return $this->__call_base($func, $args);
        if(is_callable(array($this->obj, $func))) {
            return call_user_func_array(array($this->obj, $func), $args);
        } else {
            return $this->__call_base($func, $args);
        }
    }

    /**
     * 功能说明：释放目标及辅助变量/扩展方法引用，避免析构时再次触发属性转发
     *
     * 参数说明：无显式参数。
     *
     * @return void 初始化或释放资源，无返回值。
     */
    public function __destruct() {
        $this->obj = null; $this->vars = []; $this->methods = [];
    }

    /**
     * 功能说明：合并并校验方法别名，检查通过后一次性替换别名表
     *
     * 允许多级别名，但不允许自引用或循环；验证失败时保留原表。
     *
     * @param array $alias 非空字符串别名到非空目标方法名的映射；空数组仅返回当前表（默认：array()）
     * @return array 合并后的完整别名表
     * @throws InvalidArgumentException 映射类型或名称非法，或存在循环别名
     */
    public function regAlias($alias = array()) {
        if(!is_array($alias)) throw new InvalidArgumentException('Proxy aliases must be an array');
        $next = $this->func_alias;
        foreach($alias as $name => $method) {
            if(!is_string($name) || !is_string($method) || $name === '' || $method === '') throw new InvalidArgumentException('Proxy aliases require non-empty method names');
            $next[$name] = $method;
        }
        foreach(array_keys($next) as $name) {
            $seen = []; $current = $name;
            while(isset($next[$current])) {
                if(isset($seen[$current])) throw new InvalidArgumentException('Cyclic proxy alias');
                $seen[$current] = true; $current = $next[$current];
            }
        }
        return $this->func_alias = $next;
    }
}