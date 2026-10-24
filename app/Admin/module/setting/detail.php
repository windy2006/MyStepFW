<?php
/**
 * 文件：app/Admin/module/setting/detail.php
 * 功能说明：本功能页面读取与动作处理，保持业务和对应模板独立维护。
 * 方法目录：methods、saveMethods（受信模块回调，功能及参数说明见各回调PHPDoc）。
 * 对应模板：setting_detail。
 * 使用说明：由AdminService按lib.php中pages()的固定模块映射加载；$this为应用服务，ui为当前Admin。
 * 返回说明：视图回调返回模板HTML，动作返回反馈或下载描述；HTTP鉴权及参数校验由Admin统一完成。
 */
return [
    /**
     * 功能说明：方法反射说明及可编辑别名。
     * 参数说明：无显式参数。
     * @return string 方法反射说明及可编辑别名
     */
    'methods' => function() {
        $class = $this->ui->input('class', 'myStep');
        if(!is_string($class) || !isset($this->classesList()[$class])) throw new InvalidArgumentException('未知已登记类。');
        $file = CONFIG.'method_alias/'.$class.'.php';
        $version = AdminStore::version($file);
        $aliases = AdminStore::read($file);
        $methods = $editable = [];
        $reflection = new ReflectionClass($class);
        foreach($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->name;
            $methods[] = ['name' => $name, 'doc' => $method->getDocComment()?:''];
            if(strpos($name, '__') !== 0) $editable[] = ['name' => $name, 'alias' => array_search($name, $aliases, true)?:''];
        }
        return $this->ui->view('setting_detail', ['class' => $class, 'version' => $version], ['methods' => $methods, 'aliases' => $editable]);
    },
    /**
     * 功能说明：只允许已登记类的公开普通方法配置调用别名。
     * @param array $p class/aliases/version
     * @return array 保存反馈
     */
    'saveMethods' => function($p) {
        $class = $p['class'];
        if(!isset($this->classesList()[$class]))throw new InvalidArgumentException('未知类。');
        $out = [];
        foreach($p['aliases'] as $method => $alias) {
            if(!is_string($alias))throw new InvalidArgumentException('别名无效。');
            if($alias === '')continue;
            AdminStore::identifier($alias);
            $r = new ReflectionMethod($class, $method);
            if(!$r->isPublic() || strpos($method, '__') === 0 || isset($out[strtolower($alias)]))throw new InvalidArgumentException('方法或别名无效。');
            $out[strtolower($alias)] = $method;
        }
        AdminStore::saveData(CONFIG . 'method_alias/' . $class . '.php', $out, $p['version']);
        return $this->ui->success('方法别名已保存。', 'setting/detail', ['class' => $class]);
    },
];
