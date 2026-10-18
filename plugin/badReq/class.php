<?PHP

/**
 * 文件：plugin/badReq/class.php
 * 功能说明：请求过滤、封禁及诊断日志插件。
 *
 * 方法目录：
 *   public static plugin_badReq::check(&$result = '') — 检查请求过滤插件的安装入口；当前实现不额外限制扩展条件。
 *   public static plugin_badReq::install() — 准备封禁列表与前置脚本；SQLite 可用时登记管理路由、菜单和日志表。
 *   public static plugin_badReq::uninstall() — 清理插件路由、菜单、日志数据和前置脚本，并重置封禁列表。
 *   public static plugin_badReq::main() — 校验后台登录并装配 badReq 插件的管理模块和对应模板。
 *   public static plugin_badReq::schema($db) — 确保独立SQLite日志表和检索索引存在，首次启用无需依赖旧安装顺序。
 *   public static plugin_badReq::bans($change) — 在旁路锁内更新IP屏蔽列表，原子保存并保留并发修改。
 *   public static plugin_badReq::record($ip,$info,$ua,$time) — 对可疑请求进行精确IP/路径计数，参数绑定避免URL或UA引号破坏SQL。
 *   public static plugin_badReq::redact($values) — 递归隐藏敏感表单字段，用于诊断日志；日志不保存上传文件正文或临时路径。
 *   public static plugin_badReq::url($params=[]) — 生成插件列表/操作地址，兼容子目录及三种路由模式。
 *   public static plugin_badReq::pages($total,$page=1,$size=20,$query=[]) — 列表分页；空数据保持第1页，参数编码并保留筛选项。
 *   public static plugin_badReq::parsePages(myTemplate &$tpl,&$attrs=[]) — 编译插件分页标签，避免声明与应用冲突的全局函数。
 *
 * 回调目录：
 *   回调 1：初始化空封禁文件时保留当前字典。
 *
 * 使用说明：
 *   1. 显式加载主类并由管理流程调用 check/install/uninstall；业务方法的使用见方法目录。
 *   2. 安装时按环境准备日志管理和前置过滤；普通页面仅显式加载主类。
 *   3. 过滤前置阶段早于应用认证，不替代业务授权；管理页使用框架后台登录检查。
 *
 * 说明：PHPDoc 类型用于说明，不增加原生类型约束；源码保持 PHP 7.4 兼容。
 *       方法目录覆盖本文件直接声明；闭包参数及捕获变量说明见各回调旁 PHPDoc。
 */

/**
 * 功能说明：请求过滤、封禁及诊断日志插件。
 *
 * 使用说明：
 *   1. 安装时按环境准备日志管理和前置过滤；普通页面仅显式加载主类。
 *   2. 过滤前置阶段早于应用认证，不替代业务授权；管理页使用框架后台登录检查。
 *
 * 方法目录：参见本文件顶部；仅列直接声明，不重复列出未在本文件声明的继承或协议方法。
 */
class plugin_badReq implements interface_plugin {
    /**
     * 功能说明：检查请求过滤插件的安装入口；当前实现不额外限制扩展条件。
     *
     * 当前始终返回 true；SQLite 缺失时日志与管理入口能力受限，不代表数据库已可用。
     *
     * @param string &$result 引用输出：供后台展示的插件安装条件检查说明（默认：''）
     * @return bool 当前基础检查是否通过；不代表远端服务或业务数据库可用
     */
    public static function check(&$result = '') {
        $result = 'OK!';
        return true;
    }
    /**
     * 功能说明：准备封禁列表与前置脚本；SQLite 可用时登记管理路由、菜单和日志表。
     *
     * 参数说明：无显式参数。
     *
     * @return void 无返回值，不用返回值表达多资源操作整体成功；依赖异常继续传递
     * @throws RuntimeException 必需路由、封禁列表或前置脚本准备失败
     * @throws Throwable 日志表或资源发布异常继续向外传递
     * @global myStep $mystep 当前宿主控制器，提供语言、页面及生命周期功能
     */
    public static function install() {
        global $mystep;
        if (extension_loaded('sqlite3')) {
            if(!regPluginRoute('badReq')) throw new RuntimeException('Missing badReq routes');
            addPluginLink($mystep->getLanguage('plugin_badReq_info_name'), 'badReq');
            $db = new myDb('sqlite', __DIR__.'/data.db');
            self::schema($db);
            $db->close();
        }
        if(!is_file(__DIR__.'/ban_ip.php')) self::bans(
        /**
         * 功能说明：初始化空封禁文件时保留当前字典。
         *
         * @param array $list 当前 IP 到处理信息的封禁字典
         * @return array 原始字典，不新增封禁记录
         */
        function($list) {return $list;});
        if(!setPriority(basename(__DIR__))) throw new RuntimeException('Missing badReq priority script');
    }
    /**
     * 功能说明：清理插件路由、菜单、日志数据和前置脚本，并重置封禁列表。
     *
     * 该操作会删除独立请求日志及 CACHE/tmp/post 诊断日志，重置封禁数据；不是单纯停用。
     *
     * 参数说明：无显式参数。
     *
     * @return void 无返回值，不用返回值表达多资源操作整体成功；依赖异常继续传递
     */
    public static function uninstall() {
        myFile::del(__DIR__.'/data.db');
        removePluginRoute('badReq');
        removePluginLink('badReq');
        savePluginFile(__DIR__.'/ban_ip.php', '<?PHP'.chr(10).'return [];');
        myFile::del(CACHE.'tmp/post/');
        removePriority(basename(__DIR__));
    }
    /**
     * 功能说明：校验后台登录并装配 badReq 插件的管理模块和对应模板。
     *
     * 参数说明：无显式参数。
     *
     * @return void 未通过登录检查时返回；通过时输出管理响应，部分分支直接结束请求
     */
    public static function main() {
        showPluginPage('badReq', 'badReq');
    }
    /**
     * 功能说明：确保独立SQLite日志表和检索索引存在，首次启用无需依赖旧安装顺序。
     *
     * @param myDb $db 恶意请求日志连接
     * @return void 不修改框架数据库连接
     */
    public static function schema($db) {
        $db->query('CREATE TABLE IF NOT EXISTS requests(id INTEGER PRIMARY KEY AUTOINCREMENT,ip varchar(45),url varchar(255),qry varchar(255),ua varchar(255),cnt integer,req integer)');
        $db->query('CREATE INDEX IF NOT EXISTS requests_ip_req ON requests(ip,req)');
    }
    /**
     * 功能说明：在旁路锁内更新IP屏蔽列表，原子保存并保留并发修改。
     *
     * @param callable $change 接收并返回完整IP到消息/跳转地址字典
     * @return array 保存后的列表
     * @throws RuntimeException 锁、目标文件或原子保存失败
     * @throws UnexpectedValueException 原列表或回调结果不是数组，保留原文件
     */
    public static function bans($change) {
        $file=__DIR__.'/ban_ip.php';$lock=fopen($file.'.lock','c');
        if(!$lock) throw new RuntimeException('Cannot lock banned IP list');
        if(!flock($lock,LOCK_EX)) {fclose($lock);throw new RuntimeException('Cannot lock banned IP list');}
        try {
            if(is_link($file)) throw new RuntimeException('Invalid banned IP list target');
            if(function_exists('opcache_invalidate')) opcache_invalidate($file,true);
            $list=is_file($file) ? include($file) : [];
            if(!is_array($list)) throw new UnexpectedValueException('Invalid banned IP list');
            $before=$list;
            $list=$change($list);if(!is_array($list)) throw new UnexpectedValueException('Invalid banned IP list');
            ksort($list,SORT_NATURAL);
            if($before!==$list || !is_file($file)) savePluginFile($file,'<?php'.PHP_EOL.'return '.var_export($list,true).';');
            return $list;
        } finally {flock($lock,LOCK_UN);fclose($lock);}
    }
    /**
     * 功能说明：对可疑请求进行精确IP/路径计数，参数绑定避免URL或UA引号破坏SQL。
     *
     * 使用独立 SQLite 连接，不替换框架全局数据库；近期30秒累计采用聚合记录，不是严格滑动窗口。
     *
     * @param string $ip 已验证的客户端IP
     * @param array $info path/query 请求内容
     * @param string $ua 原始User-Agent
     * @param int $time 当前秒时间戳
     * @return int 当前IP近期累计次数；SQLite不可用时为0
     */
    public static function record($ip,$info,$ua,$time) {
        if(!extension_loaded('sqlite3')) return 0;
        $db=new myDb('SQLite',__DIR__.'/data.db');self::schema($db);
        try {
            $row=$db->fetchOne('SELECT id,cnt FROM requests WHERE ip=? AND url=? AND req>=? ORDER BY req DESC LIMIT 1',[$ip,$info['path'],$time-30]);
            if($row) $db->execute('UPDATE requests SET ua=?,qry=?,cnt=cnt+1,req=? WHERE id=?',[$ua,$info['query'],$time,$row['id']]);
            else $db->execute('INSERT INTO requests(ip,url,qry,ua,cnt,req) VALUES(?,?,?,?,1,?)',[$ip,$info['path'],$info['query'],$ua,$time]);
            return (int)$db->fetchValue('SELECT COALESCE(SUM(cnt),0) FROM requests WHERE ip=? AND req>=?',[$ip,$time-30]);
        } finally {$db->close();}
    }
    /**
     * 功能说明：递归隐藏敏感表单字段，用于诊断日志；日志不保存上传文件正文或临时路径。
     *
     * @param array $values 表单数据
     * @return array 脱敏副本
     */
    public static function redact($values) {
        $result=[];
        foreach($values as $key=>$value) $result[$key]=preg_match('/password|passwd|pwd|token|secret|authorization|cookie|credential|(?:^|_)pass(?:$|_)|(?:^|_)key(?:$|_)/i',(string)$key) ? '[redacted]' : (is_array($value)?self::redact($value):$value);
        return $result;
    }
    /**
     * 功能说明：生成插件列表/操作地址，兼容子目录及三种路由模式，保留当前展示后台。
     *
     * @param array $params 查询参数（默认：[]）
     * @return string 原始URL，写入HTML属性前需转义
     */
    public static function url($params=[]) {return pluginPageURL(myStep::setURL('badReq/'),$params);}
    /**
     * 功能说明：列表分页；空数据保持第1页，参数编码并保留筛选项。
     *
     * @param int $total 总条数
     * @param int $page 当前页（默认：1）
     * @param int $size 每页条数，非法值采用20（默认：20）
     * @param string|array $query 筛选参数（默认：[]）
     * @return array 分页字典、偏移、页大小
     */
    public static function pages($total,$page=1,$size=20,$query=[]) {
        $size=(int)$size;if($size<1) $size=20;$count=max(1,(int)ceil(max(0,(int)$total)/$size));$page=max(1,min($count,(int)$page));
        if(is_string($query)) {parse_str($query,$query);}if(!is_array($query))$query=[];
        $p=['total'=>max(0,(int)$total),'page_current'=>$page,'page_count'=>$count];
        foreach(['first'=>1,'prev'=>max(1,$page-1),'next'=>min($count,$page+1),'last'=>$count] as $key=>$value) $p['link_'.$key]=self::url(array_replace($query,['page'=>$value]));
        return [$p,($page-1)*$size,$size];
    }
    /**
     * 功能说明：编译插件分页标签，避免声明与应用冲突的全局函数。
     *
     * @param myTemplate &$tpl 当前模板
     * @param array &$attrs 标签属性及默认值
     * @return string 编译后的PHP/HTML
     */
    public static function parsePages(myTemplate &$tpl,&$attrs=[]) {
        $text=$tpl->getTemplate(__DIR__.'/template/block_pages.tpl');
        list($block,$attrs['unit'],$attrs['unit_blank'])=$tpl->getBlock($text,'if');
        $attrs['unit']=preg_replace('#<!--(link_\w+)-->#',"<?=htmlspecialchars(\$page_info['\\1'],ENT_QUOTES,'UTF-8')?>",$attrs['unit']);
        $attrs['unit']=str_replace('<!--page_count-->','<?=$page_info[\'page_count\']?>',$attrs['unit']);
        $code=<<<'TPL'
<?php
list($page_info)=plugin_badReq::pages({myTemplate::count},{myTemplate::page},{myTemplate::size},{myTemplate::query});
if($page_info['page_count']>1) {
?>
{myTemplate::unit}
<?php } elseif({myTemplate::count}>0) { ?>
{myTemplate::unit_blank}
<?php } ?>
TPL;
        return str_replace($block,$code,$text);
    }
}
