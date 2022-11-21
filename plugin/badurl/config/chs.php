<?PHP
return array(
    'name' => '响应处理配置',
    'list' => [
        'forward' => array(
            'name' => '跳转网址',
            'describe' => '如发现试探性访问，将跳转至此网址',
            'type' => array('text', '_', '100')
        ),
        'bad_chars' => array(
            'name' => '可疑字符',
            'describe' => '查询字串中存在的可疑字符，以半角逗号间隔',
            'type' => array('text', '', '100')
        ),
    ]
);