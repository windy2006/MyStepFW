<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$source=rtrim(getenv('MYSTEP_SOURCE')?:dirname(__DIR__, 2),'/').'/';$stage=rtrim(getenv('MYSTEP_STAGE')?:'', '/');
define('ROOT',$source);define('LIB',($stage?:rtrim($source,'/')).'/lib/');define('VENDOR',$source.'vendor/');$_SERVER['DOCUMENT_ROOT']='/';$_SERVER['REQUEST_TIME']=time();
// Only the framework top-level loader is installed here; database entries must load via myDb.
spl_autoload_register(function($class){$file=LIB.$class.'.class.php';if(is_file($file))require_once$file;});
require_once LIB.'function.php';
set_error_handler(function($n,$m,$f,$l){if(error_reporting()&$n)throw new ErrorException($m,0,$n,$f,$l);});
if(($argv[1]??'')==='--load'){
 $mode=$argv[2];
 if($mode==='bundle'){require_once LIB.'database/interface_db.class.php';}
 elseif($mode==='split-first'){require_once LIB.'database/interface_sql.class.php';require_once LIB.'database/interface_db_transaction.class.php';require_once LIB.'database/interface_db_prepared.class.php';require_once LIB.'database/interface_db.class.php';require_once LIB.'database/interface_sql.class.php';}
 elseif($mode==='capability-only'){
  require_once LIB.'database/interface_db_prepared.class.php';require_once LIB.'database/interface_db_transaction.class.php';
  if(interface_exists('interface_db',false)||interface_exists('interface_sql',false))throw new RuntimeException('Optional protocols depend on legacy management API');
  echo'ok';exit;
 }else{
  new myDb();$target=$argv[3];$isInterface=strpos(strtolower($target),'interface_')===0;
  if(!($isInterface?interface_exists($target):class_exists($target)))throw new RuntimeException('Autoload failed');
  echo basename((new ReflectionClass($target))->getFileName());exit;
 }
 foreach(['interface_db','interface_sql','interface_db_prepared','interface_db_transaction']as$c)if(!interface_exists($c,false))throw new RuntimeException('Missing protocol');
 echo'ok';exit;
}
new myDb(); // Registers the framework database autoloader once.
// Legacy third-party implementations deliberately keep the original minimal signatures.
class LegacyReviewDB implements interface_db {
 public function connect(){return true;}public function selectDB($the_db){return true;}
 public function insert(){return true;}public function select(){return [];}public function update(){return true;}public function delete(){return true;}
 public function query($sql){return 0;}public function record($sql){return false;}public function records($sql){return [];}
 public function result($sql){return false;}public function getFields($the_tbl){return [];}public function close(){}
 public function execute($sql,array$params=[]){return 0;} // Method presence alone is not an explicit capability declaration.
}
class LegacyReviewSQL implements interface_sql {
 public function getRS(){return false;}public function getDBs(){return [];}public function getTbls(){return [];}
 public function getPri($the_tbl){return '';}public function getInsertId(){return 0;}public function getStat(){return '';}
 public function getCreateScript($the_tbl){return '';}public function getDataScript($the_tbl){return '';}
 public function getIdxScript($the_tbl){return [];}public function batchExe($SQLs){return [];}
 public function handleSQL($strSQL){return [];}public function file($file){return false;}
 public function check($obj){return false;}public function setCache($cache,$ttl){}
}
$passed=$failed=0;$scratch=sys_get_temp_dir().'/mystep-interface-'.bin2hex(random_bytes(6));mkdir($scratch,0700);
function same($a,$b){if($a!==$b)throw new RuntimeException('Expected '.var_export($a,true).'; got '.var_export($b,true));}
function check($x){if(!$x)throw new RuntimeException('Assertion failed');}
function raises($class,$fn){try{$fn();}catch(Throwable$e){if($e instanceof$class)return;throw$e;}throw new RuntimeException('Expected '.$class);}
function test($name,$fn){global$passed,$failed;try{$fn();$passed++;echo'PASS '.$name.PHP_EOL;}catch(Throwable$e){$failed++;echo'FAIL '.$name.': '.$e->getMessage().PHP_EOL;}}
function cleanup($p){if(is_dir($p)){foreach(new FilesystemIterator($p)as$f)cleanup($f->getPathname());rmdir($p);}elseif(is_file($p))unlink($p);}
function child($mode,$target=''){$env=getenv();$p=proc_open([PHP_BINARY,__FILE__,'--load',$mode,$target],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,$env);fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);if(proc_close($p)!==0||$err!=='')throw new RuntimeException('Load fixture failed: '.$err);return$out;}
function valueThroughProtocol(interface_db_prepared $db,$sql,$params=[]){return$db->fetchValue($sql,$params);}
function callbackThroughProtocol(interface_db_transaction $db,callable$fn){return$db->transaction($fn);}
function validateLegacy($class,$expected){$r=new ReflectionClass($class);same(count($expected),count($r->getMethods()));foreach($expected as$name=>$params){$m=$r->getMethod($name);same(count($params),$m->getNumberOfParameters());same(count($params),$m->getNumberOfRequiredParameters());same(false,$m->hasReturnType());same(false,$m->isStatic());same(true,$m->isPublic());foreach($m->getParameters()as$i=>$p){same($params[$i],$p->name);same(false,$p->hasType());same(false,$p->isPassedByReference());same(false,$p->isVariadic());}}}
try{
 foreach(['interface_db','interface_sql','interface_db_prepared','interface_db_transaction']as$c)test($c.' independent framework autoload',function()use($c){same($c.'.class.php',child('autoload',$c));});
 test('legacy bundle still declares both old and optional protocols',function(){same('ok',child('bundle'));});
 test('split files and old bundle load in either order without redeclaration',function(){same('ok',child('split-first'));});
 test('optional protocols do not require unrelated legacy methods',function(){same('ok',child('capability-only'));});
 foreach(['MYSQL'=>'mysql','MSSQL'=>'mssql','SQLITE'=>'sqlite','SIMPLEDB'=>'simpleDB','INTERFACE_SQL'=>'interface_sql']as$c=>$file)test($c.' resolves canonical filename on case-sensitive filesystems',function()use($c,$file){same($file.'.class.php',child('autoload',$c));});
 test('interface_db original method signatures remain compatible',function(){validateLegacy('interface_db',['connect'=>[],'selectDB'=>['the_db'],'insert'=>[],'select'=>[],'update'=>[],'delete'=>[],'query'=>['sql'],'record'=>['sql'],'records'=>['sql'],'result'=>['sql'],'getFields'=>['the_tbl'],'close'=>[]]);});
 test('interface_sql original signatures stay equivalent as contracts',function(){validateLegacy('interface_sql',['getRS'=>[],'getDBs'=>[],'getTbls'=>[],'getPri'=>['the_tbl'],'getInsertId'=>[],'getStat'=>[],'getCreateScript'=>['the_tbl'],'getDataScript'=>['the_tbl'],'getIdxScript'=>['the_tbl'],'batchExe'=>['SQLs'],'handleSQL'=>['strSQL'],'file'=>['file'],'check'=>['obj'],'setCache'=>['cache','ttl']]);});
 test('legacy db implementation compiles without optional methods',function(){$db=new LegacyReviewDB();same(0,$db->query('SELECT 1'));same([], $db->records('SELECT 1'));same(false,$db instanceof interface_db_prepared);});
 test('legacy management implementation remains independent of interface_db',function(){$db=new LegacyReviewSQL();check($db instanceof interface_sql);same(false,$db instanceof interface_db);same([], $db->batchExe([]));});
 test('all protocol classes and declared methods document parameters and return values',function(){foreach(['interface_db','interface_sql','interface_db_prepared','interface_db_transaction']as$c){$r=new ReflectionClass($c);check($r->getDocComment()!==false);foreach($r->getMethods()as$m){$d=$m->getDocComment();check($d!==false&&strpos($d,'@return')!==false);foreach($m->getParameters()as$p)check(strpos($d,'$'.$p->name)!==false);}}});
 test('capability contracts retain compatible PHP 7.4 parameter typing',function(){$m=new ReflectionMethod('interface_db_prepared','execute');same('array',(string)$m->getParameters()[1]->getType());same([],$m->getParameters()[1]->getDefaultValue());same(false,$m->hasReturnType());$m=new ReflectionMethod('interface_db_transaction','transaction');same('callable',(string)$m->getParameters()[0]->getType());});
 test('uninitialized proxy and invalid capability names return false',function(){$db=new myDb();foreach(['interface_db',null,[],0,'','MissingReviewInterface']as$c)same(false,$db->supports($c));$db=new myDb(new LegacyReviewDB());foreach([null,[],0,'','MissingReviewInterface','MySQL',"a\0b",'../interface_db','interface db']as$c)same(false,$db->supports($c));});
 test('legacy third-party API is not inferred from execute method presence',function(){$db=new myDb(new LegacyReviewDB());check($db->supports('interface_db'));same(false,$db->supports(interface_db_prepared::class));same(0,$db->execute('SELECT 1'));});
 test('supports accepts explicitly declared custom interfaces',function(){$db=new myDb(new class implements Countable{public function count(){return 0;}});check($db->supports(Countable::class));check($db->supports('\\Countable'));same(false,$db->supports('interface_db'));});
 test('MySQL declares prepared and managed-transaction APIs before connecting',function(){$db=new myDb(new MySQL());check($db->supports(interface_db_prepared::class));check($db->supports(interface_db_transaction::class));check($db->supports(interface_sql::class));});
 test('SQLite advertises supported APIs without claiming the full legacy management API',function(){$driver=new SQLite();try{$db=new myDb($driver);check($db->supports(interface_db_prepared::class));check($db->supports(interface_db_transaction::class));same(false,$db->supports(interface_sql::class));}finally{$driver->close();}});
 test('MSSQL inherited begin method does not falsely advertise managed transactions',function(){$driver=new MSSQL();$db=new myDb($driver);check(method_exists($driver,'begin'));check($db->supports(interface_db_prepared::class));same(false,$db->supports(interface_db_transaction::class));raises(BadMethodCallException::class,function()use($driver){$driver->begin();});});
 test('simpleDB keeps file APIs without SQL or transaction capabilities',function()use($scratch){$driver=new simpleDB('legacy',$scratch.'/');check($driver->create([['id',8],['value',20]]));check($driver->insert([1,'value']));$db=new myDb($driver);check($db->supports(interface_db::class));same(false,$db->supports(interface_db_prepared::class));same(false,$db->supports(interface_db_transaction::class));same('value',$db->record(0)['value']);$db->close();});
 test('proxy itself is not mislabeled as its driver interface',function(){$driver=new SQLite();try{$db=new myDb($driver);check($db->supports(interface_db_prepared::class));same(false,$db instanceof interface_db_prepared);}finally{$driver->close();}});
 test('SQLite typed prepared protocol preserves NULL zero and empty string',function(){$db=new SQLite();try{same(null,valueThroughProtocol($db,'SELECT ?',[null]));same(0,valueThroughProtocol($db,'SELECT ?',[0]));same('',valueThroughProtocol($db,'SELECT ?',['']));same([], $db->fetchAll('SELECT 1 WHERE 0'));same(false,$db->fetchOne('SELECT 1 WHERE 0'));}finally{$db->close();}});
 test('SQLite prepared protocol preserves indexed and associative result modes',function(){$db=new SQLite();try{same([[0=>7]],$db->fetchAll('SELECT ? AS n',[7],1));same(['n'=>8],$db->fetchOne('SELECT :n AS n',['n'=>8]));same([0=>9,'n'=>9],$db->fetchOne('SELECT ? AS n',[9],3));}finally{$db->close();}});
 test('SQLite typed transaction protocol commits normal false return',function(){$db=new SQLite();try{$db->query('CREATE TABLE items(value TEXT)');same(false,callbackThroughProtocol($db,function($tx){$tx->execute('INSERT INTO items VALUES(?)',['committed']);return false;}));same('committed',$db->fetchValue('SELECT value FROM items'));}finally{$db->close();}});
 test('SQLite typed transaction protocol rolls back exception and isolates nested savepoint',function(){$db=new SQLite();try{$db->query('CREATE TABLE items(value TEXT)');raises(RuntimeException::class,function()use($db){callbackThroughProtocol($db,function($tx){$tx->execute('INSERT INTO items VALUES(?)',['rolled']);throw new RuntimeException('business');});});same(0,$db->fetchValue('SELECT COUNT(*) FROM items'));check($db->begin());$db->execute('INSERT INTO items VALUES(?)',['outer']);check($db->begin());$db->execute('INSERT INTO items VALUES(?)',['inner']);check($db->rollback());check($db->commit());same([['value'=>'outer']],$db->fetchAll('SELECT value FROM items'));}finally{$db->close();}});
 test('proxy capability detection keeps proxy transaction callback identity',function(){$driver=new SQLite();try{$db=new myDb($driver);check($db->supports(interface_db_transaction::class));same('value',$db->transaction(function($tx)use($db){same($db,$tx);return'value';}));}finally{$driver->close();}});
 test('MSSQL connected empty batch returns an array without requiring sqlsrv calls',function(){$db=new MSSQL();$handle=fopen('php://temp','w+b');try{$prop=new ReflectionProperty(MSSQL::class,'connect');$prop->setAccessible(true);$prop->setValue($db,$handle);check($db->check());same([],$db->batchExe([]));same([],$db->batchExe(['','']));}finally{$prop->setValue($db,null);fclose($handle);}});
 if(in_array('--database',$argv,true)){
  $s=(include$source.'config/config.php')['db'];$admin=new mysqli($s['host'],$s['user'],$s['password']);$schema='mystep_interface_review_'.bin2hex(random_bytes(6));$admin->query('CREATE DATABASE `'.$schema.'` CHARACTER SET utf8mb4');$db=null;
  try{
   $db=new MySQL($s['host'],$s['user'],$s['password'],'utf8mb4');$db->connect(false,$schema);$db->query('CREATE TABLE items(id INT PRIMARY KEY,value TEXT) ENGINE=InnoDB');
   test('MySQL typed prepared protocol preserves binary data and falsey values',function()use($db){same(1,$db->execute('INSERT INTO items VALUES(?,?)',[1,"中文\0 ' "]));same("中文\0 ' ",valueThroughProtocol($db,'SELECT value FROM items WHERE id=?',[1]));same(null,valueThroughProtocol($db,'SELECT ?',[null]));same(0,valueThroughProtocol($db,'SELECT ?',[0]));same('',valueThroughProtocol($db,'SELECT ?',['']));same([], $db->fetchAll('SELECT * FROM items WHERE id=?',[-1]));same(false,$db->fetchOne('SELECT * FROM items WHERE id=?',[-1]));});
   test('MySQL typed transaction protocol commits and rolls back real InnoDB rows',function()use($db){same('committed',callbackThroughProtocol($db,function($tx){$tx->execute('INSERT INTO items VALUES(?,?)',[2,'committed']);return'committed';}));raises(RuntimeException::class,function()use($db){callbackThroughProtocol($db,function($tx){$tx->execute('INSERT INTO items VALUES(?,?)',[3,'rolled']);throw new RuntimeException('business');});});same(0,(int)$db->fetchValue('SELECT COUNT(*) FROM items WHERE id=?',[3]));same('committed',$db->fetchValue('SELECT value FROM items WHERE id=?',[2]));});
   test('MySQL proxy reports declared protocols while preserving normal calls',function()use($db){$proxy=new myDb($db);check($proxy->supports(interface_db::class));check($proxy->supports(interface_sql::class));check($proxy->supports(interface_db_prepared::class));check($proxy->supports(interface_db_transaction::class));same('committed',$proxy->fetchValue('SELECT value FROM items WHERE id=?',[2]));});
  }finally{if($db)$db->close();$admin->query('DROP DATABASE `'.$schema.'`');$admin->close();}
 }
}finally{cleanup($scratch);}
echo'Passed: '.$passed.'; Failed: '.$failed.PHP_EOL;exit($failed?1:0);
