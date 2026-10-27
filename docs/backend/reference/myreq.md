# 请求读取

接口说明：`myReq`。

## 职责与调用位置

格式标记影响转换，原生数组与历史HTML转义读取并不相同。会话由框架启动，应用内不要重复初始化。

源码：`lib/myReq.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1) throw new InvalidArgumentException('编号无效');
$agent = myReq::server('HTTP_USER_AGENT');
myReq::session('last_article', $id);
assert(myReq::session('last_article') === $id);
```

## 声明与类职责

### myReq

功能说明：统一请求过滤、环境读取、可信代理IP判断及会话生命周期处理。

使用说明：
  1. 应用通过get/post/request/cookie/session等入口读取和设置数据。
  2. 过滤格式和特殊键按方法说明使用，返回值可能保留数组或null。
  3. Session处理器选择与Cookie选项应在启动前配置；no_log入口按设计跳过会话。

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
public static
        $func_alias = array(
            'g' => 'get',
            'p' => 'post',
            'f' => 'files',
            'r' => 'request',
            'c' => 'cookie',
            's' => 'session',
            'e' => 'env',
            'svr' => 'server',
            'gl' => 'globals',
        );
```

```text
protected static
        $cookie_opt = array(
            'path' => '',
            'domain' => '',
            'prefix' => ''
        ),
        $session_opt = array(
            'id' => '',
            'expire' => 30,
            'gc' => false,
            'mode' => 'files',
            'trans_sid' => false
        );
```

```text
protected static $trusted_proxies = [];
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myReq::init` | Cookie（路径、域名和前缀）及Session（id、过期时间、回收、传送ID）初始化 |
| `myReq::check` | 检测相关环境变量 |
| `myReq::getValue` | 获取相关相应值 |
| `myReq::get` | 读取_GET数据 |
| `myReq::post` | 读取_POST数据 |
| `myReq::files` | 读取_FILES数据 |
| `myReq::request` | 读取_REQUEST数据 |
| `myReq::server` | 读取_SERVER数据 |
| `myReq::env` | 读取_ENV数据 |
| `myReq::globals` | 读取GLOABLS数据 |
| `myReq::ip` | 返回客户端IP |
| `myReq::setTrustedProxies` | 登记可采信转发头的代理IP列表 |
| `myReq::trustedProxy` | 检查当前REMOTE_ADDR是否精确命中可信代理列表 |
| `myReq::ping` | IP地址连通测试 (IP:PORT) |
| `myReq::pingURL` | 获取某个URL的链接时间，$detail返回相关时间参数（其他参数请自行var_export）如下： |
| `myReq::setCookieOpt` | 设置COOKIE参数 |
| `myReq::setCookie` | 设置COOKIE（带前缀） |
| `myReq::setCookie_nopre` | 设置COOKIE（不带前缀） |
| `myReq::removeCookie` | 删除COOKIE |
| `myReq::cookie` | 读取COOKIE |
| `myReq::setSessionOpt` | 设置SESSION选项；缺失name时使用当前原生名称，不读取未定义键。 |
| `myReq::sessionStart` | 启动SESSION，启用严格ID校验并接入可选validateId/updateTimestamp回调。 |
| `myReq::sessionRestart` | 轮换活动SESSION的ID并保留数据；不做多余的关闭/重读，避免严格模式丢失新ID。 |
| `myReq::sessionEnd` | 清除会话数据并销毁存储，使用原Cookie路径、域和安全属性删除浏览器Cookie。 |
| `myReq::session` | 读取SESSION |
| `myReq::removeSession` | 删除SESSION |
| `myReq::setSessions` | 将当前编码器的会话源串批量合并到活动会话，并成功后轮换ID。 |
| `myReq::sessionId` | 获取SESSION ID |
| `myReq::sessionCheck` | 检测SESSION是否已启动 |
| `myReq::setSessionPath` | 设置SESSION保存路径 |
| `myReq::setSessionName` | 设置SESSION名称 |
| `myReq::sessionEncode` | Session 编码 |
| `myReq::sessionDecode` | Session 解码 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myReq::init

功能说明：Cookie（路径、域名和前缀）及Session（id、过期时间、回收、传送ID）初始化

```text
public static function init($cookie_opt = array(), $session_opt = array());
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$cookie_opt` | array；Cookie前缀、有效期、路径、域及安全属性等默认选项（默认：array()） |
| `$session_opt` | array；会话处理器、Cookie、名称、路径和生命周期等选项（默认：array()） |

**返回值**：void 无返回值。

调用形式：

```php
$result = myReq::init($cookie_opt, $session_opt);
```

定位：`lib/myReq.class.php:116`。

### myReq::check

功能说明：检测相关环境变量

```text
public static function check($idx);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$idx` | string；要检查的请求来源/条件标记，例如post |

**返回值**：int 指定超全局数组/Countable对象的元素数量；不存在或不可计数时为0

调用形式：

```php
$result = myReq::check($idx);
```

定位：`lib/myReq.class.php:131`。

### myReq::getValue

功能说明：获取相关相应值

参数约定：空format对id或以_id结尾的字符串字段采用数值转换；ALL分支除!外使用递归HTML转义，不执行单项回调。GLOBAL分支将值导入$GLOBALS并返回true。

```text
public static function getValue($type = 'get', $para = null, $format = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$type` | string；输入来源：get/post/request/files/cookie/session/server/env等（默认：'get'） |
| `$para` | string\|int\|null；字段名或[A]/[ALL]/[G]/[GLOBAL]特殊键，null读取全部（默认：null） |
| `$format` | string\|callable；!/int/url/char/str、字符白名单或转换回调；回调接收单项字符串并返回任意值；ALL分支另按HTML规则处理（默认：''） |

**返回值**：mixed 单项为过滤后的原值/回调结果，缺失为null；ALL返回数组，GLOBAL导入分支返回true

调用形式：

```php
$result = myReq::getValue($type, $para, $format);
```

定位：`lib/myReq.class.php:148`。

### myReq::get

功能说明：读取_GET数据

```text
public static function get($para = '', $format = 'str');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$para` | string\|int\|null；请求字段名称；null或[ALL]按入口约定读取全部（默认：''） |
| `$format` | string\|callable；!/int/url/char/str、字符白名单或转换回调；回调接收单项字符串并返回任意值；ALL分支另按HTML规则处理（默认：'str'） |

**返回值**：mixed GET单项过滤结果，缺失为null；空键/ALL返回数组，GLOBAL模式可返回true

调用形式：

```php
$result = myReq::get($para, $format);
```

定位：`lib/myReq.class.php:242`。

### myReq::post

功能说明：读取_POST数据

```text
public static function post($para = '', $format = 'str');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$para` | string\|int\|null；请求字段名称；null或[ALL]按入口约定读取全部（默认：''） |
| `$format` | string\|callable；!/int/url/char/str、字符白名单或转换回调；回调接收单项字符串并返回任意值；ALL分支另按HTML规则处理（默认：'str'） |

**返回值**：mixed POST单项过滤结果，缺失为null；空键/ALL返回数组，GLOBAL模式可返回true

调用形式：

```php
$result = myReq::post($para, $format);
```

定位：`lib/myReq.class.php:257`。

### myReq::files

功能说明：读取_FILES数据

```text
public static function files($para = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$para` | string\|int\|null；条目名称，null按入口约定读取全部（默认：''） |

**返回值**：mixed 上传字段的原始结构；空键返回全部字段数组，缺失字段为null

调用形式：

```php
$result = myReq::files($para);
```

定位：`lib/myReq.class.php:271`。

### myReq::request

功能说明：读取_REQUEST数据

```text
public static function request($para = '', $format = 'str');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$para` | string\|int\|null；请求字段名称；null或[ALL]按入口约定读取全部（默认：''） |
| `$format` | string\|callable；!/int/url/char/str、字符白名单或转换回调；回调接收单项字符串并返回任意值；ALL分支另按HTML规则处理（默认：'str'） |

**返回值**：mixed REQUEST单项过滤结果，缺失为null；空键/ALL返回数组，GLOBAL模式可返回true

调用形式：

```php
$result = myReq::request($para, $format);
```

定位：`lib/myReq.class.php:286`。

### myReq::server

功能说明：读取_SERVER数据

```text
public static function server($para = '', $format = '!');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$para` | string\|int\|null；请求字段名称；null或[ALL]按入口约定读取全部（默认：''） |
| `$format` | string\|callable；!/int/url/char/str、字符白名单或转换回调；回调接收单项字符串并返回任意值；ALL分支另按HTML规则处理（默认：'!'） |

**返回值**：mixed SERVER字段的过滤结果，缺失为null；空键/ALL返回全部数据

调用形式：

```php
$result = myReq::server($para, $format);
```

定位：`lib/myReq.class.php:301`。

### myReq::env

功能说明：读取_ENV数据

```text
public static function env($para);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$para` | string；环境变量名称；优先读取$_ENV，缺失时调用getenv |

**返回值**：string|array|false 环境变量值；getenv支持的形式可返回数组，变量不存在为false

调用形式：

```php
$result = myReq::env($para);
```

定位：`lib/myReq.class.php:316`。

### myReq::globals

功能说明：读取GLOABLS数据

null为读取标记，因此此入口不用于把全局变量设置为null。

```text
public static function globals($name, $value = null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$name` | string；全局变量名 |
| `$value` | mixed；null表示读取；非null值写入$GLOBALS，包括false和0（默认：null） |

**返回值**：mixed 读取时为全局变量原值/缺失null；非null写入完成返回true

调用形式：

```php
$result = myReq::globals($name, $value);
```

定位：`lib/myReq.class.php:329`。

### myReq::ip

功能说明：返回客户端IP

参数说明：无显式参数。

```text
public static function ip();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string 验证后的客户端IP；来自可信代理时检查转发链，REMOTE_ADDR无效时返回空串

调用形式：

```php
$result = myReq::ip();
```

定位：`lib/myReq.class.php:342`。

### myReq::setTrustedProxies

功能说明：登记可采信转发头的代理IP列表

```text
public static function setTrustedProxies(array $addresses);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$addresses` | array；可信代理的完整IPv4/IPv6地址列表；不接受CIDR网段 |

**返回值**：void 无返回值。

**异常与失败**：InvalidArgumentException 列表包含非法IP地址

调用形式：

```php
$result = myReq::setTrustedProxies($addresses);
```

定位：`lib/myReq.class.php:366`。

### myReq::trustedProxy

功能说明：检查当前REMOTE_ADDR是否精确命中可信代理列表

参数说明：无显式参数。

```text
public static function trustedProxy();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool REMOTE_ADDR与可信代理列表中的IP完全相同为true

调用形式：

```php
$result = myReq::trustedProxy();
```

定位：`lib/myReq.class.php:378`。

### myReq::ping

功能说明：IP地址连通测试 (IP:PORT)

```text
public static function ping($address, &$info = '', $timeout = 3);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$address` | string；要探测的主机名或IP |
| `&$info` | string；引用输出：连接失败信息；成功时清空（默认：''） |
| `$timeout` | int\|float；最长等待/执行时间，单位秒（默认：3） |

**返回值**：float|false TCP连接耗时秒数；连接失败为false，详情写入info

调用形式：

```php
$result = myReq::ping($address, $info, $timeout);
```

定位：`lib/myReq.class.php:390`。

### myReq::pingURL

功能说明：获取某个URL的链接时间，$detail返回相关时间参数（其他参数请自行var_export）如下：

 total_time - 获得用秒表示的上一次传输总共的时间，包括DNS解析、TCP连接等。
 namelookup_time - 获得用秒表示的从最开始到域名解析完毕的时间。
 connect_time - 获得用秒表示的从最开始直到对远程主机（或代理）的连接完毕的时间。
 pretransfer_time - 获得用秒表示的从最开始直到文件刚刚开始传输的时间。
 starttransfer_time - 获得用秒表示的从最开始到第一个字节被curl收到的时间。
 redirect_time - 获得所有用秒表示的包含了所有重定向步骤的时间，包括DNS解析、连接、传输前（pretransfer)和在最后的一次传输开始之前。

```text
public static function pingURL($url, &$detail);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$url` | string；目标URL或需要处理的链接 |
| `&$detail` | array；引用输出：curl_getinfo完整连接统计，含http_code/total_time等 |

**返回值**：float|false 连接总耗时秒数；HTTP代码为0时返回false，统计通过detail输出

调用形式：

```php
$result = myReq::pingURL($url, $detail);
```

定位：`lib/myReq.class.php:419`。

### myReq::setCookieOpt

功能说明：设置COOKIE参数

```text
public static function setCookieOpt($setting);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$setting` | array；当前功能使用的设置项 |

**返回值**：void 无返回值。

调用形式：

```php
$result = myReq::setCookieOpt($setting);
```

定位：`lib/myReq.class.php:435`。

### myReq::setCookie

功能说明：设置COOKIE（带前缀）

```text
public static function setCookie($name, $value = '', $expire = 0, $path = '', $domain = '', $httponly = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$name` | string；Cookie名称，是否添加框架前缀由入口/参数决定 |
| `$value` | mixed；需要写入或设置的数据值（默认：''） |
| `$expire` | int；相对当前请求时间的有效秒数；非正值发送过期Cookie用于删除（默认：0） |
| `$path` | string；Cookie路径；空串采用配置路径，仍为空时使用/（默认：''） |
| `$domain` | string；Cookie作用域域名，空串使用当前默认值（默认：''） |
| `$httponly` | bool；是否禁止浏览器脚本访问Cookie（默认：true） |

**返回值**：bool Set-Cookie是否成功发送，不代表浏览器最终保存；已发送响应头时为false

调用形式：

```php
$result = myReq::setCookie($name, $value, $expire, $path, $domain, $httponly);
```

定位：`lib/myReq.class.php:450`。

### myReq::setCookie_nopre

功能说明：设置COOKIE（不带前缀）

```text
public static function setCookie_nopre($name, $value = '', $expire = 0, $path = '', $domain = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$name` | string；Cookie名称，是否添加框架前缀由入口/参数决定 |
| `$value` | mixed；需要写入或设置的数据值（默认：''） |
| `$expire` | int；相对当前请求时间的有效秒数；非正值发送过期Cookie用于删除（默认：0） |
| `$path` | string；Cookie路径；空串采用配置路径，仍为空时使用/（默认：''） |
| `$domain` | string；Cookie作用域域名，空串使用当前默认值（默认：''） |

**返回值**：bool 底层Cookie设置是否成功

调用形式：

```php
$result = myReq::setCookie_nopre($name, $value, $expire, $path, $domain);
```

定位：`lib/myReq.class.php:480`。

### myReq::removeCookie

功能说明：删除COOKIE

```text
public static function removeCookie($name, $pre = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$name` | string；Cookie名称，是否添加框架前缀由入口/参数决定 |
| `$pre` | bool；是否自动添加已配置的Cookie名前缀（默认：true） |

**返回值**：bool 过期Cookie是否成功发送

调用形式：

```php
$result = myReq::removeCookie($name, $pre);
```

定位：`lib/myReq.class.php:494`。

### myReq::cookie

功能说明：读取COOKIE

```text
public static function cookie($para = '', $pre = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$para` | string\|int\|null；条目名称，null按入口约定读取全部（默认：''） |
| `$pre` | bool；是否自动添加已配置的Cookie名前缀（默认：true） |

**返回值**：mixed 指定Cookie值或全部Cookie数组；字段不存在为null，默认读取原始值

调用形式：

```php
$result = myReq::cookie($para, $pre);
```

定位：`lib/myReq.class.php:506`。

### myReq::setSessionOpt

功能说明：设置SESSION选项；缺失name时使用当前原生名称，不读取未定义键。

单位约定：expire为分钟，启动时乘60交给gc_maxlifetime及Cookie生命周期；数据库/缓存的TTL仍采用各自方法说明中的秒。

```text
public static function setSessionOpt($setting);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$setting` | array；id/expire（分钟）/gc/mode/trans_sid/path/name |

**返回值**：void 无返回值。

**异常与失败**：InvalidArgumentException 会话设置不是数组

调用形式：

```php
$result = myReq::setSessionOpt($setting);
```

定位：`lib/myReq.class.php:527`。

### myReq::sessionStart

功能说明：启动SESSION，启用严格ID校验并接入可选validateId/updateTimestamp回调。

资源生命周期：成功后PHP在请求结束时写回并关闭会话；需要提前释放同ID请求锁时由调用方使用session_write_close。
处理器结构：字符串可为内置files或静态处理器类名；数组前6项依次为open/close/read/write/destroy/gc，9项模式追加create_sid/validate_sid/update_timestamp；空数组使用原生files。

```text
public static function sessionStart($handle = array(), $httponly = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$handle` | string\|array；处理器类名、6个原回调或9个完整回调；空数组使用原生files（默认：array()） |
| `$httponly` | bool；Cookie是否禁止脚本读取（默认：true） |

**返回值**：string|false 成功返回实际ID；no_log禁用、处理器无效或启动失败返回false，不静默切换存储

调用形式：

```php
$result = myReq::sessionStart($handle, $httponly);
```

定位：`lib/myReq.class.php:551`。

### myReq::sessionRestart

功能说明：轮换活动SESSION的ID并保留数据；不做多余的关闭/重读，避免严格模式丢失新ID。

```text
public static function sessionRestart($id = '', $delete_old_session = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$id` | string；兼容指定目标ID；须有效且满足PHP严格模式，不保证采用未知ID（默认：''） |
| `$delete_old_session` | bool；是否删除旧ID存储，默认true（默认：true） |

**返回值**：string|false 非活动会话、非法ID或轮换失败返回false

调用形式：

```php
$result = myReq::sessionRestart($id, $delete_old_session);
```

定位：`lib/myReq.class.php:609`。

### myReq::sessionEnd

功能说明：清除会话数据并销毁存储，使用原Cookie路径、域和安全属性删除浏览器Cookie。

参数说明：无显式参数。

```text
public static function sessionEnd();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 非活动会话视为已关闭；存储或Cookie删除失败返回false

调用形式：

```php
$result = myReq::sessionEnd();
```

定位：`lib/myReq.class.php:627`。

### myReq::session

功能说明：读取SESSION

空键和null的调用约定：session()返回序列化文本；session($key,null)读取该键。需要批量恢复会话源串时使用setSessions。

```text
public static function session($key='', $value=null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | string\|null；会话键名；空串/null返回完整会话序列化文本；整数键被拒绝（默认：''） |
| `$value` | mixed；null表示读取，非null值写入；此入口不能用null作为待保存值（默认：null） |

**返回值**：mixed 空键为会话序列化文本；有键且value为null时读取原值；写入/未启动/整数键返回null

调用形式：

```php
$result = myReq::session($key, $value);
```

定位：`lib/myReq.class.php:652`。

### myReq::removeSession

功能说明：删除SESSION

```text
public static function removeSession($key);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$key` | string；需要删除的会话键名 |

**返回值**：void 无返回值。

调用形式：

```php
$result = myReq::removeSession($key);
```

定位：`lib/myReq.class.php:673`。

### myReq::setSessions

功能说明：将当前编码器的会话源串批量合并到活动会话，并成功后轮换ID。

```text
public static function setSessions($data);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$data` | string；与session.serialize_handler一致的原始数据 |

**返回值**：array|false|null 成功返回原会话数组；坏数据返回false并保留原状态

调用形式：

```php
$result = myReq::setSessions($data);
```

定位：`lib/myReq.class.php:684`。

### myReq::sessionId

功能说明：获取SESSION ID

参数说明：无显式参数。

```text
public static function sessionId();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string 当前PHP Session ID，未分配时为空串

调用形式：

```php
$result = myReq::sessionId();
```

定位：`lib/myReq.class.php:714`。

### myReq::sessionCheck

功能说明：检测SESSION是否已启动

参数说明：无显式参数。

```text
public static function sessionCheck();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool PHP会话状态为PHP_SESSION_ACTIVE时为true

调用形式：

```php
$result = myReq::sessionCheck();
```

定位：`lib/myReq.class.php:725`。

### myReq::setSessionPath

功能说明：设置SESSION保存路径

```text
public static function setSessionPath($path = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$path` | string；目标目录或路径（默认：''） |

**返回值**：string 设置完成后读取的当前Session目录；会话已启动、非files模式或目录准备失败时保留现有路径

调用形式：

```php
$result = myReq::setSessionPath($path);
```

定位：`lib/myReq.class.php:735`。

### myReq::setSessionName

功能说明：设置SESSION名称

```text
public static function setSessionName($name = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$name` | string；新的Session名称；空串只读取当前名称（默认：''） |

**返回值**：string|false 空名称或活动会话时读取当前名称；设置新名称成功返回旧名称，PHP拒绝设置时为false

调用形式：

```php
$result = myReq::setSessionName($name);
```

定位：`lib/myReq.class.php:754`。

### myReq::sessionEncode

功能说明：Session 编码

```text
public static function sessionEncode($array, $safe = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$array` | array；需要编码或转换的数组 |
| `$safe` | bool；true先通过PHP序列化复制输入数组，再编码为php会话格式（默认：true） |

**返回值**：string php会话编码字节串；数组键作为会话字段名，不是JSON或php_serialize格式

调用形式：

```php
$result = myReq::sessionEncode($array, $safe);
```

定位：`lib/myReq.class.php:769`。

### myReq::sessionDecode

功能说明：Session 解码

解析php会话序列化格式；默认禁止恢复对象，不等同于PHP原生任意序列化格式的解码。

```text
public static function sessionDecode($str, $allowed_classes = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$str` | string；待处理的字符串 |
| `$allowed_classes` | bool\|array；反序列化允许恢复的类策略，false禁用对象恢复（默认：false） |

**返回值**：array|false 解码后的会话键值；格式错误为false

调用形式：

```php
$result = myReq::sessionDecode($str, $allowed_classes);
```

定位：`lib/myReq.class.php:795`。
