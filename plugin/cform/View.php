<?php
/** 视图准备；所有用户值按文本/属性转义，完整布局由框架myTemplate渲染。 */
final class CustomFormView
{
    public static function h(mixed $v): string
    {
        return htmlspecialchars(
            is_scalar($v) ? (string) $v : "",
            ENT_QUOTES | ENT_SUBSTITUTE,
            "UTF-8"
        );
    }
    public static function tpl(string $name, array $vars = []): myTemplate
    {
        $tpl = new myTemplate(
            [
                "name" => $name,
                "style" => "",
                "path" => __DIR__ . "/template",
                "path_compile" => CACHE . "template/custom_form"
            ],
            false
        );
        foreach ($vars as $k => $v) {
            $tpl->assign($k, $v);
        }
        return $tpl;
    }
    public static function render(string $name, array $vars = []): string
    {
        return self::tpl($name, $vars)->render("", false);
    }
    public static function page(string $body): myTemplate
    {
        return self::tpl("page", [
            "main" => $body,
            "page_start" => "",
            "page_end" => "",
            "title" => "Custom forms"
        ]);
    }
    public static function message(string $message): string
    {
        return self::render("message", ["message" => $message]);
    }
    public static function form(
        array $f,
        array $data,
        string $lng,
        bool $admin = false,
        string $request = "",
        int $id = 0,
        bool $submitted = false
    ): string {
        $prefix = $admin ? ($id ? "entry_update" : "entry_create") : "submission";
        if (!$submitted) {
            foreach ($f["fields"] as $key => $field) {
                if (($admin || !$field["manager"]) && !array_key_exists($key, $data)) {
                    $data[$key] = CustomFormFields::defaultValue($field, $lng);
                }
            }
        }
        $context = [
            "data" => $data,
            "id" => $id,
            "admin" => $admin,
            "language" => $lng,
            "submitted" => $submitted
        ];
        CustomFormEvents::run($f, $prefix . "_prepare", $context);
        if (!is_array($context["data"])) {
            throw new DomainException("展示数据必须为字段对象");
        }
        $data = $context["data"];
        $fields = "";
        $fieldVars = [];
        $fieldRows = [];
        foreach ($f["fields"] as $key => $field) {
            if ($field["manager"] && !$admin) {
                continue;
            }
            $value = array_key_exists($key, $data)
                ? $data[$key]
                : CustomFormFields::defaultValue($field, $lng);
            $title = $lng === "en" ? ($field["title_en"] ?: $field["title"]) : $field["title"];
            $comment =
                $lng === "en" ? ($field["comment_en"] ?: $field["comment"]) : $field["comment"];
            $control = "";
            $required = $field["required"] ? " required" : "";
            $max = ' maxlength="' . $field["length"] . '"';
            $name = self::h($key);
            $format = rtrim($field["format"], "_");
            $need = in_array($format, ["email", "url", "digital", "number", "date"], true)
                ? $format . ($field["required"] ? "" : "_")
                : ($field["required"]
                    ? ""
                    : "_");
            $validation = ' need="' . self::h($need) . '"';
            if ($field["type"] === "textarea") {
                $control =
                    '<textarea class="form-control" name="' .
                    $name .
                    '"' .
                    $max .
                    $required .
                    $validation .
                    ">" .
                    self::h($value) .
                    "</textarea>";
            } elseif ($field["type"] === "file") {
                $current = is_array($value) ? $value : null;
                $control =
                    '<input type="file" class="form-control-file" name="' .
                    $name .
                    '"' .
                    ($current ? "" : $required) .
                    ">";
                if ($current) {
                    $control .= "<small>" . self::h($current["name"]) . "</small>";
                }
            } elseif (in_array($field["type"], ["radio", "checkbox", "select"], true)) {
                $choices =
                    $lng === "en" && $field["value"]["en"]
                        ? $field["value"]["en"]
                        : $field["value"]["cn"];
                $selected = array_filter(is_array($value) ? $value : [$value], "is_string");
                if ($field["type"] === "select") {
                    $control =
                        '<select name="' .
                        $name .
                        '" class="form-control"' .
                        $required .
                        '><option value=""></option>';
                }
                foreach ($choices as $i => $label) {
                    $cn = $field["value"]["cn"][$i];
                    $is = in_array($submitted ? $label : $cn, $selected, true);
                    $v = self::h($label);
                    if ($field["type"] === "select") {
                        $control .=
                            '<option value="' .
                            $v .
                            '"' .
                            ($is ? " selected" : "") .
                            ">" .
                            $v .
                            "</option>";
                    } else {
                        $control .=
                            '<label class="mr-3"><input type="' .
                            $field["type"] .
                            '" name="' .
                            $name .
                            ($field["type"] === "checkbox" ? "[]" : "") .
                            '" value="' .
                            $v .
                            '"' .
                            ($is ? " checked" : "") .
                            ($field["type"] === "radio"
                                ? $required
                                : ($field["required"]
                                    ? ' need=""'
                                    : "")) .
                            "> " .
                            $v .
                            "</label> ";
                    }
                }
                if ($field["type"] === "select") {
                    $control .= "</select>";
                }
            } else {
                $type = match (rtrim($field["format"], "_")) {
                    "email" => "email",
                    "date" => "date",
                    "url" => "url",
                    default => "text"
                };
                $control =
                    '<input class="form-control" type="' .
                    $type .
                    '" name="' .
                    $name .
                    '" value="' .
                    self::h($value) .
                    '"' .
                    $max .
                    $required .
                    $validation .
                    ">";
            }
            $row = [
                "key" => $key,
                "title" => $title . ($field["required"] ? " *" : ""),
                "comment" => $comment,
                "control" => $control
            ];
            $html = self::render("field", $row);
            $fields .= $html;
            $fieldVars["field_" . $key] = $html;
            $fieldVars["control_" . $key] = $control;
            $fieldRows[] = $row;
        }
        $action = $admin
            ? plugin_cform::adminURL(["mid" => $f["id"]])
            : plugin_cform::url("submit", $f["id"], ["lng" => $lng]);
        $hidden =
            plugin_cform::tokenField() .
            '<input type="hidden" name="mid" value="' .
            $f["id"] .
            '">';
        if ($admin) {
            $hidden .=
                '<input type="hidden" name="action" value="save_entry"><input type="hidden" name="id" value="' .
                ($id ?: "") .
                '">';
        } else {
            $hidden .= '<input type="hidden" name="request_key" value="' . self::h($request) . '">';
        }
        $vars =
            [
                "title" => $lng === "en" ? ($f["name_en"] ?: $f["name"]) : $f["name"],
                "intro" => "",
                "action" => $action,
                "fields" => $fields,
                "hidden" => $hidden,
                "submit" => $lng === "en" ? "Submit" : "提交",
                "back_url" => $admin
                    ? plugin_cform::adminURL(["mid" => $f["id"]])
                    : plugin_cform::url("submit", $f["id"], ["lng" => $lng === "en" ? "cn" : "en"]),
                "back_label" => $admin ? "返回记录" : "中文 / English"
            ] + $fieldVars;
        return self::custom(
            $f,
            $admin ? "edit_data" : "cf_submit_" . $lng,
            $prefix . "_render",
            $vars,
            ["field" => $fieldRows],
            ["language" => $lng, "admin" => $admin, "data" => $data, "id" => $id]
        );
    }

    /** 每次渲染均使用当前表单覆盖，扩展可以修改展示变量。 */
    private static function custom(
        array $form,
        string $key,
        string $event,
        array $vars,
        array $loops = [],
        array $context = []
    ): string {
        $context += ["form" => $form, "vars" => $vars, "loops" => $loops];
        CustomFormEvents::run($form, $event, $context);
        if (!is_array($context["vars"]) || !is_array($context["loops"])) {
            throw new DomainException("展示vars和loops必须为对象／数组");
        }
        return CustomFormTemplates::render(
            $form,
            $key,
            $context["vars"],
            $context["loops"],
            $context
        );
    }

    public static function designer(array $f, bool $recovery = false): string
    {
        $id = $f["id"];
        $revision = $f["revision"];
        $prefix = $id ? "form_update" : "form_create";
        if (!$recovery) {
            $context = ["form" => $f];
            CustomFormEvents::run($f, $prefix . "_prepare", $context);
            if (!is_array($context["form"])) {
                throw new DomainException("form必须为表单定义对象");
            }
            $f =
                ["id" => $id, "revision" => $revision] +
                CustomFormFields::definition($context["form"]);
        }
        $json = json_encode(
            $f["fields"],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
        $script = ROOT_WEB . "plugin/cform/asset/designer.js";
        // 插件源码路径不是公开资源，使用内联本地脚本，避免要求框架扩展资源白名单。
        $script = file_get_contents(__DIR__ . "/asset/designer.js");
        $style = file_get_contents(__DIR__ . "/asset/designer.css");
        $vars = [
            "title" => "表单设计",
            "designer_title" => $recovery ? "修复功能扩展" : ($f["id"] ? "编辑表单" : "新建表单"),
            "recovery" => $recovery ? "1" : "0",
            "designer_help" => $recovery
                ? "修复模式不会执行扩展，仅更新当前表单的PHP扩展脚本。"
                : "先设置基本信息，再从列表添加或编辑表单项目。",
            "save_label" => $recovery ? "保存扩展" : "保存表单",
            "save_action" => $recovery ? "save_extension" : "save",
            "recovery_url" => $f["id"] && pluginFrameworkAdministrator()
                ? plugin_cform::adminURL(["mid" => $f["id"], "action" => "extension_recovery"])
                : "",

            "action" => plugin_cform::adminURL($f["id"] ? ["mid" => $f["id"]] : []),
            "mid" => $f["id"],
            "revision" => $f["revision"],
            "name" => $f["name"],
            "name_en" => $f["name_en"],
            "notes" => $f["notes"],
            "expire" => substr($f["expire"], 0, 10),
            "active" => $f["active"] ? "checked" : "",
            "public_list" => $f["public_list"] ? "checked" : "",
            "fields_json" => $json,
            "csrf" => plugin_cform::tokenField(),
            "back_url" => plugin_cform::adminURL(),
            "template_editor" => pluginFrameworkAdministrator() ? CustomFormTemplates::editor($f, $recovery) : "",
            "designer_script" => $script,
            "designer_style" => $style
        ];
        if (!$recovery) {
            $context = ["vars" => $vars];
            CustomFormEvents::run($f, $prefix . "_render", $context);
            $vars = $context["vars"];
        }
        return self::render("designer", $vars);
    }
    public static function forms(array $forms, string $message = ""): string
    {
        $rows = "";
        foreach ($forms as $f) {
            $id = $f["id"];
            $vars = [
                "id" => $id,
                "name" => $f["name"],
                "expire" => substr($f["expire"], 0, 10),
                "status" => $f["active"] ? "开放" : "停用",
                "design" => plugin_cform::adminURL(["mid" => $id, "action" => "design"]),
                "entries" => plugin_cform::adminURL(["mid" => $id]),
                "submit" => plugin_cform::url("submit", $id),
                "list" => plugin_cform::url("list", $id)
            ];
            CustomFormEvents::begin($f, "form_list");
            $context = ["vars" => $vars];
            CustomFormEvents::run($f, "form_list_item", $context);
            $rows .= self::render("form_row", $context["vars"]);
        }
        $global = ["id" => 0, "extension" => ""];
        CustomFormEvents::begin($global, "form_list");
        $context = [
            "vars" => [
                "rows" => $rows,
                "new_url" => plugin_cform::adminURL(["action" => "new"]),
                "message" => $message
            ]
        ];
        CustomFormEvents::run($global, "form_list_render", $context);
        return self::render("forms", $context["vars"]);
    }
    public static function entries(
        array $f,
        array $result,
        bool $admin,
        string $lng = "cn",
        bool $navigation = true,
        string $message = ""
    ): string {
        $columns = array_filter(
            $f["fields"],
            fn($field) => $admin ||
                ($field["public"] && !$field["manager"] && $field["type"] !== "file")
        );
        $headers = "<th>ID</th>";
        foreach ($columns as $field) {
            $headers .=
                "<th>" .
                self::h($lng === "en" ? ($field["title_en"] ?: $field["title"]) : $field["title"]) .
                "</th>";
        }
        if ($admin) {
            $headers .= "<th>确认邮件</th><th>提交时间</th><th>操作</th>";
        }
        $rows = "";
        $records = [];
        foreach ($result["rows"] as $r) {
            $context = [
                "data" => array_intersect_key($r["data"], $columns),
                "id" => (int) $r["id"],
                "admin" => $admin,
                "language" => $lng,
                "source" => $navigation ? "page" : "block"
            ];
            CustomFormEvents::run($f, $admin ? "entry_list_item" : "public_list_item", $context);
            if (!is_array($context["data"])) {
                throw new DomainException("列表记录数据无效");
            }
            $r["data"] = array_intersect_key($context["data"], $columns);
            $record = ["id" => (int) $r["id"], "created_at" => $r["created_at"]];
            $cells = "<td>" . (int) $r["id"] . "</td>";
            foreach ($columns as $key => $field) {
                $v = $r["data"][$key] ?? "";
                $record[$key] = CustomFormFields::display($field, $v, $lng);
                $text = self::h(CustomFormFields::display($field, $v, $lng));
                if ($field["type"] === "file" && is_array($v) && isset($v["token"])) {
                    $text =
                        '<a href="' .
                        self::h(
                            ($admin ? plugin_cform::recordURL("file", $f["id"], ["id" => $r["id"], "field" => $key]) : plugin_cform::url("file", $f["id"], ["id" => $r["id"], "field" => $key]))
                        ) .
                        '">' .
                        $text .
                        "</a>";
                }
                $cells .= "<td>" . $text . "</td>";
            }
            if ($admin) {
                $cells .=
                    "<td>" .
                    match ((int) $r["mailed"]) {
                        1 => "已发送",
                        2 => "发送中/待核对",
                        default => "未发送"
                    } .
                    "</td><td>" .
                    self::h($r["created_at"]) .
                    '</td><td><a href="' .
                    self::h(
                        plugin_cform::adminURL([
                            "mid" => $f["id"],
                            "action" => "entry",
                            "id" => $r["id"]
                        ])
                    ) .
                    '">编辑</a> <a target="_blank" href="' .
                    self::h(plugin_cform::recordURL("print", $f["id"], ["id" => $r["id"]])) .
                    '">打印</a>';
                if ((int) $r["mailed"] === 0) {
                    $cells .=
                        '<a href="' .
                        self::h(
                            plugin_cform::adminURL([
                                "mid" => $f["id"],
                                "action" => "confirm",
                                "id" => $r["id"]
                            ])
                        ) .
                        '">确认邮件</a>';
                }
                foreach (
                    ["delete_entry" => "删除"] +
                        ((int) $r["mailed"] === 2 ? ["reset_mail" => "重置发送状态"] : [])
                    as $act => $label
                ) {
                    $cells .=
                        '<form method="post" action="' .
                        self::h(plugin_cform::adminURL(["mid" => $f["id"]])) .
                        '" class="d-inline"' .
                        ($act === "reset_mail"
                            ? ' onsubmit="return confirm(\'请先核对邮件是否已投递，确认后重置发送状态？\')"'
                            : "") .
                        ">" .
                        plugin_cform::tokenField() .
                        '<input type="hidden" name="action" value="' .
                        $act .
                        '"><input type="hidden" name="id" value="' .
                        $r["id"] .
                        '"><button class="btn btn-sm btn-outline-secondary" type="submit">' .
                        $label .
                        "</button></form>";
                }
                $cells .= "</td>";
            }
            $record["cells"] = $cells;
            $records[] = $record;
            $rows .= "<tr>" . $cells . "</tr>";
        }
        $tools = $admin
            ? self::render("tools", [
                "mid" => $f["id"],
                "action" => plugin_cform::adminURL(["mid" => $f["id"]]),
                "design" => plugin_cform::adminURL(["mid" => $f["id"], "action" => "design"]),
                "add" => plugin_cform::adminURL(["mid" => $f["id"], "action" => "entry"]),
                "back" => plugin_cform::adminURL(),
                "csrf" => plugin_cform::tokenField()
            ])
            : "";
        $pagination = "";
        $page = $result["page"] ?? 1;
        $pages = (int) ceil($result["count"] / ($result["size"] ?? 20));
        if ($navigation) {
            foreach (
                array_unique([max(1, $page - 1), $page, min(max(1, $pages), $page + 1)])
                as $p
            ) {
                $query = ["page" => $p];
                if ($admin) {
                    $query["mid"] = $f["id"];
                    $query["keyword"] = is_string($_GET["keyword"] ?? null) ? $_GET["keyword"] : "";
                }
                $url = $admin
                    ? plugin_cform::adminURL($query)
                    : plugin_cform::url("list", $f["id"], $query + ["lng" => $lng]);
                $pagination .=
                    '<a class="btn btn-sm btn-light" href="' . self::h($url) . '">' . $p . "</a> ";
            }
        }
        $vars = [
            "title" => $lng === "en" ? ($f["name_en"] ?: $f["name"]) : $f["name"],
            "mid" => $f["id"],
            "headers" => $headers,
            "rows" => $rows,
            "count" => $result["count"],
            "count_label" => $lng === "en" ? "Total" : "记录数",
            "tools" => $tools,
            "pagination" => $pagination,
            "message" => $message
        ];
        $context = ["language" => $lng, "admin" => $admin];
        if ($admin) {
            return self::custom(
                $f,
                "list_data",
                "entry_list_render",
                $vars,
                ["record" => $records],
                $context
            );
        }
        $block = self::custom(
            $f,
            "block_cf_list_" . $lng,
            "public_block_render",
            $vars,
            ["record" => $records],
            $context
        );
        return $navigation
            ? self::custom(
                $f,
                "cf_list_" . $lng,
                "public_list_render",
                $vars + ["block" => $block],
                ["record" => $records],
                $context
            )
            : $block;
    }
    public static function receipt(array $f, array $r, string $lng): string
    {
        $columns = array_filter(
            $f["fields"],
            fn($field) => !$field["manager"] || plugin_cform::isAdmin()
        );
        $context = [
            "data" => array_intersect_key($r["data"], $columns),
            "id" => (int) $r["id"],
            "language" => $lng
        ];
        CustomFormEvents::run($f, "print_prepare", $context);
        if (!is_array($context["data"])) {
            throw new DomainException("打印数据无效");
        }
        $r["data"] = array_intersect_key($context["data"], $columns);
        $rows = "";
        foreach ($f["fields"] as $key => $field) {
            if ($field["manager"] && !plugin_cform::isAdmin()) {
                continue;
            }
            $title = $lng === "en" ? ($field["title_en"] ?: $field["title"]) : $field["title"];
            $rows .=
                "<tr><th>" .
                self::h($title) .
                "</th><td>" .
                nl2br(self::h(CustomFormFields::display($field, $r["data"][$key] ?? "", $lng))) .
                "</td></tr>";
        }
        $vars = [
            "title" => $lng === "en" ? ($f["name_en"] ?: $f["name"]) : $f["name"],
            "id" => $r["id"],
            "rows" => $rows,
            "created_at" => $r["created_at"]
        ];
        foreach ($f["fields"] as $key => $field) {
            if (!$field["manager"] || plugin_cform::isAdmin()) {
                $vars["record_" . $key] = CustomFormFields::display(
                    $field,
                    $r["data"][$key] ?? "",
                    $lng
                );
            }
        }
        return self::custom(
            $f,
            "cf_print_" . $lng,
            "print_render",
            $vars,
            [],
            ["language" => $lng]
        );
    }
}
