<?PHP

$construction = array();

$construction['gen'] = array();
$construction['gen']['language'] = 'text';
$construction['gen']['charset'] = 'text';
$construction['gen']['timezone'] = 'select';
$construction['gen']['cache_mode'] = 'select';
$construction['gen']['cache_page'] = 'radio';
$construction['gen']['s_usr'] = 'text';
$construction['gen']['s_pwd'] = 'password_md5';
$construction['gen']['close'] = 'text';
$construction['gen']['static'] = 'text';
$construction['gen']['debug'] = 'radio';

$construction['web'] = array();
$construction['web']['title'] = 'text';
$construction['web']['keyword'] = 'text';
$construction['web']['description'] = 'text';
$construction['web']['gzip_level'] = 'text';
$construction['web']['minify'] = 'radio';
$construction['web']['etag'] = 'text';
$construction['web']['css'] = 'text';
$construction['web']['js'] = 'text';
$construction['web']['update'] = 'text';

$construction['upload'] = array();
$construction['upload']['path_mode'] = 'text';
$construction['upload']['ban_ext'] = 'text';
$construction['upload']['free_dl'] = 'radio';

$construction['template'] = array();
$construction['template']['name'] = 'text';
$construction['template']['path'] = 'text';
$construction['template']['style'] = 'text';

$construction['session'] = array();
$construction['session']['expire'] = 'text';
$construction['session']['name'] = 'text';
$construction['session']['mode'] = 'select';
$construction['session']['path'] = 'text';
$construction['session']['gc'] = 'radio';
$construction['session']['trans_sid'] = 'radio';

$construction['cookie'] = array();
$construction['cookie']['path'] = 'text';
$construction['cookie']['prefix'] = 'text';

$construction['router'] = array();
$construction['router']['mode'] = 'select';
$construction['router']['default_app'] = 'text';
$construction['router']['delimiter_path'] = 'text';
$construction['router']['delimiter_para'] = 'text';

$construction['email'] = array();
$construction['email']['mode'] = 'select';
$construction['email']['host'] = 'text';
$construction['email']['port'] = 'text';
$construction['email']['user'] = 'text';
$construction['email']['password'] = 'text';

$construction['db'] = array();
$construction['db']['auto'] = 'radio';
$construction['db']['type'] = 'select';
$construction['db']['host'] = 'text';
$construction['db']['user'] = 'text';
$construction['db']['password'] = 'password';
$construction['db']['pconnect'] = 'radio';
$construction['db']['charset'] = 'select';
$construction['db']['name'] = 'text';
$construction['db']['pre'] = 'text';

$construction['memcached'] = array();
$construction['memcached']['server'] = 'text';
$construction['memcached']['expire'] = 'text';
$construction['memcached']['persistent'] = 'radio';
$construction['memcached']['weight'] = 'text';
$construction['memcached']['timeout'] = 'text';
$construction['memcached']['retry_interval'] = 'text';
return $construction;