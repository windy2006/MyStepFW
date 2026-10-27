<?php
/**
 * 文件：plugin/social/module/social.php (new)
 * 功能说明：会员第三方登录模块，将宿主普通会员接口与解析后的功能路径交给插件流程。
 * 方法目录：无具名方法；对应template/social.tpl。
 * 使用说明：宿主user模块通过myController::module传入当前全局上下文，不创建控制器或绕过插件状态。
 */
global $mystep,$info_app,$content;
$content=plugin_social::page($mystep->users(),array_slice($info_app['path'],2));
