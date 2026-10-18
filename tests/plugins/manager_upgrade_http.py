"""真实 PHP 7.4 + 本地受信 HTTPS 主服务器；所有升级均在临时目录。"""
from pathlib import Path
import os,tempfile,subprocess,socket,time,urllib.request,urllib.error,ssl,http.server,threading,json,zipfile,io,hashlib,shutil
source=Path(os.environ.get('MYSTEP_UPDATE_SOURCE',str(Path(__file__).resolve().parents[2])))
php=os.environ.get('MYSTEP_PHP','/Applications/ServBay/package/php/7.4/current/bin/php')
rows=[];requests=[]
def lit(s):return "'"+str(s).replace('\\','\\\\').replace("'","\\'")+"'"
def port():
 with socket.socket() as s:s.bind(('127.0.0.1',0));return s.getsockname()[1]
with tempfile.TemporaryDirectory(prefix='mystep-manager-upgrade-') as tmp:
 outer=Path(tmp);httpport=port();masterport=port();tlsport=port();base=f'http://127.0.0.1:{httpport}';remote=f'https://127.0.0.1:{tlsport}'
 cert=outer/'cert.pem';key=outer/'key.pem'
 subprocess.run(['openssl','req','-x509','-newkey','rsa:2048','-nodes','-keyout',str(key),'-out',str(cert),'-days','1','-subj','/CN=127.0.0.1','-addext','subjectAltName=IP:127.0.0.1','-addext','basicConstraints=critical,CA:TRUE'],check=True,capture_output=True)
 def write(root,path,text):
  p=root/path;p.parent.mkdir(parents=True,exist_ok=True);p.write_text(text);return p
 def fixture(name):
  root=outer/name
  for path in ['plugin/manager','config','cache','app','lib']:(root/path).mkdir(parents=True,exist_ok=True)
  shutil.copy2(source/'plugin/manager/class.php',root/'plugin/manager/class.php')
  write(root,'plugin/manager/config.php',"<?php return ['update'=>true,'pack'=>true];")
  write(root,'config/version.php',"<?php return '1.0';")
  write(root,'config/config.php',"<?php return ['web'=>['update'=>"+lit(remote)+",'etag'=>'before'],'gen'=>['charset'=>'UTF-8','debug'=>false],'keep'=>'original'];")
  write(root,'lib/old.txt','old');write(root,'lib/remove.txt','remove');write(root,'cache/script/stale','stale');return root
 master=fixture('master');write(master,'config/version.php',"<?php return '2.0';")
 write(master,'lib/old.txt','new');write(master,'lib/nested/zero.txt','0');write(master,'lib/empty.txt','');write(master,'lib/dot.txt','.');write(master,'lib/binary.dat','binary\0payload');(master/'lib/emptydir').mkdir();(master/'lib/remove.txt').unlink()
 releases={ '2.0':{'info':'second','file':['/lib/old.txt','/lib/nested/zero.txt','/lib/remove.txt','/lib/emptydir','/config/version.php','/lib/empty.txt','/lib/dot.txt','/lib/binary.dat'],'setting':{'web':{'title':'updated'}},'code':'myFile::saveFile(ROOT."migration.txt", "ran");'},'1.5':{'info':'first','file':[]}}
 def phpdata(data):
  text=json.dumps(data,ensure_ascii=False);return 'json_decode('+lit(text)+',true)'
 write(master,'plugin/manager/version.php','<?php return '+phpdata(releases)+';')
 router='''<?php
namespace app\\myStep {function logCheck($show=true){if(($_SERVER['HTTP_X_FIXTURE_AUTH'] ?? '')==='yes')return true;http_response_code(401);exit;}}
namespace {
$parts=explode('/',trim(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH),'/'));$site=$parts[0]==='api'?'master':array_shift($parts);
if(!preg_match('/^[a-zA-Z0-9_]+$/',$site) || !is_dir(__DIR__.'/'.$site)){http_response_code(404);exit;}
define('ROOT',__DIR__.'/'.$site.'/');foreach(['APP'=>'app/','CONFIG'=>'config/','PLUGIN'=>'plugin/','CACHE'=>'cache/','LIB'=>SOURCE.'/lib/'] as $key=>$path)define($key,$key==='LIB'?$path:ROOT.$path);define('ROOT_WEB','/'.$site.'/');
spl_autoload_register(function($name){foreach([LIB.$name.'.class.php',LIB.'database/'.$name.'.class.php'] as $f)if(is_file($f)){require_once $f;return;}});
require LIB.'function.php';require SOURCE.'/plugin/interface_plugin.class.php';require PLUGIN.'manager/class.php';
class myStep {
 public static function info($message,$url=''){echo json_encode(['message'=>$message]);exit;}
 public static function header($status){http_response_code((int)$status);exit;}
 public static function file($file){header('Content-Type: application/octet-stream');readfile($file);exit;}
 public static function setURL($path=''){return ROOT_WEB.ltrim($path,'/');}
 public static function clearDataCache(){file_put_contents(ROOT.'cache-cleared','yes');return !is_file(ROOT.'fail-cache');}
 public function end(){exit;}
 public function getLanguage($key){$p=include SOURCE.'/plugin/manager/language/chs.php';return $p[$key]??$key;}
 public function getInstance($class,...$args){return new $class(...$args);}
}
class myController {public static function file($file){myStep::file($file);}public static function header($status){myStep::header($status);}}
$ms_setting=$S=new myConfig(CONFIG.'config.php');$mystep=new myStep();myFile::setTLSOptions(['cafile'=>CERT]);$info_app=['path'=>$parts,'para'=>$_GET,'app'=>'myStep'];
set_error_handler(function($n,$s,$f,$l){if(error_reporting()&$n)throw new \\ErrorException($s,0,$n,$f,$l);});
ob_start();try {if($site==='master')plugin_manager::remote();else plugin_manager::main();}catch(\\Throwable $e){http_response_code(500);ob_clean();echo json_encode(['exception'=>get_class($e),'message'=>$e->getMessage()]);}
}
'''
 (outer/'router.php').write_text(router.replace('SOURCE',lit(source)).replace('CERT',lit(cert)))
 class Proxy(http.server.BaseHTTPRequestHandler):
  def do_GET(self):
   if (outer/'response-override').exists():
    body=(outer/'response-override').read_bytes();status=200
   else:
    try:
     with urllib.request.urlopen(f'http://127.0.0.1:{masterport}'+self.path,timeout=15) as response:body=response.read();status=response.status
    except urllib.error.HTTPError as e:body=e.read();status=e.code
   self.send_response(status);self.send_header('Content-Length',str(len(body)));self.end_headers();self.wfile.write(body);self.wfile.flush()
   try:self.connection.unwrap()
   except OSError:pass
  def log_message(self,*args):pass
 proxy=http.server.ThreadingHTTPServer(('127.0.0.1',tlsport),Proxy);context=ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER);context.load_cert_chain(cert,key);proxy.socket=context.wrap_socket(proxy.socket,server_side=True)
 threading.Thread(target=proxy.serve_forever,daemon=True).start()
 # Separate PHP services avoid nested requests blocking the single-threaded development server.
 log=open(outer/'php.log','w+');env=dict(os.environ)
 proc=subprocess.Popen([php,'-d','display_errors=0','-d','opcache.enable_cli=0','-S',f'127.0.0.1:{httpport}','-t',str(outer),str(outer/'router.php')],stdout=log,stderr=log,env=env,start_new_session=True)
 masterproc=subprocess.Popen([php,'-d','display_errors=0','-d','opcache.enable_cli=0','-S',f'127.0.0.1:{masterport}','-t',str(outer),str(outer/'router.php')],stdout=log,stderr=log,env=env,start_new_session=True)
 def request(path,data=None,auth=True,json_result=True):
  headers={'X-Fixture-Auth':'yes'} if auth else {}
  if isinstance(data,tuple):headers['Content-Type']=data[0];data=data[1]
  req=urllib.request.Request(base+path,headers=headers,data=data)
  try:response=urllib.request.urlopen(req,timeout=30)
  except urllib.error.HTTPError as e:response=e
  with response:body=response.read();status=response.status
  requests.append({"path":path,"status":status,"method":req.get_method()})
  return status,json.loads(body) if json_result else body
 def upload(name,blob):
  boundary='ManagerUpgradeFixture';body=(f'--{boundary}\r\nContent-Disposition: form-data; name="file"; filename="update.zip"\r\nContent-Type: application/zip\r\n\r\n').encode()+blob+f'\r\n--{boundary}--\r\n'.encode()
  return request('/'+name+'/manager/upload',('multipart/form-data; boundary='+boundary,body))
 def case(name,run):
  try:run();rows.append({'case':name,'ok':True});print('PASS '+name,flush=True)
  except Exception as e:rows.append({'case':name,'ok':False,'error':str(e)});print('FAIL '+name+' '+str(e),flush=True)
 def ensure(value,message='unexpected result'):
  if not value:raise AssertionError(message)
 def new(name):return fixture(name)
 try:
  for _ in range(80):
   try:
    with socket.create_connection(('127.0.0.1',httpport),timeout=.1):break
   except OSError:time.sleep(.05)
  def check_version():
   new('check');status,body=request('/check/manager/check/version');ensure(status==200,body);ensure(body['version']=='2.0',body);ensure(list(body['detail'])==['1.5','2.0'],body)
  case('master version chooses highest release independently of declaration order',check_version)
  def offline_package():
   root=new('offline');status,body=request('/offline/manager/download?m=0');ensure(status==200 and body.get('link'),body)
   ensure((root/'lib/old.txt').read_text()=='old');ensure(not (root/'cache-cleared').exists(),'download-only clears cache');ensure((root/'cache/script/stale').exists(),'download-only deletes script cache')
   status,blob=request(body['link'],json_result=False);ensure(status==200);status,result=upload('offline',blob);ensure(status==200 and result.get('error')==0,result)
   ensure((root/'lib/nested/zero.txt').read_text()=='0');ensure(not (root/'lib/remove.txt').exists(),'delete operation lost');ensure((root/'lib/emptydir').is_dir(),'directory operation lost');ensure((root/'migration.txt').read_text()=='ran','migration missing');ensure('updated' in (root/'config/config.php').read_text(),'settings missing');ensure((root/'cache-cleared').exists(),'offline cache not cleared');ensure((root/'lib/empty.txt').read_bytes()==b'');ensure((root/'lib/dot.txt').read_text()=='.');ensure((root/'lib/binary.dat').read_bytes()==b'binary\0payload')
  case('download-only ZIP and real multipart offline upload preserve all operations',offline_package)
  def online():
   root=new('online');status,body=request('/online/manager/download?m=1');ensure(status==200,body);ensure(not body.get('error'),body);ensure((root/'lib/nested/zero.txt').read_text()=='0');ensure(not (root/'lib/remove.txt').exists());ensure((root/'lib/emptydir').is_dir());ensure((root/'migration.txt').read_text()=='ran');ensure((root/'cache-cleared').exists());ensure('updated' in (root/'config/config.php').read_text());ensure((root/'lib/empty.txt').read_bytes()==b'');ensure((root/'lib/dot.txt').read_text()=='.')
  case('automatic online upgrade publishes files directories deletions config and code',online)
  def legacy():
   root=new('legacy');buf=io.BytesIO()
   with zipfile.ZipFile(buf,'w') as z:z.writestr('lib/nested/legacy.txt','legacy');z.writestr('_code.php','<?php myFile::saveFile(ROOT."legacy-migration", "ok");');z.writestr('_config.php',"<?php return ['web'=>['title'=>'legacy']];")
   status,body=upload('legacy',buf.getvalue());ensure(status==200 and body.get('error')==0,body);ensure((root/'lib/nested/legacy.txt').read_text()=='legacy');ensure((root/'legacy-migration').read_text()=='ok')
  case('legacy offline ZIP remains compatible',legacy)
  def invalid_mode():
   root=new('badmode');status,body=request('/badmode/manager/download?m=2');ensure(body.get('error'),body);ensure((root/'lib/old.txt').read_text()=='old');ensure(not (root/'cache-cleared').exists())
  case('invalid update mode rejected without mutation',invalid_mode)
  def equal_newer():
   for i,v in enumerate(['2.0','2.0.0','3.0']):
    root=new('none'+str(i));write(root,'config/version.php',"<?php return "+lit(v)+";")
    status,body=request('/none'+str(i)+'/manager/check/version');ensure(status==200 and body.get('version')=='' and not body.get('error'),body)
  case('equal and newer clients do not offer downgrades',equal_newer)
  def bad_response():
   root=new('badresponse');write(root,'plugin/manager/check_app.php',"<?php return ['keep'=>'1.0'];");before=(root/'plugin/manager/check_app.php').read_bytes()
   try:
    for blob in [b'not-json',b'{"version":"2.0"}',b'{"version":[],"info":[],"app":[],"plugin":[]}']:
     (outer/'response-override').write_bytes(blob);status,body=request('/badresponse/manager/check/version');ensure(status==200 and body.get('error'),body);ensure((root/'plugin/manager/check_app.php').read_bytes()==before)
   finally:(outer/'response-override').unlink(missing_ok=True)
  case('bad master responses report failure and retain cached version lists',bad_response)
  def master_closed():
   new('closed');write(master,'plugin/manager/config.php',"<?php return ['update'=>false];")
   try:status,body=request('/closed/manager/check/version');ensure(status==200 and body.get('error'),body)
   finally:write(master,'plugin/manager/config.php',"<?php return ['update'=>true];")
  case('disabled master update service is rejected',master_closed)
  def small_release():
   try:
    write(master,'plugin/manager/version.php','<?php return '+phpdata({'2.0':{'info':'configuration only','file':[],'setting':{'web':{'title':'config-only'}},'code':'myFile::saveFile(ROOT."config-only", "ok");'}})+';')
    for mode in ['0','1']:
     root=new('configonly'+mode);status,body=request('/configonly'+mode+'/manager/download?m='+mode);ensure(status==200 and not body.get('error'),body)
     if mode=='0':
      ensure(body.get('link'),body);status,blob=request(body['link'],json_result=False);status,body=upload('configonly0',blob);ensure(status==200 and body.get('error')==0,body)
     ensure((root/'config-only').read_text()=='ok');ensure('config-only' in (root/'config/config.php').read_text())
   finally:write(master,'plugin/manager/version.php','<?php return '+phpdata(releases)+';')
  case('configuration and migration only releases work online and offline',small_release)
  def cache_fingerprint():
   root=new('cachefirst');status,body=request('/cachefirst/manager/download?m=0');ensure(not body.get('error'),body)
   write(master,'lib/old.txt','revised-same-version')
   try:
    root=new('cachesecond');status,body=request('/cachesecond/manager/download?m=1');ensure(not body.get('error'),body);ensure((root/'lib/old.txt').read_text()=='revised-same-version','stale master cache')
   finally:write(master,'lib/old.txt','new')
  case('master cache invalidates when release contents change',cache_fingerprint)
  def old_log():
   # Fixed-width table: rebuild only the isolated master log with the historical schema.
   schema=[['date',10],['idx',40],['ver_remote',30],['ver_local',30],['remote_ip',50],['referer',200]]
   code='define("ROOT",'+lit(str(master)+'/')+');define("LIB",'+lit(str(source/'lib')+'/')+');spl_autoload_register(function($name){foreach([LIB.$name.".class.php",LIB."database/".$name.".class.php"] as $file)if(is_file($file)){require_once $file;return;}});$d=new simpleDB("update",'+lit(master/'plugin/manager')+');$ok=$d->create('+phpdata(schema)+');if(!$ok)exit(1);'
   (master/'plugin/manager/update.db').unlink(missing_ok=True)
   result=subprocess.run([php,'-r',code],capture_output=True);ensure(result.returncode==0,result.stderr.decode()+result.stdout.decode())
   new('oldlog');status,body=request('/oldlog/manager/download?m=0');ensure(status==200 and body.get('link'),body)
  case('historical 10-byte date log schema remains usable',old_log)
  def legacy_empty():
   root=new('legacyempty');buf=io.BytesIO()
   with zipfile.ZipFile(buf,'w') as z:z.writestr('lib/empty.txt','');z.writestr('lib/dot.txt','.')
   status,body=upload('legacyempty',buf.getvalue());ensure(status==200 and body.get('error')==0,body);ensure((root/'lib/empty.txt').read_bytes()==b'');ensure((root/'lib/dot.txt').read_text()=='.')
  case('legacy ZIP preserves empty and single-dot file contents',legacy_empty)
  def offline_without_server():
   root=new('noorigin');write(root,'config/config.php',"<?php return ['web'=>['update'=>'http://invalid','etag'=>'before'],'gen'=>['charset'=>'UTF-8']];")
   buf=io.BytesIO()
   with zipfile.ZipFile(buf,'w') as z:z.writestr('lib/local.txt','offline')
   status,body=upload('noorigin',buf.getvalue());ensure(status==200 and body.get('error')==0,body);ensure((root/'lib/local.txt').read_text()=='offline')
  case('offline upload works independently of update server configuration',offline_without_server)
  def bad_zip():
   for idx,entries in enumerate([None,{'lib/safe.txt':'safe','../outside.txt':'escape'},{'lib/safe.txt':'safe','config/config.php':'forbidden'},{'_update.json':json.dumps({'format':1,'file':['lib/missing.txt'],'kind':['file'],'setting':{},'code':[]})},{'_update.json':json.dumps({'format':1,'file':[],'kind':[],'setting':{},'code':[]}),'lib/extra.txt':'extra'},{'_update.json':'invalid json'},{'lib/conflict':'file','lib/conflict/nested.txt':'nested'}]):
    name='badzip'+str(idx);root=new(name);buf=io.BytesIO()
    if entries is None:blob=b'not a ZIP'
    else:
     with zipfile.ZipFile(buf,'w') as z:
      for member,value in entries.items():z.writestr(member,value)
     blob=buf.getvalue()
    before=(root/'config/config.php').read_bytes();status,body=upload(name,blob);ensure(status==200 and body.get('error'),[idx,body]);ensure((root/'lib/old.txt').read_text()=='old');ensure((root/'config/config.php').read_bytes()==before);ensure(not (root/'lib/safe.txt').exists());ensure(not (root/'cache-cleared').exists())
  case('seven corrupt private missing extra and conflicting ZIP cases reject before publication',bad_zip)
  def conflict():
   root=new('targetconflict');write(root,'lib/nested','parent is file');status,body=request('/targetconflict/manager/download?m=1');ensure(body.get('error'),body);ensure((root/'lib/old.txt').read_text()=='old');ensure('1.0' in (root/'config/version.php').read_text())
  case('destination conflict is detected before first write',conflict)
  def permission_failure(name,relative,mode,operation='online'):
   root=new(name);target=root/relative
   if not target.exists():
    if relative.endswith(('.php','.lock')):write(root,relative,'fixture')
    else:target.mkdir(parents=True)
   before=(root/'config/config.php').read_bytes();original=target.stat().st_mode & 0o777;target.chmod(mode)
   try:
    ensure(os.geteuid()!=0,'permission tests must run as an unprivileged user')
    if operation=='offline':
     buf=io.BytesIO()
     with zipfile.ZipFile(buf,'w') as z:z.writestr('lib/old.txt','new');z.writestr('lib/blocked/item.txt','new')
     status,body=upload(name,buf.getvalue())
    else:status,body=request('/'+name+'/manager/download?m=1')
    message=body.get('info',body.get('message',''))
    ensure(status==200 and body.get('error') and 'permission check failed' in message,[relative,body])
    ensure('Web PHP' in message and '重试' in message,message)
    ensure(relative in message,[relative,message]);ensure(str(root) not in message,message)
    ensure((root/'lib/old.txt').read_text()=='old');ensure((root/'config/config.php').read_bytes()==before)
    ensure('1.0' in (root/'config/version.php').read_text());ensure(not(root/'migration.txt').exists());ensure(not(root/'cache-cleared').exists())
    ensure(not(root/'plugin/manager/rollback').exists(),'preflight must precede backups')
    ensure(target.stat().st_mode & 0o777==mode,'updater changed permissions')
   finally:target.chmod(original)
  for name,relative,mode,operation in [
   ('readonlyfile','lib/old.txt',0o444,'online'),
   ('readonlybackup','plugin/manager',0o555,'online'),
   ('readonlymanifest','plugin/manager/check_file.php',0o444,'online'),
   ('readonlyconfig','config/config.php',0o444,'online'),
   ('readonlyconfigdir','config',0o555,'online'),
   ('readonlyconfiglock','config/config.php.lock',0o444,'online'),
   ('readonlycache','cache/script',0o555,'online'),
   ('readonlynestedcache','cache/script/nested',0o555,'online'),
   ('readonlydatacache','cache/data/v2/aa',0o555,'online'),
   ('readonlydatalock','cache/data/v2/aa/.lock',0o444,'online'),
   ('readonlyupdatelock','cache/tmp/manager/upgrade.lock',0o444,'online'),
   ('readonlynewparent','lib/blocked',0o555,'offline'),
  ]:
   case('permission preflight rejects '+relative+' in '+operation+' update',lambda n=name,r=relative,m=mode,o=operation:permission_failure(n,r,m,o))
  def delete_readonly():
   root=new('deletereadonly');target=root/'lib/remove.txt';target.chmod(0o444)
   status,body=request('/deletereadonly/manager/download?m=1');ensure(status==200 and not body.get('error'),body);ensure(not target.exists());ensure((root/'lib/old.txt').read_text()=='new')
  case('deleting a readable readonly file only requires parent directory write access',delete_readonly)
  def readonly_backup_source():
   root=new('unreadablesource');target=root/'lib/remove.txt';target.chmod(0o200)
   try:
    status,body=request('/unreadablesource/manager/download?m=1');ensure(body.get('error') and 'lib/remove.txt' in body.get('info',''),body);ensure((root/'lib/old.txt').read_text()=='old');ensure(not(root/'plugin/manager/rollback').exists())
   finally:target.chmod(0o644)
  case('an unreadable deletion source is rejected before backup or publication',readonly_backup_source)
  def readonly_delete_tree():
   root=new('readonlydeletetree');target=write(root,'lib/obsolete/nested/file.txt','old').parent;target.chmod(0o555)
   meta={'format':1,'file':['lib/old.txt','lib/obsolete'],'kind':['file','delete'],'setting':{},'code':[]};buf=io.BytesIO()
   with zipfile.ZipFile(buf,'w') as z:z.writestr('lib/old.txt','new');z.writestr('_update.json',json.dumps(meta))
   try:
    status,body=upload('readonlydeletetree',buf.getvalue());ensure(body.get('error') and 'lib/obsolete/nested' in body.get('message',''),body)
    ensure((root/'lib/old.txt').read_text()=='old');ensure(not(root/'plugin/manager/rollback').exists())
   finally:target.chmod(0o755)
  case('offline recursive deletion preflights nested directories before any publication',readonly_delete_tree)
  def manifest_failure():
   root=new('manifestwritefail');target=write(root,'plugin/manager/check_file.php','unchanged');target.chmod(0o444)
   code='define("ROOT",'+lit(str(root)+'/')+');define("LIB",'+lit(str(source/'lib')+'/')+');spl_autoload_register(function($name){$f=LIB.$name.".class.php";if(is_file($f))require_once $f;});require LIB."function.php";require '+lit(source/'plugin/interface_plugin.class.php')+';require ROOT."plugin/manager/class.php";error_reporting(0);exit(plugin_manager::checkFile(ROOT,true)===false?0:1);'
   try:
    result=subprocess.run([php,'-r',code],capture_output=True);ensure(result.returncode==0,result.stderr.decode()+result.stdout.decode());ensure(target.read_text()=='unchanged')
   finally:target.chmod(0o644)
  case('manifest builder returns false when writing its checksum file fails',manifest_failure)
  def failed_cache():
   root=new('cachefail');write(root,'fail-cache','true');status,body=request('/cachefail/manager/download?m=1');ensure(body.get('error'),body);ensure('1.0' in (root/'config/version.php').read_text());ensure(list((root/'plugin/manager/rollback').rglob('old.txt')),'missing recovery backup')
  case('cache failure reports error and preserves old framework version and backup',failed_cache)
  def failed_migration():
   try:
    changes=dict(releases['2.0']);changes['code']='throw new RuntimeException("fixture migration failure");'
    write(master,'plugin/manager/version.php','<?php return '+phpdata({'2.0':changes})+';')
    root=new('migrationfail');status,body=request('/migrationfail/manager/download?m=1');ensure(body.get('error'),body);ensure('1.0' in (root/'config/version.php').read_text())
   finally:write(master,'plugin/manager/version.php','<?php return '+phpdata(releases)+';')
  case('migration exceptions do not commit new framework version',failed_migration)
  def locked():
   import fcntl
   root=new('locked');lock=root/'cache/tmp/manager/upgrade.lock';lock.parent.mkdir(parents=True)
   with lock.open('w') as handle:
    fcntl.flock(handle,fcntl.LOCK_EX|fcntl.LOCK_NB);status,body=request('/locked/manager/download?m=1');ensure(body.get('error') and 'progress' in body.get('info',''),body);ensure((root/'lib/old.txt').read_text()=='old')
  case('concurrent update lock prevents duplicate publication',locked)
  def unauthorized():
   root=new('anonymous');status,body=request('/anonymous/manager/download?m=1',auth=False,json_result=False);ensure(status==401);ensure((root/'lib/old.txt').read_text()=='old')
  case('manager requires administrator authorization',unauthorized)

  def component_updates():
   for kind,idx,field in [('app','UpdateApp','app'),('plugin','updateDemo','idx')]:
    relative=kind+'/'+idx
    write(master,relative+'/info.php','<?php return '+phpdata({field:idx,'ver':'2.0'})+';')
    write(master,relative+'/payload.txt','new-component')
    write(master,relative+'/config.php',"<?php return ['password'=>'master-secret'];")
    write(master,relative+'/config/construction.php',"<?php return ['password'=>['password']];")
    root=new('component'+kind)
    write(root,relative+'/info.php','<?php return '+phpdata({field:idx,'ver':'1.0'})+';')
    write(root,relative+'/config.php',"<?php return ['password'=>'client-secret'];")
    status,body=request('/component'+kind+'/manager/'+kind+'/'+idx);ensure(status==200 and body.get('message')=='plugin_manager_update_succeed',body)
    ensure((root/(relative+'/payload.txt')).read_text()=='new-component');ensure('client-secret' in (root/(relative+'/config.php')).read_text());ensure('master-secret' not in (root/(relative+'/config_new.php')).read_text())
    before=(root/(relative+'/payload.txt')).read_bytes();status,body=request('/component'+kind+'/manager/'+kind+'/'+idx);ensure(body.get('message')=='plugin_manager_update_fail',body);ensure((root/(relative+'/payload.txt')).read_bytes()==before)
  case('application and plugin packages upgrade through HTTPS while retaining local configuration',component_updates)
  def component_versions():
   root=new('versions');status,body=request('/versions/manager/check/version');ensure(status==200 and body.get('version')=='2.0',body)
   ensure('UpdateApp' in (root/'plugin/manager/check_app.php').read_text());ensure('updateDemo' in (root/'plugin/manager/check_plugin.php').read_text())
  case('master version response refreshes application and plugin versions',component_versions)

 finally:
  import signal
  os.killpg(proc.pid,signal.SIGTERM);proc.wait(timeout=10);os.killpg(masterproc.pid,signal.SIGTERM);masterproc.wait(timeout=10);proxy.shutdown();log.seek(0);logtext=log.read();log.close()
  if any(not r['ok'] for r in rows):print(logtext[-3500:])
 print(json.dumps({'http_requests':len(requests),'passed':sum(r['ok'] for r in rows),'failed':sum(not r['ok'] for r in rows),'results':rows},ensure_ascii=False,indent=2))
 raise SystemExit(any(not r['ok'] for r in rows))
