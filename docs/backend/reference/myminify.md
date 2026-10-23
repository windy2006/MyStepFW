# myMinify：JS/CSS整合

修订日期：2026-10-23。功能详解。

## 职责与调用位置

使用内容指纹与原子缓存，输入是受信资源。压缩不负责检测恶意脚本，也不保证任意第三方语法都可转换。

源码：`lib/myMinify.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
$css = myMinify::minify('body { color: red; }', 'css');
$builder = new myMinify('js', $directory.'/bundle.js');
$builder->add('window.exampleValue = 1;');
$javascript = $builder->get();
```

## 声明与类职责

### myMinify

功能说明：使用JSMin/CssMin压缩合并输入，按源码/引擎摘要复用缓存并原子发布。

使用说明：
  1. add可接收同类型文件或源码，get返回结果，show发送响应并结束请求。
  2. 旧pack参数保留调用兼容，true/false均采用普通压缩。
  3. 组件或本文件内容变化会使引擎指纹失效，调用方需按原约定保留源输入。

补充说明：
生成js或css的压缩代码，使用JSMin/CssMin；旧pack参数仅保留调用兼容。
不再依赖JavaScriptPacker或生成eval打包代码，组件变化会使缓存指纹失效。

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
protected
        $mode = null,
        $cache = '',
        $content = '',
        $result = '';
```

```text
protected $inputs = [];
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myMinify::init` | 初始化类变量 |
| `myMinify::check` | 检测是否已有缓存 |
| `myMinify::add` | 添加文件 |
| `myMinify::source` | 读取当前输入并记录文件摘要；JS片段之间插入分号，避免拼接改变表达式边界。 |
| `myMinify::signature` | 计算源码和当前模式压缩引擎的指纹；只加载实际使用的组件。 |
| `myMinify::atomicWrite` | 在目标目录写入临时文件并原子发布代码或元数据。 |
| `myMinify::get` | 生成或复用压缩代码；JS统一使用JSMin，CSS使用CssMin。 |
| `myMinify::show` | 输出压缩代码、缓存及内容类型响应头，并结束请求。 |
| `myMinify::minify` | 压缩代码 |
| `myMinify::__toString` | 返回当前已有压缩结果，不主动触发生成 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myMinify::init

功能说明：初始化类变量

```text
public function init($mode = 'js', $cache = '', $renew = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$mode` | string；js使用JSMin，其他值按CSS模式处理（默认：'js'） |
| `$cache` | string；压缩结果缓存文件路径，空串不写文件（默认：''） |
| `$renew` | bool；是否清除已有结果和元数据后重新初始化（默认：false） |

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的myMinify实例。
$result = $instance->init($mode, $cache, $renew);
```

定位：`lib/myMinify.class.php:73`。

### myMinify::check

功能说明：检测是否已有缓存

```text
public function check($expires = 604800);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$expires` | int；缓存有效秒数（默认：604800） |

**返回值**：bool 源码/引擎指纹及有效期均匹配为true，否则为false；命中时载入压缩结果

调用形式：

```php
// $instance为已初始化的myMinify实例。
$result = $instance->check($expires);
```

定位：`lib/myMinify.class.php:88`。

### myMinify::add

功能说明：添加文件

```text
public function add($code);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$code` | string；代码文本或状态代码，按对应入口解释 |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myMinify实例。
$result = $instance->add($code);
```

定位：`lib/myMinify.class.php:108`。

### myMinify::source

功能说明：读取当前输入并记录文件摘要；JS片段之间插入分号，避免拼接改变表达式边界。

参数说明：无显式参数。

```text
protected function source();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：array 源码及文件路径到SHA-256摘要的映射

**异常与失败**：RuntimeException 输入文件已丢失或读取失败

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myMinify.class.php:124`。

### myMinify::signature

功能说明：计算源码和当前模式压缩引擎的指纹；只加载实际使用的组件。

```text
protected function signature($code, $pack);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$code` | string；待压缩源码，空串用于引擎指纹 |
| `$pack` | bool；历史兼容参数，已不影响输出或缓存身份 |

**返回值**：string SHA-256摘要

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myMinify.class.php:145`。

### myMinify::atomicWrite

功能说明：在目标目录写入临时文件并原子发布代码或元数据。

```text
protected function atomicWrite($path, $content);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$path` | string；目标文件路径 |
| `$content` | string；完整文件内容 |

**返回值**：void 无返回值。

**异常与失败**：RuntimeException 临时文件、写入、权限或替换失败

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myMinify.class.php:161`。

### myMinify::get

功能说明：生成或复用压缩代码；JS统一使用JSMin，CSS使用CssMin。

```text
public function get($pack = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$pack` | bool；历史兼容参数，true/false均普通压缩，不再执行旧eval打包（默认：false） |

**返回值**：string 压缩代码

**异常与失败**：Throwable 源文件、组件压缩或缓存发布失败

调用形式：

```php
// $instance为已初始化的myMinify实例。
$result = $instance->get($pack);
```

定位：`lib/myMinify.class.php:176`。

### myMinify::show

功能说明：输出压缩代码、缓存及内容类型响应头，并结束请求。

```text
public function show($pack = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$pack` | bool；历史兼容参数，不再启用eval打包（默认：false） |

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的myMinify实例。
$result = $instance->show($pack);
```

定位：`lib/myMinify.class.php:204`。

### myMinify::minify

功能说明：压缩代码

```text
public static function minify($code, $mode = 'js');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$code` | string；代码文本或状态代码，按对应入口解释 |
| `$mode` | string；js使用JSMin，其他值按CSS模式处理（默认：'js'） |

**返回值**：string 按mode压缩后的JS/CSS文本，不写缓存也不发送响应头

调用形式：

```php
$result = myMinify::minify($code, $mode);
```

定位：`lib/myMinify.class.php:224`。

### myMinify::__toString

功能说明：返回当前已有压缩结果，不主动触发生成

参数说明：无显式参数。

```text
public function __toString();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string 当前保存的压缩结果

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myMinify.class.php:258`。
