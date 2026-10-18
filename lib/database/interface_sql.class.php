<?PHP

/*******************************************
*                                          *
* Name    : SQL Management Interface (new) *
* Modifier: MyStep Framework               *
* Time    : 2026-10-18 (PHPDoc)            *
* Email   : windy2006@gmail.com            *
* HomePage: www.mysteps.cn                 *
* Notice  : U Can Use & Modify it freely,  *
*           BUT PLEASE HOLD THIS ITEM.     *
*                                          *
*******************************************/

/**
 * 文件：lib/database/interface_sql.class.php
 * 功能说明：SQL 数据库管理兼容协议
 *   独立声明原 interface_db 文件中的 SQL 管理接口，保留历史方法签名。
 *
 * 方法目录：
 *   public interface_sql::getRS() — 从当前结果读取下一行；调用方负责消费完成后按驱动约定释放结果。
 *   public interface_sql::getDBs() — 列出当前账号可见的数据库，筛选规则和权限以驱动及服务器为准。
 *   public interface_sql::getTbls() — 列出数据表；可选库名、过滤和详细信息参数由具体驱动扩展。
 *   public interface_sql::getPri($the_tbl) — 获取历史单字段键标识；部分驱动无主键时会回退唯一键/自增列。
 *   public interface_sql::getInsertId() — 读取最近插入的自增标识，取值范围与连接/触发器语义依驱动而定。
 *   public interface_sql::getStat() — 获取服务器状态信息；不能作为通用健康检查或事务能力证明。
 *   public interface_sql::getCreateScript($the_tbl) — 导出建表脚本；不同方言输出不可直接跨引擎回放。
 *   public interface_sql::getDataScript($the_tbl) — 将表数据导出为完整SQL字符串，不提供跨表一致性快照或流式备份保证。
 *   public interface_sql::getIdxScript($the_tbl) — 导出独立索引定义；MySQL为建表片段，MSSQL为CREATE INDEX语句。
 *   public interface_sql::batchExe($SQLs) — 顺序执行一条或多条可信SQL；不自动创建事务，失败策略由驱动控制。
 *   public interface_sql::handleSQL($strSQL) — 按驱动方言切分可信脚本；不执行、不做安全过滤或完整语法校验。
 *   public interface_sql::file($file) — 读取并执行可信本地SQL文件；内置驱动可附加find/replace替换参数。
 *   public interface_sql::check($obj) — 检查驱动内部连接/结果属性；仅检查对象或资源形态，不发起远程探活。
 *   public interface_sql::setCache($cache, $ttl) — 挂接共享查询缓存；事务隔离、SQL资格及失效传播仍由驱动负责。
 *
 * 使用说明：
 *   1. 由完整提供该协议的驱动实现，通过 myDb 调用库表管理、脚本导入导出等入口。
 *   2. interface_db.class.php 仍会加载此接口，兼容历史包含方式。
 *   3. 不同驱动的 SQL 方言和管理能力不同；参数绑定和受管事务另由可选接口声明。
 *
 * 说明：目录列出本文件直接声明的全部方法；参数、返回值及异常详见各方法 PHPDoc。
 *       原生签名保持 PHP 7.4 兼容，PHPDoc 类型仅用于说明。
 */
/**
 * 功能说明：保留历史 SQL 结果读取、数据库管理及脚本维护协议。
 *
 * 本文件从 interface_db.class.php 拆出，(new) 标记表示新增文件，接口本身原已存在。
 * 协议与 interface_db 独立；SQLite 未完整提供这些管理入口，不声明实现。
 * 不能据此推断事务、原生扩展安装状态或连接健康。
 *
 * 方法目录：参见本文件顶部。
 */
interface interface_sql {
    /**
     * 功能说明：从当前结果读取下一行；调用方负责消费完成后按驱动约定释放结果。
     *
     * 内置驱动额外支持mode：1数字下标、2字段名、3两者，默认2。
     *
     * 参数说明：无显式参数。
     *
     * @return array|false|null 当前记录或结果结束/失败标记，依驱动约定
     */
    public function getRS();

    /**
     * 功能说明：列出当前账号可见的数据库，筛选规则和权限以驱动及服务器为准。
     *
     * 参数说明：无显式参数。
     *
     * @return array 数据库名称列表
     * @throws Throwable 查询异常依驱动策略处理
     */
    public function getDBs();

    /**
     * 功能说明：列出数据表；可选库名、过滤和详细信息参数由具体驱动扩展。
     *
     * 参数说明：无显式参数。
     *
     * @return array 表名列表或详细信息列表
     * @throws Throwable 查询异常依驱动策略处理
     */
    public function getTbls();

    /**
     * 功能说明：获取历史单字段键标识；部分驱动无主键时会回退唯一键/自增列。
     *
     * @param string $the_tbl 表名；不保证完整表达复合主键
     * @return string|false 键字段名或未找到标记，依驱动约定
     */
    public function getPri($the_tbl);

    /**
     * 功能说明：读取最近插入的自增标识，取值范围与连接/触发器语义依驱动而定。
     *
     * 参数说明：无显式参数。
     *
     * @return int|string|false 标识值，0或false是否表示未插入须按驱动区分
     */
    public function getInsertId();

    /**
     * 功能说明：获取服务器状态信息；不能作为通用健康检查或事务能力证明。
     *
     * 参数说明：无显式参数。
     *
     * @return array|string 状态映射，未连接时内置驱动返回空字符串
     */
    public function getStat();

    /**
     * 功能说明：导出建表脚本；不同方言输出不可直接跨引擎回放。
     *
     * @param string $the_tbl 表名，附加数据库参数由具体驱动提供
     * @return string 建表SQL；是否含独立索引/约束依驱动说明
     */
    public function getCreateScript($the_tbl);

    /**
     * 功能说明：将表数据导出为完整SQL字符串，不提供跨表一致性快照或流式备份保证。
     *
     * @param string $the_tbl 表名
     * @return string INSERT脚本，空表可为空字符串
     */
    public function getDataScript($the_tbl);

    /**
     * 功能说明：导出独立索引定义；MySQL为建表片段，MSSQL为CREATE INDEX语句。
     *
     * @param string $the_tbl 表名
     * @return array 脚本/片段列表，不能假设每项都能单独执行
     */
    public function getIdxScript($the_tbl);

    /**
     * 功能说明：顺序执行一条或多条可信SQL；不自动创建事务，失败策略由驱动控制。
     *
     * @param string|array $SQLs SQL文本或按顺序执行的SQL列表
     * @return array|false 每项为[原SQL,执行结果]；空批次为[]，未连接为false
     * @throws Throwable 执行异常可中断后续语句，已完成部分不自动回滚
     */
    public function batchExe($SQLs);

    /**
     * 功能说明：按驱动方言切分可信脚本；不执行、不做安全过滤或完整语法校验。
     *
     * @param string $strSQL 脚本内容；DELIMITER等支持范围见驱动说明
     * @return array 可执行语句列表，空脚本为[]
     */
    public function handleSQL($strSQL);

    /**
     * 功能说明：读取并执行可信本地SQL文件；内置驱动可附加find/replace替换参数。
     *
     * @param string $file SQL文件路径，不应直接采用外部请求提供的路径
     * @return array|false 逐句执行结果，文件缺失/未连接时返回false
     * @throws Throwable 文件或SQL执行失败依具体驱动策略处理
     */
    public function file($file);

    /**
     * 功能说明：检查驱动内部连接/结果属性；仅检查对象或资源形态，不发起远程探活。
     *
     * @param string $obj 内置驱动通常为connect或result，类型检测可选参数依驱动决定
     * @return bool 是否具有所需内部对象/资源
     */
    public function check($obj);

    /**
     * 功能说明：挂接共享查询缓存；事务隔离、SQL资格及失效传播仍由驱动负责。
     *
     * @param interface_cache|myCache|null $cache 提供get/set的缓存后端，null关闭挂接
     * @param int $ttl 缓存有效秒数，内置驱动可省略并使用600
     * @return void 无返回值。
     */
    public function setCache($cache, $ttl);
}
