<?php
/**
 * 文件：app/Admin/module/function/app.php
 * 功能说明：本功能页面读取与动作处理，保持业务和对应模板独立维护。
 * 方法目录：apps、saveApp、saveMapping、uploadApp（受信模块回调，功能及参数说明见各回调PHPDoc）。
 * 对应模板：app_item、function_app。
 * 使用说明：由AdminService按lib.php中pages()的固定模块映射加载；$this为应用服务，ui为当前Admin。
 * 返回说明：视图回调返回模板HTML，动作返回反馈或下载描述；HTTP鉴权及参数校验由Admin统一完成。
 */
return [
    /**
     * 功能说明：应用清单、设置和语言入口，以及路由与插件编辑；入口根据实际文件可用性展示。
     * 参数说明：无显式参数。
     * @return string 应用清单、路由与插件编辑
     */
    'apps' => function() {
        $apps = [];
        $plugins = $this->catalog(PLUGIN);
        foreach($this->catalog(APP) as $id => $info) {
            $dir = APP.$id.'/';
            $version = AdminStore::version($dir.'route.php');
            $pluginVersion = AdminStore::version($dir.'plugin.php');
            $selected = AdminStore::read($dir.'plugin.php');
            $checks = [];
            foreach($plugins as $key => $unused) $checks[] = ['id' => $key, 'selected' => in_array($key,$selected,true)?'checked':''];
            $hasSettings = true;
            try { $this->configTarget($id); }
            catch(InvalidArgumentException $error) { $hasSettings = false; }
            $hasLanguage = (bool)$this->languageTypes($id);
            $apps[] = ['content' => $this->ui->view('app_item', ['id' => $id, 'name' => $info['name'], 'version_label' => $info['ver'], 'intro' => $info['intro'], 'version' => $version, 'plugin_version' => $pluginVersion, 'route' => myFile::getLocal($dir.'route.php'), 'settings_url' => $this->ui->url('setting', ['target'=>$id]), 'language_url' => $this->ui->url('function/language', ['app'=>$id])], ['plugins' => $checks], ['has_settings'=>$hasSettings, 'has_language'=>$hasLanguage])];
        }
        $mappingFile = CONFIG.'route_plugin.php';
        $version = AdminStore::version($mappingFile);
        $mapping = AdminStore::read($mappingFile);
        $routes = [];
        foreach($plugins as $id => $info) $routes[] = ['id' => $id, 'value' => isset($mapping[$id])?myPath::relative($mapping[$id]):''];
        return $this->ui->view('function_app', ['version' => $version], ['apps' => $apps, 'routes' => $routes]);
    },
    /**
     * 功能说明：校验PHP语法和配置版本后保存应用路由及插件列表。
     * @param array $p name/route/plugins/versions
     * @return array 保存反馈
     */
    'saveApp' => function($p) {
        $dir = AdminStore::directory(APP, $p['name']);
        foreach($p['plugins'] as $id)AdminStore::directory(PLUGIN, $id);
        if(AdminStore::version($dir . 'route.php') !== $p['version'] || AdminStore::version($dir . 'plugin.php') !== $p['plugin_version'])throw new RuntimeException('应用配置已改变，请刷新。', 409);
        $route = $p['route']??'';
        if($route === '')$route = "<?php\n\$rule=[];\n";
        try {
            token_get_all($route, TOKEN_PARSE);
        }
        catch(ParseError $e) {
            throw new InvalidArgumentException('路由PHP语法错误。');
        }
        AdminStore::save($dir . 'route.php', $route, $p['version']);
        AdminStore::saveData($dir . 'plugin.php', array_values(array_unique($p['plugins'])), $p['plugin_version']);
        return $this->ui->success('应用定义已保存，下次请求自动重建路由。');
    },
    /**
     * 功能说明：验证路由来源并保存插件路由映射。
     * @param array $p mapping/version
     * @return array 保存反馈
     */
    'saveMapping' => function($p) {
        $out = [];
        foreach($p['mapping'] as $id => $file) {
            if($file === '')continue;
            $dir = AdminStore::directory(PLUGIN, $id);
            if(!is_string($file) || realpath(myPath::absolute($file)) !== realpath($dir . 'route.php') || !is_file(myPath::absolute($file)))throw new InvalidArgumentException('路由源必须为该插件的route.php。');
            $out[$id] = myPath::relative($dir . 'route.php');
        }
        AdminStore::saveData(CONFIG . 'route_plugin.php', $out, $p['version']);
        return $this->ui->success();
    },
    /**
     * 功能说明：部署新应用包到应用目录。
     * @param array $p 校验参数
     * @return array 上传反馈
     */
    'uploadApp' => function($p) {
        return $this->uploadPackage(APP, 'app');
    },
];
