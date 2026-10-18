<?PHP

/**
 * 文件：plugin/frontend/class.php
 * 功能说明：按应用选择并校验前端组件版本的插件。
 *
 * 方法目录：
 *   public static plugin_frontend::check(&$result = '') — 返回前端资源插件的基础安装检查结果。
 *   public static plugin_frontend::install() — 登记前端资源管理路由和后台菜单。
 *   public static plugin_frontend::uninstall() — 移除前端资源管理路由和后台菜单，保留插件配置及附带组件。
 *   public static plugin_frontend::main() — 校验后台登录并装配 frontend 插件的管理模块和对应模板。
 *   public static plugin_frontend::selections($json, $strict = true) — 解析并校验应用资源选择，兼容历史多余反斜线；拒绝不存在的资源及路径穿越。
 *   public static plugin_frontend::migrate($file) — 返回与 jQuery 主版本匹配的兼容脚本，1.9以前无需额外迁移脚本。
 *
 * 使用说明：
 *   1. 显式加载主类并由管理流程调用 check/install/uninstall；业务方法的使用见方法目录。
 *   2. index.php 将资源替换登记到宿主 start 钩子，不新建框架控制器。
 *   3. 保存配置默认严格校验；运行/展示可逐项容错，不保证跨组件主版本的模板兼容。
 *
 * 说明：PHPDoc 类型用于说明，不增加原生类型约束；源码保持 PHP 7.4 兼容。
 *       方法目录覆盖本文件直接声明；闭包参数及捕获变量说明见各回调旁 PHPDoc。
 */

/**
 * 功能说明：按应用选择并校验前端组件版本的插件。
 *
 * 使用说明：
 *   1. index.php 将资源替换登记到宿主 start 钩子，不新建框架控制器。
 *   2. 保存配置默认严格校验；运行/展示可逐项容错，不保证跨组件主版本的模板兼容。
 *
 * 方法目录：参见本文件顶部；仅列直接声明，不重复列出未在本文件声明的继承或协议方法。
 */
class plugin_frontend implements interface_plugin {
    /**
     * 功能说明：返回前端资源插件的基础安装检查结果。
     *
     * 当前始终返回 true；具体应用及组件文件的有效性由 selections() 校验。
     *
     * @param string &$result 引用输出：供后台展示的插件安装条件检查说明（默认：''）
     * @return bool 当前基础检查是否通过；不代表远端服务或业务数据库可用
     */
    public static function check(&$result = '') {
        $result = 'OK!';
        return true;
    }
    /**
     * 功能说明：登记前端资源管理路由和后台菜单。
     *
     * 参数说明：无显式参数。
     *
     * @return void 无返回值，不用返回值表达多资源操作整体成功；依赖异常继续传递
     * @throws RuntimeException 插件路由源缺失或路由/菜单发布失败
     * @global myStep $mystep 当前宿主控制器，提供语言、页面及生命周期功能
     */
    public static function install() {
        global $mystep;
        if(!regPluginRoute('frontend')) throw new RuntimeException('Missing frontend routes');
        addPluginLink($mystep->getLanguage('plugin_frontend_info_name'), 'frontend');
    }
    /**
     * 功能说明：移除前端资源管理路由和后台菜单，保留插件配置及附带组件。
     *
     * 参数说明：无显式参数。
     *
     * @return void 无返回值，不用返回值表达多资源操作整体成功；依赖异常继续传递
     */
    public static function uninstall() {
        removePluginRoute('frontend');
        removePluginLink('frontend');
    }
    /**
     * 功能说明：校验后台登录并装配 frontend 插件的管理模块和对应模板。
     *
     * 参数说明：无显式参数。
     *
     * @return void 未通过登录检查时返回；通过时输出管理响应，部分分支直接结束请求
     */
    public static function main() {
        showPluginPage('frontend', 'frontend');
    }
    /**
     * 功能说明：解析并校验应用资源选择，兼容历史多余反斜线；拒绝不存在的资源及路径穿越。
     *
     * strict=false 只容忍单条应用/资源选择失效；整体 JSON 无法解析时仍抛异常。
     *
     * @param string $json 应用到 jq/bs 的 JSON 字典，空字符串视为空字典
     * @param bool $strict true 保存时逐项拒绝无效选择；false 运行时跳过失效应用/资源（默认：true）
     * @return array 已验证的配置；空配置为 []
     * @throws InvalidArgumentException JSON/应用/版本非法或资源不完整
     */
    public static function selections($json, $strict = true) {
        if(!is_string($json)) throw new InvalidArgumentException('Invalid frontend JSON');
        if(trim($json)==='') return [];
        $data=json_decode($json,true);
        if(json_last_error()!==JSON_ERROR_NONE) $data=json_decode(stripslashes($json),true);
        if(!is_array($data)) throw new InvalidArgumentException('Invalid frontend JSON');
        foreach($data as $app=>$selection) {
            try {
                if(!is_string($app) || !preg_match('/\A[a-zA-Z][a-zA-Z0-9_]*\z/D',$app) || !is_dir(APP.$app) || is_link(APP.$app) || !is_array($selection)) throw new InvalidArgumentException('Invalid frontend application');
                $jq=$selection['jq'] ?? ''; $bs=$selection['bs'] ?? '';
                if(!is_string($jq) || !preg_match('/\Ajquery-\d+\.\d+\.\d+\.js\z/D',$jq)
                    || !is_string($bs) || !preg_match('/\A\d+\.\d+\.\d+\z/D',$bs)
                    || !myFile::resolveWithin(__DIR__.'/files/jquery',$jq)
                    || !myFile::resolveWithin(__DIR__.'/files/bootstrap',$bs.'/css/bootstrap.css')
                    || !myFile::resolveWithin(__DIR__.'/files/bootstrap',$bs.'/js/bootstrap.js')) throw new InvalidArgumentException('Invalid frontend resource version');
                $data[$app]=['jq'=>$jq,'bs'=>$bs];
            } catch(InvalidArgumentException $error) {
                if($strict) throw $error;
                unset($data[$app]);
            }
        }
        return $data;
    }
    /**
     * 功能说明：返回与 jQuery 主版本匹配的兼容脚本，1.9以前无需额外迁移脚本。
     *
     * @param string $file jquery-x.y.z.js 文件名
     * @return string 迁移脚本绝对路径，无需迁移时为空串
     */
    public static function migrate($file) {
        if(!preg_match('/\Ajquery-(\d+\.\d+\.\d+)\.js\z/D',$file,$m)) return '';
        if(version_compare($m[1],'3.0.0','>=')) return __DIR__.'/files/jquery/jquery-migrate-3.4.0.js';
        return version_compare($m[1],'1.9.0','>=') ? __DIR__.'/files/jquery/jquery-migrate-1.4.1.js' : '';
    }
}