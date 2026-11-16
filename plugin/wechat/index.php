<?php
/** 宿主已按安装、启用与应用绑定筛选后执行；不登记全站公开签名路由。 */
require_once __DIR__.'/class.php';
try {
    $wechatOptions = plugin_wechat::options();
} catch (InvalidArgumentException) {
    return;
}
if (!$wechatOptions['enabled']) return;
// 框架后台及宿主声明的后台入口不提供注入和签名动作。
if (in_array($GLOBALS['info_app']['app'] ?? '', ['Admin','myStep'], true)) return;
$wechatPaths = $GLOBALS['info_app']['path'] ?? [];
foreach ([$this->setting->web->path_admin ?? '', $this->setting->gen->path_admin ?? ''] as $wechatAdminPath) {
    if ($wechatAdminPath !== '' && in_array(trim($wechatAdminPath, '/'), $wechatPaths, true)) return;
}
$this->setFunction('start', static function () use ($wechatOptions) {
    // 禁止CDN/浏览器共享含微信专属内容的响应，防止UA之间交叉缓存。
    header('Vary: User-Agent', false);
    header('Cache-Control: private, no-store');
    if (isset($_GET['_wechat_sdk'])) include __DIR__.'/module/sign.php';
});
$this->setFunction('page', static fn($page) => plugin_wechat::page($page, $wechatOptions));
