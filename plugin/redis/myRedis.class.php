<?php
/*******************************************
*                                          *
* Name    : My Redis (new)                 *
* Modifier: MyStep Framework               *
* Time    : 2026-10-24                     *
* Email   : windy2006@gmail.com            *
* HomePage: www.mysteps.cn                 *
* Notice  : U Can Use & Modify it freely,  *
*           BUT PLEASE HOLD THIS ITEM.     *
*                                          *
*******************************************/
/**
 * 文件：plugin/redis/myRedis.class.php
 * 功能说明：Redis连接、站点命名空间及带所有者校验的单实例租约锁。
 * 方法目录：
 *   public myRedis::__construct($setting = []) — 验证连接配置，延迟连接直到client或锁操作被调用。
 *   public static myRedis::settings($setting = []) — 补齐并验证Redis公共配置，不修改传入对象或保存站点文件。
 *   public static myRedis::configure(myConfig $config,$probe = false) — 规范插件配置对象并按需检查连接，供新旧后台保存插件设置时共用。
 *   public myRedis::client() — 按需连接Redis，设置有限超时、原始字节模式、认证和逻辑数据库。
 *   public myRedis::prefix($scope) — 生成用途前缀；相同配置在同一站点下共享，缓存/会话/业务键互相分离。
 *   public myRedis::key($scope,$key) — 将有限标量逻辑键转换为固定长度摘要，避免特殊字符或超长键。
 *   public myRedis::acquire($key,$wait=null) — 用SET NX PX获取有限租约锁，等待超时不继续执行受保护操作。
 *   public myRedis::release($key,$token) — 通过Lua比较令牌再删除锁，不会释放后继请求持有的锁。
 *   public myRedis::close() — 关闭当前非持久连接，后续client调用会重新连接。
 * 使用说明：new myRedis(plugin_redis::options())；client()按需连接，key('business','counter')生成业务键。
 *   缓存和会话复用连接规则，使用独立连接及不同键前缀；不共享持久连接、不更改php.ini。
 *   基于phpredis>=5.3，支持Redis单实例、ACL、逻辑库和验证证书的TLS；不实现Cluster/Sentinel发现。
 *   锁是有限租约，不承诺故障切换下恰好一次；耗时操作须小于lock_ttl。
 */
/**
 * 功能说明：集中验证Redis配置，管理非持久连接及站点隔离键。
 * 方法目录：参见文件头部；原生签名兼容PHP7.4，类型含义由PHPDoc说明。
 */
class myRedis {
    private $setting, $connection = null;

    /**
     * 功能说明：验证连接配置，延迟连接直到client或锁操作被调用。
     * @param array|object $setting host/port/database/auth/username/password/prefix/site_id/timeout/read_timeout/tls/cafile/lock_wait/lock_ttl/max_bytes（默认：[]）
     * @return void 初始化完成；不执行网络请求
     * @throws InvalidArgumentException 配置类型或取值不合法
     */
    public function __construct($setting = []) { $this->setting = self::settings($setting); }

    /**
     * 功能说明：补齐并验证Redis公共配置，不修改传入对象或保存站点文件。
     * @param array|object $setting 可省略的配置分组，表单数字字符串会规范为数字（默认：[]）
     * @return array 完整配置；空前缀拒绝，密码保留原始字节
     * @throws InvalidArgumentException 主机、超时、库编号、前缀、TLS或容量配置不合法
     */
    public static function settings($setting = []) {
        if(is_object($setting))$setting=(array)$setting;
        if(!is_array($setting))throw new InvalidArgumentException('Invalid Redis configuration.');
        $defaults=['host'=>'127.0.0.1','port'=>6379,'database'=>0,'auth'=>false,'username'=>'','password'=>'','prefix'=>'mystep:','site_id'=>'',
            'timeout'=>1,'read_timeout'=>2,'tls'=>false,'cafile'=>'','lock_wait'=>5,'lock_ttl'=>120,'max_bytes'=>1048576];
        $s=array_replace($defaults,$setting);
        if(!array_key_exists('auth',$setting))$s['auth']=$s['password']!=='';
        foreach(['host','username','password','prefix','site_id','cafile'] as $key)if(!is_string($s[$key]))throw new InvalidArgumentException('Invalid Redis '.$key.'.');
        $host=$s['host'];
        if(substr($host,0,1)==='[' && substr($host,-1)===']')$host=substr($host,1,-1);
        if($host==='' || (filter_var($host,FILTER_VALIDATE_IP)===false && !preg_match('/\A[A-Za-z0-9_][A-Za-z0-9_.-]{0,252}\z/D',$host)))throw new InvalidArgumentException('Redis host must be a hostname or IP without a scheme or port.');
        $s['host']=$host;
        if(!preg_match('/\A[A-Za-z0-9_:-]{1,128}\z/D',$s['prefix']))throw new InvalidArgumentException('Redis prefix must contain 1..128 letters, digits, _, : or -.');
        if(!preg_match('/\A[A-Za-z0-9_.:-]{0,128}\z/D',$s['site_id']))throw new InvalidArgumentException('Invalid Redis site identifier.');
        foreach(['port'=>[1,65535],'database'=>[0,65535],'lock_ttl'=>[1,3600],'max_bytes'=>[1,67108864]] as $key=>$range){
            if(filter_var($s[$key],FILTER_VALIDATE_INT)===false || $s[$key]<$range[0] || $s[$key]>$range[1])throw new InvalidArgumentException('Invalid Redis '.$key.'.');
            $s[$key]=(int)$s[$key];
        }
        foreach(['timeout'=>[0.01,60],'read_timeout'=>[0.01,60],'lock_wait'=>[0,60]] as $key=>$range){
            if(!is_numeric($s[$key]) || !is_finite((float)$s[$key]) || $s[$key]<$range[0] || $s[$key]>$range[1])throw new InvalidArgumentException('Invalid Redis '.$key.'.');
            $s[$key]=(float)$s[$key];
        }
        if(!is_bool($s['tls'])||!is_bool($s['auth']))throw new InvalidArgumentException('Redis tls/auth must be boolean.');
        if($s['auth'] && $s['password']==='')throw new InvalidArgumentException('Redis authentication requires a password.');
        if($s['cafile']!=='')$s['cafile']=myPath::relative($s['cafile']);
        return $s;
    }

    /**
     * 功能说明：规范插件配置对象并按需检查连接，供新旧后台保存插件设置时共用。
     * @param myConfig $config 读取或保存中的Redis插件配置
     * @param bool $probe 是否在保存前验证扩展及实际连接（默认：false）
     * @return void 调整对象内存，不写文件；probe启用时完成PING后关闭探测连接
     * @throws InvalidArgumentException Redis分组存在无效设置
     * @throws InvalidArgumentException 选择Redis存储但扩展、连接、认证、选库或PING检查失败
     */
    public static function configure(myConfig $config,$probe = false) {
        $options=self::settings($config->get(true));$config->merge($options);
        if(!$probe)return;
        $connection=new self($options);
        try{if($connection->client()->ping()===false)throw new RuntimeException();}
        catch(Throwable $error){throw new InvalidArgumentException('Redis连接检查失败，请检查服务、PHP扩展、地址、认证和数据库配置。');}
        finally{$connection->close();}
    }

    /**
     * 功能说明：按需连接Redis，设置有限超时、原始字节模式、认证和逻辑数据库。
     * 参数说明：无显式参数。
     * @return Redis 原生客户端；业务操作应配合key('business',...)隔离键
     * @throws RuntimeException 扩展缺失、连接/认证/选库失败；消息不包含凭据
     */
    public function client() {
        if($this->connection!==null)return $this->connection;
        if(!extension_loaded('redis') || version_compare(phpversion('redis'),'5.3.0','<'))throw new RuntimeException('Redis support requires phpredis 5.3 or newer.');
        $r=new Redis();$s=$this->setting;
        try{
            $context=$s['tls']?['stream'=>['verify_peer'=>true,'verify_peer_name'=>true]]:[];
            if($s['tls']&&$s['cafile']!=='')$context['stream']['cafile']=myPath::absolute($s['cafile']);
            $host=$s['tls']?'tls://'.(strpos($s['host'],':')!==false?'['.$s['host'].']':$s['host']):$s['host'];
            if(!@$r->connect($host,$s['port'],$s['timeout'],null,0,$s['read_timeout'],$context))throw new RuntimeException();
            if(!$r->setOption(Redis::OPT_SERIALIZER,Redis::SERIALIZER_NONE) || !$r->setOption(Redis::OPT_READ_TIMEOUT,$s['read_timeout']))throw new RuntimeException();
            if($s['auth'] && !$r->auth($s['username']===''?$s['password']:[$s['username'],$s['password']]))throw new RuntimeException();
            if(!$r->select($s['database']))throw new RuntimeException();
            $this->connection=$r;return $r;
        }catch(Throwable $error){try{$r->close();}catch(Throwable $ignored){}throw new RuntimeException('Redis连接、认证或选库失败，请检查Redis插件设置及服务状态。');}
    }

    /**
     * 功能说明：生成用途前缀；相同配置在同一站点下共享，缓存/会话/业务键互相分离。
     * @param string $scope cache/session/business等字母数字用途；不得包含通配符
     * @return string 配置前缀、site_id摘要和用途前缀；site_id留空时使用ROOT摘要
     * @throws InvalidArgumentException 用途不合法
     */
    public function prefix($scope) {
        if(!is_string($scope) || !preg_match('/\A[a-zA-Z0-9_-]{1,64}\z/D',$scope))throw new InvalidArgumentException('Invalid Redis scope.');
        $root=defined('ROOT')?ROOT:dirname(__DIR__).'/';
        return $this->setting['prefix'].substr(hash('sha256',$this->setting['site_id']===''?$root:$this->setting['site_id']),0,24).':'.$scope.':';
    }

    /**
     * 功能说明：将有限标量逻辑键转换为固定长度摘要，避免特殊字符或超长键。
     * @param string $scope 键用途
     * @param scalar|null $key 有限标量或null，类型不同但文本相同的值视为同键
     * @return string 带用途和站点前缀的物理键
     * @throws InvalidArgumentException 键为对象、数组、资源或非有限浮点数
     */
    public function key($scope,$key) {
        if((!is_scalar($key)&&$key!==null)||(is_float($key)&&!is_finite($key)))throw new InvalidArgumentException('Redis key must be a finite scalar.');
        return $this->prefix($scope).hash('sha256',(string)$key);
    }

    /**
     * 功能说明：用SET NX PX获取有限租约锁，等待超时不继续执行受保护操作。
     * @param string $key 已生成的锁物理键
     * @param int|float|null $wait 等待秒数0..60；null使用配置lock_wait（默认：null）
     * @return string 随机所有者令牌
     * @throws RuntimeException 服务不可用或等待超时
     * @throws InvalidArgumentException 等待时间不合法
     */
    public function acquire($key,$wait=null) {
        $wait=$wait??$this->setting['lock_wait'];
        if(!is_numeric($wait)||!is_finite((float)$wait)||$wait<0||$wait>60)throw new InvalidArgumentException('Invalid Redis lock wait.');
        $token=bin2hex(random_bytes(24));$end=microtime(true)+(float)$wait;
        do{
            if($this->client()->set($key,$token,['nx','px'=>$this->setting['lock_ttl']*1000]))return $token;
            if(microtime(true)>=$end)break;
            usleep((int)min(10000,max(1,($end-microtime(true))*1000000)));
        }while(true);
        throw new RuntimeException('Redis lock wait timed out.');
    }

    /**
     * 功能说明：通过Lua比较令牌再删除锁，不会释放后继请求持有的锁。
     * @param string $key 锁物理键
     * @param string $token 本请求获取的所有者令牌
     * @return bool 当前令牌成功释放为true，过期或已转移所有权为false
     * @throws RedisException 连接故障
     */
    public function release($key,$token) {
        return $this->client()->eval("if redis.call('GET',KEYS[1])==ARGV[1] then return redis.call('DEL',KEYS[1]) end return 0",[$key,$token],1)===1;
    }

    /**
     * 功能说明：关闭当前非持久连接，后续client调用会重新连接。
     * 参数说明：无显式参数。
     * @return bool 已关闭或无需关闭为true，关闭异常为false
     */
    public function close() {
        $r=$this->connection;$this->connection=null;
        if($r===null)return true;
        try{return $r->close();}catch(Throwable $error){return false;}
    }
}
