<?PHP
global $app_root, $info_app, $ms_setting;
$path = implode('/', $info_app['path']);
$app_root = $ms_setting->gen->path_admin;
if(defined('URL_FIX')) {
    $path = preg_replace('#^'.URL_FIX.'/#', '', $path).'/';
    $app_root = preg_replace('#^'.URL_FIX.'#', '', $app_root);
}
if($path=='/') $path=$app_root;
$app_root = myStep::setURL($app_root);
if($app_root=='/') $app_root = '';

if(isset($tpl)) {
    $tpl->assign('db', empty($ms_setting->db->password)?'n':'y')
        ->assign('path', trim($path, '/'))
        ->assign('path_root', ROOT_WEB)
        ->assign('path_admin', str_replace('//', '/', mystep::$url_prefix.$app_root))
        ->setCacheMode(false);
}