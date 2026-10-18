<?PHP
return array(
    'name' => 'MySQL 服务器设置',
    'list' => [
        'host' => array(
            'name' => '服务器地址',
            'describe' => '数据库主机的地址',
            'type' => array('text', '_', '30')
        ),
        'user' => array(
            'name' => '数据库用户',
            'describe' => '服务器所分配的数据库用户名',
            'type' => array('text', 'alpha_', '20')
        ),
        'password' => array(
            'name' => '数据库密码',
            'name2' => '重复密码',
            'describe' => '对应数据库用户的密码',
            'type' => array('password', '_', '40')
        ),
        'db' => array(
            'name' => '所用数据库',
            'describe' => '默认数据库名称',
            'type' => array('text', 'alpha_', '20')
        ),
        'charset' => array(
            'name' => '数据库编码',
            'describe' => '数据库存储数据的默认字符集',
            'type' => array('select', array('GBK'=>'gbk', 'UTF-8'=>'utf-8', 'Latin1'=>'latin1'))
        ),
    ]
);