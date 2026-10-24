<?php
/**
 * 文件：app/Admin/module/references.php
 * 功能说明：本功能页面读取与动作处理，保持业务和对应模板独立维护。
 * 方法目录：references（受信模块回调，功能及参数说明见各回调PHPDoc）。
 * 对应模板：references。
 * 使用说明：由AdminService按lib.php中pages()的固定模块映射加载；$this为应用服务，ui为当前Admin。
 * 返回说明：视图回调返回模板HTML，动作返回反馈或下载描述；HTTP鉴权及参数校验由Admin统一完成。
 */
return [
    /**
     * 功能说明：应用示例、类文档、安装初始化与扩展功能入口。
     * 参数说明：无显式参数。
     * @return string 应用示例、类文档、安装初始化与扩展功能入口
     */
    'references' => function() {
        return $this->ui->view('references', ['sample_url'=>myStep::setURL('Sample/'),'document_url'=>myStep::setURL('Document/')]);
    },
];
