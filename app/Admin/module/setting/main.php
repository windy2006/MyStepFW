<?php
/**
 * 文件：app/Admin/module/setting/main.php
 * 功能说明：本功能页面读取与动作处理，保持业务和对应模板独立维护。
 * 方法目录：settings、saveSettings（受信模块回调，功能及参数说明见各回调PHPDoc）。
 * 对应模板：setting_main。
 * 使用说明：由AdminService按pages.php中的固定模块映射加载；$this为应用服务，ui为当前Admin。
 * 返回说明：视图回调返回模板HTML，动作返回反馈或下载描述；HTTP鉴权及参数校验由Admin统一完成。
 */
return [
    /**
     * 功能说明：全局/应用配置表单。
     * 参数说明：无显式参数。
     * @return string 全局/应用配置表单
     */
    'settings' => function() {
        $target = $this->ui->input('target', '');
        if(!is_string($target)) throw new InvalidArgumentException('应用无效。');
        $apps = [['name' => '主框架', 'detail' => '全局配置', 'url' => $this->ui->url('setting'), 'current' => $target === ''?'page':'false']];
        foreach($this->catalog(APP) as $id => $info) if(is_file(APP.$id.'/config.php') && (is_file(APP.$id.'/config/'.$this->ui->setting->gen->language.'.php') || is_file(APP.$id.'/config/default.php'))) $apps[] = ['name' => $info['name'], 'detail' => $id, 'url' => $this->ui->url('setting', ['target' => $id]), 'current' => $id === $target?'page':'false'];
        list($file, $builder) = $this->configTarget($target);
        $version = AdminStore::version($file);
        $config = new myConfig($file);
        $config->merge(myPath::settings($config->get(true), $target?:'myStep'));
        return $this->ui->view('setting_main', ['target' => $target, 'version' => $version, 'config_fields' => $this->configFields($config, $builder)], ['apps' => $apps]);
    },
    /**
     * 功能说明：验证口令及配置版本，显式调用应用保存扩展以同步关联文件。
     * @param array $p target/version/setting
     * @return array 保存反馈
     */
    'saveSettings' => function($p) {
        $target = $p['target']??'';
        list($file, $builder) = $this->configTarget($target);
        $config = new myConfig($file);
        $before = $config->get(true);
        $this->passwords($p['setting']);
        $config->set($p['setting']);
        $data = myPath::settings($config->get(true), $target?:'myStep');
        if(isset($data['gen']['path_admin']) && !preg_match('#\A[a-zA-Z][a-zA-Z0-9_/-]*\z#D', $data['gen']['path_admin']))throw new InvalidArgumentException('后台路径无效。');
        $prepare = include dirname(__DIR__, 2).'/setting_expand.php';
        $apply = $prepare($target, $before, $data);
        $original = file_get_contents($file);
        $published = hash('sha256', "<?php\nreturn ".var_export($data, true).";\n");
        AdminStore::saveData($file, $data, $p['version']);
        try {
            $apply();
        } catch(Throwable $error) {
            // 关联处理失败时仅回退本次发布的配置，不覆盖其他请求的新版本。
            if(hash_equals(AdminStore::version($file), $published))AdminStore::save($file, $original, $published);
            throw $error;
        }
        return $this->ui->success('配置已保存。', 'setting', ['target' => $target]);
    },
];
