<?php
/**
 * 文件：plugin/social/index.php (new)
 * 功能说明：只为配置的会员应用登记social模块；不在初始化时请求平台或修改用户表。
 * 方法目录：无具名方法。
 * 使用说明：当前宿主$this由框架提供，普通页面仍受插件安装/启用/绑定限制。
 */
require_once __DIR__.'/class.php';
try{$socialOptions=plugin_social::options();}catch(InvalidArgumentException){return;}
if(($GLOBALS['info_app']['app']??'')===$socialOptions['app'] && $socialOptions['enabled'])$this->regModule('user_social',__DIR__.'/module/social.php');
