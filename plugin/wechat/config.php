<?php
/** 微信网页插件默认值；后台保存的config.php为站点私有配置。 */
return [
    'enabled' => false,
    'account_type' => 'subscription',
    'verified' => false,
    'appid' => '',
    'secret' => '',
    'origins' => '',
    'exclude_paths' => "/Admin/\n/admin/\n/myStep/",
    'apis' => 'checkJsApi,chooseImage,previewImage,scanQRCode,getLocation,openLocation',
    'card_enabled' => false,
    'pay_enabled' => false,
    'share_title' => '',
    'share_desc' => '',
    'share_image' => '',
    'toolbar' => false,
    'html' => '',
    'ready_script' => '',
];
