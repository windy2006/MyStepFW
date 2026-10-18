from pathlib import Path
import tempfile, subprocess, socket, time, os, json, urllib.request, urllib.error
from http.cookies import SimpleCookie

ROOT=Path(os.environ.get('MYSTEP_SOURCE',Path(__file__).resolve().parents[2])).resolve()
STAGE=Path(os.environ.get('MYSTEP_STAGE',ROOT)).resolve()
PHP=os.environ.get('MYSTEP_PHP','/Applications/ServBay/package/php/7.4/current/bin/php')
script=r'''<?php
if(!in_array($_SERVER['REMOTE_ADDR'],['127.0.0.1','::1'],true)){http_response_code(404);exit;}
define('ROOT',getenv('MYSTEP_SESSION_SOURCE').'/');define('LIB',getenv('MYSTEP_SESSION_LIB').'/');define('VENDOR',ROOT.'vendor/');
spl_autoload_register(function($name){foreach([LIB.$name.'.class.php',LIB.'session/'.$name.'.class.php']as$file)if(is_file($file)){require_once$file;return;}});
require_once LIB.'function.php';
$_SERVER['HTTPS']='on';$GLOBALS['no_log']=false;
class FixtureFailure {
 public static function open($p,$n){return false;}public static function close(){return true;}
 public static function read($id){return false;}public static function write($id,$data){return false;}
 public static function destroy($id){return false;}public static function gc($ttl){return false;}
}
myReq::init(['path'=>'/private/','domain'=>'.fixture.test'],['path'=>__DIR__.'/sessions','name'=>'FIXTURE','expire'=>2,'gc'=>false]);
$action=$_GET['action']??'start';$GLOBALS['no_log']=$action==='skip';$id=@myReq::sessionStart($action==='fail'?'FixtureFailure':'sess_file');
header('Content-Type: application/json');
if($id===false){if($action==='skip'){echo json_encode(['active'=>myReq::sessionCheck()]);exit;}http_response_code(503);echo json_encode(['active'=>myReq::sessionCheck()]);exit;}
if($action==='set')$_SESSION['user']='admin';
if($action==='rotate')$id=myReq::sessionRestart();
$seen=$_SESSION;
if($action==='end')myReq::sessionEnd();else session_write_close();
echo json_encode(['id'=>$id,'seen'=>$seen,'active'=>myReq::sessionCheck()]);
'''
with tempfile.TemporaryDirectory(prefix='mystep-session-http-') as scratch:
 path=Path(scratch);(path/'router.php').write_text(script)
 with socket.socket() as reservation:reservation.bind(('127.0.0.1',0));port=reservation.getsockname()[1]
 env=dict(os.environ,MYSTEP_SESSION_SOURCE=str(ROOT),MYSTEP_SESSION_LIB=str(STAGE/'lib'))
 with (path/'server.log').open('w+') as log:
  process=subprocess.Popen([PHP,'-d','display_errors=0','-S',f'127.0.0.1:{port}',str(path/'router.php')],cwd=path,env=env,stdout=log,stderr=log)
  try:
   for _ in range(100):
    try:
     with socket.create_connection(('127.0.0.1',port),timeout=.1):break
    except OSError:time.sleep(.03)
   else:raise RuntimeError('Session fixture did not start')
   def request(action,cookie='',status=200):
    req=urllib.request.Request(f'http://127.0.0.1:{port}/private/?action='+action,headers={'Cookie':'FIXTURE='+cookie} if cookie else {})
    try:r=urllib.request.urlopen(req,timeout=10)
    except urllib.error.HTTPError as e:r=e
    with r:
     body=r.read();assert r.status==status,(r.status,body)
     return json.loads(body),r.headers
   a,headers=request('set');jar=SimpleCookie();jar.load(headers['Set-Cookie']);c=jar['FIXTURE'];assert c['path']=='/private/' and c['domain']=='.fixture.test' and c['secure'] and c['httponly'] and c['samesite']=='Lax';assert c.value==a['id']
   b,_=request('start',a['id']);assert b['seen']=={'user':'admin'} and b['id']==a['id'] and b['active'] is False
   c,_=request('rotate',a['id']);assert c['id']!=a['id'] and c['seen']=={'user':'admin'}
   d,_=request('start',a['id']);assert d['seen']==[] and d['id']!=a['id']
   e,headers=request('end',c['id']);jar=SimpleCookie();jar.load(headers.get_all('Set-Cookie')[-1]);dead=jar['FIXTURE'];assert dead.value in ('','deleted') and dead['path']=='/private/' and dead['domain']=='.fixture.test' and dead['secure'] and dead['httponly'] and dead['samesite']=='Lax' and dead['expires'] and dead['max-age']=='0', repr(headers.get_all('Set-Cookie'));assert e['active'] is False
   f,_=request('start',c['id']);assert f['seen']==[] and f['id']!=c['id']
   skipped,skip_headers=request('skip',c['id']);assert skipped=={'active':False} and skip_headers.get_all('Set-Cookie') is None
   g,_=request('fail',status=503);assert g=={'active':False}
   log.flush();log.seek(0);messages=log.read();assert 'PHP Warning' not in messages and 'PHP Fatal' not in messages,messages
   print('Local session lifecycle and Cookie headers: 8 scenarios passed; external requests: 0')
  finally:process.terminate();process.wait(timeout=10)
