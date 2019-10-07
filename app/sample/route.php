<?php
//预加载文件，在调用当前应用时首先引入并执行的脚本
$preload = 'lib.php';
//自定义路由格式
$format = array(
    'camel' => '[a-z]+([A-Z][a-z]+)+',
);
//自定义路由规则
$rule = array(
    array('/mySample/[any]', 'app\sample\route'),
    array('/mySample2/[any]', 'mystep::getModule'),
    array('/[camel]', array('app\sample\perCheck,3','app\sample\routeTest')),
);
//当前应用的数据接口
$api = array(
    'sample' => 'app\sample\api',
);