# 全局变量

## 本篇目标与调用场景

明确全局变量、对象引用与局部模块变量的来源；不要在配置合并或应用选择之前使用后续上下文。

## 主要变量

| 变量 | 用途与约束 |
| --- | --- |
| `$ms_setting` | 当前合并配置；使用value做无副作用读取 |
| `$info_app` | 当前应用、路径及路由；命中规则可能更新 |
| `$mystep` | 当前应用的同名核心子类 |
| `$tpl_setting/$tpl_cache` | 模板实例及页面缓存参数 |
| `$tpl/$tpl_sub/$content` | 页面分支显式建立的主模板、子模板和主体 |
| `$db/$cache/$router` | 当前服务引用，仍需检查连接及能力 |

## 2. 当前请求的主要变量

| 变量 | 内容 | 可用条件与注意点 |
| --- | --- | --- |
| `$ms_setting` | 当前合并后的 `myConfig` | 全局、应用及入口覆盖按当前执行分支形成；不是永不变化的全局默认值 |
| `$info_app` | 当前应用、路由、路径等信息 | 路由选择可能更新；不同入口的 `path` 切分位置不能机械照搬 |
| `$router` | 当前 `myRouter` | 重用已加载的规则及 API 表，避免在每个模块重新扫描 |
| `$mystep` | 当前应用核心对象 | 普通应用应为自己的同名 `myStep` 子类 |
| `$db`、`$cache` | 当前控制器准备的服务引用 | 由启动条件及配置决定；不能仅凭变量名假定已连接 |
| `$tpl_setting`、`$tpl_cache` | 模板配置与页面缓存选项 | 创建模板时可复制再局部修改 |
| `$tpl`、`$tpl_sub`、`$content` | 页面分支的主／子模板和内容 | 由入口／模块装配，不是每个 API 都存在 |
| `$domain` | 域名映射 | 域名调度配置，不是通用权限列表 |

在 PHP 函数中访问全局变量仍需显式 `global` 或参数注入；应用辅助方法优先接收所需对象和数据。不要把完整 `$ms_setting`、数据库对象、控制器属性或 Session 打印到公开页面，它们可能包含口令、令牌及内部路径。

```php
$title = $ms_setting->value('web.title', 'Hello');
$localTemplate = $tpl_setting;
$localTemplate['name'] = 'detail';
```

`value()` 提供不创建配置节点的读取。直接链式赋值可以建立分组，适合明确写入；只想检查不存在的设置时不要依赖链式自动创建。

## 3. 请求值与保留参数

`myReq` 提供 GET、POST、Cookie、Session、服务器环境及全量输入访问，但不是统一的业务验证器。接口全量读取会进行 HTML 转义，原生 `$_GET`／`$_POST` 则是原始解析值。选择一种数据约定并保持一致，不要反复编码或把 HTML 编码当 SQL 过滤。

```php
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1) {
    throw new InvalidArgumentException('编号无效。');
}
```

`core` 是部分框架端点选择应用上下文的控制参数；`_ms_csrf`、`X-CSRF-Token` 用于会话 CSRF。API 分发移除已消费的 `core`，安全校验移除表单保留令牌，业务不可把它们当普通字段写入数据表。API 最终按位置顺序传参，不会把查询字段名自动绑定为函数形参名。

## 每个主要上下文的读取示例

以下片段位于应用模块，使用当前请求已有对象，不创建第二个控制器。`$tpl_sub`由应用入口建立；`$db`、`$cache`按配置是否初始化而定。

```php
$appName = $info_app['app'];                 // 例如Hello，与核心类名对应。
$segments = $info_app['path'] ?? [];         // 当前模块路径段，仍需白名单。
$parameters = $info_app['para'] ?? [];       // 路由解析出的查询参数，不是已验证业务值。
$routePath = $info_app['route'] ?? '';       // 路由承接入口使用，部分分支不存在。
$siteTitle = $ms_setting->value('web.title', 'Hello');
$localTemplate = $tpl_setting;
$localTemplate['name'] = 'detail';          // 局部选择，不修改全局模板设置。
$localPageCache = $tpl_cache;               // 页面缓存选项；用户内容需独立vary。
$tpl_sub->assign('site_title', $siteTitle);
$content = $mystep->render($tpl_sub);         // 返回子页面内容，交给主模板main。
```

`$router`是当前已注册规则的对象，普通模块只读取所需结果，不重复check整个请求。`$db->supports(interface_db_prepared::class)`检查能力声明，不证明服务器在线；真实查询错误应正常处理。`$cache->fetch($key,$hit)`通过$hit判断是否命中，不能用if($value)判断。`$domain`是主机到应用／路由绑定表，不作为认证或CORS策略。

函数内需要全局资源时优先明确传入：

```php
function helloTitle(myConfig $config) {
    return $config->value('web.title', 'Hello');
}
$title = helloTitle($ms_setting);
```

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
