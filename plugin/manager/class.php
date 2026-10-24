<?PHP

/**
 * 文件：plugin/manager/class.php
 * 功能说明：框架与应用/插件更新、发布包及文件校验管理插件。
 *
 * 方法目录：
 *   public static plugin_manager::check(&$result = '') — 检查版本资料、校验清单及更新工作目录的可写条件。
 *   public static plugin_manager::install() — 登记更新管理路由、建立工作目录并加入后台菜单。
 *   public static plugin_manager::uninstall() — 移除管理路由、更新暂存和发布包目录及后台菜单。
 *   public static plugin_manager::main() — 在后台登录检查后分派更新检查、上传、安装、归档下载及管理页面。
 *   public static plugin_manager::pageApplication($referer=null) — 从可信Referer的路由或域名绑定识别提供manager页面的应用，缺省myStep。
 *   public static plugin_manager::pageContent($app='myStep') — 准备并渲染manager主体，外层由来源应用的标准模板流程负责。
 *   public static plugin_manager::remote() — 按更新服务开关或调试设置分派版本清单、发布包、差异包及凭据下载。
 *   public static plugin_manager::pack() — 检查后台身份和打包开关后生成框架独立安装包，并输出其地址。
 *   public static plugin_manager::createSystemArchive() — 用独立副本生成完整安装ZIP，保留驱动和组件默认配置并过滤运行数据。
 *   protected static plugin_manager::systemPackageSettings($file) — 清空组件副本配置的声明密码及常用敏感字段。
 *   public static plugin_manager::checkFile($dir='', $build=false, $layer=0, $manifest=null) — 构建或比较文件校验清单，忽略运行时/私有目录与符号链接。
 *   protected static plugin_manager::checksumRules($directory) — 合并扫描与旧清单缺失检查的排除规则
 *   public static plugin_manager::checkConfig($path, $name_fix='_new') — 导出打包用配置副本，逐个清空结构声明的密码，不改写原配置。
 *   public static plugin_manager::replacePackedFile($package,$member,$content) — 流式替换生成归档中的一个文件，供发布包写入默认菜单；不改动在线源文件。
 *   public static plugin_manager::artifactPath($name) — 限定更新/差异/系统下载的 ZIP 文件名和临时目录，拒绝目录穿越及符号链接。
 *   public static plugin_manager::artifactURL($file, $public = false) — 生成经后台认证的归档下载路由，不直接暴露受保护的临时缓存目录。
 *   public static plugin_manager::publicArtifact($name, $token) — 校验公开差异归档的随机下载凭据，仅允许读取对应文件且有效期为15分钟。
 *   public static plugin_manager::installPackage($type, $idx, $file) — 校验应用/插件安装目标并发布本机下载的归档，使用解包器的完整预检及回滚。
 *   public static plugin_manager::createUpdateArchive(array $detail) — 创建保留全部更新操作的离线ZIP，仅写暂存。
 *   public static plugin_manager::applyUpdateArchive($file) — 校验新旧离线ZIP并交给统一发布流程。
 *   public static plugin_manager::applyUpdate(array $detail) — 锁内预检、备份与发布更新，最后提交版本。
 *   protected static plugin_manager::assertUpdateAccess($path, $operation='write', $backup=false) — 按写入、建目录或删除操作预检权限，以网站相对路径报告失败。
 *   public static plugin_manager::identifier($idx) — 校验应用或插件标识，防止目录穿越/任意PHP包含。
 *   public static plugin_manager::updateServer() — 读取插件更新网址，缺少新字段时兼容旧框架配置。
 *   public static plugin_manager::updateBase($server) — 生成更新服务基址，支持显式HTTP/HTTPS，缺省HTTPS且不自动降级；禁止凭据、查询串及片段。
 *   public static plugin_manager::relativePath($name) — 校验更新文件相对路径，兼容清单中的单个前导斜线；排除私有配置和运行时数据。
 *   public static plugin_manager::sourcePath($name) — 将成员解析到框架根目录，禁止任何符号链接穿越；允许尚不存在的更新目标。
 *   public static plugin_manager::decodeUpdate($compressed) — 有界解压/解码更新包，不实例化序列化对象；所有成员在首次落盘前校验。
 *
 * 回调目录：
 *   回调 4：按目录规则收集系统发布副本并统计预算。
 *   回调 5：递归脱敏组件默认配置。
 *   回调 1：在独立作用域中读取更新源已发布文件名的集合。
 *   回调 2：递归校验更新配置，拒绝对象或资源值。
 *   回调 3：按日志表字段宽度写入一行升级记录，兼容旧版日期列。
 *
 * 使用说明：
 *   1. 显式加载主类并由管理流程调用 check/install/uninstall；业务方法的使用见方法目录。
 *   2. main 为后台管理分派器，remote 为受开关限制的更新服务入口；两者权限边界不同。
 *   3. 先验证下载内容和目标路径，再进行源码发布；整站更新和迁移代码不具备整体事务回滚。
 *   4. 临时 ZIP 使用受控下载入口；公开差异包需要与文件绑定且未过期的随机凭据。
 *
 * 说明：PHPDoc 类型用于说明，不增加原生类型约束；源码保持 PHP 7.4 兼容。
 *       方法目录覆盖本文件直接声明；闭包参数及捕获变量说明见各回调旁 PHPDoc。
 */

set_time_limit(600);
/**
 * 功能说明：框架与应用/插件更新、发布包及文件校验管理插件。
 *
 * 使用说明：
 *   1. main 为后台管理分派器，remote 为受开关限制的更新服务入口；两者权限边界不同。
 *   2. 先验证下载内容和目标路径，再进行源码发布；整站更新和迁移代码不具备整体事务回滚。
 *   3. 临时 ZIP 使用受控下载入口；公开差异包需要与文件绑定且未过期的随机凭据。
 *
 * 方法目录：参见本文件顶部；仅列直接声明，不重复列出未在本文件声明的继承或协议方法。
 */
class plugin_manager implements interface_plugin {
    const IGNORE = ['pack/','rollback/','update/','cache/','file/','.git/','.codex/','.agents/','.aws/','.env','error.log','plugin.db','data.db','update.db','ban_ip.php','.svn/', '.log/', '.idea/', 'aspnet_client/', 'Thumbs.db', '.DS_Store', 'ignore','allow', '_bak', '.bak', 'config.php', 'plugin.php', 'construction.php'];
    /**
     * 功能说明：检查版本资料、校验清单及更新工作目录的可写条件。
     *
     * 可写性检查可能创建并清理临时探测文件，不执行远端下载、升级或清单重建。
     *
     * @param string &$result 引用输出：供后台展示的插件安装条件检查说明（默认：''）
     * @return bool 当前基础检查是否通过；不代表远端服务或业务数据库可用
     */
    public static function check(&$result = '') {
        $result = '';
        $theList = array(
            'version.php',
            'check_app.php',
            'check_file.php',
            'check_plugin.php',
            'update.db',
            'pack/',
            'update/',
            'rollback/',
        );
        $flag = true;
        foreach($theList as $cur) {
            if(myFile::rewritable(__DIR__.'/'.$cur)) {
                $result .= $cur . ' - <span style="color:green">Writable</span><br />';
            } else {
                $result .= $cur . ' - <span style="color:red">Readonly</span><br />';
                $flag = false;
            }
        }
        return $flag;
    }
    /**
     * 功能说明：登记更新管理路由、建立工作目录并加入后台菜单。
     *
     * 参数说明：无显式参数。
     *
     * @return void 无返回值，不用返回值表达多资源操作整体成功；依赖异常继续传递
     * @throws RuntimeException 路由源缺失或工作目录创建失败
     * @throws Throwable 菜单或路由发布异常继续向外传递
     * @global myStep $mystep 当前宿主控制器，提供语言、页面及生命周期功能
     */
    public static function install() {
        global $mystep;
        if(!regPluginRoute('manager')) throw new RuntimeException('Missing manager routes');
        if(!myFile::mkdir(__DIR__.'/update') || !myFile::mkdir(__DIR__.'/pack')) throw new RuntimeException('Cannot create manager plugin directories');
        addPluginLink($mystep->getLanguage('plugin_manager_title'), 'manager');
    }
    /**
     * 功能说明：移除管理路由、更新暂存和发布包目录及后台菜单。
     *
     * 保留配置、校验清单及 rollback 恢复资料；不承诺生命周期多文件操作的整体事务回滚。
     *
     * 参数说明：无显式参数。
     *
     * @return void 无返回值，不用返回值表达多资源操作整体成功；依赖异常继续传递
     */
    public static function uninstall() {
        removePluginRoute('manager');
        myFile::del(__DIR__.'/update');
        myFile::del(__DIR__.'/pack');
        removePluginLink('manager');
    }
    /**
     * 功能说明：在后台登录检查后分派更新检查、上传、安装、归档下载及管理页面。
     *
     * 版本检查返回更新源实际框架版本、可用升级版本、实时应用／插件清单及由 PHP version_compare 计算的更新状态，供页面无刷新同步表格。
     * 先检查框架后台身份；manager/file 只读取限定名称的归档，无需连接远端更新源。
     * 写操作的方法和 CSRF 保护由框架入口执行；不同分支可能直接发送文件或结束请求。
     *
     * 参数说明：无显式参数。
     *
     * @return void 未通过登录检查时返回；通过时输出管理响应，部分分支直接结束请求
     * @throws RuntimeException 发布、存储或数据缓存清理失败
     * @throws InvalidArgumentException 更新源、包标识或目标路径非法
     * @global myStep $mystep 当前宿主控制器，提供语言、页面及生命周期功能
     * @global array $info_app 当前路由、应用、路径及参数信息
     * @global myConfig $ms_setting 当前框架/应用配置
     */
    public static function main() {
        global $mystep, $info_app, $ms_setting;
        if(!app\myStep\logCheck()) return;
        if(($info_app['path'][1] ?? '') === 'file') {
            try {$file = self::artifactPath(myReq::get('name','!'));}
            catch(InvalidArgumentException $error) {myStep::header('404');return;}
            if(!is_file($file)) {myStep::header('404');return;}
            myStep::file($file);
            return;
        }
        if(!isset($info_app['path'][1])) $info_app['path'][1] = '';
        $header = array();
        $header['Referer'] = (isHttps()?'https':'http').'://'.myReq::server('HTTP_HOST');
        $header['ms_sign'] = time();
        $dir = __DIR__;
        switch($info_app['path'][1]) {
            case 'build':
                if(!self::checkFile(ROOT, true)) {
                    echo 'error';
                }
                break;
            case 'check':
                $url = self::updateBase(self::updateServer()).'/api/plugin_manager/';
                $ver = require(CONFIG.'version.php');
                $ver_remote = myString::fromJson(myFile::getRemote($url.'check/version?v='.$ver, $header));
                if(!is_array($ver_remote) || !isset($ver_remote['version']) || !is_string($ver_remote['version']) || !is_array($ver_remote['info'] ?? null) || !is_array($ver_remote['app'] ?? null) || !is_array($ver_remote['plugin'] ?? null)) {
                    echo json_encode(['error'=>1,'version'=>'','message'=>'Cannot read update server version']);
                    break;
                }
                $component_versions = [];
                $component_updates = [];
                foreach(['app','plugin'] as $type) {
                    $versions=[];
                    foreach($ver_remote[$type] as $idx=>$version) {
                        if(!is_string($idx) || !preg_match('/\A[a-zA-Z][a-zA-Z0-9_]*\z/D',$idx) || !is_string($version)) continue;
                        $versions[$idx]=$version;
                    }
                    $component_versions[$type] = $versions;
                    $component_updates[$type] = [];
                    foreach($versions as $idx => $remote_version) {
                        $local_file = ($type === 'app' ? APP : PLUGIN).$idx.'/info.php';
                        if(!is_file($local_file)) continue;
                        $local_info = include $local_file;
                        if(!is_array($local_info) || !is_string($local_info['ver'] ?? null)) continue;
                        $component_updates[$type][$idx] = version_compare($remote_version, $local_info['ver'], '>');
                    }
                    $script='<?php return '.var_export($versions,true).';';
                    if(myFile::saveFile(__DIR__.'/check_'.$type.'.php',$script)!==strlen($script)) throw new RuntimeException('Cannot save remote version list');
                }
                $server_version = is_string($ver_remote['server_version'] ?? null) ? $ver_remote['server_version'] : '';
                $server_compare = $server_version === '' ? null : version_compare($server_version, $ver);
                $latest=$ver_remote['version'];
                if($latest!=='' && version_compare($latest,$ver)<=0) $latest='';
                $detail=[];
                foreach($ver_remote['info'] as $version=>$description) {
                    if(!is_scalar($description) || version_compare((string)$version,$ver)<=0) continue;
                    $detail[$version]=trim((string)$description);
                }
                uksort($detail,'version_compare');
                header('Cache-Control: private, no-store');
                echo myString::toJson(['version'=>$latest,'server_version'=>$server_version,'server_compare'=>$server_compare,'detail'=>$detail,'app'=>$component_versions['app'],'plugin'=>$component_versions['plugin'],'updates'=>$component_updates],$ms_setting->gen->charset);
                break;
            case 'check_local':
                $result = self::checkFile();
                echo myString::toJson($result, $ms_setting->gen->charset);
                break;
            case 'check_server':
                $url = self::updateBase(self::updateServer()).'/api/plugin_manager/';
                $check_info=json_decode(myFile::getRemote($url.'check',$header),true);
                if(!is_array($check_info) || !isset($check_info['list_file'],$check_info['list_file_md5']) || !is_array($check_info['list_file']) || !is_array($check_info['list_file_md5'])) {
                    echo json_encode(['code'=>1,'error'=>'Cannot parse update manifest']);
                } else echo myString::toJson(self::checkFile(ROOT,false,0,$check_info),$ms_setting->gen->charset);
                break;
            case 'empty':
                myFile::del($dir.'/update.db');
                $dir .= '/update';
                myFile::del($dir);
                myFile::mkdir($dir);
                break;
            case 'export':
                $mydb = new myDb('simpleDB', 'update', __DIR__.'/');
                if($mydb->check()) {
                    $data = $mydb->select();
                    $xls = new MyExcel('update', 'info');
                    $xls->addRow();
                    $fields = ['date', 'idx', 'remote', 'local', 'ip', 'referer'];
                    $xls->addCells($fields);
                    for($i=0,$m=count($data);$i<$m;$i++) {
                        $xls->addRow();
                        $xls->addCells(array_values($data[$i]));
                    }
                    $mydb->close();
                    $xls->make();
                } else {
                    myStep::info($mystep->getLanguage('plugin_manager_update_data_empty'));
                }
                break;
            case 'upload':
                $result = ['error'=>1, 'message'=>'No file uploaded!'];
                $theFile = null;
                try {
                    if(myReq::check('files')) {
                        $upload = new myUploader(CACHE.'tmp/', true);
                        $upload->do(false);
                        $uploaded = $upload->result()[0] ?? [];
                        if(($uploaded['error'] ?? 1)!==0) throw new RuntimeException($uploaded['message'] ?? 'Invalid upload');
                        $theFile = CACHE.'tmp/'.$uploaded['new_name'];
                        self::applyUpdateArchive($theFile);
                        $result = ['error'=>0, 'message'=>$mystep->getLanguage('plugin_manager_done')];
                    }
                } catch(Throwable $error) {
                    $result = ['error'=>1, 'message'=>$error->getMessage()];
                } finally {
                    if($theFile !== null) myFile::del($theFile);
                }
                echo myString::toJson($result, $ms_setting->gen->charset);
                break;
            case 'download':
                $result = ['error'=>0, 'info'=>'', 'link'=>''];
                try {
                    $mode = $info_app['para']['m'] ?? '0';
                    if(!in_array($mode, [0,1,'0','1'],true)) throw new InvalidArgumentException('Invalid update mode');
                    $url = self::updateBase(self::updateServer()).'/api/plugin_manager/';
                    $ver = require(CONFIG.'version.php');
                    $detail = self::decodeUpdate(myFile::getRemote($url.'download?v='.rawurlencode($ver), $header));
                    if((int)$mode===1) {
                        self::applyUpdate($detail);
                        $result['info'] = sprintf($mystep->getLanguage('plugin_manager_file'), count($detail['file']));
                    } elseif($detail['file'] || $detail['setting'] || $detail['code']) {
                        $result['link'] = self::artifactURL(self::createUpdateArchive($detail));
                        $result['info'] = $mystep->getLanguage('plugin_manager_error');
                    } else {
                        $result['info'] = sprintf($mystep->getLanguage('plugin_manager_file'), 0);
                    }
                } catch(Throwable $error) {
                    $result = ['error'=>1, 'info'=>$error->getMessage(), 'link'=>''];
                }
                ob_clean();
                echo myString::toJson($result, $ms_setting->gen->charset);
                break;
            case 'plugin':
            case 'app':
                $url = self::updateBase(self::updateServer()).'/api/plugin_manager/';
                $type = $info_app['path'][1];
                $idx = self::identifier(end($info_app['path']));
                $base = $type === 'app' ? APP : PLUGIN;
                $info = is_file($base.$idx.'/info.php') ? include($base.$idx.'/info.php') : [];
                $url .= $type.'?'.http_build_query([$type === 'app' ? 'a' : 'p'=>$idx,'v'=>$info['ver'] ?? '0'],'','&',PHP_QUERY_RFC3986);
                $file = CACHE.'tmp/'.$type.'_'.bin2hex(random_bytes(8)).'.pack';
                $success = false;
                try {
                    if(myFile::getRemoteFile($url, $file) && is_file($file)) $success = self::installPackage($type, $idx, $file);
                } finally {myFile::del($file);}
                myStep::info($success ? 'plugin_manager_update_succeed' : 'plugin_manager_update_fail');
                break;
            case 'pack':
                $type = $info_app['path'][2] ?? '';
                $idx = $info_app['path'][3] ?? '';
                if(!in_array($type,['app','plugin'],true)) myStep::info('error_para');
                self::identifier($idx);
                if($type=='app') {
                    $dir = APP.$idx;
                } else {
                    $dir = PLUGIN.$idx;
                }
                $file = CACHE.'tmp/'.$type.'_'.$idx.'.pack';
                myFile::del($file);
                self::checkConfig($dir);
                $mypacker = $mystep->getInstance('myPacker', $dir, $file);
                $mypacker->addIgnore(self::IGNORE);
                if(!$mypacker->pack()) myStep::info('plugin_manager_update_fail');
                myFile::del($dir.'/config_new.php');
                myStep::file($file);
                break;
            default:
                showPluginPage('manager');
        }
        $mystep->end();
    }
    /**
     * 功能说明：从可信链接来源识别manager页面的宿主应用，未匹配时使用myStep。
     *
     * 支持重写、PATH_INFO、query_string、子目录及已登记域名绑定；不调用路由器解析
     * Referer，避免覆盖当前GET或路由状态。委托通用pluginPageApplication识别提供
     * plugin_layout.php的应用；保留此方法供旧代码兼容调用。
     * 仅用于选择展示入口；Referer不能作为登录凭据，也不决定更新、下载等动作权限。
     *
     * @param string|null $referer 完整来源URL；null读取HTTP_REFERER，空串明确使用myStep（默认：null）
     * @return string 提供通用插件布局的应用目录名，缺省myStep
     * @global array $domain 当前站点登记的域名绑定
     * @global myConfig $ms_setting 当前配置，提供缺省应用及可信来源范围
     */
    public static function pageApplication($referer=null) {
        return pluginPageApplication($referer);
    }

    /**
     * 功能说明：准备manager数据及功能主体，交给应用自己的myTemplate外层展示。
     *
     * 委托公共renderPluginPage加载主体，保留旧版/Admin/manager/入口兼容。
     * 不切换全局应用、会话、模板配置或控制器，不执行更新动作。内容页关闭页面缓存，
     * 编译目录按应用隔离；应用可在自己的对应模板中装配或包装返回的HTML。
     *
     * @param string $app 当前展示应用，须与info_app.app一致（默认：'myStep'）
     * @return string manager功能主体HTML
     * @throws InvalidArgumentException 应用标识不合法或不属于当前应用
     * @throws RuntimeException 未通过框架管理员登录检查
     * @throws Throwable 模块读取或模板渲染异常继续传递
     * @global myStep $mystep 当前宿主控制器
     * @global myConfig $ms_setting 当前应用配置
     * @global array $info_app 当前请求路由及应用信息
     */
    public static function pageContent($app='myStep') {
        self::identifier($app);
        if(($GLOBALS['info_app']['app']??'myStep')!==$app) throw new InvalidArgumentException('Invalid manager presentation application');
        return renderPluginPage('manager','manager',$app);
    }

    /**
     * 功能说明：按更新服务开关或调试设置分派版本清单、发布包、差异包及凭据下载。
     *
     * 这是更新服务入口，不是普通后台页面；开关关闭且非调试模式时返回404。
     * 公开差异包下载仅凭有效、未过期且与文件绑定的随机凭据授权；不开放任意临时文件。
     * version 分支的 server_version 为 config/version.php 实际版本，version 为发布清单中可提供的升级版本，两者分别返回。
     * 大部分分支直接输出 JSON 或二进制内容，文件响应可能直接结束请求。
     *
     * 参数说明：无显式参数。
     *
     * @return void 直接输出 JSON、文件内容或包地址，不返回业务数组；部分分支结束请求
     * @global myConfig $ms_setting 当前框架/应用配置
     * @global array $info_app 当前路由、应用、路径及参数信息
     * @global myStep $mystep 当前宿主控制器，提供语言、页面及生命周期功能
     */
    public static function remote() {
        global $ms_setting, $info_app, $mystep;
        $setting = new myConfig(__DIR__.'/config.php');
        if(!$setting->update && !$ms_setting->gen->debug) myStep::header('404');
        $method = end($info_app['path']);
        switch($method) {
            case 'artifact':
                $file = self::publicArtifact(myReq::get('name','!'),myReq::get('token','!'));
                if($file === false) {myStep::header('404');return;}
                myStep::file($file);
                return;
            case 'version':
                $ver = $info_app['para']['v'] ?? '0';
                $detail = require(__DIR__.'/version.php');
                uksort($detail, 'version_compare');
                $result = ['version'=>'', 'server_version'=>(string)require(CONFIG.'version.php'), 'info'=>[]];
                foreach($detail as $k => $v) {
                    if(version_compare($k, $ver)>0) {
                        $result['info'][$k] = $v['info'];
                        $result['version'] = $k;
                    }
                }
                $apps = [];
                $dirs = myFile::find('*', APP, false, myFile::DIR);
                foreach($dirs as $k) {
                    $k .= 'info.php';
                    if(!is_file($k)) continue;
                    $info = include($k);
                    $apps[$info['app']] = $info['ver'];
                }
                $result['app'] = $apps;

                $plugins = [];
                $files = myFile::find('*', PLUGIN, false, myFile::DIR);
                foreach($files as $k) {
                    $k .= 'info.php';
                    if(!is_file($k)) continue;
                    $info = include($k);
                    $plugins[$info['idx']] = $info['ver'];
                }
                $result['plugin'] = $plugins;

                echo myString::toJson($result, $ms_setting->gen->charset);
                break;
            case 'download':
                $v = require(CONFIG.'version.php');
                $v_remote = $info_app['para']['v'] ?? '0';
                $version = require(__DIR__.'/version.php');
                uksort($version, 'version_compare');
                $fingerprint = serialize($version);
                foreach($version as $release => $changes) {
                    if(version_compare($release,$v_remote)<=0) continue;
                    foreach($changes['file'] as $name) {
                        $path = self::sourcePath($name);
                        $fingerprint .= $name.':'.(is_file($path) ? hash_file('sha256',$path) : (is_dir($path) ? 'dir' : 'missing'));
                    }
                }
                $cache_file = __DIR__.'/update/'.hash('sha256',$v.'|'.$v_remote.'|'.$fingerprint);
                if(file_exists($cache_file)) {
                    $result = myFile::getLocal($cache_file);
                } else {
                    $list_file = array();
                    $list_setting = array();
                    $list_code = array();
                    foreach($version as $key => $value) {
                        if(version_compare($key, $v_remote)>0) {
                            $list_file = array_merge($list_file, $value['file']);
                            if(isset($value['setting'])) $list_setting = arrayMerge($list_setting, $value['setting']);
                            if(isset($value['code'])) $list_code[] = $value['code'];
                        }
                    }
                    $list_file = array_values(array_unique($list_file));
                    $result = array('file'=>$list_file, 'content'=>array(), 'kind'=>array(), 'setting'=>$list_setting, 'code'=>$list_code);
                    for($i=0,$m=count($result['file']); $i<$m; $i++) {
                        if(file_exists(self::sourcePath($result['file'][$i]))) {
                            if(is_dir(self::sourcePath($result['file'][$i]))) {
                                $result['content'][$i] = '.';
                                $result['kind'][$i] = 'directory';
                            } else {
                                $result['content'][$i] = myFile::getLocal(self::sourcePath($result['file'][$i]));
                                $result['kind'][$i] = 'file';
                            }
                        } else {
                            $result['content'][$i] = '';
                            $result['kind'][$i] = 'delete';
                        }
                    }
                    $result = serialize($result);
                    $result = gzdeflate($result, 9);
                    myFile::saveFile($cache_file, $result);
                }
                $mydb = new myDb('simpleDB', 'update', __DIR__.'/');
                if(!$mydb->check()) {
                    $mydb->create(array(
                        array('date', 19),
                        array('idx', 40),
                        array('ver_remote', 30),
                        array('ver_local', 30),
                        array('remote_ip', 50),
                        array('referer', 200)
                    ));
                }
                $data = array (
                    date('Y-m-d H:i:s'),
                    md5($v.$v_remote),
                    $v_remote,
                    $v,
                    myReq::ip(),
                    myReq::server('HTTP_REFERER')
                );
                try {
                    $mydb->mutate(
                    /**
                     * 功能说明：按已有字段宽度写入升级日志，兼容旧版10字节日期列。
                     *
                     * 闭包依赖：通过 use 捕获本次日志 data；长来源地址仅影响日志展示。
                     *
                     * @param array &$rows 引用输出：待保存的日志行
                     * @param array $schema 字段名到字节宽度的映射
                     * @return bool 日志行已加入内存后返回true
                     */
                    static function(&$rows,$schema) use ($data) {
                        $row=[];$i=0;
                        foreach($schema as $key=>$width) {
                            $value=(string)($data[$i++] ?? '');
                            if($key==='date' && $width===10) $value=substr($value,0,10);
                            $row[$key]=substr(str_replace(["\0","\r","\n"],' ',$value),0,$width);
                        }
                        $rows[]=$row;return true;
                    });
                } finally {$mydb->close();}
                echo $result;
                break;
            case 'plugin':
                $idx = myReq::get('p');
                self::identifier($idx);
                $ver = myReq::get('v');
                $file = PLUGIN.$idx.'/info.php';
                if(is_file($file)) {
                    $info = include($file);
                    if(version_compare($ver, $info['ver'])===-1) {
                        $file = __DIR__.'/pack/plugin_'.$idx.'_'.$info['ver'].'.pack';
                        if(!file_exists($file)) {
                            self::checkConfig(PLUGIN.$idx);
                            $mypacker = $mystep->getInstance('myPacker', PLUGIN.$idx, $file);
                            $mypacker->addIgnore(self::IGNORE);
                            if(!$mypacker->pack()) myStep::info('plugin_manager_update_fail');
                            myFile::del(PLUGIN.$idx.'/config_new.php');
                        }
                        myController::file($file);
                    } else {
                        myController::header('404');
                    }
                } else {
                    myController::header('404');
                }
                break;
            case 'app':
                $idx = myReq::get('a');
                self::identifier($idx);
                $ver = myReq::get('v');
                $file = APP.$idx.'/info.php';
                if(is_file($file)) {
                    $info = include($file);
                    if(version_compare($ver, $info['ver'])==-1) {
                        $file = __DIR__.'/pack/app_'.$idx.'_'.$info['ver'].'.pack';
                        if(!file_exists($file)) {
                            self::checkConfig(APP.$idx);
                            $mypacker = $mystep->getInstance('myPacker', APP.$idx, $file);
                            $mypacker->addIgnore(self::IGNORE);
                            if(!$mypacker->pack()) myStep::info('plugin_manager_update_fail');
                            myFile::del(APP.$idx.'/config_new.php');
                        }
                        myController::file($file);
                    } else {
                        myController::header('404');
                    }
                } else {
                    myController::header('404');
                }
                break;
            case 'difference':
                $list_file = myReq::post();
                $refer = myReq::svr('referer');
                $list_file = json_decode(htmlspecialchars_decode($list_file['data'] ?? ''),true);
                $published=[];$list_file_md5=[];
                if(is_file(__DIR__.'/check_file.php')) {(
                /**
                 * 功能说明：在独立作用域中读取更新源已发布文件名的集合。
                 *
                 * @param array &$published 引用输出：以已发布文件名为键、true 为值的查找集合
                 * @return void 原地更新 published，避免清单变量泄漏到外层
                 */
                static function(&$published) {$list_file=[];include __DIR__.'/check_file.php';$published=array_fill_keys($list_file,true);})($published);}
                if(!is_array($list_file) || count($list_file)>10000 || empty($refer)) {
                    $check_info = ['error'=>'Bad Request!'];
                } elseif(empty($list_file)) {
                    $check_info = ['error'=>'All Files have been up to date!'];
                } else {
                    $id = bin2hex(random_bytes(8));
                    $zipfile = self::artifactPath('difference_'.$id.'.zip');
                    $dir = CACHE.'tmp/manager/work_'.$id.'/';
                    for($i=0,$m=count($list_file); $i<$m; $i++) {
                        if(!is_string($list_file[$i]) || !isset($published[$list_file[$i]])) continue;
                        try {$source=self::sourcePath($list_file[$i]);$relative=self::relativePath($list_file[$i]);} catch(InvalidArgumentException $error) {continue;}
                        if(is_file($source)) myFile::copy($source,$dir.$relative);

                    }
                    $zip = new myZip($zipfile, $dir);
                    $packed = $zip->zip($dir);
                    myFile::del($dir);
                    $check_info = $packed ? ['link'=>self::artifactURL($zipfile, true), 'info'=>'The file is downloading...'] : ['error'=>'Cannot create difference archive'];
                }
                echo myString::toJson($check_info, $ms_setting->gen->charset);
                break;
            default:
                $the_file = __DIR__.'/check_file.php';
                $check_info = ['list_file'=>[], 'list_file_md5'=>[]];
                if(file_exists($the_file)) {
                    include($the_file);
                    $check_info['list_file'] = $list_file;
                    $check_info['list_file_md5'] = $list_file_md5;
                    unset($list_file, $list_file_md5);
                } else {
                    $check_info = ['error'=>'No verify data found from the update server!'];
                }
                echo myString::toJson($check_info, $ms_setting->gen->charset);
        }
    }
    /**
     * 功能说明：检查后台身份和打包开关后生成框架独立安装包，并输出其地址。
     *
     * 使用临时副本导出默认菜单和脱敏配置，按内容摘要复用安装包；不修改在线配置。
     * 返回受认证的manager/file地址，支持子目录和三种路由模式。
     *
     * 参数说明：无显式参数。
     *
     * @return void 直接输出 JSON、文件内容或包地址，不返回业务数组；部分分支结束请求
     * @global myConfig $ms_setting 当前框架/应用配置
     */
    public static function pack() {
        if(!app\myStep\logCheck()) return;
        global $ms_setting;
        $setting = new myConfig(__DIR__.'/config.php');
        if(!$setting->pack && !$ms_setting->gen->debug) myStep::header('404');
        echo self::artifactURL(self::createSystemArchive());
    }
    /**
     * 功能说明：在独立暂存副本中生成完整系统安装ZIP，按导出内容摘要缓存。
     *
     * 遵循各目录allow／ignore；保留lib/cache源码、应用／插件功能配置及结构定义，
     * 排除在线根配置、绑定、插件注册、日志、上传及运行数据。副本配置先脱敏，
     * 菜单采用发布默认值；不会在在线目录生成config_new.php或改写现有文件。
     *
     * 参数说明：无显式参数。
     *
     * @return string CACHE/tmp/manager中system_内容摘要.zip的绝对路径
     * @throws RuntimeException 扫描、锁定、暂存、必要源码、脱敏或打包失败
     * @throws InvalidArgumentException 版本标识、目录规则或配置数据不合法
     */
    public static function createSystemArchive() {
        $version=require CONFIG.'version.php';
        if(!is_string($version) || !preg_match('/\A[0-9A-Za-z][0-9A-Za-z._-]{0,63}\z/D',$version)) throw new InvalidArgumentException('Invalid framework version');
        $base=myFile::extractionPath(CACHE,'tmp/manager');
        if($base===false || !myFile::mkdir($base) || is_link($base.'/system.lock')) throw new RuntimeException('Cannot prepare system package');
        $lock=fopen($base.'/system.lock','c');$work=$base.'/system-work_'.bin2hex(random_bytes(8));
        if(!$lock) throw new RuntimeException('Cannot open system package lock');
        try {
            if(!flock($lock,LOCK_EX|LOCK_NB)) throw new RuntimeException('Another system package is in progress');
            if(!mkdir($work,0700)) throw new RuntimeException('Cannot create private package workspace');
            $tree=$work.'/source/';$release=$work.'/mystep_v'.$version.'/';
            if(!myFile::mkdir($tree) || !myFile::mkdir($release)) throw new RuntimeException('Cannot stage system package');
            $configs=[];$count=0;$bytes=0;
            $scan=
            /**
             * 功能说明：按目录规则收集发布文件，在私有副本保存并登记待脱敏配置。
             *
             * 闭包依赖：引用scan递归、configs／count／bytes累计状态；tree为副本根。
             *
             * @param string $relative 当前目录相对于ROOT的路径，根目录为空串
             * @return void 当前目录收集完成后返回
             * @throws RuntimeException 文件读取、预算或副本保存失败
             * @throws InvalidArgumentException allow／ignore成员包含非法路径
             */
            function($relative) use (&$scan,&$configs,&$count,&$bytes,$tree) {
                $source=ROOT.$relative;$rules=myPacker::directoryRules($source);
                if($rules['closed']) return;
                foreach(new DirectoryIterator($source) as $entry) {
                    if($entry->isDot() || $entry->isLink()) continue;
                    $name=$entry->getFilename();$member=$relative.$name;
                    if(!myPacker::allowsEntry($name,$entry->isDir(),$rules)) continue;
                    if($relative==='' && in_array($name,['test','tests','maintenance','AGENTS.md'],true)) continue;
                    if(in_array($name,['ignore','allow','config_new.php'],true) || ($name[0]==='.' && $name!=='.htaccess')) continue;
                    $defaults=array_diff(self::IGNORE,['cache/','config.php','construction.php']);
                    $documentation=strpos($member,'docs/')===0;
                    if($documentation) $defaults=array_diff($defaults,['cache/']);
                    // 功能模块 plugin.php 不是应用根目录的插件绑定配置。
                    if(preg_match('#\A(?:app|plugin)/[A-Za-z][A-Za-z0-9_]*/module/(?:[A-Za-z0-9_]+/)*plugin\.php\z#D',$member)) $defaults=array_diff($defaults,['plugin.php']);
                    if(in_array($name,$defaults,true) || in_array($name.'/',$defaults,true) || preg_match('/(?:\.db(?:-wal|-shm|-journal)?|\.sqlite3?|\.lock|\.log|\.bak)$/i',$name) || strpos($name,'_bak')!==false) continue;
                    if($member==='files' || (in_array($name,['cache','session'],true) && !$documentation && !in_array($member,['lib/cache','lib/session'],true))) continue;
                    if(in_array($member,['config/config.php','config/api.php','config/domain.php','config/route.php','config/route.php.sources.json','config/priority'],true)) continue;
                    if($name==='config.php' && !preg_match('#\A(?:app|plugin)/[A-Za-z][A-Za-z0-9_]*/config\.php\z#D',$member)) continue;
                    if(++$count>10000) throw new RuntimeException('System package member budget exceeded');
                    if($entry->isDir()) {$scan($member.'/');continue;}
                    if(!$entry->isFile() || ($bytes+=$entry->getSize())>268435456) throw new RuntimeException('System package size budget exceeded');
                    if(!myFile::mkdir(dirname($tree.$member)) || !copy($entry->getPathname(),$tree.$member)) throw new RuntimeException('Cannot copy system package member');
                    if($name==='config.php') $configs[]=$tree.$member;
                }
            };
            $scan('');
            foreach($configs as $file) {
                $defaults=dirname($file).'/config_default.php';
                if(is_file($defaults)) {
                    if(!copy($defaults,$file)) throw new RuntimeException('Cannot copy component defaults');
                } else {
                    $data=self::systemPackageSettings($file);
                    $script='<?php return '.var_export($data,true).';';
                    if(myFile::saveFile($file,$script)!==strlen($script)) throw new RuntimeException('Cannot stage component defaults');
                }
            }
            if(is_file(__DIR__.'/menu.json') && !copy(__DIR__.'/menu.json',$tree.'app/myStep/menu.json')) throw new RuntimeException('Cannot stage default menu');
            foreach(['index.php','lib/myStep.class.php','lib/cache/myCacheCodec.class.php','lib/session/sess_file.class.php','vendor/autoload.php','config/config_default.php','config/construction/default.php','app/myStep/config.php'] as $required) if(!is_file($tree.$required)) throw new RuntimeException('Required system package member is missing: '.$required);
            foreach(['index.php'=>__DIR__.'/install.php','readme.md'=>ROOT.'readme.md','install-assets/jquery.js'=>STATICS.'js/jquery.js','install-assets/bootstrap.css'=>STATICS.'css/bootstrap.css'] as $member=>$file) {
                if(!myFile::mkdir(dirname($release.$member)) || !copy($file,$release.$member)) throw new RuntimeException('Cannot stage installer assets');
            }
            $fingerprints=[];
            foreach([$tree,$release] as $root) foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS)) as $file) {
                if($file->isFile()) $fingerprints[($root===$tree?'source/':'installer/').substr($file->getPathname(),strlen($root))]=hash_file('sha256',$file->getPathname());
            }
            ksort($fingerprints);$archive=self::artifactPath('system_'.hash('sha256',serialize([$version,$fingerprints])).'.zip');
            $digest=myFile::extractionPath(CACHE,'tmp/manager/'.basename($archive).'.sha256');
            if($digest===false) throw new RuntimeException('Invalid system package checksum path');
            if(is_file($archive) && is_file($digest) && hash_equals(trim(file_get_contents($digest)),hash_file('sha256',$archive))) return $archive;
            if(!(new myPacker($tree,$release.'mystep.pack'))->pack()) throw new RuntimeException('Cannot create system payload');
            if(filesize($release.'mystep.pack')>67108864) throw new RuntimeException('System payload exceeds installer size limit');
            if(!(new myZip($archive,$work.'/'))->zip($release)) throw new RuntimeException('Cannot create system archive');
            $checksum=hash_file('sha256',$archive);
            if(myFile::saveFile($digest,$checksum)!==strlen($checksum)) throw new RuntimeException('Cannot save system package checksum');
            return $archive;
        } finally {myFile::del($work);flock($lock,LOCK_UN);fclose($lock);}
    }
    /**
     * 功能说明：读取副本中的组件配置，清空结构声明和常用敏感字段，保留功能设置。
     *
     * 脱敏只作用于发布副本；应用／插件仍应在construction中明确声明密码字段。
     * 源码中的任意硬编码凭据不由配置导出器识别。
     *
     * @param string $file 暂存副本中应用或插件的config.php路径
     * @return array 已脱敏配置，可序列化为PHP配置默认值
     * @throws InvalidArgumentException 配置或密码声明无效
     * @throws Throwable 配置加载异常继续传递
     */
    protected static function systemPackageSettings($file) {
        $data=(new myConfig($file))->get();
        $construction=dirname($file).'/config/construction.php';
        if(is_file($construction)) {
            $schema=include $construction;
            foreach($schema['password'] ?? [] as $key=>$path) {
                if($key==='func') continue;
                if(!is_string($path) || !preg_match('/\A\w+(?:\.\w+)*\z/D',$path)) throw new InvalidArgumentException('Invalid package password declaration');
                $parts=explode('.',$path);$last=array_pop($parts);$node=&$data;$found=true;
                foreach($parts as $part) {if(!isset($node[$part]) || !is_array($node[$part])) {$found=false;break;}$node=&$node[$part];}
                if($found && is_array($node) && array_key_exists($last,$node)) $node[$last]='';
                unset($node);
            }
        }
        $redact=
        /**
         * 功能说明：递归清空常用敏感键，补充组件结构声明的脱敏结果。
         *
         * 闭包依赖：引用redact自身进行递归。
         *
         * @param array $values 当前层配置数组
         * @return array 已复制并脱敏的配置层
         */
        function(array $values) use (&$redact) {
            foreach($values as $key=>$value) {
                if(preg_match('/(?:^|_)(?:password|passwd|pwd|secret|token|private_key|api_key)$/i',(string)$key)) $values[$key]='';
                elseif(is_array($value)) $values[$key]=$redact($value);
            }
            return $values;
        };
        return $redact($data);
    }
    /**
     * 功能说明：构建或比较文件校验清单，忽略运行时/私有目录与符号链接。
     *
     * @param string $dir 扫描目录，默认框架根目录
     * @param bool $build true生成本地清单，false只比较（默认：false）
     * @param int $layer 内部递归层级，外部调用保持0（默认：0）
     * @param array|null $manifest 可选远端list_file/list_file_md5数组，比较时不写本地清单（默认：null）
     * @return array|bool|null 比较返回new/mod/miss；构建成功true，无清单false，忽略目录null
     * @global array $list_file 引用更新的文件路径清单
     * @global array $list_file_md5 引用更新的文件摘要清单
     */
    public static function checkFile($dir='', $build=false, $layer=0, $manifest=null) {
        global $list_file, $list_file_md5;
        if($layer==0) {
            $list_file = array();
            $list_file_md5 = array();
        }
        $the_dir = __DIR__.'/';
        $the_file = $the_dir.'check_file.php';
        if(empty($dir)) $dir = ROOT;
        $dir = myFile::realPath($dir);
        if(($handle = opendir($dir))===false) return false;
        $rules = self::checksumRules($dir);
        if($rules['closed']) {closedir($handle);return;}
        if($build) {
            while (false !== ($file = readdir($handle))) {
                if(trim($file, '.') == '') continue;
                if(basename($file) == 'menu.json' || ($layer===0 && $file==='files')) continue;
                if(!myPacker::allowsEntry($file,is_dir($dir.$file),$rules)) continue;
                if(is_link($dir.$file)) continue;
                if(strpos($file, '.bak')!==false || strpos($file, '_bak')!==false) continue;
                $the_name = $dir.$file;
                try {self::relativePath('/'.ltrim(substr($the_name,strlen(rtrim(realpath(ROOT),'/'))),'/'));} catch(InvalidArgumentException $error) {continue;}
                if($the_name==$the_file) continue;
                if(is_dir($the_name)) {
                    self::checkFile($the_name, true, $layer+1);
                } else {
                    $list_file[] = '/'.ltrim(substr($the_name,strlen(rtrim(realpath(ROOT),'/'))),'/');
                    $list_file_md5[] = md5_file($the_name);
                }
            }
            if($layer==0) {
                $content = '<?PHP
$list_file = '.var_export($list_file, true).';
$list_file_md5 = '.var_export($list_file_md5, true).';
';
                $result = myFile::saveFile($the_file, $content)===strlen($content);
            }
            if($layer!==0) $result = true;
        } else {
            if($layer==0) {
                if($manifest!==null) {
                    if(!isset($manifest['list_file'],$manifest['list_file_md5']) || !is_array($manifest['list_file']) || !is_array($manifest['list_file_md5']) || count($manifest['list_file'])!==count($manifest['list_file_md5'])) {closedir($handle);return false;}
                    foreach($manifest['list_file'] as $key=>$name) if(!is_string($name) || !isset($manifest['list_file_md5'][$key]) || !is_string($manifest['list_file_md5'][$key]) || !preg_match('/\A[a-f0-9]{32}\z/Di',$manifest['list_file_md5'][$key])) {closedir($handle);return false;}
                    $list_file=$manifest['list_file'];$list_file_md5=$manifest['list_file_md5'];
                } else {if(!file_exists($the_file)) {closedir($handle);return false;}include($the_file);}
            }
            $result = array(
                'new' => array(),
                'mod' => array(),
                'miss' => array()
            );
            while (false !== ($file = readdir($handle))) {
                if(trim($file, '.') == '') continue;
                if(basename($file) == 'menu.json' || ($layer===0 && $file==='files')) continue;
                if(!myPacker::allowsEntry($file,is_dir($dir.$file),$rules)) continue;
                if(is_link($dir.$file)) continue;
                if(strpos($file, '.bak')!==false || strpos($file, '_bak')!==false) continue;
                $the_name = $dir.$file;
                try {self::relativePath('/'.ltrim(substr($the_name,strlen(rtrim(realpath(ROOT),'/'))),'/'));} catch(InvalidArgumentException $error) {continue;}
                if($the_name==$the_file) continue;
                if(is_dir($the_name)) {
                    $result_new = self::checkFile($the_name, false, $layer+1);
                    if($result_new==null) continue;
                    $result['new'] = array_merge($result['new'], $result_new['new']);
                    $result['mod'] = array_merge($result['mod'], $result_new['mod']);
                    $result['miss'] = array_merge($result['miss'], $result_new['miss']);
                } else {
                    $the_name = '/'.ltrim(substr($the_name,strlen(rtrim(realpath(ROOT),'/'))),'/');
                    if(strpos($the_name, '/config.php')!==false) continue;
                    if(false !== ($key = array_search($the_name, $list_file))) {
                        if(md5_file(self::sourcePath($the_name))!=$list_file_md5[$key]) {
                            $result['mod'][] = $the_name;
                        }
                        unset($list_file[$key]);
                    } else {
                        $result['new'][] = $the_name;
                    }
                }
            }
            if($layer==0) {
                $ruleCache=[];
                foreach($list_file as $the_name) {
                    try {self::relativePath($the_name);} catch(InvalidArgumentException $error) {continue;}
                    if(strpos($the_name, '/config.php')!==false) continue;
                    $parts=explode('/',ltrim($the_name,'/'));$path=ROOT;$include=true;
                    foreach($parts as $i=>$part) {
                        if(!isset($ruleCache[$path])) $ruleCache[$path]=self::checksumRules($path);
                        if(!myPacker::allowsEntry($part,$i<count($parts)-1,$ruleCache[$path]) || $part==='menu.json' || strpos($part,'.bak')!==false || strpos($part,'_bak')!==false) {$include=false;break;}
                        $path.=$part.'/';
                    }
                    if($include) $result['miss'][] = $the_name;
                }
            }
        }
        closedir($handle);
        return $result;
    }

    /**
     * 功能说明：合并文件校验的目录规则与默认排除，供扫描和旧清单缺失检查共用
     *
     * lib/cache 保留为驱动源码；网站根目录的开发工具不参加运行文件校验。
     *
     * @param string $directory 当前目录绝对路径，由受信递归扫描提供
     * @return array myPacker 使用的 closed/allow/ignore 索引表
     * @throws RuntimeException 目录规则读取失败
     * @throws InvalidArgumentException 目录规则包含跨级成员
     */
    protected static function checksumRules($directory) {
        $directory=rtrim(myFile::realPath($directory),'/');
        $rules=myPacker::directoryRules($directory);$defaults=self::IGNORE;
        $root=rtrim(myFile::realPath(ROOT),'/');
        if($directory===$root.'/lib') $defaults=array_diff($defaults,['cache/']);
        $relative=substr($directory,strlen($root)+1);
        if($relative==='docs' || strpos($relative,'docs/')===0) $defaults=array_diff($defaults,['cache/']);
        if(preg_match('#\A(?:app|plugin)/[A-Za-z][A-Za-z0-9_]*/module(?:/[A-Za-z0-9_]+)*\z#D',$relative)) $defaults=array_diff($defaults,['plugin.php']);
        if($directory===$root) $defaults=array_merge($defaults,['test/','tests/','maintenance/','AGENTS.md']);
        $rules['ignore']+=array_fill_keys($defaults,true);
        return $rules;
    }

    /**
     * 功能说明：导出打包用配置副本，逐个清空结构声明的密码，不改写原配置。
     *
     * @param string $path 应用或插件目录
     * @param string $name_fix 副本文件名后缀，默认_new
     * @return void 无配置/结构时不处理
     */
    public static function checkConfig($path, $name_fix='_new') {
        $path .= '/';
        if(is_file($path.'config.php') && !is_file($path.'config_default.php')) {
            if(is_file($path.'config/construction.php')) {
                $construction = include($path.'config/construction.php');
                if(isset($construction['password'])) {
                    $tmp = new myConfig($path.'config.php');
                    foreach($construction['password'] as $key=>$v) {
                        if($key==='func' || !is_string($v) || !preg_match('/\A\w+(?:\.\w+)*\z/D',$v)) continue;
                        $parts=explode('.',$v);$last=array_pop($parts);$node=$tmp;
                        foreach($parts as $part) $node=$node->{$part};
                        $node->{$last}='';
                    }
                    $tmp->save('php', $path.'config'.$name_fix.'.php');
                    unset($tmp, $tmp2);
                } else {
                    myFile::copy($path.'config.php', $path.'config'.$name_fix.'.php');
                }
            }
        }
    }
    /**
     * 功能说明：流式替换生成归档中的一个文件，供发布包写入默认菜单；不改动在线源文件。
     *
     * @param string $package 由本机myPacker生成的zlib归档
     * @param string $member 需要替换的相对成员名
     * @param string $content 替换内容
     * @return bool 完整生成并原子替换归档才返回true；失败保留原归档
     */
    public static function replacePackedFile($package,$member,$content) {
        if(!is_string($content) || !is_file($package)) return false;
        try {self::relativePath($member);} catch(InvalidArgumentException $error) {return false;}
        $temp=tempnam(dirname($package),'.pack-menu-');$input=$output=null;$filter=null;
        try {
            $input=fopen($package,'rb');$output=fopen($temp,'wb');
            if(!$input || !$output || !stream_filter_append($input,'zlib.inflate',STREAM_FILTER_READ,['window'=>15])) return false;
            $filter=stream_filter_append($output,'zlib.deflate',STREAM_FILTER_WRITE,['window'=>15,'level'=>9]);
            if(!$filter) return false;
            $found=false;$entries=0;$bytes=0;
            while(!feof($input)) {
                $line=fgets($input,8193);if($line===false) break;
                if(substr($line,-1)!=="\n" || ++$entries>10000) return false;
                $parts=explode('|',rtrim($line,"\r\n"));
                if(($parts[0] ?? '')==='dir') {if(fwrite($output,$line)!==strlen($line))return false;continue;}
                if(($parts[0] ?? '')!=='file' || !isset($parts[1],$parts[2]) || !preg_match('/\A\d+\z/D',$parts[2]) || (float)$parts[2]>268435456) return false;
                $remaining=(int)$parts[2];$bytes+=$remaining;if($bytes>268435456)return false;
                $replace=$parts[1]===$member;
                if($replace) {$parts[2]=strlen($content);$line=implode('|',$parts)."\n";$found=true;}
                if(fwrite($output,$line)!==strlen($line))return false;
                while($remaining>0) {
                    $chunk=fread($input,min(65536,$remaining));if($chunk===false || $chunk==='')return false;
                    $remaining-=strlen($chunk);
                    if(!$replace && fwrite($output,$chunk)!==strlen($chunk))return false;
                }
                if($replace && fwrite($output,$content)!==strlen($content))return false;
            }
            if(!$found) {
                $data='file|'.$member.'|'.strlen($content).'|'.time()."\n".$content;
                if(fwrite($output,$data)!==strlen($data))return false;
            }
            if(!stream_filter_remove($filter))return false;$filter=null;
            fclose($output);$output=null;fclose($input);$input=null;
            return rename($temp,$package);
        } catch(Throwable $error) {return false;}
        finally {
            if(is_resource($filter))stream_filter_remove($filter);
            if(is_resource($output))fclose($output);if(is_resource($input))fclose($input);
            if(is_string($temp) && is_file($temp))unlink($temp);
        }
    }
    /**
     * 功能说明：限定更新/差异/系统下载的 ZIP 文件名和临时目录，拒绝目录穿越及符号链接。
     *
     * @param string $name update_／difference_加16位随机标识，或system_加64位内容摘要的ZIP文件名
     * @return string CACHE/tmp/manager 内的目标路径；可尚未生成文件
     * @throws InvalidArgumentException 文件名非法或目标含符号链接
     */
    public static function artifactPath($name) {
        if(!is_string($name) || !preg_match('/\A(?:(?:update|difference)_[a-f0-9]{16}|system_[a-f0-9]{64})\.zip\z/D',$name)) throw new InvalidArgumentException('Invalid update artifact');
        $path = myFile::extractionPath(CACHE,'tmp/manager/'.$name);
        if($path === false) throw new InvalidArgumentException('Invalid update artifact path');
        return $path;
    }
    /**
     * 功能说明：生成经后台认证的归档下载路由，不直接暴露受保护的临时缓存目录。
     *
     * public=true 会新建该归档的下载凭据元数据；普通后台归档依赖后台登录。
     *
     * @param string $file artifactPath 生成的完整ZIP目标路径
     * @param bool $public true 为公开差异包生成15分钟随机下载凭据；false 使用后台登录入口（默认：false）
     * @return string 受控下载地址，写入HTML时由调用方转义
     * @throws InvalidArgumentException 文件不属于更新归档目录
     * @throws RuntimeException 公开下载凭据无法原子保存
     * @throws Exception 无法生成安全随机下载凭据
     */
    public static function artifactURL($file, $public = false) {
        if(!is_string($file) || self::artifactPath(basename($file)) !== $file) throw new InvalidArgumentException('Invalid update artifact');
        if($public) {
            if(strpos(basename($file),'difference_') !== 0 || !is_file($file)) throw new InvalidArgumentException('Only published difference archives may use public download credentials');
            $token = bin2hex(random_bytes(24));
            savePluginFile($file.'.download',json_encode(['hash'=>hash('sha256',$token),'expires'=>time()+900]));
            return pluginQueryURL(myStep::setURL('api/plugin_manager/artifact/'),['name'=>basename($file),'token'=>$token]);
        }
        return pluginQueryURL(myStep::setURL('manager/file/'),['name'=>basename($file)]);
    }
    /**
     * 功能说明：校验公开差异归档的随机下载凭据，仅允许读取对应文件且有效期为15分钟。
     *
     * 校验不会消耗凭据，有效期内可重复读取；本方法只返回路径，由调用方发送文件。
     *
     * @param string $name difference_加16位随机十六进制标识的ZIP文件名
     * @param string $token artifactURL 返回的48位随机十六进制凭据，服务端仅保存其SHA-256摘要
     * @return string|false 校验成功返回归档路径；无效、过期、缺失或符号链接返回false
     */
    public static function publicArtifact($name, $token) {
        if(!is_string($name) || strpos($name,'difference_') !== 0 || !is_string($token) || !preg_match('/\A[a-f0-9]{48}\z/D',$token)) return false;
        try {$file = self::artifactPath($name);} catch(InvalidArgumentException $error) {return false;}
        if(!is_file($file) || !is_file($file.'.download') || is_link($file.'.download')) return false;
        $metadata = json_decode(file_get_contents($file.'.download'),true);
        if(!is_array($metadata) || !is_string($metadata['hash'] ?? null) || !is_int($metadata['expires'] ?? null)
            || $metadata['expires'] <= time() || !hash_equals($metadata['hash'],hash('sha256',$token))) return false;
        return $file;
    }
    /**
     * 功能说明：校验应用/插件安装目标并发布本机下载的归档，使用解包器的完整预检及回滚。
     *
     * 只处理指定应用或插件目录的源码归档，不替代插件安装生命周期或数据库迁移。
     *
     * @param string $type app 或 plugin
     * @param string $idx 应用/插件目录标识
     * @param string $file 本机下载的 myPacker 归档文件路径
     * @return bool 解包发布成功为 true；损坏、空文件或发布失败为 false，源归档由调用方清理
     * @throws InvalidArgumentException 类型、标识或安装目标非法，包括符号链接
     */
    public static function installPackage($type, $idx, $file) {
        self::identifier($idx);
        if(!in_array($type, ['app','plugin'], true)) throw new InvalidArgumentException('Invalid package type');
        $target = myFile::extractionPath($type === 'app' ? APP : PLUGIN, $idx);
        if($target === false) throw new InvalidArgumentException('Invalid package target');
        if(!is_string($file) || !is_file($file) || filesize($file) === 0) return false;
        $packer = new myPacker($target, $file);
        return $packer->unpack(false);
    }
    /**
     * 功能说明：将已校验更新生成可离线上传的ZIP，清单保存删除、目录、配置和迁移指令。
     *
     * 不修改网站源文件、回滚资料和缓存；文件内容保留二进制字节及原始相对路径。
     *
     * @param array $detail decodeUpdate返回的file/content/setting/code及kind操作列表
     * @return string 受控临时目录中的ZIP绝对路径，可交给artifactURL生成下载地址
     * @throws InvalidArgumentException 更新数据或路径不合法
     * @throws RuntimeException 暂存、清单编码或ZIP发布失败
     */
    public static function createUpdateArchive(array $detail) {
        $detail=self::decodeUpdate(gzdeflate(serialize($detail)));
        $id=bin2hex(random_bytes(8));$zipfile=self::artifactPath('update_'.$id.'.zip');
        $dir=CACHE.'tmp/manager/work_'.$id.'/';$files=[];
        $manifest=['format'=>1,'file'=>$detail['file'],'kind'=>$detail['kind'],'setting'=>$detail['setting'],'code'=>$detail['code']];
        try {
            foreach($detail['file'] as $i=>$name) {
                $content=$detail['content'][$i];
                if($detail['kind'][$i]!=='file') continue;
                $path=$dir.self::relativePath($name);
                if(myFile::saveFile($path,$content)!==strlen($content)) throw new RuntimeException('Cannot stage update member');
                $files[]=$path;
            }
            $json=json_encode($manifest,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            if($json===false || myFile::saveFile($dir.'_update.json',$json)!==strlen($json)) throw new RuntimeException('Cannot write update manifest');
            $files[]=$dir.'_update.json';
            if(!(new myZip($zipfile,$dir))->zip($files)) throw new RuntimeException('Cannot create update archive');
            return $zipfile;
        } finally {myFile::del($dir);}
    }
    /**
     * 功能说明：解包并检查离线ZIP，将新清单包或旧版文件包交给统一更新流程。
     *
     * 新包不包含可执行PHP控制文件；旧包兼容_config.php与_code.php，仍须信任来源。
     * 系统安装包含mystep.pack，只能用于独立安装，不能作为离线更新上传。
     * 仅忽略解包器实际生成的根锁文件；归档自带此类名称会被myZip拒绝。
     *
     * @param string $file 已上传到本地的ZIP绝对路径，不由本方法删除原ZIP
     * @return void 全部更新及缓存刷新完成后返回
     * @throws InvalidArgumentException ZIP、清单、成员集合或路径不合法
     * @throws RuntimeException 发布、迁移或缓存刷新失败
     */
    public static function applyUpdateArchive($file) {
        $dir=CACHE.'tmp/manager/upload_'.bin2hex(random_bytes(8)).'/';
        try {
            if(!(new myZip($file))->unzip($dir)) throw new InvalidArgumentException('Invalid ZIP update');
            $actual=[];$actualKind=[];
            foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::SELF_FIRST) as $member) {
                $name=substr($member->getPathname(),strlen($dir));
                if($name==='.mystep-extract.lock') continue;
                if(preg_match('#\A(?:mystep_v[0-9A-Za-z._-]+/)?mystep\.pack\z#D',$name)) throw new InvalidArgumentException('System installation archive is not an offline update');
                if(in_array($name,['_update.json','_config.php','_code.php'],true)) {
                    if(!$member->isFile()) throw new InvalidArgumentException('Invalid update control member');
                    continue;
                }
                self::sourcePath($name);$actual[$name]=$member->isDir()?'.':file_get_contents($member->getPathname());$actualKind[]=$member->isDir()?'directory':'file';
            }
            if(is_file($dir.'_update.json')) {
                $meta=json_decode(file_get_contents($dir.'_update.json'),true);
                if(!is_array($meta) || ($meta['format']??null)!==1 || !isset($meta['file'],$meta['kind'],$meta['setting'],$meta['code']) || !is_array($meta['file']) || !is_array($meta['kind']) || count($meta['file'])!==count($meta['kind']) || is_file($dir.'_config.php') || is_file($dir.'_code.php')) throw new InvalidArgumentException('Invalid offline update manifest');
                $detail=['file'=>$meta['file'],'kind'=>$meta['kind'],'content'=>[],'setting'=>$meta['setting'],'code'=>$meta['code']];$expected=[];
                foreach($meta['file'] as $i=>$name) {
                    $name=self::relativePath($name);$kind=$meta['kind'][$i]??null;
                    if($kind==='file') {
                        if(!array_key_exists($name,$actual) || is_dir($dir.$name)) throw new InvalidArgumentException('Missing offline update member');
                        $detail['content'][]=$actual[$name];$expected[$name]=true;
                    } elseif($kind==='delete' || $kind==='directory') $detail['content'][]=$kind==='delete'?'':'.';
                    else throw new InvalidArgumentException('Invalid offline update operation');
                }
                foreach($actual as $name=>$content) if(!is_dir($dir.$name) && !isset($expected[$name])) throw new InvalidArgumentException('Unexpected offline update member');
            } else {
                $detail=['file'=>array_keys($actual),'content'=>array_values($actual),'kind'=>$actualKind,'setting'=>[],'code'=>[]];
                if(is_file($dir.'_config.php')) $detail['setting']=include $dir.'_config.php';
                if(is_file($dir.'_code.php')) $detail['code'][]='include '.var_export($dir.'_code.php',true).';';
            }
            self::applyUpdate($detail);
        } finally {myFile::del($dir);}
    }
    /**
     * 功能说明：预检所有成员后在更新锁内发布文件、配置及迁移，最后提交框架版本和刷新校验。
     *
     * 文件写入结果逐项确认，已有文件、待删除目录及配置保存到唯一rollback子目录；失败抛异常，
     * 不报告成功，也不提前提交版本。迁移代码可有数据库等副作用，不承诺整体事务回滚。
     * 历史协议中空串表示删除、单点表示目录；kind显式区分操作后可更新空文件和单点文件。
     * 按PHP当前进程身份检查成员、备份、配置锁、清单及本地缓存目录；不自动chmod/chown。
     * 预检不能代替操作结果检查，也不能预知迁移副作用、并发权限变化或外部缓存服务故障。
     *
     * @param array $detail 包含file/content/setting/code的受信更新数据，可选kind区分文件/目录/删除
     * @return void 发布与缓存/校验刷新完成后返回
     * @throws InvalidArgumentException 成员冲突、路径或数据格式非法
     * @throws RuntimeException 锁定、备份、文件/配置发布、迁移或缓存刷新失败
     * @throws Throwable 迁移脚本抛出的其他异常继续传递
     */
    public static function applyUpdate(array $detail) {
        $detail=self::decodeUpdate(gzdeflate(serialize($detail)));
        if(!$detail['file'] && !$detail['setting'] && !$detail['code']) return;
        $lockfile=CACHE.'tmp/manager/upgrade.lock';
        self::assertUpdateAccess($lockfile);
        if(!myFile::mkdir(dirname($lockfile)) || is_link($lockfile)) throw new RuntimeException('Cannot prepare update lock');
        $lock=fopen($lockfile,'c');
        if(!$lock) throw new RuntimeException('Cannot open update lock');
        try {
            if(!flock($lock,LOCK_EX|LOCK_NB)) throw new RuntimeException('Another update is in progress');
            $targets=[];$version=null;
            foreach($detail['file'] as $i=>$name) {
                $name=self::relativePath($name);$path=self::sourcePath($name);$content=$detail['content'][$i];$kind=$detail['kind'][$i];
                if(($kind==='directory' && is_file($path)) || ($kind==='file' && is_dir($path))) throw new InvalidArgumentException('Update file/directory conflict');
                self::assertUpdateAccess($path,$kind==='file'?'write':$kind,true);
                if($name==='config/version.php') {
                    if($kind!=='file') throw new InvalidArgumentException('Framework version must be a file');
                    $version=[$path,$content];
                } else $targets[]=[$path,$content,$kind,$name];
            }
            self::assertUpdateAccess(__DIR__.'/rollback','directory');
            self::assertUpdateAccess(__DIR__.'/check_file.php');
            if($detail['setting']) {
                self::assertUpdateAccess(CONFIG.'config.php','write',true);
                self::assertUpdateAccess(CONFIG.'config.php.lock');
            }
            foreach(['script','template','language','app'] as $cache) self::assertUpdateAccess(CACHE.$cache,'delete');
            self::assertUpdateAccess(CACHE.'data','directory');
            self::assertUpdateAccess(CACHE.'data/v2','directory');
            foreach(glob(CACHE.'data/v2/*',GLOB_ONLYDIR) ?: [] as $shard) {
                if(!preg_match('/\A[a-f0-9]{2}\z/D',basename($shard))) continue;
                self::assertUpdateAccess($shard,'directory');
                self::assertUpdateAccess($shard.'/.lock','write',true);
            }
            $backup=__DIR__.'/rollback/'.date('Ymd_His').'_'.bin2hex(random_bytes(6)).'/';
            foreach($detail['file'] as $i=>$name) {
                $path=self::sourcePath($name);
                if(is_file($path) && myFile::saveFile($backup.self::relativePath($name),file_get_contents($path))!==filesize($path)) throw new RuntimeException('Cannot back up update member');
                if(is_dir($path) && $detail['kind'][$i]==='delete' && !myFile::copy($path,dirname($backup.self::relativePath($name)),true)) throw new RuntimeException('Cannot back up deleted directory');
            }
            $config=null;
            if($detail['setting']) {
                $config=new myConfig(CONFIG.'config.php');
                if(myFile::saveFile($backup.'config/config.php',file_get_contents(CONFIG.'config.php'))!==filesize(CONFIG.'config.php')) throw new RuntimeException('Cannot back up configuration');
                $config->merge($detail['setting']);$config->web->etag='etag_'.bin2hex(random_bytes(8));
            }
            foreach($targets as $target) {
                list($path,$content,$kind,$name)=$target;
                try {
                    if($kind==='delete') $ok=!file_exists($path) || myFile::del($path);
                    elseif($kind==='directory') $ok=myFile::mkdir($path);
                    else $ok=myFile::saveFile($path,$content)===strlen($content);
                    if(!$ok) throw new RuntimeException('File operation failed');
                } catch(Throwable $error) {throw new RuntimeException('Cannot publish update member: '.$name.'; 请检查 Web PHP 账号的文件/目录权限及磁盘空间后重试。',0,$error);}
                if(function_exists('opcache_invalidate')) opcache_invalidate($path,true);
            }
            if($config!==null) {
                try {$config->save();}
                catch(Throwable $error) {throw new RuntimeException('Cannot publish update configuration: config/config.php; 请检查配置、配置锁及目录权限，确认配置未被并发修改后重试。',0,$error);}
            }
            foreach($detail['code'] as $code) myEval($code,false);
            foreach(['script','template','language','app'] as $cache) if(file_exists(CACHE.$cache) && !myFile::del(CACHE.$cache)) throw new RuntimeException('Cannot clear update cache');
            if(!myStep::clearDataCache()) throw new RuntimeException('Cannot clear data cache after update');
            if($version!==null) {
                if(myFile::saveFile($version[0],$version[1])!==strlen($version[1])) throw new RuntimeException('Cannot commit updated framework version: config/version.php');
                if(function_exists('opcache_invalidate')) opcache_invalidate($version[0],true);
            }
            try {
                if(!self::checkFile(ROOT,true)) throw new RuntimeException('Cannot refresh updated file manifest');
            } catch(Throwable $error) {
                if($version!==null) {
                    $previous=$backup.'config/version.php';
                    if(is_file($previous)) myFile::saveFile($version[0],file_get_contents($previous));
                    else myFile::del($version[0]);
                    if(function_exists('opcache_invalidate')) opcache_invalidate($version[0],true);
                }
                throw $error;
            }
        } finally {flock($lock,LOCK_UN);fclose($lock);}
    }
    /**
     * 功能说明：按更新操作预检文件与目录权限，失败时报告网站根目录的相对路径。
     *
     * write检查父目录和已有文件写权限；directory检查建目录条件；delete递归检查
     * 目录的读取、写入及搜索权限，删除文件本身不要求可写。备份时额外要求源可读。
     * 不创建探测文件、不提权或放宽权限；拒绝目标及祖先符号链接，缓存删除可直接移除
     * 目录内的链接但不跟随链接。ACL、只读挂载、并发变化等仍以实际文件操作结果为准。
     * 异常说明包含相对路径、操作、失败原因及向Web PHP账号授权后重试的提示。
     *
     * @param string $path 框架内部解析得到的绝对路径，可包含尚不存在的父目录
     * @param string $operation write写文件、directory建目录或delete删除（默认：'write'）
     * @param bool $backup 是否还要检查原内容可读以便备份（默认：false）
     * @return void 可检查的权限条件通过后返回
     * @throws InvalidArgumentException 操作名称不受支持
     * @throws RuntimeException 路径含符号链接、类型冲突或文件/目录权限不足
     */
    protected static function assertUpdateAccess($path, $operation='write', $backup=false) {
        if(!in_array($operation,['write','directory','delete'],true)) throw new InvalidArgumentException('Invalid update access operation');
        $path=rtrim($path,'/');clearstatcache();
        $root=rtrim(ROOT,'/').'/';
        $name=strpos($path,$root)===0?substr($path,strlen($root)):basename($path);
        $message='Update permission check failed: '.$name.' ('.$operation.'); 请为运行网站的 Web PHP 账号授予所示文件的必要读写权限，以及所在目录的写入和进入权限后重试。';
        for($ancestor=$path;;$ancestor=dirname($ancestor)) {
            if(is_link($ancestor)) throw new RuntimeException($message.'; symbolic link');
            if(dirname($ancestor)===$ancestor) break;
        }
        // 不存在的删除目标无需写权限。
        if($operation==='delete' && !file_exists($path)) return;
        if($operation==='directory' && is_dir($path)) {
            if(!is_readable($path) || !is_writable($path) || (DIRECTORY_SEPARATOR!=='\\' && !is_executable($path))) throw new RuntimeException($message.'; directory is not readable/writable/searchable');
            return;
        }
        $parent=dirname($path);
        while(!file_exists($parent) && $parent!==dirname($parent)) $parent=dirname($parent);
        if(!is_dir($parent) || !is_writable($parent) || (DIRECTORY_SEPARATOR!=='\\' && !is_executable($parent))) throw new RuntimeException($message.'; parent directory is not writable/searchable');
        if(!file_exists($path)) return;
        if(is_dir($path)) {
            if($operation==='write' || !is_readable($path) || !is_writable($path) || (DIRECTORY_SEPARATOR!=='\\' && !is_executable($path))) throw new RuntimeException($message.'; directory is not readable/writable/searchable');
            if($operation==='delete') {
                foreach(new DirectoryIterator($path) as $entry) {
                    if($entry->isDot()) continue;
                    if($entry->isLink() && !$backup) continue;
                    self::assertUpdateAccess($entry->getPathname(),'delete',$backup);
                }
            }
        } elseif(!is_file($path) || ($operation==='directory') || ($operation==='write' && !is_writable($path)) || ($backup && !is_readable($path))) {
            throw new RuntimeException($message.'; file type or access denied');
        }
    }
    /**
     * 功能说明：校验应用或插件标识，防止目录穿越/任意PHP包含。
     *
     * @param string $idx 目录名
     * @return string 校验后的原值
     * @throws InvalidArgumentException 标识非法
     */
    public static function identifier($idx) {
        if(!is_string($idx) || !preg_match('/\A[a-zA-Z][a-zA-Z0-9_]*\z/D',$idx)) throw new InvalidArgumentException('Invalid package identifier');
        return $idx;
    }
    /**
     * 功能说明：读取 manager 自有更新网址，兼容尚未迁移的旧框架配置。
     *
     * 优先读取 plugin/manager/config.php 的 update_url；仅当字段不存在时读取
     * config/config.php 的 web.update。显式空字符串不回退旧值，表示仅使用离线更新。
     * 不在读取时改写配置；实际联网前须交由 updateBase 校验 HTTP/HTTPS 地址。
     *
     * 参数说明：无显式参数。
     *
     * 调用示例：
     *     $url = plugin_manager::updateBase(plugin_manager::updateServer()).'/api/plugin_manager/';
     *
     * @return string 更新源原始域名或 HTTP/HTTPS 基址；未设置时为空字符串
     * @throws InvalidArgumentException 更新网址不是字符串
     * @throws Throwable 配置读取异常继续传递
     */
    public static function updateServer() {
        $config = new myConfig(__DIR__.'/config.php');
        $data = $config->get(true);
        if(array_key_exists('update_url', $data)) $server = $data['update_url'];
        else $server = (new myConfig(CONFIG.'config.php'))->value('web.update', '');
        if(!is_string($server)) throw new InvalidArgumentException('Invalid update server');
        return $server;
    }
    /**
     * 功能说明：生成更新服务基址，支持显式HTTP/HTTPS，缺省HTTPS且不自动降级；禁止凭据、查询串及片段。
     *
     * @param string $server 主机[:端口][/子目录]或完整HTTP/HTTPS基址；省略协议时使用HTTPS
     * @return string 不带末尾斜线的HTTP/HTTPS地址；不会探测或静默切换协议
     * @throws InvalidArgumentException 不可信地址格式
     */
    public static function updateBase($server) {
        if(!is_string($server) || preg_match('/[\x00-\x20\x7f\\\\]/',$server)) throw new InvalidArgumentException('Invalid update server');
        if($server==='') throw new InvalidArgumentException('请先在 manager 插件设置中填写更新网址。');
        $url=strpos($server,'://')===false ? 'https://'.$server : $server;$p=parse_url($url);
        if(!$p || !in_array(strtolower($p['scheme'] ?? ''), ['http','https'], true) || empty($p['host']) || isset($p['user']) || isset($p['pass']) || isset($p['query']) || isset($p['fragment'])) throw new InvalidArgumentException('Update server requires an HTTP or HTTPS base URL without credentials, query or fragment');
        $url = strtolower($p['scheme']).substr($url, strlen($p['scheme']));
        return rtrim($url,'/');
    }
    /**
     * 功能说明：校验更新文件相对路径，兼容清单中的单个前导斜线；排除私有配置和运行时数据。
     *
     * lib/cache与lib/session仅作为固定的框架源码目录放行；其他cache/session目录仍受保护。
     * config/api.php保存站点API策略，不进入升级替换成员。
     * 应用/插件 module 下的 plugin.php 为功能源码，可发布；根目录同名绑定配置仍受保护。
     * docs 中 cache/session 为开发指南分类，可发布；运行缓存、会话目录仍受保护。
     *
     * @param string $name 清单成员路径
     * @return string 不带前导斜线的路径
     * @throws InvalidArgumentException 路径越界、控制字符或敏感成员
     */
    public static function relativePath($name) {
        if(!is_string($name) || $name==='' || strlen($name)>4096 || preg_match('/[\x00-\x1f\x7f\\\\:]/',$name) || strpos($name,'//')!==false) throw new InvalidArgumentException('Invalid update path');
        $name=ltrim($name,'/');$parts=explode('/',rtrim($name,'/'));
        if($name==='config/api.php')throw new InvalidArgumentException('API policy is a private site configuration');
        if(($parts[0] ?? '')==='files') throw new InvalidArgumentException('Upload data is not an update member');
        foreach($parts as $position=>$part) {
            $libraryDirectory=$position===1 && $parts[0]==='lib' && in_array($part,['cache','session'],true);
            $documentationDirectory=$parts[0]==='docs' && in_array($part,['cache','session'],true);
            $moduleScript=$part==='plugin.php' && $position===count($parts)-1 && preg_match('#\A(?:app|plugin)/[A-Za-z][A-Za-z0-9_]*/module/(?:[A-Za-z0-9_]+/)*plugin\.php\z#D',$name);
            if($part==='' || $part==='.' || $part==='..' || (isset($part[0]) && $part[0]==='.' && !in_array($part,['.htaccess'],true))
                || (!$libraryDirectory && !$documentationDirectory && !$moduleScript && in_array($part,['config.php','plugin.php','construction.php','cache','session','error.log','plugin.db','data.db','update.db','ban_ip.php'],true))) throw new InvalidArgumentException('Private or invalid update member');
        }
        return implode('/',$parts);
    }
    /**
     * 功能说明：将成员解析到框架根目录，禁止任何符号链接穿越；允许尚不存在的更新目标。
     *
     * @param string $name 相对成员名
     * @return string 安全绝对路径
     * @throws InvalidArgumentException 目标越界或含符号链接
     */
    public static function sourcePath($name) {
        $path=myFile::extractionPath(ROOT,self::relativePath($name));
        if($path===false) throw new InvalidArgumentException('Update target escapes framework root');
        return $path;
    }
    /**
     * 功能说明：有界解压/解码更新包，不实例化序列化对象；所有成员在首次落盘前校验。
     *
     * @param string $compressed gzdeflate后的PHP数组序列化内容
     * @return array file/content/setting/code及规范化kind操作列表，兼容不含kind的旧协议
     * @throws InvalidArgumentException 传输、大小、结构或路径无效
     */
    public static function decodeUpdate($compressed) {
        if(!is_string($compressed) || strlen($compressed)>67108864) throw new InvalidArgumentException('Invalid update response');
        $raw=@gzinflate($compressed,268435456);
        if($raw===false) throw new InvalidArgumentException('Invalid compressed update');
        $data=@unserialize($raw,['allowed_classes'=>false,'max_depth'=>128]);
        if(!is_array($data)) throw new InvalidArgumentException('Invalid update payload');
        foreach(['file','content','setting','code'] as $key) if(!isset($data[$key]) || !is_array($data[$key])) throw new InvalidArgumentException('Invalid update fields');
        if(array_values($data['file'])!==$data['file']) throw new InvalidArgumentException('Invalid update file indexes');
        if(array_keys($data['content'])!==array_keys($data['file'])) throw new InvalidArgumentException('Invalid update content indexes');
        if(count($data['file'])!==count($data['content']) || count($data['file'])>10000) throw new InvalidArgumentException('Invalid update member count');
        if(!isset($data['kind'])) {
            $data['kind']=[];
            foreach($data['content'] as $content) $data['kind'][]=$content===''?'delete':($content==='.'?'directory':'file');
        }
        if(!is_array($data['kind']) || array_keys($data['kind'])!==array_keys($data['file'])) throw new InvalidArgumentException('Invalid update operation indexes');
        foreach($data['kind'] as $i=>$kind) {
            if(!in_array($kind,['file','delete','directory'],true) || ($kind==='delete' && $data['content'][$i]!=='') || ($kind==='directory' && $data['content'][$i]!=='.')) throw new InvalidArgumentException('Invalid update operation');
        }
        $seen=[];
        foreach($data['file'] as $key=>$file) {
            $name=self::relativePath($file);self::sourcePath($file);
            if(isset($seen[$name]) || !isset($data['content'][$key]) || !is_string($data['content'][$key])) throw new InvalidArgumentException('Invalid duplicate or content');
            $seen[$name]=$key;
        }
        foreach($seen as $name=>$unused) {
            $parent=dirname($name);
            while($parent!=='.') {
                if(isset($seen[$parent])) {
                    $index=$seen[$parent];
                    if($data['kind'][$index]!=='directory') throw new InvalidArgumentException('Conflicting update members');
                }
                $parent=dirname($parent);
            }
            if(in_array($name,['_update.json','_config.php','_code.php'],true)) throw new InvalidArgumentException('Reserved update member');
        }
        foreach($data['code'] as $code) if(!is_string($code)) throw new InvalidArgumentException('Invalid update code');
        $check=
        /**
         * 功能说明：递归校验更新配置，拒绝对象或资源值。
         *
         * 闭包依赖：通过引用 use 捕获自身 check 以遍历子节点。
         *
         * @param mixed $value 当前待检查的配置节点
         * @return void 合法节点返回；遇到对象或资源时抛异常
         * @throws InvalidArgumentException 配置节点包含对象或资源
         */
        function($value) use (&$check) {if(is_object($value)||is_resource($value)) throw new InvalidArgumentException('Object in update setting');if(is_array($value))foreach($value as $item)$check($item);};$check($data['setting']);
        return $data;
    }
}
