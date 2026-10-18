<?PHP

/*******************************************
*                                          *
* Name    : Prepared Query Interface (new) *
* Modifier: MyStep Framework               *
* Time    : 2026-10-18 (PHPDoc)            *
* Email   : windy2006@gmail.com            *
* HomePage: www.mysteps.cn                 *
* Notice  : U Can Use & Modify it freely,  *
*           BUT PLEASE HOLD THIS ITEM.     *
*                                          *
*******************************************/

/**
 * 文件：lib/database/interface_db_prepared.class.php
 * 功能说明：数据库参数绑定可选协议
 *   声明 execute()、fetchAll()、fetchOne()、fetchValue() 四个参数化入口。
 *
 * 方法目录：
 *   public interface_db_prepared::execute($sql, array $params = []) — 执行原生绑定SQL，结果留给驱动读取；不使用共享结果缓存或自动重放写入。
 *   public interface_db_prepared::fetchAll($sql, array $params = [], $mode = 2) — 绑定参数并读取全部记录，结束或异常时释放当前结果与构建状态。
 *   public interface_db_prepared::fetchOne($sql, array $params = [], $mode = 2) — 绑定参数读取第一条记录；不自动改写SQL，较大结果集应自行指定分页。
 *   public interface_db_prepared::fetchValue($sql, array $params = []) — 绑定参数读取首行首列，并释放当前结果。
 *
 * 使用说明：
 *   1. 由提供原生参数绑定的驱动实现；应用先检查 myDb::supports()，再通过代理调用。
 *   2. 占位符仅用于数据值，不能绑定表名、字段名或 SQL 片段；具体形式依驱动约定。
 *   3. 以 === false 判断执行失败；受影响行数为 0 可能表示成功。
 *
 * 说明：目录列出本文件直接声明的全部方法；参数、返回值及异常详见各方法 PHPDoc。
 *       原生签名保持 PHP 7.4 兼容，PHPDoc 类型仅用于说明。
 */
/**
 * 功能说明：为 SQL 驱动提供可选的原生参数绑定和统一结果读取契约。
 *
 * 该协议独立于旧管理接口，不强制文件数据库实现 SQL 功能。
 * 实现接口表示提供对应 API，不证明数据库已连接、扩展已安装或 SQL 可跨库运行。
 * 参数形式、数据类型和错误策略以具体驱动说明为准。
 *
 * 方法目录：参见本文件顶部。
 */
interface interface_db_prepared {
    /**
     * 功能说明：执行原生绑定SQL，结果留给驱动读取；不使用共享结果缓存或自动重放写入。
     *
     * @param string $sql 非空SQL模板；MySQL/MSSQL用?，SQLite还支持命名参数
     * @param array $params 数据值列表/映射，流、NULL及描述数组按驱动规则处理（默认：[]）
     * @return int|false 行数或影响数；SQLite读查询为0，MSSQL未知行数可为-1；用===false判失败
     * @throws InvalidArgumentException SQL或绑定参数不合法
     * @throws Throwable 原生扩展、查询和框架错误策略的异常继续向外传递
     */
    public function execute($sql, array $params = []);

    /**
     * 功能说明：绑定参数并读取全部记录，结束或异常时释放当前结果与构建状态。
     *
     * @param string $sql 原生SQL模板，参数形式依驱动决定
     * @param array $params 绑定的数据值，不能替代标识符（默认：[]）
     * @param int $mode 1数字下标、2字段名（默认）、3两者（默认：2）
     * @return array|false 空结果为[]，执行失败为false；字段保留原生类型
     * @throws Throwable 参数、执行或结果读取异常继续向外传递
     */
    public function fetchAll($sql, array $params = [], $mode = 2);

    /**
     * 功能说明：绑定参数读取第一条记录；不自动改写SQL，较大结果集应自行指定分页。
     *
     * @param string $sql 原生SQL模板
     * @param array $params 绑定的数据值（默认：[]）
     * @param int $mode 1数字下标、2字段名（默认）、3两者（默认：2）
     * @return array|false 首条记录；无记录或执行失败为false
     * @throws Throwable 参数、执行或结果读取异常继续向外传递
     */
    public function fetchOne($sql, array $params = [], $mode = 2);

    /**
     * 功能说明：绑定参数读取首行首列，并释放当前结果。
     *
     * @param string $sql 原生SQL模板
     * @param array $params 绑定的数据值（默认：[]）
     * @return mixed 原生标量（保留NULL、0、空字符串）；无记录或失败为false
     * @throws Throwable 参数、执行或结果读取异常继续向外传递
     */
    public function fetchValue($sql, array $params = []);
}
