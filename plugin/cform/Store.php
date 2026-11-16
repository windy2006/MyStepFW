<?php
/**
 * 独立存储：使用框架myDb的参数绑定、事务协议。JSON字段保留旧字段定义；
 * 不改变CMS表或按字段DDL改表。只在显式安装时建表，卸载保留数据。
 */
final class CustomFormStore
{
    public function __construct(
        private myDb $db,
        private string $prefix,
        private string $dialect = "mysql"
    ) {
        if (!preg_match("/\A[a-zA-Z][a-zA-Z0-9_]{0,39}\z/D", $prefix)) {
            throw new InvalidArgumentException("表前缀无效");
        }
        if (
            !$db->supports(interface_db_prepared::class) ||
            !$db->supports(interface_db_transaction::class)
        ) {
            throw new RuntimeException("数据库必须支持绑定与事务");
        }
    }
    public function table(string $name): string
    {
        if (!in_array($name, ["forms", "entries"], true)) {
            throw new InvalidArgumentException("未知表单数据表");
        }
        return "`" . $this->prefix . $name . "`";
    }
    public function write(string $sql, array $params = []): int
    {
        $n = $this->db->execute($sql, $params);
        if ($n === false) {
            throw new RuntimeException("表单数据库操作失败");
        }
        return $n;
    }
    public function install(): void
    {
        $id =
            $this->dialect === "sqlite"
                ? "INTEGER PRIMARY KEY AUTOINCREMENT"
                : "BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY";
        $text = $this->dialect === "sqlite" ? "TEXT" : "MEDIUMTEXT";
        $suffix = $this->dialect === "sqlite" ? "" : " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->write(
            "CREATE TABLE IF NOT EXISTS " .
                $this->table("forms") .
                " (id $id, revision INTEGER NOT NULL DEFAULT 1, definition $text NOT NULL, created_at VARCHAR(32) NOT NULL, deleted INTEGER NOT NULL DEFAULT 0)" .
                $suffix
        );
        $this->write(
            "CREATE TABLE IF NOT EXISTS " .
                $this->table("entries") .
                " (id $id, form_id BIGINT NOT NULL, payload $text NOT NULL, owner VARCHAR(64) NOT NULL, mailed INTEGER NOT NULL DEFAULT 0, created_at VARCHAR(32) NOT NULL, request_key VARCHAR(64) NOT NULL UNIQUE)" .
                $suffix
        );
        $index = $this->prefix . "entries_form_id";
        if ($this->dialect === "sqlite") {
            $this->write(
                "CREATE INDEX IF NOT EXISTS `" .
                    $index .
                    "` ON " .
                    $this->table("entries") .
                    " (form_id,id)"
            );
        } elseif (
            !(int) $this->db->fetchValue(
                "SELECT COUNT(*) FROM information_schema.statistics WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND INDEX_NAME=?",
                [$this->prefix . "entries", $index]
            )
        ) {
            $this->write(
                "CREATE INDEX `" . $index . "` ON " . $this->table("entries") . " (form_id,id)"
            );
        }
    }
    /** 扩展事务联动必须使用同一个store连接；成功通知在此事务返回后执行。 */
    public function transaction(callable $callback): mixed
    {
        return $this->db->transaction($callback);
    }

    public function forms(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM " . $this->table("forms") . " WHERE deleted=0 ORDER BY id DESC"
        );
        return array_map(fn($r) => $this->decodeForm($r), $rows);
    }
    private function decodeForm(array $r): array
    {
        $definition = json_decode($r["definition"], true, 32, JSON_THROW_ON_ERROR);
        // 旧定义中的站点分组元数据不再参与当前表单功能。
        unset($definition["web_id"]);
        $definition = CustomFormTemplates::upgradeLegacy($definition);

        return [
            "id" => (int) $r["id"],
            "revision" => (int) $r["revision"],
            "created_at" => $r["created_at"]
        ] + $definition;
    }
    public function form(int $id): array
    {
        $r = $this->db->fetchOne(
            "SELECT * FROM " . $this->table("forms") . " WHERE id=? AND deleted=0",
            [$id]
        );
        if (!$r) {
            throw new OutOfBoundsException("表单不存在");
        }
        return $this->decodeForm($r);
    }
    public function save(array $form, int $id = 0, int $revision = 0): int
    {
        $json = json_encode($form, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if ($id) {
            if (
                $this->write(
                    "UPDATE " .
                        $this->table("forms") .
                        " SET definition=?, revision=revision+1 WHERE id=? AND revision=? AND deleted=0",
                    [$json, $id, $revision]
                ) !== 1
            ) {
                throw new RuntimeException("表单已被其他请求修改，请重新打开", 409);
            }
        } else {
            $this->write(
                "INSERT INTO " . $this->table("forms") . " (definition,created_at) VALUES (?,?)",
                [$json, date("c")]
            );
            $id = (int) $this->db->getInsertId();
        }
        return $id;
    }
    /** 修复通道保留原始定义中的其他值，不通过业务扩展或定义转换。 */
    public function saveExtension(int $id, string $code, int $revision): void
    {
        $row = $this->db->fetchOne(
            "SELECT definition FROM " . $this->table("forms") . " WHERE id=? AND deleted=0",
            [$id]
        );
        if (!$row) {
            throw new OutOfBoundsException("表单不存在");
        }
        $definition = json_decode($row["definition"], true, 32, JSON_THROW_ON_ERROR);
        $definition["extension"] = $code;
        $this->save($definition, $id, $revision);
    }

    public function removeForm(int $id): bool
    {
        return $this->write(
            "UPDATE " .
                $this->table("forms") .
                " SET deleted=1,revision=revision+1 WHERE id=? AND deleted=0",
            [$id]
        ) === 1;
    }
    public function entry(int $mid, int $id): array
    {
        $r = $this->db->fetchOne(
            "SELECT * FROM " . $this->table("entries") . " WHERE id=? AND form_id=?",
            [$id, $mid]
        );
        if (!$r) {
            throw new OutOfBoundsException("记录不存在");
        }
        return $this->decodeEntry($r);
    }

    /**
     * 功能说明：批量解码列表，按表单与编号索引分页，避免逐条追加数据库查询。
     * @param int $mid 表单编号
     * @param int $page 页码，超出范围定位到最后一页
     * @param string $keyword 按字面量检索，不解释LIKE通配符
     * @param int $size 每页1至1000条
     * @param array|null $searchFields 可检索字段名；null保留原全payload检索约定
     * @return array count/rows/page/size
     */
    public function entries(
        int $mid,
        int $page = 1,
        string $keyword = "",
        int $size = 20,
        ?array $searchFields = null
    ): array {
        $size = max(1, min(1000, $size));
        $where = "form_id=?";
        $params = [$mid];
        if ($keyword !== "") {
            $pattern = "%" . str_replace(["!", "%", "_"], ["!!", "!%", "!_"], $keyword) . "%";
            if ($searchFields === null) {
                $where .= " AND payload LIKE ? ESCAPE '!'";
                $params[] = $pattern;
            } else {
                $clauses = [];
                foreach ($searchFields as $field) {
                    if (!is_string($field) || !preg_match("/\A[a-z][a-z0-9_]{0,39}\z/D", $field)) {
                        throw new InvalidArgumentException("检索字段无效");
                    }
                    $clauses[] =
                        ($this->dialect === "sqlite"
                            ? "json_extract(payload,?)"
                            : "JSON_UNQUOTE(JSON_EXTRACT(payload,?))") . " LIKE ? ESCAPE '!'";
                    $params[] = '$.' . $field;
                    $params[] = $pattern;
                }
                $where .= " AND (" . ($clauses ? implode(" OR ", $clauses) : "0=1") . ")";
            }
        }
        $count = (int) $this->db->fetchValue(
            "SELECT COUNT(*) FROM " . $this->table("entries") . " WHERE " . $where,
            $params
        );
        $page = max(1, min(max(1, (int) ceil($count / $size)), $page));
        $offset = ($page - 1) * $size;
        $rows = $this->db->fetchAll(
            "SELECT * FROM " .
                $this->table("entries") .
                " WHERE " .
                $where .
                " ORDER BY id DESC LIMIT " .
                $size .
                " OFFSET " .
                $offset,
            $params
        );
        return [
            "count" => $count,
            "rows" => array_map(fn($r) => $this->decodeEntry($r), $rows),
            "page" => $page,
            "size" => $size
        ];
    }

    /** 解码一条记录。@param array $row 原始数据库行 @return array 含data的记录 */
    private function decodeEntry(array $row): array
    {
        $row["data"] = json_decode($row["payload"], true, 32, JSON_THROW_ON_ERROR);
        unset($row["payload"]);
        return $row;
    }

    /**
     * 功能说明：事务内锁定表单，核对公开提交状态及定义版本，并按会话归属幂等写入。
     * @param int $mid 表单编号
     * @param array $data 已验证的数据
     * @param string $owner 会话归属标识
     * @param string $key 随机提交凭证
     * @param int|null $revision 可选的已验证表单版本
     * @param bool $public 是否再次检查活动状态和截止时间
     * @return int 新记录或同一归属的已有记录编号
     */
    public function addEntry(
        int $mid,
        array $data,
        string $owner,
        string $key,
        ?int $revision = null,
        bool $public = false,
        ?bool &$inserted = null
    ): int {
        return $this->db->transaction(function () use (
            $mid,
            $data,
            $owner,
            $key,
            $revision,
            $public,
            &$inserted
        ) {
            $inserted = false;
            $row = $this->db->fetchOne(
                "SELECT * FROM " .
                    $this->table("forms") .
                    " WHERE id=? AND deleted=0" .
                    ($this->dialect === "sqlite" ? "" : " FOR UPDATE"),
                [$mid]
            );
            if (!$row) {
                throw new OutOfBoundsException("表单不存在");
            }
            $form = $this->decodeForm($row);
            if ($revision !== null && $form["revision"] !== $revision) {
                throw new RuntimeException("表单已变更，请重新打开后提交", 409);
            }
            if (
                $public &&
                (!$form["active"] ||
                    (($end = CustomFormFields::deadline($form["expire"])) !== null &&
                        $end < time()))
            ) {
                throw new DomainException("表单已停用或已截止提交");
            }
            $existing = $this->db->fetchOne(
                "SELECT id,form_id,owner FROM " . $this->table("entries") . " WHERE request_key=?",
                [$key]
            );
            if ($existing) {
                if (
                    (int) $existing["form_id"] !== $mid ||
                    !hash_equals($existing["owner"], $owner)
                ) {
                    throw new DomainException("提交凭证已被其他请求使用");
                }
                return (int) $existing["id"];
            }
            $this->write(
                "INSERT INTO " .
                    $this->table("entries") .
                    " (form_id,payload,owner,created_at,request_key) VALUES (?,?,?,?,?)",
                [
                    $mid,
                    json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    $owner,
                    date("c"),
                    $key
                ]
            );
            $inserted = true;
            return (int) $this->db->getInsertId();
        });
    }
    public function editEntry(int $mid, int $id, array $data): void
    {
        $this->entry($mid, $id);
        $this->write(
            "UPDATE " . $this->table("entries") . " SET payload=? WHERE id=? AND form_id=?",
            [json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $id, $mid]
        );
    }
    public function deleteEntry(int $mid, int $id): bool
    {
        return $this->write(
            "DELETE FROM " . $this->table("entries") . " WHERE id=? AND form_id=?",
            [$id, $mid]
        );
    }
    public function markMailed(int $mid, int $id): void
    {
        $this->write(
            "UPDATE " . $this->table("entries") . " SET mailed=1 WHERE id=? AND form_id=?",
            [$id, $mid]
        );
    }

    /** 原子领取邮件发送权。@param int $mid 表单编号 @param int $id 记录编号 @return bool 是否由未发送转为发送中 */
    public function claimMail(int $mid, int $id): bool
    {
        return $this->write(
            "UPDATE " .
                $this->table("entries") .
                " SET mailed=2 WHERE id=? AND form_id=? AND mailed=0",
            [$id, $mid]
        ) === 1;
    }

    /** 发送失败或管理员核对后恢复。@param int $mid 表单编号 @param int $id 记录编号 @return void */
    public function releaseMail(int $mid, int $id): bool
    {
        return $this->write(
            "UPDATE " .
                $this->table("entries") .
                " SET mailed=0 WHERE id=? AND form_id=? AND mailed=2",
            [$id, $mid]
        ) === 1;
    }

    /** 幂等查询先于扩展写入阶段，避免重复提交重新触发写入代码。 */
    public function requestEntry(int $mid, string $owner, string $key): ?int
    {
        $row = $this->db->fetchOne(
            "SELECT id,form_id,owner FROM " . $this->table("entries") . " WHERE request_key=?",
            [$key]
        );
        if (!$row) {
            return null;
        }
        if ((int) $row["form_id"] !== $mid || !hash_equals($row["owner"], $owner)) {
            throw new DomainException("提交凭证已被其他请求使用");
        }
        return (int) $row["id"];
    }

    public function completeMail(int $mid, int $id): bool
    {
        return $this->write(
            "UPDATE " .
                $this->table("entries") .
                " SET mailed=1 WHERE id=? AND form_id=? AND mailed=2",
            [$id, $mid]
        ) === 1;
    }

    /** 整批CSV全成功才提交；返回本次创建的编号供完成事件使用。 */
    public function import(int $mid, array $rows, string $owner, array &$ids = []): int
    {
        $ids = [];
        return $this->db->transaction(function () use ($mid, $rows, $owner, &$ids) {
            foreach ($rows as $row) {
                $ids[] = $this->addEntry($mid, $row, $owner, bin2hex(random_bytes(32)));
            }
            return count($rows);
        });
    }
}
