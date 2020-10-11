<?PHP
return array(
    'name' => 'MemCached缓存设置',
    'list' => [
        'server' => array(
            'name' => '服务器',
            'describe' => '请根据样例的json模式添加',
            'type' => array('textarea', false, '2')
        ),
        'expire' => array(
            'name' => '过期时间',
            'describe' => '秒',
            'type' => array('text', 'digital', '8')
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