<?PHP
interface interface_plugin {
    public static function check(&$result);
    public static function install();
    public static function uninstall();
}

function addPluginLink($name, $path) {
    $menu = myFile::getLocal(APP.'myStep/menu.json');
    $menu = myString::fromJson($menu);
    $len = count($menu) - 1;
    $menu[$len]['items'][] = [
        "name" => $name,
        "link" => $path
    ];
    $menu[$len]['link'] = '#';
    myFile::saveFile(APP.'myStep/menu.json', myString::toJson($menu));
}

function removePluginLink($path) {
    $menu = myFile::getLocal(APP.'myStep/menu.json');
    $menu = myString::fromJson($menu);
    $len = count($menu) - 1;
    for($i=count($menu[$len]['items'])-1;$i>=0;$i--) {
        if($menu[$len]['items'][$i]['link']==$path) {
            unset($menu[$len]['items'][$i]);
            $menu[$len]['items'] = array_values($menu[$len]['items']);
            break;
        }
    }
    if(count($menu[$len]['items'])==0) $menu[$len]['link'] = 'manager/function/plugin';
    myFile::saveFile(APP.'myStep/menu.json', myString::toJson($menu));
}

function regPluginRoute($idx) {
    global $router;
    $router->checkRoute(CONFIG.'route.php', PLUGIN.$idx.'/route.php', 'plugin_'.$idx);
    $file = CONFIG.'route_plugin.php';
    if(is_file($file)) {
        $list = include($file);
    } else {
        $list = [];
    }
    $list[$idx] = PLUGIN.$idx.'/route.php';
    myFile::saveFile($file, '<?PHP
return '.var_export($list, true).';
');
}

function removePluginRoute($idx) {
    global $router;
    $router->remove(CONFIG.'route.php', 'plugin_'.$idx);
    $file = CONFIG.'route_plugin.php';
    if(is_file($file)) {
        $list = include($file);
        unset($list[$idx]);
    } else {
        $list = [];
    }
    myFile::saveFile($file, '<?PHP
return '.var_export($list, true).';
');
}

function setPluginTemplate($idx, $page='', $mode = true) {
    if($mode) {
        $tpl_setting = array(
            'name' => 'main',
            'path' => APP.'myStep/template/',
            'style' => '',
            'path_compile' => CACHE.'template/plugin/'.$idx.'/'
        );
        $tpl_cache = false;
    } else {
        global $tpl_setting, $tpl_cache;
    }
    if(empty($page)) $page = $idx;
    $tpl_main = new myTemplate($tpl_setting, $tpl_cache);
    $tpl_setting = array(
        'name' => $page,
        'path' => PLUGIN.$idx.'/template/',
        'style' => '',
        'path_compile' => CACHE.'template/plugin/'.$idx.'/'.$GLOBALS['info_app']['app'].'/'
    );
    $tpl_sub = new myTemplate($tpl_setting, $tpl_cache);
    return [$tpl_main, $tpl_sub];
}

function showPluginPage($idx, $page='') {
    app\myStep\logCheck();
    global $mystep;
    if(empty($page)) $page = $idx;
    $path_plugin = PLUGIN.$idx.'/';
    if(!is_file($path_plugin.'template/'.$page.'.tpl')) {
        myStep::info('page_error_plugin');
    }
    list($tpl, $tpl_sub) = setPluginTemplate($idx, $page, true);
    include(APP.'myStep/global.php');
    if(is_file($path_plugin.'module/'.$page.'.php')) include($path_plugin.'module/'.$page.'.php');
    $tpl->assign('main', $mystep->render($tpl_sub));
    $mystep->show($tpl);
}