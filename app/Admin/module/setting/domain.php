<?php
/**
 * 文件：app/Admin/module/setting/domain.php
 * 功能说明：本功能页面读取与动作处理，保持业务和对应模板独立维护。
 * 方法目录：domains、saveDomains（受信模块回调，功能及参数说明见各回调PHPDoc）。
 * 对应模板：setting_domain。
 * 使用说明：由AdminService按pages.php中的固定模块映射加载；$this为应用服务，ui为当前Admin。
 * 返回说明：视图回调返回模板HTML，动作返回反馈或下载描述；HTTP鉴权及参数校验由Admin统一完成。
 */
return [
    /**
     * 功能说明：域名绑定编辑器。
     * 参数说明：无显式参数。
     * @return string 域名绑定编辑器
     */
    'domains' => function() {
        $file = CONFIG.'domain.php';
        $version = AdminStore::version($file);
        $rows = [];
        foreach(AdminStore::read($file) as $host => $rule) $rows[] = ['index' => count($rows), 'domain' => $host, 'rule' => $rule];
        return $this->ui->view('setting_domain', ['version' => $version], ['bindings' => $rows]);
    },
    /**
     * 功能说明：验证域名绑定行并保存完整映射。
     * @param array $p rows/version
     * @return array 反馈
     */
    'saveDomains' => function($p) {
        $out = [];
        foreach($p['rows'] as $row) {
            if(!is_array($row))throw new InvalidArgumentException('绑定行无效。');
            $host = strtolower(trim($row['domain']??''));
            $rule = trim($row['rule']??'');
            if($host === '' && $rule === '')continue;
            if(!preg_match('/\A[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?\z/D', $host) || $rule === '' || preg_match('/[\r\n]/', $rule))throw new InvalidArgumentException('域名或规则无效。');
            if(isset($out[$host]))throw new InvalidArgumentException('域名重复。');
            $out[$host] = $rule;
        }
        AdminStore::saveData(CONFIG . 'domain.php', $out, $p['version']);
        return $this->ui->success();
    },
];
