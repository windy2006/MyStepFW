<?php
/**
 * 文件：plugin/social/info.php (new)
 * 功能说明：普通会员第三方登录插件元信息；读取时不连接平台或数据库。
 * 方法目录：无具名方法。
 * 使用说明：应用关联来自已校验的app标识，运行仍须安装、启用及应用绑定。
 */
return (static function (): array {
$settings=is_file(__DIR__.'/config.php')?include __DIR__.'/config.php':include __DIR__.'/config_default.php';
$app=is_string($settings['app']??null)&&preg_match('/\A[A-Za-z][A-Za-z0-9_]*\z/D',$settings['app'])?$settings['app']:'CMS';
    return ['name'=>'第三方登录','idx'=>'social','ver'=>'1.0.0','app'=>array_values(array_unique(['myStep',$app])),'intro'=>'普通会员微信、QQ、Google、GitHub登录及本站邮箱验证',
    'copyright'=>'版权所有 2019-2026 <a href="mailto:windy2006@gmail.com">Windy2000</a>',
    'description'=>'先安装以准备会员与身份存储，再配置平台应用及精确回调地址。首次须昵称＋邮箱并完成本站邮件验证；已绑定后可直接认证登录。不会授予管理员权限。'];
})();
