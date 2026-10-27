<?PHP
/********************************************
*                                           *
* Name    : Social OAuth Client (new)       *
* Modifier: Windy2000                       *
* Time    : 2026-10-25                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/
/**
 * 文件：plugin/social/SocialOAuth.class.php (new)
 * 功能说明：微信/QQ/Google/GitHub服务端授权码客户端；固定HTTPS端点，不接受可配置远程URL或浏览器提供的用户身份。
 * 方法目录：
 *   public SocialOAuth::__construct(array $options,?callable $transport=null) — 保存已校验配置；测试传输只能由受信PHP代码注入。
 *   public static SocialOAuth::providers(): array — 列出固定平台标识和展示名称。参数说明：无显式参数。
 *   public SocialOAuth::enabled(string $provider): bool — 判断平台是否已显式启用并有应用凭据。
 *   public SocialOAuth::clientKey(string $provider): string — 为平台应用配置生成稳定命名空间，不跨AppID或微信场景自动合并。
 *   public SocialOAuth::authorization(string $provider,string $redirect,string $state,string $verifier): string — 构造固定平台授权地址。
 *   public SocialOAuth::identity(string $provider,string $code,string $redirect,string $verifier): array — 交换授权码并从平台服务端取得稳定身份，不接受前端openid或邮箱作为认证证明。
 *   private SocialOAuth::request(string $method,string $url,array $headers=[],string $body=''): string — 仅向代码内固定端点发起受验证HTTPS请求，限制响应预算并禁止跟随重定向。
 *   private SocialOAuth::decode(string $body): array — 解析JSON或QQ历史响应，不执行JSONP代码。
 *   private SocialOAuth::text($value,int $max=255): string — 校验平台身份/令牌的非空受控文本。
 * 使用说明：state/一次性/绑定由SocialFlow处理；Google/GitHub使用PKCE，Google从认证的userinfo取得sub，不解码未验证的JWT。令牌只在本次请求内存在。
 */
class SocialOAuth {
    private array $options;
    private $transport;
    /**
     * 功能说明：保存已校验配置；测试传输只能由受信PHP代码注入。
     * @param array $options 插件选项
     * @param callable|null $transport 默认null；测试传输(method,url,headers,body):string，生产不传
     * @return void 初始化完成
     */
    public function __construct(array $options,?callable $transport=null){$this->options=$options;$this->transport=$transport;}
    /**
     * 功能说明：列出固定平台标识和展示名称。参数说明：无显式参数。
     * @return array 平台字典
     */
    public static function providers(): array {return ['wechat'=>'微信','qq'=>'QQ','google'=>'Google','github'=>'GitHub'];}
    /**
     * 功能说明：判断平台是否已显式启用并有应用凭据。
     * @param string $provider 平台标识
     * @return bool 可发起该平台授权
     */
    public function enabled(string $provider): bool {return isset(self::providers()[$provider]) && ($this->options[$provider.'_enabled']??false)===true && (string)($this->options[$provider.'_client_id']??'')!=='' && (string)($this->options[$provider.'_secret']??'')!=='';}
    /**
     * 功能说明：为平台应用配置生成稳定命名空间，不跨AppID或微信场景自动合并。
     * @param string $provider 平台标识
     * @return string 应用指纹
     * @throws Throwable 输入/状态无效或底层服务失败时终止本次流程，不建立登录
     */
    public function clientKey(string $provider): string {if(!$this->enabled($provider))throw new DomainException('该平台尚未配置或未启用。');return hash('sha256',$provider."\0".$this->options[$provider.'_client_id']."\0".($provider==='wechat'?($this->options['wechat_mode']??'web'):''));}
    /**
     * 功能说明：构造固定平台授权地址。
     * @param string $provider 平台标识
     * @param string $redirect 精确回调URI
     * @param string $state 当前浏览器一次性状态
     * @param string $verifier 随机PKCE验证串
     * @return string 平台授权URL
     * @throws Throwable 输入/状态无效或底层服务失败时终止本次流程，不建立登录
     */
    public function authorization(string $provider,string $redirect,string $state,string $verifier): string {
        $this->clientKey($provider);$id=$this->options[$provider.'_client_id'];$params=['client_id'=>$id,'redirect_uri'=>$redirect,'response_type'=>'code','state'=>$state];$fragment='';
        switch($provider){
            case 'google':$url='https://accounts.google.com/o/oauth2/v2/auth';$params['scope']='openid email profile';$params['prompt']='select_account';break;
            case 'github':$url='https://github.com/login/oauth/authorize';$params['scope']='read:user user:email';break;
            case 'qq':$url='https://graph.qq.com/oauth2.0/authorize';$params['scope']='get_user_info';break;
            case 'wechat':
                unset($params['client_id']);$params['appid']=$id;
                if(($this->options['wechat_mode']??'web')==='mp'){$url='https://open.weixin.qq.com/connect/oauth2/authorize';$params['scope']='snsapi_userinfo';}
                else{$url='https://open.weixin.qq.com/connect/qrconnect';$params['scope']='snsapi_login';}
                $fragment='#wechat_redirect';break;
            default:throw new InvalidArgumentException('不支持的登录平台。');
        }
        if(in_array($provider,['google','github'],true)){$params['code_challenge']=rtrim(strtr(base64_encode(hash('sha256',$verifier,true)),'+/','-_'),'=');$params['code_challenge_method']='S256';}
        return $url.'?'.http_build_query($params,'','&',PHP_QUERY_RFC3986).$fragment;
    }
    /**
     * 功能说明：交换授权码并从平台服务端取得稳定身份，不接受前端openid或邮箱作为认证证明。
     * @param string $provider 平台标识
     * @param string $code 平台返回的授权码
     * @param string $redirect 与发起授权完全相同的URI
     * @param string $verifier 本浏览器保存的PKCE验证串
     * @return array provider/subject/display_name/email；无访问或刷新令牌
     * @throws Throwable 输入/状态无效或底层服务失败时终止本次流程，不建立登录
     */
    public function identity(string $provider,string $code,string $redirect,string $verifier): array {
        $this->clientKey($provider);
        if($code===''||strlen($code)>2048||preg_match('/[\x00-\x20\x7f]/',$code))throw new DomainException('授权码无效，请重新登录。');
        $id=$this->options[$provider.'_client_id'];$secret=$this->options[$provider.'_secret'];
        $fields=['client_id'=>$id,'client_secret'=>$secret,'code'=>$code,'redirect_uri'=>$redirect,'grant_type'=>'authorization_code'];
        $subject=$name=$email='';
        if($provider==='google'||$provider==='github'){
            $fields['code_verifier']=$verifier;$tokenUrl=$provider==='google'?'https://oauth2.googleapis.com/token':'https://github.com/login/oauth/access_token';
            $tokens=$this->decode($this->request('POST',$tokenUrl,['Accept: application/json','Content-Type: application/x-www-form-urlencoded'],http_build_query($fields,'','&',PHP_QUERY_RFC3986)));
            $token=$this->text($tokens['access_token']??null,8192);if(isset($tokens['error'])||strtolower($this->text($tokens['token_type']??'bearer',32))!=='bearer')throw new DomainException('平台授权失败，请重新授权。');
            $headers=['Authorization: Bearer '.$token,'Accept: application/json'];
            if($provider==='google'){
                $profile=$this->decode($this->request('GET','https://openidconnect.googleapis.com/v1/userinfo',$headers));
                $subject=$this->text($profile['sub']??null);$name=is_string($profile['name']??null)?$profile['name']:'';$email=is_string($profile['email']??null)?$profile['email']:'';
            }else{
                $profile=$this->decode($this->request('GET','https://api.github.com/user',$headers));
                $raw=$profile['id']??null;if(!(is_int($raw)&&$raw>0)&&!(is_string($raw)&&preg_match('/\A[1-9][0-9]*\z/D',$raw)))throw new DomainException('平台未返回有效用户编号。');$subject=(string)$raw;
                $name=is_string($profile['name']??null)&&$profile['name']!==''?$profile['name']:(is_string($profile['login']??null)?$profile['login']:'');
                $emails=$this->decode($this->request('GET','https://api.github.com/user/emails',$headers));
                foreach($emails as $item)if(is_array($item)&&($item['verified']??false)===true&&is_string($item['email']??null)){if($email===''||($item['primary']??false)===true)$email=$item['email'];if(($item['primary']??false)===true)break;}
            }
        }elseif($provider==='qq'){
            $fields['fmt']='json';$tokens=$this->decode($this->request('GET','https://graph.qq.com/oauth2.0/token?'.http_build_query($fields,'','&',PHP_QUERY_RFC3986)));
            if(isset($tokens['error']))throw new DomainException('QQ授权失败，请重新授权。');$token=$this->text($tokens['access_token']??null,8192);
            $identity=$this->decode($this->request('GET','https://graph.qq.com/oauth2.0/me?'.http_build_query(['access_token'=>$token,'fmt'=>'json'],'','&',PHP_QUERY_RFC3986)));
            if(!hash_equals((string)$id,$this->text($identity['client_id']??null,256)))throw new DomainException('QQ应用身份不一致。');$subject=$this->text($identity['openid']??null);
            $profile=$this->decode($this->request('GET','https://graph.qq.com/user/get_user_info?'.http_build_query(['access_token'=>$token,'oauth_consumer_key'=>$id,'openid'=>$subject],'','&',PHP_QUERY_RFC3986)));
            if(($profile['ret']??-1)!==0)throw new DomainException('无法读取QQ用户资料。');$name=is_string($profile['nickname']??null)?$profile['nickname']:'';
        }else{
            $tokens=$this->decode($this->request('GET','https://api.weixin.qq.com/sns/oauth2/access_token?'.http_build_query(['appid'=>$id,'secret'=>$secret,'code'=>$code,'grant_type'=>'authorization_code'],'','&',PHP_QUERY_RFC3986)));
            if(!empty($tokens['errcode']))throw new DomainException('微信授权失败，请重新授权。');$token=$this->text($tokens['access_token']??null,8192);$subject=$this->text($tokens['openid']??null);
            $profile=$this->decode($this->request('GET','https://api.weixin.qq.com/sns/userinfo?'.http_build_query(['access_token'=>$token,'openid'=>$subject,'lang'=>'zh_CN'],'','&',PHP_QUERY_RFC3986)));
            if(!empty($profile['errcode'])||!hash_equals($subject,$this->text($profile['openid']??null)))throw new DomainException('微信身份资料不一致。');$name=is_string($profile['nickname']??null)?$profile['nickname']:'';
        }
        $subject=$this->text($subject);if($name!==''&&!mb_check_encoding($name,'UTF-8'))$name='';$name=mb_substr($name,0,60,'UTF-8');
        if($email!==''){try{$email=myUser::email($email);}catch(InvalidArgumentException){$email='';}}
        return ['provider'=>$provider,'subject'=>$subject,'display_name'=>$name,'email'=>$email];
    }
    /**
     * 功能说明：仅向代码内固定端点发起受验证HTTPS请求，限制响应预算并禁止跟随重定向。
     * @param string $method GET或POST
     * @param string $url 固定平台端点及受控查询
     * @param array $headers 默认空数组；受控请求头
     * @param string $body 默认空串；表单正文
     * @return string 有界响应正文；失败不输出URL、令牌或平台原始错误
     * @throws Throwable 输入/状态无效或底层服务失败时终止本次流程，不建立登录
     */
    private function request(string $method,string $url,array $headers=[],string $body=''): string {
        $host=parse_url($url,PHP_URL_HOST);if(parse_url($url,PHP_URL_SCHEME)!=='https'||!in_array($host,['oauth2.googleapis.com','openidconnect.googleapis.com','github.com','api.github.com','graph.qq.com','api.weixin.qq.com'],true))throw new LogicException('非法平台端点。');
        if($this->transport!==null){$data=($this->transport)($method,$url,$headers,$body);if(!is_string($data)||strlen($data)>65536)throw new RuntimeException('平台响应无效。');return $data;}
        $data='';$handle=curl_init($url);if($handle===false)throw new RuntimeException('无法建立平台连接。');
        curl_setopt_array($handle,[CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>15,CURLOPT_USERAGENT=>'MyStep-Social/1.0',CURLOPT_HTTPHEADER=>$headers,CURLOPT_RETURNTRANSFER=>false,CURLOPT_WRITEFUNCTION=>static function($curl,$chunk)use(&$data){if(strlen($data)+strlen($chunk)>65536)return 0;$data.=$chunk;return strlen($chunk);}]);
        if($method==='POST')curl_setopt_array($handle,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$body]);
        try{$ok=curl_exec($handle);$status=(int)curl_getinfo($handle,CURLINFO_HTTP_CODE);if($ok===false||$status<200||$status>=300)throw new RuntimeException('平台连接失败，请稍后重试。');return $data;}finally{unset($handle);}
    }
    /**
     * 功能说明：解析JSON或QQ历史响应，不执行JSONP代码。
     * @param string $body 有界响应
     * @return array 已解析数据
     * @throws DomainException 平台响应无效
     */
    private function decode(string $body): array {
        $trim=trim($body);if(preg_match('/\Acallback\s*\(\s*(\{.*\})\s*\);?\s*\z/sD',$trim,$m))$trim=$m[1];
        try{$data=json_decode($trim,true,32,JSON_THROW_ON_ERROR|JSON_BIGINT_AS_STRING);}catch(JsonException){$data=null;}
        if(!is_array($data))throw new DomainException('平台返回的数据无效。');return $data;
    }
    /**
     * 功能说明：校验平台身份/令牌的非空受控文本。
     * @param mixed $value 平台字段
     * @param int $max 默认255；最大字节数
     * @return string 合法文本
     * @throws DomainException 字段无效
     */
    private function text($value,int $max=255): string {if(!is_string($value)||$value===''||strlen($value)>$max||preg_match('/[\x00-\x20\x7f]/',$value))throw new DomainException('平台未返回有效身份。');return $value;}
}
