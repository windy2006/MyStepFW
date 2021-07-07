<?PHP
require 'inc.php';
// Basic Parameter init
global $id, $web_info, $web_id, $group, $group_info;
$mystep->regTag('pages', 'app\CMS\parsePages');
$db->cache(0);
$id = $info_app['para']['id']??r::r('id');
if(isset($info_app['para']['method'])) {
    $method = $info_app['para']['method'];
} else {
    $method = strtolower(end($info_app['path']));
    if(is_numeric($method)) {
        $id = $method;
        $method = array_slice($info_app['path'], -2, 1)[0];
    }
}

// System group and power set
if(($group = \app\CMS\getCache('sys_group'))===false) {
    myStep::info('error_para');
}
if(($group_info = \app\CMS\checkVal($group, 'group_id', r::s('ms_cms_group')))===false) {
    r::removeCookie('ms_cms_op');
    r::sessionEnd();
    CMS::redirect($path_admin);
}
function checkPower($idx='func', $val='', &$list=[]) {
    global $group_info;
    $result = false;
    if($idx=='func') {
        if($val=='' || $val=='info') return true;
        $admin_cat = \app\CMS\getCache('admin_cat')['admin_cat_plat'];
        $val = \app\CMS\checkVal($admin_cat, 'path', $val);
        if($val===false) return false;
        $val = $val['id'];
    }
    if(!is_null($group_info) && isset($group_info['power_'.$idx])) {
        $list = explode(',', $group_info['power_'.$idx]);
        if($list[0]=='all' || in_array($val, $list)) {
            $result = true;
        }
    }
    return $result;
}

// Website's power init
$web_id = r::r('web_id');
if(empty($web_id)) $web_id = 1;
if(!checkPower('web', $web_id, $list)) {
    if(count($list)==0) {
        CMS::redirect($path_admin.'log&out');
    } else {
        $web_id = $list[0];
    }
}
if($web_info['web_id']!=1) $web_id = $web_info['web_id'];
$web_info['setting'] = new myConfig(PATH.'website/config_'.$web_info['idx'].'.php');
function setWeb(myTemplate &$tpl, $web_id='') {
    global $website;
    if(empty($web_id)) $web_id = r::g('web_id');
    for($i=0,$m=count($website); $i<$m; $i++) {
        if(!checkPower('web', $website[$i]['web_id'])) continue;
        $website[$i]['selected'] = $website[$i]['web_id']==$web_id?'selected':'';
        $tpl->setLoop('website', $website[$i]);
    }
}
$tpl->assign('path_admin', $path_admin);

// Lead to the entrance of the script
if(!empty($info_app['path'][1]) && is_dir(PATH.'module/admin/'.$info_app['path'][1])) {
    $module = $info_app['path'][2] ?? 'main';
    $file = __DIR__.'/'.$info_app['path'][1].'/'.strtolower($module).'.php';
    if(!file_exists($file)) {
        myStep::info('module_missing');
    }
    if(!checkPower('func', implode('/', array_slice($info_app['path'],1,2)))) {
        myStep::info('admin_nopower', $path_admin);
    }
    $mystep->setAddedContent('end', '<script type="application/javascript" src="static/js/checkForm.js"></script>');
    include($file);
} else {
    $tpl_setting['name'] = 'frame';
    if(count($info_app['path'])>=2 && !empty($info_app['path'][1])) $tpl_setting['name'] = $info_app['path'][1];
    if(!file_exists($tpl_setting['path'].'/'.$tpl_setting['style'].'/'.$tpl_setting['name'].'.tpl')) {
        myStep::info('module_missing');
    }
    $t = new myTemplate($tpl_setting);
    setWeb($t);
    $t->assign('username', r::s('ms_cms_op'));
    $t->assign('groupname', $group_info['name']);
    $t->assign('list_func', $group_info['power_func']);
    $t->assign('list_web', $group_info['power_web']);
    $t->assign('path_admin', $path_admin);
    $t->assign('websites', myString::toJson($website));
    $t->assign('web_id', $web_info['web_id']);
    $content = $mystep->render($t);
}