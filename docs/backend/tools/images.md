# 图片解码预算、缩略图和绘图

## 本篇目标与调用场景

先检查原始字节、尺寸及像素预算再解码；使用结束后释放GD资源。远程图片仍需受控下载和大小限制。

## 操作步骤与注意事项

先准备示例变量和目标资源，再执行方法。明确区分返回内容、直接输出、落盘或网络传输；后端PHP代码不要作为JavaScript放进浏览器脚本。完整方法参数见对应公共类参考页。

## 主要方法：参数、返回和失败约定

### myImg::setLimits

功能说明：设置图像输入字节、单边尺寸及总像素预算

```text
public static function setLimits($bytes = 33554432, $dimension = 16384, $pixels = 24000000);
```

| 参数说明 |
| --- |
| int $bytes 最大允许处理的字节数，必须为正整数（默认：33554432） |
| int $dimension 图像单边允许的最大像素数（默认：16384） |
| int $pixels 图像总像素数量上限（默认：24000000） |

**返回**：void 无返回值。

**异常**：InvalidArgumentException 字节、尺寸或像素预算不是正整数

### myImg::validDimensions

功能说明：检查图像尺寸、像素及估算内存是否在预算内

```text
public static function validDimensions($width, $height);
```

| 参数说明 |
| --- |
| int\|float $width 宽度，图像/画布场景以像素为单位 |
| int\|float $height 高度，图像/画布场景以像素为单位 |

**返回**：bool 所有尺寸/像素/内存预算检查均通过为true

### myImg::inspect

功能说明：读取并验证本地图像的格式、文件大小及像素信息

```text
public static function inspect($file);
```

| 参数说明 |
| --- |
| string $file 目标文件路径 |

**返回**：array|false getimagesize返回的图像信息；无效/超预算为false

### myImg::init

功能说明：释放原画布后按位置参数加载图片或创建指定宽高的画布

```text
public function init();
```

| 参数说明 |
| --- |
| mixed ...$args 图片路径，或宽度、高度、create参数数组[真彩,背景]（位置参数，由func_get_args读取） |

**返回**：$this|false 返回当前实例；图像加载失败为false

### myImg::create

功能说明：生成画板

```text
public function create($trueImage = true, $background = NULL);
```

| 参数说明 |
| --- |
| bool $trueImage true创建真彩画布，false创建调色板画布（默认：true） |
| array\|int\|string\|null $background 画布背景颜色，null采用实现默认背景（默认：NULL） |

**返回**：$this|false 画布创建后返回当前实例；尺寸超预算为false

### myImg::setTile

功能说明：设置区域贴图

```text
public function setTile($tile, $point = NULL, $width = NULL, $height = NULL);
```

| 参数说明 |
| --- |
| string $tile 平铺图片的本地路径 |
| array\|null $point 填充区域左上角[x,y]，null采用[0,0]（默认：NULL） |
| int\|float\|null $width 填充宽度，null采用当前画布宽度（默认：NULL） |
| int\|float\|null $height 填充高度，null采用当前画布高度（默认：NULL） |

**返回**：$this 返回当前实例，支持链式调用。

### coordinateMaker::init

功能说明：初始化实例

```text
public function init($width = 400, $height = 400, $origin = array(0, 0), $args = array(true, array(0xee, 0xee, 0xee)));
```

| 参数说明 |
| --- |
| int\|float $width 宽度，图像/画布场景以像素为单位（默认：400） |
| int\|float $height 高度，图像/画布场景以像素为单位（默认：400） |
| array $origin 坐标原点[x,y]（默认：array(0, 0)） |
| array $args 传给父类create的参数数组：[是否真彩,背景颜色/图片]（默认：array(true, array(0xee, 0xee, 0xee))） |

**返回**：$this 初始化坐标画布、原点和坐标设置后返回当前实例

## 明确调用示例

```php
myImg::setLimits(8 * 1024 * 1024, 4096, 12000000);
$meta = myImg::inspect($sourceImage);
$image = new myImg();
$image->load($sourceImage);
$image->thumb(320, 240, $thumbnail);
$image->destroy();
```

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
