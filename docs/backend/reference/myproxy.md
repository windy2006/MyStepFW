# myProxy：代理对象与别名

## 职责与调用位置

真实构造只运行一次，无构造才使用init。引用属性修改需propertyReference，代理不提供权限隔离。

源码：`lib/myProxy.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
class HelloTarget { public $items = []; public function hello($name) { return 'Hi '.$name; } }
class HelloProxy extends myProxy {}
$proxy = new HelloProxy(new HelloTarget());
assert($proxy->hello('A') === 'Hi A');
$items =& $proxy->propertyReference('items');
$items[] = 'one';
assert($proxy->items === ['one']);
```

## 声明与类职责

### myProxy

功能说明：代理目标构造、公开属性、引用访问及方法调用，并支持别名和trait扩展。

使用说明：
  1. 继承后通过init选择目标类或对象；类名模式使用构造函数或init。
  2. __get保留非引用语义；需要原地修改数组时使用propertyReference。
  3. 目标返回自身时的链式调用、别名及魔术方法按实现处理，不作为权限边界。

补充说明：
通用对象代理，转发方法及公开/虚拟属性，并支持显式方法别名

    $proxy->init('TargetClass', $arg1, $arg2);  // 构造目标并传入初始化参数
    $proxy->init($existingObject);             // 接管现有实例引用，不重新初始化
    $proxy->regAlias(['short' => 'fullName']); // 注册方法别名
    $items =& $proxy->propertyReference('items'); // 显式取得可引用属性

使用说明：
1. 目标存在构造函数时只调用构造函数；没有构造函数才调用可调用的 init()。
2. 初始化失败不会替换目标；本代理不提供权限隔离，目标须来自受信代码。
3. __get() 保留非引用返回签名，支持现有子类；修改数组属性须使用
   propertyReference()，或由目标提供明确的修改方法。
4. 别名优先于目标魔术分发；没有目标方法时回退到 myTrait 的扩展方法机制。

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
protected
        $obj = '';
```

```text
protected $func_alias = [];
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myProxy::init` | 初始化代理目标，成功后才替换原实例 |
| `myProxy::__get` | 读取目标公开或虚拟属性，缺少时回退到代理辅助变量 |
| `myProxy::propertyReference` | 显式取得目标属性引用，用于公开数组的追加或原地修改 |
| `myProxy::__set` | 写入目标公开或虚拟属性，否则写入 myTrait 辅助变量 |
| `myProxy::__isset` | 检查目标属性或代理辅助变量是否已设置且非 null |
| `myProxy::__unset` | 移除目标公开或虚拟属性，并移除同名代理辅助变量 |
| `myProxy::__call` | 转发方法调用，显式别名优先于目标的普通及魔术方法 |
| `myProxy::__destruct` | 释放目标及辅助变量/扩展方法引用，避免析构时再次触发属性转发 |
| `myProxy::regAlias` | 合并并校验方法别名，检查通过后一次性替换别名表 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myProxy::init

功能说明：初始化代理目标，成功后才替换原实例

类名模式依次采用构造函数或无构造函数时的 init()，不会重复初始化。
现有对象模式仅保存引用，不接受额外初始化参数；额外参数通过 func_get_args()
获取，以保持方法签名和子类支持。

```text
public function init($module = "");
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$module` | string\|object；可实例化类名或已初始化对象；默认空值不能创建目标（默认：""） |
| `...$args` | mixed；目标类构造/init的位置参数；直接传对象时不可附加（位置参数，由func_get_args读取） |

**返回值**：void 无返回值。

**异常与失败**：InvalidArgumentException 类不可用、无法实例化或无法接收提供的参数；Throwable 目标构造或 init() 抛出的异常原样传递

调用形式：

```php
// $instance为已初始化的myProxy实例。
$result = $instance->init($module);
```

定位：`lib/myProxy.class.php:93`。

### myProxy::__get

功能说明：读取目标公开或虚拟属性，缺少时回退到代理辅助变量

```text
public function __get($para);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$para` | string；属性名；值为 null 的已存在公开属性仍交由目标读取 |

**返回值**：mixed 目标属性或 myTrait 辅助变量值；本方法不按引用返回

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myProxy.class.php:119`。

### myProxy::propertyReference

功能说明：显式取得目标属性引用，用于公开数组的追加或原地修改

不改变旧 __get() 的非引用签名。虚拟属性只有在目标 __get() 声明为引用返回时
才允许使用；普通 getter 的返回值不能据此获得可写引用。

```text
public function &propertyReference($para);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$para` | string；目标公开属性或支持引用返回的虚拟属性名 |

**返回值**：mixed 目标属性的引用；调用方使用 $value =& $proxy->propertyReference(...)

**异常与失败**：LogicException 没有可用目标、属性不可公开访问或 getter 不支持引用

调用形式：

```php
// $instance为已初始化的myProxy实例。
$result = $instance->propertyReference($para);
```

定位：`lib/myProxy.class.php:134`。

### myProxy::__set

功能说明：写入目标公开或虚拟属性，否则写入 myTrait 辅助变量

```text
public function __set($para, $value);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$para` | string；属性名 |
| `$value` | mixed；待赋值内容；目标 setter 自行负责验证 |

**返回值**：void 无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myProxy.class.php:149`。

### myProxy::__isset

功能说明：检查目标属性或代理辅助变量是否已设置且非 null

```text
public function __isset($para);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$para` | string；属性名；目标虚拟属性遵循其 __isset() 的语义 |

**返回值**：bool 遵循 PHP isset 约定，null 不视为已设置

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myProxy.class.php:160`。

### myProxy::__unset

功能说明：移除目标公开或虚拟属性，并移除同名代理辅助变量

```text
public function __unset($para);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$para` | string；属性名；不直接访问目标的受保护成员 |

**返回值**：void 无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myProxy.class.php:170`。

### myProxy::__call

功能说明：转发方法调用，显式别名优先于目标的普通及魔术方法

```text
public function __call($func, array $args);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$func` | string；调用的方法名或已登记别名 |
| `$args` | array；按原顺序传递的参数列表 |

**返回值**：mixed 目标方法或 myTrait 扩展方法的执行结果

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myProxy.class.php:182`。

### myProxy::__destruct

功能说明：释放目标及辅助变量/扩展方法引用，避免析构时再次触发属性转发

参数说明：无显式参数。

```text
public function __destruct();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 初始化或释放资源，无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myProxy.class.php:198`。

### myProxy::regAlias

功能说明：合并并校验方法别名，检查通过后一次性替换别名表

允许多级别名，但不允许自引用或循环；验证失败时保留原表。

```text
public function regAlias($alias = array());
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$alias` | array；非空字符串别名到非空目标方法名的映射；空数组仅返回当前表（默认：array()） |

**返回值**：array 合并后的完整别名表

**异常与失败**：InvalidArgumentException 映射类型或名称非法，或存在循环别名

调用形式：

```php
// $instance为已初始化的myProxy实例。
$result = $instance->regAlias($alias);
```

定位：`lib/myProxy.class.php:211`。
