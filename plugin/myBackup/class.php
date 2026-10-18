<?PHP

/**
 * 文件：plugin/myBackup/class.php
 * 功能说明：提供独立数据库备份及表维护页面的插件。
 *
 * 方法目录：
 *   public static plugin_myBackup::check(&$result = '') — 返回数据库备份插件的基础安装检查结果。
 *   public static plugin_myBackup::install() — 登记数据库备份管理路由和后台菜单。
 *   public static plugin_myBackup::uninstall() — 移除数据库备份管理路由和菜单，不执行数据库恢复或删除业务库。
 *   public static plugin_myBackup::main() — 校验后台登录并装配 myBackup 插件的管理模块和对应模板。
 *   public static plugin_myBackup::uploadLimit($upload = null, $post = null) — 将 PHP 上传及 POST 容量设置转换为有效文件上限，按较小的正上限约束。
 *
 * 使用说明：
 *   1. 显式加载主类并由管理流程调用 check/install/uninstall；业务方法的使用见方法目录。
 *   2. main 通过公共后台页面装配器进入 module/myBackup.php。
 *   3. 备份使用独立连接及随机工作目录；仅移除插件入口不会删除业务数据库。
 *
 * 说明：PHPDoc 类型用于说明，不增加原生类型约束；源码保持 PHP 7.4 兼容。
 *       方法目录覆盖本文件直接声明；闭包参数及捕获变量说明见各回调旁 PHPDoc。
 */

/**
 * 功能说明：提供独立数据库备份及表维护页面的插件。
 *
 * 使用说明：
 *   1. main 通过公共后台页面装配器进入 module/myBackup.php。
 *   2. 备份使用独立连接及随机工作目录；仅移除插件入口不会删除业务数据库。
 *
 * 方法目录：参见本文件顶部；仅列直接声明，不重复列出未在本文件声明的继承或协议方法。
 */
class plugin_myBackup implements interface_plugin {
    /**
     * 功能说明：返回数据库备份插件的基础安装检查结果。
     *
     * 当前始终返回 true，并将说明置为空串；不尝试连接配置的数据库。
     *
     * @param string &$result 引用输出：供后台展示的插件安装条件检查说明（默认：''）
     * @return bool 当前基础检查是否通过；不代表远端服务或业务数据库可用
     */
    public static function check(&$result = '') {
        $result = "";
        $flag = true;
        return $flag;
    }
    /**
     * 功能说明：登记数据库备份管理路由和后台菜单。
     *
     * 参数说明：无显式参数。
     *
     * @return void 无返回值，不用返回值表达多资源操作整体成功；依赖异常继续传递
     * @throws RuntimeException 插件路由源缺失或路由/菜单发布失败
     * @global myStep $mystep 当前宿主控制器，提供语言、页面及生命周期功能
     */
    public static function install() {
        global $mystep;
        if(!regPluginRoute('myBackup')) throw new RuntimeException('Missing myBackup routes');
        addPluginLink($mystep->getLanguage('plugin_myBackup_info_name'), 'myBackup');
    }
    /**
     * 功能说明：移除数据库备份管理路由和菜单，不执行数据库恢复或删除业务库。
     *
     * 参数说明：无显式参数。
     *
     * @return void 无返回值，不用返回值表达多资源操作整体成功；依赖异常继续传递
     */
    public static function uninstall() {
        removePluginRoute('myBackup');
        removePluginLink('myBackup');
    }
    /**
     * 功能说明：校验后台登录并装配 myBackup 插件的管理模块和对应模板。
     *
     * 参数说明：无显式参数。
     *
     * @return void 未通过登录检查时返回；通过时输出管理响应，部分分支直接结束请求
     */
    public static function main() {
        showPluginPage('myBackup', 'myBackup');
    }
    /**
     * 功能说明：将 PHP 上传及 POST 容量设置转换为有效文件上限，按较小的正上限约束。
     *
     * @param string|null $upload upload_max_filesize 值；null 读取当前设置（默认：null）
     * @param string|null $post post_max_size 值；null 读取当前设置（默认：null）
     * @return int 上限字节数；没有正上限时返回 PHP_INT_MAX，不能给隐藏 MAX_FILE_SIZE 写 0
     */
    public static function uploadLimit($upload = null, $post = null) {
        $limits = [PHP_INT_MAX];
        foreach([$upload ?? ini_get('upload_max_filesize'), $post ?? ini_get('post_max_size')] as $value) {
            $bytes = myFile::getByte($value);
            if($bytes > 0) $limits[] = min(PHP_INT_MAX, $bytes);
        }
        return (int)min($limits);
    }
}