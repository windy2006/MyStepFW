# ZIP创建与受控解包

## 本篇目标与调用场景

构造器可打开已有ZIP；解包前设置条目和总字节预算；失败时保留原始包便于检查。

## 操作步骤与注意事项

先准备示例变量和目标资源，再执行方法。明确区分返回内容、直接输出、落盘或网络传输；后端PHP代码不要作为JavaScript放进浏览器脚本。完整方法参数见对应公共类参考页。

## 主要方法：参数、返回和失败约定

### myZip::setLimits

功能说明：设置ZIP解包的总未压缩字节及成员数量上限

```text
public function setLimits($bytes = 268435456, $entries = 10000);
```

| 参数说明 |
| --- |
| int $bytes 最大允许处理的字节数，必须为正整数（默认：268435456） |
| int $entries 归档允许的最大成员数量（默认：10000） |

**返回**：$this 返回当前ZIP实例

**异常**：InvalidArgumentException 归档体积或条目上限不是正整数

### myZip::addDir

功能说明：递归向已打开ZIP加入目录成员，跳过链接及内部暂存文件

```text
public function addDir($path);
```

| 参数说明 |
| --- |
| string $path 目标目录或路径 |

**返回**：bool 目录成员全部加入成功为true，否则为false

### myZip::zip

功能说明：收集指定文件/目录，在临时ZIP中构建成功后原子发布

```text
public function zip($file);
```

| 参数说明 |
| --- |
| string\|array $file 待压缩的文件/目录路径或列表；支持多个位置参数 |
| string ...$args 额外的待压缩文件/目录路径；与首项一起处理（位置参数，由func_get_args读取） |

**返回**：bool 临时ZIP完整构建并发布成功为true，否则为false

### myZip::unzip

功能说明：验证ZIP路径、大小及CRC，在暂存目录解包后统一发布

调用示例：
    $ok = (new myZip($zipFile))->setLimits(67108864, 1000)->unzip($destination);

```text
public function unzip($dir='./', $remove = false);
```

| 参数说明 |
| --- |
| string $dir ZIP成员发布到的受信目标目录（默认：'./'） |
| bool $remove 操作成功后是否删除源归档文件（默认：false） |

**返回**：bool 解包及发布成功为true；remove启用时还要求源ZIP删除成功

## 明确调用示例

```php
$zip = new myZip($zipFile);
$zip->setLimits(64 * 1024 * 1024, 2000);
$zip->unzip($targetDirectory, false);
// false表示不因成功解包删除源归档；后续检查目标文件及业务版本。
```

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
