<?php
namespace app\CMS { function getCache($name) { return []; } function getURL($path=null, $query=[]) { return '/CMS/'.ltrim((string)$path, '/'); } }
namespace {
if(PHP_SAPI !== 'cli'){http_response_code(404);exit;}
define('ROOT',dirname(__DIR__, 2).'/');define('LIB',ROOT.'lib/');define('ROOT_APP','/CMS');
$_SERVER['DOCUMENT_ROOT']='/';$_SERVER['REQUEST_TIME']=time();
spl_autoload_register(function($class){foreach([LIB.$class.'.class.php',LIB.'database/'.$class.'.class.php'] as $file)if(is_file($file)){require_once $file;return;}});
require_once LIB.'function.php';require_once LIB.'myDb.class.php';require_once LIB.'database/interface_db.class.php';class_alias('myReq','r');
// Replace page exits only; execute the real account modules and SQL builder against temporary tables.
class WorkflowExit extends \RuntimeException {}
class myStep {
 public static $goto_url='';
 public static function info($message,$url=''){throw new WorkflowExit($message);}
 public function getLanguage($key){return $key;}
}
class CMS extends myStep {public static $log='';public static function redirect(){throw new WorkflowExit('redirect');}}
$GLOBALS['mystep']=$mystep=new CMS();$setting=[];include ROOT.'config/config.php';$o=$setting['db'];
$db=new myDb('MySQL',$o['host'],$o['user'],$o['password'],$o['charset']);$db->connect(false,'mystep_cms');$S=(object)['db'=>(object)['pre'=>'workflow_']];
$db->query('CREATE TEMPORARY TABLE workflow_sys_op(id int PRIMARY KEY, username varchar(100), password varchar(255), group_id int, email varchar(100))');
$db->query('CREATE TEMPORARY TABLE workflow_users(user_id int PRIMARY KEY, username varchar(100), password varchar(255), group_id int, email varchar(100), hash varchar(32), reg_date int)');
$original=myAuth::hashPassword('original');$db->query("INSERT INTO workflow_sys_op VALUES (1,'op1','".$original."',1,''),(2,'op2','".$original."',2,'')");
$db->query("INSERT INTO workflow_users VALUES (1,'member','".$original."',2,'','original',1)");
$scratch=sys_get_temp_dir().'/mystep-workflow-'.bin2hex(random_bytes(6));mkdir($scratch,0700);
ob_start();session_save_path($scratch);session_start();$_SESSION=['ms_cms_user'=>'member'];
$passed=0;
function check($condition){if(!$condition)throw new \RuntimeException('Account workflow assertion failed');}
function profileRequest($post){global $db,$S,$mystep,$info_app,$tpl_setting,$tpl,$content;
 $_POST=$post;$info_app=['path'=>['user','profile']];
 try{include ROOT.'app/CMS/module/user.php';}catch(WorkflowExit $expected){return $expected->getMessage();}
 throw new \RuntimeException('Expected workflow to finish');
}
$result=profileRequest(['password'=>'new&password','password_r'=>'new&password','email'=>'changed@example.test','group_id'=>1,'hash'=>'forged','user_id'=>99]);
check($result==='page_profile_changed');$row=$db->record('SELECT * FROM workflow_users WHERE user_id=1');
check($row['group_id']==='2' && $row['hash']==='original' && $row['email']==='changed@example.test');check(myAuth::verifyPassword('new&password',$row['password']));echo "PASS profile updates password and rejects privilege fields\n";$passed++;
$before=$row['password'];$result=profileRequest(['password'=>'mismatch','password_r'=>'different','email'=>'']);check($result==='checkform_err_password');check($db->result('SELECT password FROM workflow_users WHERE user_id=1')===$before);echo "PASS mismatched password confirmation does not update record\n";$passed++;
$id=1;$method='edit_ok';$_POST=['username'=>'op1','username_org'=>'op1','password'=>'operator&new','password_r'=>'operator&new','group_id'=>1,'email'=>''];
include ROOT.'app/CMS/module/admin/setting/sysop.php';
check(myAuth::verifyPassword('operator&new',$db->result('SELECT password FROM workflow_sys_op WHERE id=1')));check($db->result('SELECT password FROM workflow_sys_op WHERE id=2')===$original);echo "PASS operator edit updates only the selected account\n";$passed++;
session_write_close();foreach(glob($scratch.'/*') as $file)unlink($file);rmdir($scratch);$db->close();$out=ob_get_clean();echo $out;echo 'Passed: '.$passed."; Failed: 0\n";
}
