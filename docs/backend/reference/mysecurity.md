# mySecurity：输入预算和CSRF

修订日期：2026-10-23。功能详解。

## 职责与调用位置

inspectRequest可对合成输入做只读检查；guardRequest属于入口；会话令牌校验不代替业务授权。

源码：`lib/mySecurity.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
$problem = mySecurity::inspectRequest(
    ['REQUEST_METHOD'=>'POST', 'CONTENT_LENGTH'=>'5'],
    ['get'=>[], 'post'=>['title'=>'Hi'], 'cookie'=>[], 'files'=>[]]
);
assert($problem === []);
// 在框架已启动会话的页面处理器中：
$token = mySecurity::token();
assert(mySecurity::valid($token));
```

## 声明与类职责

### mySecurity

功能说明：提供启动期输入预算及请求入口和最终HTML输出阶段的会话安全保护。

使用说明：
  1. guardRequest()不依赖会话；CSRF等方法依赖已启动的Session及框架配置、域名和路由。
  2. 写请求携带目标应用会话的 CSRF 令牌，不能假定不同应用共享同一令牌。
  3. decorate() 在页面缓存读取后执行，装饰结果不得写入共享缓存。
  4. 来源、动作识别及 CSRF 校验不替代登录、权限和资源归属检查。
  5. 自定义应用和插件应限制状态修改的 HTTP 方法，并完成业务授权。

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `mySecurity::inspectRequest` | 有预算地检查已解析输入和声明体积，返回拒绝原因。 |
| `mySecurity::requestShape` | 在固定深度和累计预算内检查输入，不复制或序列化请求内容。 |
| `mySecurity::guardRequest` | 在框架应用和会话启动前拒绝超预算请求，不把它当作PHP解析器补丁。 |
| `mySecurity::token` | 获取当前会话的 CSRF 令牌，缺失或格式异常时重新生成 |
| `mySecurity::valid` | 校验提交的 CSRF 令牌是否与当前会话中保存的令牌一致 |
| `mySecurity::origins` | 汇总允许访问当前应用的来源地址，并去重返回 |
| `mySecurity::trustedReferer` | 检查 Referer 中的来源是否与允许列表精确匹配 |
| `mySecurity::originAllowed` | 根据 Origin 和浏览器 Fetch Metadata 判断当前请求来源是否允许 |
| `mySecurity::adminPaths` | 获取用于识别内置后台入口的路径前缀 |
| `mySecurity::administrativeContext` | 判断当前路由是否从已知后台路径前缀开始 |
| `mySecurity::mutates` | 识别 URL 是否对应已知的内置状态修改操作 |
| `mySecurity::guard` | 在业务处理前执行请求方法、来源和会话 CSRF 令牌检查 |
| `mySecurity::respond` | 向客户端提供当前应用会话的 CSRF 令牌，并结束请求 |
| `mySecurity::decorate` | 为已渲染的完整 HTML 页面注入会话令牌和前端安全适配脚本 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### mySecurity::inspectRequest

功能说明：有预算地检查已解析输入和声明体积，返回拒绝原因。

仅检查Content-Length及PHP已经生成的输入数组，不读取php://input、临时上传文件或
反序列化内容。缺少声明长度时仍检查输入结构，但无法据此限制原始或解码请求体。
PHP解析请求可能早于此检查；本方法不修复PHP内核漏洞，也不能计算原始multipart分段数。

```text
public static function inspectRequest(array $server, array $input, array $limits = []);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$server` | array；请求服务器变量，只读取CONTENT_LENGTH |
| `$input` | array；GET、POST、COOKIE、FILES等来源数组组成的集合，不修改原数组 |
| `$limits` | array；depth/nodes/bytes/body_bytes的正整数覆盖值（默认：[]） |

**返回值**：array 无问题为[]；拒绝时返回status及固定error文字，不回显输入

**异常与失败**：InvalidArgumentException 受信预算配置无效；深度不得超过64

调用形式：

```php
$result = mySecurity::inspectRequest($server, $input, $limits);
```

定位：`lib/mySecurity.class.php:72`。

### mySecurity::requestShape

功能说明：在固定深度和累计预算内检查输入，不复制或序列化请求内容。

使用strlen取得字节长度，先与剩余额度比较再相减，避免累计整数溢出；循环引用
同样受到深度预算限制。只接受数组、字符串及普通标量，不调用对象的字符串转换。

```text
private static function requestShape($value, $depth, array &$remaining, array $limits);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$value` | mixed；当前输入节点，数组按写时复制语义只读遍历 |
| `$depth` | int；当前数组深度，来源数组从0开始 |
| `&$remaining` | array；剩余nodes与bytes额度，由各来源共同消耗 |
| `$limits` | array；已验证预算，包含depth等字段 |

**返回值**：bool 输入结构及字节数均在预算内时为true

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/mySecurity.class.php:101`。

### mySecurity::guardRequest

功能说明：在框架应用和会话启动前拒绝超预算请求，不把它当作PHP解析器补丁。

预算可由入口受信常量MYSTEP_REQUEST_LIMITS覆盖；POST还受实际post_max_size的
较小正值限制。拒绝时直接输出不缓存的固定JSON，不创建会话、路由或应用对象。
请求体解析可能已在本方法之前完成；无Content-Length的请求仍需Web服务器限制。

参数说明：无显式参数。

```text
public static function guardRequest();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 合法请求返回；400/413响应后结束请求，HEAD不输出正文

**异常与失败**：InvalidArgumentException 入口预算常量不是数组或预算配置无效

调用形式：

```php
$result = mySecurity::guardRequest();
```

定位：`lib/mySecurity.class.php:131`。

### mySecurity::token

功能说明：获取当前会话的 CSRF 令牌，缺失或格式异常时重新生成

令牌由 32 字节安全随机数转为 64 位小写十六进制字符串，保存在
$_SESSION['_ms_csrf'] 中。有效令牌在同一会话内复用，不自行设置过期时间；
调用前应已启动目标应用的会话，应用间不得假定共享同一令牌。

参数说明：无显式参数。

```text
public static function token();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string 当前会话的 64 位 CSRF 令牌

**异常与失败**：Exception 无法取得安全随机数时由 random_bytes() 抛出

调用形式：

```php
$result = mySecurity::token();
```

定位：`lib/mySecurity.class.php:157`。

### mySecurity::valid

功能说明：校验提交的 CSRF 令牌是否与当前会话中保存的令牌一致

仅接受字符串，并使用 hash_equals() 进行常量时间比较；不会创建或更新令牌。
未保存会话令牌、值类型异常或内容不一致时返回 false。

```text
public static function valid($token);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$token` | mixed；来自请求头或表单的待校验令牌 |

**返回值**：bool 是否匹配当前会话中保存的令牌

调用形式：

```php
$result = mySecurity::valid($token);
```

定位：`lib/mySecurity.class.php:171`。

### mySecurity::origins

功能说明：汇总允许访问当前应用的来源地址，并去重返回

来源由当前请求的协议和 Host、绑定域名、Cookie 域名及 web.cors_origins 组成。
绑定域名和 Cookie 域名沿用当前请求的协议、显式端口；额外来源按配置原值加入，
支持数组、配置对象或逗号分隔字符串。API上下文由逐接口跨域开关决定是否接纳当前合法Origin；普通页面继续使用原名单。本方法不执行身份授权。

参数说明：无显式参数。

```text
public static function origins();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string[] 允许的来源列表，格式为“协议://主机[:端口]”

**上下文变量**：array $domain 域名绑定表，以域名为键；myConfig $ms_setting 当前应用配置，使用 cookie.domain 和 web.cors_origins

调用形式：

```php
$result = mySecurity::origins();
```

定位：`lib/mySecurity.class.php:188`。

### mySecurity::trustedReferer

功能说明：检查 Referer 中的来源是否与允许列表精确匹配

解析协议、主机和显式端口，将协议及主机转为小写后与 origins() 比较。
不参与比较的路径、查询及片段不影响结果；拒绝缺少协议或主机、包含账号密码
的地址及非字符串输入。不会按域名子串放行，也不自动统一默认端口的不同写法。
Referer 可由客户端伪造，本方法适用于来源或防盗链检查，不能作为用户授权。

```text
public static function trustedReferer($referer);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$referer` | mixed；待检查的完整 Referer URL |

**返回值**：bool Referer 是否来自允许列表中的来源

调用形式：

```php
$result = mySecurity::trustedReferer($referer);
```

定位：`lib/mySecurity.class.php:217`。

### mySecurity::originAllowed

功能说明：根据 Origin 和浏览器 Fetch Metadata 判断当前请求来源是否允许

提供 Origin 时，与 origins() 列表进行严格匹配；未提供 Origin 时，只有
Sec-Fetch-Site 明确为 cross-site 才拒绝。两者都缺失时返回 true，后续写请求
仍须由 guard() 校验会话令牌；本方法本身不校验令牌，也不执行权限检查。

参数说明：无显式参数。

```text
public static function originAllowed();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 当前请求的来源信息是否通过检查

调用形式：

```php
$result = mySecurity::originAllowed();
```

定位：`lib/mySecurity.class.php:236`。

### mySecurity::adminPaths

功能说明：获取用于识别内置后台入口的路径前缀

合并 gen.path_admin（缺省 console）、当前应用的 web.path_admin 和兼容入口
admin_cms，移除空值及重复项。返回配置中的前缀原值，匹配时再处理首尾斜杠。

参数说明：无显式参数。

```text
public static function adminPaths();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string[] 去重后的后台路径前缀列表

**上下文变量**：myConfig $ms_setting 当前应用配置

调用形式：

```php
$result = mySecurity::adminPaths();
```

定位：`lib/mySecurity.class.php:253`。

### mySecurity::administrativeContext

功能说明：判断当前路由是否从已知后台路径前缀开始

使用域名绑定和路由处理后的 info_app.route，而不是仅判断浏览器中的原始地址；
以完整路径段为边界匹配，供前端适配脚本识别后台域名下省略前缀的操作链接。
该结果只描述页面上下文，不表示当前访问者具有管理员身份。

参数说明：无显式参数。

```text
public static function administrativeContext();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 当前路由是否位于已知后台入口

**上下文变量**：array $info_app 当前应用信息，使用 route 字段

调用形式：

```php
$result = mySecurity::administrativeContext();
```

定位：`lib/mySecurity.class.php:270`。

### mySecurity::mutates

功能说明：识别 URL 是否对应已知的内置状态修改操作

支持普通路径及 index.php 查询字符串路由，并解码查询字符串后检查操作参数。
退出参数 out 和末尾 logout 不限于后台路径；后台路径内另识别删除、启停、
卸载、解锁、整理、排序、设定、打包、保存、清缓存及未记录附件删除等动作。
插件管理器的更新/打包/上传及badReq的屏蔽、解除、删除、重置也必须使用POST。
此处仅识别内置 URL 约定，不执行修改，也不能自动发现第三方代码中的 GET 副作用。

```text
public static function mutates($url);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$url` | string；待检查的绝对 URL 或带可选查询字符串的路径 |

**返回值**：bool 是否命中已知状态修改规则；无法解析或未命中时返回 false

调用形式：

```php
$result = mySecurity::mutates($url);
```

定位：`lib/mySecurity.class.php:289`。

### mySecurity::guard

功能说明：在业务处理前执行请求方法、来源和会话 CSRF 令牌检查

同时检查原始请求地址和路由改写后的地址：已知状态修改操作只接受 POST，
其他方法返回 405、Allow: POST 及 JSON 错误后立即结束请求。
其余 GET、HEAD、OPTIONS 请求直接放行；其他方法必须同时通过来源和令牌校验。
令牌优先取 X-CSRF-Token 请求头，否则取 _ms_csrf 表单字段；失败返回 403
及禁止缓存的 JSON 错误并结束请求。成功后移除 POST/REQUEST 中的保留字段，
防止旧业务把令牌作为普通数据入库。放行后仍需执行登录及业务权限检查。

参数说明：无显式参数。

```text
public static function guard();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 检查通过时返回；拒绝请求时输出响应并 exit

**上下文变量**：array $info_app 当前应用信息，使用 route 字段兼容绑定域名的路径改写

调用形式：

```php
$result = mySecurity::guard();
```

定位：`lib/mySecurity.class.php:324`。

### mySecurity::respond

功能说明：向客户端提供当前应用会话的 CSRF 令牌，并结束请求

仅接受 GET，其他方法返回 405 和 Allow: GET。来源检查失败时返回 403 JSON；
成功时输出 {"token":"当前会话令牌"}，并发送 private, no-store 及
Vary: Cookie, Origin 响应头。本方法不自行设置跨域允许头，API 场景由外层
框架处理 CORS；客户端应保留同一目标应用的会话 Cookie 用于后续写请求。

参数说明：无显式参数。

```text
public static function respond();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 始终结束请求，成功时输出令牌 JSON，失败时输出对应错误响应

**异常与失败**：Exception 生成令牌时无法取得安全随机数

调用形式：

```php
$result = mySecurity::respond();
```

定位：`lib/mySecurity.class.php:357`。

### mySecurity::decorate

功能说明：为已渲染的完整 HTML 页面注入会话令牌和前端安全适配脚本

非字符串或不含 <head 的输入原样返回。处理时设置 private, no-store，跳过
script/style/textarea 内容及 HTML 注释，为普通 POST 表单追加隐藏令牌；
显式指向未允许来源或非 HTTP(S) 协议的表单不注入令牌。
在首个 head 标签之后注入 window.msSecurity 配置和带内容版本号的 security.js，
由脚本适配动态表单、Ajax 及旧式后台操作链接。
必须在页面缓存读取或模板渲染之后调用，避免把会话令牌存入共享缓存；输入应为
框架生成的可信页面，本方法不是 HTML 净化器或浏览器级的完整 HTML 解析器。

```text
public static function decorate($html);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$html` | mixed；待处理的完整 HTML；非字符串或无 head 的输入不作处理 |

**返回值**：mixed 注入后的 HTML 字符串，或不符合处理条件时的原始输入

**异常与失败**：Exception 生成令牌时无法取得安全随机数

调用形式：

```php
$result = mySecurity::decorate($html);
```

定位：`lib/mySecurity.class.php:379`。
