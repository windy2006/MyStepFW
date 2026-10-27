# 文件路径、读写和流式响应

## 本篇目标与调用场景

只在受控目录操作；resolveWithin仅返回根内已存在文件；网络方法保持TLS验证，超时及响应大小由业务配套。

## 操作步骤与注意事项

先准备示例变量和目标资源，再执行方法。明确区分返回内容、直接输出、落盘或网络传输；后端PHP代码不要作为JavaScript放进浏览器脚本。完整方法参数见对应公共类参考页。

## 配套完整示例

阅读[文件边界与文本](files-example.md)：包含准备步骤、完整代码、预期输出及实际应用中的替换位置。先跑通示例，再查本页逐方法参数。

## 主要方法：参数、返回和失败约定

### myFile::buildContext

功能说明：构建远程连接配置

资源所有权：返回的是流上下文，不是已打开的网络连接；无需把它作为文件流传给fclose。

```text
public static function buildContext($url, $headers=array());
```

| 参数说明 |
| --- |
| string $url 目标URL或需要处理的链接 |
| array $headers HTTP头映射/列表；流上下文还支持method、data和append选项（默认：array()） |

**返回**：resource 新建的stream-context资源，可交给支持context的流函数

**异常**：InvalidArgumentException 请求头名称或值包含CR/LF

### myFile::rootPath

功能说明：获取网站根的绝对路径

```text
public static function rootPath($mode = false);
```

| 参数说明 |
| --- |
| bool $mode true使用当前操作系统分隔符，false统一使用/（默认：false） |

**返回**：string 网站根目录的绝对路径，使用所选分隔符

### myFile::resolveWithin

功能说明：定位受信根目录内已存在的文件，拒绝路径穿越和符号链接越界

```text
public static function resolveWithin($root, $relative);
```

| 参数说明 |
| --- |
| string $root 操作限制在其中的受信根目录 |
| string $relative 相对于受信根目录的待解析路径 |

**返回**：string|false 根目录内真实文件路径；不存在、越界或非法输入为false

### myFile::realPath

功能说明：获取文件真实绝对路径或相对于根的路径

声明ROOT时相对路径固定从网站根解析，与工作目录和调用文件无关；独立使用类库时保留规则。本入口不是访问权限检查。读取公开文件或解包时使用resolveWithin/extractionPath。

```text
public static function realPath($file, $relative = false, $mode = false);
```

| 参数说明 |
| --- |
| string $file 目标文件路径 |
| bool $relative true返回相对于站点根的路径，false返回完整路径（默认：false） |
| bool $mode true使用操作系统分隔符，false使用统一/分隔符（默认：false） |

**返回**：string 规范化后的绝对路径或站点相对路径；不保证文件存在

### myFile::mkdir

功能说明：建立目录，带递归和容错

```text
public static function mkdir($dir);
```

| 参数说明 |
| --- |
| string $dir 目标目录 |

**返回**：bool 目录已存在或递归创建成功为true，创建失败为false

### myFile::getMime

功能说明：获取文件类型

支持行为：未知扩展名可能直接作为返回文本，而非application/octet-stream；此入口不能仅靠返回值证明文件真实内容格式。

```text
public static function getMime($file='', $use_finfo = false);
```

| 参数说明 |
| --- |
| string $file 目标文件路径（默认：''） |
| bool $use_finfo 是否优先使用fileinfo检测实际MIME类型（默认：false） |

**返回**：string|false 已知扩展名返回MIME文本；未知扩展名沿用原扩展名；不是普通文件或finfo失败为false

## 明确调用示例

```php
// $directory是测试进程独占的临时目录。
$file = $directory.'/note.txt';
myFile::saveFile($file, 'Hello');
assert(myFile::getLocal($file) === 'Hello');
assert(myFile::resolveWithin($directory, 'note.txt') === realpath($file));
assert(myFile::resolveWithin($directory, '../outside.txt') === false);
assert((int)myFile::getByte('8M') === 8388608);
```

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
