<?php
// Framework call-site regressions. All writes use private temporary paths/test databases.
if(PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$source=rtrim(getenv('MYSTEP_SOURCE') ?: dirname(__DIR__, 2), '/').'/';
$stage=rtrim(getenv('MYSTEP_STAGE') ?: '', '/');
function projectFile($name) { global $source,$stage; return $stage && is_file($stage.'/'.$name) ? $stage.'/'.$name : $source.$name; }
$scratch=sys_get_temp_dir().'/mystep-integration-'.bin2hex(random_bytes(6));mkdir($scratch,0700);
define('ROOT',$scratch.'/');define('LIB',($stage ?: rtrim($source,'/')).'/lib/');define('VENDOR',$source.'vendor/');
define('CACHE',ROOT.'cache/');define('CONFIG',ROOT.'config/');define('PATH',$source.'app/Document/');
mkdir(CACHE,0700);mkdir(CONFIG,0700);
$_SERVER['DOCUMENT_ROOT']='/';$_SERVER['REQUEST_TIME']=time();$_GET=[];$_POST=[];$_COOKIE=[];
spl_autoload_register(function($class) {foreach([LIB.$class.'.class.php',LIB.'database/'.$class.'.class.php',LIB.'cache/'.$class.'.class.php'] as $file) if(is_file($file)) {require_once $file;return;}});
require_once LIB.'function.php';new myDb();
set_error_handler(function($n,$m,$f,$l) {if(error_reporting() & $n) throw new ErrorException($m,0,$n,$f,$l);});
function check($v) {if(!$v) throw new RuntimeException('Assertion failed');}
function same($a,$b) {if($a!==$b) throw new RuntimeException('Values differ');}
function raises($class,$fn) {try {$fn();} catch(Throwable $e) {if($e instanceof $class) return;throw $e;} throw new RuntimeException('Expected '.$class);}
function removeTree($p) {if(is_link($p)||is_file($p)) {unlink($p);return;} if(is_dir($p)) {foreach(new FilesystemIterator($p) as $f) removeTree($f->getPathname());rmdir($p);}}
register_shutdown_function(function() use($scratch) {removeTree($scratch);});
function child($args,$noIni=false) {global $source,$stage;$env=getenv();$env['MYSTEP_SOURCE']=$source;if($stage)$env['MYSTEP_STAGE']=$stage;$cmd=[PHP_BINARY];if($noIni)$cmd[]='-n';$cmd[]=__FILE__;foreach($args as $a)$cmd[]=$a;$p=proc_open($cmd,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,$env);fclose($pipes[0]);return [$p,$pipes];}
function finish($job) {list($p,$pipes)=$job;$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);if(proc_close($p)!==0 || $err!=='') throw new RuntimeException('Child failed: '.$out.$err);return $out;}
if(($argv[1]??'')==='--hold') {
 $c=new myCache('File',$argv[2],0);$c->synchronized('held',function() use($argv) {file_put_contents($argv[3],'ready');for($i=0;$i<500&&!is_file($argv[4]);$i++) usleep(10000);check(is_file($argv[4]));});echo 'ok';exit;
}
class IntegrationLegacyCache implements interface_cache {
 public $data=[];public $cleanCalls=0;
 public function set($k,$v,$t) {$this->data[$k]=$v;return true;}
 public function get($k) {return $this->data[$k]??false;}
 public function remove($k) {unset($this->data[$k]);return true;}
 public function clean() {$this->cleanCalls++;return true;}
}
class IntegrationClearCache extends IntegrationLegacyCache {public $cleared=0;public $success=true;public function clear() {$this->cleared++;$this->data=[];return $this->success;}}
if(($argv[1]??'')==='--init') {
 $kind=strtolower($argv[2])==='mysql'?'myCache_MySQL':'myCache_MSSQL';
 eval('class '.$kind.' extends IntegrationLegacyCache {public $settings;public function __construct($s) {$this->settings=$s;}}');
 $cache=null;$db=new stdClass();$fw=(new ReflectionClass(myStep::class))->newInstanceWithoutConstructor();
 $fw->setting=(object)['gen'=>(object)['cache_mode'=>$argv[2]],'db'=>(object)['host'=>'fixture','user'=>'fixture','name'=>'fixture','charset'=>'utf8','port'=>3307]];
 $fw->setInstance();same('fixture',$cache->settings['host']);same(3307,$cache->settings['port']);echo 'ok';exit;
}
if(($argv[1]??'')==='--memcached') {
 // -n removes the native extension; this double cannot contact or flush a real service.
 class Memcached {
  const OPT_CONNECT_TIMEOUT=1,OPT_SERVER_FAILURE_LIMIT=2,OPT_RETRY_TIMEOUT=3,OPT_REMOVE_FAILED_SERVERS=4,OPT_DISTRIBUTION=5,DISTRIBUTION_CONSISTENT=6,OPT_LIBKETAMA_COMPATIBLE=7;
  public static $flushes=0;public function __construct($id=null) {}public function setOption($k,$v) {return true;}public function getServerList() {return [];}
  public function addServer($h,$p,$w) {return true;}public function getResultCode() {return 0;}public function flush() {self::$flushes++;return true;}
 }
 $info_app=['app'=>'CMS'];$old=new myCache('File',CACHE.'old',0);$cache=$old;
 $target=new class {public $attached;public $ttl;public function setCache($c,$ttl) {$this->attached=$c;$this->ttl=$ttl;}};$db=new myDb($target);
 // Only the entrypoint and its actual companion/config files live in this private copy.
 $entry=ROOT.'memcached/';mkdir($entry,0700);foreach(['index.php','memoryCache.class.php','config.php'] as $f) copy(projectFile('plugin/memcached/'.$f),$entry.$f);
 include $entry.'index.php';check($cache!==$old);same($cache,$target->attached);same(600,$target->ttl);same(0,Memcached::$flushes);check($cache->clear());same(1,Memcached::$flushes);echo 'ok';exit;
}
$passed=$failed=0;
function test($name,$fn) {global $passed,$failed;$filter=getenv('MYSTEP_INTEGRATION_FILTER');if($filter && !preg_match($filter,$name)) return;try {$fn();$passed++;echo 'PASS '.$name.PHP_EOL;}catch(Throwable $e) {$failed++;echo 'FAIL '.$name.': '.$e->getMessage().PHP_EOL;}}
try {
 test('legacy null function invalidation works through func alias without recomputation',function() {$c=new myCache('File',CACHE.'func',0);$v='before';$fn=function() use(&$v) {return $v;};same('before',$c->func($fn,[],60));$v='after';same(null,$c->func($fn,[],null));same('after',$c->func($fn,[],60));});
 test('CMS catalogue and independent-article invalidation use explicit numeric zero',function() {foreach(['catalog.php','content.php'] as $name) {$text=file_get_contents(projectFile('app/CMS/module/admin/article/'.$name));preg_match_all('/\$cache->func\([^;]+setCatList[^;]+;/', $text,$calls);check(count($calls[0])>0);$cache=new myCache('File',CACHE.'cms-'.$name,0);foreach($calls[0] as $call) {check(strpos($call,', 0)')!==false);$news_cat=[];$call=str_replace("'\\app\\CMS\\setCatList'", "'count'",$call);eval($call);}}});
 test('file clear removes live and legacy values while preserving unrelated files',function() {$path=CACHE.'clear';$c=new myCache('File',$path,0);$c->set('live','value');$hash=md5('legacy');mkdir($path.'/v2/'.substr($hash,0,2),0700);file_put_contents($path.'/v2/'.substr($hash,0,2).'/'.$hash,serialize(['version'=>2,'expire'=>time()+60,'value'=>'old']));file_put_contents($path.'/notes.txt','keep');$c->clean();same('value',$c->get('live'));check($c->clear());same(false,$c->get('live'));same(false,$c->get('legacy'));same('keep',file_get_contents($path.'/notes.txt'));});
 test('file clear preserves writer lock inode',function() {$path=CACHE.'inode';$c=new myCache('File',$path,0);$c->set('key','value');$hash=md5('key');$file=$path.'/v2/'.substr($hash,0,2).'/.lock';$ino=fileinode($file);check($c->clear());same($ino,fileinode($file));});
 test('admin clearing preserves active cross-process computation lock',function() {$path=CACHE.'locked';$c=new myCache('File',$path,0);$c->set('live','value');$ready=ROOT.'ready';$release=ROOT.'release';$worker=child(['--hold',$path,$ready,$release]);try {for($i=0;$i<300&&!is_file($ready);$i++)usleep(10000);check(is_file($ready));$locks=glob($path.'/v2/.compute/*.lock');same(1,count($locks));$ino=fileinode($locks[0]);check($c->clear());same($ino,fileinode($locks[0]));raises(RuntimeException::class,function() use($c) {$c->synchronized('held',function() {throw new LogicException('must not enter');},0);});} finally {file_put_contents($release,'done');same('ok',finish($worker));}});
 test('file clear rejects symlink records without deleting their targets',function() {$path=CACHE.'symlink';$c=new myCache('File',$path,0);$c->set('key','value');$hash=md5('key');$file=$path.'/v2/'.substr($hash,0,2).'/'.$hash;unlink($file);file_put_contents(ROOT.'outside','keep');symlink(ROOT.'outside',$file);same(false,$c->clear());same('keep',file_get_contents(ROOT.'outside'));});
 test('file clear applies to all namespaces sharing its directory',function() {$a=new myCache('File',['path'=>CACHE.'namespaces','namespace'=>'a'],0);$b=new myCache('File',['path'=>CACHE.'namespaces','namespace'=>'b'],0);$a->set('key','a');$b->set('key','b');check($a->clear());same(false,$a->get('key'));same(false,$b->get('key'));});
 test('old third-party drivers remain usable without implicit clean-as-flush',function() {$target=new IntegrationLegacyCache();$c=new myCache($target,null,0);raises(BadMethodCallException::class,function() use($c) {$c->clear();});same(0,$target->cleanCalls);});
 test('framework clearing updates file storage and currently attached backend',function() {global $cache;$f=new myCache('File',CACHE.'data',0);$f->set('key','file');$target=new IntegrationClearCache();$target->set('key','remote',60);$cache=new myCache($target,null,0);check(myStep::clearDataCache());same(1,$target->cleared);same(false,$f->get('key'));same(false,$cache->get('key'));});
 test('framework clearing reports backend failure instead of success',function() {global $cache;$target=new IntegrationClearCache();$target->success=false;$cache=new myCache($target,null,0);same(false,myStep::clearDataCache());});
 test('actual admin module clears data through backend API',function() {global $cache;$target=new IntegrationClearCache();$cache=new myCache($target,null,0);$_GET=['id'=>8];$mystep=new class {public $message;public function setAddedContent($a,$b) {$this->message=$b;}public function getLanguage($k) {return $k;}};$t=new class {public function assign($k,$v) {}};include projectFile('app/myStep/module/function/cache.php');same(1,$target->cleared);check(strpos($mystep->message,'setting_done')!==false);$_GET=[];});
 test('actual admin module does not show success when backend fails',function() {global $cache;$target=new IntegrationClearCache();$target->success=false;$cache=new myCache($target,null,0);$_GET=['id'=>8];$mystep=new class {public function setAddedContent($a,$b) {throw new LogicException('success must not run');}};$t=new stdClass();try {raises(RuntimeException::class,function() use($mystep,$t) {include projectFile('app/myStep/module/function/cache.php');});}finally {$_GET=[];}});
 foreach(['MySQL','mysql','MSSQL','mssql'] as $mode) test('framework passes database configuration to '.$mode.' cache',function() use($mode) {same('ok',finish(child(['--init',$mode])));});
 test('Memcached plugin reattaches SQL query cache and supports explicit admin clearing',function() {same('ok',finish(child(['--memcached'],true)));});
 test('document example preserves framework global cache and SQL attachment',function() {global $cache;$cache=new myCache('File',CACHE.'global',0);$cache->set('sentinel','keep');$original=$cache;$db=new myDb(new SQLite());$db->setCache($cache,600);ob_start();try {include projectFile('app/Document/module/myCache.php');$output=ob_get_contents();}finally {ob_end_clean();$db->close();}same($original,$cache);same('keep',$cache->get('sentinel'));check(strpos($output,'false值命中：yes')!==false);check(strpos($output,'rememberLocked_cache_test')!==false);});
 test('updater uses the same lock-preserving data-clear entrypoint as admin',function() {$text=file_get_contents(projectFile('plugin/manager/class.php'));check(strpos($text,'myStep::clearDataCache()')!==false);check(strpos($text,"myFile::del(CACHE.'data')")===false);});
 test('SQL Server explicit clear consumes late batch errors without leaking statements',function() {global $source,$stage;$env=getenv();$env['MYSTEP_SOURCE']=$source;if($stage)$env['MYSTEP_STAGE']=$stage;$p=proc_open([PHP_BINARY,projectFile('tests/cache/cache_sqlsrv_fixture.php')],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,$env);fclose($pipes[0]);$out=finish([$p,$pipes]);check(strpos($out,'failures: 0')!==false || strpos($out,'SKIP')===0);});
 if(in_array('--database',$argv,true)) {
  $s=(include $source.'config/config.php')['db'];$admin=new mysqli($s['host'],$s['user'],$s['password']);$schema='mystep_integration_review_'.bin2hex(random_bytes(6));$admin->query('CREATE DATABASE `'.$schema.'` CHARACTER SET utf8mb4');
  try {
   test('MySQL explicit clear removes live values across shared namespaces and preserves other tables',function() use($schema,$s) {$s['name']=$schema;$a=new myCache('MySQL',$s+['table'=>'shared_cache','namespace'=>'a'],0);$b=new myCache('MySQL',$s+['table'=>'shared_cache','namespace'=>'b'],0);$other=new myCache('MySQL',$s+['table'=>'other_cache'],0);try {$a->set('k','a');$b->set('k','b');$other->set('k','other');check($a->clear());same(false,$a->get('k'));same(false,$b->get('k'));same('other',$other->get('k'));}finally {$a->close();$b->close();$other->close();}});
   test('MySQL framework clear uses selected database backend and keeps named computation lock',function() use($schema,$s) {global $cache;$s['name']=$schema;$cache=new myCache('MySQL',$s,0);$other=new myCache('MySQL',$s,0);try {$cache->set('k','sql');$cache->synchronized('held',function() use($other) {check(myStep::clearDataCache());raises(RuntimeException::class,function() use($other) {$other->synchronized('held',function() {throw new LogicException('must not enter');},0);});});same(false,$cache->get('k'));}finally {$cache->close();$other->close();}});
  } finally {$admin->query('DROP DATABASE `'.$schema.'`');$admin->close();}
 }
} finally {unset($GLOBALS['cache']);}
echo 'Passed: '.$passed.'; Failed: '.$failed.PHP_EOL;exit($failed?1:0);
