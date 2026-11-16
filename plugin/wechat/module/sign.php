<?php
/** 当前已绑定应用页面上的只读签名动作；禁止跨站调用和任意域名签名。 */
if (!isset($wechatOptions) || !is_array($wechatOptions)) { http_response_code(404); exit; }
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET' || ($_GET['_wechat_sdk'] ?? '') !== 'sign'
        || !plugin_wechat::inWechat()
        || (isset($_SERVER['HTTP_SEC_FETCH_SITE']) && !in_array($_SERVER['HTTP_SEC_FETCH_SITE'], ['same-origin','none'], true))) {
        throw new InvalidArgumentException('请求不符合网页签名条件。');
    }
    plugin_wechat::validatedURL(plugin_wechat::requestURL(), $wechatOptions);
    $url = $_GET['url'] ?? null;
    if (!is_string($url)) throw new InvalidArgumentException('缺少网页地址。');
    $url = plugin_wechat::validatedURL($url, $wechatOptions);
    // 只签当前来源，不让允许列表中的不同网站互签；不接收客户端ticket或AppID。
    if (parse_url($url, PHP_URL_HOST) !== parse_url(plugin_wechat::requestURL(), PHP_URL_HOST)) {
        throw new InvalidArgumentException('网页来源不匹配。');
    }
    require_once dirname(__DIR__).'/WeChatClient.php';
    $client = new WeChatClient($wechatOptions, CONFIG.'wechat_runtime');
    echo json_encode($client->signature($url) + ['jsApiList'=>plugin_wechat::capabilities($wechatOptions)], JSON_THROW_ON_ERROR);
} catch (InvalidArgumentException) {
    http_response_code(400);
    echo '{"error":"当前页面或账号配置不允许签名"}';
} catch (Throwable) {
    http_response_code(503);
    header('Retry-After: 30');
    // 微信错误描述、请求URL与凭证不进入响应或日志。
    echo '{"error":"微信签名暂不可用，请核对账号权限、出口IP白名单和服务器网络"}';
}
exit;
