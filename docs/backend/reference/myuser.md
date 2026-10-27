# myUser：普通会员统一服务

源码：`lib/myUser.class.php`。运行基线PHP8.5.12。管理员认证不使用此接口。完整流程见[普通会员接口与第三方登录](../security/members.md)。

## 主要用法示例

在框架已初始化的应用模块中，$mystep为当前应用核心实例：

```php
$users = $mystep->users();
$current = $users->current();
$email = myUser::email('member@EXAMPLE.com');
$name = myUser::displayName('小明');
if ($current !== null) {
    echo htmlspecialchars($current['display_name'], ENT_QUOTES, 'UTF-8');
}
```

密码登录、注册和退出只能放在通过框架CSRF/来源及业务校验的分支。verifyEmail及loginVerified为受信服务接口，不能直接使用请求传入ID调用。

## 方法目录

| 方法 | 功能 |
| --- | --- |
| `myUser::__construct` | 接管应用提供的会员存储。 |
| `myUser::forApplication` | 加载已安装应用核心类，通过静态工厂取得会员资源，不实例化第二个控制器。 |
| `myUser::provider` | 取得存储适配器，供受信插件共享事务。参数说明：无显式参数。 |
| `myUser::email` | 规范化邮箱并限制输入，不把不同邮箱提供商的点号或加号地址自动合并。 |
| `myUser::displayName` | 核对必填昵称，仅保存文本。 |
| `myUser::profile` | 只导出标准公开字段。 |
| `myUser::current` | 读取当前普通会员，不返回管理员凭据。参数说明：无显式参数。 |
| `myUser::find` | 读取指定会员公开资料，不产生登录。 |
| `myUser::findByEmail` | 查找邮箱，不自动合并账号。 |
| `myUser::register` | 创建未验证普通会员，具体权限由适配器固定。 |
| `myUser::passwordLogin` | 通过本地密码认证并建立普通会员会话。 |
| `myUser::verifyEmail` | 在邮件验证服务已独立验证后标记邮箱；本方法本身不是验证证明。 |
| `myUser::loginVerified` | 供第三方认证完成后建立已验证会员会话。 |
| `myUser::logout` | 仅退出普通会员。参数说明：无显式参数。 |

## 方法、参数和使用示例

### myUser::__construct

接管应用提供的会员存储。

```php
public function __construct(interface_user $store);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$store` | interface_user；已配置的受信适配器 |

返回值：void 初始化完成

调用示例（$users或$provider须按上面的方式取得）：

```php
$users = new myUser($provider); // $provider已实现interface_user
```

### myUser::forApplication

加载已安装应用核心类，通过静态工厂取得会员资源，不实例化第二个控制器。

```php
public static function forApplication(string $app): self;
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$app` | string；与目录/核心类一致的应用标识 |

返回值：self 对应应用的普通会员服务

异常：InvalidArgumentException 应用标识或核心类不合法；LogicException 应用未实现会员适配器

调用示例（$users或$provider须按上面的方式取得）：

```php
$users = myUser::forApplication('CMS'); // 惰性工厂，不创建控制器
```

### myUser::provider

取得存储适配器，供受信插件共享事务。参数说明：无显式参数。

```php
public function provider(): interface_user;
```

无显式参数。

返回值：interface_user 当前适配器

调用示例（$users或$provider须按上面的方式取得）：

```php
$provider = $users->provider();
```

### myUser::email

规范化邮箱并限制输入，不把不同邮箱提供商的点号或加号地址自动合并。

```php
public static function email(string $email): string;
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$email` | string；待验证邮箱 |

返回值：string 小写域名、本地部分保留原值的邮箱

异常：InvalidArgumentException 邮箱无效、过长或含控制字符

调用示例（$users或$provider须按上面的方式取得）：

```php
$normalized = myUser::email('member@EXAMPLE.com');
```

### myUser::displayName

核对必填昵称，仅保存文本。

```php
public static function displayName(string $name): string;
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$name` | string；用户昵称 |

返回值：string 去除两端空白的昵称

异常：InvalidArgumentException 无效UTF-8、空值、控制字符或超过60字

调用示例（$users或$provider须按上面的方式取得）：

```php
$name = myUser::displayName(' 小明 ');
```

### myUser::profile

只导出标准公开字段。

```php
public static function profile(?array $profile): ?array;
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$profile` | array|null；适配器结果 |

返回值：array|null 过滤后的资料，密码/令牌等字段始终丢弃

异常：UnexpectedValueException 适配器返回不符合协议的字段或类型

调用示例（$users或$provider须按上面的方式取得）：

```php
$public = myUser::profile($adapterResult); // $adapterResult来自受信适配器
```

### myUser::current

读取当前普通会员，不返回管理员凭据。参数说明：无显式参数。

```php
public function current(): ?array;
```

无显式参数。

返回值：array|null 当前公开资料

调用示例（$users或$provider须按上面的方式取得）：

```php
$current = $users->current();
```

### myUser::find

读取指定会员公开资料，不产生登录。

```php
public function find(string $id): ?array;
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$id` | string；会员ID |

返回值：array|null 公开资料或不存在

调用示例（$users或$provider须按上面的方式取得）：

```php
$public = $users->find('12'); // 读取不代表认证成功
```

### myUser::findByEmail

查找邮箱，不自动合并账号。

```php
public function findByEmail(string $email,bool $lock=false): array;
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$email` | string；邮箱 |
| `$lock` | bool；默认false；是否事务内锁定读取 |

返回值：array 公开资料列表

调用示例（$users或$provider须按上面的方式取得）：

```php
$matches = $users->findByEmail('member@example.com'); // 不自动合并
```

### myUser::register

创建未验证普通会员，具体权限由适配器固定。

```php
public function register(array $data): array;
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$data` | array；email、display_name及可选username/password |

返回值：array 新会员公开资料

异常：InvalidArgumentException 邮箱或昵称类型/格式无效

调用示例（$users或$provider须按上面的方式取得）：

```php
// 仅在验证及事务服务控制下创建；返回的会员尚未验证。
$member = $users->register(['email'=>'member@example.com', 'display_name'=>'小明']);
```

### myUser::passwordLogin

通过本地密码认证并建立普通会员会话。

```php
public function passwordLogin(string $username,string $password,int $remember=0): ?array;
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$username` | string；登录名 |
| `$password` | string；原始密码 |
| `$remember` | int；默认0；记住时长（秒） |

返回值：array|null 成功公开资料或失败

异常：DomainException 当前为管理员会话

调用示例（$users或$provider须按上面的方式取得）：

```php
// 已通过CSRF/来源和登录限流；两个输入是原始字符串。
$member = $users->passwordLogin($username, $password, 0);
```

### myUser::verifyEmail

在邮件验证服务已独立验证后标记邮箱；本方法本身不是验证证明。

```php
public function verifyEmail(string $id,string $email): array;
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$id` | string；会员ID |
| `$email` | string；已证明邮箱 |

返回值：array 公开资料

调用示例（$users或$provider须按上面的方式取得）：

```php
// $id来自已核对的草稿，仅在本站邮件令牌验证通过后执行。
$member = $users->verifyEmail($id, $provenEmail);
```

### myUser::loginVerified

供第三方认证完成后建立已验证会员会话。

```php
public function loginVerified(string $id): array;
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$id` | string；受信身份映射中的会员ID |

返回值：array 公开资料

异常：DomainException 管理员上下文、停用或未验证会员

调用示例（$users或$provider须按上面的方式取得）：

```php
// $mappedId来自已完成平台认证的服务端映射，不能来自HTTP参数。
$member = $users->loginVerified($mappedId);
```

### myUser::logout

仅退出普通会员。参数说明：无显式参数。

```php
public function logout(): void;
```

无显式参数。

返回值：void 无返回值

调用示例（$users或$provider须按上面的方式取得）：

```php
// 已通过CSRF的退出请求。
$users->logout();
```
