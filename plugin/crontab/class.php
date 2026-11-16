<?php
/** 有界任务调度：单调度锁、先记录下次时间、失败留痕。原作者Windy2000。 */
require_once __DIR__.'/Support.php';
require_once __DIR__.'/Schedule.php';
class plugin_crontab implements interface_plugin {
    use PCrontabLifecycle;
    protected const S=PCrontabSupport::class;
    public static function validateConfig(myConfig $config): void {
        $c=$config->get();
        PCrontabSupport::integer($c['interval']??null,10,86400);
        PCrontabSupport::integer($c['batch']??null,1,20);
    }
    public static function seed(array &$s): void {if(!isset($s['scheduler_initialized'])){$s['enabled']=false;$s['scheduler_initialized']=true;}}
    public static function validate(array $p): array {
        $s=self::S;$r=[];foreach(['name'=>200,'describe'=>1000,'schedule'=>200,'url'=>2000,'code'=>100000,'expire'=>10]as $k=>$max)$r[$k]=$s::text($p[$k]??'',$max);
        if(trim($r['name'])==='')throw new InvalidArgumentException('需要任务名称');
        $r['mode']=$s::integer($p['mode']??0,0,2);$r['active']=$s::flag($p['active']??false);
        $r['schedule']=$r['schedule']?:($r['mode']===2?'*/10 * * * *':'0,0,0,10,0');$r['next_time']=self::nextTime($r['mode'],$r['schedule'],time());$r['next_date']=date('c',$r['next_time']);$r['last_status']='pending';
        $r['expire']=$s::date($r['expire']);$r['url']=$s::httpURL($r['url']);$r['code']=$s::code($r['code']);
        if($r['url']===''&&trim($r['code'])==='')throw new InvalidArgumentException('至少填写URL或PHP代码');return $r;
    }
    /**
     * 功能说明：计算下次时间，保留间隔与固定计划，新增标准五字段Cron。
     * @param int $mode 0间隔、1固定日期、2标准Cron。
     * @param string $schedule 对应模式的执行计划；Cron为分、时、日、月、星期。
     * @param int $now 当前Unix时间戳。
     * @return int 下次执行时间。
     * @throws InvalidArgumentException 计划格式或日期无效。
     */
    public static function nextTime(int $mode,string $schedule,int $now): int {
        if($mode===2)return PCrontabExpression::next($schedule,$now);
        if(!preg_match('/\A\d+,\d+,\d+,\d+,\d+\z/D',$schedule))throw new InvalidArgumentException('计划需要五个逗号分隔的非负整数');
        $v=array_map('intval',explode(',',$schedule));if(count(array_filter($v,fn($n)=>$n>10000)))throw new InvalidArgumentException('计划数值过大');
        if($mode===0){$seconds=$v[4]?$v[4]*86400:($v[0]*30*86400+$v[1]*86400+$v[2]*3600+$v[3]*60);return $now+max(60,$seconds?:600);}
        if($mode!==1||$v[0]>12||$v[1]>31||$v[2]>23||$v[3]>59||$v[4]>7)throw new InvalidArgumentException('固定计划超出月/日/时/分/星期范围');
        $date=(new DateTimeImmutable('@'.$now))->setTimezone(new DateTimeZone(date_default_timezone_get()))->setTime(0,0);
        for($i=0;$i<366*8;$i++){$candidate=$date->modify('+'.$i.' days')->setTime($v[2],$v[3]);
            // 夏令时缺失的本地时刻会被PHP顺延，跳过它以保持固定钟点语义。
            if((int)$candidate->format('G')!==$v[2]||(int)$candidate->format('i')!==$v[3]||$candidate->getTimestamp()<=$now)continue;
            if($v[4]){if((int)$candidate->format('w')+1!==$v[4])continue;}
            elseif(($v[0]&&(int)$candidate->format('n')!==$v[0])||($v[1]&&(int)$candidate->format('j')!==$v[1]))continue;
            return $candidate->getTimestamp();
        }throw new InvalidArgumentException('八年内没有匹配的执行日期');
    }
    /**
     * 功能说明：将记录的类型及运行状态转换为后台列表的可读显示值，不修改存储。
     * @param array $row 当前分页中的记录，保留编号与操作所需字段。
     * @return array 用于后台列表的格式化记录。
     */
    public static function formatRow(array $row): array {
        $row['mode']=[0=>'间隔',1=>'固定时间',2=>'标准Cron'][$row['mode']]??'未知';$row['exe_count']=(int)($row['exe_count']??0);
        $row['last_status']=['ok'=>'成功','failed'=>'失败','running'=>'执行中','pending'=>'等待执行'][$row['last_status']??'']??'尚未执行';
        if(!empty($row['next_time']))$row['next_date']=date('Y-m-d H:i:s',$row['next_time']);
        return $row;
    }
    public static function manage(): string {
        return include __DIR__.'/module/page.php';
    }
    private static function tools(): string {$state=PCrontabSupport::state();return PCrontabSupport::render('scheduler',['status'=>$state['enabled']?'运行':'停止','url'=>PCrontabSupport::url(),'logs'=>PCrontabSupport::url(['view'=>'logs']),'csrf'=>PCrontabSupport::csrf()]);}
    public static function page_end(): void {
        if(!PCrontabSupport::active()||($_SERVER['REQUEST_METHOD']??'GET')!=='GET')return;
        $interval=max(10,min(86400,(int)(PCrontabSupport::config()['interval']??120)));$state=PCrontabSupport::state();
        if(!$state['enabled']||($state['last_tick']??0)>time()-$interval)return;
        self::tick();
    }
    /** 整批任务只取非阻塞调度锁；请求并发时直接跳过，CLI也使用同一把锁。 */
    public static function tick(int $manual=0): array {
        if(!PCrontabSupport::active())throw new DomainException('插件未启用');
        $dir=PCrontabSupport::directory();$lock=$dir.'/runner.lock';if(is_link($lock))throw new RuntimeException('拒绝符号链接执行锁');$fp=fopen($lock,'c+b');if(!$fp)throw new RuntimeException('无法建立任务锁');
        if(!flock($fp,LOCK_EX|LOCK_NB)){fclose($fp);return ['busy'=>true,'runs'=>0];}
        $ran=0;
        try{
            $state=PCrontabSupport::state();if(!$manual&&!$state['enabled'])return ['busy'=>false,'runs'=>0];
            $now=time();if($manual){$task=PCrontabSupport::get($manual);if(!$task['active']||($task['expire']!==''&&$task['expire']<date('Y-m-d',$now)))throw new DomainException('手动执行的任务未启用或已到期');}$tasks=$state['items'];uasort($tasks,fn($a,$b)=>($a['next_time']??0)<=>($b['next_time']??0));
            PCrontabSupport::state(function(&$s)use($now){$s['last_tick']=$now;});
            foreach($tasks as $id=>$task){
                $now=time();
                if($manual&&(int)$id!==$manual)continue;
                if(!$task['active']||($task['expire']!==''&&$task['expire']<date('Y-m-d',$now))||(!$manual&&($task['next_time']??0)>$now))continue;
                // 锁内重新核对revision，避免取出列表后管理员修改仍执行旧代码。
                $claimed=false;$stopped=false;
                PCrontabSupport::state(function(&$s)use($id,$task,$now,$manual,&$claimed,&$stopped){
                    // 每次领取任务重查停止开关；当前任务允许完成，停止后不再领取下一项。
                    if(!$manual&&!$s['enabled']){$stopped=true;return;}
                    $current=$s['items'][$id]??null;if(!$current||$current['revision']!==$task['revision']||!$current['active'])return;
                    $next=self::nextTime($task['mode'],$task['schedule'],$now);$s['items'][$id]['next_time']=$next;$s['items'][$id]['next_date']=date('c',$next);$s['items'][$id]['last_status']='running';$s['items'][$id]['exe_date']=date('c',$now);$s['items'][$id]['exe_count']=($current['exe_count']??0)+1;$claimed=true;
                });
                if($stopped)break;
                if(!$claimed)continue;
                $status='ok';$message='';$output='';$level=ob_get_level();
                // 分块吸收输出，只保留足够容纳4000个UTF-8字符的字节；避免整段输出驻留内存。
                ob_start(static function($chunk)use(&$output){$remaining=16000-strlen($output);if($remaining>0)$output.=substr($chunk,0,$remaining);return '';},4096);
                try{
                    if(trim($task['code'])!=='')PCrontabSupport::execute($task['code']);
                    if($task['url']!=='')self::request($task['url']);
                }catch(Throwable $e){$status='failed';$message=get_class($e).': '.mb_substr($e->getMessage(),0,1000);}
                finally{while(ob_get_level()>$level)ob_end_flush();}
                if($status==='ok')$message=mb_substr($output,0,4000);
                PCrontabSupport::state(function(&$s)use($id,$now,$status,$message,$task){
                    // 执行期间可能已编辑/删除任务，只为同一修订写回最近状态，历史日志仍保留。
                    if(($s['items'][$id]['revision']??null)===$task['revision'])$s['items'][$id]['last_status']=$status;
                    $s['logs'][]=['time'=>$now,'id'=>$id,'revision'=>$task['revision'],'status'=>$status,'message'=>$message];$s['logs']=array_slice($s['logs'],-1000);});
                $ran++;if($manual||$ran>=max(1,min(20,(int)(PCrontabSupport::config()['batch']??5))))break;
            }return ['busy'=>false,'runs'=>$ran];
        }finally{flock($fp,LOCK_UN);fclose($fp);}
    }
    private static function request(string $url): void {
        $url=PCrontabSupport::httpURL($url,false);$ctx=stream_context_create(['http'=>['method'=>'GET','timeout'=>10,'follow_location'=>0,'ignore_errors'=>true,'header'=>"User-Agent: MyStep-Crontab/2.0\r\n"],'ssl'=>['verify_peer'=>true,'verify_peer_name'=>true]]);
        $fp=@fopen($url,'rb',false,$ctx);if(!$fp)throw new RuntimeException('任务URL连接失败');
        try{
            $meta=stream_get_meta_data($fp);$status=0;
            // 临时1xx响应不代表最终结果，取最后一个HTTP状态行，仍不跟随重定向。
            foreach($meta['wrapper_data']??[]as $line)if(preg_match('~^HTTP/\S+\s+([1-5][0-9]{2})(?:\s|$)~i',$line,$m))$status=(int)$m[1];
            if($status<200||$status>=300)throw new RuntimeException('任务URL未返回2xx');
            if(fread($fp,4096)===false||(stream_get_meta_data($fp)['timed_out']??false))throw new RuntimeException('任务URL读取失败或超时');
        }finally{fclose($fp);}
    }
}
