<?PHP

/**
 * 文件：lib/cache/myCache_MSSQL.class.php
 * 功能说明：SQL Server缓存驱动
 *   使用专用SQLSRV连接实现参数化读写、容量检查、精确命中及过期回收。
 *
 * 方法目录：
 *   public myCache_MSSQL::__construct($setting) — 建立专用连接，检查/创建表；CHARSET仅接受UTF-8或char。
 *   protected myCache_MSSQL::columns($sql, array $params) — 读取系统列说明并释放statement。
 *   public myCache_MSSQL::getLastError() — 返回安全错误摘要，不含SQL和认证信息。
 *   public myCache_MSSQL::query($sql, array $params = []) — 兼容旧原始查询入口，新增可选参数绑定；调用者负责释放返回statement。
 *   protected myCache_MSSQL::writeStatement($sql, array $params) — 完整消费写批次，确认TRY/CATCH及COMMIT已执行；不忽略后续结果中的错误。
 *   public myCache_MSSQL::set($key, $value = '', $ttl = 600) — 事务内UPDATE/INSERT覆盖同键；显式空值正常缓存，参数化数据不经字符串转义。
 *   public myCache_MSSQL::fetch($key, &$hit = null) — SQL过滤过期条目后再解码，兼容原始序列化及MS_CACHE2。
 *   public myCache_MSSQL::get($key) — 兼容旧get。
 *   public myCache_MSSQL::remove($key) — 参数化删除指定条目，始终引用[key]标识符。
 *   public myCache_MSSQL::clean() — 删除所有已过期条目，使用PHP实际Unix时间而非MySQL函数。
 *   public myCache_MSSQL::clear() — 显式删除当前缓存表全部条目，包含各namespace；保留表结构。
 *   public myCache_MSSQL::collectGarbage($limit = 200) — 有限预算删除过期数据。
 *   public myCache_MSSQL::close() — 关闭本驱动专属连接，重复调用安全。
 *   public myCache_MSSQL::__destruct() — 释放专属连接。
 *
 * 使用说明：
 *   1. 通过myCache的MSSQL驱动传入host/user/password/name等数据库配置。
 *   2. 需要SQLSRV扩展及兼容的SQL Server服务；表结构在初始化时检查。
 *   3. 支持lookup协议，不声明计算锁；clear范围是选定缓存表的全部数据。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名保持PHP 7.4兼容，PHPDoc类型用于说明，不额外强制运行时类型。
 */
require_once __DIR__.'/myCacheCodec.class.php';
/**
 * 功能说明：使用专用SQLSRV连接实现参数化读写、容量检查、精确命中及过期回收。
 *
 * 使用说明：
 *   1. 通过myCache的MSSQL驱动传入host/user/password/name等数据库配置。
 *   2. 需要SQLSRV扩展及兼容的SQL Server服务；表结构在初始化时检查。
 *   3. 支持lookup协议，不声明计算锁；clear范围是选定缓存表的全部数据。
 *
 * 补充说明：
 * SQL Server缓存（SQL Server 2012+）：原生参数化读写、独立连接及精确命中。
 * 新表为BIGINT/VARCHAR(MAX)，旧TEXT格式可读，不自动ALTER旧表；不声明计算锁接口。
 * 本地无SQLSRV时明确报错，不静默切换后端；SQL Server实库兼容性须在目标环境验证。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 */
class myCache_MSSQL implements interface_cache, interface_cache_lookup {
    protected $cnt = null, $codec, $table = '[dbo].[my_cache]', $error = '', $capacity = 2147483647, $expiration_max = PHP_INT_MAX, $value_width = 1;

    /**
     * 功能说明：建立专用连接，检查/创建表；CHARSET仅接受UTF-8或char。
     *
     * @param array $setting host/user/password/name，可选table/charset及公共编解码选项
     * @return void 初始化或释放资源，无返回值。
     * @throws InvalidArgumentException 配置非法
     * @throws RuntimeException SQLSRV缺失、连接或初始化失败
     * @phpstan-param array{host:string,user:string,name:string,password?:string,charset?:string,table?:string,port?:int,socket?:string|null,max_bytes?:int,max_depth?:int,allowed_classes?:bool|list<string>,namespace?:string} $setting
     */
    public function __construct($setting) {
        if(!is_array($setting))throw new InvalidArgumentException('SQL Server cache settings must be an array');
        foreach(['host','user','name']as$key)if(!isset($setting[$key])||!is_string($setting[$key])||$setting[$key]==='')throw new InvalidArgumentException('Missing SQL Server cache configuration');
        if(!function_exists('sqlsrv_connect'))throw new RuntimeException('The sqlsrv extension is required for MSSQL cache');
        $this->codec=new myCacheCodec($setting);$name=$setting['table']??'my_cache';
        if(!is_string($name)||!preg_match('/\A[a-zA-Z0-9_]{1,128}\z/D',$name))throw new InvalidArgumentException('Invalid cache table');
        $this->table='[dbo].['.$name.']';$object='dbo.'.$name;
        if(isset($setting['charset'])&&!is_string($setting['charset']))throw new InvalidArgumentException('Invalid cache charset');
        $charset=strtolower(str_replace('-','',$setting['charset']??'utf8'));
        if(in_array($charset,['utf8','utf8mb4'],true))$charset='UTF-8';elseif($charset!=='char')throw new InvalidArgumentException('Invalid SQL Server cache charset');
        try {
            $this->cnt=@sqlsrv_connect($setting['host'],['Database'=>$setting['name'],'UID'=>$setting['user'],'PWD'=>$setting['password']??'','CharacterSet'=>$charset,'LoginTimeout'=>5]);
            if($this->cnt===false)throw new RuntimeException();
            $inspect='SELECT c.name,t.name AS kind,c.max_length FROM sys.columns c JOIN sys.types t ON c.user_type_id=t.user_type_id WHERE c.object_id=OBJECT_ID(?)';
            $columns=$this->columns($inspect,[$object]);
            if($columns===[]) {
                $create='IF OBJECT_ID(?,N\'U\') IS NULL BEGIN CREATE TABLE '.$this->table.' ([key] char(32) NOT NULL PRIMARY KEY,[expiration] bigint NOT NULL,[value] varchar(max) NOT NULL); CREATE INDEX [expiration] ON '.$this->table.' ([expiration]); END';
                if(!$this->writeStatement($create,[$object]))throw new RuntimeException();
                $columns=$this->columns($inspect,[$object]);
            }
            if(!is_array($columns)||!isset($columns['key'],$columns['value'],$columns['expiration']))throw new RuntimeException();
            $keykind=strtolower($columns['key']['kind']);$keylen=(int)$columns['key']['max_length'];
            if(!in_array($keykind,['char','varchar','nchar','nvarchar'],true)||($keylen>=0&&$keylen<(in_array($keykind,['nchar','nvarchar'],true)?64:32)))throw new RuntimeException();
            $kind=strtolower($columns['value']['kind']);$length=(int)$columns['value']['max_length'];
            if(!in_array($kind,['text','ntext','varchar','nvarchar','varbinary','image'],true))throw new RuntimeException();
            $this->value_width=in_array($kind,['nvarchar','ntext'],true)?2:1;
            $this->capacity=$length<0||in_array($kind,['text','ntext','image'],true)?2147483647:($kind==='nvarchar'?(int)($length/2):$length);
            $kind=strtolower($columns['expiration']['kind']);$max=['tinyint'=>255,'smallint'=>32767,'int'=>2147483647,'bigint'=>PHP_INT_MAX];
            if(!isset($max[$kind]))throw new RuntimeException();$this->expiration_max=$max[$kind];
        }catch(Throwable$e){$this->close();throw new RuntimeException('Cannot initialize SQL Server cache');}
    }

    /**
     * 功能说明：读取系统列说明并释放statement。
     *
     * @param string $sql 元数据模板
     * @param array $params 绑定参数
     * @return array|false 按name索引的字段列表
     */
    protected function columns($sql, array $params) {
        $stmt=$this->query($sql,$params);if($stmt===false)return false;
        try{$rows=[];while(($row=sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC))!==null){if($row===false)return false;$rows[strtolower($row['name'])]=$row;}return $rows;}
        finally{try{@sqlsrv_free_stmt($stmt);}catch(Throwable$e){}}
    }

    /**
     * 功能说明：返回安全错误摘要，不含SQL和认证信息。
     *
     * 参数说明：无显式参数。
     *
     * @return string 最近一次安全错误摘要；无已记录错误时为空串
     */
    public function getLastError() {return $this->error;}

    /**
     * 功能说明：兼容旧原始查询入口，新增可选参数绑定；调用者负责释放返回statement。
     *
     *
     * 异常处理：内部捕获连接/SQL执行异常并返回false，错误摘要保存在对应getLastError状态；调用方不应仅依赖catch判断失败。
     *
     * @param string $sql 可信SQL模板
     * @param array $params 数据值参数（默认：[]）
     * @return resource|false SQLSRV statement或失败
     */
    public function query($sql, array $params = []) {
        $this->error='';
        try{if($this->cnt===null||$this->cnt===false)throw new RuntimeException();$stmt=@sqlsrv_query($this->cnt,$sql,$params,['Scrollable'=>'forward','QueryTimeout'=>5]);if($stmt===false)throw new RuntimeException();return $stmt;}
        catch(Throwable$e){$this->error='SQL Server cache operation failed.';return false;}
    }

    /**
     * 功能说明：完整消费写批次，确认TRY/CATCH及COMMIT已执行；不忽略后续结果中的错误。
     *
     * @param string $sql 可信模板
     * @param array $params 绑定参数
     * @return bool 写批次及其后续结果全部消费成功为true，SQL执行或批次错误为false
     */
    protected function writeStatement($sql, array $params) {
        $stmt=$this->query($sql,$params);if($stmt===false)return false;
        try{while(($more=sqlsrv_next_result($stmt))===true){}if($more===false){$this->error='SQL Server cache batch failed.';return false;}return true;}
        catch(Throwable$e){$this->error='SQL Server cache batch failed.';return false;}
        finally{try{@sqlsrv_free_stmt($stmt);}catch(Throwable$e){}}
    }

    /**
     * 功能说明：事务内UPDATE/INSERT覆盖同键；显式空值正常缓存，参数化数据不经字符串转义。
     *
     * @param mixed $key 有限标量键
     * @param mixed $value 受信可序列化数据，省略表示删除（默认：''）
     * @param int|float $ttl 秒数，非正数删除（默认：600）
     * @return bool 条目保存成功为true；省略value或TTL非正时转为删除；超容量或存储失败为false
     */
    public function set($key, $value = '', $ttl = 600) {
        if(func_num_args()<2)return $this->remove($key);
        $hash=$this->codec->key($key);$expire=myCacheCodec::expires($ttl);if(!$expire)return $this->remove($key);
        try{$data='MS_CACHE2:'.base64_encode($this->codec->pack($value));}catch(LengthException$e){$this->error='Cache value exceeds encoding budget.';return false;}
        if(strlen($data)>$this->capacity||$expire>$this->expiration_max){$this->error='Cache record exceeds table capacity.';return false;}
        $sql='SET NOCOUNT ON; SET XACT_ABORT ON; BEGIN TRY BEGIN TRANSACTION; UPDATE '.$this->table.' WITH (UPDLOCK,HOLDLOCK) SET [expiration]=?,[value]=? WHERE [key]=?; IF @@ROWCOUNT=0 INSERT INTO '.$this->table.' ([key],[expiration],[value]) VALUES(?,?,?); COMMIT TRANSACTION; END TRY BEGIN CATCH IF XACT_STATE()<>0 ROLLBACK TRANSACTION; THROW; END CATCH';
        return $this->writeStatement($sql,[(string)$expire,[$data,SQLSRV_PARAM_IN,null,SQLSRV_SQLTYPE_VARCHAR('max')],$hash,$hash,(string)$expire,[$data,SQLSRV_PARAM_IN,null,SQLSRV_SQLTYPE_VARCHAR('max')]]);
    }

    /**
     * 功能说明：SQL过滤过期条目后再解码，兼容原始序列化及MS_CACHE2。
     *
     * @param mixed $key 缓存键
     * @param bool|null &$hit 引用参数；精确命中输出（默认：null）
     * @return mixed 缓存值或false
     */
    public function fetch($key, &$hit = null) {
        $hit=false;$stmt=$this->query('SELECT [value] FROM '.$this->table.' WHERE [key]=? AND [expiration]>? AND DATALENGTH([value])<=?',[$this->codec->key($key),time(),(int)((ceil($this->codec->maxBytes()/3)*4+10)*$this->value_width)]);
        if($stmt===false)return false;
        try{$row=sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC);if(!is_array($row))return false;$value=$this->codec->sqlUnpack($row['value'],$valid);if($valid){$hit=true;return $value;}$this->error='Invalid cached payload.';return false;}
        catch(Throwable$e){$this->error='SQL Server cache read failed.';return false;}
        finally{try{@sqlsrv_free_stmt($stmt);}catch(Throwable$e){}}
    }

    /**
     * 功能说明：兼容旧get。
     *
     * @param mixed $key 缓存键
     * @return mixed 有效false与未命中不区分
     */
    public function get($key) {return $this->fetch($key,$hit);}

    /**
     * 功能说明：参数化删除指定条目，始终引用[key]标识符。
     *
     * @param mixed $key 缓存键
     * @return bool 删除语句执行成功为true，包括键不存在；执行失败为false
     */
    public function remove($key) {return $this->writeStatement('DELETE FROM '.$this->table.' WHERE [key]=?',[$this->codec->key($key)]);}

    /**
     * 功能说明：删除所有已过期条目，使用PHP实际Unix时间而非MySQL函数。
     *
     * 参数说明：无显式参数。
     *
     * @return bool 过期数据删除语句是否成功，不表示实际删除了多少条
     */
    public function clean() {return $this->collectGarbage(0);}

    /**
     * 功能说明：显式删除当前缓存表全部条目，包含各namespace；保留表结构。
     *
     * 完整消费批次结果，后续结果失败不能报告成功。
     *
     * 参数说明：无显式参数。
     *
     * @return bool DELETE及全部结果成功为true，连接或SQL失败为false
     */
    public function clear() {return $this->writeStatement('DELETE FROM '.$this->table, []);}

    /**
     * 功能说明：有限预算删除过期数据。
     *
     * @param int $limit 默认200，0不限，负数非法（默认：200）
     * @return bool 本次预算内的过期回收语句是否成功
     * @throws InvalidArgumentException 回收预算不是非负整数
     */
    public function collectGarbage($limit = 200) {
        if(!is_int($limit)||$limit<0)throw new InvalidArgumentException('Invalid cache GC limit');
        return $this->writeStatement('DELETE '.($limit?'TOP ('.$limit.') ':'').'FROM '.$this->table.' WHERE [expiration]<=?',[time()]);
    }

    /**
     * 功能说明：关闭本驱动专属连接，重复调用安全。
     *
     * 参数说明：无显式参数。
     *
     * @return void 无返回值。
     */
    public function close() {if($this->cnt!==null&&$this->cnt!==false)try{@sqlsrv_close($this->cnt);}catch(Throwable$e){}$this->cnt=null;}

    /**
     * 功能说明：释放专属连接。
     *
     * 参数说明：无显式参数。
     *
     * @return void 初始化或释放资源，无返回值。
     */
    public function __destruct() {$this->close();}
}
