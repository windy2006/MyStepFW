<?php
if(PHP_SAPI !== 'cli'){http_response_code(404);exit;}
$scratch=sys_get_temp_dir().'/mystep-flow-'.bin2hex(random_bytes(6));mkdir($scratch,0700);
define('ROOT',dirname(__DIR__, 2).'/');define('LIB',ROOT.'lib/');define('VENDOR',ROOT.'vendor/');define('ROOT_WEB',getenv('FLOW_BASE') ?: '/');
define('CONFIG',$scratch.'/config/');define('APP',$scratch.'/app/');define('PLUGIN',$scratch.'/plugin/');define('CACHE',$scratch.'/cache/');
foreach([CONFIG,APP,PLUGIN,CACHE] as $path)mkdir($path);
$_SERVER['DOCUMENT_ROOT']='/';$_SERVER['HTTP_HOST']='mystep.com';$_SERVER['REQUEST_TIME']=time();
spl_autoload_register(function($name){foreach([LIB.$name.'.class.php',LIB.'database/'.$name.'.class.php'] as $file)if(is_file($file)){require_once $file;return;}});
require_once LIB.'myStep.class.php';
set_error_handler(function($severity,$message,$file,$line){if(error_reporting()&$severity)throw new ErrorException($message,0,$severity,$file,$line);});
$passed=$failed=0;
function same($expected,$actual){if($expected!==$actual)throw new RuntimeException('Expected '.var_export($expected,true).'; got '.var_export($actual,true));}
function test($name,$run){global $passed,$failed;try{$run();$passed++;echo 'PASS '.$name.PHP_EOL;}catch(Throwable $error){$failed++;echo 'FAIL '.$name.': '.$error->getMessage().PHP_EOL;}}
file_put_contents(CONFIG.'config.php','<?php return ["gen"=>["path_admin"=>"console"],"web"=>["title"=>"base"]];');
foreach(['myStep','CMS'] as $app){mkdir(APP.$app);file_put_contents(APP.$app.'/info.php','<?php return ["app"=>'.var_export($app,true).'];');}
file_put_contents(APP.'myStep/route.php','<?php $rule=[["/console/[any]", "frameworkHandler"]];$api=["upload"=>"uploadHandler"];');
file_put_contents(APP.'CMS/config.php','<?php return ["web"=>["path_admin"=>"cms_admin"]];');
file_put_contents(APP.'CMS/route.php','<?php $rule=[["/".$GLOBALS["ms_setting"]->web->path_admin."/[any]", "cmsHandler"]];');
$ms_setting=new myConfig(CONFIG.'config.php');
test('cold registry includes application rules and correct configuration context',function(){global $ms_setting;myStep::refreshRoutes();include CONFIG.'route.php';same('/cms_admin/[any]',$rule_list['CMS'][0][0]);same(['upload'=>'uploadHandler'],$api_list['myStep']);same('base',$ms_setting->web->title);same(false,isset($ms_setting->web->path_admin));});
test('unchanged sources skip route compilation',function(){touch(CONFIG.'route.php',1000000000);myStep::refreshRoutes();clearstatcache(true,CONFIG.'route.php');same(1000000000,filemtime(CONFIG.'route.php'));});
test('same-size edits within the same timestamp invalidate registry',function(){$file=APP.'myStep/route.php';$mtime=filemtime($file);$source=file_get_contents($file);file_put_contents($file,str_replace('/console/','/updated/',$source));touch($file,$mtime);myStep::refreshRoutes();include CONFIG.'route.php';same('/updated/[any]',$rule_list['myStep'][0][0]);same('/cms_admin/[any]',$rule_list['CMS'][0][0]);});
test('missing compiled registry rebuilds on first request',function(){unlink(CONFIG.'route.php');myStep::refreshRoutes();same(true,is_file(CONFIG.'route.php'));});
test('bound domains and root origin can access credentialed API',function(){$domains=['api.mystep.com'=>'/api/(.*)','cms.mystep.com'=>'CMS'];same(true,myStep::apiOriginAllowed('https://mystep.com','https://api.mystep.com',$domains,'.mystep.com'));same(true,myStep::apiOriginAllowed('https://cms.mystep.com','https://api.mystep.com',$domains,'.mystep.com'));});
test('foreign, downgraded and malformed origins are rejected',function(){foreach(['https://evil.test','http://mystep.com','https://mystep.com:8443','https://mystep.com/anything','null',"https://mystep.com\r\nInjected: yes"] as $origin)same(false,myStep::apiOriginAllowed($origin,'https://api.mystep.com',[],'.mystep.com'));});
test('additional origins require explicit configuration',function(){same(true,myStep::apiOriginAllowed('https://client.test','https://api.mystep.com',[],'',['https://client.test']));});
test('core class resolver rejects path traversal and array parameters',function(){global $domain;$domain=[];foreach(['../lib/myFile',['CMS'],'Missing'] as $value){$_GET=['core'=>$value];same('myStep',myStep::getCore());}});
test('core class resolver requires a framework subclass',function(){mkdir(APP.'InvalidCore');file_put_contents(APP.'InvalidCore/InvalidCore.class.php','<?php class InvalidCore {}');$_GET=['core'=>'InvalidCore'];same('myStep',myStep::getCore());mkdir(APP.'ValidCore');file_put_contents(APP.'ValidCore/ValidCore.class.php','<?php class ValidCore extends myStep {}');$_GET=['core'=>'ValidCore'];same('ValidCore',myStep::getCore());});
test('URL generation preserves query values and fragments',function(){global $ms_setting;$ms_setting->router->mode='rewrite';same(ROOT_WEB.'console?next=https://other.test/a//b#section',myStep::setURL('console?next=https://other.test/a//b#section'));same('https://external.test/a//b',myStep::setURL('https://external.test/a//b'));});
test('URL generation supports query and PATH_INFO modes',function(){global $ms_setting;$ms_setting->router->mode='query_string';same(ROOT_WEB.'index.php?/console&x=1',myStep::setURL('console?x=1'));same(ROOT_WEB.'index.php?',myStep::setURL());$ms_setting->router->mode='path_info';same(ROOT_WEB.'index.php/console?x=1',myStep::setURL('console?x=1'));$ms_setting->router->mode='rewrite';same(ROOT_WEB.'console',myStep::setURL(ROOT_WEB.'console'));});
function removeTree($path){if(is_file($path)||is_link($path)){unlink($path);return;}foreach(new FilesystemIterator($path) as $file)removeTree($file->getPathname());rmdir($path);}
removeTree($scratch);echo "Passed: $passed; Failed: $failed\n";exit($failed?1:0);
