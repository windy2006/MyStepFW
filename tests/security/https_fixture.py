from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
import tempfile, subprocess, http.server, ssl, threading, json, os, time, urllib.parse

with tempfile.TemporaryDirectory(prefix='mystep-tls-') as scratch:
    path=Path(scratch)
    # This isolated CA is only trusted for the duration of the test process.
    (path/'extensions.cnf').write_text('subjectAltName=DNS:localhost\nextendedKeyUsage=serverAuth\n')
    def openssl(*args):
        subprocess.run([os.environ.get('MYSTEP_OPENSSL', '/Applications/ServBay/bin/openssl' if Path('/Applications/ServBay/bin/openssl').exists() else 'openssl'),*args],cwd=path,check=True,stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
    openssl('req','-x509','-newkey','rsa:2048','-nodes','-keyout','ca.key','-out','ca.crt','-days','1','-subj','/CN=MyStep Test CA')
    openssl('req','-new','-newkey','rsa:2048','-nodes','-keyout','server.key','-out','server.csr','-subj','/CN=localhost')
    openssl('x509','-req','-in','server.csr','-CA','ca.crt','-CAkey','ca.key','-CAcreateserial','-out','server.crt','-days','1','-extfile','extensions.cnf')
    class Handler(http.server.BaseHTTPRequestHandler):
        protocol_version='HTTP/1.1'
        def log_message(self,*args):pass
        def do_HEAD(self):self.send_response(200);self.send_header('Content-Length','0');self.end_headers()
        def do_GET(self):self.respond()
        def do_POST(self):self.respond()
        def respond(self):
            if self.path.startswith('/redirect'):
                self.send_response(302);self.send_header('Location','http://127.0.0.1:1/');self.send_header('Content-Length','0');self.end_headers();return
            if self.path.startswith('/slow'):time.sleep(2.2)
            if self.path.startswith('/chunked'):
                self.send_response(200);self.send_header('Transfer-Encoding','chunked');self.end_headers()
                self.wfile.write(b'5\r\nhello\r\n6\r\n world\r\n0\r\n\r\n');return
            body=self.rfile.read(int(self.headers.get('Content-Length','0'))).decode()
            payload=json.dumps({'method':self.command,'query':urllib.parse.urlsplit(self.path).query,'body':body,'probe':self.headers.get('X-Probe')}).encode()
            try:
                self.send_response(200);self.send_header('Content-Length',str(len(payload)));self.end_headers();self.wfile.write(payload)
            except (BrokenPipeError,ssl.SSLError):pass
    server=http.server.ThreadingHTTPServer(('127.0.0.1',0),Handler)
    context=ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER);context.load_cert_chain(path/'server.crt',path/'server.key')
    server.socket=context.wrap_socket(server.socket,server_side=True)
    thread=threading.Thread(target=server.serve_forever,daemon=True);thread.start()
    env=os.environ.copy();env['TLS_FIXTURE_URL']=f'https://localhost:{server.server_port}';env['TLS_FIXTURE_CA']=str(path/'ca.crt')
    result=subprocess.run([os.environ.get('MYSTEP_PHP', '/Applications/ServBay/package/php/7.4/current/bin/php' if Path('/Applications/ServBay/package/php/7.4/current/bin/php').exists() else 'php'), str(ROOT/'tests/security/security_regression.php'), '--database'],env=env,capture_output=True,text=True)
    print(result.stdout+result.stderr)
    server.shutdown();server.server_close()
    raise SystemExit(result.returncode)
