<?PHP
return array(

    $setting_detail['memcached'] = array(
        'name' => 'Memory Cache Setting',
        'list' => [
            'server' => array(
                'name' => 'Server host',
                'describe' => 'Memcache Host Address (IP:PORT)',
                'type' => array('text', false, '40')
            ),
            'expire' => array(
                'name' => 'Expire Period',
                'describe' => 'How long will Memcache expire, in second',
                'type' => array('text', 'digital', '8')
            ),
            'persistent' => array(
                'name' => 'Persistent Connect',
                'describe' => 'Use Persistant Connect or not',
                'type' => array('radio', array('Open'=>'true', 'Close'=>'false'))
            ),
            'weight' => array(
                'name' => 'Server Weight',
                'describe' => 'Memcache Host Weight',
                'type' => array('text', 'digital', '2')
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
    )
);