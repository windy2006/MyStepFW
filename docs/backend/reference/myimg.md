# myImg：图片处理与绘图

修订日期：2026-10-23。功能详解。

## 职责与调用位置

先检查原始字节、尺寸及像素预算再解码；使用结束后释放GD资源。远程图片仍需受控下载和大小限制。

源码：`lib/myImg.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
myImg::setLimits(8 * 1024 * 1024, 4096, 12000000);
$meta = myImg::inspect($sourceImage);
$image = new myImg();
$image->load($sourceImage);
$image->thumb(320, 240, $thumbnail);
$image->destroy();
```

## 声明与类职责

### myImg

功能说明：基于GD管理画布、图像转换、绘制、验证码、水印及缩略图

使用说明：
  1. 需要GD及对应图片格式支持；图像句柄在PHP 7.4中通常为resource。
  2. 先初始化图像或画布，设置颜色/字体后绘制，make输出或保存。
  3. setLimits限制输入大小/尺寸/像素；调用结束后释放不再使用的句柄。

使用示例：
    $img = new myImg(320, 180, [true, 'white']);
    $img->drawLine([0, 0], [100, 80], 'red')->make('png', $target);

### coordinateMaker

功能说明：继承myImg的坐标换算及折线、柱状、饼图绘制工具

使用说明：
  1. 需要GD及对应图片格式支持；图像句柄在PHP 7.4中通常为resource。
  2. 先初始化图像或画布，设置颜色/字体后绘制，make输出或保存。
  3. setLimits限制输入大小/尺寸/像素；调用结束后释放不再使用的句柄。

## 状态字段与公开变量

以下是本文件中的字段声明。public字段属于可见状态，protected/private字段只用于解释执行过程，不能绕过方法校验写入。字段用途需结合初始化、设置及读取方法；这些值不是站点的当前私有配置。

```text
protected
        $img = NULL,
        $width = 0,
        $height = 0,
        $color_lst = array(),
        $font = '',
        $dst_type = 'jpg',
        $file = '',
        $file_date = array(),
        $true_color = false;
```

```text
protected static $limits = ['bytes'=>33554432, 'dimension'=>16384, 'pixels'=>24000000];
```

```text
public
        $origin = array(0, 0),
        $scale_x = 1,
        $scale_y = 1,
        $distance_x = 0,
        $distance_y = 0,
        $start_x = 0,
        $start_y = 0,
        $title_x = array(),
        $title_y = array(),
        $text_x = 'X',
        $text_y = 'Y',
        $fix_x = 10,
        $fix_y = -10;
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myImg::setLimits` | 设置图像输入字节、单边尺寸及总像素预算 |
| `myImg::validDimensions` | 检查图像尺寸、像素及估算内存是否在预算内 |
| `myImg::inspect` | 读取并验证本地图像的格式、文件大小及像素信息 |
| `myImg::init` | 释放原画布后按位置参数加载图片或创建指定宽高的画布 |
| `myImg::create` | 生成画板 |
| `myImg::setTile` | 设置区域贴图 |
| `myImg::setTransparent` | 设置透明色（可为点、索引色和数组色） |
| `myImg::randomColor` | 优先随机复用登记颜色，或分配新的随机GD颜色 |
| `myImg::load` | 读取图片文件，并返回图片信息 |
| `myImg::rotate` | 旋转画布 |
| `myImg::resize` | 按比例缩放画布（可选择返回图像源） |
| `myImg::crop` | 截取画布的一部分（可选择返回图像源） |
| `myImg::paste` | 将一图像源粘贴到画布的某一位置 |
| `myImg::setLine` | 设定画线的粗细、风格、笔刷 |
| `myImg::setFilter` | 为图像添加滤镜效果 |
| `myImg::getSize` | 返回图像源的宽、高 |
| `myImg::getColor` | 取得画布某点的颜色 |
| `myImg::setColor` | 设置颜色 |
| `myImg::fill` | 从一点向画布右下填充 |
| `myImg::drawLine` | 画线 |
| `myImg::drawRectangle` | 画矩形 |
| `myImg::drawPolygon` | 画多边形 |
| `myImg::drawFiveSidedStar` | 画五角星 |
| `myImg::drawEllipse` | 画椭圆 |
| `myImg::drawArc` | 画圆弧 |
| `myImg::drawPie` | 画饼图 |
| `myImg::drawZigzag` | 画折线 |
| `myImg::drawString` | 添加文字 |
| `myImg::setFont` | 设置字体 |
| `myImg::getFontSize` | 取得字符串的占位大小 |
| `myImg::addNoise` | 添加干扰点 |
| `myImg::make` | 输出图像源到浏览器或文件 |
| `myImg::destroy` | 释放图像源 |
| `myImg::transString` | 字符集转换 |
| `myImg::checkPoint` | 检测点的有效性 |
| `myImg::check` | 检测图像源的有效性 |
| `myImg::gif_info` | 读取GIF信息 |
| `myImg::captcha` | 生成验证码 |
| `myImg::watermark` | 添加图片或文字水印并输出到浏览器或目标文件 |
| `myImg::thumb` | 制作缩略图 |
| `coordinateMaker::init` | 初始化实例 |
| `coordinateMaker::setOrigin` | 设置原点位置 |
| `coordinateMaker::setPara` | 设置坐标参数 |
| `coordinateMaker::getPoint` | 取得相对原点坐标的实际位置 |
| `coordinateMaker::getScalePoint` | 按坐标比例换算点位置，可选择转换到画布坐标 |
| `coordinateMaker::buileCoordinate` | 绘制坐标系 |
| `coordinateMaker::drawZigzag` | 画折线 |
| `coordinateMaker::drawBar` | 按数据系列绘制柱状图及可选数值和图例 |
| `coordinateMaker::drawPie` | 绘制饼图，并标注数值/百分比及可选图例 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myImg::setLimits

功能说明：设置图像输入字节、单边尺寸及总像素预算

```text
public static function setLimits($bytes = 33554432, $dimension = 16384, $pixels = 24000000);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$bytes` | int；最大允许处理的字节数，必须为正整数（默认：33554432） |
| `$dimension` | int；图像单边允许的最大像素数（默认：16384） |
| `$pixels` | int；图像总像素数量上限（默认：24000000） |

**返回值**：void 无返回值。

**异常与失败**：InvalidArgumentException 字节、尺寸或像素预算不是正整数

调用形式：

```php
$result = myImg::setLimits($bytes, $dimension, $pixels);
```

定位：`lib/myImg.class.php:132`。

### myImg::validDimensions

功能说明：检查图像尺寸、像素及估算内存是否在预算内

```text
public static function validDimensions($width, $height);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$width` | int\|float；宽度，图像/画布场景以像素为单位 |
| `$height` | int\|float；高度，图像/画布场景以像素为单位 |

**返回值**：bool 所有尺寸/像素/内存预算检查均通过为true

调用形式：

```php
$result = myImg::validDimensions($width, $height);
```

定位：`lib/myImg.class.php:144`。

### myImg::inspect

功能说明：读取并验证本地图像的格式、文件大小及像素信息

```text
public static function inspect($file);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；目标文件路径 |

**返回值**：array|false getimagesize返回的图像信息；无效/超预算为false

调用形式：

```php
$result = myImg::inspect($file);
```

定位：`lib/myImg.class.php:158`。

### myImg::init

功能说明：释放原画布后按位置参数加载图片或创建指定宽高的画布

```text
public function init();
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `...$args` | mixed；图片路径，或宽度、高度、create参数数组[真彩,背景]（历史位置参数，由func_get_args读取） |

**返回值**：$this|false 返回当前实例；图像加载失败为false

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->init();
```

定位：`lib/myImg.class.php:170`。

### myImg::create

功能说明：生成画板

```text
public function create($trueImage = true, $background = NULL);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$trueImage` | bool；true创建真彩画布，false创建调色板画布（默认：true） |
| `$background` | array\|int\|string\|null；画布背景颜色，null采用实现默认背景（默认：NULL） |

**返回值**：$this|false 画布创建后返回当前实例；尺寸超预算为false

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->create($trueImage, $background);
```

定位：`lib/myImg.class.php:202`。

### myImg::setTile

功能说明：设置区域贴图

```text
public function setTile($tile, $point = NULL, $width = NULL, $height = NULL);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$tile` | string；平铺图片的本地路径 |
| `$point` | array\|null；填充区域左上角[x,y]，null采用[0,0]（默认：NULL） |
| `$width` | int\|float\|null；填充宽度，null采用当前画布宽度（默认：NULL） |
| `$height` | int\|float\|null；填充高度，null采用当前画布高度（默认：NULL） |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->setTile($tile, $point, $width, $height);
```

定位：`lib/myImg.class.php:257`。

### myImg::setTransparent

功能说明：设置透明色（可为点、索引色和数组色）

```text
public function setTransparent($para);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$para` | array\|int\|string；取色坐标[x,y]、已登记颜色名或GD颜色值 |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->setTransparent($para);
```

定位：`lib/myImg.class.php:275`。

### myImg::randomColor

功能说明：优先随机复用登记颜色，或分配新的随机GD颜色

```text
public function randomColor($rand = false, $alpha = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$rand` | bool；true优先从已登记颜色中随机选取（默认：false） |
| `$alpha` | bool；true生成带透明度的随机颜色，false生成不透明颜色（默认：true） |

**返回值**：int|false 分配或复用的GD颜色索引；底层分配失败为false

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->randomColor($rand, $alpha);
```

定位：`lib/myImg.class.php:298`。

### myImg::load

功能说明：读取图片文件，并返回图片信息


资源所有权：直接调用load得到独立句柄，调用方负责imagedestroy；经init加载时由当前图像实例管理，destroy可显式释放。

```text
public function load($image, &$data = array());
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$image` | string；待加载的图片文件路径 |
| `&$data` | array；引用输出的图片格式/尺寸等getimagesize信息（默认：array()） |

**返回值**：resource|false 新GD图像句柄；格式、资源预算或解码失败为false；独立句柄由调用方释放

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->load($image, $data);
```

定位：`lib/myImg.class.php:316`。

### myImg::rotate

功能说明：旋转画布


资源所有权：未传img时本实例接管新画布；显式传img时返回的新句柄归调用方，须另行释放。传入的已有GD句柄不因成功返回而自动释放。

调用示例：
    $rotated = $image->rotate(90, $source);
    if ($rotated !== false) { imagedestroy($rotated); } // 独立返回句柄由调用方释放

```text
public function rotate($angle, $img = NULL);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$angle` | int\|float；旋转或文字倾斜角度，单位度 |
| `$img` | resource\|string\|null；GD图像句柄或图片路径；null修改当前画布，传入时返回新句柄（默认：NULL） |

**返回值**：$this|resource|false img为null时替换当前画布并返回自身；传入图像时返回新句柄；无效/超限为false

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->rotate($angle, $img);
```

定位：`lib/myImg.class.php:374`。

### myImg::resize

功能说明：按比例缩放画布（可选择返回图像源）


资源所有权：未传img时本实例接管新画布；显式传img时返回的新句柄归调用方，须另行释放。传入的已有GD句柄不因成功返回而自动释放。

```text
public function resize($rate, $img = NULL);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$rate` | int\|float\|array；正缩放倍率，或目标边界[宽,高]；边界模式保持比例且不放大 |
| `$img` | resource\|string\|null；GD图像句柄或图片路径；null修改当前画布，传入时返回新句柄（默认：NULL） |

**返回值**：$this|resource|false img为null时替换当前画布；传入图像时返回新句柄；比例/尺寸/加载无效为false

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->resize($rate, $img);
```

定位：`lib/myImg.class.php:406`。

### myImg::crop

功能说明：截取画布的一部分（可选择返回图像源）


资源所有权：未传img时本实例接管新画布；显式传img时返回的新句柄归调用方，须另行释放。传入的已有GD句柄不因成功返回而自动释放。

```text
public function crop($point, $width, $height, $img = NULL);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$point` | array；坐标数组[x,y]，具体原点/方向由当前绘图入口决定 |
| `$width` | int\|float；宽度，图像/画布场景以像素为单位 |
| `$height` | int\|float；高度，图像/画布场景以像素为单位 |
| `$img` | resource\|string\|null；GD图像句柄或图片路径；null修改当前画布，传入时返回新句柄（默认：NULL） |

**返回值**：$this|resource|false img为null时替换当前画布；指定图像时返回裁剪句柄；坐标/尺寸/加载无效为false

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->crop($point, $width, $height, $img);
```

定位：`lib/myImg.class.php:452`。

### myImg::paste

功能说明：将一图像源粘贴到画布的某一位置

```text
public function paste($img, $point = array(0, 0), $alpha = 100);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$img` | resource\|string；待粘贴的GD图像句柄或图片路径 |
| `$point` | array；坐标数组[x,y]，具体原点/方向由当前绘图入口决定（默认：array(0, 0)） |
| `$alpha` | int；1至100执行透明合成，非正值执行灰阶合成，大于100直接拷贝（默认：100） |

**返回值**：$this|false 粘贴后返回当前实例；源图像加载失败为false

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->paste($img, $point, $alpha);
```

定位：`lib/myImg.class.php:479`。

### myImg::setLine

功能说明：设定画线的粗细、风格、笔刷

```text
public function setLine($thickness = 1, $style = NULL, $brush = NULL);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$thickness` | int；绘线厚度，单位像素（默认：1） |
| `$style` | array\|null；GD线条样式颜色序列，null不设置（默认：NULL） |
| `$brush` | resource\|null；GD画笔图像句柄，null不设置（默认：NULL） |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->setLine($thickness, $style, $brush);
```

定位：`lib/myImg.class.php:499`。

### myImg::setFilter

功能说明：为图像添加滤镜效果

```text
public function setFilter($filtertype = IMG_FILTER_COLORIZE, $args = array());
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$filtertype` | int；GD的IMG_FILTER_*滤镜常量（默认：IMG_FILTER_COLORIZE） |
| `$args` | array；按目标函数顺序传入的附加参数（默认：array()） |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->setFilter($filtertype, $args);
```

定位：`lib/myImg.class.php:525`。

### myImg::getSize

功能说明：返回图像源的宽、高

```text
public function getSize($img = NULL);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$img` | resource\|null；GD图像句柄；null使用当前画布（默认：NULL） |

**返回值**：array [宽,高]像素二元组；当前/指定句柄无效时返回[0,0]

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->getSize($img);
```

定位：`lib/myImg.class.php:539`。

### myImg::getColor

功能说明：取得画布某点的颜色

```text
public function getColor($point, $mode = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$point` | array；坐标数组[x,y]，具体原点/方向由当前绘图入口决定 |
| `$mode` | bool；true返回GD颜色索引，false返回RGBA组成信息（默认：true） |

**返回值**：$this|array|int|false mode为true返回颜色索引，为false返回RGBA映射；无效点/画布可返回自身，GD读取失败为false

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->getColor($point, $mode);
```

定位：`lib/myImg.class.php:553`。

### myImg::setColor

功能说明：设置颜色

```text
public function setColor($idx = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$idx` | string；颜色索引名，空串自动分配；附加参数指定RGB及透明度（默认：''） |
| `...$args` | mixed；[R,G,B]及可选透明度，或R/G/B及可选透明度；GD透明度0至127（历史位置参数，由func_get_args读取） |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->setColor($idx);
```

定位：`lib/myImg.class.php:574`。

### myImg::fill

功能说明：从一点向画布右下填充

```text
public function fill($point = array(0, 0), $color = '', $color_border = NULL);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$point` | array；泛洪填充起点[x,y]，不是矩形区域边界（默认：array(0, 0)） |
| `$color` | string\|int；已登记颜色的索引名；未登记时采用随机颜色（默认：''） |
| `$color_border` | int\|null；GD边界颜色值；null普通泛洪填充，非null填充到该颜色边界（默认：NULL） |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->fill($point, $color, $color_border);
```

定位：`lib/myImg.class.php:612`。

### myImg::drawLine

功能说明：画线

```text
public function drawLine($p_start, $p_end, $color='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$p_start` | array；线段起点坐标[x,y] |
| `$p_end` | array；线段终点坐标[x,y] |
| `$color` | string\|int；已登记颜色的索引名；未登记时采用随机颜色（默认：''） |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->drawLine($p_start, $p_end, $color);
```

定位：`lib/myImg.class.php:630`。

### myImg::drawRectangle

功能说明：画矩形

```text
public function drawRectangle($point, $width, $height, $color_border = '', $color_fill = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$point` | array；矩形左上角在画布上的坐标[x,y] |
| `$width` | int\|float；宽度，图像/画布场景以像素为单位 |
| `$height` | int\|float；高度，图像/画布场景以像素为单位 |
| `$color_border` | string\|int；已登记颜色的索引名；未登记时采用随机颜色（默认：''） |
| `$color_fill` | string\|int；已登记颜色的索引名；未登记时采用随机颜色；空串不执行填充（默认：''） |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->drawRectangle($point, $width, $height, $color_border, $color_fill);
```

定位：`lib/myImg.class.php:654`。

### myImg::drawPolygon

功能说明：画多边形

```text
public function drawPolygon($points, $color_border = '', $color_fill = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$points` | array；依绘制顺序排列的二维坐标列表 |
| `$color_border` | string\|int；已登记颜色的索引名；未登记时采用随机颜色（默认：''） |
| `$color_fill` | string\|int；已登记颜色的索引名；未登记时采用随机颜色；空串不执行填充（默认：''） |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->drawPolygon($points, $color_border, $color_fill);
```

定位：`lib/myImg.class.php:672`。

### myImg::drawFiveSidedStar

功能说明：画五角星

```text
public function drawFiveSidedStar($point, $radius, $color_border = '', $color_fill = '', $star=true, $spiky=0.5);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$point` | array；图形中心在画布上的坐标[x,y] |
| `$radius` | int\|float；图形外接半径，单位像素 |
| `$color_border` | string\|int；已登记颜色的索引名；未登记时采用随机颜色（默认：''） |
| `$color_fill` | string\|int；已登记颜色的索引名；未登记时采用随机颜色；空串不执行填充（默认：''） |
| `$star` | bool；true绘制星形，false绘制正多边形（默认：true） |
| `$spiky` | float；星形内外半径比例（默认：0.5） |

**返回值**：$this 委托drawPolygon绘制后返回当前实例

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->drawFiveSidedStar($point, $radius, $color_border, $color_fill, $star, $spiky);
```

定位：`lib/myImg.class.php:708`。

### myImg::drawEllipse

功能说明：画椭圆

```text
public function drawEllipse($point, $width, $height, $color_border = '', $color_fill = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$point` | array；图形中心在画布上的坐标[x,y] |
| `$width` | int\|float；宽度，图像/画布场景以像素为单位 |
| `$height` | int\|float；高度，图像/画布场景以像素为单位 |
| `$color_border` | string\|int；已登记颜色的索引名；未登记时采用随机颜色（默认：''） |
| `$color_fill` | string\|int；已登记颜色的索引名；未登记时采用随机颜色；空串不执行填充（默认：''） |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->drawEllipse($point, $width, $height, $color_border, $color_fill);
```

定位：`lib/myImg.class.php:754`。

### myImg::drawArc

功能说明：画圆弧

```text
public function drawArc($point, $width, $height, $arc = array(0, 360), $color_border = '', $color_fill = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$point` | array；图形中心在画布上的坐标[x,y] |
| `$width` | int\|float；宽度，图像/画布场景以像素为单位 |
| `$height` | int\|float；高度，图像/画布场景以像素为单位 |
| `$arc` | array；起始/结束角度数组[start,end]，单位度（默认：array(0, 360)） |
| `$color_border` | string\|int；已登记颜色的索引名；未登记时采用随机颜色（默认：''） |
| `$color_fill` | string\|int；已登记颜色的索引名；未登记时采用随机颜色；空串不执行填充（默认：''） |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->drawArc($point, $width, $height, $arc, $color_border, $color_fill);
```

定位：`lib/myImg.class.php:775`。

### myImg::drawPie

功能说明：画饼图

```text
public function drawPie($data, $point, $width, $height, $mask = 20, $ext_value = 0, $distance = 0, $start_angle = 0);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$data` | array；按扇区顺序排列的非负数值列表 |
| `$point` | array；图形中心在画布上的坐标[x,y] |
| `$width` | int\|float；宽度，图像/画布场景以像素为单位 |
| `$height` | int\|float；高度，图像/画布场景以像素为单位 |
| `$mask` | int；饼图立体厚度，单位像素（默认：20） |
| `$ext_value` | int\|float；额外计入总量但不绘制的数值（默认：0） |
| `$distance` | int\|float；扇区向外分离的距离，单位像素（默认：0） |
| `$start_angle` | int\|float；首个扇区起始角度（默认：0） |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->drawPie($data, $point, $width, $height, $mask, $ext_value, $distance, $start_angle);
```

定位：`lib/myImg.class.php:798`。

### myImg::drawZigzag

功能说明：画折线

```text
public function drawZigzag($points, $color_line = '');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$points` | array；依绘制顺序排列的二维坐标列表 |
| `$color_line` | string\|int；已登记颜色的索引名；未登记时采用随机颜色（默认：''） |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->drawZigzag($points, $color_line);
```

定位：`lib/myImg.class.php:842`。

### myImg::drawString

功能说明：添加文字

```text
public function drawString($text, $point, $color='', $font='', $font_size = 12, $angle = 0);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$text` | string；要绘制或测量的文本 |
| `$point` | array；坐标数组[x,y]，具体原点/方向由当前绘图入口决定 |
| `$color` | string\|int；已登记颜色的索引名；未登记时采用随机颜色（默认：''） |
| `$font` | string；字体文件路径或实现支持的字体名（默认：''） |
| `$font_size` | int；文字大小（默认：12） |
| `$angle` | int\|float；旋转或文字倾斜角度，单位度（默认：0） |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->drawString($text, $point, $color, $font, $font_size, $angle);
```

定位：`lib/myImg.class.php:868`。

### myImg::setFont

功能说明：设置字体

```text
public function setFont($font);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$font` | string；字体文件路径或实现支持的字体名 |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->setFont($font);
```

定位：`lib/myImg.class.php:887`。

### myImg::getFontSize

功能说明：取得字符串的占位大小

```text
public function getFontSize($text, $size = 12, $angle = 0);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$text` | string\|int；待测量文字；未设置TTF或size为0时，数值表示GD内置字体编号 |
| `$size` | int；待测量文字的字号（默认：12） |
| `$angle` | int\|float；旋转或文字倾斜角度，单位度（默认：0） |

**返回值**：array 二元数组[宽,高]，单位像素；未设置TTF时按内置字体测量

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->getFontSize($text, $size, $angle);
```

定位：`lib/myImg.class.php:903`。

### myImg::addNoise

功能说明：添加干扰点

```text
public function addNoise($number);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$number` | int；需要添加的随机干扰点数量 |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->addNoise($number);
```

定位：`lib/myImg.class.php:920`。

### myImg::make

功能说明：输出图像源到浏览器或文件

```text
public function make($type='', $file='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$type` | string；输出图像格式，空串沿用当前格式（默认：''） |
| `$file` | string；保存路径；空串直接输出图像响应（默认：''） |

**返回值**：$this|false 输出/保存调用完成后返回当前实例；没有有效图像为false，底层编码错误不保证由返回值反映

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->make($type, $file);
```

定位：`lib/myImg.class.php:934`。

### myImg::destroy

功能说明：释放图像源

```text
public function destroy($img = NULL);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$img` | resource\|null；GD图像句柄；null使用当前画布（默认：NULL） |

**返回值**：bool 指定或当前GD句柄的释放结果；无有效句柄为false

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->destroy($img);
```

定位：`lib/myImg.class.php:976`。

### myImg::transString

功能说明：字符集转换

```text
public function transString($str, $charset = 'gbk');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$str` | string；待处理的字符串 |
| `$charset` | string；无myString时后备分支使用的字符编码提示；正常myString分支固定转为UTF-8（默认：'gbk'） |

**返回值**：string|false 通常转换为UTF-8；兼容后备分支可能沿用检测编码，iconv失败可为false

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->transString($str, $charset);
```

定位：`lib/myImg.class.php:992`。

### myImg::checkPoint

功能说明：检测点的有效性

```text
public function checkPoint();
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `...$args` | array；需要逐个校验的[x,y]数值坐标数组，至少一个（历史位置参数，由func_get_args读取） |

**返回值**：bool 至少一个参数且所有参数均为两个数值组成的数组时为true，否则为false

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->checkPoint();
```

定位：`lib/myImg.class.php:1015`。

### myImg::check

功能说明：检测图像源的有效性

```text
public function check($img = NULL);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$img` | resource\|null；GD图像句柄；null使用当前画布（默认：NULL） |

**返回值**：bool 目标为有效GD资源时为true；null检查当前画布

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->check($img);
```

定位：`lib/myImg.class.php:1033`。

### myImg::gif_info

功能说明：读取GIF信息

```text
public function gif_info($filename);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$filename` | string；附件展示名称或文件路径，按方法用途解释 |

**返回值**：array|false 含signatur/version/width/height/flag/trans_red/trans_green/trans_blue的GIF信息；头/调色板无效为false

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->gif_info($filename);
```

定位：`lib/myImg.class.php:1046`。

### myImg::captcha

功能说明：生成验证码

```text
public function captcha($str, $font, $fontsize = 16);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$str` | string；待处理的字符串 |
| `$font` | string；字体文件路径或实现支持的字体名 |
| `$fontsize` | int；验证码文字大小（默认：16） |

**返回值**：void 无返回值。

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->captcha($str, $font, $fontsize);
```

定位：`lib/myImg.class.php:1074`。

### myImg::watermark

功能说明：添加图片或文字水印并输出到浏览器或目标文件

```text
public function watermark($watermark, $position=1, $img_dst='', $para=array());
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$watermark` | string；水印图片路径；路径不存在时作为文字水印 |
| `$position` | int；POS_*位置常量；未匹配的位置数值作为居中水印的旋转角度（默认：1） |
| `$img_dst` | string；输出图片路径，空串按方法约定输出或修改当前实例（默认：''） |
| `$para` | array；图片支持rate/alpha；文字支持alpha/font/fontsize/fontcolor/bgcolor（默认：array()） |

**返回值**：$this 水印处理及输出后返回当前实例

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->watermark($watermark, $position, $img_dst, $para);
```

定位：`lib/myImg.class.php:1120`。

### myImg::thumb

功能说明：制作缩略图

```text
public function thumb($dstW, $dstH, $img_dst='');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$dstW` | int；缩略图目标宽度，单位像素 |
| `$dstH` | int；缩略图目标高度，单位像素 |
| `$img_dst` | string；输出图片路径，空串按方法约定输出或修改当前实例（默认：''） |

**返回值**：$this|false 返回当前实例；目标已存在时直接加载，尺寸/缩放/加载失败为false

调用形式：

```php
// $instance为已初始化的myImg实例。
$result = $instance->thumb($dstW, $dstH, $img_dst);
```

定位：`lib/myImg.class.php:1299`。

### coordinateMaker::init

功能说明：初始化实例

```text
public function init($width = 400, $height = 400, $origin = array(0, 0), $args = array(true, array(0xee, 0xee, 0xee)));
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$width` | int\|float；宽度，图像/画布场景以像素为单位（默认：400） |
| `$height` | int\|float；高度，图像/画布场景以像素为单位（默认：400） |
| `$origin` | array；坐标原点[x,y]（默认：array(0, 0)） |
| `$args` | array；传给父类create的参数数组：[是否真彩,背景颜色/图片]（默认：array(true, array(0xee, 0xee, 0xee))） |

**返回值**：$this 初始化坐标画布、原点和坐标设置后返回当前实例

调用形式：

```php
// $instance为已初始化的coordinateMaker实例。
$result = $instance->init($width, $height, $origin, $args);
```

定位：`lib/myImg.class.php:1362`。

### coordinateMaker::setOrigin

功能说明：设置原点位置

```text
public function setOrigin($point);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$point` | array；坐标数组[x,y]，具体原点/方向由当前绘图入口决定 |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的coordinateMaker实例。
$result = $instance->setOrigin($point);
```

定位：`lib/myImg.class.php:1376`。

### coordinateMaker::setPara

功能说明：设置坐标参数

```text
public function setPara($para = array());
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$para` | array；scale_x/scale_y、distance_x/distance_y、start_x/start_y、text_x/text_y和font等坐标配置（默认：array()） |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的coordinateMaker实例。
$result = $instance->setPara($para);
```

定位：`lib/myImg.class.php:1388`。

### coordinateMaker::getPoint

功能说明：取得相对原点坐标的实际位置

```text
public function getPoint($point);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$point` | array；坐标数组[x,y]，具体原点/方向由当前绘图入口决定 |

**返回值**：array|$this 有效坐标返回[画布x,画布y]；无效点返回当前实例

调用形式：

```php
// $instance为已初始化的coordinateMaker实例。
$result = $instance->getPoint($point);
```

定位：`lib/myImg.class.php:1413`。

### coordinateMaker::getScalePoint

功能说明：按坐标比例换算点位置，可选择转换到画布坐标

```text
public function getScalePoint($point, $trans = true);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$point` | array；坐标数组[x,y]，具体原点/方向由当前绘图入口决定 |
| `$trans` | bool；是否执行坐标系方向转换（默认：true） |

**返回值**：array|$this 有效坐标返回缩放/转换后的[x,y]；无效点返回当前实例

调用形式：

```php
// $instance为已初始化的coordinateMaker实例。
$result = $instance->getScalePoint($point, $trans);
```

定位：`lib/myImg.class.php:1425`。

### coordinateMaker::buileCoordinate

功能说明：绘制坐标系

```text
public function buileCoordinate($padding = 20);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$padding` | int；坐标轴与画布边缘的留白像素（默认：20） |

**返回值**：$this 返回当前实例，支持链式调用。

**异常与失败**：InvalidArgumentException 坐标刻度方向无效或刻度数量超过10000

调用形式：

```php
// $instance为已初始化的coordinateMaker实例。
$result = $instance->buileCoordinate($padding);
```

定位：`lib/myImg.class.php:1441`。

### coordinateMaker::drawZigzag

功能说明：画折线

多系列模式的data_lst按系列排列，data_str与color_lst逐系列对应。当前点生成循环依赖show_value，绘制折线时应保留true。

```text
public function drawZigzag($data_lst, $color_lst='', $data_str='', $show_value = true, $legend_point = NULL);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$data_lst` | array；需要绘制的数据系列 |
| `$color_lst` | array\|string；各数据系列的颜色列表，空串采用默认配色（默认：''） |
| `$data_str` | array\|string；数据点/系列标签，空串不提供自定义标签（默认：''） |
| `$show_value` | bool；是否在图上绘制数值（默认：true） |
| `$legend_point` | array\|null；图例起点坐标[x,y]，null采用默认位置（默认：NULL） |

**返回值**：$this 绘图及图例处理后返回当前实例

调用形式：

```php
// $instance为已初始化的coordinateMaker实例。
$result = $instance->drawZigzag($data_lst, $color_lst, $data_str, $show_value, $legend_point);
```

定位：`lib/myImg.class.php:1537`。

### coordinateMaker::drawBar

功能说明：按数据系列绘制柱状图及可选数值和图例

```text
public function drawBar($data_lst, $color_lst='', $data_str='', $show_value = false, $legend_point = NULL);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$data_lst` | array；需要绘制的数据系列 |
| `$color_lst` | array\|string；各数据系列的颜色列表，空串采用默认配色（默认：''） |
| `$data_str` | array\|string；数据点/系列标签，空串不提供自定义标签（默认：''） |
| `$show_value` | bool；是否在图上绘制数值（默认：false） |
| `$legend_point` | array\|null；图例起点坐标[x,y]，null采用默认位置（默认：NULL） |

**返回值**：$this 返回当前实例，支持链式调用。

调用形式：

```php
// $instance为已初始化的coordinateMaker实例。
$result = $instance->drawBar($data_lst, $color_lst, $data_str, $show_value, $legend_point);
```

定位：`lib/myImg.class.php:1594`。

### coordinateMaker::drawPie

功能说明：绘制饼图，并标注数值/百分比及可选图例

```text
public function drawPie($data, $point, $width, $height, $data_str = '', $mode = 1, $legend = 0, $mask = 20, $ext_value = 0, $distance = 0, $start_angle = 0);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$data` | array；按扇区顺序排列的数值列表 |
| `$point` | array；饼图中心在画布上的坐标[x,y] |
| `$width` | int\|float；宽度，图像/画布场景以像素为单位 |
| `$height` | int\|float；高度，图像/画布场景以像素为单位 |
| `$data_str` | array\|string；数据点/系列标签，空串不提供自定义标签（默认：''） |
| `$mode` | int；0显示原始数值，其他值显示向上取整的百分比（默认：1） |
| `$legend` | int；0不绘制图例，1在右侧绘制，其他非零值在下方绘制（默认：0） |
| `$mask` | int；饼图立体厚度，单位像素（默认：20） |
| `$ext_value` | int\|float；额外计入总量但不绘制的数值（默认：0） |
| `$distance` | int\|float；扇区向外分离的距离，单位像素（默认：0） |
| `$start_angle` | int\|float；首个扇区起始角度（默认：0） |

**返回值**：$this 饼图、数值及图例处理后返回当前实例；空总量不绘制标签

调用形式：

```php
// $instance为已初始化的coordinateMaker实例。
$result = $instance->drawPie($data, $point, $width, $height, $data_str, $mode, $legend, $mask, $ext_value, $distance, $start_angle);
```

定位：`lib/myImg.class.php:1662`。

