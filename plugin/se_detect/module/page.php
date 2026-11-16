<?php
/** 当前插件类作用域内包含的后台处理；由manage鉴权后调用。 */

        $s=self::S;$action=$_POST['action']??'';
        if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'&&$action==='export_stats'){$s::post();$s::export($s::state());}
        if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'&&$action==='refresh_catalog'){$s::post();$s::state(static function(&$st){self::seed($st,true);});$s::redirect('内置规则已补充，已有设置保留');}
        $view=$_GET['view']??'rules';
        if($view==='visits'||$view==='daily'){
            $state=$s::state();$rows=[];
            if($view==='visits'){$rows=array_values($state['visits']??[]);usort($rows,fn($a,$b)=>$b['last_time']<=>$a['last_time']);$cols=['engine'=>'引擎','ip'=>'IP','count'=>'次数','first'=>'首次','last'=>'最近','path'=>'路径','ua'=>'UA'];}
            else{foreach($state['daily']??[]as $day=>$counts)foreach($counts as $idx=>$count)$rows[]=['date'=>$day,'engine'=>$idx,'count'=>$count];usort($rows,fn($a,$b)=>strcmp($b['date'],$a['date']));$cols=['date'=>'日期','engine'=>'引擎','count'=>'抓取次数'];}
            $page=max(1,(int)($_GET['page']??1));$n=count($rows);$rows=array_slice($rows,($page-1)*50,50);
            return $s::shell(self::nav().'<p>共 '.$n.' 条</p>'.$s::table($cols,$rows).self::pages($view,$page,$n));
        }
        return self::nav().self::crud('引擎识别规则',['idx'=>['label'=>'引擎标识','required'=>true],'name'=>['label'=>'名称'],'agent'=>['label'=>'User-Agent关键字','required'=>true],'order'=>['label'=>'匹配优先级','type'=>'number','default'=>0,'help'=>'大值先匹配，相同值按编号；具体规则可设更高优先级。'],'category'=>['label'=>'抓取用途','type'=>'select','options'=>self::categories()],'ban'=>['label'=>'阻挡该引擎（返回404）','type'=>'checkbox'],'active'=>['label'=>'启用识别','type'=>'checkbox']],['id'=>'ID','idx'=>'引擎','name'=>'名称','agent'=>'UA关键字','category'=>'用途','order'=>'优先级','ban'=>'阻挡','active'=>'启用']);
