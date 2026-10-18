"""真实新后台HTTP回归；仅临时站点、测试账号/SQLite，不对正式站点提交表单。"""
from pathlib import Path
import tempfile,subprocess,socket,time,urllib.request,urllib.error,http.cookiejar,urllib.parse,json,os,re,shutil,gzip,zlib
source=Path(os.environ.get('MYSTEP_ADMIN_SOURCE',str(Path(__file__).resolve().parents[2])))
stage=Path(os.environ.get('MYSTEP_ADMIN_STAGE',str(source)))
php=os.environ.get('MYSTEP_PHP','/Applications/ServBay/package/php/7.4/current/bin/php');results=[]
with tempfile.TemporaryDirectory(prefix='mystep-admin-http-') as scratch:
 outer=Path(scratch);root=outer/'sub';root.mkdir();(root/'lib').symlink_to(source/'lib',target_is_directory=True)
 for d in ['app','config/priority','cache','files','plugin']:(root/d).mkdir(parents=True,exist_ok=True)
 shutil.copytree(stage/'app/Admin',root/'app/Admin');shutil.copytree(source/'app/myStep',root/'app/myStep');shutil.copytree(source/'config/construction',root/'config/construction');shutil.copyfile(source/'config/class_alias.php',root/'config/class_alias.php');shutil.copyfile(source/'plugin/interface_plugin.class.php',root/'plugin/interface_plugin.class.php');shutil.copyfile(source/'readme.md',root/'readme.md');shutil.copytree(source/'static',root/'static')
 shutil.copytree(source/'docs',root/'docs')
 for name in ['app/README.md','plugin/README.md','tests/README.md']:
  target=root/name;target.parent.mkdir(parents=True,exist_ok=True);shutil.copyfile(source/name,target)
 def literal(s):return "'"+str(s).replace('\\','\\\\').replace("'","\\'")+"'"
 (root/'config/config.php').write_text('<?php include '+literal(source/'config/config_default.php')+''';$setting['gen']['s_usr']='fixture';$setting['gen']['s_pwd']=FIXTURE_PASSWORD;$setting['db']['auto']=false;$setting['db']['type']='MySQL';$setting['db']['host']='127.0.0.1';$setting['db']['name']='fixture';$setting['router']['mode']='rewrite';$setting['cookie']['prefix']='fixture_';return $setting;'''.replace('FIXTURE_PASSWORD',literal(subprocess.check_output([php,'-r','echo password_hash("fixture-pass",PASSWORD_DEFAULT);'],text=True))))
 config_admin=(root/'app/Admin/config.php').read_text().replace("'db'=>['auto'=>false]","'db'=>['auto'=>false,'type'=>'SQLite','host'=>ROOT.'fixture.db','name'=>'main']")
 (root/'app/Admin/config.php').write_text(config_admin)
 (root/'app/myStep/config.php').write_text('<?php return ["gen"=>["cache_page"=>false,"path_admin"=>"console"],"db"=>["auto"=>false]];')
 (root/'config/class.php').write_text('<?php return [["path"=>"lib/","ext"=>".class.php,.php","idx"=>["interface_plugin"=>"../plugin/interface_plugin.class.php"]],["path"=>"lib/database/","ext"=>".class.php,.php"]];')
 (root/'plugin/fixture').mkdir()
 (root/'plugin/fixture/info.php').write_text('<?php return ["name"=>"Fixture plugin","idx"=>"fixture","ver"=>"1","intro"=>"temporary"];')
 (root/'plugin/fixture/class.php').write_text('<?php class plugin_fixture implements interface_plugin {public static function check(&$r){$r="ok";return true;}public static function install(){file_put_contents(CONFIG."installed-marker","installed");}public static function uninstall(){unlink(CONFIG."installed-marker");}}')
 (root/'fixture.php').write_text('''<?php function fixtureRotateCsrf(){$_SESSION['_ms_csrf']=bin2hex(random_bytes(32));echo "rotated";} function fixtureCaptcha(){$_SESSION['captcha']='ABCD';header('Content-Type: application/json');echo json_encode(['token'=>mySecurity::token()]);exit;}''')
 route=(root/'app/myStep/route.php').read_text();route=route.replace('$rule = array(',"$rule = array(array('/_fixture_rotate','fixtureRotateCsrf'),array('/_fixture_captcha','fixtureCaptcha'),",1);(root/'app/myStep/route.php').write_text(route)
 script='''<?php $_SERVER['DOCUMENT_ROOT']=dirname(__DIR__);$_SERVER['SCRIPT_NAME']='/sub/index.php';$_SERVER['SCRIPT_FILENAME']=__FILE__;
define('ROOT',__DIR__.'/');define('LIB',ROOT.'lib/');define('VENDOR',SOURCE.'/vendor/');foreach(['APP'=>'app/','CONFIG'=>'config/','PLUGIN'=>'plugin/','CACHE'=>'cache/','FILE'=>'files/','STATICS'=>'static/'] as $k=>$v)define($k,ROOT.$v);
spl_autoload_register(function($name){foreach([STAGE.'/lib/'.$name.'.class.php',STAGE.'/lib/database/'.$name.'.class.php',LIB.$name.'.class.php',LIB.'database/'.$name.'.class.php'] as $file)if(is_file($file)){require_once $file;return;}});
require ROOT.'fixture.php';require LIB.'myStep.class.php';myStep::init();
'''
 (root/'index.php').write_text(script.replace('SOURCE',literal(source)).replace('STAGE',literal(stage)))
 subprocess.run([php,'-r',"$db=new SQLite3($argv[1]);$db->exec('CREATE TABLE example(id INTEGER,title TEXT)');$db->exec(\"INSERT INTO example VALUES(1,'row')\");",str(root/'fixture.db')],check=True)
 sock=socket.socket();sock.bind(('127.0.0.1',0));port=sock.getsockname()[1];sock.close()
 class NoRedirect(urllib.request.HTTPRedirectHandler):
  def redirect_request(self,*args,**kwargs):return None
 public_client=urllib.request.build_opener(NoRedirect())
 jar=http.cookiejar.CookieJar();client=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar),NoRedirect())
 with tempfile.TemporaryFile(mode='w+') as log:
  proc=subprocess.Popen([php,'-S',f'127.0.0.1:{port}','-t',str(outer),str(root/'index.php')],stdout=log,stderr=log)
  def request(path,status=200,data=None,headers=None,binary=False,anonymous=False):
   payload=data if isinstance(data,bytes) else (None if data is None else urllib.parse.urlencode(data,doseq=True).encode())
   req=urllib.request.Request(f'http://127.0.0.1:{port}/sub/'+path,data=payload,headers=headers or {})
   try:r=(public_client if anonymous else client).open(req,timeout=15)
   except urllib.error.HTTPError as e:r=e
   with r:body=r.read();results.append({'path':path,'method':req.get_method(),'status':r.status});assert r.status==status,(results[-1],re.sub(b'<[^>]+>',b' ',re.sub(b'<(?:script|style)[^>]*>.*?</(?:script|style)>',b'',body,flags=re.S))[-1400:]);return (body if binary else body.decode('utf-8','replace')),r.headers
  def token(body):return re.search(r'name="_ms_csrf" value="([^"]+)"',body).group(1)
  def version(body):return re.search(r'name="version" value="([^"]+)"',body).group(1)
  try:
   for _ in range(80):
    try:
     with socket.create_connection(('127.0.0.1',port),timeout=.1):break
    except OSError:time.sleep(.05)
   for endpoint,status in [('error/404',404),('ms_setting/Admin',200),('ms_language/Admin/chs',200)]:
    _,h=request(endpoint,status,anonymous=True);assert not h.get('Set-Cookie'),endpoint
   _,h=request('captcha/',binary=True,anonymous=True);assert h.get('Set-Cookie')
   body,h=request('Admin/');assert '管理员登录' in body and '用户名' not in body;csrf=token(body)
   body,h=request('Admin/function/plugin');assert '管理员登录' in body and '插件清单' not in body
   body,h=request('Admin/error');assert '管理员登录' in body
   request('Admin/error',403,{'_action':'download'})
   request('Admin/',403,{'username':'fixture','password':'fixture-pass','captcha':'ABCD'})
   request('_fixture_captcha');body,h=request('Admin/',303,{'username':'fixture','password':'fixture-pass','captcha':'ABCD','_ms_csrf':csrf});assert '/Admin/' in h['Location']
   body,h=request('Admin/');assert '运行概览' in body and '管理员登录' not in body;csrf=token(body)
   pages=['db','db?table=example','php','phpinfo','error','setting','setting?target=myStep','setting?target=Admin','setting/class','setting/domain','setting/alias','setting/detail?class=myStep','setting/execute','function/app','function/cache','function/plugin','function/attachment','function/language','guide','samples','samples?sample=form','references']
   for page in pages:
    body,h=request('Admin/'+page);assert '操作未完成' not in body and '管理员登录' not in body, page
    if page=='phpinfo':
     assert 'id="adm-phpinfo"' in body and 'class="e"' in body and 'class="v"' in body
     assert '<style type="text/css">' not in body # 原生全局选择器不得泄漏到后台布局
     css_files=list((root/'cache/script').glob('Admin_*.css'));assert css_files
     css='\n'.join(p.read_text() for p in css_files)
     for selector in ['#adm-phpinfo .e','#adm-phpinfo .h','#adm-phpinfo .v','#adm-phpinfo td,#adm-phpinfo th']:
      assert selector in css,selector
   def verify_guides(prefix):
    separator='&' if '?' in prefix else '?'
    body,h=request(prefix+'guide');assert '文档总览' in body and '应用与插件开发' in body and '应用、插件与站点资料' in body
    assert body.count('aria-current="page"')>=1
    for doc,heading in [('execution','框架执行流程'),('app-rules','应用编写规范'),('plugin-rules','插件编写规范'),('first-app','开发第一个应用'),('first-plugin','开发第一个插件'),('frontend-forms','表单校验与need规则'),('tests','测试目录与运行说明')]:
     body,h=request(prefix+'guide'+separator+'doc='+doc);assert '<div class="guide-document">' in body and '<h2>'+heading+'</h2>' in body,(prefix,doc)
     assert 'href="'+('/sub/'+prefix+'guide'+separator+'doc=') in body.replace('&amp;','&'),(prefix,doc)
     assert 'class="guide-outline"' in body and 'id="' in body
    body,h=request(prefix+'guide'+separator+'doc=frontend');assert 'doc=frontend-forms' in body and 'static/docs/forms.md' not in re.findall(r'href="([^"]+)"',body)
    for invalid in ['../config/config.php','config/config.php','unknown','/etc/passwd']:
     body,h=request(prefix+'guide'+separator+'doc='+urllib.parse.quote(invalid),404);assert '文档不存在' in body and 'fixture-pass' not in body
    body,h=request(prefix+'guide'+separator+'doc%5B%5D=execution',404);assert '文档不存在' in body
   verify_guides('Admin/');verify_guides('console/')
   def verify_images(prefix,legacy=False):
    for sample in ['image','image_load']:
     suffix=('samples/'+sample) if legacy else ('samples'+('&' if '?' in prefix else '?')+'sample='+sample)
     body,h=request(prefix+suffix)
     assert 'desk-fd.zol-img' not in body and 'cccfna.org' not in body
     images=re.findall(r'(?:src|data-src)="([^"<>]*asset/samples/[^"<>]+\.jpg)"',body)
     assert len(images)==(24 if sample=='image' else 6),(prefix,sample,images)
     assert all(url.startswith('/sub/') and not url.startswith('/sub/sub/') for url in images)
     if sample=='image_load':
      assert 'loading_img.gif' in body
      data,h=request(images[0][len('/sub/'):],binary=True,anonymous=True)
      assert data.startswith(b'\xff\xd8') and h.get_content_type()=='image/jpeg'
   verify_images('Admin/');verify_images('console/',True)
   for photo in sorted((root/'app/myStep/asset/samples').glob('*.jpg')):
    data,h=request('myStep/asset/samples/'+photo.name,binary=True,anonymous=True)
    assert data==photo.read_bytes() and h.get_content_type()=='image/jpeg'
   data,h=request('static/images/loading_img.gif',binary=True,anonymous=True);assert data.startswith(b'GIF')
   # 默认应用入口与显式路由共用同一个Admin核心和受保护模块分发。
   source_route=(root/'app/Admin/route.php').read_text()
   try:
    (root/'app/Admin/route.php').write_text('<?php $rule=[];')
    body,h=request('Admin/setting');assert '选择配置' in body and '管理员登录' not in body and body.count('<h1>参数设置</h1>')==1
   finally:(root/'app/Admin/route.php').write_text(source_route)
   # 新旧类编辑器往返同一PHP数组配置；所有路径以临时网站根解析。
   body,h=request('Admin/setting/class');assert '所在目录' in body and '特别指定' in body and 'JSON对象' not in body
   class_data={'_action':'save','_ms_csrf':csrf,'version':version(body),'rows[0][path]':'lib/','rows[0][ext]':'.php,.class.php','rows[0][idx]':"array('interface_plugin'=>'../plugin/interface_plugin.class.php')",'rows[1][path]':'lib/database/','rows[1][ext]':'.php,.class.php','rows[1][idx]':'array ()'}
   request('Admin/setting/class',303,class_data);saved_class=(root/'config/class.php').read_text();assert str(root) not in saved_class and "'path' => 'lib/'" in saved_class
   body,h=request('console/setting/class');assert 'value="lib/"' in body
   body,h=request('console/setting/class',data={'_ms_csrf':token(body),'path[]':['lib/','lib/database/'],'ext[]':['.php,.class.php','.php,.class.php'],'idx[]':["array('interface_plugin'=>'../plugin/interface_plugin.class.php')",'array ()']});assert 'value="lib/"' in body
   body,h=request('console/setting/alias/1');assert 'sqlite' in body.lower()
   body,h=request('Admin/setting/alias?directory=1');assert 'aliases[sqlite]' in body and 'aliases[myFile]' not in body
   # 会话必须在日志页保持可用；写操作继续检查CSRF，内容仅在临时站点创建。
   fixture_log=root/'error.log';fixture_log.write_text('fixture-error-log')
   body,h=request('Admin/error');assert 'fixture-error-log' in body and '管理员登录' not in body
   body,h=request('Admin/error?download=1',anonymous=True);assert '管理员登录' in body and not h.get('Content-Disposition')
   data,h=request('Admin/error?download=1',binary=True);assert data==b'fixture-error-log' and h.get('Content-Disposition')
   body,h=request('Admin/error?_action=clear');assert fixture_log.read_text()=='fixture-error-log'
   data,h=request('Admin/error',data={'_action':'download','_ms_csrf':csrf},binary=True);assert data==b'fixture-error-log' and h.get('Content-Disposition')
   request('Admin/error',403,{'_action':'clear'});assert fixture_log.read_text()=='fixture-error-log'
   request('Admin/error',303,{'_action':'clear','_ms_csrf':csrf});assert not fixture_log.exists()
   body,h=request('Admin/');assert '运行概览' in body and '管理员登录' not in body
   body,h=request('Admin/function/plugin/view?idx=fixture');assert '条件检查' in body
   rv=re.search(r'name="registry_version" value="([^"]+)"',body).group(1)
   request('Admin/function/plugin/view?idx=fixture',303,{'_action':'install','_ms_csrf':csrf,'idx':'fixture','version':'missing','registry_version':rv,'apps[]':'Admin'})
   assert (root/'config/installed-marker').read_text()=='installed'
   body,h=request('Admin/function/plugin');assert '>停用</button>' in body and '保存运行顺序' in body
   request('Admin/function/plugin',303,{'_action':'toggle','_ms_csrf':csrf,'idx':'fixture'})
   body,h=request('Admin/function/plugin');assert '>启用</button>' in body and '启用 / 停用' not in body
   request('Admin/function/plugin',303,{'_action':'order','_ms_csrf':csrf,'order[fixture]':'9'})
   body,h=request('Admin/function/plugin/setting?idx=fixture');assert '保存插件设置' in body
   data,h=request('Admin/function/plugin',data={'_action':'pack','_ms_csrf':csrf,'idx':'fixture'},binary=True);assert b'plugin_fixture' in zlib.decompress(data)
   request('Admin/function/plugin',303,{'_action':'uninstall','_ms_csrf':csrf,'idx':'fixture'});assert not (root/'config/installed-marker').exists()
   request('Admin/function/plugin',303,{'_action':'delete','_ms_csrf':csrf,'idx':'fixture'});assert not (root/'plugin/fixture').exists()
   # 真正的multipart上传，解包到临时站点，随后检查发布结果。
   package_source=outer/'package-source';package_source.mkdir();(package_source/'info.php').write_text('<?php return ["app"=>"Uploaded","name"=>"Uploaded"];');(package_source/'route.php').write_text('<?php $rule=[];')
   package=outer/'app_Uploaded.app'
   pack_code='spl_autoload_register(function($c)use($argv){$f=$argv[1]."/lib/".$c.".class.php";if(is_file($f))require_once $f;});$_SERVER["DOCUMENT_ROOT"]="/";$p=new myPacker($argv[2],$argv[3]);if(!$p->pack())exit(1);'
   subprocess.run([php,'-r',pack_code,str(source),str(package_source),str(package)],check=True)
   def upload(filename,contents,status):
    boundary='----MyStepFixtureBoundary';parts=[]
    for key,value in {'_action':'upload','_ms_csrf':csrf}.items():parts.append(('--'+boundary+'\r\nContent-Disposition: form-data; name="'+key+'"\r\n\r\n'+value+'\r\n').encode())
    parts.append(('--'+boundary+'\r\nContent-Disposition: form-data; name="package"; filename="'+filename+'"\r\nContent-Type: application/octet-stream\r\n\r\n').encode()+contents+b'\r\n');parts.append(('--'+boundary+'--\r\n').encode())
    return request('Admin/function/app',status,b''.join(parts),{'Content-Type':'multipart/form-data; boundary='+boundary})
   upload('app_Uploaded.app',package.read_bytes(),303);assert (root/'app/Uploaded/info.php').is_file()
   upload('app_Wrong.plugin',package.read_bytes(),422);assert not (root/'app/Wrong').exists()
   upload('app_Bad.app',b'not-a-package',422);assert not (root/'app/Bad').exists()
   request('Admin/samples?sample[]=bad',422)
   request('Admin/not-a-page',404)
   body,h=request('Admin/setting');v=version(body)
   request('Admin/setting',303,{'_action':'save','_ms_csrf':csrf,'target':'','version':v,'setting[web][title]':'Fixture Changed'})
   request('Admin/setting',409,{'_action':'save','_ms_csrf':csrf,'target':'','version':v,'setting[web][title]':'Stale Changed'})
   body,h=request('Admin/setting');assert 'Fixture Changed' in body
   body,h=request('Admin/setting/domain');request('Admin/setting/domain',303,{'_action':'save','_ms_csrf':csrf,'version':version(body),'rows[0][domain]':'admin.fixture','rows[0][rule]':'Admin'})
   body,h=request('Admin/setting/domain');assert 'admin.fixture' in body
   request('Admin/function/cache',422,{'_action':'unknown','_ms_csrf':csrf})
   request('Admin/function/cache',422,{'_action':'clear','_ms_csrf':csrf,'areas[]':'../config'})
   body,h=request('Admin/function/cache');assert 'value="all"' in body and '全部清理' in body
   request('Admin/function/cache',403,{'_action':'clear','areas[]':'all'})
   request('Admin/function/cache',422,{'_action':'clear','_ms_csrf':csrf,'areas[]':['all','../config']})
   request('Admin/setting/execute',303,{'_action':'run','_ms_csrf':csrf,'code':'<?php echo "ONLY-FIXTURE";'})
   body,h=request('Admin/setting/execute');assert 'ONLY-FIXTURE' in body
   request('Admin/logout',405)
   request('Admin/logout',303,{'_action':'logout','_ms_csrf':csrf})
   body,h=request('Admin/');assert '管理员登录' in body
   # 旧后台使用真实模块和模板；三种路由下验证新POST退出及旧URL兼容。
   legacy_config=(root/'config/config.php').read_text()
   for mode,admin_url,old_logout in [('rewrite','Admin/','console/login?out&from=legacy'),('query_string','index.php?/Admin/','index.php?/console/login&out&from=legacy'),('path_info','index.php/Admin/','index.php/console/login?out&from=legacy')]:
    (root/'config/config.php').write_text(legacy_config.replace("'rewrite'",literal(mode)))
    for legacy in [False,True]:
     body,h=request(admin_url);login_token=token(body);request('_fixture_captcha')
     request(admin_url,303,{'username':'fixture','password':'fixture-pass','captcha':'ABCD','_ms_csrf':login_token,'expire':'7'})
     assert any(c.name.endswith('ms_auth') for c in jar)
     body,h=request('console/');assert 'name="username"' not in body
     form=re.search(r'<form[^>]*data-ms-logout[^>]*>',body).group(0)
     action=re.search(r'action="([^"]+)"',form).group(1).replace('&amp;','&');endpoint=re.search(r'data-ms-csrf="([^"]+)"',form).group(1).replace('&amp;','&')
     assert action.startswith('/sub/') and not action.startswith('/sub/sub/')
     # 模拟页面停留期间会话令牌变化；失效令牌不应退出，新表单先读取当前令牌。
     stale=token(body);request('_fixture_rotate')
     target=old_logout if legacy else action[len('/sub/'):]
     request(target,403,{'out':'1','_ms_csrf':stale})
     body,h=request(admin_url);assert '管理员登录' not in body
     request(old_logout,405)
     body,h=request(endpoint[len('/sub/'):]);current=json.loads(body)['token'];assert current!=stale
     body,h=request(target,data={'out':'1','_ms_csrf':current});assert '成功退出' in body or 'Logout Successfully' in body
     assert not any(c.name.endswith('ms_auth') for c in jar)
     body,h=request(admin_url);assert '管理员登录' in body
   (root/'config/config.php').write_text(legacy_config)
   # 路由模式变更只写临时配置，验证登录页面和隐藏表单action。
   config=(root/'config/config.php').read_text()
   for mode,url in [('query_string','index.php?/Admin/'),('path_info','index.php/Admin/')]:
    (root/'config/config.php').write_text(config.replace("'rewrite'",literal(mode)))
    body,h=request(url);assert '管理员登录' in body;assert ('index.php?/Admin/' if mode=='query_string' else 'index.php/Admin/') in body
    login_token=token(body);request('_fixture_captcha')
    request(url,303,{'username':'fixture','password':'fixture-pass','captcha':'ABCD','_ms_csrf':login_token})
    body,h=request(url+'error');assert '错误日志' in body and '管理员登录' not in body
    assert len(re.findall(r'<button[^>]*\bdisabled\b[^>]*>',body))==2
    fixture_log.write_text('fixture-download-'+mode)
    body,h=request(url+'error');assert 'disabled>下载完整日志' not in body
    download=re.search(r'<a[^>]+href="([^"]+)"[^>]*>下载完整日志</a>',body).group(1).replace('&amp;','&')
    data,h=request(download[len('/sub/'):],binary=True);assert data==('fixture-download-'+mode).encode() and h.get('Content-Disposition')
    request(url+'error',303,{'_action':'clear','_ms_csrf':token(body)});assert not fixture_log.exists()
    verify_images(url);verify_images(url.replace('Admin/','console/'),True)
    verify_guides(url);verify_guides(url.replace('Admin/','console/'))
    request(url+'logout',303,{'_action':'logout','_ms_csrf':token(body)})
   # 绑定Admin的域名省略应用前缀后，/error仍属于后台，/error/404仍走公共状态码规则。
   (root/'config/config.php').write_text(config)
   (root/'config/domain.php').write_text('<?php return ["127.0.0.1"=>"Admin"];')
   body,h=request('');assert '管理员登录' in body;login_token=token(body);request('_fixture_captcha')
   request('',303,{'username':'fixture','password':'fixture-pass','captcha':'ABCD','_ms_csrf':login_token})
   body,h=request('error');assert '错误日志' in body and '管理员登录' not in body
   _,h=request('error/404',404,anonymous=True);assert not h.get('Set-Cookie')
   # 全部清理只在临时站点执行：清理七类而保留管理目录，确认HTTP动作可完成。
   for area in ['script','template','language','session','tmp','app']:
    (root/'cache'/area).mkdir(exist_ok=True);(root/'cache'/area/'all-http-marker').write_text('cache')
   (root/'cache/admin').mkdir(exist_ok=True);(root/'cache/admin/all-http-audit').write_text('keep')
   body,h=request('function/cache');clear_token=token(body)
   request('function/cache',303,{'_action':'clear','_ms_csrf':clear_token,'areas[]':'all'})
   assert all(not (root/'cache'/area/'all-http-marker').exists() for area in ['script','template','language','session','tmp','app'])
   assert (root/'cache/admin/all-http-audit').read_text()=='keep'
   body,h=request('')
   if '管理员登录' in body:
    login_token=token(body);request('_fixture_captcha');request('',303,{'username':'fixture','password':'fixture-pass','captcha':'ABCD','_ms_csrf':login_token});body,h=request('')
   request('logout',303,{'_action':'logout','_ms_csrf':token(body)})
   proc.terminate();proc.wait(timeout=10);log.seek(0);output=log.read();assert 'PHP Warning' not in output and 'PHP Fatal' not in output,output
   assert not (root/'error.log').exists() or (root/'error.log').stat().st_size==0,(root/'error.log').read_text()
   print(f'Admin HTTP fixture: {len(results)} scenarios passed; business writes: 0')
   if os.environ.get('MYSTEP_ADMIN_HTTP_RESULTS'):Path(os.environ['MYSTEP_ADMIN_HTTP_RESULTS']).write_text(json.dumps(results,ensure_ascii=False,indent=2))
  except Exception:
   log.seek(0);print(log.read());print((root/"error.log").read_text() if (root/"error.log").exists() else "No framework log");raise
  finally:
   if proc.poll() is None:proc.terminate();proc.wait(timeout=10)
