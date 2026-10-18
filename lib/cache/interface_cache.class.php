<?PHP

/**
 * 文件：lib/cache/interface_cache.class.php
 * 功能说明：缓存驱动的基础兼容协议
 *   声明set/get/remove/clean四个历史入口，并加载精确命中和计算锁可选接口。
 *
 * 方法目录：
 *   public interface_cache::set($key, $value, $ttl) — 保存可序列化值；内置驱动省略第二参数时沿用删除约定。
 *   public interface_cache::get($key) — 读取有效值。
 *   public interface_cache::remove($key) — 删除单个键。
 *   public interface_cache::clean() — 显式清理；内置驱动只清理过期/损坏条目，第三方含义须查其实现。
 *
 * 使用说明：
 *   1. 供内置与第三方缓存驱动实现；应用通常通过myCache代理调用。
 *   2. get返回false可能表示未命中或有效false；需要精确区分时使用可选fetch协议。
 *   3. clean沿用驱动语义，内置实现用于过期回收；全量清空使用显式clear入口。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名保持PHP 7.4兼容，PHPDoc类型用于说明，不额外强制运行时类型。
 */
/**
 * 功能说明：声明set/get/remove/clean四个历史入口，并加载精确命中和计算锁可选接口。
 *
 * 使用说明：
 *   1. 供内置与第三方缓存驱动实现；应用通常通过myCache代理调用。
 *   2. get返回false可能表示未命中或有效false；需要精确区分时使用可选fetch协议。
 *   3. clean沿用驱动语义，内置实现用于过期回收；全量清空使用显式clear入口。
 *
 * 补充说明：
 * 历史缓存协议，保持原四个方法及必需参数签名，兼容第三方适配器。
 * 内置驱动TTL为秒，非正数删除；clean只回收过期数据，不代表flush。
 * 第三方get返回false仍可能表示有效值，精确命中检查采用可选lookup接口。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 */
interface interface_cache {
    /**
     * 功能说明：保存可序列化值；内置驱动省略第二参数时沿用删除约定。
     *
     * @param string|int $key 缓存键，内置驱动也支持其他有限标量及null的字符串化
     * @param mixed $value 缓存值，显式false/null/空字符串均有效
     * @param int|float $ttl 有效秒数，正的小数向上取整，非正数删除
     * @return bool 保存或删除是否成功；无缓存保证时调用者仍须能直接获取业务数据
     */
    public function set($key, $value, $ttl);
    /**
     * 功能说明：读取有效值。
     *
     * @param string|int $key 缓存键
     * @return mixed 不存在/过期/故障为false，旧协议不能区分有效false
     */
    public function get($key);
    /**
     * 功能说明：删除单个键。
     *
     * @param string|int $key 缓存键
     * @return bool 键原本不存在也视为成功
     */
    public function remove($key);
    /**
     * 功能说明：显式清理；内置驱动只清理过期/损坏条目，第三方含义须查其实现。
     *
     * 参数说明：无显式参数。
     *
     * @return bool 是否成功；框架不自动把本方法当成GC调用
     */
    public function clean();
}
require_once __DIR__.'/interface_cache_lookup.class.php';
require_once __DIR__.'/interface_cache_lock.class.php';
