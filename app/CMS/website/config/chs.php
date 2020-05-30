<?PHP
$setting_detail = array();

$setting_detail['gen'] = array(
    'name' => '基本设置',
    'list' => [
        'language' => array(
            'name' => '网站语言',
            'describe' => '网站显示语种',
            'type' => array('text', 'alpha', '10')
        ),
        'close' => array(
            'name' => '关闭页面',
            'describe' => '网站关闭并显示的设置页面',
            'type' => array('text', '_', '80')
        ),
    ]
);

$setting_detail['web'] = array(
    'name' => '站点设置',
    'list' => [
        'title' => array(
            'name' => '网站名称',
            'describe' => '用于在浏览器上显示网站名称',
            'type' => array('text', 'name', '40')
        ),
        'keyword' => array(
            'name' => '网站关键字',
            'describe' => '用于搜索引擎检索网站，多个关键字请用“, ”间隔',
            'type' => array('text', '', '60')
        ),
        'description' => array(
            'name' => '网站描述',
            'describe' => '用于搜索引擎的网站简介',
            'type' => array('text', '', '100')
        ),
    ]
);

$setting_detail['template'] = array(
    'name' => '模版设置',
    'list' => [
        'style' => array(
            'name' => '模版样式',
            'describe' => '如果程序有多个模版，可通过本选项设置（即为模板路径下的子目录名）',
            'type' => array('text', '_', '20')
        ),
    ]
);

$setting_detail['db'] = array(
    'name' => '数据库设置',
    'list' => [
        'pre' => array(
            'name' => '数据表前缀',
            'describe' => '用于区分本系统数据表与其他数据表数据表前缀',
            'type' => array('text', 'alpha', '10')
        ),
    ]
);

return $setting_detail;