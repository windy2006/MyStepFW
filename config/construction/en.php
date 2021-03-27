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
        'charset' => array(
            'name' => 'Charset',
            'describe' => 'Charset of Framework',
            'type' => array('text', 'word', '10')
        ),
        'timezone' => array(
            'name' => 'Timezone',
            'describe' => 'Set the timezone of the website',
            'type' => array('select', array('GMT-12'=>'Etc/GMT+12', 'GMT-11'=>'Etc/GMT+11', 'GMT-10'=>'Etc/GMT+10', 'GMT-9'=>'Etc/GMT+9', 'GMT-8'=>'Etc/GMT+8', 'GMT-7'=>'Etc/GMT+7', 'GMT-6'=>'Etc/GMT+6', 'GMT-5'=>'Etc/GMT+5', 'GMT-4'=>'Etc/GMT+4', 'GMT-3'=>'Etc/GMT+3', 'GMT-2'=>'Etc/GMT+2', 'GMT-1'=>'Etc/GMT+1', 'GMT'=>'Etc/GMT', 'GMT+1'=>'Etc/GMT-1', 'GMT+2'=>'Etc/GMT-2', 'GMT+3'=>'Etc/GMT-3', 'GMT+4'=>'Etc/GMT-4', 'GMT+5'=>'Etc/GMT-5', 'GMT+6'=>'Etc/GMT-6', 'GMT+7'=>'Etc/GMT-7', 'GMT+8'=>'Etc/GMT-8', 'GMT+9'=>'Etc/GMT-9', 'GMT+10'=>'Etc/GMT-10', 'GMT+11'=>'Etc/GMT-11', 'GMT+12'=>'Etc/GMT-12'))
        ),
        'cache_mode' => array(
            'name' => 'Cache Mode',
            'describe' => 'Open the cache mode to reduce the db query',
            'type' => array('select', array('File Mode'=>'File', 'DB Mode'=>'MySQL'))
        ),
        'cache_page' => array(
            'name' => 'HTML Cache',
            'describe' => 'Save page content into cache file',
            'type' => array('radio', array('Open'=>'true', 'Close'=>'false'))
        ),
        's_usr' => array(
            'name' => 'SA Name',
            'describe' => 'Name of the super user',
            'type' => array('text', 'alpha', '16')
        ),
        's_pwd' => array(
            'name' => 'SA Password',
            'name2' => 'Repeat',
            'describe' => 'Password of the super user',
            'type' => array('password', 'md5', '40')
        ),
        'close' => array(
            'name' => 'Close Page',
            'describe' => 'If set, all of the request will be redirect to this page',
            'type' => array('text', '_', '80')
        ),
        'static' => array(
            'name' => 'Static File',
            'describe' => 'A list of extensions which can be visited through url.',
            'type' => array('text', '', '80')
        ),
        'debug' => array(
            'name' => 'Error',
            'describe' => 'Open the option to show all of the script error message',
            'type' => array('radio', array('Show'=>'true', 'Hide'=>'false'))
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
        'gzip_level' => array(
            'name' => 'Gzip output',
            'describe' => 'GZIP page content (Level 0-9)',
            'type' => array('text', 'digital', '1')
        ),
        'minify' => array(
            'name' => 'HTML Minify',
            'describe' => 'Minify the html code of each page to reduce the internet transfer time',
            'type' => array('radio', array('Open'=>'true', 'Close'=>'false'))
        ),
        'etag' => array(
            'name' => 'Etag Fix',
            'describe' => 'Append to Etag with which to avoid non-modification HTML pages transfer',
            'type' => array('text', '', '10')
        ),
        'css' => array(
            'name' => 'CSS Files',
            'describe' => 'Split with common in order, and the files in static folder will be load to all the pages',
            'type' => array('text', '', '150')
        ),
        'js' => array(
            'name' => 'JS Files',
            'describe' => 'Split with common in order, and the files in static folder will be load to all the pages',
            'type' => array('text', '', '150')
        ),
        'update' => array(
            'name' => 'Update URL',
            'describe' => 'From the URL your can get the newest update of the Framework',
            'type' => array('text', '_', '200')
        ),
    ]
);

$setting_detail['upload'] = array(
    'name' => 'Upload Setting',
    'list' => [
        'path_mode' => array(
            'name' => 'Path Mode',
            'describe' => 'The directory construction to save the upload file, according to the date function of php',
            'type' => array('text', '', '10')
        ),
        'ban_ext' => array(
            'name' => 'Special File',
            'describe' => 'FW will change the extension for the specified files.',
            'type' => array('text', '', '50')
        ),
        'free_dl' => array(
            'name' => 'Free Download',
            'describe' => 'If the uploaded files can be download from external.',
            'type' => array('radio', array('开启'=>'true', '关闭'=>'false'))
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
        'expire' => array(
            'name' => 'Expire time',
            'describe' => 'How long to keep the guest online status',
            'type' => array('text', 'digital', '2')
        ),
        'name' => array(
            'name' => 'Name',
            'describe' => 'Name of the Framework Session',
            'type' => array('text', 'alpha', '10')
        ),
        'mode' => array(
            'name' => 'Mode',
            'describe' => 'Which mode will be used to save the Session data',
            'type' => array('select', array('MyStep mode'=>'sess_mystep', 'DB mode'=>'sess_mysql', 'File Mode'=>'sess_file'))
        ),
        'path' => array(
            'name' => 'Path',
            'describe' => 'The path to save the session files',
            'type' => array('text', '', '30')
        ),
        'gc' => array(
            'name' => 'Gabage Collection',
            'describe' => 'Delete expire Session data',
            'type' => array('radio', array('Open'=>'true', 'Close'=>'false'))
        ),
        'trans_sid' => array(
            'name' => 'Send SID',
            'describe' => 'Send SID in local URL when cookie cannot be use',
            'type' => array('radio', array('Open'=>'true', 'Close'=>'false'))
        ),
    ]
);

$setting_detail['cookie'] = array(
    'name' => 'Cookie Setting',
    'list' => [
        'prefix' => array(
            'name' => 'Prefix',
            'describe' => 'The prefix will add to the name of every cookie',
            'type' => array('text', 'alpha', '10')
        ),
        'domain' => array(
            'name' => 'Domain',
            'describe' => 'Cookie will only avilable in the domain',
            'type' => array('text', '', '20')
        ),
    ]
);

$setting_detail['router'] = array(
    'name' => 'Router Setting',
    'list' => [
        'mode' => array(
            'name' => 'Parse Mode',
            'describe' => 'The way to parse the router path',
            'type' => array('select', array('QueryString'=>'query_string', 'PathInfo'=>'path_info', 'Rewrite'=>'rewrite'))
        ),
        'default_app' => array(
            'name' => 'Default APP',
            'describe' => 'If no parameter has been given, which app which be run',
            'type' => array('text', 'name', '50'),
        ),
        'delimiter_path' => array(
            'name' => 'Path Delimiter',
            'describe' => 'The delimiter to separate the path',
            'type' => array('text', '', '2'),
        ),
        'delimiter_para' => array(
            'name' => 'Parameter Delimiter',
            'describe' => 'The delimiter to separate the parameters',
            'type' => array('text', '', '2'),
        ),
    ]
);

$setting_detail['email'] = array(
    'name' => 'SMTP Parameter Set',
    'list' => [
        'mode' => array(
            'name' => 'Mode',
            'describe' => 'Authority mode of SMTP Server',
            'type' => array('select', array('PHP mail()'=>'', 'Normal SMTP'=>'smtp', 'SSL SMTP'=>'ssl', 'TLS SMTP'=>'tls', 'SSL/TLS Mix'=>'ssl/tls'))
        ),
        'host' => array(
            'name' => 'Server',
            'describe' => 'Address of SMTP server',
            'type' => array('text', false, '30')
        ),
        'port' => array(
            'name' => 'Port',
            'describe' => 'Port of SMTP server',
            'type' => array('text', 'digital_', '5')
        ),
        'user' => array(
            'name' => 'User',
            'describe' => 'Account of SMTP server',
            'type' => array('text', false, '30')
        ),
        'password' => array(
            'name' => 'Password',
            'describe' => 'Password of SMTP server',
            'type' => array('text', false, '40')
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
            'type' => array('select', array('GBK'=>'gbk', 'UTF-8'=>'utf-8', 'Latin1'=>'latin1'))
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

return $setting_detail;