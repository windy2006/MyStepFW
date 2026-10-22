"""临时站点通过真实 init/go/start/路由/模板/API/文件出口验证；不访问现有业务。"""
from pathlib import Path
import tempfile,subprocess,socket,time,urllib.request,urllib.error,http.cookiejar,json,hashlib,os,gzip,urllib.parse
source=Path(__file__).resolve().parents[2]
# 正式测试位于 tests/core/；允许在私有工作目录编写时明确源码根目录。
source=Path(os.environ.get('MYSTEP_EXECUTION_SOURCE',str(source)))
php=os.environ.get('MYSTEP_PHP','/Applications/ServBay/package/php/7.4/current/bin/php')
rows=[]
with tempfile.TemporaryDirectory(prefix='mystep-execution-http-') as scratch:
 outer=Path(scratch);root=outer/'sub';root.mkdir();(root/'lib').symlink_to(source/'lib',target_is_directory=True)
 for name in ['app/myStep','app/Fixture/template','static/js','static/images','config/priority','cache','plugin']:(root/name).mkdir(parents=True,exist_ok=True)
 def literal(s):return "'"+str(s).replace('\\','\\\\').replace("'","\\'")+"'"
 (root/'config/config.php').write_text('<?php include '+literal(source/'config/config_default.php')+''';
$setting['gen']['memory']='16M';$setting['gen']['s_usr']='fixture';$setting['gen']['s_pwd']='disabled-fixture-password';$setting['gen']['cache_page']=false;
$setting['web']['gzip_level']=-1;$setting['web']['css']='';$setting['web']['js']='';$setting['db']['auto']=false;
$setting['template']['path']='app/Fixture/template';$setting['router']['mode']='rewrite';$setting['router']['default_app']='Fixture';$setting['cookie']['domain']='';return $setting;
''')
 (root/'config/class.php').write_text('<?php return [["path"=>"lib/","ext"=>".class.php,.php","idx"=>["interface_plugin"=>"../plugin/interface_plugin.class.php"]],["path"=>"lib/database/","ext"=>".class.php,.php"]];')
 (root/'config/class_alias.php').write_bytes((source/'config/class_alias.php').read_bytes())
 (root/'config/domain.php').write_text('<?php return ["bound.test"=>"/console/(.*)"];')
 (root/'static/js/security.js').write_bytes((source/'static/js/security.js').read_bytes())
 (root/'plugin/interface_plugin.class.php').write_bytes((source/'plugin/interface_plugin.class.php').read_bytes())
 (root/'plugin/probe').mkdir()
 (root/'plugin/probe/info.php').write_text('<?php return ["idx"=>"probe"];')
 (root/'plugin/probe/index.php').write_text('<?php file_put_contents(ROOT."unexpected-plugin-init", "initialized");')
 (root/'plugin/probe/class.php').write_text('''<?php
file_put_contents(ROOT.'plugin-api-class-loaded','loaded');
class plugin_probe implements interface_plugin {
 public static function check(&$result){return true;}
 public static function install(){}
 public static function uninstall(){}
 public static function echoValue($value=''){return ['value'=>$value,'host'=>$GLOBALS['info_app']['app']];}
}
''')
 (root/'plugin/probe/route.php').write_text('<?php $api=["echo"=>"plugin_probe::echoValue"];')
 registry_setup='''<?php
define('LIB', SOURCE.'/lib/');require LIB.'function.php';
spl_autoload_register(function($name){foreach([LIB.$name.'.class.php',LIB.'database/'.$name.'.class.php'] as $file)if(is_file($file)){require_once $file;return;}});
$db=new myDb('simpleDB','plugin',__DIR__.'/plugin/');$db->create([['order',2],['active',1],['idx',40]]);$db->insert(['order'=>1,'active'=>1,'idx'=>'probe'],true);$db->close();
'''
 (root/'prepare-registry.php').write_text(registry_setup.replace('SOURCE',literal(source)))
 subprocess.run([php,str(root/'prepare-registry.php')],check=True,capture_output=True)
 (root/'prepare-registry.php').unlink()
 for app in ['Fixture','myStep']:(root/f'app/{app}/info.php').write_text('<?php return ["app"=>"'+app+'","name"=>"Fixture"];')
 (root/'app/Fixture/Fixture.class.php').write_text('''<?php
class Fixture extends myStep {
 public function preload(){
  $this->setFunction('start',function(){file_put_contents(ROOT.'trace', 'start:'.self::$url_prefix."\\n",FILE_APPEND);});
  $this->setFunction('end',function(){file_put_contents(ROOT.'trace', "end\\n",FILE_APPEND);});
 }
 public static function matched(){global $mystep,$tpl_setting;$tpl=new myTemplate($tpl_setting);$tpl->assign('main','matched-page');$mystep->show($tpl);}
 public static function apiEcho($value=''){return ['value'=>$value];}
 public static function memoryPolicy(){header('Content-Type: application/json');echo json_encode(['memory'=>ini_get('memory_limit')]);}
}
''')
 (root/'app/Fixture/lib.php').write_text('<?php myController::regModule("Fixture_widget",function(){echo "callback-module";});')
 (root/'app/myStep/lib.php').write_text('<?php require_once APP."Fixture/Fixture.class.php";')
 (root/'app/Fixture/route.php').write_text('<?php $rule=[["/matched", "Fixture::matched"],["/memory-policy", "Fixture::memoryPolicy"]];')
 (root/'app/myStep/route.php').write_text('<?php $rule=[["/ms_csrf","mySecurity::respond"],["/api/[str]/[any]","myStep::api"],["/module/[str]/[any]","myStep::module"]];$api=["echo"=>"Fixture::apiEcho"];')
 (root/'app/Fixture/index.php').write_text('<?php echo "fallback-page";')
 (root/'app/Fixture/template/main.tpl').write_text('<html><head><title>Fixture</title></head><body><!--page_start--><!--main--><!--page_end--></body></html>')
 small=b'\xef\xbb\xbfbody\x00end';(root/'static/images/small.txt').write_bytes(small)
 block=(b'\xef\xbb\xbfABC\x00DEF'+bytes(range(256)))*2048
 large=root/'static/images/large.txt';large_hash=hashlib.sha256();large_size=0
 with large.open('wb') as file:
  for _ in range(48):file.write(block);large_hash.update(block);large_size+=len(block)
 script='''<?php
$_SERVER['DOCUMENT_ROOT']=dirname(__DIR__);$_SERVER['SCRIPT_NAME']='/sub/index.php';$_SERVER['SCRIPT_FILENAME']=__FILE__;
define('ROOT',__DIR__.'/');define('LIB',ROOT.'lib/');define('VENDOR',SOURCE.'/vendor/');
foreach(['APP'=>'app/','CONFIG'=>'config/','PLUGIN'=>'plugin/','CACHE'=>'cache/','STATICS'=>'static/','FILE'=>'files/'] as $key=>$value)define($key,ROOT.$value);
spl_autoload_register(function($name){foreach([LIB.$name.'.class.php',LIB.'database/'.$name.'.class.php'] as $file)if(is_file($file)){require_once $file;return;}});
require_once LIB.'myStep.class.php';
if(getenv('FIXTURE_BODY_BYTES'))define('MYSTEP_REQUEST_LIMITS',['body_bytes'=>(int)getenv('FIXTURE_BODY_BYTES')]);
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if($path==='/sub/etag'){myController::etag('fixture');echo 'fresh';exit;}
if($path==='/sub/probe-file'){
 ob_start();echo 'discard-me';
 if(isset($_GET['gzip']))ob_start('ob_gzhandler');
 myController::file(ROOT.'static/images/'.(isset($_GET['missing'])?'missing.txt':(isset($_GET['large'])?'large.txt':'small.txt')),"report\\\"中文\\r\\nX-Probe: injected.txt");echo 'trailing-execution';exit;
}
myStep::init();
echo 'unexpected-return';
'''
 (root/'index.php').write_text(script.replace('SOURCE',literal(source)))
 sock=socket.socket();sock.bind(('127.0.0.1',0));port=sock.getsockname()[1];sock.close()
 with tempfile.TemporaryFile(mode='w+') as log:
  process=subprocess.Popen([php,'-d','memory_limit=16M','-S',f'127.0.0.1:{port}','-t',str(outer),str(root/'index.php')],stdout=log,stderr=log)
  jar=http.cookiejar.CookieJar();opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
  def request(path,status=200,method='GET',headers=None,data=None,stream=False):
   req=urllib.request.Request(f'http://127.0.0.1:{port}/sub/'+path,headers=headers or {},data=data,method=method)
   try:response=opener.open(req,timeout=15)
   except urllib.error.HTTPError as err:response=err
   with response:
    if stream:
     digest=hashlib.sha256();size=0
     body_stream=gzip.GzipFile(fileobj=response) if stream=='gzip' else response
     while True:
      part=body_stream.read(262144)
      if not part:break
      digest.update(part);size+=len(part)
     body=(size,digest.hexdigest())
    else:body=response.read()
    row={'path':path,'method':method,'status':response.status};rows.append(row)
    assert response.status==status,row
    return body,response.headers
  try:
   for _ in range(80):
    try:
     with socket.create_connection(('127.0.0.1',port),timeout=.1):break
    except OSError:time.sleep(.05)
   # 有界小请求验证框架在应用、会话和优先脚本前拒绝深层输入；不制造OOM或崩溃。
   deep='x'+('[n]'*33)
   for name in ['GET','POST']:
    query=urllib.parse.urlencode({deep:'test'})
    body,h=request('matched'+('?' + query if name=='GET' else ''),status=413,method=name,headers={'Content-Type':'application/x-www-form-urlencoded'} if name=='POST' else None,data=query.encode() if name=='POST' else None)
    assert json.loads(body)['error']=='Request input exceeds configured limits' and not h.get('Set-Cookie')
    assert not (root/'trace').exists()
   body,h=request('matched?'+urllib.parse.urlencode({deep:'test'}),status=413,method='HEAD');assert body==b'' and not h.get('Set-Cookie') and not (root/'trace').exists()
   body,h=request('matched');assert b'matched-page' in body and b'unexpected-return' not in body;assert 'no-store' in h['Cache-Control'];assert (root/'trace').read_text()=='start:/sub/\nend\n'
   body,h=request('fallback');assert body==b'fallback-page';assert (root/'trace').read_text().endswith('start:/sub/\nend\n')
   body,h=request('index.php?matched&x=1');assert b'matched-page' in body
   body,h=request('index.php/matched');assert b'matched-page' in body
   body,h=request('module/Fixture/widget');assert body==b'callback-module'
   body,h=request('module/Fixture/widget',headers={'Host':'bound.test'});assert body==b'callback-module'
   body,h=request('api/myStep/echo/json?v=query');assert json.loads(body)=={'value':'query'}
   body,h=request('api/plugin_probe/echo/json?value=standalone');assert json.loads(body)=={'value':'standalone','host':'myStep'}
   body,h=request('api/plugin_probe/echo?value=implicit');assert json.loads(body)=={'value':'implicit','host':'myStep'}
   body,h=request('api/myStep/echo?v=implicit');assert json.loads(body)=={'value':'implicit'}
   body,h=request('api/plugin_probe/echo/json?core=Fixture&value=context');assert json.loads(body)=={'value':'context','host':'Fixture'}
   assert not (root/'unexpected-plugin-init').exists(), 'An independent API must not run host initialization'
   (root/'plugin-api-class-loaded').unlink()
   disable_registry=registry_setup[:registry_setup.index('$db=')]+"$db=new myDb('simpleDB','plugin',__DIR__.'/plugin/');$db->update(['idx'=>'probe'],['active'=>0]);$db->close();"
   (root/'prepare-registry.php').write_text(disable_registry.replace('SOURCE',literal(source)))
   subprocess.run([php,str(root/'prepare-registry.php')],check=True,capture_output=True)
   (root/'prepare-registry.php').unlink()
   body,h=request('api/plugin_probe/echo/json?core=Fixture');assert 'error' in json.loads(body)
   assert not (root/'plugin-api-class-loaded').exists(), 'Disabled API must not load its class'
   body,h=request('ms_csrf');token=json.loads(body)['token'];assert len(token)>=32
   body,h=request('api/myStep/echo/json?core=Fixture',method='POST',data=b'value=posted',headers={'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':token});assert json.loads(body)=={'value':'posted'}
   body,h=request('api/myStep/echo/json',status=403,method='POST',data=b'value=posted');assert b'CSRF' in body
   body,h=request('api/myStep/echo/json',status=204,method='OPTIONS');assert body==b''
   before=(root/'trace').read_text();body,h=request('static/images/small.txt');assert body==small;etag=h['ETag'];assert etag.startswith('"');assert int(h['Content-Length'])==len(small);assert (root/'trace').read_text()==before
   body,h=request('static/images/small.txt',method='HEAD');assert body==b'' and int(h['Content-Length'])==len(small)
   body,h=request('static/images/small.txt',status=304,headers={'If-None-Match':'"different", W/'+etag});assert body==b''
   stat=(root/'static/images/small.txt').stat();updated=small.replace(b'body',b'edit');(root/'static/images/small.txt').write_bytes(updated);os.utime(root/'static/images/small.txt',ns=(stat.st_atime_ns,stat.st_mtime_ns));body,h=request('static/images/small.txt',headers={'If-None-Match':etag});assert body==updated and h['ETag']!=etag
   body,h=request('static/images/large.txt',stream=True);assert body==(large_size,large_hash.hexdigest());assert int(h['Content-Length'])==large_size;assert (root/'trace').read_text()==before
   body,h=request('probe-file');assert body==updated;assert 'filename*=UTF-8' in h['Content-Disposition'];assert h['X-Probe'] is None
   body,h=request('probe-file?gzip=1',headers={'Accept-Encoding':'gzip'});assert gzip.decompress(body)==updated;assert h.get('Content-Length') is None
   body,h=request('probe-file?large=1&gzip=1',headers={'Accept-Encoding':'gzip'},stream='gzip');assert body==(large_size,large_hash.hexdigest());assert h.get('Content-Length') is None
   body,h=request('probe-file?missing=1',status=404);assert body==b''
   body,h=request('etag',status=304,headers={'If-None-Match':'W/"fixture"'});assert body==b''
   body,h=request('etag',status=304,headers={'If-None-Match':'*'});assert body==b''
   body,h=request('etag',headers={'If-None-Match':'*bad*'});assert body==b'fresh'
   body,h=request('etag',method='POST',headers={'If-None-Match':'"fixture"'},data=b'');assert body==b'fresh'
   # 类索引损坏以及迁移后旧目录仍存在，均应自动修复并继续请求。
   valid_index=(root/'config/class.php').read_text()
   for broken in ['<?php return [false];','<?php return [;', '<?php return [["path"=>'+literal(source/'lib/database')+'."/","ext"=>".class.php"]];']:
    (root/'config/class.php').write_text(broken)
    body,h=request('fallback');assert body==b'fallback-page'
    rebuilt=(root/'config/class.php').read_text();assert '"path"' not in rebuilt and "'path' => 'lib/'" in rebuilt
    (root/'config/class.php').write_text(valid_index)
   process.terminate();process.wait(timeout=10);log.seek(0);messages=log.read();assert 'PHP Warning' not in messages and 'PHP Fatal' not in messages,messages
   assert not (root/'error.log').exists() or (root/'error.log').stat().st_size==0,(root/'error.log').read_text()
   # 两个独立进程验证：缺省沿用应用16M，显式开关保留服务器32M；不取消内存上限。
   for preserve,expected in [('0','16M'),('1','32M')]:
    sock=socket.socket();sock.bind(('127.0.0.1',0));policy_port=sock.getsockname()[1];sock.close()
    env=os.environ.copy();env['MYSTEP_PRESERVE_MEMORY_LIMIT']=preserve;env['FIXTURE_BODY_BYTES']='128'
    with tempfile.TemporaryFile(mode='w+') as policy_log:
     worker=subprocess.Popen([php,'-d','memory_limit=32M','-S',f'127.0.0.1:{policy_port}','-t',str(outer),str(root/'index.php')],stdout=policy_log,stderr=policy_log,env=env)
     try:
      for _ in range(80):
       try:
        with socket.create_connection(('127.0.0.1',policy_port),timeout=.1):break
       except OSError:time.sleep(.05)
      base=f'http://127.0.0.1:{policy_port}/sub/'
      with urllib.request.urlopen(base+'memory-policy',timeout=15) as response:assert json.loads(response.read())=={'memory':expected}
      rows.append({'path':'memory-policy','status':200,'preserve':preserve,'memory':expected})
      trace=(root/'trace').read_bytes()
      req=urllib.request.Request(base+'matched',data=b' '*129,headers={'Content-Type':'application/json'})
      try:response=urllib.request.urlopen(req,timeout=15)
      except urllib.error.HTTPError as err:response=err
      with response:
       assert response.status==413 and json.loads(response.read())['error']=='Request body exceeds configured limit' and not response.headers.get('Set-Cookie')
      assert (root/'trace').read_bytes()==trace
      rows.append({'path':'matched','status':413,'method':'POST','body_bytes':129})
     finally:
      worker.terminate();worker.wait(timeout=10);policy_log.seek(0);messages=policy_log.read();assert 'PHP Warning' not in messages and 'PHP Fatal' not in messages,messages
   print(f'Execution HTTP fixture: {len(rows)} scenarios passed; large file {large_size} bytes under PHP 16 MiB; business requests: 0')
   if os.environ.get('MYSTEP_EXECUTION_HTTP_RESULTS'):Path(os.environ['MYSTEP_EXECUTION_HTTP_RESULTS']).write_text(json.dumps(rows,ensure_ascii=False,indent=2))
  except Exception:
   log.seek(0);print(log.read());raise
  finally:
   if process.poll() is None:process.terminate();process.wait(timeout=10)
