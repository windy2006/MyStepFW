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
        $registryVersion = AdminStore::version(PLUGIN.'plugin.db');
        $plugins = array_intersect_key($this->catalog(PLUGIN), myStep::activePlugins());
        require_once PLUGIN.'guide.php';
        $guide = new pluginGuide(APP, ROOT_WEB, myStep::setURL('app/guide'), 'app');
        foreach($this->catalog(APP) as $id => $info) {
            $dir = APP.$id.'/';
            $version = AdminStore::version($dir.'route.php');
            $pluginVersion = AdminStore::version($dir.'plugin.php');
            $selected = AdminStore::read($dir.'plugin.php');
            $checks = [];
            foreach($plugins as $key => $meta) $checks[] = ['id' => $key, 'name' => $meta['name'], 'selected' => in_array($key,$selected,true)?'checked':''];
            $hasSettings = true;
            try { $this->configTarget($id); }
            catch(InvalidArgumentException $error) { $hasSettings = false; }
            $hasLanguage = (bool)$this->languageTypes($id);
            $apps[] = ['content' => $this->ui->view('app_item', ['id' => $id, 'name' => $info['name'], 'version_label' => $info['ver'], 'intro' => $info['intro'], 'version' => $version, 'plugin_version' => $pluginVersion, 'registry_version' => $registryVersion, 'help_url' => $guide->url($id), 'route' => myFile::getLocal($dir.'route.php'), 'settings_url' => $this->ui->url('setting', ['target'=>$id]), 'language_url' => $this->ui->url('function/language', ['app'=>$id])], ['plugins' => $checks], ['has_settings'=>$hasSettings, 'has_language'=>$hasLanguage, 'has_help'=>is_file($dir.'README.md'), 'has_plugins'=>(bool)$plugins])];
        }
        $mappingFile = CONFIG.'route_plugin.php';
        $version = AdminStore::version($mappingFile);
        $mapping = AdminStore::read($mappingFile);
        foreach($mapping as &$source) $source = myPath::relative($source);
        unset($source);
        return $this->ui->view('function_app', [
            'version' => $version,
            'route_mapping' => "<?php\nreturn ".var_export($mapping, true).";\n",
            'routing_url' => $this->ui->url('guide', ['doc' => 'routing']),
        ], ['apps' => $apps]);
    },
    /**
     * 功能说明：校验PHP语法、插件注册版本与配置版本后保存路由及活动插件选择，保留未显示的关联。
     * @param array $p name/route/plugins/version/plugin_version/registry_version，plugins仅含页面可见的活动插件
     * @return array 保存反馈
     */
    'saveApp' => function($p) {
        $dir = AdminStore::directory(APP, $p['name']);
        if(AdminStore::version(PLUGIN.'plugin.db') !== $p['registry_version']) throw new RuntimeException('插件状态已改变，请刷新后重新选择。', 409);
        $available = array_intersect_key($this->catalog(PLUGIN), myStep::activePlugins());
        foreach($p['plugins'] as $id) {
            AdminStore::identifier($id);
            if(!isset($available[$id])) throw new InvalidArgumentException('只能选择已安装并启用的插件，请刷新后重试。');
        }
        // 不显示的关联继续保留，避免保存应用路由时丢失停用插件的原有绑定。
        $retained = array_filter(AdminStore::read($dir.'plugin.php'), static function($id) use ($available, $p) {
            return !isset($available[$id]) || in_array($id, $p['plugins'], true);
        });
        $selected = array_values(array_unique(array_merge($retained, $p['plugins'])));
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
        AdminStore::saveData($dir . 'plugin.php', $selected, $p['plugin_version']);
        return $this->ui->success('应用定义已保存，下次请求自动重建路由。');
    },
    /**
     * 功能说明：解析路由扩展数组，验证根目录内的路由源并原子保存映射；不执行提交文本或源文件。
     * @param array $p mapping为PHP数组字面量或完整<?php return数组配置（空白清除映射），version为浏览时摘要
     * @return array 保存反馈；下次请求自动重建路由，已有插件启停约束保持不变
     * @throws InvalidArgumentException 数组格式、扩展标识、路径、源文件或PHP语法无效
     * @throws RuntimeException 配置保存冲突或写入失败
     */
    'saveMapping' => function($p) {
        $text = $p['mapping'];
        if(!is_string($text)) throw new InvalidArgumentException('路由扩展须为PHP数组配置。');
        $text = trim($text);
        if(preg_match('/\A<\?php\s+return\s+(.+)\z/is', $text, $match)) $text = $match[1];
        try {
            $mapping = myClassLoader::mapping($text);
        } catch(InvalidArgumentException $error) {
            throw new InvalidArgumentException('路由扩展须为标识到文件路径的PHP数组，不能包含可执行代码。', 0, $error);
        }
        $out = [];
        foreach($mapping as $id => $file) {
            AdminStore::identifier($id);
            $relative = myPath::relative(trim($file));
            $source = realpath(myPath::absolute($relative));
            if(!$source || !is_file($source) || !is_readable($source) || strtolower(pathinfo($source, PATHINFO_EXTENSION)) !== 'php') {
                throw new InvalidArgumentException('路由源必须为网站根目录内可读取的PHP文件：'.$id);
            }
            // 按磁盘路径检查真实位置，拒绝链接越界；不对文件名做URL解码。
            myPath::relative($source);
            try {
                token_get_all(myFile::getLocal($source), TOKEN_PARSE);
            } catch(ParseError $error) {
                throw new InvalidArgumentException('路由源PHP语法错误：'.$id, 0, $error);
            }
            $out[$id] = $relative;
        }
        AdminStore::saveData(CONFIG.'route_plugin.php', $out, $p['version']);
        return $this->ui->success('路由扩展已保存，下次请求自动重建路由。');
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
