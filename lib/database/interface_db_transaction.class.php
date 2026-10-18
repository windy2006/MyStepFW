<?PHP

/*******************************************
*                                          *
* Name    : Transaction Interface (new)    *
* Modifier: MyStep Framework               *
* Time    : 2026-10-18 (PHPDoc)            *
* Email   : windy2006@gmail.com            *
* HomePage: www.mysteps.cn                 *
* Notice  : U Can Use & Modify it freely,  *
*           BUT PLEASE HOLD THIS ITEM.     *
*                                          *
*******************************************/

/**
 * 文件：lib/database/interface_db_transaction.class.php
 * 功能说明：数据库受管事务可选协议
 *   声明事务边界、回调事务及嵌套保存点能力。
 *
 * 方法目录：
 *   public interface_db_transaction::begin() — 开始一层受管事务，嵌套层使用保存点。
 *   public interface_db_transaction::commit() — 提交一层；内层释放保存点，最外层提交并发布缓存失效。
 *   public interface_db_transaction::rollback() — 回滚一层；内层回滚并释放保存点，最外层回滚事务。
 *   public interface_db_transaction::transaction(callable $callback) — 执行受管回调；正常返回（包括false）提交，异常回滚并继续抛出。
 *
 * 使用说明：
 *   1. 由支持受管事务的驱动实现，通过 myDb::supports() 判断能力。
 *   2. transaction() 回调正常返回时提交，包括返回 false；抛出异常时回滚。
 *   3. 事务只覆盖数据库操作，不回滚文件、邮件等外部副作用。
 *
 * 说明：目录列出本文件直接声明的全部方法；参数、返回值及异常详见各方法 PHPDoc。
 *       原生签名保持 PHP 7.4 兼容，PHPDoc 类型仅用于说明。
 */
/**
 * 功能说明：为数据库驱动提供可选的受管事务和嵌套保存点契约。
 *
 * 目前由 MySQL 和 SQLite 声明实现；不能仅凭存在 begin() 方法推断事务能力。
 * MySQL 表是否支持事务取决于存储引擎；本协议不接管已有原生 SQL 事务。
 * 受管回调内不得自行提交事务或执行会导致隐式提交的 DDL。
 *
 * 方法目录：参见本文件顶部。
 */
interface interface_db_transaction {
    /**
     * 功能说明：开始一层受管事务，嵌套层使用保存点。
     *
     * 参数说明：无显式参数。
     *
     * @return bool 开启成功为true，驱动执行失败可为false
     * @throws LogicException 已有外部事务、边界已被打断或回调内手动修改边界
     * @throws Throwable 数据库执行异常继续传递
     */
    public function begin();

    /**
     * 功能说明：提交一层；内层释放保存点，最外层提交并发布缓存失效。
     *
     * 参数说明：无显式参数。
     *
     * @return bool 成功为true，驱动执行失败可为false
     * @throws LogicException 没有有效受管事务或调用试图改变回调拥有的边界
     * @throws Throwable 数据库执行异常继续传递
     */
    public function commit();

    /**
     * 功能说明：回滚一层；内层回滚并释放保存点，最外层回滚事务。
     *
     * 参数说明：无显式参数。
     *
     * @return bool 成功为true，驱动执行失败可为false
     * @throws LogicException 没有有效受管事务或调用试图改变回调拥有的边界
     * @throws Throwable 数据库执行异常继续传递
     */
    public function rollback();

    /**
     * 功能说明：执行受管回调；正常返回（包括false）提交，异常回滚并继续抛出。
     *
     * @param callable $callback 接收当前驱动的回调；代理入口则接收当前myDb
     * @return mixed 回调的原始返回值
     * @throws Throwable 回调、事务边界或数据库操作异常继续向外传递
     * @template TResult
     * @phpstan-param callable(object): TResult $callback
     * @phpstan-return TResult
     */
    public function transaction(callable $callback);
}
