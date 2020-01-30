<?php
$setting_detail = array();

$setting_detail['text'] = array(
    'name' => '文本项目', 
    'list' => [
        'para_1' => array(
            'name' => '文本测试', 
            'describe' => '不可为特殊符号，限长50字', 
            'type' => array('text', 'name', '50'),
        ), 
        'para_2' => array(
            'name' => '数字测试', 
            'describe' => '只可为合法实数，限长10位', 
            'type' => array('text', 'number', '10'),
        ), 
        'para_7' => array(
            'name' => '多行文本', 
            'describe' => '请输入多行文本', 
            'type' => array('textarea', false, '4'),
        )
    ]
);
$setting_detail['choice'] = array(
    'name' => '选择项目', 
    'list' => [
        'para_3' => array(
            'name' => '复选测试', 
            'describe' => '可多选', 
            'type' => array('checkbox', array('选项 1'=>1, '选项 2'=>2, '选项 3'=>3, '选项 4'=>4)),
        ), 
        'para_4' => array(
            'name' => '单选测试', 
            'describe' => '单选', 
            'type' => array('radio', array('开启'=>'true', '关闭'=>'false')),
        ), 
        'para_5' => array(
            'name' => '选单测试', 
            'describe' => '下拉列表', 
            'type' => array('select', array('选项 1'=>'select_1', '选项 2'=>'select_2', '选项 3'=>'select_3', '选项 4'=>'select_4')),
        )
    ]
);
$setting_detail['etc'] = array(
    'name' => '其他项目', 
    'list' => [
        'para_6' => array(
            'name' => '密码测试', 
            'name2' => '重复密码', 
            'describe' => '请输入两次密码，限长15位', 
            'type' => array('password', '', '15'),
        )
    ]
);

return $setting_detail;