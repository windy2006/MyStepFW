<?php
/** 执行流程专项：真实核心类配合临时应用、配置和独立子进程，不连接业务数据库。 */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$source=dirname(__DIR__, 2).'/';$override=rtrim(getenv('MYSTEP_EXECUTION_LIB') ?: '', '/');
$library=$source.'lib/';set_include_path(($override!==''?$override.PATH_SEPARATOR:'').$library.PATH_SEPARATOR.get_include_path());
$scratch=sys_get_temp_dir().'/mystep-execution-'.bin2hex(random_bytes(6));mkdir($scratch,0700);
define('ROOT',$scratch.'/');define('LIB',$library);define('APP',ROOT.'app/');define('CONFIG',ROOT.'config/');define('PLUGIN',ROOT.'plugin/');define('CACHE',ROOT.'cache/');define('ROOT_WEB','/sub/');define('VENDOR',$source.'vendor/');
foreach([APP.'myStep',APP.'Demo',CONFIG.'method_alias',PLUGIN,CACHE] as $path)mkdir($path,0700,true);
$_SERVER+=['DOCUMENT_ROOT'=>'/','REQUEST_TIME'=>time(),'HTTP_HOST'=>'fixture.test','REQUEST_METHOD'=>'GET','SCRIPT_NAME'=>'/sub/index.php','REQUEST_URI'=>'/sub/','QUERY_STRING'=>''];
spl_autoload_register(function($name)use($override,$library){foreach([$override.'/'.$name.'.class.php',$library.$name.'.class.php',$library.'database/'.$name.'.class.php'] as $file)if(is_file($file)){require_once $file;return;}});
require_once ($override!==''?$override.'/':LIB).'myStep.class.php';
set_error_handler(function($n,$s,$f,$l){if(error_reporting()&$n)throw new ErrorException($s,0,$n,$f,$l);});
$clean=function($path)use(&$clean){if(is_link($path)||is_file($path)){unlink($path);return;}foreach(new FilesystemIterator($path) as $file)$clean($file->getPathname());rmdir($path);};register_shutdown_function(function()use($scratch,$clean){$clean($scratch);});
foreach(['myStep','Demo'] as $app)file_put_contents(APP.$app.'/info.php','<?php return ["app"=>'.var_export($app,true).'];');
file_put_contents(CONFIG.'config.php','<?php $GLOBALS["config_reads"]=($GLOBALS["config_reads"] ?? 0)+1;return ["gen"=>["path_admin"=>"console"],"upload"=>["free_dl"=>false]];');
file_put_contents(APP.'myStep/route.php','<?php $rule=[["/console/[any]","frameworkHandler"]];');file_put_contents(APP.'Demo/route.php','<?php $rule=[["/demo/[int]","demoHandler"]];');
$ms_setting=new myConfig(CONFIG.'config.php');$domain=[];
if(($argv[1]??'')==='--settings'){
    file_put_contents(CONFIG.'config.php','<?php include '.var_export($source.'config/config_default.php',true).';return $setting;');
    myStep::$url_prefix='/sub/';myStep::setting('myStep');
}
if(($argv[1] ?? '')==='--buffer'){
    ob_start(null,0,PHP_OUTPUT_HANDLER_CLEANABLE | PHP_OUTPUT_HANDLER_FLUSHABLE);
    try{myController::file(APP.'Demo/info.php');}catch(RuntimeException $error){echo 'buffer-ok';exit;}
    throw new RuntimeException('Non-removable response buffer was not rejected');
}
if(($argv[1] ?? '')==='--power'){
    $name=$argv[2];mkdir(APP.$name);file_put_contents(APP.$name.'/'.$name.'.class.php',$name==='InheritedCore'?'<?php class InheritedCore extends myStep {}':'<?php class ParentCore extends myStep {public static function checkPower($idx){return parent::checkPower($idx);}}');$_GET=['core'=>$name];
    echo json_encode([myStep::checkPower('upload'),myStep::checkPower('download')]);exit;
}
if(in_array($argv[1] ?? '', ['--opcache','--opcache-single'], true)){
    if(!function_exists('opcache_compile_file') || !opcache_get_status())throw new RuntimeException('OPcache unavailable');
    $route=APP.'Demo/route.php';if(!opcache_compile_file($route))throw new RuntimeException('Cannot cache route');if($argv[1]==='--opcache-single')myRouter::checkRoute(CONFIG.'route.php',$route,'Demo');else myStep::refreshRoutes();
    file_put_contents($route,'<?php $rule=[["/changed/[int]","demoHandler"]];');clearstatcache();if($argv[1]==='--opcache-single')myRouter::checkRoute(CONFIG.'route.php',$route,'Demo');else myStep::refreshRoutes();include CONFIG.'route.php';
    if($rule_list['Demo'][0][0]!=='/changed/[int]')throw new RuntimeException('Route bytecode remained stale');echo 'opcache-ok';exit;
}
function same($expected,$actual){if($expected!==$actual)throw new RuntimeException('Unexpected value: '.var_export($actual,true));}
function raises($class,$run){try{$run();}catch(Throwable $e){if($e instanceof $class)return;throw $e;}throw new RuntimeException('Expected '.$class);}
$passed=$failed=0;
function test($name,$run){global $passed,$failed;try{$run();$passed++;echo 'PASS '.$name.PHP_EOL;}catch(Throwable $e){$failed++;echo 'FAIL '.$name.': '.$e->getMessage().PHP_EOL;}}
function child(array $args){$pipes=[];$p=proc_open(array_merge([PHP_BINARY],$args),[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);if(!is_resource($p))throw new RuntimeException('Cannot start child');fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$code=proc_close($p);if($code!==0)throw new RuntimeException('Child failed with status '.$code);same('',$err);return $out;}
class OptionalFactory extends myBase {public $calls=0,$value='pending';public function init($v='ready'){$this->calls++;$this->value=$v;}}
class RequiredFactory extends myBase {public $value='pending';public function init($v){$this->value=$v;}}
class ConstructorFactory {public $value='pending';public function __construct($value='ready'){$this->value=$value;}}
class RetryingFactory extends myBase {public static $fail=true;public $ready=false;public function init(){if(self::$fail)throw new RuntimeException('Not ready');$this->ready=true;}}
class AliasFactory extends myBase {use myTrait;public function lower($value){return strtolower($value);}}
class HookTarget {public $calls=[];public function handle($value){$this->calls[]=$value;return $value.'!';}public static function upper($v){return strtoupper($v);}}
class FactoryController extends myController {}
class ConstructorWithInit extends myBase {public $constructed=0,$initialized=0,$value;public function __construct($value){$this->constructed++;parent::__construct($value);}public function init($value){$this->initialized++;$this->value=$value;}}
class NonInvokableAlias extends myBase {public static $aliases=[];public static function regAlias($list){self::$aliases=$list;}}
$controller=new FactoryController();
test('factory initializes a new zero-argument optional initializer',function(){ $o=myController::getInstance('OptionalFactory');same('ready',$o->value);same(1,$o->calls);});
test('factory keeps required initializers deferred without arguments',function(){same('pending',myController::getInstance('RequiredFactory')->value);same('explicit',myController::getInstance('RequiredFactory','explicit')->value);});
test('factory invokes a public optional constructor without arguments',function(){same('ready',myController::getInstance('ConstructorFactory')->value);});
test('new factory instances execute custom constructors before their initializer',function(){$o=myController::getInstance('ConstructorWithInit','value');same(1,$o->constructed);same(1,$o->initialized);same('value',$o->value);});
test('singleton construction happens once and explicit reinitialization remains available',function()use($controller){$controller->setSingleton(true);try{$a=myController::getInstance('OptionalFactory');$b=myController::getInstance('OptionalFactory');same($a,$b);same(1,$a->calls);same($a,myController::getInstance('OptionalFactory','updated'));same(2,$a->calls);same('updated',$b->value);}finally{$controller->setSingleton(false);}});
test('failed singleton initialization never publishes a half-ready object',function()use($controller){$controller->setSingleton(true);try{RetryingFactory::$fail=true;raises('RuntimeException',function(){myController::getInstance('RetryingFactory');});RetryingFactory::$fail=false;same(true,myController::getInstance('RetryingFactory')->ready);}finally{$controller->setSingleton(false);}});
test('framework factory loads configured method aliases',function(){file_put_contents(CONFIG.'method_alias/AliasFactory.php','<?php return ["upper"=>"lower"];');$o=myStep::getInstance('AliasFactory');same('abc',$o->upper('ABC'));});
test('framework aliases also work for non-invokable classes',function(){file_put_contents(CONFIG.'method_alias/NonInvokableAlias.php','<?php return ["short"=>"long"];');$o=myStep::getInstance('NonInvokableAlias');same(['short'=>'long'],NonInvokableAlias::$aliases);});
test('framework factory accepts ordinary constructor-based objects',function(){same('ready',myStep::getInstance('ConstructorFactory')->value);});
test('object method callback is registered as one hook',function(){$c=new FactoryController();$t=new HookTarget();$c->setFunction('page',[$t,'handle']);same('text!',$c->run('page',false,'text'));same(['text'],$t->calls);});
test('static method callback is registered as one hook',function(){$c=new FactoryController();$c->setFunction('page',['HookTarget','upper']);same('TEXT',$c->run('page',false,'text'));});
test('hook callback lists retain declaration and reverse order',function(){$c=new FactoryController();$c->setFunction('page',[function($v){return $v.'a';},function($v){return $v.'b';}]);same('xab',$c->run('page',false,'x'));same('xba',$c->run('page',true,'x'));});
foreach(['', '0',0,false,null] as $empty)test('explicit pipeline input retains '.var_export($empty,true),function()use($empty){$c=new FactoryController();$seen=[];$c->setFunction('page',function($v)use(&$seen){$seen[]=$v;return $v;});same($empty,$c->run('page',false,$empty));same([$empty],$seen);});
test('pipeline remains a pipeline after a hook returns an empty string',function(){$c=new FactoryController();$c->setFunction('page',[function($v){return '';},function($v){same('',$v);return 'restored';}]);same('restored',$c->run('page',false,'body'));});
test('lifecycle hooks without an explicit payload still receive the controller',function(){$c=new FactoryController();$seen=null;$c->setFunction('start',function($value)use(&$seen){$seen=$value;});same('',$c->run('start'));same($c,$seen);});
test('registered file modules still import requested globals',function(){$GLOBALS['module_fixture']='visible';$file=CONFIG.'module.php';file_put_contents($file,'<?php $GLOBALS["module_seen"]=$module_fixture;');myController::regModule('fixture_file',$file);myController::module('fixture_file','module_fixture');same('visible',$GLOBALS['module_seen']);});
test('registered closure modules execute as callbacks',function(){$GLOBALS['module_calls']=0;myController::regModule('fixture_callback',function(){$GLOBALS['module_calls']++;});myController::module('fixture_callback');same(1,$GLOBALS['module_calls']);});
test('removed registered module files follow the missing-module fallback',function(){$file=CONFIG.'removed-module.php';file_put_contents($file,'<?php');myController::regModule('removed_module',$file);unlink($file);myController::$goto_url='';try{myController::module('removed_module');same('/',myController::$goto_url);}finally{myController::$goto_url='';}});
test('route refresh reads the base configuration once per rebuild',function(){$GLOBALS['config_reads']=0;myStep::refreshRoutes();same(1,$GLOBALS['config_reads']);});
test('unchanged route sources never execute route PHP again',function(){file_put_contents(APP.'Demo/route.php','<?php $GLOBALS["route_reads"]=($GLOBALS["route_reads"] ?? 0)+1;$rule=[["/demo/[int]","demoHandler"]];');myStep::refreshRoutes();$before=$GLOBALS['route_reads'];myStep::refreshRoutes();same($before,$GLOBALS['route_reads']);});
test('explicit external route sources participate in invalidation',function(){file_put_contents(CONFIG.'external.php','<?php $rule=[["/external/old","handler"]];');file_put_contents(CONFIG.'route_plugin.php','<?php return ["external"=>'.var_export(CONFIG.'external.php',true).'];');myStep::refreshRoutes();file_put_contents(CONFIG.'external.php','<?php $rule=[["/external/new","handler"]];');myStep::refreshRoutes();include CONFIG.'route.php';same('/external/new',$rule_list['plugin_external'][0][0]);});
test('malformed explicit mappings retain the previous compiled registry',function(){ $before=file_get_contents(CONFIG.'route.php');$mapping=file_get_contents(CONFIG.'route_plugin.php');file_put_contents(CONFIG.'route_plugin.php','<?php return false;');try{raises('UnexpectedValueException',function(){myStep::refreshRoutes();});same($before,file_get_contents(CONFIG.'route.php'));}finally{file_put_contents(CONFIG.'route_plugin.php',$mapping);}});
test('route source failures restore the exact original global configuration',function(){global $ms_setting;$original=$ms_setting;$before=file_get_contents(APP.'Demo/route.php');file_put_contents(APP.'Demo/route.php','<?php throw new RuntimeException("broken route");');try{raises('RuntimeException',function(){myStep::refreshRoutes();});same($original,$ms_setting);}finally{file_put_contents(APP.'Demo/route.php',$before);}});
foreach(['InheritedCore','ParentCore'] as $name)test('permission delegation terminates for '.$name,function()use($name){same('[false,false]',child(['-d','memory_limit=16M',__FILE__,'--power',$name]));});
test('changed route PHP bypasses stale OPcache bytecode',function(){same('opcache-ok',child(array_merge(extension_loaded('Zend OPcache') ? [] : ['-d','zend_extension='.ini_get('extension_dir').'/opcache.so'], ['-d','opcache.enable_cli=1','-d','opcache.file_update_protection=0','-d','opcache.validate_timestamps=0',__FILE__,'--opcache'])));});
test('single-source route synchronization bypasses stale OPcache bytecode',function(){same('opcache-ok',child(array_merge(extension_loaded('Zend OPcache') ? [] : ['-d','zend_extension='.ini_get('extension_dir').'/opcache.so'],['-d','opcache.enable_cli=1','-d','opcache.file_update_protection=0','-d','opcache.validate_timestamps=0',__FILE__,'--opcache-single'])));});
test('public upload limits convert INI units without treating unlimited as a small limit',function(){
    foreach([
        ['512K','2M',0.5,524288],
        ['1G','2G',1024,1073741824],
        ['0','64M',64,67108864],
        ['0','0',32,0]
    ] as $case){
        list($upload,$post,$megabytes,$bytes)=$case;
        $js=child(['-d','upload_max_filesize='.$upload,'-d','post_max_size='.$post,__FILE__,'--settings']);
        if(!preg_match('/\Avar setting = (.*);\z/sD',trim($js),$match))throw new RuntimeException('Unexpected settings response');
        $settings=json_decode($match[1],true);same((float)$megabytes,(float)$settings['max_size']);same($bytes,$settings['max_size_bytes']);
    }
});
test('non-removable response buffers fail instead of spinning forever',function(){same('buffer-ok',child([__FILE__,'--buffer']));});
test('failed scoped rules restore formats for the next registration',function(){
    $router=new myRouter();$file=CONFIG.'bad-rules.php';file_put_contents($file,'<?php $format_list=["Demo"=>["extra"=>"[0-9]+"]];$rule_list=["Demo"=>[["[", "handler"]]];');
    raises('InvalidArgumentException',function()use($router,$file){$router->setRules($file);});raises('InvalidArgumentException',function()use($router){$router->literal('/next/[extra]','handler','myStep');});
});
echo "Passed: $passed; Failed: $failed\n";exit($failed?1:0);
