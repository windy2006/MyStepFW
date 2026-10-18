<?PHP

/**
 * 文件：lib/cache/myCache_MySQL.class.php
 * 功能说明：MySQL缓存驱动
 *   使用专用mysqli连接、参数化UPSERT及命名锁提供数据库缓存。
 *
 * 方法目录：
 *   public myCache_MySQL::__construct($setting) — 连接并检查/创建缓存表；已有表不反复执行DDL，容量检查防止宽松SQL模式截断。
 *   protected myCache_MySQL::codec() — 取得当前编解码策略，未初始化时采用默认配置。
 *   public myCache_MySQL::getLastError() — 返回最近错误摘要，不含SQL、凭据或正文。
 *   protected myCache_MySQL::run($sql, array $params) — 执行内部参数化模板；关闭连接/SQL错误均转换为false，及时释放statement。
 *   public myCache_MySQL::set($key, $value = '', $ttl = 600) — 原子覆盖条目，拒绝超出配置/旧表列容量或时间列范围的写入，保留旧值。
 *   public myCache_MySQL::fetch($key, &$hit = null) — 读取有效条目，SQL过滤过期记录后才解码对象。
 *   public myCache_MySQL::get($key) — 兼容旧读取入口。
 *   public myCache_MySQL::remove($key) — 删除单个键。
 *   public myCache_MySQL::clean() — 全量回收过期条目，不清空有效值。
 *   public myCache_MySQL::clear() — 显式删除当前缓存表全部条目，包含各namespace；不修改表结构或命名锁。
 *   public myCache_MySQL::collectGarbage($limit = 200) — 有预算的过期回收；同表所有namespace的过期值均可被清理。
 *   public myCache_MySQL::synchronized($key, callable $operation, $timeout = 5) — 同一MySQL写实例上的计算命名锁，超时/故障不执行回调。
 *   public myCache_MySQL::close() — 关闭自有连接，外部注入的借用连接不在此关闭；重复调用安全。
 *   public myCache_MySQL::__destruct() — 释放本驱动自有连接。
 *
 * 使用说明：
 *   1. 通过myCache的MySQL驱动传入host/user/password/name等连接及编解码选项。
 *   2. 需要mysqli/mysqlnd；已有表会检查键唯一性、容量及时间列范围。
 *   3. 支持fetch和计算锁；业务数据库事务与缓存专用连接相互独立。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名保持PHP 7.4兼容，PHPDoc类型用于说明，不额外强制运行时类型。
 */
require_once __DIR__.'/myCacheCodec.class.php';
/**
 * 功能说明：使用专用mysqli连接、参数化UPSERT及命名锁提供数据库缓存。
 *
 * 使用说明：
 *   1. 通过myCache的MySQL驱动传入host/user/password/name等连接及编解码选项。
 *   2. 需要mysqli/mysqlnd；已有表会检查键唯一性、容量及时间列范围。
 *   3. 支持fetch和计算锁；业务数据库事务与缓存专用连接相互独立。
 *
 * 补充说明：
 * MySQL缓存：专用连接、参数化UPSERT、容量保护及精确命中。
 * 兼容旧TEXT/原始序列化和MS_CACHE2编码；普通请求不ALTER已有表。
 * 可选table和namespace隔离业务键，synchronized用同一写实例的命名锁；需要mysqli/mysqlnd。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 */
class myCache_MySQL implements interface_cache, interface_cache_lookup, interface_cache_lock {
    protected $cnt = null, $owned = false, $codec = null, $table = 'my_cache', $error = '', $capacity = 16777215, $expiration_max = PHP_INT_MAX, $identity = '', $locks = [];

    /**
     * 功能说明：连接并检查/创建缓存表；已有表不反复执行DDL，容量检查防止宽松SQL模式截断。
     *
     * @param array $setting host/user/password/name，可选port/socket/charset/table及公共编解码选项
     * @return void 初始化或释放资源，无返回值。
     * @throws InvalidArgumentException 配置非法
     * @throws RuntimeException 连接、表初始化或结构检查失败，错误不含认证信息
     * @phpstan-param array{host:string,user:string,name:string,password?:string,charset?:string,table?:string,port?:int,socket?:string|null,max_bytes?:int,max_depth?:int,allowed_classes?:bool|list<string>,namespace?:string} $setting
     */
    public function __construct($setting) {
        if(!is_array($setting))throw new InvalidArgumentException('MySQL cache settings must be an array');
        foreach(['host','user','name']as$key)if(!isset($setting[$key])||!is_string($setting[$key])||$setting[$key]==='')throw new InvalidArgumentException('Missing MySQL cache configuration');
        $this->codec=new myCacheCodec($setting);$this->table=$setting['table']??'my_cache';
        if(!is_string($this->table)||!preg_match('/\A[a-zA-Z0-9_]{1,64}\z/D',$this->table))throw new InvalidArgumentException('Invalid cache table');
        if(isset($setting['charset'])&&!is_string($setting['charset']))throw new InvalidArgumentException('Invalid cache charset');
        $charset=strtolower(str_replace('-','',$setting['charset']??'utf8mb4'));if($charset==='utf8')$charset='utf8mb4';
        if(!preg_match('/\A[a-z0-9_]+\z/D',$charset))throw new InvalidArgumentException('Invalid cache charset');
        try {
            $host=strpos($setting['host'],'p:')===0?substr($setting['host'],2):$setting['host'];
            $this->cnt=mysqli_init();$this->owned=true;$this->cnt->options(MYSQLI_OPT_CONNECT_TIMEOUT,5);
            if(!@$this->cnt->real_connect($host,$setting['user'],$setting['password']??'',$setting['name'],(int)($setting['port']??ini_get('mysqli.default_port')),$setting['socket']??null)||!@$this->cnt->set_charset($charset))throw new RuntimeException();
            $sql='SELECT COLUMN_NAME,DATA_TYPE,CHARACTER_OCTET_LENGTH,CHARACTER_MAXIMUM_LENGTH,COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?';
            $meta=$this->run($sql,[$this->table]);if(!($meta instanceof mysqli_result))throw new RuntimeException();
            try{$columns=$meta->fetch_all(MYSQLI_ASSOC);}finally{$meta->free();}
            if(!$columns) {
                if(!$this->run('CREATE TABLE IF NOT EXISTS `'.$this->table.'` (`key` char(32) NOT NULL,`expiration` bigint NOT NULL,`value` mediumblob NOT NULL,PRIMARY KEY (`key`),INDEX `expiration` (`expiration`)) ENGINE=InnoDB',[]))throw new RuntimeException();
                $meta=$this->run($sql,[$this->table]);if(!($meta instanceof mysqli_result))throw new RuntimeException();
                try{$columns=$meta->fetch_all(MYSQLI_ASSOC);}finally{$meta->free();}
            }
            $columns=array_change_key_case(array_column($columns,null,'COLUMN_NAME'),CASE_LOWER);
            if(!isset($columns['key'],$columns['value'],$columns['expiration'])||!in_array($columns['value']['DATA_TYPE'],['text','mediumtext','longtext','tinytext','blob','mediumblob','longblob','tinyblob','varchar','varbinary'],true))throw new RuntimeException();
            if(!in_array($columns['key']['DATA_TYPE'],['char','varchar','binary','varbinary'],true)||(int)$columns['key']['CHARACTER_MAXIMUM_LENGTH']<32)throw new RuntimeException();
            $unique=$this->run('SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND NON_UNIQUE=0 GROUP BY INDEX_NAME HAVING COUNT(*)=1 AND MIN(COLUMN_NAME)=? AND MIN(COALESCE(SUB_PART,32))>=32',[$this->table,'key']);
            if(!($unique instanceof mysqli_result))throw new RuntimeException();
            try{if($unique->num_rows<1)throw new RuntimeException();}finally{$unique->free();}
            $this->capacity=(int)$columns['value']['CHARACTER_OCTET_LENGTH'];
            $type=$columns['expiration']['DATA_TYPE'];$unsigned=strpos($columns['expiration']['COLUMN_TYPE'],'unsigned')!==false;
            $max=['tinyint'=>127,'smallint'=>32767,'mediumint'=>8388607,'int'=>2147483647,'bigint'=>PHP_INT_MAX];
            if(!isset($max[$type]))throw new RuntimeException();
            $this->expiration_max=$type==='bigint'?PHP_INT_MAX:($unsigned?$max[$type]*2+1:$max[$type]);
            $this->identity=hash('sha256',strtolower($setting['name'])."\0".strtolower($this->table));
        }catch(Throwable$e){$this->close();throw new RuntimeException('Cannot initialize MySQL cache');}
    }

    /**
     * 功能说明：取得当前编解码策略，未初始化时采用默认配置。
     *
     * 参数说明：无显式参数。
     *
     * @return myCacheCodec 本驱动当前使用的编解码策略实例，缺失时创建默认实例
     */
    protected function codec() {if($this->codec===null)$this->codec=new myCacheCodec();return $this->codec;}

    /**
     * 功能说明：返回最近错误摘要，不含SQL、凭据或正文。
     *
     * 参数说明：无显式参数。
     *
     * @return string 最近一次安全错误摘要；无已记录错误时为空串
     */
    public function getLastError() {return $this->error;}

    /**
     * 功能说明：执行内部参数化模板；关闭连接/SQL错误均转换为false，及时释放statement。
     *
     *
     * 异常处理：内部捕获连接/SQL执行异常并返回false，错误摘要保存在对应getLastError状态；调用方不应仅依赖catch判断失败。
     *
     * @param string $sql 可信模板，表名已在构造阶段校验
     * @param array $params 顺序绑定值
     * @return mysqli_result|bool 结果由调用者释放，非查询为bool
     */
    protected function run($sql, array $params) {
        $stmt=null;$this->error='';
        try {
            if(!($this->cnt instanceof mysqli))throw new RuntimeException();
            $stmt=@$this->cnt->prepare($sql);if(!$stmt)throw new RuntimeException();
            if($params&&!$stmt->bind_param(str_repeat('s',count($params)),...$params))throw new RuntimeException();
            if(!@$stmt->execute())throw new RuntimeException();
            return $stmt->field_count?$stmt->get_result():true;
        }catch(Throwable$e){$this->error='MySQL cache operation failed.';return false;}
        finally{if($stmt instanceof mysqli_stmt)try{@$stmt->close();}catch(Throwable$e){}}
    }

    /**
     * 功能说明：原子覆盖条目，拒绝超出配置/旧表列容量或时间列范围的写入，保留旧值。
     *
     * @param mixed $key 有限标量键
     * @param mixed $value 受信数据，省略时删除（默认：''）
     * @param int|float $ttl 有效秒数，非正数删除（默认：600）
     * @return bool UPSERT成功为true；省略value或TTL非正时转为删除；编码/容量/后端失败为false
     * @throws Throwable 编码或参数校验异常继续传递
     */
    public function set($key, $value = '', $ttl = 600) {
        if(func_num_args()<2)return $this->remove($key);
        $hash=$this->codec()->key($key);$expire=myCacheCodec::expires($ttl);if(!$expire)return $this->remove($key);
        try{$data='MS_CACHE2:'.base64_encode($this->codec()->pack($value));}catch(LengthException$e){$this->error='Cache value exceeds encoding budget.';return false;}
        if(strlen($data)>$this->capacity||$expire>$this->expiration_max){$this->error='Cache record exceeds table capacity.';return false;}
        return $this->run('INSERT INTO `'.$this->table.'` (`key`,`expiration`,`value`) VALUES(?,?,?) ON DUPLICATE KEY UPDATE `expiration`=VALUES(`expiration`),`value`=VALUES(`value`)',[$hash,$expire,$data]);
    }

    /**
     * 功能说明：读取有效条目，SQL过滤过期记录后才解码对象。
     *
     * @param mixed $key 缓存键
     * @param bool|null &$hit 引用参数；精确命中输出（默认：null）
     * @return mixed 有效值或false
     */
    public function fetch($key, &$hit = null) {
        $hit=false;$hash=$this->codec()->key($key);
        $result=$this->run('SELECT `value` FROM `'.$this->table.'` WHERE `key`=? AND `expiration`>? AND OCTET_LENGTH(`value`)<=?',[$hash,time(),(int)(ceil($this->codec()->maxBytes()/3)*4+10)]);
        if(!($result instanceof mysqli_result))return false;
        try{$row=$result->fetch_assoc();if(!$row)return false;$value=$this->codec()->sqlUnpack($row['value'],$valid);if($valid){$hit=true;return $value;}$this->error='Invalid cached payload.';return false;}
        finally{$result->free();}
    }

    /**
     * 功能说明：兼容旧读取入口。
     *
     * @param mixed $key 缓存键
     * @return mixed 有效false与未命中不区分，需区分时使用fetch
     */
    public function get($key) {return $this->fetch($key,$hit);}

    /**
     * 功能说明：删除单个键。
     *
     * @param mixed $key 缓存键
     * @return bool 原本不存在也成功
     */
    public function remove($key) {return $this->run('DELETE FROM `'.$this->table.'` WHERE `key`=?',[$this->codec()->key($key)]);}

    /**
     * 功能说明：全量回收过期条目，不清空有效值。
     *
     * 参数说明：无显式参数。
     *
     * @return bool 过期记录删除语句是否成功；有效记录继续保留
     */
    public function clean() {return $this->run('DELETE FROM `'.$this->table.'` WHERE `expiration`<=?',[time()]);}

    /**
     * 功能说明：显式删除当前缓存表全部条目，包含各namespace；不修改表结构或命名锁。
     *
     * 并发请求仍可在清空后重新写入；不影响使用其他表的缓存实例。
     *
     * 参数说明：无显式参数。
     *
     * @return bool DELETE执行成功为true，连接或SQL失败为false
     */
    public function clear() {return $this->run('DELETE FROM `'.$this->table.'`', []);}

    /**
     * 功能说明：有预算的过期回收；同表所有namespace的过期值均可被清理。
     *
     * @param int $limit 最多删除条目数，默认200，0不限（默认：200）
     * @return bool 预算内删除过期记录的语句是否成功，不表示清理条数
     * @throws InvalidArgumentException 回收预算不是非负整数
     */
    public function collectGarbage($limit = 200) {
        if(!is_int($limit)||$limit<0)throw new InvalidArgumentException('Invalid cache GC limit');
        return $this->run('DELETE FROM `'.$this->table.'` WHERE `expiration`<=?'.($limit?' LIMIT '.$limit:''),[time()]);
    }

    /**
     * 功能说明：同一MySQL写实例上的计算命名锁，超时/故障不执行回调。
     *
     * @param mixed $key 逻辑锁键
     * @param callable $operation 无参数回调
     * @param int|float $timeout 0..60秒（默认：5）
     * @return mixed 回调结果
     * @throws RuntimeException 锁不可用
     * @throws LogicException 同一适配器重入同键
     * @template TResult
     * @phpstan-param callable(): TResult $operation
     * @phpstan-return TResult
     */
    public function synchronized($key, callable $operation, $timeout = 5) {
        $wait=myCacheCodec::timeout($timeout);$name=hash('sha256','mystep-cache:'.$this->identity.':'.$this->codec()->key($key));
        if(isset($this->locks[$name]))throw new LogicException('Recursive cache computation lock');
        $r=$this->run('SELECT GET_LOCK(?,?) AS acquired',[$name,$wait]);$acquired=false;
        if($r instanceof mysqli_result){try{$acquired=(int)($r->fetch_assoc()['acquired']??0)===1;}finally{$r->free();}}
        if(!$acquired)throw new RuntimeException('Cache computation lock unavailable');
        $this->locks[$name]=true;
        try{return $operation();}
        finally {
            unset($this->locks[$name]);$previous=$this->error;
            $r=$this->run('SELECT RELEASE_LOCK(?) AS released',[$name]);$released=false;
            if($r instanceof mysqli_result){try{$released=(int)($r->fetch_assoc()['released']??0)===1;}finally{$r->free();}}
            if(!$released){$this->close();$this->error='Cache computation lock release failed.';}
            elseif($previous!=='')$this->error=$previous;
        }
    }

    /**
     * 功能说明：关闭自有连接，外部注入的借用连接不在此关闭；重复调用安全。
     *
     * 参数说明：无显式参数。
     *
     * @return void 无返回值。
     */
    public function close() {
        if($this->owned&&$this->cnt instanceof mysqli)try{@$this->cnt->close();}catch(Throwable$e){}
        $this->cnt=null;$this->owned=false;$this->locks=[];
    }

    /**
     * 功能说明：释放本驱动自有连接。
     *
     * 参数说明：无显式参数。
     *
     * @return void 初始化或释放资源，无返回值。
     */
    public function __destruct() {$this->close();}
}
