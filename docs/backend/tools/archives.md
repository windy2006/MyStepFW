# 打包、暂存、解包和失败处理

修订日期：2026-10-23。适用于当前框架。

## 本篇目标与调用场景

先明确包根、排除项及发布目标。安装应使用统一受管流程，避免直接覆盖在线配置和业务数据。

## 操作步骤与注意事项

先准备示例变量和目标资源，再执行方法。明确区分返回内容、直接输出、落盘或网络传输；后端PHP代码不要作为JavaScript放进浏览器脚本。完整方法参数见对应公共类参考页。

## 主要方法：参数、返回和失败约定

### myPacker::setLimits

功能说明：设置自定义归档的未压缩字节及成员数量上限

```text
public function setLimits($bytes = 268435456, $entries = 10000);
```

| 参数说明 |
| --- |
| int $bytes 最大允许处理的字节数，必须为正整数（默认：268435456） |
| int $entries 归档允许的最大成员数量（默认：10000） |

**返回**：$this 返回当前实例，便于连续设置

**异常**：InvalidArgumentException 归档体积或条目上限不是正整数

### myPacker::init

功能说明：初始化类变量

```text
public function init($pack_dir = './', $pack_file = 'mypack.pkg', $separator = '|');
```

| 参数说明 |
| --- |
| string $pack_dir 待打包或待解包的根目录（默认：'./'） |
| string $pack_file 自定义归档文件路径（默认：'mypack.pkg'） |
| string $separator 自定义归档格式的分隔标记（默认：'\|'） |

**返回**：void 无返回值。

### myPacker::addIgnore

功能说明：添加排除文件

```text
public function addIgnore();
```

| 参数说明 |
| --- |
| string\|array ...$args 排除项列表，或一个包含排除项的数组（历史位置参数，由func_get_args读取） |

**返回**：void 无返回值。

### myPacker::setCharset

功能说明：设定字符集相关变量

```text
public function setCharset($charset, $lng_type='', $file_ext='');
```

| 参数说明 |
| --- |
| string $charset 字符编码名称，例如UTF-8；是否自动检测由具体入口决定 |
| string $lng_type 地区用语转换目标代号，空串不转换（默认：''） |
| string $file_ext 需要转码的扩展名集合文本，内部按子串匹配，例如php,html,txt（默认：''） |

**返回**：void 无返回值。

### myPacker::addFile

功能说明：添加需打包文件（如设置则只处理已添加文件，否则为目录下全部文件）

```text
public function addFile();
```

| 参数说明 |
| --- |
| string\|array ...$args 待打包的路径列表，或一个路径数组（历史位置参数，由func_get_args读取） |

**返回**：void 无返回值。

### myPacker::pack

功能说明：打包文件

参数说明：无显式参数。

```text
public function pack();
```

**返回**：bool 归档完整生成并发布为true；预算、输入或写入失败为false，详细状态见result

## 明确调用示例

```php
$packer = new myPacker($targetDirectory, $packageFile);
$packer->setLimits(64 * 1024 * 1024, 2000);
$ok = $packer->unpack(false);
$report = $packer->result();
// false保留源包；必须按返回及结果检查失败，不用固定最小包大小判断。
```

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
