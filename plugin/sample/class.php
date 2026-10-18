<?PHP

/**
 * 文件：plugin/sample/class.php
 * 功能说明：展示插件生命周期、页面钩子、标签、模块与公开 API 的示例。
 *
 * 方法目录：
 *   public static plugin_sample::check(&$result = '') — 检查演示资源目录的可写条件，并汇总供后台显示的 HTML 说明。
 *   public static plugin_sample::install() — 建立演示目录、清理脚本缓存并登记演示路由和菜单。
 *   public static plugin_sample::uninstall() — 移除演示目录、路由及菜单，并清理相关脚本缓存。
 *   public static plugin_sample::setPage($content) — 为页面中匹配到的 title 起始标签增加演示标题前缀。
 *   public static plugin_sample::main() — 校验后台登录并装配 sample 插件的管理模块和对应模板。
 *   public static plugin_sample::api() — 返回演示 API 明确允许公开的设置，不泄露密码字段及后续新增的私有设置。
 *   public static plugin_sample::rate($record,$now,$limit=30) — 计算演示限流的固定60秒窗口，不与Sample应用的counter Cookie混用。
 *
 * 使用说明：
 *   1. 显式加载主类并由管理流程调用 check/install/uninstall；业务方法的使用见方法目录。
 *   2. 实际登记在 index.php 完成，类方法供后台、路由、API 和页面回调调用。
 *   3. 公开 API 使用显式字段白名单；Cookie 限流仅为演示，管理员会话可跳过。
 *
 * 说明：PHPDoc 类型用于说明，不增加原生类型约束；源码保持 PHP 7.4 兼容。
 *       方法目录覆盖本文件直接声明；闭包参数及捕获变量说明见各回调旁 PHPDoc。
 */

/**
 * 功能说明：展示插件生命周期、页面钩子、标签、模块与公开 API 的示例。
 *
 * 使用说明：
 *   1. 实际登记在 index.php 完成，类方法供后台、路由、API 和页面回调调用。
 *   2. 公开 API 使用显式字段白名单；Cookie 限流仅为演示，管理员会话可跳过。
 *
 * 方法目录：参见本文件顶部；仅列直接声明，不重复列出未在本文件声明的继承或协议方法。
 */
class plugin_sample implements interface_plugin {
    /**
     * 功能说明：检查演示资源目录的可写条件，并汇总供后台显示的 HTML 说明。
     *
     * 可写性检查可能创建并清理临时探测文件，不建立 dir1/dir2 演示资源。
     *
     * @param string &$result 引用输出：供后台展示的插件安装条件检查说明（默认：''）
     * @return bool 当前基础检查是否通过；不代表远端服务或业务数据库可用
     */
    public static function check(&$result = '') {
        $result = "";
        $theList = array(
            "dir1/",
            "dir2/",
        );
        $flag = true;
        $dir = __DIR__;
        foreach($theList as $cur) {
            if(myFile::rewritable($dir.'/'.$cur)) {
                $result .= $cur . ' - <span style="color:green">Writable</span><br />';
            } else {
                $result .= $cur . ' - <span style="color:red">Readonly</span><br />';
                $flag = false;
            }
        }
        return $flag;
    }
    /**
     * 功能说明：建立演示目录、清理脚本缓存并登记演示路由和菜单。
     *
     * 参数说明：无显式参数。
     *
     * @return void 无返回值，不用返回值表达多资源操作整体成功；依赖异常继续传递
     * @throws RuntimeException 演示目录创建失败或路由源缺失
     * @throws Throwable 菜单、路由或缓存操作异常按框架错误策略向外传递
     * @global myStep $mystep 当前宿主控制器，提供语言、页面及生命周期功能
     */
    public static function install() {
        global $mystep;
        $dir = __DIR__;
        if(!myFile::mkdir($dir.'/dir1') || !myFile::mkdir($dir.'/dir2')) throw new RuntimeException('Cannot create sample plugin directories');
        myFile::del(CACHE.'script');
        if(!regPluginRoute('sample')) throw new RuntimeException('Missing sample routes');
        addPluginLink($mystep->getLanguage('plugin_sample_name_link'), 'sample_route');
    }
    /**
     * 功能说明：移除演示目录、路由及菜单，并清理相关脚本缓存。
     *
     * 参数说明：无显式参数。
     *
     * @return void 无返回值，不用返回值表达多资源操作整体成功；依赖异常继续传递
     */
    public static function uninstall() {
        $dir = __DIR__;
        myFile::del($dir.'/dir1');
        myFile::del($dir.'/dir2');
        myFile::del(CACHE.'script');
        removePluginRoute('sample');
        removePluginLink('sample_route');
    }
    /**
     * 功能说明：为页面中匹配到的 title 起始标签增加演示标题前缀。
     *
     * 使用大小写敏感的字符串替换，可能修改多个匹配项；本方法不解析 HTML DOM。
     *
     * @param string $content page 钩子传入的完整页面 HTML
     * @return string 增加前缀后的页面；没有匹配 title 起始标签时保留原文
     */
    public static function setPage($content) {
        return str_replace('<title>', '<title>插件测试 - ', $content);
    }
    /**
     * 功能说明：校验后台登录并装配 sample 插件的管理模块和对应模板。
     *
     * 参数说明：无显式参数。
     *
     * @return void 未通过登录检查时返回；通过时输出管理响应，部分分支直接结束请求
     */
    public static function main() {
        showPluginPage('sample', 'sample');
    }
    /**
     * 功能说明：返回演示 API 明确允许公开的设置，不泄露密码字段及后续新增的私有设置。
     *
     * 参数说明：无显式参数。
     *
     * @return array 公开的 para_1 至 para_5、para_7、para_8 设置
     */
    public static function api() {
        $config = include(__DIR__.'/config.php');
        return array_intersect_key($config, array_flip(['para_1','para_2','para_3','para_4','para_5','para_7','para_8']));
    }
    /**
     * 功能说明：计算演示限流的固定60秒窗口，不与Sample应用的counter Cookie混用。
     *
     * @param string|null $record JSON格式的旧窗口记录
     * @param int $now 当前秒时间戳
     * @param int $limit 窗口内最大次数，默认30
     * @return array allowed是否允许、start窗口起点、count累计次数、retry剩余秒数
     * 注意：Cookie计数用于演示，不能替代服务端防滥用策略。
     */
    public static function rate($record,$now,$limit=30) {
        $state=is_string($record)?json_decode($record,true):null;
        if(!is_array($state) || !isset($state['start'],$state['count']) || !is_int($state['start']) || !is_int($state['count'])
            || $state['start']>$now || $state['start']<=$now-60 || $state['count']<0) $state=['start'=>$now,'count'=>0];
        $allowed=$state['count']<$limit;
        return ['allowed'=>$allowed,'start'=>$state['start'],'count'=>$allowed?$state['count']+1:$state['count'],'retry'=>max(1,$state['start']+60-$now)];
    }
}