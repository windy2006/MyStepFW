<?php
if(PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('ROOT', dirname(__DIR__, 2).'/'); define('LIB', ROOT.'lib/'); define('VENDOR', ROOT.'vendor/'); define('APP', ROOT.'app/');
$scratch=sys_get_temp_dir().'/mystep-security-'.bin2hex(random_bytes(6)); mkdir($scratch,0700,true); mkdir($scratch.'/config');
define('CONFIG', $scratch.'/config/');
$_SERVER['DOCUMENT_ROOT']='/'; $_SERVER['REQUEST_TIME']=time(); $_SERVER['HTTPS']='on'; $_SERVER['HTTP_HOST']='mystep.com';
spl_autoload_register(function($name) {foreach([LIB.$name.'.class.php', LIB.'database/'.$name.'.class.php'] as $file) if(is_file($file)){require_once $file;return;}});
require_once LIB.'myStep.class.php'; require_once LIB.'myDb.class.php'; require_once LIB.'database/interface_db.class.php'; require_once ROOT.'app/CMS/CMS.class.php';
require_once LIB.'session/interface_session.class.php'; require_once LIB.'session/sess_file.class.php';
class_alias('myReq','r');
ob_start(); $GLOBALS['no_log']=false; session_save_path($scratch);
session_set_save_handler('sess_file::open','sess_file::close','sess_file::read','sess_file::write','sess_file::destroy','sess_file::gc');
session_start(); myReq::setCookieOpt(['prefix'=>'test_', 'path'=>'/', 'domain'=>'']);
set_error_handler(function($severity,$message,$file,$line){if(error_reporting() & $severity)throw new ErrorException($message,0,$severity,$file,$line);});
$passed=$failed=0;
function check($condition,$message='Unexpected result'){if(!$condition)throw new RuntimeException($message);}
function test($name,$run){global $passed,$failed;try{$run();$passed++;echo 'PASS '.$name.PHP_EOL;}catch(Throwable $e){$failed++;echo 'FAIL '.$name.': '.$e->getMessage().PHP_EOL;}}
foreach(['normal-password','0','a&<"quoted','eval assert',str_repeat('long',30),"nul\0byte"] as $password){
 test('password roundtrip '.strlen($password).' bytes',function()use($password){$hash=myAuth::hashPassword($password);check(myAuth::verifyPassword($password,$hash));check(!myAuth::verifyPassword($password.'wrong',$hash));check(!myAuth::needsUpgrade($hash));});
}
test('legacy MD5 and protected legacy hashes migrate',function(){foreach([md5('a&b'),md5(htmlspecialchars('a&b'))] as $legacy){$protected=myAuth::protectLegacyHash($legacy);check(strpos($protected,'v1:')===0);check(myAuth::verifyPassword('a&b',$protected));check(myAuth::verifyPassword('a&b',$legacy));check(!myAuth::verifyPassword('wrong',$protected));check(myAuth::needsUpgrade($protected));}});
test('long passwords are not truncated at bcrypt limit',function(){$hash=myAuth::hashPassword(str_repeat('x',80).'a');check(!myAuth::verifyPassword(str_repeat('x',80).'b',$hash));});
$hash=myAuth::hashPassword('token-password');$token=myAuth::makeToken('admin',$hash,'operator','secret',60);
test('remember token roundtrip',function()use($hash,$token){check(myAuth::verifyToken($token,'admin',$hash,'operator','secret'));check(strpos($token,$hash)===false);check(strpos($token,'token-password')===false);});
test('remember token rejects tampering and role changes',function()use($hash,$token){check(!myAuth::verifyToken(substr($token,0,-1).(substr($token,-1)==='0'?'1':'0'),'admin',$hash,'operator','secret'));check(!myAuth::verifyToken($token,'admin',$hash,'member','secret'));check(!myAuth::verifyToken($token,'other',$hash,'operator','secret'));check(!myAuth::verifyToken($token,'admin',myAuth::hashPassword('new'),'operator','secret'));});
test('legacy password cookies are rejected',function(){check(myAuth::readToken('admin'.chr(9).md5('password'))===false);check(myAuth::readToken(['bad'])===false);});
test('expired signed token is rejected',function()use($hash){$payload=rtrim(strtr(base64_encode(json_encode(['v'=>2,'u'=>'admin','s'=>'operator','e'=>time()-1])),'+/','-_'),'=');$signature=hash_hmac('sha256',$payload,hash('sha256',"secret\0".$hash,true));check(!myAuth::verifyToken($payload.'.'.$signature,'admin',$hash,'operator','secret'));});
test('config password callback hashes new passwords',function(){file_put_contents(CONFIG.'test.php', '<?php return ["gen"=>["s_pwd"=>"old"]];');file_put_contents(CONFIG.'construction.php','<?php return ["password"=>[0=>"gen.s_pwd", "func"=>["gen.s_pwd"=>"myAuth::hashPassword"]]];');$config=new myConfig(CONFIG.'test.php');$config->makeConstruction(CONFIG.'construction.php');$config->set(['gen'=>['s_pwd'=>'new&password']]);check(myAuth::verifyPassword('new&password',$config->gen->s_pwd));});
if(in_array('--database',$argv,true)){
 $setting=[];include ROOT.'config/config.php';$options=$setting['db'];
 $db=new myDb('MySQL',$options['host'],$options['user'],$options['password'],$options['charset']);$db->connect(false,'mystep_cms');
 $db->query('CREATE TEMPORARY TABLE security_test_sys_op (id int PRIMARY KEY, username varchar(100), password varchar(255), group_id int)');
 $db->query('CREATE TEMPORARY TABLE security_test_users (user_id int PRIMARY KEY, username varchar(100), password varchar(255), group_id int, hash varchar(32))');
 $operator='op&password';$member='member&password';
 $db->query("INSERT INTO security_test_sys_op VALUES (1,'operator','".md5(htmlspecialchars($operator))."',1)");
 $db->query("INSERT INTO security_test_users VALUES (1,'member','".md5(htmlspecialchars($member))."',2,'')");
 $S=$ms_setting=(object)['gen'=>(object)['s_usr'=>'superadmin','s_pwd'=>myAuth::protectLegacyHash(md5('super&password'))], 'web'=>(object)['etag'=>'test-secret'], 'db'=>(object)['pre'=>'security_test_','name'=>'mystep_cms']];
 file_put_contents(CONFIG.'config.php','<?php return '.var_export(myConfig::o2a($S),true).';');
 class AuthCMSProbe extends CMS{public function __construct($settings){$this->setting=$settings;}}
 $cms=new AuthCMSProbe($S);
 test('CMS rejects wrong credentials',function()use($cms){$_SESSION=[];$_COOKIE=[];check($cms->login('operator','wrong')===0);check(myReq::session('sysop')===null);});
 test('CMS operator migrates and restores signed cookie',function()use($cms,$db,$operator){$_SESSION=[];$_COOKIE=[];$old=session_id();check($cms->login('operator',$operator)===1);check(session_id()!==$old);check(myReq::session('sysop')==='y');$record=$db->record("SELECT password FROM security_test_sys_op WHERE id=1");check(strpos($record['password'],'v2:')===0);check(myAuth::verifyPassword($operator,$record['password']));check(myAuth::readToken($_COOKIE['test_ms_cms_op'])!==false);$_SESSION=[];check($cms->login()===1);});
 test('CMS member restores without administrator privileges',function()use($cms,$member){$_SESSION=[];$_COOKIE=[];check($cms->login('member',$member)===2);check(myReq::session('sysop')===null);$_SESSION=[];check($cms->login()===2);check(CMS::checkPower('upload')===false);});
 test('forged operator cookie cannot authorize upload',function()use($cms){$_SESSION=[];$_COOKIE=['test_ms_cms_op'=>'operator'.chr(9).md5('op&password')];check($cms->login()===0);check(CMS::checkPower('upload')===false);});
 test('framework administrator upgrades without changing password',function()use($cms,$S){$_SESSION=[];$_COOKIE=[];check($cms->login('superadmin','super&password')===1);check(strpos($S->gen->s_pwd,'v2:')===0);check(myAuth::verifyPassword('super&password',$S->gen->s_pwd));$_SESSION=[];check($cms->login()===1);});
 test('logout removes every authentication cookie',function()use($cms){$cms->logout();foreach(['ms_auth','ms_cms_op','ms_cms_user'] as $cookie)check(($_COOKIE['test_'.$cookie]??'')==='');});
 $db->close();
}
test('HTTPS context verifies peers and uses HTTP options',function(){$before=stream_context_get_options(stream_context_get_default());$options=stream_context_get_options(myFile::buildContext('https://localhost/', ['method'=>'POST','data'=>['a'=>'b']]));check($options['ssl']['verify_peer']===true && $options['ssl']['verify_peer_name']===true);check($options['http']['method']==='POST' && $options['http']['content']==='a=b');check(strpos($options['http']['header'],'method:')===false);check($before===stream_context_get_options(stream_context_get_default()));});
test('TLS options cannot disable certificate verification',function(){try{myFile::setTLSOptions(['verify_peer'=>false]);throw new RuntimeException('insecure option accepted');}catch(InvalidArgumentException $expected){}});
if(getenv('TLS_FIXTURE_URL')){
 $url=getenv('TLS_FIXTURE_URL');$ca=getenv('TLS_FIXTURE_CA');
 foreach(['getRemote_curl','getRemote_stream'] as $method){
  test($method.' rejects untrusted certificate',function()use($method,$url){myFile::setTLSOptions([]);check(myFile::$method($url.'/')==='');});
  test($method.' accepts trusted CA',function()use($method,$url,$ca){myFile::setTLSOptions(['cafile'=>$ca]);$body=myFile::$method($url.'/');check(json_decode($body,true)['method']==='GET');});
  test($method.' rejects hostname mismatch',function()use($method,$url,$ca){myFile::setTLSOptions(['cafile'=>$ca]);check(myFile::$method(str_replace('localhost','127.0.0.1',$url).'/')==='');});
  test($method.' sends explicit GET query and POST body',function()use($method,$url){
   $unused=[];
   if($method==='getRemote_curl'){
    $get=json_decode(myFile::$method($url.'/echo',['X-Probe'=>'yes'],['a'=>'b'],$unused,'GET',3),true);
    $post=json_decode(myFile::$method($url.'/echo',['X-Probe'=>'yes'],['a'=>'b'],$unused,'POST',3),true);
   }else{
    $get=json_decode(myFile::$method($url.'/echo',['X-Probe'=>'yes'],['a'=>'b'],'GET',3),true);
    $post=json_decode(myFile::$method($url.'/echo',['X-Probe'=>'yes'],['a'=>'b'],'POST',3),true);
   }
   check(($get['query']??null)==='a=b');check(($get['probe']??null)==='yes');check(($post['body']??null)==='a=b');check(($post['method']??null)==='POST');
  });
  test($method.' decodes chunked responses',function()use($method,$url){check(myFile::$method($url.'/chunked')==='hello world');});
  test($method.' does not follow redirects or downgrade HTTPS',function()use($method,$url){check(myFile::$method($url.'/redirect')==='');});
  test($method.' enforces GET timeout',function()use($method,$url){$start=microtime(true);$unused=[];$result=$method==='getRemote_curl'?myFile::$method($url.'/slow',[],'',$unused,'GET',1):myFile::$method($url.'/slow',[],'','GET',1);check($result==='');check(microtime(true)-$start<2);});
 }
 test('getHeader verifies certificates',function()use($url,$ca){myFile::setTLSOptions([]);check(@myFile::getHeader($url.'/')===false);myFile::setTLSOptions(['cafile'=>$ca]);check(is_array(myFile::getHeader($url.'/')));check(@myFile::getHeader(str_replace('localhost','127.0.0.1',$url).'/')===false);});
 myFile::setTLSOptions([]);
}
if(session_status()===PHP_SESSION_ACTIVE)session_write_close();
$out=ob_get_clean();
$iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($scratch,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);foreach($iterator as $file){$file->isDir()?rmdir($file->getPathname()):unlink($file->getPathname());}rmdir($scratch);
echo $out;echo 'Passed: '.$passed.'; Failed: '.$failed.PHP_EOL;exit($failed?1:0);
