<?php
/**
 * 文件：app/Admin/module/setting/class.php
 * 功能说明：本功能页面读取与动作处理，保持业务和对应模板独立维护。
 * 方法目录：classes、saveClasses（受信模块回调，功能及参数说明见各回调PHPDoc）。
 * 对应模板：setting_class。
 * 使用说明：由AdminService按lib.php中pages()的固定模块映射加载；$this为应用服务，ui为当前Admin。
 * 返回说明：视图回调返回模板HTML，动作返回反馈或下载描述；HTTP鉴权及参数校验由Admin统一完成。
 */
return [
    /**
     * 功能说明：类加载规则与别名入口。
     * 参数说明：无显式参数。
     * @return string 已配置的类加载规则与别名入口，不额外追加新增占位行
     */
    'classes' => function() {
        $file = CONFIG.'class.php';
        $version = AdminStore::version($file);
        $rows = AdminStore::read($file);
        foreach($rows as $i => &$row) { $row['index'] = $i; $row['view_url'] = $this->ui->url('setting/alias', ['directory'=>$i]); $row['remove_disabled'] = $i === 0?'disabled':''; $row['path'] = myPath::relative($row['path']); $row['idx'] = var_export($row['idx']??[], true); }
        unset($row);
        return $this->ui->view('setting_class', ['version' => $version, 'alias_url' => $this->ui->url('setting/alias')], ['rules' => $rows]);
    },
    /**
     * 功能说明：验证类目录和PHP数组索引并保存类加载规则。
     * @param array $p rows/version
     * @return array 操作结果
     */
    'saveClasses' => function($p) {
        $rows = [];
        foreach($p['rows'] as $row) {
            if(!is_array($row)) throw new InvalidArgumentException('类加载规则无效。');
            if(empty($row['path'])) continue;
            $row['idx'] = myClassLoader::mapping($row['idx']??'');
            $rows[] = $row;
        }
        $out = myClassLoader::normalize($rows, true);
        myClassLoader::save(CONFIG.'class.php', $out, $p['version']);
        return $this->ui->success();
    },
];
