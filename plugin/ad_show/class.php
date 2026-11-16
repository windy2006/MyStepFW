<?php
/** 广告管理、展示和点击统计；原作者Windy2000。 */
require_once __DIR__.'/Support.php';
class plugin_ad_show implements interface_plugin {
    use PAdShowLifecycle;
    protected const S=PAdShowSupport::class;
    public static function validate(array $p): array {
        $s=self::S;$r=[];
        foreach(['idx'=>40,'ad_client'=>100,'ad_url'=>2000,'ad_file'=>2000,'ad_mobile'=>2000,'ad_poster'=>2000,'ad_text'=>500,'ad_desc'=>1000,'ad_cta'=>40,'ad_label'=>60,'comment'=>1000,'add_date'=>10,'exp_date'=>10] as $key=>$max)$r[$key]=$s::text($p[$key]??'',$max);
        if(!preg_match('/\A[a-zA-Z0-9_-]{1,40}\z/D',$r['idx']))throw new InvalidArgumentException('广告位标识仅限字母数字下划线连字符');
        $r['ad_mode']=$s::integer($p['ad_mode']??1,0,6);if(!isset(self::modes()[$r['ad_mode']]))throw new InvalidArgumentException('此广告类型已停用，请使用图片、HTML5页面或视频');$r['ad_level']=$s::integer($p['ad_level']??0,0,255);$r['active']=$s::flag($p['active']??false);
        $r['add_date']=$s::date($r['add_date']);$r['exp_date']=$s::date($r['exp_date']);
        if($r['add_date']!==''&&$r['exp_date']!==''&&$r['exp_date']<$r['add_date'])throw new InvalidArgumentException('结束日期早于开始日期');
        $r['ad_url']=self::resourceURL($r['ad_url']);$r['ad_file']=self::resourceURL($r['ad_file']);
        foreach(['ad_mobile','ad_poster']as $key)$r[$key]=self::resourceURL($r[$key]);
        $r['ad_label']=$r['ad_label']?:'广告';$r['ad_cta']=$r['ad_cta']?:'了解详情';
        $r['video_autoplay']=$s::flag($p['video_autoplay']??false);$r['video_loop']=$s::flag($p['video_loop']??false);
        if(in_array($r['ad_mode'],[0,6],true)&&trim($r['ad_text'])==='')throw new InvalidArgumentException('文字和原生图文广告需要标题');
        if($r['ad_mode']!==0&&$r['ad_file']==='')throw new InvalidArgumentException('需要广告资源地址');
        foreach(['view','click','ip_view','ip_click']as $key)if(isset($p[$key]))$r[$key]=$s::integer($p[$key],0,PHP_INT_MAX);
        return $r;
    }
    /**
     * 功能说明：返回可用素材类型；编号2保留为空，避免旧Flash素材误作其他类型。
     * @return array<int,string> 类型编号及后台显示名称。
     */
    public static function modes(): array {return [0=>'文字',1=>'响应式图片',3=>'HTML5页面',4=>'第三方脚本',5=>'HTML5视频',6=>'原生图文'];}
    public static function resourceURL(string $v): string {
        if($v==='')return '';
        if(str_starts_with($v,'/')&&!str_starts_with($v,'//')&&!preg_match('/[\x00-\x20\x7f\\\\]/',$v)&&!preg_match('#(?:^|/)\.\.(?:/|$)#',$v))return $v;
        return PAdShowSupport::httpURL($v,false);
    }
    /**
     * 功能说明：将记录的类型及运行状态转换为后台列表的可读显示值，不修改存储。
     * @param array $row 当前分页中的记录，保留编号与操作所需字段。
     * @return array 用于后台列表的格式化记录。
     */
    public static function formatRow(array $row): array {
        $row['ad_mode']=self::modes()[$row['ad_mode']]??'已停用类型';
        foreach(['view','ip_view','click','ip_click']as $key)$row[$key]=(int)($row[$key]??0);
        return $row;
    }
    public static function manage(): string {
        return include __DIR__.'/module/page.php';
    }
    public static function rowActions(array $row): string {
        return PAdShowSupport::actions($row['id']).'<a class="btn btn-sm btn-light" href="'.PAdShowSupport::h(PAdShowSupport::url(['action'=>'stats','id'=>$row['id']])).'">统计明细</a>';
    }
    private static function upload(): string {
        $s=self::S;$s::post();$u=$_FILES['image']??[];
        if(($u['error']??-1)!==UPLOAD_ERR_OK||!is_string($u['tmp_name']??null)||!is_uploaded_file($u['tmp_name'])||filesize($u['tmp_name'])>5242880)throw new InvalidArgumentException('上传图片失败或超过5MiB');
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file($u['tmp_name']);$ext=['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp','image/avif'=>'avif'][$mime]??null;
        if(!$ext||!@getimagesize($u['tmp_name']))throw new InvalidArgumentException('仅支持JPEG/PNG/GIF/WebP/AVIF图片');
        $token=bin2hex(random_bytes(20)).'.'.$ext;$path=$s::directory().'/'.$token;
        if(!move_uploaded_file($u['tmp_name'],$path))throw new RuntimeException('保存图片失败');chmod($path,0600);
        $url=myStep::setURL('ad_asset/'.pathinfo($token,PATHINFO_FILENAME));
        return $s::shell($s::error('图片已上传，资源URL：'.$url));
    }
    public static function asset($token=''): void {
        if(!PAdShowSupport::active()||!is_string($token)||!preg_match('/\A[a-f0-9]{40}\z/D',$token)){http_response_code(404);return;}
        $path='';$mime='';foreach(['jpg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif','webp'=>'image/webp','avif'=>'image/avif']as $ext=>$type){$candidate=CONFIG.'plugins/ad_show/'.$token.'.'.$ext;if(is_file($candidate)&&!is_link($candidate)){$path=$candidate;$mime=$type;break;}}
        if($path===''){http_response_code(404);return;}
        while(ob_get_level())ob_end_clean();header('Content-Type: '.$mime);header('X-Content-Type-Options: nosniff');header('Content-Length: '.filesize($path));readfile($path);exit;
    }
    /** 编译期不统计；动态展示及统计在运行期执行。 */
    public static function ad_show(myTemplate &$tpl,array &$attrs=[]): string {return '<?php echo plugin_ad_show::display('.var_export(self::attributes($attrs),true).'); ?>';}
    public static function attributes(array $a): array {
        $out=['idx'=>PAdShowSupport::text($a['idx']??'',40),'limit'=>PAdShowSupport::integer($a['limit']??1,1,100)];
        foreach(['class','css','css_ad']as $k){$out[$k]=PAdShowSupport::text($a[$k]??'',500);if(preg_match('/[<>]/',$out[$k]))throw new InvalidArgumentException('广告样式无效');}
        foreach(['width','height','width_ad','height_ad'] as $k)$out[$k]=isset($a[$k])?PAdShowSupport::integer($a[$k],0,10000):0;return $out;
    }
    public static function display(array $attrs=[]): string {
        if(!PAdShowSupport::active())return '';$a=self::attributes($attrs);$day=date('Y-m-d');$records=array_filter(PAdShowSupport::state()['items'],fn($r)=>$r['active']&&isset(self::modes()[$r['ad_mode']])&&($a['idx']===''||$r['idx']===$a['idx'])&&($r['add_date']===''||$r['add_date']<=$day)&&($r['exp_date']===''||$r['exp_date']>=$day));
        usort($records,fn($a,$b)=>($b['ad_level']<=>$a['ad_level'])?:($a['id']<=>$b['id']));$records=array_slice($records,0,$a['limit']);$body='';
        foreach($records as $r){
            $url=$r['ad_url']!==''?myStep::setURL('ad_link/'.$r['id']):'';
            $vars=['url'=>$url,'file'=>$r['ad_file'],'mobile'=>str_replace(',','%2C',$r['ad_mobile']??''),'poster'=>$r['ad_poster']??'',
                'text'=>$r['ad_text'],'description'=>$r['ad_desc']??'','cta'=>$r['ad_cta']??'了解详情',
                'autoplay'=>!empty($r['video_autoplay'])?' autoplay muted':'','loop'=>!empty($r['video_loop'])?' loop':'',
                'width'=>$a['width_ad']?' width="'.$a['width_ad'].'"':'','height'=>$a['height_ad']?' height="'.$a['height_ad'].'"':''];
            $body.=PAdShowSupport::render('unit',['body'=>PAdShowSupport::render('ad_'.$r['ad_mode'],$vars),'style'=>$a['css_ad'],'label'=>$r['ad_label']??'广告']);
        }
        self::countMany(array_column($records,'id'),'view');
        $style=$a['css'];foreach(['width','height']as $k)if($a[$k])$style.=';'.$k.':'.$a[$k].'px';
        return PAdShowSupport::render('ads',['body'=>$body,'class'=>$a['class'],'style'=>$style,'style_ad'=>$a['css_ad']]);
    }
    /**
     * 功能说明：记录单个广告的展示或点击，沿用批量统计的会话去重规则。
     * @param int $id 广告编号。
     * @param string $mode view（展示）或click（点击）。
     * @return void 无返回值。
     */
    public static function count(int $id,string $mode): void {self::countMany([$id],$mode);}
    /**
     * 功能说明：在一次锁定读改写中统计本页广告，避免每条广告重复读写完整数据文件。
     * @param array $ids 广告编号列表；重复编号只计算一次，已删除记录自动跳过。
     * @param string $mode view（展示）或click（点击），其他值抛出InvalidArgumentException。
     * @return void 无返回值；爬虫和空列表不产生写入。
     */
    private static function countMany(array $ids,string $mode): void {
        if(!in_array($mode,['view','click'],true))throw new InvalidArgumentException('统计类型无效');
        $ids=array_values(array_unique(array_map(static fn($id)=>PAdShowSupport::integer($id,1,PHP_INT_MAX),$ids)));
        if(!$ids||PAdShowSupport::bot($_SERVER['HTTP_USER_AGENT']??''))return;
        $day=date('Y-m-d');$counted=[];
        PAdShowSupport::state(function(&$s)use($ids,$mode,$day,&$counted){
            foreach($ids as $id){if(!isset($s['items'][(string)$id])||!isset(self::modes()[$s['items'][(string)$id]['ad_mode']]))continue;
                $unique=!isset($_SESSION['ad_show_seen'][$day][$mode][$id]);
                $r=&$s['items'][(string)$id];$r[$mode]=($r[$mode]??0)+1;$r['ip_'.$mode]=($r['ip_'.$mode]??0)+(int)$unique;
                if($unique)$s['events'][]=['id'=>$id,'mode'=>$mode,'time'=>date('c'),'ip'=>mb_substr($_SERVER['REMOTE_ADDR']??'',0,64)];
                $counted[]=$id;
            }
            $s['events']=array_slice($s['events']??[],-5000);
        });
        $_SESSION['ad_show_seen']=array_intersect_key($_SESSION['ad_show_seen']??[],[$day=>true]);
        foreach($counted as $id)$_SESSION['ad_show_seen'][$day][$mode][$id]=true;
    }
    public static function link($id=null): void {
        if(!PAdShowSupport::active()){http_response_code(404);return;}
        try{$r=PAdShowSupport::get(PAdShowSupport::integer($id,1,2147483647));}
        catch(InvalidArgumentException|OutOfBoundsException){http_response_code(404);return;}
        $today=date('Y-m-d');
        if(!isset(self::modes()[$r['ad_mode']])||!$r['active']||($r['add_date']!==''&&$r['add_date']>$today)||($r['exp_date']!==''&&$r['exp_date']<$today)||$r['ad_url']===''){http_response_code(404);return;}
        self::count($r['id'],'click');header('Location: '.self::resourceURL($r['ad_url']),true,302);exit;
    }
}
