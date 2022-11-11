<?PHP

$setting = array();

$setting['gen'] = array();
$setting['gen']['language'] = 'default';
$setting['gen']['charset'] = 'utf-8';
$setting['gen']['timezone'] = 'Etc/GMT-8';
$setting['gen']['cache_mode'] = 'File';
$setting['gen']['cache_page'] = true;
$setting['gen']['s_usr'] = 'mystep';
$setting['gen']['s_pwd'] = 'e10adc3949ba59abbe56e057f20f883e';
$setting['gen']['close'] = '';
$setting['gen']['static'] = 'jpg,png,gif,ico,css,js,json,html,htm,woff,woff2,eot,svg,ttf,map,zip,rar,7z,txt,mp3,mp4,flv';
$setting['gen']['memory'] = '256M';
$setting['gen']['force_domain'] = false;
$setting['gen']['debug'] = false;

$setting['web'] = array();
$setting['web']['title'] = '迈思框架 MyStep Framework';
$setting['web']['keyword'] = 'mystep,framework,free';
$setting['web']['description'] = '开源PHP框架系统';
$setting['web']['gzip_level'] = 0;
$setting['web']['minify'] = false;
$setting['web']['etag'] = 'etag_20220101';
$setting['web']['css'] = 'bootstrap,font-awesome,glyphicons';
$setting['web']['js'] = 'jquery,jquery.addon,bootstrap.bundle';
$setting['web']['update'] = 'mysteps.com.cn';

$setting['upload'] = array();
$setting['upload']['path_mode'] = 'Y/m/';
$setting['upload']['ban_ext'] = 'php,exe,com,bat,pif';
$setting['upload']['free_dl'] = false;

$setting['template'] = array();
$setting['template']['name'] = 'main';
$setting['template']['path'] = 'template';
$setting['template']['style'] = '';

$setting['session'] = array();
$setting['session']['expire'] = 30;
$setting['session']['name'] = 'MyStepSession';
$setting['session']['mode'] = 'sess_file';
$setting['session']['path'] = './cache/session/';
$setting['session']['gc'] = true;
$setting['session']['trans_sid'] = false;

$setting['cookie'] = array();
$setting['cookie']['prefix'] = 'ms_';
$setting['cookie']['domain'] = '';

$setting['router'] = array();
$setting['router']['mode'] = 'query_string';
$setting['router']['default_app'] = 'myStep';
$setting['router']['delimiter_path'] = '/';
$setting['router']['delimiter_para'] = '&';

$setting['email'] = array();
$setting['email']['mode'] = 'smtp';
$setting['email']['host'] = '';
$setting['email']['port'] = 25;
$setting['email']['user'] = '';
$setting['email']['password'] = '';

$setting['db'] = array();
$setting['db']['auto'] = false;
$setting['db']['type'] = 'mysql';
$setting['db']['host'] = '127.0.0.1:3306';
$setting['db']['user'] = 'root';
$setting['db']['password'] = '';
$setting['db']['pconnect'] = false;
$setting['db']['charset'] = 'utf-8';
$setting['db']['name'] = 'mystep';
$setting['db']['pre'] = 'ms_';
return $setting;