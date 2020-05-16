<?PHP
$setting_detail = array();

$setting_detail['gen'] = array(
    'name' => 'General Setting',
    'list' => [
        'language' => array(
            'name' => 'Language',
            'describe' => 'Change Framework language',
            'type' => array('text', 'alpha', '10')
        ),
        'close' => array(
            'name' => 'Close Page',
            'describe' => 'If set, all of the request will be redirect to this page',
            'type' => array('text', '_', '80')
        ),
    ]
);

$setting_detail['web'] = array(
    'name' => 'Website setting',
    'list' => [
        'title' => array(
            'name' => 'Website Name',
            'describe' => 'Name of the website',
            'type' => array('text', 'name', '40')
        ),
        'keyword' => array(
            'name' => 'keyword',
            'describe' => 'Keywords for the searching engine',
            'type' => array('text', '', '60')
        ),
        'description' => array(
            'name' => 'Description',
            'describe' => 'Description for the searching engine',
            'type' => array('text', '', '100')
        ),
    ]
);

$setting_detail['template'] = array(
    'name' => 'Template Setting',
    'list' => [
        'style' => array(
            'name' => 'Style',
            'describe' => 'Style for template',
            'type' => array('text', '_', '20')
        ),
    ]
);

$setting_detail['db'] = array(
    'name' => 'Database Setting',
    'list' => [
        'pre' => array(
            'name' => 'Table Prefix',
            'describe' => 'The prefix of all offical website tables',
            'type' => array('text', 'alpha', '10')
        ),
    ]
);

return $setting_detail;