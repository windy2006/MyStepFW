<?PHP

/*******************************************
*                                          *
* Name    : Cache Lookup Interface (new)   *
* Modifier: MyStep Framework               *
* Time    : 2026-10-18 (PHPDoc)            *
* Email   : windy2006@gmail.com            *
* HomePage: www.mysteps.cn                 *
* Notice  : U Can Use & Modify it freely,  *
*           BUT PLEASE HOLD THIS ITEM.     *
*                                          *
*******************************************/

/**
 * 文件：lib/cache/interface_cache_lookup.class.php
 * 功能说明：缓存精确命中可选协议
 *   通过 fetch() 同时返回缓存值和引用命中状态，区分有效 false、null 与未命中。
 *
 * 方法目录：
 *   public interface_cache_lookup::fetch($key, &$hit) — 一次读取同时取得值及命中状态，避免先has后get的并发窗口。
 *
 * 使用说明：
 *   1. 由支持精确命中的驱动实现，通过 myCache::supports() 检查能力。
 *   2. 使用 $value = $cache->fetch($key, $hit)，以 $hit 判断命中。
 *   3. 保留旧 get() 协议，第三方缓存驱动按需实现此接口。
 *
 * 说明：目录列出本文件直接声明的全部方法；参数、返回值及异常详见各方法 PHPDoc。
 *       原生签名保持 PHP 7.4 兼容，PHPDoc 类型仅用于说明。
 */
/**
 * 功能说明：为缓存读取提供独立的引用命中状态。
 *
 * fetch() 在一次读取中取得值和命中标志，避免先判断存在再读取的并发窗口。
 * 该接口不改变历史 get() 的返回约定。
 *
 * 方法目录：参见本文件顶部。
 */
interface interface_cache_lookup {
    /**
     * 功能说明：一次读取同时取得值及命中状态，避免先has后get的并发窗口。
     *
     * @param string|int $key 缓存键
     * @param bool &$hit 引用参数；有效记录为true，不存在/过期/损坏/后端失败为false
     * @return mixed 缓存值，含false和null；未命中返回false
     */
    public function fetch($key, &$hit);
}
