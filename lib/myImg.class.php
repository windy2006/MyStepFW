<?PHP
/********************************************
*                                           *
* Name    : Image Creator                   *
* Modifier: Windy2000                       *
* Time    : 2007-06-26                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/
/**
 * 文件：lib/myImg.class.php
 * 功能说明：GD图像绘制、变换及图表工具
 *   包含myImg图像操作和coordinateMaker坐标/图表辅助，支持验证码、水印、缩略图。
 *
 * 方法目录：
 *   public static myImg::setLimits($bytes = 33554432, $dimension = 16384, $pixels = 24000000) — 设置图像输入字节、单边尺寸及总像素预算
 *   public static myImg::validDimensions($width, $height) — 检查图像尺寸、像素及估算内存是否在预算内
 *   public static myImg::inspect($file) — 读取并验证本地图像的格式、文件大小及像素信息
 *   public myImg::init() — 释放原画布后按位置参数加载图片或创建指定宽高的画布
 *   public myImg::create($trueImage = true, $background = NULL) — 生成画板
 *   public myImg::setTile($tile, $point = NULL, $width = NULL, $height = NULL) — 设置区域贴图
 *   public myImg::setTransparent($para) — 设置透明色（可为点、索引色和数组色）
 *   public myImg::randomColor($rand = false, $alpha = true) — 优先随机复用登记颜色，或分配新的随机GD颜色
 *   public myImg::load($image, &$data = array()) — 读取图片文件，并返回图片信息
 *   public myImg::rotate($angle, $img = NULL) — 旋转画布
 *   public myImg::resize($rate, $img = NULL) — 按比例缩放画布（可选择返回图像源）
 *   public myImg::crop($point, $width, $height, $img = NULL) — 截取画布的一部分（可选择返回图像源）
 *   public myImg::paste($img, $point = array(0, 0), $alpha = 100) — 将一图像源粘贴到画布的某一位置
 *   public myImg::setLine($thickness = 1, $style = NULL, $brush = NULL) — 设定画线的粗细、风格、笔刷
 *   public myImg::setFilter($filtertype = IMG_FILTER_COLORIZE, $args = array()) — 为图像添加滤镜效果
 *   public myImg::getSize($img = NULL) — 返回图像源的宽、高
 *   public myImg::getColor($point, $mode = true) — 取得画布某点的颜色
 *   public myImg::setColor($idx = '') — 设置颜色
 *   public myImg::fill($point = array(0, 0), $color = '', $color_border = NULL) — 从一点向画布右下填充
 *   public myImg::drawLine($p_start, $p_end, $color='') — 画线
 *   public myImg::drawRectangle($point, $width, $height, $color_border = '', $color_fill = '') — 画矩形
 *   public myImg::drawPolygon($points, $color_border = '', $color_fill = '') — 画多边形
 *   public myImg::drawFiveSidedStar($point, $radius, $color_border = '', $color_fill = '', $star=true, $spiky=0.5) — 画五角星
 *   public myImg::drawEllipse($point, $width, $height, $color_border = '', $color_fill = '') — 画椭圆
 *   public myImg::drawArc($point, $width, $height, $arc = array(0, 360), $color_border = '', $color_fill = '') — 画圆弧
 *   public myImg::drawPie($data, $point, $width, $height, $mask = 20, $ext_value = 0, $distance = 0, $start_angle = 0) — 画饼图
 *   public myImg::drawZigzag($points, $color_line = '') — 画折线
 *   public myImg::drawString($text, $point, $color='', $font='', $font_size = 12, $angle = 0) — 添加文字
 *   public myImg::setFont($font) — 设置字体
 *   public myImg::getFontSize($text, $size = 12, $angle = 0) — 取得字符串的占位大小
 *   public myImg::addNoise($number) — 添加干扰点
 *   public myImg::make($type='', $file='') — 输出图像源到浏览器或文件
 *   public myImg::destroy($img = NULL) — 释放图像源
 *   public myImg::transString($str, $charset = 'gbk') — 字符集转换
 *   public myImg::checkPoint() — 检测点的有效性
 *   public myImg::check($img = NULL) — 检测图像源的有效性
 *   public myImg::gif_info($filename) — 读取GIF信息
 *   public myImg::captcha($str, $font, $fontsize = 16) — 生成验证码
 *   public myImg::watermark($watermark, $position=1, $img_dst='', $para=array()) — 添加图片或文字水印并输出到浏览器或目标文件
 *   public myImg::thumb($dstW, $dstH, $img_dst='') — 制作缩略图
 *   public coordinateMaker::init($width = 400, $height = 400, $origin = array(0, 0), $args = array(true, array(0xee, 0xee, 0xee))) — 初始化实例
 *   public coordinateMaker::setOrigin($point) — 设置原点位置
 *   public coordinateMaker::setPara($para = array()) — 设置坐标参数
 *   public coordinateMaker::getPoint($point) — 取得相对原点坐标的实际位置
 *   public coordinateMaker::getScalePoint($point, $trans = true) — 按坐标比例换算点位置，可选择转换到画布坐标
 *   public coordinateMaker::buileCoordinate($padding = 20) — 绘制坐标系
 *   public coordinateMaker::drawZigzag($data_lst, $color_lst='', $data_str='', $show_value = true, $legend_point = NULL) — 画折线
 *   public coordinateMaker::drawBar($data_lst, $color_lst='', $data_str='', $show_value = false, $legend_point = NULL) — 按数据系列绘制柱状图及可选数值和图例
 *   public coordinateMaker::drawPie($data, $point, $width, $height, $data_str = '', $mode = 1, $legend = 0, $mask = 20, $ext_value = 0, $distance = 0, $start_angle = 0) — 绘制饼图，并标注数值/百分比及可选图例
 *
 * 使用说明：
 *   1. 需要GD及对应图片格式支持；图像句柄在PHP 7.4中通常为resource。
 *   2. 先初始化图像或画布，设置颜色/字体后绘制，make输出或保存。
 *   3. setLimits限制输入大小/尺寸/像素；调用结束后释放不再使用的句柄。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名保持PHP 7.4兼容，PHPDoc类型用于说明，不额外强制运行时类型。
 */


/**
 * 功能说明：基于GD管理画布、图像转换、绘制、验证码、水印及缩略图
 *
 * 使用说明：
 *   1. 需要GD及对应图片格式支持；图像句柄在PHP 7.4中通常为resource。
 *   2. 先初始化图像或画布，设置颜色/字体后绘制，make输出或保存。
 *   3. setLimits限制输入大小/尺寸/像素；调用结束后释放不再使用的句柄。
 *
 * 使用示例：
 *     $img = new myImg(320, 180, [true, 'white']);
 *     $img->drawLine([0, 0], [100, 80], 'red')->make('png', $target);
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 *
 * @example ../app/Document/module/myImg.php 框架现有演示，仅作为文档引用，不在检查时执行
 */
class myImg extends myBase {
    use myTrait;

    const
        POS_RB = 1,
        POS_RT = 2,
        POS_LB = 3,
        POS_LT = 4,
        POS_LM = 5,
        POS_RM = 6,
        POS_MT = 7,
        POS_MB = 8,
        POS_M = 9;

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

    protected static $limits = ['bytes'=>33554432, 'dimension'=>16384, 'pixels'=>24000000];

    /**
     * 功能说明：设置图像输入字节、单边尺寸及总像素预算
     *
     * @param int $bytes 最大允许处理的字节数，必须为正整数（默认：33554432）
     * @param int $dimension 图像单边允许的最大像素数（默认：16384）
     * @param int $pixels 图像总像素数量上限（默认：24000000）
     * @return void 无返回值。
     * @throws InvalidArgumentException 字节、尺寸或像素预算不是正整数
     */
    public static function setLimits($bytes = 33554432, $dimension = 16384, $pixels = 24000000) {
        foreach([$bytes, $dimension, $pixels] as $limit) if(!is_int($limit) || $limit <= 0) throw new InvalidArgumentException('Image limits must be positive integers');
        self::$limits = ['bytes'=>$bytes, 'dimension'=>$dimension, 'pixels'=>$pixels];
    }

    /**
     * 功能说明：检查图像尺寸、像素及估算内存是否在预算内
     *
     * @param int|float $width 宽度，图像/画布场景以像素为单位
     * @param int|float $height 高度，图像/画布场景以像素为单位
     * @return bool 所有尺寸/像素/内存预算检查均通过为true
     */
    public static function validDimensions($width, $height) {
        if(!is_numeric($width) || !is_numeric($height) || $width < 1 || $height < 1 || !is_finite((float)$width) || !is_finite((float)$height)) return false;
        $pixels = (float)$width * (float)$height;
        if($width > self::$limits['dimension'] || $height > self::$limits['dimension'] || $pixels > self::$limits['pixels']) return false;
        $memory = myFile::getByte(ini_get('memory_limit'));
        return $memory <= 0 || $pixels * 8 + 1048576 < $memory - memory_get_usage(true);
    }

    /**
     * 功能说明：读取并验证本地图像的格式、文件大小及像素信息
     *
     * @param string $file 目标文件路径
     * @return array|false getimagesize返回的图像信息；无效/超预算为false
     */
    public static function inspect($file) {
        if(!is_file($file) || filesize($file) > self::$limits['bytes']) return false;
        $data = @getimagesize($file);
        return $data && self::validDimensions($data[0], $data[1]) ? $data : false;
    }

    /**
     * 功能说明：释放原画布后按位置参数加载图片或创建指定宽高的画布
     *
     * @param mixed ...$args 图片路径，或宽度、高度、create参数数组[真彩,背景]（历史位置参数，由func_get_args读取）
     * @return $this|false 返回当前实例；图像加载失败为false
     */
    public function init() {
        $this->destroy();
        $args = func_get_args();
        if(empty($args)) return $this;
        if(is_file($args[0])) {
            $data = array();
            $this->img = $this->load($args[0], $data);
            if(!$this->img) {
                $this->error('Cannot read image file: '.$args[0]);
                return false;
            }
            list($this->width, $this->height, $type) = $data;
            if($type == 'JPG') imageinterlace($this->img, 0);
            $this->file_date = $data;
            $this->file = $args[0];
            $this->true_color = imageistruecolor($this->img);
        } elseif(is_numeric($args[0]) && isset($args[1]) && is_numeric($args[1])) {
            $this->width = $args[0];
            $this->height = $args[1];
            if(!isset($args[2])) $args[2] = array();
            call_user_func_array(array($this, 'create'), $args[2]);
        }
        return $this;
    }

    /**
     * 功能说明：生成画板
     *
     * @param bool $trueImage true创建真彩画布，false创建调色板画布（默认：true）
     * @param array|int|string|null $background 画布背景颜色，null采用实现默认背景（默认：NULL）
     * @return $this|false 画布创建后返回当前实例；尺寸超预算为false
     */
    public function create($trueImage = true, $background = NULL) {
        if(!self::validDimensions($this->width, $this->height)) return false;
        $this->destroy();
        $this->color_lst = [];
        $this->img = $trueImage ? imagecreatetruecolor($this->width, $this->height) : imagecreate($this->width, $this->height);
        $this->setColor('red', 255, 0, 0);
        $this->setColor('green', 0, 255, 0);
        $this->setColor('blue', 0, 0, 255);
        $this->setColor('yellow', 255, 255, 0);
        $this->setColor('white', 255, 255, 255);
        $this->setColor('black', 0, 0, 0);
        $this->setColor('transparent', 0, 0, 0, 127);

        $color_pie = array();
        $color_pie[] = array(0, 62, 136);
        $color_pie[] = array(0, 115, 106);
        $color_pie[] = array(220, 101, 29);
        $color_pie[] = array(189, 24, 51);
        $color_pie[] = array(214, 0, 127);
        $color_pie[] = array(98, 1, 96);
        $color_pie[] = array(82, 56, 47);
        $color_pie[] = array(255, 153, 204);
        $color_pie[] = array(137, 91, 74);
        $color_pie[] = array(255, 203, 3);
        $color_pie[] = array(153, 204, 0);
        shuffle($color_pie);
        $i = 1;
        foreach($color_pie as $value) {
            $this->setColor('pie_'.$i, $value);
            $i++;
        }
        if(!is_null($background)) {
            if(is_array($background)) {
                $this->setColor('background', $background[0], $background[1], $background[2]);
                $this->fill(array(0, 0), 'background');
            } elseif(is_file($background)) {
                $this->setTile($background);
            } else {
                $this->fill(array(0, 0), $background);
            }
        } else {
            $this->fill(array(0, 0), 'transparent');
        }
        return $this;
    }

    /**
     * 功能说明：设置区域贴图
     *
     * @param string $tile 平铺图片的本地路径
     * @param array|null $point 填充区域左上角[x,y]，null采用[0,0]（默认：NULL）
     * @param int|float|null $width 填充宽度，null采用当前画布宽度（默认：NULL）
     * @param int|float|null $height 填充高度，null采用当前画布高度（默认：NULL）
     * @return $this 返回当前实例，支持链式调用。
     */
    public function setTile($tile, $point = NULL, $width = NULL, $height = NULL) {
        if($tile = $this->load($tile)) {
            if(!$this->checkPoint($point)) $point = array(0 , 0);
            if(is_null($width)) $width = $this->width;
            if(is_null($height)) $height = $this->height;
            imagesettile($this->img, $tile);
            imageFilledRectangle($this->img, $point[0], $point[1], $point[0] + $width, $point[1] + $height, IMG_COLOR_TILED);
            $this->destroy($tile);
        }
        return $this;
    }

    /**
     * 功能说明：设置透明色（可为点、索引色和数组色）
     *
     * @param array|int|string $para 取色坐标[x,y]、已登记颜色名或GD颜色值
     * @return $this 返回当前实例，支持链式调用。
     */
    public function setTransparent($para) {
        if($this->checkPoint($para)) {
            imagecolortransparent($this->img, $this->getColor($para));
        } elseif(isset($this->color_lst[$para])) {
            imagecolortransparent($this->img, $this->color_lst[$para]);
        } else {
            imagecolortransparent($this->img, $para);
        }
        return $this;
    }

    /**随机颜色
     * @param bool $rand
     * @param bool $alpha
     * @return int|mixed
     */
    /**
     * 功能说明：优先随机复用登记颜色，或分配新的随机GD颜色
     *
     * @param bool $rand true优先从已登记颜色中随机选取（默认：false）
     * @param bool $alpha true生成带透明度的随机颜色，false生成不透明颜色（默认：true）
     * @return int|false 分配或复用的GD颜色索引；底层分配失败为false
     */
    public function randomColor($rand = false, $alpha = true) {
        if(count($this->color_lst)>0 && $rand) {
            return $this->color_lst[array_rand($this->color_lst)];
        } else {
            return $alpha ? imagecolorallocatealpha($this->img, rand(0, 255), rand(0, 255), rand(0, 255), rand(0, 127)) : imagecolorallocate($this->img, rand(0, 255), rand(0, 255), rand(0, 255));
        }
    }

    /**
     * 功能说明：读取图片文件，并返回图片信息
     *
     *
     * 资源所有权：直接调用load得到独立句柄，调用方负责imagedestroy；经init加载时由当前图像实例管理，destroy可显式释放。
     *
     * @param string $image 待加载的图片文件路径
     * @param array &$data 引用输出的图片格式/尺寸等getimagesize信息（默认：array()）
     * @return resource|false 新GD图像句柄；格式、资源预算或解码失败为false；独立句柄由调用方释放
     */
    public function load($image, &$data = array()) {
        if(!self::inspect($image)) return false;
        $info = array();
        $data = @getimagesize($image, $info);
        if(!$data) return false;
        switch(true) {
            case ($data[2]==1 && (imagetypes() & IMG_GIF)):
                $img = imagecreatefromgif($image);
                break;
            case ($data[2]==2 && (imagetypes() & IMG_JPG)):
                $img = imagecreatefromjpeg($image);
                break;
            case ($data[2]==3 && (imagetypes() & IMG_PNG)):
                $img = imagecreatefrompng($image);
                break;
            case ($data[2]==6):
                $img = imagecreatefrombmp($image);
                break;
            case ($data[2]==15 && (imagetypes() & IMG_WBMP)):
                $img = imagecreatefromwbmp($image);
                break;
            default:
                return false;
            break;
        }
        if(!$img) return false;
        if($data[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($image, 'IFD0', true, false);
            $orientation = (int)($exif['IFD0']['Orientation'] ?? 1);
            if(in_array($orientation, [2, 4, 5, 7], true)) imageflip($img, in_array($orientation, [4, 5], true) ? IMG_FLIP_VERTICAL : IMG_FLIP_HORIZONTAL);
            $angle = [3=>180, 5=>-90, 6=>-90, 7=>-90, 8=>90][$orientation] ?? 0;
            if($angle) {
                $rotated = imagerotate($img, $angle, 0);
                if(!$rotated) { imagedestroy($img); return false; }
                imagedestroy($img); $img = $rotated;
            }
            $data[0] = imagesx($img); $data[1] = imagesy($img);
        }
        $type_list = array('unknow', 'GIF', 'JPG', 'PNG', 'SWF', 'PSD', 'BMP', 'TIFF(intel byte order)', 'TIFF(motorola byte order)', 'JPC', 'JP2', 'JPX', 'JB2', 'SWC', 'IFF', 'WBMP', 'XBM');
        $data[2] = $type_list[$data[2]];
        $data[3] = $info;
        return $img;
    }

    /**
     * 功能说明：旋转画布
     *
     *
     * 资源所有权：未传img时本实例接管新画布；显式传img时返回的新句柄归调用方，须另行释放。传入的已有GD句柄不因成功返回而自动释放。
     *
     * 调用示例：
     *     $rotated = $image->rotate(90, $source);
     *     if ($rotated !== false) { imagedestroy($rotated); } // 独立返回句柄由调用方释放
     *
     * @param int|float $angle 旋转或文字倾斜角度，单位度
     * @param resource|string|null $img GD图像句柄或图片路径；null修改当前画布，传入时返回新句柄（默认：NULL）
     * @return $this|resource|false img为null时替换当前画布并返回自身；传入图像时返回新句柄；无效/超限为false
     */
    public function rotate($angle, $img = NULL) {
        if(!is_numeric($angle) || !is_finite((float)$angle)) return false;
        $own = $img === null; $loaded = false;
        $source = $own ? $this->img : $img;
        if(!$this->check($source)) { $source = $this->load($source); $loaded = true; }
        if(!$source) return false;
        try {
            $radians = deg2rad(fmod($angle, 360));
            $w = imagesx($source); $h = imagesy($source);
            $width = ceil(round(abs($w*cos($radians))+abs($h*sin($radians)), 6));
            $height = ceil(round(abs($w*sin($radians))+abs($h*cos($radians)), 6));
            if(!self::validDimensions($width, $height)) return false;
            $rotated = imagerotate($source, $angle, imagecolorallocatealpha($source, 0, 0, 0, 127));
            if(!$rotated) return false;
            imagesavealpha($rotated, true);
            if(!$own) return $rotated;
            $this->destroy(); $this->img = $rotated;
            list($this->width, $this->height) = $this->getSize();
            return $this;
        } finally { if($loaded) imagedestroy($source); }
    }

    /**
     * 功能说明：按比例缩放画布（可选择返回图像源）
     *
     *
     * 资源所有权：未传img时本实例接管新画布；显式传img时返回的新句柄归调用方，须另行释放。传入的已有GD句柄不因成功返回而自动释放。
     *
     * @param int|float|array $rate 正缩放倍率，或目标边界[宽,高]；边界模式保持比例且不放大
     * @param resource|string|null $img GD图像句柄或图片路径；null修改当前画布，传入时返回新句柄（默认：NULL）
     * @return $this|resource|false img为null时替换当前画布；传入图像时返回新句柄；比例/尺寸/加载无效为false
     */
    public function resize($rate, $img = NULL) {
        if($return = is_null($img)) $img = $this->img;
        if(!$this->check($img) && (($img=$this->load($img))===false)) return false;
        list($width, $height) = $this->getSize($img);
        if(is_array($rate)) {
            list($new_width, $new_height) = $rate;
            $new_width = min($new_width, $width);
            $new_height = min($new_height, $height);
            $rate = min($new_width/$width, $new_height/$height);
        }
        if(!is_numeric($rate) || $rate <= 0) return false;
        $new_width = max(1, (int)round($width * $rate));
        $new_height = max(1, (int)round($height * $rate));
        if(!self::validDimensions($new_width, $new_height)) return false;
        if(function_exists('imagecopyresampled')) {
            $new_img = imagecreatetruecolor($new_width, $new_height);
            imagealphablending($new_img, false);
            imagesavealpha($new_img, true);
            imagecopyresampled($new_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        } else {
            $new_img = imagecreate($new_width, $new_height);
            imagecopyresized($new_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        }
        if($return) {
            $this->destroy();
            $this->img = $new_img;
            $this->width = $new_width;
            $this->height = $new_height;
            return $this;
        } else {
            return $new_img;
        }
    }

    /**
     * 功能说明：截取画布的一部分（可选择返回图像源）
     *
     *
     * 资源所有权：未传img时本实例接管新画布；显式传img时返回的新句柄归调用方，须另行释放。传入的已有GD句柄不因成功返回而自动释放。
     *
     * @param array $point 坐标数组[x,y]，具体原点/方向由当前绘图入口决定
     * @param int|float $width 宽度，图像/画布场景以像素为单位
     * @param int|float $height 高度，图像/画布场景以像素为单位
     * @param resource|string|null $img GD图像句柄或图片路径；null修改当前画布，传入时返回新句柄（默认：NULL）
     * @return $this|resource|false img为null时替换当前画布；指定图像时返回裁剪句柄；坐标/尺寸/加载无效为false
     */
    public function crop($point, $width, $height, $img = NULL) {
        if($flag = is_null($img)) $img = $this->img;
        if(!$this->check($img) && (($img=$this->load($img))===false)) return false;
        if(!$this->checkPoint($point) || !self::validDimensions($width, $height)) return false;
        $new_img = imagecreatetruecolor($width, $height);
        imagealphablending($new_img, false);
        imagesavealpha($new_img, true);
        imagecopyresampled($new_img, $img, 0, 0, $point[0], $point[1], $width, $height, $width, $height);
        if(!$flag) {
            return $new_img;
        } else {
            $this->destroy();
            $this->img = $new_img;
            $this->width = $width;
            $this->height = $height;
            return $this;
        }
    }

    /**
     * 功能说明：将一图像源粘贴到画布的某一位置
     *
     * @param resource|string $img 待粘贴的GD图像句柄或图片路径
     * @param array $point 坐标数组[x,y]，具体原点/方向由当前绘图入口决定（默认：array(0, 0)）
     * @param int $alpha 1至100执行透明合成，非正值执行灰阶合成，大于100直接拷贝（默认：100）
     * @return $this|false 粘贴后返回当前实例；源图像加载失败为false
     */
    public function paste($img, $point = array(0, 0), $alpha = 100) {
        if(!$this->check($img) && (($img=$this->load($img))===false)) return false;
        if($alpha <= 0) {
            imagecopymergegray($this->img, $img, $point[0], $point[1], 0, 0, imagesx($img), imagesy($img), $alpha);
        } elseif($alpha > 0 && $alpha <= 100) {
            imagecopymerge($this->img, $img, $point[0], $point[1], 0, 0, imagesx($img), imagesy($img), $alpha);
        } else {
            imagecopy($this->img, $img, $point[0], $point[1], 0, 0, imagesx($img), imagesy($img));
        }
        return $this;
    }

    /**
     * 功能说明：设定画线的粗细、风格、笔刷
     *
     * @param int $thickness 绘线厚度，单位像素（默认：1）
     * @param array|null $style GD线条样式颜色序列，null不设置（默认：NULL）
     * @param resource|null $brush GD画笔图像句柄，null不设置（默认：NULL）
     * @return $this 返回当前实例，支持链式调用。
     */
    public function setLine($thickness = 1, $style = NULL, $brush = NULL) {
        imagesetthickness($this->img, $thickness);
        if(is_array($style)) {
            $style_lst = array();
            $m=count($style);
            for($i=0; $i<$m; $i++) {
                if(isset($this->color_lst[$style[$i]])) $style_lst[] = $this->color_lst[$style[$i]];
            }
            imagesetstyle($this->img, $style_lst);
        }
        if(!is_null($brush)) {
            if($this->check($brush) || (($brush = $this->load($brush))!==false)) {
                imagecolortransparent($brush, $this->color_lst['black']);
                imagesetbrush($this->img, $brush);
            }
        }
        return $this;
    }

    /**
     * 功能说明：为图像添加滤镜效果
     *
     * @param int $filtertype GD的IMG_FILTER_*滤镜常量（默认：IMG_FILTER_COLORIZE）
     * @param array $args 按目标函数顺序传入的附加参数（默认：array()）
     * @return $this 返回当前实例，支持链式调用。
     */
    public function setFilter($filtertype = IMG_FILTER_COLORIZE, $args = array()) {
        if(!is_array($args)) $args = array($args);
        $args = array_merge(array($this->img, $filtertype), $args);
        call_user_func_array('imagefilter', $args);
        return $this;
    }

    /**
     * 功能说明：返回图像源的宽、高
     *
     * @param resource|null $img GD图像句柄；null使用当前画布（默认：NULL）
     * @return array [宽,高]像素二元组；当前/指定句柄无效时返回[0,0]
     * @phpstan-return array{int,int}
     */
    public function getSize($img = NULL) {
        if(is_null($img)) $img = $this->img;
        if(!$this->check($img)) return array(0, 0);
        return array(imagesx($img), imagesy($img));
    }

    /**
     * 功能说明：取得画布某点的颜色
     *
     * @param array $point 坐标数组[x,y]，具体原点/方向由当前绘图入口决定
     * @param bool $mode true返回GD颜色索引，false返回RGBA组成信息（默认：true）
     * @return $this|array|int|false mode为true返回颜色索引，为false返回RGBA映射；无效点/画布可返回自身，GD读取失败为false
     * @phpstan-return $this|array{red:int,green:int,blue:int,alpha:int}|int|false
     */
    public function getColor($point, $mode = true) {
        if(!$this->check()) return $this;
        if(!$this->checkPoint($point)) return $this;
        if(($color_index = imagecolorat($this->img, $point[0], $point[1]))!==false) {
            $color_tran = imagecolorsforindex($this->img, $color_index);
            if($mode) {
                return imagecolorallocate($this->img, $color_tran['red'], $color_tran['green'], $color_tran['blue']);
            } else {
                return $color_tran;
            }
        }
        return false;
    }

    /**
     * 功能说明：设置颜色
     *
     * @param string $idx 颜色索引名，空串自动分配；附加参数指定RGB及透明度（默认：''）
     * @param mixed ...$args [R,G,B]及可选透明度，或R/G/B及可选透明度；GD透明度0至127（历史位置参数，由func_get_args读取）
     * @return $this 返回当前实例，支持链式调用。
     */
    public function setColor($idx = '') {
        if(empty($idx)) $idx = 'idx_'.(count($this->color_lst)+1);
        $alpha = 0;
        if(func_num_args()==2) {
            $color = func_get_arg(1);
        } elseif(func_num_args()==3) {
            $color = func_get_arg(1);
            $alpha = func_get_arg(2);
        } elseif(func_num_args()==4) {
            $color = array_slice(func_get_args(), 1);
        } elseif(func_num_args()==5) {
            $color = array_slice(func_get_args(), 1, -1);
            $alpha = func_get_arg(4);
        } else {
            $color = array(0, 0, 0);
            $alpha = 127;
        }
        imagealphablending($this->img, true);
        imagesavealpha($this->img, true);
        if(isset($this->color_lst[$idx])) imagecolordeallocate($this->img, $this->color_lst[$idx]);
        $this->color_lst[$idx] = imagecolorallocatealpha($this->img, $color[0], $color[1], $color[2], $alpha);
        $color_mask = array();
        $color_mask[0] = $color[0]<50 ? 0 : $color[0]-50;
        $color_mask[1] = $color[1]<50 ? 0 : $color[1]-50;
        $color_mask[2] = $color[2]<50 ? 0 : $color[2]-50;
        if(isset($this->color_lst[$idx.'_mask'])) imagecolordeallocate($this->img, $this->color_lst[$idx.'_mask']);
        $this->color_lst[$idx.'_mask'] = imagecolorallocatealpha($this->img, $color_mask[0], $color_mask[1], $color_mask[2], $alpha);
        return $this;
    }

    /**
     * 功能说明：从一点向画布右下填充
     *
     * @param array $point 泛洪填充起点[x,y]，不是矩形区域边界（默认：array(0, 0)）
     * @param string|int $color 已登记颜色的索引名；未登记时采用随机颜色（默认：''）
     * @param int|null $color_border GD边界颜色值；null普通泛洪填充，非null填充到该颜色边界（默认：NULL）
     * @return $this 返回当前实例，支持链式调用。
     */
    public function fill($point = array(0, 0), $color = '', $color_border = NULL) {
        $color = isset($this->color_lst[$color]) ? $this->color_lst[$color] : $this->randomColor();
        if(is_null($color_border)) {
            imagefill($this->img, $point[0], $point[1], $color);
        } else {
            imagefilltoborder($this->img, $point[0], $point[1], $color_border, $color);
        }
        return    $this;
    }

    /**
     * 功能说明：画线
     *
     * @param array $p_start 线段起点坐标[x,y]
     * @param array $p_end 线段终点坐标[x,y]
     * @param string|int $color 已登记颜色的索引名；未登记时采用随机颜色（默认：''）
     * @return $this 返回当前实例，支持链式调用。
     */
    public function drawLine($p_start, $p_end, $color='') {
        if($color=="style") {
            $color = IMG_COLOR_STYLED;
        } elseif($color=="brush") {
            $color = IMG_COLOR_BRUSHED;
        } elseif($color=="style_brush") {
            $color = IMG_COLOR_STYLEDBRUSHED;
        } else {
            $color = isset($this->color_lst[$color]) ? $this->color_lst[$color] : $this->randomColor();
        }
        imageline($this->img, $p_start[0], $p_start[1], $p_end[0], $p_end[1], $color);
        return $this;
    }

    /**
     * 功能说明：画矩形
     *
     * @param array $point 矩形左上角在画布上的坐标[x,y]
     * @param int|float $width 宽度，图像/画布场景以像素为单位
     * @param int|float $height 高度，图像/画布场景以像素为单位
     * @param string|int $color_border 已登记颜色的索引名；未登记时采用随机颜色（默认：''）
     * @param string|int $color_fill 已登记颜色的索引名；未登记时采用随机颜色；空串不执行填充（默认：''）
     * @return $this 返回当前实例，支持链式调用。
     */
    public function drawRectangle($point, $width, $height, $color_border = '', $color_fill = '') {
        if(!empty($color_fill)) {
            $color = isset($this->color_lst[$color_fill]) ? $this->color_lst[$color_fill] : $this->randomColor();
            imagefilledrectangle($this->img, $point[0], $point[1], $point[0]+$width, $point[1]+$height, $color);
        }
        $color = isset($this->color_lst[$color_border]) ? $this->color_lst[$color_border] : $this->randomColor();
        imagerectangle($this->img, $point[0], $point[1], $point[0]+$width, $point[1]+$height, $color);
        return $this;
    }

    /**
     * 功能说明：画多边形
     *
     * @param array $points 依绘制顺序排列的二维坐标列表
     * @param string|int $color_border 已登记颜色的索引名；未登记时采用随机颜色（默认：''）
     * @param string|int $color_fill 已登记颜色的索引名；未登记时采用随机颜色；空串不执行填充（默认：''）
     * @return $this 返回当前实例，支持链式调用。
     */
    public function drawPolygon($points, $color_border = '', $color_fill = '') {
        if(count($points)==1) {
            $color = isset($this->color_lst[$color_border]) ? $this->color_lst[$color_border] : $this->randomColor();
            imagesetpixel($this->img, $points[0][0], $points[0][1], $color);
        }    elseif(count($points)==2) {
            $this->drawLine($points[0], $points[1], $color_border);
        } else {
            $point_lst = array();
            $count = 0;
            foreach($points as $value) {
                if($this->checkPoint($value)) {
                    $point_lst = array_merge($point_lst, $value);
                    $count++;
                }
            }
            if(!empty($color_fill)) {
                $color = isset($this->color_lst[$color_fill]) ? $this->color_lst[$color_fill] : $this->randomColor();
                imagefilledpolygon($this->img, $point_lst, $count, $color);
            }
            $color = isset($this->color_lst[$color_border]) ? $this->color_lst[$color_border] : $this->randomColor();
            imagepolygon($this->img, $point_lst, $count, $color);
        }
        return $this;
    }

    /**
     * 功能说明：画五角星
     *
     * @param array $point 图形中心在画布上的坐标[x,y]
     * @param int|float $radius 图形外接半径，单位像素
     * @param string|int $color_border 已登记颜色的索引名；未登记时采用随机颜色（默认：''）
     * @param string|int $color_fill 已登记颜色的索引名；未登记时采用随机颜色；空串不执行填充（默认：''）
     * @param bool $star true绘制星形，false绘制正多边形（默认：true）
     * @param float $spiky 星形内外半径比例（默认：0.5）
     * @return $this 委托drawPolygon绘制后返回当前实例
     */
    public function drawFiveSidedStar($point, $radius, $color_border = '', $color_fill = '', $star=true, $spiky=0.5) {
        $x = $point[0];
        $y = $point[1];
        $angle = 360/5;
        $points = array();
        $points[0][0] = $x;
        $points[0][1] = $y - $radius;
        $points[2][0] = $x + ($radius * cos(deg2rad(90 - $angle)));
        $points[2][1] = $y - ($radius * sin(deg2rad(90 - $angle)));
        $points[4][0] = $x + ($radius * sin(deg2rad(180 - ($angle*2))));
        $points[4][1] = $y + ($radius * cos(deg2rad(180 - ($angle*2))));
        $points[6][0] = $x - ($radius * sin(deg2rad(180 - ($angle*2))));
        $points[6][1] = $y + ($radius * cos(deg2rad(180 - ($angle*2))));
        $points[8][0] = $x - ($radius * cos(deg2rad(90 - $angle)));
        $points[8][1] = $y - ($radius * sin(deg2rad(90 - $angle)));
        if($star) {
             $indent = $radius * $spiky;
             $points[1][0] = $x + ($indent * cos(deg2rad(90 - $angle/2)));
             $points[1][1] = $y - ($indent * sin(deg2rad(90 - $angle/2)));
             $points[3][0] = $x + ($indent * sin(deg2rad(180 - $angle)));
             $points[3][1] = $y - ($indent * cos(deg2rad(180 - $angle)));
             $points[5][0] = $x;
             $points[5][1] = $y + ($indent * sin(deg2rad(180 - $angle)));
             $points[7][0] = $x - ($indent * sin(deg2rad(180 - $angle)));
             $points[7][1] = $y - ($indent * cos(deg2rad(180 - $angle)));
             $points[9][0] = $x - ($indent * cos(deg2rad(90 - $angle/2)));
             $points[9][1] = $y - ($indent * sin(deg2rad(90 - $angle/2)));
        }
        ksort($points);
        $point_lst = array();
        foreach($points as $value) {
            $point_lst[] = $value;
        }
        return $this->drawPolygon($point_lst, $color_border, $color_fill);
    }

    /**
     * 功能说明：画椭圆
     *
     * @param array $point 图形中心在画布上的坐标[x,y]
     * @param int|float $width 宽度，图像/画布场景以像素为单位
     * @param int|float $height 高度，图像/画布场景以像素为单位
     * @param string|int $color_border 已登记颜色的索引名；未登记时采用随机颜色（默认：''）
     * @param string|int $color_fill 已登记颜色的索引名；未登记时采用随机颜色；空串不执行填充（默认：''）
     * @return $this 返回当前实例，支持链式调用。
     */
    public function drawEllipse($point, $width, $height, $color_border = '', $color_fill = '') {
        if(!empty($color_fill)) {
            $color = isset($this->color_lst[$color_fill]) ? $this->color_lst[$color_fill] : $this->randomColor();
            imagefilledellipse($this->img, $point[0], $point[1], $width, $height, $color);
        }
        $color = isset($this->color_lst[$color_border]) ? $this->color_lst[$color_border] : $this->randomColor();
        imageellipse($this->img, $point[0], $point[1], $width, $height, $color);
        return $this;
    }

    /**
     * 功能说明：画圆弧
     *
     * @param array $point 图形中心在画布上的坐标[x,y]
     * @param int|float $width 宽度，图像/画布场景以像素为单位
     * @param int|float $height 高度，图像/画布场景以像素为单位
     * @param array $arc 起始/结束角度数组[start,end]，单位度（默认：array(0, 360)）
     * @param string|int $color_border 已登记颜色的索引名；未登记时采用随机颜色（默认：''）
     * @param string|int $color_fill 已登记颜色的索引名；未登记时采用随机颜色；空串不执行填充（默认：''）
     * @return $this 返回当前实例，支持链式调用。
     */
    public function drawArc($point, $width, $height, $arc = array(0, 360), $color_border = '', $color_fill = '') {
        if(!empty($color_fill)) {
            $color = isset($this->color_lst[$color_fill]) ? $this->color_lst[$color_fill] : $this->randomColor();
            imagefilledarc($this->img, $point[0], $point[1], $width, $height, $arc[0], $arc[1], $color, IMG_ARC_PIE);
        }
        $color = isset($this->color_lst[$color_border]) ? $this->color_lst[$color_border] : $this->randomColor();
        imagearc($this->img, $point[0], $point[1], $width, $height, $arc[0], $arc[1], $color);
        return $this;
    }

    /**
     * 功能说明：画饼图
     *
     * @param array $data 按扇区顺序排列的非负数值列表
     * @param array $point 图形中心在画布上的坐标[x,y]
     * @param int|float $width 宽度，图像/画布场景以像素为单位
     * @param int|float $height 高度，图像/画布场景以像素为单位
     * @param int $mask 饼图立体厚度，单位像素（默认：20）
     * @param int|float $ext_value 额外计入总量但不绘制的数值（默认：0）
     * @param int|float $distance 扇区向外分离的距离，单位像素（默认：0）
     * @param int|float $start_angle 首个扇区起始角度（默认：0）
     * @return $this 返回当前实例，支持链式调用。
     */
    public function drawPie($data, $point, $width, $height, $mask = 20, $ext_value = 0, $distance = 0, $start_angle = 0) {
        $data_sum = array_sum($data) + $ext_value;
        if($data_sum <= 0) return $this;
        $angle = array();
        $angle_sum = array($start_angle);
        $m=count($data);
        for($i=0; $i<$m; $i++) {
            $angle[] = (($data[$i] / $data_sum) * 360);
             $angle_sum[] = array_sum($angle) + $start_angle;
        }
        for($i=$point[1]+$mask; $i>$point[1]; $i--) {
            $m=count($data);
            for($j=1; $j<=$m; $j++) {
                if($angle_sum[$j-1] == $angle_sum[$j]) continue;
                $point_cur = $point;
                if($distance>0) {
                    $point_cur[0] += round($distance * cos(deg2rad($angle_sum[$j] - $angle[$j-1] / 2)));
                    $point_cur[1] = $i + round($distance * sin(deg2rad($angle_sum[$j] - $angle[$j-1] / 2)));
                } else {
                    $point_cur[1] = $i;
                }
                $this->drawArc($point_cur, $width, $height, array($angle_sum[$j-1], $angle_sum[$j]), 'pie_'.$j.'_mask', 'pie_'.$j.'_mask');
            }
        }
        $m=count($data);
        for($j=1; $j<=$m; $j++) {
            if($angle_sum[$j-1] == $angle_sum[$j]) continue;
            $point_cur = $point;
            if($distance>0) {
                $point_cur[0] += round($distance * cos(deg2rad($angle_sum[$j] - $angle[$j-1] / 2)));
                $point_cur[1] += round($distance * sin(deg2rad($angle_sum[$j] - $angle[$j-1] / 2)));
            }
            $this->drawArc($point_cur, $width, $height, array($angle_sum[$j-1], $angle_sum[$j]), 'pie_'.$j, 'pie_'.$j);
        }
        return $this;
    }

    /**
     * 功能说明：画折线
     *
     * @param array $points 依绘制顺序排列的二维坐标列表
     * @param string|int $color_line 已登记颜色的索引名；未登记时采用随机颜色（默认：''）
     * @return $this 返回当前实例，支持链式调用。
     */
    public function drawZigzag($points, $color_line = '') {
        if(count($points)==1) {
            $color = isset($this->color_lst[$color_line]) ? $this->color_lst[$color_line] : $this->randomColor();
            imagesetpixel($this->img, $points[0][0], $points[0][1], $color);
        }    elseif(count($points)==2) {
            $this->drawLine($points[0], $points[1], $color_line);
        } else {
            $m=count($points)-1;
            for($i=0; $i<$m; $i++) {
                $this->drawLine($points[$i], $points[$i+1], $color_line);
            }
        }
        return $this;
    }

    /**
     * 功能说明：添加文字
     *
     * @param string $text 要绘制或测量的文本
     * @param array $point 坐标数组[x,y]，具体原点/方向由当前绘图入口决定
     * @param string|int $color 已登记颜色的索引名；未登记时采用随机颜色（默认：''）
     * @param string $font 字体文件路径或实现支持的字体名（默认：''）
     * @param int $font_size 文字大小（默认：12）
     * @param int|float $angle 旋转或文字倾斜角度，单位度（默认：0）
     * @return $this 返回当前实例，支持链式调用。
     */
    public function drawString($text, $point, $color='', $font='', $font_size = 12, $angle = 0) {
        $color = isset($this->color_lst[$color]) ? $this->color_lst[$color] : $this->randomColor();
        $func = ($font == 'up' ? 'imagestringup' : 'imagestring');
        if(empty($font) || $font=='up') $font = $this->font;
        if(empty($font)) {
            $font_size = floor($font_size/3);
            $func($this->img, $font_size, $point[0], $point[1], $text, $color);
        } else {
            imagettftext($this->img, $font_size, $angle, $point[0], $point[1], $color, $font, $text);
        }
        return $this;
    }

    /**
     * 功能说明：设置字体
     *
     * @param string $font 字体文件路径或实现支持的字体名
     * @return $this 返回当前实例，支持链式调用。
     */
    public function setFont($font) {
        if(is_file($font)) {
            $this->font = realpath($font);
        }
        return $this;
    }

    /**
     * 功能说明：取得字符串的占位大小
     *
     * @param string|int $text 待测量文字；未设置TTF或size为0时，数值表示GD内置字体编号
     * @param int $size 待测量文字的字号（默认：12）
     * @param int|float $angle 旋转或文字倾斜角度，单位度（默认：0）
     * @return array 二元数组[宽,高]，单位像素；未设置TTF时按内置字体测量
     * @phpstan-return array{int,int}
     */
    public function getFontSize($text, $size = 12, $angle = 0) {
        if($size == 0 || empty($this->font)) {
            if(!is_numeric($text)) $text = 4;
            $result = array(imagefontwidth($text), imagefontheight($text));
        } else {
            $points = imagettfbbox($size, $angle, $this->font, $text);
            $result = [max($points[0],$points[2],$points[4],$points[6])-min($points[0],$points[2],$points[4],$points[6]), max($points[1],$points[3],$points[5],$points[7])-min($points[1],$points[3],$points[5],$points[7])];
        }
        return $result;
    }

    /**
     * 功能说明：添加干扰点
     *
     * @param int $number 需要添加的随机干扰点数量
     * @return $this 返回当前实例，支持链式调用。
     */
    public function addNoise($number) {
        for($i=0; $i<$number; $i++) {
            imageSetPixel($this->img, rand(0, $this->width-1), rand(0, $this->height-1), $this->randomColor(true));
        }
        return $this;
    }

    /**
     * 功能说明：输出图像源到浏览器或文件
     *
     * @param string $type 输出图像格式，空串沿用当前格式（默认：''）
     * @param string $file 保存路径；空串直接输出图像响应（默认：''）
     * @return $this|false 输出/保存调用完成后返回当前实例；没有有效图像为false，底层编码错误不保证由返回值反映
     */
    public function make($type='', $file='') {
        if(empty($type)) $type = $this->dst_type;
        if(!$this->check($this->img)) return false;
        switch(true) {
            case (strtolower($type)=='gif' && (imagetypes() & IMG_GIF)):
                $func = 'imagegif';
                $contentType = 'image/gif';
                break;
            case (strtolower($type)=='png' && (imagetypes() & IMG_PNG)):
                $func = 'imagepng';
                $contentType = 'image/png';
                break;
            case (strtolower($type)=='wbmp' && (imagetypes() & IMG_WBMP)):
                $func = 'imagewbmp';
                $contentType = 'image/vnd.wap.wbmp';
                break;
            case (strtolower($type)=='bmp'):
                $func = 'imagebmp';
                $contentType = 'image/bmp';
                break;
            default:
                $func = 'imagejpeg';
                $contentType = 'image/jpeg';
                break;
        }

        if(empty($file)) {
            header('Content-type: '.$contentType);
            $file = null;
        } else {
            if(!file_exists(dirname($file))) mkdir(dirname($file), 0777, true);
        }
        $func($this->img, $file);
        return $this;
    }

    /**
     * 功能说明：释放图像源
     *
     * @param resource|null $img GD图像句柄；null使用当前画布（默认：NULL）
     * @return bool 指定或当前GD句柄的释放结果；无有效句柄为false
     */
    public function destroy($img = NULL) {
        $own = is_null($img);
        if($own) $img = $this->img;
        if(!$this->check($img)) return false;
        $result = imagedestroy($img);
        if($own) $this->img = null;
        return $result;
    }

    /**
     * 功能说明：字符集转换
     *
     * @param string $str 待处理的字符串
     * @param string $charset 无myString时后备分支使用的字符编码提示；正常myString分支固定转为UTF-8（默认：'gbk'）
     * @return string|false 通常转换为UTF-8；兼容后备分支可能沿用检测编码，iconv失败可为false
     */
    public function transString($str, $charset = 'gbk') {
        switch(true) {
            case class_exists('myString'):
                $str = myString::setCharset($str, 'utf-8');
                break;
            case function_exists('mb_detect_encoding'):
                $charset = mb_detect_encoding($str, array('UTF-8', 'GBK', 'BIG5', 'ASCII', 'EUC-CN', 'ISO-8859-1', 'windows-1251', 'Shift-JIS'));
                if($charset=='CP936') $charset = 'GBK';
                $str = mb_convert_encoding($str, $charset, 'UTF-8, GBK');
                break;
            case function_exists('iconv'):
                $str = iconv($charset, 'UTF-8//IGNORE', $str);
                break;
        }
        return $str;
    }

    /**
     * 功能说明：检测点的有效性
     *
     * @param array ...$args 需要逐个校验的[x,y]数值坐标数组，至少一个（历史位置参数，由func_get_args读取）
     * @return bool 至少一个参数且所有参数均为两个数值组成的数组时为true，否则为false
     */
    public function checkPoint() {
        $count = func_num_args();
        if($count==0) return false;
        for($i=0; $i<$count; $i++) {
            $point = func_get_arg($i);
            if(!is_array($point)) return false;
            if(count($point)!=2) return false;
            if(!is_numeric($point[0]) || !is_numeric($point[1])) return false;
        }
        return true;
    }

    /**
     * 功能说明：检测图像源的有效性
     *
     * @param resource|null $img GD图像句柄；null使用当前画布（默认：NULL）
     * @return bool 目标为有效GD资源时为true；null检查当前画布
     */
    public function check($img = NULL) {
        if(is_null($img)) $img = $this->img;
        if(is_null($img)) return false;
        return ((is_resource($img) && get_resource_type($img)=='gd') || $img instanceof \GdImage);
    }

    /**
     * 功能说明：读取GIF信息
     *
     * @param string $filename 附件展示名称或文件路径，按方法用途解释
     * @return array|false 含signatur/version/width/height/flag/trans_red/trans_green/trans_blue的GIF信息；头/调色板无效为false
     * @phpstan-return array{signatur:string,version:string,width:int,height:int,flag:int,trans_red:int,trans_green:int,trans_blue:int}|false
     */
    public function gif_info($filename) {
        $fp = @fopen($filename, 'rb');
        if(!$fp) return false;
        try {
            $header = fread($fp, 13);
            if(strlen($header) !== 13 || !in_array(substr($header, 0, 6), ['GIF87a','GIF89a'], true)) return false;
            $packed = ord($header[10]); $palette = '';
            if($packed & 0x80) {
                $size = 3*(1 << (($packed & 7)+1));
                $palette = fread($fp, $size);
                if(strlen($palette) !== $size) return false;
            }
            $offset = ord($header[11])*3;
            $rgb = strlen($palette) >= $offset+3 ? array_values(unpack('C3', substr($palette, $offset, 3))) : [0,0,0];
            $dimensions = unpack('vwidth/vheight', substr($header, 6, 4));
            return ['signatur'=>'GIF', 'version'=>substr($header,3,3), 'width'=>$dimensions['width'], 'height'=>$dimensions['height'],
                'flag'=>$packed >> 7, 'trans_red'=>$rgb[0], 'trans_green'=>$rgb[1], 'trans_blue'=>$rgb[2]];
        } finally { fclose($fp); }
    }

    /**
     * 功能说明：生成验证码
     *
     * @param string $str 待处理的字符串
     * @param string $font 字体文件路径或实现支持的字体名
     * @param int $fontsize 验证码文字大小（默认：16）
     * @return void 无返回值。
     */
    public function captcha($str, $font, $fontsize = 16) {
        $this->setFont($font);
        list($this->width, $this->height) = $this->getFontSize($str, $fontsize);
        $this->width += $fontsize*2;
        $this->height += $fontsize;
        $this->destroy();
        $this->create(true, array(255, 255, 255));
        $this->setColor('vertify', rand(220, 255), rand(220, 255), rand(220, 255));
        $this->setLine(1);
        $this->drawRectangle(array(0, 0), $this->width-2, $this->height-2, 'black', 'vertify');
        if(class_exists('myString')) {
            $char_lst = myString::breakStr($str);
        } else {
            preg_match_all('/[\xa0-\xff]?./', $str, $arr);
            $char_lst = $arr[0];
        }
        $top = 0;
        $left = 0;
        $m=count($char_lst);
        for($i=0; $i<$m; $i++) {
            $char = $char_lst[$i];
            if($char=='\r') {
                $top += $fontsize+$fontsize/2;
                $left = 0;
                continue;
            }
             $randsize = ceil(rand($fontsize-$fontsize/6, $fontsize+$fontsize/6));
             $randAngle = rand(-15, 15);
             $x = $fontsize/3 + rand($left-$fontsize/6, $left+$fontsize/6);
             $y = $fontsize + rand(0, $fontsize/2);
             $this->drawString($this->transString($char), array($x, $top + $y), 'black', $font, $randsize, $randAngle);
            $left += $fontsize * strlen($char) - $fontsize/6;
        }
        $this->addNoise(max($this->width, $this->height)*2);
        $this->make('jpg');
    }

    /**
     * 功能说明：添加图片或文字水印并输出到浏览器或目标文件
     *
     * @param string $watermark 水印图片路径；路径不存在时作为文字水印
     * @param int $position POS_*位置常量；未匹配的位置数值作为居中水印的旋转角度（默认：1）
     * @param string $img_dst 输出图片路径，空串按方法约定输出或修改当前实例（默认：''）
     * @param array $para 图片支持rate/alpha；文字支持alpha/font/fontsize/fontcolor/bgcolor（默认：array()）
     * @return $this 水印处理及输出后返回当前实例
     */
    public function watermark($watermark, $position=1, $img_dst='', $para=array()) {
        $this->dst_type = 'jpg';
        if(!empty($img_dst)) {
            if(!file_exists(dirname($img_dst))) mkdir(dirname($img_dst), 0777, true);
            $this->dst_type = pathinfo($img_dst, PATHINFO_EXTENSION);
        }
        if(is_file($watermark)) {
            $new_watermark = $watermark;
            $img_wm = new myImg($new_watermark);
            list($wm_width, $wm_height) = $img_wm->getSize();
            if($this->width<$wm_width || $this->height<$wm_height) {
                $this->make($this->dst_type, $img_dst);
                $img_wm->destroy();
                return $this;
            }
            $rate = 4;
            if(isset($para['rate'])) $rate = $para['rate'];
            $alpha = 60;
            if(isset($para['alpha'])) $alpha = $para['alpha'];
            if($rate!=1) {
                $wm_rate = min($this->width/$rate/$wm_width, $this->height/$rate/$wm_height);
                $wm_width *= $wm_rate;
                $wm_height *= $wm_rate;
                $img_wm->resize($wm_rate);
                $img_wm->setTransparent(array(0, 0));
            }
            switch($position) {
                case self::POS_RB: //right-bottom
                    $pos = array($this->width - $wm_width, $this->height - $wm_height);
                    break;
                case self::POS_RT:    //right-top
                    $pos = array($this->width - $wm_width, 0);
                    break;
                case self::POS_LB:    //left-bottom
                    $pos = array(0, $this->height - $wm_height);
                    break;
                case self::POS_LT:    //left-top
                    $pos = array(0, 0);
                    break;
                case self::POS_LM:    //left-middle
                    $pos = array(0, ($this->height - $wm_height)/2);
                    break;
                case self::POS_RM:    //right-middle
                    $pos = array($this->width - $wm_width, ($this->height - $wm_height)/2);
                    break;
                case self::POS_MT:    //middle-top
                    $pos = array(($this->width - $wm_width)/2, 0);
                    break;
                case self::POS_MB:    //middle-bottom
                    $pos = array(($this->width - $wm_width)/2, $this->height - $wm_height);
                    break;
                                case self::POS_M:    //middle
                default:
                    if(is_numeric($position)) {
                        $img_wm->rotate($position);
                        $wm_width = $img_wm->width;
                        $wm_height = $img_wm->height;
                    }
                    $pos = array(($this->width - $wm_width)/2, ($this->height - $wm_height)/2);
                    break;
            }
            $this->paste($img_wm->img, $pos, $alpha);
            $this->make($this->dst_type, $img_dst);
            $img_wm->destroy();
        } else {
            $alpha = isset($para['alpha']) ? $para['alpha'] : 100;
            $font = isset($para['font']) ? $para['font'] : 'font.ttc';
            if(!is_file($font)) $font = false;
            $fontsize = isset($para['fontsize']) ? $para['fontsize'] : (($position==5 || $position==6) ? $this->height/80 : $this->width/80);
            if($fontsize<12) $fontsize = 12;
            $fontcolor = isset($para['fontcolor']) ? $para['fontcolor'] : 'white';
            $bgcolor = isset($para['bgcolor']) ? $para['bgcolor'] : null;
            if(preg_match('/(#)?[0-9a-f]{6}/i', $bgcolor)) $bgcolor = array_map('hexdec', str_split(str_replace('#', '', $bgcolor), '2'));

            if($this->setFont($font)) {
                $font_size = $this->getFontSize($watermark, $fontsize);
                $font_size[0] += 10;
                $font_size[1] += 10;
                $img_txt = new myImg($font_size[0], $font_size[1], array(true, $bgcolor));
                if(preg_match('/(#)?[0-9a-f]{6}/i', $fontcolor)) {
                    $img_txt->setColor('fontcolor', array_map('hexdec', str_split(str_replace('#', '', $fontcolor), '2')));
                    $fontcolor = 'fontcolor';
                }
                $img_txt->drawString($img_txt->transString($watermark), array(5, $font_size[1]-5), $fontcolor, $font, $fontsize);

                if($position==5) {
                    $img_txt->rotate(270);
                } elseif($position==6) {
                    $img_txt->rotate(90);
                }
                switch($position) {
                    case self::POS_RB:
                    case self::POS_RT:
                    case self::POS_LB:
                    case self::POS_LT:
                    case self::POS_MT:
                    case self::POS_MB:
                        $width = $this->width;
                        $height = $this->height + $font_size[1];
                        break;
                    case self::POS_LM:
                    case self::POS_RM:
                        $width = $this->width + $font_size[1];
                        $height = $this->height;
                        break;
                                        case self::POS_M:
                    default:
                        $width = $this->width;
                        $height = $this->height;
                        break;
                }
                $img_out = new myImg($width, $height);
                $img_out->create(true, $bgcolor);

                switch($position) {
                    case 1: //right-bottom
                        $img_out->paste($this->img, array(0, 0));
                        $img_out->paste($img_txt->img, array($this->width - $img_txt->width, $this->height), $alpha);
                        break;
                    case 2:    //right-top
                        $img_out->paste($this->img, array(0, $font_size[1]));
                        $img_out->paste($img_txt->img, array($this->width - $img_txt->width, 0), $alpha);
                        break;
                    case 3:    //left-bottom
                        $img_out->paste($this->img, array(0, 0));
                        $img_out->paste($img_txt->img, array(0, $this->height), $alpha);
                        break;
                    case 4:    //left-top
                        $img_out->paste($this->img, array(0, $font_size[1]));
                        $img_out->paste($img_txt->img, array(0, 0), $alpha);
                        break;
                    case 5:    //left-middle
                        $img_out->paste($this->img, array($font_size[1], 0));
                        $img_out->paste($img_txt->img, array(0, ($this->height - $font_size[0])/2), $alpha);
                        break;
                    case 6:    //right-middle
                        $img_out->paste($this->img, array(0, 0));
                                                $img_out->paste($img_txt->img, array($this->width, ($this->height - $font_size[0])/2), $alpha);
                        break;
                    case 7:    //middle-top
                        $img_out->paste($this->img, array(0, $font_size[1]));
                        $img_out->paste($img_txt->img, array(($this->width - $font_size[0])/2, 0), $alpha);
                        break;
                    case 8:    //middle-bottom
                        $img_out->paste($this->img, array(0, 0));
                        $img_out->paste($img_txt->img, array(($this->width - $font_size[0])/2, $this->height), $alpha);
                        break;
                    default:    //middle
                        if(is_numeric($position)) {
                            $img_txt->rotate($position);
                            $font_size = array($img_txt->width, $img_txt->height);
                        }
                        $img_out->paste($this->img, array(0, 0));
                        $img_out->paste($img_txt->img, array(($this->width - $font_size[0])/2, ($this->height - $font_size[1])/2), $alpha);
                        break;
                }
                $this->destroy();
                $this->img = $img_out->img;
                $this->make($this->dst_type, $img_dst);
                $img_txt->destroy();
                $this->width = $img_out->width;
                $this->height = $img_out->height;
                $img_out->img = null;
            } else {
                $this->drawString($watermark, array(0, 0), $fontcolor, $font, $fontsize);
                $this->make($this->dst_type, $img_dst);
            }
        }
        return $this;
    }

    /**
     * 功能说明：制作缩略图
     *
     * @param int $dstW 缩略图目标宽度，单位像素
     * @param int $dstH 缩略图目标高度，单位像素
     * @param string $img_dst 输出图片路径，空串按方法约定输出或修改当前实例（默认：''）
     * @return $this|false 返回当前实例；目标已存在时直接加载，尺寸/缩放/加载失败为false
     */
    public function thumb($dstW, $dstH, $img_dst='') {
        if(!self::validDimensions($dstW, $dstH) || !$this->check()) return false;
        if(is_file($img_dst)) return $this->init($img_dst);
        $this->dst_type = 'jpg';
        if(!empty($img_dst)) {
            if(!file_exists(dirname($img_dst))) mkdir(dirname($img_dst), 0777, true);
            $this->dst_type = pathinfo($img_dst, PATHINFO_EXTENSION);
        }
        $srcW = $this->width;
        $srcH = $this->height;
        $rate = min($dstW/$srcW, $dstH/$srcH);
        if($this->resize($rate) === false) return false;

        $img_out = new myImg($dstW, $dstH);
        $img_out->create(true, array(0xff, 0xff, 0xff));
        $img_out->setTransparent('white');
        $img_out->paste($this->img, array(($dstW-$srcW*$rate)/2, ($dstH-$srcH*$rate)/2));

        $this->destroy();
        $this->img = $img_out->img;
        $this->make($this->dst_type, $img_dst);
        $this->width = $dstW;
        $this->height = $dstH;
        $img_out->img = null;
        return $this;
    }
}

/**
 * 功能说明：继承myImg的坐标换算及折线、柱状、饼图绘制工具
 *
 * 使用说明：
 *   1. 需要GD及对应图片格式支持；图像句柄在PHP 7.4中通常为resource。
 *   2. 先初始化图像或画布，设置颜色/字体后绘制，make输出或保存。
 *   3. setLimits限制输入大小/尺寸/像素；调用结束后释放不再使用的句柄。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 */
class coordinateMaker extends myImg {
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

    /**
     * 功能说明：初始化实例
     *
     * @param int|float $width 宽度，图像/画布场景以像素为单位（默认：400）
     * @param int|float $height 高度，图像/画布场景以像素为单位（默认：400）
     * @param array $origin 坐标原点[x,y]（默认：array(0, 0)）
     * @param array $args 传给父类create的参数数组：[是否真彩,背景颜色/图片]（默认：array(true, array(0xee, 0xee, 0xee))）
     * @return $this 初始化坐标画布、原点和坐标设置后返回当前实例
     */
    public function init($width = 400, $height = 400, $origin = array(0, 0), $args = array(true, array(0xee, 0xee, 0xee))) {
        $this->width = $width;
        $this->height = $height;
        $this->setOrigin($origin);
        call_user_func_array(array($this, 'create'), $args);
        return $this;
    }

    /**
     * 功能说明：设置原点位置
     *
     * @param array $point 坐标数组[x,y]，具体原点/方向由当前绘图入口决定
     * @return $this 返回当前实例，支持链式调用。
     */
    public function setOrigin($point) {
        if(!$this->checkPoint($point)) return $this;
        $this->origin = $point;
        return $this;
    }

    /**
     * 功能说明：设置坐标参数
     *
     * @param array $para scale_x/scale_y、distance_x/distance_y、start_x/start_y、text_x/text_y和font等坐标配置（默认：array()）
     * @return $this 返回当前实例，支持链式调用。
     */
    public function setPara($para = array()) {
        $this->scale_x = isset($para['scale_x']) ? $para['scale_x'] : 1;
        if(!is_numeric($this->scale_x)) $this->scale_x = 1;
        $this->scale_y = isset($para['scale_y']) ? $para['scale_y'] : 1;
        if(!is_numeric($this->scale_y)) $this->scale_y = 1;
        $this->distance_x = isset($para['distance_x']) ? $para['distance_x'] : 0;
        if(!is_numeric($this->distance_x)) $this->distance_x = 0;
        $this->distance_y = isset($para['distance_y']) ? $para['distance_y'] : 0;
        if(!is_numeric($this->distance_y)) $this->distance_y = 0;
        $this->start_x = isset($para['start_x']) ? $para['start_x'] : 0;
        if(!is_numeric($this->start_x)) $this->start_x = 0;
        $this->start_y = isset($para['start_y']) ? $para['start_y'] : 0;
        if(!is_numeric($this->start_y)) $this->start_y = 0;
        $this->text_x = $this->transString(isset($para['text_x']) ? $para['text_x'] : 'X');
        $this->text_y = $this->transString(isset($para['text_y']) ? $para['text_y'] : 'Y');
        if(isset($para['font'])) $this->setFont($para['font']);
        return $this;
    }

    /**
     * 功能说明：取得相对原点坐标的实际位置
     *
     * @param array $point 坐标数组[x,y]，具体原点/方向由当前绘图入口决定
     * @return array|$this 有效坐标返回[画布x,画布y]；无效点返回当前实例
     */
    public function getPoint($point) {
        if(!$this->checkPoint($point)) return $this;
        return array(($this->origin[0]+$point[0]), $this->origin[1]-$point[1]);
    }

    /**
     * 功能说明：按坐标比例换算点位置，可选择转换到画布坐标
     *
     * @param array $point 坐标数组[x,y]，具体原点/方向由当前绘图入口决定
     * @param bool $trans 是否执行坐标系方向转换（默认：true）
     * @return array|$this 有效坐标返回缩放/转换后的[x,y]；无效点返回当前实例
     */
    public function getScalePoint($point, $trans = true) {
        if(!$this->checkPoint($point)) return $this;
        $point_new = array(($point[0]-$this->start_x)*$this->scale_x, ($point[1]-$this->start_y)*$this->scale_y);
        if(abs($point[0]) < abs($this->start_x)) $point_new[0] = 0;
        if(abs($point[1]) < abs($this->start_y)) $point_new[1] = 0;
        if($trans) $point_new = $this->getPoint($point_new);
        return $point_new;
    }

    /**
     * 功能说明：绘制坐标系
     *
     * @param int $padding 坐标轴与画布边缘的留白像素（默认：20）
     * @return $this 返回当前实例，支持链式调用。
     * @throws InvalidArgumentException 坐标刻度方向无效或刻度数量超过10000
     */
    public function buileCoordinate($padding = 20) {
        foreach([[$this->distance_x,$this->scale_x,$this->width],[$this->distance_y,$this->scale_y,$this->height]] as $axis) {
            if($axis[0] != 0 && ($axis[0]*$axis[1] <= 0 || $axis[2]/($axis[0]*$axis[1]) > 10000)) throw new InvalidArgumentException('Invalid or excessive coordinate ticks');
        }
        $axis_x_start = array($padding, $this->origin[1]);
        $axis_x_end = array($this->width - $padding, $this->origin[1]);
        $axis_y_start = array($this->origin[0], $padding);
        $axis_y_end = array($this->origin[0], $this->height - $padding);

        //axis x
        $this->drawLine($axis_x_start, $axis_x_end, 'black');
        $this->drawLine($axis_x_end, array($axis_x_end[0]-10, $axis_x_end[1]+5), 'black');
        $this->drawLine($axis_x_end, array($axis_x_end[0]-10, $axis_x_end[1]-5), 'black');
        $this->drawString($this->text_x, array($axis_x_end[0] + 10, $axis_x_end[1]+5), 'black', $this->font);
        $this->drawString(0, $this->getPoint(array(-10, -15)), 'black', $this->font);
        if($this->distance_x!=0) {
            $step = $this->distance_x * $this->scale_x;
            $step_length = $step;
            $positive = true;
            $negative    = true;
            $i = 1;
            while($positive || $negative) {
                if($positive && $step_length >= $this->width - $this->origin[0] - $padding) $positive = false;
                if($negative && $step_length >= $this->origin[0] - $padding) $negative = false;
                if($positive) {
                    $point_start = $this->getPoint(array($step_length, 0));
                    $point_end = $this->getPoint(array($step_length, 5));
                    $this->drawLine($point_start, $point_end, 'black');
                    $title = isset($this->title_x[$i-1])?$this->transString($this->title_x[$i-1]):$i*$this->distance_x+$this->start_x;
                    list($t_width, $t_height) = $this->getFontSize($title);
                    $this->drawString($title, array($point_start[0] - $t_width/2, $point_start[1] + $t_height + $this->fix_x), 'black', $this->font);
                }
                if($negative) {
                    $point_start = $this->getPoint(array(-$step_length, 0));
                    $point_end = $this->getPoint(array(-$step_length, 5));
                    $this->drawLine($point_start, $point_end, 'black');
                    $title = isset($this->title_x[$i-1])?$this->transString($this->title_x[$i-1]):-$i*$this->distance_x-$this->start_x;
                    list($t_width, $t_height) = $this->getFontSize($title);
                    $this->drawString($title, array($point_start[0] - $t_width/2, $point_start[1] + $t_height + $this->fix_x), 'black', $this->font);
                }
                $step_length += $step;
                $i++;
            }
        }

        //axis y
        $this->drawLine($axis_y_start, $axis_y_end, 'black');
        $this->drawLine($axis_y_start, array($axis_y_start[0]-5, $axis_y_start[1]+10), 'black');
        $this->drawLine($axis_y_start, array($axis_y_start[0]+5, $axis_y_start[1]+10), 'black');
        $this->drawString($this->text_y, array($axis_y_start[0] - 10, $axis_y_start[1] - 10), 'black', $this->font);
        if($this->distance_y!=0) {
            $step = $this->distance_y * $this->scale_y;
            $step_length = $step;
            $positive = true;
            $negative    = true;
            $i = 1;
            while($positive || $negative) {
                if($positive && $step_length >= $this->origin[1] - $padding) $positive = false;
                if($negative && $step_length >= $this->height - $this->origin[1] - $padding) $negative = false;
                if($positive) {
                    $point_start = $this->getPoint(array(0, $step_length));
                    $point_end = $this->getPoint(array(5, $step_length));
                    $this->drawLine($point_start, $point_end, 'black');
                    $cur_scale = (String)($i * $this->distance_y + $this->start_y);
                    $title = isset($this->title_y[$i-1])?$this->transString($this->title_y[$i-1]):$cur_scale;
                    list($t_width, $t_height) = $this->getFontSize($title);
                    $this->drawString($title, array($point_start[0] - $t_width + $this->fix_y, $point_start[1] + $t_height/2), 'black', $this->font);
                }
                if($negative) {
                    $point_start = $this->getPoint(array(0, -$step_length));
                    $point_end = $this->getPoint(array(5, -$step_length));
                    $this->drawLine($point_start, $point_end, 'black');
                    $cur_scale = (String)(-$i * $this->distance_y - $this->start_y);
                    $title = isset($this->title_y[$i-1])?$this->transString($this->title_y[$i-1]):$cur_scale;
                    list($t_width, $t_height) = $this->getFontSize($title);
                    $this->drawString($title, array($point_start[0] - $t_width + $this->fix_y, $point_start[1] + $t_height/2), 'black', $this->font);
                }
                $step_length += $step;
                $i++;
            }
        }
        return $this;
    }

    /**
     * 功能说明：画折线
     *
     * 多系列模式的data_lst按系列排列，data_str与color_lst逐系列对应。当前点生成循环依赖show_value，绘制折线时应保留true。
     *
     * @param array $data_lst 需要绘制的数据系列
     * @param array|string $color_lst 各数据系列的颜色列表，空串采用默认配色（默认：''）
     * @param array|string $data_str 数据点/系列标签，空串不提供自定义标签（默认：''）
     * @param bool $show_value 是否在图上绘制数值（默认：true）
     * @param array|null $legend_point 图例起点坐标[x,y]，null采用默认位置（默认：NULL）
     * @return $this 绘图及图例处理后返回当前实例
     */
    public function drawZigzag($data_lst, $color_lst='', $data_str='', $show_value = true, $legend_point = NULL) {
        if(is_string($color_lst)) {
            $points_new = array();
            $points = $data_lst;
            $distance = $this->start_x;
            $m=count($points);
            for($i=0; $show_value && $i<$m; $i++) {
                $distance += $this->distance_x;
                $point = array($distance, $points[$i]);
                $point = $this->getScalePoint($point, true);
                $points_new[] = $point;
                $this->drawString($points[$i], $point, 'black');
                $this->drawEllipse($point, 5, 5, $color_lst, true);
            }
            parent::drawZigzag($points_new, $color_lst);
        } else {
            $cnt = min(count($data_lst), count($data_str), count($color_lst));
            for($n=0; $n<$cnt; $n++) {
                $points_new = array();
                $points = $data_lst[$n];
                $distance = $this->start_x;
                $m=count($points);
                for($i=0; $show_value && $i<$m; $i++) {
                    $distance += $this->distance_x;
                    $point = array($distance, $points[$i]);
                    $point = $this->getScalePoint($point, true);
                    $points_new[] = $point;
                    $this->drawString($points[$i], $point, 'black');
                    $this->drawEllipse($point, 5, 5, $color_lst[$n], true);
                }
                parent::drawZigzag($points_new, $color_lst[$n]);
            }

            if(is_null($legend_point)) $legend_point = array($this->width - 150, 20);
            $line_len = 50;
            $m=count($data_str);
            for($i = 0; $i < $m; $i++) {
                $this->drawLine($legend_point, array($legend_point[0]+$line_len, $legend_point[1]), $color_lst[$i]);
                $this->drawEllipse(array($legend_point[0]+$line_len/2, $legend_point[1]), 5, 5, $color_lst[$i], true);
                $data_str[$i] = $this->transString($data_str[$i]);
                $this->drawString($data_str[$i], array($legend_point[0]+$line_len+10, $legend_point[1]+5), 'black', $this->font);
                $legend_point[1] += 30;
            }
        }
        return $this;
    }

    /**
     * 功能说明：按数据系列绘制柱状图及可选数值和图例
     *
     * @param array $data_lst 需要绘制的数据系列
     * @param array|string $color_lst 各数据系列的颜色列表，空串采用默认配色（默认：''）
     * @param array|string $data_str 数据点/系列标签，空串不提供自定义标签（默认：''）
     * @param bool $show_value 是否在图上绘制数值（默认：false）
     * @param array|null $legend_point 图例起点坐标[x,y]，null采用默认位置（默认：NULL）
     * @return $this 返回当前实例，支持链式调用。
     */
    public function drawBar($data_lst, $color_lst='', $data_str='', $show_value = false, $legend_point = NULL) {
        if(!$data_lst) return $this;
        $distance = $this->start_x;
        list($font_width, $font_height) = $this->getFontSize(4, 0);
        if(is_string($color_lst)) {
            $cnt = count($data_lst);
            $bar_width = 30;
            for($i=0; $i<$cnt; $i++) {
                $distance += $this->distance_x;
                $point = $this->getScalePoint(array($distance, $data_lst[$i]), false);
                $point[0] -= $bar_width/2;
                $this->drawRectangle($this->getPoint($point), $bar_width, $point[1], 'black', $color_lst);
                if($show_value) {
                    $point = $this->getPoint($point);
                    $point[0] -= ($font_width * strlen((STRING)$data_lst[$i])-$bar_width)/2;
                    $point[1] -= $font_height;
                    $this->drawString($data_lst[$i], $point, 'black');
                }
            }
        } else {
            $cnt = min(count((array)$data_lst[0]), count((array)$data_str), count($color_lst));
            if($cnt === 0) return $this;
            $bar_width = ceil(($this->distance_x*$this->scale_x - 100)/$cnt);
            if($bar_width > 20) $bar_width = 20;
            $m=count($data_lst);
            for($n=0; $n<$m; $n++) {
                $distance += $this->distance_x;
                for($i=0; $i<$cnt; $i++) {
                    $point = $this->getScalePoint(array($distance, $data_lst[$n][$i]), false);
                    $point[0] = $point[0] - ($bar_width*$cnt)/2 + $bar_width*$i;
                    $this->drawRectangle($this->getPoint($point), $bar_width, $point[1], 'black', $color_lst[$i]);
                    if($show_value) {
                        $point = $this->getPoint($point);
                        $point[0] -= ($font_width * strlen((STRING)$data_lst[$n][$i])-$bar_width)/2;
                        $point[1] -= $font_height;
                        $this->drawString($data_lst[$n][$i], $point, 'black');
                    }
                }
            }

            if(is_null($legend_point)) $legend_point = array($this->width - 150, 20);
            $m=count($data_str);
            for($i = 0; $i < $m; $i++) {
                $this->drawRectangle($legend_point, 15, 15, 'black', $color_lst[$i]);
                $data_str[$i] = $this->transString($data_str[$i]);
                $this->drawString($data_str[$i], array($legend_point[0]+30, $legend_point[1]+14), 'black', $this->font);
                $legend_point[1] += 30;
            }
        }
        return $this;
    }

    /**
     * 功能说明：绘制饼图，并标注数值/百分比及可选图例
     *
     * @param array $data 按扇区顺序排列的数值列表
     * @param array $point 饼图中心在画布上的坐标[x,y]
     * @param int|float $width 宽度，图像/画布场景以像素为单位
     * @param int|float $height 高度，图像/画布场景以像素为单位
     * @param array|string $data_str 数据点/系列标签，空串不提供自定义标签（默认：''）
     * @param int $mode 0显示原始数值，其他值显示向上取整的百分比（默认：1）
     * @param int $legend 0不绘制图例，1在右侧绘制，其他非零值在下方绘制（默认：0）
     * @param int $mask 饼图立体厚度，单位像素（默认：20）
     * @param int|float $ext_value 额外计入总量但不绘制的数值（默认：0）
     * @param int|float $distance 扇区向外分离的距离，单位像素（默认：0）
     * @param int|float $start_angle 首个扇区起始角度（默认：0）
     * @return $this 饼图、数值及图例处理后返回当前实例；空总量不绘制标签
     */
    public function drawPie($data, $point, $width, $height, $data_str = '', $mode = 1, $legend = 0, $mask = 20, $ext_value = 0, $distance = 0, $start_angle = 0) {
        if($width <= 0 || $height <= 0) return $this;
        if(!is_array($data_str)) $data_str = array_fill(0, count($data), '');
        parent::drawPie($data, $point, $width, $height, $mask, $ext_value, $distance, $start_angle);
        $data_sum = array_sum($data) + $ext_value;
        if($data_sum <= 0) return $this;
        $angle = array();
        $angle_sum = array($start_angle);
        $m=count($data);
        for($i=0; $i<$m; $i++) {
            $angle[] = (($data[$i] / $data_sum) * 360);
             $angle_sum[] = array_sum($angle);
        }
        $radius = ($width + $height) / 4 + $distance + 20;
        if($legend==1) {
            $legend_left = $point[0]+$width/2 + 80 + $distance;
            $legend_top = $point[1]-$height/2;
        } else {
            $legend_left = $distance;
            $legend_top = $point[1] + $height - 60;
        }

        $m=count($data);
        for($i=0; $i<$m; $i++) {
            if($mode==0) {
                $value = $data[$i];
            } else {
                $value = ceil($data[$i]*100/$data_sum).'%';
            }
            $value_show = ($data_str[$i] ?? '').chr(10).$value;
            $radian = deg2rad($angle_sum[$i]+$angle[$i]/2);
            $the_point = array(ceil($point[0]+$radius*cos($radian)*($width/$height)), ceil($point[1]+$radius*sin($radian)));
            $this->drawString($value_show, array($the_point[0]+1, $the_point[1]+1), 'white');
            $this->drawString($value_show, $the_point, 'black');

            if($legend!=0) {
                $the_point = array($legend_left, $legend_top);
                $this->drawRectangle($the_point, 15, 15, 'black', 'pie_'.($i+1));
                $the_point[0] += 20;
                $the_point[1] += 14;
                $data_str[$i] .= ' - '.$value;
                $data_str[$i] = $this->transString($data_str[$i]);
                $this->drawString($data_str[$i], array($the_point[0]+1, $the_point[1]+1), 'white', $this->font);
                $this->drawString($data_str[$i], $the_point, 'black', $this->font);
                $legend_top += 30;
            }
        }
        return $this;
    }
}