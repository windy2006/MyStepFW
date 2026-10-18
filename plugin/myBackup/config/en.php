<?PHP
return array(
    'name' => 'MySQL Setting',
    'list' => [
        'host' => array(
            'name' => 'Server',
            'describe' => 'Address of DB server',
            'type' => array('text', '_', '30')
        ),
        'user' => array(
            'name' => 'User',
            'describe' => 'Username of DB',
            'type' => array('text', 'alpha_', '20')
        ),
        'password' => array(
            'name' => 'Password',
            'name2' => 'Repeat',
            'describe' => 'Password of DB',
            'type' => array('password', '_', '40')
        ),
        'db' => array(
            'name' => 'DB name',
            'describe' => 'The DB name by default',
            'type' => array('text', 'alpha_', '20')
        ),
        'charset' => array(
            'name' => 'Charset',
            'describe' => 'Default Charset of DB',
            'type' => array('select', array('GBK'=>'gbk', 'UTF-8'=>'utf-8', 'Latin1'=>'latin1'))
        ),
    ]
);