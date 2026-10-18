<?PHP
//自定义路由格式
$format = array(
    'Camel' => '([A-Z][a-z]+)+',
);
//自定义路由规则
$rule = array(
    array('/t1/[any]', 'app\test\route'),
    array('/t2/[any]', 'mystep::getModule'),
    array('/t3/[Camel]/[any]', array('app\test\f1,$1', 'app\test\f2,$2', 'app\test\f3')),
);
//当前应用的数据接口
$api = array(
    'api' => 'app\test\api',
);