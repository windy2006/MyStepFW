<?php
/** PHP 7.4 -n tests/plugins/plugin_memcached.php：协议替身，不连接或清空真实Memcached。 */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
if(extension_loaded('memcached')){echo "Run with php -n to isolate the protocol double\n";exit(1);}
class Memcached {
    const OPT_CONNECT_TIMEOUT=1,OPT_SERVER_FAILURE_LIMIT=2,OPT_RETRY_TIMEOUT=3,OPT_REMOVE_FAILED_SERVERS=4,OPT_DISTRIBUTION=5,DISTRIBUTION_CONSISTENT=6,OPT_LIBKETAMA_COMPATIBLE=7;
    public static $values=[],$flushes=0,$pools=[],$failOptions=false,$failServer=false;public $id,$options=[],$servers=[],$code=0,$ttl=0;
    public function __construct($id=null){$this->id=$id;if($id!==null && isset(self::$pools[$id]))$this->servers=self::$pools[$id];}
    public function setOption($key,$value){if(self::$failOptions)return false;$this->options[$key]=$value;return true;}
    public function getServerList(){return $this->servers;}
    public function addServer($host,$port,$weight){if(self::$failServer)return false;$this->servers[]=[$host,$port,$weight];if($this->id!==null)self::$pools[$this->id]=$this->servers;return true;}
    public function set($key,$value,$ttl){self::$values[$key]=$value;$this->ttl=$ttl;$this->code=0;return true;}
    public function get($key){$hit=array_key_exists($key,self::$values);$this->code=$hit?0:16;return $hit?self::$values[$key]:false;}
    public function delete($key){$hit=array_key_exists($key,self::$values);unset(self::$values[$key]);$this->code=$hit?0:16;return $hit;}
    public function getResultCode(){return $this->code;}
    public function flush(){self::$flushes++;self::$values=[];return true;}
    public function quit(){return true;}
}
require_once dirname(__DIR__, 2).'/lib/cache/interface_cache.class.php';
require_once dirname(__DIR__, 2).'/plugin/memcached/memoryCache.class.php';
set_error_handler(function($n,$s,$f,$l){if(error_reporting()&$n)throw new ErrorException($s,0,$n,$f,$l);});
$passed=0;
function check($value){if(!$value)throw new RuntimeException('Assertion failed');}
function same($a,$b){check($a===$b);}
function test($name,$run){$run();$GLOBALS['passed']++;echo 'PASS '.$name.PHP_EOL;}
$cache=new memoryCache(['server'=>['port'=>11212,'weight'=>3,'server'=>'cache.test'],'timeout'=>1]);
test('associative server field order and timeout units',function()use($cache){same([['cache.test',11212,3]],$cache->obj()->servers);same(1000,$cache->obj()->options[Memcached::OPT_CONNECT_TIMEOUT]);same(0,Memcached::$flushes);});
foreach([0,false,null,'',[],['x'=>1]] as $n=>$value)test('preserves empty and structured values '.$n,function()use($cache,$value,$n){check($cache->set('key'.$n,$value,60));$hit=false;same($value,$cache->fetch('key'.$n,$hit));check($hit);});
test('missing and valid false have distinct hit flags',function()use($cache){same(false,$cache->fetch('missing',$hit));same(false,$hit);check($cache->check());check($cache->set('false',false));same(false,$cache->fetch('false',$hit));same(true,$hit);});
test('omitting value deletes and missing deletion is idempotent',function()use($cache){check($cache->set('delete','value'));check($cache->set('delete'));same(false,$cache->fetch('delete',$hit));same(false,$hit);check($cache->remove('delete'));});
test('TTL above 30 days is an absolute expiration',function()use($cache){$before=time();$cache->set('long','v',2592001);check($cache->obj()->ttl>=$before+2592001);$cache->set('forever','v',-1);same(0,$cache->obj()->ttl);});
test('long keys are accepted through bounded protocol keys',function()use($cache){$key=str_repeat('x y',1000);$cache->set($key,'value');same('value',$cache->get($key));foreach(array_keys(Memcached::$values) as $stored)check(strlen($stored)<250 && strpos($stored,' ')===false);});
test('persistent pools are stable and isolated by configuration',function(){$a=new memoryCache(['server'=>'a:11211','persistent'=>true]);$b=new memoryCache(['server'=>'a:11211','persistant'=>true]);$c=new memoryCache(['server'=>'b:11211','persistent'=>true]);same($a->obj()->id,$b->obj()->id);check($a->obj()->id!==$c->obj()->id);same(1,count($b->obj()->servers));});
test('namespace isolates identical business keys',function(){$a=new memoryCache(['namespace'=>'a']);$b=new memoryCache(['namespace'=>'b']);$a->set('key','a');$b->set('key','b');same('a',$a->get('key'));same('b',$b->get('key'));});
test('JSON lists and IPv6 endpoints parse correctly',function(){same('::1',memoryCache::servers('[::1]:11211')[0]['server']);same(11212,memoryCache::servers('[{"server":"cache.test","port":11212}]')[0]['port']);});
test('invalid servers and unbounded TTL fail explicitly',function()use($cache){foreach(['',[],['server'=>'host','port'=>0],['server'=>'host','weight'=>-1]] as $bad){try{new memoryCache(['server'=>$bad]);throw new RuntimeException('Expected rejection');}catch(InvalidArgumentException $e){}}try{$cache->set('x','x',INF);throw new RuntimeException('Expected TTL rejection');}catch(InvalidArgumentException $e){}});
test('binary namespace delimiters cannot collide with business keys',function(){
    $a=new memoryCache(['namespace'=>"a"]);$b=new memoryCache(['namespace'=>"a\0b"]);$a->set("b\0c",'first');$b->set('c','second');same('first',$a->get("b\0c"));same('second',$b->get('c'));
});
test('duration bounds reject native integer and protocol expiration overflows',function()use($cache){
    foreach([['timeout'=>2147484],['retry_interval'=>2147483648],['expire'=>4294967295]] as $options){try{new memoryCache($options);throw new RuntimeException('Expected duration rejection');}catch(InvalidArgumentException $e){}}
    try{$cache->set('overflow','value',4294967295);throw new RuntimeException('Expected protocol TTL rejection');}catch(InvalidArgumentException $e){}
});
test('client configuration failures cannot produce a usable-looking adapter',function(){
    foreach(['failOptions','failServer'] as $flag){Memcached::${$flag}=true;try{new memoryCache([]);throw new LogicException('Expected client initialization failure');}catch(RuntimeException $e){}finally{Memcached::${$flag}=false;}}
});
test('only explicit clearing flushes service and close uses native quit',function()use($cache){same(0,Memcached::$flushes);check($cache->clear());same(1,Memcached::$flushes);check($cache->close());});
echo 'Passed: '.$passed.'; Failed: 0'.PHP_EOL;
