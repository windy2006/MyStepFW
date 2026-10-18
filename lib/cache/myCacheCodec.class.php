<?PHP

/*******************************************
*                                          *
* Name    : Cache Encoding Tools (new)     *
* Modifier: MyStep Framework               *
* Time    : 2026-10-18 (PHPDoc)            *
* Email   : windy2006@gmail.com            *
* HomePage: www.mysteps.cn                 *
* Notice  : U Can Use & Modify it freely,  *
*           BUT PLEASE HOLD THIS ITEM.     *
*                                          *
*******************************************/

/**
 * 文件：lib/cache/myCacheCodec.class.php
 * 功能说明：缓存键、有效期与序列化公共工具
 *   统一内置缓存的键摘要、命名空间、TTL 校验、编解码预算及对象恢复策略。
 *
 * 方法目录：
 *   public myCacheCodec::__construct(array $options = []) — 验证公共选项。
 *   public myCacheCodec::key($key) — 转换逻辑键；保持无namespace时原有MD5布局，namespace不是访问权限边界。
 *   public static myCacheCodec::expires($ttl, $now = null) — 规范化TTL，防止NAN/INF、浮点转整数和到期加法溢出。
 *   public static myCacheCodec::timeout($timeout) — 验证锁等待时间。
 *   public myCacheCodec::maxBytes() — 返回原始序列化字节预算。
 *   public myCacheCodec::maxDepth() — 返回序列化结构深度预算。
 *   protected myCacheCodec::inspect($value, array &$references, SplObjectStorage $objects, $depth = 0) — 拒绝PHP会静默编码为0的资源，并对数组引用/对象循环去重。
 *   public myCacheCodec::pack($value) — 编码受信数据，超限或不可序列化时不发布新条目。
 *   public myCacheCodec::unpack($data, &$valid) — 解码已确认未过期的受信数据，不执行超出预算的数据。
 *   public myCacheCodec::sqlUnpack($data, &$valid) — 读取SQL缓存的MS_CACHE2 Base64封装或旧原始序列化值。
 *
 * 使用说明：
 *   1. 由 File、MySQL、MSSQL 缓存驱动复用，配置通过驱动选项传入。
 *   2. max_bytes、max_depth 限制编解码；allowed_classes 控制对象恢复。
 *   3. namespace 隔离逻辑键，不构成访问权限或独立清理边界。
 *
 * 说明：目录列出本文件直接声明的全部方法；参数、返回值及异常详见各方法 PHPDoc。
 *       原生签名保持 PHP 7.4 兼容，PHPDoc 类型仅用于说明。
 */
/**
 * 功能说明：集中处理内置缓存驱动共用的键、TTL 和序列化规则。
 *
 * 默认保留受信对象兼容性，可通过 allowed_classes 禁用或限制对象实例化。
 * 字节预算约束序列化结果和存储读取量，不代替业务输入的内存预算。
 *
 *
 * 方法目录：参见本文件顶部。
 *
 * @phpstan-type CacheCodecOptions array{max_bytes?:int,max_depth?:int,allowed_classes?:bool|list<string>,namespace?:string}
 */
class myCacheCodec {
    protected $max_bytes = 8388608, $depth = 128, $allowed = true, $namespace = '';

    /**
     * 功能说明：验证公共选项。
     *
     * @param array $options max_bytes默认8MiB（1..64MiB）、max_depth默认128（1..4096）、allowed_classes及namespace（默认：[]）
     * @return void 初始化或释放资源，无返回值。
     * @throws InvalidArgumentException 配置非法
     * @phpstan-param CacheCodecOptions $options
     */
    public function __construct(array $options = []) {
        foreach(['max_bytes'=>[1,67108864],'max_depth'=>[1,4096]] as $key=>$range) {
            if(!array_key_exists($key,$options)) continue;
            if(!is_int($options[$key]) || $options[$key]<$range[0] || $options[$key]>$range[1]) throw new InvalidArgumentException('Invalid cache '.$key);
        }
        $this->max_bytes = $options['max_bytes'] ?? $this->max_bytes;
        $this->depth = $options['max_depth'] ?? $this->depth;
        $this->allowed = array_key_exists('allowed_classes',$options)?$options['allowed_classes']:true;
        if(!is_bool($this->allowed) && !is_array($this->allowed)) throw new InvalidArgumentException('Invalid cache allowed_classes');
        if(is_array($this->allowed)) foreach($this->allowed as $class) if(!is_string($class) || $class==='') throw new InvalidArgumentException('Invalid cache class allowlist');
        $this->namespace = array_key_exists('namespace',$options)?$options['namespace']:'';
        if(!is_string($this->namespace)) throw new InvalidArgumentException('Invalid cache namespace');
    }

    /**
     * 功能说明：转换逻辑键；保持无namespace时原有MD5布局，namespace不是访问权限边界。
     *
     * @param scalar|null $key 有限标量或null，转为字符串后计算摘要；不同类型但文本相同的键可能相同
     * @return string 固定32位摘要
     * @throws InvalidArgumentException 键不是有限标量或null
     */
    public function key($key) {
        if((!is_scalar($key) && $key!==null) || (is_float($key) && !is_finite($key))) throw new InvalidArgumentException('Cache key must be a finite scalar');
        $key = (string)$key;
        return md5($this->namespace === '' ? $key : serialize(['cache-namespace:v1',$this->namespace,$key]));
    }

    /**
     * 功能说明：规范化TTL，防止NAN/INF、浮点转整数和到期加法溢出。
     *
     * @param int|float|string $ttl 有效秒数，必须有限；正小数向上取整，非正值表示删除
     * @param int|null $now 当前秒数，null使用time，测试时可传入时钟（默认：null）
     * @return int 绝对到期时间；非正TTL为0
     * @throws InvalidArgumentException TTL不是有限数值，或绝对到期时间会溢出
     */
    public static function expires($ttl, $now = null) {
        if(!is_numeric($ttl) || !is_finite((float)$ttl)) throw new InvalidArgumentException('Invalid cache TTL');
        if($ttl<=0) return 0;
        $now = $now ?? time();$seconds = ceil((float)$ttl);
        if($seconds >= (float)(PHP_INT_MAX-$now)) throw new InvalidArgumentException('Cache TTL overflows expiration');
        return $now+(int)$seconds;
    }

    /**
     * 功能说明：验证锁等待时间。
     *
     * @param int|float|string $timeout 等待秒数，须为0至60的有限数值；0表示立即尝试
     * @return float 有限等待秒数
     * @throws InvalidArgumentException 等待时间不是0至60秒内的有限数值
     */
    public static function timeout($timeout) {
        if(!is_numeric($timeout) || !is_finite((float)$timeout) || $timeout<0 || $timeout>60) throw new InvalidArgumentException('Invalid cache lock timeout');
        return (float)$timeout;
    }

    /**
     * 功能说明：返回原始序列化字节预算。
     *
     * 参数说明：无显式参数。
     *
     * @return int 允许序列化/解码的原始字节上限，单位字节
     */
    public function maxBytes() { return $this->max_bytes; }

    /**
     * 功能说明：返回序列化结构深度预算。
     *
     * 参数说明：无显式参数。
     *
     * @return int 允许数组/对象嵌套的最大深度
     */
    public function maxDepth() { return $this->depth; }

    /**
     * 功能说明：拒绝PHP会静默编码为0的资源，并对数组引用/对象循环去重。
     *
     * @param mixed $value 待检查数据
     * @param array &$references 已访问数组引用ID
     * @param SplObjectStorage $objects 已访问对象
     * @param int $depth 当前层级（默认：0）
     * @return void 无返回值。
     * @throws InvalidArgumentException 值含活动或已关闭的资源
     * @throws LengthException 值的嵌套深度超过配置上限
     */
    protected function inspect($value, array &$references, SplObjectStorage $objects, $depth = 0) {
        if(is_resource($value) || gettype($value)==='resource (closed)') throw new InvalidArgumentException('Resources cannot be cached; use an explicit key and cache their data');
        if($depth>$this->depth) throw new LengthException('Cached value exceeds depth limit');
        if(is_object($value)) {
            if($objects->contains($value)) return;
            $objects->attach($value);$value=(array)$value;
        }
        if(!is_array($value)) return;
        foreach($value as $key=>$item) {
            $ref=ReflectionReference::fromArrayElement($value,$key);
            if($ref) { $id=$ref->getId();if(isset($references[$id]))continue;$references[$id]=true; }
            $this->inspect($item,$references,$objects,$depth+1);
        }
    }

    /**
     * 功能说明：编码受信数据，超限或不可序列化时不发布新条目。
     *
     * @param mixed $value 数据值
     * @return string PHP序列化字节串
     * @throws LengthException 编码字节或结构深度超限
     * @throws Throwable 对象序列化回调异常继续传递
     */
    public function pack($value) {
        $references=[];$this->inspect($value,$references,new SplObjectStorage());
        $data=serialize($value);
        if(strlen($data)>$this->max_bytes) throw new LengthException('Cached value exceeds byte limit');
        return $data;
    }

    /**
     * 功能说明：解码已确认未过期的受信数据，不执行超出预算的数据。
     *
     * @param string $data PHP序列化字节串
     * @param bool &$valid 引用参数；解码有效时true，合法false也为true
     * @return mixed 数据或失败标记false
     */
    public function unpack($data, &$valid) {
        $valid=false;
        if(!is_string($data) || strlen($data)>$this->max_bytes) return false;
        try { $value=@unserialize($data,['allowed_classes'=>$this->allowed,'max_depth'=>$this->depth]); }
        catch(Throwable $e) { return false; }
        $valid=$value!==false || $data==='b:0;';
        return $valid ? $value : false;
    }

    /**
     * 功能说明：读取SQL缓存的MS_CACHE2 Base64封装或旧原始序列化值。
     *
     * @param string $data 数据库列内容
     * @param bool &$valid 引用参数；有效性输出
     * @return mixed 解码结果，格式/预算失败为false
     */
    public function sqlUnpack($data, &$valid) {
        $valid=false;
        if(!is_string($data) || strlen($data)>ceil($this->max_bytes/3)*4+10) return false;
        if(strpos($data,'MS_CACHE2:')===0) $data=base64_decode(substr($data,10),true);
        return $this->unpack($data,$valid);
    }
}
