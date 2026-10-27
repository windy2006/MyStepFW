<?PHP
/********************************************
*                                           *
* Name    : Social Identity Store (new)     *
* Modifier: Windy2000                       *
* Time    : 2026-10-25                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/
/**
 * 文件：plugin/social/SocialStore.class.php (new)
 * 功能说明：第三方身份映射、待完成流程、一次性邮件摘要和限流存储；与会员适配器共用事务连接。
 * 方法目录：
 *   public SocialStore::__construct(interface_user $members) — 接管会员连接，确保身份与账号写入可共同回滚。
 *   public SocialStore::prepare(): void — 显式准备会员扩展及插件专属事务表。参数说明：无显式参数。
 *   public SocialStore::ready(): bool — 只读检查会员及插件存储已安装。参数说明：无显式参数。
 *   private SocialStore::table(string $name): string — 取得受控插件表标识。
 *   private SocialStore::lock(bool $lock): string — 取得当前方言的锁定读取后缀。
 *   public SocialStore::transaction(callable $callback) — 在共享连接中执行回调事务。
 *   public SocialStore::identityKey(string $provider,string $client,string $subject): string — 生成大小写敏感、应用和平台命名空间隔离的身份键。
 *   public SocialStore::identity(string $key,bool $lock=false): ?array — 读取当前作用域身份映射。
 *   public SocialStore::identities(string $id,bool $lock=false): array — 列出指定会员的已绑定平台，不输出身份原文。
 *   public SocialStore::link(array $pending,string $user): void — 事务内绑定唯一身份；并发已有映射时不覆盖其他账号。
 *   public SocialStore::unlink(string $key,string $user): bool — 删除本账号指定身份，不能删除他人关联。
 *   public SocialStore::newPending(array $row): void — 建立短时待完成流程，尚不创建会员或登录。
 *   public SocialStore::pending(string $id,bool $lock=false): ?array — 读取当前作用域待完成记录。
 *   public SocialStore::updatePending(string $id,array $fields): void — 只更新白名单流程字段。
 *   public SocialStore::rate(string $key,int $now,int $limit): void — 事务内占用小时限额并锁定对应邮箱/来源行，串行化首次账号创建。
 *   public SocialStore::cleanup(int $now): void — 清理过期待验证状态和旧限额，不删除会员或身份绑定。
 * 使用说明：仅prepare执行DDL；身份只保存作用域化摘要，不保存平台令牌、OpenID原文或邮箱验证明文令牌。
 */
class SocialStore {
    private interface_user $members;
    private myDb $db;
    private string $realm;
    /**
     * 功能说明：接管会员连接，确保身份与账号写入可共同回滚。
     * @param interface_user $members 会员适配器
     * @return void 初始化完成
     */
    public function __construct(interface_user $members){$this->members=$members;$this->db=$members->database();$this->realm=hash('sha256',$members->scope());}
    /**
     * 功能说明：显式准备会员扩展及插件专属事务表。参数说明：无显式参数。
     * @return void 失败抛异常，保留已有账号和绑定
     */
    public function prepare(): void {
        $this->members->prepare();$tail=$this->members->dialect()==='mysql'?' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4':'';
        $this->db->execute('CREATE TABLE IF NOT EXISTS '.$this->table('identities').' (identity_key CHAR(64) PRIMARY KEY,realm CHAR(64) NOT NULL,provider VARCHAR(16) NOT NULL,client_key CHAR(64) NOT NULL,user_id VARCHAR(64) NOT NULL,verified_email VARCHAR(254) NOT NULL,created_at BIGINT NOT NULL)'.$tail);
        $this->db->execute('CREATE TABLE IF NOT EXISTS '.$this->table('pending').' (pending_id CHAR(64) PRIMARY KEY,realm CHAR(64) NOT NULL,identity_key CHAR(64) NOT NULL,provider VARCHAR(16) NOT NULL,client_key CHAR(64) NOT NULL,browser_key CHAR(64) NOT NULL,intent VARCHAR(8) NOT NULL,user_id VARCHAR(64) NOT NULL,email VARCHAR(254) NOT NULL,display_name VARCHAR(100) NOT NULL,mail_hash CHAR(64) NOT NULL,mail_expires BIGINT NOT NULL,resend_after BIGINT NOT NULL,expires_at BIGINT NOT NULL,consumed INTEGER NOT NULL DEFAULT 0)'.$tail);
        $this->db->execute('CREATE TABLE IF NOT EXISTS '.$this->table('rate').' (rate_key CHAR(64) PRIMARY KEY,bucket BIGINT NOT NULL,counter INTEGER NOT NULL)'.$tail);
        foreach(['identities'=>['owner'=>'realm,user_id'],'pending'=>['expiry'=>'expires_at'],'rate'=>['bucket'=>'bucket']] as $name=>$indexes){
            $tableName=$this->members->prefix().'social_'.$name;
            if($this->members->dialect()==='mysql'){
                $engine=$this->db->fetchValue('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?',[$tableName]);
                if(strcasecmp((string)$engine,'InnoDB')!==0)$this->db->execute('ALTER TABLE '.$this->table($name).' ENGINE=InnoDB');
            }
            foreach($indexes as $suffix=>$fields){
                $index=$this->members->prefix().'social_'.$suffix;
                if($this->members->dialect()==='mysql'){
                    if((int)$this->db->fetchValue('SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND INDEX_NAME=?',[$tableName,$index])>0)continue;
                    $prefix='CREATE INDEX ';
                }else $prefix='CREATE INDEX IF NOT EXISTS ';
                $columns=implode(',',array_map([$this->db,'quoteIdentifier'],explode(',',$fields)));
                $this->db->execute($prefix.$this->db->quoteIdentifier($index).' ON '.$this->table($name).' ('.$columns.')');
            }
        }

    }
    /**
     * 功能说明：只读检查会员及插件存储已安装。参数说明：无显式参数。
     * @return bool 是否可运行
     */
    public function ready(): bool {
        if(!$this->members->ready())return false;$tables=$this->db->getTbls();foreach(['identities','pending','rate'] as $name)if(!in_array($this->members->prefix().'social_'.$name,$tables,true))return false;
        if($this->members->dialect()==='mysql'){
            $names=array_map(fn($name)=>$this->members->prefix().'social_'.$name,['identities','pending','rate']);
            if((int)$this->db->fetchValue("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN (?,?,?) AND ENGINE='InnoDB'",$names)!==3)return false;
        }
        return true;
    }
    /**
     * 功能说明：取得受控插件表标识。
     * @param string $name identities/pending/rate
     * @return string 已引用表名
     * @throws Throwable 输入/状态无效或底层服务失败时终止本次流程，不建立登录
     */
    private function table(string $name): string {if(!in_array($name,['identities','pending','rate'],true))throw new LogicException('非法身份表。');return $this->db->quoteIdentifier($this->members->prefix().'social_'.$name);}
    /**
     * 功能说明：取得当前方言的锁定读取后缀。
     * @param bool $lock 是否加锁
     * @return string MySQL的FOR UPDATE或空串
     */
    private function lock(bool $lock): string {return $lock&&$this->members->dialect()==='mysql'?' FOR UPDATE':'';}
    /**
     * 功能说明：在共享连接中执行回调事务。
     * @param callable $callback 接收本存储实例
     * @return mixed 回调结果；异常回滚
     */
    public function transaction(callable $callback){return $this->db->transaction(fn()=> $callback($this));}
    /**
     * 功能说明：生成大小写敏感、应用和平台命名空间隔离的身份键。
     * @param string $provider 平台
     * @param string $client 应用指纹
     * @param string $subject 平台稳定身份
     * @return string SHA256身份键
     */
    public function identityKey(string $provider,string $client,string $subject): string {return hash('sha256',json_encode([$this->realm,$provider,$client,$subject],JSON_THROW_ON_ERROR));}
    /**
     * 功能说明：读取当前作用域身份映射。
     * @param string $key 身份摘要
     * @param bool $lock 默认false；是否事务锁定
     * @return array|null 映射或不存在
     */
    public function identity(string $key,bool $lock=false): ?array {$row=$this->db->fetchOne('SELECT * FROM '.$this->table('identities').' WHERE identity_key=? AND realm=?'.$this->lock($lock),[$key,$this->realm]);return is_array($row)?$row:null;}
    /**
     * 功能说明：列出指定会员的已绑定平台，不输出身份原文。
     * @param string $id 会员ID
     * @param bool $lock 默认false；是否事务内锁定当前会员的绑定
     * @return array 映射列表
     */
    public function identities(string $id,bool $lock=false): array {return $this->db->fetchAll('SELECT identity_key,provider,client_key,created_at,verified_email FROM '.$this->table('identities').' WHERE realm=? AND user_id=? ORDER BY created_at'.$this->lock($lock),[$this->realm,$id]);}
    /**
     * 功能说明：事务内绑定唯一身份；并发已有映射时不覆盖其他账号。
     * @param array $pending 已验证的待完成记录
     * @param string $user 会员ID
     * @return void 不一致时抛异常
     * @throws Throwable 输入/状态无效或底层服务失败时终止本次流程，不建立登录
     */
    public function link(array $pending,string $user): void {
        $sql=($this->members->dialect()==='mysql'?'INSERT IGNORE':'INSERT OR IGNORE').' INTO '.$this->table('identities').' (identity_key,realm,provider,client_key,user_id,verified_email,created_at) VALUES (?,?,?,?,?,?,?)';
        $this->db->execute($sql,[$pending['identity_key'],$this->realm,$pending['provider'],$pending['client_key'],$user,$pending['email'],time()]);
        $row=$this->identity($pending['identity_key'],true);if(!$row||$row['user_id']!==$user||strcasecmp($row['verified_email'],$pending['email'])!==0)throw new DomainException('此第三方身份已关联其他账号。');
    }
    /**
     * 功能说明：删除本账号指定身份，不能删除他人关联。
     * @param string $key 身份键
     * @param string $user 会员ID
     * @return bool 是否删除
     */
    public function unlink(string $key,string $user): bool {return $this->db->execute('DELETE FROM '.$this->table('identities').' WHERE identity_key=? AND realm=? AND user_id=?',[$key,$this->realm,$user])===1;}
    /**
     * 功能说明：建立短时待完成流程，尚不创建会员或登录。
     * @param array $row 由流程服务构造的受信字段
     * @return void 已保存
     */
    public function newPending(array $row): void {
        $this->db->execute('INSERT INTO '.$this->table('pending').' (pending_id,realm,identity_key,provider,client_key,browser_key,intent,user_id,email,display_name,mail_hash,mail_expires,resend_after,expires_at,consumed) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,0)',[$row['pending_id'],$this->realm,$row['identity_key'],$row['provider'],$row['client_key'],$row['browser_key'],$row['intent'],$row['user_id'],$row['email'],$row['display_name'],'',0,0,$row['expires_at']]);
    }
    /**
     * 功能说明：读取当前作用域待完成记录。
     * @param string $id 随机流程ID
     * @param bool $lock 默认false；是否锁定
     * @return array|null 记录或不存在
     */
    public function pending(string $id,bool $lock=false): ?array {$row=$this->db->fetchOne('SELECT * FROM '.$this->table('pending').' WHERE pending_id=? AND realm=?'.$this->lock($lock),[$id,$this->realm]);return is_array($row)?$row:null;}
    /**
     * 功能说明：只更新白名单流程字段。
     * @param string $id 流程ID
     * @param array $fields 受信变更
     * @return void 无返回值
     * @throws Throwable 输入/状态无效或底层服务失败时终止本次流程，不建立登录
     */
    public function updatePending(string $id,array $fields): void {
        $allowed=['email','display_name','mail_hash','mail_expires','resend_after','consumed','expires_at'];$set=[];$values=[];
        foreach($fields as $key=>$value){if(!in_array($key,$allowed,true))throw new LogicException('非法流程字段。');$set[]=$this->db->quoteIdentifier($key).'=?';$values[]=$value;}
        if(!$set)return;$values[]=$id;$values[]=$this->realm;$this->db->execute('UPDATE '.$this->table('pending').' SET '.implode(',',$set).' WHERE pending_id=? AND realm=?',$values);
    }
    /**
     * 功能说明：事务内占用小时限额并锁定对应邮箱/来源行，串行化首次账号创建。
     * @param string $key 邮箱、IP等用途化标识，不明文保存
     * @param int $now 当前秒
     * @param int $limit 每小时上限；0只取得锁不增加计数
     * @return void 超限抛异常
     * @throws Throwable 输入/状态无效或底层服务失败时终止本次流程，不建立登录
     */
    public function rate(string $key,int $now,int $limit): void {
        $hash=hash('sha256',$this->realm."\0".$key);$bucket=(int)floor($now/3600);$insert=$this->members->dialect()==='mysql'?'INSERT IGNORE':'INSERT OR IGNORE';
        $this->db->execute($insert.' INTO '.$this->table('rate').' (rate_key,bucket,counter) VALUES (?,?,0)',[$hash,$bucket]);
        $row=$this->db->fetchOne('SELECT bucket,counter FROM '.$this->table('rate').' WHERE rate_key=?'.$this->lock(true),[$hash]);$count=(int)$row['bucket']===$bucket?(int)$row['counter']:0;
        if($limit>0&&$count>=$limit)throw new DomainException('验证邮件发送过于频繁，请稍后重试。');
        $this->db->execute('UPDATE '.$this->table('rate').' SET bucket=?,counter=? WHERE rate_key=?',[$bucket,$count+($limit>0?1:0),$hash]);
    }
    /**
     * 功能说明：清理过期待验证状态和旧限额，不删除会员或身份绑定。
     * @param int $now 当前秒
     * @return void 无返回值
     */
    public function cleanup(int $now): void {$this->db->execute('DELETE FROM '.$this->table('pending').' WHERE expires_at<?',[$now-86400]);$this->db->execute('DELETE FROM '.$this->table('rate').' WHERE bucket<?',[(int)floor($now/3600)-48]);}
}
