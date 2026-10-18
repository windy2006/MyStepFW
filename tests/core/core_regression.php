<?php
// Run with PHP 7.4. Add --database to exercise the configured MySQL connection.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('ROOT', rtrim(getenv('MYSTEP_ROOT') ?: dirname(__DIR__, 2), '/').'/');
define('LIB', ROOT.'lib/');
define('VENDOR', ROOT.'vendor/');
define('APP', ROOT.'app/');
$_SERVER['DOCUMENT_ROOT'] = '/';
$_SERVER['REQUEST_TIME'] = time();
$_SERVER['SCRIPT_NAME'] = '/index.php';
spl_autoload_register(function ($class) {
    foreach ([LIB.$class.'.class.php', LIB.'cache/'.$class.'.class.php', LIB.'session/'.$class.'.class.php'] as $file) {
        if (is_file($file)) { require_once $file; return; }
    }
});
require_once LIB.'function.php';
require_once LIB.'myDb.class.php';
require_once LIB.'database/interface_db.class.php';
require_once LIB.'database/mysql.class.php';
require_once LIB.'cache/interface_cache.class.php';
require_once LIB.'session/interface_session.class.php';
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line);
});
$scratch = sys_get_temp_dir().'/mystep-core-'.bin2hex(random_bytes(6));
mkdir($scratch, 0700, true);
$passed = $failed = 0;
function same($expected, $actual) {
    if ($expected !== $actual) throw new RuntimeException('Expected '.var_export($expected, true).'; got '.(is_string($actual) && strlen($actual)>100 ? 'string length '.strlen($actual) : var_export($actual, true)));
}
function test($name, $run) {
    global $passed, $failed;
    try { $run(); $passed++; echo 'PASS '.$name.PHP_EOL; }
    catch (Throwable $e) { $failed++; echo 'FAIL '.$name.': '.$e->getMessage().PHP_EOL; }
}
function removeTree($path) {
    if (is_dir($path)) {foreach (new FilesystemIterator($path) as $file) removeTree($file->getPathname()); rmdir($path);}
    elseif (is_file($path)) unlink($path);
}
// Keep output buffered so the actual template/HTTP helpers can be exercised in CLI.
ob_start();
$fileCache = new myCache_File($scratch.'/cache');
foreach ([0, '0', '', [], null] as $i => $value) {
    test('file cache retains value '.$i, function () use ($fileCache, $i, $value) {
        $fileCache->set('empty-'.$i, $value); same($value, $fileCache->get('empty-'.$i));
    });
}
test('file cache deletion and expiry', function () use ($fileCache) {
    $fileCache->set('delete', 'data'); $fileCache->set('delete'); same(false, $fileCache->get('delete'));
    $fileCache->set('expiry', 'data', -1); same(false, $fileCache->get('expiry'));
});
test('file cache uses full key digest', function () use ($fileCache, $scratch) {
    $fileCache->set('long-key', 'value'); $key = md5('long-key');
    same(true, is_file($scratch.'/cache/v2/'.substr($key, 0, 2).'/'.$key));
});
test('corrupt file cache is a miss', function () use ($fileCache, $scratch) {
    $fileCache->set('corrupt', 'value');
    foreach (glob($scratch.'/cache/v2/*/*') as $file) {
        if (basename($file) === md5('corrupt') || basename($file) === substr(md5('corrupt'), 0, 8)) file_put_contents($file, 'broken');
    }
    same(false, $fileCache->get('corrupt'));
});
foreach ([false, 0, '', []] as $i => $value) {
    test('function cache executes once for falsey value '.$i, function () use ($scratch, $value) {
        $cache = new myCache('myCache_File', $scratch.'/func', 0);
        // Named functions are the supported cache keys; Closure serialization is intentionally excluded.
        $GLOBALS['test_calls'] = 0; $GLOBALS['test_value'] = $value;
        same($value, $cache->getData_func('regressionValue', [$value]));
        same($value, $cache->getData_func('regressionValue', [$value]));
        same(1, $GLOBALS['test_calls']);
    });
}
function regressionValue($ignored) {$GLOBALS['test_calls']++; return $GLOBALS['test_value'];}
test('cache query tolerates absent database', function () use ($scratch) {
    $GLOBALS['db'] = null;
    same('', (new myCache('myCache_File', $scratch.'/null-db', 0))->getData('SELECT 1'));
});
test('route parameter preserves equals signs', function () {
    $_SERVER['QUERY_STRING'] = ''; $_SERVER['PATH_INFO'] = ''; $_SERVER['REQUEST_URI'] = '/console/?token=a=b=c';
    $_SERVER['QUERY_STRING'] = 'token=a=b=c';
    $router = new myRouter(['mode'=>'rewrite']); same('a=b=c', $router->info['para']['token']);
});
test('rewrite and legacy route compatibility', function () {
    foreach ([['/console/?x=1', 'x=1'], ['/index.php?/console/&x=1', '/console/&x=1']] as $case) {
        $_SERVER['REQUEST_URI'] = $case[0]; $_SERVER['QUERY_STRING'] = $case[1];
        $router = new myRouter(['mode'=>'rewrite']); same('/console/', $router->route['p']); same('1', $_GET['x']);
    }
});
test('template removeCache removes compiled file', function () use ($scratch) {
    mkdir($scratch.'/tpl'); file_put_contents($scratch.'/tpl/main.tpl', 'Hello <!--name-->');
    $tpl = new myTemplate(['path'=>$scratch.'/tpl', 'path_compile'=>$scratch.'/compiled/', 'name'=>'main']);
    $tpl->assign('name', 'World'); same('Hello World', $tpl->render('', false));
    same(true, is_file($scratch.'/compiled/main.php')); $tpl->removeCache();
    same(false, is_file($scratch.'/compiled/main.php'));
});
test('template if block without else', function () use ($scratch) {
    $tpl = new myTemplate(['path'=>$scratch.'/tpl', 'path_compile'=>$scratch.'/compiled/', 'name'=>'main']);
    same(['<!--if:start-->yes<!--if:end-->', 'yes', ''], $tpl->getBlock('<!--if:start-->yes<!--if:end-->', 'if'));
});
test('session GC tolerates missing directories', function () use ($scratch) {
    $GLOBALS['no_log'] = false; sess_file::$path = $scratch.'/missing'; same(true, sess_file::gc(1800));
});
test('session GC preserves recent sessions in older date directories', function () use ($scratch) {
    $root = $scratch.'/sessions'; mkdir($root.'/20000101', 0700, true); mkdir($root.'/20000102');
    file_put_contents($root.'/20000101/sess_recent', 'recent'); file_put_contents($root.'/20000101/sess_expired', 'expired');
    touch($root.'/20000101/sess_expired', time()-3600); sess_file::$path = $root.'/20000102';
    sess_file::gc(1800); same(true, is_file($root.'/20000101/sess_recent')); same(false, is_file($root.'/20000101/sess_expired'));
});
test('dated session migrates to stable storage', function () use ($scratch) {
    $path = $scratch.'/session-migration'; mkdir($path.'/20000101', 0700, true);
    file_put_contents($path.'/20000101/sess_legacy', 'user|s:5:"admin";');
    same(true, sess_file::open($path, 'test')); same('user|s:5:"admin";', sess_file::read('legacy'));
    same(true, sess_file::write('legacy', 'migrated')); sess_file::close(); same('migrated', file_get_contents($path.'/sess_legacy'));
    same(false, is_file($path.'/20000101/sess_legacy'));
});
test('logout cannot restore a dated session', function () use ($scratch) {
    $path = $scratch.'/session-destroy'; mkdir($path.'/20000101', 0700, true);
    file_put_contents($path.'/20000101/sess_legacy', 'old-admin');
    sess_file::open($path, 'test'); same(true, sess_file::destroy('legacy')); same('', sess_file::read('legacy')); sess_file::close();
});
test('active session is not removed by garbage collection', function () use ($scratch) {
    $path = $scratch.'/session-lock'; sess_file::open($path, 'test'); sess_file::read('active');
    touch($path.'/sess_active', time()-3600); sess_file::gc(1); same(true, is_file($path.'/sess_active')); sess_file::close();
});
test('concurrent session updates are serialized', function () use ($scratch) {
    $path = $scratch.'/session-concurrency'; mkdir($path);
    $worker = $scratch.'/session-worker.php';
    $code = '<?php require '.var_export(LIB.'session/interface_session.class.php', true).'; require '.var_export(LIB.'session/sess_file.class.php', true).';'
        .'$_SERVER["REQUEST_TIME"]=time(); $GLOBALS["no_log"]=false; sess_file::open($argv[1], "test"); $value=sess_file::read("shared"); usleep(100000); sess_file::write("shared", (string)((int)$value+1)); sess_file::close();';
    file_put_contents($worker, $code); $processes = [];
    for($i=0;$i<4;$i++) {
        $pipes = []; $process = proc_open([PHP_BINARY, $worker, $path], [0=>['pipe','r'], 1=>['pipe','w'], 2=>['pipe','w']], $pipes);
        $processes[] = [$process, $pipes];
    }
    foreach($processes as [$process, $pipes]) {
        fclose($pipes[0]); $out=stream_get_contents($pipes[1]); $err=stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
        same(0, proc_close($process)); same('', $err);
    }
    same('4', file_get_contents($path.'/sess_shared'));
});
test('native PHP session protocol persists data', function () use ($scratch) {
    $path = $scratch.'/session-protocol'; $worker = $scratch.'/protocol-worker.php';
    $code = '<?php require '.var_export(LIB.'session/interface_session.class.php', true).'; require '.var_export(LIB.'session/sess_file.class.php', true).';'
        .'$GLOBALS["no_log"]=false; ini_set("session.use_cookies", "0"); session_save_path($argv[1]); session_id("protocol");'
        .'session_set_save_handler("sess_file::open", "sess_file::close", "sess_file::read", "sess_file::write", "sess_file::destroy", "sess_file::gc");'
        .'session_start(); $_SESSION["value"]=($_SESSION["value"]??0)+1; $result=$_SESSION["value"]; session_write_close(); echo $result;';
    file_put_contents($worker, $code);
    foreach(['1', '2'] as $expected) {
        $pipes=[]; $process=proc_open([PHP_BINARY, $worker, $path], [0=>['pipe','r'], 1=>['pipe','w'], 2=>['pipe','w']], $pipes);
        fclose($pipes[0]); $out=stream_get_contents($pipes[1]); $err=stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
        same(0, proc_close($process)); same('', $err); same($expected, $out);
    }
});
foreach (['../../escape.txt'=>'escape.txt', '..\\escape.txt'=>'escape.txt', 'shell.PHP'=>'shell.PHP.upload', 'file.txt'=>'file.txt'] as $name => $expected) {
    test('upload destination '.$name, function () use ($scratch, $name, $expected) {
        file_put_contents($scratch.'/upload-source', 'content');
        $_FILES = ['file'=>['name'=>$name, 'type'=>'text/plain', 'tmp_name'=>$scratch.'/upload-source', 'error'=>0, 'size'=>7]];
        $upload = new myUploader($scratch.'/uploads/', false); $upload->do();
        same($expected, $upload->result[0]['new_name']); $_FILES = [];
    });
}
class CompressionProbe extends myStep {
    public function __construct() {$this->setting = (object)['gen'=>(object)['cache_mode'=>'File']];}
    public function getLanguage($key) {return $key.': ';}
}
foreach (['br', 'gzip;q=0', 'deflate', 'gzip', 'br, gzip;q=0.5', 'x-gzip', '*;q=0.5', 'gzip;q=0, *;q=1'] as $encoding) {
    test('compression negotiation '.$encoding, function () use ($encoding) {
        $_SERVER['HTTP_ACCEPT_ENCODING'] = $encoding; $probe = new CompressionProbe();
        ob_start(); ob_start(); echo 'probe-content'; $probe->gzOut(3); $body = ob_get_clean();
        $compressed = in_array($encoding, ['gzip', 'br, gzip;q=0.5', 'x-gzip', '*;q=0.5'], true);
        if ($compressed) same(true, strpos(gzdecode($body), 'probe-content') === 0);
        else same('probe-content', $body);
    });
}
if (in_array('--database', $argv, true)) {
    $setting = []; include ROOT.'config/config.php'; $options = $setting['db'];
    $db = new MySQL($options['host'], $options['user'], $options['password'], $options['charset']);
    $db->connect(false, $options['name']);
    test('SQL literals are not rewritten', function () use ($db) {
        $value = 'four    spaces (1=1) 1=1 and where 1=1 order CHARSET=utf-8';
        same($value, $db->result("SELECT '".$db->safeValue($value)."' AS literal"));
    });
    $db->setCache($fileCache);
    test('writes invalidate query cache across connections', function () use ($db, $options, $fileCache) {
        // The session-local table is never visible as a persistent business table.
        $db->query('CREATE TEMPORARY TABLE core_regression_value (id int PRIMARY KEY, value int)');
        $db->query('INSERT INTO core_regression_value VALUES (1, 10)');
        $sql = 'SELECT value FROM core_regression_value WHERE id=1'; same('10', $db->result($sql));
        $db->query('UPDATE core_regression_value SET value=20 WHERE id=1'); same('20', $db->result($sql));
        $other = new MySQL($options['host'], $options['user'], $options['password'], $options['charset']);
        $other->connect(false, $options['name']); $other->setCache($fileCache);
        $key = $db->cacheKey('probe'); $other->query('CREATE TEMPORARY TABLE core_regression_other (id int)');
        same(false, $key === $db->cacheKey('probe')); $other->close();
        $db->query('DROP TEMPORARY TABLE core_regression_value');
    });
    test('query cache separates records and scalar results', function () use ($db) {
        $sql = 'SELECT 42 AS answer';
        same(['answer'=>'42'], $db->record($sql)); same('42', $db->result($sql));
        same([['answer'=>'42']], $db->records($sql)); same(['42'], $db->record($sql, 1));
    });
    test('query cache isolates selected database', function () use ($db) {
        $sql = 'SELECT DATABASE() AS name'; $db->selectDB('mystep'); same('mystep', $db->result($sql));
        $db->selectDB('mystep_cms'); same('mystep_cms', $db->result($sql)); $db->selectDB('mystep');
    });
    test('empty query results are cached', function () use ($db) {
        $sql = 'SELECT 1 WHERE 1=0'; $counter = new ReflectionProperty(MySQL::class, 'count'); $counter->setAccessible(true);
        same([], $db->records($sql)); $before = $counter->getValue($db);
        same([], $db->records($sql)); same($before, $counter->getValue($db));
    });
    test('query wrapper separates databases and return formats', function () use ($db, $scratch, $options, $fileCache) {
        $proxy = new myDb('MySQL', $options['host'], $options['user'], $options['password'], $options['charset']);
        $proxy->connect(false, 'mystep'); $GLOBALS['db'] = $proxy; $proxy->setCache($fileCache);
        $cache = new myCache('myCache_File', $scratch.'/query', 0); $sql = 'SELECT DATABASE() AS name';
        same('mystep', $cache->getData($sql, 'result')); same([['name'=>'mystep']], $cache->getData($sql));
        $proxy->selectDB('mystep_cms'); same('mystep_cms', $cache->getData($sql, 'result'));
        $proxy->selectDB('mystep');
        $proxy->query('SET @cache_probe=1'); same('1', $cache->getData('SELECT @cache_probe', 'result'));
        $proxy->query('SET @cache_probe=2'); $cache->getData('SELECT @cache_probe', 'remove');
        same('2', $cache->getData('SELECT @cache_probe', 'result')); same(255, $proxy->cache()); $proxy->close(); $GLOBALS['db'] = $db;
    });
    test('MySQL cache supports empty values and removal', function () use ($options) {
        // Use a connection-local temporary table so the business schema is untouched.
        $connection = new mysqli('127.0.0.1', $options['user'], $options['password'], $options['name'], 3306);
        $connection->query('CREATE TEMPORARY TABLE my_cache (`key` char(32) PRIMARY KEY, expiration int, value text)');
        $reflection = new ReflectionClass('myCache_MySQL'); $cache = $reflection->newInstanceWithoutConstructor();
        $property = $reflection->getProperty('cnt'); $property->setAccessible(true); $property->setValue($cache, $connection);
        foreach ([0, [], '', null] as $i=>$value) {$cache->set('probe-'.$i, $value); same($value, $cache->get('probe-'.$i));}
        $cache->remove('probe-0'); same(false, $cache->get('probe-0'));
        $cache->set('probe-1'); same(false, $cache->get('probe-1')); $connection->close();
    });
    $db->close();
}
$output = ob_get_clean(); removeTree($scratch); echo $output;
echo "Passed: $passed; Failed: $failed".PHP_EOL;
exit($failed ? 1 : 0);
