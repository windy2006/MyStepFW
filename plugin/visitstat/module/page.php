<?php
/** 当前插件类作用域内包含的后台处理；由manage鉴权后调用。 */

        $s=self::S;$view=match($_GET['view']??''){'keyword'=>'keywords','campaign'=>'campaigns',default=>'items'};$viewParam=['items'=>'referer','keywords'=>'keyword','campaigns'=>'campaign'][$view];$state=$s::state();
        if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
            $s::post();$action=$_POST['action']??'';
            if($action==='export')$s::export($state);
            if($action!=='delete')throw new InvalidArgumentException('未知操作');$key=$s::text($_POST['id']??'',64);if(!preg_match('/\A[a-f0-9]{64}\z/D',$key))throw new InvalidArgumentException('记录标识无效');
            $kind=$s::text($_POST['kind']??'items',20);if(!in_array($kind,['items','keywords','campaigns'],true))throw new InvalidArgumentException('统计集合无效');$s::state(function(&$st)use($kind,$key){unset($st[$kind][$key]);});$s::redirect();
        }
        $rows=[];foreach($state[$view]??[]as $key=>$r){if($view==='items'){if($r['month']!==date('Y-m'))$r['count_month']=0;if($r['year']!==date('Y'))$r['count_year']=0;}$rows[$key]=$r;}
        uasort($rows,fn($a,$b)=>$b['count']<=>$a['count']);$count=count($rows);$page=max(1,(int)($_GET['page']??1));$rows=array_slice($rows,($page-1)*50,50,true);
        // 先分页后生成表单，避免为最多20000项逐一生成CSRF及操作HTML。
        foreach($rows as $key=>&$r){$r['_actions']=$s::actions($key,false);$r['_actions']=str_replace('name="action" value="delete"','name="kind" value="'.$view.'"><input type="hidden" name="action" value="delete"',$r['_actions']);}unset($r);
        $cols=$view==='campaigns'?['app'=>'应用','source'=>'活动来源','medium'=>'媒介','campaign'=>'活动名称','id'=>'活动编号','content'=>'内容','term'=>'关键词','count'=>'次数','url'=>'落地路径','last'=>'最近']:($view==='items'?['app'=>'应用','host'=>'来源','count'=>'总计','count_month'=>'本月','count_year'=>'本年','first'=>'首次','last'=>'最近']:['app'=>'应用','keyword'=>'关键词','count'=>'次数','url'=>'访问路径','referer'=>'最近来源','last'=>'最近']);
        $tools=$s::render('stats_tools',['url'=>$s::url(),'keywords'=>$s::url(['view'=>'keyword']),'campaigns'=>$s::url(['view'=>'campaign']),'csrf'=>$s::csrf()]);
        $nav='<p>共 '.$count.' 条；<a href="'.$s::h($s::url(['view'=>$viewParam,'page'=>max(1,$page-1)])).'">上一页</a> · '.$page.' · <a href="'.$s::h($s::url(['view'=>$viewParam,'page'=>min(max(1,(int)ceil($count/50)),$page+1)])).'">下一页</a></p>';
        return $s::shell($tools.$nav.$s::table($cols+['_actions'=>'操作'],$rows),$s::notice());
