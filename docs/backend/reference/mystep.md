# myStep：框架请求控制

## 职责与调用位置

index.php仅调用init；应用使用自己的同名子类。API、文件、跳转等分支可能直接退出，不一定执行页面end。

源码：`lib/myStep.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
class Hello extends myStep {}
// 在框架已完成当前应用初始化的模块中：
$url = myStep::setURL('Hello/detail');
$url .= (strpos($url, '?') === false ? '?' : '&').http_build_query(['id'=>7]);
$local = $tpl_setting; $local['name'] = 'detail';
$template = new myTemplate($local, false);
$template->assign('detail_url', $url);
$content = $mystep->render($template);
```

## 声明与类职责

### myStep

功能说明：协调配置、认证、实例、路由、应用/插件加载、资源输出与生命周期。

使用说明：
  1. 网站index.php定义路径常量并调用myStep::init；应用通常继承本类。
  2. 路由/API/模块/自定义函数分支使用当前应用上下文及权限检查。
  3. 清理数据缓存使用clearDataCache保留活动锁；详细请求流程见docs/framework/execution.md。

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
public static
        $variant_alias = [],
        $url_prefix = '',
        $shown = false,
        $cache_js = '',
        $cache_css = '',
        $func_ignore = ['captcha', 'error', 'ms_language', 'ms_setting', 'sitemap', 'atom', 'rss'];
```

```text
public $setting;
```

```text
protected
        $editor_plugin = [],
        $editor_btn = [],
        $mem_start = 0,
        $time_start = 0,
        $time_css = 0,
        $time_js = 0;
```

```text
protected static $active_plugins = null, $known_plugins = [], $plugin_signature = null;
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myStep::__construct` | 构造函数 |
| `myStep::getInstance` | 取得类实例 |
| `myStep::start` | 框架执行入口，初始化所有变量 |
| `myStep::ms_login` | 框架登录验证 |
| `myStep::rememberAdmin` | 设置管理员签名登录Cookie，非正有效期删除该Cookie |
| `myStep::auth_code` | 生成认证码，可扩展，可在添加任意个salt参数 |
| `myStep::setInstance` | 声明重要实例 |
| `myStep::clearDataCache` | 清空框架数据文件及当前缓存后端，供后台和更新器共同使用。 |
| `myStep::show` | 通过模板类显示页面 |
| `myStep::render` | 通过模板类输出页面内容 |
| `myStep::checkCache` | 检查模版缓存并输出 |
| `myStep::end` | 框架终止，销毁相关变量 |
| `myStep::gzOut` | 页面压缩 |
| `myStep::addCSS` | 添加页面CSS文件 |
| `myStep::CSS` | 生成整合CSS文件 |
| `myStep::addJS` | 添加页面脚本文件 |
| `myStep::JS` | 整合页面脚本文件 |
| `myStep::editorSetPlugin` | 富文本编辑器功能添加 |
| `myStep::editorGetPlugin` | 生成编辑器插件缓存脚本 |
| `myStep::info` | 信息提示，需先声明mystep类 |
| `myStep::captcha` | 生成验证码 |
| `myStep::language` | JS语言包接口 |
| `myStep::setting` | JS设置信息接口 |
| `myStep::apiOriginAllowed` | 按当前来源、绑定域名、Cookie域及额外白名单检查API来源 |
| `myStep::api` | 框架API执行接口 |
| `myStep::module` | 框架模块调用接口 |
| `myStep::appFunc` | 应用自定义功能接口（sitemap, atom, rss等） |
| `myStep::upload` | 文件上传 |
| `myStep::download` | 文件下载 |
| `myStep::remove_ul` | 删除上传文件 |
| `myStep::header` | 处理头信息 |
| `myStep::setURL` | 链接处理 |
| `myStep::redirect` | 链接跳转 |
| `myStep::init` | 框架变量初始化 |
| `myStep::activePlugins` | 按注册表 order 从大到小收集活动插件，内容未变时复用结果 |
| `myStep::refreshRoutes` | 内容指纹监测路由源、应用发现结果及配置，变更时加锁重建，否则复用注册表 |
| `myStep::go` | 执行框架 |
| `myStep::setPara` | 根据实际路由应用确定公共端点会话策略，并初始化应用模块参数 |
| `myStep::checkBind` | 检测域名绑定 |
| `myStep::checkClose` | 检测是否网站关闭 |
| `myStep::getModule` | 应用模块调用 |
| `myStep::vendor` | 调用第三方组件 |
| `myStep::segment` | 字符串分词 |
| `myStep::checkPower` | 权限检测接口 |
| `myStep::getCore` | 获取指定核心类名称 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myStep::__construct

功能说明：构造函数

参数说明：无显式参数。

```text
public function __construct();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 初始化或释放资源，无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myStep.class.php:153`。

### myStep::getInstance

功能说明：取得类实例

```text
public static function getInstance($calledClass = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$calledClass` | string；待创建或获取实例的类名（默认：''） |
| `...$args` | mixed；传给目标类初始化入口的位置参数（位置参数，由func_get_args读取） |

**返回值**：object 按父控制器规则取得并配置别名和错误回调的实例

调用形式：

```php
$result = myStep::getInstance($calledClass);
```

定位：`lib/myStep.class.php:167`。

### myStep::start

功能说明：框架执行入口，初始化所有变量

```text
public function start($charset = 'UTF-8', $set_plugin = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$charset` | string；保留参数；实际使用当前setting->gen->charset（默认：'UTF-8'） |
| `$set_plugin` | bool；保留参数；执行时依据当前路由及func_ignore重新计算是否运行插件（默认：true） |

**返回值**：void 初始化当前应用上下文并进入控制器启动流程

**异常与失败**：RuntimeException 需要认证的请求无法启动会话存储；Throwable 自定义解析器、初始化/反射目标或受信执行文件产生的异常继续传递

调用形式：

```php
// $instance为已初始化的myStep实例。
$result = $instance->start($charset, $set_plugin);
```

定位：`lib/myStep.class.php:186`。

### myStep::ms_login

功能说明：框架登录验证

```text
public function ms_login($usr='', $pwd='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$usr` | string；登录用户名（默认：''） |
| `$pwd` | string；账号密码，空串按调用入口的支持约定处理（默认：''） |

**返回值**：bool 凭据/当前会话通过框架管理员认证为true，否则为false

调用形式：

```php
// $instance为已初始化的myStep实例。
$result = $instance->ms_login($usr, $pwd);
```

定位：`lib/myStep.class.php:282`。

### myStep::rememberAdmin

功能说明：设置管理员签名登录Cookie，非正有效期删除该Cookie

```text
public function rememberAdmin($ttl);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$ttl` | int；有效期秒数，非正值按入口约定删除或取消 |

**返回值**：bool 记住登录Cookie设置或删除是否成功

调用形式：

```php
// $instance为已初始化的myStep实例。
$result = $instance->rememberAdmin($ttl);
```

定位：`lib/myStep.class.php:321`。

### myStep::auth_code

功能说明：生成认证码，可扩展，可在添加任意个salt参数

```text
public static function auth_code();
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `...$args` | scalar；参与SHA-256摘要的盐值片段，按顺序以竖线连接（位置参数，由func_get_args读取） |

**返回值**：string 按盐片段顺序生成的64位SHA-256十六进制摘要

调用形式：

```php
$result = myStep::auth_code();
```

定位：`lib/myStep.class.php:332`。

### myStep::setInstance

功能说明：按驱动签名准备缓存与数据库实例

参数说明：无显式参数。

```text
public function setInstance();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的myStep实例。
$result = $instance->setInstance();
```

定位：`lib/myStep.class.php:346`。

SQLite 初始化只接收数据库文件路径或 `:memory:`，不传入 MySQL 的用户名、密码和字符集。配置中的相对文件路径由 `myPath::absolute()` 按网站根解析，不依赖服务器工作目录。自动连接仍受当前应用的 `db.auto` 配置控制；已有数据库实例继续复用。

### myStep::clearDataCache

功能说明：清空框架数据文件及当前缓存后端，供后台和更新器共同使用。

使用驱动clear保留活动锁；SQL/Memcached后端也执行显式清空。
同目录/同表/同Memcached服务的其他namespace可能受影响，并发请求可重建数据。

参数说明：无显式参数。

```text
public static function clearDataCache();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 本地数据目录和当前后端均清空成功为true

**异常与失败**：BadMethodCallException 自定义后端未提供显式clear能力；Throwable 驱动异常原样传递，调用方不应提示清理成功

调用形式：

```php
$result = myStep::clearDataCache();
```

定位：`lib/myStep.class.php:390`。

### myStep::show

功能说明：通过模板类显示页面

输出约定：填充框架变量与资源标签后调用父类输出，并标记已展示；不自动调用end。仅需字符串时使用render。

```text
public function show(myTemplate $tpl, $append_pare = []);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$tpl` | myTemplate；已经初始化的模板实例 |
| `$append_pare` | array；在渲染时补充的模板变量（默认：[]） |

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的myStep实例。
$result = $instance->show($tpl, $append_pare);
```

定位：`lib/myStep.class.php:408`。

### myStep::render

功能说明：通过模板类输出页面内容

```text
public function render(myTemplate $tpl, $append_pare = []);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$tpl` | myTemplate；已经初始化的模板实例 |
| `$append_pare` | array；在渲染时补充的模板变量（默认：[]） |

**返回值**：string 模板最终输出文本，附加参数先按当前方法规则赋值

调用形式：

```php
// $instance为已初始化的myStep实例。
$result = $instance->render($tpl, $append_pare);
```

定位：`lib/myStep.class.php:455`。

### myStep::checkCache

功能说明：检查模版缓存并输出

命中页面缓存时输出当前会话装饰后的HTML并调用end终止；未命中时继续返回业务流程。

```text
public function checkCache(myTemplate $tpl);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$tpl` | myTemplate；已经初始化的模板实例 |

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的myStep实例。
$result = $instance->checkCache($tpl);
```

定位：`lib/myStep.class.php:489`。

### myStep::end

功能说明：框架终止，销毁相关变量

参数说明：无显式参数。

生命周期：关闭全局数据库、记录耗时/内存、按需压缩输出并exit；调用后不会回到业务代码。

```text
public function end();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的myStep实例。
$result = $instance->end();
```

定位：`lib/myStep.class.php:506`。

### myStep::gzOut

功能说明：页面压缩

```text
public function gzOut($level = 3, $query = 0, $time = 0, $mem = 0);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$level` | int；gzip压缩级别，发送时限制到0至9；负数跳过压缩（默认：3） |
| `$query` | int；本次请求的数据库查询次数（默认：0） |
| `$time` | int\|float；本次请求耗时，单位毫秒（默认：0） |
| `$mem` | int；内存使用量，单位字节（默认：0） |

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的myStep实例。
$result = $instance->gzOut($level, $query, $time, $mem);
```

定位：`lib/myStep.class.php:526`。

### myStep::addCSS

功能说明：添加页面CSS文件

```text
public function addCSS($file, $code=false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；目标文件路径 |
| `$code` | bool；true把file视为CSS源码，false视为CSS文件路径（默认：false） |

**返回值**：$this 登记有效CSS文件或直接源码后返回当前实例

调用形式：

```php
// $instance为已初始化的myStep实例。
$result = $instance->addCSS($file, $code);
```

定位：`lib/myStep.class.php:566`。

### myStep::CSS

功能说明：生成整合CSS文件

```text
public function CSS($cache='', $dummy_1='', $dummy_2='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$cache` | string；压缩结果缓存文件路径，空串不写文件（默认：''） |
| `$dummy_1` | mixed；保留参数，本实现不使用（默认：''） |
| `$dummy_2` | mixed；保留参数，本实现不使用（默认：''） |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myStep实例。
$result = $instance->CSS($cache, $dummy_1, $dummy_2);
```

定位：`lib/myStep.class.php:587`。

### myStep::addJS

功能说明：添加页面脚本文件

```text
public function addJS($file, $code=false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；目标文件路径 |
| `$code` | bool；true把file视为JS源码，false视为JS文件路径（默认：false） |

**返回值**：$this 登记有效JavaScript文件或直接源码后返回当前实例

调用形式：

```php
// $instance为已初始化的myStep实例。
$result = $instance->addJS($file, $code);
```

定位：`lib/myStep.class.php:601`。

### myStep::JS

功能说明：整合页面脚本文件

```text
public function JS($cache='', $dummy_1='', $dummy_2='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$cache` | string；压缩结果缓存文件路径，空串不写文件（默认：''） |
| `$dummy_1` | mixed；保留参数，本实现不使用（默认：''） |
| `$dummy_2` | mixed；保留参数，本实现不使用（默认：''） |

**返回值**：$this 生成/复用整合脚本缓存后返回当前实例，不直接返回脚本文本

调用形式：

```php
// $instance为已初始化的myStep实例。
$result = $instance->JS($cache, $dummy_1, $dummy_2);
```

定位：`lib/myStep.class.php:622`。

### myStep::editorSetPlugin

功能说明：富文本编辑器功能添加

```text
public function editorSetPlugin($code, $btn='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$code` | string；注册编辑器插件时插入init函数的受信JavaScript源码 |
| `$btn` | string；编辑器工具栏按钮配置（默认：''） |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myStep实例。
$result = $instance->editorSetPlugin($code, $btn);
```

定位：`lib/myStep.class.php:636`。

### myStep::editorGetPlugin

功能说明：生成编辑器插件缓存脚本

参数说明：无显式参数。

```text
public function editorGetPlugin();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myStep实例。
$result = $instance->editorGetPlugin();
```

定位：`lib/myStep.class.php:650`。

### myStep::info

功能说明：信息提示，需先声明mystep类

输出约定：清理当前缓冲并输出提示页，调用end后exit；不应把此入口当作可返回的错误字符串函数。

```text
public static function info($msg, $url = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$msg` | string\|array；语言键、消息文本或错误结构 |
| `$url` | string；显示消息后的跳转目标，空串沿用当前策略（默认：''） |

**返回值**：void 无返回值。

调用形式：

```php
$result = myStep::info($msg, $url);
```

定位：`lib/myStep.class.php:683`。

### myStep::captcha

功能说明：生成验证码

输出约定：需要Referer及Cookie才继续，写入当前会话captcha并输出图片；条件不满足时静默返回。

```text
public static function captcha($len = 4, $scope = 3);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$len` | int；生成验证码的字符数（默认：4） |
| `$scope` | int；验证码字符池/生成范围选项（默认：3） |

**返回值**：void 无返回值。

**相关入口**：myString::rndKey() 字符池scope的取值约定

调用形式：

```php
$result = myStep::captcha($len, $scope);
```

定位：`lib/myStep.class.php:732`。

### myStep::language

功能说明：JS语言包接口

```text
public static function language($app_name, $type='default');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$app_name` | string；已安装应用的名称 |
| `$type` | string；语言包名，默认由调用入口决定（默认：'default'） |

**返回值**：void 无返回值。

调用形式：

```php
$result = myStep::language($app_name, $type);
```

定位：`lib/myStep.class.php:747`。

### myStep::setting

功能说明：JS设置信息接口

上传限制先按INI单位解析为字节，忽略表示无限制的非正值；max_size继续以MB提供给旧组件，
max_size_bytes为精确字节数（0表示PHP这两项均无限制）。均无限制时旧组件保留32MB默认值。
实际上传还需通过服务端应用配置、文件类型和权限检查。

```text
public static function setting($app_name);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$app_name` | string；已安装应用的名称 |

**返回值**：void 无返回值。

调用形式：

```php
$result = myStep::setting($app_name);
```

定位：`lib/myStep.class.php:787`。

### myStep::apiOriginAllowed

功能说明：按当前来源、绑定域名、Cookie域及额外白名单检查API来源

```text
public static function apiOriginAllowed($origin, $current, array $domains, $cookie_domain = '', array $extra = []);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$origin` | string；待检查的Origin请求头，必须为合法完整来源地址 |
| `$current` | string；当前请求的完整来源地址（协议、主机和端口） |
| `$domains` | array；框架配置的域名绑定规则 |
| `$cookie_domain` | string；配置的Cookie域，参与同站来源判定（默认：''） |
| `$extra` | array；额外明确允许的完整来源列表（默认：[]） |

**返回值**：bool 来源符合允许规则为true，否则为false

调用形式：

```php
$result = myStep::apiOriginAllowed($origin, $current, $domains, $cookie_domain, $extra);
```

定位：`lib/myStep.class.php:833`。

### myStep::api

功能说明：框架API执行接口

已登记插件API按需加载标准class.php，不依赖当前宿主应用绑定；不额外执行插件index.php。
活动插件API仍由路由注册表筛选；独立外部API映射的既有可调用处理器继续支持。

未指定输出格式的插件接口默认使用JSON，避免无尾段路径被误判为非法格式。

```text
public static function api($path);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$path` | string；包含应用/接口/输出格式的API路由路径 |

**返回值**：void 无返回值。

**异常与失败**：InvalidArgumentException 插件API主类路径或标识无效；Throwable 插件主类加载或接口执行异常继续传递

调用形式：

```php
$result = myStep::api($path);
```

定位：`lib/myStep.class.php:861`。

### myStep::module

功能说明：框架模块调用接口

```text
public static function module($path, $dummy = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$path` | string；应用模块调用路径 |
| `$dummy` | mixed；保留参数，本实现不使用（默认：''） |

**返回值**：void 无返回值。

调用形式：

```php
$result = myStep::module($path, $dummy);
```

定位：`lib/myStep.class.php:933`。

### myStep::appFunc

功能说明：应用自定义功能接口（sitemap, atom, rss等）

```text
public static function appFunc($func);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$func` | string；当前核心应用下自定义PHP入口名，例如sitemap；会移除非单词字符 |

**返回值**：void 无返回值。

调用形式：

```php
$result = myStep::appFunc($func);
```

定位：`lib/myStep.class.php:971`。

### myStep::upload

功能说明：文件上传

参数说明：无显式参数。

```text
public static function upload();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：array 各上传项的结果，非POST请求返回错误结构

调用形式：

```php
$result = myStep::upload();
```

定位：`lib/myStep.class.php:993`。

### myStep::download

功能说明：文件下载

```text
public static function download($idx='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$idx` | string；附件唯一标识，允许完整存储名或框架标识（默认：''） |

**返回值**：void 无返回值。

调用形式：

```php
$result = myStep::download($idx);
```

定位：`lib/myStep.class.php:1029`。

### myStep::remove_ul

功能说明：删除上传文件

```text
public static function remove_ul($idx='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$idx` | string；附件唯一标识，允许完整存储名或框架标识（默认：''） |

**返回值**：array 含error/message的删除结果；操作受请求方法、标识及路径验证约束

调用形式：

```php
$result = myStep::remove_ul($idx);
```

定位：`lib/myStep.class.php:1048`。

### myStep::header

功能说明：处理头信息

```text
public static function header($idx, $para = '', $exit = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$idx` | string\|int；状态码或已支持的响应头类别 |
| `$para` | string\|array；响应头模板的替换值；数组用于refresh等多值模板（默认：''） |
| `$exit` | bool；是否在发出响应头后结束请求（默认：true） |

**返回值**：void 发送状态/响应头或进入错误提示流程；按exit决定是否结束请求

调用形式：

```php
$result = myStep::header($idx, $para, $exit);
```

定位：`lib/myStep.class.php:1068`。

### myStep::setURL

功能说明：链接处理

```text
public static function setURL($url='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$url` | string；应用内部路径或外部完整URL，按当前路由模式生成链接（默认：''） |

**返回值**：string 按当前绑定/路由策略转换的URL文本；空路径返回可继续拼接功能路径的前缀

调用形式：

```php
$result = myStep::setURL($url);
```

定位：`lib/myStep.class.php:1093`。

### myStep::redirect

功能说明：链接跳转

```text
public static function redirect($url = '', $code = '302');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$url` | string；跳转目标；空串回到Referer或默认地址（默认：''） |
| `$code` | string\|int；重定向HTTP状态码，默认302（默认：'302'） |

**返回值**：void 无返回值。

调用形式：

```php
$result = myStep::redirect($url, $code);
```

定位：`lib/myStep.class.php:1120`。

### myStep::init

功能说明：框架变量初始化

参数说明：无显式参数。

Web请求在类加载注册后、缓存清理和应用/会话启动前检查已解析输入预算；
PHP解析器已可能处理请求，此检查不能替代运行时补丁及Web服务器限制。

```text
public static function init();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 设置核心路径、配置、自动加载及请求上下文；超预算请求返回400/413并结束

**异常与失败**：InvalidArgumentException 受信的MYSTEP_REQUEST_LIMITS配置无效；RuntimeException 无法完整发布类索引

调用形式：

```php
$result = myStep::init();
```

定位：`lib/myStep.class.php:1146`。

### myStep::activePlugins

功能说明：按注册表 order 从大到小收集活动插件，内容未变时复用结果

参数说明：无显式参数。

相同 order 保持注册表原顺序；文件内容变化后在当前请求内重新读取。

```text
public static function activePlugins();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：array 按执行顺序排列的插件标识到 true 的字典

调用形式：

```php
$result = myStep::activePlugins();
```

定位：`lib/myStep.class.php:1192`。

### myStep::refreshRoutes

功能说明：内容指纹监测路由源、应用发现结果及配置，变更时加锁重建，否则复用注册表

参数说明：无显式参数。

```text
public static function refreshRoutes();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 无返回值。

**异常与失败**：UnexpectedValueException 显式插件路由映射或条目无效，原编译表保持不变；RuntimeException 路由注册表无法加锁或完整写入；Throwable 自定义解析器、初始化/反射目标或受信执行文件产生的异常继续传递

调用形式：

```php
$result = myStep::refreshRoutes();
```

定位：`lib/myStep.class.php:1227`。

### myStep::go

功能说明：执行框架

加载配置、优先脚本及路由并分流请求。服务器环境变量
MYSTEP_PRESERVE_MEMORY_LIMIT=1时保留启动期memory_limit，不写入gen.memory；
该开关本身不改变内存上限，外部应用仍须遵循服务器锁定的管理策略。
全局api分组由框架配置补齐；专属域名在当前请求内提供API虚拟前缀，不改绑定文件。

```text
public static function go();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 无返回值。

**异常与失败**：RuntimeException API专属域名与现有应用绑定冲突

参数说明：无显式参数。

调用形式：

```php
$result = myStep::go();
```

定位：`lib/myStep.class.php:1315`。

### myStep::setPara

功能说明：根据实际路由应用确定公共端点会话策略，并初始化应用模块参数

参数说明：无显式参数。

```text
public static function setPara();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 无返回值。

调用形式：

```php
$result = myStep::setPara();
```

定位：`lib/myStep.class.php:1490`。

### myStep::checkBind

功能说明：检测域名绑定

```text
public static function checkBind($pattern);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$pattern` | string；待验证的域名绑定表达式或规则 |

**返回值**：void 无返回值。

调用形式：

```php
$result = myStep::checkBind($pattern);
```

定位：`lib/myStep.class.php:1560`。

### myStep::checkClose

功能说明：检测是否网站关闭

参数说明：无显式参数。

```text
public static function checkClose();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 无返回值。

调用形式：

```php
$result = myStep::checkClose();
```

定位：`lib/myStep.class.php:1581`。

### myStep::getModule

功能说明：应用模块调用

```text
public static function getModule($m);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$m` | string；需要分派的模块路径/名称 |

**返回值**：void 无返回值。

调用形式：

```php
$result = myStep::getModule($m);
```

定位：`lib/myStep.class.php:1601`。

### myStep::vendor

功能说明：调用第三方组件

```text
public static function vendor();
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `...$args` | mixed；首项为组件名或dir/file/class/namespace数组，其余为构造参数（位置参数，由func_get_args读取） |

**返回值**：object 按组件声明创建的实例；需附加参数时再调用构造/init

**异常与失败**：ReflectionException 目标反射或底层调用产生的异常继续传递

调用形式：

```php
$result = myStep::vendor();
```

定位：`lib/myStep.class.php:1637`。

### myStep::segment

功能说明：字符串分词

```text
public static function segment($str='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$str` | string；提交给分词服务的文本；空串不请求服务（默认：''） |

**返回值**：string 分词服务返回文本经换行整理后的内容；空输入返回空串，不返回JSON结构

调用形式：

```php
$result = myStep::segment($str);
```

定位：`lib/myStep.class.php:1675`。

### myStep::checkPower

功能说明：权限检测接口

```text
public static function checkPower($idx);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$idx` | string；待检查的能力名称，例如upload/download/remove_ul |

**返回值**：bool 当前请求对指定能力检查通过为true，否则为false；子类继承或调用parent时使用框架默认检查，避免再次委派

调用形式：

```php
$result = myStep::checkPower($idx);
```

定位：`lib/myStep.class.php:1695`。

### myStep::getCore

功能说明：获取指定核心类名称

参数说明：无显式参数。

```text
public static function getCore();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string 当前核心应用类/目录名称，按配置及框架回退规则选择

调用形式：

```php
$result = myStep::getCore();
```

定位：`lib/myStep.class.php:1722`。
