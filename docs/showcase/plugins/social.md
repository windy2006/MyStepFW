# social · 普通会员第三方登录

版本1.0.0，运行基线PHP8.5.12及以上8.x。微信（网站扫码或公众号网页）、QQ、Google、GitHub共用普通会员服务；当前CMS已提供适配器，其他应用可实现interface_user后接入。管理员账号不使用此入口。

## 首次登录与后续登录

首次授权后补全昵称和邮箱，必须完成本站邮件验证，事务提交后才创建普通会员、绑定平台并登录。即使平台返回已验证邮箱也不能跳过本站验证。之后同一个平台应用身份再次授权，若账号有效、绑定及邮箱未改变，就直接登录。

默认关闭全部平台，应用ID/密钥为空。插件文件复制到网站并不会自动安装、改表、发信或启用。测试替身不是平台正式授权结果；上线需要自行申请平台应用、配置域名并完成实际授权验证。

## 安装、设置和开启顺序

1. 备份会员数据库。CMS旧会员表若是MyISAM，安装会将其转换为InnoDB，并补充昵称、邮箱验证时间和本地密码开关，扩展邮箱长度；会员主键和原记录保留。DDL部分失败时可修正权限/结构后重新安装，不会删除账号。
2. 在新后台或旧后台“插件管理”找到“第三方登录”，安装到CMS（或已实现接口的其他会员应用）。插件同时绑定框架myStep以加载后台管理路由，会员模块仍只在所选app生效。数据库账号需要ALTER、CREATE、INDEX等安装权限；运行时需要相应表的SELECT/INSERT/UPDATE/DELETE权限。
3. 在插件设置填写会员应用、规范入口、站点名称、有效期、各平台应用ID与密钥。暂时保持总开关关闭，可逐个平台配置。
4. 在会员应用的“参数设置”配置email组SMTP服务器、模式、端口、用户和密码。发件邮箱优先email.from，否则email.user；必须为有效邮箱，不使用PHP mail回退。生产环境采用邮箱服务商要求的TLS及认证。
5. 在平台控制台配置精确回调URI或对应授权域名，启用所需平台，再开启插件总开关。框架插件自身也须处于启用且绑定会员应用的状态。
6. 用普通访客浏览器分别验证平台授权、邮件确认、资料页绑定、退出及再次登录。不能使用管理员会话测试普通会员绑定。

## 设置项目

| 项目/键 | 含义 |
| --- | --- |
| enabled | 总开关，默认false；为true时要求规范入口、至少一个平台及相应凭据完整 |
| app | 会员应用目录/核心类名，默认CMS；作用域由应用适配器提供 |
| endpoint | 固定HTTPS认证入口，必须以/结尾，不允许账号、片段或普通查询参数；不从不受信Host构造回调 |
| site_name | 邮件发件展示名，1至80字符 |
| email_ttl | 邮件有效期，默认900秒，300–1800秒，不能超过草稿时长 |
| pending_ttl | 资料草稿有效期，默认1800秒，600–3600秒 |
| wechat_mode | web：网站扫码；mp：公众号网页；二者使用各自应用凭据，不自动按UnionID合并 |
| 平台_enabled | 分别控制wechat、qq、google、github |
| 平台_client_id | 平台颁发的AppID/Client ID |
| 平台_secret | 服务端密钥；后台密码框留空保留原值，不向浏览器公开，不导出到插件包 |

配置存放在plugin/social/config.php；config_default.php只保存无凭据默认值。中文/英文表单说明位于config/chs.php、en.php，校验沿用myConfig及checkForm，服务器仍做独立校验。插件与应用专属代码都保留在各自目录。

建议本站规范入口：`https://cms.mystep.com/user/social/`。其他可用路由示例：

```text
Rewrite:  https://example.com/CMS/user/social/
Pathinfo: https://example.com/index.php/CMS/user/social/
Query:    https://example.com/index.php?/CMS/user/social/
```

须使用实际可达的域名与路由模式，平台后台配置必须与生成地址一致。插件禁止把HTTPS自动降为HTTP。更换入口、租户、AppID或微信场景会改变认证上下文；进行中的授权需重新开始，已有身份不能跨AppID自动合并。修改app后须重新安装/调整应用绑定，以准备对应数据库。

## 平台申请与回调

若endpoint使用`https://cms.mystep.com/user/social/`，回调为：

| 平台 | 地址 | 所需平台应用 |
| --- | --- | --- |
| 微信 | https://cms.mystep.com/user/social/callback/wechat | 开放平台网站应用web，或公众号网页授权mp；按对应产品登记授权域名 |
| QQ | https://cms.mystep.com/user/social/callback/qq | QQ互联网站应用，使用get_user_info权限 |
| Google | https://cms.mystep.com/user/social/callback/google | Web OAuth客户端，登记精确Authorized redirect URI，范围openid email profile |
| GitHub | https://cms.mystep.com/user/social/callback/github | OAuth App，登记Authorization callback URL，范围read:user user:email |

后台“第三方登录”管理页会列出当前配置生成的回调地址，不显示密钥。微信一个插件实例选择一种场景；需要同时提供网站扫码与公众号网页两个不同AppID时，当前版本不提供双客户端并行配置。QQ/微信通常不提供邮箱，必须在本站补填。

授权码、客户端密钥仅由服务端发送到代码内固定的HTTPS平台端点，校验TLS证书，禁止跟随重定向，有连接/读取超时和64KB响应上限。Google从受认证的userinfo取得sub，GitHub采用稳定数字id；不解码未验证JWT认定身份。QQJSONP只解析严格包装的JSON，不执行脚本。

平台细则以官方文档为准：[微信网站登录](https://developers.weixin.qq.com/doc/oplatform/Website_App/WeChat_Login/Wechat_Login.html)、[微信网页授权](https://developers.weixin.qq.com/doc/offiaccount/OA_Web_Apps/Wechat_webpage_authorization.html)、[QQ授权码交换](https://wiki.connect.qq.com/使用authorization_code获取access_token/)、[Google网站OAuth](https://developers.google.com/identity/protocols/oauth2/web-server)、[GitHub OAuth](https://docs.github.com/en/apps/oauth-apps/building-oauth-apps/authorizing-oauth-apps)。

## 账号关联与资料维护

登录页/注册页显示已启用的平台按钮，个人资料页显示绑定和管理入口。绑定现有账号时须先用原方式登录，再用该会员当前邮箱完成本站验证；相同邮箱不会自动并入账号。第三方创建的账号固定为普通会员组，本地登录名由服务器生成，昵称用于展示；默认没有可用本地密码，可在资料页设置后使用该登录名和密码登录。

用户更改邮箱后，已有平台必须重新证明当前邮箱，不能继续沿用旧邮箱的绑定证明。解绑只允许本人执行；无本地密码时不能移除最后一个有效平台入口。若管理员主动关闭全部入口或更换平台AppID，系统不会绕过配置自动恢复访问，需由站点管理员协助恢复配置或按本站账号恢复流程处理。

## 邮件验证与限额

state十分钟有效、每会话最多五个，回调消费后不能重用；首次草稿绑定发起授权的浏览器会话。验证邮件60秒内不能重发，每邮箱每小时最多5封，每来源IP每小时最多20封。额度以数据库事务记录，不能靠更换浏览器绕过邮箱/IP限制。代理部署时先正确配置框架受信代理，避免所有用户共用代理IP。

邮件链接的令牌位于URL片段，确认脚本填入POST隐藏字段后移除地址栏片段；不会自动提交。GET预览不会创建账号或消费令牌。必须在发起授权的同一浏览器打开或粘贴完整链接；更换浏览器、退出/清理会话、过期或重发邮件后旧证明不可用。SMTP返回成功只表示服务器接收，不等于收件箱最终送达。

## 文件和存储

| 文件 | 作用 |
| --- | --- |
| class.php | 生命周期、配置校验、HTTP方法/来源/CSRF检查与模板装配 |
| index.php | 仅为活动宿主登记user_social模块，不隐式安装 |
| route.php | 现有管理员认证保护的插件管理页 |
| SocialOAuth.class.php | 四平台服务端授权客户端，可在受信PHP测试中注入传输替身 |
| SocialStore.class.php | 与会员适配器共用myDb事务，身份/草稿/限额表 |
| SocialFlow.class.php | 状态、草稿、邮件证明、关联及登录流程 |
| SocialMailer.class.php | 复用myEmail和宿主email设置，失败抛异常并撤销令牌 |
| module/social.php、template/social.tpl | 前台流程与模板对应；buttons、manage也分别对应 |
| asset/verify.js | 本地邮件片段读取，只填表不发请求；不引入外部资源 |

以CMS默认前缀cms_为例：cms_social_identities保存作用域化身份摘要、会员ID及验证邮箱；cms_social_pending保存短时资料草稿和邮件令牌摘要；cms_social_rate保存散列后的额度键。身份表不保存平台访问/刷新令牌和OpenID原文；邮箱、昵称等业务资料仍属站点会员数据。过期草稿/额度在发起授权时低频清理，不删除账号或有效映射。

MySQL使用InnoDB，SQLite也支持相同事务流程；其他数据库适配器须另行实现，不能直接改dialect假装支持。独立插件包排除在线config.php；重新安装缺少配置的包时，安装钩子会用并发保护生成无凭据默认配置。卸载只取消路由、菜单和应用绑定，保留会员及映射；删除文件前先备份配置和数据库，需要清理数据时由管理员明确处理，不自动删会员。

## 开发接入与测试边界

[普通会员服务](../../backend/security/members.md)说明统一字段、应用工厂、权限及受信方法边界。平台验证只由服务器完成；不能对外提供“提交会员ID直接登录”的接口。页面写操作使用POST、框架CSRF与插件复核，模板沿用myTemplate及现有Bootstrap样式，表单沿用checkForm。

本机专项覆盖MySQL/SQLite状态和事务、模拟四平台响应、真实CMS模块及临时HTTP/SMTP服务、邮件脚本DOM；不使用真实平台凭据，不向外部发送邮件。正式平台应用审核、域名解析、证书、SMTP送达及授权返回仍需部署后验证。默认配置保持关闭，测试不代表已开通正式第三方登录。
