<?php
/**
 * 文件：app/Admin/module/function/language.php
 * 功能说明：本功能页面读取与动作处理，保持业务和对应模板独立维护。
 * 方法目录：languages、saveLanguage（受信模块回调，功能及参数说明见各回调PHPDoc）。
 * 对应模板：function_language。
 * 使用说明：由AdminService按lib.php中pages()的固定模块映射加载；$this为应用服务，ui为当前Admin。
 * 返回说明：视图回调返回模板HTML，动作返回反馈或下载描述；HTTP鉴权及参数校验由Admin统一完成。
 */
return [
    /**
     * 功能说明：语言选择、新增、编辑；未指定语言时优先default，再选择当前语种或首个可用文件。
     * 参数说明：无显式参数。
     * @return string 语言选择、新增、编辑
     */
    'languages' => function() {
        $apps = $this->catalog(APP);
        $app = $this->ui->input('app','myStep');
        $files = $this->languageTypes($app);
        $preferred = $this->ui->setting->gen->language;
        $default = isset($files['default'])?'default':(isset($files[$preferred])?$preferred:(array_key_first($files)??'default'));
        $type = $this->ui->input('type', $default);
        AdminStore::identifier($type);
        if(!isset($files[$type]))throw new InvalidArgumentException('语言文件不存在。');
        $file = $files[$type];
        $version = AdminStore::version($file);
        $data = AdminStore::read($file);
        $appLinks = $types = $items = [];
        foreach($apps as $id => $info) if($this->languageTypes($id))$appLinks[] = ['name'=>$info['name'],'detail'=>$id,'url'=>$this->ui->url('function/language',['app'=>$id]),'current'=>$id === $app?'page':'false'];
        foreach($files as $name => $unused)$types[] = ['name'=>$name,'url'=>$this->ui->url('function/language',['app'=>$app,'type'=>$name]),'current'=>$name === $type?'page':'false'];
        foreach($data as $key => $value) if(is_scalar($value)) $items[] = ['key'=>$key,'value'=>$value];
        return $this->ui->view('function_language',['app'=>$app,'type'=>$type,'version'=>$version],['apps'=>$appLinks,'types'=>$types,'items'=>$items]);
    },
    /**
     * 功能说明：仅修改已有语言键，支持另存为新语种。
     * @param array $p app/type/new/language/version
     * @return array 保存反馈
     */
    'saveLanguage' => function($p) {
        $dir = AdminStore::directory(APP, $p['app']) . 'language/';
        $type = AdminStore::identifier($p['type']);
        $files = $this->languageTypes($p['app']);
        if(!isset($files[$type]))throw new InvalidArgumentException('语言文件不存在。');
        $file = $files[$type];
        $data = AdminStore::read($file);
        foreach($p['language'] as $key => $value) {
            if(!is_string($value) || !array_key_exists($key, $data))throw new InvalidArgumentException('语言字段无效。');
            $data[$key] = $value;
        }
        $version = $p['version'];
        if(($p['new']??'') !== '') {
            if(!hash_equals(AdminStore::version($file), $version))throw new RuntimeException('源语言已改变，请重新加载。', 409);
            $type = AdminStore::identifier($p['new']);
            $file = $dir . $type . '.php';
            if(file_exists($file))throw new InvalidArgumentException('新语言已存在。');
            $version = 'missing';
        }
        AdminStore::saveData($file, $data, $version);
        return $this->ui->success('语言已保存。', 'function/language', ['app' => $p['app'], 'type' => $type]);
    },
];
