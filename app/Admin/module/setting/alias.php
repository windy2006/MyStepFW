<?php
/**
 * 文件：app/Admin/module/setting/alias.php
 * 功能说明：本功能页面读取与动作处理，保持业务和对应模板独立维护。
 * 方法目录：aliases、saveAliases（受信模块回调，功能及参数说明见各回调PHPDoc）。
 * 对应模板：setting_alias。
 * 使用说明：由AdminService按pages.php中的固定模块映射加载；$this为应用服务，ui为当前Admin。
 * 返回说明：视图回调返回模板HTML，动作返回反馈或下载描述；HTTP鉴权及参数校验由Admin统一完成。
 */
return [
    /**
     * 功能说明：类别名表单。
     * 参数说明：无显式参数。
     * @return string 类别名表单
     */
    'aliases' => function() {
        $file = CONFIG.'class_alias.php';
        $version = AdminStore::version($file);
        $data = AdminStore::read($file);
        $classes = [];
        $directory = $this->ui->input('directory');
        foreach($this->classesList($directory) as $class) $classes[] = ['name' => $class, 'alias' => $data[$class]??'', 'url' => $this->ui->url('setting/detail', ['class' => $class])];
        return $this->ui->view('setting_alias', ['version' => $version, 'directory' => $directory], ['classes' => $classes], ['scoped' => $directory !== null]);
    },
    /**
     * 功能说明：检查类及别名冲突后保存类别名配置。
     * @param array $p aliases/version
     * @return array 保存反馈
     */
    'saveAliases' => function($p) {
        $allowed = $this->classesList($p['directory']??null);
        $out = AdminStore::read(CONFIG.'class_alias.php');
        foreach($allowed as $class) unset($out[$class]);
        foreach($p['aliases'] as $class => $alias) {
            if(!isset($allowed[$class]) || !is_string($alias)) throw new InvalidArgumentException('类或别名无效。');
            if($alias !== '') $out[$class] = $alias;
        }
        $seen = [];
        foreach($out as $class => $alias) {
            AdminStore::identifier($alias);
            $lower = strtolower($alias);
            if(isset($seen[$lower]) || (class_exists($alias, false) && strcasecmp((new ReflectionClass($alias))->getName(), $class) !== 0)) throw new InvalidArgumentException('别名重复或与已有类冲突。');
            $seen[$lower] = true;
        }
        AdminStore::saveData(CONFIG.'class_alias.php', $out, $p['version']);
        return $this->ui->success('保存成功。', 'setting/alias', isset($p['directory'])?['directory'=>$p['directory']]:[]);
    },
];
