# 普通会员接口与第三方登录

本章针对普通会员。框架管理员继续使用管理员认证；CMS管理员仍来自sys_op，不共享第三方登录入口。

## 三层职责

| 层 | 文件 | 职责 |
| --- | --- | --- |
| 公共服务 | `lib/myUser.class.php` | 规范资料字段、昵称/邮箱校验，统一注册、密码登录、受信身份登录和退出 |
| 应用适配协议 | `lib/interface_user.class.php` | 应用自己的表、事务、账号状态、密码和会话；CMS实现在`app/CMS/lib.php`的`app\CMS\MemberProvider` |
| 第三方插件 | `plugin/social/` | 授权码交换、平台身份映射、资料补全、本站邮件验证、关联和解除关联 |

公共lib只包含会员抽象及通用校验。各应用适配器放在自己的lib.php，插件特有的OAuth、数据库表及模板保留在plugin/social。没有调用会员服务的应用不会额外打开会员连接；未实现工厂的应用调用users()时抛出明确异常。

## 统一资料结构

```php
[
    'id' => '12',                   // 非空字符串主键；不是前端可提交的认证证明
    'username' => 'member12',       // 非空登录名
    'display_name' => '小明',       // 展示昵称
    'email' => 'member@example.com',
    'email_verified' => true,       // 严格bool：本站当前邮箱已经验证
    'active' => true,               // 严格bool：普通会员未被停用
    'has_password' => false,        // 严格bool：是否存在可用本地密码
]
```

myUser按白名单导出以上字段，拒绝错误类型，丢弃适配器附加的密码摘要、激活码、平台令牌等信息。昵称是1至60字符的有效UTF-8文本，保存时不做HTML转换，在输出位置统一转义；邮箱限制254字节，域名转小写，不按服务商规则合并点号/加号地址。CMS查询邮箱时不区分大小写，并保留查询结果列表以识别重复账号。

## 应用取用与本地密码登录

下面代码位于已经初始化的应用模块中，$mystep是当前应用核心实例。使用当前应用会话；不能在HTTP处理里另建一个CMS控制器。

```php
$users = $mystep->users();
$current = $users->current();          // 未登录或已停用/密码版本失效为null
if ($current !== null) {
    echo htmlspecialchars($current['display_name'], ENT_QUOTES, 'UTF-8');
}

// 只在通过框架CSRF和登录限流/验证码的POST分支调用。
// $username和$password必须先检查为原始字符串，密码不要HTML转义。
$member = $users->passwordLogin($username, $password, 0);
if ($member === null) {
    // 用统一“账号或密码错误”信息处理，不展示数据库记录。
}

// 只清理普通会员状态，不取得、覆盖管理员权限。
$users->logout();
```

CMS仅在普通会员扩展字段存在时让显式密码登录走此服务；未安装social的旧表沿用原密码流程。既有记住登录令牌、密码摘要升级和会话版本校验继续有效。第三方创建的账号默认password_enabled=0，随机密码摘要不能用作本地密码入口；会员在资料页设置密码后才能使用本地登录。

## 其他应用接入

应用核心类与目录同名并继承myStep；在应用已有lib.php实现interface_user，在核心类提供静态userProvider工厂。CMS::userProvider()是完整工作范例：独立连接与实例惰性复用、抛异常的数据库错误策略、稳定租户作用域及权限隔离。

```php
class Example extends myStep {
    public static function userProvider(): interface_user {
        require_once __DIR__.'/lib.php';
        // ExampleMemberProvider由应用lib.php实现完整interface_user协议。
        // 工厂读取服务端配置，不能把请求中的库名、前缀、类名交给构造器。
        return new \app\Example\ExampleMemberProvider();
    }
}
```

适配器必须提供同一myDb连接给会员写入和插件存储；MySQL表必须支持事务。不要在数据库错误回调中直接输出页面或exit，否则事务可能无法按预期回滚。scope绑定应用/租户和会员存储，不能包含密码，也不能在同一批会员间随意变化。CMS使用CMS加表前缀作为逻辑作用域，身份表与会员表共用同一数据库；不依赖数据库主机或库名，保留数据搬迁数据库时不会仅因连接地址改变而丢失关联。

ready()只能只读检查。prepare()必须显式调用并保留已有账号；它不是普通页面的初始化步骤。CMS的prepare会补充display_name、email_verified_at、password_enabled，将邮箱扩至254字符，并将MyISAM会员表转换为InnoDB。DDL无法保证整体回滚，请在安装插件前备份数据库；没有安装插件时不会自动改表。

会员资料、登录及授权页必须关闭共享页面内容缓存，并使用private, no-store响应；模板编译缓存仍可复用。CMS会员模块已统一处理，避免跨会话复用邮箱、绑定信息或表单令牌。

## 第三方认证闭环

1. 登录/注册页POST平台按钮，校验CSRF和来源，建立绑定浏览器的10分钟一次性state；Google/GitHub同时使用PKCE。
2. 平台回调先消费state，再由服务端交换授权码并读取平台身份。只信任固定HTTPS端点的响应，不使用浏览器提交的OpenID或邮箱认定身份。
3. 对没有绑定的身份，只建立短时草稿，预填的平台昵称/邮箱可修改；不立即创建账号。
4. 用户提交昵称和邮箱，本站SMTP发送短时验证邮件。即使平台声称email_verified，仍必须验证本站邮箱。
5. 同一浏览器打开邮件链接，确认页通过POST提交一次性令牌；事务中创建固定普通组账号、标记邮箱、保存身份映射并消费令牌，提交后才轮换会话并登录。
6. 后续相同平台/应用身份已绑定、账号有效且当前邮箱仍验证通过时，认证后直接登录，不再重复填写和发信。

邮件令牌放在URL片段中，服务器访问日志收不到片段。浏览器脚本读取片段到POST隐藏字段并清理地址栏，不自动提交。GET打开邮件不会创建账号；另一浏览器没有原流程会话，无法完成。若邮件软件切换浏览器，请将完整原邮件链接复制到最初发起授权的浏览器。

## 已有账号、修改邮箱与解绑

- 同邮箱已有会员：不能自动合并。先用原账号登录，从个人资料绑定平台，再验证现有账号邮箱。绑定不会改变原昵称或用户组。
- 已绑定身份属于另一个会员：拒绝关联，不能凭相同邮箱覆盖归属。
- 更换资料邮箱：CMS重置邮箱验证时间，已有平台必须重新验证当前邮箱后才能用于直接登录。
- 解绑：只允许当前会员POST操作；没有本地密码时，不能删除最后一个仍启用、配置一致且邮箱有效的平台入口。
- 停用会员、管理员上下文、过期草稿、重放state/邮件：全部拒绝建立普通会员登录。

## 受信接口边界

register()创建未验证会员，不接受HTTP中的group_id等权限字段。verifyEmail()和loginVerified()是供已完成认证的服务端调用的受信方法，本身不验证邮箱令牌或平台授权码。不要暴露“提交user_id直接调用loginVerified”的API。

```php
// 仅展示可信服务器内部的顺序；应由social的事务服务实际协调。
// $mappedId来自已核对的平台映射，不能来自$_GET或$_POST。
$member = $users->find($mappedId);
if ($member && $member['active'] && $member['email_verified']) {
    $users->loginVerified($mappedId);
}
```

完整配置、回调地址、平台申请、存储和限流见[social插件接入指南](../../showcase/plugins/social.md)。逐方法说明见[myUser](../reference/myuser.md)及[interface_user](../reference/interface-user.md)。
