# 会话CSRF、来源、方法与后台判定

修订日期：2026-10-23。适用于当前框架。

## 本篇目标与调用场景

会话与凭据是框架辅助能力，业务仍独立校验身份、数据归属及操作权限。代码预算不替代PHP解析前的补丁与服务器限制。

## 会话 CSRF、来源和操作方法

`myStep::start()` 在会话启动后、登录恢复和插件启动前执行 `mySecurity::guard()`。

- POST/PUT/PATCH/DELETE 等非安全方法需要当前应用会话的令牌；通过 `X-CSRF-Token` 或表单 `_ms_csrf` 提交。
- 若请求提供 Origin，必须属于当前站点、绑定域名或显式允许来源；无 Origin 的 cross-site 浏览器请求拒绝。
- 已识别的后台删除、启停、卸载、解锁、整理、排序、设定、插件打包、保存动作、清缓存和退出动作只接受 POST；GET/HEAD/OPTIONS 等返回 405。
- 令牌校验成功后从 POST/REQUEST 中移除保留字段，避免旧式“所有字段写入数据库”逻辑误处理。
- 权限校验仍由具体业务处理器执行。令牌有效不代表有权上传、删除或管理。

页面在模板缓存之后注入当前会话的令牌与脚本，并发送 `Cache-Control: private, no-store`。内置 POST 表单提供隐藏字段；`static/js/security.js` 处理动态表单、jQuery、XHR、fetch，以及旧式后台操作链接。令牌不会写入合并脚本或共享 HTML 缓存。渲染缓存命中出口同样注入；直接输出 HTML 的自定义处理器应自行集成。

第三方程序需要先用相同 Cookie 会话 GET `/ms_csrf`，再在写请求中携带返回的 `token`。还可通过框架 API `csrf` 获取，例如主域名 `/api/myStep/csrf/json`。指定其他核心应用时，由该应用的接口入口和 core 参数选择目标上下文。使用查询路由或子目录时按框架 URL 生成规则转换入口。

**框架与 CMS 使用各自会话，跨应用时应向目标应用获取令牌。** 不能拿框架令牌直接发 CMS 写请求。令牌失效返回 403，刷新页面或重新获取目标应用令牌后重试。带会话的跨域请求仍需满足原有 CORS 配置。

自定义应用/插件不应使用 GET 改变状态。内置动作识别不是第三方所有 GET 副作用的自动审计；外部回调、无浏览器 API 若需要其他认证协议，应设计独立校验入口，不应整体关闭会话保护。


## 主要方法：参数、返回和失败约定

### mySecurity::token

功能说明：获取当前会话的 CSRF 令牌，缺失或格式异常时重新生成

令牌由 32 字节安全随机数转为 64 位小写十六进制字符串，保存在
$_SESSION['_ms_csrf'] 中。有效令牌在同一会话内复用，不自行设置过期时间；
调用前应已启动目标应用的会话，应用间不得假定共享同一令牌。

参数说明：无显式参数。

```text
public static function token();
```

**返回**：string 当前会话的 64 位 CSRF 令牌

**异常**：Exception 无法取得安全随机数时由 random_bytes() 抛出

### mySecurity::valid

功能说明：校验提交的 CSRF 令牌是否与当前会话中保存的令牌一致

仅接受字符串，并使用 hash_equals() 进行常量时间比较；不会创建或更新令牌。
未保存会话令牌、值类型异常或内容不一致时返回 false。

```text
public static function valid($token);
```

| 参数说明 |
| --- |
| mixed $token 来自请求头或表单的待校验令牌 |

**返回**：bool 是否匹配当前会话中保存的令牌

### mySecurity::originAllowed

功能说明：根据 Origin 和浏览器 Fetch Metadata 判断当前请求来源是否允许

提供 Origin 时，与 origins() 列表进行严格匹配；未提供 Origin 时，只有
Sec-Fetch-Site 明确为 cross-site 才拒绝。两者都缺失时返回 true，后续写请求
仍须由 guard() 校验会话令牌；本方法本身不校验令牌，也不执行权限检查。

参数说明：无显式参数。

```text
public static function originAllowed();
```

**返回**：bool 当前请求的来源信息是否通过检查

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

**返回**：void 检查通过时返回；拒绝请求时输出响应并 exit

**上下文**：array $info_app 当前应用信息，使用 route 字段兼容绑定域名的路径改写

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

| 参数说明 |
| --- |
| mixed $html 待处理的完整 HTML；非字符串或无 head 的输入不作处理 |

**返回**：mixed 注入后的 HTML 字符串，或不符合处理条件时的原始输入

**异常**：Exception 生成令牌时无法取得安全随机数

## 明确调用示例

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

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
