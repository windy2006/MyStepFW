<?php
/**
 * custom_form PHP8插件。原作者Windy2000；旧CMS插件1.6的独立框架移植。
 * 生命周期check/install/uninstall；main管理页；submit/list/print/file公开入口；
 * store/config提供隔离服务；tagList在运行期读取允许公开的字段。
 * 文件只声明，不执行安装；权限、活动状态、方法在处理器中再次核验。
 */
require_once __DIR__ . "/Store.php";
require_once __DIR__ . "/Fields.php";
require_once __DIR__ . "/Events.php";
require_once __DIR__ . "/Operations.php";
require_once __DIR__ . "/View.php";
class plugin_cform implements interface_plugin
{
    use CustomFormOperations;
    private static ?CustomFormStore $store = null;
    public static function config(): array
    {
        $c = include __DIR__ . "/config.php";
        if (
            !is_array($c) ||
            !preg_match("/\A[a-zA-Z][a-zA-Z0-9_]{0,39}\z/D", $c["table_prefix"] ?? "")
        ) {
            throw new RuntimeException("表单配置无效");
        }
        $c["upload_bytes"] = max(1, min(20971520, (int) ($c["upload_bytes"] ?? 5242880)));
        $c["submit_interval"] = max(0, min(86400, (int) ($c["submit_interval"] ?? 300)));
        return $c;
    }
    public static function validateConfig(myConfig $config): void
    {
        $c = $config->get();
        $old = self::config();
        if (($c["table_prefix"] ?? "") !== $old["table_prefix"]) {
            throw new InvalidArgumentException("安装后不支持通过设置更改表前缀；请保留当前值");
        }
        foreach (
            ["upload_bytes" => [1, 20971520], "submit_interval" => [0, 86400]]
            as $k => [$min, $max]
        ) {
            if (
                filter_var($c[$k] ?? null, FILTER_VALIDATE_INT) === false ||
                (int) $c[$k] < $min ||
                (int) $c[$k] > $max
            ) {
                throw new InvalidArgumentException("设置数值超出范围");
            }
        }
    }

    /** 独立连接使用根配置，不受CMS子站或后台入口切换影响。 */
    public static function store(): CustomFormStore
    {
        if (self::$store) {
            return self::$store;
        }
        $config = new myConfig(CONFIG . "config.php");
        $c = $config->get();
        $d = $c["db"] ?? [];
        $db = new myDb(
            "MySQL",
            $d["host"] ?? "",
            $d["user"] ?? "",
            $d["password"] ?? "",
            "utf8mb4"
        );
        $db->connect(false, $d["name"] ?? "");
        return self::$store = new CustomFormStore($db, self::config()["table_prefix"]);
    }
    public static function check(&$result = ""): bool
    {
        $ok =
            PHP_VERSION_ID >= 80512 &&
            PHP_VERSION_ID < 90000 &&
            extension_loaded("mysqli") &&
            extension_loaded("mbstring") &&
            extension_loaded("fileinfo");
        $result = $ok
            ? "PHP8.5 / mysqli / mbstring / fileinfo：OK。安装将建立两张独立表，卸载保留数据。"
            : "需要PHP8.5.12、mysqli、mbstring和fileinfo";
        return $ok;
    }
    public static function install(): void
    {
        $result = "";
        if (!self::check($result)) {
            throw new RuntimeException($result);
        }
        self::store()->install();
        try {
            setPriority("cform");
            if (!regPluginRoute("cform")) {
                throw new RuntimeException("表单路由登记失败");
            }
        } catch (Throwable $e) {
            removePluginRoute("cform");
            removePriority("cform");
            removePluginLink("cform/manage");
            throw $e;
        }
    }
    public static function uninstall(): void
    {
        removePluginRoute("cform");
        removePriority("cform");
        removePluginLink("cform/manage");
    }
    public static function active(): void
    {
        if (!isset(myStep::activePlugins()["cform"])) {
            http_response_code(404);
            throw new OutOfBoundsException("插件未启用");
        }
    }
    public static function isAdmin(): bool
    {
        return pluginPageAdministrator("cform");
    }
    public static function admin(): void
    {
        self::active();
        if (!self::isAdmin()) {
            http_response_code(403);
            throw new DomainException("需要表单管理权限");
        }
    }
    /**
     * 功能说明：单独校验受信模板与PHP扩展编辑权限，应用管理员不可通过提交参数绕过。
     * 参数说明：无显式参数。
     * @return void 框架管理员通过，否则返回403并抛出异常
     * @throws DomainException 当前用户不具备源码编辑权限
     */
    public static function sourceAdmin(): void
    {
        if (!pluginFrameworkAdministrator()) {
            http_response_code(403);
            throw new DomainException("模板源码与PHP扩展仅限框架管理员修改");
        }
    }
    public static function post(): void
    {
        if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
            http_response_code(405);
            header("Allow: POST");
            throw new DomainException("操作只接受POST");
        }
        // 框架guard已核验并移除表单令牌；不重复验证被移除的令牌。
    }
    public static function id(mixed $v): int
    {
        if (!is_scalar($v) || !preg_match("/\A[1-9][0-9]{0,9}\z/D", (string) $v)) {
            throw new InvalidArgumentException("编号无效");
        }
        return (int) $v;
    }
    public static function language(): string
    {
        return ($_GET["lng"] ?? $GLOBALS["ms_setting"]->value("gen.language", "cn")) === "en"
            ? "en"
            : "cn";
    }
    public static function url(string $mode, int $mid, array $query = []): string
    {
        return pluginQueryURL(myStep::setURL("cform/" . $mode . "/" . $mid), $query);
    }
    public static function getUrl_submit($url = "", $idx = "", $page = 1): string
    {
        return self::url("submit", self::id($idx));
    }
    public static function getUrl_list($url = "", $idx = "", $page = 1): string
    {
        return self::url("list", self::id($idx), ["page" => max(1, (int) $page)]);
    }
    public static function main(): void
    {
        self::active();
        showPluginPage("cform", "manage");
    }
    public static function tokenField(): string
    {
        return '<input type="hidden" name="_ms_csrf" value="' . mySecurity::token() . '">';
    }
    private static function owner(): string
    {
        if (!isset($_SESSION["custom_form_owner"])) {
            $_SESSION["custom_form_owner"] = bin2hex(random_bytes(32));
        }
        return $_SESSION["custom_form_owner"];
    }
    public static function publicForm(int $mid, bool $submission = false): array
    {
        self::active();
        $f = self::store()->form($mid);
        if (!$f["active"] && !self::isAdmin()) {
            throw new DomainException("表单未开放");
        }
        if (
            $submission &&
            ($deadline = CustomFormFields::deadline($f["expire"])) !== null &&
            $deadline < time()
        ) {
            throw new DomainException("本表单已截止提交");
        }
        return $f;
    }

    /**
     * 功能说明：只接收已声明的附件字段，核对大小、扩展名和MIME，随机存入私有目录。
     * @param array $form 表单定义
     * @param array $previous 编辑前的附件及字段值
     * @param array $created 按引用返回本次新建文件，失败时由调用方清理
     * @param bool $admin 是否由已经过管理员鉴权的管理动作调用
     * @return array 合并后的附件值；普通提交忽略管理员字段
     */
    public static function uploads(
        array $form,
        array $previous,
        array &$created,
        bool $admin = false
    ): array {
        $out = $previous;
        foreach ($form["fields"] as $key => $f) {
            if ($f["type"] !== "file" || ($f["manager"] && !$admin)) {
                continue;
            }
            $u = $_FILES[$key] ?? null;
            if (!$u || ($u["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if (
                !is_array($u) ||
                !is_int($u["error"] ?? null) ||
                $u["error"] !== UPLOAD_ERR_OK ||
                !is_string($u["tmp_name"] ?? null) ||
                !is_uploaded_file($u["tmp_name"])
            ) {
                throw new InvalidArgumentException("附件上传失败");
            }
            if (filesize($u["tmp_name"]) > self::config()["upload_bytes"]) {
                throw new InvalidArgumentException("附件超过大小限制");
            }
            $name = basename(str_replace("\\", "/", CustomFormFields::text($u["name"] ?? "", 255)));
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $mime = new finfo(FILEINFO_MIME_TYPE)->file($u["tmp_name"]);
            $types = [
                "pdf" => ["application/pdf"],
                "txt" => ["text/plain"],
                "csv" => ["text/plain", "text/csv"],
                "jpg" => ["image/jpeg"],
                "jpeg" => ["image/jpeg"],
                "png" => ["image/png"],
                "gif" => ["image/gif"],
                "webp" => ["image/webp"],
                "doc" => ["application/msword", "application/CDFV2"],
                "xls" => ["application/vnd.ms-excel", "application/CDFV2"],
                "docx" => [
                    "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                    "application/zip"
                ],
                "xlsx" => [
                    "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                    "application/zip"
                ],
                "zip" => ["application/zip", "application/x-zip", "application/x-zip-compressed"]
            ];
            if (!isset($types[$ext]) || !in_array($mime, $types[$ext], true)) {
                throw new InvalidArgumentException("附件扩展名与实际类型不匹配或不受支持");
            }
            if (str_starts_with($mime, "image/") && @getimagesize($u["tmp_name"]) === false) {
                throw new InvalidArgumentException("图片附件已损坏");
            }
            $dir = self::uploadDirectory();
            $token = bin2hex(random_bytes(32));
            $path = $dir . "/" . $token;
            if (!move_uploaded_file($u["tmp_name"], $path)) {
                throw new RuntimeException("无法保存附件");
            }
            chmod($path, 0600);
            $created[] = $path;
            $out[$key] = ["token" => $token, "name" => $name, "mime" => $mime];
        }
        return $out;
    }
    private static function uploadDirectory(): string
    {
        $path = CONFIG . "custom_form_uploads";
        clearstatcache(true, $path);
        if (is_link($path)) {
            throw new RuntimeException("附件目录不可为符号链接");
        }
        if (!is_dir($path)) {
            // 并发首次上传时允许其他请求先建立目录，实际失败仍抛出异常。
            @mkdir($path, 0700, true);
            clearstatcache(true, $path);
            if (is_link($path)) {
                throw new RuntimeException("附件目录不可为符号链接");
            }
            if (!is_dir($path)) {
                throw new RuntimeException("无法建立私有附件目录");
            }
        }
        return $path;
    }
    public static function submitPage($mid = null): void
    {
        self::publicResponse(function () use ($mid) {
            $mid = self::id($mid ?? ($_REQUEST["mid"] ?? ""));
            $f = self::publicForm($mid, true);
            $lng = self::language();
            $message = "";
            $data = [];
            $created = [];
            if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
                self::post();
                $key = CustomFormFields::text($_POST["request_key"] ?? "", 64);
                if (
                    !preg_match("/\A[a-f0-9]{64}\z/D", $key) ||
                    !isset($_SESSION["custom_form_requests"][$mid][$key])
                ) {
                    throw new DomainException("表单已失效，请重新打开");
                }
                $state = $_SESSION["custom_form_requests"][$mid][$key];
                if (!is_array($state) || ($state["time"] ?? 0) <= time() - 7200) {
                    throw new DomainException("表单已失效，请重新打开");
                }
                if (isset($state["revision"]) && $state["revision"] !== $f["revision"]) {
                    throw new RuntimeException("表单已变更，请重新打开后提交", 409);
                }
                $id = (int) ($state["id"] ?? 0);
                if (!$id) {
                    $id = self::store()->requestEntry($mid, self::owner(), $key) ?? 0;
                }
                if (!$id) {
                    if (
                        ($_SESSION["custom_form_last"][$mid] ?? 0) >
                        time() - self::config()["submit_interval"]
                    ) {
                        throw new DomainException("提交过于频繁，请稍后再试");
                    }
                    CustomFormEvents::begin($f, "submission");
                    try {
                        $files = self::uploads($f, [], $created);
                        $data = self::entryData($f, $_POST, $lng, false, $files, "submission");
                        $inserted = false;
                        $id = self::store()->transaction(function () use (
                            $mid,
                            $data,
                            $key,
                            $f,
                            $lng,
                            &$inserted
                        ) {
                            $id = self::store()->addEntry(
                                $mid,
                                $data,
                                self::owner(),
                                $key,
                                $f["revision"],
                                true,
                                $inserted
                            );
                            if ($inserted) {
                                $context = ["id" => $id, "data" => $data, "language" => $lng];
                                CustomFormEvents::run($f, "submission_persisted", $context);
                            }
                            return $id;
                        });
                    } catch (Throwable $error) {
                        foreach ($created as $path) {
                            unlink($path);
                        }
                        CustomFormEvents::failed($error);
                        if ($error instanceof InvalidArgumentException) {
                            http_response_code(400);
                            return CustomFormView::message($error->getMessage()) .
                                CustomFormView::form($f, $_POST, $lng, false, $key, 0, true);
                        }
                        throw $error;
                    }
                    if (!$inserted) {
                        // 并发幂等竞争中未入库的本次附件不再需要。
                        foreach ($created as $path) {
                            unlink($path);
                        }
                    }
                    // 先登记成功结果，再通知；通知失败或请求重放均不会再次执行成功分支。
                    $_SESSION["custom_form_requests"][$mid][$key]["id"] = $id;
                    $_SESSION["custom_form_last"][$mid] = time();
                    if ($inserted) {
                        CustomFormEvents::notify($f, "submission_saved", [
                            "id" => $id,
                            "data" => $data,
                            "language" => $lng
                        ]);
                    }
                } else {
                    $_SESSION["custom_form_requests"][$mid][$key]["id"] = $id;
                }
                header(
                    "Location: " . self::url("print", $mid, ["id" => $id, "lng" => $lng]),
                    true,
                    303
                );
                exit();
            }
            $_SESSION["custom_form_requests"][$mid] = array_filter(
                $_SESSION["custom_form_requests"][$mid] ?? [],
                fn($r) => $r["time"] > time() - 7200
            );
            if (count($_SESSION["custom_form_requests"][$mid]) >= 20) {
                array_shift($_SESSION["custom_form_requests"][$mid]);
            }
            $key = bin2hex(random_bytes(32));
            $_SESSION["custom_form_requests"][$mid][$key] = [
                "time" => time(),
                "id" => 0,
                "revision" => $f["revision"]
            ];
            CustomFormEvents::begin($f, "submission");
            return CustomFormView::form($f, [], $lng, false, $key);
        });
    }
    public static function listPage($mid = null): void
    {
        self::publicResponse(function () use ($mid) {
            $mid = self::id($mid ?? ($_REQUEST["mid"] ?? ""));
            $f = self::publicForm($mid);
            if (!$f["public_list"] && !self::isAdmin()) {
                http_response_code(403);
                throw new DomainException("此表单不公开提交记录");
            }
            return CustomFormView::entries(
                $f,
                self::queryEntries(
                    $f,
                    max(1, (int) ($_GET["page"] ?? 1)),
                    "",
                    20,
                    false,
                    self::language()
                ),
                false,
                self::language()
            );
        });
    }
    public static function printPage($mid = null): void
    {
        self::publicResponse(function () use ($mid) {
            $mid = self::id($mid);
            $f = self::publicForm($mid);
            $r = self::ownedEntry($mid, self::id($_GET["id"] ?? ""));
            CustomFormEvents::begin($f, "print", ["id" => (int) $r["id"]]);
            return CustomFormView::receipt($f, $r, self::language());
        });
    }
    private static function ownedEntry(int $mid, int $id): array
    {
        $r = self::store()->entry($mid, $id);
        if (!self::isAdmin()) {
            if (!hash_equals($r["owner"], self::owner())) {
                http_response_code(403);
                throw new DomainException("没有权限访问该提交记录");
            }
        }
        return $r;
    }
    public static function filePage($mid = null): void
    {
        self::publicResponse(fn() => self::downloadFile($mid));
    }
    private static function downloadFile($mid): never
    {
        self::active();
        $mid = self::id($mid);
        $f = self::publicForm($mid);
        $r = self::ownedEntry($mid, self::id($_GET["id"] ?? ""));
        $key = $_GET["field"] ?? "";
        if (!is_string($key) || ($f["fields"][$key]["type"] ?? "") !== "file") {
            throw new OutOfBoundsException("附件不存在");
        }
        if ($f["fields"][$key]["manager"] && !self::isAdmin()) {
            http_response_code(403);
            throw new DomainException("管理员专用附件不对提交者开放");
        }
        $file = $r["data"][$key] ?? null;
        if (!is_array($file) || !preg_match("/\A[a-f0-9]{64}\z/D", $file["token"] ?? "")) {
            throw new OutOfBoundsException("附件不存在");
        }
        $path = self::uploadDirectory() . "/" . $file["token"];
        if (is_link($path) || !is_file($path)) {
            throw new OutOfBoundsException("附件不存在");
        }
        CustomFormEvents::begin($f, "file_download", ["id" => (int) $r["id"]]);
        $context = [
            "id" => (int) $r["id"],
            "field" => $key,
            "file" => ["name" => $file["name"], "mime" => $file["mime"]]
        ];
        CustomFormEvents::run($f, "file_download_validate", $context);
        while (ob_get_level()) {
            ob_end_clean();
        }
        header("Content-Type: application/octet-stream");
        header("X-Content-Type-Options: nosniff");
        header("Cache-Control: private, no-store");
        header(
            "Content-Disposition: attachment; filename=attachment; filename*=UTF-8''" .
                rawurlencode($file["name"])
        );
        header("Content-Length: " . filesize($path));
        readfile($path);
        exit();
    }
    private static function publicResponse(callable $fn): void
    {
        try {
            $body = $fn();
        } catch (Throwable $error) {
            CustomFormEvents::failed($error);
            if (
                $error instanceof InvalidArgumentException ||
                $error instanceof DomainException ||
                $error instanceof OutOfBoundsException
            ) {
                if (http_response_code() < 400) {
                    http_response_code($error instanceof OutOfBoundsException ? 404 : 400);
                }
            } elseif ($error instanceof RuntimeException && $error->getCode() === 409) {
                http_response_code(409);
            } else {
                throw $error;
            }
            $body = CustomFormView::message($error->getMessage());
        }
        $warning = CustomFormEvents::warnings();
        if ($warning !== "") {
            $body = CustomFormView::message($warning) . $body;
        }
        global $mystep;
        $mystep->addJS(STATICS . "js/checkForm.js");
        header("Cache-Control: private, no-store");
        $mystep->show(CustomFormView::page($body));
    }

    /** 编译期只生成静态调用，禁止旧condition/order SQL与eval。 */
    public static function tagList(myTemplate &$tpl, array &$attrs = []): string
    {
        $mid = self::id($attrs["mid"] ?? "");
        $limit = max(1, min(100, (int) ($attrs["limit"] ?? 10)));
        $lng = ($attrs["lng"] ?? "cn") === "en" ? "en" : "cn";
        return "<?php echo plugin_cform::listBlock(" .
            $mid .
            "," .
            $limit .
            "," .
            var_export($lng, true) .
            "); ?>";
    }
    public static function listBlock(int $mid, int $limit = 10, string $lng = "cn"): string
    {
        self::active();
        $f = self::publicForm($mid);
        if (!$f["public_list"]) {
            return "";
        }
        return CustomFormEvents::scope(
            $f,
            "public_list",
            static fn() => CustomFormView::entries(
                $f,
                self::queryEntries($f, 1, "", $limit, false, $lng, "block"),
                false,
                $lng,
                false
            )
        );
    }

    public static function csvCell(mixed $v): string
    {
        $v = (string) $v;
        return preg_match("/\A[\s]*[=+@\-]/u", $v) ? "'" . $v : $v;
    }

    /** 管理器已经完成身份检查；每项动作仍在插件内核验。 */
    public static function manage(): string
    {
        self::admin();
        $store = self::store();
        $action = $_POST["action"] ?? ($_GET["action"] ?? "");
        if (in_array($action, ["save_extension", "extension_recovery"], true)) {
            self::sourceAdmin();
        }
        $mid = isset($_REQUEST["mid"]) ? self::id($_REQUEST["mid"]) : 0;
        if (in_array($action, ["print_entry", "download_file"], true)) {
            if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "GET") {
                throw new DomainException("打印和附件下载只接受GET");
            }
            if ($action === "download_file") {
                self::downloadFile($mid);
            }
            self::printPage($mid);
            exit();
        }
        if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
            self::post();
            switch ($action) {
                case "save":
                    $saved = self::saveDefinition($_POST, $mid);
                    $mid = $saved["id"];
                    $message = "表单已保存";
                    break;
                case "save_extension":
                    // 专用修复通道不运行任何扩展，只按修订更新源码。
                    $form = $store->form($mid);
                    $checked = CustomFormTemplates::validate([
                        "extension" => $_POST["extension"] ?? ""
                    ]);
                    $store->saveExtension(
                        $mid,
                        $checked["extension"],
                        (int) ($_POST["revision"] ?? 0)
                    );
                    $message = "功能扩展已修复";
                    break;
                case "delete_form":
                    self::removeDefinition($store->form($mid));
                    $mid = 0;
                    $message = "表单已停用并移入保留区，记录保留";
                    break;
                case "save_entry":
                    self::saveRecord($store->form($mid), $_POST);
                    $message = "记录已保存";
                    break;
                case "delete_entry":
                    self::deleteRecord($store->form($mid), self::id($_POST["id"] ?? ""));
                    $message = "记录已删除";
                    break;
                case "export":
                    self::export($store->form($mid));
                    exit();
                case "import":
                    $message = "已导入 " . self::importCSV($store->form($mid)) . " 条记录";
                    break;
                case "reset_mail":
                    self::resetMail($store->form($mid), self::id($_POST["id"] ?? ""));
                    $message = "发送状态已重置；请确认此前邮件未投递后再重发";
                    break;
                case "mail":
                    self::sendMail(
                        $store->form($mid),
                        $store->entry($mid, self::id($_POST["id"] ?? "")),
                        $_POST
                    );
                    $message = "确认邮件已发送";
                    break;
                default:
                    throw new InvalidArgumentException("未知操作");
            }
            $_SESSION["custom_form_notice"] = $message;
            $next = $mid ? ["mid" => $mid] : [];
            if ($action === "save_extension") {
                $next["action"] = "design";
            }
            header("Location: " . self::adminURL($next), true, 303);
            exit();
        }
        $message = (string) ($_SESSION["custom_form_notice"] ?? "");
        unset($_SESSION["custom_form_notice"]);
        $message = trim($message . " " . CustomFormEvents::warnings());
        if ($action === "extension_recovery") {
            return CustomFormView::designer($store->form($mid), true);
        }
        if ($action === "new" || $action === "design") {
            $form = $mid ? $store->form($mid) : self::defaultForm();
            CustomFormEvents::begin($form, $mid ? "form_update" : "form_create");
            return CustomFormView::designer($form);
        }
        if ($mid) {
            $form = $store->form($mid);
            if ($action === "confirm") {
                $entry = $store->entry($mid, self::id($_GET["id"] ?? ""));
                CustomFormEvents::begin($form, "mail_preview", ["id" => (int) $entry["id"]]);
                require_once __DIR__ . "/Mailer.php";
                return CustomFormMailer::editor($form, $entry, self::language());
            }
            if ($action === "entry") {
                $id = isset($_GET["id"]) ? self::id($_GET["id"]) : 0;
                $data = $id ? $store->entry($mid, $id)["data"] : [];
                CustomFormEvents::begin($form, $id ? "entry_update" : "entry_create", [
                    "id" => $id
                ]);
                return CustomFormView::form($form, $data, "cn", true, "", $id);
            }
            $result = self::queryEntries(
                $form,
                max(1, (int) ($_GET["page"] ?? 1)),
                CustomFormFields::text($_GET["keyword"] ?? "", 200),
                20,
                true,
                "cn"
            );
            return CustomFormView::entries($form, $result, true, "cn", true, $message);
        }
        return CustomFormView::forms($store->forms(), $message);
    }

    public static function adminURL(array $query = []): string
    {
        return pluginManagementURL("cform", "manage", $query);
    }
    /**
     * 功能说明：在应用原生后台内打印或下载记录，沿用该后台会话，不跨到框架后台登录。
     * @param string $mode print或file；其他模式仍走原公开入口
     * @param int $mid 表单编号
     * @param array $query 记录编号、字段、语言等业务参数
     * @return string 已保留宿主上下文的站内地址
     */
    public static function recordURL(string $mode, int $mid, array $query = []): string
    {
        $app = $GLOBALS["plugin_page_app"] ?? "myStep";
        if (in_array($mode, ["print", "file"], true) && pluginAdminBackend($app) !== null) {
            return self::adminURL(array_replace($query, [
                "mid" => $mid,
                "action" => $mode === "print" ? "print_entry" : "download_file"
            ]));
        }
        return self::url($mode, $mid, $query);
    }
    public static function defaultForm(): array
    {
        $fields = include __DIR__ . "/defaults.php";
        return ["id" => 0, "revision" => 0] +
            CustomFormFields::definition([
                "name" => "新表单",
                "fields" => $fields,
                "active" => true
            ]);
    }
}
