"""以本机当前版本和源码为发布基线，走真实框架、登录、CSRF与HTTPS的隔离升级测试。

仅复制运行代码与公共资源；不复制正式配置、插件状态、数据库或发布清单。
MYSTEP_PHP指定PHP7.4；MYSTEP_UPDATE_SOURCE指定源码根；MYSTEP_RELEASE_REPORT可保存JSON报告。
"""
from pathlib import Path
import contextlib,hashlib,http.cookiejar,http.server,io,json,os,re,shutil,signal,socket,ssl,subprocess,tempfile,threading,time,urllib.error,urllib.parse,urllib.request,zipfile
source=Path(os.environ.get('MYSTEP_UPDATE_SOURCE',str(Path(__file__).resolve().parents[2])))
php=os.environ.get('MYSTEP_PHP','/Applications/ServBay/package/php/7.4/current/bin/php')
system_package=os.environ.get('MYSTEP_SYSTEM_PACKAGE')=='1'
results=[];requests=[]
def literal(value):return "'"+str(value).replace('\\','\\\\').replace("'","\\'")+"'"
def free_port():
 with socket.socket() as sock:sock.bind(('127.0.0.1',0));return sock.getsockname()[1]
def php_json(value):return 'json_decode('+literal(json.dumps(value,ensure_ascii=False))+',true)'
def require(value,message):
 if not value:raise AssertionError(message)
current=subprocess.check_output([php,'-r','echo require $argv[1];',str(source/'config/version.php')],text=True).strip()
old='0.0.0'
require(subprocess.check_output([php,'-r','echo version_compare($argv[1],$argv[2],">")?"yes":"no";',current,old],text=True)=='yes','Current release must be newer than the fixture baseline')
members=['lib/myStep.class.php','lib/myRouter.class.php','lib/myConfig.class.php','lib/myDb.class.php','lib/myCache.class.php','lib/mySecurity.class.php','lib/cache/myCacheCodec.class.php','lib/session/sess_file.class.php','plugin/manager/class.php','plugin/manager/template/manager.tpl','app/Admin/AdminService.class.php','static/js/global.js','static/js/checkForm.js','config/version.php']
expected={name:hashlib.sha256((source/name).read_bytes()).hexdigest() for name in members}
protected_paths=['config/config.php','config/version.php','plugin/plugin.db','plugin/manager/config.php','plugin/manager/version.php','plugin/manager/check_file.php','plugin/manager/check_app.php','plugin/manager/check_plugin.php']
protected={name:hashlib.sha256((source/name).read_bytes()).hexdigest() if (source/name).is_file() else None for name in protected_paths}
with tempfile.TemporaryDirectory(prefix='mystep-current-release-') as tmp:
 outer=Path(tmp);clientport=free_port();masterport=free_port();tlsport=free_port();base=f'http://127.0.0.1:{clientport}';remote=f'https://127.0.0.1:{tlsport}/master'
 cert=outer/'ca.pem';key=outer/'key.pem'
 subprocess.run(['openssl','req','-x509','-newkey','rsa:2048','-nodes','-keyout',str(key),'-out',str(cert),'-days','1','-subj','/CN=127.0.0.1','-addext','subjectAltName=IP:127.0.0.1','-addext','basicConstraints=critical,CA:TRUE'],check=True,capture_output=True)
 password_hash=subprocess.check_output([php,'-r','echo password_hash("release-fixture-pass",PASSWORD_DEFAULT);'],text=True)
 def write(root,path,text):
  target=root/path;target.parent.mkdir(parents=True,exist_ok=True);target.write_text(text);return target
 def ignored(directory,names):
  skipped=[]
  for name in names:
   path=Path(directory)/name
   if (name=='cache' and Path(directory)!=source/'lib') or path.is_symlink() or name in ['.svn','.git','.DS_Store','file','files','pack','rollback','update','config.php','config_new.php','plugin.php','check_file.php','check_app.php','check_plugin.php','error.log','ban_ip.php'] or name.endswith(('.db','.db-wal','.db-shm','.lock','.log')):skipped.append(name)
  return skipped
 template=outer/'snapshot';template.mkdir()
 for name in ['lib','app/myStep','app/Admin','plugin/manager','static','vendor','config/construction']:
  shutil.copytree(source/name,template/name,ignore=ignored)
 for name in ['index.php','readme.md','config/config_default.php','config/class_alias.php','config/version.php','plugin/interface_plugin.class.php']:
  target=template/name;target.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(source/name,target)
 # Admin configuration contains only application defaults; myStep's auto DB connection is disabled in every fixture.
 shutil.copy2(source/'app/Admin/config.php',template/'app/Admin/config.php')
 bootstrap='''<?php
$site=explode('/',trim(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH),'/'))[0];
if(!in_array($site,['master','online','offline','latest','fresh'],true)){http_response_code(404);exit;}
$root=__DIR__.'/'.$site.'/';$_SERVER['DOCUMENT_ROOT']=__DIR__;$_SERVER['SCRIPT_NAME']='/'.$site.'/index.php';$_SERVER['SCRIPT_FILENAME']=$root.'index.php';
if($site==='fresh') {
 $path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
 if(preg_match('#/install-assets/(jquery.js|bootstrap.css)$#',$path,$match)) {readfile($root.'install-assets/'.$match[1]);exit;}
 if(is_file($root.'mystep.pack') || basename($path)==='install.php') {chdir($root);require $root.(basename($path)==='install.php'?'install.php':'index.php');exit;}
}
define('ROOT',$root);foreach(['LIB'=>'lib/','APP'=>'app/','CONFIG'=>'config/','PLUGIN'=>'plugin/','CACHE'=>'cache/','STATICS'=>'static/','VENDOR'=>'vendor/','FILE'=>'files/'] as $name=>$path)define($name,ROOT.$path);
spl_autoload_register(function($name){foreach([LIB.$name.'.class.php',LIB.'database/'.$name.'.class.php'] as $file)if(is_file($file)){require_once $file;return;}});
require LIB.'myStep.class.php';myFile::setTLSOptions(['cafile'=>CA_FILE]);
function releaseFixtureCaptcha(){$_SESSION['captcha']='ABCD';header('Content-Type: application/json');echo json_encode(['token'=>mySecurity::token()]);exit;}
myStep::init();
'''
 (outer/'router.php').write_text(bootstrap.replace('CA_FILE',literal(cert)))
 roots={}
 for name in ['master','online','offline','latest']:
  root=outer/name;shutil.copytree(template,root);roots[name]=root
  for path in ['cache','files','config/priority']:(root/path).mkdir(parents=True,exist_ok=True)
  config="<?php include __DIR__.'/config_default.php';$setting['gen']['s_usr']='release-fixture';$setting['gen']['s_pwd']="+literal(password_hash)+";$setting['gen']['cache_page']=false;$setting['gen']['debug']=false;$setting['gen']['language']='chs';$setting['db']['auto']=false;$setting['cookie']['prefix']='"+name+"_';$setting['router']['mode']='rewrite';$setting['web']['gzip_level']=-1;$setting['web']['update']="+literal(remote)+";$setting['web']['title']='Local release fixture';return $setting;"
  write(root,'config/config.php',config)
  write(root,'app/myStep/config.php',"<?php return ['gen'=>['cache_page'=>false,'path_admin'=>'console'],'db'=>['auto'=>false]];")
  write(root,'config/class.php',"<?php return [['path'=>'lib/','ext'=>'.class.php,.php','idx'=>['interface_plugin'=>'../plugin/interface_plugin.class.php']],['path'=>'lib/database/','ext'=>'.class.php,.php']];")
  write(root,'config/domain.php','<?php return [];')
  write(root,'app/myStep/plugin.php',"<?php return ['manager'];")
  write(root,'app/Admin/plugin.php','<?php return [];')
  write(root,'plugin/manager/config.php',"<?php return ['update'=>true,'pack'=>true];")
  route=(root/'app/myStep/route.php').read_text().replace('$rule = array(',"$rule = array(array('/_release_captcha','releaseFixtureCaptcha'),",1);write(root,'app/myStep/route.php',route)
  setup="define('ROOT',"+literal(str(root)+'/')+");define('LIB',ROOT.'lib/');require LIB.'function.php';spl_autoload_register(function($name){foreach([LIB.$name.'.class.php',LIB.'database/'.$name.'.class.php'] as $file)if(is_file($file)){require_once $file;return;}});$d=new myDb('simpleDB','plugin',ROOT.'plugin/');$d->create([['order',2],['active',1],['idx',40]]);$d->insert(['order'=>1,'active'=>1,'idx'=>'manager'],true);$d->close();"
  subprocess.run([php,'-r',setup],check=True,capture_output=True)
  if name in ['online','offline']:
   write(root,'config/version.php','<?php return '+literal(old)+';')
   for member in members:
    if member=='config/version.php':continue
    suffix=b'\n<!-- previous fixture revision -->\n' if member.endswith('.tpl') else b'\n// previous fixture revision\n'
    with (root/member).open('ab') as output:output.write(suffix)
  else:shutil.copy2(source/'config/version.php',root/'config/version.php')
 if system_package:roots['fresh']=outer/'fresh'
 release={current:{'info':'Local current release '+current,'file':['/'+member for member in members],'setting':{'web':{'description':'local release migration verified'}},'code':'myFile::saveFile(ROOT."release-migration.txt", '+literal(current)+');'}}
 write(roots['master'],'plugin/manager/version.php','<?php return '+php_json(release)+';')
 # Unused clone release declarations are also local; the obsolete public source is never contacted.
 for name in ['online','offline','latest']:write(roots[name],'plugin/manager/version.php','<?php return [];')
 remote_state={'enabled':True,'requests':[]}
 class Gateway(http.server.BaseHTTPRequestHandler):
  def do_GET(self):
   remote_state['requests'].append(self.path)
   if not remote_state['enabled']:
    self.send_response(503);self.send_header('Content-Length','0');self.end_headers();self.wfile.flush()
    with contextlib.suppress(OSError):self.connection.unwrap()
    return
   try:
    with urllib.request.urlopen(f'http://127.0.0.1:{masterport}'+self.path,timeout=30) as response:status=response.status;body=response.read();mime=response.headers.get('Content-Type','application/octet-stream')
   except urllib.error.HTTPError as error:status=error.code;body=error.read();mime='application/octet-stream'
   self.send_response(status);self.send_header('Content-Type',mime);self.send_header('Content-Length',str(len(body)));self.end_headers();self.wfile.write(body);self.wfile.flush()
   with contextlib.suppress(OSError):self.connection.unwrap()
  def log_message(self,*args):pass
 gateway=http.server.ThreadingHTTPServer(('127.0.0.1',tlsport),Gateway);tls=ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER);tls.load_cert_chain(cert,key);gateway.socket=tls.wrap_socket(gateway.socket,server_side=True)
 threading.Thread(target=gateway.serve_forever,daemon=True).start()
 class NoRedirect(urllib.request.HTTPRedirectHandler):
  def redirect_request(self,*args,**kwargs):return None
 clients={name:urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()),NoRedirect()) for name in roots}
 public=urllib.request.build_opener(NoRedirect())
 log=open(outer/'php.log','w+');processes=[]
 def request(site,path,method='GET',data=None,token=None,status=200,binary=False,anonymous=False,content_type=None,extra_headers=None):
  headers={'Accept-Encoding':'identity'}
  headers.update(extra_headers or {})
  if token:headers['X-CSRF-Token']=token
  if content_type:headers['Content-Type']=content_type
  payload=data if isinstance(data,bytes) or data is None else urllib.parse.urlencode(data).encode()
  req=urllib.request.Request(base+'/'+site+'/'+path.lstrip('/'),data=payload,headers=headers,method=method)
  try:response=(public if anonymous else clients[site]).open(req,timeout=45)
  except urllib.error.HTTPError as error:response=error
  with response:body=response.read();actual=response.status;response_headers=response.headers
  requests.append({'site':site,'path':path,'method':method,'status':actual})
  require(actual==status,{'request':requests[-1],'body':body[:600].decode('utf-8',errors='replace')})
  return (body if binary else body.decode('utf-8')),response_headers
 def check(name,run):
  run();results.append({'case':name,'ok':True});print('PASS '+name,flush=True)
 def login(site):
  body,_=request(site,'Admin/login');require('管理员登录' in body,'Missing real Admin login form')
  body,_=request(site,'_release_captcha');token=json.loads(body)['token']
  _,headers=request(site,'Admin/login',method='POST',data={'username':'release-fixture','password':'release-fixture-pass','captcha':'ABCD','_ms_csrf':token},status=303)
  require('/Admin/' in headers['Location'],'Login did not use the real redirect')
  body,_=request(site,'Admin/');require('运行概览' in body and '管理员登录' not in body,'Admin session was not established')
  body,_=request(site,'ms_csrf');return json.loads(body)['token']
 def hashes(site):return {member:hashlib.sha256((roots[site]/member).read_bytes()).hexdigest() for member in members}
 def version(site):return subprocess.check_output([php,'-r','echo require $argv[1];',str(roots[site]/'config/version.php')],text=True)
 def post(site,path,token):return json.loads(request(site,path,method='POST',data=b'',token=token)[0])
 def ready(port):
  for _ in range(100):
   try:
    with socket.create_connection(('127.0.0.1',port),timeout=.1):return
   except OSError:time.sleep(.05)
  raise RuntimeError('PHP fixture did not start')
 try:
  for port in [clientport,masterport]:
   processes.append(subprocess.Popen([php,'-d','display_errors=0','-d','opcache.enable_cli=0','-d','memory_limit=256M','-S',f'127.0.0.1:{port}','-t',str(outer),str(outer/'router.php')],stdout=log,stderr=log,start_new_session=True));ready(port)
  tokens={}
  def real_login():
   for site in ['online','offline','latest']:tokens[site]=login(site)
  check('real framework boot and administrator login for all clients',real_login)
  def auth_csrf():
   body,_=request('online','manager/download?m=1',status=405);require('POST required' in body,'GET did not hit real method guard')
   body,_=request('online','manager/download?m=1',method='POST',data=b'',status=403);require('CSRF' in body,'Missing CSRF was not rejected')
   body,_=request('online','manager/',anonymous=True);require('captcha' in body and '登录' in body,'Unauthenticated manager did not require login')
   require(version('online')==old,'Rejected request changed fixture version')
  check('real manager routing rejects anonymous requests GET mutation and missing CSRF',auth_csrf)
  def manager_layouts():
   for referer in ['',base+'/latest/console/error','https://foreign.test/latest/Admin/error']:
    body,_=request('latest','manager/',extra_headers={'Referer':referer} if referer else {});require('body class="py-5"' in body and 'adm-shell' not in body,'Expected myStep layout fallback')
   for route in ['Admin/error','index.php/Admin/error','index.php?Admin/error/&x=1']:
    body,headers=request('latest','manager/',extra_headers={'Referer':base+'/latest/'+route},status=302);require(headers['Location']=='/latest/manager/?_plugin_app=Admin',headers['Location'])
   body,_=request('latest','manager/?_plugin_app=Admin');require('adm-plugin-page' in body and 'adm-shell' in body and 'manager-feedback' in body,'Shared layout did not render manager on its original route')
   body,_=request('latest','Admin/manager/',extra_headers={'Referer':base+'/latest/manager/'})
   require('adm-shell' in body and 'adm-plugin-manager' in body and 'manager-feedback' in body,'Manager did not render in the real Admin layout');require('/latest/Admin/function/plugin/setting/?idx=manager' in body,'Plugin settings link escaped Admin');require('/latest/Admin/setting?target=' in body,'Application settings link escaped Admin')
   body,_=request('latest','Admin/manager/',extra_headers={'Referer':base+'/latest/Admin/manager/'});require('adm-plugin-manager' in body,'Refresh lost Admin layout')
   body,_=request('latest','Admin/manager/',anonymous=True);require('管理员登录' in body and 'adm-plugin-manager' not in body,'Manager presentation bypassed Admin login')
  check('manager follows trusted Referer into Admin while empty legacy and foreign sources use myStep',manager_layouts)
  def manager_custom_style():
   root=roots['latest'];config=root/'app/Admin/config.php';before=config.read_bytes();style=root/'app/Admin/template/fixture_manager';style.mkdir()
   try:
    (style/'main.tpl').write_text('<html><head><!--page_start--></head><body class="custom-admin-layout"><!--main--><!--page_end--></body></html>');(style/'manager.tpl').write_text('<section class="custom-manager-view"><!--content--></section>');(style/'plugin.tpl').write_text('<section class="custom-plugin-view"><!--content--></section>');config.write_text("<?php return ['gen'=>['cache_page'=>false],'web'=>['minify'=>false],'template'=>['name'=>'main','path'=>'app/Admin/template','style'=>'fixture_manager'],'db'=>['auto'=>false]];")
    body,_=request('latest','Admin/manager/');require('custom-admin-layout' in body and 'custom-manager-view' in body and 'manager-feedback' in body,'Configured Admin style or matching manager template was ignored')
    body,_=request('latest','manager/?_plugin_app=Admin');require('custom-admin-layout' in body and 'custom-plugin-view' in body,'Shared plugin page ignored the application custom style')
   finally:config.write_bytes(before);shutil.rmtree(style)
   config=root/'app/myStep/config.php';before=config.read_bytes();style=root/'app/myStep/template/fixture_manager';style.mkdir()
   try:
    (style/'main.tpl').write_text('<html><head><!--page_start--></head><body class="custom-mystep-layout"><!--main--><!--page_end--></body></html>');config.write_text("<?php return ['gen'=>['cache_page'=>false,'path_admin'=>'console'],'template'=>['name'=>'main','path'=>'app/myStep/template','style'=>'fixture_manager'],'db'=>['auto'=>false]];")
    body,_=request('latest','manager/');require('custom-mystep-layout' in body and 'manager-feedback' in body,'Empty Referer ignored configured myStep template style')
   finally:config.write_bytes(before);shutil.rmtree(style)
  check('manager respects the host application custom template style and matching module template',manager_custom_style)
  def common_plugin_layouts():
   root=roots['latest'];ids=['frontend','badReq','sample'];registry=root/'plugin/plugin.db';bindings=root/'app/myStep/plugin.php';saved_registry=registry.read_bytes();saved_bindings=bindings.read_bytes()
   try:
    for plugin in ids:shutil.copytree(source/'plugin'/plugin,root/'plugin'/plugin,ignore=ignored)
    write(root,'plugin/frontend/config.php',"<?php return ['detail'=>'{}'];");write(root,'plugin/badReq/config.php',"<?php return ['forward'=>'fixture denied'];")
    write(root,'app/Document/info.php',"<?php return ['app'=>'Document','name'=>'Fixture Document'];")
    for name in ['jquery/jquery-3.6.1.js','bootstrap/5.2.2/css/bootstrap.css','bootstrap/5.2.2/js/bootstrap.js']:write(root,'plugin/frontend/files/'+name,'/* isolated selectable resource */')
    write(root,'app/myStep/plugin.php',"<?php return ['manager','frontend','badReq','sample'];")
    code="define('ROOT',"+literal(str(root)+'/')+");define('LIB',ROOT.'lib/');require LIB.'function.php';spl_autoload_register(function($n){foreach([LIB.$n.'.class.php',LIB.'database/'.$n.'.class.php'] as $f)if(is_file($f)){require_once $f;return;}});$d=new simpleDB('plugin',ROOT.'plugin/');foreach(['frontend','badReq','sample'] as $id)$d->insert(['order'=>1,'active'=>1,'idx'=>$id],true);$d->close();"
    subprocess.run([php,'-r',code],check=True,capture_output=True)
    for plugin,path in [('frontend','frontend/'),('badReq','badReq/?mode=ip&keyword=fixture'),('sample','sample_route/')]:
     require(not(root/('app/Admin/module/'+plugin+'.php')).exists(),'Test should not rely on a per-plugin Admin module')
     _,headers=request('latest',path,extra_headers={'Referer':base+'/latest/Admin/setting'},status=302);link=headers['Location'];require('_plugin_app=Admin' in link and path.split('?')[0] in link,link)
     body,_=request('latest',link.removeprefix('/latest/'));require('adm-shell' in body and 'adm-plugin-page' in body and 'adm-plugin-'+plugin in body,'Generic layout failed for '+plugin)
     if plugin=='badReq':require('badReqPageURL=' in body and 'jumpURL=' in body and 'keyword=fixture' in body and '_plugin_app=Admin' in body,'badReq generated links lost filters or layout context')
    config=root/'plugin/frontend/config.php';before=config.read_bytes()
    request('latest','frontend/?_plugin_app=Admin',method='POST',data={'detail':'{}'},status=403);require(config.read_bytes()==before,'Missing CSRF changed frontend configuration')
    details=json.dumps({'Document':{'jq':'jquery-3.6.1.js','bs':'5.2.2'}})
    body,_=request('latest','frontend/?_plugin_app=Admin',method='POST',data={'detail':details},token=tokens['latest']);require('adm-plugin-frontend' in body and re.search(r'<textarea\b[^>]*\bid=[\"\']?detail\b',body),'POST did not preserve the plugin form and Admin layout');require(b'Document' in config.read_bytes(),'Real frontend module did not save')
    request('latest','badReq/?m=reset&_plugin_app=Admin',status=405)
    for operation in ['ban','unban']:
     _,headers=request('latest','badReq/',method='POST',data={'m':operation,'ip':'192.0.2.123','msg':'fixture denied'},token=tokens['latest'],extra_headers={'Referer':base+'/latest/badReq/?_plugin_app=Admin'},status=302);require('_plugin_app=Admin' in headers['Location'],'Mutation redirect lost layout context')
    body,_=request('latest','frontend/');require('body class="py-5"' in body and 'adm-shell' not in body,'An empty Referer inherited a previous layout')
    body,_=request('latest','frontend/?_plugin_app[invalid]=Admin');require('body class="py-5"' in body,'Invalid presentation selector did not fall back')
   finally:
    registry.write_bytes(saved_registry);bindings.write_bytes(saved_bindings)
    for plugin in ids:shutil.rmtree(root/'plugin'/plugin,ignore_errors=True)
  check('all plugin management pages share one host layout with real POST CSRF filtering and return links',common_plugin_layouts)
  def version_check():
   for site in ['online','offline']:require(post(site,'manager/check/version',tokens[site])['version']==current,'Older client was not offered the current release')
   require(post('latest','manager/check/version',tokens['latest'])['version']=='','Current source version should be up to date')
  check('local current release is offered only to simulated older clients',version_check)
  def online_upgrade():
   require(hashes('online')!=expected,'Older fixture must differ from published files')
   result=post('online','manager/download?m=1',tokens['online']);require(not result.get('error'),result)
   require(hashes('online')==expected,'Online release hashes differ from current source')
   require((roots['online']/'release-migration.txt').read_text()==current,'Online migration missing')
   require('local release migration verified' in (roots['online']/'config/config.php').read_text(),'Online configuration migration missing')
   body,_=request('online','Admin/');require('运行概览' in body,'Updated framework failed to boot')
   require(post('online','manager/check/version',tokens['online'])['version']=='','Updated client still offers update')
  check('online update publishes 14 actual source files and the updated framework boots',online_upgrade)
  def offline_download():
   before=hashes('offline');config_before=(roots['offline']/'config/config.php').read_bytes();result=post('offline','manager/download?m=0',tokens['offline']);require(result.get('link') and not result.get('error'),result)
   require(hashes('offline')==before and (roots['offline']/'config/config.php').read_bytes()==config_before,'Download changed client source or settings')
   path=result['link'].removeprefix('/offline/');body,headers=request('offline',path,binary=True);require(body.startswith(b'PK'),'Offline artifact is not ZIP')
   archive=zipfile.ZipFile(io.BytesIO(body));meta=json.loads(archive.read('_update.json'));require(meta['file']==release[current]['file'],'Offline metadata lost file order')
   for member in members:require(hashlib.sha256(archive.read(member)).hexdigest()==expected[member],'Archive member differs: '+member)
   (outer/'offline.zip').write_bytes(body)
  check('offline ZIP contains the exact current source bytes without changing the client',offline_download)
  def offline_upload():
   blob=(outer/'offline.zip').read_bytes();boundary='CurrentSourceUpgrade';payload=(f'--{boundary}\r\nContent-Disposition: form-data; name="file"; filename="current.zip"\r\nContent-Type: application/zip\r\n\r\n').encode()+blob+f'\r\n--{boundary}--\r\n'.encode()
   remote_before=len(remote_state['requests']);remote_state['enabled']=False
   try:
    body,_=request('offline','manager/upload',method='POST',data=payload,token=tokens['offline'],content_type='multipart/form-data; boundary='+boundary);result=json.loads(body);require(result.get('error')==0,result)
    require(hashes('offline')==expected,'Offline files differ from current release');require((roots['offline']/'release-migration.txt').read_text()==current,'Offline migration missing')
    body,_=request('offline','Admin/');require('运行概览' in body,'Offline-updated framework failed to boot')
   finally:remote_state['enabled']=True
   require(len(remote_state['requests'])==remote_before,'Offline installation contacted the unavailable update source')
   require(post('offline','manager/check/version',tokens['offline'])['version']=='','Offline-updated client still offers update')
  check('real multipart upload applies current release and the offline-updated framework boots',offline_upload)
  def equal_clients():
   for site in ['online','offline','latest']:require(version(site)==current,'Unexpected final version');require(hashes(site)==expected,'Final source mismatch: '+site)
   for site in ['online','offline']:
    listed=json.loads(subprocess.check_output([php,'-r','require $argv[1];echo json_encode($list_file);',str(roots[site]/'plugin/manager/check_file.php')],text=True))
    require('/lib/cache/myCacheCodec.class.php' in listed and '/lib/session/sess_file.class.php' in listed,'Updated driver sources are missing from checksum manifest')
    require('/config/config.php' not in listed and not any(path.startswith('/cache/') for path in listed),'Manifest includes runtime/configuration data')
  check('online offline and already-current clients match the local release hashes',equal_clients)
  if system_package:
   package={}
   def system_archive():
    root=roots['latest'];write(root,'app/allow',(source/'app/allow').read_text());secrets=['system-pack-password-fixture','system-pack-cache-fixture','system-pack-upload-fixture','system-pack-api-key-fixture']
    write(root,'plugin/privateDemo/info.php',"<?php return ['idx'=>'privateDemo','ver'=>'1'];")
    write(root,'plugin/privateDemo/config.php',"<?php return ['password'=>'"+secrets[0]+"','visible'=>'keep','nested'=>['api_key'=>'"+secrets[3]+"']];")
    write(root,'plugin/privateDemo/config/construction.php',"<?php return ['password'=>['missing.value','password']];")
    write(root,'plugin/privateDemo/asset/files/public.txt','public-asset');write(root,'cache/private.txt',secrets[1]);write(root,'files/private.txt',secrets[2])
    original=(root/'plugin/privateDemo/config.php').read_bytes()
    request('latest','pack/',status=405);request('latest','pack/',method='POST',data=b'',status=403)
    link=request('latest','pack/',method='POST',data=b'',token=tokens['latest'])[0].strip()
    require(link.startswith('/latest/manager/file/'),{'invalid subdirectory download link':link})
    blob,_=request('latest',link.removeprefix('/latest/'),binary=True);archive=zipfile.ZipFile(io.BytesIO(blob));version_root='mystep_v'+current+'/'
    require(version_root+'mystep.pack' in archive.namelist(),'Missing standalone package payload')
    import zlib
    raw=zlib.decompress(archive.read(version_root+'mystep.pack'));entries={};offset=0
    while offset<len(raw):
     end=raw.index(b'\n',offset);fields=raw[offset:end].split(b'|');offset=end+1
     if fields[0]==b'file':
      name=fields[1].decode();size=int(fields[2]);entries[name]=raw[offset:offset+size];offset+=size
    for name in members:require(hashlib.sha256(entries.get(name,b'')).hexdigest()==expected[name],'Missing/changed core member: '+name)
    require(entries.get('plugin/privateDemo/asset/files/public.txt')==b'public-asset','Public asset files directory was mistaken for uploads')
    require('app/Admin/config.php' in entries and 'app/myStep/config.php' in entries,'Missing application defaults')
    require('app/Admin/config/construction.php' in entries,'Missing settings schema')
    require('config/config.php' not in entries and 'plugin/plugin.db' not in entries,'Live state exported')
    require(all(secret.encode() not in raw for secret in secrets),'Private configuration/cache/upload leaked')
    require(b'keep' in entries.get('plugin/privateDemo/config.php',b''),'Public configuration was lost')
    require((root/'plugin/privateDemo/config.php').read_bytes()==original,'Packaging changed live plugin config')
    require('https://cdn.' not in archive.read(version_root+'index.php').decode(),'Installer depends on external assets')
    require(version_root+'install-assets/jquery.js' in archive.namelist(),'Installer local assets missing')
    package.update(link=link,blob=blob,entries=entries)
    target=outer/'distribution';archive.extractall(target);shutil.move(str(target/version_root),roots['fresh'])
   check('system package preserves core drivers schemas defaults and excludes live secrets',system_archive)
   def cache_package():
    link=request('latest','pack/',method='POST',data=b'',token=tokens['latest'])[0].strip();require(link==package['link'],'Unchanged source did not reuse package')
    path=roots['latest']/'readme.md';before=path.read_bytes()
    try:
     path.write_bytes(before+b'\nfixture package revision\n');changed=request('latest','pack/',method='POST',data=b'',token=tokens['latest'])[0].strip();require(changed!=link,'Same-version change reused stale package')
    finally:path.write_bytes(before)
   check('system package cache follows source contents rather than only version number',cache_package)
   def package_entries(blob):
    import zlib
    archive=zipfile.ZipFile(io.BytesIO(blob));raw=zlib.decompress(archive.read('mystep_v'+current+'/mystep.pack'));entries={};offset=0
    while offset<len(raw):
     end=raw.index(b'\n',offset);fields=raw[offset:end].split(b'|');offset=end+1
     if fields[0]==b'file':
      name=fields[1].decode();size=int(fields[2]);entries[name]=raw[offset:offset+size];offset+=size
    return entries
   def corrupt_package_cache():
    query=urllib.parse.parse_qs(urllib.parse.urlsplit(package['link']).query);file=roots['latest']/'cache/tmp/manager'/query['name'][0];file.write_bytes(b'corrupted-cache')
    link=request('latest','pack/',method='POST',data=b'',token=tokens['latest'])[0].strip();blob,_=request('latest',link.removeprefix('/latest/'),binary=True)
    require('lib/cache/myCacheCodec.class.php' in package_entries(blob),'Corrupt cached archive was reused')
   check('corrupted cached system ZIP is rebuilt before download',corrupt_package_cache)
   def directory_rules():
    root=roots['latest'];write(root,'plugin/privateDemo/included.txt','included');write(root,'plugin/privateDemo/allow-excluded.txt','excluded');write(root,'plugin/privateDemo/ignore-excluded.txt','excluded')
    write(root,'plugin/privateDemo/allow','info.php\nconfig.php\nconfig\nincluded.txt\nignore-excluded.txt');write(root,'plugin/privateDemo/ignore','ignore-excluded.txt')
    try:
     link=request('latest','pack/',method='POST',data=b'',token=tokens['latest'])[0].strip();blob,_=request('latest',link.removeprefix('/latest/'),binary=True);entries=package_entries(blob)
     require('plugin/privateDemo/included.txt' in entries,'Explicit allowed member missing');require('plugin/privateDemo/allow-excluded.txt' not in entries and 'plugin/privateDemo/ignore-excluded.txt' not in entries,'Directory packaging rules ignored')
    finally:
     for name in ['allow','ignore','included.txt','allow-excluded.txt','ignore-excluded.txt']:(root/'plugin/privateDemo'/name).unlink(missing_ok=True)
   check('system export honors component allow and ignore rules',directory_rules)
   def pack_disabled():
    file=roots['latest']/'plugin/manager/config.php';before=file.read_bytes()
    try:
     file.write_text("<?php return ['pack'=>false,'update'=>true];");request('latest','pack/',method='POST',data=b'',token=tokens['latest'],status=404)
    finally:file.write_bytes(before)
   check('system packaging respects the disabled pack switch in production mode',pack_disabled)
   def package_lock():
    import fcntl
    file=roots['latest']/'cache/tmp/manager/system.lock'
    with file.open('a') as handle:
     fcntl.flock(handle,fcntl.LOCK_EX|fcntl.LOCK_NB);request('latest','pack/',method='POST',data=b'',token=tokens['latest'],status=500)
   check('concurrent system packaging is rejected by the real file lock',package_lock)
   def installation_zip_as_update():
    buffer=io.BytesIO()
    with zipfile.ZipFile(buffer,'w') as archive:archive.writestr('mystep_v'+current+'/mystep.pack',b'fixture installation payload');archive.writestr('mystep_v'+current+'/index.php','<?php echo "installer";')
    boundary='WrongInstallationArchive';payload=(f'--{boundary}\r\nContent-Disposition: form-data; name="file"; filename="system.zip"\r\nContent-Type: application/zip\r\n\r\n').encode()+buffer.getvalue()+f'\r\n--{boundary}--\r\n'.encode()
    body,_=request('latest','manager/upload',method='POST',data=payload,token=tokens['latest'],content_type='multipart/form-data; boundary='+boundary);result=json.loads(body);require(result.get('error') and 'installation archive' in result.get('message',''),result);require(not (roots['latest']/('mystep_v'+current)).exists(),'Installation ZIP was copied into live source')
   check('installation ZIP cannot be mistaken for an offline system update',installation_zip_as_update)
   def readonly_install_root():
    root=roots['fresh'];mode=root.stat().st_mode&0o777;before=(root/'index.php').read_bytes();root.chmod(0o555)
    try:
     body,_=request('fresh','index.php',status=403);require('install-permissions' in body and 'alert-danger' in body,body[:600]);require(re.search(r'<button id="install"[^>]*disabled',body),'Install button should be disabled');require('PHP' in body,'Missing permission advice');require((root/'index.php').read_bytes()==before,'Installer changed on denied GET');require(not(root/'install.php').exists(),'Denied GET staged installer')
    finally:root.chmod(mode)
   check('readonly install root displays actionable feedback and disables the install button',readonly_install_root)
   def installer_permission_failures():
    root=roots['fresh'];body,_=request('fresh','index.php');match=re.search(r"const installToken = '([a-f0-9]+)'",body);require(match,'Missing installer CSRF token');token=match.group(1);require('alert-success' in body,'Missing initial permission feedback');before=(root/'index.php').read_bytes();payload=(root/'mystep.pack').read_bytes()
    for relative,mode in [('config',0o555),('lib/cache',0o555),('install.log',0o444),('mystep.pack',0o000)]:
     target=root/relative
     if not target.exists():
      if relative.endswith('.log'):target.write_text('fixture log')
      else:target.mkdir(parents=True,exist_ok=True)
     original=target.stat().st_mode&0o777;target.chmod(mode)
     try:
      body,_=request('fresh','install.php',method='POST',data={'step':999,'csrf':token},status=403);require(relative in body and 'PHP' in body,body);require(str(root) not in body,'Absolute path leaked');require(target.stat().st_mode&0o777==mode,'Installer relaxed permissions');require((root/'index.php').read_bytes()==before,'A source member was published before permission failure');require(not(root/'lib/myStep.class.php').exists(),'Core files were partly installed')
     finally:target.chmod(original)
     require((root/'mystep.pack').read_bytes()==payload,'Failed install changed or removed the source package')
   check('installer POST preflights configuration nested members log and archive access without partial publication',installer_permission_failures)
   def standalone_install():
    body,_=request('fresh','index.php');match=re.search(r"const installToken = '([a-f0-9]+)'",body);require(match,'Missing standalone installer token');token=match.group(1)
    request('fresh','install-assets/jquery.js');request('fresh','install-assets/bootstrap.css')
    request('fresh','install.php?step=999',status=403)
    request('fresh','install.php',method='POST',data={'step':999,'csrf':'invalid'},status=403)
    require((roots['fresh']/'mystep.pack').exists(),'Rejected installer request removed payload')
    body,_=request('fresh','install.php',method='POST',data={'step':999,'csrf':token});require(body.strip()=='done!',body[:300])
    require(not (roots['fresh']/'mystep.pack').exists(),'Installed payload was not cleaned')
    request('fresh','install.php',method='POST',data={'step':3,'csrf':token},status=302)
    require(not (roots['fresh']/'install.php').exists(),'Installer was not cleaned')
    body,_=request('fresh','');require('/fresh/static/' in body,'Initial setup resources lost deployment prefix');require('setting[gen][s_usr]' in body,'Fresh framework did not show initial settings')
    for name in members:require(hashlib.sha256((roots['fresh']/name).read_bytes()).hexdigest()==expected[name],'Standalone output differs: '+name)
   check('standalone installer enforces token extracts package and boots initial settings',standalone_install)
   def configure_installed():
    config=json.loads(subprocess.check_output([php,'-r','echo json_encode(require $argv[1]);',str(roots['fresh']/'config/config_default.php')],text=True))
    config['gen'].update(s_usr='release-fixture',s_pwd='release-fixture-pass',language='chs');config['db']['auto']=False;config['db']['password']='';config['cookie']['prefix']='fresh_';config['router']['mode']='rewrite';config['web']['update']=remote
    fields={}
    def flatten(data,prefix):
     for key,value in data.items():
      name=prefix+'['+key+']'
      if isinstance(value,dict):flatten(value,name)
      else:fields[name]='true' if value is True else ('false' if value is False else value)
    flatten(config,'setting');request('fresh','',method='POST',data=fields,status=302)
    tokens['fresh']=login('fresh')
    body,_=request('fresh','Admin/function/plugin/view?idx=manager')
    values={name:re.search('name="'+name+'" value="([^"]*)"',body).group(1) for name in ['version','registry_version']}
    values.update({'_action':'install','_ms_csrf':tokens['fresh'],'idx':'manager','apps[]':'myStep'})
    request('fresh','Admin/function/plugin/view?idx=manager',method='POST',data=values,status=303)
    require(post('fresh','manager/check/version',tokens['fresh'])['version']=='','Freshly installed current release offers update')
   check('fresh package saves initial settings logs in and installs manager through Admin',configure_installed)
   def installed_upgrade():
    for mode in ['1','0']:
     write(roots['fresh'],'config/version.php','<?php return '+literal(old)+';')
     path=roots['fresh']/'lib/myRouter.class.php';path.write_bytes(path.read_bytes()+b'\n// old package fixture\n')
     require(post('fresh','manager/check/version',tokens['fresh'])['version']==current,'Installed old fixture did not get release')
     result=post('fresh','manager/download?m='+mode,tokens['fresh']);require(not result.get('error'),result)
     if mode=='0':
      blob,_=request('fresh',result['link'].removeprefix('/fresh/'),binary=True);boundary='InstalledSystemUpdate'
      payload=(f'--{boundary}\r\nContent-Disposition: form-data; name="file"; filename="update.zip"\r\nContent-Type: application/zip\r\n\r\n').encode()+blob+f'\r\n--{boundary}--\r\n'.encode()
      body,_=request('fresh','manager/upload',method='POST',data=payload,token=tokens['fresh'],content_type='multipart/form-data; boundary='+boundary);require(json.loads(body).get('error')==0,body)
     require(hashes('fresh')==expected,'Installed system upgrade differs from local source')
     body,_=request('fresh','Admin/');require('运行概览' in body,'Installed upgraded Admin cannot boot')
   check('package-installed system completes online and offline upgrades with original login retained',installed_upgrade)
   def partial_setup_on_existing_site():
    request('fresh','console/init',method='POST',data={'setting[web][title]':'partial setup title'},token=tokens['fresh'],status=302)
    body,_=request('fresh','Admin/');require('运行概览' in body and '管理员登录' not in body,'Partial existing settings reset administrator defaults')
   check('partial settings on an existing installed site preserve administrator credentials',partial_setup_on_existing_site)


 except Exception as error:
  results.append({'case':'workflow','ok':False,'error':str(error)});print('FAIL '+str(error),flush=True)
 finally:
  for process in processes:
   with contextlib.suppress(ProcessLookupError):os.killpg(process.pid,signal.SIGTERM)
   process.wait(timeout=10)
  gateway.shutdown();gateway.server_close();log.seek(0);logtext=log.read();log.close()
  if any(not row['ok'] for row in results):
   print(logtext[-8000:])
   for name,root in roots.items():
    for file in root.rglob('error.log'):print(name,str(file.relative_to(root)),file.read_text()[-6000:])
 unchanged={name:(hashlib.sha256((source/name).read_bytes()).hexdigest() if(source/name).is_file() else None)==value for name,value in protected.items()}
 report={'release':current,'simulated_old':old,'published_files':expected,'http_requests':len(requests),'update_source_requests':len(remote_state['requests']),'passed':sum(row['ok'] for row in results),'failed':sum(not row['ok'] for row in results),'formal_files_unchanged':unchanged,'results':results,'requests':requests}
 print(json.dumps(report,ensure_ascii=False,indent=2))
 if os.environ.get('MYSTEP_RELEASE_REPORT'):Path(os.environ['MYSTEP_RELEASE_REPORT']).write_text(json.dumps(report,ensure_ascii=False,indent=2))
 require(all(unchanged.values()),'Formal source/configuration changed during isolated test')
 raise SystemExit(any(not row['ok'] for row in results))
