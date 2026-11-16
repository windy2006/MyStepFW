<?php
/** 表单操作事件：每个名称只表示一个阶段，入口授权与数据校验由调用方负责。 */
final class CustomFormEvents
{
    /** 名称 => [用途、可修改的数据]；默认扩展与文档依据此清单核对。 */
    public const CATALOG = [
        "form_list_item" => ["每个表单生成管理列表行之前", "vars"],
        "form_list_render" => ["表单管理列表整页渲染前，仅使用默认扩展", "vars"],
        "form_create_prepare" => ["新建表单设计页：默认定义生成后、控件生成前", "form"],
        "form_create_render" => ["新建表单设计页：展示变量生成后", "vars"],
        "form_update_prepare" => ["编辑表单设计页：定义读取后、控件生成前", "form"],
        "form_update_render" => ["编辑表单设计页：展示变量生成后", "vars"],
        "form_create_validate" => ["新增表单：基础定义验证后、入库前", "form"],
        "form_update_validate" => [
            "修改表单：修订核对和基础验证后、入库前",
            "form; previous为原定义"
        ],
        "form_create_persisted" => [
            "新增表单已写入、事务提交前",
            "同一store事务联动，抛出异常回滚"
        ],
        "form_created" => ["新增表单已入库并取得编号", "仅通知"],
        "form_update_persisted" => [
            "表单修改已写入、事务提交前",
            "同一store事务联动，抛出异常回滚"
        ],
        "form_updated" => ["修改表单已入库并更新修订号", "仅通知"],
        "form_delete_validate" => ["表单停用前", "抛出异常可阻止"],
        "form_delete_persisted" => [
            "停用标记已写入、事务提交前",
            "同一store事务联动，抛出异常回滚"
        ],
        "form_deleted" => ["表单已停用，报名记录仍保留", "仅通知"],
        "submission_prepare" => ["前台填写页：初始数据准备后、生成控件前", "data"],
        "submission_render" => ["前台填写页：展示变量生成后", "vars; loops"],
        "submission_input" => ["前台提交：凭证核对及上传检查后、字段验证前", "input"],
        "submission_validate" => ["前台提交：标准中文值验证后、入库前", "data"],
        "submission_persisted" => [
            "前台新提交已写入并取得编号、事务提交前",
            "同一store事务联动，抛出异常回滚"
        ],
        "submission_saved" => ["前台新提交已入库并登记幂等结果", "仅通知，重放不触发"],
        "entry_create_prepare" => ["后台新增记录：默认数据准备后、生成控件前", "data"],
        "entry_create_render" => ["后台新增记录：展示变量生成后", "vars; loops"],
        "entry_update_prepare" => ["后台编辑记录：记录读取后、生成控件前", "data"],
        "entry_update_render" => ["后台编辑记录：展示变量生成后", "vars; loops"],
        "entry_create_input" => ["后台新增记录：上传检查后、字段验证前", "input"],
        "entry_update_input" => ["后台修改记录：旧记录读取及上传检查后、字段验证前", "input"],
        "entry_create_validate" => ["后台新增记录：标准中文值验证后、入库前", "data"],
        "entry_update_validate" => [
            "后台修改记录：标准中文值验证后、入库前",
            "data; previous为原值"
        ],
        "entry_create_persisted" => [
            "后台新增记录已写入并取得编号、事务提交前",
            "同一store事务联动，抛出异常回滚"
        ],
        "entry_created" => ["后台新增记录已入库并取得编号", "仅通知"],
        "entry_update_persisted" => [
            "后台记录修改已写入、事务提交前",
            "同一store事务联动，抛出异常回滚"
        ],
        "entry_updated" => ["后台记录修改已入库", "仅通知"],
        "entry_list_query" => ["后台记录列表：查询执行前", "query"],
        "entry_list_item" => ["后台记录列表：每条记录生成单元格前", "data"],
        "entry_list_render" => ["后台记录列表：整页展示变量生成后", "vars; loops"],
        "entry_delete_validate" => ["记录归属核对后、删除前", "抛出异常可阻止"],
        "entry_delete_persisted" => [
            "记录删除已执行、事务提交前",
            "同一store事务联动，抛出异常回滚"
        ],
        "entry_deleted" => ["记录已删除", "仅通知"],
        "public_list_query" => ["公开列表或子块：公开权限核对后、查询前", "query"],
        "public_list_item" => ["公开列表或子块：每条记录投影后、生成单元格前", "data，仅公开字段"],
        "public_block_render" => ["公开列表子块：展示变量生成后", "vars; loops"],
        "public_list_render" => ["公开列表页面：子块完成后、整页渲染前", "vars; loops"],
        "print_prepare" => ["回执归属核对后、生成打印内容前", "data"],
        "print_render" => ["打印页展示变量生成后", "vars"],
        "file_download_validate" => [
            "附件权限、令牌及文件存在检查后，发送响应前",
            "抛出异常可阻止"
        ],
        "import_prepare" => ["CSV上传与表头读取后，列名验证前", "columns"],
        "import_row_input" => ["每行CSV拆列和多选解析后，字段验证前", "input"],
        "import_row_validate" => ["每行CSV字段验证后，加入批次前", "data"],
        "import_validate" => ["完整批次准备后，事务写入前", "rows"],
        "import_persisted" => ["完整导入批次已写入、事务提交前", "同一store事务联动，抛出异常回滚"],
        "import_completed" => ["整批导入事务成功后", "仅通知，含ids"],
        "export_prepare" => ["CSV生成前，尚未发送下载响应头", "columns; query"],
        "export_row" => ["每条导出记录编码为CSV之前", "data"],
        "export_completed" => ["CSV完整生成后、发送响应前，不代表客户端已接收", "仅通知"],
        "mail_preview_prepare" => ["邮件预览：记录读取后、模板生成前", "data; recipient"],
        "mail_preview_render" => ["邮件预览：草稿生成后、预览页渲染前", "mail.html; recipient"],
        "mail_send_prepare" => [
            "发送权领取和SMTP配置验证后、邮件生成前",
            "data; recipient; input.mail_html"
        ],
        "mail_send_validate" => ["实际邮件生成后、最终验证和SMTP调用前", "mail.html; recipient"],
        "mail_sent_persisted" => [
            "SMTP已确认、发送标记已写入但尚未提交",
            "同一store事务联动，抛出异常保留待核对状态"
        ],
        "mail_sent" => ["SMTP确认成功且记录已标记发送后", "仅通知"],
        "mail_reset_validate" => ["核对发送中状态后，重置发送状态前", "抛出异常可阻止"],
        "mail_reset_persisted" => ["发送状态已重置、事务提交前", "同一store事务联动，抛出异常回滚"],
        "mail_reset" => ["发送状态已恢复为未发送", "仅通知"],
        "operation_failed" => ["已授权操作失败后，错误响应生成前", "仅通知，含operation/error"]
    ];

    private const LEGACY = [
        "submission_render" => "render_form",
        "entry_create_render" => "render_form",
        "entry_update_render" => "render_form",
        "entry_list_render" => "render_list",
        "public_list_render" => "render_list",
        "public_block_render" => "render_block",
        "print_render" => "render_print",
        "mail_preview_render" => "render_mail",
        "mail_send_validate" => "render_mail",
        "submission_validate" => "before_submit",
        "entry_create_validate" => "before_entry_save",
        "entry_update_validate" => "before_entry_save",
        "import_row_validate" => "before_import",
        "export_row" => "before_export",
        "entry_delete_validate" => "before_delete_entry",
        "form_delete_validate" => "before_delete_form"
    ];

    private static ?array $operation = null;
    private static bool $failureReported = false;
    private static array $legacyCache = [];
    private static ?SplObjectStorage $reportedErrors = null;

    /** 已经完成权限、归属等入口检查后调用，不接受用户传入的事件名称。 */
    public static function begin(array $form, string $operation, array $context = []): void
    {
        self::$operation = ["form" => $form, "operation" => $operation] + $context;
        self::$failureReported = false;
    }

    public static function annotate(array $details): void
    {
        if (self::$operation !== null) {
            self::$operation = array_replace(self::$operation, $details);
        }
    }

    public static function currentFormId(): int
    {
        return (int) (self::$operation["form"]["id"] ?? 0);
    }

    /** 兼容上一轮扩展的字面事件名；一次只执行新事件或旧别名，不双重调用。 */
    public static function legacy(array $form): bool
    {
        $code = $form["extension"] ?? "";
        if ($code === "") {
            return false;
        }
        if (preg_match('/^\s*\/\/\s*cform-events:\s*([12])\s*$/m', $code, $marker)) {
            return $marker[1] === "1";
        }
        $digest = hash("sha256", $code);
        if (array_key_exists($digest, self::$legacyCache)) {
            return self::$legacyCache[$digest];
        }
        $names = [];
        foreach (token_get_all("<?php " . $code) as $token) {
            if (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING) {
                $names[] = substr($token[1], 1, -1);
            }
        }
        return self::$legacyCache[$digest] =
            !array_intersect($names, array_keys(self::CATALOG)) &&
            (bool) array_intersect($names, array_values(self::LEGACY));
    }

    public static function run(array $form, string $event, array &$context): void
    {
        if (!isset(self::CATALOG[$event])) {
            throw new LogicException("未知表单扩展事件：" . $event);
        }
        $delivered = $event;
        if (self::legacy($form)) {
            if (!isset(self::LEGACY[$event])) {
                return;
            }
            $delivered = self::LEGACY[$event];
        }
        $context += ["form" => $form, "operation" => self::$operation["operation"] ?? $event];
        $context["event"] = $event;
        $previousOperation = self::$operation;
        if ($previousOperation !== null) {
            $previousOperation["stage"] = $event;
            foreach (["id", "row_index", "language", "source"] as $key) {
                if (array_key_exists($key, $context)) {
                    $previousOperation[$key] = $context[$key];
                } else {
                    unset($previousOperation[$key]);
                }
            }
            self::$operation = $previousOperation;
        }
        $previousFailure = self::$failureReported;
        $level = ob_get_level();
        ob_start();
        try {
            CustomFormTemplates::hook($form, $delivered, $context);
            if (ob_get_contents() !== "") {
                throw new DomainException("扩展不得直接输出内容，请修改对应的上下文数据");
            }
        } finally {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            self::$operation = $previousOperation;
            self::$failureReported = $previousFailure;
        }
    }

    /** 成功通知不能把已经提交的业务操作变成失败，更不能促成重复提交。 */
    public static function notify(array $form, string $event, array $context): void
    {
        try {
            self::run($form, $event, $context);
        } catch (Throwable $error) {
            $record = [
                "time" => date("c"),
                "form_id" => (int) ($form["id"] ?? 0),
                "event" => $event,
                "error" => get_class($error) . ": " . mb_substr($error->getMessage(), 0, 1000)
            ];
            try {
                error_log(
                    json_encode($record, JSON_UNESCAPED_UNICODE) . "\n",
                    3,
                    CONFIG . "custom_form_events.log"
                );
            } catch (Throwable) {
                // 日志介质不可用也不能把已提交操作变成失败。
            }
            $_SESSION["custom_form_event_warnings"][] =
                "扩展阶段“" . $event . "”执行失败，请查看扩展日志；已完成的操作不会撤销。";
        }
    }

    /** 一次失败只通知一次，通知代码再次报错只记日志。 */
    public static function failed(Throwable $error): void
    {
        if (!self::$operation || self::$failureReported) {
            return;
        }
        self::$reportedErrors ??= new SplObjectStorage();
        for ($cause = $error; $cause; $cause = $cause->getPrevious()) {
            if (self::$reportedErrors->offsetExists($cause)) {
                return;
            }
        }
        self::$reportedErrors->offsetSet($error, true);
        self::$failureReported = true;
        self::notify(
            self::$operation["form"],
            "operation_failed",
            self::$operation + [
                "error" => ["class" => get_class($error), "message" => $error->getMessage()]
            ]
        );
    }

    /** 嵌套标签使用自己的表单上下文，不能污染外层表单的失败通知。 */
    public static function scope(array $form, string $operation, callable $callback): mixed
    {
        $previous = self::$operation;
        $reported = self::$failureReported;
        self::begin($form, $operation);
        try {
            return $callback();
        } catch (Throwable $error) {
            self::failed($error);
            throw $error;
        } finally {
            self::$operation = $previous;
            self::$failureReported = $reported;
        }
    }

    public static function warnings(): string
    {
        $warnings = $_SESSION["custom_form_event_warnings"] ?? [];
        unset($_SESSION["custom_form_event_warnings"]);
        return implode(" ", $warnings);
    }
}
