<?php
/** 插件运行链路回归：临时注册表、应用及前置脚本，使用真实核心类；不更改网站插件状态。 */
if(PHP_SAPI !== 'cli') {http_response_code(404);exit;}
$source=dirname(__DIR__,2).'/';$scratch=sys_get_temp_dir().'/mystep-plugin-runtime-'.bin2hex(random_bytes(6));mkdir($scratch,0700);
define('VENDOR',$source.'vendor/');define('ROOT',$scratch.'/');define('LIB',$source.'lib/');define('APP',ROOT.'app/');define('PLUGIN',ROOT.'plugin/');define('CONFIG',ROOT.'config/');define('CACHE',ROOT.'cache/');define('ROOT_WEB','/sub/');define('PATH',APP.'Host/');
foreach([PATH,APP.'test_helper',PLUGIN,CONFIG.'priority',CACHE] as $dir)mkdir($dir,0700,true);
spl_autoload_register(function($class){foreach([LIB.$class.'.class.php',LIB.'database/'.$class.'.class.php'] as $file)if(is_file($file)){require_once $file;return;}});
require_once LIB.'function.php';require_once LIB.'myStep.class.php';
set_error_handler(function($n,$s,$f,$l){if(error_reporting()&$n)throw new ErrorException($s,0,$n,$f,$l);});
file_put_contents(CONFIG.'config.php','<?php return ["gen"=>["debug"=>false]];');file_put_contents(CONFIG.'class_alias.php','<?php return [];');
file_put_contents(PATH.'info.php','<?php return ["app"=>"Host"];');file_put_contents(APP.'test_helper/info.php','<?php return [];');
$ms_setting=new myConfig(CONFIG.'config.php');
$registry=new myDb('simpleDB','plugin',PLUGIN);$registry->create([['order',12],['active',1],['idx',40]]);
$rows=[['order'=>1,'active'=>1,'idx'=>'low'],['order'=>20,'active'=>1,'idx'=>'high'],['order'=>20,'active'=>1,'idx'=>'same_order'],['order'=>100,'active'=>0,'idx'=>'disabled'],['order'=>5,'active'=>1,'idx'=>'../invalid']];
foreach(['low','high','same_order','disabled'] as $name){mkdir(PLUGIN.$name);file_put_contents(PLUGIN.$name.'/info.php','<?php return [];');file_put_contents(PLUGIN.$name.'/index.php','<?php');file_put_contents(PLUGIN.$name.'/route.php','<?php $rule=[["/'.$name.'", "handler"]];$api=["status"=>"handler"];');}
function writeRows($rows){global $registry;$registry->empty();foreach($rows as $row)$registry->insert($row,true);}
function same($a,$b){if($a!==$b)throw new RuntimeException('Expected '.var_export($a,true).'; got '.var_export($b,true));}
$passed=$failed=0;
function test($name,$run){global $passed,$failed;try{$run();$passed++;echo 'PASS '.$name.PHP_EOL;}catch(Throwable $e){$failed++;echo 'FAIL '.$name.': '.$e->getMessage().PHP_EOL;}}
class CollectedPlugins extends RuntimeException {}
class PriorityComplete extends RuntimeException {}
class PluginHostProbe extends myStep {
    public $seen=[];
    public function __construct(){$this->setting=$GLOBALS['ms_setting'];}
    public function regPlugin($plugin,&$info=[]){$this->seen[]=basename($plugin);if(count($this->seen)===3)throw new CollectedPlugins();return $this;}
}
try {
    writeRows($rows);
    test('active registry orders descending and keeps ties stable',function(){same(['high','same_order','low'],array_keys(myStep::activePlugins()));});
    test('same-request registry edits invalidate active cache',function()use($rows){$change=$rows;$change[0]['active']=0;writeRows($change);same(['high','same_order'],array_keys(myStep::activePlugins()));writeRows($rows);same(['high','same_order','low'],array_keys(myStep::activePlugins()));});
    test('host binding filters scope without changing global order or initializing duplicates',function(){
        file_put_contents(PATH.'plugin.php','<?php return ["low","low","disabled",["invalid"],"same_order","high"];');$host=new PluginHostProbe();
        try{$host->start();}catch(CollectedPlugins $e){}finally{restore_error_handler();}
        same(['high','same_order','low'],$host->seen);
    });
    test('priority scripts preserve underscore identifiers and skip disabled plugins',function()use($rows){
        $withEnd=$rows;$withEnd[]=['order'=>-99,'active'=>1,'idx'=>'finish'];writeRows($withEnd);mkdir(PLUGIN.'finish');file_put_contents(PLUGIN.'finish/info.php','<?php return [];');
        foreach(['low','high','same_order','disabled'] as $name)file_put_contents(CONFIG.'priority/plugin_'.$name.'.php','<?php $GLOBALS["priority_seen"][]='.var_export($name,true).';');
        file_put_contents(CONFIG.'priority/app_test_helper.php','<?php $GLOBALS["priority_seen"][]="app_test_helper";');
        file_put_contents(CONFIG.'priority/plugin_finish.php','<?php throw new PriorityComplete();');$GLOBALS['priority_seen']=[];
        try{myStep::go();throw new RuntimeException('Priority sentinel was skipped');}catch(PriorityComplete $e){}
        same(['app_test_helper','high','same_order','low'],$GLOBALS['priority_seen']);writeRows($rows);
    });
    test('explicit plugin mappings cannot restore disabled routes or APIs',function(){
        file_put_contents(CONFIG.'outside.php','<?php $rule=[["/external", "externalHandler"]];');
        file_put_contents(CONFIG.'route_plugin.php','<?php return '.var_export(['disabled'=>PLUGIN.'disabled/route.php','high'=>PLUGIN.'high/route.php','external'=>CONFIG.'outside.php'],true).';');
        myStep::refreshRoutes();include CONFIG.'route.php';same(false,isset($rule_list['plugin_disabled']));same(false,isset($api_list['plugin_disabled']));same('/high',$rule_list['plugin_high'][0][0]);same('/external',$rule_list['plugin_external'][0][0]);
    });
    test('enabling and disabling invalidates compiled routes in the same request',function()use($rows){
        $change=$rows;$change[3]['active']=1;writeRows($change);myStep::refreshRoutes();include CONFIG.'route.php';same('/disabled',$rule_list['plugin_disabled'][0][0]);
        writeRows($rows);myStep::refreshRoutes();include CONFIG.'route.php';same(false,isset($rule_list['plugin_disabled']));
    });
} finally {
    $registry->close();$clean=function($path)use(&$clean){if(is_link($path)||is_file($path)){unlink($path);return;}foreach(new FilesystemIterator($path) as $file)$clean($file->getPathname());rmdir($path);};$clean($scratch);
}
echo 'Passed: '.$passed.'; Failed: '.$failed.PHP_EOL;exit($failed?1:0);
