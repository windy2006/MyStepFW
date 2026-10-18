<?php
/**
 * 插件公共逻辑回归。PHP 7.4 CLI执行；全部写入随机临时目录，仅使用SQLite测试库。
 * 不执行真实安装/升级，不连接远程更新服务，不修改现有插件配置和菜单。
 */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$source=dirname(__DIR__, 2).'/';$scratch=sys_get_temp_dir().'/mystep-plugins-'.bin2hex(random_bytes(6));mkdir($scratch,0700);
define('ROOT',$scratch.'/');define('LIB',$source.'lib/');define('APP',ROOT.'app/');define('PLUGIN',ROOT.'plugin/');define('CONFIG',ROOT.'config/');define('CACHE',ROOT.'cache/');define('VENDOR',$source.'vendor/');define('ROOT_WEB','/sub/');
foreach([APP.'myStep',APP.'Document',APP.'test',PLUGIN.'manager',PLUGIN.'badReq/template',PLUGIN.'sample',CONFIG,CACHE] as $path)mkdir($path,0700,true);
spl_autoload_register(function($class){foreach([LIB.$class.'.class.php',LIB.'database/'.$class.'.class.php',LIB.'cache/'.$class.'.class.php'] as $file)if(is_file($file)){require_once $file;return;}});
require_once LIB.'function.php';require_once $source.'plugin/interface_plugin.class.php';
foreach(['manager/class.php','badReq/class.php','badReq/template/block_pages.tpl'] as $file)copy($source.'plugin/'.$file,PLUGIN.$file);
require_once PLUGIN.'manager/class.php';require_once PLUGIN.'badReq/class.php';require_once $source.'plugin/frontend/class.php';require_once $source.'plugin/sample/class.php';require_once $source.'plugin/myBackup/class.php';
$_SERVER['DOCUMENT_ROOT']='/';$_SERVER['HTTP_HOST']='example.test';$_SERVER['REQUEST_TIME']=time();
file_put_contents(CONFIG.'config.php',"<?php return ['gen'=>['path_admin'=>'manage'],'router'=>['mode'=>'rewrite','default_app'=>'myStep']];");
$ms_setting=$S=new myConfig(CONFIG.'config.php');$info_app=['app'=>'myStep','path'=>[]];
set_error_handler(function($n,$s,$f,$l){if(error_reporting()&$n)throw new ErrorException($s,0,$n,$f,$l);});
class FrontendHarness {
    protected $css=['bootstrap'=>'/fixture/bootstrap.css'],$js=['jq'=>'/fixture/jquery.js','bs'=>'/fixture/bootstrap.bundle.js','other'=>'/fixture/global.js'];
    private $hooks=[];
    public function setFunction($name,$callback){$this->hooks[$name]=$callback;}
    public function load($file,$config=null){
        if($config===null){include $file;return;}
        $code=file_get_contents($file);
        $code=str_replace("new myConfig(__DIR__.'/config.php')",'new myConfig('.var_export($config,true).')',$code);
        $code=str_replace('__DIR__',var_export(dirname($file),true),$code);eval('?>'.$code);
    }
    public function run(){($this->hooks['start'])();return [$this->css,$this->js];}
}
$passed=$failed=0;
function same($a,$b){if($a!==$b)throw new RuntimeException('Unexpected value');}
function check($value){if(!$value)throw new RuntimeException('Assertion failed');}
function rejects($call){try{$call();}catch(InvalidArgumentException $e){return;}throw new RuntimeException('Expected rejection');}
function test($name,$run){global $passed,$failed;try{$run();$passed++;echo 'PASS '.$name.PHP_EOL;}catch(Throwable $e){$failed++;echo 'FAIL '.$name.': '.$e->getMessage().PHP_EOL;}}
try {
    file_put_contents(APP.'myStep/menu.json',json_encode([['name'=>'other','items'=>[]],['name'=>'plugins','items'=>[]]]));
    test('plugin menu installs idempotently and preserves other groups',function(){
        addPluginLink('one','sample_route');addPluginLink('renamed','sample_route');addPluginLink('other','frontend');
        $menu=json_decode(file_get_contents(APP.'myStep/menu.json'),true);same(2,count($menu));same(2,count($menu[1]['items']));same('renamed',$menu[1]['items'][0]['name']);
    });
    test('uninstall removes menu and uses actual framework settings',function(){removePluginLink('sample_route');removePluginLink('frontend');$menu=json_decode(file_get_contents(APP.'myStep/menu.json'),true);same([],$menu[1]['items']);same('manage/function/plugin',$menu[1]['link']);});
    test('invalid menu does not get overwritten',function(){file_put_contents(APP.'myStep/menu.json','bad json');try{addPluginLink('x','x');}catch(UnexpectedValueException $e){}same('bad json',file_get_contents(APP.'myStep/menu.json'));});
    foreach(['../manager','x/y','sample?x',''] as $bad)test('plugin identifier rejects '.json_encode($bad),function()use($bad){rejects(function()use($bad){pluginDirectory($bad);});});
    test('query helper retains route query and fragment',function(){same('/sub/index.php?badReq/&k=A%2BB&page=2#x',pluginQueryURL('/sub/index.php?badReq/&k=old#x',['k'=>'A+B','page'=>2]));same('/a#x',pluginQueryURL('/a?x=1#x',['x'=>null]));});
    foreach(['rewrite','path_info','query_string'] as $mode)test('badReq pagination '.$mode,function()use($mode,$S){$S->router->mode=$mode;list($p,$offset,$size)=plugin_badReq::pages(45,999,20,['keyword'=>'A+B &中']);same(3,$p['page_current']);same(40,$offset);same(20,$size);check(strpos($p['link_first'],'keyword=A%2BB%20%26%E4%B8%AD')!==false);list($p,$offset,$size)=plugin_badReq::pages(0,-5,0);same(1,$p['page_current']);same(0,$offset);same(20,$size);});
    test('badReq redacts nested credentials without modifying input',function(){$input=['username'=>'demo','password'=>'secret','sub'=>['csrf_token'=>'secret','value'=>'safe']];$out=plugin_badReq::redact($input);same('[redacted]',$out['password']);same('[redacted]',$out['sub']['csrf_token']);same('safe',$out['sub']['value']);same('secret',$input['password']);});
    test('badReq bans persist unban before returning',function(){file_put_contents(PLUGIN.'badReq/ban_ip.php','<?php return [];');plugin_badReq::bans(function($v){$v['2001:db8::1']='message';return $v;});$stored=include PLUGIN.'badReq/ban_ip.php';same('message',$stored['2001:db8::1']);plugin_badReq::bans(function($v){unset($v['2001:db8::1']);return $v;});same([],include PLUGIN.'badReq/ban_ip.php');});
    test('SQLite recording isolates exact IPv4 and IPv6 addresses',function(){
        $now=time();$a=['path'=>"/quote'page",'query'=>"k='value"];same(1,plugin_badReq::record('10.0.0.1',$a,"UA'",$now));same(1,plugin_badReq::record('10.0.0.10',$a,"UA'",$now));same(2,plugin_badReq::record('10.0.0.1',$a,'other UA',$now));same(1,plugin_badReq::record('2001:db8::1',$a,'IPv6',$now));
    });
    test('real badReq pagination template compiles escaped links',function(){
        global $S;$S->router->mode='query_string';file_put_contents(PLUGIN.'badReq/template/test.tpl','<!--pages count="45" page="2" size="20" query="\'keyword=A%2BB\'"-->');
        $t=new myTemplate(['path'=>PLUGIN.'badReq/template','name'=>'test','style'=>'','path_compile'=>CACHE.'compiled']);$t->regTag('pages','plugin_badReq::parsePages');$html=$t->render('',false);check(strpos($html,'keyword=A%2BB&amp;page=3')!==false);
    });
    test('frontend accepts current and historical escaped JSON',function(){$raw='{"Document":{"jq":"jquery-3.6.1.js","bs":"5.2.2"}}';same(plugin_frontend::selections($raw),plugin_frontend::selections(addslashes($raw)));same([],plugin_frontend::selections('{}'));});
    foreach(['bad json','null','{"Document":{"jq":"../../config.php","bs":"5.2.2"}}','{"Document":{"jq":"jquery-9.9.9.js","bs":"5.2.2"}}'] as $json)test('frontend rejects invalid selection '.substr($json,0,20),function()use($json){rejects(function()use($json){plugin_frontend::selections($json);});});
    test('real frontend startup hook uses current application and valid files',function()use($source){
        $GLOBALS['info_app']=['app'=>'Document'];$GLOBALS['I']=['app'=>'unrelated'];
        foreach([['jquery-1.8.3.js','2.3.2'],['jquery-2.2.4.js','3.4.1'],['jquery-3.6.1.js','5.2.2']] as $choice) {
            $config=ROOT.'frontend-fixture.php';file_put_contents($config,'<?php return '.var_export(['detail'=>json_encode(['Document'=>['jq'=>$choice[0],'bs'=>$choice[1]]])],true).';');
            $h=new FrontendHarness();$h->load($source.'plugin/frontend/index.php',$config);list($css,$js)=$h->run();
            check(strpos(implode(' ',$js),$choice[0])!==false);check(strpos(implode(' ',$css),$choice[1].'/css/bootstrap.css')!==false);
            foreach($js as $file) if(strpos($file,'/fixture/')!==0)check(is_file($file));
            if(strpos($choice[0],'3.6.1')!==false)check(strpos(implode(' ',$js),'jquery-migrate-3.4.0.js')!==false);
        }
        $GLOBALS['info_app']=['app'=>'myStep'];
    });
    test('jQuery migrations match major versions',function(){check(strpos(plugin_frontend::migrate('jquery-3.6.1.js'),'3.4.0')!==false);check(strpos(plugin_frontend::migrate('jquery-2.2.4.js'),'1.4.1')!==false);same('',plugin_frontend::migrate('jquery-1.8.3.js'));});
    test('update server enforces authenticated HTTPS transport',function(){same('https://updates.test',plugin_manager::updateBase('updates.test'));same('https://updates.test/base',plugin_manager::updateBase('https://updates.test/base/'));foreach(['http://updates.test','https://u:p@updates.test','https://updates.test?q=x',"updates.test\n"] as $bad)rejects(function()use($bad){plugin_manager::updateBase($bad);});});
    test('manager presentation resolves three route modes without changing the active request',function(){
        mkdir(APP.'Admin/module',0700,true);file_put_contents(APP.'Admin/info.php','<?php return [];');file_put_contents(APP.'Admin/plugin_layout.php','<?php return [];');$_SERVER['SCRIPT_NAME']='/sub/index.php';
        $before=[$_GET,$_REQUEST,$GLOBALS['info_app']];
        foreach(['http://example.test/sub/Admin/error','http://example.test/sub/index.php/Admin/error','http://example.test/sub/index.php?Admin/error/&x=1'] as $ref)same('Admin',plugin_manager::pageApplication($ref));
        same($before,[$_GET,$_REQUEST,$GLOBALS['info_app']]);
    });
    test('manager presentation defaults to myStep for absent invalid external or unsupported sources',function(){
        foreach(['',null,false,[], 'https://foreign.test/sub/Admin/','http://example.test.evil/sub/Admin/','http://user@example.test/sub/Admin/','javascript:Admin','http://example.test/other/Admin/','http://example.test/submarine/Admin/','http://example.test/sub/Administer/','http://example.test/sub/console/error','http://example.test/sub/Admin/%2e%2e/config','http://example.test/sub/index.php?target=Admin','http://example.test/sub/Document/'] as $ref)same('myStep',plugin_manager::pageApplication($ref));
    });
    test('manager presentation supports registered domain bindings and installed application adapters',function(){
        $GLOBALS['domain']=['admin.example.test'=>'Admin'];
        try {same('Admin',plugin_manager::pageApplication('http://admin.example.test/sub/setting'));same('Admin',plugin_manager::pageApplication('http://admin.example.test/'));same('myStep',plugin_manager::pageApplication('http://admin.example.test/sub/manage/error'));mkdir(APP.'Custom/module',0700,true);file_put_contents(APP.'Custom/info.php','<?php return [];');file_put_contents(APP.'Custom/plugin_layout.php','<?php return [];');same('Custom',plugin_manager::pageApplication('http://example.test/sub/Custom/tools'));unlink(APP.'Custom/plugin_layout.php');same('myStep',plugin_manager::pageApplication('http://example.test/sub/Custom/tools'));}
        finally {unset($GLOBALS['domain']);}
    });
    foreach(['../escape','/../escape','a/../../escape','a//file','C:/file','a\\file','config/config.php','cache/a','a/.env'] as $bad)test('manager path rejects '.json_encode($bad),function()use($bad){rejects(function()use($bad){plugin_manager::relativePath($bad);});});
    test('update paths allow library cache/session code while preserving runtime boundaries',function(){
        foreach(['lib/cache/myCacheCodec.class.php','lib/session/sess_file.class.php'] as $name) same($name,plugin_manager::relativePath('/'.$name));
        foreach(['cache/file','session/file','app/test/cache/file','app/test/session/file','lib/cache/cache/file','lib/session/session/file','lib/cache/config.php','lib/cache/data.db'] as $name) rejects(function()use($name){plugin_manager::relativePath($name);});
    });
    test('update paths reject existing symlink ancestors',function(){symlink(sys_get_temp_dir(),ROOT.'outside');rejects(function(){plugin_manager::sourcePath('outside/file');});});
    test('update decoder preserves zero contents and validates all members',function(){
        $data=['file'=>['/lib/new.php'],'content'=>['0'],'setting'=>[],'code'=>[]];$result=plugin_manager::decodeUpdate(gzdeflate(serialize($data)));same('0',$result['content'][0]);
        $data['file'][]='../escape';$data['content'][]='x';rejects(function()use($data){plugin_manager::decodeUpdate(gzdeflate(serialize($data)));});
        rejects(function(){plugin_manager::decodeUpdate('not a package');});
    });
    test('serialized objects in update settings are rejected without instantiation',function(){
        $data=['file'=>[],'content'=>[],'setting'=>['x'=>(object)['key'=>'value']],'code'=>[]];rejects(function()use($data){plugin_manager::decodeUpdate(gzdeflate(serialize($data)));});
    });
    test('package menu replacement preserves online files and binary members',function(){
        $dir=ROOT.'package-source';mkdir($dir);file_put_contents($dir.'/menu.json','online-menu');$binary="data\0dir";file_put_contents($dir.'/binary.dat',$binary);
        $package=ROOT.'package.pack';$packer=new myPacker($dir,$package);check($packer->pack());
        check(plugin_manager::replacePackedFile($package,'menu.json','default-menu'));same('online-menu',file_get_contents($dir.'/menu.json'));
        $out=ROOT.'package-output';$unpack=new myPacker($out,$package);check($unpack->unpack(false));same('default-menu',file_get_contents($out.'/menu.json'));same($binary,file_get_contents($out.'/binary.dat'));
    });
    test('invalid package replacement keeps the original archive bytes',function(){
        $file=ROOT.'invalid-package.pack';$before=gzcompress("file|bad|100\nx");file_put_contents($file,$before);
        same(false,plugin_manager::replacePackedFile($file,'menu.json','default-menu'));same($before,file_get_contents($file));
    });
    test('packaged config redacts each password from the root cursor',function(){
        mkdir(ROOT.'fixture/config',0700,true);$config=['email'=>['password'=>'mail-secret'],'db'=>['password'=>'db-secret']];file_put_contents(ROOT.'fixture/config.php','<?php return '.var_export($config,true).';');file_put_contents(ROOT.'fixture/config/construction.php',"<?php return ['password'=>['email.password','db.password','func'=>['db.password'=>'_']]];");$before=file_get_contents(ROOT.'fixture/config.php');plugin_manager::checkConfig(ROOT.'fixture');$out=include ROOT.'fixture/config_new.php';same('',$out['email']['password']);same('',$out['db']['password']);same($before,file_get_contents(ROOT.'fixture/config.php'));
    });
    test('checksum scan skips ignored directory suffixes and symlinks',function(){
        mkdir(ROOT.'lib/cache',0700,true);mkdir(ROOT.'lib/session',0700,true);file_put_contents(ROOT.'lib/cache/driver.php','driver');file_put_contents(ROOT.'lib/session/driver.php','driver');
        mkdir(ROOT.'.git');file_put_contents(ROOT.'.git/secret','do not include');file_put_contents(CACHE.'secret','do not include');file_put_contents(ROOT.'visible.txt','one');
        check(plugin_manager::checkFile(ROOT,true));$list_file=[];$list_file_md5=[];include PLUGIN.'manager/check_file.php';if(!in_array('/visible.txt',$list_file,true)) throw new RuntimeException('Visible file missing: '.json_encode($list_file));check(!in_array('/.git/secret',$list_file,true));check(!in_array('/cache/secret',$list_file,true));check(in_array('/lib/cache/driver.php',$list_file,true));check(in_array('/lib/session/driver.php',$list_file,true));
        $result=plugin_manager::checkFile();if($result['new'] || $result['mod'] || $result['miss']) throw new RuntimeException(json_encode($result));
    });
    test('sample rate window is fixed and expires exactly at 60 seconds',function(){
        $state=plugin_sample::rate(null,1000);same(1,$state['count']);
        $state=plugin_sample::rate(json_encode(['start'=>1000,'count'=>29]),1059);same(true,$state['allowed']);same(30,$state['count']);same(1,$state['retry']);
        $state=plugin_sample::rate(json_encode(['start'=>1000,'count'=>30]),1059);same(false,$state['allowed']);
        $state=plugin_sample::rate(json_encode(['start'=>1000,'count'=>30]),1060);same(true,$state['allowed']);same(1,$state['count']);
    });
    test('sample public API excludes password and keeps public fields',function(){
        $out=plugin_sample::api();same(false,array_key_exists('para_6',$out));same(['para_1','para_2','para_3','para_4','para_5','para_7','para_8'],array_keys($out));
    });
    test('frontend tolerant runtime ignores stale apps without disabling valid choices',function(){
        $json=json_encode(['Missing'=>['jq'=>'jquery-3.6.1.js','bs'=>'5.2.2'],'Document'=>['jq'=>'jquery-3.6.1.js','bs'=>'5.2.2']]);
        rejects(function()use($json){plugin_frontend::selections($json);});same(['Document'],array_keys(plugin_frontend::selections($json,false)));
        $file=ROOT.'stale-frontend.php';file_put_contents($file,'<?php return '.var_export(['detail'=>$json],true).';');$GLOBALS['info_app']=['app'=>'Document'];
        $host=new FrontendHarness();$host->load(dirname(__DIR__,2).'/plugin/frontend/index.php',$file);list($css,$js)=$host->run();check(strpos(implode(' ',$js),'jquery-3.6.1.js')!==false);
    });
    test('badReq rejects corrupt persisted lists and preserves original bytes',function(){
        $file=PLUGIN.'badReq/ban_ip.php';file_put_contents($file,'<?php return false;');$before=file_get_contents($file);
        try{plugin_badReq::bans(function($list){return $list;});throw new RuntimeException('Corrupt list accepted');}catch(UnexpectedValueException $e){}
        same($before,file_get_contents($file));file_put_contents($file,'<?php return [];');
    });
    test('badReq unchanged bans avoid publishing a replacement',function(){
        $file=PLUGIN.'badReq/ban_ip.php';touch($file,1000000000);plugin_badReq::bans(function($list){return $list;});clearstatcache(true,$file);same(1000000000,filemtime($file));
    });
    test('backup capacity parser honors units POST limits and unlimited settings',function(){
        same(2*1048576,plugin_myBackup::uploadLimit('2M','8M'));same(1048576,plugin_myBackup::uploadLimit('8M','1M'));same(2*1048576,plugin_myBackup::uploadLimit('0','2M'));same(PHP_INT_MAX,plugin_myBackup::uploadLimit('0','0'));same(3*1073741824,plugin_myBackup::uploadLimit('3G','-1'));
    });
    test('manager package publisher accepts small archives and reports corrupt packages',function(){
        $source=ROOT.'small-package';mkdir($source);file_put_contents($source.'/one.txt','0');$archive=ROOT.'small.pack';$packer=new myPacker($source,$archive);check($packer->pack());check(filesize($archive)<1024);
        same(true,plugin_manager::installPackage('plugin','newDemo',$archive));same('0',file_get_contents(PLUGIN.'newDemo/one.txt'));check(is_file($archive));
        file_put_contents($archive,'not an archive');same(false,plugin_manager::installPackage('plugin','newDemo',$archive));same('0',file_get_contents(PLUGIN.'newDemo/one.txt'));
        rejects(function()use($archive){plugin_manager::installPackage('plugin','../escape',$archive);});
        symlink(sys_get_temp_dir(),PLUGIN.'linkedDemo');rejects(function()use($archive){plugin_manager::installPackage('plugin','linkedDemo',$archive);});
    });
    test('manager artifact links use authenticated routes and reject paths outside their scope',function()use($S){
        $name='update_0123456789abcdef.zip';$file=plugin_manager::artifactPath($name);same(realpath(CACHE).'/tmp/manager/'.$name,$file);
        foreach(['rewrite','path_info','query_string'] as $mode){$S->router->mode=$mode;$url=plugin_manager::artifactURL($file);check(strpos($url,'manager/file/')!==false);check(strpos($url,'name='.$name)!==false);same(false,strpos($url,'/cache/tmp/')!==false);}
        foreach(['../a.zip','x.zip','update_0123456789abcdef.zip/../../config.php'] as $bad)rejects(function()use($bad){plugin_manager::artifactPath($bad);});
        rejects(function()use($name){plugin_manager::artifactURL(ROOT.$name);});
    });
    test('public difference downloads require the matching unexpired random credential',function(){
        $file=plugin_manager::artifactPath('difference_fedcba9876543210.zip');myFile::saveFile($file,'fixture');$url=plugin_manager::artifactURL($file,true);parse_str(parse_url($url,PHP_URL_QUERY),$query);
        if(strpos($url,'index.php?')!==false){$queryText=explode('&',parse_url($url,PHP_URL_QUERY),2);parse_str($queryText[1] ?? '',$query);}
        check(strpos($url,'api/plugin_manager/artifact/')!==false);same($file,plugin_manager::publicArtifact($query['name'],$query['token']));same(false,plugin_manager::publicArtifact($query['name'],str_repeat('0',48)));
        same(false,plugin_manager::publicArtifact('update_fedcba9876543210.zip',$query['token']));same(false,plugin_manager::publicArtifact('../escape',$query['token']));
        $metadata=json_decode(file_get_contents($file.'.download'),true);check(strpos(file_get_contents($file.'.download'),$query['token'])===false);$metadata['expires']=time()-1;file_put_contents($file.'.download',json_encode($metadata));same(false,plugin_manager::publicArtifact($query['name'],$query['token']));
    });
    test('sample page uses deployment and route prefixes in all URL modes',function()use($S){
        foreach(['rewrite','path_info','query_string'] as $mode){$S->router->mode=$mode;$tpl_sub=new myTemplate(['path'=>dirname(__DIR__,2).'/plugin/sample/template','style'=>'','name'=>'sample','path_compile'=>CACHE.'sample/'.$mode]);$tpl_sub->assign(['url_prefix'=>myStep::setURL(),'root_web'=>ROOT_WEB]);include dirname(__DIR__,2).'/plugin/sample/module/sample.php';$html=$tpl_sub->render('',false);
            check(strpos($html,'href="'.str_replace('?/', '?', rtrim(myStep::setURL('module/test1'),'/')).'"')!==false);check(strpos($html,'href="'.str_replace('?/', '?', rtrim(myStep::setURL('api/plugin_sample/sample/json'),'/')).'"')!==false);check(strpos($html,'src="'.ROOT_WEB.'vendor/tinymce/tinymce.min.js"')!==false);same(false,strpos($html,'api.mystep.com')!==false);
        }
    });
    test('plugin mutations require POST across routing modes',function()use($S){
        foreach(['/manager/build','/manager/download?m=1','/manager/plugin/sample','/pack/','/badReq?m=reset','/sub/index.php?badReq/&m=unban'] as $url)check(mySecurity::mutates($url));
        foreach(['/manager/','/manager/check_local','/manager/check_server','/badReq?keyword=ban','/CMS/catalog/pack/','/CMS/article/manager/build'] as $url)same(false,mySecurity::mutates($url));
    });
} finally {
    $walk=function($path)use(&$walk){if(is_link($path)||is_file($path)){unlink($path);return;}foreach(new FilesystemIterator($path) as $file)$walk($file->getPathname());rmdir($path);};$walk($scratch);
}
echo "Passed: $passed; Failed: $failed\n";exit($failed?1:0);
