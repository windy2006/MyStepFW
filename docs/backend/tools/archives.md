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

## 发布排除规则（2.3.1）

GitHub 的 `.gitignore` 与框架目录中的 `ignore/allow` 作用不同。前者防止本机文件进入 Git；后者控制 `myPacker` 打包、manager 整站安装包和文件校验清单。两套规则都要维护，robots.txt 不参与文件发布。

`ignore/allow` 每行写一个**当前目录直接成员**，支持首尾空白、UTF-8 BOM、空行和整行 `#` 注释。名称精确匹配，不支持 Git 的通配符或 `!` 取反，也不接受跨目录路径。`assets` 匹配同名文件或目录；`assets/` 只匹配目录，不能用它排除一个叫 `assets` 的文件。需要过滤子目录时，在子目录自己的 `ignore` 中列出成员。

```text
# 当前目录的 ignore 示例
private/
secret.php
```

```text
# 同目录 allow 示例；非空时只收录以下成员
info.php
config.php
config/
asset/
```

白名单和排除表同时存在时，成员先通过 allow，再接受 ignore；排除优先。规则文件恰好为零字节时，沿用旧约定：整目录不扫描。只含注释或空白表示没有该类约束。不要用空 allow 表示“全部允许”，应删除该文件或写一行注释。规则文件不能是符号链接。

```php
// 以下规则解析和判断已由打包器与 manager 共用，普通应用通常无需直接调用。
$rules = myPacker::directoryRules(ROOT . 'plugin/example/');
$include = myPacker::allowsEntry('asset', true, $rules);
// true 表示该成员是目录；路径边界、符号链接及发布脱敏仍由扫描器检查。
```

网站根 `ignore` 排除上传、缓存、本机工具、测试及维护脚本；`docs/ignore` 排除文档备份。manager 另有系统默认保护，保留 `lib/cache`、`lib/session` 的源码，以及应用目录内的公开 `asset/files`。`app/test` 属于演示应用，不按根目录测试目录处理；是否打包由 `app/allow` 决定。旧远端清单里已排除的开发文件，也不会被误报为待恢复的缺失文件。
