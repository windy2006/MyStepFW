<?php
namespace app\myStep { function logCheck($show=true){return $GLOBALS['plugin_authorized'] ?? false;} }
namespace {
/** 实际插件模块隔离回归，数据库备份仅在随机空库中读取；不执行业务恢复/升级。 */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
if(!isset($argv[1])){
    $cases=['auth','backup-list','backup-empty','backup-invalid','bad-list','bad-group','bad-unban','bad-delete','bad-reset','frontend-invalid','frontend-save','manager-links','manager-file'];$failed=0;
    foreach($cases as $case){$pipes=[];$p=proc_open([PHP_BINARY,__FILE__,$case],[1=>['pipe','w'],2=>['pipe','w']],$pipes);$out=stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);foreach($pipes as $pipe)fclose($pipe);$ok=proc_close($p)===0;echo ($ok?'PASS ':'FAIL ').$case.PHP_EOL;if(!$ok){echo $out;$failed++;}}
    echo 'Passed: '.(count($cases)-$failed).'; Failed: '.$failed.PHP_EOL;exit($failed?1:0);
}
$source=dirname(__DIR__, 2).'/';$scratch=sys_get_temp_dir().'/mystep-plugin-modules-'.bin2hex(random_bytes(6));mkdir($scratch,0700);
define('ROOT',$scratch.'/');define('APP',ROOT.'app/');define('PLUGIN',ROOT.'plugin/');define('CACHE',ROOT.'cache/');define('CONFIG',ROOT.'config/');define('LIB',$source.'lib/');define('ROOT_WEB','/');
foreach([APP.'myStep',APP.'Document',PLUGIN.'myBackup/module',PLUGIN.'badReq/module',PLUGIN.'frontend/module',CACHE,CONFIG] as $path)mkdir($path,0700,true);
spl_autoload_register(function($name){foreach([LIB.$name.'.class.php',LIB.'database/'.$name.'.class.php',LIB.'cache/'.$name.'.class.php'] as $file)if(is_file($file)){require_once $file;return;}});
require_once LIB.'function.php';class_alias('myReq','r');class_alias('myFile','f');
require_once $source.'plugin/interface_plugin.class.php';
foreach(['myBackup/module/myBackup.php','badReq/module/badReq.php','badReq/class.php','frontend/module/frontend.php','frontend/config.php'] as $file)copy($source.'plugin/'.$file,PLUGIN.$file);
require_once PLUGIN.'badReq/class.php';require_once $source.'plugin/frontend/class.php';require_once $source.'plugin/myBackup/class.php';require_once $source.'plugin/manager/class.php';
class ModuleExit extends \RuntimeException {}
class myStep {public static function info($value,$url=''){throw new ModuleExit($value);}public static function redirect($url=''){throw new ModuleExit('redirect');}public static function header($status){throw new ModuleExit($status);}public static function file($file){$GLOBALS['downloaded_file']=$file;throw new ModuleExit('download');}public static function setURL($path=''){return '/'.ltrim($path,'/');}}
class TemplateSpy {public $rows=[],$values=[];public function setLoop($key,$value){$this->rows[$key][]=$value;}public function assign($key,$value=null){if(is_array($key))$this->values=array_replace($this->values,$key);else $this->values[$key]=$value;}}
$tpl_sub=new TemplateSpy();$mystep=new class {public $renders=0;public function getLanguage($key){return $key;}public function render($template){$this->renders++;return 'fixture';}public function regTag($tag,$callback){}public function show($tpl){throw new \RuntimeException('Unauthorized page was rendered');}};
$_SERVER['DOCUMENT_ROOT']='/';$_SERVER['REQUEST_TIME']=time();$_SERVER['REQUEST_METHOD']='GET';$_SERVER['HTTP_HOST']='example.test';$_SERVER['REMOTE_ADDR']='127.0.0.1';$_GET=$_POST=$_REQUEST=$_FILES=[];$info_app=['app'=>'myStep','path'=>[]];
set_error_handler(function($n,$s,$f,$l){if(error_reporting()&$n)throw new \ErrorException($s,0,$n,$f,$l);});
function ensure($value,$message){if(!$value)throw new \RuntimeException($message);}
$db=null;$schema='';
try {
    $case=$argv[1];
    if($case==='auth') {showPluginPage('myBackup');ensure($mystep->renders===0,'Unauthenticated plugin page must stop');}
    elseif(strpos($case,'backup-')===0){
        include $source.'config/config.php';$o=$setting['db'];$schema='mystep_plugin_'.bin2hex(random_bytes(5));
        $db=new myDb('MySQL',$o['host'],$o['user'],$o['password'],$o['charset']);$db->connect(false,$o['name']);$db->query('CREATE DATABASE '.$db->quoteIdentifier($schema));$db->selectDB($schema);$original=$db;
        $S=(object)['db'=>(object)array_replace($o,['name'=>$schema]),'upload'=>(object)['ban_ext'=>'php']];
        file_put_contents(PLUGIN.'myBackup/config.php','<?php return '.var_export(['host'=>$o['host'],'user'=>$o['user'],'password'=>$o['password'],'db'=>$schema,'charset'=>$o['charset']],true).';');
        if($case!=='backup-list'){$_SERVER['REQUEST_METHOD']='POST';$_POST=['method'=>$case==='backup-empty'?'import':'optimize','table'=>'missing; DROP TABLE anything'];$_REQUEST=$_POST;}
        $exit='';try {include PLUGIN.'myBackup/module/myBackup.php';}catch(ModuleExit $e){$exit=$e->getMessage();}
        ensure($case==='backup-invalid'?$exit==='error_para':$exit==='','Expected backup result');ensure($db===$original,'Global database connection must remain identical');ensure((int)$db->fetchValue('SELECT 1')===1,'Framework connection remains usable');
        if($case!=='backup-invalid'){ensure($mystep->renders===1,'Module rendered once');ensure($tpl_sub->values['upload_max_filesize']===plugin_myBackup::uploadLimit(),'Byte limit must match PHP upload and POST capacities');}
    } elseif(strpos($case,'bad-')===0){
        file_put_contents(PLUGIN.'badReq/config.php',"<?php return ['forward'=>'https://example.test/?x=1','post'=>true,'bad_chars'=>'[','cookie'=>''];");file_put_contents(PLUGIN.'badReq/ban_ip.php',"<?php return ['192.0.2.1'=>'blocked'];");
        $fixture=new myDb('SQLite',PLUGIN.'badReq/data.db');plugin_badReq::schema($fixture);
        foreach([['192.0.2.1','/match',2],['192.0.2.1','/other',3],['192.0.2.10','/outside',7]] as $row)$fixture->execute('INSERT INTO requests(ip,url,qry,ua,cnt,req) VALUES(?,?,?,?,?,?)',[$row[0],$row[1],'','test',$row[2],time()]);$fixture->close();
        if($case==='bad-list')$_GET=['keyword'=>'match'];elseif($case==='bad-group')$_GET=['mode'=>'ip'];
        else {$_SERVER['REQUEST_METHOD']='POST';$_POST=['m'=>$case==='bad-delete'?'del':substr($case,4),'ip'=>'192.0.2.1'];$_REQUEST=$_POST;}
        $exit='';try {include PLUGIN.'badReq/module/badReq.php';}catch(ModuleExit $e){$exit=$e->getMessage();}
        if($case==='bad-list'){ensure($count===1 || (int)$count===1,'Filtered count');ensure(count($tpl_sub->rows['record'])===1,'Filtering must survive count()');ensure($tpl_sub->rows['record'][0]['url']==='/match','Correct row');}
        elseif($case==='bad-group'){ensure(count($tpl_sub->rows['record'])===2,'Group count');$rows=array_column($tpl_sub->rows['record'],null,'ip');ensure($rows['192.0.2.1']['cnt']==='5','Group sums request counts');}
        else {
            ensure($exit==='redirect','Mutation completes with redirect');$fixture=new myDb('SQLite',PLUGIN.'badReq/data.db');
            if($case==='bad-unban')ensure(!isset((include PLUGIN.'badReq/ban_ip.php')['192.0.2.1']),'Unban saved before redirect');
            if($case==='bad-delete')ensure((int)$fixture->fetchValue('SELECT COUNT(*) FROM requests')===1,'Only exact IP rows removed');
            if($case==='bad-reset')ensure((int)$fixture->fetchValue('SELECT COUNT(*) FROM requests')===0,'Reset clears rows without replacing the database');$fixture->close();
        }
    } elseif($case==='manager-file') {
        $info_app=['app'=>'myStep','path'=>['manager','file']];$name='update_0123456789abcdef.zip';$file=plugin_manager::artifactPath($name);myFile::saveFile($file,'zip fixture');$_GET=['name'=>$name];
        $GLOBALS['plugin_authorized']=false;plugin_manager::main();ensure(!isset($GLOBALS['downloaded_file']),'Anonymous download must stop');
        $GLOBALS['plugin_authorized']=true;$exit='';try{plugin_manager::main();}catch(ModuleExit $e){$exit=$e->getMessage();}ensure($exit==='download' && $GLOBALS['downloaded_file']===$file,'Authorized artifact served without remote update request');
        $_GET=['name'=>'../../config.php'];$exit='';try{plugin_manager::main();}catch(ModuleExit $e){$exit=$e->getMessage();}ensure($exit==='404','Traversal is refused');
        $file=plugin_manager::artifactPath('difference_0123456789abcdef.zip');myFile::saveFile($file,'public fixture');$url=plugin_manager::artifactURL($file,true);parse_str(parse_url($url,PHP_URL_QUERY),$_GET);$ms_setting=(object)['gen'=>(object)['debug'=>true]];$info_app['path']=['plugin_manager','artifact'];
        $exit='';try{plugin_manager::remote();}catch(ModuleExit $e){$exit=$e->getMessage();}ensure($exit==='download' && $GLOBALS['downloaded_file']===$file,'Public published artifact uses a valid capability');
        $_GET['token']=str_repeat('0',48);$exit='';try{plugin_manager::remote();}catch(ModuleExit $e){$exit=$e->getMessage();}ensure($exit==='404','Invalid public capability is refused');
    } elseif($case==='manager-links') {
        mkdir(PLUGIN.'manager');
        file_put_contents(CONFIG.'version.php','<?php return "1.0";');
        file_put_contents(APP.'myStep/info.php','<?php return ["app"=>"myStep","name"=>"Core","ver"=>"1.0","intro"=>"Core app"];');
        file_put_contents(PLUGIN.'frontend/info.php','<?php return ["idx"=>"frontend","name"=>"Frontend","ver"=>"1.0","intro"=>"Resources"];');
        file_put_contents(PLUGIN.'manager/check_app.php','<?php return ["myStep"=>"1.1"];');
        file_put_contents(PLUGIN.'manager/check_plugin.php','<?php return ["frontend"=>"1.0"];');
        $ms_setting=$S=(object)['gen'=>(object)['path_admin'=>'manage'],'web'=>(object)['update'=>'updates.example.test']];$mystep->setting=$S;
        include $source.'plugin/manager/module/manager.php';
        ensure($tpl_sub->rows['app'][0]['setting_url']==='/manage/setting/myStep','Application settings URL');
        ensure($tpl_sub->rows['plugin'][0]['setting_url']==='/manage/function/plugin/setting/?idx=frontend','Plugin settings URL');
        ensure($tpl_sub->rows['app'][0]['update_class']==='' && $tpl_sub->rows['plugin'][0]['update_class']==='d-none','Update state is explicit data');
        ensure($tpl_sub->rows['plugin'][0]['link']==='#','No fabricated attributes in href');
        $template=new myTemplate(['path'=>$source.'plugin/manager/template','style'=>'','name'=>'manager','path_compile'=>CACHE.'compiled']);
        foreach($tpl_sub->rows as $key=>$rows)foreach($rows as $row)$template->setLoop($key,$row);
        $template->assign('plugin_setting_url','#manager-settings');$html=$template->render('',false);
        ensure(strpos($html,'href="/manage/function/plugin/setting/?idx=frontend"')!==false,'Rendered loop URL retains its own plugin');
        ensure(strpos($html,'href="#manager-settings"')!==false,'Header settings URL remains independent');
    } else {
        $_SERVER['REQUEST_METHOD']='POST';$_POST=['detail'=>$case==='frontend-invalid'?'not json':'{"Document":{"jq":"jquery-3.6.1.js","bs":"5.2.2"}}'];$before=file_get_contents(PLUGIN.'frontend/config.php');$exit='';
        try {include PLUGIN.'frontend/module/frontend.php';}catch(ModuleExit $e){$exit=$e->getMessage();}
        if($case==='frontend-invalid'){ensure($exit==='error_para','Invalid JSON rejected');ensure(file_get_contents(PLUGIN.'frontend/config.php')===$before,'Invalid JSON must not change settings');}
        else {ensure($exit==='','Save succeeds');$out=include PLUGIN.'frontend/config.php';ensure(isset(json_decode($out['detail'],true)['Document']),'Canonical JSON persisted');}
    }
} finally {
    if($schema!=='' && $db){$db->query('DROP DATABASE '.$db->quoteIdentifier($schema));$db->close();}
    $clean=function($path)use(&$clean){if(is_link($path)||is_file($path)){unlink($path);return;}foreach(new \FilesystemIterator($path) as $file)$clean($file->getPathname());rmdir($path);};$clean($scratch);
}
echo 'OK'.PHP_EOL;
}
