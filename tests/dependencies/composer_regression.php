<?php
// Dependency integration checks use private cache paths; no application data is changed.
if(PHP_SAPI !== 'cli') {http_response_code(404);exit;}
define('ROOT',rtrim(getenv('MYSTEP_ROOT') ?: dirname(__DIR__, 2),'/').'/');define('LIB',ROOT.'lib/');define('VENDOR',ROOT.'vendor/');define('ROOT_WEB','/');
$_SERVER['DOCUMENT_ROOT']='/';$_SERVER['REQUEST_TIME']=time();$_SERVER['HTTP_HOST']='mystep.com';
spl_autoload_register(function($class) {$p=LIB.$class.'.class.php';if(is_file($p))require_once $p;});
require VENDOR.'autoload.php';require LIB.'function.php';
set_error_handler(function($n,$m,$f,$l) {if(error_reporting() & $n)throw new ErrorException($m,0,$n,$f,$l);});
if(($argv[1]??'')==='--minify') {$v=json_decode(stream_get_contents(STDIN),true);$m=new myMinify('js');$m->add($v['code']);echo json_encode(['normal'=>$m->get(),'legacy'=>$m->get(true)]);exit;}
$scratch=sys_get_temp_dir().'/mystep-composer-'.bin2hex(random_bytes(6));mkdir($scratch,0700);$passed=$failed=0;ob_start();
function same($a,$b) {if($a!==$b)throw new RuntimeException('Values differ');}
function check($v) {if(!$v)throw new RuntimeException('Assertion failed');}
function test($name,$fn) {global $passed,$failed;try {$fn();$passed++;echo 'PASS '.$name.PHP_EOL;}catch(Throwable $e) {$failed++;echo 'FAIL '.$name.': '.$e->getMessage().PHP_EOL;}}
function rmTree($p) {if(is_link($p)||is_file($p)) {unlink($p);return;}if(is_dir($p)) {foreach(new FilesystemIterator($p)as$f)rmTree($f->getPathname());rmdir($p);}}
try {
 // 发布源码不携带本机更新校验清单；缺省在隔离副本中用真实生成器建立测试清单。
 $manifestFile=ROOT.'plugin/manager/check_file.php';
 if(!is_file($manifestFile)) {
  $release=$scratch.'/release/';mkdir($release.'plugin/manager',0700,true);
  $members=[ROOT.'composer.json',ROOT.'composer.lock',VENDOR.'autoload.php'];
  foreach(['composer','erusev/parsedown','mrclay/jsmin-php','phrozenbyte/cssmin'] as $directory) {
   foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(VENDOR.$directory,FilesystemIterator::SKIP_DOTS)) as $file)
    if($file->isFile()&&$file->getFilename()!=='.DS_Store')$members[]=$file->getPathname();
  }
  foreach($members as $file) {$target=$release.substr($file,strlen(ROOT));if(!is_dir(dirname($target)))mkdir(dirname($target),0700,true);copy($file,$target);}
  copy(ROOT.'plugin/manager/class.php',$release.'plugin/manager/class.php');
  $worker=<<<'PHP'
define('ROOT',$argv[1]);define('LIB',$argv[2].'lib/');define('PLUGIN',ROOT.'plugin/');
require LIB.'function.php';spl_autoload_register(function($name){$file=LIB.$name.'.class.php';if(is_file($file))require_once $file;});
require $argv[2].'plugin/interface_plugin.class.php';require PLUGIN.'manager/class.php';
if(plugin_manager::checkFile(ROOT,true)!==true)throw new RuntimeException('Cannot generate isolated dependency manifest');
PHP;
  $pipes=[];$process=proc_open([PHP_BINARY,'-r',$worker,$release,ROOT],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
  if(!is_resource($process))throw new RuntimeException('Cannot start dependency manifest fixture');
  fclose($pipes[0]);$output=stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
  if(proc_close($process)!==0)throw new RuntimeException('Dependency manifest fixture failed: '.$output);
  $manifestFile=$release.'plugin/manager/check_file.php';
 }
 test('production lock contains only three stable runtime packages',function() {$d=json_decode(file_get_contents(ROOT.'composer.lock'),true);$names=array_column($d['packages'],'name');sort($names);same(['erusev/parsedown','mrclay/jsmin-php','phrozenbyte/cssmin'],$names);foreach($d['packages']as$p)check(strpos($p['version'],'dev')===false);same([],$d['packages-dev']);});
 test('PHP constraint and resolution platform match supported PHP 7.4 baseline',function() {$d=json_decode(file_get_contents(ROOT.'composer.json'),true);same('^7.4',$d['require']['php']);same('7.4.0',$d['config']['platform']['php']);});
 test('removed packer has no eager autoload or installed class',function() {same(false,class_exists('JavaScriptPacker'));same(false,Composer\InstalledVersions::isInstalled('meenie/javascript-packer'));});
 test('installed versions and lock agree',function() {$d=json_decode(file_get_contents(ROOT.'composer.lock'),true);foreach($d['packages']as$p) {same($p['version'],Composer\InstalledVersions::getPrettyVersion($p['name']));same($p['source']['reference'],Composer\InstalledVersions::getReference($p['name']));}});
 test('stable Markdown preserves headings lists links tables and fenced code',function() {$md=new Parsedown();$v=$md->text("# 标题\n\n- 一\n- 二\n\n[官网](https://example.test)\n\n|甲|乙|\n|-|-|\n|1|2|\n\n```php\necho 1;\n```");foreach(['<h1>标题</h1>','<li>一</li>','href="https://example.test"','<table>','language-php']as$s)check(strpos($v,$s)!==false);});
 test('stable Markdown safe mode escapes nested HTML and rejects javascript links',function() {$md=(new Parsedown())->setSafeMode(true);$v=$md->text("> <img src=x onerror=alert(1)>\n\n[x](javascript:alert(1))");check(strpos($v,'<img')===false);check(strpos($v,'href="javascript:')===false);});
 test('framework guide executes updated stable Markdown API',function() {require_once ROOT.'app/myStep/MyStepGuide.class.php';$reader=new MyStepGuide('/console/guide');$page=$reader->page('overview');check(is_string($page['html'])&&strlen($page['html'])>100);check(strpos($page['html'],'<h1')!==false);check(count($page['groups'])>0);check(count($page['outline'])>0);});
 test('CMS guide executes updated Markdown API and builds heading anchors',function() {$t=new class {public $values=[];public function assign($k,$v) {$this->values[$k]=$v;}};include ROOT.'app/CMS/template/guide/module/index.php';check(isset($t->values['content']));check(strpos($t->values['content'],'id="p1"')!==false);});
 test('legacy pack flag keeps executable ordinary JavaScript without eval wrapper',function() {$m=new myMinify('js');$m->add('var value = 1 + 2;');$v=$m->get();same($v,$m->get(true));check(strpos($v,'eval(')===false);same(myMinify::minify('var value = 1 + 2;'),$v);});
 test('cached pack compatibility reuses one result without rewriting output',function() use($scratch) {$p=$scratch.'/out.js';$m=new myMinify('js',$p);$m->add('var value = 3;');$v=$m->get();touch($p,time()-60);clearstatcache(true,$p);$stamp=filemtime($p);$before=hash_file('sha256',$p);same($v,$m->get(true));clearstatcache(true,$p);same($stamp,filemtime($p));same($before,hash_file('sha256',$p));$meta=json_decode(file_get_contents($p.'.meta'),true);same(false,$meta['pack']);});
 test('legacy minifier metadata is invalidated and rebuilt from supplied sources',function() use($scratch) {$p=$scratch.'/old.js';file_put_contents($p,'eval("old")');file_put_contents($p.'.meta',json_encode(['engine'=>'old','output'=>hash_file('sha256',$p),'pack'=>true]));$m=new myMinify('js',$p);same(false,$m->check());$m->add('var newValue = 4;');check(strpos($m->get(true),'eval(')===false);check($m->check());});
 test('CSS minification preserves custom properties calc strings and URL values',function() {$v=myMinify::minify(':root { --gap: 4px; } .x { margin: calc(100% - var(--gap)); content: "a b"; background: url("https://example.test/a.png?q=1&b=2"); }','css');foreach(['--gap:4px','calc(100% - var(--gap))','"a b"','https://example.test/a.png?q=1&b=2']as$s)check(strpos($v,$s)!==false);});
 test('controller and Document minifier callers use ordinary minification',function() {foreach(['lib/myController.class.php','app/Document/module/myMinify.php']as$f) {$s=file_get_contents(ROOT.$f);check(strpos($s,'$minify->show(true)')===false);check(strpos($s,'$minify->get(true)')===false);}});
 test('update manifest contains no retired Packer or Parsedown development paths',function() use($manifestFile) {include $manifestFile;same(count($list_file),count($list_file_md5));foreach($list_file as $path) {check(strpos($path,'/vendor/meenie/javascript-packer/')!==0);check(strpos($path,'/vendor/erusev/parsedown/src/')!==0);}});
 test('update manifest matches deployable Composer-managed file hashes',function() use($manifestFile) {include $manifestFile;$map=array_combine($list_file,$list_file_md5);$files=[ROOT.'vendor/autoload.php',ROOT.'composer.json',ROOT.'composer.lock'];foreach(['composer','erusev/parsedown','mrclay/jsmin-php','phrozenbyte/cssmin'] as $dir) {$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator(VENDOR.$dir,FilesystemIterator::SKIP_DOTS));foreach($it as $f)if($f->isFile()&&$f->getFilename()!=='.DS_Store')$files[]=$f->getPathname();}foreach($files as $file) {$path='/'.substr($file,strlen(ROOT));if(preg_match('#(?:^|/)\.[^/]+#',$path))continue;if(!isset($map[$path]))throw new RuntimeException("Missing dependency manifest member: ".$path);same(md5_file($file),$map[$path]);}});
 test('new behavior and compatibility parameters have method documentation',function() {foreach(['signature','source','atomicWrite','get','show']as$n) {$m=new ReflectionMethod(myMinify::class,$n);$d=$m->getDocComment();check($d!==false&&strpos($d,'@return')!==false);foreach($m->getParameters()as$p)check(strpos($d,'$'.$p->name)!==false);}});
}finally {rmTree($scratch);}
echo 'Passed: '.$passed.'; Failed: '.$failed.PHP_EOL;$output=ob_get_clean();echo $output;exit($failed?1:0);
