<?php
/*******************************************
*                                          *
* Name    : Redis Cache Driver (new)       *
* Modifier: MyStep Framework               *
* Time    : 2026-10-24                     *
* Email   : windy2006@gmail.com            *
* HomePage: www.mysteps.cn                 *
* Notice  : U Can Use & Modify it freely,  *
*           BUT PLEASE HOLD THIS ITEM.     *
*                                          *
*******************************************/
/**
 * 文件：plugin/redis/myCache_Redis.class.php
 * 功能说明：Redis缓存驱动，支持精确命中、TTL、计算锁和当前命名空间清理。
 * 方法目录：
 *   public myCache_Redis::__construct($setting=null) — 验证配置并建立Redis连接，失败时保留代理原来的缓存后端。
 *   public myCache_Redis::set($key,$value='',$ttl=600) — 保存统一编码的数据及TTL，省略value或非正TTL沿用删除约定。
 *   public myCache_Redis::fetch($key,&$hit) — 在Redis端检查字节预算后读取并解码，同时返回精确命中状态。
 *   public myCache_Redis::get($key) — 兼容读取入口；精确区分有效false时使用fetch。
 *   public myCache_Redis::remove($key) — 删除当前命名空间的单个缓存键，键不存在也算成功。
 *   public myCache_Redis::clean() — 沿用过期回收入口；Redis自动回收到期键，不清空有效数据。
 *   public myCache_Redis::clear() — 用SCAN分批删除当前站点、当前namespace的value键，保留锁、会话和业务键。
 *   public myCache_Redis::synchronized($key,callable $operation,$timeout=5) — 持有逻辑键租约锁执行回调，异常也释放；令牌校验避免释放其他请求的锁。
 * 使用说明：new myCache('Redis',$options)；接口沿用File等驱动，false/null/空字符串均为有效值。
 *   clean依赖Redis原生到期回收；clear仅扫描当前站点、当前namespace的缓存键，不使用FLUSHDB/FLUSHALL。
 *   namespace继续使用myCacheCodec语义；计算锁为有限租约，回调须短于lock_ttl。
 */
require_once LIB.'cache/interface_cache.class.php';
require_once LIB.'cache/myCacheCodec.class.php';
require_once __DIR__.'/myRedis.class.php';
/**
 * 功能说明：以框架统一序列化封装连接Redis，缓存故障不伪造命中。
 * 方法目录：参见文件头部。
 */
class myCache_Redis implements interface_cache,interface_cache_lookup,interface_cache_lock {
    private $store, $codec, $prefix, $locks=[];

    /**
     * 功能说明：验证配置并建立Redis连接，失败时保留代理原来的缓存后端。
     * @param array|null $setting Redis公共设置及namespace/max_depth/allowed_classes，null使用默认设置
     * @return void 初始化完成
     * @throws InvalidArgumentException 配置或编解码选项不合法
     * @throws RuntimeException Redis扩展或服务不可用
     */
    public function __construct($setting=null) {
        $setting=$setting??[];if(!is_array($setting))throw new InvalidArgumentException('Redis cache options must be an array.');
        $this->codec=new myCacheCodec($setting);$this->store=new myRedis($setting);$this->store->client();
        $namespace=$setting['namespace']??'';
        $this->prefix=$this->store->prefix('cache').hash('sha256',$namespace).':';
    }

    /**
     * 功能说明：保存统一编码的数据及TTL，省略value或非正TTL沿用删除约定。
     * @param scalar|null $key 逻辑缓存键
     * @param mixed $value 可序列化值，显式null/false有效；默认空字符串
     * @param int|float|string $ttl 秒数，正小数向上取整，默认600
     * @return bool 完整写入或删除成功为true，后端故障为false
     * @throws InvalidArgumentException 键、TTL或资源值不合法
     * @throws LengthException 序列化内容超过预算
     */
    public function set($key,$value='',$ttl=600) {
        $expire=myCacheCodec::expires($ttl);$id=$this->codec->key($key);
        if(func_num_args()===1||!$expire)return $this->remove($key);
        $data=$this->codec->pack($value);$physical=$this->prefix.'value:'.$id;
        try{
            if(isset($this->locks[$id]))return $this->store->client()->eval("if redis.call('GET',KEYS[1])~=ARGV[1] then return 0 end redis.call('SET',KEYS[2],ARGV[2],'EX',ARGV[3]); return 1",[$this->prefix.'lock:'.$id,$physical,$this->locks[$id],$data,(string)max(1,$expire-time())],2)===1;
            return $this->store->client()->setex($physical,max(1,$expire-time()),$data);
        }catch(RedisException $error){return false;}
    }

    /**
     * 功能说明：在Redis端检查字节预算后读取并解码，同时返回精确命中状态。
     * @param scalar|null $key 逻辑缓存键
     * @param bool &$hit 有效数据为true；缺失、损坏、超限或服务故障为false
     * @return mixed 原始缓存值；未命中返回false
     * @throws InvalidArgumentException 键不合法
     */
    public function fetch($key,&$hit) {
        $hit=false;$physical=$this->prefix.'value:'.$this->codec->key($key);
        try{
            $data=$this->store->client()->eval("if redis.call('STRLEN',KEYS[1])>tonumber(ARGV[1]) then return false end return redis.call('GET',KEYS[1])",[$physical,(string)$this->codec->maxBytes()],1);
        }catch(RedisException $error){return false;}
        if(!is_string($data)||strlen($data)>$this->codec->maxBytes())return false;
        return $this->codec->unpack($data,$hit);
    }

    /**
     * 功能说明：兼容读取入口；精确区分有效false时使用fetch。
     * @param scalar|null $key 逻辑缓存键
     * @return mixed 有效值或未命中false
     */
    public function get($key) { return $this->fetch($key,$hit); }

    /**
     * 功能说明：删除当前命名空间的单个缓存键，键不存在也算成功。
     * @param scalar|null $key 逻辑缓存键
     * @return bool 后端完成删除为true，连接故障为false
     */
    public function remove($key) {
        $physical=$this->prefix.'value:'.$this->codec->key($key);
        try{return $this->store->client()->del($physical)!==false;}catch(RedisException $error){return false;}
    }

    /**
     * 功能说明：沿用过期回收入口；Redis自动回收到期键，不清空有效数据。
     * 参数说明：无显式参数。
     * @return bool Redis可响应PING为true，连接故障为false
     */
    public function clean() {
        try{return $this->store->client()->ping()!==false;}catch(RedisException $error){return false;}
    }

    /**
     * 功能说明：用SCAN分批删除当前站点、当前namespace的value键，保留锁、会话和业务键。
     * 参数说明：无显式参数；并发请求可能在清理后重新生成缓存。
     * @return bool 扫描及删除成功为true，后端故障为false
     */
    public function clear() {
        try{
            $r=$this->store->client();$cursor='0';
            do{
                // phpredis 5.3的scan会吞掉部分ACL错误；读取原始响应以区分拒绝和空结果。
                $reply=$r->rawCommand('SCAN',$cursor,'MATCH',$this->prefix.'value:*','COUNT','200');
                if(!is_array($reply)||count($reply)!==2||!is_string($reply[0])||!ctype_digit($reply[0])||!is_array($reply[1]))return false;
                $cursor=$reply[0];$keys=$reply[1];
                foreach($keys as $key)if(!is_string($key)||strpos($key,$this->prefix.'value:')!==0)return false;
                if($keys && $r->del($keys)===false)return false;
            }while($cursor!=='0');
            return true;
        }catch(RedisException $error){return false;}
    }

    /**
     * 功能说明：持有逻辑键租约锁执行回调，异常也释放；令牌校验避免释放其他请求的锁。
     * @param scalar|null $key 与缓存逻辑键对应的锁键
     * @param callable $operation 无参数计算回调
     * @param int|float|string $timeout 最长等待秒数0..60，默认5
     * @return mixed 回调返回值
     * @throws LogicException 同一实例递归获取同一锁
     * @throws RuntimeException 等待超时、服务故障或执行后发现租约丢失
     * @throws Throwable 回调异常原样传递，释放操作不覆盖原异常
     */
    public function synchronized($key,callable $operation,$timeout=5) {
        $timeout=myCacheCodec::timeout($timeout);$id=$this->codec->key($key);
        if(isset($this->locks[$id]))throw new LogicException('Redis computation lock is not reentrant.');
        $physical=$this->prefix.'lock:'.$id;$token=$this->store->acquire($physical,$timeout);$this->locks[$id]=$token;
        try{
            $result=$operation();
            if($this->store->client()->get($physical)!==$token)throw new RuntimeException('Redis computation lock lease expired.');
            return $result;
        }finally{unset($this->locks[$id]);try{$this->store->release($physical,$token);}catch(Throwable $ignored){}}
    }
}
