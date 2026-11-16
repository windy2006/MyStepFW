<?php
/** 当前插件类作用域内包含的后台处理；由manage鉴权后调用。 */

        $s=self::S;$action=$_POST['action']??'';
        if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'&&in_array($action,['start','stop','tick','run'],true)){
            $s::post();
            if($action==='start'||$action==='stop'){$s::state(function(&$state)use($action){$state['enabled']=$action==='start';});$s::redirect($action==='start'?'调度已启动':'调度已停止；已开始的任务正常收尾');}
            $result=self::tick($action==='run'?$s::integer($_POST['id']??0,1,2147483647):0);
            $s::redirect($result['busy']?'已有任务正在执行，本次未重复执行':'本次执行 '.$result['runs'].' 个任务；具体结果请查看日志');
        }
        if(($_GET['view']??'')==='logs'){
            $logs=array_reverse($s::state()['logs']);foreach($logs as &$r)$r['time']=date('c',$r['time']);unset($r);
            return $s::shell(self::tools().$s::table(['time'=>'时间','id'=>'任务','status'=>'结果','message'=>'详情'],array_slice($logs,0,200)));
        }
        $fields=['name'=>['label'=>'任务名称','required'=>true],'describe'=>['label'=>'说明'],'mode'=>['label'=>'计划类型','type'=>'select','default'=>2,'options'=>[2=>'标准Cron',0=>'间隔',1=>'固定时间']],
            'schedule'=>['label'=>'执行计划','default'=>'*/10 * * * *','help'=>'Cron：分 时 日 月 星期；例如*/10 * * * *、0 9 * * MON-FRI、@daily。间隔/固定使用原五段逗号格式。分钟级任务需每分钟触发CLI，或将检查间隔设为不超过60秒。'],
            'expire'=>['label'=>'截止日期（含当天）','type'=>'date'],'url'=>['label'=>'执行URL（GET，不携带管理员凭据）'],'code'=>['label'=>'受信PHP代码（不含PHP标签）','type'=>'code','help'=>'拥有服务器执行权限；与URL同时填写时先执行代码。'],'active'=>['label'=>'启用任务','type'=>'checkbox']];
        $html=self::crud('计划任务',$fields,['id'=>'ID','name'=>'任务','mode'=>'类型','schedule'=>'计划','active'=>'启用','exe_count'=>'执行次数','last_status'=>'最近结果','next_date'=>'下次时间']);
        if(!isset($_GET['action'])){$rows=[];foreach($s::state()['items']as $r)$rows[]=['name'=>$r['name'],'_actions'=>$s::actions($r['id'],false,['run'=>'立即执行'])];$html.=self::tools().$s::table(['name'=>'手动执行','_actions'=>'操作'],$rows);}return $html.$s::render('editor_script',['src'=>ROOT_WEB.'plugin/crontab/asset/admin.js']);
