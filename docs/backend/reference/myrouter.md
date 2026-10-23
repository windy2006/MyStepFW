# myRouter：规则、格式与匹配

修订日期：2026-10-23。功能详解。

## 职责与调用位置

正则rule和字面literal分开；闭包只用于内存规则，持久声明使用命名回调。

源码：`lib/myRouter.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

本例用于已加载框架依赖、目录常量与服务器变量的独立测试进程，只验证规则注册和捕获，不再次启动当前应用。实际网站在route.php声明规则，由统一调度器调用check。构造／parseQuery会重建请求数组，不能在业务模块随意重放当前请求。

```php
$router = new myRouter();
$router->literal('/article/[int].json', 'app\\Hello\\detail', 'Hello');
$compiled = end($router->rules);
assert(preg_match($compiled['regex'], '/article/42.json', $captures) === 1);
assert($captures[1] === '42');
// 注册命名回调不立即执行；真实check命中后会准备应用、会话并执行处理器。
```

## 声明与类职责

### myRouter

功能说明：处理重写/查询/PATH_INFO模式、路径参数、路由规则与目标回调。

使用说明：
  1. 框架按配置初始化后登记规则并调用check；应用规则由myStep加载。
  2. 保留查询参数值及编码边界，嵌套回调与动态参数按规则占位符展开。
  3. 不使用任意用户字符串直接构造执行回调或正则规则。

补充说明：
网站路由处理：解析请求路径、注册格式及规则，并执行首个匹配的处理器

    $router = new myRouter($setting);                       // 初始化请求及应用信息
    $router->format('hex', '[a-fA-F0-9]+');                  // 注册单个捕获参数格式
    $router->rule('/item/[hex]', 'showItem', 'MyApp');        // 注册保留正则语义的规则
    $router->literal('/item/[int].json', 'showItem', 'MyApp'); // 注册字面路径模板
    $router->setRules($file);                               // 加载可信路由注册文件
    $router->check('/item/12.json');                         // 匹配并执行规则

使用说明：
1. rule() 保留原始正则语义；literal() 仅展开已定义占位符，其他字符按字面匹配。
2. 先检查直接路径，再检查绑定前缀路径；每轮按注册顺序使用首个匹配规则。
3. parseQuery() 会重建 GET、REQUEST 及 QUERY_STRING；parse() 生成应用信息，
   check() 在命中后同步应用全局信息并调用处理器，不是纯粹的匹配查询。
4. 内存规则可使用闭包/对象回调；持久注册文件只能保存普通数据及命名回调。
   路由文件会被 include，必须来自受信开发代码；正则本身不提供资源隔离。

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
public
        $query = '',
        $apis = [],
        $rules = array(),
        $route = array(),
        $info = array('app'=>'', 'path'=>'', 'para'=>array());
```

```text
protected
        $setting = array(),
        $formats = array(
            'any' => '(.*)',
            'int' => '(\d+)',
            'str' => '(\w+)'
        );
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myRouter::init` | 初始化路由设置并解析当前请求，清空已注册规则及 API 列表 |
| `myRouter::parseQuery` | 统一解析重写路径、PATH_INFO 及旧式 index.php?route 请求 |
| `myRouter::hasPrefix` | 按完整路径段判断前缀，避免 /app 错误匹配 /application |
| `myRouter::format` | 注册或覆盖一个命名占位符格式，每个占位符只占一个参数位置 |
| `myRouter::setFormats` | 批量注册占位符格式，可递归处理多组格式数组 |
| `myRouter::rule` | 注册保持历史正则语义的路径规则 |
| `myRouter::literal` | 注册字面路径模板，只把已定义的命名占位符展开为正则 |
| `myRouter::compilePattern` | 为已展开的规则选择无冲突分隔符，并编译为完整匹配正则 |
| `myRouter::setRules` | 批量注册规则或加载可信 PHP 路由注册文件 |
| `myRouter::check` | 按优先级匹配路由并执行处理器，命中后更新应用配置及全局应用信息 |
| `myRouter::routeArguments` | 分离命名处理器的显式参数，并替换捕获参数引用 |
| `myRouter::parse` | 从当前 route 生成应用、模块路径及查询参数信息 |
| `myRouter::remove` | 加锁移除持久路由注册表中某个应用的格式、规则及 API |
| `myRouter::checkRoute` | 同步单个应用的路由定义到共享注册表，内容不变时不重复写入 |
| `myRouter::saveRegistry` | 原子保存格式、规则及 API 注册表，并使对应 OPcache 失效 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myRouter::init

功能说明：初始化路由设置并解析当前请求，清空已注册规则及 API 列表

```text
public function init($setting=array());
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$setting` | array；设置项：default_app 默认 myStep；delimiter_path 默认 /；<br>                      delimiter_para 默认 &；url_fix 为绑定路径前缀，默认空字符串（默认：array()） |

**返回值**：void 初始化 route、query 和 info；格式表保留实例当前定义

**异常与失败**：InvalidArgumentException 路径或查询分隔符不是非空字符串

调用形式：

```php
// $instance为已初始化的myRouter实例。
$result = $instance->init($setting);
```

定位：`lib/myRouter.class.php:102`。

### myRouter::parseQuery

功能说明：统一解析重写路径、PATH_INFO 及旧式 index.php?route 请求

显式传入时按第一个 ? 或 & 分离路径与查询；空字符串表示读取当前服务器变量。
规范化前导斜杠，纯单词路径沿用尾斜杠约定。覆盖 GET 和 QUERY_STRING，并依照
PHP request_order（缺省回退 variables_order）顺序重建 REQUEST，避免残留旧 GET。

```text
public function parseQuery($qstr='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$qstr` | string；待解析的路径及查询；空字符串使用当前请求（默认：''） |

**返回值**：array 包含 p（规范路径）、q（查询串）、qstr（p 与可选 &q）的数组

调用形式：

```php
// $instance为已初始化的myRouter实例。
$result = $instance->parseQuery($qstr);
```

定位：`lib/myRouter.class.php:126`。

### myRouter::hasPrefix

功能说明：按完整路径段判断前缀，避免 /app 错误匹配 /application

```text
public static function hasPrefix($path, $prefix);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$path` | string；待检查路径 |
| `$prefix` | string；路径前缀；比较前移除其末尾斜杠 |

**返回值**：bool 路径等于前缀或以前缀加 / 开头时为 true

调用形式：

```php
$result = myRouter::hasPrefix($path, $prefix);
```

定位：`lib/myRouter.class.php:181`。

### myRouter::format

功能说明：注册或覆盖一个命名占位符格式，每个占位符只占一个参数位置

内部普通捕获组转为非捕获组，整体再包一层捕获；不接受命名捕获及反向引用。
格式在注册时校验，应用作用域格式可由 setRules() 临时加载。

```text
public function format($name, $pattern);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$name` | string；占位符名称，由字母、数字或下划线组成，例如 hex |
| `$pattern` | string；非空 PCRE 片段，不包含两端分隔符，例如 [a-fA-F0-9]+ |

**返回值**：$this 当前路由对象，可继续注册

**异常与失败**：InvalidArgumentException 名称、片段、捕获形式或编译结果非法

调用形式：

```php
// $instance为已初始化的myRouter实例。
$result = $instance->format($name, $pattern);
```

定位：`lib/myRouter.class.php:197`。

### myRouter::setFormats

功能说明：批量注册占位符格式，可递归处理多组格式数组

```text
public function setFormats($formats);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$formats` | array；名称到正则片段的映射，或由此类映射组成的嵌套数组 |

**返回值**：$this 当前路由对象；空数组不改变格式

**异常与失败**：InvalidArgumentException 参数不是数组或其中格式非法

调用形式：

```php
// $instance为已初始化的myRouter实例。
$result = $instance->setFormats($formats);
```

定位：`lib/myRouter.class.php:224`。

### myRouter::rule

功能说明：注册保持历史正则语义的路径规则

已知 [name] 展开为对应格式；未知 [name] 沿用旧行为，成为捕获该名称的分组。
整条规则由 compilePattern() 统一添加完整匹配边界，不修改处理器执行顺序。

```text
public function rule($rule, $method, $idx = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$rule` | string；非空路径正则片段，不包含分隔符，可使用 [int]/[str]/[any] |
| `$method` | callable\|string\|array；回调、带逗号参数的命名处理器或中间件序列 |
| `$idx` | string；所属应用标识；空字符串沿用现有框架应用解析行为（默认：''） |

**返回值**：$this 当前路由对象

**异常与失败**：InvalidArgumentException 规则为空、类型错误或无法编译

调用形式：

```php
// $instance为已初始化的myRouter实例。
$result = $instance->rule($rule, $method, $idx);
```

定位：`lib/myRouter.class.php:252`。

### myRouter::literal

功能说明：注册字面路径模板，只把已定义的命名占位符展开为正则

例如 /article/[int].json 中的点号按字面匹配；捕获值仍按声明顺序传给处理器。
与 rule() 相比，未知占位符直接报错，不回退为捕获占位符名称。

```text
public function literal($path, $handler, $idx = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$path` | string；非空路径模板，例如 /article/[int].json |
| `$handler` | callable\|string\|array；回调、带逗号参数的命名处理器或中间件序列 |
| `$idx` | string；所属应用标识，含义与 rule() 一致（默认：''） |

**返回值**：$this 当前路由对象

**异常与失败**：InvalidArgumentException 路径非法、占位符未定义或展开结果无法编译

调用形式：

```php
// $instance为已初始化的myRouter实例。
$result = $instance->literal($path, $handler, $idx);
```

定位：`lib/myRouter.class.php:274`。

### myRouter::compilePattern

功能说明：为已展开的规则选择无冲突分隔符，并编译为完整匹配正则

以非捕获组包围整个片段，避免顶层 | 越过匹配边界；允许一个可选末尾斜杠。

```text
protected function compilePattern($pattern);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$pattern` | string；已展开占位符的 PCRE 片段，不含两端分隔符 |

**返回值**：string 含分隔符、首尾边界及 D 修饰符的完整正则

**异常与失败**：InvalidArgumentException 正则非法或所有候选分隔符均被占用

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myRouter.class.php:296`。

### myRouter::setRules

功能说明：批量注册规则或加载可信 PHP 路由注册文件

支持 [路径, 处理器]、规则列表、应用标识到规则列表的映射。文件可定义
format_list、rule_list、api_list；应用格式仅在编译所属应用规则时使用。

```text
public function setRules($rules, $idx = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$rules` | array\|string；规则数组或可信注册文件路径；不存在的文件不添加规则 |
| `$idx` | string；数字下标规则默认所属的应用，关联下标可指定各自应用（默认：''） |

**返回值**：$this 当前路由对象

**异常与失败**：InvalidArgumentException 缺少处理器、规则或格式不合法

调用形式：

```php
// $instance为已初始化的myRouter实例。
$result = $instance->setRules($rules, $idx);
```

定位：`lib/myRouter.class.php:318`。

### myRouter::check

功能说明：按优先级匹配路由并执行处理器，命中后更新应用配置及全局应用信息

可重复检查不同路径，保留实例绑定前缀；命名捕获只按数字顺序传递一次。
支持普通回调、逗号参数中的 $1 等引用及中间件序列；中间件返回 false 时
停止后续调用，但已命中的请求仍返回 true。实际处理器可输出、跳转或结束请求。

```text
public function check($qstr='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$qstr` | string；非空时重新解析该路径及查询，空字符串检查当前 query（默认：''） |

**返回值**：bool true 表示规则已命中并处理（含被中间件阻止）；false 表示无匹配

**异常与失败**：RuntimeException PCRE 执行失败，不能按普通未匹配继续回退

调用形式：

```php
// $instance为已初始化的myRouter实例。
$result = $instance->check($qstr);
```

定位：`lib/myRouter.class.php:361`。

### myRouter::routeArguments

功能说明：分离命名处理器的显式参数，并替换捕获参数引用

```text
protected function routeArguments($handler, array $captures);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$handler` | callable\|string\|array；非字符串回调原样保留；字符串按逗号分割 |
| `$captures` | array；从第一个捕获开始的零起始列表；$1 引用其第 0 项 |

**返回值**：array [处理器, 显式参数列表]；不存在的捕获引用保留原文本

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myRouter.class.php:469`。

### myRouter::parse

功能说明：从当前 route 生成应用、模块路径及查询参数信息

重置旧 info，避免连续解析残留；绑定前缀只补一次。首段不是有效应用时使用
默认应用，存在的 info.php 元数据与解析出的 app/path/para 合并。

参数说明：无显式参数。

```text
public function parse();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 结果写入实例 info，不执行路由处理器

调用形式：

```php
// $instance为已初始化的myRouter实例。
$result = $instance->parse();
```

定位：`lib/myRouter.class.php:489`。

### myRouter::remove

功能说明：加锁移除持久路由注册表中某个应用的格式、规则及 API

```text
public function remove($file, $idx);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；可信 PHP 注册文件路径；锁文件位于同目录的 .lock 路径 |
| `$idx` | string；需要删除的应用标识 |

**返回值**：bool 至少存在一项并已移除时为 true，文件或标识不存在时为 false

**异常与失败**：RuntimeException 无法锁定或原子保存注册表

调用形式：

```php
// $instance为已初始化的myRouter实例。
$result = $instance->remove($file, $idx);
```

定位：`lib/myRouter.class.php:519`。

### myRouter::checkRoute

功能说明：同步单个应用的路由定义到共享注册表，内容不变时不重复写入

源文件应定义 format、rule、api；空值或空数组表示移除该应用对应分组。
整个读改写过程使用目标旁的锁文件，防止多个应用同时登记时覆盖彼此。

```text
public static function checkRoute($path_main, $path_this, $idx);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$path_main` | string；目标 PHP 注册文件路径，不存在时创建所在目录及注册表 |
| `$path_this` | string；可信的应用路由定义文件路径，会被 include |
| `$idx` | string；目标注册表中的应用标识 |

**返回值**：bool 源文件存在且同步完成为 true；源文件不存在为 false

**异常与失败**：RuntimeException 无法锁定或保存注册表；InvalidArgumentException 定义中包含不能持久化的对象或资源

调用形式：

```php
$result = myRouter::checkRoute($path_main, $path_this, $idx);
```

定位：`lib/myRouter.class.php:548`。

### myRouter::saveRegistry

功能说明：原子保存格式、规则及 API 注册表，并使对应 OPcache 失效

仅导出普通数据，拒绝所有对象（含闭包）和资源；myStep 规则分组排到最后。
本方法不获取读改写锁；需要合并现有注册表时由调用方先持有 $file.lock。

```text
public static function saveRegistry($file, array $formats, array $rules, array $apis);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；目标 PHP 文件路径，父目录须已存在且可写 |
| `$formats` | array；全部格式定义，写入 format_list |
| `$rules` | array；全部规则定义，写入 rule_list；持久回调使用函数名或静态方法 |
| `$apis` | array；全部 API 定义，写入 api_list |

**返回值**：void 成功时发布完整文件，不返回字节数

**异常与失败**：InvalidArgumentException 数据中包含对象或资源；RuntimeException 临时文件写入不完整或原子替换失败

调用形式：

```php
$result = myRouter::saveRegistry($file, $formats, $rules, $apis);
```

定位：`lib/myRouter.class.php:587`。
