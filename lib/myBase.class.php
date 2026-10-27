<?PHP

/**
 * 文件：lib/myBase.class.php
 * 功能说明：框架类的初始化与错误处理基类
 *   把构造参数按需转交init，并提供可恢复的错误回调策略。
 *
 * 方法目录：
 *   public myBase::__construct() — 将构造参数转交给实际声明且当前作用域可调用的 init()
 *   protected myBase::setErrHandler($func) — 设置或清除实例错误处理回调，参数校验失败时保留原回调
 *   protected myBase::error($msg, $exit = false) — 执行实例回调或框架默认错误策略，并恢复临时回调位
 *
 * 使用说明：
 *   1. 具体类继承myBase并按需实现init；必需参数缺失时保留延迟初始化约定。
 *   2. 构造和init签名保持兼容，不在此处统一强制参数类型。
 *   3. 错误回调及终止行为受myException配置影响，不能假设所有错误都会返回。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名以PHP 8.5.12为运行基线，PHPDoc类型用于说明，不额外强制运行时类型。
 */
/**
 * 功能说明：把构造参数按需转交init，并提供可恢复的错误回调策略。
 *
 * 使用说明：
 *   1. 具体类继承myBase并按需实现init；必需参数缺失时保留延迟初始化约定。
 *   2. 构造和init签名保持兼容，不在此处统一强制参数类型。
 *   3. 错误回调及终止行为受myException配置影响，不能假设所有错误都会返回。
 *
 * 补充说明：
 * 框架基础类，提供按需初始化及可恢复的错误处理
 *
 *     new ChildClass($arg1, $arg2); // 只向实际声明的 init() 转交参数
 *     $this->setErrHandler($callable); // 子类内部设置错误回调，null 恢复默认
 *     $this->error($message, false); // 按 myException 策略记录/回调
 *
 * 无参数构造且 init() 有必需参数时保留延迟初始化约定；不把 __call() 伪装的
 * init 当作初始化入口。错误临时修改的全局回调标志在正常返回或异常时恢复。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 */
abstract class myBase {
    /** @var callable|null 当前实例错误回调 */
    protected $err_handler = null;
    /** @var array 按实际类缓存 init() 的必需参数数目，不保存实例引用 */
    protected static $init_required = [];

    /**
     * 功能说明：将构造参数转交给实际声明且当前作用域可调用的 init()
     *
     * @param mixed ...$args 按具体子类init的位置顺序传入；无参数时遵守延迟初始化规则（历史位置参数，由func_get_args读取）
     * @return void 初始化或释放资源，无返回值。
     * @throws Throwable 显式参数不符或 init() 内部异常继续向外传递
     */
    public function __construct() {
        if(!method_exists($this, 'init') || !is_callable([$this, 'init'])) return;
        $args = func_get_args();
        if(!$args) {
            $class = get_class($this);
            if(!array_key_exists($class, self::$init_required)) self::$init_required[$class] = (new ReflectionMethod($this, 'init'))->getNumberOfRequiredParameters();
            if(self::$init_required[$class] > 0) return;
        }
        $this->init(...$args);
    }

    /**
     * 功能说明：设置或清除实例错误处理回调，参数校验失败时保留原回调
     *
     * @param callable|null $func 接收错误消息的回调；null 清除实例回调
     * @return void 回调的返回值不改变 error() 的返回及终止策略
     * @throws InvalidArgumentException 非 null 参数不可调用
     * @phpstan-param (callable(string): mixed)|null $func
     */
    protected function setErrHandler($func) {
        if($func !== null && !is_callable($func)) throw new InvalidArgumentException('Error handler must be callable or null');
        $this->err_handler = $func;
    }

    /**
     * 功能说明：执行实例回调或框架默认错误策略，并恢复临时回调位
     *
     * 实例回调先接收消息；随后以 E_USER_ERROR 交给已有错误处理器记录，
     * 此时临时关闭框架回调，避免同一错误重复展示。未安装处理器或返回false时抛出ErrorException。进程 exit 不保证执行 finally。
     *
     * @param string $msg 错误消息
     * @param bool $exit true 明确终止；false 仍遵循 myException::exit_on_error（默认：false）
     * @return bool 未终止且未抛异常时为 true
     * @throws Throwable 实例或 PHP 错误处理器抛出的异常继续传递，临时位会恢复
     */
    protected function error($msg, $exit = false) {
        $type = myException::$callback_type;
        /**
         * 功能说明：按既有PHP错误处理器分发用户错误，避免PHP8.4起弃用的trigger_error(E_USER_ERROR)。
         * @param string $message 错误消息，不附加凭据或请求正文
         * @return void 自定义处理器接管时返回；未接管时抛出异常
         * @throws ErrorException 没有处理器或处理器明确返回false
         */
        $raise = static function($message) {
            $handler = set_error_handler(null);
            restore_error_handler();
            if($handler !== null && $handler(E_USER_ERROR,$message,__FILE__,__LINE__) !== false)return;
            throw new ErrorException($message,0,E_USER_ERROR);
        };
        try {
            if($this->err_handler === null) {
                myException::$callback_type = $type | E_USER_ERROR;
                if(myException::$log_type & E_USER_ERROR) $raise($msg);
            } else {
                call_user_func($this->err_handler, $msg);
                myException::$callback_type = 0;
                $raise($msg);
            }
        } finally { myException::$callback_type = $type; }
        if($exit || myException::$exit_on_error) exit;
        return true;
    }
}
