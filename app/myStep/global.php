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
    require_once PLUGIN.'interface_plugin.class.php';
    $adminMenu=json_decode(myFile::getLocal(APP.'myStep/menu.json'),true);
    $adminMenu=is_array($adminMenu)?$adminMenu:[];
    $last=array_key_last($adminMenu);
    foreach($adminMenu as $key=>&$group) {
        $group['link']=($group['link']??'#')==='#'?'#':myStep::setURL($group['link']);
        if($key===$last) $group['items']=pluginAdminMenu('myStep',$group['items']??[]);
        else foreach($group['items'] as &$item) $item['link']=myStep::setURL($item['link']);
        unset($item);
    }
    unset($group);
    $tpl->assign('admin_menu_json',json_encode($adminMenu,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR));
    $tpl->assign('db', empty($ms_setting->db->password)?'n':'y')
        ->assign('path', trim($path, '/'))
        ->assign('path_root', ROOT_WEB)
        ->assign('path_admin', $app_root)
        ->assign('logout_url', myStep::setURL(trim($ms_setting->gen->path_admin, '/').'/login'))
        ->assign('logout_csrf', myStep::setURL('api/myStep/csrf?core=myStep'))
        ->setCacheMode(false);
}