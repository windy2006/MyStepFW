<?PHP
$setting_detail = array();

$setting_detail['gen'] = array(
    'name' => '基本设置',
    'list' => [
        'cache_page' => array(
            'name' => 'HTML Cache',
            'describe' => 'Save page content into cache file',
            'type' => array('radio', array('Open'=>'true', 'Close'=>'false'))
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
    ]
);

$setting_detail['template'] = array(
    'name' => 'Template Setting',
    'list' => [
        'name' => array(
            'name' => 'Name',
            'describe' => 'Main template of a set',
            'type' => array('text', '', '10')
        ),
        'path' => array(
            'name' => 'Path',
            'describe' => 'The path which save the template files',
            'type' => array('text', '', '30')
        ),
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
        'auto' => array(
            'name' => 'Auto Connect',
            'describe' => 'Build DB Connection automatically',
            'type' => array('radio', array('Open'=>'true', 'Close'=>'false'))
        ),
    ]
);

return $setting_detail;