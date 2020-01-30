<?php
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
    图形处理
        $myImg = new myImg($width, $height, $cr)                          // 构造函数
        $myImg->create($trueImage, $background)                           // 生成画板
        $myImg->setTile($image, $point, $width, $height)                  // 设置区域贴图
        $myImg->setTransparent($color)                                    // 设置透明色（可为点、索引色和数组色）
        $myImg->randomColor($rand)                                        // 随机颜色
        $myImg->load($image, &$data)                                      // 读取图片文件，并返回图片信息
        $myImg->rotate($angle, $img)                                      // 旋转画布
        $myImg->resize($rate, $return, $img)                              // 按比例缩放画布（可选择返回图像源）
        $myImg->crop($point, $width, $height, $img, $return)              // 截取画布的一部分（可选择返回图像源）
        $myImg->paste($img, $point, $alpha, $gray)                        // 将一图像源粘贴到画布的某一位置
        $myImg->setLine($thickness, $style, $brush)                       // 设定画线的粗细、风格、笔刷
        $myImg->setFilter($filtertype, $img, $arg)                        // 为图像添加滤镜效果
        $myImg->getSize($img)                                             // 返回图像源的宽、高
        $myImg->getColor($point, $mode)                                   // 取得画布某点的颜色
        $myImg->setColor($idx)                                            // 设置颜色
        $myImg->checkPoint()                                              // 检测点的有效性
        $myImg->check()                                                   // 检测图像源的有效性
        $myImg->fill($point, $color, $color_border)                       // 从一点向画布右下填充
        $myImg->drawLine($p_start, $p_end, $color)                        // 画线
        $myImg->drawRectangle($point, $width, $height, $color_line, $color_fill)                    // 画矩形
        $myImg->drawPolygon($points, $color_line, $color_fill)                                      // 画多边形
        $myImg->drawFiveSidedStar($point, $radius, $color_line, $color_fill, $star, $spiky)         // 画五角星
        $myImg->drawEllipse($point, $width, $height, $color, $fill)                                 // 画椭圆
        $myImg->drawArc($point, $width, $height, $arc, $color, $fill)                               // 画椭圆弧
        $myImg->drawPie($data, $point, $width, $height, $mask, $ext_value, $distance, $start_angle) // 画饼图
        $myImg->drawZigzag($points, $color_line)                                                    // 画折线
        $myImg->drawString($text, $point, $color, $font, $font_size, $angle)                        // 添加文字
        $myImg->setFont($font)                                                                      // 设置字体
        $myImg->getFontSize($text, $size, $angle)                                                   // 取得字符串的占位大小
        $myImg->addNoise($number)                                                                   // 添加干扰点
        $myImg->make($type, $file, $img)                                                            // 输出图像源到浏览器或文件
        $myImg->destroy($img)                                                                       // 释放图像源
        $myImg->transString($str)                                                                   // 字符集转换
        $myImg->gif_info($filename)                                                                 // 读取GIF信息
        $myImg->captcha($str, $font, $fontsize)                                                     // 生成验证码
        $myImg->watermark($watermark, $position, $img_dst, $para)                                   // 添加图片水印
        $myImg->thumb($dstW, $dstH, $img_dst)                                                       // 制作缩略图
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

    /**
     * 初始化实例
     * @return $this|bool|void
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
        } elseif(is_numeric($args[0]) && is_numeric($args[1])) {
            $this->width = $args[0];
            $this->height = $args[1];
            if(!isset($args[2])) $args[2] = array();
            call_user_func_array(array($this, 'create'), $args[2]);
        }
        return $this;
    }

    /**
     * 生成画板
     * @param bool $trueImage
     * @param null $background
     * @return $this
     */
    public function create($trueImage = true, $background = NULL) {
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
        if(!function_exists('color_cmp')) {
            function color_cmp($a, $b) {return (rand(0, 10) > 7) ? -1 : 1;};
        }
        uksort($color_pie, 'color_cmp');
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
     * 设置区域贴图
     * @param $tile
     * @param null $point
     * @param null $width
     * @param null $height
     * @return $this
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
     * 设置透明色（可为点、索引色和数组色）
     * @param $para
     * @return $this
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
    public function randomColor($rand = false, $alpha = true) {
        if(count($this->color_lst)>0 && $rand) {
            return $this->color_lst[array_rand($this->color_lst)];
        } else {
            return $alpha ? imagecolorallocatealpha($this->img, rand(0, 255), rand(0, 255), rand(0, 255), rand(0, 127)) : imagecolorallocate($this->img, rand(0, 255), rand(0, 255), rand(0, 255));
        }
    }

    /**
     * 读取图片文件，并返回图片信息
     * @param $image
     * @param array $data
     * @return bool|resource
     */
    public function load($image, &$data = array()) {
        if(!is_file($image)) return false;
        $info = array();
        $data = getimagesize($image, $info);
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
        $type_list = array('unknow', 'GIF', 'JPG', 'PNG', 'SWF', 'PSD', 'BMP', 'TIFF(intel byte order)', 'TIFF(motorola byte order)', 'JPC', 'JP2', 'JPX', 'JB2', 'SWC', 'IFF', 'WBMP', 'XBM');
        $data[2] = $type_list[$data[2]];
        $data[3] = $info;
        return $img;
    }

    /**
     * 旋转画布
     * @param $angle
     * @param null $img
     * @return $this|bool|resource
     */
    public function rotate($angle, $img = NULL) {
        if(is_null($img)) {
            $new_img = imagerotate($this->img, $angle, 0);
            $this->destroy();
            $this->img = $new_img;
            if(fmod($angle, 90)!==0) $this->setTransparent(array(0, 0));
            list($this->width, $this->height) = $this->getSize();
            return $this;
        } else {
            if(!$this->check($img) && (($img=$this->load($img))===false)) return false;
            return imagerotate($img, $angle, IMG_COLOR_TRANSPARENT);
        }
    }

    /**
     * 按比例缩放画布（可选择返回图像源）
     * @param $rate
     * @param null $img
     * @return $this|bool|resource
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
        $new_width = $width * $rate;
        $new_height = $height * $rate;
        if(function_exists('imagecopyresampled')) {
            $new_img = imagecreatetruecolor($new_width, $new_height);
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
     * 截取画布的一部分（可选择返回图像源）
     * @param $point
     * @param $width
     * @param $height
     * @param null $img
     * @return $this|bool|resource
     */
    public function crop($point, $width, $height, $img = NULL) {
        if($flag = is_null($img)) $img = $this->img;
        if(!$this->check($img) && (($img=$this->load($img))===false)) return false;
        $new_img = imagecreatetruecolor($width, $height);
        imagecopyresampled($new_img, $img, 0, 0, $point[0], $point[1], $width, $height, $width, $height);
        if($flag) {
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
     * 将一图像源粘贴到画布的某一位置
     * @param $img
     * @param array $point
     * @param int $alpha
     * @return $this|bool
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
     * 设定画线的粗细、风格、笔刷
     * @param int $thickness
     * @param null $style
     * @param null $brush
     * @return $this
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
     * 为图像添加滤镜效果
     * @param int $filtertype
     * @param array $args
     * @return $this
     */
    public function setFilter($filtertype = IMG_FILTER_COLORIZE, $args = array()) {
        if(!is_array($args)) $args = array($args);
        $args = array_merge(array($this->img, $filtertype), $args);
        call_user_func_array('imagefilter', $args);
        return $this;
    }

    /**
     * 返回图像源的宽、高
     * @param null $img
     * @return array
     */
    public function getSize($img = NULL) {
        if(is_null($img)) $img = $this->img;
        if(!$this->check($img)) return array(0, 0);
        return array(imagesx($img), imagesy($img));
    }

    /**
     * 取得画布某点的颜色
     * @param $point
     * @param bool $mode
     * @return $this|array|bool|int
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
     * 设置颜色
     * @param string $idx
     * @return $this
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
     * 从一点向画布右下填充
     * @param array $point
     * @param string $color
     * @param null $color_border
     * @return $this
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
     * 画线
     * @param $p_start
     * @param $p_end
     * @param string $color
     * @return $this
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
     * 画矩形
     * @param $point
     * @param $width
     * @param $height
     * @param string $color_border
     * @param string $color_fill
     * @return $this
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
     * 画多边形
     * @param $points
     * @param string $color_border
     * @param string $color_fill
     * @return $this
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
     * 画五角星
     * @param $point
     * @param $radius
     * @param string $color_border
     * @param string $color_fill
     * @param bool $star
     * @param float $spiky
     * @return myImg
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
     * 画椭圆
     * @param $point
     * @param $width
     * @param $height
     * @param string $color_border
     * @param string $color_fill
     * @return $this
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
     * 画圆弧
     * @param $point
     * @param $width
     * @param $height
     * @param array $arc
     * @param string $color_border
     * @param string $color_fill
     * @return $this
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
     * 画饼图
     * @param $data
     * @param $point
     * @param $width
     * @param $height
     * @param int $mask
     * @param int $ext_value
     * @param int $distance
     * @param int $start_angle
     * @return $this
     */
    public function drawPie($data, $point, $width, $height, $mask = 20, $ext_value = 0, $distance = 0, $start_angle = 0) {
        $data_sum = array_sum($data) + $ext_value;
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
     * 画折线
     * @param $points
     * @param string $color_line
     * @return $this
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
     * 添加文字
     * @param $text
     * @param $point
     * @param string $color
     * @param string $font
     * @param int $font_size
     * @param int $angle
     * @return $this
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
     * 设置字体
     * @param $font
     * @return $this
     */
    public function setFont($font) {
        if(is_file($font)) {
            $this->font = realpath($font);
        }
        return $this;
    }

    /**
     * 取得字符串的占位大小
     * @param $text
     * @param int $size
     * @param int $angle
     * @return array
     */
    public function getFontSize($text, $size = 12, $angle = 0) {
        if($size == 0 || empty($this->font)) {
            if(!is_numeric($text)) $text = 4;
            $result = array(imagefontwidth($text), imagefontheight($text));
        } else {
            $points = imagettfbbox($size, $angle, $this->font, $text);
            $result = array(($points[2]- $points[0]), ($points[3]- $points[5]));
        }
        return $result;
    }

    /**
     * 添加干扰点
     * @param $number
     * @return $this
     */
    public function addNoise($number) {
        for($i=0; $i<$number; $i++) {
            imageSetPixel($this->img, rand(0, $this->width), rand(0, $this->height), $this->randomColor(true));
        }
        return $this;
    }

    /**
     * 输出图像源到浏览器或文件
     * @param string $type
     * @param string $file
     * @return bool
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
                $contentType = 'imagejpeg';
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
     * 释放图像源
     * @param null $img
     * @return bool
     */
    public function destroy($img = NULL) {
        if(is_null($img)) $img = $this->img;
        if(!$this->check($img)) return false;
        return imagedestroy($img);
    }

    /**
     * 字符集转换
     * @param $str
     * @param string $charset
     * @return array|string
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
     * 检测点的有效性
     * @return bool
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
     * 检测图像源的有效性
     * @param null $img
     * @return bool
     */
    public function check($img = NULL) {
        if(is_null($img)) $img = $this->img;
        return (is_resource($img) && get_resource_type($img)=='gd');
    }

    /**
     * 读取GIF信息
     * @param $filename
     * @return mixed
     */
    public function gif_info($filename) {
        $fp = fopen($filename, 'rb');
        $result = fread($fp, 13);
        $file['signatur'] = substr($result, 0, 3);
        $file['version'] = substr($result, 3, 3);
        $file['width'] = ord(substr($result, 6, 1))+ord(substr($result, 7, 1))*256;
        $file['height'] = ord(substr($result, 8, 1))+ord(substr($result, 9, 1))*256;
        $file['flag'] = ord(substr($result, 10, 1))>>7;
        $file['trans_red'] = ord(substr($result, ord(substr($result, 11))*3, 1));
        $file['trans_green'] = ord(substr($result, ord(substr($result, 11))*3+1, 1));
        $file['trans_blue'] = ord(substr($result, ord(substr($result, 11))*3+2, 1)) ;
        fclose($fp);
        return $file;
    }

    /**
     * 生成验证码
     * @param $str
     * @param $font
     * @param int $fontsize
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
        return;
    }

    /**
     * 添加图片水印
     * @param $watermark
     * @param int $position
     * @param string $img_dst
     * @param array $para
     * @return $this|bool|myImg|void
     */
    public function watermark($watermark, $position=1, $img_dst='', $para=array()) {
        if(is_file($img_dst)) return $this->init($img_dst);
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
            if(preg_match('/(#)?[0-9a-f] {6}/i', $bgcolor)) $bgcolor = array_map('hexdec', str_split(str_replace('#', '', $bgcolor), '2'));

            if($this->setFont($font)) {
                $font_size = $this->getFontSize($watermark, $fontsize);
                $font_size[0] += 10;
                $font_size[1] += 10;
                $img_txt = new myImg($font_size[0], $font_size[1], array(true, $bgcolor));
                if(preg_match('/(#)?[0-9a-f] {6}/i', $fontcolor)) {
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
                $img_out->destroy();
            } else {
                $this->drawString($watermark, array(0, 0), $fontcolor, $font, $fontsize);
                $this->make($this->dst_type, $img_dst);
            }
        }
        return $this;
    }

    /**
     * 制作缩略图
     * @param $dstW
     * @param $dstH
     * @param string $img_dst
     * @return $this|bool|myImg|void
     */
    public function thumb($dstW, $dstH, $img_dst='') {
        if(is_file($img_dst)) return $this->init($img_dst);
        $this->dst_type = 'jpg';
        if(!empty($img_dst)) {
            if(!file_exists(dirname($img_dst))) mkdir(dirname($img_dst), 0777, true);
            $this->dst_type = pathinfo($img_dst, PATHINFO_EXTENSION);
        }
        $srcW = $this->width;
        $srcH = $this->height;
        $rate = min($dstW/$srcW, $dstH/$srcH);
        $this->resize($rate);

        $img_out = new myImg($dstW, $dstH);
        $img_out->create(true, array(0xff, 0xff, 0xff));
        $img_out->setTransparent('white');
        $img_out->paste($this->img, array(($dstW-$srcW*$rate)/2, ($dstH-$srcH*$rate)/2));

        $this->destroy();
        $this->img = $img_out->img;
        $this->make($this->dst_type, $img_dst);
        $img_out->destroy();
        return $this;
    }
}

/**
    统计图生成
        $coordinateMaker = new coordinateMaker($width, $height, $origin, $cr)        //构造函数
        $coordinateMaker->setOrigin($point)                                          //设置原点位置
        $coordinateMaker->setPara()                                                  //设置坐标参数
        $coordinateMaker->getPoint($point)                                           //取得相对原点坐标的实际位置
        $coordinateMaker->getScalePoint($point, $trans)                              //俺比例取得相对原点坐标的实际位置
        $coordinateMaker->buileCoordinate($padding)                                  //绘制坐标系
        $coordinateMaker->drawZigzag($data_lst, $color_lst, $data_str, $show_value, $legend_point)                               //画折线
        $coordinateMaker->drawBar($data_lst, $color_lst, $data_str, $show_value, $legend_point)                                  //画柱
        $coordinateMaker->drawPie($data, $data_str, $point, $width, $height, $mode, $mask, $ext_value, $distance, $start_angle)  //画饼
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
     * 初始化实例
     * @param int $width
     * @param int $height
     * @param array $origin
     * @param array $args
     * @return $this|bool|myImg|void
     */
    public function init($width = 400, $height = 400, $origin = array(0, 0), $args = array(true, array(0xee, 0xee, 0xee))) {
        $this->width = $width;
        $this->height = $height;
        $this->setOrigin($origin);
        call_user_func_array(array($this, 'create'), $args);
        return $this;
    }

    /**
     * 设置原点位置
     * @param $point
     * @return $this
     */
    public function setOrigin($point) {
        if(!$this->checkPoint($point)) return $this;
        $this->origin = $point;
        return $this;
    }

    /**
     * 设置坐标参数
     * @param array $para
     * @return $this
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
     * 取得相对原点坐标的实际位置
     * @param $point
     * @return $this|array
     */
    public function getPoint($point) {
        if(!$this->checkPoint($point)) return $this;
        return array(($this->origin[0]+$point[0]), $this->origin[1]-$point[1]);
    }

    /**
     * 俺比例取得相对原点坐标的实际位置
     * @param $point
     * @param bool $trans
     * @return $this|array|coordinateMaker
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
     * 绘制坐标系
     * @param int $padding
     * @return $this
     */
    public function buileCoordinate($padding = 20) {
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
     * 画折线
     * @param $data_lst
     * @param string $color_lst
     * @param string $data_str
     * @param bool $show_value
     * @param null $legend_point
     * @return $this|myImg
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
     * 画柱
     * @param $data_lst
     * @param string $color_lst
     * @param string $data_str
     * @param bool $show_value
     * @param null $legend_point
     * @return $this
     */
    public function drawBar($data_lst, $color_lst='', $data_str='', $show_value = false, $legend_point = NULL) {
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
            $cnt = min(count($data_lst[0]), count($data_str), count($color_lst));
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
     * 画饼
     * @param $data
     * @param $point
     * @param $width
     * @param $height
     * @param string $data_str
     * @param int $mode
     * @param int $legend
     * @param int $mask
     * @param int $ext_value
     * @param int $distance
     * @param int $start_angle
     * @return $this|myImg
     */
    public function drawPie($data, $point, $width, $height, $data_str = '', $mode = 1, $legend = 0, $mask = 20, $ext_value = 0, $distance = 0, $start_angle = 0) {
        parent::drawPie($data, $point, $width, $height, $mask, $ext_value, $distance, $start_angle);
        $data_sum = array_sum($data) + $ext_value;
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
            $value_show = $data_str[$i].chr(10).$value;
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