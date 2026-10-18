<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$source=rtrim(getenv('MYSTEP_SOURCE')?:dirname(__DIR__, 2),'/').'/';$stage=rtrim(getenv('MYSTEP_STAGE')?:'','/');
$scratch=sys_get_temp_dir().'/mystep-db-'.bin2hex(random_bytes(8));mkdir($scratch,0700);
define('ROOT',$scratch.'/');define('LIB',$source.'lib/');define('VENDOR',$source.'vendor/');$_SERVER['DOCUMENT_ROOT']='/';$_SERVER['REQUEST_TIME']=time();
spl_autoload_register(function($class)use($stage){foreach([$class.'.class.php','database/'.$class.'.class.php','cache/'.$class.'.class.php']as$rel){$p=$stage!==''&&is_file($stage.'/lib/'.$rel)?$stage.'/lib/'.$rel:LIB.$rel;if(is_file($p)){require_once$p;return;}}});
require_once ($stage!==''?$stage.'/lib/myDb.class.php':LIB.'myDb.class.php');require_once LIB.'function.php';
set_error_handler(function($n,$m,$f,$l){if(error_reporting()&$n)throw new ErrorException($m,0,$n,$f,$l);});
$passed=$failed=0;
function same($a,$b){if($a!==$b)throw new RuntimeException('Expected '.var_export($a,true).' got '.var_export($b,true));}
function check($x){if(!$x)throw new RuntimeException('Assertion failed');}
function raises($type,$run){try{$run();}catch(Throwable$e){if($e instanceof$type)return;throw$e;}throw new RuntimeException('Expected '.$type);}
function test($name,$run){global$passed,$failed;try{$run();$passed++;echo'PASS '.$name.PHP_EOL;}catch(Throwable$e){$failed++;echo'FAIL '.$name.': '.$e->getMessage().PHP_EOL;}}
function cleanup($p){if(is_dir($p)){foreach(new FilesystemIterator($p)as$f)cleanup($f->getPathname());rmdir($p);}elseif(is_file($p))unlink($p);}
function directory($name){$p=ROOT.$name;if(!is_dir($p))mkdir($p,0700,true);return$p;}
class DbReviewSQLite extends SQLite {protected function error($m,$exit=false){throw new RuntimeException('SQLite query failed');}}
class DbReviewMySQL extends MySQL {protected function error($m,$exit=false){throw new RuntimeException('MySQL query failed: '.(int)$this->err);}}
function textTable($suffix){$db=new simpleDB('table',directory($suffix).'/');$db->create([['id',8],['value',32]]);return$db;}
try {
 test('INSERT preview preserves pending positional values',function(){$b=new SQLBuilder('items',[],'','`');$b->values([1,"O'Reilly"],[2,'中文']);$one=$b->insert();same($one,$b->insert());check(strpos($one,"O\\'Reilly")!==false);});
 test('function projections preserve comma-separated arguments',function(){$db=new DbReviewSQLite();$db->query('CREATE TABLE items(value TEXT)');$db->execute('INSERT INTO items VALUES(?)',['x']);$db->build('items')->field("COALESCE(value, 'a,b') AS result");same('x',$db->result());});
 test('function ordering is not prefixed with table alias',function(){$b=new SQLBuilder('items',[],'t0','`');$b->order('RAND()');same('RAND()',$b->order());});
 test('builder alias survives a failed escaping callback',function(){$b=new SQLBuilder('items',[],'t0','`',function(){throw new RuntimeException('escape');});$b->field(['value'=>'x']);raises(RuntimeException::class,function()use($b){$b->update();});same('t0',$b->idx);});
 test('SQL splitting preserves semicolons and whitespace inside literals',function(){$db=new MySQL();$sql="INSERT INTO t VALUES('a;\n  b  c', '/*keep*/'); SELECT 2;";$parts=$db->handleSQL($sql);same(2,count($parts));check(strpos($parts[0],"a;\n  b  c")!==false);check(strpos($parts[0],'/*keep*/')!==false);});
 test('SQL import retains executable version comments',function(){$parts=(new MySQL())->handleSQL('/*!40101 SET @value=1 */; SELECT 1;');same(2,count($parts));same('/*!40101 SET @value=1 */',$parts[0]);});
 test('SQL import delimiter supports complete stored routine bodies',function(){$sql="DELIMITER $$\nCREATE PROCEDURE p() BEGIN SELECT 'a;b'; SELECT 2; END$$\nDELIMITER ;\nSELECT 3;";$parts=(new MySQL())->handleSQL($sql);same(2,count($parts));check(strpos($parts[0],'SELECT 2; END')!==false);same('SELECT 3',$parts[1]);});
 test('SQL splitting ignores comment-only tail',function(){$parts=(new MySQL())->handleSQL("SELECT 1; -- tail\n/* last */");same(['SELECT 1'],$parts);});
 test('MySQL double minus without whitespace is arithmetic',function(){$words=array_column(SQLStatement::tokens('SELECT 3--2 + RAND()',true),'word');check(in_array('RAND',$words,true));});
 test('SQLite count and record accept trailing comments and terminator',function(){$db=new DbReviewSQLite();same(1,$db->count("SELECT 1; -- tail"));same(3,$db->result("SELECT 3 --tail"));});
 test('identifier quoting preserves names instead of deleting characters',function(){$db=new DbReviewSQLite();same('"odd"" name"',$db->quoteIdentifier('odd" name'));same('"main"."odd.name"',$db->quoteIdentifier(['main','odd.name']));});
 test('SQLite mixed named and positional input is rejected',function(){$db=new DbReviewSQLite();raises(InvalidArgumentException::class,function()use($db){$db->fetchOne('SELECT :a,:b',['a'=>1,0=>2]);});same(3,$db->fetchValue('SELECT ?', [3]));});
 test('SQLite duplicate normalized parameter names are rejected',function(){$db=new DbReviewSQLite();raises(InvalidArgumentException::class,function()use($db){$db->fetchOne('SELECT :a,:b',['a'=>1,':a'=>2]);});});
 test('SQLite repeated and prefixed named parameters bind correctly',function(){$db=new DbReviewSQLite();same([4,4,5],$db->fetchOne('SELECT :a,:a,@b',['a'=>4,'@b'=>5],1));});
 test('SQLite raw outer savepoint bypasses shared reads',function(){$db=new DbReviewSQLite(directory('raw-savepoint').'/a.db');$db->setCache(new myCache_File(directory('raw-sp-cache')));$db->query('CREATE TABLE t(value TEXT)');$db->execute('INSERT INTO t VALUES(?)',['old']);same('old',$db->result('SELECT value FROM t'));$db->query('SAVEPOINT raw');$db->execute('UPDATE t SET value=?',['new']);same('new',$db->result('SELECT value FROM t'));$db->query('ROLLBACK');same('old',$db->result('SELECT value FROM t'));});
 test('SQLite releasing root savepoint invalidates other connection caches',function(){$file=directory('release').'/a.db';$a=new DbReviewSQLite($file);$b=new DbReviewSQLite($file);$cache=new myCache_File(directory('release-cache'));$a->setCache($cache);$b->setCache($cache);$a->query('CREATE TABLE t(value TEXT)');$a->execute('INSERT INTO t VALUES(?)',['old']);same('old',$b->result('SELECT value FROM t'));$a->query('SAVEPOINT "outer point"');$a->execute('UPDATE t SET value=?',['new']);same('old',$b->result('SELECT value FROM t'));$a->query('RELEASE SAVEPOINT "outer point"');same('new',$b->result('SELECT value FROM t'));});
 test('SQLite nested raw savepoints rollback only the requested layer',function(){$db=new DbReviewSQLite();$db->query('CREATE TABLE t(value TEXT)');$db->query('SAVEPOINT x');$db->execute('INSERT INTO t VALUES(?)',['outer']);$db->query('SAVEPOINT x');$db->execute('INSERT INTO t VALUES(?)',['inner']);$db->query('ROLLBACK TO x');$db->query('RELEASE x');$db->query('RELEASE x');same([['value'=>'outer']],$db->fetchAll('SELECT value FROM t'));});
 test('managed scope rejects an externally opened SQLite savepoint',function(){$db=new DbReviewSQLite();$db->query('SAVEPOINT x');raises(LogicException::class,function()use($db){$db->begin();});$db->query('ROLLBACK');});
 test('SQLite schema export preserves defaults composite keys and constraints',function(){$db=new DbReviewSQLite();$db->query("CREATE TABLE t(a INT,b INT DEFAULT 7,txt TEXT DEFAULT 'x',PRIMARY KEY(a,b),CHECK(a>0))");$script=$db->getCreateScript('t');$copy=new DbReviewSQLite();$copy->query($script);same($db->fetchAll('PRAGMA table_info(t)'),$copy->fetchAll('PRAGMA table_info(t)'));raises(RuntimeException::class,function()use($copy){$copy->execute('INSERT INTO t(a,b)VALUES(?,?)',[0,1]);});});
 test('SQLite data export round trips null text NUL blob numbers and generated columns',function(){$db=new DbReviewSQLite();$db->query('CREATE TABLE t(id INTEGER PRIMARY KEY,value TEXT,bin BLOB,nil TEXT,n REAL,g TEXT GENERATED ALWAYS AS (value) STORED)');$db->execute('INSERT INTO t(id,value,bin,nil,n) VALUES(?,?,?,?,?)',[1,"O'Reilly\0中文", "\xff\0",null,1.25]);$expected=$db->fetchAll('SELECT * FROM t');$copy=new DbReviewSQLite();$copy->query($db->getCreateScript('t'));$copy->query($db->getDataScript('t'));same($expected,$copy->fetchAll('SELECT * FROM t'));});
 test('SQLite metadata accepts quoted table names',function(){$db=new DbReviewSQLite();$db->query('CREATE TABLE "odd name"("odd field" TEXT)');same(['odd field'],$db->getFields('odd name'));check(strpos($db->getCreateScript('odd name'),'odd field')!==false);});
 test('SQLite index export retains independent indexes',function(){$db=new DbReviewSQLite();$db->query('CREATE TABLE t(x TEXT UNIQUE)');$db->query('CREATE INDEX ix ON t(x)');$sql=$db->getIdxScript('t');same(1,count($sql));check(strpos($sql[0],'CREATE INDEX ix')===0);});
 test('SQLite close keeps error inspection safe and id unavailable',function(){$db=new DbReviewSQLite();$db->close();$err=[];same(false,$db->checkError($err));same(false,$db->getInsertId());});
 test('simpleDB default record reads first row',function(){$db=textTable('simple-first');$db->insert([[1,'first'],[2,'second']]);same(['id'=>'1','value'=>'first'],$db->record());same(2,$db->getFields()['max_row']);});
 test('simpleDB oversized values fail without changing file bytes',function(){$db=textTable('simple-overflow');$db->insert([1,'first']);$file=directory('simple-overflow').'/table.db';$before=file_get_contents($file);raises(LengthException::class,function()use($db){$db->insert([2,str_repeat('x',33)]);});same($before,file_get_contents($file));});
 test('simpleDB control separators fail before write',function(){$db=textTable('simple-control');foreach(["a\0b","a\nb","a\rb"]as$value)raises(InvalidArgumentException::class,function()use($db,$value){$db->insert([1,$value]);});same([],$db->records());});
 test('simpleDB conditions preserve equals in data',function(){$db=textTable('simple-equals');$db->insert([1,'a=b=c']);same('a=b=c',$db->result('value=a=b=c','value'));});
 test('simpleDB empty update and delete conditions affect all rows correctly',function(){$db=textTable('simple-all');$db->insert([[1,'one'],[2,'two']]);same(true,$db->update(null,['value'=>'all']));same(['all','all'],array_column($db->records(),'value'));same(true,$db->delete());same([],$db->records());same(0,$db->getFields()['max_row']);});
 test('simpleDB select respects explicit limit',function(){$db=textTable('simple-limit');$db->insert([[1,'one'],[2,'two']]);same(1,count($db->select('id>0',1)));});
 test('simpleDB deletion keeps offsets and permits further append',function(){$db=textTable('simple-delete');$db->insert([[1,'one'],[2,'two'],[3,'three']]);$db->delete('id=2');$db->insert([4,'four']);same(['1','3','4'],array_column($db->records(),'id'));same('three',$db->record(2)['value']);});
 test('simpleDB readwrite connection never implicitly truncates existing data',function(){$db=textTable('simple-mode');$db->insert([1,'one']);same(true,$db->connect('write'));$db->close();same('one',$db->record(1)['value']);});
 test('simpleDB header path does not require a trailing slash',function(){$path=directory('simple-path');$db=new simpleDB('table',$path);same(true,$db->create([['id',8]]));same(true,is_file($path.'/table.db'));same(true,$db->destroy());});
 test('simpleDB invalid schema and damaged files fail clearly',function(){$db=new simpleDB('table',directory('simple-bad'));raises(InvalidArgumentException::class,function()use($db){$db->create([['db_row',8]]);});file_put_contents(directory('simple-bad').'/table.db','bad');raises(UnexpectedValueException::class,function()use($db){$db->records();});});
 test('MSSQL literals use doubled quotes and preserve backslashes',function(){$db=new MSSQL();same("O''Reilly\\x",$db->safeValue("O'Reilly\\x"));});
 test('MSSQL SQL conversion leaves literal text untouched',function(){$db=new MSSQL();same("SELECT ' NOW() LOW_PRIORITY' AS value",$db->convertSQL("SELECT ' NOW() LOW_PRIORITY' AS value"));same('SELECT GETDATE()',$db->convertSQL('SELECT NOW()'));});
 test('MSSQL offset conversion only rewrites the outer query',function(){$db=new MSSQL();$sql="SELECT 'limit 1' AS x FROM t ORDER BY id LIMIT 2,5";same("SELECT 'limit 1' AS x FROM t ORDER BY id OFFSET 2 ROWS FETCH NEXT 5 ROWS ONLY",$db->convertLimit($sql));});
 test('MSSQL TOP placement preserves DISTINCT syntax',function(){$db=new MSSQL();same('SELECT DISTINCT TOP (1) x FROM t',$db->convertLimit('SELECT DISTINCT x FROM t LIMIT 1'));});
 test('MSSQL import preserves literal whitespace and semicolons',function(){$parts=(new MSSQL())->handleSQL("INSERT INTO t VALUES('a;\n  b');SELECT 2;");same(2,count($parts));check(strpos($parts[0],"a;\n  b")!==false);});
 if(!extension_loaded('sqlsrv'))test('MSSQL missing extension reports an explicit runtime error',function(){raises(RuntimeException::class,function(){(new MSSQL('host','u','p'))->connect('db');});});
 test('zero LIMIT remains explicit for SELECT and DELETE',function(){$b=new SQLBuilder('t',[],'','`');$b->limit(0);same(' limit 0',$b->limit());check(stripos($b->delete(),'truncate')===false);check(strpos($b->delete(),'limit 0')!==false);});
 test('date interval condition retains legacy tuple semantics',function(){$b=new SQLBuilder('t',[],'','`');$b->where('created','d>',['NOW()','y-1']);check(strpos($b->where(),'DATE_SUB(NOW(), INTERVAL 1 YEAR)')!==false);$b->where('[reset]');$b->where('created','d>','2025-01-01');check(strpos($b->where(),"'2025-01-01'")!==false);});
 test('new INSERT previews use the current escaping callback',function(){$version='old';$b=new SQLBuilder('t',[],'','`',function($value)use(&$version){return $version.$value;});$b->values('text');check(strpos($b->insert(),'oldtext')!==false);$version='new';check(strpos($b->insert(),'newtext')!==false);});
 test('managed SQLite callback cannot commit its own outer scope',function(){$db=new DbReviewSQLite();$db->query('CREATE TABLE t(x INT)');raises(LogicException::class,function()use($db){$db->transaction(function($tx){$tx->execute('INSERT INTO t VALUES(?)',[1]);$tx->commit();});});same(0,$db->count('SELECT * FROM t'));});
 test('managed SQLite raw COMMIT is rejected before writes become permanent',function(){$db=new DbReviewSQLite();$db->query('CREATE TABLE t(x INT)');raises(LogicException::class,function()use($db){$db->transaction(function($tx){$tx->execute('INSERT INTO t VALUES(?)',[1]);$tx->query('COMMIT');});});same(0,$db->count('SELECT * FROM t'));});
 test('SQLite native RETURNING is rejected before any write',function(){$db=new DbReviewSQLite();$db->query('CREATE TABLE t(id INTEGER PRIMARY KEY,x TEXT)');raises(BadMethodCallException::class,function()use($db){$db->fetchValue('INSERT INTO t(x)VALUES(?) RETURNING id',['x']);});same(0,$db->count('SELECT * FROM t'));same(1,$db->execute('INSERT INTO t(x)VALUES(?)',['x']));same(1,$db->getInsertId());});
 test('SQLite quoted savepoint rollback ignores TO text in comments',function(){$db=new DbReviewSQLite();$db->query('CREATE TABLE t(x INT)');$db->query('SAVEPOINT "root"');$db->execute('INSERT INTO t VALUES(?)',[1]);$db->query('ROLLBACK -- TO is a comment');same(0,$db->count('SELECT * FROM t'));same('ok',$db->transaction(function(){return'ok';}));});
 test('simpleDB retains existing fixed record file format',function(){$dir=directory('simple-legacy');$length=43;$body=str_pad('1',8)."\0".str_pad('one',32)."\0\n";$header=str_pad((string)(501+$length),10)."\0".$length."\0id:8\0value:32";file_put_contents($dir.'/table.db',str_pad($header,500)."\n".$body);$db=new simpleDB('table',$dir);same('one',$db->record(1)['value']);$db->insert([2,'two']);same(['1','2'],array_column($db->records(),'id'));});
 test('simpleDB enforces file budget before writing',function(){$dir=directory('simple-budget');$db=new simpleDB('table',$dir,500,600);$db->create([['id',8],['value',32]]);raises(LengthException::class,function()use($db){$db->insert([[1,'one'],[2,'two'],[3,'three']]);});same([],$db->records());});
 test('simpleDB concurrent appends preserve every record and header',function()use($scratch){
  $dir=directory('simple-parallel');$db=new simpleDB('table',$dir);$db->create([['id',8],['value',32]]);$driver=(new ReflectionClass('simpleDB'))->getFileName();$worker=$scratch.'/simple-worker.php';
  $code='<?php require '.var_export(LIB.'database/interface_db.class.php',true).';require '.var_export($driver,true).';$db=new simpleDB("table",$argv[1]."/");for($i=0;$i<12;$i++)$db->insert([(int)$argv[2]*100+$i,"value"]);';file_put_contents($worker,$code);$children=[];
  for($i=0;$i<8;$i++){$pipes=[];$p=proc_open([PHP_BINARY,$worker,$dir,(string)$i],[1=>['pipe','w'],2=>['pipe','w']],$pipes);$children[]=[$p,$pipes];}
  foreach($children as$child){list($p,$pipes)=$child;$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);same(0,proc_close($p));same('',$out.$err);}same(96,$db->getFields()['max_row']);same(96,count(array_unique(array_column($db->records(),'id'))));
 });

 if(!extension_loaded('sqlsrv'))test('SQL Server native parameter and result contracts use an isolated driver double',function()use($scratch){
  $driver=(new ReflectionClass('MSSQL'))->getFileName();$core=(new ReflectionClass('myDb'))->getFileName();$worker=$scratch.'/sqlsrv-contract.php';
  $code=<<<'SQLSRV_STUB'
<?php
// SQL Server contract double: never opens a socket or calls an external database.
define('ROOT',sys_get_temp_dir().'/');define('LIB',SOURCE_LIB);define('SQLSRV_ERR_ALL',0);
spl_autoload_register(function($class){foreach([LIB.$class.'.class.php',LIB.'database/'.$class.'.class.php']as$p)if(is_file($p)){require_once$p;return;}});
require_once CORE_FILE;
require DRIVER_FILE;
set_error_handler(function($n,$m,$f,$l){if(error_reporting()&$n)throw new ErrorException($m,0,$n,$f,$l);});
$GLOBALS['wire']=[];$GLOBALS['statements']=[];$GLOBALS['fake_errors']=null;$GLOBALS['freed']=0;
function sqlsrv_connect($host,$settings){return fopen('php://temp','w+b');}
function sqlsrv_configure($key,$value){return true;}
function sqlsrv_query($link,$sql,$params,$options){$GLOBALS['wire'][]=[$sql,$params,$options];$GLOBALS['fake_errors']=null;$stmt=fopen('php://temp','w+b');$read=preg_match('/^\s*(SELECT|WITH|EXEC)\b/i',$sql)===1;$GLOBALS['statements'][(int)$stmt]=['fields'=>$read?1:0,'fetched'=>false];return$stmt;}
function sqlsrv_num_fields($stmt){return$GLOBALS['statements'][(int)$stmt]['fields'];}
function sqlsrv_num_rows($stmt){return 1;}
function sqlsrv_rows_affected($stmt){return 0;}
function sqlsrv_fetch_array($stmt,$mode){$data=&$GLOBALS['statements'][(int)$stmt];if($data['fetched'])return null;$data['fetched']=true;return$mode===1?['ok']:['value'=>'ok'];}
function sqlsrv_free_stmt($stmt){unset($GLOBALS['statements'][(int)$stmt]);fclose($stmt);$GLOBALS['freed']++;return true;}
function sqlsrv_close($link){fclose($link);return true;}
function sqlsrv_errors($kind){return$GLOBALS['fake_errors'];}
class ContractMSSQL extends MSSQL{protected function error($m,$exit=false){throw new RuntimeException($m);}}
function equal($a,$b){if($a!==$b)throw new RuntimeException('sqlsrv contract mismatch');}
$db=new ContractMSSQL('unused','test','test');$db->connect('db');$cases=0;
equal(1,$db->execute('SELECT ? AS value',['中文']));$wire=end($GLOBALS['wire']);equal(['中文'],$wire[1]);equal('SELECT ? AS value',$wire[0]);$cases++;
equal('static',$wire[2]['Scrollable']);equal(30,$wire[2]['QueryTimeout']);$cases++;
equal(['value'=>'ok'],$db->fetchOne('WITH source AS (SELECT 1 AS x) SELECT x FROM source'));$cases++;
equal(['ok'],$db->record('SELECT TOP (1) value FROM t',1));$wire=end($GLOBALS['wire']);equal(false,stripos($wire[0],'LIMIT')!==false);$cases++;
$db->query('SELECT 1');equal(['value'=>'ok'],$db->getRS());equal(false,$db->getRS());$cases++;
$GLOBALS['fake_errors']=[['code'=>123,'message'=>'test error','SQLSTATE'=>'42000']];$message=[];equal(true,$db->checkError($message));equal(true,strpos($message,'test error')!==false);$GLOBALS['fake_errors']=null;$db->clearError();$cases++;
$db->selectDB('odd database');$wire=end($GLOBALS['wire']);equal('USE [odd database]',$wire[0]);$cases++;
$db->close();equal(0,count($GLOBALS['statements']));$cases++;
echo json_encode(['cases'=>$cases,'ok'=>true]);

SQLSRV_STUB;
  $code=str_replace(['SOURCE_LIB','CORE_FILE','DRIVER_FILE'],[var_export(LIB,true),var_export($core,true),var_export($driver,true)],$code);file_put_contents($worker,$code);$pipes=[];$p=proc_open([PHP_BINARY,$worker],[1=>['pipe','w'],2=>['pipe','w']],$pipes);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);if(proc_close($p)!==0)throw new RuntimeException('sqlsrv contract: '.$err);same('',$err);same(['cases'=>8,'ok'=>true],json_decode($out,true));
 });

 test('single-letter column aliases are retained',function(){$db=new DbReviewSQLite();$db->query('CREATE TABLE t(x TEXT)');$db->execute('INSERT INTO t VALUES(?)',['ok']);$db->build('t')->field('x AS y');same(['y'=>'ok'],$db->record());});

 test('SQLite dollar parameters preserve distinct prefixes and literal text',function(){$db=new DbReviewSQLite();same([1,2,3,'$a'],$db->fetchOne("SELECT \$a,:a,@a,'\$a'",['$a'=>1,':a'=>2,'@a'=>3],1));});

 if(in_array('--database',$argv,true)) {
  $settings=(include $source.'config/config.php')['db'];$admin=new mysqli($settings['host'],$settings['user'],$settings['password']);$schema='mystep_db_review_'.bin2hex(random_bytes(6));$admin->query('CREATE DATABASE `'.$schema.'` CHARACTER SET utf8mb4');$a=$b=null;
  try {
   $a=new DbReviewMySQL($settings['host'],$settings['user'],$settings['password'],'utf8mb4');$a->connect(false,$schema);$b=new DbReviewMySQL($settings['host'],$settings['user'],$settings['password'],'utf8mb4');$b->connect(false,$schema);
   $a->query('CREATE TABLE items(id INT PRIMARY KEY,value TEXT,bin VARBINARY(100),nil TEXT,n DOUBLE,g INT GENERATED ALWAYS AS (id+1) STORED) ENGINE=InnoDB');
   test('MySQL prepared query handles quote NUL null float and injection-looking values',function()use($a){$value="a';DROP TABLE items; -- 中文\0";same(1,$a->execute('INSERT INTO items(id,value,bin,nil,n)VALUES(?,?,?,?,?)',[1,$value,"\0\xff",null,1.25]));same($value,$a->fetchValue('SELECT value FROM items WHERE id=?',[1]));});
   test('MySQL repeat INSERT previews execute both positional rows once',function()use($a){$builder=$a->build('items')->field('id,value,bin,nil,n')->values([2,'two','bin',null,2.5],[3,'three','bin',null,3.5]);$sql=$a->insert(true);same($sql,$a->insert(true));same(2,$a->insert());same('3',$a->result('SELECT count(*) FROM items'));});
   test('MySQL count handles trailing SQL comment',function()use($a){same('3',$a->count("SELECT id FROM items; -- tail"));});
   test('MySQL file import retains spaces and embedded semicolon newline',function()use($a){$file=ROOT.'import.sql';file_put_contents($file,"-- header\nINSERT INTO items(id,value) VALUES (4,'a;\n  b  c');\n");$a->file($file);same("a;\n  b  c",$a->fetchValue('SELECT value FROM items WHERE id=?',[4]));});
   test('MySQL data export round trips nullable binary and generated columns',function()use($a){$expected=$a->records('SELECT * FROM items ORDER BY id');$sql=$a->getDataScript('items');$a->query('CREATE TABLE restored LIKE items');foreach($a->handleSQL(str_replace('INSERT INTO `items`','INSERT INTO `restored`',$sql))as$statement)$a->query($statement);same($expected,$a->records('SELECT * FROM restored ORDER BY id'));});
   test('MySQL stored procedure result sets are drained before next query',function()use($a){$a->query('CREATE PROCEDURE results_probe() BEGIN SELECT 11 AS n; SELECT 22 AS n; END');$a->query('CALL results_probe()');same(['n'=>'11'],$a->getRS());same('99',$a->result('SELECT 99'));});
   test('MySQL delimiter import executes complete stored procedure',function()use($a){$file=ROOT.'routine.sql';file_put_contents($file,"DELIMITER $$\nCREATE PROCEDURE import_probe() BEGIN SELECT 'a;b' AS value; SELECT 2; END$$\nDELIMITER ;\n");$a->file($file);$a->query('CALL import_probe()');same(['value'=>'a;b'],$a->getRS());$a->free();same('1',$a->result('SELECT 1'));});
   test('MySQL fresh connect without database cannot reuse prior cached rows',function()use($a,$schema){$a->setCache(new myCache_File(directory('mysql-cache')));same('4',$a->result('SELECT count(*) FROM items'));$a->connect(false);try{raises(RuntimeException::class,function()use($a){$a->result('SELECT count(*) FROM items');});}finally{$a->selectDB($schema);}});
   test('MySQL raw savepoint rollback leaves other connection data coherent',function()use($a,$b){$shared=new myCache_File(directory('mysql-raw-cache'));$a->setCache($shared);$b->setCache($shared);$old=$b->result('SELECT value FROM items WHERE id=1');$a->query('BEGIN');$a->query('SAVEPOINT raw');$a->execute('UPDATE items SET value=? WHERE id=?',['temporary',1]);$a->query('ROLLBACK TO SAVEPOINT raw');$a->query('RELEASE SAVEPOINT raw');$a->query('COMMIT');same($old,$b->result('SELECT value FROM items WHERE id=1'));});
   test('MySQL identifiers with spaces survive schema export',function()use($a){$a->query('CREATE TABLE `odd name`(`odd field` TEXT)');check(strpos($a->getCreateScript('odd name'),'odd field')!==false);});
   test('MySQL user switch retains configured connection charset',function()use($a,$settings,$schema){same(true,$a->changUser($settings['user'],$settings['password'],$schema));same('utf8mb4',$a->result('SELECT @@character_set_connection'));});
   test('MySQL LIMIT zero cannot truncate a table',function()use($a){$before=$a->result('SELECT count(*) FROM items');$a->build('items')->limit(0);same(0,$a->delete());same($before,$a->result('SELECT count(*) FROM items'));});
   test('managed MySQL DDL is blocked before implicit commit',function()use($a){$before=$a->fetchValue('SELECT value FROM items WHERE id=?',[1]);raises(LogicException::class,function()use($a){$a->transaction(function($tx){$tx->execute('UPDATE items SET value=? WHERE id=?',['must-rollback',1]);$tx->query('CREATE TABLE should_not_exist(x INT)');});});same($before,$a->fetchValue('SELECT value FROM items WHERE id=?',[1]));same([],$a->records("SHOW TABLES LIKE 'should_not_exist'"));});
   test('managed MySQL manual commit is blocked and changes rollback',function()use($a){$before=$a->fetchValue('SELECT value FROM items WHERE id=?',[1]);raises(LogicException::class,function()use($a){$a->transaction(function($tx){$tx->execute('UPDATE items SET value=? WHERE id=?',['must-rollback',1]);$tx->commit();});});same($before,$a->fetchValue('SELECT value FROM items WHERE id=?',[1]));});
   test('full-table builder delete remains rollbackable inside a transaction',function()use($a){$before=$a->result('SELECT count(*) FROM items');raises(RuntimeException::class,function()use($a){$a->transaction(function($tx){$tx->build('items');check(stripos($tx->delete(true),'truncate')===false);$tx->delete();throw new RuntimeException('rollback');});});same($before,$a->result('SELECT count(*) FROM items'));});
   test('MySQL blob parameters reject an incompatible explicit type',function()use($a){$stream=fopen('php://temp','w+b');try{raises(InvalidArgumentException::class,function()use($a,$stream){$a->execute('SELECT ?',[$stream],'i');});}finally{fclose($stream);}});

   test('MySQL SQL mode can preserve server defaults or explicitly enable strict mode',function()use($settings,$schema,$admin){$native=$admin->query('SELECT @@SESSION.sql_mode')->fetch_row()[0];$db=new DbReviewMySQL($settings['host'],$settings['user'],$settings['password'],'utf8mb4',['sql_mode'=>null]);try{$db->connect(false,$schema);same($native,$db->result('SELECT @@SESSION.sql_mode'));}finally{$db->close();}$db=new DbReviewMySQL($settings['host'],$settings['user'],$settings['password'],'utf8mb4',['sql_mode'=>'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION']);try{$db->connect(false,$schema);check(in_array('STRICT_TRANS_TABLES',explode(',',$db->result('SELECT @@SESSION.sql_mode')),true));$db->changUser($settings['user'],$settings['password'],$schema);check(in_array('STRICT_TRANS_TABLES',explode(',',$db->result('SELECT @@SESSION.sql_mode')),true));}finally{$db->close();}});
   test('MySQL prepared CALL closes all pending statement results',function()use($a){$a->execute('CALL results_probe()');same(['n'=>11],$a->getRS());$a->free();same('1',$a->result('SELECT 1'));});
   test('MySQL numeric metadata preserves decimal and bit export types',function()use($a){$a->query('CREATE TABLE numeric_dump (d DECIMAL(25,8),b BIT(8),s VARCHAR(30))');$a->query("INSERT INTO numeric_dump VALUES (1234567890123.12500000,b'10000001','0001')");$expected=$a->records('SELECT * FROM numeric_dump');$script=$a->getDataScript('numeric_dump');$a->query('CREATE TABLE numeric_restore LIKE numeric_dump');$a->query(str_replace('INSERT INTO `numeric_dump`','INSERT INTO `numeric_restore`',$script));same($expected,$a->records('SELECT * FROM numeric_restore'));});

   test('MySQL cache namespace isolates different configured SQL modes',function()use($settings,$schema){$shared=new myCache_File(directory('mode-cache'));$legacy=new DbReviewMySQL($settings['host'],$settings['user'],$settings['password'],'utf8mb4');$concat=new DbReviewMySQL($settings['host'],$settings['user'],$settings['password'],'utf8mb4',['sql_mode'=>'PIPES_AS_CONCAT']);try{$legacy->connect(false,$schema);$concat->connect(false,$schema);$legacy->setCache($shared);$concat->setCache($shared);$sql="SELECT 'a' || 'b'";same('0',$legacy->result($sql));same('ab',$concat->result($sql));}finally{$legacy->close();$concat->close();}});

   test('MySQL stream already at EOF binds empty BLOB rather than NULL',function()use($a){$stream=fopen('php://temp','w+b');fwrite($stream,'consumed');fread($stream,1);check(feof($stream));try{$a->execute('SELECT ?',[$stream],'b');same([''], $a->getRS(1));$a->free();}finally{fclose($stream);}});

  }finally{if($a)$a->close();if($b)$b->close();$admin->query('DROP DATABASE `'.$schema.'`');$admin->close();}
 }
}finally{cleanup($scratch);}
echo 'Passed: '.$passed.'; Failed: '.$failed.PHP_EOL;exit($failed?1:0);
