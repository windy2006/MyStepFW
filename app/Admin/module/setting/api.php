<?php
/**
 * 文件：app/Admin/module/setting/api.php (new)
 * 功能说明：兼容旧独立API参数入口，跳转到框架现有参数设置。
 * 方法目录：apiSettings（受信页面回调）。
 * 对应模板：setting_main；全局API字段由框架多语种结构生成。
 * 使用说明：由Admin完成管理员认证后调用，不处理旧独立表单保存。
 */
return [
    /**
     * 功能说明：将已认证访问引导到统一框架参数设置。
     * 参数说明：无显式参数。
     * @return void 302跳转并结束
     */
    'apiSettings'=>function(){myStep::redirect($this->ui->url('setting').'#api');},
];
