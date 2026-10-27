# 配置读取

## 本篇目标与调用场景

将配置从读取到后台编辑再到持久保存拆开，避免把每次读取变成写配置。

### 类型、合并和读取

PHP/JSON 加载和导出保留实际类型，包括 null、布尔值、数字、数字文本和列表，字符串`false`保持文本类型，也不把 null 读成空对象。数组与对象转换使用新容器，不修改传入对象；链式访问仍以对象形式提供，导出为数组时还原嵌套列表。

INI 沿用文本配置约定，对 true/false 读取；复杂嵌套及严格类型往返优先使用 PHP/JSON。公开的 recover() 辅助方法保留，类型保真的 PHP/JSON 转换不再调用它猜类型。

合并支持标量节点被配置分组替换，保留 null，并避免将外部对象引用直接挂入配置。克隆实例会复制嵌套配置。

```php
$value = $config->value('db.port', 3306); // 路径不存在时不创建节点
isset($config->section);
unset($config->temporary);
```

`$config->newSection->item = ...`用于自动创建分组；需要无副作用读取时使用 value()。

## 操作步骤与注意事项

PHP/JSON类型保真；INI按其文本约定恢复。value读取缺失路径不会创建节点。

## 配套完整示例

阅读[配置读取、合并和并发冲突](config-example.md)：包含准备步骤、完整代码、预期输出及实际应用中的替换位置。先跑通示例，再查本页逐方法参数。

## 主要方法：参数、返回和失败约定

### myConfig::init

功能说明：绑定配置文件、读取内容版本，并寻找相邻的设置结构定义

优先查找 config/default.php，再查找 construction/default.php；实际配置文件
不存在时以空分组初始化，首次保存仍参与版本冲突检查。

```text
public function init($file);
```

| 参数说明 |
| --- |
| string $file 配置路径，扩展名决定加载格式；内置 php/ini/json |

**返回**：void 无返回值。

**异常**：InvalidArgumentException 不支持的格式；UnexpectedValueException 配置根节点或结构定义不是数组/对象；RuntimeException 读取期间文件持续变动、消失或结构保存失败

### myConfig::get

功能说明：获取全部配置，可选择递归数组或当前内部对象

```text
public function get($arr = true);
```

| 参数说明 |
| --- |
| bool $arr true 返回递归转换的数组；false 返回内部配置对象引用（默认：true） |

**返回**：array|stdClass 全部设置；对象模式下修改嵌套属性会影响当前实例

### myConfig::value

功能说明：无副作用地读取点分隔配置路径，保留已存在的 null

调用示例：
    $port = $config->value('db.port', 3306); // 不创建缺失分组

```text
public function value($path, $default = null);
```

| 参数说明 |
| --- |
| string $path 例如 db.port；空字符串返回整个内部配置对象，点号作为分隔符 |
| mixed $default 任一路径段不存在时的返回值，默认 null（默认：null） |

**返回**：mixed 已存在的配置值或默认值；返回对象仍是内部对象，不是副本

**异常**：InvalidArgumentException 路径不是字符串

### myConfig::load

功能说明：按文件格式读取配置，并返回本次稳定读取的内容摘要

读取前后比较文件摘要，最多重试三次；PHP 在独立作用域 include，JSON 严格解码，
INI 保留类型恢复。首次发现文件不存在返回空配置，不自动保存或替换实例设置。

```text
public function load($file='', $return_object=false, &$snapshot = null);
```

| 参数说明 |
| --- |
| string $file 配置路径；空字符串使用实例绑定文件（默认：''） |
| bool $return_object true 将结果转换为分组对象；false 保留解析器结果（默认：false） |
| string\|null &$snapshot 引用参数；输出稳定读取内容的 SHA-256 摘要；初始文件不存在为 null（默认：null） |

**返回**：array|object 配置根节点，具体容器取决于格式及 return_object

**异常**：InvalidArgumentException 文件名类型或格式不支持；UnexpectedValueException 解析结果根节点不是数组或对象；JsonException JSON 内容非法；RuntimeException 文件在读取中消失或持续变化；Throwable 自定义解析器、初始化/反射目标或受信执行文件产生的异常继续传递

### myConfig::merge

功能说明：递归合并分组配置，同名标量覆盖，分组可替换原标量

输入数组/对象会复制为分组对象，避免把其嵌套引用直接挂入当前配置。

```text
public function merge($setting, &$org = null);
```

| 参数说明 |
| --- |
| array\|object\|string $setting 待覆盖的设置或配置文件路径；不存在的文件跳过 |
| object\|null &$org 合并目标，null 使用实例设置；引用参数供递归及指定目标使用（默认：null） |

**返回**：void 只更新内存，不保存文件或改变原文件的版本基准

**异常**：InvalidArgumentException 输入不是数组、对象或文件路径

## 明确调用示例

```php
// $file指向临时配置文件，内容为return ['web'=>['title'=>'原名']];
$config = new myConfig($file);
assert($config->value('web.title') === '原名');
$config->merge(['web' => ['title' => '新名']]);
$config->save('php');
assert((include $file)['web']['title'] === '新名');
```

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
