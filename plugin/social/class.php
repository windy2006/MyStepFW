<?PHP
/********************************************
*                                           *
* Name    : Social Login Plugin (new)       *
* Modifier: Windy2000                       *
* Time    : 2026-10-25                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/
/**
 * 文件：plugin/social/class.php (new)
 * 功能说明：第三方普通会员登录插件生命周期、设置校验、模块/模板展示及受保护HTTP分派。
 * 方法目录：
 *   public static plugin_social::load(): void — 加载插件拥有的协议、存储及流程类。参数说明：无显式参数。
 *   public static plugin_social::check(&$result='') — 只读检查PHP/cURL及框架会员协议，不在发现时连接平台或迁移用户表。
 *   public static plugin_social::options(?myConfig $config=null): array — 读取并规范化扁平插件设置，回调入口固定为受信HTTPS URL。
 *   public static plugin_social::validateConfig(myConfig $config): void — 在设置写入前规范化类型并校验，不连接平台或发邮件。
 *   public static plugin_social::install() — 显式准备会员事务存储、插件身份表和后台路由；不启用未配置平台。参数说明：无显式参数。
 *   public static plugin_social::uninstall() — 移除路由和菜单，保留会员和绑定以便重新安装；停用期间平台登录不可用。参数说明：无显式参数。
 *   public static plugin_social::main(): void — 使用现有后台布局显示配置接入说明。参数说明：无显式参数。
 *   public static plugin_social::flow(myUser $users,array $o,?callable $transport=null,?callable $mailer=null,?callable $clock=null): SocialFlow — 组合会员流程；受信测试可显式注入传输、邮件和时钟。
 *   private static plugin_social::active(array $o): bool — 校验当前宿主、活动状态和已登记会员模块，不因类可加载就授权。
 *   private static plugin_social::template(string $name): myTemplate — 创建插件自身模板，编译目录按应用隔离并关闭页面缓存。
 *   public static plugin_social::escape($value): string — 按UTF-8输出HTML属性/文本。
 *   public static plugin_social::buttons(myUser $users,string $intent='login'): string — 为登录/注册页生成平台按钮，为会员资料页生成绑定入口；管理员不显示。
 *   public static plugin_social::page(myUser $users,array $segments): string — 处理宿主的user/social功能段，写操作只允许POST且独立复核保留的social_csrf字段。
 *   private static plugin_social::field(string $key): string — 读取单个原始POST字段，不接受数组或超大输入。
 *   private static plugin_social::assertCanonical(array $o): void — 只在配置的规范HTTPS主机处理认证，防止Host构造回调或跨站完成流程。
 * 使用说明：配置后仍须安装、启用并绑定会员应用；首次需本站邮箱验证，不合并同邮箱账号，不获得管理员身份。后台页面使用module/manage与template/manage，前台使用module/social与template/social。
 */
class plugin_social implements interface_plugin {
    /**
     * 功能说明：加载插件拥有的协议、存储及流程类。参数说明：无显式参数。
     * @return void 不请求远端
     */
    public static function load(): void {foreach(['SocialOAuth','SocialStore','SocialFlow','SocialMailer'] as $name)require_once __DIR__.'/'.$name.'.class.php';}
    /**
     * 功能说明：只读检查PHP/cURL及框架会员协议，不在发现时连接平台或迁移用户表。
     * @param string &$result 默认空串；通过引用返回环境说明
     * @return bool 基础依赖是否齐全
     */
    public static function check(&$result='') {
        $ok=PHP_VERSION_ID>=80512&&extension_loaded('curl')&&extension_loaded('mbstring')&&class_exists('myUser')&&interface_exists('interface_user');
        $result=$ok?'基础依赖可用。安装会补齐会员字段并准备事务表；请先备份会员数据库，再配置平台凭据和应用SMTP。':'需要PHP8.5.12、cURL、mbstring及框架普通会员接口。';return $ok;
    }
    /**
     * 功能说明：读取并规范化扁平插件设置，回调入口固定为受信HTTPS URL。
     * @param myConfig|null $config 默认null；保存前配置，null读取当前插件值
     * @return array 规范化设置；包含秘密，仅供服务端使用
     * @throws InvalidArgumentException 设置格式或启用凭据缺失
     */
    public static function options(?myConfig $config=null): array {
        $defaults=include __DIR__.'/config_default.php';$input=$config?$config->get(true):(is_file(__DIR__.'/config.php')?include __DIR__.'/config.php':$defaults);
        if(!is_array($input)||array_diff(array_keys($input),array_keys($defaults)))throw new InvalidArgumentException('第三方登录设置项目无效。');$o=array_replace($defaults,$input);
        foreach(['enabled','wechat_enabled','qq_enabled','google_enabled','github_enabled'] as $key){$value=filter_var($o[$key],FILTER_VALIDATE_BOOLEAN,FILTER_NULL_ON_FAILURE);if($value===null)throw new InvalidArgumentException('登录开关必须为布尔值。');$o[$key]=$value;}
        foreach(['app','endpoint','site_name','wechat_mode','wechat_client_id','wechat_secret','qq_client_id','qq_secret','google_client_id','google_secret','github_client_id','github_secret'] as $key){if(!is_string($o[$key])||strlen($o[$key])>4096||preg_match('/[\x00-\x1f\x7f]/',$o[$key]))throw new InvalidArgumentException('第三方登录文本设置无效。');$o[$key]=trim($o[$key]);}
        if(!preg_match('/\A[A-Za-z][A-Za-z0-9_]{0,39}\z/D',$o['app'])||!in_array($o['wechat_mode'],['web','mp'],true))throw new InvalidArgumentException('会员应用或微信场景无效。');
        if(!mb_check_encoding($o['site_name'],'UTF-8')||mb_strlen($o['site_name'],'UTF-8')<1||mb_strlen($o['site_name'],'UTF-8')>80)throw new InvalidArgumentException('站点名称须为1至80个字符。');
        foreach(['email_ttl'=>[300,1800],'pending_ttl'=>[600,3600]] as $key=>$range){if(filter_var($o[$key],FILTER_VALIDATE_INT)===false||(int)$o[$key]<$range[0]||(int)$o[$key]>$range[1])throw new InvalidArgumentException('验证有效期超出允许范围。');$o[$key]=(int)$o[$key];}
        if($o['email_ttl']>$o['pending_ttl'])throw new InvalidArgumentException('邮件有效期不能大于待完成流程有效期。');
        if($o['endpoint']!==''){
            $url=parse_url($o['endpoint']);$query=$url['query']??null;
            if(!filter_var($o['endpoint'],FILTER_VALIDATE_URL)||!$url||($url['scheme']??'')!=='https'||empty($url['host'])||isset($url['user'])||isset($url['pass'])||isset($url['fragment'])||!str_ends_with($o['endpoint'],'/')||strlen($o['endpoint'])>2048||($query!==null&&(!str_ends_with($url['path']??'','/index.php')||strpbrk($query,'=&?#')!==false)))throw new InvalidArgumentException('规范入口须为HTTPS、以/结尾，且不能包含账号、片段或普通查询参数。');
        }
        if($o['enabled']){
            if($o['endpoint']===''||!is_file(APP.$o['app'].'/'.$o['app'].'.class.php'))throw new InvalidArgumentException('启用前请配置规范入口及已安装的会员应用。');
            $count=0;foreach(['wechat','qq','google','github'] as $provider)if($o[$provider.'_enabled']){$count++;if($o[$provider.'_client_id']===''||$o[$provider.'_secret']==='')throw new InvalidArgumentException('请填写所有已启用平台的应用ID和密钥。');}
            if(!$count)throw new InvalidArgumentException('至少启用一个已配置的登录平台。');
        }
        return $o;
    }
    /**
     * 功能说明：在设置写入前规范化类型并校验，不连接平台或发邮件。
     * @param myConfig $config 待保存设置
     * @return void 无效时抛InvalidArgumentException
     */
    public static function validateConfig(myConfig $config): void {$config->set(self::options($config));}
    /**
     * 功能说明：显式准备会员事务存储、插件身份表和后台路由；不启用未配置平台。参数说明：无显式参数。
     * @return bool 完成安装
     * @throws Throwable 准备失败，数据和已有账号不删除
     */
    public static function install() {
        self::load();$message='';if(!self::check($message))throw new RuntimeException($message);
        // 独立插件包排除在线配置；只在显式安装中以乐观锁生成无凭据默认值。
        if(!is_file(__DIR__.'/config.php')){$seed=new myConfig(__DIR__.'/config.php');if(!is_file(__DIR__.'/config.php')){$seed->merge(include __DIR__.'/config_default.php');$seed->save('php');}}
        $o=self::options();$users=myUser::forApplication($o['app']);(new SocialStore($users->provider()))->prepare();
        if(!regPluginRoute('social'))throw new RuntimeException('第三方登录管理路由登记失败。');
        addPluginLink('第三方登录','social/');setPluginApps('social',array_values(array_unique(['myStep',$o['app']])),array_values(array_unique(['myStep',$o['app']])));return true;
    }
    /**
     * 功能说明：移除路由和菜单，保留会员和绑定以便重新安装；停用期间平台登录不可用。参数说明：无显式参数。
     * @return bool 完成清理
     */
    public static function uninstall() {removePluginRoute('social');removePluginLink('social/');setPluginApps('social',[]);return true;}
    /**
     * 功能说明：使用现有后台布局显示配置接入说明。参数说明：无显式参数。
     * @return void 输出管理页
     */
    public static function main(): void {showPluginPage('social','manage');}
    /**
     * 功能说明：组合会员流程；受信测试可显式注入传输、邮件和时钟。
     * @param myUser $users 会员服务
     * @param array $o 规范设置
     * @param callable|null $transport 默认null；测试网络
     * @param callable|null $mailer 默认null；测试邮件
     * @param callable|null $clock 默认null；测试时钟
     * @return SocialFlow 流程服务
     */
    public static function flow(myUser $users,array $o,?callable $transport=null,?callable $mailer=null,?callable $clock=null): SocialFlow {
        self::load();global $S;$mail=new SocialMailer(isset($S->email)?myConfig::o2a($S->email):[],$o['site_name']);
        return new SocialFlow($users,new SocialStore($users->provider()),new SocialOAuth($o,$transport),$o,$mailer??[$mail,'deliver'],$clock);
    }
    /**
     * 功能说明：校验当前宿主、活动状态和已登记会员模块，不因类可加载就授权。
     * @param array $o 插件设置
     * @return bool 当前普通会员入口可用
     */
    private static function active(array $o): bool {return $o['enabled']&&($GLOBALS['info_app']['app']??'')===$o['app']&&isset(myStep::activePlugins()['social'])&&isset(myController::$modules['user_social']);}
    /**
     * 功能说明：创建插件自身模板，编译目录按应用隔离并关闭页面缓存。
     * @param string $name 固定模板名
     * @return myTemplate 模板实例
     */
    private static function template(string $name): myTemplate {return new myTemplate(['name'=>$name,'path'=>__DIR__.'/template/','style'=>'','path_compile'=>CACHE.'template/plugin/social/'.($GLOBALS['info_app']['app']??'myStep').'/'],false);}
    /**
     * 功能说明：按UTF-8输出HTML属性/文本。
     * @param mixed $value 受信或用户文本
     * @return string 已转义文本
     */
    public static function escape($value): string {return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
    /**
     * 功能说明：为登录/注册页生成平台按钮，为会员资料页生成绑定入口；管理员不显示。
     * @param myUser $users 当前会员服务
     * @param string $intent 默认login；login或bind
     * @return string 模板HTML，未启用或未准备时为空
     */
    public static function buttons(myUser $users,string $intent='login'): string {
        try{$o=self::options();if(!self::active($o)||!$users->provider()->allowsLogin())return '';self::load();$store=new SocialStore($users->provider());if(!$store->ready())return '';$oauth=new SocialOAuth($o);$tpl_social=self::template('buttons');include __DIR__.'/module/buttons.php';return $tpl_social->render('',false);}catch(Throwable){return '';}
    }
    /**
     * 功能说明：处理宿主的user/social功能段，写操作只允许POST且独立复核保留的social_csrf字段。
     * @param myUser $users 当前应用会员资源
     * @param array $segments action/provider等已解析路径段
     * @return string 插件前台模板HTML；重定向分支结束请求
     * 异常处理：输入/状态错误转换为对应页面；底层失败显示通用服务不可用，不输出秘密。
     */
    public static function page(myUser $users,array $segments): string {
        header('Cache-Control: private, no-store');header('Referrer-Policy: no-referrer');header('X-Content-Type-Options: nosniff');
        $page='message';$error='';$notice='';$fields=['email'=>'','display_name'=>'','pending_id'=>''];$bindings=[];$readonly=false;
        try{
            $o=self::options();if(!self::active($o))throw new DomainException('第三方登录尚未启用。');self::assertCanonical($o);self::load();$store=new SocialStore($users->provider());if(!$store->ready())throw new DomainException('请先在后台完成第三方登录插件安装。');
            $flow=self::flow($users,$o);$action=$segments[0]??'connections';$provider=$segments[1]??'';$method=strtoupper($_SERVER['REQUEST_METHOD']??'GET');
            $allowed=['begin'=>['POST'],'callback'=>['GET'],'complete'=>['GET','POST'],'verify'=>['GET','POST'],'connections'=>['GET'],'unlink'=>['POST'],'cancel'=>['POST']];
            if(!is_string($action)||!isset($allowed[$action])){http_response_code(404);throw new DomainException('功能不存在。');}
            if(!in_array($method,$allowed[$action],true)){http_response_code(405);header('Allow: '.implode(', ',$allowed[$action]));throw new DomainException('请使用页面按钮执行此操作。');}
            if($method==='POST'&&(!mySecurity::originAllowed()||!mySecurity::valid($_POST['social_csrf']??null))){http_response_code(403);throw new DomainException('表单已过期，请刷新后重试。');}
            switch($action){
                case 'begin':myStep::redirect($flow->begin((string)$provider,self::field('intent')),303);break;
                case 'callback':$result=$flow->callback((string)$provider,$_GET);myStep::redirect($result['kind']==='pending'?$o['endpoint'].'complete':$o['endpoint'].'connections',303);break;
                case 'complete':
                    if($method==='POST'){$flow->sendVerification(self::field('email'),self::field('display_name'),(string)myReq::ip());$page='wait';$notice='验证邮件已提交。请在同一浏览器中打开邮件链接，确认后完成关联。';}
                    else $page='complete';
                    $pending=$flow->pending();$fields=['email'=>$pending['email'],'display_name'=>$pending['display_name'],'pending_id'=>$pending['pending_id']];$readonly=$pending['intent']!=='new';break;
                case 'verify':
                    if($method==='POST'){$flow->confirm(self::field('pending_id'),self::field('mail_token'));myStep::redirect($o['endpoint'].'connections',303);}
                    $flow->pending();$page='verify';break;
                case 'connections':$bindings=$flow->connections();$page='connections';break;
                case 'unlink':$flow->unlink(self::field('identity_key'));myStep::redirect($o['endpoint'].'connections',303);break;
                case 'cancel':$flow->cancel();$notice='已取消本次关联。';break;
            }
        }catch(InvalidArgumentException|DomainException $e){if(http_response_code()<400)http_response_code(422);$error=$e->getMessage();if(isset($flow)&&isset($action)&&$action==='complete'){try{$pending=$flow->pending();$fields=['email'=>$pending['email'],'display_name'=>$pending['display_name'],'pending_id'=>$pending['pending_id']];$readonly=$pending['intent']!=='new';$page='complete';}catch(Throwable){}}}
        catch(Throwable){http_response_code(503);$error='会员认证服务暂不可用，请检查配置或稍后重试。';}
        $tpl_social=self::template('social');include __DIR__.'/module/view.php';return $tpl_social->render('',false);
    }
    /**
     * 功能说明：读取单个原始POST字段，不接受数组或超大输入。
     * @param string $key 字段名
     * @return string 原始字符串
     * @throws InvalidArgumentException 字段无效
     */
    private static function field(string $key): string {$value=$_POST[$key]??'';if(!is_string($value)||strlen($value)>4096)throw new InvalidArgumentException('表单字段无效。');return $value;}
    /**
     * 功能说明：只在配置的规范HTTPS主机处理认证，防止Host构造回调或跨站完成流程。
     * @param array $o 规范配置
     * @return void 主机不符时抛异常
     * @throws Throwable 输入/状态无效或底层服务失败时终止本次流程，不建立登录
     */
    private static function assertCanonical(array $o): void {
        $url=parse_url($o['endpoint']);$expected=strtolower($url['host']).(isset($url['port'])?':'.$url['port']:'');$host=strtolower((string)($_SERVER['HTTP_HOST']??''));
        if(!isHttps()||!hash_equals($expected,$host))throw new DomainException('请通过插件设置中的规范HTTPS入口访问。');
    }
}
