<?php
/**
 * 文件：app/Admin/module/info/main.php
 * 功能说明：本功能页面读取与动作处理，保持业务和对应模板独立维护。
 * 方法目录：dashboard（受信模块回调，功能及参数说明见各回调PHPDoc）。
 * 对应模板：info_main。
 * 使用说明：由AdminService按lib.php中pages()的固定模块映射加载；$this为应用服务，ui为当前Admin。
 * 返回说明：视图回调返回模板HTML，动作返回反馈或下载描述；HTTP鉴权及参数校验由Admin统一完成。
 */
return [
    /**
     * 功能说明：服务器基本信息。
     * 参数说明：无显式参数。
     * @return string 服务器基本信息
     */
    'dashboard' => function() {
        return $this->ui->view('info_main', [], ['info' => $this->pairs(['服务器域名' => $_SERVER['SERVER_NAME']??'', '服务器地址' => $_SERVER['SERVER_ADDR']??'', '端口' => $_SERVER['SERVER_PORT']??'', '当前时间' => date('Y-m-d H:i:s'), '系统' => PHP_OS, 'Web服务器' => $_SERVER['SERVER_SOFTWARE']??'', '协议' => $_SERVER['SERVER_PROTOCOL']??'', '剩余空间' => myFile::formatSize(disk_free_space(ROOT)), '系统用户' => get_current_user(), '网站目录' => ROOT, '后台结构' => 'module 功能脚本 + 对应 template + 应用公共辅助', '数据连接' => '按需建立，普通设置页不连接SQL数据库'])]);
    },
];
