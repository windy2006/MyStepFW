<?php
/** 管理器已经完成身份检查；失败事件只在已授权的操作上下文内触发。 */
require_once dirname(__DIR__) . "/class.php";
try {
    $content = plugin_cform::manage();
} catch (Throwable $error) {
    CustomFormEvents::failed($error);
    if ($error instanceof RuntimeException && $error->getCode() === 409) {
        http_response_code(409);
    } elseif (
        $error instanceof InvalidArgumentException ||
        $error instanceof DomainException ||
        $error instanceof OutOfBoundsException ||
        $error instanceof JsonException
    ) {
        if (http_response_code() < 400) {
            http_response_code(400);
        }
    } else {
        throw $error;
    }
    $content = CustomFormView::message($error->getMessage());
    $mid = CustomFormEvents::currentFormId();
    if (
        !$mid &&
        isset($_REQUEST["mid"]) &&
        is_scalar($_REQUEST["mid"]) &&
        preg_match("/\A[1-9][0-9]{0,9}\z/D", (string) $_REQUEST["mid"])
    ) {
        $mid = (int) $_REQUEST["mid"];
    }
    $links = '<p><a href="' . CustomFormView::h(plugin_cform::adminURL()) . '">返回表单管理</a>';
    if ($mid) {
        $links .=
            ' · <a href="' .
            CustomFormView::h(
                plugin_cform::adminURL(["mid" => $mid, "action" => "extension_recovery"])
            ) .
            '">跳过扩展并修复源码</a>';
    }
    $content .= $links . "</p>";
}
