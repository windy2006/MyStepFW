<?php
/** CLI调度：php plugin/crontab/run.php [--loop]；HTTP直访拒绝。 */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$root=dirname(__DIR__,2).'/';
foreach(['ROOT'=>'','LIB'=>'lib/','CONFIG'=>'config/','PLUGIN'=>'plugin/','APP'=>'app/','CACHE'=>'cache/','VENDOR'=>'vendor/','STATICS'=>'static/','FILE'=>'files/']as $name=>$path)if(!defined($name))define($name,$root.$path);
spl_autoload_register(static function($class){foreach([LIB,LIB.'database/',PLUGIN]as $dir)foreach([$class,strtolower($class)]as $name){$p=$dir.$name.'.class.php';if(is_file($p)){require_once $p;return;}}});
require_once __DIR__.'/class.php';
try{
    $ms_setting=new myConfig(CONFIG.'config.php');date_default_timezone_set($ms_setting->value('gen.timezone','Asia/Shanghai'));$mystep=new myStep();
    do{$result=plugin_crontab::tick();echo json_encode($result,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)."\n";if(!in_array('--loop',$argv,true))break;sleep(max(10,min(86400,(int)(PCrontabSupport::config()['interval']??120))));}while(true);
}catch(Throwable $e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}
