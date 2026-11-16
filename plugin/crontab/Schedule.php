<?php
/**
 * Cron五字段计划计算器。
 * 功能说明：支持列表、范围、步长、英文月份/星期及常用宏，不启动进程或运行任务。
 * 方法目录：next、fields、field。
 * 使用说明：调用next(表达式, time())返回严格晚于当前时刻的时间戳。
 */
final class PCrontabExpression {
    /**
     * 功能说明：按当前框架时区寻找下一执行分钟，处理夏令时缺失及重复钟点。
     * @param string $expression 分、时、日、月、星期，或@hourly/@daily/@weekly/@monthly/@yearly。
     * @param int $now 当前Unix时间戳；不补跑历史分钟。
     * @return int 严格晚于now的下次执行时间。
     * @throws InvalidArgumentException 表达式无效，或八年内没有可执行时刻。
     */
    public static function next(string $expression,int $now): int {
        [$minutes,$hours,$days,$months,$weekdays]=self::fields($expression);
        $zone=new DateTimeZone(date_default_timezone_get());$date=(new DateTimeImmutable('@'.$now))->setTimezone($zone)->setTime(0,0);
        for($i=0;$i<366*8;$i++,$date=$date->modify('+1 day')){
            if(!isset($months['values'][(int)$date->format('n')]))continue;
            $dom=isset($days['values'][(int)$date->format('j')]);$dow=isset($weekdays['values'][(int)$date->format('w')]);
            if(($days['star']||$weekdays['star'])?!($dom&&$dow):!($dom||$dow))continue;
            $ymd=$date->format('Y-m-d');$wall=(new DateTimeImmutable($ymd.' 00:00:00',new DateTimeZone('UTC')))->getTimestamp();
            $transitions=$zone->getTransitions($date->getTimestamp()-86400,$date->modify('+1 day')->getTimestamp()+86400);
            $offsets=[$zone->getOffset($date)];foreach($transitions?:[]as $change)$offsets[]=$change['offset'];$offsets=array_unique($offsets);$best=null;
            foreach($hours['values']as $hour=>$_)foreach($minutes['values']as $minute=>$_){
                $local=sprintf('%s %02d:%02d',$ymd,$hour,$minute);
                foreach($offsets as $offset){$candidate=$wall+$hour*3600+$minute*60-$offset;
                    if($candidate<=$now||($best!==null&&$candidate>=$best))continue;
                    if((new DateTimeImmutable('@'.$candidate))->setTimezone($zone)->format('Y-m-d H:i')===$local)$best=$candidate;
                }
            }
            if($best!==null)return $best;
        }
        throw new InvalidArgumentException('Cron计划八年内没有匹配时刻');
    }
    /**
     * 功能说明：解析并缓存本次请求的表达式字段，使用固定上限避免常驻进程缓存持续增长。
     * @param string $expression 数字/英文名称五字段或受支持宏。
     * @return array 五个已解析的字段，包含values集合和star星号标记。
     * @throws InvalidArgumentException 字段数量或格式无效。
     */
    private static function fields(string $expression): array {
        static $cache=[];$expression=strtolower(trim($expression));
        $expression=['@yearly'=>'0 0 1 1 *','@annually'=>'0 0 1 1 *','@monthly'=>'0 0 1 * *','@weekly'=>'0 0 * * 0','@daily'=>'0 0 * * *','@midnight'=>'0 0 * * *','@hourly'=>'0 * * * *'][$expression]??$expression;
        if(isset($cache[$expression]))return $cache[$expression];
        if(strlen($expression)>200)throw new InvalidArgumentException('Cron表达式过长');
        $parts=preg_split('/\s+/',$expression);if(count($parts)!==5)throw new InvalidArgumentException('Cron需要五字段：分 时 日 月 星期');
        $months=array_flip(['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec']);foreach($months as &$v)$v++;unset($v);
        $weekdays=array_flip(['sun','mon','tue','wed','thu','fri','sat']);$out=[];
        foreach([[0,59,[]],[0,23,[]],[1,31,[]],[1,12,$months],[0,7,$weekdays]]as $i=>[$min,$max,$names])$out[]=self::field($parts[$i],$min,$max,$names,$i===4);
        if(count($cache)>=32)array_shift($cache);return $cache[$expression]=$out;
    }
    /**
     * 功能说明：解析单字段的星号、数字、列表、范围、步长及允许的英文名称。
     * @param string $field 单字段文本，例如星号、1,15、mon-fri、星号/10。
     * @param int $min 最小值。
     * @param int $max 最大值。
     * @param array $names 英文名称到数值的映射。
     * @param bool $weekday 是否将星期7归一为星期0（周日）。
     * @return array values为数值集合，star表示字段以星号开头。
     * @throws InvalidArgumentException 数值越界、范围倒置或步长无效。
     */
    private static function field(string $field,int $min,int $max,array $names,bool $weekday): array {
        $field=preg_replace_callback('/[a-z]+/',static function($m)use($names){if(!array_key_exists($m[0],$names))throw new InvalidArgumentException('Cron包含不支持的名称');return (string)$names[$m[0]];},$field);
        $values=[];
        foreach(explode(',',$field)as $item){
            if(!preg_match('/\A(\*|[0-9]+(?:-[0-9]+)?)(?:\/([0-9]+))?\z/D',$item,$m))throw new InvalidArgumentException('Cron字段格式无效');
            $step=isset($m[2])?(int)$m[2]:1;if($step<1||$step>10000)throw new InvalidArgumentException('Cron步长应为1至10000');
            if($m[1]==='*'){[$start,$end]=[$min,$max];}
            elseif(str_contains($m[1],'-')){[$start,$end]=array_map('intval',explode('-',$m[1]));}
            else{$start=(int)$m[1];$end=isset($m[2])?$max:$start;}
            if($start<$min||$end>$max||$start>$end)throw new InvalidArgumentException('Cron字段超出范围');
            for($n=$start;$n<=$end;$n+=$step)$values[$weekday&&$n===7?0:$n]=true;
        }
        ksort($values);return ['values'=>$values,'star'=>str_starts_with($field,'*')];
    }
}
