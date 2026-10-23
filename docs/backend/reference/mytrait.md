# myTrait：类的通用扩展

修订日期：2026-10-23。功能详解。

## 职责与调用位置

addMethod绑定受信回调；别名只改变方法调度，不建立HTTP接口或权限。

源码：`lib/myTrait.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
class HelloExtension { use myTrait; }
$object = new HelloExtension();
$object->addMethod(function ($value) { return strtoupper($value); }, 'upper');
assert($object->upper('hello') === 'HELLO');
```

## 声明与类职责

### myTrait

功能说明：提供辅助属性、扩展方法登记、别名解析和可恢复调用状态。

使用说明：
  1. 由myProxy等类使用；静态/实例扩展和别名按登记范围分离。
  2. 依赖声明元数据的调用应通过对应入口，避免把魔术方法存在当作实际能力。

补充说明：
通用魔术扩展：辅助属性、实例方法、类方法别名及对象状态展示

    $object->addMethod(function ($v) { return $this->value + $v; }, 'sum');
    ClassName::regAlias(['short' => 'fullName']); // 多级别名会先校验循环
    $fresh = $object->instance;                 // 新建实际调用类，旧 instatnce 仍兼容

扩展方法优先于历史全局函数回退，别名优先于扩展方法；全局函数回退为兼容保留，
不能把未知方法名直接视为 API 白名单。序列化只保存实例状态，恢复时不重跑构造；
闭包及资源仍受 PHP 原生序列化限制，含这些内容的对象不承诺可持久化。

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
protected $methods = [];
```

```text
protected $vars = [];
```

```text
protected static $extension_aliases = [];
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myTrait::__get` | 读取辅助属性，兼容旧实例工厂拼写并补充正确的 instance 名称 |
| `myTrait::__set` | 写入辅助属性，不以外部魔术调用修改受保护/私有成员 |
| `myTrait::__destruct` | 释放扩展方法及辅助属性引用，其他成员由 PHP 正常释放 |
| `myTrait::__isset` | 按 PHP isset 约定检查辅助属性 |
| `myTrait::__unset` | 移除辅助属性，不删除对象自身受保护成员 |
| `myTrait::extensionAliases` | 汇总实例/静态默认别名与实际类的扩展别名，统一为小写索引 |
| `myTrait::resolveExtensionMethod` | 解析多级别名并拒绝循环，避免魔术分发无限递归 |
| `myTrait::__call` | 按别名、实例扩展、历史全局函数回退的顺序处理未知方法 |
| `myTrait::__callStatic` | 解析静态别名或历史全局函数回退，不创建实例、不调用实例方法 |
| `myTrait::__sleep` | 返回全部实际实例属性，包含父类私有状态，不包含静态表 |
| `myTrait::__wakeup` | 恢复序列化状态，不重新调用构造函数或 init() |
| `myTrait::__toString` | 兼容旧对象文本快照，无法序列化调试状态时回退类名 |
| `myTrait::__invoke` | 兼容对象函数式调用：文本生成扰动摘要，数值生成确定整数，映射批量赋值 |
| `myTrait::__set_state` | 从 var_export() 状态重建实际调用类，不触发构造及初始化副作用 |
| `myTrait::__debugInfo` | 返回可供 var_dump() 使用的实例快照，不读取静态属性或再次触发魔术 getter |
| `myTrait::addMethod` | 登记实例扩展方法，支持闭包、函数名、方法数组及可调用对象 |
| `myTrait::regAlias` | 原子合并类方法别名，支持未声明静态 func_alias 的普通 trait 使用类 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myTrait::__get

功能说明：读取辅助属性，兼容旧实例工厂拼写并补充正确的 instance 名称

```text
public function __get($para);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$para` | string；属性名；instance/instatnce 新建实际调用类，不复制当前状态 |

**返回值**：mixed 辅助属性值（保留 null）或新实例；不存在时为 null

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTrait.class.php:72`。

### myTrait::__set

功能说明：写入辅助属性，不以外部魔术调用修改受保护/私有成员

```text
public function __set($para, $value);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$para` | string；辅助属性名 |
| `$value` | mixed；保存的值 |

**返回值**：void 无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTrait.class.php:84`。

### myTrait::__destruct

功能说明：释放扩展方法及辅助属性引用，其他成员由 PHP 正常释放

参数说明：无显式参数。

```text
public function __destruct();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 初始化或释放资源，无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTrait.class.php:93`。

### myTrait::__isset

功能说明：按 PHP isset 约定检查辅助属性

```text
public function __isset($para);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$para` | string；属性名；不向外暴露受保护成员的存在状态 |

**返回值**：bool 辅助属性存在且非 null 时为 true

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTrait.class.php:101`。

### myTrait::__unset

功能说明：移除辅助属性，不删除对象自身受保护成员

```text
public function __unset($para);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$para` | string；辅助属性名 |

**返回值**：void 无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTrait.class.php:109`。

### myTrait::extensionAliases

功能说明：汇总实例/静态默认别名与实际类的扩展别名，统一为小写索引

```text
protected static function extensionAliases($instance = null);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$instance` | object\|null；实例调用时传入对象，静态调用为 null（默认：null） |

**返回值**：array 方法名到目标方法名的映射，不改写静态表为实例属性

**异常与失败**：UnexpectedValueException 已声明 func_alias 不是数组

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTrait.class.php:118`。

### myTrait::resolveExtensionMethod

功能说明：解析多级别名并拒绝循环，避免魔术分发无限递归

```text
protected static function resolveExtensionMethod($func, array $aliases);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$func` | string；原始方法名 |
| `$aliases` | array；小写索引别名表 |

**返回值**：string 最终目标方法名；没有别名时保留原名称

**异常与失败**：LogicException 别名表存在运行时引入的循环或非法目标

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTrait.class.php:141`。

### myTrait::__call

功能说明：按别名、实例扩展、历史全局函数回退的顺序处理未知方法

```text
public function __call($func, array $args);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$func` | string；方法名；别名和新登记扩展按大小写不敏感匹配 |
| `$args` | array；按原顺序传递的位置参数 |

**返回值**：mixed 目标结果；没有可调用目标时沿用旧行为返回 null

**异常与失败**：LogicException 别名循环；业务回调异常继续传递

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTrait.class.php:159`。

### myTrait::__callStatic

功能说明：解析静态别名或历史全局函数回退，不创建实例、不调用实例方法

```text
public static function __callStatic($func, $args);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$func` | string；静态方法名或别名 |
| `$args` | array；位置参数 |

**返回值**：mixed 静态方法/全局函数结果；没有目标时为 null

**异常与失败**：BadMethodCallException 静态别名指向实例方法或不可访问的方法；LogicException 运行时别名表包含循环

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTrait.class.php:180`。

### myTrait::__sleep

功能说明：返回全部实际实例属性，包含父类私有状态，不包含静态表

参数说明：无显式参数。

```text
public function __sleep();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：array 原始属性键列表；闭包仍由原生 serialize() 明确拒绝，不静默丢弃

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTrait.class.php:199`。

### myTrait::__wakeup

功能说明：恢复序列化状态，不重新调用构造函数或 init()

参数说明：无显式参数。

```text
public function __wakeup();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 需要恢复连接等外部资源的类应自行覆盖本方法

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTrait.class.php:208`。

### myTrait::__toString

功能说明：兼容旧对象文本快照，无法序列化调试状态时回退类名

参数说明：无显式参数。

```text
public function __toString();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string 序列化的 name/vars/methods 信息，或类名；不会因闭包抛出转换异常

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTrait.class.php:217`。

### myTrait::__invoke

功能说明：兼容对象函数式调用：文本生成扰动摘要，数值生成确定整数，映射批量赋值

```text
public function __invoke($para);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$para` | mixed；字符串、数字、数组/对象；其他值返回调用栈（不含参数） |

**返回值**：string|int|bool|array 对应计算结果；本方法不重置或消耗 mt_rand 全局状态

映射批量赋值保留当前作用域可写成员的旧语义，只接受受信字段；本类不提供权限隔离。
文本结果不是认证令牌；数值结果的算法与旧 mt_srand 版本不同，不用于持久标识。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTrait.class.php:231`。

### myTrait::__set_state

功能说明：从 var_export() 状态重建实际调用类，不触发构造及初始化副作用

```text
public static function __set_state($para_arr);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$para_arr` | array；实例属性映射；静态属性不恢复，声明属性通过反射写入 |

**返回值**：object 当前实际调用类的新实例

**异常与失败**：Throwable 类不可实例化或属性类型不匹配等异常继续传递

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTrait.class.php:249`。

### myTrait::__debugInfo

功能说明：返回可供 var_dump() 使用的实例快照，不读取静态属性或再次触发魔术 getter

参数说明：无显式参数。

```text
public function __debugInfo();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：array 包含 name、当前作用域可见的实例 vars、可见 methods；不要记录秘密数据

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTrait.class.php:269`。

### myTrait::addMethod

功能说明：登记实例扩展方法，支持闭包、函数名、方法数组及可调用对象

未绑定的非静态普通闭包绑定到当前实例及其作用域；静态/已绑定闭包及其他回调保持身份，
不把函数名/数组传入 Closure::bind()，也不强制改绑其他对象的方法。


回调参数由扩展方法自身定义，__call按调用位置顺序传入并返回原值。普通未绑定闭包可读取当前$this，其他可调用对象保持原绑定。

```text
public function addMethod($func, $name='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$func` | callable；新方法实现，非静态普通闭包可使用当前 $this |
| `$name` | string；扩展名称；函数名/方法数组可省略，闭包/可调用对象须明确命名（默认：''） |

**返回值**：$this 当前对象，登记失败不改写已有方法

**异常与失败**：InvalidArgumentException 不可调用、名称非法

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTrait.class.php:285`。

### myTrait::regAlias

功能说明：原子合并类方法别名，支持未声明静态 func_alias 的普通 trait 使用类

```text
public static function regAlias($alias = []);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$alias` | array；别名到目标方法名的非空字符串映射；空数组读取当前别名（默认：[]） |

**返回值**：array 合并后的完整别名表；原有静态表继续同步，否则保存到实际类覆盖表

**异常与失败**：InvalidArgumentException 映射或名称非法、存在循环；失败保留原表

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myTrait.class.php:316`。

