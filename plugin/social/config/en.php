<?php
/**
 * 文件：plugin/social/config/en.php (new)
 * 功能说明：英文设置表单结构；参数和验证规则与中文一致，不存储密钥。
 * 方法目录：无具名方法。
 * 使用说明：myConfig按界面语言加载。
 */
$schema=include __DIR__.'/chs.php';
$schema['name']='Social member sign-in';
$labels=['enabled'=>'Enable social sign-in','app'=>'Member application','endpoint'=>'Canonical endpoint','site_name'=>'Site name','email_ttl'=>'Email validity (seconds)','pending_ttl'=>'Pending flow validity (seconds)','wechat_mode'=>'WeChat mode'];
$descriptions=['enabled'=>'Install storage first; enable only after all credentials are configured.','app'=>'Application core must implement userProvider(); CMS includes an adapter.','endpoint'=>'HTTPS URL ending in /, e.g. https://cms.example.com/user/social/. Framework query routing is also supported.','site_name'=>'Display name used in local verification mail.','email_ttl'=>'300–1800 seconds; cannot exceed pending flow validity.','pending_ttl'=>'600–3600 seconds.','wechat_mode'=>'Website QR uses a WeChat Open Platform website app; in-app OAuth uses an Official Account.'];
foreach(['wechat'=>'WeChat','qq'=>'QQ','google'=>'Google','github'=>'GitHub'] as $provider=>$label){
    $labels[$provider.'_enabled']=$label.' sign-in';$labels[$provider.'_client_id']=$label.' App ID / Client ID';$labels[$provider.'_secret']=$label.' app secret';
    $descriptions[$provider.'_enabled']='Enable an approved app with the exact callback registered.';$descriptions[$provider.'_client_id']='Provider-issued application identifier.';$descriptions[$provider.'_secret']='Leave blank to keep the existing secret; never export credentials in plugin packages.';
}
foreach($schema['list'] as $key=>&$field){$field['name']=$labels[$key];if($field['type'][0]==='password')$field['name2']='Repeat secret';$field['describe']=$descriptions[$key];if($field['type'][0]==='radio')$field['type'][1]=$key==='wechat_mode'?['Website QR'=>'web','Official Account'=>'mp']:['Enabled'=>'true','Disabled'=>'false'];}
unset($field);return $schema;
