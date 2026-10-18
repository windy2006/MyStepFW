<?php
/**
 * 应用 SQL 回归：仅创建随机名称的 TEMPORARY 表，断开连接即清理。
 * 用法：PHP 7.4 CLI tests/applications/app_database_regression.php；使用本机已配置的测试连接。
 * 真实业务模块在子进程中执行；跳转抛异常截获，不提交 HTTP 表单或发送邮件。
 */
if(PHP_SAPI!=='cli') { http_response_code(404);exit; }
if(!isset($argv[1])) {
    $failed=0;
    foreach($cases=['search','tag-delete','tag-rebuild','backup-empty','backup-invalid','statistics','catalog-self','catalog-child','catalog-orphan','captcha-login','captcha-register','profile-zero','article-lock'] as $mode) {
        $pipes=[];$p=proc_open([PHP_BINARY,__FILE__,$mode],[1=>['pipe','w'],2=>['pipe','w']],$pipes);
        $out=stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);foreach($pipes as $pipe)fclose($pipe);
        $ok=proc_close($p)===0;$failed+=!$ok;echo ($ok?'PASS ':'FAIL ').$mode.PHP_EOL;if(!$ok)echo $out;
    }
    echo 'Passed: '.(count($cases)-$failed).'; Failed: '.$failed.PHP_EOL;exit($failed?1:0);
}
define('ROOT',dirname(__DIR__, 2).'/');define('LIB',ROOT.'lib/');define('VENDOR',ROOT.'vendor/');define('APP',ROOT.'app/');define('PATH',sys_get_temp_dir().'/mystep-app-db-'.bin2hex(random_bytes(6)).'/');define('CONFIG',ROOT.'config/');define('ROOT_WEB','/');define('ROOT_APP','/');define('CACHE',sys_get_temp_dir().'/mystep-app-test-cache/');
$_SERVER['DOCUMENT_ROOT']='/';$_SERVER['REQUEST_TIME']=time();$_SERVER['HTTP_HOST']='cms.test';$_SERVER['REQUEST_METHOD']='POST';$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_USER_AGENT']='AppRegression';
spl_autoload_register(function($name) { foreach([LIB.$name.'.class.php',LIB.'database/'.$name.'.class.php'] as $file)if(is_file($file)){require_once $file;return;} });
require_once LIB.'function.php';require_once APP.'CMS/lib.php';class_alias('myReq','r');class_alias('myFile','f');
class ModuleExit extends RuntimeException {}
if($argv[1]!=='statistics') {
    class CMS {public static $log='';public static function redirect(){throw new ModuleExit('redirect');}}
    class myStep {public static function info($message,$url=''){throw new ModuleExit($message);}}
}
$mystep=new class { public function getLanguage($key){return $key;} public function render($tpl){return '';} };
include CONFIG.'config.php';$o=$setting['db'];$db=new myDb('MySQL',$o['host'],$o['user'],$o['password'],$o['charset']);$db->connect(false,'mystep_cms');
set_error_handler(function($n,$s,$f,$l) { if(error_reporting() & $n)throw new ErrorException($s,0,$n,$f,$l); });
$pre='appcheck_'.bin2hex(random_bytes(5)).'_';
$S=(object)['db'=>(object)['pre'=>$pre,'pre_sub'=>$pre,'name'=>'mystep_cms'],'upload'=>(object)['ban_ext'=>'php'],'list'=>(object)['txt'=>20],'gen'=>(object)['charset'=>'UTF-8']];
mkdir(PATH.'website',0700,true);file_put_contents(PATH.'config.php','<?php return [];');
file_put_contents(PATH.'website/config_main.php','<?php return '.var_export(['db'=>['pre'=>$pre]],true).';');
register_shutdown_function(function(){
    if(session_status()===PHP_SESSION_ACTIVE)session_write_close();
    foreach(glob(PATH.'website/*') as $file)unlink($file);rmdir(PATH.'website');
    foreach(glob(PATH.'*') as $file)if(is_file($file))unlink($file);rmdir(PATH);
});
$web_id=1;
$web_cur=['setting'=>(object)['db'=>(object)['pre'=>$pre]]];$web_info=['idx'=>'main','web_id'=>1];$website=[['idx'=>'main','web_id'=>1]];
function ensure($condition,$message) { if(!$condition)throw new RuntimeException($message); }
function temp($name,$columns) {global $db,$pre;$db->query('CREATE TEMPORARY TABLE '.$db->quoteIdentifier($pre.$name).' ('.$columns.')');}
temp('news_show','news_id int PRIMARY KEY, cat_id int, web_id int, subject varchar(150), tag text, active date NULL, expire date NULL, `order` int DEFAULT 0');
temp('news_tag','id int AUTO_INCREMENT PRIMARY KEY,tag varchar(120),count int,click int,add_date bigint,update_date bigint');
$news=$db->quoteIdentifier($pre.'news_show');$fixtureTags=$db->quoteIdentifier($pre.'news_tag');
if($argv[1]==='search') {
    temp('news_cat','cat_id int PRIMARY KEY,pid int,web_id int,idx varchar(50),name varchar(50)');temp('news_detail','news_id int,content text');
    $db->query('INSERT INTO '.$db->quoteIdentifier($pre.'news_cat')." VALUES(1,0,1,'news','News')");
    $db->execute('INSERT INTO '.$news.' VALUES(1,1,1,?,?,NULL,NULL,0),(2,1,1,?,?,NULL,NULL,0),(3,1,1,?,?,NULL,DATE_SUB(CURDATE(),INTERVAL 1 DAY),0)', ['needle title','PHP7','other','PHP7','needle expired','PHP7']);
    $db->query('INSERT INTO '.$db->quoteIdentifier($pre.'news_detail')." VALUES(1,'needle'),(1,'needle second page'),(2,'needle'),(3,'needle')");
    $sql=app\CMS\buildSQL(['keyword'=>'needle']);
    ensure((int)$db->count($sql)===2,'Count must match visible articles without duplicate detail pages');
    ensure(count($db->records($sql))===2,'List must match count');
    ensure(strpos($sql,'EXISTS')!==false,'Search must use EXISTS');
} elseif(strpos($argv[1],'tag-')===0) {
    $apostrophe="O'Reilly";$db->execute('INSERT INTO '.$news.' VALUES(1,1,1,?,?,NULL,NULL,0),(2,1,1,?,?,NULL,NULL,0),(3,1,1,?,?,NULL,NULL,0)', ['one',$apostrophe.','.$apostrophe.',PHP7,PHP7,中','two','PHP7,123','three',$apostrophe]);
    $db->execute('INSERT INTO '.$fixtureTags.' (id,tag,count,click,add_date,update_date) VALUES(1,?,99,4,1,1),(2,?,99,0,1,1)', [$apostrophe,'unused']);
    $id=1;$method=$argv[1]==='tag-delete'?'delete':'rebuild';
    try { include APP.'CMS/module/admin/article/tag.php'; }catch(ModuleExit $e){ensure($e->getMessage()==='redirect','Expected redirect');}
    if($method==='delete') {
        $rows=$db->records('SELECT tag FROM '.$news.' ORDER BY news_id');ensure($rows[0]['tag']==='PHP7,PHP7,中' && $rows[2]['tag']==='','Delete must remove complete tags including a single tag');
        ensure((int)$db->fetchValue('SELECT COUNT(*) FROM '.$fixtureTags.' WHERE id=1')===0,'Tag must be removed');
    } else {
        $rows=$db->records('SELECT tag,count,click FROM '.$fixtureTags);$map=array_column($rows,null,'tag');
        ensure((int)$map['PHP7']['count']===2,'Count once per article');ensure((int)$map[$apostrophe]['count']===2,'Preserve quotes');ensure((int)$map[$apostrophe]['click']===4,'Preserve clicks');ensure(isset($map['中'])&&!isset($map['unused']),'Keep Chinese; remove unused');
        ensure($db->fetchValue('SELECT tag FROM '.$news.' WHERE news_id=2')==='PHP7','Clean numeric tags');
    }
} elseif($argv[1]==='article-lock') {
    function checkPower($idx='',$value=''){return true;}
    $db->query('ALTER TABLE '.$news.' ADD add_date datetime');
    $db->execute('INSERT INTO '.$news.' (news_id,cat_id,web_id,subject,tag,add_date) VALUES(1,1,1,?,?,DATE_SUB(NOW(),INTERVAL 70 DAY))',['old','PHP']);
    $S->info=(object)['time'=>time()];$method='edit_ok';$id=1;$_REQUEST=[];$exit='';
    try { include APP.'CMS/module/admin/article/content.php'; }catch(ModuleExit $e){$exit=$e->getMessage();}
    ensure($exit==='admin_art_content_locked','60-day lock must compare timestamps in seconds');
} elseif(strpos($argv[1],'catalog-')===0) {
    function checkPower($idx='',$value=''){return true;}
    $news_cat_plat=[['cat_id'=>1,'pid'=>0,'web_id'=>1],['cat_id'=>2,'pid'=>1,'web_id'=>1]];
    $method='edit_ok';$id=1;
    $_POST=['cat_id'=>1,'pid'=>$argv[1]==='catalog-self'?1:($argv[1]==='catalog-child'?2:99),'web_id'=>1];$exit='';
    try { include APP.'CMS/module/admin/article/catalog.php'; }catch(ModuleExit $e){$exit=$e->getMessage();}
    ensure($exit==='admin_art_catalog_error','Reject invalid parenting before any data changes');
} elseif(strpos($argv[1],'captcha-')===0 || $argv[1]==='profile-zero') {
    session_start(['use_cookies'=>false,'cache_limiter'=>'','save_path'=>PATH]);
    $info_app=['path'=>['user',$argv[1]==='profile-zero'?'profile':substr($argv[1],8)]];
    $_POST=['username'=>'user','password'=>'0','password_r'=>'0','email'=>'user@example.test','expire'=>0];$exit='';
    if($argv[1]==='profile-zero') {
        temp('users','username varchar(100) PRIMARY KEY,password varchar(255),email varchar(100)');
        $db->execute('INSERT INTO '.$db->quoteIdentifier($pre.'users')." VALUES('user',?,'')",[myAuth::hashPassword('old')]);
        myReq::session('ms_cms_user','user');myReq::session('ms_cms_user_group',2);
    }
    try {include APP.'CMS/module/user.php';}catch(ModuleExit $e){$exit=$e->getMessage();}
    if($argv[1]==='profile-zero') {
        ensure($exit==='page_profile_changed','Profile save should complete');
        ensure(myAuth::verifyPassword('0',$db->fetchValue('SELECT password FROM '.$db->quoteIdentifier($pre.'users'))),'Password zero should not be silently ignored');
    } else ensure($exit==='login_error_captcha','Missing captcha cannot match an empty session captcha');
    session_write_close();
} elseif(strpos($argv[1],'backup-')===0) {
    $tpl_setting=['path'=>APP.'CMS/template','style'=>'admin','name'=>'func_backup','path_compile'=>CACHE.'template'];$website=[['idx'=>'main']];
    $_REQUEST=['method'=>$argv[1]==='backup-empty'?'import':'optimize','table'=>'missing; DROP TABLE anything'];$_POST=['test'=>'1'];$_FILES=[];$exit='';
    try { include APP.'CMS/module/admin/function/backup.php'; }catch(ModuleExit $e){$exit=$e->getMessage();}
    ensure($argv[1]==='backup-empty'?$exit==='':$exit==='error_para','Empty upload must render and invalid table must be rejected');
} else {
    require_once APP.'CMS/CMS.class.php';temp('user_online','ip varchar(50)');temp('counter','`date` date PRIMARY KEY,pv int,iv int,online int');
    $info_app=['path'=>['index']];$counter=$db->quoteIdentifier($pre.'counter');
    $cms=(new ReflectionClass('CMS'))->newInstanceWithoutConstructor();$cms->shutdown();$cms->shutdown();
    $data=$db->fetchOne('SELECT pv,iv,online FROM '.$counter);ensure((int)$data['pv']===2,'PV must increment instead of overwrite');ensure((int)$data['online']===1,'Track peak');
}
$db->close();echo 'OK'.PHP_EOL;
