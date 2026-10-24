<?php
/**
 * 文件：app/Admin/module/manager.php
 * 功能说明：将manager插件功能主体装配到Admin自己的布局与风格模板中。
 * 方法目录：manager（模块视图回调，说明见下方PHPDoc）。
 * 对应模板：manager。
 * 使用说明：由lib.php的pages()登记并经AdminService加载；Admin负责登录检查，插件保留原操作入口。
 */
return [
    /**
     * 功能说明：确认manager已启用，读取插件功能主体并按当前应用模板风格展示。
     * 参数说明：无显式参数。
     * @return string manager主体及Admin对应功能模板的HTML
     * @throws InvalidArgumentException manager不存在或未启用
     * @throws Throwable 插件数据或模板渲染失败
     */
    'manager' => function() {
        if(!isset(myStep::activePlugins()['manager']) || !is_file(PLUGIN.'manager/class.php')) throw new InvalidArgumentException('manager插件尚未启用。');
        require_once PLUGIN.'manager/class.php';
        return $this->ui->view('manager',['content'=>plugin_manager::pageContent('Admin')]);
    },
];
