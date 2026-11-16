<?php
/** page钩子注入模板；仅接收class.php生成的公开JSON和管理员受信内容。 */
if (!isset($json, $html, $script)) return;
?>
<div id="mystep-wechat-content"><?php echo $html; ?></div>
<script id="mystep-wechat-config" type="application/json"><?php echo $json; ?></script>
<script>
(function () {
    'use strict';
    const customReady = function (wx, wechat) {
        <?php echo $script; ?>
    };
    <?php readfile(dirname(__DIR__).'/asset/wechat.js'); ?>
})();
</script>
