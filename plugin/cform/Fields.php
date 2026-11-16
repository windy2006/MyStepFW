<?php
/** 旧para格式的声明式字段：只读数据，不执行PHP、SQL、eval或模板中的脚本。 */
require_once __DIR__ . "/Templates.php";
final class CustomFormFields
{
    public const TYPES = ["text", "textarea", "radio", "select", "checkbox", "file"];
    public static function text(mixed $v, int $max = 1000): string
    {
        if (
            !is_string($v) ||
            !mb_check_encoding($v, "UTF-8") ||
            mb_strlen($v) > $max ||
            str_contains($v, "\0")
        ) {
            throw new InvalidArgumentException("文字格式或长度无效");
        }
        return $v;
    }
    public static function flag(mixed $v): bool
    {
        return in_array($v, [true, 1, "1", "true", "on"], true);
    }
    public static function definition(array $input): array
    {
        $input = CustomFormTemplates::upgradeLegacy($input);
        $out = CustomFormTemplates::validate($input);
        foreach (
            [
                "name" => 100,
                "name_en" => 100,
                "notes" => 2000,
                "expire" => 32
            ]
            as $k => $max
        ) {
            $out[$k] = self::text($input[$k] ?? "", $max);
        }
        if (trim($out["name"]) === "") {
            throw new InvalidArgumentException("请填写表单名称");
        }
        self::deadline($out["expire"]);
        // 兼容读取旧日期时间，但新保存的定义只保留日期。
        $out["expire"] = $out["expire"] === "" ? "" : substr($out["expire"], 0, 10);
        $out["public_list"] = self::flag($input["public_list"] ?? false);
        $out["active"] = self::flag($input["active"] ?? true);
        $fields = $input["fields"] ?? [];
        if (!is_array($fields) || count($fields) < 1 || count($fields) > 100) {
            throw new InvalidArgumentException("字段数应为1至100");
        }
        $out["fields"] = [];
        foreach ($fields as $key => $field) {
            if (
                !is_string($key) ||
                !preg_match("/\A[a-z][a-z0-9_]{0,39}\z/D", $key) ||
                in_array(
                    $key,
                    [
                        "id",
                        "mid",
                        "mailed",
                        "add_date",
                        "append",
                        "print",
                        "_ms_csrf",
                        "action",
                        "request_key",
                        "csv",
                        "revision",
                        "fields_json"
                    ],
                    true
                ) ||
                !is_array($field)
            ) {
                throw new InvalidArgumentException(
                    "字段索引须为1至40位，以小写字母开头，只可含小写字母、数字和下划线，且不能为系统保留名称"
                );
            }
            $f = [];
            $f["type"] = $field["type"] ?? "text";
            if (!in_array($f["type"], self::TYPES, true)) {
                throw new InvalidArgumentException("不支持的字段类型");
            }
            foreach (
                ["title", "title_en", "comment", "comment_en", "default", "default_en", "format"]
                as $k
            ) {
                $f[$k] = self::text($field[$k] ?? "", $k === "format" ? 100 : 1000);
            }
            if ($f["title"] === "" && $f["title_en"] === "") {
                throw new InvalidArgumentException("字段必须有标题");
            }
            $length = $field["length"] ?? "";
            if ($length === "") {
                $length = $f["type"] === "textarea" ? 4000 : 255;
            }
            if (
                (!is_int($length) && !(is_string($length) && ctype_digit($length))) ||
                (int) $length < 1 ||
                (int) $length > 20000
            ) {
                throw new InvalidArgumentException("字段长度应为1至20000的整数");
            }
            $f["length"] = (int) $length;
            $f["required"] = self::flag(
                $field["required"] ??
                    $f["format"] !== "" && !str_ends_with($f["format"], "_") && $f["format"] !== "."
            );
            foreach (["manager", "search", "public"] as $k) {
                $f[$k] = self::flag($field[$k] ?? false);
            }
            $format = rtrim($f["format"], "_");
            if (
                !in_array(
                    $format,
                    ["", ".", "email", "digital", "number", "date", "mobile", "url"],
                    true
                )
            ) {
                throw new InvalidArgumentException("不支持的字段格式：" . $format);
            }
            $f["value"] = ["cn" => [], "en" => []];
            if (in_array($f["type"], ["select", "radio", "checkbox"], true)) {
                foreach (["cn", "en"] as $lng) {
                    $values = $field["value"][$lng] ?? [];
                    if (!is_array($values) || count($values) > 100) {
                        throw new InvalidArgumentException("选项无效");
                    }
                    foreach (array_values($values) as $v) {
                        $f["value"][$lng][] = self::text($v, 255);
                    }
                }
                if (
                    !$f["value"]["cn"] ||
                    count(array_unique($f["value"]["cn"])) !== count($f["value"]["cn"]) ||
                    array_filter($f["value"]["cn"], fn($v) => trim($v) === "")
                ) {
                    throw new InvalidArgumentException("中文选项不能为空或重复");
                }
                if (
                    $f["value"]["en"] &&
                    (count($f["value"]["en"]) !== count($f["value"]["cn"]) ||
                        count(array_unique($f["value"]["en"])) !== count($f["value"]["en"]) ||
                        array_filter($f["value"]["en"], fn($v) => trim($v) === ""))
                ) {
                    throw new InvalidArgumentException("英文选项须唯一、非空并与中文一一对应");
                }
                foreach (["cn", "en"] as $lng) {
                    self::defaultValue($f, $lng);
                }
            }
            $out["fields"][$key] = $f;
        }
        return $out;
    }

    /** 验证白名单字段，英文选项按位置转换为稳定中文值，不做数值数组求和。 */
    public static function values(
        array $form,
        array $input,
        string $lng = "cn",
        bool $admin = false,
        array $previous = []
    ): array {
        $result = [];
        foreach ($form["fields"] as $key => $f) {
            if ($f["manager"] && !$admin) {
                if (array_key_exists($key, $previous)) {
                    $result[$key] = $previous[$key];
                }
                continue;
            }
            if ($f["type"] === "file") {
                $value = $previous[$key] ?? null;
                if ($f["required"] && !$value) {
                    throw new InvalidArgumentException($f["title"] . "：请上传附件");
                }
                $result[$key] = $value;
                continue;
            }
            $v = $input[$key] ?? ($f["type"] === "checkbox" ? [] : "");
            if ($f["type"] === "checkbox") {
                if (!is_array($v) || count($v) > 100) {
                    throw new InvalidArgumentException("多选字段无效");
                }
                $values = [];
                foreach ($v as $item) {
                    $values[] = self::option($f, self::text($item, 255), $lng);
                }
                $v = array_values(array_unique($values));
            } else {
                $v = self::text($v, $f["length"]);
                if ($v !== "" && in_array($f["type"], ["select", "radio"], true)) {
                    $v = self::option($f, $v, $lng);
                }
            }
            if ($f["required"] && ((is_string($v) && trim($v) === "") || $v === [])) {
                throw new InvalidArgumentException($f["title"] . "：必填");
            }
            if (is_string($v) && $v !== "") {
                $format = rtrim($f["format"], "_");
                $valid = match ($format) {
                    "email" => (bool) filter_var($v, FILTER_VALIDATE_EMAIL),
                    "digital" => preg_match("/\A\d+\z/D", $v) === 1,
                    "number" => is_numeric($v) && is_finite((float) $v),
                    "date" => self::dateValue($v),
                    "mobile" => preg_match("/\A[+\d ()-]{5,30}\z/D", $v) === 1,
                    "url" => filter_var($v, FILTER_VALIDATE_URL) &&
                        in_array(
                            strtolower((string) parse_url($v, PHP_URL_SCHEME)),
                            ["http", "https"],
                            true
                        ),
                    default => true
                };
                if (!$valid) {
                    throw new InvalidArgumentException($f["title"] . "：格式无效");
                }
            }
            $result[$key] = $v;
        }
        return $result;
    }

    /**
     * 功能说明：严格解析截止日期；日期型截止到当天末尾，旧日期时间保持精确含义。
     * @param string $value 空字符串或YYYY-MM-DD[ HH:MM[:SS]]，也接受T分隔
     * @return int|null 日期取23:59:59；旧日期时间保留原时刻；空值表示不限
     */
    public static function deadline(string $value): ?int
    {
        if ($value === "") {
            return null;
        }
        if (!preg_match("/\A\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}(?::\d{2})?)?\z/D", $value)) {
            throw new InvalidArgumentException("截止日期格式无效");
        }
        $canonical = str_replace("T", " ", $value);
        $format = match (strlen($canonical)) {
            10 => "Y-m-d",
            16 => "Y-m-d H:i",
            default => "Y-m-d H:i:s"
        };
        $date = DateTimeImmutable::createFromFormat("!" . $format, $canonical);
        if (!$date || $date->format($format) !== $canonical) {
            throw new InvalidArgumentException("截止日期无效");
        }
        if ($format === "Y-m-d") {
            $date = $date->setTime(23, 59, 59);
        }
        return $date->getTimestamp();
    }

    /**
     * 功能说明：将语言默认选项映射为稳定中文值；记录回显只比较中文值。
     * @param array $field 已规范化字段
     * @param string $lng cn或en
     * @return string|array 默认文本或多选值数组
     */
    public static function defaultValue(array $field, string $lng = "cn"): string|array
    {
        $value =
            $lng === "en"
                ? ($field["default_en"] !== ""
                    ? $field["default_en"]
                    : $field["default"])
                : $field["default"];
        if (!in_array($field["type"], ["select", "radio", "checkbox"], true)) {
            return $value;
        }
        if ($value === "") {
            return $field["type"] === "checkbox" ? [] : "";
        }
        $language = $lng === "en" && $field["default_en"] !== "" ? "en" : "cn";
        if ($field["type"] !== "checkbox") {
            return self::option($field, $value, $language);
        }
        $choices =
            $language === "en" && $field["value"]["en"]
                ? $field["value"]["en"]
                : $field["value"]["cn"];
        $values = in_array($value, $choices, true)
            ? [$value]
            : array_map("trim", explode(",", $value));
        return array_values(
            array_unique(array_map(fn($v) => self::option($field, $v, $language), $values))
        );
    }
    private static function dateValue(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat("!Y-m-d", $value);
        return $date && $date->format("Y-m-d") === $value;
    }
    private static function option(array $f, string $v, string $lng): string
    {
        $options = $lng === "en" && $f["value"]["en"] ? $f["value"]["en"] : $f["value"]["cn"];
        $i = array_search($v, $options, true);
        if ($i === false) {
            throw new InvalidArgumentException("选择了未定义的选项");
        }
        return $f["value"]["cn"][$i];
    }
    public static function display(array $f, mixed $v, string $lng = "cn"): string
    {
        if (is_array($v) && isset($v["token"])) {
            return (string) $v["name"];
        }
        if ($v === null) {
            return "";
        }
        $values = is_array($v) ? $v : [$v];
        foreach ($values as &$item) {
            $i = array_search($item, $f["value"]["cn"], true);
            if ($lng === "en" && $i !== false) {
                $item = $f["value"]["en"][$i] ?? $item;
            }
        }
        return implode(", ", $values);
    }
}
