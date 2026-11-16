<?php
/** 双语确认邮件：表单模板生成草稿，管理员预览修改后显式发送。 */
class CustomFormMailer extends myEmail
{
    private bool $transportStarted = false;

    public function transportStarted(): bool
    {
        return $this->transportStarted;
    }

    /** 生成邮件内容；模板变量只包含普通提交者可见字段，不发送管理员字段。 */
    public static function compose(
        array $form,
        array $entry,
        string $lng = "cn",
        array $input = []
    ): array {
        $form = CustomFormTemplates::upgradeLegacy($form);
        $lng = $lng === "en" ? "en" : "cn";
        $title = $lng === "en" ? ($form["name_en"] ?: $form["name"]) : $form["name"];
        $vars = [
            "title" => $title,
            "id" => (int) $entry["id"],
            "created_at" => $entry["created_at"] ?? "",
            "greeting" => $lng === "en" ? "Dear participant," : "尊敬的用户：",
            "confirmation" =>
                $lng === "en" ? "Your submission has been confirmed." : "您提交的信息已确认。"
        ];
        $rows = "";
        foreach ($form["fields"] as $key => $field) {
            if ($field["manager"]) {
                continue;
            }
            $value = CustomFormFields::display($field, $entry["data"][$key] ?? "", $lng);
            $vars["record_" . $key] = $value;
            $label = $lng === "en" ? ($field["title_en"] ?: $field["title"]) : $field["title"];
            $rows .=
                "<tr><th>" .
                CustomFormView::h($label) .
                "</th><td>" .
                nl2br(CustomFormView::h($value)) .
                "</td></tr>";
        }
        $vars["rows"] = $rows;
        $subject = $title;
        $html = CustomFormTemplates::render(
            $form,
            "mail_" . $lng,
            $vars,
            [],
            ["language" => $lng, "entry" => $entry]
        );
        if (array_key_exists("mail_html", $input)) {
            $html = CustomFormFields::text($input["mail_html"], 100000);
        }
        return self::checkedMail($form, $lng, ["html" => $html]);
    }

    private static function checkedMail(array $form, string $language, array $mail): array
    {
        $title = $language === "en" ? ($form["name_en"] ?: $form["name"]) : $form["name"];
        $subject = CustomFormFields::text($title, 1000);
        $html = CustomFormFields::text($mail["html"] ?? "", 1048576);
        if (strlen($html) > 1048576) {
            throw new DomainException("邮件正文超过1 MiB，请简化模板");
        }
        return [
            "subject" => mb_substr(str_replace(["\r", "\n"], " ", $subject), 0, 200),
            "html" => $html,
            "text" => html_entity_decode(
                strip_tags(preg_replace("/<br\s*\/?\s*>|<\/tr>/i", "\n", $html)),
                ENT_QUOTES | ENT_HTML5,
                "UTF-8"
            )
        ];
    }

    /** 邮件草稿不触发发送，修改仅用于本次投递，不覆盖表单模板。 */
    public static function editor(array $form, array $entry, string $lng): string
    {
        CustomFormEvents::begin($form, "mail_preview", ["id" => (int) $entry["id"]]);
        $context = [
            "id" => (int) $entry["id"],
            "data" => $entry["data"],
            "recipient" => $entry["data"]["email"] ?? "",
            "language" => $lng
        ];
        CustomFormEvents::run($form, "mail_preview_prepare", $context);
        if (!is_array($context["data"])) {
            throw new DomainException("邮件预览数据无效");
        }
        $entry["data"] = $context["data"];
        $context["mail"] = self::compose($form, $entry, $lng);
        CustomFormEvents::run($form, "mail_preview_render", $context);
        if (!is_array($context["mail"])) {
            throw new DomainException("mail必须为邮件对象");
        }
        $mail = self::checkedMail($form, $lng, $context["mail"]);
        $recipient = CustomFormFields::text($context["recipient"], 254);
        return CustomFormView::render("mail_editor", [
            "action" => plugin_cform::adminURL(["mid" => $form["id"]]),
            "csrf" => plugin_cform::tokenField(),
            "id" => $entry["id"],
            "mail_to" => $recipient,
            "language" => $lng,
            "subject" => $mail["subject"],
            "html" => $mail["html"],
            "cn_url" => plugin_cform::adminURL([
                "mid" => $form["id"],
                "id" => $entry["id"],
                "action" => "confirm",
                "lng" => "cn"
            ]),
            "en_url" => plugin_cform::adminURL([
                "mid" => $form["id"],
                "id" => $entry["id"],
                "action" => "confirm",
                "lng" => "en"
            ]),
            "back_url" => plugin_cform::adminURL(["mid" => $form["id"]])
        ]);
    }
    public function deliver(
        array $smtp,
        string $email,
        array $form,
        array $entry,
        string $lng = "cn",
        array $input = []
    ): array {
        $this->transportStarted = false;
        $from = (string) ($smtp["from"] ?? ($smtp["user"] ?? ""));
        if (!filter_var($from, FILTER_VALIDATE_EMAIL) || empty($smtp["host"])) {
            throw new DomainException("请在当前应用配置SMTP及有效发件邮箱");
        }
        $context = [
            "id" => (int) $entry["id"],
            "data" => $entry["data"],
            "recipient" => $email,
            "input" => array_intersect_key($input, ["mail_html" => true]),
            "language" => $lng
        ];
        CustomFormEvents::run($form, "mail_send_prepare", $context);
        if (!is_array($context["data"]) || !is_array($context["input"])) {
            throw new DomainException("邮件发送数据无效");
        }
        $entry["data"] = $context["data"];
        $context["mail"] = self::compose($form, $entry, $lng, $context["input"]);
        CustomFormEvents::run($form, "mail_send_validate", $context);
        if (!is_array($context["mail"])) {
            throw new DomainException("mail必须为邮件对象");
        }
        $mail = self::checkedMail($form, $lng, $context["mail"]);
        $email = CustomFormFields::text($context["recipient"], 254);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("收件邮箱无效");
        }
        $this->init($from, "UTF-8");
        $this->to($email);
        $this->setSubject($mail["subject"]);
        $this->setContent($mail["html"], true, $mail["text"]);
        $this->transportStarted = true;
        $result = $this->send($smtp, false, 3);
        if (!is_array($result) || !$result) {
            throw new DomainException("确认邮件发送失败");
        }
        foreach ($result as $line) {
            if (!str_ends_with((string) $line, " sent successfully!")) {
                throw new DomainException("确认邮件发送失败");
            }
        }
        return ["recipient" => $email] + $mail;
    }
    protected function error($msg, $exit = false)
    {
        throw new DomainException("确认邮件发送失败，请检查SMTP配置");
    }
}
