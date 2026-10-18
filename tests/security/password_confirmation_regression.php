<?php
// PHP 7.4 CLI: execute the real admin modules against TEMPORARY tables only.
namespace app\CMS {
    function getCache($name) { return []; }
    function getURL($path=null,$query=[]) { return '/CMS/'.ltrim((string)$path,'/'); }
}
namespace {
if(PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if(!isset($argv[1])) {
    $passed=0;
    foreach(['sysop','detail'] as $module) foreach(['match','mismatch','zero','legacy','blank','missing'] as $case) {
        $pipes=[];$process=proc_open([PHP_BINARY,__FILE__,$module,$case],[1=>['pipe','w'],2=>['pipe','w']],$pipes);
        $out=stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);foreach($pipes as $pipe)fclose($pipe);
        if(proc_close($process)!==0) { echo 'FAIL '.$module.' '.$case.PHP_EOL.$out;exit(1); }
        $passed++;echo 'PASS '.$module.' '.$case.PHP_EOL;
    }
    echo 'Passed: '.$passed.'; Failed: 0'.PHP_EOL;exit;
}
define('ROOT',dirname(__DIR__, 2).'/');define('LIB',ROOT.'lib/');$_SERVER['DOCUMENT_ROOT']='/';$_SERVER['REQUEST_TIME']=time();
spl_autoload_register(function($class) { foreach([LIB.$class.'.class.php',LIB.'database/'.$class.'.class.php'] as $file)if(is_file($file)){require_once $file;return;} });
require_once LIB.'function.php';require_once LIB.'myDb.class.php';require_once LIB.'database/interface_db.class.php';class_alias('myReq','r');
class ModuleExit extends \RuntimeException {}
class myStep { public static $goto_url='';public static function info($message,$url=''){throw new ModuleExit($message);}public function getLanguage($key){return $key;} }
class CMS extends myStep {public static $log='';public static function redirect(){throw new ModuleExit('redirect');}}
$mystep=new CMS();include ROOT.'config/config.php';$o=$setting['db'];
$db=new myDb('MySQL',$o['host'],$o['user'],$o['password'],$o['charset']);$db->connect(false,'mystep_cms');
$S=(object)['db'=>(object)['pre'=>'confirm_'],'web'=>(object)['path_admin'=>'admin_cms']];
$module=$argv[1];$case=$argv[2];$table=$module==='sysop'?'sys_op':'users';$column=$module==='sysop'?'id':'user_id';
$db->query('CREATE TEMPORARY TABLE confirm_'.$table.'('.$column.' int PRIMARY KEY, username varchar(100), password varchar(255), group_id int, email varchar(100))');
$original=myAuth::hashPassword('original');$db->query("INSERT INTO confirm_".$table." VALUES (1,'op','".$original."',1,'')");
$_GET=[];$_SERVER['REQUEST_METHOD']='POST';$id=1;$method='edit_ok';
$password=$case==='zero'?'0':($case==='blank'?'':'changed');
$_POST=['username'=>'op','username_org'=>'op','password'=>$password,'password_r'=>$case==='mismatch'?'different':$password,'group_id'=>1,'email'=>''];
if($case==='missing')unset($_POST['password_r']);
if($case==='legacy'){unset($_POST['password_r']);$_POST['password_c']=$password;}
$exit=null;
try { include ROOT.'app/CMS/module/admin/'.($module==='sysop'?'setting/sysop.php':'user/detail.php'); }
catch(ModuleExit $error) {$exit=$error->getMessage();}
$stored=$db->result('SELECT password FROM confirm_'.$table.' WHERE '.$column.'=1');
if($case==='mismatch') {if($exit!=='checkform_err_password' || $stored!==$original)throw new \RuntimeException('Mismatch must leave the stored hash unchanged');}
elseif($case==='blank') {if($stored!==$original)throw new \RuntimeException('Blank edit must retain the stored hash');}
else {if($exit!==null || !myAuth::verifyPassword($password,$stored))throw new \RuntimeException('Expected password update without confirmation columns');}
$db->close();
}
