<?PHP
/********************************************
*                                           *
* Name    : Social Login Flow (new)         *
* Modifier: Windy2000                       *
* Time    : 2026-10-25                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/
/**
 * 文件：plugin/social/SocialFlow.class.php (new)
 * 功能说明：一次性OAuth状态、首次资料补全、本站邮箱验证、绑定与直接登录的普通会员流程。
 * 方法目录：
 *   public SocialFlow::__construct(myUser $users,SocialStore $store,SocialOAuth $oauth,array $options,callable $mailer,?callable $clock=null) — 组合受信依赖，测试可注入本地邮件/时钟，不接受HTTP配置回调。
 *   private SocialFlow::now(): int — 读取当前秒。参数说明：无显式参数。
 *   private SocialFlow::sessionKey(string $suffix): string — 隔离应用及配置端点的流程会话。
 *   private SocialFlow::requireMemberContext(): void — 拒绝未启动的会话或管理员上下文。参数说明：无显式参数。
 *   public SocialFlow::begin(string $provider,string $intent='login'): string — 建立短时一次性state与PKCE；登录和绑定意图不能在回调中切换。
 *   public SocialFlow::callback(string $provider,array $query): array — 消费浏览器state后交换平台身份，已验证绑定可直接登录，其他情况进入本站邮件流程。
 *   private SocialFlow::draft(string $key,string $provider,string $client,string $intent,string $actor,array $identity): void — 创建只有当前浏览器能完成的短时草稿，不创建会员。
 *   public SocialFlow::pending(bool $lock=false): array — 校验待完成流程的浏览器、时效、平台及当前绑定账号。
 *   public SocialFlow::sendVerification(string $email,string $name,string $ip): void — 校验必填资料，保存短时邮件摘要并在事务外投递验证邮件；同邮箱不自动合并。
 *   public SocialFlow::validateMail(string $id,string $token,bool $lock=false): array — 核对短时邮件令牌，不在GET中消费或登录。
 *   public SocialFlow::confirm(string $id,string $token): array — 原子消费邮件证明，创建/验证会员并关联身份，提交后才建立会话。
 *   public SocialFlow::connections(): array — 列出当前会员的绑定，公开摘要键仅用于受CSRF保护的解除操作。参数说明：无显式参数。
 *   public SocialFlow::unlink(string $key): void — 解除当前会员绑定，拒绝删除唯一可用登录方式。
 *   public SocialFlow::cancel(): void — 取消当前浏览器草稿，不删除账号或关联。参数说明：无显式参数。
 * 使用说明：HTTP方法与CSRF由plugin_social入口检查；此类也校验浏览器绑定、平台配置、账号状态及一次性状态。不按相同邮箱自动合并账号。
 */
class SocialFlow {
    private myUser $users;
    private SocialStore $store;
    private SocialOAuth $oauth;
    private array $options;
    private $mailer;
    private $clock;
    /**
     * 功能说明：组合受信依赖，测试可注入本地邮件/时钟，不接受HTTP配置回调。
     * @param myUser $users 会员服务
     * @param SocialStore $store 身份存储
     * @param SocialOAuth $oauth 平台客户端
     * @param array $options 已校验配置
     * @param callable $mailer function(email,link):void
     * @param callable|null $clock 默认null；测试时钟
     * @return void 初始化完成
     */
    public function __construct(myUser $users,SocialStore $store,SocialOAuth $oauth,array $options,callable $mailer,?callable $clock=null){$this->users=$users;$this->store=$store;$this->oauth=$oauth;$this->options=$options;$this->mailer=$mailer;$this->clock=$clock??static fn()=>time();}
    /**
     * 功能说明：读取当前秒。参数说明：无显式参数。
     * @return int 受信时钟时间
     */
    private function now(): int {return (int)($this->clock)();}
    /**
     * 功能说明：隔离应用及配置端点的流程会话。
     * @param string $suffix 用途后缀
     * @return string 本站会话键
     */
    private function sessionKey(string $suffix): string {return '_social_'.substr(hash('sha256',$this->users->provider()->scope().$this->options['endpoint']),0,16).'_'.$suffix;}
    /**
     * 功能说明：拒绝未启动的会话或管理员上下文。参数说明：无显式参数。
     * @return void 可继续普通会员流程
     * @throws Throwable 输入/状态无效或底层服务失败时终止本次流程，不建立登录
     */
    private function requireMemberContext(): void {if(!myReq::sessionCheck()||!$this->users->provider()->allowsLogin())throw new DomainException('请先退出管理员，再使用普通会员第三方登录。');}
    /**
     * 功能说明：建立短时一次性state与PKCE；登录和绑定意图不能在回调中切换。
     * @param string $provider 平台
     * @param string $intent 默认login；login或bind
     * @return string 平台授权地址
     * @throws Throwable 输入/状态无效或底层服务失败时终止本次流程，不建立登录
     */
    public function begin(string $provider,string $intent='login'): string {
        $this->requireMemberContext();if(random_int(1,20)===1)$this->store->cleanup($this->now());$client=$this->oauth->clientKey($provider);$current=$this->users->current();
        if(!in_array($intent,['login','bind'],true)||($intent==='bind'&&!$current)||($intent==='login'&&$current))throw new DomainException('请在个人资料中绑定平台，或先退出当前会员。');
        $states=myReq::session($this->sessionKey('states'));$states=is_array($states)?$states:[];$now=$this->now();
        foreach($states as $key=>$entry)if(!is_array($entry)||($entry['expires']??0)<=$now)unset($states[$key]);
        while(count($states)>=5)array_shift($states);
        $state=bin2hex(random_bytes(32));$verifier=bin2hex(random_bytes(32));$redirect=$this->options['endpoint'].'callback/'.$provider;
        $states[hash('sha256',$state)]=['provider'=>$provider,'client'=>$client,'expires'=>$now+600,'verifier'=>$verifier,'redirect'=>$redirect,'intent'=>$intent,'actor'=>$current['id']??''];
        myReq::session($this->sessionKey('states'),$states);
        return $this->oauth->authorization($provider,$redirect,$state,$verifier);
    }
    /**
     * 功能说明：消费浏览器state后交换平台身份，已验证绑定可直接登录，其他情况进入本站邮件流程。
     * @param string $provider 平台
     * @param array $query 平台GET回调参数
     * @return array kind为login/bound/pending
     * @throws Throwable 输入/状态无效或底层服务失败时终止本次流程，不建立登录
     */
    public function callback(string $provider,array $query): array {
        $this->requireMemberContext();$state=$query['state']??null;
        if(!is_string($state)||!preg_match('/\A[a-f0-9]{64}\z/D',$state))throw new DomainException('登录状态无效，请重新发起授权。');
        $states=myReq::session($this->sessionKey('states'));$states=is_array($states)?$states:[];$key=hash('sha256',$state);$entry=$states[$key]??null;unset($states[$key]);myReq::session($this->sessionKey('states'),$states);
        if(!is_array($entry)||$entry['expires']<=$this->now()||$entry['provider']!==$provider||!hash_equals($entry['client'],$this->oauth->clientKey($provider)))throw new DomainException('登录状态已过期或平台配置已改变，请重新授权。');
        $current=$this->users->current();
        if(($entry['intent']==='bind'&&(!$current||$current['id']!==$entry['actor']))||($entry['intent']==='login'&&$current))throw new DomainException('当前会员身份已经改变，请重新授权。');
        if(isset($query['error']))throw new DomainException('第三方授权已取消或未获批准。');
        $code=$query['code']??null;if(!is_string($code))throw new DomainException('平台未返回有效授权码。');
        $identity=$this->oauth->identity($provider,$code,$entry['redirect'],$entry['verifier']);$key=$this->store->identityKey($provider,$entry['client'],$identity['subject']);$link=$this->store->identity($key);
        if($link){
            $member=$this->users->find($link['user_id']);if(!$member||!$member['active'])throw new DomainException('关联会员不存在或已停用。');
            if($entry['intent']==='bind'&&$member['id']!==$entry['actor'])throw new DomainException('此第三方身份已关联其他会员。');
            if($member['email_verified']&&strcasecmp($link['verified_email'],$member['email'])===0){
                if($entry['intent']==='login')return ['kind'=>'login','user'=>$this->users->loginVerified($member['id'])];
                return ['kind'=>'bound','user'=>$member];
            }
            $intent=$entry['intent']==='bind'?'bind':'reverify';$actor=$member['id'];$identity['email']=$member['email'];$identity['display_name']=$member['display_name'];
        }else{$intent=$entry['intent']==='bind'?'bind':'new';$actor=$entry['actor'];if($current){$identity['email']=$current['email'];$identity['display_name']=$current['display_name'];}}
        $this->draft($key,$provider,$entry['client'],$intent,$actor,$identity);return ['kind'=>'pending'];
    }
    /**
     * 功能说明：创建只有当前浏览器能完成的短时草稿，不创建会员。
     * @param string $key 身份摘要
     * @param string $provider 平台
     * @param string $client 应用指纹
     * @param string $intent new/bind/reverify
     * @param string $actor 既有会员ID或空
     * @param array $identity 平台资料提示
     * @return void 已建立草稿
     */
    private function draft(string $key,string $provider,string $client,string $intent,string $actor,array $identity): void {
        $id=bin2hex(random_bytes(32));$secret=bin2hex(random_bytes(32));$row=['pending_id'=>$id,'identity_key'=>$key,'provider'=>$provider,'client_key'=>$client,'browser_key'=>hash('sha256',$secret),'intent'=>$intent,'user_id'=>$actor,'email'=>$identity['email']??'','display_name'=>$identity['display_name']??'','expires_at'=>$this->now()+(int)$this->options['pending_ttl']];
        $this->store->newPending($row);myReq::session($this->sessionKey('pending'),['id'=>$id,'secret'=>$secret]);
    }
    /**
     * 功能说明：校验待完成流程的浏览器、时效、平台及当前绑定账号。
     * @param bool $lock 默认false；是否在事务内锁定
     * @return array 当前有效记录
     * @throws DomainException 状态无效
     */
    public function pending(bool $lock=false): array {
        $this->requireMemberContext();$local=myReq::session($this->sessionKey('pending'));
        if(!is_array($local)||!is_string($local['id']??null)||!is_string($local['secret']??null))throw new DomainException('请先完成第三方授权，并在同一浏览器中继续。');
        $row=$this->store->pending($local['id'],$lock);
        if(!$row||(int)$row['consumed']!==0||(int)$row['expires_at']<=$this->now()||!hash_equals($row['browser_key'],hash('sha256',$local['secret'])))throw new DomainException('待完成登录已失效，请重新授权。');
        if(!hash_equals($row['client_key'],$this->oauth->clientKey($row['provider'])))throw new DomainException('平台配置已改变，请重新授权。');
        if($row['intent']==='bind'){$current=$this->users->current();if(!$current||$current['id']!==$row['user_id'])throw new DomainException('当前会员身份已经改变，请重新绑定。');}
        elseif($this->users->current()!==null)throw new DomainException('当前已有会员登录，请重新发起绑定。');
        return $row;
    }
    /**
     * 功能说明：校验必填资料，保存短时邮件摘要并在事务外投递验证邮件；同邮箱不自动合并。
     * @param string $email 邮箱
     * @param string $name 必填昵称
     * @param string $ip 已由框架解析的来源IP，用于小时限额
     * @return void 投递成功；失败使刚生成的邮件令牌失效
     * @throws Throwable 输入/状态无效或底层服务失败时终止本次流程，不建立登录
     */
    public function sendVerification(string $email,string $name,string $ip): void {
        $email=myUser::email($email);$name=myUser::displayName($name);$token=bin2hex(random_bytes(32));$now=$this->now();
        $pending=$this->store->transaction(function(SocialStore $store)use($email,$name,$ip,$token,$now){
            $p=$this->pending(true);if((int)$p['resend_after']>$now)throw new DomainException('请稍后再发送验证邮件。');
            if($p['intent']==='new'&&$this->users->findByEmail($email,true))throw new DomainException('该邮箱已有账号，请先用原账号登录，再从资料页绑定第三方平台。');
            if($p['intent']!=='new'){$member=$this->users->find($p['user_id']);if(!$member||!$member['active']||strcasecmp($member['email'],$email)!==0)throw new DomainException('绑定或重新验证须使用现有会员邮箱。');$name=$member['display_name'];}
            $store->rate('email:'.strtolower($email),$now,5);$store->rate('ip:'.$ip,$now,20);
            $store->updatePending($p['pending_id'],['email'=>$email,'display_name'=>$name,'mail_hash'=>hash('sha256',$token),'mail_expires'=>min((int)$p['expires_at'],$now+(int)$this->options['email_ttl']),'resend_after'=>$now+60]);return $p;
        });
        // 片段不进入HTTP访问日志或Referer；确认页脚本只填入隐藏字段，最终提交仍须本站CSRF和浏览器绑定。
        $link=$this->options['endpoint'].'verify#'.http_build_query(['id'=>$pending['pending_id'],'token'=>$token],'','&',PHP_QUERY_RFC3986);
        try{($this->mailer)($email,$link);}catch(Throwable $error){$this->store->updatePending($pending['pending_id'],['mail_hash'=>'','mail_expires'=>0]);throw new RuntimeException('验证邮件未能提交，请检查邮件设置或稍后重试。',0,$error);}
    }
    /**
     * 功能说明：核对短时邮件令牌，不在GET中消费或登录。
     * @param string $id 流程ID
     * @param string $token 邮件片段令牌
     * @param bool $lock 默认false；是否锁定
     * @return array 已验证但尚未消费的草稿
     * @throws Throwable 输入/状态无效或底层服务失败时终止本次流程，不建立登录
     */
    public function validateMail(string $id,string $token,bool $lock=false): array {
        if(!preg_match('/\A[a-f0-9]{64}\z/D',$id)||!preg_match('/\A[a-f0-9]{64}\z/D',$token))throw new DomainException('邮件验证信息无效。');
        $p=$this->pending($lock);
        if(!hash_equals($p['pending_id'],$id)||$p['mail_hash']===''||(int)$p['mail_expires']<=$this->now()||!hash_equals($p['mail_hash'],hash('sha256',$token)))throw new DomainException('邮件验证已失效，请重新发送。');
        return $p;
    }
    /**
     * 功能说明：原子消费邮件证明，创建/验证会员并关联身份，提交后才建立会话。
     * @param string $id 流程ID
     * @param string $token 邮件片段令牌
     * @return array 登录后的会员资料
     * @throws Throwable 输入/状态无效或底层服务失败时终止本次流程，不建立登录
     */
    public function confirm(string $id,string $token): array {
        $this->requireMemberContext();$user=$this->store->transaction(function(SocialStore $store)use($id,$token){
            $p=$this->validateMail($id,$token,true);$store->rate('email:'.strtolower($p['email']),$this->now(),0);$existing=$store->identity($p['identity_key'],true);
            if($p['intent']==='new'){
                if($existing||$this->users->findByEmail($p['email'],true))throw new DomainException('邮箱或第三方身份已关联，请重新登录或使用原账号绑定。');
                $user=$this->users->register(['email'=>$p['email'],'display_name'=>$p['display_name']]);
            }else{
                $user=$this->users->find($p['user_id']);if(!$user||!$user['active'])throw new DomainException('会员不存在或已停用。');
                if($p['intent']==='reverify'&&(!$existing||$existing['user_id']!==$user['id']))throw new DomainException('原关联已改变，请重新授权。');
                if($existing&&$existing['user_id']!==$user['id'])throw new DomainException('第三方身份已关联其他会员。');
            }
            $user=$this->users->verifyEmail($user['id'],$p['email']);
            if($existing && $p['intent']!=='new'){
                $store->unlink($p['identity_key'],$user['id']);
            }
            $store->link($p,$user['id']);$store->updatePending($id,['consumed'=>1,'mail_hash'=>'','mail_expires'=>0]);return $user;
        });
        myReq::removeSession($this->sessionKey('pending'));return $this->users->loginVerified($user['id']);
    }
    /**
     * 功能说明：列出当前会员的绑定，公开摘要键仅用于受CSRF保护的解除操作。参数说明：无显式参数。
     * @return array 当前会员绑定
     * @throws Throwable 输入/状态无效或底层服务失败时终止本次流程，不建立登录
     */
    public function connections(): array {$current=$this->users->current();if(!$current)throw new DomainException('请先登录普通会员。');return $this->store->identities($current['id']);}
    /**
     * 功能说明：解除当前会员绑定，拒绝删除唯一可用登录方式。
     * @param string $key 身份摘要
     * @return void 无返回值
     * @throws Throwable 输入/状态无效或底层服务失败时终止本次流程，不建立登录
     */
    public function unlink(string $key): void {
        $this->requireMemberContext();if(!preg_match('/\A[a-f0-9]{64}\z/D',$key))throw new InvalidArgumentException('关联标识无效。');
        $this->store->transaction(function(SocialStore $store)use($key){
            $user=$this->users->current();if(!$user)throw new DomainException('请先登录普通会员。');$store->rate('member:'.$user['id'],$this->now(),0);$target=$store->identity($key,true);if(!$target||$target['user_id']!==$user['id'])throw new DomainException('关联不存在。');
            $usable=0;foreach($store->identities($user['id'],true) as $item)if($this->oauth->enabled($item['provider'])&&hash_equals($item['client_key'],$this->oauth->clientKey($item['provider']))&&$user['email_verified']&&strcasecmp($user['email'],$item['verified_email'])===0)$usable++;
            $targetUsable=$this->oauth->enabled($target['provider'])&&hash_equals($target['client_key'],$this->oauth->clientKey($target['provider']))&&$user['email_verified']&&strcasecmp($user['email'],$target['verified_email'])===0;
            if(!$user['has_password']&&$usable-($targetUsable?1:0)<1)throw new DomainException('这是唯一可用登录方式，请先设置本地密码或绑定另一平台。');
            $store->unlink($key,$user['id']);
        });
    }
    /**
     * 功能说明：取消当前浏览器草稿，不删除账号或关联。参数说明：无显式参数。
     * @return void 无返回值
     */
    public function cancel(): void {
        try{$p=$this->pending();$this->store->updatePending($p['pending_id'],['consumed'=>1,'mail_hash'=>'','mail_expires'=>0]);}catch(DomainException){}
        myReq::removeSession($this->sessionKey('pending'));
    }
}
