<?PHP
return array(
    'name' => 'Memory Cache Setting',
    'list' => [
        'server' => array(
            'name' => 'Server host',
            'describe' => 'Memcache Host Address (in Jason)',
            'type' => array('textarea', false, '2')
        ),
        'expire' => array(
            'name' => 'Expire Period',
            'describe' => 'How long will Memcache expire, in second',
            'type' => array('text', 'digital', '8')
        ),
        'timeout' => array(
            'name' => 'Timeout time',
            'describe' => 'Memcache Server connect timeout time (in second)',
            'type' => array('text', 'digital', '2')
        ),
        'retry_interval' => array(
            'name' => 'Retry Interval',
            'describe' => 'Memcache Retry frequency (in second)',
            'type' => array('text', 'digital', '2')
        ),
    ]
);