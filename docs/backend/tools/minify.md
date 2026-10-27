# 资源压缩

## 本篇目标与调用场景

使用内容指纹与原子缓存，输入是受信资源。压缩不负责检测恶意脚本，也不保证任意第三方语法都可转换。

## 操作步骤与注意事项

先准备示例变量和目标资源，再执行方法。明确区分返回内容、直接输出、落盘或网络传输；后端PHP代码不要作为JavaScript放进浏览器脚本。完整方法参数见对应公共类参考页。

## 主要方法：参数、返回和失败约定

### myMinify::init

功能说明：初始化类变量

```text
public function init($mode = 'js', $cache = '', $renew = false);
```

| 参数说明 |
| --- |
| string $mode js使用JSMin，其他值按CSS模式处理（默认：'js'） |
| string $cache 压缩结果缓存文件路径，空串不写文件（默认：''） |
| bool $renew 是否清除已有结果和元数据后重新初始化（默认：false） |

**返回**：void 无返回值。

### myMinify::check

功能说明：检测是否已有缓存

```text
public function check($expires = 604800);
```

| 参数说明 |
| --- |
| int $expires 缓存有效秒数（默认：604800） |

**返回**：bool 源码/引擎指纹及有效期均匹配为true，否则为false；命中时载入压缩结果

### myMinify::add

功能说明：添加文件

```text
public function add($code);
```

| 参数说明 |
| --- |
| string $code 代码文本或状态代码，按对应入口解释 |

**返回**：$this 返回当前实例，支持链式调用。

### myMinify::get

功能说明：生成或复用压缩代码；JS统一使用JSMin，CSS使用CssMin。

```text
public function get($pack = false);
```

| 参数说明 |
| --- |
| bool $pack 参数，true/false均普通压缩，不执行eval打包（默认：false） |

**返回**：string 压缩代码

**异常**：Throwable 源文件、组件压缩或缓存发布失败

### myMinify::show

功能说明：输出压缩代码、缓存及内容类型响应头，并结束请求。

```text
public function show($pack = false);
```

| 参数说明 |
| --- |
| bool $pack 参数，不启用eval打包（默认：false） |

**返回**：void 无返回值。

### myMinify::minify

功能说明：压缩代码

```text
public static function minify($code, $mode = 'js');
```

| 参数说明 |
| --- |
| string $code 代码文本或状态代码，按对应入口解释 |
| string $mode js使用JSMin，其他值按CSS模式处理（默认：'js'） |

**返回**：string 按mode压缩后的JS/CSS文本，不写缓存也不发送响应头

## 明确调用示例

```php
$css = myMinify::minify('body { color: red; }', 'css');
$builder = new myMinify('js', $directory.'/bundle.js');
$builder->add('window.exampleValue = 1;');
$javascript = $builder->get();
```

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
