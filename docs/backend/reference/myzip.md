# myZip：ZIP归档

修订日期：2026-10-23。功能详解。

## 职责与调用位置

构造器可打开已有ZIP；解包前设置条目和总字节预算；失败时保留原始包便于检查。

源码：`lib/myZip.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
$zip = new myZip($zipFile);
$zip->setLimits(64 * 1024 * 1024, 2000);
$zip->unzip($targetDirectory, false);
// false表示不因成功解包删除源归档；后续检查目标文件及业务版本。
```

## 声明与类职责

### myZip

功能说明：扩展ZipArchive，提供目录收集、原子生成ZIP、预算校验及事务式解包。

使用说明：
  1. 需要ZipArchive扩展；构造时选择ZIP路径及成员相对根目录。
  2. zip接收文件/目录列表；unzip先校验成员再通过myArchive暂存发布。
  3. 拒绝路径越界、符号链接及超预算归档；不会为方便解包而放宽边界。

使用示例：
    $zip = new myZip($archive, $sourceRoot); $ok = $zip->zip([$file, $directory]);
    $ok = (new myZip($archive))->unzip($destination);

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
protected $file, $root_fix, $working_file;
```

```text
protected $max_bytes = 268435456, $max_entries = 10000;
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myZip::setLimits` | 设置ZIP解包的总未压缩字节及成员数量上限 |
| `myZip::__construct` | 登记ZIP路径及成员相对根目录，延迟到实际操作时打开归档 |
| `myZip::memberName` | 按相对根目录生成ZIP成员名，根外路径使用文件名 |
| `myZip::addDir` | 递归向已打开ZIP加入目录成员，跳过链接及内部暂存文件 |
| `myZip::zip` | 收集指定文件/目录，在临时ZIP中构建成功后原子发布 |
| `myZip::unzip` | 验证ZIP路径、大小及CRC，在暂存目录解包后统一发布 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myZip::setLimits

功能说明：设置ZIP解包的总未压缩字节及成员数量上限

```text
public function setLimits($bytes = 268435456, $entries = 10000);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$bytes` | int；最大允许处理的字节数，必须为正整数（默认：268435456） |
| `$entries` | int；归档允许的最大成员数量（默认：10000） |

**返回值**：$this 返回当前ZIP实例

**异常与失败**：InvalidArgumentException 归档体积或条目上限不是正整数

调用形式：

```php
// $instance为已初始化的myZip实例。
$result = $instance->setLimits($bytes, $entries);
```

定位：`lib/myZip.class.php:66`。

### myZip::__construct

功能说明：登记ZIP路径及成员相对根目录，延迟到实际操作时打开归档

```text
public function __construct($zip_file = '', $root_fix = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$zip_file` | string；需要创建或读取的ZIP文件路径（默认：''） |
| `$root_fix` | string；ZIP成员名使用的相对根目录，空串采用默认根目录（默认：''） |

**返回值**：void 初始化或释放资源，无返回值。

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myZip.class.php:79`。

### myZip::memberName

功能说明：按相对根目录生成ZIP成员名，根外路径使用文件名

```text
protected function memberName($path);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$path` | string；目标目录或路径 |

**返回值**：string 写入ZIP使用的相对成员名

调用位置：由框架内部、PHP引擎或对应扩展协议触发。扩展时保留可见性、引用参数和父类／接口签名，不通过公开HTTP参数指定此方法。

定位：`lib/myZip.class.php:89`。

### myZip::addDir

功能说明：递归向已打开ZIP加入目录成员，跳过链接及内部暂存文件

```text
public function addDir($path);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$path` | string；目标目录或路径 |

**返回值**：bool 目录成员全部加入成功为true，否则为false

调用形式：

```php
// $instance为已初始化的myZip实例。
$result = $instance->addDir($path);
```

定位：`lib/myZip.class.php:101`。

### myZip::zip

功能说明：收集指定文件/目录，在临时ZIP中构建成功后原子发布

```text
public function zip($file);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string\|array；待压缩的文件/目录路径或列表；兼容多个位置参数 |
| `...$args` | string；额外的待压缩文件/目录路径；与首项一起处理（历史位置参数，由func_get_args读取） |

**返回值**：bool 临时ZIP完整构建并发布成功为true，否则为false

调用形式：

```php
// $instance为已初始化的myZip实例。
$result = $instance->zip($file);
```

定位：`lib/myZip.class.php:122`。

### myZip::unzip

功能说明：验证ZIP路径、大小及CRC，在暂存目录解包后统一发布


调用示例：
    $ok = (new myZip($zipFile))->setLimits(67108864, 1000)->unzip($destination);

```text
public function unzip($dir='./', $remove = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$dir` | string；ZIP成员发布到的受信目标目录（默认：'./'） |
| `$remove` | bool；操作成功后是否删除源归档文件（默认：false） |

**返回值**：bool 解包及发布成功为true；remove启用时还要求源ZIP删除成功

调用形式：

```php
// $instance为已初始化的myZip实例。
$result = $instance->unzip($dir, $remove);
```

定位：`lib/myZip.class.php:167`。
