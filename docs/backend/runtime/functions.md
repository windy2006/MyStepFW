# 公共函数逐项用法

修订日期：2026-10-23。适用于当前框架。

## 本篇目标与调用场景

仅讲PHP全局函数；JavaScript同名debug或setURL属于不同运行环境。

## 4. 公共函数及调用边界

公共函数来自 `lib/function.php`，由框架基础加载阶段引入。

| 函数组 | 入口 | 说明 |
| --- | --- | --- |
| 时间 | `getMicrotime()`、`getTimeDiff()` | 前者返回时间文本；后者默认计算毫秒差，参数可改为秒 |
| 日期 | `getDate_cn()`、`formatDate()` | 中文日期或格式化日期；业务自行验证输入含义 |
| 环境 | `isHttps()`、`isMobile()` | 当前请求判断，不构成身份或权限验证 |
| 文本链接 | `shortUrl()`、`tinyUrl()` | 链接显示／短地址辅助，具体协议和输入仍需校验 |
| 数组／递归 | `arrayMerge()`、`recursionFunction()` | 依据原有规则递归合并或处理，不能套用所有框架的同名语义 |
| 缓冲 | `getOB()` | 取得缓冲内容，默认清理；注意当前缓冲的归属 |
| 调试 | `debug_show()`、`debug()`、`debug_set()`、`debug_check()` | 展示、结束或条件调试；不得混进正常 JSON、下载或生产响应 |
| 受信表达式 | `myEval()`、`checkPara()` | 仅用于受信程序与框架约定，不能运行用户提交代码 |

```php
$started = getMicrotime();
// 执行一次需要测量的受控操作。
$milliseconds = getTimeDiff($started, 2, true);
```

日志只记录必要的耗时及业务标识，不记录数据库密码、完整请求头、CSRF 或记住登录凭据。各方法完整参数与返回说明以源码 PHPDoc 为准，定位入口见[功能设置](../../basics/03-management.md)。


## 主要方法：参数、返回和失败约定

### getMicrotime

功能说明：获取微秒时间

```text
function getMicrotime($rate = 5);
```

| 参数说明 |
| --- |
| int $rate 保留微秒部分的小数位数；返回秒.小数形式的时间文本（默认：5） |

**返回**：string 秒.小数形式的当前时间文本

### getTimeDiff

功能说明：取得时间差

```text
function getTimeDiff($time_start, $decimal = 2, $micro = true);
```

| 参数说明 |
| --- |
| int\|float\|string $time_start getMicrotime等取得的开始时间，单位秒 |
| int $decimal 时间差保留的小数位数（默认：2） |
| bool $micro true将秒差换算为毫秒，false保留秒（默认：true） |

**返回**：float 按decimal舍入的耗时；micro为true时单位毫秒，否则为秒

### getDate_cn

功能说明：获取中文日期

```text
function getDate_cn($date='');
```

| 参数说明 |
| --- |
| string\|int $date 待解析的日期文本或时间戳，空串采用当前日期（默认：''） |

**返回**：string 以中文数字表示的年月日文本，例如二○二六年十月十六日

### formatDate

功能说明：格式化日期

```text
function formatDate($date='', $format='Y-m-d');
```

| 参数说明 |
| --- |
| string\|int $date 待解析的日期文本或时间戳，空串采用当前日期（默认：''） |
| string $format 输出或输入处理的格式规则（默认：'Y-m-d'） |

**返回**：string 按format格式化的日期；格式不符合历史要求时回退Y-m-d

### shortUrl

功能说明：缩略链接

```text
function shortUrl($url, $max_length = 40);
```

| 参数说明 |
| --- |
| string $url 需要缩短展示的URL；会移除查询部分并压缩路径，不生成HTML链接 |
| int $max_length 显示链接的最大长度（默认：40） |

**返回**：string 仅用于展示的缩短链接文本，不包含HTML标签

### tinyUrl

功能说明：获取短网址

```text
function tinyUrl($url);
```

| 参数说明 |
| --- |
| string $url 需要提交给短网址服务的原始URL |

**返回**：string 短网址服务的响应文本；传输失败或空响应均为空串，服务错误文本不自动转为false

### isMobile

功能说明：判断是否为移动设备

参数说明：无显式参数。

```text
function isMobile();
```

**返回**：bool 请求头命中移动终端启发式规则为true，否则为false；不是可靠设备身份认证

### isHttps

功能说明：判断当前是否为SSL链接

参数说明：无显式参数。

```text
function isHttps();
```

**返回**：bool 原生HTTPS标记或可信代理的HTTPS转发标记成立为true，否则为false

### myEval

功能说明：自定义代码执行

```text
function myEval($code, $return = false);
```

| 参数说明 |
| --- |
| string $code 受信PHP代码文本，不接受不可信用户输入 |
| bool $return true取得include/表达式的返回值，false捕获并返回执行输出；不直接发送捕获内容（默认：false） |

**返回**：mixed return为true时是代码返回值；为false时是捕获的输出字符串

**异常**：RuntimeException 无法创建或完整写入受信代码的临时文件

### checkPara

功能说明：检测数据变量中是否有待解析的变量，并解析

```text
function checkPara(&$att_list, $parse = false);
```

| 参数说明 |
| --- |
| array &$att_list 引用传入的属性列表，解析时原地更新 |
| bool $parse 是否将检测到的变量表达式替换为实际值（默认：false） |

**返回**：bool 检测到以$开头的待解析属性为true，否则为false；parse为true时原地更新属性

### arrayMerge

功能说明：递归合并数组

```text
function arrayMerge($arr_1, $arr_2);
```

| 参数说明 |
| --- |
| array $arr_1 用于合并的基础数组 |
| mixed $arr_2 递归合并的数组；非数组值追加到第一个数组 |

**返回**：array|false 按历史递归覆盖/追加规则合并的新数组；第一个参数不是数组为false

### recursionFunction

功能说明：递归执行某一函数

```text
function recursionFunction($func, $para);
```

| 参数说明 |
| --- |
| callable $func 应用到数组叶子或单个值上的回调 |
| mixed $para 需要递归处理的值或数组 |

**返回**：mixed 回调处理后的值或保持原键结构的数组

### getOB

功能说明：获取缓存区内容并清空

```text
function getOB($clean=true);
```

| 参数说明 |
| --- |
| bool $clean 是否同时清空当前输出缓冲（默认：true） |

**返回**：string|false 当前输出缓冲内容，未启用缓冲为false；clean只清空内容，不关闭缓冲

### debug_show

功能说明：变量情况查看

```text
function debug_show();
```

| 参数说明 |
| --- |
| mixed ...$args 需要依次输出的任意个调试值（历史位置参数，由func_get_args读取） |

**返回**：void 无返回值。

### debug

功能说明：清空输出缓冲并展示任意个值，然后结束当前请求

```text
function debug();
```

| 参数说明 |
| --- |
| mixed ...$args 需要依次展示的调试值，展示后结束请求（历史位置参数，由func_get_args读取） |

**返回**：void 无返回值。

### debug_set

功能说明：开启当前请求的条件调试标记

参数说明：无显式参数。

```text
function debug_set();
```

**返回**：void 无返回值。

### debug_check

功能说明：已开启条件调试时展示传入值并结束请求

```text
function debug_check();
```

| 参数说明 |
| --- |
| mixed ...$args 条件调试开启时交给debug的值（历史位置参数，由func_get_args读取） |

**返回**：void 无返回值。

## 明确调用示例

```php
$started = getMicrotime();
$milliseconds = getTimeDiff($started, 2, true);
assert(is_numeric($milliseconds));
$merged = arrayMerge(['a'=>1], ['b'=>2]);
assert($merged['a'] === 1 && $merged['b'] === 2);
```

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
