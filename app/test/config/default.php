<?PHP
$setting_detail = array();
$setting_detail['web'] = array(
    'name' => '站点设置',
    'list' => [
        'title' => array(
            'name' => '网站名称',
            'describe' => '用于在浏览器上显示网站名称',
            'type' => array('text', 'name', '40')
        ),
    ]
);
$setting_detail['js'] = array(
    'name' => 'JS脚本参数',
    'list' => [
        'info' => array(
            'name' => '测试参数',
            'describe' => '传入前台的JS脚本信息',
            'type' => array('text', 'name', '20')
        ),
    ]
);
return $setting_detail;