<?PHP
$setting_detail = array();

$setting_detail['gen'] = array(
    'name' => '基本设置',
    'list' => [
        'cache_page' => array(
            'name' => '页面缓存',
            'describe' => '开启页面缓存，减少固定时间内的查询频率，增强网站效率',
            'type' => array('radio', array('开启'=>'true', '关闭'=>'false'))
        )
    ]
);

$setting_detail['web'] = array(
    'name' => '站点设置',
    'list' => [
        'title' => array(
            'name' => '网站名称',
            'describe' => '用于在浏览器上显示网站名称',
            'type' => array("text", "name", "40")
        ),
    ]
);

$setting_detail['template'] = array(
    'name' => '模版设置',
    'list' => [
        'name' => array(
            'name' => '调用模版',
            'describe' => '应用调用的主模版（子模版需要在程序内设置）',
            'type' => array("text", "", "10")
        ),
        'path' => array(
            'name' => '模版路径',
            'describe' => '模版的存放位置（相对于本应用的相对路径）',
            'type' => array("text", "", "30")
        ),
        'style' => array(
            'name' => '模版样式',
            'describe' => '如果程序有多个模版，可通过本选项设置（即为模板路径下的子目录名）',
            'type' => array("text", "_", "20")
        ),
    ]
);

$setting_detail['db'] = array(
    'name' => '数据库设置',
    'list' => [
        'auto' => array(
            'name' => '自动连接',
            'describe' => '在框架执行时自动建立数据库连接',
            'type' => array('radio', array('开启'=>'true', '关闭'=>'false'))
        ),
    ]
);

return $setting_detail;