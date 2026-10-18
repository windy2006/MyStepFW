<?php
/**
 * 文件：app/Admin/module/function/plugin.php
 * 功能说明：本功能页面读取与动作处理，保持业务和对应模板独立维护。
 * 方法目录：plugins、togglePlugin、uninstallPlugin、deletePlugin、orderPlugins、packPlugin、uploadPlugin（受信模块回调，功能及参数说明见各回调PHPDoc）。
 * 对应模板：plugin_actions、function_plugin。
 * 使用说明：由AdminService按pages.php中的固定模块映射加载；$this为应用服务，ui为当前Admin。
 * 返回说明：视图回调返回模板HTML，动作返回反馈或下载描述；HTTP鉴权及参数校验由Admin统一完成。
 */
return [
    /**
     * 功能说明：插件安装、启停、顺序、导出和卸载。
     * 参数说明：无显式参数。
     * @return string 插件安装、启停、顺序、导出和卸载
     */
    'plugins' => function() {
        $installed = $this->installed();
        $catalog = $this->catalog(PLUGIN);
        $rows = $orders = [];
        foreach($catalog as $id => $info) {
            $record = $installed[$id]??null;
            $active = $record && trim($record['active']) === '1';
            $actions = $this->ui->view('plugin_actions', ['toggle_label' => $active ? '停用' : '启用', 'idx' => $id, 'setting_url' => $this->ui->url($record?'function/plugin/setting':'function/plugin/view', ['idx'=>$id])], [], ['installed' => (bool)$record]);
            if($record) $orders[] = ['idx' => $id, 'name' => $info['name'], 'state' => $active ? '已启用' : '已停用', 'order' => (int)$record['order']];
            $rows[] = ['name'=>$info['name'], 'idx'=>$id, 'version'=>$info['ver'], 'state'=>$record?(trim($record['active'])==='1'?'已启用':'已停用'):'未安装', 'intro'=>$info['intro'], 'actions'=>$actions];
        }
        foreach($installed as $id => $record) if(!isset($catalog[$id])) $rows[] = ['name'=>$record['name'],'idx'=>$id,'version'=>$record['ver'],'state'=>'目录缺失','intro'=>'可恢复目录后卸载，避免执行不完整的卸载代码。','actions'=>''];
        // 与插件注册表执行规则一致：数值越大越先运行，同值按标识排列以稳定展示。
        usort($orders, function($a, $b) { return ($b['order'] <=> $a['order']) ?: strcmp($a['idx'], $b['idx']); });
        return $this->ui->view('function_plugin', [], ['plugins'=>$rows, 'orders'=>$orders], ['has_orders'=>(bool)$orders]);
    },
    /**
     * 功能说明：切换已安装插件的启用状态。
     * @param array $p idx
     * @return array 反馈
     */
    'togglePlugin' => function($p) {
        $id = AdminStore::identifier($p['idx']);
        $db = $this->registry();
        if(!$db)throw new InvalidArgumentException('插件尚未安装。');
        try {
            if($db->mutate(
                /**
                 * 功能说明：在注册表锁内读取并切换目标插件的启用状态。
                 * 闭包依赖：通过use捕获id。
                 * @param array &$rows 当前插件记录，引用修改
                 * @return bool 更新目标后返回true
                 * @throws InvalidArgumentException 目标未安装或刚被卸载
                 */
                function(&$rows)use($id) {
                    foreach($rows as &$row)if($row['idx']===$id){$row['active']=trim($row['active'])==='1'?0:1;return true;}
                    throw new InvalidArgumentException('插件尚未安装。');
                }
            )!==true)throw new RuntimeException('无法更新插件状态。');
        } finally {$db->close();}
        return $this->ui->success('插件状态已更新。');
    },
    /**
     * 功能说明：执行插件卸载钩子并移除注册和应用绑定。
     * @param array $p idx
     * @return array 反馈
     */
    'uninstallPlugin' => function($p) {
        $id = $p['idx'];
        if(!isset($this->installed()[$id]))throw new InvalidArgumentException('插件尚未安装。');
        list($dir, $class) = $this->plugin($id);
        $class::uninstall();
        $db = $this->registry();
        $db->delete(['idx' => $id]);
        $db->close();
        $this->bindPlugin($id, []);
        return $this->ui->success('插件已卸载。');
    },
    /**
     * 功能说明：只删除尚未安装的安全插件目录。
     * @param array $p idx
     * @return array 反馈
     */
    'deletePlugin' => function($p) {
        $id = $p['idx'];
        $dir = AdminStore::directory(PLUGIN, $id);
        if(isset($this->installed()[$id]))throw new InvalidArgumentException('请先卸载插件。');
        if(!myFile::del($dir))throw new RuntimeException('删除插件失败。');
        return $this->ui->success('插件文件已删除。');
    },
    /**
     * 功能说明：在单个simpleDB排他锁内更新并排序实际记录。
     * @param array $p order
     * @return array 反馈
     */
    'orderPlugins' => function($p) {
        if(!$p['order'])return $this->ui->success('没有需要排序的插件。');
        require_once PLUGIN.'interface_plugin.class.php';
        setPluginOrder($p['order']);
        return $this->ui->success('运行顺序已保存。');
    },
    /**
     * 功能说明：在独立副本中清理运行配置后生成插件下载包。
     * @param array $p idx
     * @return array 包下载
     */
    'packPlugin' => function($p) {
        require_once PLUGIN.'interface_plugin.class.php';
        return ['download'=>buildPluginPackage($p['idx'])];
    },
    /**
     * 功能说明：部署新插件包到插件目录。
     * @param array $p 校验参数
     * @return array 上传反馈
     */
    'uploadPlugin' => function($p) {
        return $this->uploadPackage(PLUGIN, 'plugin');
    },
];
