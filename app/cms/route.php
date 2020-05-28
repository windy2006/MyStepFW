<?PHP
$rule = array(
    array('/admin_cms/[any]', array('app\cms\installCheck,index', 'app\cms\logCheck', 'myStep::getModule')),
);
$api = array(
    'user' => 'app\cms\getUserInfo',
    'get' => 'app\cms\getData',
);