# 对象代理

## 本篇目标与调用场景

真实构造只运行一次，无构造才使用init。引用属性修改需propertyReference，代理不提供权限隔离。

## 操作步骤与注意事项

先准备示例变量和目标资源，再执行方法。明确区分返回内容、直接输出、落盘或网络传输；后端PHP代码不要作为JavaScript放进浏览器脚本。完整方法参数见对应公共类参考页。

## 主要方法：参数、返回和失败约定

### myProxy::init

功能说明：初始化代理目标，成功后才替换原实例

类名模式依次采用构造函数或无构造函数时的 init()，不会重复初始化。
现有对象模式仅保存引用，不接受额外初始化参数；额外参数通过 func_get_args()
获取，以保持方法签名和子类支持。

```text
public function init($module = "");
```

| 参数说明 |
| --- |
| string\|object $module 可实例化类名或已初始化对象；默认空值不能创建目标（默认：""） |
| mixed ...$args 目标类构造/init的位置参数；直接传对象时不可附加（位置参数，由func_get_args读取） |

**返回**：void 无返回值。

**异常**：InvalidArgumentException 类不可用、无法实例化或无法接收提供的参数；Throwable 目标构造或 init() 抛出的异常原样传递

### myProxy::propertyReference

功能说明：显式取得目标属性引用，用于公开数组的追加或原地修改

不改变旧 __get() 的非引用签名。虚拟属性只有在目标 __get() 声明为引用返回时
才允许使用；普通 getter 的返回值不能据此获得可写引用。

```text
public function &propertyReference($para);
```

| 参数说明 |
| --- |
| string $para 目标公开属性或支持引用返回的虚拟属性名 |

**返回**：mixed 目标属性的引用；调用方使用 $value =& $proxy->propertyReference(...)

**异常**：LogicException 没有可用目标、属性不可公开访问或 getter 不支持引用

### myProxy::regAlias

功能说明：合并并校验方法别名，检查通过后一次性替换别名表

允许多级别名，但不允许自引用或循环；验证失败时保留原表。

```text
public function regAlias($alias = array());
```

| 参数说明 |
| --- |
| array $alias 非空字符串别名到非空目标方法名的映射；空数组仅返回当前表（默认：array()） |

**返回**：array 合并后的完整别名表

**异常**：InvalidArgumentException 映射类型或名称非法，或存在循环别名

## 明确调用示例

```php
class HelloTarget { public $items = []; public function hello($name) { return 'Hi '.$name; } }
class HelloProxy extends myProxy {}
$proxy = new HelloProxy(new HelloTarget());
assert($proxy->hello('A') === 'Hi A');
$items =& $proxy->propertyReference('items');
$items[] = 'one';
assert($proxy->items === ['one']);
```

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
