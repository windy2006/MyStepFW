<?PHP
if(myReq::check('files')) {
    $path_upload = CACHE.'tmp';
    $upload = new myUploader($path_upload, true);
    $upload->do(false);
    $result = $upload->result();
    if($result[0]['error'] == 0) {
        $file = $path_upload.'/'.$result[0]['new_name'];
        $name = strstr($result[0]['name'], '.', true);
        $name = str_replace('app_', '', $name);
        if(!file_exists(APP.$name)) {
            $mypack = $mystep->getInstance('myPacker', APP.$name.'/', $file);
            $mypack->unpack();
            unset($mypack);
            myFile::del($file);
            if(is_file(APP.$name.'/config_new.php')) myFile::move(APP.$name.'/config_new.php', APP.$name.'/config.php');
            $result = [
                'error' => 0,
                'message' => $mystep->getLanguage('plugin_upload_done')
            ];
        } else {
            $result = [
                'error' => 99,
                'message' => $mystep->getLanguage('plugin_upload_exists')
            ];
        }
    } else {
        $result = [
            'error' => $result[0]['error'],
            'message' => $result[0]['message']
        ];
    }
    unset($upload);
    echo myString::toJson($result, $ms_setting->gen->charset);
    $mystep->end();
}
if(myReq::check('post')) {
    global $router;
    $name = myReq::post('name');
    $route = myReq::post('route');
    $plugin = myReq::post('plugin');
    $route_plugin = myReq::post('route_plugin');
    myFile::del(CONFIG.'route.php');
    for($i=0,$m=count($name);$i<$m;$i++) {
        myFile::saveFile(APP.$name[$i].'/route.php', htmlspecialchars_decode($route[$i]));
        myFile::saveFile(APP.$name[$i].'/plugin.php', '<?PHP'.chr(10).'return '.var_export(explode(',', $plugin[$i]), true).';');
        $ms_setting->merge(APP.$name[$i].'/config.php');
        $router->checkRoute(CONFIG.'route.php', APP.$name[$i].'/route.php', $name[$i]);
    }
    if(empty($route_plugin)) {
        myFile::del(CONFIG.'route_plugin.php');
    } else {
        myFile::saveFile(CONFIG.'route_plugin.php', htmlspecialchars_decode($route_plugin));
    }
    if(is_file(CONFIG.'route_plugin.php')) {
        $list = include(CONFIG.'route_plugin.php');
        foreach($list as $k => $v) {
            $router->checkRoute(CONFIG.'route.php', $v, 'plugin_'.$k);
        }
    }
    $mystep->setAddedContent('end', '<script>alert("'.$mystep->getLanguage('setting_done').'");</script>');
}

$check_app = [];
if(is_file(PLUGIN.'manager/check_app.php')) $check_app = include(PLUGIN.'manager/check_app.php');

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
            'copyright' => '版权所有 2022 <a href="mailto:windy2006@gmail.com">Windy2000</a>'
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
    $info['update'] = (version_compare($info['ver'], $check_app[$info['app']]??'')<0) ? '<a href="'.ROOT_WEB.$ms_setting->gen->path_admin.'/app/'.$info['app'].'" title="v'.$check_app[$info['app']].'">【Update】</a>': '';
    $t->setLoop('app', $info);
}
$route_plugin = '';
if(is_file(CONFIG.'route_plugin.php')) {
    $route_plugin = htmlspecialchars(myFile::getLocal(CONFIG.'route_plugin.php'));
}
$t->assign('route_plugin', $route_plugin);
$mydb = new myDb('simpleDB', 'plugin', PLUGIN);
$plugin = array();
if($mydb->check()) {
    $plugin = $mydb->records();
}
foreach($plugin as $k) {
    $t->setLoop('plugin', $k);
}
