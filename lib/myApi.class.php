<?PHP

/**
 * 文件：lib/myApi.class.php
 * 功能说明：可注册的应用接口调用器
 *   管理接口别名、回调执行及JSON/XML/文本输出，并提供框架API参数、正文预算、逐接口CORS和地址生成。
 *
 * 方法目录：
 *   public static myApi::httpDefaults() — 返回HTTP接口默认值；全局参数属于框架api分组，endpoints属于独立策略。
 *   public static myApi::httpSettings(array $data = []) — 补齐并验证框架api分组，供运行时及现有参数设置共同使用。
 *   public static myApi::httpConfiguration($data = null) — 合并框架api参数与config/api.php的逐接口策略，兼容旧独立参数文件。
 *   public static myApi::httpTarget($path, $apis = null) — 从框架API捕获路径定位已登记接口，兼容插件前缀和框架简写。
 *   public static myApi::httpOrigins($path, $config = null, $apis = null) — 按选定接口的跨域开关返回当前合法Origin，供CORS和CSRF共同使用。
 *   public static myApi::httpURL($scope, $name, $format = null, array $query = []) — 按专属域名或普通路径生成接口地址，沿用三种路由模式和子目录。
 *   public static myApi::httpError($status, $code, $message) — 输出固定JSON错误并结束HTTP请求，不暴露内部配置或异常。
 *   public static myApi::httpBodyLimit(array $config) — 对所有模式和内容类型执行统一请求正文上限，不改变原输入流和上传元数据。
 *   public static myApi::httpCors($config = null, $path = '', $apis = null) — 准备选定接口的CORS、统一正文预算和总开关；预检早于应用及会话启动。
 *   public static myApi::httpPolicy($scope, $name, array $config) — 执行逐接口额外限制，不替代原业务鉴权和CSRF。
 *   public static myApi::httpFormat($value, $default = 'json') — 归一化支持的输出格式别名，未知值使用指定默认格式。
 *   public static myApi::httpArguments(array $tail, array $policy, array $config, $legacy_format) — 按原有位置参数约定构造接口参数；各内容类型已先执行正文上限。
 *   public myApi::init() — 初始化空接口对象；再次调用不主动清空已登记方法
 *   protected myApi::methodName($method) — 验证接口名称，避免非法数组键及控制字符
 *   public myApi::regMethod($method, $func) — 登记或覆盖单个接口，失败时保留原有登记
 *   public myApi::regMethods($method_list) — 先验证整批回调，再一次性合并，避免错误批次只登记了一部分
 *   public myApi::hasMethod($method) — 查询接口是否已明确登记，不进行全局函数回退
 *   public myApi::removeMethod($method) — 注销接口并释放该回调的引用
 *   public myApi::invoke($method, array $para = []) — 执行已登记接口并返回原始结果，不编码、不修改响应头
 *   protected myApi::xmlResult($value) — 将结果编码为完整 UTF-8 XML，限制递归深度并拒绝对象循环
 *   public myApi::run($method, $para=[], $return='json', $charset='utf-8') — 执行接口并返回编码响应，仅在编码成功且头未发送时设置内容类型
 *
 * 使用说明：
 *   1. 先注册接口或选择已登记接口，再按约定调用/输出。
 *   2. 输入过滤和权限由框架或业务入口完成，回调异常按当前执行策略传递。
 *
 * 说明：目录覆盖本文件直接声明的全部方法/函数，包括非公开及静态入口。
 *       继承方法、trait导入/别名和运行时扩展不重复计数；详细参数见各声明旁PHPDoc。
 *       原生签名以PHP 8.5.12为运行基线，PHPDoc类型用于说明，不额外强制运行时类型。
 */
/**
 * 功能说明：管理接口别名、回调执行及JSON/XML/文本输出。
 *
 * 使用说明：
 *   1. 先注册接口或选择已登记接口，再按约定调用/输出。
 *   2. 输入过滤和权限由框架或业务入口完成，回调异常按当前执行策略传递。
 *
 * 补充说明：
 * 应用接口注册、原始执行及响应编码，执行目标仅来自明确登记的回调
 *
 *     $api->regMethod('sum', function ($a, $b) { return $a + $b; });
 *     $value = $api->invoke('sum', [2, 3]);  // 原始结果，不发送响应头
 *     $json = $api->run('sum', [2, 3]);     // 编码 JSON 并设置内容类型
 *     $api->hasMethod('sum');              // 查询登记状态
 *     $api->removeMethod('sum');           // 注销单个接口
 *
 * run() 保留未知接口编码空字符串的旧约定，invoke() 对未知接口明确报错。
 * 参数使用位置顺序，不将关联键解释为 PHP 8 命名参数。JSON 恒为 UTF-8；XML 先以
 * UTF-8 生成，再按显式 charset 无损转码。回调、格式或编码异常不包装成成功空响应。
 * 注册表不是认证/权限系统；HTTP 方法、CSRF、授权仍由应用及框架入口负责。
 *
 * 方法目录：参见本文件顶部；只列直接声明，继承和trait别名沿用原声明注释。
 */
class myApi extends myBase {
    use myTrait;
    protected $methods = [];

    /**
     * 功能说明：返回HTTP接口默认值；全局参数属于框架api分组，endpoints属于独立策略。
     * 参数说明：无显式参数。
     * @return array enabled/default_format/body_bytes/domain/endpoints
     */
    public static function httpDefaults() {
        return ['enabled'=>true,'default_format'=>'json','body_bytes'=>33554432,'domain'=>'','endpoints'=>[]];
    }

    /**
     * 功能说明：补齐并验证框架api分组，供运行时及现有参数设置共同使用。
     * @param array $data api分组中的四个全局字段（默认：[]）
     * @return array 标准全局字段；域名转为小写，正文上限为字节数
     * @throws UnexpectedValueException 未知字段、类型、正文额度或域名无效
     */
    public static function httpSettings(array $data = []) {
        $defaults=self::httpDefaults();unset($defaults['endpoints']);
        if(array_diff_key($data,$defaults))throw new UnexpectedValueException('API全局字段无效。');
        $data=array_replace($defaults,$data);
        if(!is_bool($data['enabled']) || !in_array($data['default_format'],['json','xml','string','hex','code'],true))throw new UnexpectedValueException('API总开关或默认格式无效。');
        if(!is_int($data['body_bytes']) || $data['body_bytes']<1 || $data['body_bytes']>33554432)throw new UnexpectedValueException('API正文上限须为1至33554432字节。');
        if(!is_string($data['domain']))throw new UnexpectedValueException('API专属域名须为文本。');
        $data['domain']=strtolower(trim($data['domain']));
        if($data['domain']!=='') {
            $p=parse_url('http://'.$data['domain']);
            if(!$p || empty($p['host']) || !filter_var($p['host'],FILTER_VALIDATE_DOMAIN,FILTER_FLAG_HOSTNAME) || isset($p['user']) || isset($p['pass']) || isset($p['path']) || isset($p['query']) || isset($p['fragment']) || preg_match('/[\x00-\x20*]/',$data['domain']) || strlen($data['domain'])>260 || (isset($p['port']) && $p['port']<1) || $data['domain']!==$p['host'].(isset($p['port'])?':'.$p['port']:''))throw new UnexpectedValueException('API专属域名只填写主机名及可选端口，不含协议、路径或通配符。');
        }
        return $data;
    }

    /**
     * 功能说明：合并框架api参数与config/api.php的逐接口策略，兼容旧独立参数文件。
     * @param array|null $data 显式完整配置；null读取当前框架及策略文件（默认：null）
     * @return array 标准全局参数及已补齐的接口策略
     * @throws UnexpectedValueException 配置、来源或逐接口策略无效
     */
    public static function httpConfiguration($data = null) {
        if($data===null) {
            $legacy=[];$file=CONFIG.'api.php';
            if(is_link($file))throw new UnexpectedValueException('API配置不能使用符号链接。');
            if(is_file($file)) {
                if(function_exists('opcache_invalidate'))opcache_invalidate($file,true);
                $legacy=(static function($path){return include $path;})($file);
                if(!is_array($legacy))throw new UnexpectedValueException('API策略文件须返回数组。');
            }
            $old=array_intersect_key($legacy,array_flip(['enabled','default_format','body_bytes','domain']));
            if(!isset($old['body_bytes']) && isset($legacy['json_bytes']))$old['body_bytes']=$legacy['json_bytes'];
            $setting=$GLOBALS['ms_setting']??null;
            $core=$setting instanceof myConfig?$setting->value('api',[]):($setting->api??[]);
            if(is_object($core))$core=myConfig::o2a($core);
            if(!is_array($core))throw new UnexpectedValueException('框架API设置须为数组。');
            $data=array_replace($old,$core);$data['endpoints']=$legacy['endpoints']??[];

        }
        if(!is_array($data) || array_diff_key($data,self::httpDefaults()))throw new UnexpectedValueException('API配置字段无效。');
        $endpoints=$data['endpoints']??[];unset($data['endpoints']);$data=self::httpSettings($data);$data['endpoints']=$endpoints;
        if(!is_array($endpoints))throw new UnexpectedValueException('API策略列表无效。');
        foreach($endpoints as $scope=>$items) {
            if(!is_string($scope) || !preg_match('/\A[a-zA-Z_][a-zA-Z0-9_]*\z/D',$scope) || !is_array($items))throw new UnexpectedValueException('API所属项目无效。');
            foreach($items as $name=>$policy) {
                if(!is_string($name) || !preg_match('/\A[a-zA-Z_][a-zA-Z0-9_]*\z/D',$name) || !is_array($policy))throw new UnexpectedValueException('API策略标识无效。');
                // 旧格式、参数模式和来源名单只作兼容读取；现统一使用位置参数及跨域开关。
                $old=['formats'=>null,'mode'=>null,'cors_origins'=>null];
                $base=['enabled'=>true,'methods'=>[],'cors'=>false,'admin'=>false];
                if(array_diff_key($policy,$base+$old))throw new UnexpectedValueException('API策略字段无效。');
                $policy=array_replace($base,array_diff_key($policy,$old));
                foreach(['enabled','cors','admin'] as $flag)if(!is_bool($policy[$flag]))throw new UnexpectedValueException('API开关须为布尔值。');
                if(!is_array($policy['methods']) || array_filter($policy['methods'],static function($v){return !is_string($v);}) || array_diff($policy['methods'],['GET','HEAD','POST','PUT','PATCH','DELETE']))throw new UnexpectedValueException('API方法列表无效。');
                $policy['methods']=array_values(array_unique($policy['methods']));$data['endpoints'][$scope][$name]=$policy;
            }
        }
        return $data;
    }

    /**
     * 功能说明：从框架API捕获路径定位已登记接口，兼容插件前缀和框架简写。
     * @param string $path API前缀之后的路径
     * @param array|null $apis 当前API注册表，null使用全局路由（默认：null）
     * @return array [scope,name]；不存在返回空数组，不加载或调用目标
     */
    public static function httpTarget($path, $apis = null) {
        if($apis===null){$router=$GLOBALS['router']??null;$apis=$router instanceof myRouter?$router->apis:[];}
        if(strpos($path,'myStep/plugin_')===0)$path=substr($path,7);
        $parts=explode('/',trim(preg_replace('#[?&].*$#','',$path),'/'));$scope=$parts[0]??'';$name=$parts[1]??'';
        if(!isset($apis[$scope])){$name=$scope;$scope='myStep';}
        return isset($apis[$scope][$name])?[$scope,$name]:[];
    }

    /**
     * 功能说明：按选定接口的跨域开关返回当前合法Origin，供CORS和CSRF共同使用。
     * @param string $path API前缀之后的路径
     * @param array|null $config 已验证配置，null读取当前配置（默认：null）
     * @param array|null $apis API注册表，null使用全局路由（默认：null）
     * @return array 当前请求的合法来源；跨域关闭、接口未登记或来源非法时为空数组
     */
    public static function httpOrigins($path, $config = null, $apis = null) {
        if($config===null)$config=self::httpConfiguration();$target=self::httpTarget($path,$apis);
        if(!$target || !($config['endpoints'][$target[0]][$target[1]]['cors']??false))return [];
        $origin=$_SERVER['HTTP_ORIGIN']??null;
        // 复用框架来源解析，禁止null、路径、账号、控制字符等非法Origin进入响应头。
        return is_string($origin) && filter_var($origin,FILTER_VALIDATE_URL) && myStep::apiOriginAllowed($origin,'http://localhost',[],'',[$origin])?[$origin]:[];
    }

    /**
     * 功能说明：按专属域名或普通路径生成接口地址，沿用三种路由模式和子目录。
     * @param string $scope 应用或plugin_插件标识
     * @param string $name 接口名称
     * @param string|null $format 返回格式，null使用当前默认值，空字符串不附加格式尾段（默认：null）
     * @param array $query 查询参数（默认：[]）
     * @return string 专属域名非空时返回绝对URL，否则返回普通框架API路径
     * @throws InvalidArgumentException 接口标识或格式无效
     */
    public static function httpURL($scope, $name, $format = null, array $query = []) {
        $config=self::httpConfiguration();
        foreach([$scope,$name] as $v)if(!is_string($v) || !preg_match('/\A[a-zA-Z_][a-zA-Z0-9_]*\z/D',$v))throw new InvalidArgumentException('Invalid API URL target');
        $format=$format??$config['default_format'];if($format!=='' && self::httpFormat($format,'')==='')throw new InvalidArgumentException('Invalid API URL format');
        $path=$scope.'/'.$name.($format===''?'':'/'.$format).($query?'?'.http_build_query($query,'','&',PHP_QUERY_RFC3986):'');
        if($config['domain']==='')return myStep::setURL('api/'.$path);
        return (isHttps()?'https://':'http://').$config['domain'].myStep::setURL($path);
    }

    /**
     * 功能说明：输出固定JSON错误并结束HTTP请求，不暴露内部配置或异常。
     * @param int $status HTTP错误状态
     * @param string $code 固定错误标识
     * @param string $message 固定说明
     * @return void HEAD无正文；其他方法输出后结束
     */
    public static function httpError($status, $code, $message) {
        http_response_code($status);header('Content-Type: application/json; charset=UTF-8');header('Cache-Control:no-store');
        if(strtoupper($_SERVER['REQUEST_METHOD']??'GET')!=='HEAD')echo json_encode(['error'=>$code,'message'=>$message],JSON_UNESCAPED_UNICODE);exit;
    }

    /**
     * 功能说明：对所有模式和内容类型执行统一请求正文上限，不改变原输入流和上传元数据。
     * @param array $config 已验证的API配置
     * @return void 合法请求继续；超限413、声明无效400、无长度multipart为411
     * @throws RuntimeException 无法读取请求正文
     */
    public static function httpBodyLimit(array $config) {
        $declared=$_SERVER['CONTENT_LENGTH']??'';
        if($declared!=='') {
            $problem=mySecurity::inspectRequest(['CONTENT_LENGTH'=>$declared],[],['body_bytes'=>$config['body_bytes']]);
            if($problem)self::httpError($problem['status'],'body_limit',$problem['error']);return;
        }
        $type=strtolower(trim(explode(';',$_SERVER['CONTENT_TYPE']??'')[0]));
        // POST multipart已可能被PHP解析且不再暴露原始流，无法据此核验完整元数据体积。
        if($type==='multipart/form-data')self::httpError(411,'length_required','Multipart requests require a declared length');
        $stream=fopen('php://input','rb');if(!$stream)throw new RuntimeException('Cannot inspect API body');$total=0;
        try {while(!feof($stream)){$chunk=fread($stream,min(65536,$config['body_bytes']-$total+1));if($chunk===false)throw new RuntimeException('Cannot inspect API body');$total+=strlen($chunk);if($total>$config['body_bytes'])self::httpError(413,'body_limit','Request body exceeds configured limit');if($chunk==='')break;}}
        finally {fclose($stream);}
    }

    /**
     * 功能说明：准备选定接口的CORS、统一正文预算和总开关；预检早于应用及会话启动。
     * @param array|null $config 已验证配置，null时读取（默认：null）
     * @param string $path API前缀之后的捕获路径（默认：''）
     * @param array|null $apis 当前API注册表，null使用全局路由（默认：null）
     * @return void 普通请求继续；预检204、来源403、关闭503及正文拒绝后结束
     */
    public static function httpCors($config = null, $path = '', $apis = null) {
        global $ms_setting,$domain;
        if($config===null)$config=self::httpConfiguration();$origin=myReq::server('HTTP_ORIGIN');header('Vary: Origin',false);
        if($origin!==null) {
            $extra=self::httpOrigins($path,$config,$apis);$current=(isHttps()?'https://':'http://').myReq::server('HTTP_HOST');
            if(!myStep::apiOriginAllowed($origin,$current,[],'',$extra))self::httpError(403,'origin_denied','Origin is not allowed');
            header('Access-Control-Allow-Origin: '.$origin);header('Access-Control-Allow-Credentials:true');
        }
        header('Access-Control-Allow-Methods: GET,HEAD,POST,PUT,PATCH,DELETE,OPTIONS');
        header('Access-Control-Allow-Headers: Content-MD5,X-Alt-Referer,DNT,X-CustomHeader,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,X-CSRF-Token');
        if(!$config['enabled'])self::httpError(503,'api_disabled','API service is disabled');self::httpBodyLimit($config);
        if(strtoupper($_SERVER['REQUEST_METHOD']??'GET')==='OPTIONS'){http_response_code(204);exit;}
    }

    /**
     * 功能说明：执行逐接口额外限制，不替代原业务鉴权和CSRF。
     * @param string $scope 已登记的应用或插件范围
     * @param string $name 已登记接口名称
     * @param array $config 已验证配置
     * @return array 完整策略；拒绝时404/405/403结束
     */
    public static function httpPolicy($scope, $name, array $config) {
        global $ms_setting;
        $policy=$config['endpoints'][$scope][$name]??['enabled'=>true,'methods'=>[],'cors'=>false,'admin'=>false];
        if(!$policy['enabled'])self::httpError(404,'api_disabled','API endpoint is disabled');$method=strtoupper($_SERVER['REQUEST_METHOD']??'GET');
        if($policy['methods'] && !in_array($method,$policy['methods'],true)){header('Allow: '.implode(', ',array_merge($policy['methods'],['OPTIONS'])));self::httpError(405,'method_denied','HTTP method is not allowed');}
        if($policy['admin'] && (myReq::session('ms_user')!==$ms_setting->gen->s_usr || myReq::session('sysop')!=='y'))self::httpError(403,'admin_required','Framework administrator is required');return $policy;
    }

    /**
     * 功能说明：归一化支持的输出格式别名，未知值使用指定默认格式。
     * @param mixed $value 格式候选
     * @param string $default 未知时的回退值（默认：'json'）
     * @return string 规范格式或显式的空回退值
     */
    public static function httpFormat($value, $default = 'json') {
        $aliases=['j'=>'json','json'=>'json','x'=>'xml','xml'=>'xml','s'=>'string','string'=>'string','h'=>'hex','hex'=>'hex','c'=>'code','code'=>'code','script'=>'code'];return is_string($value)?($aliases[strtolower($value)]??$default):$default;
    }

    /**
     * 功能说明：按原有位置参数约定构造接口参数；各内容类型已先执行正文上限。
     * @param array $tail 接口之后的路径段
     * @param array $policy 当前接口策略；保留签名兼容，参数模式不再可配置
     * @param array $config 已验证配置
     * @param mixed $legacy_format 旧分发器的格式候选
     * @return array [规范格式,位置参数列表]；GET/POST合并后追加路径参数
     * @throws RuntimeException 读取请求参数失败；正文预算由前置httpBodyLimit检查
     */
    public static function httpArguments(array $tail, array $policy, array $config, $legacy_format) {
        $format=self::httpFormat($legacy_format,$config['default_format']);
        $args=myReq::getValue('get','[ALL]');
        if(!in_array(strtoupper($_SERVER['REQUEST_METHOD']??'GET'),['GET','HEAD'],true))$args=array_replace($args,myReq::getValue('post','[ALL]'));
        unset($args['core']);return [$format,array_merge($args,$tail)];
    }

    /**
     * 功能说明：初始化空接口对象；再次调用不主动清空已登记方法
     *
     * 参数说明：无显式参数。
     *
     * @return void 无返回值。
     */
    public function init() {}

    /**
     * 功能说明：验证接口名称，避免非法数组键及控制字符
     *
     * @param string|int $method 接口标识，保留数字键兼容
     * @return string 非空规范名称；大小写仍按旧注册表区分
     * @throws InvalidArgumentException 名称类型、空值或控制字符非法
     */
    protected function methodName($method) {
        if((!is_string($method) && !is_int($method)) || (string)$method === '' || preg_match('/[\x00-\x1f\x7f]/', (string)$method)) throw new InvalidArgumentException('Invalid API method name');
        return (string)$method;
    }

    /**
     * 功能说明：登记或覆盖单个接口，失败时保留原有登记
     *
     * @param string|int $method 非空接口标识
     * @param callable $func 函数、静态/对象方法、闭包或可调用对象
     * @return $this 当前 API 对象，可连续登记
     * @throws InvalidArgumentException 名称非法或回调不可调用
     */
    public function regMethod($method, $func) {
        $name = $this->methodName($method);
        if(!is_callable($func)) throw new InvalidArgumentException('API callback must be callable');
        $this->methods[$name] = $func;
        return $this;
    }

    /**
     * 功能说明：先验证整批回调，再一次性合并，避免错误批次只登记了一部分
     *
     * @param array|Traversable $method_list 接口名称到回调的映射
     * @return $this 当前 API 对象；同名接口覆盖旧回调
     * @throws InvalidArgumentException 列表类型、名称或任一回调非法
     */
    public function regMethods($method_list) {
        if(!is_array($method_list) && !($method_list instanceof Traversable)) throw new InvalidArgumentException('API methods must be a map');
        $next = [];
        foreach($method_list as $key => $value) {
            $name = $this->methodName($key);
            if(!is_callable($value)) throw new InvalidArgumentException('API callback must be callable');
            $next[$name] = $value;
        }
        $this->methods = array_replace($this->methods, $next);
        return $this;
    }

    /**
     * 功能说明：查询接口是否已明确登记，不进行全局函数回退
     *
     * @param string|int $method 接口标识
     * @return bool 名称存在于当前注册表时为 true
     */
    public function hasMethod($method) { return array_key_exists($this->methodName($method), $this->methods); }

    /**
     * 功能说明：注销接口并释放该回调的引用
     *
     * @param string|int $method 接口标识
     * @return bool 原先存在并已删除为 true；不存在为 false
     */
    public function removeMethod($method) {
        $name = $this->methodName($method); $exists = array_key_exists($name, $this->methods);
        unset($this->methods[$name]); return $exists;
    }

    /**
     * 功能说明：执行已登记接口并返回原始结果，不编码、不修改响应头
     *
     *
     * 调用示例：
     *     $api->regMethod('sum', function ($a, $b) { return $a + $b; });
     *     $sum = $api->invoke('sum', [2, 3]); // 原始整数结果5
     *
     * @param string|int $method 明确登记的接口名
     * @param array $para 按遍历顺序作为位置参数传递，关联键不作为参数名（默认：[]）
     * @return mixed 回调结果，保留 false/null/0/空数组等类型
     * @throws BadMethodCallException 接口未登记，不自动寻找同名全局函数
     * @throws Throwable 业务回调异常继续传递
     */
    public function invoke($method, array $para = []) {
        $name = $this->methodName($method);
        if(!array_key_exists($name, $this->methods)) throw new BadMethodCallException('API method is not registered');
        return call_user_func_array($this->methods[$name], array_values($para));
    }

    /**
     * 功能说明：将结果编码为完整 UTF-8 XML，限制递归深度并拒绝对象循环
     *
     * @param mixed $value 原始结果；对象使用公开属性，数字/非法元素名回退 item
     * @return string 以 mystep 为根的 XML，不发送响应头
     * @throws InvalidArgumentException 文本不是有效 UTF-8 或包含资源
     * @throws RuntimeException 无 XMLWriter、结果嵌套超限或包含循环引用
     */
    protected function xmlResult($value) {
        if(!class_exists('XMLWriter')) throw new RuntimeException('XML responses require XMLWriter');
        $xml = new XMLWriter(); $xml->openMemory(); $xml->startDocument('1.0', 'UTF-8'); $xml->startElement('mystep');
        $objects = new SplObjectStorage();
        $write = function($item, $depth) use (&$write, $xml, $objects) {
            if($depth > 64) throw new RuntimeException('API XML nesting limit exceeded');
            if(is_object($item) || is_array($item)) {
                $object = is_object($item) ? $item : null;
                if($object !== null) { if($objects->offsetExists($object)) throw new RuntimeException('Cyclic API XML result'); $objects->offsetSet($object); }
                try {
                    foreach(is_object($item) ? get_object_vars($item) : $item as $key => $part) {
                        $tag = is_string($key) && preg_match('/\A[A-Za-z_][A-Za-z0-9_.-]*\z/D', $key) ? $key : 'item';
                        $xml->startElement($tag); $write($part, $depth+1); $xml->endElement();
                    }
                } finally { if($object !== null) $objects->offsetUnset($object); }
            } else {
                if(is_resource($item)) throw new InvalidArgumentException('API XML cannot encode resources');
                $text = $item === null ? '' : (is_bool($item) ? ($item ? 'true' : 'false') : (string)$item);
                if(!preg_match('//u', $text)) throw new InvalidArgumentException('API XML requires UTF-8 text');
                $text = preg_replace('/[^\x09\x0a\x0d\x20-\x{d7ff}\x{e000}-\x{fffd}\x{10000}-\x{10ffff}]/u', '', $text);
                $xml->text($text);
            }
        };
        $write($value, 0); $xml->endElement(); $xml->endDocument(); return $xml->outputMemory();
    }

    /**
     * 功能说明：执行接口并返回编码响应，仅在编码成功且头未发送时设置内容类型
     *
     * JSON 不猜测或改写对象编码，避免修改回调返回对象；非法 UTF-8/循环会明确报错。
     * XML 字符串需要有效 UTF-8，非法 XML 控制字符移除；charset 仅控制文本类响应
     * 的输出标签及 XML 转码，不用于把任意二进制猜测为 Unicode。
     *
     * @param string|int $method 已登记接口；未知接口沿用编码空字符串的旧行为
     * @param array $para 位置参数数组，关联键按遍历顺序传递（默认：[]）
     * @param string $return json/j、xml/x、string/s、hex/h、code/c/script；未知值回退 JSON（默认：'json'）
     * @param string $charset 文本输出字符集，空值使用 UTF-8；JSON 恒为 UTF-8（默认：'utf-8'）
     * @return string 编码后的内容，不 echo、不结束请求
     * @throws InvalidArgumentException 参数/字符集标签非法或 XML 文本非法
     * @throws JsonException JSON 编码失败，不静默返回 false
     * @throws RuntimeException XML 超限/循环、字符转码失败或编码不可用
     * @throws Throwable 业务回调异常继续传递
     */
    public function run($method, $para=[], $return='json', $charset='utf-8') {
        if(!is_array($para)) throw new InvalidArgumentException('API parameters must be an array');
        if(!is_string($return)) throw new InvalidArgumentException('API response format must be a string');
        if($charset === '') $charset = 'UTF-8';
        if(!is_string($charset) || !preg_match('/\A[A-Za-z][A-Za-z0-9._-]*\z/D', $charset)) throw new InvalidArgumentException('Invalid API response charset');
        $result = $this->hasMethod($method) ? $this->invoke($method, $para) : '';
        switch(strtolower($return)) {
            case 'x': case 'xml':
                $result = $this->xmlResult($result);
                if(!in_array(strtoupper($charset), ['UTF-8','UTF8'], true)) {
                    if(!function_exists('iconv')) throw new RuntimeException('XML charset conversion requires iconv');
                    $result = str_replace('encoding="UTF-8"', 'encoding="'.$charset.'"', $result);
                    try { $converted = @iconv('UTF-8', $charset, $result); } catch(Throwable $error) { $converted = false; }
                    if($converted === false) throw new RuntimeException('Cannot encode XML response without data loss');
                    $result = $converted;
                }
                $type = 'application/xml; charset='.$charset; break;
            case 's': case 'string': $result = myString::fromAny($result); $type = 'text/plain; charset='.$charset; break;
            case 'h': case 'hex': $result = myString::toHex(myString::fromAny($result)); $type = 'text/plain; charset=US-ASCII'; break;
            case 'c': case 'code': case 'script': $result = myString::toScript(['result'=>$result]); $type = 'text/plain; charset='.$charset; break;
            default: $result = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR); $type = 'application/json; charset=UTF-8';
        }
        if(!headers_sent()) header('Content-Type: '.$type);
        return $result;
    }
}
