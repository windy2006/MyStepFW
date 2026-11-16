<?php
/** 当前插件类作用域内包含的后台处理；由manage鉴权后调用。 */

        $s=static::S;$action=$_POST['action']??($_GET['action']??'');
        if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
            $s::post();$id=$s::integer($_POST['id']??0,0,2147483647);
            switch($action){
                case 'save':$row=static::validate($_POST);$s::save($row,$id,$s::integer($_POST['revision']??0,0,2147483647));break;
                case 'delete':$s::delete((string)$id);break;
                case 'export':$s::export(['plugin'=>'se_detect','version'=>1,'items'=>array_values($s::state()['items'])]);
                case 'import':$n=static::importRows($s::text($_POST['json']??'',1000000));$s::redirect('已导入 '.$n.' 条记录');
                default:throw new InvalidArgumentException('未知操作');
            }$s::redirect();
        }
        if($action==='new'||$action==='edit'){
            $row=$action==='edit'?$s::get($s::integer($_GET['id']??0,1,2147483647)):[];
            return $s::shell($s::editor($title,$fields,$row));
        }
        $rows=array_values($s::state()['items']);usort($rows,fn($a,$b)=>$b['id']<=>$a['id']);
        $page=max(1,(int)($_GET['page']??1));$count=count($rows);$rows=array_slice($rows,($page-1)*30,30);
        foreach($rows as &$r){if(method_exists(static::class,'formatRow'))$r=static::formatRow($r);$r['_actions']=method_exists(static::class,'rowActions')?static::rowActions($r):$s::actions($r['id']);foreach($r as $k=>$v)if(is_bool($v))$r[$k]=$v?'是':'否';}unset($r);
        $tools=$s::render('tools',['new_url'=>$s::url(['action'=>'new']),'url'=>$s::url(),'csrf'=>$s::csrf()]);
        $tools.=$s::render('import',['url'=>$s::url(),'csrf'=>$s::csrf()]);
        $nav='<p>共 '.$count.' 条；<a href="'.$s::h($s::url(['page'=>max(1,$page-1)])).'">上一页</a> · '.$page.' · <a href="'.$s::h($s::url(['page'=>min(max(1,(int)ceil($count/30)),$page+1)])).'">下一页</a></p>';
        return $s::shell($tools.$nav.$s::table($columns+['_actions'=>'操作'],$rows),$s::notice());
