<?PHP
global $app_root;
$path = implode('/', $info_app['path']);
$app_root = 'console';
if(defined('URL_FIX')) {
    $path = preg_replace('#^'.URL_FIX.'/#', '', $path).'/';
    $app_root = preg_replace('#^'.URL_FIX.'#', '', $app_root);
}
if($path=='/') $path=$app_root;
$app_root = myStep::setURL($app_root);
if(isset($tpl)) {
    $tpl->assign('db', empty($s->db->password)?'n':'y')
        ->assign('path', trim($path, '/'))
        ->assign('path_admin', mystep::$url_prefix.trim($app_root, '/'))
        ->setCacheMode(false);
}