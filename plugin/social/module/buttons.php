<?php
/** 文件：plugin/social/module/buttons.php (new)；功能说明：为buttons模板准备固定平台POST按钮。方法目录：无。使用说明：调用作用域提供$users/$o/$oauth/$intent/$tpl_social，不在模板执行认证逻辑。 */
$token=mySecurity::token();$tpl_social->assign('csrf',plugin_social::escape($token));$tpl_social->assign('intent',plugin_social::escape($intent));
foreach(SocialOAuth::providers() as $provider=>$label)if($oauth->enabled($provider))$tpl_social->setLoop('providers',['url'=>plugin_social::escape($o['endpoint'].'begin/'.$provider),'label'=>plugin_social::escape(($intent==='bind'?'绑定':'使用').$label.($intent==='bind'?'':'登录'))]);
$tpl_social->setIf('bind',$intent==='bind');$tpl_social->assign('connections_url',plugin_social::escape($o['endpoint'].'connections'));
