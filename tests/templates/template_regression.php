<?php
// PHP 7.4 CLI regression coverage for template parsing, isolation and cache invalidation.
if(PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root = rtrim(getenv('MYSTEP_SOURCE') ?: dirname(__DIR__, 2), '/').'/';
define('ROOT', $root); define('LIB', ROOT.'lib/'); define('VENDOR', ROOT.'vendor/');
$_SERVER['DOCUMENT_ROOT'] = '/'; $_SERVER['REQUEST_TIME'] = time();
spl_autoload_register(function($class) { $file = LIB.$class.'.class.php'; if(is_file($file)) require_once $file; });
require_once LIB.'function.php';
set_error_handler(function($n,$s,$f,$l) { if(error_reporting() & $n) throw new ErrorException($s,0,$n,$f,$l); });
$scratch = sys_get_temp_dir().'/mystep-template-'.bin2hex(random_bytes(8)); mkdir($scratch,0700,true);
$passed = $failed = 0; $number = 0;
function same($expected,$actual) { if($expected !== $actual) throw new RuntimeException('Expected '.var_export($expected,true).'; got '.var_export($actual,true)); }
function raises($type,$run) { try { $run(); } catch(Throwable $e) { if($e instanceof $type) return; throw $e; } throw new RuntimeException('Expected '.$type); }
function test($name,$run) { global $passed,$failed; try { $run(); $passed++; echo 'PASS '.$name.PHP_EOL; } catch(Throwable $e) { $failed++; echo 'FAIL '.$name.': '.$e->getMessage().PHP_EOL; } }
function fixture($content,$cache=false,$settings=[],$script=false,$delimiters=null) {
    global $scratch,$number;
    $dir=$scratch.'/'.(++$number); mkdir($dir); file_put_contents($dir.'/main.tpl',$content);
    $settings += ['name'=>'main','path'=>$dir,'path_compile'=>$dir.'/compiled'];
    return [new myTemplate($settings,$cache,$script,$delimiters),$dir,$settings];
}
function cleanTree($path) { if(is_dir($path)) { foreach(new FilesystemIterator($path) as $p) cleanTree($p->getPathname()); rmdir($path); } elseif(is_file($path)) unlink($path); }
class TemplateStateProbe extends myTemplate { private $label = 'default'; public function setLabel($value) { $this->label=$value; } public function label() { return $this->label; } }
ob_start();
try {
    test('simple and absent variables',function(){list($t)=fixture('<!--name-->|<!--missing-->');$t->assign('name','OK');same('OK|',$t->render('',false));});
    test('same-file instances isolate assigned values',function(){list($a,$d,$s)=fixture('<!--x-->');$b=new myTemplate($s);$a->assign('x','A');$b->assign('x','B');same('A',$a->render('',false));same('B',$b->render('',false));});
    test('shared compiled file receives new instance data',function(){list($a,$d,$s)=fixture('<!--x-->');$a->assign('x','one')->render('',false);$b=new myTemplate($s);same('two',$b->assign('x','two')->render('',false));});
    test('clone has independent data',function(){list($a)=fixture('<!--x-->');$a->assign('x','A');$b=clone $a;$b->assign('x','B');same('A',$a->render('',false));same('B',$b->render('',false));});
    test('destruction releases only owned static data',function(){ $n=count(myTemplate::$tpl_para);list($a)=fixture('A');list($b)=fixture('B');unset($a);same($n+1,count(myTemplate::$tpl_para));same('B',$b->render('',false));unset($b);same($n,count(myTemplate::$tpl_para));});
    test('config may be loaded twice without global function declaration',function(){list($t,$d)=fixture('<!--a_x-->|<!--b_y-->');file_put_contents($d.'/one.json','{"x":"a=b"}');file_put_contents($d.'/two.json','{"y":"C:\\\\tmp"}');$t->loadSet($d.'/one.json','a')->loadSet($d.'/two.json','b');same('a=b|C:\\tmp',$t->render('',false));});
    test('nested config keys retain section names',function(){list($t,$d)=fixture('<!--pre_a_x-->|<!--pre_b_x-->');file_put_contents($d.'/a.php','<?php return ["a"=>["x"=>1],"b"=>["x"=>2]];');same('1|2',$t->loadSet($d.'/a.php','pre',true)->render('',false));});
    test('config must return a mapping',function(){list($t,$d)=fixture('A');file_put_contents($d.'/a.json','123');raises(UnexpectedValueException::class,function()use($t,$d){$t->loadSet($d.'/a.json');});});
    test('literal money braces backslashes and heredoc marker survive loops',function(){ $text='${literal} {$value} C:\\new\\test'."\ncontent;\n\n";list($t)=fixture('<!--loop:start key="r"-->'.$text.'<!--r_x--><!--loop:end-->');$t->setLoop('r',['x'=>'OK']);same($text.'OK',$t->render('',false));});
    test('literal text survives if switch and random',function(){list($t)=fixture('<!--if:start key="yes"-->$a\\b<!--if:end--><!--switch:start key="s"--><!--(1)-->$c<!--switch:end--><!--random:start-->{$d}<!--random:end-->');$t->setIf('yes')->setSwitch('s',1);same('$a\\b$c{$d}',$t->render('',false));});
    test('preserves CRLF and blank lines',function(){list($t)=fixture("a\r\n\r\nb\n\n");same("a\r\n\r\nb\n\n",$t->render('',false));});
    test('loop rows need not share first row schema',function(){list($t)=fixture('<!--loop:start key="r"--><!--r_a-->/<!--r_b-->;<!--loop:end-->');$t->setLoop('r',[['a'=>1],['b'=>2]],true);same('1/;/2;',$t->render('',false));});
    test('empty compilation accepts data in later render',function(){list($t)=fixture('<!--loop:start key="r"--><!--r_x--><!--loop:end-->');same('',$t->render('',false));same('X',$t->setLoop('r',['x'=>'X'])->render('',false));});
    test('associative and object rows plus iterable collection',function(){list($t)=fixture('<!--loop:start key="r"--><!--r_x--><!--loop:end-->');$t->setLoop('r',new ArrayIterator(['k'=>(object)['x'=>1],'j'=>['x'=>2]]),true);same('12',$t->render('',false));});
    test('nested loops keep inner and outer rows',function(){list($t)=fixture('<!--loop:start key="a"--><!--a_x-->[<!--loop:start key="b"--><!--a_x--><!--b_y--><!--loop:end-->]<!--a_x--><!--loop:end-->');$t->setLoop('a',[['x'=>'A'],['x'=>'B']],true)->setLoop('b',[['y'=>1],['y'=>2]],true);same('A[A1A2]AB[B1B2]B',$t->render('',false));});
    test('nested if else binds nearest block',function(){list($t)=fixture('<!--if:start key="a"-->A<!--if:start key="b"-->B<!--else-->C<!--if:end--><!--else-->D<!--if:end-->');same('AC',$t->setIf('a')->render('',false));same('D',$t->setIf('a',false)->render('',false));});
    test('case content can contain variables and nested blocks',function(){list($t)=fixture('<!--switch:start key="s"--><!--(1)-->A<!--x--><!--if:start key="y"-->Y<!--if:end--><!--(2)-->B<!--switch:end-->');$t->setSwitch('s',1)->setIf('y')->assign('x','X');same('AXY',$t->render('',false));});
    test('explicit default switch branch',function(){list($t)=fixture('<!--switch:start key="s"--><!--(1)-->A<!--switch:default-->D<!--switch:end-->');same('D',$t->setSwitch('s',2)->render('',false));});
    test('random selects a complete branch',function(){list($t)=fixture('<!--random:start-->A<!--(s)-->B<!--(s)-->C<!--random:end-->');for($i=0;$i<12;$i++)same(true,in_array($t->render('',false),['A','B','C'],true));});
    test('case-insensitive block names',function(){list($t)=fixture('<!--IF:START KEY="yes"-->Y<!--ELSE-->N<!--IF:END-->');same('Y',$t->setIf('yes')->render('',false));});
    test('loop time only pads to minimum row count',function(){list($t)=fixture('<!--loop:start key="r" time="2"--><li><!--r_x--></li><!--loop:end-->');same('<li>X</li><li>&nbsp;</li>',$t->setLoop('r',['x'=>'X'])->render('',false));$t->setLoop('r',[['x'=>1],['x'=>2],['x'=>3]],true);same('<li>1</li><li>2</li><li>3</li>',$t->render('',false));});
    test('malformed block reports error without damaging outer buffer',function(){list($t)=fixture('<!--if:start key="x"-->A');$level=ob_get_level();$old=ob_get_contents();raises(UnexpectedValueException::class,function()use($t){$t->render('',false);});same($level,ob_get_level());same($old,ob_get_contents());});
    test('crossed blocks are rejected',function(){list($t)=fixture('<!--if:start--><!--loop:start--><!--if:end--><!--loop:end-->');raises(UnexpectedValueException::class,function()use($t){$t->render('',false);});});
    test('PHP tags including multiline short echo are stripped by default',function(){list($t)=fixture("A<?=\n'BAD'\n?>B<?php echo 'BAD'; ?>C");same('ABC',$t->render('',false));});
    test('PHP-like text and XML declaration stay literal',function(){list($t)=fixture('<?xml version="1.0"?> <? echo "not-executed"; ?>');same('<?xml version="1.0"?> ',$t->render('',false));});
    test('script opt-in is part of compiled identity',function(){list($a,$d,$s)=fixture('<?php echo "OK";?>');same('',$a->render('',false));$b=new myTemplate($s,false,true);same('OK',$b->render('',false));same('',$a->render('',false));});
    test('custom delimiters and standard compile file can coexist',function(){list($a,$d,$s)=fixture('[[x]]<!--x-->');$a->assign('x','A');same('[[x]]A',$a->render('',false));$b=new myTemplate($s,false,false,['[[',']]']);same('B<!--x-->',$b->assign('x','B')->render('',false));same('[[x]]A',$a->render('',false));});
    test('HTML and URL component filters are opt-in',function(){list($t)=fixture('<!--x-->|<!--x|html-->|<!--x|url-->');$t->assign('x','<a "b"> &');same('<a "b"> &|&lt;a &quot;b&quot;&gt; &amp;|%3Ca%20%22b%22%3E%20%26',$t->render('',false));});
    test('HTML assignment supports prefixed batches',function(){list($t)=fixture('<!--p_a-->|<!--p_b-->');same('&lt;|&quot;',$t->assignEscaped('p',['a'=>'<','b'=>'"'])->render('',false));});
    test('original template updates detected in same second and same object',function(){list($t,$d)=fixture('AAAA');same('AAAA',$t->render('',false));$mtime=filemtime($d.'/main.tpl');file_put_contents($d.'/main.tpl','BBBB');touch($d.'/main.tpl',$mtime);same('BBBB',$t->render('',false));});
    test('compile directory joins style without trailing slash',function(){list($t,$d,$s)=fixture('D');mkdir($d.'/blue');file_put_contents($d.'/blue/main.tpl','B');$s['style']='blue';$t=new myTemplate($s);same('B',$t->render('',false));same(true,is_file($d.'/compiled/blue/main.php'));});
    test('relative name traversal is rejected',function(){list($t,$d,$s)=fixture('D');foreach(['../out','a/../out','/out','a\\..\\out','php://out','a*','a[1]']as$name){$s['name']=$name;raises(InvalidArgumentException::class,function()use($s){new myTemplate($s);});}});
    test('true and empty array enable page cache',function(){foreach([true,[]]as$cache){list($t)=fixture('<!--x-->',$cache);same('A',$t->assign('x','A')->render('',false));same(true,$t->checkCache());same('A',$t->assign('x','B')->render('',false));}});
    test('zero and empty output are valid cached pages',function(){foreach(['0','']as$content){list($t)=fixture($content,true);same($content,$t->render('',false));same(true,$t->checkCache());same($content,$t->getCache());}});
    test('nonpositive page expiry disables reuse',function(){list($t)=fixture('<!--x-->',['expire'=>0]);same('A',$t->assign('x','A')->render('',false));same(false,$t->checkCache());same('B',$t->assign('x','B')->render('',false));});
    test('expiry uses present time and not request start',function(){list($t,$d)=fixture('A',['expire'=>5]);$t->render('',false);$file=glob($d.'/cache/*.html')[0];touch($file,time()-6);$_SERVER['REQUEST_TIME']=time()-100;same(false,$t->checkCache());$_SERVER['REQUEST_TIME']=time();});
    test('page cache invalidates on same-size source edit',function(){list($t,$d)=fixture('A',true);$t->render('',false);file_put_contents($d.'/main.tpl','B');same(false,$t->checkCache());same('B',$t->render('',false));});
    test('page cache vary separates language or user dimensions',function(){list($a,$d,$s)=fixture('<!--x-->',['vary'=>['lang'=>'zh']]);$a->assign('x','ZH')->render('',false);$b=new myTemplate($s,['vary'=>['lang'=>'en']]);same(false,$b->checkCache());same('EN',$b->assign('x','EN')->render('',false));same('ZH',$a->getCache());});
    test('sanitized cache names cannot collide',function(){list($a,$d,$s)=fixture('<!--x-->',['name'=>'a-b']);$a->assign('x','A')->render('',false);$b=new myTemplate($s,['name'=>'ab']);same(false,$b->checkCache());});
    test('removeCache optionally removes HTML metadata',function(){list($t,$d)=fixture('A',true);$t->render('',false);$t->removeCache(true);same(false,$t->checkCache());same([],glob($d.'/compiled/main.php*'));});
    test('corrupt cache metadata causes recompile',function(){list($t,$d)=fixture('A');$t->render('',false);file_put_contents($d.'/compiled/main.php.meta.json','bad');same('A',$t->render('',false));});
    test('tampered compiled artifact is replaced',function(){list($t,$d)=fixture('A');$t->render('',false);foreach(glob($d.'/compiled/main.php.*.php')as$p)file_put_contents($p,'<?php echo "BAD";');same('A',$t->render('',false));});
    test('custom tag attribute preserves equals empty and backslashes',function(){list($t)=fixture('<!--show a="a=b=c" b="" c="C:\\tmp"-->');$t->regTag('show',function($tpl,&$a){return $a['a'].'|'.$a['b'].'|'.$a['c'];});same('a=b=c||C:\\tmp',$t->render('',false));});
    test('custom tag PHP and placeholders retain existing contract',function(){list($t)=fixture('<!--show value="X"-->');$t->regTag('show',function(){return '<?php echo "A"; ?>[<!--value-->]';});same('A[X]',$t->render('',false));});
    test('custom tag runtime global reference works',function(){list($t)=fixture('<!--show value=\'$template_test_global["v"]\'-->');$GLOBALS['template_test_global']=['v'=>'YES'];$t->regTag('show',function(){return '<!--value-->';});same('YES',$t->render('',false));unset($GLOBALS['template_test_global']);});
    test('custom tag dependencies invalidate compiled and HTML caches',function(){list($t,$d)=fixture('<!--sub name="test"-->',true);file_put_contents($d.'/sub.tpl','A');$t->regTag('sub',function($tpl)use($d){return $tpl->getTemplate($d.'/sub.tpl');});same('A',$t->render('',false));file_put_contents($d.'/sub.tpl','B');same(false,$t->checkCache());same('B',$t->render('',false));});
    test('default style fallback invalidates when preferred file appears',function(){list($a,$d,$s)=fixture('unused');mkdir($d.'/default');file_put_contents($d.'/default/other.tpl','D');$s['name']='other';$s['style']='blue';$t=new myTemplate($s,true);same('D',$t->render('',false));mkdir($d.'/blue');file_put_contents($d.'/blue/other.tpl','B');same(false,$t->checkCache());same('B',$t->render('',false));});
    test('unknown custom tags remain intact',function(){list($t)=fixture('<!--unknown a="x"-->');same('<!--unknown a="x"-->',$t->render('',false));});
    test('runtime exception preserves outer buffer',function(){list($t)=fixture('<?php echo "partial"; throw new RuntimeException("test"); ?>',false,[],true);$level=ob_get_level();$old=ob_get_contents();raises(RuntimeException::class,function()use($t){$t->render('',false);});same($level,ob_get_level());same($old,ob_get_contents());});
    test('global imports do not replace renderer control variables',function(){list($t)=fixture('<?php echo $template_import; ?>',false,[],true);$GLOBALS['template_import']='OK';$GLOBALS['template_script']='BAD';same('OK',$t->render('template_import,template_script',false));unset($GLOBALS['template_import'],$GLOBALS['template_script']);});
    test('minify preserves significant text and JS CSS pre whitespace',function(){ $s="<pre> a\n  b </pre><script>const x = '// x y';\n  const y = 'a  b';</script><style>.x { content: 'a  b'; }</style><span>a  b</span>";list($t)=fixture($s);same($s,$t->render('',false,true));});
    test('native custom getBlock loop remains compatible',function(){list($t)=fixture('A');same(['<!--loop:start--><p><!--r_x--></p><!--loop:end-->','<p>{$r[\'x\']}</p>','<p>&nbsp;</p>'],$t->getBlock('<!--loop:start--><p><!--r_x--></p><!--loop:end-->','loop','r'));});
    test('serialized template restores independent data',function(){list($a)=fixture('<!--x-->');$a->assign('x','A');$b=unserialize(serialize($a));$b->assign('x','B');same('A',$a->render('',false));same('B',$b->render('',false));unset($b);same('A',$a->render('',false));});
    test('getBlock preserves nested loop boundaries',function(){list($t)=fixture('A');$s='<!--loop:start-->X<!--loop:start-->Y<!--loop:end-->Z<!--loop:end-->';same([$s,'X<!--loop:start-->Y<!--loop:end-->Z','XYZ'],$t->getBlock($s,'loop'));});
    test('getBlock switch retains variable comments inside each case',function(){list($t)=fixture('A');list($block,$cases)=$t->getBlock('<!--switch:start--><!--(1)-->A<!--x-->Z<!--(2)-->B<!--switch:end-->','switch');same(['1'=>'A<!--x-->Z','2'=>'B'],$cases);});
    test('getBlock nested if does not consume inner else',function(){list($t)=fixture('A');list($b,$yes,$no)=$t->getBlock('<!--if:start-->A<!--if:start-->B<!--else-->C<!--if:end-->D<!--else-->E<!--if:end-->','if');same('A<!--if:start-->B<!--else-->C<!--if:end-->D',$yes);same('E',$no);});
    test('compiled custom tag callback identity changes are detected',function(){list($a,$d,$s)=fixture('<!--show value="x"-->');$a->regTag('show',function(){return 'A';});same('A',$a->render('',false));$b=new myTemplate($s);$b->regTag('show',function(){return 'B';});same('B',$b->render('',false));same('A',$a->render('',false));});
    test('trusted plugin heredoc unit and block extraction are compatible',function(){list($t,$d)=fixture('<!--show value="x"-->');file_put_contents($d.'/sub.tpl','<!--loop:start--><b><!--r_x--></b><!--loop:end-->');$t->regTag('show',function($tpl)use($d){$s=$tpl->getTemplate($d.'/sub.tpl');list($b,$u)=$tpl->getBlock($s,'loop','r');return str_replace($b,"<?php foreach([['x'=>'A'],['x'=>'B']] as \$r) { echo <<<content\n".$u."\ncontent;\n} ?>",$s);});same('<b>A</b><b>B</b>',$t->render('',false));});
    test('bounded nesting fails explicitly',function(){list($t)=fixture(str_repeat('<!--if:start key="x"-->',66).'A'.str_repeat('<!--if:end-->',66));raises(UnexpectedValueException::class,function()use($t){$t->render('',false);});});
    test('nested custom PHP is only executed in selected standard branch',function(){list($t)=fixture('<!--if:start key="yes"--><!--show value="x"--><!--else-->N<!--if:end-->');$t->regTag('show',function(){return '<?php echo "Y";?>';});same('N',$t->render('',false));same('Y',$t->setIf('yes')->render('',false));});
    test('page cache recompiles after minify option changes',function(){list($t)=fixture("  <p>A</p>",true);same("  <p>A</p>",$t->render('',false,false));same('<p>A</p>',$t->render('',false,true));same("  <p>A</p>",$t->render('',false,false));});
    test('parallel cold compilation returns complete isolated output',function(){
        list($t,$d,$s)=fixture('<!--loop:start key="r"--><b><!--r_x--></b><!--loop:end-->');
        $worker=$d.'/worker.php';
        $code='<?php $_SERVER["DOCUMENT_ROOT"]="/"; spl_autoload_register(function($c){$f='.var_export(LIB,true).'.$c.".class.php";if(is_file($f))require_once $f;}); $t=new myTemplate('.var_export($s,true).');$t->setLoop("r",array_fill(0,100,["x"=>$argv[1]]),true);echo $t->render("",false);';
        file_put_contents($worker,$code);$processes=[];
        for($i=0;$i<8;$i++){ $pipes=[];$p=proc_open([PHP_BINARY,$worker,(string)$i],[1=>['pipe','w'],2=>['pipe','w']],$pipes);$processes[]=[$p,$pipes,$i]; }
        foreach($processes as $item){list($p,$pipes,$i)=$item;$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);same(0,proc_close($p));same('',$err);same(str_repeat('<b>'.$i.'</b>',100),$out);}
    });

    test('registered custom tags accept no attributes',function(){list($t)=fixture('<!--hello-->');$t->regTag('hello',function(){return 'OK';});same('OK',$t->render('',false));});
    test('HTML cache checks registered callback changes',function(){list($t)=fixture('<!--show value="x"-->',true);$t->regTag('show',function(){return 'A';});same('A',$t->render('',false));$t->regTag('show',function(){return 'B';});same(false,$t->checkCache());same('B',$t->render('',false));});
    test('explicit global imports retain PHP reference semantics',function(){list($t)=fixture('<?php $template_mutable["n"]++; ?>',false,[],true);$GLOBALS['template_mutable']=['n'=>1];same('',$t->render('template_mutable',false));same(2,$GLOBALS['template_mutable']['n']);unset($GLOBALS['template_mutable']);});
    test('custom heredoc unit resolves default and supplied attributes safely',function(){list($t,$d)=fixture('<!--show class="a=b"-->');$t->regTag('show',function($tpl,&$a){$a['unit']='<b class="<!--class-->"><!--missing--></b>';return "<?php echo <<<content\n{myTemplate::unit}\ncontent;\n?>";});same('<b class="a=b"></b>',$t->render('',false));});
    test('custom PHP quoted strings use tag values without nested PHP open tags',function(){list($t)=fixture('<!--show a="a=b"-->');$t->regTag('show',function(){return '<?php echo "[<!--a-->]"; echo \'[<!--a-->]\'; ?>';});same('[a=b][a=b]',$t->render('',false));});
    test('custom tag code parameters include numeric callback defaults',function(){list($t)=fixture('<!--show name="x"-->');$t->regTag('show',function($tpl,&$a){$a['limit']=0;return '<?php echo {myTemplate::limit}; ?>';});same('0',$t->render('',false));});
    foreach(['atom','rss','sitemap'] as $endpoint) test('CMS '.$endpoint.' initializes database before cached shutdown',function()use($endpoint,$scratch){
        $worker=$scratch.'/handoff-'.$endpoint.'.php';
        $code=<<<'CODE'
<?php
namespace app\CMS {
    function getCache($name) { return []; }
    function checkVal($data,$key,$value,$part=false) { return ['idx'=>'main','web_id'=>1]; }
}
namespace {
    define('APP', SOURCE_APP); define('CACHE', sys_get_temp_dir().'/');
    class myReq { public static function server($key) { return 'cms.test'; } }
    class myTemplate { public function __construct($setting,$cache) {} }
    class SettingsProbe {
        public $db; public $gen;
        public function __construct() { $this->db=(object)['pre'=>'base_','name'=>'base','pconnect'=>false];$this->gen=(object)['charset'=>'UTF-8']; }
        public function merge($path) { global $events;$site=strpos($path,'/website/')!==false;$events[]=$site?'site':'config';$this->db->pre=$site?'site_':'cms_';$this->db->name='cms_db'; }
    }
    class DatabaseProbe { public $selected='';public function connect($persistent,$database){global $events;$events[]='connect';$this->selected=$database;} }
    class HandoffProbe { public function checkCache($tpl) { global $events,$db,$S;if($events!==['config','site','connect']||$db->selected!=='cms_db'||$S->db->pre!=='cms_'||$S->db->pre_sub!=='site_')throw new \RuntimeException('Cache hit reached shutdown before CMS database initialization');exit('OK'); } }
    $events=[];$S=new SettingsProbe();$db=new DatabaseProbe();$mystep=new HandoffProbe();$app='CMS';$cache=null;
    require ENDPOINT_FILE;
}
CODE;
        $code=str_replace(['SOURCE_APP','ENDPOINT_FILE'],[var_export(ROOT.'app/',true),var_export(ROOT.'app/CMS/'.$endpoint.'.php',true)],$code);
        file_put_contents($worker,$code);$pipes=[];$process=proc_open([PHP_BINARY,$worker],[1=>['pipe','w'],2=>['pipe','w']],$pipes);$out=stream_get_contents($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);same(0,proc_close($process));same('',$error);same('OK',$out);
    });

    test('sanitized name digest cannot be impersonated by a plain name',function(){list($a,$d,$settings)=fixture('<!--x-->',['name'=>'a-b']);$a->assign('x','A')->render('',false);$b=new myTemplate($settings,['name'=>'ab_'.substr(hash('sha256','a-b'),0,16)]);same(false,$b->checkCache());});
    test('vary digest cannot collide with a plain cache name',function(){list($a,$d,$settings)=fixture('<!--x-->',['name'=>'home','vary'=>'zh']);$a->assign('x','A')->render('',false);$b=new myTemplate($settings,['name'=>'home_'.hash('sha256',serialize('zh'))]);same(false,$b->checkCache());});
    test('serialization preserves subclass private state with isolated data',function(){list($a,$d,$settings)=fixture('<!--x-->');$a=new TemplateStateProbe($settings);$a->assign('x','A');$a->setLabel('stored');$b=unserialize(serialize($a));$b->assign('x','B');same('stored',$b->label());same('A',$a->render('',false));same('B',$b->render('',false));});
    test('legacy default case label retains literal matching semantics',function(){list($t)=fixture('<!--switch:start key="s"--><!--(default)-->LITERAL<!--switch:end-->');same('LITERAL',$t->setSwitch('s','default')->render('',false));same('',$t->setSwitch('s','unmatched')->render('',false));});
    test('explicit fallback coexists with literal default label',function(){list($t)=fixture('<!--switch:start key="s"--><!--(default)-->LITERAL<!--switch:default-->FALLBACK<!--switch:end-->');same('LITERAL',$t->setSwitch('s','default')->render('',false));same('FALLBACK',$t->setSwitch('s','other')->render('',false));});
    test('getBlock distinguishes default case from fallback body',function(){list($t)=fixture('A');list($b,$cases,$fallback)=$t->getBlock('<!--switch:start--><!--(default)-->LITERAL<!--(1)-->ONE<!--switch:default-->FALLBACK<!--switch:end-->','switch');same(['default'=>'LITERAL',1=>'ONE'],$cases);same('FALLBACK',$fallback);});
    test('legacy numeric switch template renders all existing branches',function(){
        $source=file_get_contents(ROOT.'app/Document/data/template/test.tpl');
        preg_match('/<!--switch:start\b.*?<!--switch:end-->/s',$source,$match);
        list($t)=fixture($match[0]);
        foreach([1=>'11111',2=>'222222',3=>'333333','missing'=>''] as $value=>$expected) same($expected,trim(strip_tags($t->setSwitch('sw_show',$value)->render('',false))));
    });
    test('existing extracted string switch template preserves all case names',function(){
        list($t)=fixture('A');$source=file_get_contents(ROOT.'app/Document/data/template/block_switch.tpl');list($block,$cases,$fallback)=$t->getBlock($source,'switch');
        same(['black','white','yellow'],array_keys($cases));same('',$fallback);
        foreach($cases as $color=>$body)same(ucfirst($color),trim(strip_tags($body)));
    });
    test('Document template demo executes its actual custom switch callback',function()use($scratch){
        $seeds=[];
        for($seed=0;$seed<100&&count($seeds)<3;$seed++){mt_srand($seed);rand();rand(0,9);$color=['white','yellow','black'][array_rand(['white','yellow','black'])];$seeds[$color]=$seed;}
        same(3,count($seeds));
        foreach($seeds as $expected=>$seed){
            $worker=$scratch.'/document-'.$expected.'.php';
            $code=<<<'CODE'
<?php
$root=SOURCE_ROOT;define('ROOT',$root);define('LIB',ROOT.'lib/');define('VENDOR',ROOT.'vendor/');define('PATH',ROOT.'app/Document/');define('CACHE',SCRATCH_CACHE);
$_SERVER['DOCUMENT_ROOT']='/';$_SERVER['REQUEST_TIME']=time();$_GET['c']='a=b';
spl_autoload_register(function($class){$file=LIB.$class.'.class.php';if(is_file($file))require_once$file;});
require_once LIB.'function.php';
set_error_handler(function($n,$s,$f,$l){if(error_reporting()&$n)throw new ErrorException($s,0,$n,$f,$l);});
$info_app=['app'=>'Document'];mt_srand(SEED);ob_start();require PATH.'module/myTemplate.php';$html=ob_get_clean();
echo json_encode(['color'=>$test_switch,'ok'=>strpos($html,'<b style="color:'.$test_switch.'">'.ucfirst($test_switch).'</b>')!==false]);
CODE;
            $code=str_replace(['SOURCE_ROOT','SCRATCH_CACHE','SEED'],[var_export(ROOT,true),var_export($scratch.'/document-'.$expected.'/',true),(string)$seed],$code);
            file_put_contents($worker,$code);$pipes=[];$p=proc_open([PHP_BINARY,$worker],[1=>['pipe','w'],2=>['pipe','w']],$pipes);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);same(0,proc_close($p));same('',$err);same(['color'=>$expected,'ok'=>true],json_decode($out,true));
        }
    });
    test('explicit fallback can appear before ordinary branches',function(){list($t)=fixture('<!--switch:start key="s"--><!--switch:default-->F<!--(1)-->A<!--switch:end-->');same('A',$t->setSwitch('s',1)->render('',false));same('F',$t->setSwitch('s',2)->render('',false));});
    test('duplicate switch fallback is rejected during compilation',function(){list($t)=fixture('<!--switch:start key="s"--><!--switch:default-->A<!--switch:default-->B<!--switch:end-->');raises(UnexpectedValueException::class,function()use($t){$t->render('',false);});});
    test('nested switches preserve separate literal and fallback branches',function(){list($t)=fixture('<!--switch:start key="a"--><!--(default)-->L<!--switch:start key="b"--><!--(x)-->X<!--switch:default-->Y<!--switch:end--><!--switch:default-->Z<!--switch:end-->');same('LY',$t->setSwitch('a','default')->setSwitch('b','other')->render('',false));same('Z',$t->setSwitch('a','other')->render('',false));});

} finally { cleanTree($scratch); $report=ob_get_clean(); }
echo $report; echo 'Passed: '.$passed.'; Failed: '.$failed.PHP_EOL; exit($failed ? 1 : 0);
