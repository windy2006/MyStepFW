<?php
/** 文件：plugin/social/module/manage.php (new)；功能说明：为manage模板提供接入状态和精确平台回调地址，不显示密钥。方法目录：无。使用说明：由showPluginPage完成框架管理员鉴权与后台布局匹配。 */
plugin_social::load();$o=plugin_social::options();$tpl_sub->assign('app',plugin_social::escape($o['app']))->assign('enabled',$o['enabled']?'已启用':'未启用')->assign('endpoint',plugin_social::escape($o['endpoint']?:'尚未设置'));
foreach(SocialOAuth::providers() as $provider=>$name)$tpl_sub->setLoop('providers',['name'=>$name,'callback'=>plugin_social::escape($o['endpoint']!==''?$o['endpoint'].'callback/'.$provider:'请先设置规范入口')]);
