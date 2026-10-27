<?PHP
/********************************************
*                                           *
* Name    : Member Service (new)            *
* Modifier: Windy2000                       *
* Time    : 2026-10-25                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/
/**
 * 文件：lib/myUser.class.php (new)
 * 功能说明：统一普通会员资料、密码认证、受信登录及邮箱验证入口；管理员认证仍由myAuth/myStep承担。
 * 方法目录：
 *   public myUser::__construct(interface_user $store) — 接管应用提供的会员存储。
 *   public static myUser::forApplication(string $app): self — 加载已安装应用核心类，通过静态工厂取得会员资源，不实例化第二个控制器。
 *   public myUser::provider(): interface_user — 取得存储适配器，供受信插件共享事务。参数说明：无显式参数。
 *   public static myUser::email(string $email): string — 规范化邮箱并限制输入，不把不同邮箱提供商的点号或加号地址自动合并。
 *   public static myUser::displayName(string $name): string — 核对必填昵称，仅保存文本。
 *   public static myUser::profile(?array $profile): ?array — 只导出标准公开字段。
 *   public myUser::current(): ?array — 读取当前普通会员，不返回管理员凭据。参数说明：无显式参数。
 *   public myUser::find(string $id): ?array — 读取指定会员公开资料，不产生登录。
 *   public myUser::findByEmail(string $email,bool $lock=false): array — 查找邮箱，不自动合并账号。
 *   public myUser::register(array $data): array — 创建未验证普通会员，具体权限由适配器固定。
 *   public myUser::passwordLogin(string $username,string $password,int $remember=0): ?array — 通过本地密码认证并建立普通会员会话。
 *   public myUser::verifyEmail(string $id,string $email): array — 在邮件验证服务已独立验证后标记邮箱；本方法本身不是验证证明。
 *   public myUser::loginVerified(string $id): array — 供第三方认证完成后建立已验证会员会话。
 *   public myUser::logout(): void — 仅退出普通会员。参数说明：无显式参数。
 * 使用说明：$mystep->users()取得当前应用资源；应用核心类实现静态userProvider()返回interface_user。第三方插件完成独立认证后才能调用loginVerified，不能把HTTP传入ID当作认证证明。
 */
class myUser {
    /**
     *
     * @var interface_user 当前应用会员适配器，不含第三方平台逻辑
     */
    private interface_user $store;
    /**
     * 功能说明：接管应用提供的会员存储。
     * @param interface_user $store 已配置的受信适配器
     * @return void 初始化完成
     */
    public function __construct(interface_user $store) {$this->store=$store;}
    /**
     * 功能说明：加载已安装应用核心类，通过静态工厂取得会员资源，不实例化第二个控制器。
     * @param string $app 与目录/核心类一致的应用标识
     * @return self 对应应用的普通会员服务
     * @throws InvalidArgumentException 应用标识或核心类不合法
     * @throws LogicException 应用未实现会员适配器
     */
    public static function forApplication(string $app): self {
        if(!preg_match('/\A[A-Za-z][A-Za-z0-9_]*\z/D',$app) || !is_file(APP.$app.'/'.$app.'.class.php'))throw new InvalidArgumentException('应用不支持普通会员接口。');
        require_once APP.$app.'/'.$app.'.class.php';
        if(!is_subclass_of($app,myStep::class))throw new InvalidArgumentException('应用核心必须继承myStep。');
        return new self($app::userProvider());
    }
    /**
     * 功能说明：取得存储适配器，供受信插件共享事务。参数说明：无显式参数。
     * @return interface_user 当前适配器
     */
    public function provider(): interface_user {return $this->store;}
    /**
     * 功能说明：规范化邮箱并限制输入，不把不同邮箱提供商的点号或加号地址自动合并。
     * @param string $email 待验证邮箱
     * @return string 小写域名、本地部分保留原值的邮箱
     * @throws InvalidArgumentException 邮箱无效、过长或含控制字符
     */
    public static function email(string $email): string {
        $email=trim($email);
        if(strlen($email)>254 || preg_match('/[\x00-\x20\x7f]/',$email) || !filter_var($email,FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('请输入有效邮箱。');
        $at=strrpos($email,'@');return substr($email,0,$at).'@'.strtolower(substr($email,$at+1));
    }
    /**
     * 功能说明：核对必填昵称，仅保存文本。
     * @param string $name 用户昵称
     * @return string 去除两端空白的昵称
     * @throws InvalidArgumentException 无效UTF-8、空值、控制字符或超过60字
     */
    public static function displayName(string $name): string {
        $name=trim($name);
        if($name==='' || !mb_check_encoding($name,'UTF-8') || mb_strlen($name,'UTF-8')>60 || preg_match('/[\x00-\x1f\x7f]/',$name))throw new InvalidArgumentException('昵称须为1至60个字符。');
        return $name;
    }
    /**
     * 功能说明：只导出标准公开字段。
     * @param array|null $profile 适配器结果
     * @return array|null 过滤后的资料，密码/令牌等字段始终丢弃
     * @throws UnexpectedValueException 适配器返回不符合协议的字段或类型
     */
    public static function profile(?array $profile): ?array {
        if($profile===null)return null;
        foreach(['id','username','display_name','email','email_verified','active','has_password'] as $key)if(!array_key_exists($key,$profile))throw new UnexpectedValueException('会员适配器缺少字段：'.$key);
        foreach(['id','username','display_name','email'] as $key)if(!is_string($profile[$key]))throw new UnexpectedValueException('会员适配器文本字段类型无效：'.$key);
        foreach(['email_verified','active','has_password'] as $key)if(!is_bool($profile[$key]))throw new UnexpectedValueException('会员适配器状态类型无效：'.$key);
        if($profile['id']==='' || $profile['username']==='')throw new UnexpectedValueException('会员适配器缺少有效身份。');
        return array_intersect_key($profile,array_flip(['id','username','display_name','email','email_verified','active','has_password']));
    }
    /**
     * 功能说明：读取当前普通会员，不返回管理员凭据。参数说明：无显式参数。
     * @return array|null 当前公开资料
     */
    public function current(): ?array {return self::profile($this->store->current());}
    /**
     * 功能说明：读取指定会员公开资料，不产生登录。
     * @param string $id 会员ID
     * @return array|null 公开资料或不存在
     */
    public function find(string $id): ?array {return self::profile($this->store->find($id));}
    /**
     * 功能说明：查找邮箱，不自动合并账号。
     * @param string $email 邮箱
     * @param bool $lock 默认false；是否事务内锁定读取
     * @return array 公开资料列表
     */
    public function findByEmail(string $email,bool $lock=false): array {return array_map([self::class,'profile'],$this->store->findByEmail(self::email($email),$lock));}
    /**
     * 功能说明：创建未验证普通会员，具体权限由适配器固定。
     * @param array $data email、display_name及可选username/password
     * @return array 新会员公开资料
     * @throws InvalidArgumentException 邮箱或昵称类型/格式无效
     */
    public function register(array $data): array {
        if(!is_string($data['email']??null)||!is_string($data['display_name']??null))throw new InvalidArgumentException('邮箱和昵称须为字符串。');
        $data['email']=self::email($data['email']);$data['display_name']=self::displayName($data['display_name']);
        return self::profile($this->store->create($data));
    }
    /**
     * 功能说明：通过本地密码认证并建立普通会员会话。
     * @param string $username 登录名
     * @param string $password 原始密码
     * @param int $remember 默认0；记住时长（秒）
     * @return array|null 成功公开资料或失败
     * @throws DomainException 当前为管理员会话
     */
    public function passwordLogin(string $username,string $password,int $remember=0): ?array {
        if(!$this->store->allowsLogin())throw new DomainException('请先退出管理员，再使用普通会员登录。');
        $profile=$this->store->authenticate($username,$password);return $profile===null?null:self::profile($this->store->login((string)$profile['id'],$remember));
    }
    /**
     * 功能说明：在邮件验证服务已独立验证后标记邮箱；本方法本身不是验证证明。
     * @param string $id 会员ID
     * @param string $email 已证明邮箱
     * @return array 公开资料
     */
    public function verifyEmail(string $id,string $email): array {return self::profile($this->store->verifyEmail($id,self::email($email)));}
    /**
     * 功能说明：供第三方认证完成后建立已验证会员会话。
     * @param string $id 受信身份映射中的会员ID
     * @return array 公开资料
     * @throws DomainException 管理员上下文、停用或未验证会员
     */
    public function loginVerified(string $id): array {
        $profile=$this->find($id);
        if(!$this->store->allowsLogin() || !$profile || !$profile['active'] || !$profile['email_verified'])throw new DomainException('会员未完成验证、已停用，或当前为管理员会话。');
        return self::profile($this->store->login($id,0));
    }
    /**
     * 功能说明：仅退出普通会员。参数说明：无显式参数。
     * @return void 无返回值
     */
    public function logout(): void {$this->store->logout();}
}
