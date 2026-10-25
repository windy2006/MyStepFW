<?php
/*******************************************
* Name    : Redis Plugin (new)             *
* Modifier: MyStep Framework               *
* Time    : 2026-10-25                     *
* Notice  : U Can Use & Modify it freely,  *
*           BUT PLEASE HOLD THIS ITEM.     *
*******************************************/
/**
 * 文件：plugin/redis/class.php
 * 功能说明：Redis插件生命周期、早期驱动加载、配置读取及停用保护。
 * 方法目录：
 *   public static plugin_redis::check(&$result = '') — 检查PHP扩展及版本，不在插件发现时建立连接。
 *   public static plugin_redis::load() — 加载连接、缓存、Session驱动；不替换全局缓存。
 *   public static plugin_redis::options() — 从插件配置读取并验证连接参数。
 *   public static plugin_redis::validateConfig(myConfig $config) — 保存插件设置前检查格式和实际连接。
 *   public static plugin_redis::beforeDisable() — 检查框架和应用是否仍选择Redis存储。
 *   public static plugin_redis::install() — 提供安装入口，不修改全局存储选择或绑定。
 *   public static plugin_redis::uninstall() — 使用中拒绝卸载；不删除服务端数据。
 * 使用说明：主类由插件管理或myStep::storagePlugin加载；全局存储显式选择生效，不依赖普通入口与应用绑定。
 */
/**
 * 功能说明：封装Redis驱动及配置，避免普通插件入口晚于缓存/Session初始化。
 * 方法目录：参见文件头；PHPDoc说明类型，源码兼容PHP7.4。
 */
class plugin_redis implements interface_plugin {
    /**
     * 功能说明：检查phpredis扩展版本，返回可展示的环境诊断。
     * @param string &$result 引用输出检查说明（默认：''）
     * @return bool phpredis版本至少5.3为true；服务连通性在保存设置时检查
     */
    public static function check(&$result = '') {
        $ok=extension_loaded('redis') && class_exists('Redis',false) && version_compare((string)phpversion('redis'),'5.3','>=');
        $result=$ok?'phpredis扩展检查通过；保存设置时将验证Redis服务连接。':'Redis需要PHP的phpredis扩展5.3或以上，请在当前网站使用的PHP环境启用。';
        return $ok;
    }
    /**
     * 功能说明：加载插件拥有的三个驱动声明，不连接服务器、不替换现有实例。
     * 参数说明：无显式参数。
     * @return void 无返回值；require_once保证同请求只声明一次
     */
    public static function load() {
        require_once __DIR__.'/myRedis.class.php';
        require_once __DIR__.'/myCache_Redis.class.php';
        require_once __DIR__.'/sess_redis.class.php';
    }
    /**
     * 功能说明：读取插件私有配置并补齐、规范参数，不建立连接。
     * 参数说明：无显式参数。
     * @return array myRedis已验证的连接与命名空间参数
     * @throws InvalidArgumentException 配置文件缺失或参数不合法
     */
    public static function options() {
        self::load();
        if(!is_file(__DIR__.'/config.php'))throw new InvalidArgumentException('Redis插件配置缺失，请恢复plugin/redis/config.php。');
        return myRedis::settings((new myConfig(__DIR__.'/config.php'))->get(true));
    }
    /**
     * 功能说明：发布插件设置前规范值并验证扩展、连接、认证、选库和PING。
     * @param myConfig $config 待保存的Redis插件配置，验证失败不写文件
     * @return void 只规范传入对象，不保存文件、不切换活动会话
     * @throws InvalidArgumentException 配置或实际连接检查失败，消息不包含密码
     */
    public static function validateConfig(myConfig $config) {
        $result='';if(!self::check($result))throw new InvalidArgumentException($result);
        self::load();myRedis::configure($config,true);
    }
    /**
     * 功能说明：拒绝停用仍被框架或任一应用选用的Redis存储，避免下一请求登录失败。
     * 参数说明：无显式参数。
     * @return void 无引用时允许停用；不修改存储选择或清理Redis数据
     * @throws InvalidArgumentException 有配置选择Redis，提示先切换缓存和Session
     */
    public static function beforeDisable() {
        $files=array_merge([CONFIG.'config.php'],glob(APP.'*/config.php')?:[]);
        foreach($files as $file){
            $config=new myConfig($file);
            if(strtolower((string)$config->value('gen.cache_mode',''))==='redis' || $config->value('session.mode','')==='sess_redis')
                throw new InvalidArgumentException('Redis仍被框架或应用配置使用，请先在参数设置中切换缓存和Session，再停用或卸载插件。');
        }
    }
    /**
     * 功能说明：插件安装生命周期入口；实际配置已由后台验证保存。
     * 参数说明：无显式参数。
     * @return bool 环境满足为true；不自动更改全局存储、注册优先脚本或清空数据
     * @throws InvalidArgumentException 当前PHP扩展不可用
     */
    public static function install() {
        $result='';if(!self::check($result))throw new InvalidArgumentException($result);return true;
    }
    /**
     * 功能说明：卸载前检查存储引用，保留插件配置、Redis缓存和会话数据。
     * 参数说明：无显式参数。
     * @return bool 没有存储引用时允许卸载
     * @throws InvalidArgumentException 框架或应用仍使用Redis存储
     */
    public static function uninstall() {self::beforeDisable();return true;}
}
