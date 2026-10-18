<?PHP
/**
 * 文件：plugin/interface_plugin.class.php
 * 功能说明：插件生命周期接口及公共辅助函数
 *
 * 方法目录：
 *   public static interface_plugin::check(&$result) — 检查运行环境、扩展及依赖，将检查结果写入引用参数。
 *   public static interface_plugin::install() — 初始化插件资源，登记路由、菜单和所需的优先脚本。
 *   public static interface_plugin::uninstall() — 清理插件注册信息及安装资源，数据保留策略由插件实现决定。
 *   addPluginLink($name, $path) — 幂等增加/更新插件菜单，保留其余菜单内容。
 *   removePluginLink($path) — 删除指定插件的全部重复菜单项，空菜单回到框架插件管理入口。
 *   changePluginMenu($change) — 持有旁路锁执行菜单读改写，避免同时安装插件时丢失菜单。
 *   pluginDirectory($idx, $required = true) — 校验插件标识并返回目录，加载资源时同时检查目录存在且不是符号链接。
 *   setPluginApps($idx, array $apps, array $required = []) — 在有序文件锁内同步应用绑定，保留其他插件及强制绑定，无变化不重写。
 *   setPluginOrder(array $orders) — 在注册表单次排他读改写中校验并稳定保存0到99的运行顺序。
 *   buildPluginPackage($idx) — 在独立副本中生成插件包，过滤在线配置和运行数据，返回下载描述。
 *   pluginQueryURL($url,$params=[]) — 合并 URL 查询参数，保留 index.php?路由和片段，值为 null 时删除参数。
 *   regPluginRoute($idx) — 将插件 route.php 同步到编译表，并写入显式路由源映射。
 *   removePluginRoute($idx) — 移除插件编译规则及显式源映射，目录已删除或重复清理时仍可执行。
 *   changePluginRoute($idx, $remove = false) — 持有源映射旁路锁，合并插件映射并调用路由器同步编译规则。
 *   savePluginFile($file, $content) — 先完整写入同目录临时文件，再原子替换配置/脚本，并清除对应 OPcache。
 *   setPluginTemplate($idx, $page='', $mode = true) — 创建外层页面模板及插件内容模板，不覆盖应用全局模板配置。
 *   pluginPageHost($app) — 检查应用是否提供通用插件布局适配器。
 *   pluginPageApplication($referer=null) — 从可信来源识别外层后台，缺省myStep。
 *   pluginPageURL($url='', array $params=[], $app=null) — 为插件自身链接保留展示上下文。
 *   pluginAdminURL($path, array $params=[], $app=null) — 生成当前后台中的设置等功能链接。
 *   renderPluginPage($idx, $page='', $app=null, $tpl=null) — 校验管理员及启用状态，装配插件主体。
 *   showPluginPage($idx, $page='', $mode=null) — 校验后台登录，加载插件模板和可选模块，并通过当前控制器显示页面。
 *   setPriority($idx) — 将插件 priority.php 原子复制到框架优先脚本目录，覆盖旧版本。
 *   removePriority($idx) — 删除插件优先脚本并使 OPcache 失效，支持目录已删除和重复清理。
 *
 * 回调目录：
 *   回调 1：在菜单锁内更新现有插件菜单或追加新菜单。
 *   回调 2：在菜单锁内移除目标入口并修复空分组链接。
 *   回调 3：筛选应保留的插件菜单项。
 *   回调 4：在独立作用域执行插件模块，避免业务变量覆盖展示上下文。
 *
 * 使用说明：
 *   1. 框架启动或插件入口显式加载，依赖 APP、PLUGIN、CONFIG、CACHE 等路径常量。
 *   2. 菜单、路由、优先脚本修改由已验证身份、POST 和 CSRF 的管理流程调用。
 *   3. 页面装配与资源发布的参数和失败约定详见各函数说明。
 *
 * 说明：PHPDoc 类型用于说明，不增加原生类型约束；源码保持 PHP 7.4 兼容。
 *       方法目录覆盖本文件直接声明；闭包参数及捕获变量说明见各回调旁 PHPDoc。
 */
/**
 * 功能说明：约定插件检查、安装与卸载生命周期的公共接口。
 *
 * 使用说明：
 *   1. 插件主类按 plugin_目录名 命名并实现此接口；由后台管理流程显式调用生命周期。
 *   2. check 用于检查与说明；install/uninstall 失败须抛异常，兼容原有无返回值实现。
 *   3. 接口只定义生命周期，不自动提供业务授权、活动状态判断或多资源事务。
 *
 * 方法目录：参见本文件顶部；仅列直接声明，不重复列出未在本文件声明的继承或协议方法。
 */
interface interface_plugin {
    /**
     * 功能说明：检查运行环境、扩展及依赖，将检查结果写入引用参数。
     *
     * @param string &$result 供后台展示的检查说明；实现方可设置默认空串
     * @return bool 满足安装条件为 true，否则为 false
     */
    public static function check(&$result);

    /**
     * 功能说明：初始化插件资源，登记路由、菜单和所需的优先脚本。
     *
     * 参数说明：无显式参数。
     *
     * @return bool|null 兼容原有无返回值实现；管理器不以返回值判定成功，失败须抛异常
     * @throws Exception 安装失败时由实现方或辅助函数抛出
     */
    public static function install();

    /**
     * 功能说明：清理插件注册信息及安装资源，数据保留策略由插件实现决定。
     *
     * 参数说明：无显式参数。
     *
     * @return bool|null 兼容原有无返回值实现；管理器不以返回值判定成功，失败须抛异常
     * @throws Exception 卸载失败时由实现方或辅助函数抛出
     */
    public static function uninstall();
}

/**
 * 功能说明：幂等增加/更新插件菜单，保留其余菜单内容。
 *
 * @param string $name 显示名称
 * @param string $path 插件路由路径
 * @return bool 保存结果
 * @throws UnexpectedValueException 菜单结构无效
 * @throws RuntimeException 锁或保存失败
 */
function addPluginLink($name, $path) {
    return changePluginMenu(
    /**
     * 功能说明：在菜单锁内更新现有插件菜单或追加新菜单。
     *
     * 闭包依赖：通过 use 捕获 name、path。
     *
     * @param array &$menu 引用输入/输出：完整后台菜单结构
     * @param int|string $last 插件菜单分组的实际下标
     * @return void 直接修改 menu，不返回新的菜单数组
     */
    function(&$menu,$last) use ($name,$path) {
        $menu[$last]['link']='#';
        foreach($menu[$last]['items'] as &$item) if(($item['link'] ?? '')===$path) { $item['name']=$name; return; }
        unset($item);
        $menu[$last]['items'][]=['name'=>$name,'link'=>$path];
    });
}

/**
 * 功能说明：删除指定插件的全部重复菜单项，空菜单回到框架插件管理入口。
 *
 * @param string $path 插件路由路径
 * @return bool 保存结果
 * @throws UnexpectedValueException 菜单结构无效
 * @throws RuntimeException 锁或保存失败
 */
function removePluginLink($path) {
    return changePluginMenu(
    /**
     * 功能说明：在菜单锁内移除目标入口并修复空分组链接。
     *
     * 闭包依赖：通过 use 捕获 path，并读取框架后台路径配置。
     *
     * @param array &$menu 引用输入/输出：完整后台菜单结构
     * @param int|string $last 插件菜单分组的实际下标
     * @return void 直接修改 menu，保留其他入口
     */
    function(&$menu,$last) use ($path) {
        $menu[$last]['items']=array_values(array_filter($menu[$last]['items'],
        /**
         * 功能说明：筛选应保留的插件菜单项。
         *
         * 闭包依赖：通过 use 捕获 path。
         *
         * @param array $item 当前菜单项
         * @return bool 菜单路径与待删除路径不相同为 true
         */
        function($item) use ($path) {return ($item['link'] ?? '')!==$path;}));
        if(!$menu[$last]['items']) {
            $setting=new myConfig(CONFIG.'config.php');
            $menu[$last]['link']=trim($setting->gen->path_admin ?? 'console','/').'/function/plugin';
        }
    });
}

/**
 * 功能说明：持有旁路锁执行菜单读改写，避免同时安装插件时丢失菜单。
 *
 * @param callable $change 回调接收引用菜单数组及插件组下标
 * @return bool 保存结果
 * @throws RuntimeException 锁或保存失败
 * @throws UnexpectedValueException 原菜单无效，不覆盖原文件
 */
function changePluginMenu($change) {
    $file=APP.'myStep/menu.json';$lock=fopen($file.'.lock','c');
    if(!$lock) throw new RuntimeException('Cannot lock plugin menu');
    if(!flock($lock,LOCK_EX)) {fclose($lock);throw new RuntimeException('Cannot lock plugin menu');}
    try {
        $menu=json_decode(myFile::getLocal($file),true);
        if(!is_array($menu) || !$menu) throw new UnexpectedValueException('Invalid plugin menu');
        $last=array_key_last($menu);
        if(!is_array($menu[$last])) throw new UnexpectedValueException('Invalid plugin menu group');
        if(!isset($menu[$last]['items'])) $menu[$last]['items']=[];
        if(!is_array($menu[$last]['items'])) throw new UnexpectedValueException('Invalid plugin menu items');
        foreach($menu[$last]['items'] as $item) if(!is_array($item)) throw new UnexpectedValueException('Invalid plugin menu item');
        $change($menu,$last);
        savePluginFile($file,myString::toJson($menu));
        return true;
    } finally {flock($lock,LOCK_UN);fclose($lock);}
}

/**
 * 功能说明：校验插件标识并返回目录，加载资源时同时检查目录存在且不是符号链接。
 *
 * @param string $idx 插件目录名，只允许字母开头的字母、数字及下划线
 * @param bool $required true 要求目录存在且非符号链接；false 仅校验标识，用于清理已删除的插件（默认：true）
 * @return string 插件目录，带末尾斜线；required=false 不表示该目录可用
 * @throws InvalidArgumentException 标识非法，或 required=true 时目录不存在/为符号链接
 */
function pluginDirectory($idx, $required = true) {
    if(!is_string($idx) || !preg_match('/\A[a-zA-Z][a-zA-Z0-9_]*\z/D',$idx)
        || ($required && (!is_dir(PLUGIN.$idx) || is_link(PLUGIN.$idx)))) throw new InvalidArgumentException('Invalid plugin name');
    return PLUGIN.$idx.'/';
}

/**
 * 功能说明：同步插件应用绑定，按应用名顺序持有全部旁路锁后再校验和发布。
 *
 * 保留其他插件及原顺序，去除目标插件的重复项；仅数据变化时写入连续索引数组。
 * 全部现有清单通过校验后才开始保存；单文件原子发布，不提供跨文件I/O回滚。
 *
 * @param string $idx 插件目录标识；卸载清理时允许源目录已经不存在
 * @param string[] $apps 本次选择的应用标识
 * @param string[] $required 安装/设置时必须保留的应用标识，卸载时传空数组（默认：[]）
 * @return int 实际改写的应用清单数量
 * @throws InvalidArgumentException 插件或应用标识无效、指定应用不存在或路径含符号链接
 * @throws UnexpectedValueException 现有清单不是字符串数组，保留原文件
 * @throws RuntimeException 清单、锁或原子发布失败；已发布的其他文件不自动回滚
 */
function setPluginApps($idx, array $apps, array $required = []) {
    pluginDirectory($idx,false);
    $known=[];
    foreach(glob(APP.'*/info.php') ?: [] as $info) {
        $name=basename(dirname($info));$directory=APP.$name.'/';
        if(preg_match('/\A[a-zA-Z][a-zA-Z0-9_]*\z/D',$name) && is_file($info) && !is_link(rtrim($directory,'/')) && !is_link($info)) $known[$name]=$directory;
    }
    $selected=[];
    foreach(array_merge($apps,$required) as $name) {
        if(!is_string($name) || !isset($known[$name])) throw new InvalidArgumentException('Invalid plugin application');
        $selected[$name]=true;
    }
    ksort($known,SORT_STRING);$locks=$changes=[];
    try {
        foreach($known as $name=>$directory) {
            $file=myFile::extractionPath($directory,'plugin.php');
            $lock_file=myFile::extractionPath($directory,'plugin.php.lock');
            if(!$file || !$lock_file) throw new InvalidArgumentException('Invalid plugin binding path');
            if(file_exists($file) && !is_file($file)) throw new RuntimeException('Invalid plugin binding file');
            $lock=fopen($lock_file,'c');
            if(!$lock) throw new RuntimeException('Cannot open plugin binding lock');
            if(!flock($lock,LOCK_EX)) {fclose($lock);throw new RuntimeException('Cannot lock plugin binding');}
            $locks[]=$lock;
            if(function_exists('opcache_invalidate') && is_file($file)) opcache_invalidate($file,true);
            $list=is_file($file) ? (
                /**
                 * 功能说明：在独立作用域读取受信应用插件清单。
                 * @param string $path 已验证的清单文件路径
                 * @return mixed 文件返回值，交由调用方检查数组及成员类型
                 */
                static function($path) {return include($path);}
            )($file) : [];
            if(!is_array($list)) throw new UnexpectedValueException('Invalid plugin binding list');
            $next=[];$found=false;
            foreach($list as $value) {
                if(!is_string($value)) throw new UnexpectedValueException('Invalid plugin binding member');
                if($value!==$idx) $next[]=$value;
                elseif(isset($selected[$name]) && !$found) {$next[]=$idx;$found=true;}
            }
            if(isset($selected[$name]) && !$found) $next[]=$idx;
            if($next!==$list) $changes[$file]='<?php'.PHP_EOL.'return '.var_export($next,true).';';
        }
        foreach($changes as $file=>$content) savePluginFile($file,$content);
        return count($changes);
    } finally {
        foreach(array_reverse($locks) as $lock) {flock($lock,LOCK_UN);fclose($lock);}
    }
}

/**
 * 功能说明：校验并在同一次注册表排他读改写内保存插件运行顺序。
 *
 * 只修改提交的插件，其余记录和状态保留；按order降序排列，同值保持原记录顺序。
 * 无效输入抛异常且不会先清空注册表。调用方负责管理员身份、POST与CSRF验证。
 *
 * @param array $orders 插件标识到0至99整数或整数字符串的字典，可为空
 * @return bool 保存成功为true；注册表缺失、读写失败或输入无效时抛异常
 * @throws InvalidArgumentException 插件不存在或顺序不在允许范围
 * @throws RuntimeException 注册表不存在、无法打开或无法保存
 * @throws Throwable 注册表损坏或回调异常继续传递
 */
function setPluginOrder(array $orders) {
    $db=new myDb('simpleDB','plugin',PLUGIN);
    try {
        if(!$db->check()) throw new RuntimeException('Plugin registry is missing');
        $result=$db->mutate(
            /**
             * 功能说明：在锁内验证目标标识与数值，更新后稳定排序全部记录。
             * 闭包依赖：通过use捕获orders，不执行插件生命周期脚本。
             * @param array &$rows 引用输入/输出：当前插件注册记录
             * @param array $schema 字段宽度定义，本操作不修改结构
             * @return bool 完成更新后返回true
             * @throws InvalidArgumentException 目标插件或顺序非法
             */
            function(&$rows,$schema) use ($orders) {
                $known=array_column($rows,null,'idx');
                foreach($orders as $id=>$order) if(!isset($known[$id]) || (!is_int($order) && !is_string($order)) || filter_var($order,FILTER_VALIDATE_INT)===false || (int)$order<0 || (int)$order>99) throw new InvalidArgumentException('Plugin order must be an integer from 0 to 99');
                $indexed=[];
                foreach($rows as $position=>$row) {
                    if(array_key_exists($row['idx'],$orders)) $row['order']=(int)$orders[$row['idx']];
                    $indexed[]=['row'=>$row,'position'=>$position];
                }
                usort($indexed,
                    /**
                     * 功能说明：order较大者优先，同值按原记录位置保持稳定顺序。
                     * @param array $left 左侧记录及原位置
                     * @param array $right 右侧记录及原位置
                     * @return int 小于零表示左侧先执行，等于零表示顺序相同
                     */
                    static function($left,$right) {return (int)$right['row']['order']<=>(int)$left['row']['order'] ?: $left['position']<=>$right['position'];}
                );
                $rows=array_column($indexed,'row');
                return true;
            }
        );
        if($result!==true) throw new RuntimeException('Cannot save plugin order');
        return true;
    } finally {$db->close();}
}

/**
 * 功能说明：在隔离副本中导出指定插件，供新旧后台共用且不依赖manager已启用。
 *
 * 在线config.php、运行数据、锁、日志和版本库信息不入包；manager主类存在时按原规则生成脱敏配置副本。
 * 缺少manager时不生成配置副本，仍强制过滤在线配置；临时目录在请求结束时清理，下载需在此之前完成。
 *
 * @param string $idx 已存在且不是符号链接的插件目录标识，不允许空标识或插件根目录
 * @return array 下载描述，file为临时归档绝对路径，name为plugin_标识.plugin
 * @throws InvalidArgumentException 插件或manager路径不安全
 * @throws RuntimeException 暂存目录、完整复制或归档生成失败
 * @throws Throwable 脱敏配置生成或归档异常继续传递；不改写在线插件目录
 */
function buildPluginPackage($idx) {
    $directory=pluginDirectory($idx);
    $work=myFile::extractionPath(CACHE,'tmp/plugin-pack-'.bin2hex(random_bytes(8)));
    if(!$work || !myFile::mkdir($work)) throw new RuntimeException('Cannot create plugin package workspace');
    register_shutdown_function(
        /**
         * 功能说明：请求结束后删除本次归档和副本，不删除在线插件资源。
         * 闭包依赖：通过use捕获本次独立work目录。
         * 参数说明：无显式参数。
         * @return void 完成临时资源清理，无返回值
         */
        static function() use ($work) {myFile::del($work);}
    );
    if(!myFile::copy($directory,$work.'/',true)) throw new RuntimeException('Cannot copy plugin package source');
    $copy=$work.'/'.$idx;$package=$work.'/plugin_'.$idx.'.plugin';
    // 先移除在线目录中可能残留的导出副本，只接受本次在隔离目录重新生成的内容。
    if(file_exists($copy.'/config_new.php')) myFile::del($copy.'/config_new.php');
    $ignore=['config.php','.lock','.db','.db-wal','.db-shm','.db-journal','.log','.env','.git/','.svn/','.codex/','.agents/','.aws/','.log/','error.log','ban_ip.php','check_file.php','.DS_Store'];
    if(is_file(PLUGIN.'manager/class.php')) {
        $manager=pluginDirectory('manager').'class.php';
        if(is_link($manager)) throw new InvalidArgumentException('Invalid manager class');
        require_once $manager;
        plugin_manager::checkConfig($copy);
        $ignore=array_merge($ignore,plugin_manager::IGNORE);
    }
    $packer=new myPacker($copy,$package);$packer->addIgnore($ignore);
    if(!$packer->pack()) throw new RuntimeException('Cannot build plugin package');
    return ['file'=>$package,'name'=>'plugin_'.$idx.'.plugin'];
}

/**
 * 功能说明：合并 URL 查询参数，保留 index.php?路由和片段，值为 null 时删除参数。
 *
 * @param string $url 原始 URL
 * @param array $params 需要覆盖的查询参数（默认：[]）
 * @return string RFC3986 编码后的地址；放入 HTML 时再转义
 */
function pluginQueryURL($url,$params=[]) {
    $fragment='';$at=strpos($url,'#');
    if($at!==false){$fragment=substr($url,$at);$url=substr($url,0,$at);}
    $parts=explode('?',$url,2);$head=$parts[0];$query=$parts[1] ?? '';
    if(preg_match('#(?:^|/)index\.php$#i',$head) && $query!=='' && strpos(explode('&',$query,2)[0],'=')===false) {
        $route=explode('&',$query,2);$head.='?'.$route[0];$query=$route[1] ?? '';
    }
    parse_str($query,$values);$values=array_replace($values,$params);
    foreach($values as $key=>$value) if($value===null) unset($values[$key]);
    $query=http_build_query($values,'','&',PHP_QUERY_RFC3986);
    return $head.($query!=='' ? (strpos($head,'?')===false?'?':'&').$query : '').$fragment;
}

/**
 * 功能说明：将插件 route.php 同步到编译表，并写入显式路由源映射。
 *
 * 使用已初始化的全局 $router；编译表分组名为 plugin_插件标识，不修改其他应用分组。
 *
 * @param string $idx 已存在的插件目录名
 * @return bool 同步成功为 true；插件没有 route.php 时为 false，不新增无效映射
 * @throws InvalidArgumentException 插件标识/目录非法，或路由包含不可持久化对象
 * @throws UnexpectedValueException 原路由源映射不是数组
 * @throws LogicException 全局路由对象尚未初始化
 * @throws RuntimeException 加锁或保存失败
 */
function regPluginRoute($idx) {
    return changePluginRoute($idx);
}

/**
 * 功能说明：移除插件编译规则及显式源映射，目录已删除或重复清理时仍可执行。
 *
 * @param string $idx 插件目录名；只用于定位注册键，不读取插件目录
 * @return bool 清理完成为 true，包括目标原本不存在的情况
 * @throws InvalidArgumentException 插件标识非法
 * @throws UnexpectedValueException 原路由源映射不是数组
 * @throws LogicException 全局路由对象尚未初始化
 * @throws RuntimeException 加锁或保存失败
 */
function removePluginRoute($idx) {
    return changePluginRoute($idx, true);
}

/**
 * 功能说明：持有源映射旁路锁，合并插件映射并调用路由器同步编译规则。
 *
 * 两个文件各自完整发布；不承诺跨文件事务，保存异常须交由调用方中止操作。
 * 框架下一次 refreshRoutes 会按源映射和启用状态重建不一致的编译表。
 *
 * @param string $idx 插件目录名
 * @param bool $remove true 清理；false 登记，要求目录及 route.php 存在（默认：false）
 * @return bool 成功为 true；登记时没有路由源为 false
 * @internal 供 regPluginRoute/removePluginRoute 共用
 * @throws InvalidArgumentException 插件标识/目录非法，或路由无法持久化
 * @throws UnexpectedValueException 路由源映射不是数组
 * @throws LogicException 全局路由对象尚未初始化
 * @throws RuntimeException 加锁、读取或保存失败
 * @global myRouter $router 已初始化的全局路由器
 */
function changePluginRoute($idx, $remove = false) {
    $source=pluginDirectory($idx, !$remove).'route.php';
    if(!$remove && !is_file($source)) return false;
    global $router;
    if(!($router instanceof myRouter)) throw new LogicException('Plugin routes require an initialized router');
    $file=CONFIG.'route_plugin.php';$lock=fopen($file.'.lock','c');
    if(!$lock) throw new RuntimeException('Cannot lock plugin routes');
    if(!flock($lock,LOCK_EX)) {fclose($lock);throw new RuntimeException('Cannot lock plugin routes');}
    try {
        $list=is_file($file) ? include($file) : [];
        if(!is_array($list)) throw new UnexpectedValueException('Invalid plugin route mapping');
        $before=$list;
        foreach($list as &$routeSource) $routeSource=myPath::relative($routeSource);
        unset($routeSource);
        if($remove) {
            $router->remove(CONFIG.'route.php','plugin_'.$idx);
            unset($list[$idx]);
        } else {
            if(!$router->checkRoute(CONFIG.'route.php',$source,'plugin_'.$idx)) return false;
            $list[$idx]=myPath::relative($source);
        }
        if($before!==$list || !is_file($file)) savePluginFile($file,"<?PHP\nreturn ".var_export($list,true).";\n");
        return true;
    } finally {flock($lock,LOCK_UN);fclose($lock);}
}

/**
 * 功能说明：先完整写入同目录临时文件，再原子替换配置/脚本，并清除对应 OPcache。
 *
 * 本函数只发布内容，不提供读改写锁；需要合并原文件的调用方应先持有旁路锁。
 *
 * @param string $file 目标本地文件路径；父目录不存在时创建
 * @param string $content 完整文件字节串，包括空串和字符串 0
 * @return bool 完整发布后返回 true
 * @internal 供插件菜单、源映射和优先脚本保存共用；文件路径必须由可信代码构造
 * @throws RuntimeException 无法创建目录、完整写入或原子替换目标文件
 */
function savePluginFile($file, $content) {
    if(is_link($file)) throw new InvalidArgumentException('Invalid plugin configuration target');
    if(!myFile::mkdir(dirname($file))) throw new RuntimeException('Cannot create plugin configuration directory');
    $temporary=$file.'.'.bin2hex(random_bytes(8)).'.tmp';
    try {
        if(@myFile::saveFile($temporary,$content)!==strlen($content)) throw new RuntimeException('Cannot save plugin configuration');
        if(is_file($file) && !@chmod($temporary,fileperms($file)&0777)) throw new RuntimeException('Cannot preserve plugin configuration permissions');
        if(!@rename($temporary,$file)) throw new RuntimeException('Cannot save plugin configuration');
        if(function_exists('opcache_invalidate')) opcache_invalidate($file,true);
        return true;
    } finally {if(is_file($temporary)) unlink($temporary);}
}

/**
 * 功能说明：创建外层页面模板及插件内容模板，不覆盖应用全局模板配置。
 *
 * mode=true 使用框架后台 main 模板且关闭页面缓存；mode=false 复用全局
 * tpl_setting/tpl_cache 创建外层模板。子模板始终关闭页面缓存，防止与外层共用缓存文件。
 * 编译目录按插件和应用隔离；未提供应用信息时使用 myStep。
 *
 * @param string $idx 已存在的插件目录名
 * @param string $page 插件 template 下的模板名（不含扩展名）；空串使用插件目录名（默认：''）
 * @param bool $mode true 使用框架后台外层模板；false 使用当前应用外层模板（默认：true）
 * @return myTemplate[] 下标 0 为外层模板，1 为插件内容模板
 * @throws InvalidArgumentException 插件、页名或模板配置非法
 * @throws LogicException 复用应用模板时缺少配置，或应用标识非法
 * @throws RuntimeException 外层或内容模板不存在/不可读
 */
function setPluginTemplate($idx, $page='', $mode = true) {
    $directory=pluginDirectory($idx);
    if($page!=='' && (!is_string($page) || !preg_match('/\A\w+\z/D',$page))) throw new InvalidArgumentException('Invalid plugin page');
    if($page==='') $page=$idx;
    if($mode) {
        $main_setting=[
            'name'=>'main','path'=>APP.'myStep/template/','style'=>'',
            'path_compile'=>CACHE.'template/plugin/'.$idx.'/'
        ];
        $main_cache=false;
    } else {
        $main_setting=$GLOBALS['tpl_setting'] ?? null;
        $main_cache=$GLOBALS['tpl_cache'] ?? false;
        if(!is_array($main_setting)) throw new LogicException('Application template settings are required');
    }
    $app=$GLOBALS['info_app']['app'] ?? 'myStep';
    if(!is_string($app) || !preg_match('/\A[a-zA-Z][a-zA-Z0-9_]*\z/D',$app)) throw new LogicException('Invalid current application');
    $tpl_main=new myTemplate($main_setting,$main_cache);
    $sub_setting=[
        'name'=>$page,'path'=>$directory.'template/','style'=>'',
        'path_compile'=>CACHE.'template/plugin/'.$idx.'/'.$app.'/'
    ];
    $tpl_sub=new myTemplate($sub_setting,false);
    return [$tpl_main,$tpl_sub];
}

/**
 * 功能说明：检查应用是否提供通用插件后台布局适配器，myStep作为内置缺省后台。
 *
 * 仅检查应用标识及固定文件，不由请求参数指定包含路径，也不读取应用业务配置。
 *
 * @param mixed $app 应用目录名
 * @return bool 应用标识合法且具备插件布局适配器时为true
 */
function pluginPageHost($app) {
    if(!is_string($app) || !preg_match('/\A[a-zA-Z][a-zA-Z0-9_]*\z/D',$app)) return false;
    if($app==='myStep') return true;
    return is_file(APP.$app.'/info.php') && !is_link(APP.$app) && !is_link(APP.$app.'/plugin_layout.php') && is_file(APP.$app.'/plugin_layout.php');
}

/**
 * 功能说明：从可信Referer识别插件页面的外层后台，缺省返回myStep。
 *
 * 支持三种路由、子目录、域名绑定及来源插件页的_plugin_app标记；不修改GET、REQUEST、
 * 当前路由或会话。来源为空、不可信、应用没有通用布局适配器时使用myStep。
 * 只决定展示，不授予登录、插件启用或业务操作权限。
 *
 * @param string|null $referer 完整来源URL；null读取HTTP_REFERER，空串明确使用myStep（默认：null）
 * @return string 通过pluginPageHost验证的展示应用名
 * @global array $domain 当前域名绑定
 * @global myConfig $ms_setting 当前配置，提供旧后台路径和缺省应用
 */
function pluginPageApplication($referer=null) {
    if($referer===null) $referer=$_SERVER['HTTP_REFERER']??'';
    if(!is_string($referer) || $referer==='' || strlen($referer)>8192 || preg_match('/[\x00-\x20\x7f\\\\]/',$referer) || !mySecurity::trustedReferer($referer)) return 'myStep';
    $url=parse_url($referer);parse_str($url['query']??'',$query);
    if(isset($query['_plugin_app']) && pluginPageHost($query['_plugin_app'])) return $query['_plugin_app'];
    $path=$url['path']??'/';$base=rtrim(ROOT_WEB,'/');
    if($base!=='' && !myRouter::hasPrefix($path,$base)) {
        // 跨域策略可能只保留来源域名；仅已有域名绑定时允许据此识别应用。
        if($path!=='/' || !isset($GLOBALS['domain'][strtolower($url['host'])])) return 'myStep';
    } elseif($base!=='') $path=substr($path,strlen($base));
    $script=basename($_SERVER['SCRIPT_NAME']??'index.php');
    if($path==='/'.$script) {
        $head=explode('&',$url['query']??'',2)[0];
        $path=$head!=='' && strpos($head,'=')===false?'/'.ltrim($head,'/'):'/';
    } elseif(myRouter::hasPrefix($path,'/'.$script)) $path=substr($path,strlen('/'.$script));
    $path=rawurldecode($path);$parts=explode('/',trim($path,'/'));
    if(array_intersect($parts,['.','..']) || strpos($path,'\\')!==false || preg_match('/[\x00-\x1f\x7f]/',$path)) return 'myStep';
    $legacy=trim($GLOBALS['ms_setting']->gen->path_admin??'console','/');
    if($legacy!=='' && myRouter::hasPrefix($path,'/'.$legacy)) return 'myStep';
    $app=$parts[0]??'';
    if(!preg_match('/\A[a-zA-Z][a-zA-Z0-9_]*\z/D',$app) || !is_file(APP.$app.'/info.php')) {
        $binding=$GLOBALS['domain'][strtolower($url['host'])]??'';
        if(is_string($binding) && preg_match('#\A/?([a-zA-Z][a-zA-Z0-9_]*)(?:/|\z)#D',$binding,$match)) $app=$match[1];
        else $app=$path==='/'?($GLOBALS['ms_setting']->router->default_app??'myStep'):'myStep';
    }
    if(!is_string($app) || !preg_match('/\A[a-zA-Z][a-zA-Z0-9_]*\z/D',$app) || !is_file(APP.$app.'/info.php') || !pluginPageHost($app)) return 'myStep';
    return $app;
}

/**
 * 功能说明：生成保留插件后台展示标记的页面链接，兼容三种路由及原查询参数。
 *
 * 供插件自己的分页、筛选、表单和返回链接使用；app为空时读取当前插件展示上下文。
 * myStep缺省布局移除标记，不将布局偏好写入会话。只接受站内地址，不改外部链接。
 *
 * @param string $url 站内URL；空串使用当前REQUEST_URI（默认：''）
 * @param array $params 需要合并的查询参数；null值删除参数（默认：array()）
 * @param string|null $app 展示应用；null使用当前上下文，myStep不附加标记（默认：null）
 * @return string 合并参数及有效展示标记后的链接
 * @throws InvalidArgumentException 指定的展示应用未提供适配器
 */
function pluginPageURL($url='', array $params=[], $app=null) {
    if($url==='') $url=$_SERVER['REQUEST_URI']??ROOT_WEB;
    $parts=parse_url($url);
    if($parts===false || isset($parts['host']) || isset($parts['scheme']) || strpos($url,'//')===0) return $url;
    if($app===null) $app=$GLOBALS['plugin_page_app']??'myStep';
    if(!pluginPageHost($app)) throw new InvalidArgumentException('Invalid plugin presentation application');
    $params['_plugin_app']=$app==='myStep'?null:$app;
    return pluginQueryURL($url,$params);
}

/**
 * 功能说明：生成当前展示后台中的设置、缓存等通用功能链接。
 *
 * myStep使用配置的gen.path_admin，其他后台使用其应用名；参数按框架查询规则合并。
 * 应用中另有特殊功能路由时由应用适配器或插件自身生成对应链接。
 *
 * @param string $path 后台内的相对功能路径
 * @param array $params 查询参数（默认：array()）
 * @param string|null $app 展示应用；null使用当前插件上下文（默认：null）
 * @return string 支持当前路由模式的站内后台URL
 * @throws InvalidArgumentException 展示应用不合法
 * @global myConfig $ms_setting 当前配置
 */
function pluginAdminURL($path, array $params=[], $app=null) {
    if($app===null) $app=$GLOBALS['plugin_page_app']??'myStep';
    if(!pluginPageHost($app)) throw new InvalidArgumentException('Invalid plugin presentation application');
    $prefix=$app==='myStep'?trim($GLOBALS['ms_setting']->gen->path_admin,'/'):$app;
    return pluginQueryURL(myStep::setURL($prefix.'/'.ltrim($path,'/')),$params);
}

/**
 * 功能说明：统一装配已启用插件的后台模块及内容模板，返回主体HTML。
 *
 * 不重新启动插件、会话或宿主控制器。模块仍在独立函数作用域中执行，保留tpl、tpl_sub、
 * path_plugin、page_app以及历史全局配置别名；模块设置的content包括空串和0均原样采用。
 * 编译按插件及展示后台隔离，内容不缓存；展示上下文在正常返回或异常时恢复。
 *
 * @param string $idx 插件目录名，必须已安装且启用
 * @param string $page 模块及模板同名页；空串使用插件名（默认：''）
 * @param string|null $app 展示后台；null使用当前上下文或当前应用（默认：null）
 * @param myTemplate|null $tpl 供历史模块使用的外层模板；null自行创建当前外层（默认：null）
 * @return string 插件内容HTML或模块显式提供的字符串
 * @throws InvalidArgumentException 插件未启用、页名或展示应用不合法
 * @throws RuntimeException 框架管理员身份不满足，或模板缺失
 * @throws Throwable 模块及模板异常继续传递
 * @global myStep $mystep 当前控制器
 * @global myConfig $S 当前配置历史别名
 * @global myConfig $ms_setting 当前配置
 * @global array $info_app 当前真实请求及路由信息
 */
function renderPluginPage($idx, $page='', $app=null, $tpl=null) {
    global $mystep,$S,$ms_setting,$info_app;
    $user=$ms_setting->gen->s_usr;
    if(!is_string($user) || $user==='' || myReq::session('ms_user')!==$user || myReq::session('sysop')!=='y') throw new RuntimeException('Administrator login required');
    $path_plugin=pluginDirectory($idx);
    if(!isset(myStep::activePlugins()[$idx])) throw new InvalidArgumentException('Plugin is not active');
    if($page==='') $page=$idx;
    if(!is_string($page) || !preg_match('/\A\w+\z/D',$page)) throw new InvalidArgumentException('Invalid plugin page');
    if(!is_file($path_plugin.'template/'.$page.'.tpl')) throw new RuntimeException('Plugin page template is missing');
    if($app===null) $app=$GLOBALS['plugin_page_app']??($info_app['app']??'myStep');
    if(!pluginPageHost($app)) throw new InvalidArgumentException('Invalid plugin presentation application');
    $had=array_key_exists('plugin_page_app',$GLOBALS);$previous=$GLOBALS['plugin_page_app']??null;
    $GLOBALS['plugin_page_app']=$page_app=$app;
    try {
        if(is_file($path_plugin.'class.php')) require_once $path_plugin.'class.php';
        if(is_dir($path_plugin.'language/')) $mystep->setLanguagePack($path_plugin.'language/',$ms_setting->gen->language);
        if($tpl===null) $tpl=new myTemplate($GLOBALS['tpl_setting'],false);
        $tpl_sub=new myTemplate(['name'=>$page,'path'=>$path_plugin.'template/','style'=>'','path_compile'=>CACHE.'template/plugin/'.$idx.'/'.$app.'/'],false);
        $tpl_sub->assign('root_web',ROOT_WEB);
        $tpl_sub->assign('path_admin',$app==='myStep'?$S->gen->path_admin:$app);
        $tpl_sub->assign('plugin_setting_url',pluginAdminURL('function/plugin/setting/',['idx'=>$idx],$app));
        $tpl_sub->assign('plugin_page_url',pluginPageURL('',[],$app));
        /**
         * 功能说明：隔离模块局部变量，保留历史模板和配置别名及显式输出内容。
         *
         * @param string $path_plugin 已验证的插件绝对目录
         * @param string $page 模块名称
         * @param string $page_app 展示后台名称
         * @param myTemplate $tpl 外层模板历史别名
         * @param myTemplate &$tpl_sub 内容模板；兼容模块替换模板对象
         * @return string|null 模块显式内容，包括空串和0；null表示渲染内容模板
         * @throws Throwable 模块异常继续向调用方传递
         */
        $content=(static function($path_plugin,$page,$page_app,$tpl,&$tpl_sub) {
            global $mystep,$S,$ms_setting,$info_app;
            if(is_file($path_plugin.'module/'.$page.'.php')) include $path_plugin.'module/'.$page.'.php';
            return isset($content)?$content:null;
        })($path_plugin,$page,$page_app,$tpl,$tpl_sub);
        return $content!==null?$content:$mystep->render($tpl_sub);
    } finally {
        if($had) $GLOBALS['plugin_page_app']=$previous;else unset($GLOBALS['plugin_page_app']);
    }
}

/**
 * 功能说明：按当前展示后台统一输出插件管理页，保留原插件路由与业务参数。
 *
 * 自动模式从显式_plugin_app或可信Referer选择布局；GET首次进入其他后台时仅补展示标记，
 * 再渲染页面，不执行模块两次。POST直接处理，保留原业务路由、会话和框架CSRF检查。
 * 外部后台通过app/应用/plugin_layout.php提供返回myTemplate的适配回调，不为每个插件另建页面。
 * 管理页不缓存，CSS/JS缓存按插件和布局隔离；不更改全局应用、配置或模板设置。
 *
 * @param string $idx 已启用的插件目录名
 * @param string $page 模块与模板的同名页；空串使用插件名（默认：''）
 * @param bool|null $mode null自动选择后台；true固定myStep外层；false沿用当前外层（默认：null）
 * @return void 输出管理页或在补充展示标记后重定向；未登录时交给原登录检查
 * @throws InvalidArgumentException 插件、页名或展示应用不合法
 * @throws LogicException 布局适配器没有返回myTemplate或不是回调
 * @throws Throwable 模块、布局和模板异常继续传递
 * @global myStep $mystep 当前宿主控制器
 * @global myConfig $S 当前配置历史别名
 * @global array $info_app 当前实际请求及路由
 */
function showPluginPage($idx, $page='', $mode=null) {
    if(function_exists('app\\myStep\\logCheck') && !app\myStep\logCheck()) return;
    global $mystep,$S,$info_app;
    $directory=pluginDirectory($idx);
    if($page==='') $page=$idx;
    if(!is_string($page) || !preg_match('/\A\w+\z/D',$page) || !is_file($directory.'template/'.$page.'.tpl')) {myStep::info('page_error_plugin');return;}
    $app='myStep';
    if($mode===null) {
        $explicit=$_GET['_plugin_app']??($_POST['_plugin_app']??null);
        $app=$explicit===null?pluginPageApplication():(pluginPageHost($explicit)?$explicit:'myStep');
    }
    if($mode===null && $app!=='myStep' && in_array($_SERVER['REQUEST_METHOD']??'GET',['GET','HEAD'],true) && ($_GET['_plugin_app']??null)!==$app) {
        myStep::redirect(pluginPageURL('',[],$app));return;
    }
    unset($_POST['_plugin_app'],$_REQUEST['_plugin_app']);
    $had=array_key_exists('plugin_page_app',$GLOBALS);$previous=$GLOBALS['plugin_page_app']??null;
    $GLOBALS['plugin_page_app']=$app;
    $previousCSS=myStep::$cache_css;$previousJS=myStep::$cache_js;
    try {
        $fixed=$mode===true || !isset($GLOBALS['tpl_setting']);
        list($tpl,$unused)=setPluginTemplate($idx,$page,$fixed);$tpl->setCacheMode(false);
        if($app==='myStep') include APP.'myStep/global.php';
        $body=renderPluginPage($idx,$page,$app,$tpl);
        if($app==='myStep') $tpl->assign('main',$body);
        else {
            $layout=include APP.$app.'/plugin_layout.php';
            if(!is_callable($layout)) throw new LogicException('Plugin layout adapter must return a callback');
            $title=$mystep->getLanguage('plugin_'.$idx.'_info_name');
            $tpl=$layout($body,['plugin'=>$idx,'page'=>$page,'app'=>$app,'title'=>$title]);
            if(!$tpl instanceof myTemplate) throw new LogicException('Plugin layout adapter must return myTemplate');
            $key=substr(hash('sha256',$app.'|'.(is_file(APP.$app.'/config.php')?hash_file('sha256',APP.$app.'/config.php'):'')),0,16);
            myStep::$cache_css=CACHE.'script/plugin_'.$idx.'_'.$key.'.css';
            myStep::$cache_js=CACHE.'script/plugin_'.$idx.'_'.$key.'.js';
        }
        $mystep->setAddedContent('end','<script src="'.ROOT_WEB.'static/js/checkForm.js"></script>');
        $mystep->show($tpl);
    } finally {
        myStep::$cache_css=$previousCSS;myStep::$cache_js=$previousJS;
        if($had) $GLOBALS['plugin_page_app']=$previous;else unset($GLOBALS['plugin_page_app']);
    }
}


/**
 * 功能说明：将插件 priority.php 原子复制到框架优先脚本目录，覆盖旧版本。
 *
 * 目标为 CONFIG/priority/plugin_插件标识.php；源文件复制后 __DIR__ 会改变，
 * 引用插件资源须使用 PLUGIN 常量。调用本函数不会立即执行优先脚本。
 *
 * @param string $idx 已存在的插件目录名
 * @return bool 安装成功为 true；没有 priority.php 时为 false
 * @throws InvalidArgumentException 插件标识/目录非法，或源脚本为符号链接
 * @throws RuntimeException 无法读取、创建或完整发布脚本
 */
function setPriority($idx) {
    $file=pluginDirectory($idx).'priority.php';
    if(is_link($file)) throw new InvalidArgumentException('Invalid plugin priority script');
    if(!is_file($file)) return false;
    $content=file_get_contents($file);
    if($content===false) throw new RuntimeException('Cannot read plugin priority script');
    return savePluginFile(CONFIG.'priority/plugin_'.$idx.'.php',$content);
}

/**
 * 功能说明：删除插件优先脚本并使 OPcache 失效，支持目录已删除和重复清理。
 *
 * @param string $idx 插件目录名；只用于构造配置文件名，不读取插件目录
 * @return bool 删除成功或脚本原本不存在时为 true
 * @throws InvalidArgumentException 插件标识非法
 * @throws RuntimeException 目标异常地成为目录，或无法删除目标文件
 */
function removePriority($idx) {
    pluginDirectory($idx,false);
    $file=CONFIG.'priority/plugin_'.$idx.'.php';
    if(!file_exists($file) && !is_link($file)) return true;
    if((is_dir($file) && !is_link($file)) || !myFile::del($file)) throw new RuntimeException('Cannot remove plugin priority script');
    if(function_exists('opcache_invalidate')) opcache_invalidate($file,true);
    return true;
}
