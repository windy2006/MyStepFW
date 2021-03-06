<?PHP
if(myReq::check('post')) {
    global $router;
    $name = myReq::post('name');
    $route = myReq::post('route');
    $plugin = myReq::post('plugin');
    myFile::del(CONFIG.'route.php');
    for($i=0,$m=count($name);$i<$m;$i++) {
        myFile::saveFile(APP.$name[$i].'/route.php', $route[$i]);
        myFile::saveFile(APP.$name[$i].'/plugin.php', '<?PHP'.chr(10).'return '.var_export(explode(',', $plugin[$i]), true).';');
        $ms_setting->merge(APP.$name[$i].'/config.php');
        $router->checkRoute(CONFIG.'route.php', APP.$name[$i].'/route.php', $name[$i]);
    }
    if(is_file(CONFIG.'route_plugin.php')) {
        $list = include(CONFIG.'route_plugin.php');
        foreach($list as $k => $v) {
            $router->checkRoute(CONFIG.'route.php', $v, 'plugin_'.$k);
        }
    }
    $mystep->setAddedContent('end', '<script>alert("'.$mystep->getLanguage('setting_done').'");</script>');
}
$dirs = myFile::find('', APP, false, myFile::DIR);
$dirs = array_map(function ($v) {return basename($v);} , $dirs);
foreach($dirs as $k) {
    if(is_file(APP.$k.'/info.php')) {
        $info = include(APP.$k.'/info.php');
    } else {
        $info = array(
            'name' => '未知应用',
            'app' => $k,
            'ver' => '',
            'intro' => '信息文件缺失，应用有可能无法正常执行',
            'copyright' => '版权所有 2021 <a href="mailto:windy2006@gmail.com">Windy2000</a>'
        );
    }
    $info['route'] = '';
    if(is_file(APP.$k.'/route.php')) {
        $info['route'] = htmlspecialchars(myFile::getLocal(APP.$k.'/route.php'));
    }
    $info['plugin'] = '';
    if(is_file(APP.$k.'/plugin.php')) {
        $info['plugin'] = implode(',', include(APP.$k.'/plugin.php'));
    }
    $t->setLoop('app', $info);
}
$mydb = new myDb('simpleDB', 'plugin', PLUGIN);
$plugin = array();
if($mydb->check()) {
    $plugin = $mydb->records();
}
foreach($plugin as $k) {
    $t->setLoop('plugin', $k);
}
