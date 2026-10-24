<?php
/**
 * 文件：app/Admin/module/function/plugin_setting.php
 * 功能说明：本功能页面读取与动作处理，保持业务和对应模板独立维护。
 * 方法目录：pluginSettings、installPlugin、savePlugin（受信模块回调，功能及参数说明见各回调PHPDoc）。
 * 对应模板：function_plugin_view、function_plugin_setting。
 * 使用说明：由AdminService按lib.php中pages()的固定模块映射加载；$this为应用服务，ui为当前Admin。
 * 返回说明：视图回调返回模板HTML，动作返回反馈或下载描述；HTTP鉴权及参数校验由Admin统一完成。
 */
return [
    /**
     * 功能说明：选定插件的检查、配置和应用绑定。
     * 参数说明：无显式参数。
     * @return string 选定插件的检查、配置和应用绑定
     */
    'pluginSettings' => function() {
        $id = $this->ui->input('idx');
        list($dir, $class, $info) = $this->plugin($id);
        $installed = isset($this->installed()[$id]);
        $result = '';
        $canInstall = $class::check($result);
        $file = $dir.'config.php';
        $version = AdminStore::version($file);
        $configFields = '';
        if(is_file($file)) {
            $builder = $dir.'config/'.$this->ui->setting->gen->language.'.php';
            if(!is_file($builder)) $builder = $dir.'config/default.php';
            if(is_file($builder)) $configFields = $this->configFields(new myConfig($file), $builder);
        }
        $mandatory = is_array($info['app']??null)?$info['app']:[];
        $apps = [];
        foreach($this->catalog(APP) as $app => $meta) {
            $selected = in_array($app,$mandatory,true) || in_array($id,AdminStore::read(APP.$app.'/plugin.php'),true);
            $apps[] = ['id'=>$app, 'name'=>$meta['name'], 'selected'=>$selected?'checked':''];
        }
        return $this->ui->view($installed?'function_plugin_setting':'function_plugin_view', ['idx'=>$id,'name'=>$info['name']??$id,'intro'=>$info['intro']??'','check_result'=>strip_tags($result),'check_label'=>$canInstall?'通过':'未通过','config_fields'=>$configFields,'version'=>$version,'registry_version'=>AdminStore::version(PLUGIN.'plugin.db')], ['apps'=>$apps], ['can_install'=>$installed || $canInstall]);
    },
    /**
     * 功能说明：执行受信插件安装并登记注册表和应用绑定。
     * @param array $p 安装字段
     * @return array 反馈
     */
    'installPlugin' => function($p) {
        return $this->writePlugin($p, true);
    },
    /**
     * 功能说明：保存已安装插件配置和应用绑定。
     * @param array $p 设置字段
     * @return array 反馈
     */
    'savePlugin' => function($p) {
        return $this->writePlugin($p, false);
    },
];
