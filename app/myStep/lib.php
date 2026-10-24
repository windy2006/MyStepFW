<?php
/**
 * 文件：app/myStep/lib.php
 * 功能说明：原后台公共函数及新旧后台复用的 API、指南数据辅助声明。
 * 方法目录：app\myStep 的 logCheck/getData/getError/autoComplete；MyStepApiManager、MyStepGuide 的目录见各类说明。
 * 使用说明：require_once 只加载声明；API 页面装配在 module/function/api.php，指南页面装配在 module/guide.php。
 * 辅助声明不输出页面、不启动会话、不执行业务 API；各管理模块继续加载自己的对应模板。
 */
namespace app\myStep {
function logCheck($show = true) {
    $user = \myReq::session('ms_user');
    if(empty($user)) {
        if($show) {
            $url = \myReq::server('REQUEST_URI');
            if(\myReq::session('url')=='' && !preg_match('#'.$GLOBALS['ms_setting']->gen->path_admin.'/$#', $url)) \myReq::session('url', $url);
            \myStep::getModule('login');
        }
        return false;
    }
    return true;
}

/**
 * 为后台数据浏览器分页读取数据，先检查框架管理员身份及实际表/字段。
 * @param string $tbl_name 表名或数据库.表名，只接受字母、数字和下划线；省略或无效时返回空分页结果（默认：''）
 * @return array total 为不含分页的匹配数，rows 为记录列表；未授权/无效表返回空列表
 * POST JSON 的 limit 限制为 1..1000、offset 不小于0，field 仅允许真实字段及标量值。
 */
function getData($tbl_name = '') {
    if(!logCheck(false)) return [];
    global $db, $ms_setting, $db_name;
    $db_name = $ms_setting->db->name;
    $result = ['total'=>0,'rows'=>[]];
    if(!is_string($tbl_name) || !preg_match('/\A(?:[a-zA-Z0-9_]+\.)?[a-zA-Z0-9_]+\z/D',$tbl_name)) return $result;
    if(strpos($tbl_name,'.')!==false) list($db_name,$tbl_name)=explode('.',$tbl_name,2);
    if(!$db->check()) $db->connect(0,$db_name);
    if(!in_array($tbl_name,$db->getTbls($db_name),true)) return $result;
    $fields=$db->getFields($tbl_name,$db_name);
    $table=$db_name.'.'.$tbl_name;
    $request=json_decode(file_get_contents('php://input'),true);
    if(!is_array($request)) $request=[];
    $limit=is_scalar($request['limit'] ?? null) ? (int)$request['limit'] : 20;
    $offset=is_scalar($request['offset'] ?? null) ? (int)$request['offset'] : 0;
    $db->build('[reset]');
    $db->build($table)->limit(max(0,$offset),max(1,min(1000,$limit)));
    foreach(is_array($request['field'] ?? null) ? $request['field'] : [] as $key=>$value) {
        if(!in_array($key,$fields,true) || !is_scalar($value) || $value==='') continue;
        $db->build($table)->where($key,is_numeric($value) ? '=' : 'like',$value);
    }
    $sql=$db->select(true);
    $result['total']=$db->count($sql);
    $result['rows']=$db->records($sql);
    return $result;
}

function getError() {
    $err_file = ROOT.'error.log';
    $count = 0;
    if(is_file($err_file)) {
        $err_content= \myFile::getLocal($err_file);
        $err_lst = preg_split("/\n+[\-]{20,}\n+/", $err_content);
        $count = count($err_lst) - 1;
    }
    return array('count'=>$count);
}

function autoComplete($mode, $keyword) {
    global $ms_setting;
    $keyword = \myString::setCharset($keyword, $ms_setting->gen->charset);
    $result = array(
        'query' => $keyword,
        'suggestions' => array(),
        'data' => array()
    );
    if(!is_string($mode) || !preg_match('/\A[a-zA-Z][a-zA-Z0-9_]*\z/D',$mode)) return $result;
    $dataFile = VENDOR.'jquery.autocomplete/'.$mode.'.php';
    if(is_file($dataFile)) {
        include($dataFile);
        $data = $$mode;
        unset($$mode);
        $keyword = strtolower($keyword);
        for($i=0,$m=count($data);$i<$m;$i++) {
            if(strpos(strtolower(implode('|', $data[$i])), $keyword)!==false) {
                if($ms_setting->gen->language=='en') {
                    $result['suggestions'][] = $data[$i][1];
                    $result['data'][] = $data[$i][1];
                } else {
                    $result['suggestions'][] = $data[$i][0];
                    $result['data'][] = $data[$i][1];
                }
            }
        }
    }
    return $result;
}
}
namespace {
/**
 * 辅助声明：MyStepApiManager.class（位于 app/myStep/lib.php） (new)
 * 功能说明：框架后台共用的API设置与策略管理，不登记或执行新的业务回调。
 * 方法目录：
 *   public MyStepApiManager::__construct() — 先记录版本，再读取配置及当前接口清单
 *   public static MyStepApiManager::frameworkSettings(myConfig $config) — 为框架参数设置补齐并验证API分组
 *   public MyStepApiManager::normalizeStorage() — 框架设置保存后清理旧全局字段
 *   public MyStepApiManager::fields($kind, $scope = '', $name = '') — 返回后台表单字段声明
 *   public MyStepApiManager::entries() — 返回接口名称、回调说明、状态及原始标识
 *   public MyStepApiManager::selection($scope, $name) — 检查选择目标已登记或有历史策略
 *   public MyStepApiManager::saveEndpoint($scope, $name, array $policy, $version) — 保存已登记接口的额外限制
 *   public MyStepApiManager::resetEndpoint($scope, $name, $version) — 移除覆盖策略，恢复代码和默认行为
 *   public MyStepApiManager::result($scope, $name) — 返回保存后的版本、状态和四项设置
 *   private MyStepApiManager::flag($value) — 严格转换表单布尔值
 *   private MyStepApiManager::csv($value) — 拆分逗号及空白分隔的策略列表
 *   private MyStepApiManager::write(array $config, $version) — 版本比较、锁定及原子发布配置
 * 使用说明：仅由已认证后台调用；调用方负责POST和CSRF。全局参数在config/config.php，接口策略在config/api.php。
 */
/**
 * 功能说明：向新旧框架后台提供相同的API配置字段、接口目录和并发安全保存逻辑。
 * 使用说明：实例只读取受信配置和现有路由注册表，不加载插件业务类或修改接口声明。
 * 方法目录：参见文件顶部。
 */
class MyStepApiManager {
    /** @var array 已验证的当前HTTP配置 */
    public $config;
    /** @var string 读取配置前取得的文件版本；竞争修改只会导致保存冲突 */
    public $version;
    /** @var array 已生成的应用/插件API注册表 */
    private $apis;
    /**
     * 功能说明：先记录版本，再读取配置及当前接口清单，防止视图读写竞争覆盖新值。
     * 参数说明：无显式参数。
     * @return void 初始化当前管理快照，不执行业务接口
     */
    public function __construct() {
        $file=CONFIG.'api.php';clearstatcache(true,$file);$this->version=is_file($file)?hash_file('sha256',$file):'missing';
        $this->config=myApi::httpConfiguration();$router=$GLOBALS['router']??null;
        $this->apis=$router instanceof myRouter?$router->apis:[];
    }
    /**
     * 功能说明：为现有框架参数设置补齐API分组并验证，包含旧配置回退和域名冲突检查。
     * @param myConfig $config 框架根配置对象，修改尚未持久化
     * @return void 将四个标准API字段合并到api分组，不写文件
     * @throws UnexpectedValueException 配置字段或专属域名与现有绑定冲突
     */
    public static function frameworkSettings(myConfig $config) {
        $fallback=myApi::httpConfiguration();unset($fallback['endpoints']);$current=$config->value('api',[]);
        if(is_object($current))$current=myConfig::o2a($current);
        if(!is_array($current))throw new UnexpectedValueException('框架API设置须为数组。');
        $api=myApi::httpSettings(array_replace($fallback,$current));
        if($api['domain']!=='' && is_file(CONFIG.'domain.php')) {
            $bindings=include CONFIG.'domain.php';$host=parse_url('http://'.$api['domain'],PHP_URL_HOST);$binding=$bindings[$host]??null;
            if($binding!==null && (!is_string($binding) || !preg_match('#^/api(?:/|$)#',$binding)))throw new UnexpectedValueException('专属域名已绑定其他应用或路由，请先调整域名绑定。');
        }
        $config->merge(['api'=>$api]);
    }
    /**
     * 功能说明：框架参数保存后移除策略文件中的旧全局字段，保留逐接口设置。
     * 参数说明：无显式参数。
     * @return void 文件缺失或已是纯策略时不写入
     * @throws RuntimeException 策略保存冲突或I/O失败
     */
    public function normalizeStorage() {
        $file=CONFIG.'api.php';if(!is_file($file))return;
        $data=include $file;if(array_diff_key($data,['endpoints'=>[]]))$this->write($this->config,$this->version);
    }

    /**
     * 功能说明：返回后台表单字段声明，布局由各后台自身模板处理。
     * @param string $kind endpoint；全局API参数使用框架现有参数设置
     * @param string $scope 应用/插件范围（默认：''）
     * @param string $name 接口标识（默认：''）
     * @return array name/type/label/value/options/help/required字段列表
     * @throws InvalidArgumentException 未知表单类型或选择目标无效
     */
    public function fields($kind, $scope = '', $name = '') {
        $fields=[['name'=>'version','type'=>'hidden','value'=>$this->version]];
        if($kind!=='endpoint')throw new InvalidArgumentException('未知API表单。');
        $this->selection($scope,$name);$p=$this->config['endpoints'][$scope][$name]??['enabled'=>true,'methods'=>[],'cors'=>false,'admin'=>false];
        return array_merge($fields,[
            ['name'=>'scope','type'=>'hidden','value'=>$scope],['name'=>'name','type'=>'hidden','value'=>$name],
            ['name'=>'policy[enabled]','type'=>'checkbox','label'=>'启用接口','value'=>$p['enabled'],'help'=>'关闭后停止当前接口；启用仍须通过原有业务权限。'],
            ['name'=>'policy[methods]','type'=>'text','label'=>'允许方法','value'=>implode(',',$p['methods']),'help'=>'逗号分隔，如 GET,POST；留空不额外限制。支持 GET、HEAD、POST、PUT、PATCH、DELETE。'],
            ['name'=>'policy[cors]','type'=>'checkbox','label'=>'允许跨域','value'=>$p['cors'],'help'=>'开启后允许任意有效 HTTP/HTTPS 来源；写请求仍须通过 CSRF 和业务权限检查。'],
            ['name'=>'policy[admin]','type'=>'checkbox','label'=>'仅限后台','value'=>$p['admin'],'help'=>'调用者必须已登录框架管理员账号；保留接口原有权限检查。'],
        ]);
    }
    /**
     * 功能说明：枚举现有登记与历史策略，提供显示数据，不判断回调是否可调用以免触发自动加载。
     * 参数说明：无显式参数。
     * @return array 每项包含scope/name/callback/url/status/registered/override/id/policy
     */
    public function entries() {
        $out=[];$scopes=array_unique(array_merge(array_keys($this->apis),array_keys($this->config['endpoints'])));sort($scopes);
        foreach($scopes as $scope) {
            $names=array_unique(array_merge(array_keys($this->apis[$scope]??[]),array_keys($this->config['endpoints'][$scope]??[])));sort($names);
            foreach($names as $name) {
                $registered=array_key_exists($name,$this->apis[$scope]??[]);$callback=$this->apis[$scope][$name]??null;
                if(is_array($callback))$callback=(is_object($callback[0])?get_class($callback[0]):(string)$callback[0]).'::'.(string)($callback[1]??'');
                if(!is_string($callback))$callback=$registered?'已登记回调':'当前未注册（可能已停用或移除）';
                $p=$this->config['endpoints'][$scope][$name]??[];
                $out[]=['scope'=>$scope,'name'=>(string)$name,'callback'=>$callback,'status'=>$registered?((!$this->config['enabled'] || ($p['enabled']??true)===false)?'已停用':'已启用'):'未注册','url'=>myApi::httpURL($scope,(string)$name,''),'id'=>'api-'.hash('sha256',$scope.'/'.$name),'policy'=>array_replace(['enabled'=>true,'methods'=>[],'cors'=>false,'admin'=>false],$p),'registered'=>$registered,'override'=>isset($this->config['endpoints'][$scope][$name])?'已配置':'默认'];
            }
        }
        return $out;
    }
    /**
     * 功能说明：校验目标在当前接口目录或历史策略内，禁止提交任意函数或PHP路径。
     * @param mixed $scope 项目标识
     * @param mixed $name 接口名称
     * @return bool 当前仍登记时为true，仅历史策略时为false
     * @throws InvalidArgumentException 目标格式无效或不存在
     */
    public function selection($scope, $name) {
        if(!is_string($scope) || !is_string($name) || !preg_match('/\A[a-zA-Z_][a-zA-Z0-9_]*\z/D',$scope) || !preg_match('/\A[a-zA-Z_][a-zA-Z0-9_]*\z/D',$name))throw new InvalidArgumentException('API目标无效。');
        $exists=array_key_exists($name,$this->apis[$scope]??[]);
        if(!$exists && !isset($this->config['endpoints'][$scope][$name]))throw new InvalidArgumentException('接口未登记。');
        return $exists;
    }
    /**
     * 功能说明：为代码已登记的接口保存额外限制，后台不能新增可调用目标。
     * @param string $scope 所属项目
     * @param string $name 接口名称
     * @param array $policy enabled/methods/cors/admin四项表单值
     * @param string $version 页面版本
     * @return void 保存完成
     * @throws InvalidArgumentException 未登记目标或策略字段无效
     * @throws RuntimeException 冲突或写入失败
     */
    public function saveEndpoint($scope, $name, array $policy, $version) {
        if(!$this->selection($scope,$name))throw new InvalidArgumentException('未注册接口只能移除历史策略，不能启用。');
        if(array_diff_key($policy,array_flip(['enabled','methods','cors','admin'])) || count($policy)!==4)throw new InvalidArgumentException('API设置字段无效。');
        $p=['enabled'=>$this->flag($policy['enabled']),'methods'=>array_map('strtoupper',$this->csv($policy['methods'])),'cors'=>$this->flag($policy['cors']),'admin'=>$this->flag($policy['admin'])];
        $c=$this->config;$c['endpoints'][$scope][$name]=$p;$this->write($c,$version);
    }
    /**
     * 功能说明：删除覆盖策略，恢复默认及代码声明的行为；历史未注册条目不会因此注册。
     * @param string $scope 所属项目
     * @param string $name 接口名称
     * @param string $version 页面版本
     * @return void 保存完成
     * @throws InvalidArgumentException 目标无效
     * @throws RuntimeException 冲突或写入失败
     */
    public function resetEndpoint($scope, $name, $version) {
        $this->selection($scope,$name);$c=$this->config;unset($c['endpoints'][$scope][$name]);if(empty($c['endpoints'][$scope]))unset($c['endpoints'][$scope]);$this->write($c,$version);
    }
    /**
     * 功能说明：生成单个接口保存后的反馈，供新旧后台AJAX更新状态与表单版本。
     * @param string $scope 所属项目
     * @param string $name 接口名称
     * @return array ok/message/version/entry；历史接口清理后entry为null
     */
    public function result($scope, $name) {
        $entry=null;foreach($this->entries() as $row)if($row['scope']===$scope && $row['name']===$name){$entry=$row;break;}
        return ['ok'=>true,'message'=>'API设置已应用。','version'=>$this->version,'entry'=>$entry];
    }
    /**
     * 功能说明：严格转换后台布尔值，不把未知文本视为启用。
     * @param mixed $value bool、0/1或对应字符串
     * @return bool 转换结果
     * @throws InvalidArgumentException 非允许值
     */
    private function flag($value) {
        if(in_array($value,[true,1,'1'],true))return true;if(in_array($value,[false,0,'0'],true))return false;throw new InvalidArgumentException('API开关值无效。');
    }
    /**
     * 功能说明：拆分逗号或空白分隔的策略文本，保留空列表作为沿用原逻辑。
     * @param mixed $value 普通文本
     * @return array 去重后的字符串列表
     * @throws InvalidArgumentException 输入不是文本
     */
    private function csv($value) {
        if(!is_string($value))throw new InvalidArgumentException('API策略列表须为文本。');return array_values(array_unique(preg_split('/[\s,]+/',trim($value),-1,PREG_SPLIT_NO_EMPTY)));
    }
    /**
     * 功能说明：在固定配置文件的旁路锁内比较版本，写入完整临时文件后原子替换。
     * @param array $config 待严格验证的普通配置
     * @param string $version 视图版本，缺失文件为missing
     * @return void 成功后更新当前快照
     * @throws RuntimeException 保存冲突（409）或文件操作失败
     * @throws InvalidArgumentException 版本或目标不安全
     */
    private function write(array $config, $version) {
        $config=myApi::httpConfiguration($config);$file=CONFIG.'api.php';
        if(!is_string($version) || is_link($file) || is_link($file.'.lock'))throw new InvalidArgumentException('API配置版本或路径无效。');
        $lock=fopen($file.'.lock','c');if(!$lock)throw new RuntimeException('无法锁定API配置。');$tmp='';
        try {
            if(!flock($lock,LOCK_EX))throw new RuntimeException('无法锁定API配置。');
            clearstatcache(true,$file);$current=is_file($file)?hash_file('sha256',$file):'missing';
            if(!hash_equals($current,$version))throw new RuntimeException('API配置已被修改，请重新加载后保存。',409);
            $payload=['endpoints'=>$config['endpoints']];
            $main=is_file(CONFIG.'config.php')?(include CONFIG.'config.php'):[];
            if(!is_array($main) || !isset($main['api'])) {
                $previous=is_file($file)?(include $file):[];
                $payload=array_replace(array_intersect_key($previous,array_flip(['enabled','default_format','json_bytes','body_bytes','domain'])),$payload);
            }
            $content="<?php\nreturn ".var_export($payload,true).";\n";$tmp=$file.'.'.bin2hex(random_bytes(8)).'.tmp';
            if(file_put_contents($tmp,$content,LOCK_EX)!==strlen($content))throw new RuntimeException('API配置写入不完整。');
            if(is_file($file))chmod($tmp,fileperms($file)&0777);
            if(!rename($tmp,$file))throw new RuntimeException('无法发布API配置。');
            if(function_exists('opcache_invalidate'))opcache_invalidate($file,true);
            $this->config=$config;$this->version=hash('sha256',$content);
        } finally {if($tmp!=='' && is_file($tmp))unlink($tmp);flock($lock,LOCK_UN);fclose($lock);}
    }
}

/**
 * 辅助声明：MyStepGuide.class（位于 app/myStep/lib.php）
 * 功能说明：新旧后台共用的文档目录、Markdown阅读及站内文档链接转换。
 *
 * 方法目录：
 *   public MyStepGuide::__construct($baseURL) — 记录当前后台指南地址，不启动框架或会话。
 *   public static MyStepGuide::catalog() — 读取经路径校验的分类详细指南目录，所有路径相对于网站根。
 *   private static MyStepGuide::aliases() — 将旧文档标识映射到新版主题，不读取旧备份。
 *   public MyStepGuide::url($id, $fragment = '') — 生成当前后台中的文档及章节地址。
 *   public MyStepGuide::page($id = null, $navigation = true) — 读取已登记文档，准备正文、分类导航及章节目录。
 *   public MyStepGuide::search($query, $limit = 30) — 只搜索已登记文档，按标题及正文匹配并返回摘要。
 *   public MyStepGuide::ajax($mode, $id = null, $query = null, $renderer = null) — 在已有后台鉴权后输出局部正文或搜索JSON。
 *   private MyStepGuide::resolveLink($href, $source) — 将相对文档引用转换为当前后台阅读地址。
 *   private MyStepGuide::render($text, $source) — 安全解析Markdown，补充章节锚点并修正链接。
 *
 * 使用说明：
 *   1. 管理入口验证登录后显式加载；new MyStepGuide(当前后台guide地址)->page(doc标识)。
 *   2. 只读经校验目录中的Markdown，绝不将请求参数作为文件路径；不复制或保存文档副本。
 *   3. 返回的数据由各应用自己的guide及guide_group模板展示；正文不使用页面缓存。
 *   4. 依赖框架已有Parsedown、DOM和myFile，兼容PHP 7.4，无新增外部资源。
 */
/**
 * 功能说明：将现有框架文档组织为可在任一管理后台阅读的文档中心。
 * 方法目录：参见文件顶部。
 * 使用说明：本类只处理阅读数据，登录与HTTP状态由调用模块负责。
 */
class MyStepGuide {
    /** @var string 当前后台指南URL，支持框架的三种路由模式 */
    private $baseURL;
    /** @var array|null 当前一次page读取使用的已验证目录，避免同页生成大量URL时重复读JSON */
    private $currentCatalog = null;
    /** @var array 规范文档路径到受信标识的索引；只在page读取时建立 */
    private $documentPaths = [];

    /**
     * 功能说明：记录当前后台指南地址，不启动框架或会话。
     * @param string $baseURL 由框架生成的当前后台guide URL，不接受请求直接指定
     * @return void 初始化只读文档阅读器
     */
    public function __construct($baseURL) {
        $this->baseURL=$baseURL;
    }

    /**
     * 功能说明：读取经路径校验的分类详细指南目录，所有路径相对于网站根。
     * 参数说明：无显式参数。
     * @return array 文档标识到title、path、group的受信映射；目录不包含旧版或备份文件
     */
    public static function catalog() {
        // 路径固定在站点内；请求参数不能指定清单或Markdown路径。
        $fallback=[
            'index'=>['title'=>'文档总览','path'=>'docs/README.md','group'=>'阅读入口'],
            'guide-basics'=>['title'=>'整体介绍与框架组成','path'=>'docs/basics/01-guide-basics.md','group'=>'基础说明'],
        ];
        $file=myFile::resolveWithin(ROOT,'docs/catalog.json');
        if(!$file)return $fallback;
        static $cached=[];
        // 文件内容指纹保证同一秒内等长替换清单也能在下一次读取生效。
        $raw=file_get_contents($file);
        if($raw===false || strlen($raw)>1048576)throw new RuntimeException('指南目录不可读取或过大。');
        $fingerprint=$file.':'.hash('sha256',$raw);
        if(isset($cached[$fingerprint]))return $cached[$fingerprint];
        $data=json_decode($raw,true);
        if(!is_array($data) || ($data['version']??null)!==1 || !isset($data['entries']) || !is_array($data['entries']) || count($data['entries'])>1000)throw new RuntimeException('指南目录格式错误。');
        $catalog=[];$paths=[];
        foreach($data['entries'] as $entry) {
            if(!is_array($entry))throw new RuntimeException('指南项目格式错误。');
            foreach(['id','title','path','group'] as $field)if(!isset($entry[$field]) || !is_string($entry[$field]) || $entry[$field]==='')throw new RuntimeException('指南项目字段不完整。');
            $id=$entry['id'];$path=$entry['path'];
            $allowed=preg_match('#\Adocs/(?:basics|backend|frontend|development|showcase|maintenance|history)/(?:[A-Za-z0-9_-]+/)*[A-Za-z0-9_-]+\.md\z#D',$path) || in_array($path,['docs/README.md','app/README.md','plugin/README.md'],true);
            if(!preg_match('/\A[a-z0-9][a-z0-9-]{0,63}\z/D',$id) || !$allowed || isset($catalog[$id]) || isset($paths[$path]) || strlen($entry['title'])>500 || strlen($entry['group'])>200)throw new RuntimeException('指南目录含非法路径或重复项目。');
            $catalog[$id]=['title'=>$entry['title'],'path'=>$path,'group'=>$entry['group']];$paths[$path]=true;
        }
        if(!isset($catalog['index'],$catalog['guide-basics']) || $catalog['index']['path']!==$fallback['index']['path'] || $catalog['guide-basics']['path']!==$fallback['guide-basics']['path'])throw new RuntimeException('指南缺少固定阅读入口。');
        $cached=[$fingerprint=>$catalog];
        return $catalog;
    }

    /**
     * 功能说明：把旧阅读标识定位到新版主题，不注册或读取备份目录。
     * 参数说明：无显式参数。
     * @return array 旧标识到新版受信标识的固定映射
     */
    private static function aliases() {
        return [
            'overview'=>'guide-basics','paths'=>'constants','deployment'=>'installation',
            'guide-modules'=>'backend-index','guide-core'=>'execution','guide-app'=>'app-development','guide-plugin'=>'plugin-development','guide-current'=>'management',
            'frontend-functions'=>'js-ref-global','frontend-extensions'=>'js-addons',
            'utilities'=>'files','extensions'=>'class-loading','php-runtime'=>'input-budget',
            'app-layouts'=>'app-rules','admin'=>'app-info-admin','mystep'=>'app-info-mystep','cms'=>'app-info-cms','examples'=>'app-info-sample','test-app'=>'app-info-test','sample-sources'=>'app-info-sample',
            'consolidation'=>'index','javascript'=>'frontend',
            'maintenance'=>'management','dependencies'=>'installation','phpdoc'=>'standards','tests'=>'index',
            'history-current'=>'history-index','history-published'=>'history-index',
        ];
    }

    /**
     * 功能说明：生成当前后台中的文档及章节地址。
     * @param string $id 已登记的文档标识
     * @param string $fragment 原始章节锚点，不含#（默认：''）
     * @return string 未经HTML转义的指南URL，保留query_string路由首段
     */
    public function url($id, $fragment = '') {
        $catalog=$this->currentCatalog??self::catalog();$target=isset($catalog[$id])?null:(self::aliases()[$id]??null);
        if($target!==null && isset($catalog[$target]) && myFile::resolveWithin(ROOT,$catalog[$target]['path']))$id=$target;
        $url=$this->baseURL.(strpos($this->baseURL,'?')===false?'?':'&').'doc='.rawurlencode($id);
        return $url.($fragment===''?'':'#'.rawurlencode($fragment));
    }

    /**
     * 功能说明：读取已登记文档，准备正文、分类导航及章节目录。
     * @param string|null $id 文档标识；空值优先总览、其次新版基础说明；极简安装仅剩根介绍时读取介绍（默认：null）
     * @param bool $navigation 是否准备左侧目录；局部加载为false（默认：true）
     * @return array id/title/path/html/groups/outline；groups内含title、links与当前分类展开标记open，outline含title/url/level
     * @throws OutOfBoundsException 标识不是字符串、不在固定清单或对应文档不可读
     */
    public function page($id = null, $navigation = true) {
        $catalog=self::catalog();
        $minimal=!myFile::resolveWithin(ROOT,$catalog['index']['path']) && !myFile::resolveWithin(ROOT,$catalog['guide-basics']['path']);
        if($minimal && ($id===null || $id==='' || $id==='overview'))$catalog['overview']=['title'=>'框架介绍','path'=>'readme.md','group'=>'阅读入口'];
        if($id===null || $id==='') {
            if(myFile::resolveWithin(ROOT,$catalog['index']['path']))$id='index';
            elseif(myFile::resolveWithin(ROOT,$catalog['guide-basics']['path']))$id='guide-basics';
            else {$id='overview';$catalog['overview']=['title'=>'框架介绍','path'=>'readme.md','group'=>'阅读入口'];}
        }
        if(is_string($id) && !isset($catalog[$id]))$id=self::aliases()[$id]??$id;
        if(!is_string($id) || !isset($catalog[$id])) throw new OutOfBoundsException('文档不存在，请从目录选择。');
        $this->currentCatalog=$catalog;$this->documentPaths=[];
        foreach($catalog as $key=>$item)$this->documentPaths[$item['path']]=$key;
        $entry=$catalog[$id];$file=myFile::resolveWithin(ROOT,$entry['path']);
        if(!$file || !is_readable($file)) throw new OutOfBoundsException('文档不存在或不可读取，请检查站点文档是否完整。');
        $text=file_get_contents($file);
        if($text===false) throw new OutOfBoundsException('文档读取失败，请从目录重新选择。');
        list($html,$outline)=$this->render($text,$entry['path']);
        $groups=[];
        foreach($navigation?$catalog:[] as $key=>$item) {
            if(!myFile::resolveWithin(ROOT,$item['path'])) continue;
            $groups[$item['group']][]=['title'=>$item['title'],'url'=>$this->url($key),'current'=>$key===$id?'page':'false'];
        }
        $navigation=[];
        foreach($groups as $title=>$links) $navigation[]=['title'=>$title,'links'=>$links,'open'=>in_array('page',array_column($links,'current'),true)];
        return ['id'=>$id,'title'=>$entry['title'],'path'=>$entry['path'],'html'=>$html,'groups'=>$navigation,'outline'=>$outline];
    }

    /**
     * 功能说明：在受信目录内搜索标题、分类和Markdown正文，不读取备份或渲染全部页面。
     *
     * 多个空白分隔关键词采用AND匹配，大小写不敏感；标题匹配优先。结果只含纯文本摘要
     * 和当前后台生成的文档URL；缺失、越界链接及超过2MiB的单篇文件不加入搜索。
     * @param string $query UTF-8关键词，最多80字符、8个词；空字符串返回空结果
     * @param int $limit 返回条目上限，允许1至50（默认：30）
     * @return array query/total/items，items含id/title/group/url/snippet，不回传全文或任意文件内容
     * @throws InvalidArgumentException 查询类型、编码、长度或结果上限无效
     */
    public function search($query, $limit = 30) {
        if(!is_string($query) || !mb_check_encoding($query,'UTF-8'))throw new InvalidArgumentException('请输入有效的搜索词。');
        $query=trim(preg_replace('/\s+/u',' ',$query));
        if(mb_strlen($query,'UTF-8')>80)throw new InvalidArgumentException('搜索词不能超过80个字符。');
        if(!is_int($limit) || $limit<1 || $limit>50)throw new InvalidArgumentException('搜索结果数量无效。');
        if($query==='')return ['query'=>'','total'=>0,'items'=>[]];
        $terms=array_values(array_unique(explode(' ',mb_strtolower($query,'UTF-8'))));
        if(count($terms)>8)throw new InvalidArgumentException('请使用不超过8个关键词。');
        $this->currentCatalog=self::catalog();$matches=[];$order=0;
        foreach($this->currentCatalog as $id=>$entry) {
            $order++;$file=myFile::resolveWithin(ROOT,$entry['path']);
            if(!$file || !is_readable($file) || filesize($file)>2097152)continue;
            $raw=file_get_contents($file);if($raw===false || !mb_check_encoding($raw,'UTF-8'))continue;
            $body=mb_strtolower($raw,'UTF-8');$title=mb_strtolower($entry['title'],'UTF-8');$group=mb_strtolower($entry['group'],'UTF-8');
            $score=0;$position=null;$found=true;
            foreach($terms as $term){
                $inTitle=mb_strpos($title,$term,0,'UTF-8');$inBody=mb_strpos($body,$term,0,'UTF-8');$inGroup=mb_strpos($group,$term,0,'UTF-8');
                if($inTitle===false && $inBody===false && $inGroup===false){$found=false;break;}
                if($inTitle!==false)$score+=40;
                if($inGroup!==false)$score+=5;
                if($inBody!==false){$score++;if($position===null || $inBody<$position)$position=$inBody;}
            }
            if(!$found)continue;
            if($title===mb_strtolower($query,'UTF-8'))$score+=100;
            $start=max(0,($position??0)-45);$snippet=mb_substr($raw,$start,190,'UTF-8');
            $snippet=preg_replace('/\[([^\]\n]+)\]\([^\s)]+\)/u','$1',$snippet);
            $snippet=preg_replace('/(?:^|\n)#{1,6}\s*|```\w*|[\r\n\t]+/u',' ',$snippet);
            $snippet=trim(preg_replace('/\s+/u',' ',$snippet));
            $matches[]=['id'=>$id,'title'=>$entry['title'],'group'=>$entry['group'],'url'=>$this->url($id),'snippet'=>($start>0?'…':'').$snippet.(mb_strlen($raw,'UTF-8')>$start+190?'…':''),'score'=>$score,'order'=>$order];
        }
        usort($matches,static function($a,$b){return ($b['score']<=>$a['score'])?:($a['order']<=>$b['order']);});
        $total=count($matches);$matches=array_slice($matches,0,$limit);
        foreach($matches as &$match){unset($match['score'],$match['order']);}unset($match);
        return ['query'=>$query,'total'=>$total,'items'=>$matches];
    }

    /**
     * 功能说明：为已鉴权的指南模块输出只读JSON，并结束请求，避免拼接后台整页。
     *
     * page通过宿主提供的myTemplate回调生成正文片段；search只返回纯文本条目。
     * 不创建独立公开路由，不在此放宽登录要求；错误响应不包含异常堆栈或绝对路径。
     * @param mixed $mode page或search，其他值返回400
     * @param mixed $id 已登记文档标识，page分支使用（默认：null）
     * @param mixed $query 搜索字符串，search分支使用，null按空字符串处理（默认：null）
     * @param callable|null $renderer 接收page数组并返回当前宿主正文模板HTML（默认：null）
     * @return void 输出JSON后exit；HEAD只发送头部
     */
    public function ajax($mode, $id = null, $query = null, $renderer = null) {
        header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: private, no-store');header('X-Content-Type-Options: nosniff');
        try {
            if(!in_array($_SERVER['REQUEST_METHOD']??'GET',['GET','HEAD'],true)){header('Allow: GET, HEAD');http_response_code(405);$response=['ok'=>false,'message'=>'请使用只读请求。'];}
            elseif($mode==='search')$response=['ok'=>true,'type'=>'search']+$this->search($query??'');
            elseif($mode==='page'){
                if(!is_callable($renderer))throw new RuntimeException('Guide renderer missing');
                $data=$this->page($id,false);$article=call_user_func($renderer,$data);
                if(!is_string($article))throw new RuntimeException('Guide renderer returned invalid content');
                $response=['ok'=>true,'type'=>'document','id'=>$data['id'],'title'=>$data['title'],'url'=>$this->url($data['id']),'article'=>$article];
            } else throw new InvalidArgumentException('未知的文档请求。');
        } catch(OutOfBoundsException $error){http_response_code(404);$response=['ok'=>false,'message'=>$error->getMessage()];}
        catch(InvalidArgumentException $error){http_response_code(400);$response=['ok'=>false,'message'=>$error->getMessage()];}
        catch(Throwable $error){http_response_code(500);error_log('Guide request failed: '.$error->getMessage());$response=['ok'=>false,'message'=>'文档暂时无法读取，请稍后重试。'];}
        if(($_SERVER['REQUEST_METHOD']??'GET')!=='HEAD')echo json_encode($response,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_HEX_TAG|JSON_HEX_AMP);
        exit;
    }

    /**
     * 功能说明：将相对文档引用转换为当前后台阅读地址。
     * @param string $href Markdown中的链接；仅保留http、https、mailto外部协议
     * @param string $source 当前文档的网站根相对路径
     * @return string|null 内部阅读URL或合法外部链接；非文档源文件、越界及非法协议返回null
     */
    private function resolveLink($href, $source) {
        if(preg_match('/[\x00-\x20\x7f\\\\]/',$href)) return null;
        // PHP 7.4的parse_url可能将UTF-8中的C1字节替换为下划线，先逐字节编码再拆分。
        static $encoding=null;
        if($encoding===null) { $encoding=[];for($i=128;$i<256;$i++)$encoding[chr($i)]=sprintf('%%%02X',$i); }
        $parts=parse_url(strtr($href,$encoding));
        if($parts===false) return null;
        if(isset($parts['scheme'])) return in_array(strtolower($parts['scheme']),['http','https','mailto'],true)?$href:null;
        if(isset($parts['host'])) return null;
        $fragment=rawurldecode($parts['fragment']??'');
        $path=rawurldecode($parts['path']??'');
        if($path==='') $path=$source;
        elseif($path[0]!=='/') $path=dirname($source).'/'.$path;
        $segments=[];
        foreach(explode('/',$path) as $part) {
            if($part==='' || $part==='.') continue;
            if($part==='..') {if(!$segments)return null;array_pop($segments);}
            else $segments[]=$part;
        }
        $path=implode('/',$segments);
        if(isset($this->documentPaths[$path]) && myFile::resolveWithin(ROOT,$path))return $this->url($this->documentPaths[$path],$fragment);
        if($source==='readme.md' && $path==='readme.md' && !myFile::resolveWithin(ROOT,'docs/README.md') && !myFile::resolveWithin(ROOT,'docs/basics/01-guide-basics.md'))return $this->url('overview',$fragment);
        return null;
    }

    /**
     * 功能说明：安全解析Markdown，补充章节锚点并修正链接。
     * @param string $text 当前文档原文；代码块保留原有换行和内容
     * @param string $source 网站根相对文档路径，用于解析相对链接
     * @return array 第0项为正文HTML，第1项为h2/h3章节导航
     * @throws RuntimeException Markdown解析后的HTML无法读取
     */
    private function render($text, $source) {
        $md=new Parsedown();$md->setSafeMode(true);
        $html=$md->text($text);$dom=new DOMDocument('1.0','UTF-8');
        $previous=libxml_use_internal_errors(true);
        try {$loaded=$dom->loadHTML('<?xml encoding="UTF-8"><div id="guide-document">'.$html.'</div>',LIBXML_NONET|LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);}
        finally {libxml_clear_errors();libxml_use_internal_errors($previous);}
        if(!$loaded) throw new RuntimeException('Cannot render framework guide');
        $wrapper=$dom->getElementsByTagName('div')->item(0);$outline=[];$ids=[];$id='';
        $id=$this->documentPaths[$source]??'';
        if($id==='' && $source==='readme.md')$id='overview';
        $xpath=new DOMXPath($dom);
        foreach($xpath->query('//h1|//h2|//h3|//h4|//h5|//h6') as $heading) {
            $title=trim($heading->textContent);
            $slug=mb_strtolower($title,'UTF-8');$slug=preg_replace('/[^\pL\pN_\-\s]/u','',$slug);$slug=preg_replace('/\s+/u','-',trim($slug));
            if($slug==='')$slug='section';$anchor=$slug;$suffix=0;
            while(isset($ids[$anchor])) $anchor=$slug.'-'.(++$suffix);
            $ids[$anchor]=true;$heading->setAttribute('id',$anchor);
            $level=(int)substr($heading->nodeName,1);
            if($level===2 || $level===3) $outline[]=['title'=>$title,'url'=>$this->url($id,$anchor),'level'=>(string)$level];
        }
        foreach($xpath->query('//a[@href]') as $link) {
            $href=$link->getAttribute('href');$target=$this->resolveLink($href,$source);
            if($target===null) {$link->removeAttribute('href');$link->setAttribute('class','guide-source');$link->setAttribute('title','源码或未收录资料：'.$href);}
            else {$link->setAttribute('href',$target);if(preg_match('#\Ahttps?://#i',$target)) {$link->setAttribute('target','_blank');$link->setAttribute('rel','noopener noreferrer');}}
        }
        $html='';foreach($wrapper->childNodes as $node) $html.=$dom->saveHTML($node);
        return [$html,$outline];
    }
}

}
