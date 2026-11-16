<?php
/** 表单级模板继承与受信扩展；源码随表单定义保存，运行产物仅在私有配置目录生成。 */
final class CustomFormTemplates
{
    public const LABELS = [
        "cf_submit_cn" => "中文前台填写页",
        "cf_submit_en" => "英文前台填写页",
        "cf_print_cn" => "中文打印页",
        "cf_print_en" => "英文打印页",
        "cf_list_cn" => "中文信息列表页",
        "cf_list_en" => "英文信息列表页",
        "block_cf_list_cn" => "中文列表子块",
        "block_cf_list_en" => "英文列表子块",
        "mail_cn" => "中文确认邮件",
        "mail_en" => "英文确认邮件",
        "edit_data" => "后台记录编辑页",
        "list_data" => "后台记录列表页"
    ];
    /** 旧独立说明／正文转成模板覆盖；不执行旧文本，也不修改数据库。 */
    public static function upgradeLegacy(array $form): array
    {
        $templates = $form["templates"] ?? [];
        if (!is_array($templates)) {
            throw new InvalidArgumentException("模板名称无效");
        }

        foreach (["cn", "en"] as $language) {
            $intro = CustomFormFields::text($form["intro_" . $language] ?? "", 4000);
            $key = "cf_submit_" . $language;
            if ($intro !== "") {
                $source =
                    ($templates[$key] ?? "") !== ""
                        ? $templates[$key]
                        : file_get_contents(__DIR__ . "/template/defaults/" . $key . ".tpl");
                $escaped = htmlspecialchars($intro, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
                $converted = str_replace(["<!--intro|html-->", "<!--intro-->"], $escaped, $source);
                $converted = str_replace(
                    "<!-- 可在此添加活动介绍和填写说明。 -->",
                    '<p style="white-space:pre-wrap">' . $escaped . "</p>",
                    $converted
                );
                if ($converted !== $source) {
                    $templates[$key] = $converted;
                }
            }

            $bodyField = $language === "en" ? "mail_body_en" : "mail_body";
            $body = CustomFormFields::text($form[$bodyField] ?? "", 8000);
            $key = "mail_" . $language;
            if ($body !== "" && ($templates[$key] ?? "") === "") {
                $tokens = [
                    "{{id}}" => "<!--id|html-->",
                    "{{form_name}}" => "<!--title|html-->"
                ];
                foreach ($form["fields"] ?? [] as $field => $definition) {
                    if (
                        !CustomFormFields::flag($definition["manager"] ?? false) &&
                        !array_key_exists("{{" . $field . "}}", $tokens)
                    ) {
                        $tokens["{{" . $field . "}}"] = "<!--record_" . $field . "|html-->";
                    }
                }
                $escaped = htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
                $templates[$key] =
                    '<div style="white-space:pre-wrap">' . strtr($escaped, $tokens) . "</div>\n";
            }
        }

        $form["templates"] = $templates;
        unset(
            $form["intro_cn"],
            $form["intro_en"],
            $form["mail_body"],
            $form["mail_body_en"],
            $form["mail_subject"],
            $form["mail_subject_en"]
        );
        return $form;
    }

    /** 默认模板集中在插件template/defaults，空覆盖实时继承默认。 */
    public static function source(array $form, string $key): string
    {
        if (!isset(self::LABELS[$key])) {
            throw new InvalidArgumentException("未知模板");
        }
        $form = self::upgradeLegacy($form);
        return ($form["templates"][$key] ?? "") !== ""
            ? $form["templates"][$key]
            : file_get_contents(__DIR__ . "/template/defaults/" . $key . ".tpl");
    }

    /** 浏览器表单会把换行转换成CRLF；不因此误存默认模板覆盖。 */
    public static function inherits(string $source, string $default): bool
    {
        return trim($source) === "" ||
            str_replace(["\r\n", "\r"], "\n", $source) ===
                str_replace(["\r\n", "\r"], "\n", $default);
    }

    /** 只校验语法，不在保存时运行管理员源码。 */
    public static function validate(array $input): array
    {
        $out = [];
        $templates = $input["templates"] ?? [];
        if (!is_array($templates) || array_diff(array_keys($templates), array_keys(self::LABELS))) {
            throw new InvalidArgumentException("模板名称无效");
        }
        foreach ($templates as $key => $value) {
            $value = CustomFormFields::text($value, 100000);
            if (self::inherits($value, self::source([], $key))) {
                continue;
            }
            self::syntax($value, self::LABELS[$key]);
            $out[$key] = $value;
        }
        $code = CustomFormFields::text($input["extension"] ?? "", 100000);
        if (self::inherits($code, file_get_contents(__DIR__ . "/extension-default.txt"))) {
            $code = "";
        }
        if ($code !== "") {
            if (str_contains($code, "<?") || str_contains($code, "?>")) {
                throw new InvalidArgumentException("扩展填写return闭包，不带PHP开始或结束标签");
            }
            self::syntax("<?php " . $code, "功能扩展");
            if (
                !preg_match(
                    '/\A\s*(?:(?:\/\*.*?\*\/|\/\/[^\n]*|\#[^\n]*)\s*)*return\s+(?:static\s+)?function\s*\(/s',
                    $code
                )
            ) {
                throw new InvalidArgumentException(
                    '功能扩展须返回闭包：return function(string $event, array &$context): void { … };'
                );
            }
        }
        return ["templates" => $out, "extension" => $code];
    }
    private static function syntax(string $source, string $label): void
    {
        try {
            token_get_all($source, TOKEN_PARSE);
        } catch (ParseError $e) {
            throw new InvalidArgumentException($label . " PHP语法错误：" . $e->getMessage());
        }
    }

    /** 内容寻址+原子替换，禁止把用户提供的文件名拼入路径。 */
    private static function materialize(string $source, string $ext): string
    {
        $dir = CONFIG . "custom_form_runtime";
        clearstatcache(true, $dir);
        if (is_link($dir)) {
            throw new RuntimeException("模板运行目录不可为符号链接");
        }
        if (!is_dir($dir)) {
            // 其他请求可能在检查后建好目录；以重新读取的实际状态为准。
            @mkdir($dir, 0700, true);
            clearstatcache(true, $dir);
            if (is_link($dir)) {
                throw new RuntimeException("模板运行目录不可为符号链接");
            }
            if (!is_dir($dir)) {
                throw new RuntimeException("无法建立模板运行目录");
            }
        }
        $path = $dir . "/" . hash("sha256", $source) . "." . $ext;
        if (is_link($path)) {
            throw new RuntimeException("模板文件不可为符号链接");
        }
        if (!is_file($path)) {
            $tmp = tempnam($dir, "cf_");
            try {
                if (file_put_contents($tmp, $source, LOCK_EX) !== strlen($source)) {
                    throw new RuntimeException("模板写入失败");
                }
                chmod($tmp, 0600);
                if (!rename($tmp, $path)) {
                    throw new RuntimeException("模板保存失败");
                }
            } finally {
                if (is_file($tmp)) {
                    unlink($tmp);
                }
            }
        }
        return $path;
    }

    /** PHP模板通过$cf读取本次上下文；普通值用|html，已生成控件和子块可直接插入。 */
    public static function render(
        array $form,
        string $key,
        array $vars = [],
        array $loops = [],
        array $context = []
    ): string {
        $source = self::source($form, $key);
        $path = self::materialize($source, "tpl");
        $tpl = new myTemplate(
            [
                "name" => basename($path, ".tpl"),
                "path" => dirname($path),
                "style" => "",
                "path_compile" => CACHE . "template/custom_form_pages"
            ],
            false,
            true
        );
        foreach ($vars as $name => $value) {
            $tpl->assign($name, $value);
        }
        foreach ($loops as $name => $rows) {
            $tpl->setLoop($name, $rows, true);
        }
        $had = array_key_exists("cf", $GLOBALS);
        $before = $GLOBALS["cf"] ?? null;
        $GLOBALS["cf"] = $context + ["form" => $form, "vars" => $vars];
        try {
            return $tpl->render('$cf', false);
        } catch (Throwable $e) {
            throw new DomainException(
                "模板“" . self::LABELS[$key] . "”运行失败：" . $e->getMessage(),
                0,
                $e
            );
        } finally {
            if ($had) {
                $GLOBALS["cf"] = $before;
            } else {
                unset($GLOBALS["cf"]);
            }
        }
    }

    /** 按表单隔离的PHP闭包，事件和数据显式传递，不声明旧版全局ext_func。 */
    public static function hook(array $form, string $event, array &$context): void
    {
        $code =
            ($form["extension"] ?? "") !== ""
                ? $form["extension"]
                : file_get_contents(__DIR__ . "/extension-default.txt");
        $path = self::materialize("<?php " . $code, "php");
        try {
            $callback = (static fn($file) => include $file)($path);
            if (!($callback instanceof Closure)) {
                throw new DomainException("扩展必须返回闭包");
            }
            $callback($event, $context);
        } catch (Throwable $e) {
            throw new DomainException(
                "表单功能扩展（" . $event . "）失败：" . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /** 编辑器在列表与字段编辑器之外使用独立区域；每次只展开一种源码。 */
    public static function editor(array $form, bool $extensionOnly = false): string
    {
        $form = self::upgradeLegacy($form);
        $options = "";
        $panels = "";
        $labels = $extensionOnly
            ? ["extension" => "功能扩展（PHP）"]
            : self::LABELS + ["extension" => "功能扩展（PHP）"];
        $first = array_key_first($labels);
        foreach ($labels as $key => $label) {
            $custom =
                $key === "extension" ? $form["extension"] ?? "" : $form["templates"][$key] ?? "";
            $default =
                $key === "extension"
                    ? file_get_contents(__DIR__ . "/extension-default.txt")
                    : self::source([], $key);
            $vars = [
                "key" => $key,
                "label" => $label,
                "input_name" => $key === "extension" ? "extension" : "templates[" . $key . "]",
                "source" => $custom !== "" ? $custom : $default,
                "default_source" => $default,
                "status" => $custom === "" ? "继承默认" : "单表单覆盖",
                "hidden" => $key === $first ? "" : "hidden",
                "event_note" =>
                    $key === "extension" && CustomFormEvents::legacy($form)
                        ? "当前已保存扩展使用旧事件兼容模式；新脚本请采用默认示例中的阶段名称。"
                        : ""
            ];
            $options .= '<option value="' . $key . '">' . CustomFormView::h($label) . "</option>";
            $panels .= CustomFormView::render("template_editor_panel", $vars);
        }
        return CustomFormView::render("template_editor", [
            "options" => $options,
            "panels" => $panels,
            "editor_base" => ROOT_WEB . "vendor/codemirror/"
        ]);
    }
}
