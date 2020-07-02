<?PHP
return array(
    'name' => 'MemCached缓存设置',
    'list' => [
        'server' => array(
            'name' => '服务器',
            'describe' => 'IP:PORT',
            'type' => array('text', false, '40')
        ),
        'expire' => array(
            'name' => '过期时间',
            'describe' => '秒',
            'type' => array('text', 'digital', '8')
        ),
        'persistent' => array(
            'name' => '持续连接',
            'describe' => '在使用较频繁时推荐',
            'type' => array('radio', array('开启'=>'true', '关闭'=>'false'))
        ),
        'weight' => array(
            'name' => '权重',
            'describe' => '服务器权重',
            'type' => array('text', 'digital', '2')
        ),
        'timeout' => array(
            'name' => '超时时间',
            'describe' => '秒',
            'type' => array('text', 'digital', '2')
        ),
        'retry_interval' => array(
            'name' => '重试频率',
            'describe' => '失败重试频率（秒）',
            'type' => array('text', 'digital', '2')
        ),
    ]
);