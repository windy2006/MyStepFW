"""Local-only SMTP and multipart-upload integration for PHP 7.4. No external mail."""
import os,json,socket,socketserver,subprocess,tempfile,threading,time,urllib.request
from pathlib import Path
from email import policy
from email.parser import BytesParser
ROOT=Path(__file__).resolve().parents[2]
PHP=os.environ.get('MYSTEP_PHP','/Applications/ServBay/package/php/7.4/current/bin/php')
BOOT='''<?php
$source=$argv[1] ?? getenv('MYSTEP_SOURCE');
define('ROOT',$source.'/');define('LIB',ROOT.'lib/');define('VENDOR',ROOT.'vendor/');
spl_autoload_register(function($class){$file=LIB.$class.'.class.php';if(is_file($file))require_once $file;});
require_once VENDOR.'autoload.php';require_once LIB.'function.php';
set_error_handler(function($n,$m,$f,$l){if(error_reporting()&$n)throw new ErrorException($m,0,$n,$f,$l);});
'''
messages=[]
class SMTPHandler(socketserver.StreamRequestHandler):
 def handle(self):
  self.request.settimeout(10);self.wfile.write(b'220 localhost fixture\r\n');sender='';recipients=[]
  while True:
   line=self.rfile.readline()
   if not line:return
   command=line.decode().strip();verb=command.split(' ',1)[0].upper()
   if verb in ('EHLO','HELO'):self.wfile.write(b'250 localhost\r\n')
   elif verb=='MAIL':sender=command;self.wfile.write(b'250 OK\r\n')
   elif verb=='RCPT':recipients.append(command);self.wfile.write(b'250 OK\r\n')
   elif verb=='DATA':
    self.wfile.write(b'354 End with dot\r\n');body=[]
    while True:
     part=self.rfile.readline()
     if part in (b'.\r\n',b'.\n',b''):break
     body.append(part[1:] if part.startswith(b'..') else part)
    messages.append((sender,recipients[:],b''.join(body)));self.wfile.write(b'250 queued locally\r\n')
   elif verb=='RSET':sender='';recipients=[];self.wfile.write(b'250 OK\r\n')
   elif verb=='QUIT':self.wfile.write(b'221 bye\r\n');return
   else:self.wfile.write(b'500 unsupported\r\n')
class Server(socketserver.ThreadingTCPServer):allow_reuse_address=True;daemon_threads=True
checks=0
with tempfile.TemporaryDirectory(prefix='mystep-io-') as folder:
 directory=Path(folder).resolve();(directory/'attachment.txt').write_bytes(b'EXAMPLE\0binary')
 mail=directory/'mail.php';mail.write_text(BOOT+'''
$_SERVER['DOCUMENT_ROOT']='/';$_SERVER['SERVER_NAME']='localhost';
$m=new myEmail('sender+tag@example.technology');$m->to('to@example.test');$m->cc('copy@example.test');$m->bcc('hidden@example.test');
$m->setSubject('Fixture');$m->setContent('<p>Hello</p>',true,'Plain alternate');
$m->addFile(__DIR__.'/attachment.txt','attachment.txt',false,'text/plain');
echo json_encode($m->send(['host'=>'127.0.0.1','port'=>(int)$argv[2],'mode'=>'smtp'],(bool)$argv[3]));
''')
 with Server(('127.0.0.1',0),SMTPHandler) as server:
  thread=threading.Thread(target=server.serve_forever,daemon=True);thread.start()
  try:
   for single,count in [(0,1),(1,3)]:
    messages.clear()
    run=subprocess.run([PHP,str(mail),str(ROOT),str(server.server_address[1]),str(single)],capture_output=True,text=True,timeout=20)
    assert run.returncode==0 and not run.stderr,(run.stdout,run.stderr)
    result=json.loads(run.stdout);assert len(result)==3 and all('successfully' in item for item in result),result
    assert len(messages)==count,len(messages)
    for sender,recipients,raw in messages:
     assert 'sender+tag@example.technology' in sender,sender
     message=BytesParser(policy=policy.default).parsebytes(raw)
     assert message['Bcc'] is None
     assert not any(part.defects for part in message.walk()),[(p.get_content_type(),p.defects) for p in message.walk()]
     attachments=list(message.iter_attachments()) if not message.is_multipart() else [p for p in message.walk() if p.get_content_disposition()=='attachment']
     assert len(attachments)==1 and attachments[0].get_payload(decode=True)==b'EXAMPLE\0binary'
     assert len(recipients)==(1 if single else 3)
    checks+=1
  finally:server.shutdown();thread.join()
 # Run an ephemeral PHP development server only on loopback.
 handler=directory/'index.php';handler.write_text(BOOT.replace("$source=$argv[1] ?? getenv('MYSTEP_SOURCE');","$source=getenv('MYSTEP_SOURCE');")+'''
$u=new myUploader(__DIR__.'/uploads',isset($_GET['rename']));
if(isset($_GET['limits']))$u->setLimits(8,['txt','png']);
$u->do(false);
if(isset($_GET['html']))echo $u->result(true);else{header('Content-Type: application/json');echo json_encode($u->result());}
''')
 sock=socket.socket();sock.bind(('127.0.0.1',0));port=sock.getsockname()[1];sock.close()
 log=(directory/'server.log').open('w+')
 process=subprocess.Popen([PHP,'-S',f'127.0.0.1:{port}','-t',str(directory)],env=dict(os.environ,MYSTEP_SOURCE=str(ROOT)),stdout=log,stderr=log)
 def upload(filename,content,field='file',html=False,query=''):
  boundary='mystep-fixture-boundary'
  payload=(f'--{boundary}\r\nContent-Disposition: form-data; name="{field}"; filename="{filename}"\r\nContent-Type: application/octet-stream\r\n\r\n').encode()+content+f'\r\n--{boundary}--\r\n'.encode()
  request=urllib.request.Request(f'http://127.0.0.1:{port}/'+('?'+('html=1&' if html else '')+query),data=payload,headers={'Content-Type':f'multipart/form-data; boundary={boundary}'})
  with urllib.request.urlopen(request,timeout=5) as response:return response.read().decode()
 try:
  for _ in range(100):
   if process.poll() is not None:raise RuntimeError('PHP fixture stopped')
   try:
    with socket.create_connection(('127.0.0.1',port),timeout=.1):break
   except OSError:time.sleep(.03)
  result=json.loads(upload('safe.txt',b'EXAMPLE'));assert result[0]['error']==0,result
  assert (directory/'uploads/safe.txt').read_bytes()==b'EXAMPLE';checks+=1
  result=json.loads(upload('code.PHTML',b'example'));assert result[0]['new_name']=='code.PHTML.upload' and result[0]['error']==0,result;checks+=1
  result=json.loads(upload('nested.txt',b'nested','files[group][]'));assert result[0]['error']==0 and (directory/'uploads/nested.txt').read_bytes()==b'nested';checks+=1
  result=json.loads(upload('empty.txt',b''));assert result[0]['error']==9,result;checks+=1
  body=upload('evil<name>.txt',b'text',html=True);assert 'evil&lt;name&gt;.txt' in body and '>evil<name>.txt<' not in body;checks+=1
  result=json.loads(upload('large.txt',b'123456789',query='limits=1'));assert result[0]['error']!=0 and not (directory/'uploads/large.txt').exists();checks+=1
  result=json.loads(upload('blocked.pdf',b'data',query='limits=1'));assert result[0]['error']!=0 and not (directory/'uploads/blocked.pdf').exists();checks+=1
  result=json.loads(upload('fake.png',b'bad',query='limits=1'));assert result[0]['error']!=0 and not (directory/'uploads/fake.png').exists();checks+=1
  result=json.loads(upload('renamed.txt',b'data',query='rename=1'));name=result[0]['new_name'];assert result[0]['error']==0 and len(name.split('.'))==3 and name.split('.')[0].isdigit() and (directory/'uploads'/name).read_bytes()==b'data';checks+=1
 finally:
  process.terminate();process.wait(timeout=5);log.close()
print('Local SMTP/MIME and real HTTP uploads:',checks,'scenarios passed; external messages: 0')
