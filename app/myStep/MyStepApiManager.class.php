<?php
/**
 * 文件：app/myStep/MyStepApiManager.class.php (new)
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
