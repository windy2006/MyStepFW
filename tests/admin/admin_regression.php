<?php
/** 新后台真实服务隔离回归：配置、插件注册、附件、包与会话都位于随机临时目录。 */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$source=getenv('MYSTEP_ADMIN_SOURCE')?:dirname(__DIR__, 2);$stage=getenv('MYSTEP_ADMIN_STAGE')?:$source;$scratch=sys_get_temp_dir().'/mystep-admin-'.bin2hex(random_bytes(6));mkdir($scratch,0700);
define('ROOT',$scratch.'/');define('LIB',ROOT.'lib/');define('APP',ROOT.'app/');define('PLUGIN',ROOT.'plugin/');define('CONFIG',ROOT.'config/');define('CACHE',ROOT.'cache/');define('FILE',ROOT.'files/');define('VENDOR',$source.'/vendor/');define('STATICS',$source.'/static/');define('ROOT_WEB','/sub/');symlink($source.'/lib',rtrim(LIB,'/'));define('PATH',APP.'Admin/');
foreach([APP,PLUGIN,CONFIG.'construction',CACHE.'session',FILE] as $dir)mkdir($dir,0700,true);
$copy=function($a,$b)use(&$copy){if(is_dir($a)){if(!is_dir($b))mkdir($b,0700,true);foreach(new FilesystemIterator($a) as $f)$copy($f->getPathname(),$b.'/'.$f->getFilename());}else copy($a,$b);};$copy($stage.'/app/Admin',APP.'Admin');$copy($source.'/app/myStep',APP.'myStep');$copy($source.'/config/construction',CONFIG.'construction');$copy($source.'/config/class_alias.php',CONFIG.'class_alias.php');$copy($source.'/plugin/interface_plugin.class.php',PLUGIN.'interface_plugin.class.php');
spl_autoload_register(function($name)use($stage){foreach([$stage.'/lib/'.$name.'.class.php',$stage.'/lib/database/'.$name.'.class.php',LIB.$name.'.class.php',LIB.'database/'.$name.'.class.php'] as $f)if(is_file($f)){require_once $f;return;}});
require LIB.'myStep.class.php';require APP.'Admin/Admin.class.php';
include $source.'/config/config_default.php';$setting['gen']['s_usr']='fixture';$setting['gen']['s_pwd']=password_hash('fixture-pass',PASSWORD_DEFAULT);$setting['db']['auto']=false;$setting['cookie']['prefix']='adm_';file_put_contents(CONFIG.'config.php','<?php return '.var_export($setting,true).';');
file_put_contents(CONFIG.'class.php','<?php return [["path"=>'.var_export(LIB,true).',"ext"=>".php,.class.php","idx"=>[]]];');
$_SERVER+=['DOCUMENT_ROOT'=>'/','HTTP_HOST'=>'fixture.test','REQUEST_TIME'=>time(),'REQUEST_URI'=>'/sub/Admin/','REQUEST_METHOD'=>'GET','SCRIPT_NAME'=>'/sub/index.php'];
ini_set('session.use_cookies','0');session_save_path(CACHE.'session');session_start();$_SESSION['ms_user']='fixture';$_SESSION['sysop']='y';
$ms_setting=new myConfig(CONFIG.'config.php');$info_app=['app'=>'Admin','route'=>'/Admin/','path'=>['Admin']];$domain=[];$tpl_setting=['name'=>'main','path'=>PATH.'template','style'=>'','path_compile'=>CACHE.'compiled'];$mystep=new Admin();$mystep->preload();$router=new myRouter();$service=new AdminService($mystep);
set_error_handler(function($n,$s,$f,$l){if(error_reporting()&$n)throw new ErrorException($s,0,$n,$f,$l);});$passed=$failed=0;ob_start();
function ensure($v,$message='Assertion failed'){if(!$v)throw new RuntimeException($message);}
function same($a,$b){ensure($a===$b,'Unexpected result');}
function raises($class,$run){try{$run();}catch(Throwable $e){if($e instanceof $class)return;throw $e;}throw new RuntimeException('Expected '.$class);}
function test($name,$run){global $passed,$failed;try{$run();$passed++;echo 'PASS '.$name."\n";}catch(Throwable $e){$failed++;echo 'FAIL '.$name.': '.$e->getMessage().' line '.$e->getLine()."\n";}}
try{
 test('declarations cover legacy menu pages and every handler exists',function()use($service){$pages=include PATH.'pages.php';foreach(['','db','php','phpinfo','error','setting','setting/class','setting/domain','setting/execute','function/app','function/cache','function/plugin','function/attachment','function/language','guide','samples','references','setting/alias','setting/detail','function/plugin/view','function/plugin/setting'] as $path)ensure(isset($pages[$path]),$path);foreach($pages as $entry){ensure($service->supports($entry['view']));ensure(is_file(PATH.'module/'.$entry['module'].'.php'));foreach($entry['actions']??[] as $action)ensure($service->supports($action['handler']));}});
 test('application core directly inherits myStep and has no public Admin implementation',function()use($source){same('myStep',get_parent_class('Admin'));ensure(!is_file($source.'/lib/myAdmin.class.php'));ensure(!is_file($source.'/lib/myAdminStore.class.php'));same(realpath(APP.'Admin/AdminStore.class.php'),realpath((new ReflectionClass('AdminStore'))->getFileName()));});
 test('required shared fields declare framework need alongside native required',function()use($mystep){
  foreach(['text','password','textarea','select','file','checkbox'] as $type){
   $html=$mystep->field(['name'=>'example','type'=>$type,'label'=>'Example','required'=>true,'options'=>[''=>'Choose','value'=>'Value']]);
   $doc=new DOMDocument();@$doc->loadHTML($html);$xpath=new DOMXPath($doc);$controls=$xpath->query('//*[@name="example" and not(@type="hidden")]');same(1,$controls->length);
   ensure($controls->item(0)->hasAttribute('need'));same('',$controls->item(0)->getAttribute('need'));ensure($controls->item(0)->hasAttribute('required'));
  }
  $doc=new DOMDocument();@$doc->loadHTML($mystep->field(['name'=>'optional']));$xpath=new DOMXPath($doc);same(0,$xpath->query('//*[@name="optional" and (@need or @required)]')->length);
 });
 test('functional modules load only on demand and reject undeclared methods',function()use($mystep){
  $local=new AdminService($mystep);$property=new ReflectionProperty('AdminService','modules');$property->setAccessible(true);same([],$property->getValue($local));
  ensure(strpos($local->php(),'PHP版本')!==false);same(['info/php'],array_keys($property->getValue($local)));
  same(false,$local->supports('catalog'));same(false,$local->supports('../setting/main'));
  raises('BadMethodCallException',function()use($local){$local->unknownAction();});
 });
 test('every page and action maps to an actual functional module callback',function(){
  foreach(include PATH.'pages.php' as $entry){$callbacks=include PATH.'module/'.$entry['module'].'.php';ensure(($callbacks[$entry['view']]??null) instanceof Closure);foreach($entry['actions']??[] as $action)ensure(($callbacks[$action['handler']]??null) instanceof Closure);}
 });
 test('administrator requires both expected username and sysop',function()use($mystep){same(true,$mystep->authorized());$_SESSION['ms_user']='another';same(false,$mystep->authorized());$_SESSION['ms_user']='fixture';$_SESSION['sysop']='n';same(false,$mystep->authorized());$_SESSION['sysop']='y';});
 test('URL builder supports all three modes and query encoding',function()use($mystep,$ms_setting){foreach(['rewrite'=>'/sub/Admin/db?table=A%2BB','path_info'=>'/sub/index.php/Admin/db?table=A%2BB','query_string'=>'/sub/index.php?/Admin/db&table=A%2BB'] as $mode=>$wanted){$ms_setting->router->mode=$mode;same($wanted,$mystep->url('db',['table'=>'A+B']));}$ms_setting->router->mode='rewrite';});
 test('forms and tables escape input while explicit HTML remains possible',function()use($mystep){$html=$mystep->form('save',[['name'=>'x','value'=>'"><script>x</script>']]);ensure(strpos($html,'<script>')===false);$table=$mystep->table(['value'=>'列'],[['value'=>'<img src=x>']]);ensure(strpos($table,'&lt;img')!==false);ensure(strpos($mystep->table(['x'=>'x'],[['x'=>['html'=>'<b>yes</b>']]]),'<b>yes</b>')!==false);});
 test('action validation excludes extra fields and rejects invalid shapes',function()use($mystep){same(['id'=>2],$mystep->validate(['id'=>['type'=>'int','min'=>1]],['id'=>'2','password'=>'secret']));raises('InvalidArgumentException',function()use($mystep){$mystep->validate(['id'=>['type'=>'int','min'=>1]],['id'=>[]]);});});
 test('atomic data saves preserve zero and stale versions never overwrite',function(){ $file=CONFIG.'data.php';same(true,AdminStore::saveData($file,['zero'=>'0'],'missing'));$old=AdminStore::version($file);AdminStore::saveData($file,['zero'=>0],$old);raises('RuntimeException',function()use($file,$old){AdminStore::saveData($file,[],$old);});same(['zero'=>0],AdminStore::read($file));});
 test('invalid PHP is rejected without executing or replacing the old file',function(){ $file=CONFIG.'text.php';AdminStore::save($file,'<?php $x=1;','missing');$before=file_get_contents($file);raises('InvalidArgumentException',function()use($file){AdminStore::save($file,'<?php $x=[;',AdminStore::version($file));});same($before,file_get_contents($file));same([],glob($file.'.*.tmp'));});
 test('all supported read-only pages render without opening SQL',function()use($service){foreach(['dashboard','php','phpinfo','errors','settings','classes','domains','aliases','methods','apps','caches','plugins','attachments','languages','guide','samples','references','execute'] as $method){$_GET=$_POST=[];if($method==='guide')file_put_contents(ROOT.'readme.md','# Fixture');$html=$service->$method();ensure(is_string($html),$method);}ensure(empty($GLOBALS['db']));});
 test('configuration navigation identifies exactly one current target in every routing mode',function()use($service,$mystep,$ms_setting){
  $mode=$ms_setting->router->mode;
  try{
   foreach(['rewrite','path_info','query_string'] as $routing)foreach(['','Admin','myStep'] as $target){
    $ms_setting->router->mode=$routing;$_GET=$target===''?[]:['target'=>$target];$_POST=[];
    $html=$service->settings();$doc=new DOMDocument();@$doc->loadHTML('<?xml encoding="UTF-8">'.$html);$xpath=new DOMXPath($doc);
    $links=$xpath->query('//nav[@aria-label="选择配置"]/a[@aria-current="page"]');same(1,$links->length);
    same($mystep->url('setting',$target===''?[]:['target'=>$target]),$links->item(0)->getAttribute('href'));
   }
  }finally{$_GET=$_POST=[];$ms_setting->router->mode=$mode;}
 });
 test('language and attachment groups retain independent current choices',function()use($service){
  mkdir(FILE.'2025/03',0700,true);mkdir(FILE.'2025/04',0700,true);mkdir(FILE.'2024/12',0700,true);
  try{
   foreach([
    ['languages',['app'=>'myStep','type'=>'chs'],['选择语言所属应用'=>'myStep','选择语言文件'=>'chs']],
    ['attachments',['y'=>'2025','m'=>'03'],['选择附件年份'=>'2025','选择附件月份'=>'03月']],
    ['attachments',[],['选择附件年份'=>'2025','选择附件月份'=>'04月']]
   ] as $case){
    $_GET=$case[1];$_POST=[];$html=$service->{$case[0]}();$doc=new DOMDocument();@$doc->loadHTML('<?xml encoding="UTF-8">'.$html);$xpath=new DOMXPath($doc);
    foreach($case[2] as $label=>$value){$links=$xpath->query('//nav[@aria-label="'.$label.'"]/a[@aria-current="page"]');same(1,$links->length);ensure(strpos($links->item(0)->textContent,$value)!==false);}
   }
  }finally{$_GET=$_POST=[];rmdir(FILE.'2025/03');rmdir(FILE.'2025/04');rmdir(FILE.'2025');rmdir(FILE.'2024/12');rmdir(FILE.'2024');}
 });
 test('application management exposes targeted settings and language links in every routing mode',function()use($service,$mystep,$ms_setting){
  $previous=$ms_setting->router->mode;
  try{foreach(['rewrite','path_info','query_string'] as $mode){$ms_setting->router->mode=$mode;$_GET=$_POST=[];$doc=new DOMDocument();@$doc->loadHTML($service->apps());$xpath=new DOMXPath($doc);
   foreach(['Admin','myStep'] as $app){$card='//article[@data-app="'.$app.'"]';$links=$xpath->query($card.'/div[@class="adm-app-heading"]/div[@class="adm-actions"]/a');same(2,$links->length);same($mystep->url('setting',['target'=>$app]),$links->item(0)->getAttribute('href'));same($mystep->url('function/language',['app'=>$app]),$links->item(1)->getAttribute('href'));}
  }}finally{$ms_setting->router->mode=$previous;$_GET=$_POST=[];}
 });
 test('application shortcuts disable unavailable editors and open a language without default',function()use($service){
  mkdir(APP.'NoManage');file_put_contents(APP.'NoManage/info.php','<?php return ["app"=>"NoManage","name"=>"No management files"];');mkdir(APP.'SingleLanguage/language',0700,true);file_put_contents(APP.'SingleLanguage/info.php','<?php return ["app"=>"SingleLanguage","name"=>"One language"];');file_put_contents(APP.'SingleLanguage/language/chs.php','<?php return ["hello"=>"hello"];');
  try{
   $doc=new DOMDocument();@$doc->loadHTML($service->apps());$xpath=new DOMXPath($doc);same(2,$xpath->query('//article[@data-app="NoManage"]//button[@disabled]')->length);same(0,$xpath->query('//article[@data-app="NoManage"]//a')->length);same(1,$xpath->query('//article[@data-app="SingleLanguage"]//a')->length);
   $_GET=['app'=>'SingleLanguage'];$_POST=[];$doc=new DOMDocument();@$doc->loadHTML($service->languages());$xpath=new DOMXPath($doc);same('SingleLanguage',$xpath->query('//input[@name="app"]')->item(0)->getAttribute('value'));same('chs',$xpath->query('//input[@name="type"]')->item(0)->getAttribute('value'));same(0,$xpath->query('//nav[@aria-label="选择语言所属应用"]/a[contains(@href,"NoManage")]')->length);
   $_GET=['app'=>'SingleLanguage','type'=>'missing'];raises('InvalidArgumentException',function()use($service){$service->languages();});
  }finally{$_GET=$_POST=[];myFile::del(APP.'NoManage');myFile::del(APP.'SingleLanguage');}
 });
 test('configuration sections preserve every control and nested parent headings',function()use($service){
  $builder=CONFIG.'fixture-construction.php';$values=CONFIG.'fixture-values.php';file_put_contents($values,"<?php return ['group'=>['value'=>'one','child'=>['value'=>'two'],'secret'=>'']];");$config=new myConfig($values);
  $field=['name'=>'Field','describe'=>'Description','type'=>['text',false,100]];
  $structure=['group'=>['name'=>'Parent <group>','list'=>['value'=>$field,'child'=>['name'=>'Child','list'=>['value'=>$field]],'secret'=>['name'=>'Password','name2'=>'Repeat','describe'=>'Match','type'=>['password',false,100]]]]];
  file_put_contents($builder,'<?php return '.var_export($structure,true).';');
  $method=new ReflectionMethod('AdminService','configFields');$method->setAccessible(true);$html=$method->invoke($service,$config,$builder);
  $doc=new DOMDocument();@$doc->loadHTML($html);$xpath=new DOMXPath($doc);
  same(3,$xpath->query('//section[@class="adm-config-section"]')->length);
  same(2,$xpath->query('//section[@class="adm-config-section"][last()]//input[@type="password"]')->length);
  same(4,$xpath->query('//div[@class="adm-field adm-config-row"]')->length);
  same(4,$xpath->query('//input')->length);same(2,$xpath->query('//input[@type="password"]')->length);
  ensure(strpos($html,'Parent &lt;group&gt;')!==false);same(1,$xpath->query('//header/small')->length);
  ensure(strpos($html,'name="setting[group][child][value]"')!==false);unlink($builder);
 });
 test('image examples use six local application assets in every routing mode',function()use($service,$ms_setting){
  $mode=$ms_setting->router->mode;
  try{foreach(['rewrite','path_info','query_string'] as $routing)foreach(['image'=>24,'image_load'=>6] as $sample=>$count){
   $ms_setting->router->mode=$routing;$_GET=['sample'=>$sample];$_POST=[];$html=$service->samples();
   ensure(strpos($html,'desk-fd.zol-img')===false && strpos($html,'cccfna.org')===false);
   $doc=new DOMDocument();@$doc->loadHTML($html);$xpath=new DOMXPath($doc);$images=$xpath->query('//img[contains(@src,"asset/samples/") or contains(@data-src,"asset/samples/")]');same($count,$images->length);
   foreach($images as $img){$url=$img->getAttribute($sample==='image'?'src':'data-src');$file=basename($url);same(myStep::setURL('myStep/asset/samples/').$file,$url);ensure(is_file(APP.'myStep/asset/samples/'.$file));ensure($img->getAttribute('alt')!=='');}
   if($sample==='image')same(3,$xpath->query('//div[@class="sample-image-wall"]')->length);
   else ensure(strpos($html,myStep::setURL('static/images/loading_img.gif'))!==false);
  }}finally{$_GET=$_POST=[];$ms_setting->router->mode=$mode;}
 });
 test('sample array input is rejected without template lookup',function()use($service){$_GET=['sample'=>[]];raises('InvalidArgumentException',function()use($service){$service->samples();});$_GET=[];});
 test('class index requires scalar array text input',function()use($service){raises('InvalidArgumentException',function()use($service){$service->saveClasses(['version'=>AdminStore::version(CONFIG.'class.php'),'rows'=>[['path'=>LIB,'ext'=>'.php','idx'=>[]]]]);});});
 test('responsive stylesheet preserves media conditions through framework minification',function(){ $css=myMinify::minify(file_get_contents(PATH.'asset/style.css'),'css');ensure(preg_match('/@media\s*\(max-width:850px\)/',$css)===1);ensure(preg_match('/@media\s*\(max-width:600px\)/',$css)===1);});
 test('page layout can be edited in tpl without changing PHP',function()use($service){$file=PATH.'template/default/info_main.tpl';$source=file_get_contents($file);try{file_put_contents($file,str_replace('运行概览','模板布局可调整',$source));$html=$service->dashboard();ensure(strpos($html,'模板布局可调整')!==false);ensure(strpos($html,'服务器域名')!==false);}finally{file_put_contents($file,$source);}});
 test('custom style overrides a page and other pages fall back to default',function()use($service){global $tpl_setting;$previous=$tpl_setting['style'];$dir=PATH.'template/fixture';mkdir($dir);file_put_contents($dir.'/info_main.tpl','<section>custom-skin</section>');try{$tpl_setting['style']='fixture';ensure(strpos($service->dashboard(),'custom-skin')!==false);ensure(strpos($service->php(),'PHP 运行环境')!==false);}finally{$tpl_setting['style']=$previous;unlink($dir.'/info_main.tpl');rmdir($dir);}});
 test('settings preserve typed fields and enforce stale-edit conflict',function()use($service){$v=AdminStore::version(CONFIG.'config.php');$service->saveSettings(['target'=>'','version'=>$v,'setting'=>['web'=>['title'=>'New title']]]);same('New title',AdminStore::read(CONFIG.'config.php')['web']['title']);raises('RuntimeException',function()use($service,$v){$service->saveSettings(['target'=>'','version'=>$v,'setting'=>['web'=>['title'=>'Stale']]]);});same('New title',AdminStore::read(CONFIG.'config.php')['web']['title']);});
 test('settings extension synchronizes legacy menus with application entry changes',function()use($service){
  $file=APP.'myStep/config.php';$menu=APP.'myStep/menu.json';$before=file_get_contents($file);$beforeMenu=file_get_contents($menu);
  try{$service->saveSettings(['target'=>'myStep','version'=>AdminStore::version($file),'setting'=>['gen'=>['path_admin'=>'fixture_console']]]);same('fixture_console',AdminStore::read($file)['gen']['path_admin']);ensure(strpos(file_get_contents($menu),'fixture_console')!==false);}
  finally{file_put_contents($file,$before);file_put_contents($menu,$beforeMenu);}
 });
 test('CMS entry configuration follows renamed routes and collisions preserve settings',function()use($service,$source,$copy){
  $dir=APP.'CMS/';mkdir($dir);$copy($source.'/app/CMS/config',$dir.'config');file_put_contents($dir.'info.php','<?php return ["app"=>"CMS","name"=>"Fixture CMS"];');file_put_contents($dir.'config.php','<?php return ["web"=>["path_admin"=>"admin_cms"],"template"=>["path"=>"app/CMS/template","style"=>"default"]];');$override='<?php return ["template"=>["style"=>"admin"]];';file_put_contents($dir.'config_admin_cms.php',$override);
  try{
   $service->saveSettings(['target'=>'CMS','version'=>AdminStore::version($dir.'config.php'),'setting'=>['web'=>['path_admin'=>'fixture_manage']]]);
   same(false,is_file($dir.'config_admin_cms.php'));same($override,file_get_contents($dir.'config_fixture_manage.php'));same('fixture_manage',AdminStore::read($dir.'config.php')['web']['path_admin']);
   file_put_contents($dir.'config_taken.php','<?php return [];');$before=file_get_contents($dir.'config.php');
   raises('InvalidArgumentException',function()use($service,$dir){$service->saveSettings(['target'=>'CMS','version'=>AdminStore::version($dir.'config.php'),'setting'=>['web'=>['path_admin'=>'taken']]]);});same($before,file_get_contents($dir.'config.php'));same($override,file_get_contents($dir.'config_fixture_manage.php'));
  }finally{myFile::del($dir);}
 });
 test('password confirmation mismatch does not write configuration',function()use($service){$v=AdminStore::version(CONFIG.'config.php');raises('InvalidArgumentException',function()use($service,$v){$service->saveSettings(['target'=>'','version'=>$v,'setting'=>['gen'=>['s_pwd'=>'a','s_pwd_pwd_r'=>'b']]]);});same($v,AdminStore::version(CONFIG.'config.php'));});
 test('domain editor validates rows and supports additions and removals',function()use($service){$service->saveDomains(['version'=>'missing','rows'=>[['domain'=>'admin.test','rule'=>'Admin'],['domain'=>'','rule'=>'']]]);same(['admin.test'=>'Admin'],AdminStore::read(CONFIG.'domain.php'));raises('InvalidArgumentException',function()use($service){$service->saveDomains(['version'=>AdminStore::version(CONFIG.'domain.php'),'rows'=>[['domain'=>"bad\r\nheader",'rule'=>'Admin']]]);});});
 test('domain editor renders only stored rows and supports an empty table',function()use($service){
  $file=CONFIG.'domain.php';$before=file_get_contents($file);
  try{foreach([['admin.test'=>'Admin'],[]] as $bindings){file_put_contents($file,'<?php return '.var_export($bindings,true).';');$doc=new DOMDocument();@$doc->loadHTML($service->domains());$xpath=new DOMXPath($doc);same(count($bindings),$xpath->query('//table[@id="domain-rows"]/tbody/tr')->length);same(1,$xpath->query('//template[@id="domain-row-template"]')->length);}}
  finally{file_put_contents($file,$before);}
 });
 test('saving unchanged aliases allows aliases already loaded for the same class',function()use($service){$data=AdminStore::read(CONFIG.'class_alias.php');myController::setAlias($data);$v=AdminStore::version(CONFIG.'class_alias.php');$service->saveAliases(['aliases'=>$data,'version'=>$v]);same($data,AdminStore::read(CONFIG.'class_alias.php'));});
 test('bulk mutation sorts physical records and exceptions leave bytes unchanged',function(){ $db=new simpleDB('bulk',CONFIG);$db->create([['idx',20],['order',3]]);$db->insert([['idx'=>'a','order'=>1],['idx'=>'b','order'=>2]],true);$db->mutate(function(&$rows){usort($rows,function($a,$b){return $b['order']<=>$a['order'];});return true;});same('b',$db->records()[0]['idx']);$before=file_get_contents(CONFIG.'bulk.db');raises('RuntimeException',function()use($db){$db->mutate(function(&$rows){$rows=[];throw new RuntimeException('cancel');});});same($before,file_get_contents(CONFIG.'bulk.db'));$db->close();});
 test('class editor protects the core root and accepts PHP mappings',function()use($service){$v=AdminStore::version(CONFIG.'class.php');$service->saveClasses(['version'=>$v,'rows'=>[['path'=>LIB,'ext'=>'.php,.class.php','idx'=>"array('interface_plugin'=>'../plugin/interface_plugin.class.php')"]]]);same('../plugin/interface_plugin.class.php',AdminStore::read(CONFIG.'class.php')[0]['idx']['interface_plugin']);});
 test('portable custom class rules share scoped aliases without discarding other directories',function()use($service){
  $file=CONFIG.'class.php';$before=file_get_contents($file);$aliasesBefore=file_get_contents(CONFIG.'class_alias.php');mkdir(ROOT.'models');file_put_contents(ROOT.'models/RegisteredAliasFixture.php','<?php class RegisteredAliasFixture {}');
  try{
   $service->saveClasses(['version'=>AdminStore::version($file),'rows'=>[['path'=>'lib/','ext'=>'.php,.class.php','idx'=>"array('interface_plugin'=>'../plugin/interface_plugin.class.php')"],['path'=>'models/','ext'=>'.php','idx'=>'array ()']]]);
   $rules=AdminStore::read($file);same('lib/',$rules[0]['path']);same('models/',$rules[1]['path']);ensure(preg_match("/'idx'\\s*=>\\s*array\\s*\\(/",file_get_contents($file))===1);
   myController::regClass($rules);same(true,class_exists('RegisteredAliasFixture'));
   $_GET=['directory'=>'1'];$_POST=[];$html=$service->aliases();ensure(strpos($html,'RegisteredAliasFixture')!==false);ensure(strpos($html,'aliases[myFile]')===false);
   $old=AdminStore::read(CONFIG.'class_alias.php');$service->saveAliases(['directory'=>1,'version'=>AdminStore::version(CONFIG.'class_alias.php'),'aliases'=>['RegisteredAliasFixture'=>'fixture_alias']]);$now=AdminStore::read(CONFIG.'class_alias.php');same('fixture_alias',$now['RegisteredAliasFixture']);foreach($old as $class=>$alias)same($alias,$now[$class]);
  }finally{$_GET=$_POST=[];file_put_contents($file,$before);file_put_contents(CONFIG.'class_alias.php',$aliasesBefore);}
 });
 test('language saves and create-new preserve other keys',function()use($service){$file=APP.'myStep/language/chs.php';$data=AdminStore::read($file);$key=array_key_first($data);$service->saveLanguage(['app'=>'myStep','type'=>'chs','new'=>'fixture','version'=>AdminStore::version($file),'language'=>[$key=>'changed']]);$out=AdminStore::read(APP.'myStep/language/fixture.php');same('changed',$out[$key]);same(count($data),count($out));});
 test('new language refuses a stale source version',function()use($service){raises('RuntimeException',function()use($service){$service->saveLanguage(['app'=>'myStep','type'=>'chs','new'=>'stale','version'=>'stale-version','language'=>[]]);});same(false,is_file(APP.'myStep/language/stale.php'));});
 test('unknown cache classification never deletes arbitrary paths',function()use($service){file_put_contents(CACHE.'keep','keep');raises('InvalidArgumentException',function()use($service){$service->clearCaches(['areas'=>['../config']]);});same('keep',file_get_contents(CACHE.'keep'));});
 test('plugin install binds applications, toggles and uninstalls using real registry',function()use($service){$dir=PLUGIN.'fixture';mkdir($dir);file_put_contents($dir.'/info.php','<?php return ["idx"=>"fixture","name"=>"Fixture","ver"=>"1","intro"=>"test"];');file_put_contents($dir.'/class.php','<?php class plugin_fixture implements interface_plugin {public static function check(&$result){$result="ok";return true;}public static function install(){$GLOBALS["fixture_install"]=true;}public static function uninstall(){$GLOBALS["fixture_uninstall"]=true;}}');$service->installPlugin(['idx'=>'fixture','setting'=>[],'apps'=>['Admin'],'version'=>'missing','registry_version'=>'missing']);same(true,$GLOBALS['fixture_install']);ensure(in_array('fixture',AdminStore::read(APP.'Admin/plugin.php'),true));$checkToggle=function($expected)use($service){$doc=new DOMDocument();@$doc->loadHTML('<?xml encoding="UTF-8">'.$service->plugins());$xpath=new DOMXPath($doc);$buttons=$xpath->query('//form[input[@name="_action" and @value="toggle"] and input[@name="idx" and @value="fixture"]]/button');same(1,$buttons->length);same($expected,trim($buttons->item(0)->textContent));};$checkToggle('停用');$service->togglePlugin(['idx'=>'fixture']);$checkToggle('启用');$service->togglePlugin(['idx'=>'fixture']);$checkToggle('停用');$service->orderPlugins(['order'=>['fixture'=>'7']]);$html=$service->plugins();ensure(strpos($html,'value="7"')!==false && strpos($html,'保存运行顺序')!==false);$service->uninstallPlugin(['idx'=>'fixture']);same(true,$GLOBALS['fixture_uninstall']);same(false,in_array('fixture',AdminStore::read(APP.'Admin/plugin.php'),true));});
 test('failed plugin checks never install or add registry rows',function()use($service){mkdir(PLUGIN.'rejected');file_put_contents(PLUGIN.'rejected/info.php','<?php return ["name"=>"No"];');file_put_contents(PLUGIN.'rejected/class.php','<?php class plugin_rejected implements interface_plugin {public static function check(&$result){$result="not ready";return false;}public static function install(){throw new Exception("must not install");}public static function uninstall(){}}');raises('InvalidArgumentException',function()use($service){$service->installPlugin(['idx'=>'rejected','setting'=>[],'apps'=>[],'version'=>'missing','registry_version'=>AdminStore::version(PLUGIN.'plugin.db')]);});});
 test('plugin file deletion refuses installed or traversing targets',function()use($service){raises('InvalidArgumentException',function()use($service){$service->deletePlugin(['idx'=>'../config']);});same(true,is_file(CONFIG.'config.php'));});
 test('unlogged attachment deletion preserves neighboring files',function()use($service){mkdir(FILE.'2026/10',0700,true);file_put_contents(FILE.'2026/10/orphan.txt','one');file_put_contents(FILE.'2026/10/keep.txt','two');$service->deleteAttachment(['idx'=>'2026/10/orphan.txt','logged'=>0]);same(false,is_file(FILE.'2026/10/orphan.txt'));same('two',file_get_contents(FILE.'2026/10/keep.txt'));});
 test('code execution uses POST action service and preserves output as flash state',function()use($service){$service->runCode(['code'=>'<?php echo "fixture-output";']);same('fixture-output',myReq::session('_admin_code_result'));});
 test('clear-all rejects invalid categories and linked data before touching any cache',function()use($service){
  myFile::mkdir(CACHE.'script');file_put_contents(CACHE.'script/all-keep','keep');
  raises('InvalidArgumentException',function()use($service){$service->clearCaches(['areas'=>['all','../config']]);});same('keep',file_get_contents(CACHE.'script/all-keep'));
  $data=CACHE.'data';if(file_exists($data))rename($data,CACHE.'data-original');symlink(CONFIG,rtrim($data,'/'));
  try{raises('InvalidArgumentException',function()use($service){$service->clearCaches(['areas'=>['all']]);});same('keep',file_get_contents(CACHE.'script/all-keep'));same(true,is_file(CONFIG.'config.php'));}
  finally{unlink($data);if(file_exists(CACHE.'data-original'))rename(CACHE.'data-original',$data);}
 });
 test('clear-all clears seven categories and the active data driver while preserving admin files',function()use($service){
  global $cache;$previous=$cache??null;
  foreach(['script','template','language','session','tmp','app'] as $area){myFile::mkdir(CACHE.$area);file_put_contents(CACHE.$area.'/all-marker','test');}
  myFile::mkdir(CACHE.'admin');file_put_contents(CACHE.'admin/audit-marker','keep');
  $files=new myCache('File',CACHE.'data',0);$files->set('all-fixture','data');$cache=new myCache('File',CACHE.'active-driver',0);$cache->set('all-fixture','driver');
  try{
   $doc=new DOMDocument();@$doc->loadHTML($service->caches());$xpath=new DOMXPath($doc);same(1,$xpath->query('//form/input[@name="areas[]" and @value="all"]')->length);
   $service->clearCaches(['areas'=>['all','script']]);
   foreach(['script','template','language','session','tmp','app'] as $area){same(false,is_file(CACHE.$area.'/all-marker'));ensure(is_dir(CACHE.$area));}
   same(false,$files->get('all-fixture'));same(false,$cache->get('all-fixture'));same('keep',file_get_contents(CACHE.'admin/audit-marker'));
  }finally{$cache=$previous;}
 });
}finally{session_write_close();$output=ob_get_clean();$clean=function($p)use(&$clean){if(is_link($p)||is_file($p)){unlink($p);return;}foreach(new FilesystemIterator($p) as $f)$clean($f->getPathname());rmdir($p);};$clean($scratch);}
echo $output;echo "Passed: $passed; Failed: $failed\n";exit($failed?1:0);
