<?php
/**
 * 文件：app/Admin/plugin_layout.php
 * 功能说明：声明Admin通用插件后台布局适配器，所有调用showPluginPage的插件共用。
 * 方法目录：无新增函数；返回Admin::pluginLayout回调。
 * 使用说明：由插件公共展示入口在登录校验后读取；回调返回myTemplate，不独立输出或启动框架。
 */
require_once __DIR__.'/Admin.class.php';
return ['Admin','pluginLayout'];
