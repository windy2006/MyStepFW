<?php
/** UA爬虫识别、IP记录、每日统计与阻挡；识别结果不替代身份验证。 */
require_once __DIR__.'/Support.php';
class plugin_se_detect implements interface_plugin {
    use PSeDetectLifecycle;
    protected const S=PSeDetectSupport::class;
    public static function validateConfig(myConfig $config): void {
        $c=$config->get();
        PSeDetectSupport::integer($c['retention_days']??null,1,3650);
    }
    /**
     * 功能说明：列出搜索及AI访问用途；分类用于统计与单条规则策略。
     * @return array<string,string> 分类标识及中文名称。
     */
    public static function categories(): array {return ['search'=>'搜索引擎','ai_train'=>'AI训练','ai_search'=>'AI搜索','ai_user'=>'用户触发','other'=>'其他抓取'];}
    /**
     * 功能说明：补充缺少的内置规则，不覆盖已有规则的启停、阻挡或自定义内容。
     * @param array $s 完整存储状态，引用更新。
     * @param bool $refresh 是否明确补充已删除规则；普通初始化只在清单版本变化时执行。
     * @return void 无返回值；新增规则默认允许访问。
     */
    public static function seed(array &$s,bool $refresh=false): void {
        if(!$refresh&&($s['catalog_version']??0)>=2)return;
        $existing=array_column($s['items'],'idx');
        foreach(include __DIR__.'/catalog.php' as $idx=>[$name,$agent,$category]){
            if(in_array($idx,$existing,true))continue;
            $id=$s['next_id']++;$s['items'][(string)$id]=['id'=>$id,'revision'=>1,'idx'=>$idx,'name'=>$name,'agent'=>$agent,'category'=>$category,'ban'=>false,'active'=>true];
        }
        $s['seeded']=true;$s['catalog_version']=2;
    }
    /**
     * 功能说明：为管理列表转换用途名称，保留记录标识与状态。
     * @param array $row 单条识别规则。
     * @return array 可读的后台记录。
     */
    public static function formatRow(array $row): array {$row['order']=(int)($row['order']??0);$row['category']=self::categories()[$row['category']??'search']??'其他抓取';return $row;}
    public static function validate(array $p): array {
        $s=self::S;$r=[];foreach(['idx'=>40,'name'=>100,'agent'=>200]as $k=>$max)$r[$k]=$s::text($p[$k]??'',$max);
        if(!preg_match('/\A[a-z][a-z0-9_]{0,39}\z/D',$r['idx'])||$r['agent']==='')throw new InvalidArgumentException('引擎标识或UA关键字无效');
        $r['order']=$s::integer($p['order']??0,0,9999);$r['category']=$s::text($p['category']??'search',20);if(!isset(self::categories()[$r['category']]))throw new InvalidArgumentException('抓取用途无效');
        $r['ban']=$s::flag($p['ban']??false);$r['active']=$s::flag($p['active']??false);return $r;
    }
    /**
     * 功能说明：按优先级从大到小、编号从小到大匹配启用规则，未命中的抓取归入other。
     * @param string $ua 请求User-Agent。
     * @param array $rules 已验证的识别规则；缺少order的既有规则按0处理。
     * @return array|null 首条命中规则；普通浏览器返回null，不核验来源IP身份。
     */
    public static function classify(string $ua,array $rules): ?array {
        usort($rules,static fn($a,$b)=>((int)($b['order']??0)<=>(int)($a['order']??0))?:(($a['id']??0)<=>($b['id']??0)));
        foreach($rules as $rule)if($rule['active']&&stripos($ua,$rule['agent'])!==false)return $rule;
        return PSeDetectSupport::bot($ua)?['idx'=>'other','name'=>'其他','category'=>'other','ban'=>false]:null;
    }
    public static function record(string $ua,string $ip,string $path,int $now): ?array {
        $rules=PSeDetectSupport::state()['items'];$engine=self::classify(mb_substr($ua,0,1000),$rules);if(!$engine)return null;
        if(!filter_var($ip,FILTER_VALIDATE_IP))$ip='unknown';$path=mb_substr($path,0,1000);$day=date('Y-m-d',$now);
        PSeDetectSupport::state(function(&$s)use($ua,$ip,$path,$now,$engine,$day){
            $key=$engine['idx'].':'.$ip;$old=$s['visits'][$key]??[];
            $s['visits'][$key]=['engine'=>$engine['idx'],'category'=>$engine['category']??'search','ip'=>$ip,'count'=>($old['count']??0)+1,'first'=>$old['first']??date('c',$now),'last'=>date('c',$now),'last_time'=>$now,'path'=>$path,'ua'=>mb_substr($ua,0,1000)];
            $s['daily'][$day][$engine['idx']]=($s['daily'][$day][$engine['idx']]??0)+1;
            $days=max(1,min(3650,(int)(PSeDetectSupport::config()['retention_days']??365)));
            $cut=$now-$days*86400;$s['visits']=array_filter($s['visits'],fn($r)=>$r['last_time']>=$cut);
            foreach(array_keys($s['daily']) as $date)if($date<date('Y-m-d',$cut))unset($s['daily'][$date]);
            if(count($s['visits'])>20000){uasort($s['visits'],fn($a,$b)=>$b['last_time']<=>$a['last_time']);$s['visits']=array_slice($s['visits'],0,20000,true);}
            PSeDetectSupport::bound($s,['visits','daily']);
        });return $engine;
    }
    public static function page_start(): void {
        if(!PSeDetectSupport::active()||($_SERVER['REQUEST_METHOD']??'GET')!=='GET'||myReq::session('sysop')==='y')return;
        $engine=self::record($_SERVER['HTTP_USER_AGENT']??'',$_SERVER['REMOTE_ADDR']??'',PSeDetectSupport::path(),time());
        if($engine){$GLOBALS['se_bot']=$engine['idx'];if($engine['ban']){http_response_code(404);exit;}}
    }
    public static function manage(): string {
        return include __DIR__.'/module/page.php';
    }
    private static function nav(): string {return PSeDetectSupport::render('stats_tools',['rules'=>PSeDetectSupport::url(),'visits'=>PSeDetectSupport::url(['view'=>'visits']),'daily'=>PSeDetectSupport::url(['view'=>'daily']),'url'=>PSeDetectSupport::url(),'csrf'=>PSeDetectSupport::csrf()]);}
    private static function pages(string $view,int $page,int $count): string {$s=self::S;return '<a href="'.$s::h($s::url(['view'=>$view,'page'=>max(1,$page-1)])).'">上一页</a> · '.$page.' · <a href="'.$s::h($s::url(['view'=>$view,'page'=>min(max(1,(int)ceil($count/50)),$page+1)])).'">下一页</a>';}
}
