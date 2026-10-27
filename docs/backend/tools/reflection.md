# 类、方法及注释反射

## 本篇目标与调用场景

只对受信类或对象反射；用于说明和辅助调用，不把用户输入当待加载类名。

## 操作步骤与注意事项

先准备示例变量和目标资源，再执行方法。明确区分返回内容、直接输出、落盘或网络传输；后端PHP代码不要作为JavaScript放进浏览器脚本。完整方法参数见对应公共类参考页。

## 主要方法：参数、返回和失败约定

### myReflection::init

功能说明：获取对象基本信息

```text
public function init($para = '');
```

| 参数说明 |
| --- |
| string\|object $para 类/接口/trait名、函数名、扩展名或待反射对象（默认：''） |

**返回**：void 无返回值。

**异常**：ReflectionException 目标反射或底层调用产生的异常继续传递

### myReflection::info

功能说明：返回全部基本信息

参数说明：无显式参数。

```text
public function info();
```

**返回**：array 当前目标的反射元信息映射；尚未初始化时为空数组

### myReflection::export

功能说明：返回对象详细信息

参数说明：无显式参数。

```text
public function export();
```

**返回**：string 底层Reflection对象的字符串描述，不是可执行导出脚本

### myReflection::get

功能说明：按位置参数调用反射函数或构造普通类实例

```text
public function get();
```

| 参数说明 |
| --- |
| mixed ...$args 函数调用或类构造的位置参数（位置参数，由func_get_args读取） |

**返回**：mixed 函数的返回值或新类实例；未初始化为false，其他目标类别为null

**异常**：Throwable 自定义解析器、初始化/反射目标或受信执行文件产生的异常继续传递

### myReflection::getName

功能说明：获取对象名称

```text
public function getName($mode = false);
```

| 参数说明 |
| --- |
| bool $mode 目标位于命名空间时，true返回namespace/name数组，false返回完整名称（默认：false） |

**返回**：string|array 名称文本；命名空间目标且mode为true时返回namespace/name数组

### myReflection::getVariants

功能说明：获取对象所有变量及常量

参数说明：无显式参数。

```text
public function getVariants();
```

**返回**：array|null 含constant/variant的常量和默认属性映射；非类或未初始化为null

## 明确调用示例

```php
$reflection = new myReflection('myString');
$name = $reflection->getName();
$methods = $reflection->getFunc();
$comment = $reflection->getComment('convertCharset');
```

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
