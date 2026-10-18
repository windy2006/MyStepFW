<?PHP
/********************************************
*                                           *
* Name    : My Cache                        *
* Modifier: Windy2000                       *
* Time    : 2010-12-12                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/
/**
 * 文件：lib/myCache.class.php
 * 功能说明：缓存代理与结果缓存入口
 *   选择缓存驱动，封装查询/函数结果、显式业务键、能力检查和计算锁。
 *
 * 方法目录：
 *   public myCache::init($module = '', $setting = null, $gc_rate = 0.1) — 选择并初始化缓存适配器，仅登记一次目录自动加载器
 *   public myCache::change($module, $setting = null) — 切换缓存适配器，沿用 init() 的初始化及默认回收概率
 *   public myCache::getData($query, $mode='all', $ttl = 600) — 缓存全局 myDb 查询结果，支持空记录、false 和 SQL NULL
 *   public myCache::getData_func($func, $args = array(), $ttl = 600) — 按可调用对象身份及序列化参数缓存结果，兼容旧 TTL 删除约定
 *   protected myCache::callableIdentity($object) — 取得当前对象生命周期内的唯一身份，避免对象句柄复用导致键冲突
 *   public myCache::remember($key, callable $producer, $ttl = 600) — 以显式业务键缓存回调结果，使用封装区分空值与未命中
 *   public myCache::clear() — 显式清空当前后端全部数据，用于管理员清理；不同于只回收过期项的clean。
 *   public myCache::supports($interface) — 检查当前后端是否声明指定接口，不探测远程服务或调用扩展方法。
 *   public myCache::synchronized($key, callable $operation, $timeout = 5) — 显式转发计算锁，旧后端不支持时抛异常，不静默忽略业务回调。
 *   public myCache::fetch($key, &$hit = null) — 显式转发引用参数，避免魔术代理丢失命中输出；旧适配器按get结果保守推断。
 *   protected myCache::rememberKey($key) — 生成remember的兼容物理键，提前拒绝资源/数组/对象键。
 *   protected myCache::rememberUsing(interface_cache $backend, $key, callable $producer, $ttl) — 在同一个后端上读取、计算和写入，回调中change不会把旧上下文写进新后端。
 *   public myCache::rememberLocked($key, callable $producer, $ttl = 600, $timeout = 5) — 防并发重建的显式入口：未命中后加锁，再次检查，成功发布后释放。
 *
 * 使用说明：
 *   1. 通过new myCache('File',CACHE.'data')或数据库缓存配置初始化。
 *   2. fetch区分有效空值与未命中；remember/rememberLocked接收可重复计算回调。
 *   3. getData遵循数据库查询缓存资格/失效规则；getData_func的非正TTL或历史null用于删除。
 *   4. clear显式清空，clean保留驱动过期回收语义；切换后端须同步相关持有者。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名保持PHP 7.4兼容，PHPDoc类型用于说明，不额外强制运行时类型。
 */


require_once __DIR__.'/cache/myCacheCodec.class.php';
/**
 * 功能说明：选择缓存驱动，封装查询/函数结果、显式业务键、能力检查和计算锁。
 *
 * 使用说明：
 *   1. 通过new myCache('File',CACHE.'data')或数据库缓存配置初始化。
 *   2. fetch区分有效空值与未命中；remember/rememberLocked接收可重复计算回调。
 *   3. getData遵循数据库查询缓存资格/失效规则；getData_func的非正TTL或历史null用于删除。
 *   4. clear显式清空，clean保留驱动过期回收语义；切换后端须同步相关持有者。
 *
 * 补充说明：
 * 缓存代理及数据库/回调结果缓存，兼容旧set/get/remove/clean。
 *
 *     $cache->init('File', $path, 0.1);              // 选择适配器及自动回收概率
 *     $value = $cache->fetch($key, $hit);            // 精确区分false/null与未命中
 *     $cache->getData($sql, 'all', 600);             // 共享失效的查询缓存
 *     $cache->getData_func($callback, $args, 600);    // 按回调身份及可序列化参数缓存
 *     $cache->remember($key, $producer, 600);        // 普通显式业务键缓存
 *     $cache->rememberLocked($key, $producer, 600);  // 支持锁时防止并发重复重建
 *     $cache->clear();                            // 管理员显式清空；clean仅回收过期
 *
 * 使用说明：
 * 1. File/MySQL/MSSQL简称不区分大小写；第三方仍只须实现原interface_cache。
 * 2. 只有无需必需参数的显式collectGarbage钩子参与自动回收，不自动调用clean。
 * 3. 查询缓存遵循数据库读写位、事务和共享失效；参数化读取本身不使用共享缓存。
 * 4. 对象回调身份仅在当前代理及对象生命周期内稳定；跨请求使用显式remember键，
 *    键须包含用户、租户及影响结果的全部上下文。资源参数不能作为稳定缓存参数。
 * 5. 普通remember不加计算锁；rememberLocked要求后端声明interface_cache_lock。
 *    锁不保证业务副作用恰好执行一次，写入失败时可能再次计算。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 *
 * IDE提示说明：set/get/remove/clean来自基础缓存协议。TTL、省略value删除及clean过期回收为内置驱动约定；第三方适配器应核对其实现。lookup及锁等可选能力仍须supports确认。
 *
 * @method bool set(mixed $key, mixed $value = '', int|float $ttl = 600) 驱动写入；内置驱动TTL为秒，省略value可删除
 * @method mixed get(mixed $key) 兼容驱动读取；false可同时表示缓存值或未命中
 * @method bool remove(mixed $key) 删除指定键，成功语义由后端约定
 * @method bool clean() 内置驱动回收过期数据；第三方实现语义需单独确认
 * @example ../app/Document/module/myCache.php 框架现有演示，仅作为文档引用，不在检查时执行
 */
class myCache extends myProxy {
    protected static $cache_autoload_registered = false;
    protected $callable_identities = [];
    protected
        $func_alias = array(
            's' => 'set',
            'g' => 'get',
            'r' => 'remove',
            'c' => 'clean',
            'db' => 'getData',
            'func' => 'getData_func',
        );

    /**
     * 功能说明：选择并初始化缓存适配器，仅登记一次目录自动加载器
     *
     * 验证新驱动后才替换当前目标；未知类名沿用历史行为回退文件驱动。
     * 自动垃圾回收仅调用 collectGarbage()，不把第三方 clean() 当成回收方法。
     *
     * @param string|interface_cache $module 驱动简称、类名或已初始化驱动；空字符串使用 File（默认：''）
     * @param mixed $setting 驱动构造参数：File 为目录/选项数组，SQL 为连接及编解码选项；对象模式忽略（默认：null）
     * @param float $gc_rate 本次初始化触发回收的概率，范围 0 到 1；0 禁止，默认 0.1（默认：0.1）
     * @return void 无返回值。
     * @throws InvalidArgumentException 驱动接口不符、名称类型非法或回收概率越界
     * @throws Throwable 驱动构造及回收异常原样传递
     */
    public function init($module = '', $setting = null, $gc_rate = 0.1) {
        if(!self::$cache_autoload_registered) {
            spl_autoload_register(function($class) {
                if(!preg_match('/\A\w+\z/D', $class)) return;
                $known=['mycache_file'=>'myCache_File','mycache_mysql'=>'myCache_MySQL','mycache_mssql'=>'myCache_MSSQL',
                    'interface_cache'=>'interface_cache','interface_cache_lookup'=>'interface_cache_lookup','interface_cache_lock'=>'interface_cache_lock','mycachecodec'=>'myCacheCodec'];
                $name=$known[strtolower($class)]??$class;
                foreach([__DIR__.'/cache/'.$name.'.class.php', __DIR__.'/cache/myCache_'.$name.'.class.php'] as $file) if(is_file($file)) { require_once $file; return; }
            });
            self::$cache_autoload_registered = true;
        }
        if(!is_numeric($gc_rate) || !is_finite((float)$gc_rate) || $gc_rate < 0 || $gc_rate > 1) throw new InvalidArgumentException('Cache GC rate must be between zero and one');
        if(is_object($module)) $target = $module;
        else {
            if(!is_string($module)) throw new InvalidArgumentException('Cache module must be a class name or adapter');
            $known = ['file'=>'myCache_File', 'mysql'=>'myCache_MySQL', 'mssql'=>'myCache_MSSQL'];
            $short = strtolower(preg_replace('/^myCache_/i', '', $module));
            $candidate = $known[$short] ?? (strpos($module, 'myCache_') === 0 ? $module : 'myCache_'.$module);
            if(class_exists($candidate)) {
                if(!is_subclass_of($candidate, 'interface_cache')) throw new InvalidArgumentException('Invalid cache adapter class');
                $target = new $candidate($setting);
            } elseif($module !== '' && class_exists($module)) {
                if(!is_subclass_of($module, 'interface_cache')) throw new InvalidArgumentException('Invalid cache adapter class');
                $target = new $module($setting);
            }
            else $target = new myCache_File($setting);
        }
        if(!($target instanceof interface_cache)) throw new InvalidArgumentException('Cache adapter must implement interface_cache');
        // Third-party clean() may flush all live data. Only an explicit GC hook is automatic.
        if($gc_rate > 0 && method_exists($target, 'collectGarbage') && is_callable([$target, 'collectGarbage']) && (new ReflectionMethod($target,'collectGarbage'))->getNumberOfRequiredParameters()===0 && random_int(1, 1000000) <= $gc_rate*1000000) $target->collectGarbage();
        $this->obj = $target;
    }

    /**
     * 功能说明：切换缓存适配器，沿用 init() 的初始化及默认回收概率
     *
     * @param string|interface_cache $module 驱动简称、类名或已初始化适配器
     * @param mixed $setting 传给新驱动构造函数的设置（默认：null）
     * @return void 新驱动构造、接口校验或自动回收异常时原目标仍可用
     * @throws Throwable 被调用回调/后端的异常按当前入口继续传递，不包装为成功结果
     */
    public function change($module, $setting = null) {
        // Keep the old adapter usable when replacement initialization fails.
        $this->init($module, $setting);
    }

    /**
     * 功能说明：缓存全局 myDb 查询结果，支持空记录、false 和 SQL NULL
     *
     * 仅当数据库 setCache() 已挂接共享失效后端且查询可缓存时读写；遵循 cache() 位。
     * 实际查询期间临时关闭底层结果缓存并恢复，避免下层 TTL 遮挡本包装器 TTL。
     *
     * @param string $query 完整 SQL 查询，不能直接拼接不可信外部输入
     * @param string|int $mode all 全部记录、record 首条、result 首值、remove 删除各模式；
     * 数字时兼容为 TTL，模式按 all 处理（默认：'all'）
     * @param int|float $ttl 有效秒数，默认 600；非正数删除当前模式条目，不执行查询（默认：600）
     * @return mixed all为记录列表、record为单条/空值、result为首值；删除或无全局myDb时为空串，按驱动约定保留false/null
     * @throws InvalidArgumentException 不支持的结果模式
     */
    public function getData($query, $mode='all', $ttl = 600) {
        global $db;
        if(!($db instanceof myDb)) return '';
        if(is_numeric($mode)) { $ttl = $mode; $mode = 'all'; }
        if(!in_array($mode, ['all','record','result','remove'], true)) throw new InvalidArgumentException('Unsupported cached query mode');
        if(!is_string($query)||trim($query)==='')throw new InvalidArgumentException('Cached query must be non-empty SQL');
        myCacheCodec::expires($ttl);
        $backend=$this->obj;
        $db->build('[reset]');
        $key = $db->cacheKey('query:v3:'.serialize([$mode, $query]));
        if($mode === 'remove' || $ttl <= 0) {
            foreach($mode === 'remove' ? ['all','record','result'] : [$mode] as $each) $backend->remove($db->cacheKey('query:v3:'.serialize([$each, $query])));
            return '';
        }
        $policy = $db->cache();
        $eligible = $db->hasCache() && $db->isCacheable($query);
        $entry = $eligible && ($policy & 1) ? $backend->get($key) : false;
        if(is_array($entry) && ($entry['cached-query'] ?? null) === 1 && array_key_exists('value', $entry)) return $entry['value'];
        $cache_mode = $db->cache(); $db->cache(0);
        try {
            if($mode === 'record') $result = $db->record($query);
            elseif($mode === 'result') $result = $db->result($query);
            else $result = $db->records($query);
        } finally { $db->free(); $db->cache($cache_mode); }
        if($eligible && ($policy & 2) && !$db->checkError()) $backend->set($key, ['cached-query'=>1, 'value'=>$result], $ttl);
        return $result;
    }

    /**
     * 功能说明：按可调用对象身份及序列化参数缓存结果，兼容旧 TTL 删除约定
     *
     * 闭包/对象方法使用弱引用及随机身份，防止 PHP 复用对象句柄后误命中。
     * 不可序列化参数应改用 remember() 并由调用方指定包含全部上下文的稳定键。
     *
     * @param callable $func 函数名、静态方法、对象方法、闭包或可调用对象
     * @param mixed $args 按顺序传给回调的参数，非数组自动包装为单个参数（默认：array()）
     * @param int|float|null $ttl 有效秒数；非正数或历史null仅删除该键并返回null，不调用回调（默认：600）
     * @return mixed 已缓存或新计算的结果，false/null/空数组均可命中
     * @throws InvalidArgumentException 回调不可调用或参数无法序列化
     * @throws Throwable 回调异常向外传递，不缓存异常结果
     */
    public function getData_func($func, $args = array(), $ttl = 600) {
        if($ttl === null) $ttl = 0; // 保留旧调用者用null删除函数缓存的约定。
        myCacheCodec::expires($ttl);
        if(!is_callable($func)) throw new InvalidArgumentException('Cached function must be callable');
        if(!is_array($args)) $args = [$args];
        if(is_string($func)) $identity = strtolower($func);
        elseif(is_array($func)) $identity = [is_object($func[0]) ? $this->callableIdentity($func[0]) : strtolower($func[0]), strtolower($func[1])];
        else $identity = $this->callableIdentity($func);
        try { $key = 'func:v4:'.hash('sha256', (new myCacheCodec())->pack([$identity, $args])); }
        catch(Throwable $error) { throw new InvalidArgumentException('Use remember() with an explicit key for non-serializable arguments', 0, $error); }
        if($ttl <= 0) { $this->remove('remember:v1:'.hash('sha256', $key)); return null; }
        return $this->remember($key, function() use ($func, $args) { return call_user_func_array($func, $args); }, $ttl);
    }

    /**
     * 功能说明：取得当前对象生命周期内的唯一身份，避免对象句柄复用导致键冲突
     *
     * @param object $object 闭包、可调用对象或对象方法所属实例
     * @return string 当前缓存代理分配的随机身份；跨请求不稳定，条目多时清理已失效弱引用
     */
    protected function callableIdentity($object) {
        $hash = spl_object_hash($object);
        $entry = $this->callable_identities[$hash] ?? null;
        if(!$entry || $entry['object']->get() !== $object) $this->callable_identities[$hash] = ['object'=>WeakReference::create($object), 'key'=>bin2hex(random_bytes(16))];
        if(count($this->callable_identities)>256) foreach($this->callable_identities as $key => $entry) if($entry['object']->get() === null) unset($this->callable_identities[$key]);
        return $this->callable_identities[$hash]['key'];
    }

    /**
     * 功能说明：以显式业务键缓存回调结果，使用封装区分空值与未命中
     *
     *
     * 回调约定：producer无参数，返回原始业务值；命中时不调用。键应覆盖用户/语言/租户等影响结果的上下文；失败后重试可能再次计算。
     * 类型提示约定：TResult以同一业务键的已缓存数据与producer返回类型保持一致为前提；外部写入或业务类型改变时应变更键版本/清理该键。
     *
     * 调用示例：
     *     $value = $cache->remember('public:home:v1', function () { return ['title' => '首页']; }, 60);
     *
     * @param string $key 含用户/租户/参数等所有结果上下文的键，内部再加命名空间并散列
     * @param callable $producer 未命中时无参数调用；调用方使用闭包捕获所需参数
     * @param int|float $ttl 有效秒数，默认 600；非正数直接计算，不读写或删除现有条目（默认：600）
     * @return mixed 缓存值或回调结果，包括 false、null、空数组及空字符串
     * @throws Throwable 回调异常原样传递，不写入异常结果；不提供并发单次执行锁
     * @template TResult
     * @phpstan-param callable(): TResult $producer
     * @phpstan-return TResult
     */
    public function remember($key, callable $producer, $ttl = 600) {
        myCacheCodec::expires($ttl);
        $key=$this->rememberKey($key);
        if($ttl<=0)return $producer();
        return $this->rememberUsing($this->obj,$key,$producer,$ttl);
    }

    /**
     * 功能说明：显式清空当前后端全部数据，用于管理员清理；不同于只回收过期项的clean。
     *
     * 同目录/同表的所有namespace均受影响，文件锁基础设施保留；并发请求仍可重建值。
     * 第三方后端须显式提供clear，不把含义不确定的clean当作清空入口。
     *
     * 参数说明：无显式参数。
     *
     * @return bool 存储清空成功为true，后端返回失败为false
     * @throws BadMethodCallException 后端没有公开的clear方法
     * @throws Throwable 后端异常原样传递
     */
    public function clear() {
        if(!is_object($this->obj) || !method_exists($this->obj, 'clear') || !is_callable([$this->obj, 'clear'])) throw new BadMethodCallException('Cache adapter does not provide explicit clearing');
        return (bool)$this->obj->clear();
    }

    /**
     * 功能说明：检查当前后端是否声明指定接口，不探测远程服务或调用扩展方法。
     *
     * @param string $interface 接口名，例如interface_cache_lookup或interface_cache_lock
     * @return bool 未初始化、非法/未知名称或普通类名为false
     */
    public function supports($interface) {
        if(!is_object($this->obj)||!is_string($interface)||!preg_match('/\A[\\\\A-Za-z0-9_\x80-\xff]+\z/D',$interface))return false;
        return interface_exists($interface)&&$this->obj instanceof $interface;
    }

    /**
     * 功能说明：显式转发计算锁，旧后端不支持时抛异常，不静默忽略业务回调。
     *
     * @param mixed $key 逻辑锁键
     * @param callable $operation 无参数回调
     * @param int|float $timeout 等待秒数，0..60（默认：5）
     * @return mixed 回调结果
     * @throws BadMethodCallException 后端未声明计算锁接口
     * @throws Throwable 锁或回调异常继续传递
     * @template TResult
     * @phpstan-param callable(): TResult $operation
     * @phpstan-return TResult
     */
    public function synchronized($key, callable $operation, $timeout = 5) {
        myCacheCodec::timeout($timeout);
        if(!($this->obj instanceof interface_cache_lock))throw new BadMethodCallException('Cache adapter does not provide computation locks');
        return $this->obj->synchronized($key,$operation,$timeout);
    }

    /**
     * 功能说明：显式转发引用参数，避免魔术代理丢失命中输出；旧适配器按get结果保守推断。
     *
     *
     * 调用示例：
     *     $value = $cache->fetch('counter', $hit);
     *     if (!$hit) {
     *         // 处理未命中；有效false/null仍可能命中
     *     }
     *
     * @param mixed $key 缓存键
     * @param bool|null &$hit 引用参数；精确命中；旧get返回false时无法判断是否命中（默认：null）
     * @return mixed 缓存值或false
     */
    public function fetch($key, &$hit = null) {
        if($this->obj instanceof interface_cache_lookup)return $this->obj->fetch($key,$hit);
        $value=$this->obj->get($key);$hit=$value!==false;return $value;
    }

    /**
     * 功能说明：生成remember的兼容物理键，提前拒绝资源/数组/对象键。
     *
     * @param mixed $key 有限标量或null
     * @return string 带版本的SHA-256键
     */
    protected function rememberKey($key) {
        (new myCacheCodec())->key($key);
        return 'remember:v1:'.hash('sha256',(string)$key);
    }

    /**
     * 功能说明：在同一个后端上读取、计算和写入，回调中change不会把旧上下文写进新后端。
     *
     *
     * 类型提示约定：TResult以同一业务键的已缓存数据与producer返回类型保持一致为前提；外部写入或业务类型改变时应变更键版本/清理该键。
     *
     * @param interface_cache $backend 本次捕获的后端
     * @param string $key 物理remember键
     * @param callable $producer 无参数回调
     * @param int|float $ttl 保存秒数
     * @return mixed 已有或新计算的值，写失败仍返回已计算的业务结果
     * @template TResult
     * @phpstan-param callable(): TResult $producer
     * @phpstan-return TResult
     */
    protected function rememberUsing(interface_cache $backend, $key, callable $producer, $ttl) {
        $entry=$backend->get($key);
        if(is_array($entry)&&($entry['cached-value']??null)===1&&array_key_exists('value',$entry))return $entry['value'];
        $value=$producer();$backend->set($key,['cached-value'=>1,'value'=>$value],$ttl);return $value;
    }

    /**
     * 功能说明：防并发重建的显式入口：未命中后加锁，再次检查，成功发布后释放。
     *
     *
     * 回调约定：producer无参数，返回原始业务值；命中时不调用。键应覆盖用户/语言/租户等影响结果的上下文；失败后重试可能再次计算。
     * 类型提示约定：TResult以同一业务键的已缓存数据与producer返回类型保持一致为前提；外部写入或业务类型改变时应变更键版本/清理该键。
     *
     * 调用示例：
     *     if ($cache->supports(interface_cache_lock::class)) {
     *         $value = $cache->rememberLocked('report:2026', $producer, 120, 3);
     *     }
     *
     * @param mixed $key 含全部用户/租户及业务参数的逻辑键
     * @param callable $producer 无参数计算回调；异常不缓存，锁仍释放
     * @param int|float $ttl 秒数，非正数直接计算，不读取/加锁/写入（默认：600）
     * @param int|float $timeout 锁等待秒数，0..60（默认：5）
     * @return mixed 缓存值或计算结果；依赖同一后端和正常存储，非恰好一次副作用保证
     * @throws BadMethodCallException 后端未声明interface_cache_lock
     * @throws Throwable 锁超时、参数或生产回调异常继续向外传递
     * @template TResult
     * @phpstan-param callable(): TResult $producer
     * @phpstan-return TResult
     */
    public function rememberLocked($key, callable $producer, $ttl = 600, $timeout = 5) {
        myCacheCodec::expires($ttl);myCacheCodec::timeout($timeout);$key=$this->rememberKey($key);
        if($ttl<=0)return $producer();
        $backend=$this->obj;
        if(!($backend instanceof interface_cache_lock))throw new BadMethodCallException('Cache adapter does not provide computation locks');
        $entry=$backend->get($key);
        if(is_array($entry)&&($entry['cached-value']??null)===1&&array_key_exists('value',$entry))return $entry['value'];
        return $backend->synchronized($key,function()use($backend,$key,$producer,$ttl){return $this->rememberUsing($backend,$key,$producer,$ttl);},$timeout);
    }
}
