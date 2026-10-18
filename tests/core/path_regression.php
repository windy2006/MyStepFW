<?php
/** 根相对路径、类索引纯数据解析及跨工作目录加载；只写临时目录。 */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$source=dirname(__DIR__, 2);$scratch=sys_get_temp_dir().'/mystep-path-'.bin2hex(random_bytes(6));mkdir($scratch,0700);
define('ROOT',$scratch.'/');define('LIB',ROOT.'lib/');mkdir(LIB);mkdir(ROOT.'modules');
require $source.'/lib/myPath.class.php';require $source.'/lib/myClassLoader.class.php';require $source.'/lib/myController.class.php';require $source.'/lib/myTrait.class.php';require $source.'/lib/myFile.class.php';
$previous=getcwd();$passed=$failed=0;
function same($a,$b){if($a!==$b)throw new RuntimeException('Unexpected result: '.var_export($b,true));}
function rejects($run){try{$run();}catch(InvalidArgumentException $e){return;}throw new RuntimeException('Expected rejection');}
function test($name,$run){global $passed,$failed;try{$run();$passed++;echo 'PASS '.$name."\n";}catch(Throwable $e){$failed++;echo 'FAIL '.$name.': '.$e->getMessage()."\n";}}
try{
 test('root relative resolution is independent of the working directory',function(){chdir('/');same(ROOT.'lib/',myPath::absolute('lib/'));same('lib/database/',myPath::relative('./lib/other/../database/'));same(ROOT.'new/file.txt',myFile::realPath('new/file.txt'));});
 test('legacy absolute directories become portable relative values',function(){same('lib/',myPath::relative(LIB));same('lib/',myClassLoader::normalize([['path'=>LIB,'ext'=>'.php,.class.php','idx'=>[]]],true)[0]['path']);});
 test('bootstrap migration preserves custom rules and stores PHP arrays',function(){
  $file=ROOT.'classes.php';$data=[['path'=>LIB,'ext'=>'.php','idx'=>['PortableSpecialModule'=>'../modules/different.php']],['path'=>ROOT.'modules/','ext'=>'.php','idx'=>[]]];
  file_put_contents($file,'<?php return '.var_export($data,true).';');
  $loaded=myClassLoader::load($file);same(false,$loaded['reset']);same('lib/',$loaded['rules'][0]['path']);same('modules/',$loaded['rules'][1]['path']);same($loaded['rules'],include $file);
  $bytes=file_get_contents($file);myClassLoader::load($file);same($bytes,file_get_contents($file));same(false,strpos($bytes,ROOT)!==false);
 });
 test('shared class rule saving normalizes values and rejects stale edits',function(){
  $file=ROOT.'saved-classes.php';$rules=[['path'=>LIB,'ext'=>'.php','idx'=>[]]];same(true,myClassLoader::save($file,$rules,'missing'));same('lib/',(include $file)[0]['path']);$before=file_get_contents($file);
  try{myClassLoader::save($file,$rules,'missing');throw new RuntimeException('Expected conflict');}catch(RuntimeException $error){same(409,$error->getCode());}same($before,file_get_contents($file));
  symlink($file,ROOT.'linked-classes.php');rejects(function()use($rules){myClassLoader::save(ROOT.'linked-classes.php',$rules,'missing');});same($before,file_get_contents($file));
 });
 test('special mappings preserve PHP array syntax, strings and namespace keys',function(){foreach([['interface_plugin'=>'../plugin/interface_plugin.class.php'],['Demo\\Module'=>"../modules/引号'文件.php"],[]] as $data)same($data,myClassLoader::mapping(var_export($data,true)));same(['Demo'=>'../modules/file.php'],myClassLoader::mapping('["Demo" => "../modules/file.php",]'));});
 test('previous JSON editor values convert to an ordinary PHP array',function(){same(['Demo'=>'../modules/file.php'],myClassLoader::mapping('{"Demo":"../modules/file.php"}'));});
 test('array text never executes PHP code',function(){foreach(['array("Demo"=>strtolower("FILE"))','array("Demo"=>$GLOBALS["x"])','array("Demo"=>"file.php"); touch("marker");','include "file.php"','array("Demo"=>array())','["file.php"]'] as $value)rejects(function()use($value){myClassLoader::mapping($value);});same(false,is_file('marker'));});
 test('class directory and special file stay within the project',function(){foreach(['../outside','php://filter','/outside'] as $path)rejects(function()use($path){myClassLoader::normalize([['path'=>$path,'ext'=>'.php','idx'=>[]]]);});foreach(['../../outside.php','php://filter','/outside.php'] as $file)rejects(function()use($file){myClassLoader::normalize([['path'=>'lib/','ext'=>'.php','idx'=>['Demo'=>$file]]]);});});
 test('ordinary class and special out of directory module load from another cwd',function(){file_put_contents(LIB.'PortableClass.php','<?php class PortableClass {}');file_put_contents(ROOT.'modules/different.php','<?php class PortableSpecialModule {}');myController::regClass(myClassLoader::normalize([['path'=>'lib/','ext'=>'.php','idx'=>['PortableSpecialModule'=>'../modules/different.php']]],true));same(true,class_exists('PortableClass'));same(true,class_exists('PortableSpecialModule'));});
 test('filesystem settings normalize while URLs and server addresses retain their meaning',function(){same(['template'=>['path'=>'app/Admin/template'], 'session'=>['path'=>'cache/session/'], 'db'=>['type'=>'mysql','host'=>'127.0.0.1:3306']],myPath::settings(['template'=>['path'=>'template'], 'session'=>['path'=>'./cache/session/'], 'db'=>['type'=>'mysql','host'=>'127.0.0.1:3306']],'Admin'));same(':memory:',myPath::settings(['db'=>['type'=>'SQLite','host'=>':memory:']])['db']['host']);});
}finally{chdir($previous);myFile::del($scratch);}
echo "Passed: $passed; Failed: $failed\n";exit($failed?1:0);
