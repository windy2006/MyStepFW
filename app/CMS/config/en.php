<?PHP
$setting_detail = array();

$setting_detail['gen'] = array(
    'name' => 'General Setting',
    'list' => [
        'force_domain' => array(
            'name' => 'Force Domain',
            'describe' => 'Force jump to the binded domain.',
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
        'path_admin' => array(
            'name' => 'Manager Path',
            'describe' => 'Route to admin panel',
            'type' => array('text', '', '20')
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
        'type' => array(
            'name' => 'Database',
            'describe' => 'Which database will be used',
            'type' => array('text', false, '40')
        ),
        'host' => array(
            'name' => 'Server',
            'describe' => 'Address of DB server',
            'type' => array('text', '', '30')
        ),
        'user' => array(
            'name' => 'User',
            'describe' => 'Username of DB',
            'type' => array('text', 'alpha', '20')
        ),
        'password' => array(
            'name' => 'Password',
            'name2' => 'Repeat',
            'describe' => 'Password of DB',
            'type' => array('password', '_', '40')
        ),
        'pconnect' => array(
            'name' => 'Persistent Connections',
            'describe' => 'Use persistent connections',
            'type' => array('radio', array('Open'=>'true', 'Close'=>'false'))
        ),
        'charset' => array(
            'name' => 'Charset',
            'describe' => 'Default Charset of DB',
            'type' => array('select', array('UTF-8'=>'utf-8', 'GBK'=>'gbk', 'Latin1'=>'latin1'))
        ),
        'name' => array(
            'name' => 'DB name',
            'describe' => 'The DB name which the website store in',
            'type' => array('text', 'alpha', '20')
        ),
        'pre' => array(
            'name' => 'Table Prefix',
            'describe' => 'The prefix of all offical website tables',
            'type' => array('text', 'alpha', '10')
        ),
    ]
);

$setting_detail['content'] = array(
    'name' => 'Content Setting',
    'list' => [
        'cat_pos' => array(
            'name' => 'Catalog',
            'describe' => 'Which position will show the specified catalog, split with common',
            'type' => array('text', '', '120')
        ),
        'push_pos' => array(
            'name' => 'Push Position',
            'describe' => 'Which position will show the pushed news, split with common',
            'type' => array('text', '', '120')
        ),
        'push_mode' => array(
            'name' => 'Push Mode',
            'describe' => 'How to show the pushed news, split with common',
            'type' => array('text', '', '120')
        ),
        'upload' => array(
            'name' => 'Upload Path',
            'describe' => 'The directory save teh upload files',
            'type' => array('text', '', '40')
        ),
        'get_remote_img' => array(
            'name' => 'Get Image',
            'describe' => 'Download content images from other website',
            'type' => array('radio', array('Enable'=>'true', 'Disable'=>'false'))
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

$setting_detail['session'] = array(
    'name' => 'Session Setting',
    'list' => [
        'mode' => array(
            'name' => 'Mode',
            'describe' => 'Which mode will be used to save the Session data',
            'type' => array('select', array('MyStep mode'=>'sess_mystep', 'DB mode'=>'sess_mysql', 'File Mode'=>'sess_file'))
        ),
    ]
);

$setting_detail['list'] = array(
    'name' => 'Parameter for Artile List',
    'list' => [
        'txt' => array(
            'name' => 'Text List',
            'describe' => 'How many artile will be show in subject only list.',
            'type' => array('text', 'digital', '3')
        ),
        'img' => array(
            'name' => 'Image List',
            'describe' => 'How many artile will be show in image with subject list.',
            'type' => array('text', 'digital', '3')
        ),
        'mix' => array(
            'name' => 'Mix List',
            'describe' => 'How many artile will be show in image with description list.',
            'type' => array('text', 'digital', '3')
        ),
    ]
);

$setting_detail['expire'] = array(
    'name' => 'Expiration setting for html cache',
    'list' => [
        'default' => array(
            'name' => 'Default',
            'describe' => 'The expire time for non-specified pages',
            'type' => array('text', 'digital', '6')
        ),
        'index' => array(
            'name' => 'Index',
            'describe' => 'The expire time for index page',
            'type' => array('text', 'digital', '6')
        ),
        'catalog' => array(
            'name' => 'List',
            'describe' => 'The expire time for list pages',
            'type' => array('text', 'digital', '6')
        ),
        'tag' => array(
            'name' => 'Tag',
            'describe' => 'The expire time for tag page',
            'type' => array('text', 'digital', '6')
        ),
        'article' => array(
            'name' => 'Content',
            'describe' => 'The expire time for content page',
            'type' => array('text', 'digital', '6')
        ),
    ]
);

$setting_detail['watermark'] = array(
    'name' => 'Watermark Setting',
    'list' => [
        'thumb' => array(
            'name' => 'Thumb Width',
            'describe' => 'System will reduce the width of picture to the specified size',
            'type' => array('text', 'digital', '5')
        ),
        'mode' => array(
            'name' => 'Mode',
            'describe' => 'Add Watermark to artile content or images',
            'type' => array('checkbox', array('checkbox', array('Text'=>1, 'Image'=>2)))
        ),
        'txt' => array(
            'name' => 'Jam String',
            'describe' => 'Jam String will be added to article content',
            'type' => array('text', '', '30')
        ),
        'img' => array(
            'name' => 'Watermark',
            'describe' => 'Image or any text that will be added to images',
            'type' => array('text', '', '30')
        ),
        'position' => array(
            'name' => 'Position',
            'describe' => 'Where to put the watermark',
            'type' => array('select', array('select', array('Right-Bottom'=>1, 'Right-Top'=>2, 'Left-Bottom'=>3, 'Left-Top'=>4, 'Left-Middle'=>5, 'Right-Middle'=>6, 'Middle-Top'=>7, 'Middle-Bottom'=>8, 'Center'=>9)))
        ),
        'img_rate' => array(
            'name' => 'Rate',
            'describe' => 'Control the size of Watermark image',
            'type' => array('text', 'digital', '2')
        ),
        'txt_font' => array(
            'name' => 'Font File',
            'describe' => 'The font file for the text watermark',
            'type' => array('text', '', '40')
        ),
        'txt_fontsize' => array(
            'name' => 'Font Size',
            'describe' => 'The size of text watermark, in pixal',
            'type' => array('text', 'digital', '2')
        ),
        'txt_fontcolor' => array(
            'name' => 'Font Color',
            'describe' => 'The color of text watermark (HTML color code like #000000)',
            'type' => array('text', '', '7')
        ),
        'txt_bgcolor' => array(
            'name' => 'Background Color',
            'describe' => 'The background color of text watermark (HTML color code like #000000)',
            'type' => array('text', '', '7')
        ),
        'alpha' => array(
            'name' => 'Transparent Level',
            'describe' => 'The transparent level of watermark (0-100)',
            'type' => array('text', 'digital', '3')
        ),
        'credit' => array(
            'name' => 'Credit',
            'describe' => 'Credit information that will be added to the end of every line',
            'type' => array('text', 'name', '30')
        ),
    ]
);

return $setting_detail;