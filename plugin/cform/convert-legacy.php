<?php
/** CLI: php convert-legacy.php /path/to/setting/1.php [GB18030|UTF-8] > fields.json */
if (PHP_SAPI !== "cli") {
    http_response_code(404);
    exit();
}
require_once __DIR__ . "/Fields.php";
require_once __DIR__ . "/LegacyFields.php";
try {
    if (!isset($argv[1]) || !is_file($argv[1])) {
        throw new InvalidArgumentException(
            "用法：php convert-legacy.php 旧字段.php [GB18030|UTF-8]"
        );
    }
    $encoding = $argv[2] ?? "GB18030";
    if (!in_array($encoding, ["GB18030", "GBK", "UTF-8"], true)) {
        throw new InvalidArgumentException("不支持的编码");
    }
    $fields = new CustomFormLegacyFields()->parse(file_get_contents($argv[1]), $encoding);
    echo json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) .
        "\n";
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
