<?php
return array(
    'name' => '插件设置', 
    'list' => [
        'pack' => array(
            'name' => '开启打包', 
            'describe' => '是否允许任意访客打包框架文件', 
            'type' => array("radio", array("开启"=>"true", "关闭"=>"false"))
        ), 
        'update' => array(
            'name' => '开启更新', 
            'describe' => '是否用于其他框架用户通过本网站更新', 
            'type' => array("radio", array("开启"=>"true", "关闭"=>"false"))
        ), 
    ]
);