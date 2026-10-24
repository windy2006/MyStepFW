<?php
/**
 * 文件：app/Admin/module/info/phpinfo.php
 * 功能说明：本功能页面读取与动作处理，保持业务和对应模板独立维护。
 * 方法目录：phpinfo（受信模块回调，功能及参数说明见各回调PHPDoc）。
 * 对应模板：info_phpinfo。
 * 使用说明：由AdminService按lib.php中pages()的固定模块映射加载；$this为应用服务，ui为当前Admin。
 * 返回说明：视图回调返回模板HTML，动作返回反馈或下载描述；HTTP鉴权及参数校验由Admin统一完成。
 */
return [
    /**
     * 功能说明：受管理员保护的phpinfo表格。
     * 参数说明：无显式参数。
     * @return string 受管理员保护的phpinfo表格
     */
    'phpinfo' => function() {
        ob_start();
        phpinfo(INFO_GENERAL|INFO_CONFIGURATION|INFO_MODULES);
        $html = ob_get_clean();
        preg_match('#<body[^>]*>(.*?)</body>#is', $html, $match);
        return $this->ui->view('info_phpinfo', ['phpinfo' => $match[1]??$html]);
    },
];
