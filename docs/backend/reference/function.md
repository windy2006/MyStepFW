# function：PHP公共函数

## 职责与调用位置

由框架基础引入；时间、数组、缓冲和调试函数有不同副作用。myEval只处理受信代码。

源码：`lib/function.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
$started = getMicrotime();
$milliseconds = getTimeDiff($started, 2, true);
assert(is_numeric($milliseconds));
$merged = arrayMerge(['a'=>1], ['b'=>2]);
assert($merged['a'] === 1 && $merged['b'] === 2);
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `getMicrotime` | 获取微秒时间 |
| `getTimeDiff` | 取得时间差 |
| `getDate_cn` | 获取中文日期 |
| `formatDate` | 格式化日期 |
| `shortUrl` | 缩略链接 |
| `tinyUrl` | 获取短网址 |
| `isMobile` | 判断是否为移动设备 |
| `isHttps` | 判断当前是否为SSL链接 |
| `myEval` | 自定义代码执行 |
| `checkPara` | 检测数据变量中是否有待解析的变量，并解析 |
| `arrayMerge` | 递归合并数组 |
| `recursionFunction` | 递归执行某一函数 |
| `getOB` | 获取缓存区内容并清空 |
| `debug_show` | 变量情况查看 |
| `debug` | 清空输出缓冲并展示任意个值，然后结束当前请求 |
| `debug_set` | 开启当前请求的条件调试标记 |
| `debug_check` | 已开启条件调试时展示传入值并结束请求 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### getMicrotime

功能说明：获取微秒时间

```text
function getMicrotime($rate = 5);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$rate` | int；保留微秒部分的小数位数；返回秒.小数形式的时间文本（默认：5） |

**返回值**：string 秒.小数形式的当前时间文本

调用形式：

```php
$result = getMicrotime($rate);
```

定位：`lib/function.php:54`。

### getTimeDiff

功能说明：取得时间差

```text
function getTimeDiff($time_start, $decimal = 2, $micro = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$time_start` | int\|float\|string；getMicrotime等取得的开始时间，单位秒 |
| `$decimal` | int；时间差保留的小数位数（默认：2） |
| `$micro` | bool；true将秒差换算为毫秒，false保留秒（默认：true） |

**返回值**：float 按decimal舍入的耗时；micro为true时单位毫秒，否则为秒

调用形式：

```php
$result = getTimeDiff($time_start, $decimal, $micro);
```

定位：`lib/function.php:68`。

### getDate_cn

功能说明：获取中文日期

```text
function getDate_cn($date='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$date` | string\|int；待解析的日期文本或时间戳，空串采用当前日期（默认：''） |

**返回值**：string 以中文数字表示的年月日文本，例如二○二六年十月十六日

调用形式：

```php
$result = getDate_cn($date);
```

定位：`lib/function.php:82`。

### formatDate

功能说明：格式化日期

```text
function formatDate($date='', $format='Y-m-d');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$date` | string\|int；待解析的日期文本或时间戳，空串采用当前日期（默认：''） |
| `$format` | string；输出或输入处理的格式规则（默认：'Y-m-d'） |

**返回值**：string 按format格式化的日期；格式不符合格式要求时回退Y-m-d

调用形式：

```php
$result = formatDate($date, $format);
```

定位：`lib/function.php:124`。

### shortUrl

功能说明：缩略链接

```text
function shortUrl($url, $max_length = 40);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$url` | string；需要缩短展示的URL；会移除查询部分并压缩路径，不生成HTML链接 |
| `$max_length` | int；显示链接的最大长度（默认：40） |

**返回值**：string 仅用于展示的缩短链接文本，不包含HTML标签

调用形式：

```php
$result = shortUrl($url, $max_length);
```

定位：`lib/function.php:138`。

### tinyUrl

功能说明：获取短网址

```text
function tinyUrl($url);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$url` | string；需要提交给短网址服务的原始URL |

**返回值**：string 短网址服务的响应文本；传输失败或空响应均为空串，服务错误文本不自动转为false

调用形式：

```php
$result = tinyUrl($url);
```

定位：`lib/function.php:176`。

### isMobile

功能说明：判断是否为移动设备

参数说明：无显式参数。

```text
function isMobile();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 请求头命中移动终端启发式规则为true，否则为false；不是可靠设备身份认证

调用形式：

```php
$result = isMobile();
```

定位：`lib/function.php:187`。

### isHttps

功能说明：判断当前是否为SSL链接

参数说明：无显式参数。

```text
function isHttps();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 原生HTTPS标记或可信代理的HTTPS转发标记成立为true，否则为false

调用形式：

```php
$result = isHttps();
```

定位：`lib/function.php:225`。

### myEval

功能说明：自定义代码执行

```text
function myEval($code, $return = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$code` | string；受信PHP代码文本，不接受不可信用户输入 |
| `$return` | bool；true取得include/表达式的返回值，false捕获并返回执行输出；不直接发送捕获内容（默认：false） |

**返回值**：mixed return为true时是代码返回值；为false时是捕获的输出字符串

**异常与失败**：RuntimeException 无法创建或完整写入受信代码的临时文件

调用形式：

```php
$result = myEval($code, $return);
```

定位：`lib/function.php:244`。

### checkPara

功能说明：检测数据变量中是否有待解析的变量，并解析

```text
function checkPara(&$att_list, $parse = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `&$att_list` | array；引用传入的属性列表，解析时原地更新 |
| `$parse` | bool；是否将检测到的变量表达式替换为实际值（默认：false） |

**返回值**：bool 检测到以$开头的待解析属性为true，否则为false；parse为true时原地更新属性

调用形式：

```php
$result = checkPara($att_list, $parse);
```

定位：`lib/function.php:271`。

### arrayMerge

功能说明：递归合并数组

```text
function arrayMerge($arr_1, $arr_2);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$arr_1` | array；用于合并的基础数组 |
| `$arr_2` | mixed；递归合并的数组；非数组值追加到第一个数组 |

**返回值**：array|false 按递归覆盖/追加规则合并的新数组；第一个参数不是数组为false

调用形式：

```php
$result = arrayMerge($arr_1, $arr_2);
```

定位：`lib/function.php:293`。

### recursionFunction

功能说明：递归执行某一函数

```text
function recursionFunction($func, $para);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$func` | callable；应用到数组叶子或单个值上的回调 |
| `$para` | mixed；需要递归处理的值或数组 |

**返回值**：mixed 回调处理后的值或保持原键结构的数组

调用形式：

```php
$result = recursionFunction($func, $para);
```

定位：`lib/function.php:331`。

### getOB

功能说明：获取缓存区内容并清空

```text
function getOB($clean=true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$clean` | bool；是否同时清空当前输出缓冲（默认：true） |

**返回值**：string|false 当前输出缓冲内容，未启用缓冲为false；clean只清空内容，不关闭缓冲

调用形式：

```php
$result = getOB($clean);
```

定位：`lib/function.php:350`。

### debug_show

功能说明：变量情况查看

```text
function debug_show();
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `...$args` | mixed；需要依次输出的任意个调试值（位置参数，由func_get_args读取） |

**返回值**：void 无返回值。

调用形式：

```php
$result = debug_show();
```

定位：`lib/function.php:365`。

### debug

功能说明：清空输出缓冲并展示任意个值，然后结束当前请求

```text
function debug();
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `...$args` | mixed；需要依次展示的调试值，展示后结束请求（位置参数，由func_get_args读取） |

**返回值**：void 无返回值。

调用形式：

```php
$result = debug();
```

定位：`lib/function.php:383`。

### debug_set

功能说明：开启当前请求的条件调试标记

参数说明：无显式参数。

```text
function debug_set();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 无返回值。

调用形式：

```php
$result = debug_set();
```

定位：`lib/function.php:395`。

### debug_check

功能说明：已开启条件调试时展示传入值并结束请求

```text
function debug_check();
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `...$args` | mixed；条件调试开启时交给debug的值（位置参数，由func_get_args读取） |

**返回值**：void 无返回值。

调用形式：

```php
$result = debug_check();
```

定位：`lib/function.php:405`。
