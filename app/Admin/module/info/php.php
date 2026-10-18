<?php
/**
 * 文件：app/Admin/module/info/php.php
 * 功能说明：本功能页面读取与动作处理，保持业务和对应模板独立维护。
 * 方法目录：php（受信模块回调，功能及参数说明见各回调PHPDoc）。
 * 对应模板：info_php。
 * 使用说明：由AdminService按pages.php中的固定模块映射加载；$this为应用服务，ui为当前Admin。
 * 返回说明：视图回调返回模板HTML，动作返回反馈或下载描述；HTTP鉴权及参数校验由Admin统一完成。
 */
return [
    /**
     * 功能说明：PHP环境参数和扩展。
     * 参数说明：无显式参数。
     * @return string PHP环境参数和扩展
     */
    'php' => function() {
        $data = ['PHP版本' => PHP_VERSION, '运行方式' => PHP_SAPI, 'Zend引擎' => zend_version()];
        foreach(['memory_limit','post_max_size','upload_max_filesize','max_execution_time','display_errors','enable_dl','allow_url_fopen','disable_functions','precision','SMTP','smtp_port'] as $name) $data[$name] = ini_get($name);
        return $this->ui->view('info_php', ['extensions' => implode(' · ', get_loaded_extensions())], ['info' => $this->pairs($data)]);
    },
];
