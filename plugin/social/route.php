<?php
/** 文件：plugin/social/route.php (new)；功能说明：框架管理员查看插件接入状态，会员入口由宿主应用user模块承接。方法目录：无。使用说明：路由仍由活动插件注册表控制，后台使用统一插件布局与鉴权。 */
$rule=[['/social/[any]',['app\myStep\logCheck','plugin_social::main']]];
