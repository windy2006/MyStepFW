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
// 应用关联只展示已安装且启用的有效插件，读取注册摘要防止页面打开后状态改变。
$plugin_registry_version = is_file(PLUGIN.'plugin.db') ? hash_file('sha256', PLUGIN.'plugin.db') : 'missing';
$active_plugins = array_filter(myStep::activePlugins(), static function($unused, $id) {
    return is_file(PLUGIN.$id.'/info.php');
}, ARRAY_FILTER_USE_BOTH);
if(myReq::check('post')) {
    global $router;
    $name = myReq::post('name');
    $route = myReq::post('route');
    $plugin = myReq::post('plugin');
    $route_plugin = myReq::post('route_plugin');
    try {
        if(!is_string(myReq::post('plugin_registry_version')) || !hash_equals($plugin_registry_version, myReq::post('plugin_registry_version'))) throw new RuntimeException('插件状态已改变，请刷新后重新选择。', 409);
        if(!is_array($name) || !is_array($route) || !is_array($plugin) || count($name)!==count($route) || count($name)!==count($plugin)) throw new InvalidArgumentException('应用设置格式无效。');
        $bindings = [];
        foreach($name as $i => $app) {
            if(!is_string($app) || !preg_match('/\A[a-zA-Z][a-zA-Z0-9_]*\z/D', $app) || !is_file(APP.$app.'/info.php') || !is_string($plugin[$i]??null)) throw new InvalidArgumentException('应用设置无效。');
            $selected = array_values(array_unique(array_filter(array_map('trim', explode(',', $plugin[$i])), static function($id) { return $id!==''; })));
            foreach($selected as $id) if(!isset($active_plugins[$id])) throw new InvalidArgumentException('只能选择已安装并启用的插件，请刷新后重试。');
            $current = is_file(APP.$app.'/plugin.php') ? include APP.$app.'/plugin.php' : [];
            if(!is_array($current)) throw new UnexpectedValueException('应用插件配置须为数组。');
            $retained = array_filter($current, static function($id) use ($active_plugins, $selected) {
                return !isset($active_plugins[$id]) || in_array($id, $selected, true);
            });
            $bindings[$i] = array_values(array_unique(array_merge($retained, $selected)));
        }
    } catch(InvalidArgumentException|RuntimeException $error) {
        http_response_code($error->getCode() === 409 ? 409 : 422);
        myStep::info($error->getMessage(), $ms_setting->gen->path_admin.'/function/app');
    }
    myFile::del(CONFIG.'route.php');
    for($i=0,$m=count($name);$i<$m;$i++) {
        myFile::saveFile(APP.$name[$i].'/route.php', htmlspecialchars_decode($route[$i]));
        myFile::saveFile(APP.$name[$i].'/plugin.php', '<?PHP'.chr(10).'return '.var_export($bindings[$i], true).';');
        $ms_setting->merge(APP.$name[$i].'/config.php');
        $router->checkRoute(CONFIG.'route.php', APP.$name[$i].'/route.php', $name[$i]);
    }
    if(empty($route_plugin)) {
        myFile::del(CONFIG.'route_plugin.php');
    } else {
        if(!is_string($route_plugin) || !preg_match('/\A\s*<\?php\s+return\s+(.+?)\s*\z/is', htmlspecialchars_decode($route_plugin), $mapping)) throw new InvalidArgumentException('插件路由映射须为PHP数组配置。');
        $list = myClassLoader::mapping($mapping[1]);
        foreach($list as &$source) $source = myPath::relative($source);
        unset($source);
        myFile::saveFile(CONFIG.'route_plugin.php', '<?PHP'.chr(10).'return '.var_export($list, true).';');
    }
    if(is_file(CONFIG.'route_plugin.php')) {
        $list = include(CONFIG.'route_plugin.php');
        foreach($list as $k => $v) {
            $router->checkRoute(CONFIG.'route.php', myPath::absolute($v), 'plugin_'.$k);
        }
    }
    $mystep->setAddedContent('end', '<script>alert("'.$mystep->getLanguage('setting_done').'");</script>');
}

$check_app = [];
if(is_file(PLUGIN.'manager/check_app.php')) $check_app = include(PLUGIN.'manager/check_app.php');

require_once PLUGIN.'guide.php';
$app_guide = new pluginGuide(APP, ROOT_WEB, myStep::setURL('app/guide'), 'app');
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
    $info['has_help'] = is_file(APP.$k.'/README.md');
    $info['help_url'] = $app_guide->url($k);
    $info['route'] = '';
    if(is_file(APP.$k.'/route.php')) {
        $info['route'] = htmlspecialchars(myFile::getLocal(APP.$k.'/route.php'));
    }
    $info['plugin'] = '';
    if(is_file(APP.$k.'/plugin.php')) {
        $info['plugin'] = implode(',', array_values(array_intersect(include(APP.$k.'/plugin.php'), array_keys($active_plugins))));
    }
    $info['update'] = (version_compare($info['ver'], $check_app[$info['app']]??'')<0) ? '<a href="'.ROOT_WEB.$ms_setting->gen->path_admin.'/app/'.$info['app'].'" title="v'.$check_app[$info['app']].'">【Update】</a>': '';
    $t->setLoop('app', $info);
}
$route_plugin = '';
if(is_file(CONFIG.'route_plugin.php')) {
    $route_plugin = htmlspecialchars(myFile::getLocal(CONFIG.'route_plugin.php'));
}
$t->assign('route_plugin', $route_plugin);
$t->assign('plugin_registry_version', $plugin_registry_version);
$mydb = new myDb('simpleDB', 'plugin', PLUGIN);
$plugin = array();
if($mydb->check()) {
    $plugin = $mydb->records();
}
foreach($plugin as $k) {
    if(isset($active_plugins[trim($k['idx'])])) $t->setLoop('plugin', $k);
}
$mydb->close();
