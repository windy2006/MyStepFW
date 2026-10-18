<?PHP

/*******************************************
*                                          *
* Name    : Cache Lock Interface (new)     *
* Modifier: MyStep Framework               *
* Time    : 2026-10-18 (PHPDoc)            *
* Email   : windy2006@gmail.com            *
* HomePage: www.mysteps.cn                 *
* Notice  : U Can Use & Modify it freely,  *
*           BUT PLEASE HOLD THIS ITEM.     *
*                                          *
*******************************************/

/**
 * 文件：lib/cache/interface_cache_lock.class.php
 * 功能说明：缓存计算互斥可选协议
 *   声明按逻辑键串行执行计算回调的跨进程锁能力。
 *
 * 方法目录：
 *   public interface_cache_lock::synchronized($key, callable $operation, $timeout = 5) — 在同一后端的逻辑键锁内执行回调，finally释放；锁失败时不调用回调。
 *
 * 使用说明：
 *   1. 由支持计算锁的驱动实现，通过 myCache::supports() 检查能力。
 *   2. 直接调用 synchronized()，或通过 myCache::rememberLocked() 使用；锁失败不执行回调。
 *   3. 计算锁减少重复计算，不保证业务副作用恰好执行一次。
 *
 * 说明：目录列出本文件直接声明的全部方法；参数、返回值及异常详见各方法 PHPDoc。
 *       原生签名保持 PHP 7.4 兼容，PHPDoc 类型仅用于说明。
 */
/**
 * 功能说明：为缓存驱动提供可选的跨进程计算互斥契约。
 *
 * 不扩展基础缓存接口；调用方应先检查能力再使用。
 * 锁范围和重入规则由具体后端决定，不能替代会话锁或业务事务。
 *
 * 方法目录：参见本文件顶部。
 */
interface interface_cache_lock {
    /**
     * 功能说明：在同一后端的逻辑键锁内执行回调，finally释放；锁失败时不调用回调。
     *
     * @param string|int $key 锁键，不能与会话/业务事务锁互相替代
     * @param callable $operation 无参数计算回调
     * @param int|float $timeout 最长等待秒数，0为非阻塞，范围0到60（默认：5）
     * @return mixed 回调原始结果
     * @throws RuntimeException 后端不可用或等待超时
     * @throws LogicException 不允许重入的锁被同一适配器递归请求
     * @throws Throwable 回调异常原样传递，锁仍被释放
     * @template TResult
     * @phpstan-param callable(): TResult $operation
     * @phpstan-return TResult
     */
    public function synchronized($key, callable $operation, $timeout = 5);
}
