<?php
/** 表单操作服务：扩展夹在授权、规范化、校验、提交和响应的明确边界之间。 */
trait CustomFormOperations
{
    private static function checkedQuery(array $form, array $query, bool $public): array
    {
        $allowed = array_keys(
            array_filter(
                $form["fields"],
                static fn($field) => $public
                    ? $field["public"] && !$field["manager"] && $field["type"] !== "file"
                    : $field["search"]
            )
        );
        foreach (
            ["page" => [1, PHP_INT_MAX], "size" => [1, $public ? 100 : 1000]]
            as $key => [$min, $max]
        ) {
            $value = $query[$key] ?? ($key === "page" ? 1 : 20);
            if (
                filter_var($value, FILTER_VALIDATE_INT) === false ||
                $value < $min ||
                $value > $max
            ) {
                throw new InvalidArgumentException("分页参数无效：" . $key);
            }
            $query[$key] = (int) $value;
        }
        $query["keyword"] = CustomFormFields::text($query["keyword"] ?? "", 200);
        $fields = $query["search_fields"] ?? $allowed;
        if (
            !is_array($fields) ||
            array_filter($fields, static fn($field) => !is_string($field)) ||
            array_diff($fields, $allowed)
        ) {
            throw new InvalidArgumentException("检索字段超出允许范围");
        }
        $query["search_fields"] = array_values($fields);
        return array_intersect_key(
            $query,
            array_flip(["page", "size", "keyword", "search_fields"])
        );
    }

    public static function queryEntries(
        array $form,
        int $page,
        string $keyword,
        int $size,
        bool $admin,
        string $language,
        string $source = "page"
    ): array {
        CustomFormEvents::begin($form, $admin ? "entry_list" : "public_list");
        $context = [
            "query" => ["page" => $page, "keyword" => $keyword, "size" => $size],
            "admin" => $admin,
            "language" => $language,
            "source" => $source
        ];
        CustomFormEvents::run($form, $admin ? "entry_list_query" : "public_list_query", $context);
        if (!is_array($context["query"])) {
            throw new DomainException("query必须为查询参数对象");
        }
        $query = self::checkedQuery($form, $context["query"], !$admin);
        return self::store()->entries(
            $form["id"],
            $query["page"],
            $query["keyword"],
            $query["size"],
            $query["search_fields"]
        );
    }

    /** input阶段可修整原始值；validate阶段可处理标准中文值，之后再次严格校验。 */
    private static function entryData(
        array $form,
        array $input,
        string $language,
        bool $admin,
        array $files,
        string $prefix,
        int $id = 0,
        array $previous = [],
        array $extra = []
    ): array {
        $allowed = array_filter($form["fields"], static fn($field) => $admin || !$field["manager"]);
        $context =
            [
                "input" => array_intersect_key($input, $allowed),
                "id" => $id,
                "admin" => $admin,
                "language" => $language,
                "previous" => $previous
            ] + $extra;
        CustomFormEvents::run($form, $prefix . "_input", $context);
        if (!is_array($context["input"])) {
            throw new InvalidArgumentException("扩展输入必须为字段对象");
        }
        $data = CustomFormFields::values($form, $context["input"], $language, $admin, $files);
        $context["data"] = $data;
        CustomFormEvents::run($form, $prefix . "_validate", $context);
        if (!is_array($context["data"])) {
            throw new InvalidArgumentException("扩展数据必须为字段对象");
        }
        return CustomFormFields::values($form, $context["data"], "cn", $admin, $files);
    }

    private static function saveDefinition(array $input, int $mid): array
    {
        $previous = $mid ? self::store()->form($mid) : null;
        if (!pluginFrameworkAdministrator()) {
            // 源码字段即使手工POST也必须与原值一致；普通字段保存不清空已有覆盖。
            $expected = ["templates" => $previous["templates"] ?? [], "extension" => $previous["extension"] ?? ""];
            foreach ($expected as $key => $value) {
                if (array_key_exists($key, $input) && $input[$key] !== $value) {
                    self::sourceAdmin();
                }
                $input[$key] = $value;
            }
        }
        CustomFormEvents::begin(
            $previous ?? self::defaultForm(),
            $mid ? "form_update" : "form_create"
        );
        $revision = (int) ($input["revision"] ?? 0);
        if ($previous && $revision !== $previous["revision"]) {
            throw new RuntimeException("表单已被其他请求修改，请重新打开", 409);
        }
        $input["active"] = isset($input["active"]);
        $input["fields"] = json_decode(
            CustomFormFields::text($input["fields_json"] ?? "", 200000),
            true,
            32,
            JSON_THROW_ON_ERROR
        );
        foreach (["templates", "extension"] as $key) {
            if (!array_key_exists($key, $input) && $previous) {
                $input[$key] = $previous[$key] ?? ($key === "templates" ? [] : "");
            }
        }
        $candidate = ["id" => $mid, "revision" => $revision] + CustomFormFields::definition($input);
        CustomFormEvents::begin($candidate, $mid ? "form_update" : "form_create");
        $context = ["form" => $candidate, "previous" => $previous];
        CustomFormEvents::run(
            $candidate,
            $mid ? "form_update_validate" : "form_create_validate",
            $context
        );
        if (!is_array($context["form"])) {
            throw new DomainException("form必须为表单定义对象");
        }
        $definition = CustomFormFields::definition($context["form"]);
        $saved = self::store()->transaction(function () use (
            $definition,
            $mid,
            $revision,
            $previous
        ) {
            $id = self::store()->save($definition, $mid, $revision);
            $written = self::store()->form($id);
            $context = ["form" => $written, "id" => $id, "previous" => $previous];
            CustomFormEvents::run(
                $written,
                $mid ? "form_update_persisted" : "form_create_persisted",
                $context
            );
            return self::store()->form($id);
        });
        $id = $saved["id"];
        CustomFormEvents::notify($saved, $mid ? "form_updated" : "form_created", [
            "form" => $saved,
            "previous" => $previous,
            "id" => $id
        ]);
        return $saved;
    }

    private static function saveRecord(array $form, array $input): void
    {
        $id = isset($input["id"]) && $input["id"] !== "" ? self::id($input["id"]) : 0;
        $entry = $id ? self::store()->entry($form["id"], $id) : ["data" => []];
        $prefix = $id ? "entry_update" : "entry_create";
        CustomFormEvents::begin($form, $prefix, ["id" => $id]);
        $created = [];
        try {
            $files = self::uploads($form, $entry["data"], $created, true);
            $data = self::entryData(
                $form,
                $input,
                "cn",
                true,
                $files,
                $prefix,
                $id,
                $entry["data"]
            );
            $id = self::store()->transaction(function () use ($id, $form, $data, $entry, $prefix) {
                if ($id) {
                    self::store()->editEntry($form["id"], $id, $data);
                } else {
                    $id = self::store()->addEntry(
                        $form["id"],
                        $data,
                        self::owner(),
                        bin2hex(random_bytes(32))
                    );
                }
                $context = ["id" => $id, "data" => $data, "previous" => $entry["data"]];
                CustomFormEvents::run($form, $prefix . "_persisted", $context);
                return $id;
            });
        } catch (Throwable $error) {
            foreach ($created as $path) {
                unlink($path);
            }
            throw $error;
        }
        CustomFormEvents::notify(
            $form,
            $prefix === "entry_create" ? "entry_created" : "entry_updated",
            [
                "id" => $id,
                "data" => $data,
                "previous" => $entry["data"]
            ]
        );
    }

    private static function deleteRecord(array $form, int $id): void
    {
        $entry = self::store()->entry($form["id"], $id);
        CustomFormEvents::begin($form, "entry_delete", ["id" => $id]);
        $context = ["id" => $id, "entry" => $entry];
        CustomFormEvents::run($form, "entry_delete_validate", $context);
        self::store()->transaction(function () use ($form, $id, $entry) {
            if (!self::store()->deleteEntry($form["id"], $id)) {
                throw new RuntimeException("记录已被其他请求删除", 409);
            }
            $context = ["id" => $id, "entry" => $entry];
            CustomFormEvents::run($form, "entry_delete_persisted", $context);
        });
        CustomFormEvents::notify($form, "entry_deleted", ["id" => $id, "entry" => $entry]);
    }

    private static function removeDefinition(array $form): void
    {
        CustomFormEvents::begin($form, "form_delete");
        $context = ["form" => $form];
        CustomFormEvents::run($form, "form_delete_validate", $context);
        self::store()->transaction(function () use ($form) {
            if (!self::store()->removeForm($form["id"])) {
                throw new RuntimeException("表单已被其他请求停用", 409);
            }
            $context = ["form" => $form];
            CustomFormEvents::run($form, "form_delete_persisted", $context);
        });
        CustomFormEvents::notify($form, "form_deleted", ["form" => $form]);
    }

    private static function resetMail(array $form, int $id): void
    {
        $entry = self::store()->entry($form["id"], $id);
        CustomFormEvents::begin($form, "mail_reset", ["id" => $id]);
        if ((int) $entry["mailed"] !== 2) {
            throw new DomainException("只能重置发送中／待核对的记录");
        }
        $context = ["id" => $id, "entry" => $entry];
        CustomFormEvents::run($form, "mail_reset_validate", $context);
        self::store()->transaction(function () use ($form, $id, $entry) {
            if (!self::store()->releaseMail($form["id"], $id)) {
                throw new RuntimeException("邮件状态已被其他请求修改", 409);
            }
            $context = [
                "id" => $id,
                "entry" => array_replace($entry, ["mailed" => 0]),
                "previous" => $entry
            ];
            CustomFormEvents::run($form, "mail_reset_persisted", $context);
        });
        CustomFormEvents::notify($form, "mail_reset", [
            "id" => $id,
            "entry" => array_replace($entry, ["mailed" => 0]),
            "previous" => $entry
        ]);
    }

    private static function export(array $form): void
    {
        CustomFormEvents::begin($form, "export");
        $columns = array_keys($form["fields"]);
        $context = [
            "columns" => $columns,
            "query" => ["page" => 1, "size" => 1000, "keyword" => ""]
        ];
        CustomFormEvents::run($form, "export_prepare", $context);
        if (
            !is_array($context["columns"]) ||
            !$context["columns"] ||
            array_filter($context["columns"], static fn($column) => !is_string($column)) ||
            count(array_unique($context["columns"])) !== count($context["columns"]) ||
            array_diff($context["columns"], $columns)
        ) {
            throw new InvalidArgumentException("导出列必须为已声明且不重复的字段");
        }
        $columns = array_values($context["columns"]);
        if (!is_array($context["query"])) {
            throw new DomainException("query必须为查询参数对象");
        }
        $query = self::checkedQuery($form, $context["query"], false);
        $stream = tmpfile();
        if (!$stream) {
            throw new RuntimeException("无法建立导出文件");
        }
        $count = 0;
        try {
            if (
                fwrite($stream, "\xEF\xBB\xBF") === false ||
                fputcsv(
                    $stream,
                    array_merge(["id"], $columns, ["mailed", "add_date"]),
                    ",",
                    '"',
                    ""
                ) === false
            ) {
                throw new RuntimeException("导出文件写入失败");
            }
            for ($page = 1; ; $page++) {
                $result = self::store()->entries(
                    $form["id"],
                    $page,
                    $query["keyword"],
                    $query["size"],
                    $query["search_fields"]
                );
                foreach ($result["rows"] as $entry) {
                    $context = ["data" => $entry["data"], "id" => (int) $entry["id"]];
                    CustomFormEvents::run($form, "export_row", $context);
                    if (!is_array($context["data"])) {
                        throw new DomainException("导出记录无效");
                    }
                    $values = [$entry["id"]];
                    foreach ($columns as $key) {
                        $field = $form["fields"][$key];
                        $value = $context["data"][$key] ?? "";
                        $values[] = self::csvCell(
                            $field["type"] === "checkbox"
                                ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
                                : CustomFormFields::display($field, $value)
                        );
                    }
                    if (
                        fputcsv(
                            $stream,
                            array_merge($values, [$entry["mailed"], $entry["created_at"]]),
                            ",",
                            '"',
                            ""
                        ) === false
                    ) {
                        throw new RuntimeException("导出文件写入失败");
                    }
                    $count++;
                }
                if ($page * $query["size"] >= $result["count"]) {
                    break;
                }
            }
            CustomFormEvents::notify($form, "export_completed", [
                "count" => $count,
                "columns" => $columns
            ]);
            rewind($stream);
            while (ob_get_level()) {
                ob_end_clean();
            }
            header("Content-Type: text/csv; charset=UTF-8");
            header("Cache-Control: private, no-store");
            header(
                'Content-Disposition: attachment; filename="custom_form_' . $form["id"] . '.csv"'
            );
            header("Content-Length: " . fstat($stream)["size"]);
            fpassthru($stream);
        } finally {
            fclose($stream);
        }
    }

    private static function importCSV(array $form): int
    {
        CustomFormEvents::begin($form, "import");
        $upload = $_FILES["csv"] ?? [];
        if (
            ($upload["error"] ?? -1) !== UPLOAD_ERR_OK ||
            !is_string($upload["tmp_name"] ?? null) ||
            !is_uploaded_file($upload["tmp_name"]) ||
            filesize($upload["tmp_name"]) > 5242880
        ) {
            throw new InvalidArgumentException("请上传5 MiB以内UTF-8 CSV文件");
        }
        $stream = fopen($upload["tmp_name"], "rb");
        try {
            $rows = self::readCSV($form, $stream);
        } finally {
            fclose($stream);
        }
        $context = ["rows" => $rows, "count" => count($rows)];
        CustomFormEvents::run($form, "import_validate", $context);
        if (
            !is_array($context["rows"]) ||
            !array_is_list($context["rows"]) ||
            count($context["rows"]) > 5000
        ) {
            throw new InvalidArgumentException("导入批次无效");
        }
        $rows = [];
        foreach ($context["rows"] as $row) {
            if (!is_array($row)) {
                throw new InvalidArgumentException("导入行无效");
            }
            $rows[] = CustomFormFields::values($form, $row, "cn", true);
        }
        $ids = [];
        $count = self::store()->transaction(function () use ($form, $rows, &$ids) {
            $count = self::store()->import($form["id"], $rows, self::owner(), $ids);
            $context = ["count" => $count, "ids" => $ids, "rows" => $rows];
            CustomFormEvents::run($form, "import_persisted", $context);
            return $count;
        });
        CustomFormEvents::notify($form, "import_completed", [
            "count" => $count,
            "ids" => $ids,
            "rows" => $rows
        ]);
        return $count;
    }

    private static function readCSV(array $form, $stream): array
    {
        $header = fgetcsv($stream, 0, ",", '"', "");
        if (!$header || $header === [null]) {
            throw new InvalidArgumentException("CSV为空");
        }
        $header[0] = ltrim($header[0], "\xEF\xBB\xBF");
        $context = ["columns" => $header];
        CustomFormEvents::run($form, "import_prepare", $context);
        $header = $context["columns"];
        $allowed = array_merge(["id", "mailed", "add_date"], array_keys($form["fields"]));
        if (
            !is_array($header) ||
            !$header ||
            array_filter($header, static fn($column) => !is_string($column)) ||
            count(array_unique($header)) !== count($header) ||
            array_diff($header, $allowed)
        ) {
            throw new InvalidArgumentException("CSV含重复或未知字段");
        }
        $rows = [];
        $rowIndex = 0;
        while (($row = fgetcsv($stream, 0, ",", '"', "")) !== false) {
            $rowIndex++;
            if ($row === [null]) {
                continue;
            }
            if (count($row) !== count($header) || count($rows) >= 5000) {
                throw new InvalidArgumentException("CSV列数不一致或超过5000条");
            }
            $input = array_combine($header, $row);
            foreach ($form["fields"] as $key => $field) {
                if ($field["type"] === "checkbox") {
                    $value = $input[$key] ?? "";
                    $input[$key] =
                        $value === ""
                            ? []
                            : (str_starts_with(trim($value), "[")
                                ? json_decode($value, true, 32, JSON_THROW_ON_ERROR)
                                : array_map("trim", explode(",", $value)));
                }
            }
            $rows[] = self::entryData(
                $form,
                $input,
                "cn",
                true,
                [],
                "import_row",
                0,
                [],
                ["row_index" => $rowIndex]
            );
        }
        return $rows;
    }

    /** 保留上一轮服务调用入口；控制器统一使用有明确阶段的entryData。 */
    public static function filterEntry(
        array $form,
        array $data,
        string $event,
        bool $admin,
        int $id = 0
    ): array {
        $context = ["form" => $form, "data" => $data, "admin" => $admin, "id" => $id];
        if (isset(CustomFormEvents::CATALOG[$event])) {
            CustomFormEvents::run($form, $event, $context);
        } else {
            CustomFormTemplates::hook($form, $event, $context);
        }
        $files = [];
        foreach ($form["fields"] as $key => $field) {
            if ($field["type"] === "file" && isset($data[$key])) {
                $files[$key] = $data[$key];
            }
        }
        return CustomFormFields::values($form, $context["data"], "cn", $admin, $files);
    }

    private static function sendMail(
        array $form,
        array $entry,
        array $input = [],
        ?CustomFormMailer $mailer = null
    ): void {
        CustomFormEvents::begin($form, "mail_send", ["id" => (int) $entry["id"]]);
        if ((int) $entry["mailed"] === 1) {
            throw new DomainException("此记录已经发送确认邮件");
        }
        $recipient = $input["mail_to"] ?? ($entry["data"]["email"] ?? "");
        if (!is_string($recipient)) {
            throw new InvalidArgumentException("收件邮箱格式无效");
        }
        if (!self::store()->claimMail($form["id"], (int) $entry["id"])) {
            throw new DomainException("该记录已发送或正在发送，请核对邮件状态");
        }
        global $ms_setting;
        $smtp = $ms_setting instanceof myConfig ? $ms_setting->get()["email"] ?? [] : [];
        $accepted = false;
        try {
            require_once __DIR__ . "/Mailer.php";
            $mailer ??= new CustomFormMailer();
            $delivery = $mailer->deliver(
                $smtp,
                $recipient,
                $form,
                $entry,
                ($input["mail_language"] ?? "cn") === "en" ? "en" : "cn",
                $input
            );
            $accepted = true;
            self::store()->transaction(function () use ($form, $entry, $delivery) {
                if (!self::store()->completeMail($form["id"], (int) $entry["id"])) {
                    throw new DomainException(
                        "邮件已被SMTP接受，但记录状态未能确认，请先核对投递结果"
                    );
                }
                $context = [
                    "id" => (int) $entry["id"],
                    "entry" => array_replace($entry, ["mailed" => 1]),
                    "mail" => $delivery
                ];
                CustomFormEvents::run($form, "mail_sent_persisted", $context);
            });
        } catch (Throwable $error) {
            $attempted = $mailer?->transportStarted() ?? false;
            if (!$accepted && !$attempted) {
                self::store()->releaseMail($form["id"], (int) $entry["id"]);
            }
            CustomFormEvents::annotate([
                "delivery_attempted" => $attempted,
                "delivery_accepted" => $accepted
            ]);
            if ($accepted) {
                throw new DomainException(
                    "SMTP已接受邮件，但发送状态或事务联动未完成；请先核对投递结果，不要直接重发。",
                    0,
                    $error
                );
            }
            throw $error;
        }
        CustomFormEvents::notify($form, "mail_sent", [
            "id" => (int) $entry["id"],
            "entry" => array_replace($entry, ["mailed" => 1]),
            "mail" => $delivery
        ]);
    }
}
