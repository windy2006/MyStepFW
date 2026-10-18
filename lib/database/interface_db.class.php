<?PHP

/**
 * 文件：lib/database/interface_db.class.php
 * 功能说明：数据库历史基础兼容协议
 *   声明连接、CRUD、结果读取及字段查询的基础入口，并加载SQL与可选能力接口。
 *
 * 方法目录：
 *   public interface_db::connect() — 建立或检查连接；连接参数由构造/init及具体驱动的可选参数决定。
 *   public interface_db::selectDB($the_db) — 选择数据库或切换本地文件；不保证不同驱动的连接状态切换过程相同。
 *   public interface_db::insert() — 插入记录；SQL驱动使用build/field/values状态，simpleDB通过额外参数接收记录。
 *   public interface_db::select() — 查询当前表；SQL驱动执行/预览构建的SELECT，simpleDB返回筛选记录。
 *   public interface_db::update() — 更新记录；SQL驱动使用当前构建条件，simpleDB额外接收条件和字段数据。
 *   public interface_db::delete() — 删除匹配记录；空条件、事务和预览行为以具体驱动说明为准。
 *   public interface_db::query($sql) — 执行历史查询入口；不提供跨驱动参数绑定或统一行数语义。
 *   public interface_db::record($sql) — 读取单条记录；SQL驱动可用当前构建器，simpleDB按行号读取。
 *   public interface_db::records($sql) — 读取全部记录；SQL执行查询，simpleDB读取当前文件的全部数据。
 *   public interface_db::result($sql) — 读取单个字段；SQL驱动取第一行第一列，simpleDB使用条件及可选字段参数。
 *   public interface_db::getFields($the_tbl) — 获取表结构；SQL驱动返回字段名称，simpleDB返回字段宽度及记录统计元数据。
 *   public interface_db::close() — 释放结果及连接/文件句柄；各驱动可以额外接收可选错误输出参数。
 *
 * 使用说明：
 *   1. 由MySQL/MSSQL/SQLite/simpleDB实现，保留旧方法签名。
 *   2. SQL驱动和文件驱动对部分参数/返回值的解释不同，应查阅具体实现。
 *   3. 参数绑定、受管事务等能力通过独立接口及myDb::supports()检查。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名保持PHP 7.4兼容，PHPDoc类型用于说明，不额外强制运行时类型。
 */
/**
 * 功能说明：声明连接、CRUD、结果读取及字段查询的基础入口，并加载SQL与可选能力接口。
 *
 * 使用说明：
 *   1. 由MySQL/MSSQL/SQLite/simpleDB实现，保留旧方法签名。
 *   2. SQL驱动和文件驱动对部分参数/返回值的解释不同，应查阅具体实现。
 *   3. 参数绑定、受管事务等能力通过独立接口及myDb::supports()检查。
 *
 * 补充说明：
 * 兼容SQL驱动和simpleDB的历史数据库入口协议。
 *
 * 保留原方法、参数个数及无类型签名，允许已有第三方驱动继续实现；这不是统一的
 * SQL语义协议：simpleDB使用文件、条件数组及行号，SQL驱动使用连接与构建器。
 * 驱动可提供更多可选参数，具体格式见其方法说明。参数化查询和受管事务分别由
 * interface_db_prepared、interface_db_transaction表达，不强加给所有旧驱动。
 * 历史上载入本文件会同时定义interface_sql，该加载方式继续有效。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 */
interface interface_db {
    /**
     * 功能说明：建立或检查连接；连接参数由构造/init及具体驱动的可选参数决定。
     *
     * 参数说明：无显式参数。
     *
     * @return bool|object 内置MySQL/SQLite/simpleDB返回bool，MSSQL成功返回自身
     * @throws Throwable 原生扩展、连接或框架错误策略的异常继续传递
     */
    public function connect();

    /**
     * 功能说明：选择数据库或切换本地文件；不保证不同驱动的连接状态切换过程相同。
     *
     * @param string $the_db SQL数据库名；SQLite文件路径；simpleDB表名
     * @return bool|object 是否成功，或驱动自身，依实现约定
     * @throws Throwable 参数校验或存储失败时依驱动错误策略处理
     */
    public function selectDB($the_db);

    /**
     * 功能说明：插入记录；SQL驱动使用build/field/values状态，simpleDB通过额外参数接收记录。
     *
     * 参数说明：无显式参数。
     *
     * @return int|bool|string 执行结果；SQL驱动的可选预览模式返回SQL文本
     * @throws Throwable 参数及执行异常依具体驱动处理
     */
    public function insert();

    /**
     * 功能说明：查询当前表；SQL驱动执行/预览构建的SELECT，simpleDB返回筛选记录。
     *
     * 参数说明：无显式参数。
     *
     * @return int|array|false|string 行数、记录、失败或预览SQL，依驱动及模式决定
     * @throws Throwable 参数及执行异常依具体驱动处理
     */
    public function select();

    /**
     * 功能说明：更新记录；SQL驱动使用当前构建条件，simpleDB额外接收条件和字段数据。
     *
     * 参数说明：无显式参数。
     *
     * @return int|bool|string 受影响数、匹配结果或预览SQL；0不等于失败
     * @throws Throwable 参数及执行异常依具体驱动处理
     */
    public function update();

    /**
     * 功能说明：删除匹配记录；空条件、事务和预览行为以具体驱动说明为准。
     *
     * 参数说明：无显式参数。
     *
     * @return int|bool|string 执行结果或预览SQL；空条件可能删除整表数据
     * @throws Throwable 参数及执行异常依具体驱动处理
     */
    public function delete();

    /**
     * 功能说明：执行历史查询入口；不提供跨驱动参数绑定或统一行数语义。
     *
     * @param string|array $sql SQL驱动接收可信SQL；simpleDB接收条件字符串/数组
     * @return int|array|false SQL行数/影响数或文件记录；SQLite读查询成功为0
     * @throws Throwable SQL、条件或存储异常依驱动策略处理
     */
    public function query($sql);

    /**
     * 功能说明：读取单条记录；SQL驱动可用当前构建器，simpleDB按行号读取。
     *
     * @param string|int $sql SQL或表名；simpleDB为行号，0取首行
     * @return array|false 一条记录，无记录或失败为false
     * @throws Throwable 查询及存储异常依驱动策略处理
     */
    public function record($sql);

    /**
     * 功能说明：读取全部记录；SQL执行查询，simpleDB读取当前文件的全部数据。
     *
     * @param string $sql SQL或表名；simpleDB仅保留该兼容占位参数
     * @return array|false 空结果为数组，失败为false
     * @throws Throwable 查询及存储异常依驱动策略处理
     */
    public function records($sql);

    /**
     * 功能说明：读取单个字段；SQL驱动取第一行第一列，simpleDB使用条件及可选字段参数。
     *
     * @param string|array $sql SQL或文件筛选条件
     * @return mixed 保留字段类型；SQL无记录为false，simpleDB无记录为空字符串
     * @throws Throwable 查询及存储异常依驱动策略处理
     */
    public function result($sql);

    /**
     * 功能说明：获取表结构；SQL驱动返回字段名称，simpleDB返回字段宽度及记录统计元数据。
     *
     * @param string $the_tbl SQL表名；simpleDB忽略本参数并使用当前文件
     * @return array 具体字段结构依驱动约定，不承诺统一列元数据格式
     * @throws Throwable 表查询或文件读取异常依驱动策略处理
     */
    public function getFields($the_tbl);

    /**
     * 功能说明：释放结果及连接/文件句柄；各驱动可以额外接收可选错误输出参数。
     *
     * 参数说明：无显式参数。
     *
     * @return int|null 内置SQL驱动返回累计执行次数，simpleDB无返回值
     */
    public function close();
}

// 保留旧“单文件载入数据库协议”的用法；每个接口也有独立的自动加载入口。
require_once __DIR__.'/interface_sql.class.php';
require_once __DIR__.'/interface_db_prepared.class.php';
require_once __DIR__.'/interface_db_transaction.class.php';
