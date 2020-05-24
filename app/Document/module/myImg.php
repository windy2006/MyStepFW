<?PHP
/*
//picture functions test
$myImg = new myImg(PATH.'data/image/image.jpg');
$myImg->watermark(PATH.'data/image/logo.png', 3, PATH.'data/watermark.png')
            ->thumb(400, 300, PATH.'data/thumb.png')
            ->init()
            ->captcha('ab哈cd', PATH.'data/image/font.ttc', 16)
            ->destory();
*/

/*
//graphic functions test
$myImg = new myImg(1600, 1200, array(true, PATH.'data/image/tile.gif'));
$myImg->setLine(20, array('white', 'white', 'white', 'red', 'red', 'red', 'green', 'green', 'green', 'blue', 'blue', 'blue'), PATH.'data/image/smile.png')
            ->drawLine(array(0, 0), array(500, 500), 'style')
            ->drawLine(array(500, 500), array(500, 0), 'brush')
            ->drawLine(array(500, 0), array(1000, 500), 'style_brush')
            ->setLine(5)
            ->drawRectangle(array(500, 500), 500, 500, 'red', 'green')
            ->drawPolygon(array(array(20, 20), array(100, 40), array(150, 300), array(80, 300), array(20, 120)), 'blue', 'white')
            ->drawFiveSidedStar(array(100, 1000), 80, 'black', 'red', true, 1)
            ->drawFiveSidedStar(array(300, 1000), 80, 'black', 'red', false, 1)
            ->drawFiveSidedStar(array(500, 1000), 80, 'black', 'red', true, 0.5)
            ->drawFiveSidedStar(array(700, 1000), 80, 'black', 'red', true, 0)
            ->setFilter(IMG_FILTER_GRAYSCALE)
            ->drawEllipse(array(700, 500), 100, 100,  'white', 'blue')
            ->drawEllipse(array(500, 500), 100, 200,  'white', 'blue')
            ->drawEllipse(array(900, 500), 200, 100,  'white', 'blue')
            ->drawArc(array(1200, 300), 300, 300, array(45, 315), 'black', 'yellow')
            ->drawPie(array(10, 16, 5, 30, 28), array(1200, 800), 400, 400, 20, 0, 10, 150)
            ->drawZigzag(array(array(0, 1200), array(200, 1000), array(500, 500), array(600, 800), array(1000, 300), array(1400, 400), array(1600, 0)), 'red')
            ->addNoise(1000)
            ->setFilter(IMG_FILTER_GAUSSIAN_BLUR)
            ->make()
            ->destory();
*/

//Chart test
$width = 1350;
$height = 850;
$painter = new coordinateMaker($width, $height, array(50, 800), array(true, array(0xee, 0xee, 0xee)));
$painter->setPara(array(
                                        'scale_x' => 180,
                                        'scale_y' => 1,
                                        'distance_x' => 1,
                                        'distance_y' => 100,
                                        'start_x' => 2000,
                                        'start_y' => 1000,
                                        'text_x' => '年度',
                                        'text_y' => '数值',
                                        'font' => PATH.'data/image/font.ttc'))
                ->setLine(1);

$painter->title_x = array('2007年1月', '2007年2月', '2007年3月', '2007年4月', '2007年5月', '2007年6月前半月');
$painter->setColor('gray', 0xee, 0xee, 0xee)
                ->setColor('darkgray', 0x90, 0x90, 0x90)
                ->setColor('navy', 0x00, 0x00, 0x80)
                ->setColor('darknavy', 0x00, 0x00, 0x50)
                ->setColor('darkred', 0x90, 0x00, 0x00)
                ->buileCoordinate(50);

$data = array(200, 400, 600, 800, 100, 300, 500, 700);
$data_str = array('中国', '日本', '韩国', '缅甸', '泰国', '新加坡', '马来西亚', '越南');
$point = array(600, 250);
$painter->drawPie($data, $point, 400, 300, $data_str, 1, 0, 20, 0, 20, 0);

$data = array();
$data[] = array(1111, 1333, 1222);
$data[] = array(1211, 1433, 1322);
$data[] = array(1111, 1333, 1222);
$data[] = array(1211, 1433, 1322);
$data[] = array(1111, 1333, 1222);
$data[] = array(1211, 1433, 1322);
$data_str = array('中国', '日本', '韩国');
$painter->drawBar($data, array('red', 'green', 'blue'), $data_str, true, array(1250, 700));

$data = array(1182, 1702, 1536, 1498, 1266, 1629);
$painter->setLine(5)
                ->drawZigzag(array($data, array_reverse($data)), array('red', 'green'), array('中国', '美国'));

$painter->make()->destory();