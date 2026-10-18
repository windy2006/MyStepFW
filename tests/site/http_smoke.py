import re,ssl,urllib.request,urllib.error,json,os,tempfile,http.cookiejar
from pathlib import Path
context=ssl.SSLContext(ssl.PROTOCOL_TLS_CLIENT)
context.load_verify_locations(os.environ.get('MYSTEP_CA_FILE','/Applications/ServBay/package/common/openssl/3.2/cacert.pem'))
rows=[];failures=[];csrf=None
opener=urllib.request.build_opener(urllib.request.HTTPSHandler(context=context),urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
def request(url,method='GET',headers=None,expected=200):
    headers=dict(headers or {})
    if csrf and method not in ('GET','HEAD','OPTIONS'):headers['X-CSRF-Token']=csrf
    req=urllib.request.Request(url,headers=headers,method=method)
    try:r=opener.open(req,timeout=20)
    except urllib.error.HTTPError as error:r=error
    with r:
        data=r.read();row={'url':url,'method':method,'status':r.status,'final':r.url,'size':len(data),'allow_origin':r.headers.get('Access-Control-Allow-Origin')}
        rows.append(row)
        if row['status']!=expected or b'An error was found' in data or b'Parse error' in data:failures.append(row)
        return row,data,r.headers
for url in ['https://mystep.com/console/','https://admin.mystep.com/','https://cms.mystep.com/','https://cms.mystep.com/admin_cms/','https://doc.mystep.com/','https://sample.mystep.com/','https://guide.mystep.com/','https://sample.mystep.com/mySample3/helloWorld','https://mystep.com/index.php?/console/&probe=1','https://mystep.com/index.php/console/?probe=1','https://mystep.com/index.php?ms_setting/Sample','https://mystep.com/index.php?ms_language/Sample/default','https://api.mystep.com/CMS/user/json','https://cms.mystep.com/asset/theme/default.css','https://mystep.com/static/js/jquery.js']:
    request(url)
_,page,_=request('https://mystep.com/console/')
for asset in list(dict.fromkeys(re.findall(rb'/cache/script/[^"?<> ]+\.(?:js|css)',page)))[:2]:
    path=asset.decode()+'.meta'
    request('https://mystep.com'+path,expected=404)
    request('https://mystep.com/index.php?'+path.lstrip('/'),expected=404)
for path in ['/.svn/wc.db','/plugin/plugin.db','/config/config.php','/lib/myStep.class.php','/cache/session/sess_any','/index.php?static/../config/config.php','/index.php?static/%2e%2e/config/config.php','/index.php?config/config.php','/index.php?files/202604/log.txt']:
    request('https://mystep.com'+path,expected=404)
row,_,headers=request('https://api.mystep.com/CMS/user/json','OPTIONS',{'Origin':'https://mystep.com','Access-Control-Request-Method':'GET'},204)
if row['allow_origin']!='https://mystep.com':failures.append(row)
row,_,headers=request('https://api.mystep.com/CMS/user/json','OPTIONS',{'Origin':'https://evil.test','Access-Control-Request-Method':'GET'},403)
if row['allow_origin'] is not None:failures.append(row)
request('https://mystep.com/api/myStep/upload/json',method='POST',expected=403)
_,token_body,_=request('https://mystep.com/ms_csrf')
csrf=json.loads(token_body)['token']
request('https://mystep.com/ms_csrf',headers={'Origin':'https://evil.test'},expected=403)
for endpoint in ['upload/json','remove/nonexistent']:
    row,body,headers=request('https://mystep.com/api/myStep/'+endpoint,expected=405)
    if headers.get('Allow')!='POST':failures.append(row)
    request('https://mystep.com/api/myStep/'+endpoint,method='POST')
row,body,headers=request('https://mystep.com/api/myStep/upload/json?core=CMS',method='POST',expected=307)
if not headers.get('Location','').startswith('https://api.mystep.com/'):failures.append(row)
redirected=headers['Location']
_,token_body,_=request('https://api.mystep.com/myStep/csrf/json?core=CMS')
csrf=json.loads(token_body)['token']
request(redirected,method='POST')
request('https://mystep.com/index.php?%63onfig/route.php.sources.json',expected=404)
for path in ['/console/function/cache?id=1','/console/function/plugin/active/?idx=sample','/console/login?out']:
    request('https://mystep.com'+path,expected=405)
request('https://mystep.com/static/js/security.js')
# The fixture targets public index.php, not a credential file, and is removed even on failure.
with tempfile.TemporaryDirectory(prefix='mystep-flow-',dir=Path(__file__).resolve().parents[2]/'files') as scratch:
    link=Path(scratch)/'source.png'
    link.symlink_to(Path(__file__).resolve().parents[2]/'index.php')
    request('https://mystep.com/index.php?files/'+Path(scratch).name+'/source.png',expected=404)
if os.environ.get('MYSTEP_HTTP_RESULTS'):Path(os.environ['MYSTEP_HTTP_RESULTS']).write_text(json.dumps(rows,ensure_ascii=False,indent=2))
print('HTTP requests:',len(rows),'; failures:',json.dumps(failures,ensure_ascii=False))
raise SystemExit(bool(failures))
