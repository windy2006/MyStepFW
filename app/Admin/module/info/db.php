<?php
/**
 * 文件：app/Admin/module/info/db.php
 * 功能说明：本功能页面读取与动作处理，保持业务和对应模板独立维护。
 * 方法目录：database（受信模块回调，功能及参数说明见各回调PHPDoc）。
 * 对应模板：database_cells、info_db。
 * 使用说明：由AdminService按pages.php中的固定模块映射加载；$this为应用服务，ui为当前Admin。
 * 返回说明：视图回调返回模板HTML，动作返回反馈或下载描述；HTTP鉴权及参数校验由Admin统一完成。
 */
return [
    /**
     * 功能说明：数据库状态和只读浏览器。
     * 参数说明：无显式参数。
     * @return string 数据库状态和只读浏览器
     */
    'database' => function() {
        global $db, $ms_setting;
        if(!$db->check()) $db->connect(false, $ms_setting->db->name);
        $stat = $db->getStat();
        if(!is_array($stat)) $stat = ['状态' => is_scalar($stat)?$stat:'已连接'];
        $tables = $db->getTbls($ms_setting->db->name);
        $links = [];
        $table = $this->ui->input('table', '');
        foreach($tables as $name) $links[] = ['name' => $name, 'url' => $this->ui->url('db', ['table' => $name]), 'current' => $name === $table?'page':'false'];
        $valid = is_string($table) && in_array($table, $tables, true);
        $page = max(1, (int)$this->ui->input('page', 1));
        $headings = $records = [];
        if($valid) {
            $fields = $db->getFields($table, $ms_setting->db->name);
            foreach($fields as $field) $headings[] = ['name' => $field];
            $db->build('[reset]');
            $db->build($ms_setting->db->name . '.' . $table)->limit(($page-1)*50, 50);
            foreach($db->records($db->select(true)) as $row) {
                $cells = [];
                foreach($fields as $field) $cells[] = ['value' => $row[$field]??''];
                $records[] = ['cells' => $this->ui->view('database_cells', [], ['cells' => $cells])];
            }
        }
        return $this->ui->view('info_db', ['table' => $valid?$table:'', 'previous_url' => $this->ui->url('db', ['table' => $valid?$table:'', 'page' => max(1,$page-1)]), 'next_url' => $this->ui->url('db', ['table' => $valid?$table:'', 'page' => $page+1])], ['info' => $this->pairs(['Database Type' => $ms_setting->db->type]+$stat), 'tables' => $links, 'headings' => $headings, 'records' => $records], ['selected' => $valid]);
    },
];
