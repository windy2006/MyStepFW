<?php
/** 旧visit_analysis：来源及关键词统计，按全局应用标识隔离。 */
require_once __DIR__.'/Support.php';
class plugin_visitstat implements interface_plugin {
    use PVisitstatLifecycle;
    protected const S=PVisitstatSupport::class;
    /** 精确比较主机；拒绝协议、凭据及数组型查询参数，避免同域子串误判。 */
    public static function source(string $referer,string $host): ?array {
        $host=strtolower((string)parse_url('http://'.$host,PHP_URL_HOST));
        if($referer==='')return ['host'=>'None','keyword'=>'','referer'=>''];
        if(strlen($referer)>8192)return null;
        $url=parse_url($referer);if(!$url||!in_array(strtolower($url['scheme']??''),['http','https'],true)||empty($url['host'])||isset($url['user'])||isset($url['pass']))return null;
        $remote=strtolower(rtrim($url['host'],'.'));if($remote===rtrim($host,'.'))return null;
        if(!preg_match('/\A[a-z0-9.\-\[\]:]+\z/Di',$remote))return null;
        parse_str($url['query']??'',$q);$words=[];
        foreach(['k','q','wd','w','query','keyword']as $key)if(isset($q[$key])&&is_string($q[$key])&&$q[$key]!==''&&($key!=='w'||!is_numeric($q[$key])))$words[]=$q[$key];
        $keyword=implode(' ',$words);if(!mb_check_encoding($keyword,'UTF-8'))$keyword=mb_convert_encoding($keyword,'UTF-8','GB18030');
        // 保留最近来源地址，长度受限，不进行任何远程抓取。
        return ['host'=>$remote,'keyword'=>mb_substr($keyword,0,190),'referer'=>mb_substr(explode('#',$referer,2)[0],0,2000)];
    }
    /**
     * 功能说明：提取当前落地页的标准UTM参数，只接收UTF-8文本，不采集任意查询参数。
     * @param array $query 当前GET查询参数。
     * @return array 已归一字段；没有有效utm_source时返回空数组。
     */
    public static function campaign(array $query): array {
        $out=[];
        foreach(['source','medium','campaign','term','content','id']as $key){$value=$query['utm_'.$key]??'';$out[$key]=is_string($value)&&mb_check_encoding($value,'UTF-8')&&!preg_match('/[\x00-\x1f\x7f]/',$value)?mb_substr(trim($value),0,200):'';}
        return $out['source']===''?[]:$out;
    }
    /**
     * 功能说明：记录外部/直接来访、搜索关键词和可选UTM活动，按应用隔离。
     * @param string $referer 浏览器Referer，可为空；本站内部访问不累计落地来源。
     * @param string $host 当前本站主机名。
     * @param string $ua User-Agent，用于排除自动抓取。
     * @param string $url 当前路由路径；活动记录中不保留查询串。
     * @param int $now 当前Unix时间戳。
     * @param string $app 当前应用标识。
     * @param array $campaign 当前GET查询参数，可包含utm_source等标准字段。
     * @return void 无返回值；未提供Referer或关键词时不推测其内容。
     */
    public static function record(string $referer,string $host,string $ua,string $url,int $now,string $app='',array $campaign=[]): void {
        if(PVisitstatSupport::bot($ua))return;$source=self::source($referer,$host);if(!$source)return;
        $campaign=self::campaign($campaign);$month=date('Y-m',$now);$year=date('Y',$now);$name=$source['host'];$key=hash('sha256',$app.'|'.$name);
        PVisitstatSupport::state(function(&$s)use($source,$name,$key,$url,$now,$month,$year,$app,$campaign){
            $r=$s['items'][$key]??['host'=>$name,'app'=>$app,'count'=>0,'count_month'=>0,'count_year'=>0,'first'=>date('c',$now),'month'=>$month,'year'=>$year];
            if($r['month']!==$month)$r['count_month']=0;if($r['year']!==$year)$r['count_year']=0;
            $r['month']=$month;$r['year']=$year;$r['count']++;$r['count_month']++;$r['count_year']++;$r['last']=date('c',$now);$r['last_time']=$now;$s['items'][$key]=$r;
            if($source['keyword']!==''){
                $k=hash('sha256',$app.'|'.$source['keyword']);$r=$s['keywords'][$k]??['keyword'=>$source['keyword'],'app'=>$app,'count'=>0,'first'=>date('c',$now)];
                $r['count']++;$r['last']=date('c',$now);$r['last_time']=$now;$r['url']=mb_substr($url,0,2000);$r['referer']=$source['referer'];$s['keywords'][$k]=$r;
            }
            if($campaign){
                $campaignKey=hash('sha256',json_encode([$app,$campaign],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
                $r=$s['campaigns'][$campaignKey]??['app'=>$app]+$campaign+['count'=>0,'first'=>date('c',$now)];
                $r['count']++;$r['last']=date('c',$now);$r['last_time']=$now;$r['url']=mb_substr(explode('?',$url,2)[0],0,2000);$s['campaigns'][$campaignKey]=$r;
            }
            foreach(['items','keywords','campaigns']as $kind)if(count($s[$kind]??[])>20000){uasort($s[$kind],fn($a,$b)=>$b['last_time']<=>$a['last_time']);$s[$kind]=array_slice($s[$kind],0,20000,true);}
            PVisitstatSupport::bound($s,['items','keywords','campaigns']);
        });
    }
    public static function referer_analysis(): void {
        if(!PVisitstatSupport::active()||($_SERVER['REQUEST_METHOD']??'GET')!=='GET'||myReq::session('sysop')==='y')return;
        self::record($_SERVER['HTTP_REFERER']??'',$_SERVER['HTTP_HOST']??'',$_SERVER['HTTP_USER_AGENT']??'',PVisitstatSupport::path(),time(),(string)($GLOBALS['info_app']['app']??''),$_GET);
    }
    public static function manage(): string {
        return include __DIR__.'/module/page.php';
    }
}
