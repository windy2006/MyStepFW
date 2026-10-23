# myApi：注册接口与HTTP协议

修订日期：2026-10-23。功能详解。

## 职责与调用位置

invoke返回原始值，run返回编码文本；对象注册与route.php的HTTP注册并非同一件事。

源码：`lib/myApi.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
$api = new myApi();
$api->regMethod('sum', function ($a, $b) { return $a + $b; });
assert($api->invoke('sum', [2, 3]) === 5);
assert(json_decode($api->run('sum', [2, 3]), true) === 5);
```

## 声明与类职责

### myApi

功能说明：管理接口别名、回调执行及JSON/XML/文本输出。

使用说明：
  1. 先注册接口或选择已登记接口，再按约定调用/输出。
  2. 输入过滤和权限由框架或业务入口完成，回调异常按当前执行策略传递。

补充说明：
应用接口注册、原始执行及响应编码，执行目标仅来自明确登记的回调

    $api->regMethod('sum', function ($a, $b) { return $a + $b; });
    $value = $api->invoke('sum', [2, 3]);  // 原始结果，不发送响应头
    $json = $api->run('sum', [2, 3]);     // 编码 JSON 并设置内容类型
    $api->hasMethod('sum');              // 查询登记状态
    $api->removeMethod('sum');           // 注销单个接口

run() 保留未知接口编码空字符串的旧约定，invoke() 对未知接口明确报错。
参数使用位置顺序，不将关联键解释为 PHP 8 命名参数。JSON 恒为 UTF-8；XML 先以
UTF-8 生成，再按显式 charset 无损转码。回调、格式或编码异常不包装成成功空响应。
注册表不是认证/权限系统；HTTP 方法、CSRF、授权仍由应用及框架入口负责。

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
protected $methods = [];
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myApi::httpDefaults` | 返回HTTP接口默认值；全局参数属于框架api分组，endpoints属于独立策略。 |
| `myApi::httpSettings` | 补齐并验证框架api分组，供运行时及现有参数设置共同使用。 |
| `myApi::httpConfiguration` | 合并框架api参数与config/api.php的逐接口策略，兼容旧独立参数文件。 |
| `myApi::httpTarget` | 从框架API捕获路径定位已登记接口，兼容插件前缀和框架简写。 |
| `myApi::httpOrigins` | 按选定接口的跨域开关返回当前合法Origin，供CORS和CSRF共同使用。 |
| `myApi::httpURL` | 按专属域名或普通路径生成接口地址，沿用三种路由模式和子目录。 |
| `myApi::httpError` | 输出固定JSON错误并结束HTTP请求，不暴露内部配置或异常。 |
| `myApi::httpBodyLimit` | 对所有模式和内容类型执行统一请求正文上限，不改变原输入流和上传元数据。 |
| `myApi::httpCors` | 准备选定接口的CORS、统一正文预算和总开关；预检早于应用及会话启动。 |
| `myApi::httpPolicy` | 执行逐接口额外限制，不替代原业务鉴权和CSRF。 |
| `myApi::httpFormat` | 归一化支持的输出格式别名，未知值使用指定默认格式。 |
| `myApi::httpArguments` | 按原有位置参数约定构造接口参数；各内容类型已先执行正文上限。 |
| `myApi::init` | 初始化空接口对象；再次调用不主动清空已登记方法 |
| `myApi::methodName` | 验证接口名称，避免非法数组键及控制字符 |
| `myApi::regMethod` | 登记或覆盖单个接口，失败时保留原有登记 |
| `myApi::regMethods` | 先验证整批回调，再一次性合并，避免错误批次只登记了一部分 |
| `myApi::hasMethod` | 查询接口是否已明确登记，不进行全局函数回退 |
| `myApi::removeMethod` | 注销接口并释放该回调的引用 |
| `myApi::invoke` | 执行已登记接口并返回原始结果，不编码、不修改响应头 |
| `myApi::xmlResult` | 将结果编码为完整 UTF-8 XML，限制递归深度并拒绝对象循环 |
| `myApi::run` | 执行接口并返回编码响应，仅在编码成功且头未发送时设置内容类型 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myApi::httpDefaults

功能说明：返回HTTP接口默认值；全局参数属于框架api分组，endpoints属于独立策略。
参数说明：无显式参数。

```text
public static function httpDefaults();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：array enabled/default_format/body_bytes/domain/endpoints

调用形式：

```php
$result = myApi::httpDefaults();
```

定位：`lib/myApi.class.php:71`。

### myApi::httpSettings

功能说明：补齐并验证框架api分组，供运行时及现有参数设置共同使用。

```text
public static function httpSettings(array $data = []);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$data` | array；api分组中的四个全局字段（默认：[]） |

**返回值**：array 标准全局字段；域名转为小写，正文上限为字节数

**异常与失败**：UnexpectedValueException 未知字段、类型、正文额度或域名无效

调用形式：

```php
$result = myApi::httpSettings($data);
```

定位：`lib/myApi.class.php:81`。

### myApi::httpConfiguration

功能说明：合并框架api参数与config/api.php的逐接口策略，兼容旧独立参数文件。

```text
public static function httpConfiguration($data = null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$data` | array\|null；显式完整配置；null读取当前框架及策略文件（默认：null） |

**返回值**：array 标准全局参数及已补齐的接口策略

**异常与失败**：UnexpectedValueException 配置、来源或逐接口策略无效

调用形式：

```php
$result = myApi::httpConfiguration($data);
```

定位：`lib/myApi.class.php:102`。

### myApi::httpTarget

功能说明：从框架API捕获路径定位已登记接口，兼容插件前缀和框架简写。

```text
public static function httpTarget($path, $apis = null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$path` | string；API前缀之后的路径 |
| `$apis` | array\|null；当前API注册表，null使用全局路由（默认：null） |

**返回值**：array [scope,name]；不存在返回空数组，不加载或调用目标

调用形式：

```php
$result = myApi::httpTarget($path, $apis);
```

定位：`lib/myApi.class.php:146`。

### myApi::httpOrigins

功能说明：按选定接口的跨域开关返回当前合法Origin，供CORS和CSRF共同使用。

```text
public static function httpOrigins($path, $config = null, $apis = null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$path` | string；API前缀之后的路径 |
| `$config` | array\|null；已验证配置，null读取当前配置（默认：null） |
| `$apis` | array\|null；API注册表，null使用全局路由（默认：null） |

**返回值**：array 当前请求的合法来源；跨域关闭、接口未登记或来源非法时为空数组

调用形式：

```php
$result = myApi::httpOrigins($path, $config, $apis);
```

定位：`lib/myApi.class.php:161`。

### myApi::httpURL

功能说明：按专属域名或普通路径生成接口地址，沿用三种路由模式和子目录。

```text
public static function httpURL($scope, $name, $format = null, array $query = []);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$scope` | string；应用或plugin_插件标识 |
| `$name` | string；接口名称 |
| `$format` | string\|null；返回格式，null使用当前默认值，空字符串不附加格式尾段（默认：null） |
| `$query` | array；查询参数（默认：[]） |

**返回值**：string 专属域名非空时返回绝对URL，否则返回普通框架API路径

**异常与失败**：InvalidArgumentException 接口标识或格式无效

调用形式：

```php
$result = myApi::httpURL($scope, $name, $format, $query);
```

定位：`lib/myApi.class.php:178`。

### myApi::httpError

功能说明：输出固定JSON错误并结束HTTP请求，不暴露内部配置或异常。

```text
public static function httpError($status, $code, $message);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$status` | int；HTTP错误状态 |
| `$code` | string；固定错误标识 |
| `$message` | string；固定说明 |

**返回值**：void HEAD无正文；其他方法输出后结束

调用形式：

```php
$result = myApi::httpError($status, $code, $message);
```

定位：`lib/myApi.class.php:194`。

### myApi::httpBodyLimit

功能说明：对所有模式和内容类型执行统一请求正文上限，不改变原输入流和上传元数据。

```text
public static function httpBodyLimit(array $config);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$config` | array；已验证的API配置 |

**返回值**：void 合法请求继续；超限413、声明无效400、无长度multipart为411

**异常与失败**：RuntimeException 无法读取请求正文

调用形式：

```php
$result = myApi::httpBodyLimit($config);
```

定位：`lib/myApi.class.php:205`。

### myApi::httpCors

功能说明：准备选定接口的CORS、统一正文预算和总开关；预检早于应用及会话启动。

```text
public static function httpCors($config = null, $path = '', $apis = null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$config` | array\|null；已验证配置，null时读取（默认：null） |
| `$path` | string；API前缀之后的捕获路径（默认：''） |
| `$apis` | array\|null；当前API注册表，null使用全局路由（默认：null） |

**返回值**：void 普通请求继续；预检204、来源403、关闭503及正文拒绝后结束

调用形式：

```php
$result = myApi::httpCors($config, $path, $apis);
```

定位：`lib/myApi.class.php:226`。

### myApi::httpPolicy

功能说明：执行逐接口额外限制，不替代原业务鉴权和CSRF。

```text
public static function httpPolicy($scope, $name, array $config);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$scope` | string；已登记的应用或插件范围 |
| `$name` | string；已登记接口名称 |
| `$config` | array；已验证配置 |

**返回值**：array 完整策略；拒绝时404/405/403结束

调用形式：

```php
$result = myApi::httpPolicy($scope, $name, $config);
```

定位：`lib/myApi.class.php:247`。

### myApi::httpFormat

功能说明：归一化支持的输出格式别名，未知值使用指定默认格式。

```text
public static function httpFormat($value, $default = 'json');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$value` | mixed；格式候选 |
| `$default` | string；未知时的回退值（默认：'json'） |

**返回值**：string 规范格式或显式的空回退值

调用形式：

```php
$result = myApi::httpFormat($value, $default);
```

定位：`lib/myApi.class.php:261`。

### myApi::httpArguments

功能说明：按原有位置参数约定构造接口参数；各内容类型已先执行正文上限。

```text
public static function httpArguments(array $tail, array $policy, array $config, $legacy_format);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$tail` | array；接口之后的路径段 |
| `$policy` | array；当前接口策略；保留签名兼容，参数模式不再可配置 |
| `$config` | array；已验证配置 |
| `$legacy_format` | mixed；旧分发器的格式候选 |

**返回值**：array [规范格式,位置参数列表]；GET/POST合并后追加路径参数

**异常与失败**：RuntimeException 读取请求参数失败；正文预算由前置httpBodyLimit检查

调用形式：

```php
$result = myApi::httpArguments($tail, $policy, $config, $legacy_format);
```

定位：`lib/myApi.class.php:274`。

### myApi::init

功能说明：初始化空接口对象；再次调用不主动清空已登记方法

参数说明：无显式参数。

```text
public function init();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的myApi实例。
$result = $instance->init();
```

定位：`lib/myApi.class.php:288`。

### myApi::methodName

功能说明：验证接口名称，避免非法数组键及控制字符

```text
protected function methodName($method);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$method` | string\|int；接口标识，保留数字键兼容 |

**返回值**：string 非空规范名称；大小写仍按旧注册表区分

**异常与失败**：InvalidArgumentException 名称类型、空值或控制字符非法

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myApi.class.php:297`。

### myApi::regMethod

功能说明：登记或覆盖单个接口，失败时保留原有登记

```text
public function regMethod($method, $func);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$method` | string\|int；非空接口标识 |
| `$func` | callable；函数、静态/对象方法、闭包或可调用对象 |

**返回值**：$this 当前 API 对象，可连续登记

**异常与失败**：InvalidArgumentException 名称非法或回调不可调用

调用形式：

```php
// $instance为已初始化的myApi实例。
$result = $instance->regMethod($method, $func);
```

定位：`lib/myApi.class.php:310`。

### myApi::regMethods

功能说明：先验证整批回调，再一次性合并，避免错误批次只登记了一部分

```text
public function regMethods($method_list);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$method_list` | array\|Traversable；接口名称到回调的映射 |

**返回值**：$this 当前 API 对象；同名接口覆盖旧回调

**异常与失败**：InvalidArgumentException 列表类型、名称或任一回调非法

调用形式：

```php
// $instance为已初始化的myApi实例。
$result = $instance->regMethods($method_list);
```

定位：`lib/myApi.class.php:324`。

### myApi::hasMethod

功能说明：查询接口是否已明确登记，不进行全局函数回退

```text
public function hasMethod($method);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$method` | string\|int；接口标识 |

**返回值**：bool 名称存在于当前注册表时为 true

调用形式：

```php
// $instance为已初始化的myApi实例。
$result = $instance->hasMethod($method);
```

定位：`lib/myApi.class.php:342`。

### myApi::removeMethod

功能说明：注销接口并释放该回调的引用

```text
public function removeMethod($method);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$method` | string\|int；接口标识 |

**返回值**：bool 原先存在并已删除为 true；不存在为 false

调用形式：

```php
// $instance为已初始化的myApi实例。
$result = $instance->removeMethod($method);
```

定位：`lib/myApi.class.php:350`。

### myApi::invoke

功能说明：执行已登记接口并返回原始结果，不编码、不修改响应头


调用示例：
    $api->regMethod('sum', function ($a, $b) { return $a + $b; });
    $sum = $api->invoke('sum', [2, 3]); // 原始整数结果5

```text
public function invoke($method, array $para = []);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$method` | string\|int；明确登记的接口名 |
| `$para` | array；按遍历顺序作为位置参数传递，关联键不作为参数名（默认：[]） |

**返回值**：mixed 回调结果，保留 false/null/0/空数组等类型

**异常与失败**：BadMethodCallException 接口未登记，不自动寻找同名全局函数；Throwable 业务回调异常继续传递

调用形式：

```php
// $instance为已初始化的myApi实例。
$result = $instance->invoke($method, $para);
```

定位：`lib/myApi.class.php:369`。

### myApi::xmlResult

功能说明：将结果编码为完整 UTF-8 XML，限制递归深度并拒绝对象循环

```text
protected function xmlResult($value);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$value` | mixed；原始结果；对象使用公开属性，数字/非法元素名回退 item |

**返回值**：string 以 mystep 为根的 XML，不发送响应头

**异常与失败**：InvalidArgumentException 文本不是有效 UTF-8 或包含资源；RuntimeException 无 XMLWriter、结果嵌套超限或包含循环引用

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myApi.class.php:383`。

### myApi::run

功能说明：执行接口并返回编码响应，仅在编码成功且头未发送时设置内容类型

JSON 不猜测或改写对象编码，避免修改回调返回对象；非法 UTF-8/循环会明确报错。
XML 字符串需要有效 UTF-8，非法 XML 控制字符移除；charset 仅控制文本类响应
的输出标签及 XML 转码，不用于把任意二进制猜测为 Unicode。

```text
public function run($method, $para=[], $return='json', $charset='utf-8');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$method` | string\|int；已登记接口；未知接口沿用编码空字符串的旧行为 |
| `$para` | array；位置参数数组，关联键按遍历顺序传递（默认：[]） |
| `$return` | string；json/j、xml/x、string/s、hex/h、code/c/script；未知值回退 JSON（默认：'json'） |
| `$charset` | string；文本输出字符集，空值使用 UTF-8；JSON 恒为 UTF-8（默认：'utf-8'） |

**返回值**：string 编码后的内容，不 echo、不结束请求

**异常与失败**：InvalidArgumentException 参数/字符集标签非法或 XML 文本非法；JsonException JSON 编码失败，不静默返回 false；RuntimeException XML 超限/循环、字符转码失败或编码不可用；Throwable 业务回调异常继续传递

调用形式：

```php
// $instance为已初始化的myApi实例。
$result = $instance->run($method, $para, $return, $charset);
```

定位：`lib/myApi.class.php:426`。
