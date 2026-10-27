# 跨域访问

## 本篇目标与调用场景

HTTP分发仅调用已登记处理器。接口提供启用、允许方法、跨域、仅限后台四项设置；参数按处理器签名的位置传入。

## 7. CORS、CSRF与执行边界

路由命中后，先准备选定接口的CORS、正文预算及总开关检查。合法OPTIONS返回204，不实例化应用、不启动会话或调用业务；指定core时仍可能按原流程加载核心类文件，既有优先脚本也仍早于此阶段。

实际调用继续应用preload/start、会话、CSRF和登录恢复，再执行接口设置和回调。逐接口限制不能替代应用业务授权，也不能消除启动逻辑中的副作用。预检通过不代表接口可用或已获授权。

API跨域关闭时只接受当前来源；开启时为当前已登记接口接纳本次合法Origin，并返回明确的Access-Control-Allow-Origin及凭据响应头。CORS与该接口的CSRF来源使用同一判定，不扩大其他接口或普通后台页面的来源。普通页面绑定域名、Cookie域及web.cors_origins规则保留。

跨域前端需携带`credentials: 'include'`并取得相同目标应用上下文的CSRF令牌；使用core=CMS等上下文时，令牌获取也应使用相同core。还须满足浏览器SameSite及第三方Cookie规则。CORS与CSRF均不等于登录凭据。

新拒绝响应为固定JSON：api_disabled（503/404）、origin_denied（403）、method_denied（405）、admin_required（403）、body_limit（400/413）、length_required（411）。HEAD错误无正文。未知接口和业务异常仍沿用原框架行为，部分旧Module is Missing响应可能仍为200。

## 主要方法：参数、返回和失败约定

### myApi::httpOrigins

功能说明：按选定接口的跨域开关返回当前合法Origin，供CORS和CSRF共同使用。

```text
public static function httpOrigins($path, $config = null, $apis = null);
```

| 参数说明 |
| --- |
| string $path API前缀之后的路径 |
| array\|null $config 已验证配置，null读取当前配置（默认：null） |
| array\|null $apis API注册表，null使用全局路由（默认：null） |

**返回**：array 当前请求的合法来源；跨域关闭、接口未登记或来源非法时为空数组

### myApi::httpCors

功能说明：准备选定接口的CORS、统一正文预算和总开关；预检早于应用及会话启动。

```text
public static function httpCors($config = null, $path = '', $apis = null);
```

| 参数说明 |
| --- |
| array\|null $config 已验证配置，null时读取（默认：null） |
| string $path API前缀之后的捕获路径（默认：''） |
| array\|null $apis 当前API注册表，null使用全局路由（默认：null） |

**返回**：void 普通请求继续；预检204、来源403、关闭503及正文拒绝后结束

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
