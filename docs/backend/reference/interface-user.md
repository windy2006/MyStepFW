# interface_user：应用会员适配协议

源码：`lib/interface_user.class.php`。运行基线PHP8.5.12。管理员认证不使用此接口。完整流程见[普通会员接口与第三方登录](../security/members.md)。

## 接口与实现示例

CMS的完整实现为app/CMS/lib.php中的app\CMS\MemberProvider，通过CMS::userProvider()构建。实现其他应用时，在其lib.php中实现全部方法，并在同名核心类返回该对象：

```php
// 放在已初始化的会员应用模块中；不另外实例化应用控制器。
$users = $mystep->users();
$provider = $users->provider();
$installed = $provider->ready();    // 只检查，不隐式改表
$current = $provider->current();   // 仅公开普通会员资料
```

应用适配器的scope必须稳定且隔离租户。database必须是会员写入的同一myDb连接，支持事务，错误策略为抛异常；MySQL使用InnoDB。prepare只在管理员显式安装时调用。findByEmail返回列表，不凭“相同邮箱”选一个账号直接登录。

## 方法目录

| 方法 | 功能 |
| --- | --- |
| `interface_user::scope` | 取得稳定的应用/租户身份命名空间。参数说明：无显式参数。 |
| `interface_user::database` | 取得与会员写入共用的数据库代理。参数说明：无显式参数。 |
| `interface_user::prefix` | 取得受信表前缀。参数说明：无显式参数。 |
| `interface_user::dialect` | 取得支持的SQL方言。参数说明：无显式参数。 |
| `interface_user::ready` | 只读检查会员存储是否已支持标准扩展。参数说明：无显式参数。 |
| `interface_user::prepare` | 显式安装时准备必要存储，不在普通登录中调用。参数说明：无显式参数。 |
| `interface_user::allowsLogin` | 判断当前会话是否允许普通会员登录。参数说明：无显式参数。 |
| `interface_user::find` | 按主键读取公开资料。 |
| `interface_user::findByEmail` | 查询同邮箱会员，保留重复记录。 |
| `interface_user::create` | 创建待验证普通会员，不接受权限字段。 |
| `interface_user::verifyEmail` | 在独立验证成功后标记指定邮箱，不改变用户组权限。 |
| `interface_user::authenticate` | 核对本地密码，不建立会话。 |
| `interface_user::login` | 为已由受信服务认证的普通会员建立会话。 |
| `interface_user::current` | 重新核对当前普通会员会话。参数说明：无显式参数。 |
| `interface_user::logout` | 退出普通会员会话，不获得或改写管理员身份。参数说明：无显式参数。 |

## 方法、参数和使用示例

### interface_user::scope

取得稳定的应用/租户身份命名空间。参数说明：无显式参数。

```php
public function scope(): string;
```

无显式参数。

返回值：string 不含凭据的稳定作用域

调用示例（$users或$provider须按上面的方式取得）：

```php
$value = $provider->scope();
```

### interface_user::database

取得与会员写入共用的数据库代理。参数说明：无显式参数。

```php
public function database(): myDb;
```

无显式参数。

返回值：myDb 参数绑定和事务的连接

调用示例（$users或$provider须按上面的方式取得）：

```php
$value = $provider->database();
```

### interface_user::prefix

取得受信表前缀。参数说明：无显式参数。

```php
public function prefix(): string;
```

无显式参数。

返回值：string 仅字母、数字、下划线的前缀

调用示例（$users或$provider须按上面的方式取得）：

```php
$value = $provider->prefix();
```

### interface_user::dialect

取得支持的SQL方言。参数说明：无显式参数。

```php
public function dialect(): string;
```

无显式参数。

返回值：string mysql或sqlite

调用示例（$users或$provider须按上面的方式取得）：

```php
$value = $provider->dialect();
```

### interface_user::ready

只读检查会员存储是否已支持标准扩展。参数说明：无显式参数。

```php
public function ready(): bool;
```

无显式参数。

返回值：bool 可安全运行标准会员流程

调用示例（$users或$provider须按上面的方式取得）：

```php
$value = $provider->ready();
```

### interface_user::prepare

显式安装时准备必要存储，不在普通登录中调用。参数说明：无显式参数。

```php
public function prepare(): void;
```

无显式参数。

返回值：void 失败抛异常

调用示例（$users或$provider须按上面的方式取得）：

```php
// 仅明确安装操作，先备份会员数据库；不能放在普通页面。
$provider->prepare();
```

### interface_user::allowsLogin

判断当前会话是否允许普通会员登录。参数说明：无显式参数。

```php
public function allowsLogin(): bool;
```

无显式参数。

返回值：bool 管理员上下文等不允许时为false

调用示例（$users或$provider须按上面的方式取得）：

```php
$value = $provider->allowsLogin();
```

### interface_user::find

按主键读取公开资料。

```php
public function find(string $id): ?array;
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$id` | string；应用会员ID |

返回值：array|null 不含密码/令牌的资料或不存在

调用示例（$users或$provider须按上面的方式取得）：

```php
$public = $provider->find('12');
```

### interface_user::findByEmail

查询同邮箱会员，保留重复记录。

```php
public function findByEmail(string $email, bool $lock = false): array;
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$email` | string；规范化邮箱 |
| `$lock` | bool；默认false；true在已有事务内采用当前锁定读取 |

返回值：array 公开资料列表

调用示例（$users或$provider须按上面的方式取得）：

```php
$matches = $provider->findByEmail('member@example.com', false);
```

### interface_user::create

创建待验证普通会员，不接受权限字段。

```php
public function create(array $profile): array;
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$profile` | array；email、display_name及可选username/password |

返回值：array 新会员公开资料

调用示例（$users或$provider须按上面的方式取得）：

```php
$created = $provider->create(['email'=>'member@example.com', 'display_name'=>'小明']); // 仅受信服务，未验证
```

### interface_user::verifyEmail

在独立验证成功后标记指定邮箱，不改变用户组权限。

```php
public function verifyEmail(string $id, string $email): array;
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$id` | string；会员ID |
| `$email` | string；本次证明的邮箱 |

返回值：array 更新后公开资料

调用示例（$users或$provider须按上面的方式取得）：

```php
$member = $provider->verifyEmail($authenticatedId, $provenEmail); // 本站邮件令牌已验证
```

### interface_user::authenticate

核对本地密码，不建立会话。

```php
public function authenticate(string $username, string $password): ?array;
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$username` | string；登录名 |
| `$password` | string；原始密码 |

返回值：array|null 成功的公开资料或失败

调用示例（$users或$provider须按上面的方式取得）：

```php
$checked = $provider->authenticate($username, $password); // 只核对，不创建会话
```

### interface_user::login

为已由受信服务认证的普通会员建立会话。

```php
public function login(string $id, int $remember = 0): array;
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$id` | string；会员ID |
| `$remember` | int；本地密码登录记住时长，第三方默认0 |

返回值：array 实际登录的公开资料

调用示例（$users或$provider须按上面的方式取得）：

```php
$member = $provider->login($authenticatedId, 0); // 仅受信认证服务使用，先核对证明
```

### interface_user::current

重新核对当前普通会员会话。参数说明：无显式参数。

```php
public function current(): ?array;
```

无显式参数。

返回值：array|null 有效公开资料或未登录

调用示例（$users或$provider须按上面的方式取得）：

```php
$value = $provider->current();
```

### interface_user::logout

退出普通会员会话，不获得或改写管理员身份。参数说明：无显式参数。

```php
public function logout(): void;
```

无显式参数。

返回值：void 无返回值

调用示例（$users或$provider须按上面的方式取得）：

```php
$provider->logout();
```
