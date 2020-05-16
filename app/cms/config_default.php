<?PHP
$setting = array();

$setting['web'] = array();
$setting['web']['title'] = '迈思内容管理系统 MyStep CMS';

$setting['db'] = array();
$setting['db']['auto'] = true;
$setting['db']['type'] = 'mysql';
$setting['db']['host'] = '127.0.0.1:3306';
$setting['db']['user'] = 'root';
$setting['db']['password'] = '';
$setting['db']['pconnect'] = false;
$setting['db']['charset'] = 'utf8';
$setting['db']['name'] = 'mystep_cms';
$setting['db']['pre'] = 'cms_';

$setting['content'] = array();
$setting['content']['cat_pos'] = '主导航,列表导航,自定义位置';
$setting['content']['push_pos'] = '首页,列表页,内容页';
$setting['content']['push_mode'] = '不推送,标题列表,幻灯图片';
$setting['content']['upload'] = 'files/';
$setting['content']['get_remote_img'] = true;

$setting['template'] = array();
$setting['template']['name'] = 'main';
$setting['template']['path'] = 'template';
$setting['template']['style'] = 'default';

$setting['list'] = array();
$setting['list']['txt'] = 30;
$setting['list']['img'] = 12;
$setting['list']['mix'] = 15;
$setting['list']['rss'] = 50;

$setting['expire'] = array();
$setting['expire']['default'] = 600;
$setting['expire']['index'] = 1800;
$setting['expire']['list'] = 3600;
$setting['expire']['tag'] = 86400;
$setting['expire']['read'] = 604800;

$setting['watermark'] = array();
$setting['watermark']['mode'] = 2;
$setting['watermark']['txt'] = 'MyStep CMS';
$setting['watermark']['img'] = 'static/images/logo.png';
$setting['watermark']['position'] = 3;
$setting['watermark']['img_rate'] = 4;
$setting['watermark']['txt_font'] = 'static/fonts/font.ttc';
$setting['watermark']['txt_fontsize'] = 12;
$setting['watermark']['txt_fontcolor'] = '#FFFFFF';
$setting['watermark']['txt_bgcolor'] = '#000000';
$setting['watermark']['alpha'] = 50;
$setting['watermark']['credit'] = 'Original From MyStep';

return $setting;