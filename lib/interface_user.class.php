<?PHP
/********************************************
*                                           *
* Name    : Member Provider Interface (new) *
* Modifier: Windy2000                       *
* Time    : 2026-10-25                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/
/**
 * 文件：lib/interface_user.class.php (new)
 * 功能说明：普通会员存储及会话适配协议；不包含框架管理员认证或第三方平台协议。
 * 方法目录：
 *   public interface_user::scope(): string — 取得稳定的应用/租户身份命名空间。参数说明：无显式参数。
 *   public interface_user::database(): myDb — 取得与会员写入共用的数据库代理。参数说明：无显式参数。
 *   public interface_user::prefix(): string — 取得受信表前缀。参数说明：无显式参数。
 *   public interface_user::dialect(): string — 取得支持的SQL方言。参数说明：无显式参数。
 *   public interface_user::ready(): bool — 只读检查会员存储是否已支持标准扩展。参数说明：无显式参数。
 *   public interface_user::prepare(): void — 显式安装时准备必要存储，不在普通登录中调用。参数说明：无显式参数。
 *   public interface_user::allowsLogin(): bool — 判断当前会话是否允许普通会员登录。参数说明：无显式参数。
 *   public interface_user::find(string $id): ?array — 按主键读取公开资料。
 *   public interface_user::findByEmail(string $email, bool $lock = false): array — 查询同邮箱会员，保留历史重复记录。
 *   public interface_user::create(array $profile): array — 创建待验证普通会员，不接受权限字段。
 *   public interface_user::verifyEmail(string $id, string $email): array — 在独立验证成功后标记指定邮箱，不改变用户组权限。
 *   public interface_user::authenticate(string $username, string $password): ?array — 核对本地密码，不建立会话。
 *   public interface_user::login(string $id, int $remember = 0): array — 为已由受信服务认证的普通会员建立会话。
 *   public interface_user::current(): ?array — 重新核对当前普通会员会话。参数说明：无显式参数。
 *   public interface_user::logout(): void — 退出普通会员会话，不获得或改写管理员身份。参数说明：无显式参数。
 * 使用说明：应用实现协议，由核心类的userProvider()提供；myUser统一公开资料格式，插件不直接写应用会员表。
 */
interface interface_user {
    /**
     * 功能说明：取得稳定的应用/租户身份命名空间。参数说明：无显式参数。
     * @return string 不含凭据的稳定作用域
     */
    public function scope(): string;
    /**
     * 功能说明：取得与会员写入共用的数据库代理。参数说明：无显式参数。
     * @return myDb 支持参数绑定和事务的连接
     */
    public function database(): myDb;
    /**
     * 功能说明：取得受信表前缀。参数说明：无显式参数。
     * @return string 仅字母、数字、下划线的前缀
     */
    public function prefix(): string;
    /**
     * 功能说明：取得支持的SQL方言。参数说明：无显式参数。
     * @return string mysql或sqlite
     */
    public function dialect(): string;
    /**
     * 功能说明：只读检查会员存储是否已支持标准扩展。参数说明：无显式参数。
     * @return bool 可安全运行标准会员流程
     */
    public function ready(): bool;
    /**
     * 功能说明：显式安装时准备必要存储，不在普通登录中调用。参数说明：无显式参数。
     * @return void 失败抛异常
     */
    public function prepare(): void;
    /**
     * 功能说明：判断当前会话是否允许普通会员登录。参数说明：无显式参数。
     * @return bool 管理员上下文等不允许时为false
     */
    public function allowsLogin(): bool;
    /**
     * 功能说明：按主键读取公开资料。
     * @param string $id 应用会员ID
     * @return array|null 不含密码/令牌的资料或不存在
     */
    public function find(string $id): ?array;
    /**
     * 功能说明：查询同邮箱会员，保留历史重复记录。
     * @param string $email 规范化邮箱
     * @param bool $lock 默认false；true在已有事务内采用当前锁定读取
     * @return array 公开资料列表
     */
    public function findByEmail(string $email, bool $lock = false): array;
    /**
     * 功能说明：创建待验证普通会员，不接受权限字段。
     * @param array $profile email、display_name及可选username/password
     * @return array 新会员公开资料
     */
    public function create(array $profile): array;
    /**
     * 功能说明：在独立验证成功后标记指定邮箱，不改变用户组权限。
     * @param string $id 会员ID
     * @param string $email 本次证明的邮箱
     * @return array 更新后公开资料
     */
    public function verifyEmail(string $id, string $email): array;
    /**
     * 功能说明：核对本地密码，不建立会话。
     * @param string $username 登录名
     * @param string $password 原始密码
     * @return array|null 成功的公开资料或失败
     */
    public function authenticate(string $username, string $password): ?array;
    /**
     * 功能说明：为已由受信服务认证的普通会员建立会话。
     * @param string $id 会员ID
     * @param int $remember 本地密码登录记住时长，第三方默认0
     * @return array 实际登录的公开资料
     */
    public function login(string $id, int $remember = 0): array;
    /**
     * 功能说明：重新核对当前普通会员会话。参数说明：无显式参数。
     * @return array|null 有效公开资料或未登录
     */
    public function current(): ?array;
    /**
     * 功能说明：退出普通会员会话，不获得或改写管理员身份。参数说明：无显式参数。
     * @return void 无返回值
     */
    public function logout(): void;
}
