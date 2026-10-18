<?php
// Isolated SQLSRV contract double. No sockets, native SQLSRV calls or remote credentials.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
if(extension_loaded('sqlsrv')){echo'SKIP isolated fixture requires no native sqlsrv extension'.PHP_EOL;exit;}
$lib=rtrim(getenv('MYSTEP_STAGE')?:getenv('MYSTEP_SOURCE')?:dirname(__DIR__, 2),'/').'/lib/cache/';
define('SQLSRV_FETCH_ASSOC',2);define('SQLSRV_PARAM_IN',1);
$GLOBALS['rows']=[];$GLOBALS['calls']=[];$GLOBALS['made']=0;$GLOBALS['freed']=0;$GLOBALS['closed']=0;$GLOBALS['exists']=false;$GLOBALS['failure']='';
function SQLSRV_SQLTYPE_VARCHAR($n){return 'varchar('.$n.')';}
function sqlsrv_connect($h,$o){return(object)['open'=>true];}
function sqlsrv_close($c){$c->open=false;$GLOBALS['closed']++;return true;}
function sqlsrv_query($c,$sql,$params,$opts){
 $GLOBALS['calls'][]=[$sql,$params];if(!$c->open||$GLOBALS['failure']==='query')return false;
 $values=array_map(function($p){return is_array($p)?$p[0]:$p;},$params);$rows=[];$next=null;
 if(strpos($sql,'SELECT c.name')===0){if($GLOBALS['exists'])$rows=[['name'=>'key','kind'=>'char','max_length'=>32],['name'=>'expiration','kind'=>'bigint','max_length'=>8],['name'=>'value','kind'=>'varchar','max_length'=>-1]];}
 elseif(strpos($sql,'IF OBJECT_ID')===0){$GLOBALS['exists']=true;}
 elseif(strpos($sql,'SET NOCOUNT')===0){if($GLOBALS['failure']==='batch')$next=false;else$GLOBALS['rows'][$values[2]]=['value'=>$values[1],'expiration'=>(int)$values[0]];}
 elseif(strpos($sql,'SELECT [value]')===0){$row=$GLOBALS['rows'][$values[0]]??null;if($row&&$row['expiration']>$values[1]&&strlen($row['value'])<=$values[2])$rows=[$row];}
 elseif(strpos($sql,'DELETE')===0){if($GLOBALS['failure']==='batch')$next=false;elseif(!$values)$GLOBALS['rows']=[];elseif(strpos($sql,'[key]=?')!==false)unset($GLOBALS['rows'][$values[0]]);else foreach($GLOBALS['rows']as$k=>$r)if($r['expiration']<=$values[0])unset($GLOBALS['rows'][$k]);}
 else throw new RuntimeException('Unexpected SQL contract');
 $GLOBALS['made']++;return(object)['rows'=>$rows,'index'=>0,'next'=>$next];
}
function sqlsrv_next_result($s){return$s->next;}
function sqlsrv_fetch_array($s,$mode){return$s->rows[$s->index++]??null;}
function sqlsrv_free_stmt($s){$GLOBALS['freed']++;return true;}
require_once $lib.'interface_cache.class.php';require_once $lib.'myCache_MSSQL.class.php';
set_error_handler(function($n,$m,$f,$l){if(error_reporting()&$n)throw new ErrorException($m,0,$n,$f,$l);});
function eq($a,$b){if($a!==$b)throw new RuntimeException('Contract mismatch');}
function ok($x){if(!$x)throw new RuntimeException('Contract assertion failed');}
function throws($class,$fn){try{$fn();}catch(Throwable$e){if($e instanceof$class)return;throw$e;}throw new RuntimeException('Missing expected exception');}
$s=['host'=>'fixture','user'=>'fixture','password'=>'fixture','name'=>'fixture','charset'=>'utf-8'];$c=new myCache_MSSQL($s);$cases=0;
foreach([false,null,0,'',[],"中文\0O'Reilly\\end "]as$i=>$value){ok($c->set('key'.$i,$value));eq($value,$c->fetch('key'.$i,$hit));eq(true,$hit);$cases++;}
ok($c->set('repeat','first'));ok($c->set('repeat','second'));eq('second',$c->get('repeat'));$cases++;
$last=$GLOBALS['calls'][count($GLOBALS['calls'])-2];ok(strpos($last[0],'UPDLOCK,HOLDLOCK')!==false);ok(is_array($last[1][1]));eq('varchar(max)',$last[1][1][3]);$cases++;
ok($c->remove('repeat'));eq(false,$c->fetch('repeat',$hit));eq(false,$hit);ok($c->set('key0'));eq(false,$c->get('key0'));$cases++;
$_SERVER['REQUEST_TIME']=1;ok($c->set('clock','value'));eq('value',$c->get('clock'));ok($c->clean());$last=end($GLOBALS['calls']);ok(strpos($last[0],'UNIX_TIMESTAMP')===false);ok($last[1][0]>1000000);$cases++;
$before=count($GLOBALS['calls']);throws(InvalidArgumentException::class,function()use($c){$c->set('bad','value',NAN);});eq($before,count($GLOBALS['calls']));$cases++;
$GLOBALS['failure']='batch';eq(false,$c->set('late-error','value'));ok($c->getLastError()!=='');$GLOBALS['failure']='query';eq(false,$c->fetch('key1',$hit));eq(false,$hit);$GLOBALS['failure']='';$cases++;
ok($c->set('clear-live','value'));ok($c->clear());eq([],$GLOBALS['rows']);$last=end($GLOBALS['calls']);eq('DELETE FROM [dbo].[my_cache]',$last[0]);eq([],$last[1]);$cases++;
$GLOBALS['failure']='batch';eq(false,$c->clear());ok($c->getLastError()!=='');$GLOBALS['failure']='';$cases++;
$c->close();$c->close();eq(false,$c->get('key1'));eq(1,$GLOBALS['closed']);eq($GLOBALS['made'],$GLOBALS['freed']);$cases++;
echo 'SQLSRV cache contract subcases: '.$cases.'; failures: 0; external connections: 0'.PHP_EOL;
