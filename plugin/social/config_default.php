<?php
/** 文件：plugin/social/config_default.php (new)；功能说明：第三方登录默认关闭，所有平台凭据为空。方法目录：无。使用说明：首次安装建立独立配置；不保存站点秘密。 */
return ['enabled'=>false,'app'=>'CMS','endpoint'=>'','site_name'=>'MyStep','email_ttl'=>900,'pending_ttl'=>1800,
    'wechat_enabled'=>false,'wechat_mode'=>'web','wechat_client_id'=>'','wechat_secret'=>'',
    'qq_enabled'=>false,'qq_client_id'=>'','qq_secret'=>'','google_enabled'=>false,'google_client_id'=>'','google_secret'=>'','github_enabled'=>false,'github_client_id'=>'','github_secret'=>''];
