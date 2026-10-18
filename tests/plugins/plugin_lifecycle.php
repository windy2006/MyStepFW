<?php
/**
 * 旧后台插件生命周期回归：真实模块和simpleDB，插件、配置、删除目标均在随机临时目录。
 * 使用说明：PHP 7.4 CLI；不安装、卸载或启停正式站点插件，不连接业务数据库。
 */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
if(($argv[1]??'')!=='--case') {
    $failed=0;
    foreach(['uninstall-failure','delete-installed','invalid-order','mandatory-binding','empty-pack','private-pack'] as $case) {
        $pipes=[];$child=proc_open([PHP_BINARY,__FILE__,'--case',$case],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
        fclose($pipes[0]);$output=stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$status=proc_close($child);
        if($status){$failed++;echo 'FAIL '.$case.': '.trim($output).PHP_EOL;}else echo 'PASS '.$case.PHP_EOL;
    }
    echo 'Passed: '.(6-$failed).'; Failed: '.$failed.PHP_EOL;exit($failed?1:0);
}
$source=dirname(__DIR__,2).'/';$scratch=sys_get_temp_dir().'/mystep-plugin-lifecycle-'.bin2hex(random_bytes(6));mkdir($scratch,0700);
define('ROOT',$scratch.'/');define('LIB',$source.'lib/');define('APP',ROOT.'app/');define('PLUGIN',ROOT.'plugin/');define('CONFIG',ROOT.'config/');define('CACHE',ROOT.'cache/');define('ROOT_WEB','/');
foreach([APP.'myStep',APP.'Demo',PLUGIN.'fixture',PLUGIN.'other',CONFIG,CACHE] as $directory)mkdir($directory,0700,true);
$clean=function($path)use(&$clean){if(is_link($path)||is_file($path)){unlink($path);return;}foreach(new FilesystemIterator($path) as $file)$clean($file->getPathname());rmdir($path);};
register_shutdown_function(function()use($scratch,$clean){$clean($scratch);});
spl_autoload_register(function($name){foreach([LIB.$name.'.class.php',LIB.'database/'.$name.'.class.php'] as $file)if(is_file($file)){require_once $file;return;}});
require_once LIB.'function.php';copy($source.'plugin/interface_plugin.class.php',PLUGIN.'interface_plugin.class.php');
class LifecycleResponse extends RuntimeException {}
class myStep {
    public static function info($message,$url=''){throw new LifecycleResponse($message);}
    public static function redirect($url=''){throw new LifecycleResponse('redirect');}
    public static function file($file,$name=''){$GLOBALS['package_file']=$file;throw new LifecycleResponse('download');}
    public function getInstance($class,...$args){$GLOBALS['packer_requested']=true;return new $class(...$args);}
    public function getLanguage($key){return $key;}
}
class LifecycleTemplate {public function assign($key,$value=null){}public function setLoop($key,$value){}}
foreach(['myStep','Demo'] as $app){file_put_contents(APP.$app.'/info.php','<?php return ["app"=>'.var_export($app,true).'];');file_put_contents(APP.$app.'/plugin.php','<?php return [2=>"fixture",4=>"other"];');}
foreach(['fixture','other'] as $id){
    file_put_contents(PLUGIN.$id.'/info.php','<?php return ["app"=>["myStep"],"name"=>"Fixture","idx"=>'.var_export($id,true).',"ver"=>"1","intro"=>"Fixture"];');
    file_put_contents(PLUGIN.$id.'/config.php','<?php return [];');
    file_put_contents(PLUGIN.$id.'/class.php','<?php class plugin_'.$id.' implements interface_plugin {public static function check(&$result){return true;} public static function install(){} public static function uninstall(){if(!empty($GLOBALS["fail_uninstall"]))throw new RuntimeException("fixture uninstall failed");}}');
}
$registry=new myDb('simpleDB','plugin',PLUGIN);$registry->create([['order',2],['active',1],['idx',10],['ver',10],['name',40],['intro',200]]);
$registry->insert([['order'=>1,'active'=>1,'idx'=>'fixture','ver'=>'1','name'=>'Fixture','intro'=>'Fixture'],['order'=>2,'active'=>1,'idx'=>'other','ver'=>'1','name'=>'Other','intro'=>'Other']],true);$registry->close();
$_SERVER+=['REQUEST_METHOD'=>'POST','REQUEST_URI'=>'/console/function/plugin/','REQUEST_TIME'=>time(),'HTTP_HOST'=>'fixture.test','DOCUMENT_ROOT'=>'/','SCRIPT_NAME'=>'/index.php'];
$_GET=['idx'=>'fixture'];$_POST=['probe'=>'1'];$info_app=['path'=>['console','function','plugin']];$app_root='/console';$mystep=new myStep();$t=new LifecycleTemplate();$ms_setting=new myConfig(PLUGIN.'fixture/config.php');
$before=file_get_contents(PLUGIN.'plugin.db');$case=$argv[2];
if($case==='uninstall-failure'){$info_app['path'][]='uninstall';$GLOBALS['fail_uninstall']=true;}
elseif($case==='delete-installed')$info_app['path'][]='delete';
elseif($case==='invalid-order'){$_GET=[];$_POST=['idx'=>['fixture'],'order'=>['not-a-number']];}
elseif($case==='mandatory-binding'){$info_app['path'][]='setting';$_POST=['setting'=>[],'apps'=>[]];}
elseif($case==='empty-pack'){$info_app['path'][]='pack';$_GET=['idx'=>''];}
elseif($case==='private-pack'){$info_app['path'][]='pack';file_put_contents(PLUGIN.'fixture/config.php','<?php return ["password"=>"never-export-this-fixture-password"];');}
$error=null;
try{include $source.'app/myStep/module/function/plugin.php';}catch(Throwable $caught){$error=$caught;}
try {
    if($case==='uninstall-failure' && (!$error || $error->getMessage()!=='fixture uninstall failed' || file_get_contents(PLUGIN.'plugin.db')!==$before))throw new RuntimeException('Failed uninstall removed registry state');
    if($case==='delete-installed' && !is_file(PLUGIN.'fixture/class.php'))throw new RuntimeException('Installed plugin directory was deleted');
    if($case==='invalid-order' && file_get_contents(PLUGIN.'plugin.db')!==$before)throw new RuntimeException('Invalid order damaged the registry');
    if($case==='mandatory-binding') {
        $bound=include APP.'myStep/plugin.php';$unbound=include APP.'Demo/plugin.php';
        if($bound!==['fixture','other'] || $unbound!==['other'])throw new RuntimeException('Required binding or contiguous list was lost');
    }
    if($case==='mandatory-binding' && (!$error || $error->getMessage()!=='redirect'))throw new RuntimeException('Settings workflow did not finish');
    if($case==='empty-pack' && (!empty($GLOBALS['packer_requested']) || !empty($GLOBALS['package_file'])))throw new RuntimeException('Empty plugin name attempted to package the complete plugin directory');
    if($case==='private-pack') {
        if(!$error || $error->getMessage()!=='download' || empty($GLOBALS['package_file']))throw new RuntimeException('Plugin export did not produce a package');
        $destination=ROOT.'unpacked/';$archive=new myPacker($destination,$GLOBALS['package_file']);$archive->unpack(false);
        if(is_file($destination.'config.php'))throw new RuntimeException('Live plugin configuration was exported');
        if(!is_file($destination.'class.php'))throw new RuntimeException('Plugin source is missing from exported package');
        if(strpos(file_get_contents(PLUGIN.'fixture/config.php'),'never-export-this-fixture-password')===false || is_file(PLUGIN.'fixture/config_new.php'))throw new RuntimeException('Live configuration was changed during export');
    }
} catch(Throwable $failure){fwrite(STDERR,$failure->getMessage().PHP_EOL);exit(1);}
