<?php
$setting_detail = array();

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

return $setting_detail;