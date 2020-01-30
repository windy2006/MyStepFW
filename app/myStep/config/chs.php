<?php
$setting_detail = array();

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

return $setting_detail;