<?PHP

/**
 * 文件：plugin/memcached/class.php
 * 功能说明：提供 Memcached 环境检查和生命周期入口的缓存插件主类。
 *
 * 方法目录：
 *   public static plugin_memcached::check(&$result = '') — 检查 Memcached 客户端类是否可用，并返回可读的扩展检查说明。
 *   public static plugin_memcached::install() — 提供缓存插件的安装生命周期入口，当前不建立持久资源。
 *   public static plugin_memcached::uninstall() — 提供缓存插件的卸载生命周期入口，当前不清空服务端缓存。
 *
 * 使用说明：
 *   1. 显式加载主类并由管理流程调用 check/install/uninstall；业务方法的使用见方法目录。
 *   2. 缓存驱动与配置由 index.php 明确加载，本类不建立数据库表或管理页面。
 *   3. 检查只验证客户端类存在，不证明远端服务可达；卸载本类不 flush 共享服务。
 *
 * 说明：PHPDoc 类型用于说明，不增加原生类型约束；源码保持 PHP 7.4 兼容。
 *       方法目录覆盖本文件直接声明；闭包参数及捕获变量说明见各回调旁 PHPDoc。
 */

/**
 * 功能说明：提供 Memcached 环境检查和生命周期入口的缓存插件主类。
 *
 * 使用说明：
 *   1. 缓存驱动与配置由 index.php 明确加载，本类不建立数据库表或管理页面。
 *   2. 检查只验证客户端类存在，不证明远端服务可达；卸载本类不 flush 共享服务。
 *
 * 方法目录：参见本文件顶部；仅列直接声明，不重复列出未在本文件声明的继承或协议方法。
 */
class plugin_memcached implements interface_plugin {
    /**
     * 功能说明：检查 Memcached 客户端类是否可用，并返回可读的扩展检查说明。
     *
     * @param string &$result 引用输出：供后台展示的插件安装条件检查说明（默认：''）
     * @return bool 当前基础检查是否通过；不代表远端服务或业务数据库可用
     */
    public static function check(&$result = '') {
        $available = class_exists('Memcached');
        $result = $available ? 'OK!' : 'Memcached extension is unavailable';
        return $available;
    }
    /**
     * 功能说明：提供缓存插件的安装生命周期入口，当前不建立持久资源。
     *
     * 参数说明：无显式参数。
     *
     * @return bool 当前空生命周期入口返回 true，不清理共享服务
     */
    public static function install() {
        return true;
    }
    /**
     * 功能说明：提供缓存插件的卸载生命周期入口，当前不清空服务端缓存。
     *
     * 参数说明：无显式参数。
     *
     * @return bool 当前空生命周期入口返回 true，不清理共享服务
     */
    public static function uninstall() {
        return true;
    }
}