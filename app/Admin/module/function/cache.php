<?php
/**
 * 文件：app/Admin/module/function/cache.php
 * 功能说明：本功能页面读取与动作处理，保持业务和对应模板独立维护。
 * 方法目录：caches、clearCaches（受信模块回调，功能及参数说明见各回调PHPDoc）。
 * 对应模板：function_cache。
 * 使用说明：由AdminService按lib.php中pages()的固定模块映射加载；$this为应用服务，ui为当前Admin。
 * 返回说明：视图回调返回模板HTML，动作返回反馈或下载描述；HTTP鉴权及参数校验由Admin统一完成。
 */
return [
    /**
     * 功能说明：缓存分类、容量及清理。
     * 参数说明：无显式参数。
     * @return string 缓存分类、容量及清理
     */
    'caches' => function() {
        $rows = [];
        foreach(['script'=>'合并脚本','template'=>'编译模板','language'=>'语言','data'=>'数据缓存','session'=>'会话','tmp'=>'临时文件','app'=>'应用页面'] as $key => $label) $rows[] = ['id' => $key, 'name' => $label, 'size' => myFile::getSize(CACHE.$key)];
        return $this->ui->view('function_cache', [], ['caches' => $rows]);
    },
    /**
     * 功能说明：清理明确登记的缓存类别；all代表全部七类，预检所有目录后再执行，不删除管理锁及审计目录。
     * @param array $p areas为类别标识数组；all包含会话，清理后可能需要重新登录
     * @return array 清理反馈
     */
    'clearCaches' => function($p) {
        $allowed = ['script', 'template', 'language', 'data', 'session', 'tmp', 'app'];
        foreach($p['areas'] as $area)if(!is_string($area) || ($area !== 'all' && !in_array($area, $allowed, true)))throw new InvalidArgumentException('缓存分类无效。');
        $all = in_array('all', $p['areas'], true);
        $areas = $all ? $allowed : array_unique($p['areas']);
        foreach($areas as $area)if(is_link(CACHE . $area))throw new InvalidArgumentException('缓存目录为链接。');
        foreach($areas as $area) {
            if($area === 'data') {
                if(!myStep::clearDataCache())throw new RuntimeException('数据缓存清理失败。');
            }
            else {
                if(file_exists(CACHE . $area) && !myFile::del(CACHE . $area))throw new RuntimeException('缓存清理失败。');
                if(!myFile::mkdir(CACHE . $area))throw new RuntimeException('缓存目录重建失败。');
            }
        }
        return $this->ui->success($all ? '全部缓存已清理；如会话失效，请重新登录。' : '选定缓存已清理。');
    },
];
