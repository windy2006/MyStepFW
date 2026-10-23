# myPacker：框架包打包与安装

修订日期：2026-10-23。功能详解。

## 职责与调用位置

先明确包根、排除项及发布目标。安装应使用统一受管流程，避免直接覆盖在线配置和业务数据。

源码：`lib/myPacker.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
$packer = new myPacker($targetDirectory, $packageFile);
$packer->setLimits(64 * 1024 * 1024, 2000);
$ok = $packer->unpack(false);
$report = $packer->result();
// false保留源包；必须按返回及结果检查失败，不用固定最小包大小判断。
```

## 声明与类职责

### myPacker

功能说明：打包/解包自定义归档格式，提供体积/条目预算、字符集转换和暂存回滚发布。

使用说明：
  1. 通过pack_dir/pack_file选择目录和包文件；不是Composer或JS打包器。
  2. 解包接受受信包来源，并对成员路径/资源预算进行检查。
  3. 与myArchive共同完成发布；历史包格式及编码规则按方法说明使用。

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
protected
        $file_count = 0,
        $file_ignore = array(),        // ignore these files when packing
        $file_list = array(),        // only files in the list will be pack
        $pack_file = 'pack.bin',    // the file name of packed file
        $pack_dir = './',            // the directory of pack or unpack to
        $pack_fp = null,
        $pack_result = array(),
        $separator = '|',
        $charset = array();
```

```text
protected $max_bytes = 268435456, $max_entries = 10000, $raw_bytes = 0, $entry_count = 0;
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myPacker::setLimits` | 设置自定义归档的未压缩字节及成员数量上限 |
| `myPacker::init` | 初始化类变量 |
| `myPacker::addIgnore` | 添加排除文件 |
| `myPacker::setCharset` | 设定字符集相关变量 |
| `myPacker::addFile` | 添加需打包文件（如设置则只处理已添加文件，否则为目录下全部文件） |
| `myPacker::packFileList` | 打包已添加文件 |
| `myPacker::packFile` | 打包文件或目录 |
| `myPacker::write` | 累计归档写入预算并完整写入数据，超限或短写抛异常 |
| `myPacker::unpackFile` | 解开文件包 |
| `myPacker::pack` | 打包文件 |
| `myPacker::unpack` | 解包文件 |
| `myPacker::result` | 返回操作结果 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myPacker::setLimits

功能说明：设置自定义归档的未压缩字节及成员数量上限

```text
public function setLimits($bytes = 268435456, $entries = 10000);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$bytes` | int；最大允许处理的字节数，必须为正整数（默认：268435456） |
| `$entries` | int；归档允许的最大成员数量（默认：10000） |

**返回值**：$this 返回当前实例，便于连续设置

**异常与失败**：InvalidArgumentException 归档体积或条目上限不是正整数

调用形式：

```php
// $instance为已初始化的myPacker实例。
$result = $instance->setLimits($bytes, $entries);
```

定位：`lib/myPacker.class.php:77`。

### myPacker::init

功能说明：初始化类变量

```text
public function init($pack_dir = './', $pack_file = 'mypack.pkg', $separator = '|');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$pack_dir` | string；待打包或待解包的根目录（默认：'./'） |
| `$pack_file` | string；自定义归档文件路径（默认：'mypack.pkg'） |
| `$separator` | string；自定义归档格式的分隔标记（默认：'\|'） |

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的myPacker实例。
$result = $instance->init($pack_dir, $pack_file, $separator);
```

定位：`lib/myPacker.class.php:91`。

### myPacker::addIgnore

功能说明：添加排除文件

```text
public function addIgnore();
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `...$args` | string\|array；排除项列表，或一个包含排除项的数组（历史位置参数，由func_get_args读取） |

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的myPacker实例。
$result = $instance->addIgnore();
```

定位：`lib/myPacker.class.php:104`。

### myPacker::setCharset

功能说明：设定字符集相关变量

```text
public function setCharset($charset, $lng_type='', $file_ext='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$charset` | string；字符编码名称，例如UTF-8；是否自动检测由具体入口决定 |
| `$lng_type` | string；地区用语转换目标代号，空串不转换（默认：''） |
| `$file_ext` | string；需要转码的扩展名集合文本，内部按子串匹配，例如php,html,txt（默认：''） |

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的myPacker实例。
$result = $instance->setCharset($charset, $lng_type, $file_ext);
```

定位：`lib/myPacker.class.php:119`。

### myPacker::addFile

功能说明：添加需打包文件（如设置则只处理已添加文件，否则为目录下全部文件）

```text
public function addFile();
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `...$args` | string\|array；待打包的路径列表，或一个路径数组（历史位置参数，由func_get_args读取） |

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的myPacker实例。
$result = $instance->addFile();
```

定位：`lib/myPacker.class.php:131`。

### myPacker::packFileList

功能说明：打包已添加文件

参数说明：无显式参数。

```text
protected function packFileList();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：void 无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myPacker.class.php:144`。

### myPacker::packFile

功能说明：打包文件或目录

```text
protected function packFile($dir='./');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$dir` | string；目标目录（默认：'./'） |

**返回值**：void 无返回值。

**异常与失败**：RuntimeException 源路径越界、文件名不受格式支持、资源超限或I/O失败

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myPacker.class.php:157`。

### myPacker::write

功能说明：累计归档写入预算并完整写入数据，超限或短写抛异常

写入预算按当前归档已累计字节检查，异常由上层pack流程处理。

```text
protected function write($content);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$content` | string；待处理或输出的内容字节串 |

**返回值**：void 无返回值。

**异常与失败**：RuntimeException 累计写入超过上限或底层写入不完整

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myPacker.class.php:240`。

### myPacker::unpackFile

功能说明：解开文件包

```text
protected function unpackFile($outdir='./');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$outdir` | string；解包文件发布到的目标目录（默认：'./'） |

**返回值**：int|false 成功发布的归档成员数；校验、写入或发布失败为false

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myPacker.class.php:251`。

### myPacker::pack

功能说明：打包文件

参数说明：无显式参数。

```text
public function pack();
```

显式参数：无。若采用历史可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：bool 归档完整生成并发布为true；预算、输入或写入失败为false，详细状态见result

调用形式：

```php
// $instance为已初始化的myPacker实例。
$result = $instance->pack();
```

定位：`lib/myPacker.class.php:312`。

### myPacker::unpack

功能说明：解包文件

```text
public function unpack($remove = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$remove` | bool；操作成功后是否删除源归档文件（默认：true） |

**返回值**：bool 解包发布成功为true；remove启用时还要求源归档删除成功

调用形式：

```php
// $instance为已初始化的myPacker实例。
$result = $instance->unpack($remove);
```

定位：`lib/myPacker.class.php:343`。

### myPacker::result

功能说明：返回操作结果

```text
public function result($mode = 0);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$mode` | int；0返回HTML说明，非零返回原始操作记录数组（默认：0） |

**返回值**：array|string mode为0返回HTML摘要，非零返回操作记录数组；尚无结果时HTML模式返回空串

调用形式：

```php
// $instance为已初始化的myPacker实例。
$result = $instance->result($mode);
```

定位：`lib/myPacker.class.php:375`。
