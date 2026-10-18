<?PHP
/********************************************
*                                           *
* Name    : Reflection Function For All     *
* Modifier: Windy2000                       *
* Time    : 2018-11-2                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/
/**
 * 文件：lib/myReflection.class.php
 * 功能说明：PHP反射与注释读取封装
 *   封装类、对象、函数及扩展反射，读取元信息、成员列表与文档注释。
 *
 * 方法目录：
 *   public myReflection::init($para = '') — 获取对象基本信息
 *   public myReflection::__get($para) — 返回对象某一基本信息
 *   public myReflection::info() — 返回全部基本信息
 *   public myReflection::export() — 返回对象详细信息
 *   public myReflection::get() — 按位置参数调用反射函数或构造普通类实例
 *   public myReflection::getName($mode = false) — 获取对象名称
 *   public myReflection::getVariants() — 获取对象所有变量及常量
 *   public myReflection::getFunc($mode = false) — 获取对象方法
 *   public myReflection::getComment($method = '') — 读取目标/指定方法的注释，或返回全部方法注释映射
 *   public myReflection::is($type = '') — 对象相关特性检测
 *   public myReflection::check($name, $type = 'method') — 对象相关属性检测
 *
 * 使用说明：
 *   1. 传类/接口/trait名、函数名、扩展名或对象初始化，再读取声明及注释信息。
 *   2. 被反射目标的文档依赖PHP保留DocComment；其他反射能力由底层Reflection对象代理提供。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名保持PHP 7.4兼容，PHPDoc类型用于说明，不额外强制运行时类型。
 */


/**
 * 功能说明：封装类、对象、函数及扩展反射，读取元信息、成员列表与文档注释。
 *
 * 使用说明：
 *   1. 传类/接口/trait名、函数名、扩展名或对象初始化，再读取声明及注释信息。
 *   2. 被反射目标的文档依赖PHP保留DocComment；其他反射能力由底层Reflection对象代理提供。
 *
 * 使用示例：
 *     $ref = new myReflection('myFile');
 *     $methods = $ref->getFunc(); $doc = $ref->getComment('getLocal');
 *     $instance = (new myReflection($className))->get(...$constructorArgs);
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 *
 * 虚拟属性说明：字段在init成功后建立，尚未初始化或目标种类不适用时__get可返回null；这些属性只是元信息快照，不修改底层反射对象。
 *
 * @property-read string|null $name 反射目标名称
 * @property-read string|null $type class/abstract/anonymous/interface/trait/function/extension
 * @property-read string|false|null $file 声明文件，内部目标通常为false
 * @property-read int|false|null $start 声明起始行，内部目标通常为false
 * @property-read int|false|null $end 声明结束行，内部目标通常为false
 * @property-read string|false|null $ext 所属扩展名称，无扩展或不适用时为false
 * @property-read string|false|null $doc 当前目标的文档说明，依赖PHP保留DocComment
 * @property-read array|null $method 类方法反射列表，非类目标可能没有该字段
 * @example ../app/Document/module/myReflection.php 框架现有演示，仅作为文档引用，不在检查时执行
 */
class myReflection extends myProxy {
    protected
        $info = array();

    /**
     * 功能说明：获取对象基本信息
     *
     * @param string|object $para 类/接口/trait名、函数名、扩展名或待反射对象（默认：''）
     * @return void 无返回值。
     * @throws ReflectionException 目标反射或底层调用产生的异常继续传递
     */
    public function init($para = '') {
        $this->info = array();
        switch (true) {
            case is_string($para) && extension_loaded($para):
                $this->obj = new ReflectionExtension($para);
                $type = 'extension';
                break;
            case is_string($para) && (class_exists($para) || interface_exists($para) || trait_exists($para)):
                $this->obj = new ReflectionClass($para);
                $type = 'class';
                break;
            case is_string($para) && function_exists($para):
                $this->obj = new ReflectionFunction($para);
                $type = 'function';
                break;
            case is_object($para):
                $this->obj = new ReflectionObject($para);
                $type = 'class';
                break;
            default:
                $this->error('Only class or function is allowed!');
                return;
                break;
        }
        $this->info = array(
            'para' => $para,
            'name' => $this->obj->getName(),
            'type' => $type,
            'file' => method_exists($this->obj, 'getFileName') ? $this->obj->getFileName() : false,
            'start' => method_exists($this->obj, 'getStartLine') ? $this->obj->getStartLine() : false,
            'end' => method_exists($this->obj, 'getEndLine') ? $this->obj->getEndLine() : false,
            'ext' => method_exists($this->obj, 'getExtensionName') ? $this->obj->getExtensionName() : false,
            'doc' => $this->getComment(),
        );

        if($type=='class') {
            $this->info['interface'] = $this->getInterfaceNames();
            $this->info['parent'] = $this->getParentClass()?$this->getParentClass()->getName():'';
            $this->info['trail'] = $this->getTraitNames();
            $this->info['instance'] = is_object($para)?$this->isInstance($para):false;
            $this->info['method'] = $this->getFunc();
            switch(true) {
                case $this->isAbstract() && !$this->isInterface() && !$this->isTrait():
                    $this->info['type'] = 'abstract';
                    break;
                case $this->isAnonymous():
                    $this->info['type'] = 'anonymous';
                    break;
                case $this->isInterface():
                    $this->info['type'] = 'interface';
                    break;
                case $this->isTrait():
                    $this->info['type'] = 'trait';
                    break;
                default:
                    $this->info['type'] = 'class';
                    break;
            }
        }elseif($type=='extension') {
            $this->info['version'] = $this->getVersion();
            $this->info['setting'] = $this->getINIEntries();
            $this->info['temporary'] = $this->isTemporary();
        }
    }

    /**
     * 功能说明：返回对象某一基本信息
     *
     * @param string $para 反射元信息字段名或代理属性名
     * @return mixed 反射元信息字段的原值；未知字段为null
     */
    public function __get($para) {
        return array_key_exists($para, $this->info) ? $this->info[$para] : null;
    }

    /**
     * 功能说明：返回全部基本信息
     *
     * 参数说明：无显式参数。
     *
     * @return array 当前目标的反射元信息映射；尚未初始化时为空数组
     */
    public function info() {
        return $this->info;
    }

    /**
     * 功能说明：返回对象详细信息
     *
     * 参数说明：无显式参数。
     *
     * @return string 底层Reflection对象的字符串描述，不是可执行导出脚本
     */
    public function export() {
        return (string)$this->obj;
    }

    /**
     * 功能说明：按位置参数调用反射函数或构造普通类实例
     *
     * @param mixed ...$args 函数调用或类构造的位置参数（历史位置参数，由func_get_args读取）
     * @return mixed 函数的返回值或新类实例；未初始化为false，其他目标类别为null
     * @throws Throwable 自定义解析器、初始化/反射目标或受信执行文件产生的异常继续传递
     */
    public function get() {
        if(empty($this->info)) return false;
        $result = null;
        if($this->info['type']=='function') {
            //$result = call_user_func_array([$this, 'invoke'], func_get_args());
            $result = $this->invokeArgs(func_get_args());
        } elseif($this->info['type']=='class') {
            if($this->getConstructor()==null) {
                $result = $this->newInstanceWithoutConstructor();
            } else {
                //$result = call_user_func_array([$this, 'newInstance'], func_get_args());
                $result = $this->newInstanceArgs(func_get_args());
            }
        }
        return $result;
    }

    /**
     * 功能说明：获取对象名称
     *
     * @param bool $mode 目标位于命名空间时，true返回namespace/name数组，false返回完整名称（默认：false）
     * @return string|array 名称文本；命名空间目标且mode为true时返回namespace/name数组
     */
    public function getName($mode = false) {
        if(method_exists($this->obj, 'inNamespace') && $this->obj->inNamespace()) {
            if($mode) {
                $result = array(
                    'namespace' => $this->getNamespaceName(),
                    'name' => $this->getShortName()
                );
            } else {
                $result = $this->obj->getName();
            }
        } else {
            $result = method_exists($this->obj, 'getShortName') ? $this->obj->getShortName() : $this->obj->getName();
        }
        return $result;
    }

    /**
     * 功能说明：获取对象所有变量及常量
     *
     * 参数说明：无显式参数。
     *
     * @return array|null 含constant/variant的常量和默认属性映射；非类或未初始化为null
     */
    public function getVariants() {
        if(empty($this->info)) return;
        if(!$this->obj instanceof ReflectionClass) return null;
        return array(
            'constant' => $this->getConstants(),
            'variant' => $this->getDefaultProperties()
        );
    }

    /**
     * 功能说明：获取对象方法
     *
     * @param bool $mode 反射扩展时true返回反射对象列表，false返回名称列表；类返回方法反射列表（默认：false）
     * @return array|null 类的方法列表，或扩展的function/class映射；未初始化为null
     */
    public function getFunc($mode = false) {
        if(empty($this->info)) return;
        $result = array();
        switch($this->info['type']) {
            case 'extension':
                if($mode) {
                    $result = array(
                        'function' => $this->getFunctions(),
                        'class' => $this->getClasses()
                    );
                } else {
                    $result = array(
                        'function' => get_extension_funcs($this->info['name']),
                        'class' => $this->getClassNames()
                    );
                }
                break;
            case 'class':
            case 'abstract':
            case 'anonymous':
            case 'interface':
            case 'trait':
                $result = $this->getMethods();
                break;
        }
        return $result;
    }

    /**
     * 功能说明：读取目标/指定方法的注释，或返回全部方法注释映射
     *
     *
     * 调用示例：
     *     $doc = (new myReflection('myFile'))->getComment('getLocal');
     *
     * @param string $method 方法名；空串读目标注释，__ALL__返回全部方法的原始注释映射（默认：''）
     * @return string|array|false 注释文本；__ALL__返回方法注释映射，底层无DocComment可能为false
     */
    public function getComment($method = '') {
        if(!method_exists($this->obj, 'getDocComment')) return '';
        if(empty($method)) {
            $result = $this->getDocComment();
        } elseif($method == '__ALL__') {
            $result = array();
            foreach($this->getMethods() as $method) {
                $result[$method->name] = $method->getDocComment();
            }
        } else {
            $result = $this->getMethod($method)->getDocComment();
        }
        if($result) {
            if(is_array($result)) return array_map(function($comment) { return $comment ?: ''; }, $result);
            $result = preg_replace('/^\/[\*\r\n\s]*(.+)[\r\n\s\*]+\/$/ms', '\1', $result);
            $result = preg_replace('/[\r\n]+[\s\*]+/m', chr(10), $result);
            $result = preg_replace('/[\r\n]+$/m', '', $result);
        }
        return $result;
    }

    /**
     * 功能说明：对象相关特性检测
     *
     * @param string $type 特性代号：abstract/a、anonymous/n、interface/i、clone/c、final/f、instantiable/ia、internal/in、iterable/it、iterateable/ita、trait/t或userdefined/u（默认：''）
     * @return bool 类目标具备指定特性为true；非类目标或未知标记为false
     */
    public function is($type = '') {
        if(!$this->obj instanceof ReflectionClass) return false;
        $result = false;
        switch($type) {
            case 'a':
            case 'abstract':
                $result = $this->isAbstract();
                break;
            case 'n':
            case 'anonymous':
                $result = $this->isAnonymous();
                break;
            case 'i':
            case 'interface':
                $result = $this->isInterface();
                break;
            case 'c':
            case 'clone':
                $result = $this->isCloneable();
                break;
            case 'f':
            case 'final':
                $result = $this->isFinal();
                break;
            case 'ia':
            case 'instantiable':
                $result = $this->isInstantiable();
                break;
            case 'in':
            case 'internal':
                $result = $this->isInternal();
                break;
            case 'it':
            case 'iterable':
                $result = $this->isIterable();
                break;
            case 'ita':
            case 'iterateable':
                $result = $this->isIterateable();
                break;
            case 't':
            case 'trait':
                $result = $this->isTrait();
                break;
            case 'u':
            case 'userdefined':
                $result = $this->isUserDefined();
                break;
        }
        return $result;
    }

    /**
     * 功能说明：对象相关属性检测
     *
     * @param string $name 待检查的属性/方法等成员名
     * @param string $type 成员类别：method/m、constant/c、property/p/variant/v、interface/i、namespace/n或subclass/s（默认：'method'）
     * @return bool 类目标满足指定成员/接口/命名空间检查为true；不满足或非类为false
     */
    public function check($name, $type = 'method') {
        if(!$this->obj instanceof ReflectionClass) return false;
        $result = false;
        switch($type) {
            case 'm':
            case 'method':
                $result = $this->hasMethod($name);
                break;
            case 'c':
            case 'constant':
                $result = $this->hasConstant($name);
                break;
            case 'p':
            case 'property':
            case 'v':
            case 'variant':
                $result = $this->hasProperty($name);
                break;
            case 'i':
            case 'interface':
                $result = $this->implementsInterface($name);
                break;
            case 'n':
            case 'namespace':
                $result = $this->obj->getNamespaceName() === $name;
                break;
            case 's':
            case 'subclass':
                $result = $this->isSubclassOf($name);
                break;
        }
        return $result;
    }
}