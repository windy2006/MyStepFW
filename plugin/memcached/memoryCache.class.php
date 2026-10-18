<?PHP
/********************************************
*                                           *
* Name    : Memory Cache Manager            *
* Author  : Windy2000                       *
* Time    : 2009-11-05                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT HOLD THIS ITEM PLEASE.      *
*                                           *
********************************************/
/**
 * 文件：plugin/memcached/memoryCache.class.php
 * 功能说明：Memcached 缓存驱动与精确命中适配器
 *
 * 方法目录：
 *   public memoryCache::__construct($options) — 校验连接配置，持久连接池按服务列表和超时隔离；不在构造时清缓存或连接探测。
 *   public static memoryCache::servers($input,$weight=5) — 规范化服务器列表，不依赖关联数组键的输入顺序，支持IPv6括号地址。
 *   protected memoryCache::key($key) — 以结构化命名空间和业务键生成固定长度的 ms:v3 协议键。
 *   public memoryCache::set($key,$value='',$ttl=0) — 保存值；只省略value参数时删除，0/false/null/空串均正常存储。
 *   public memoryCache::fetch($key,&$hit) — 读取缓存值，并通过引用参数报告原生客户端的精确命中状态。
 *   public memoryCache::get($key) — 按基础缓存协议读取值，未命中或后端失败返回 false。
 *   public memoryCache::remove($key,$timeout=0) — 立即删除缓存键；键原本不存在时也视为删除完成。
 *   public memoryCache::clean() — 通过历史兼容入口显式清空整个 Memcached 服务。
 *   public memoryCache::clear() — 显式刷新整个 Memcached 服务，影响其他命名空间。
 *   public memoryCache::close() — 调用原生 quit 释放当前客户端连接，不删除服务端数据。
 *   public memoryCache::obj() — 返回适配器持有的原生 Memcached 客户端。
 *   public memoryCache::check() — 判断最近操作是否成功或只是正常未命中，不主动探测服务器。
 *
 * 使用说明：
 *   1. 通过 new myCache('memoryCache', $options) 使用；timeout 以秒配置，原生连接选项使用毫秒。
 *   2. 初始化不 flush；fetch 以引用 hit 区分合法 false/null 与未命中，clear/clean 会影响整个服务。
 *   3. 持久连接兼容 persistent/persistant；ms:v3 命名空间键按结构化编码隔离。
 *
 * 说明：PHPDoc 类型用于说明，不增加原生类型约束；源码保持 PHP 7.4 兼容。
 *       方法目录覆盖本文件直接声明；闭包参数及捕获变量说明见各回调旁 PHPDoc。
 */



/**
 * 功能说明：实现基础缓存及精确命中协议的 Memcached 适配器。
 *
 * 使用说明：
 *   1. 通过 new myCache('memoryCache', $options) 使用；timeout 以秒配置，原生连接选项使用毫秒。
 *   2. 初始化不 flush；fetch 以引用 hit 区分合法 false/null 与未命中，clear/clean 会影响整个服务。
 *   3. 持久连接兼容 persistent/persistant；ms:v3 命名空间键按结构化编码隔离。
 *
 * 方法目录：参见本文件顶部；仅列直接声明，不重复列出未在本文件声明的继承或协议方法。
 */
class memoryCache implements interface_cache, interface_cache_lookup {
    protected $mc, $mc_cnnopt=[], $namespace='myStep';
    /**
     * 功能说明：校验连接配置，持久连接池按服务列表和超时隔离；不在构造时清缓存或连接探测。
     *
     * @param array $options server支持host:port、[host,port,weight]、关联记录或记录列表/JSON
     * @return void 初始化客户端选项及服务器池，不主动 flush 或进行连通性检查
     * @throws RuntimeException Memcached 扩展不可用，或客户端选项/服务池设置失败
     * @throws InvalidArgumentException 服务/超时/过期/命名空间配置非法
     */
    public function __construct($options) {
        if(!class_exists('Memcached')) throw new RuntimeException('Memcached extension is unavailable');
        if(!is_array($options)) throw new InvalidArgumentException('Invalid Memcached options');
        $servers=self::servers($options['server'] ?? '127.0.0.1:11211',$options['weight'] ?? 5);
        $timeout=$options['timeout'] ?? 1; $retry=$options['retry_interval'] ?? 30; $expire=$options['expire'] ?? 259200;
        foreach([$timeout,$retry,$expire] as $value) if(!is_numeric($value) || !is_finite((float)$value) || $value<0) throw new InvalidArgumentException('Invalid Memcached duration');
        if($timeout>2147483.647 || $retry>2147483647 || $expire>min(PHP_INT_MAX,4294967295)-time()) throw new InvalidArgumentException('Memcached duration overflows native protocol');
        $this->namespace=$options['namespace'] ?? 'myStep';
        if(!is_string($this->namespace)) throw new InvalidArgumentException('Invalid Memcached namespace');
        $this->mc_cnnopt=['expire'=>$expire,'timeout'=>$timeout,'retry_interval'=>$retry];
        $persistent=filter_var($options['persistent'] ?? $options['persistant'] ?? false,FILTER_VALIDATE_BOOLEAN);
        $pool='myStep:'.hash('sha256',serialize([$servers,$timeout,$retry]));
        $this->mc=$persistent ? new Memcached($pool) : new Memcached();
        $nativeOptions = [
            Memcached::OPT_CONNECT_TIMEOUT=>(int)max(1,ceil((float)$timeout*1000)),
            Memcached::OPT_SERVER_FAILURE_LIMIT=>2,
            Memcached::OPT_RETRY_TIMEOUT=>(int)$retry,
            Memcached::OPT_REMOVE_FAILED_SERVERS=>true,
            Memcached::OPT_DISTRIBUTION=>Memcached::DISTRIBUTION_CONSISTENT,
            Memcached::OPT_LIBKETAMA_COMPATIBLE=>true
        ];
        foreach($nativeOptions as $option=>$value) if(!$this->mc->setOption($option,$value)) throw new RuntimeException('Cannot configure Memcached client');
        if(!$this->mc->getServerList()) foreach($servers as $server) {
            if(!$this->mc->addServer($server['server'],$server['port'],$server['weight'])) throw new RuntimeException('Cannot configure Memcached server pool');
        }
    }
    /**
     * 功能说明：规范化服务器列表，不依赖关联数组键的输入顺序，支持IPv6括号地址。
     *
     * @param string|array $input 服务配置
     * @param int|string $weight 默认权重
     * @return array 规范服务记录列表
     * @throws InvalidArgumentException 地址/端口/权重非法
     */
    public static function servers($input,$weight=5) {
        if(is_string($input) && in_array(substr(ltrim($input),0,1),['[','{'],true)) {
            $json=json_decode($input,true);if(!is_array($json)) $json=json_decode(stripslashes($input),true);if(is_array($json)) $input=$json;
        }
        if(is_string($input)) {
            if(!preg_match('/\A(?:\[([^\]]+)\]|([^:\s]+))(?::(\d+))?\z/D',$input,$m)) throw new InvalidArgumentException('Invalid Memcached address');
            $input=[['server'=>($m[1] ?: $m[2]),'port'=>$m[3] ?? 11211,'weight'=>$weight]];
        } elseif(is_array($input) && (isset($input['server']) || isset($input['host']) || !is_array($input[0] ?? null))) $input=[$input];
        if(!is_array($input) || !$input) throw new InvalidArgumentException('Empty Memcached servers');
        $result=[];
        foreach($input as $record) {
            if(!is_array($record)) throw new InvalidArgumentException('Invalid Memcached server');
            $host=$record['server'] ?? $record['host'] ?? $record[0] ?? '';
            $port=$record['port'] ?? $record[1] ?? 11211; $w=$record['weight'] ?? $record[2] ?? $weight;
            if(!is_string($host) || $host==='' || preg_match('/[\s\x00-\x1f]/',$host)
                || filter_var($port,FILTER_VALIDATE_INT)===false || $port<1 || $port>65535
                || filter_var($w,FILTER_VALIDATE_INT)===false || $w<0) throw new InvalidArgumentException('Invalid Memcached server');
            $result[]=['server'=>$host,'port'=>(int)$port,'weight'=>(int)$w];
        }
        return $result;
    }
    /**
     * 功能说明：以结构化命名空间和业务键生成固定长度的 ms:v3 协议键。
     *
     * @param string|int $key 业务键
     * @return string 带命名空间的固定长度协议键
     * @throws InvalidArgumentException 业务键不是字符串或整数
     */
    protected function key($key) {
        if(!is_string($key) && !is_int($key)) throw new InvalidArgumentException('Invalid cache key');
        return 'ms:v3:'.hash('sha256',serialize([$this->namespace,(string)$key]));
    }
    /**
     * 功能说明：保存值；只省略value参数时删除，0/false/null/空串均正常存储。
     *
     * @param string|int $key 业务键
     * @param mixed $value 值，省略表示删除（默认：''）
     * @param int|float $ttl 秒；0使用默认值，负数表示不超时，超过30天转换为绝对时间
     * @return bool 写入/删除结果
     * @throws InvalidArgumentException TTL 非法或超过 Memcached 32 位过期时间范围
     */
    public function set($key,$value='',$ttl=0) {
        if(func_num_args()<2) return $this->remove($key);
        if(!is_numeric($ttl) || !is_finite((float)$ttl)) throw new InvalidArgumentException('Invalid cache TTL');
        if((float)$ttl==0) $ttl=$this->mc_cnnopt['expire'];
        $now=time();
        $ttl=$ttl<=0 ? 0 : ceil((float)$ttl);
        if($ttl>2592000) $ttl+=$now;
        if($ttl>min(PHP_INT_MAX,4294967295)) throw new InvalidArgumentException('Cache TTL overflows native protocol');
        $ttl=(int)$ttl;
        return $this->mc->set($this->key($key),$value,$ttl);
    }
    /**
     * 功能说明：读取缓存值，并通过引用参数报告原生客户端的精确命中状态。
     *
     * 合法 false/null 也可命中，应判断 hit；后端失败与未命中均返回 false，最近状态可另行检查。
     *
     * @param string|int $key 业务键
     * @param bool &$hit 是否命中
     * @return mixed 原值或未命中的false
     */
    public function fetch($key,&$hit) {$value=$this->mc->get($this->key($key));$hit=(int)$this->mc->getResultCode()===0;return $hit?$value:false;}
    /**
     * 功能说明：按基础缓存协议读取值，未命中或后端失败返回 false。
     *
     * 需要区分缓存中的有效 false/null 时，改用 fetch() 的引用命中标记。
     *
     * @param string|int $key 业务键
     * @return mixed 原值，未命中false
     */
    public function get($key) {return $this->fetch($key,$hit);}
    /**
     * 功能说明：立即删除缓存键；键原本不存在时也视为删除完成。
     *
     * @param string|int $key 业务键
     * @param int $timeout 历史兼容参数，不延迟删除（默认：0）
     * @return bool 删除成功或原本不存在
     */
    public function remove($key,$timeout=0) {return $this->mc->delete($this->key($key)) || (int)$this->mc->getResultCode()===16;}
    /**
     * 功能说明：通过历史兼容入口显式清空整个 Memcached 服务。
     *
     * 与 clear() 完全相同，不能将它当作仅清理到期数据的垃圾回收。
     *
     * 参数说明：无显式参数。
     *
     * @return bool 兼容管理员显式清空，影响当前服务上所有业务键，初始化不会调用
     */
    public function clean() {return $this->clear();}
    /**
     * 功能说明：显式刷新整个 Memcached 服务，影响其他命名空间。
     *
     * 仅用于显式管理操作；该操作不按 namespace 隔离，也不在初始化时调用。
     *
     * 参数说明：无显式参数。
     *
     * @return bool 清空整个服务，包括其他命名空间；仅供管理员明确执行
     */
    public function clear() {return $this->mc->flush();}
    /**
     * 功能说明：调用原生 quit 释放当前客户端连接，不删除服务端数据。
     *
     * 参数说明：无显式参数。
     *
     * @return bool 断开当前客户端，不删除服务端数据
     */
    public function close() {return $this->mc->quit();}
    /**
     * 功能说明：返回适配器持有的原生 Memcached 客户端。
     *
     * 参数说明：无显式参数。
     *
     * @return Memcached 原生客户端
     */
    public function obj() {return $this->mc;}
    /**
     * 功能说明：判断最近操作是否成功或只是正常未命中，不主动探测服务器。
     *
     * 参数说明：无显式参数。
     *
     * @return bool 最近操作成功或正常未命中；不把未命中作为PHP错误
     */
    public function check() {return in_array((int)$this->mc->getResultCode(),[0,16],true);}
}
