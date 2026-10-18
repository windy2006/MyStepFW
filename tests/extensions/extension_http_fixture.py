from pathlib import Path
import tempfile,subprocess,socket,time,os,json,urllib.request,urllib.error,xml.etree.ElementTree as ET

ROOT=Path(os.environ.get('MYSTEP_SOURCE',Path(__file__).resolve().parents[2])).resolve()
STAGE=Path(os.environ.get('MYSTEP_STAGE',ROOT)).resolve()
PHP=os.environ.get('MYSTEP_PHP','/Applications/ServBay/package/php/7.4/current/bin/php')
script=r'''<?php
spl_autoload_register(function($name){$file=getenv('MYSTEP_EXTENSION_LIB').'/'.$name.'.class.php';if(is_file($file))require_once $file;});
try {
 if(strpos($_SERVER['REQUEST_URI'],'/excel')===0){
  if(isset($_GET['gzip']))ob_start('ob_gzhandler');
  $xls=new myExcel("../中文\r\nX-Probe: injected", "A&B'表");$xls->addCells(['0012','中文😀',"line\nend"]);$xls->make($_GET['charset']??'UTF-8');
 }
 $api=new myApi();$api->regMethods(['data'=>function(){return ['text'=>'中文','null'=>null,'flag'=>false];},'bad'=>function(){return "\xff";}]);
 echo $api->run($_GET['method']??'data',[],$_GET['type']??'json',$_GET['charset']??'UTF-8');
} catch(Throwable $e){http_response_code(500);header('Content-Type: application/json; charset=UTF-8');echo '{"error":"encoding failed"}';}
'''
with tempfile.TemporaryDirectory(prefix='mystep-extension-http-') as scratch:
 path=Path(scratch);(path/'index.php').write_text(script)
 with socket.socket() as reservation:reservation.bind(('127.0.0.1',0));port=reservation.getsockname()[1]
 env=dict(os.environ,MYSTEP_EXTENSION_LIB=str(STAGE/'lib'))
 with (path/'server.log').open('w+') as log:
  process=subprocess.Popen([PHP,'-d','display_errors=0','-S',f'127.0.0.1:{port}',str(path/'index.php')],cwd=path,env=env,stdout=log,stderr=log)
  try:
   for attempt in range(100):
    try:
     with socket.create_connection(('127.0.0.1',port),timeout=.1):break
    except OSError:time.sleep(.03)
   else:raise RuntimeError('Fixture did not start')
   rows=[]
   def request(url,status=200,extra_headers=None):
    try:r=urllib.request.urlopen(urllib.request.Request(f'http://127.0.0.1:{port}'+url,headers=extra_headers or {}),timeout=10)
    except urllib.error.HTTPError as e:r=e
    with r:
     body=r.read();assert r.status==status,(url,r.status,body);rows.append({'url':url,'status':r.status,'bytes':len(body)})
     return body,r.headers
   body,headers=request('/api');assert headers['Content-Type']=='application/json; charset=UTF-8';assert json.loads(body)=={'text':'中文','null':None,'flag':False}
   body,headers=request('/api?type=xml&charset=GBK');assert 'application/xml' in headers['Content-Type'];assert ET.fromstring(body.decode('gbk')).find('text').text=='中文'
   body,headers=request('/api?method=missing');assert json.loads(body)==''
   body,headers=request('/api?method=bad',500);assert json.loads(body)=={'error':'encoding failed'}
   for charset in ['UTF-8','GBK']:
    body,headers=request('/excel?charset='+charset);assert headers['Content-Type']=='application/vnd.ms-excel; charset='+charset
    assert int(headers['Content-Length'])==len(body);assert headers['X-Probe'] is None
    assert 'filename*=UTF-8' in headers['Content-Disposition'];assert '\r' not in headers['Content-Disposition'];assert '\n' not in headers['Content-Disposition']
    tree=ET.fromstring(body.decode(charset.lower()));ns={'s':'urn:schemas-microsoft-com:office:spreadsheet'}
    sheet=tree.find('s:Worksheet',ns);assert sheet.attrib['{'+ns['s']+'}Name']=="A&B'表"
    assert [cell.text for cell in tree.findall('.//s:Data',ns)]==['0012','中文😀','line\nend']
   import gzip
   body,headers=request('/excel?gzip=1',extra_headers={'Accept-Encoding':'gzip'});assert headers['Content-Encoding']=='gzip';assert headers.get('Content-Length') is None
   xml=gzip.decompress(body);assert ET.fromstring(xml).tag.endswith('Workbook')
   log.flush();log.seek(0);messages=log.read();assert 'PHP Warning' not in messages and 'PHP Fatal' not in messages,messages
   print('Local API and Excel download: 7 scenarios passed; external requests: 0')
   if os.environ.get('MYSTEP_EXTENSION_HTTP_RESULTS'):Path(os.environ['MYSTEP_EXTENSION_HTTP_RESULTS']).write_text(json.dumps(rows,ensure_ascii=False,indent=2))
  finally:process.terminate();process.wait(timeout=10)
