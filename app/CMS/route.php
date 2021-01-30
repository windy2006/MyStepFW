<?PHP
$rule = array(
    array(
        '/'.$GLOBALS['s']->web->path_admin.'/[any]',
        array(
            'app\CMS\installCheck,index',
            'app\CMS\logCheck',
            'myStep::getModule'
        )
    ),
);
$api = array(
    'user' => 'app\CMS\getUserInfo',
    'get' => 'app\CMS\getData',
    'attachment' => 'app\CMS\getAttachment',
);