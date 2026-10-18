<?php
namespace app\myStep { function logCheck($show=true) {return $GLOBALS['plugin_authorized'] ?? false;} }
namespace {
/**
 * 插件公共接口专项。仅 PHP 7.4 CLI；菜单、路由、模板及脚本都写入随机临时目录。
 * 使用真实路由器和模板引擎，控制器/登录检查使用替身；不安装或启停正式站点插件。
 */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$source=dirname(__DIR__, 2).'/';
$worker=in_array($argv[1] ?? '', ['--worker','--bind-worker'],true);
$scratch=$worker ? $argv[2] : sys_get_temp_dir().'/mystep-plugin-interface-'.bin2hex(random_bytes(6));
if(!$worker)mkdir($scratch,0700);
define('ROOT',$scratch.'/');define('APP',ROOT.'app/');define('PLUGIN',ROOT.'plugin/');define('CONFIG',ROOT.'config/');define('CACHE',ROOT.'cache/');define('LIB',$source.'lib/');define('ROOT_WEB','/sub/');
$_SERVER['DOCUMENT_ROOT']='/';$_SERVER['HTTP_HOST']='example.test';$_SERVER['REQUEST_TIME']=time();$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['REQUEST_URI']='/';$_SERVER['QUERY_STRING']='';
spl_autoload_register(function($class){foreach([LIB.$class.'.class.php',LIB.'database/'.$class.'.class.php'] as $file)if(is_file($file)){require_once $file;return;}});
require_once LIB.'function.php';require_once $source.'plugin/interface_plugin.class.php';
set_error_handler(function($n,$s,$f,$l){if(error_reporting()&$n)throw new \ErrorException($s,0,$n,$f,$l);});
$router=new myRouter();
if($worker){if($argv[1]==='--bind-worker')setPluginApps($argv[3],['Sample']);else regPluginRoute($argv[3]);exit;}
foreach([APP.'myStep/template',APP.'Sample/template',PLUGIN.'demo/template',PLUGIN.'demo/module',CONFIG,CACHE] as $path)mkdir($path,0700,true);
file_put_contents(CONFIG.'config.php',"<?php return ['gen'=>['path_admin'=>'manage','s_usr'=>'fixture'],'db'=>['password'=>'']];");
session_start(['save_path'=>$scratch,'use_cookies'=>false,'cache_limiter'=>'']);
$_SESSION=['ms_user'=>'fixture','sysop'=>'y'];$S=$ms_setting=new myConfig(CONFIG.'config.php');$info_app=['app'=>'myStep','path'=>[]];
copy($source.'app/myStep/global.php',APP.'myStep/global.php');
file_put_contents(APP.'myStep/template/main.tpl','<main><!--main--></main>');
file_put_contents(APP.'Sample/template/main.tpl','<article><!--main--></article>');
file_put_contents(PLUGIN.'demo/template/demo.tpl','<!--message-->:<!--root_web-->|<!--path_admin-->|<!--plugin_setting_url|html-->');
file_put_contents(PLUGIN.'demo/template/0.tpl','zero-page');
class PageFailure extends \RuntimeException {}
class PageRedirect extends \RuntimeException {}
class myStep {
    public static $url_prefix='',$cache_css='original.css',$cache_js='original.js';
    public static $active=['demo'=>[]];
    public static function activePlugins(){return self::$active;}
    public static function redirect($url){throw new PageRedirect($url);}
    public static function setURL($path=''){return '/sub/index.php?'.ltrim($path,'/');}
    public static function info($message){throw new PageFailure($message);}
}
class PageController {
    public $html='',$renders=0,$shows=0,$scripts=[];
    public function getLanguage($key){return $key;}
    public function render($tpl){$this->renders++;return $tpl->render('',false);}
    public function show($tpl){$this->shows++;$this->html=$tpl->render('',false);}
    public function setAddedContent($where,$content){$this->scripts[$where][]=$content;}
}
$passed=$failed=0;
function ensure($value,$message='Assertion failed'){if(!$value)throw new \RuntimeException($message);}
function same($wanted,$actual){ensure($wanted===$actual,'Unexpected result');}
function rejects($class,$callback){try{$callback();}catch(\Throwable $e){if($e instanceof $class)return;throw $e;}throw new \RuntimeException('Expected '.$class);}
function test($name,$callback){global $passed,$failed;try{$callback();$passed++;echo 'PASS '.$name.PHP_EOL;}catch(\Throwable $e){$failed++;echo 'FAIL '.$name.': '.$e->getMessage().' ('.$e->getLine().')'.PHP_EOL;}}
function registry(){ $format_list=$rule_list=$api_list=[];if(is_file(CONFIG.'route.php'))include CONFIG.'route.php';return [$format_list,$rule_list,$api_list];}
function property($object,$name){$p=new \ReflectionProperty($object,$name);$p->setAccessible(true);return $p->getValue($object);}
try {
    test('registration publishes formats, rules and APIs without removing other applications',function(){
        myRouter::saveRegistry(CONFIG.'route.php',[],['myStep'=>[['/','home']]],[]);
        file_put_contents(PLUGIN.'demo/route.php','<?php $format=["number"=>"[0-9]+"];$rule=[["/demo/[number]","handler"]];$api=["status"=>"statusHandler"];');
        same(true,regPluginRoute('demo'));$map=include CONFIG.'route_plugin.php';same('plugin/demo/route.php',$map['demo']);list($formats,$rules,$apis)=registry();
        same('[0-9]+',$formats['plugin_demo']['number']);same('statusHandler',$apis['plugin_demo']['status']);same(['plugin_demo','myStep'],array_keys($rules));
    });
    test('idempotent registration skips unchanged mapping and compiled writes',function(){
        touch(CONFIG.'route.php',1000000000);touch(CONFIG.'route_plugin.php',1000000000);same(true,regPluginRoute('demo'));
        clearstatcache();same(1000000000,filemtime(CONFIG.'route.php'));same(1000000000,filemtime(CONFIG.'route_plugin.php'));
    });
    test('plugins without route files do not create dangling source mappings',function(){
        mkdir(PLUGIN.'noRoute');$before=file_get_contents(CONFIG.'route_plugin.php');same(false,regPluginRoute('noRoute'));same($before,file_get_contents(CONFIG.'route_plugin.php'));
    });
    test('malformed source mapping is retained and leaves compiled rules untouched',function(){
        $before=file_get_contents(CONFIG.'route_plugin.php');$compiled=file_get_contents(CONFIG.'route.php');file_put_contents(CONFIG.'route_plugin.php','<?php return false;');
        try{rejects('UnexpectedValueException',function(){regPluginRoute('demo');});same('<?php return false;',file_get_contents(CONFIG.'route_plugin.php'));same($compiled,file_get_contents(CONFIG.'route.php'));}
        finally{file_put_contents(CONFIG.'route_plugin.php',$before);}
    });
    test('invalid route definitions do not update source or compiled registries',function(){
        $route=file_get_contents(PLUGIN.'demo/route.php');$before=file_get_contents(CONFIG.'route_plugin.php');$compiled=file_get_contents(CONFIG.'route.php');
        file_put_contents(PLUGIN.'demo/route.php','<?php $rule=[["/demo/",function(){}]];');
        try{rejects('InvalidArgumentException',function(){regPluginRoute('demo');});same($before,file_get_contents(CONFIG.'route_plugin.php'));same($compiled,file_get_contents(CONFIG.'route.php'));}
        finally{file_put_contents(PLUGIN.'demo/route.php',$route);}
    });
    test('missing router has an explicit diagnostic and does not overwrite files',function(){
        global $router;$keep=$router;$router=null;
        try{rejects('LogicException',function(){regPluginRoute('demo');});}finally{$router=$keep;}
    });
    test('route cleanup removes all groups and keeps other applications',function(){
        same(true,removePluginRoute('demo'));list($formats,$rules,$apis)=registry();same([], $formats);same([], $apis);same(['myStep'],array_keys($rules));same([],include CONFIG.'route_plugin.php');same(true,removePluginRoute('demo'));
    });
    test('deleted plugin directories still permit stale route cleanup',function(){
        mkdir(PLUGIN.'removed');file_put_contents(PLUGIN.'removed/route.php','<?php $rule=[["/removed/","handler"]];');regPluginRoute('removed');unlink(PLUGIN.'removed/route.php');rmdir(PLUGIN.'removed');
        same(true,removePluginRoute('removed'));same(false,isset((include CONFIG.'route_plugin.php')['removed']));same(false,isset(registry()[1]['plugin_removed']));
    });
    test('route cleanup rejects traversal even when plugin directory is absent',function(){rejects('InvalidArgumentException',function(){removePluginRoute('../outside');});});
    test('four simultaneous registrations retain every source and compiled group',function()use($scratch){
        $jobs=[];
        for($i=0;$i<4;$i++){$id='concurrent'.$i;mkdir(PLUGIN.$id);file_put_contents(PLUGIN.$id.'/route.php','<?php usleep(50000);$rule=[["/'.$id.'/","handler"]];');$pipes=[];$p=proc_open([PHP_BINARY,__FILE__,'--worker',$scratch,$id],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);ensure(is_resource($p));fclose($pipes[0]);$jobs[]=[$p,$pipes];}
        foreach($jobs as [$p,$pipes]){$output=stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);same(0,proc_close($p));same('',$output);}
        $map=include CONFIG.'route_plugin.php';$rules=registry()[1];for($i=0;$i<4;$i++){ensure(isset($map['concurrent'.$i]));ensure(isset($rules['plugin_concurrent'.$i]));}ensure(isset($rules['myStep']));
    });
    test('priority absence returns false and does not create a destination',function(){same(false,setPriority('demo'));same(false,is_file(CONFIG.'priority/plugin_demo.php'));});
    test('priority installation creates directories and overwrites the complete previous script',function(){
        file_put_contents(PLUGIN.'demo/priority.php','<?php return "first";');same(true,setPriority('demo'));same('first',include CONFIG.'priority/plugin_demo.php');
        file_put_contents(PLUGIN.'demo/priority.php','0');same(true,setPriority('demo'));same('0',file_get_contents(CONFIG.'priority/plugin_demo.php'));
    });
    test('priority installation rejects a linked source and preserves installed bytes',function(){
        unlink(PLUGIN.'demo/priority.php');symlink(CONFIG.'config.php',PLUGIN.'demo/priority.php');
        try{rejects('InvalidArgumentException',function(){setPriority('demo');});same('0',file_get_contents(CONFIG.'priority/plugin_demo.php'));}finally{unlink(PLUGIN.'demo/priority.php');}
    });
    test('priority removal is idempotent after the source directory has disappeared',function(){
        savePluginFile(CONFIG.'priority/plugin_gone.php','fixture');same(true,removePriority('gone'));same(false,file_exists(CONFIG.'priority/plugin_gone.php'));same(true,removePriority('gone'));
    });
    test('priority removal refuses a directory instead of recursively deleting it',function(){
        mkdir(CONFIG.'priority/plugin_keep.php');file_put_contents(CONFIG.'priority/plugin_keep.php/keep.txt','keep');rejects('RuntimeException',function(){removePriority('keep');});same('keep',file_get_contents(CONFIG.'priority/plugin_keep.php/keep.txt'));
    });
    test('priority cleanup rejects traversal even without a plugin directory',function(){rejects('InvalidArgumentException',function(){removePriority('../outside');});});
    test('failed atomic publication preserves existing destination and removes temporary files',function(){
        $target=CONFIG.'priority/plugin_keep.php';rejects('RuntimeException',function()use($target){savePluginFile($target,'replacement');});same('keep',file_get_contents($target.'/keep.txt'));same([],glob($target.'.*.tmp'));
    });
    test('application template globals survive repeated plugin template creation',function(){
        global $tpl_setting,$tpl_cache;$tpl_setting=['name'=>'main','path'=>APP.'Sample/template','style'=>'','path_compile'=>CACHE.'app'];$tpl_cache=['name'=>'shared','path'=>CACHE.'pages','expire'=>300];$before=[$tpl_setting,$tpl_cache];
        for($i=0;$i<2;$i++){list($main,$sub)=setPluginTemplate('demo','0',false);same($before,[$tpl_setting,$tpl_cache]);same('zero-page',$sub->render('',false));same(false,property($sub,'cache')['use']);same(true,property($main,'cache')['use']);}
    });
    test('partial content never overwrites the application whole-page cache',function(){
        list($main,$sub)=setPluginTemplate('demo','0',false);$cache=property($main,'cache')['file'];$sub->render('',false);same(false,is_file($cache));$main->assign('main','outer');same('<article>outer</article>',$main->render('',false));ensure(is_file($cache));
    });
    test('missing application template settings fail before global state changes',function(){
        $before=$GLOBALS['tpl_setting'];unset($GLOBALS['tpl_setting']);try{rejects('LogicException',function(){setPluginTemplate('demo','',false);});same(false,array_key_exists('tpl_setting',$GLOBALS));}finally{$GLOBALS['tpl_setting']=$before;}
    });
    test('backend templates use default application when application information is missing',function(){
        $before=$GLOBALS['info_app'];unset($GLOBALS['info_app']);try{list($main,$sub)=setPluginTemplate('demo','0');same('zero-page',$sub->render('',false));same(false,property($main,'cache')['use']);}finally{$GLOBALS['info_app']=$before;}
    });
    test('invalid page names and application identifiers cannot select outside paths',function(){
        rejects('InvalidArgumentException',function(){setPluginTemplate('demo','../config');});$before=$GLOBALS['info_app'];$GLOBALS['info_app']['app']='../outside';try{rejects('LogicException',function(){setPluginTemplate('demo');});}finally{$GLOBALS['info_app']=$before;}
    });
    test('unauthenticated pages do not load or execute their module',function(){
        global $mystep;$GLOBALS['tpl_setting']=['name'=>'main','path'=>APP.'myStep/template','style'=>'','path_compile'=>CACHE.'template/myStep'];$mystep=new PageController();$GLOBALS['plugin_authorized']=false;file_put_contents(PLUGIN.'demo/module/demo.php','<?php $GLOBALS["module_calls"]=($GLOBALS["module_calls"] ?? 0)+1;$tpl_sub->assign("message","from-module");');showPluginPage('demo');same(0,$mystep->shows);same(false,isset($GLOBALS['module_calls']));
    });
    test('authenticated page executes module once and assigns framework URLs',function(){
        global $mystep;$mystep=new PageController();$GLOBALS['plugin_authorized']=true;showPluginPage('demo');same(1,$GLOBALS['module_calls']);same(1,$mystep->renders);same(1,$mystep->shows);ensure(strpos($mystep->html,'from-module:/sub/|manage|/sub/index.php?manage/function/plugin/setting/&amp;idx=demo')!==false);ensure(strpos($mystep->scripts['end'][0],'/sub/static/js/checkForm.js')!==false);
    });
    test('atomic publishing preserves restrictive modes and rejects symbolic targets',function(){
        $file=CONFIG.'private.php';file_put_contents($file,'old');chmod($file,0600);savePluginFile($file,'new');clearstatcache(true,$file);same(0600,fileperms($file)&0777);same('new',file_get_contents($file));
        $link=CONFIG.'linked.php';symlink($file,$link);rejects('InvalidArgumentException',function()use($link){savePluginFile($link,'changed');});same(true,is_link($link));same('new',file_get_contents($file));
    });
    test('explicit module content including zero bypasses redundant subtemplate rendering',function(){
        global $mystep;file_put_contents(PLUGIN.'demo/module/demo.php','<?php $content="0";');$mystep=new PageController();showPluginPage('demo');same('<main>0</main>',$mystep->html);same(0,$mystep->renders);same(1,$mystep->shows);
    });
    test('plugin management pages can reuse the current layout without touching its page cache',function(){
        global $mystep,$tpl_setting,$tpl_cache;$tpl_setting=['name'=>'main','path'=>APP.'Sample/template','style'=>'','path_compile'=>CACHE.'app'];$before=[$tpl_setting,$tpl_cache];$mystep=new PageController();showPluginPage('demo','',false);same('<article>0</article>',$mystep->html);same($before,[$tpl_setting,$tpl_cache]);
    });
    test('missing and invalid plugin pages take the framework error path',function(){rejects('PageFailure',function(){showPluginPage('demo','missing');});rejects('PageFailure',function(){showPluginPage('demo','../config');});});
    test('updating an existing menu entry repairs its collapsed parent link',function(){
        file_put_contents(APP.'myStep/menu.json',json_encode([['link'=>'manage/function/plugin','items'=>[['name'=>'old','link'=>'demo']]]]));same(true,addPluginLink('new','demo'));$menu=json_decode(file_get_contents(APP.'myStep/menu.json'),true);same('#',$menu[0]['link']);same('new',$menu[0]['items'][0]['name']);same(1,count($menu[0]['items']));
    });
    test('malformed menu groups and items are retained without PHP offset errors',function(){
        foreach([[false],[['items'=>[false]]]] as $menu){$json=json_encode($menu);file_put_contents(APP.'myStep/menu.json',$json);rejects('UnexpectedValueException',function(){addPluginLink('new','demo');});same($json,file_get_contents(APP.'myStep/menu.json'));}
    });
    test('shared bindings preserve mandatory applications and skip unchanged writes',function(){
        foreach(['myStep','Sample','EmptyApp'] as $name){if(!is_dir(APP.$name))mkdir(APP.$name);file_put_contents(APP.$name.'/info.php','<?php return [];');}
        file_put_contents(APP.'myStep/plugin.php','<?php return [2=>"other",5=>"demo",7=>"demo"];');
        file_put_contents(APP.'Sample/plugin.php','<?php return ["demo","third"];');
        same(2,setPluginApps('demo',[],['myStep']));same(['other','demo'],include APP.'myStep/plugin.php');same(['third'],include APP.'Sample/plugin.php');same(false,is_file(APP.'EmptyApp/plugin.php'));
        touch(APP.'myStep/plugin.php',1000000000);same(0,setPluginApps('demo',[],['myStep']));clearstatcache();same(1000000000,filemtime(APP.'myStep/plugin.php'));
    });
    test('shared bindings reject invalid selections before changing any list',function(){
        $before=file_get_contents(APP.'myStep/plugin.php');rejects('InvalidArgumentException',function(){setPluginApps('demo',['../outside']);});rejects('InvalidArgumentException',function(){setPluginApps('demo',[],['missing']);});same($before,file_get_contents(APP.'myStep/plugin.php'));
    });
    test('corrupt later bindings prevent all planned publications',function(){
        mkdir(APP.'zzLast');file_put_contents(APP.'zzLast/info.php','<?php return [];');file_put_contents(APP.'zzLast/plugin.php','<?php return false;');$before=file_get_contents(APP.'myStep/plugin.php');
        try{rejects('UnexpectedValueException',function(){setPluginApps('demo',[]);});same($before,file_get_contents(APP.'myStep/plugin.php'));}finally{unlink(APP.'zzLast/plugin.php');unlink(APP.'zzLast/info.php');if(is_file(APP.'zzLast/plugin.php.lock'))unlink(APP.'zzLast/plugin.php.lock');rmdir(APP.'zzLast');}
    });
    test('concurrent binding edits retain every other plugin',function()use($scratch){
        $jobs=[];for($i=0;$i<4;$i++){$pipes=[];$job=proc_open([PHP_BINARY,__FILE__,'--bind-worker',$scratch,'bound'.$i],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);fclose($pipes[0]);$jobs[]=[$job,$pipes];}
        foreach($jobs as [$job,$pipes]){$output=stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);same(0,proc_close($job));same('',$output);}
        $list=include APP.'Sample/plugin.php';sort($list);same(['bound0','bound1','bound2','bound3','third'],$list);same(['other','demo'],include APP.'myStep/plugin.php');
    });
    test('binding helper rejects symbolic targets without replacing them',function(){
        $file=APP.'myStep/plugin.php';$before=file_get_contents($file);unlink($file);symlink(CONFIG.'config.php',$file);
        try{rejects('InvalidArgumentException',function(){setPluginApps('demo',['myStep']);});same(true,is_link($file));}finally{unlink($file);file_put_contents($file,$before);}
    });
    test('shared order updates preserve ties and reject bad values without clearing registry',function(){
        $db=new myDb('simpleDB','plugin',PLUGIN);$db->create([['order',2],['active',1],['idx',40]]);
        $db->insert([['order'=>2,'active'=>1,'idx'=>'first'],['order'=>2,'active'=>0,'idx'=>'second'],['order'=>1,'active'=>1,'idx'=>'third']],true);
        same(true,setPluginOrder(['third'=>'2']));same(['first','second','third'],array_column($db->records(),'idx'));same(['1','0','1'],array_column($db->records(),'active'));
        $before=file_get_contents(PLUGIN.'plugin.db');foreach([['first'=>'bad'],['first'=>100],['first'=>true],['first'=>1.0],['missing'=>1],['first'=>[]]] as $bad)rejects('InvalidArgumentException',function()use($bad){setPluginOrder($bad);});same($before,file_get_contents(PLUGIN.'plugin.db'));$db->close();
    });
    test('shared package export redacts in a copy and filters private runtime files',function()use($source){
        mkdir(PLUGIN.'manager');copy($source.'plugin/manager/class.php',PLUGIN.'manager/class.php');mkdir(PLUGIN.'demo/config');
        file_put_contents(PLUGIN.'demo/config.php','<?php return ["password"=>"private-fixture-value","label"=>"visible"];');
        file_put_contents(PLUGIN.'demo/config/construction.php','<?php return ["password"=>["first"=>"password"]];');
        file_put_contents(PLUGIN.'demo/config_new.php','<?php return ["stale"=>"private-stale-value"];');
        foreach(['state.db','state.db-wal','state.db-shm','trace.log','.env'] as $name)file_put_contents(PLUGIN.'demo/'.$name,'private');
        $before=file_get_contents(PLUGIN.'demo/config.php');$stale=file_get_contents(PLUGIN.'demo/config_new.php');$package=buildPluginPackage('demo');same('plugin_demo.plugin',$package['name']);
        $target=ROOT.'exported/';$archive=new myPacker($target,$package['file']);$archive->unpack(false);
        foreach(['config.php','state.db','state.db-wal','state.db-shm','trace.log','.env'] as $name)same(false,is_file($target.$name));
        $config=include $target.'config_new.php';same('',$config['password']);same('visible',$config['label']);same($before,file_get_contents(PLUGIN.'demo/config.php'));same($stale,file_get_contents(PLUGIN.'demo/config_new.php'));
        rejects('InvalidArgumentException',function(){buildPluginPackage('');});
    });
    test('layout hosts require one application adapter and reject symbolic adapters',function(){
        mkdir(APP.'Admin');file_put_contents(APP.'Admin/info.php','<?php return [];');
        same(false,pluginPageHost('Admin'));same(false,pluginPageHost('../Admin'));same(false,pluginPageHost(['Admin']));same(true,pluginPageHost('myStep'));
        file_put_contents(APP.'Admin/plugin_layout.php','<?php return function($body,$context){$tpl=new myTemplate(["name"=>"main","path"=>APP."Sample/template","style"=>"","path_compile"=>CACHE."adapter"],false);$tpl->assign("main",$body);return $tpl;};');
        same(true,pluginPageHost('Admin'));symlink(APP.'Admin/plugin_layout.php',APP.'Sample/plugin_layout.php');same(false,pluginPageHost('Sample'));unlink(APP.'Sample/plugin_layout.php');
    });
    test('plugin page links preserve all route modes filters fragments and explicit layout',function(){
        foreach(['/sub/badReq/?mode=ip#list','/sub/index.php/badReq/?mode=ip#list','/sub/index.php?badReq/&mode=ip#list'] as $url){
            $result=pluginPageURL($url,['keyword'=>'a & b'],'Admin');ensure(strpos($result,'_plugin_app=Admin')!==false);ensure(strpos($result,'mode=ip')!==false);ensure(substr($result,-5)==='#list');ensure(strpos($result,'keyword=a%20%26%20b')!==false);
            same(pluginQueryURL($url,['keyword'=>'a & b']),pluginPageURL($result,[],'myStep'));
        }
        same('https://external.test/a',pluginPageURL('https://external.test/a',[],'Admin'));
        rejects('InvalidArgumentException',function(){pluginPageURL('/sub/',[],'Sample');});
    });
    test('backend function links follow the scoped host and configured legacy prefix',function(){
        $GLOBALS['plugin_page_app']='Admin';same('/sub/index.php?Admin/function/cache/',pluginAdminURL('function/cache/'));
        same('/sub/index.php?manage/function/plugin/setting/&idx=demo',pluginAdminURL('function/plugin/setting/',['idx'=>'demo'],'myStep'));unset($GLOBALS['plugin_page_app']);
    });
    test('shared body guard rejects wrong identity inactive plugins and missing sysop before module',function(){
        file_put_contents(PLUGIN.'demo/module/demo.php','<?php throw new LogicException("module should not run");');
        $session=$_SESSION;
        try {$_SESSION['sysop']='n';rejects('RuntimeException',function(){renderPluginPage('demo');});$_SESSION=$session;$_SESSION['ms_user']='different';rejects('RuntimeException',function(){renderPluginPage('demo');});$_SESSION=$session;myStep::$active=[];rejects('InvalidArgumentException',function(){renderPluginPage('demo');});}
        finally {$_SESSION=$session;myStep::$active=['demo'=>[]];}
    });
    test('modules receive common variables before rendering and cannot overwrite outer state',function(){
        global $mystep;$mystep=new PageController();$GLOBALS['plugin_page_app']='myStep';$_SERVER['REQUEST_URI']='/sub/actual_alias/?filter=one';
        file_put_contents(PLUGIN.'demo/module/demo.php','<?php $app="broken";$previous="broken";$had=false;$tpl_sub->assign("message","own-render");$content=$mystep->render($tpl_sub);');
        $body=renderPluginPage('demo','','Admin');ensure(strpos($body,'own-render:/sub/|Admin|/sub/index.php?Admin/')!==false);same('myStep',$GLOBALS['plugin_page_app']);
        file_put_contents(PLUGIN.'demo/template/alias.tpl','<!--plugin_page_url|html-->');same('/sub/actual_alias/?filter=one&amp;_plugin_app=Admin',renderPluginPage('demo','alias','Admin'));unset($GLOBALS['plugin_page_app']);
    });
    test('module exceptions restore scoped context without leaking a selected backend',function(){
        file_put_contents(PLUGIN.'demo/module/demo.php','<?php throw new LogicException("fixture module failure");');
        rejects('LogicException',function(){renderPluginPage('demo','','Admin');});same(false,array_key_exists('plugin_page_app',$GLOBALS));
    });
    test('initial trusted origin adds a marker without executing the business module',function(){
        $GLOBALS['plugin_authorized']=true;$_GET=$_POST=$_REQUEST=[];$_SERVER['REQUEST_METHOD']='GET';$_SERVER['HTTP_REFERER']='http://example.test/sub/Admin/setting';$_SERVER['REQUEST_URI']='/sub/demo/?filter=one';
        try{showPluginPage('demo');throw new \RuntimeException('Expected redirect');}catch(PageRedirect $e){same('/sub/demo/?filter=one&_plugin_app=Admin',$e->getMessage());}
        same(false,array_key_exists('plugin_page_app',$GLOBALS));unset($_SERVER['HTTP_REFERER']);
    });
    test('one adapter renders a plugin and restores all global context and resource cache paths',function(){
        global $mystep,$tpl_setting;$tpl_setting=['name'=>'main','path'=>APP.'myStep/template','style'=>'','path_compile'=>CACHE.'template/myStep'];$before=[$tpl_setting,$GLOBALS['info_app'],myStep::$cache_css,myStep::$cache_js];
        file_put_contents(PLUGIN.'demo/module/demo.php','<?php $content="generic-body";');$mystep=new PageController();$_GET=['_plugin_app'=>'Admin'];$_REQUEST=$_GET;showPluginPage('demo');same('<article>generic-body</article>',$mystep->html);same($before,[$tpl_setting,$GLOBALS['info_app'],myStep::$cache_css,myStep::$cache_js]);same(false,array_key_exists('plugin_page_app',$GLOBALS));same(false,isset($_REQUEST['_plugin_app']));$_GET=$_REQUEST=[];
    });
    test('empty origin does not inherit the last selected backend and explicit fixed mode still works',function(){
        global $mystep;$mystep=new PageController();showPluginPage('demo');same('<main>generic-body</main>',$mystep->html);$_GET=['_plugin_app'=>'Admin'];showPluginPage('demo','',true);same('<main>generic-body</main>',$mystep->html);$_GET=[];
    });
} finally {
    session_write_close();
    $clean=function($path)use(&$clean){if(is_link($path)||is_file($path)){unlink($path);return;}foreach(new \FilesystemIterator($path) as $file)$clean($file->getPathname());rmdir($path);};$clean($scratch);
}
echo "Passed: $passed; Failed: $failed\n";exit($failed?1:0);
}
