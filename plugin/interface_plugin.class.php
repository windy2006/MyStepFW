<?php
interface interface_plugin {
    public static function check();
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

function setPluginTemplate($idx, $name='') {
    $tpl_setting = array(
        'name' => 'main',
        'path' => APP.'myStep/template/',
        'style' => '',
        'path_compile' => CACHE.'template/plugin_'.$idx.'/'
    );
    $path = $idx.'/';
    if(empty($name)) $name = $idx;
    $tpl_main = new myTemplate($tpl_setting, false);
    $tpl_main->assign('path', $path);
    $tpl_setting['name'] = $name;
    $tpl_setting['path'] = PLUGIN.$path;
    $tpl_sub = new myTemplate($tpl_setting, false);
    return [$tpl_main, $tpl_sub];
}