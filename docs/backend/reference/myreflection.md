# myReflection：声明和注释反射

修订日期：2026-10-23。功能详解。

## 职责与调用位置

只对受信类或对象反射；用于说明和辅助调用，不把用户输入当待加载类名。

源码：`lib/myReflection.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
$reflection = new myReflection('myString');
$name = $reflection->getName();
$methods = $reflection->getFunc();
$comment = $reflection->getComment('convertCharset');
```

## 声明与类职责

### myReflection

功能说明：封装类、对象、函数及扩展反射，读取元信息、成员列表与文档注释。

使用说明：
  1. 传类/接口/trait名、函数名、扩展名或对象初始化，再读取声明及注释信息。
  2. 被反射目标的文档依赖PHP保留DocComment；其他反射能力由底层Reflection对象代理提供。

使用示例：
    $ref = new myReflection('myFile');
    $methods = $ref->getFunc(); $doc = $ref->getComment('getLocal');
    $instance = (new myReflection($className))->get(...$constructorArgs);

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
protected
        $info = array();
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myReflection::init` | 获取对象基本信息 |
| `myReflection::__get` | 返回对象某一基本信息 |
| `myReflection::info` | 返回全部基本信息 |
| `myReflection::export` | 返回对象详细信息 |
| `myReflection::get` | 按位置参数调用反射函数或构造普通类实例 |
| `myReflection::getName` | 获取对象名称 |
| `myReflection::getVariants` | 获取对象所有变量及常量 |
| `myReflection::getFunc` | 获取对象方法 |
| `myReflection::getComment` | 读取目标/指定方法的注释，或返回全部方法注释映射 |
| `myReflection::is` | 对象相关特性检测 |
| `myReflection::check` | 对象相关属性检测 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myReflection::init

功能说明：获取对象基本信息

```text
public function init($para = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$para` | string\|object；类/接口/trait名、函数名、扩展名或待反射对象（默认：''） |

**返回值**：void 无返回值。

**异常与失败**：ReflectionException 目标反射或底层调用产生的异常继续传递

调用形式：

```php
// $instance为已初始化的myReflection实例。
$result = $instance->init($para);
```

定位：`lib/myReflection.class.php:78`。

### myReflection::__get

功能说明：返回对象某一基本信息

```text
public function __get($para);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$para` | string；反射元信息字段名或代理属性名 |

**返回值**：mixed 反射元信息字段的原值；未知字段为null

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myReflection.class.php:149`。

### myReflection::info

功能说明：返回全部基本信息

参数说明：无显式参数。

```text
public function info();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：array 当前目标的反射元信息映射；尚未初始化时为空数组

调用形式：

```php
// $instance为已初始化的myReflection实例。
$result = $instance->info();
```

定位：`lib/myReflection.class.php:160`。

### myReflection::export

功能说明：返回对象详细信息

参数说明：无显式参数。

```text
public function export();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string 底层Reflection对象的字符串描述，不是可执行导出脚本

调用形式：

```php
// $instance为已初始化的myReflection实例。
$result = $instance->export();
```

定位：`lib/myReflection.class.php:171`。

### myReflection::get

功能说明：按位置参数调用反射函数或构造普通类实例

```text
public function get();
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `...$args` | mixed；函数调用或类构造的位置参数（历史位置参数，由func_get_args读取） |

**返回值**：mixed 函数的返回值或新类实例；未初始化为false，其他目标类别为null

**异常与失败**：Throwable 自定义解析器、初始化/反射目标或受信执行文件产生的异常继续传递

调用形式：

```php
// $instance为已初始化的myReflection实例。
$result = $instance->get();
```

定位：`lib/myReflection.class.php:182`。

### myReflection::getName

功能说明：获取对象名称

```text
public function getName($mode = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$mode` | bool；目标位于命名空间时，true返回namespace/name数组，false返回完整名称（默认：false） |

**返回值**：string|array 名称文本；命名空间目标且mode为true时返回namespace/name数组

调用形式：

```php
// $instance为已初始化的myReflection实例。
$result = $instance->getName($mode);
```

定位：`lib/myReflection.class.php:205`。

### myReflection::getVariants

功能说明：获取对象所有变量及常量

参数说明：无显式参数。

```text
public function getVariants();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：array|null 含constant/variant的常量和默认属性映射；非类或未初始化为null

调用形式：

```php
// $instance为已初始化的myReflection实例。
$result = $instance->getVariants();
```

定位：`lib/myReflection.class.php:228`。

### myReflection::getFunc

功能说明：获取对象方法

```text
public function getFunc($mode = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$mode` | bool；反射扩展时true返回反射对象列表，false返回名称列表；类返回方法反射列表（默认：false） |

**返回值**：array|null 类的方法列表，或扩展的function/class映射；未初始化为null

调用形式：

```php
// $instance为已初始化的myReflection实例。
$result = $instance->getFunc($mode);
```

定位：`lib/myReflection.class.php:243`。

### myReflection::getComment

功能说明：读取目标/指定方法的注释，或返回全部方法注释映射


调用示例：
    $doc = (new myReflection('myFile'))->getComment('getLocal');

```text
public function getComment($method = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$method` | string；方法名；空串读目标注释，__ALL__返回全部方法的原始注释映射（默认：''） |

**返回值**：string|array|false 注释文本；__ALL__返回方法注释映射，底层无DocComment可能为false

调用形式：

```php
// $instance为已初始化的myReflection实例。
$result = $instance->getComment($method);
```

定位：`lib/myReflection.class.php:281`。

### myReflection::is

功能说明：对象相关特性检测

```text
public function is($type = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$type` | string；特性代号：abstract/a、anonymous/n、interface/i、clone/c、final/f、instantiable/ia、internal/in、iterable/it、iterateable/ita、trait/t或userdefined/u（默认：''） |

**返回值**：bool 类目标具备指定特性为true；非类目标或未知标记为false

调用形式：

```php
// $instance为已初始化的myReflection实例。
$result = $instance->is($type);
```

定位：`lib/myReflection.class.php:308`。

### myReflection::check

功能说明：对象相关属性检测

```text
public function check($name, $type = 'method');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$name` | string；待检查的属性/方法等成员名 |
| `$type` | string；成员类别：method/m、constant/c、property/p/variant/v、interface/i、namespace/n或subclass/s（默认：'method'） |

**返回值**：bool 类目标满足指定成员/接口/命名空间检查为true；不满足或非类为false

调用形式：

```php
// $instance为已初始化的myReflection实例。
$result = $instance->check($name, $type);
```

定位：`lib/myReflection.class.php:367`。
