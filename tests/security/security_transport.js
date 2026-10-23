/* CLI helper: execute security.js prefilter + XHR transport against an isolated HTTP fixture. */
'use strict';
const fs=require('node:fs'),path=require('node:path'),vm=require('node:vm'),http=require('node:http');
const [target,token,cookie,mode='valid']=process.argv.slice(2),url=new URL(target);
if(url.protocol!=='http:' || !['127.0.0.1','localhost'].includes(url.hostname))throw new Error('Only isolated loopback HTTP fixtures are supported');
const events={};let prefilter;
class XHR {
    open(method,address){this.method=method;this.url=address;this.headers={};}
    setRequestHeader(name,value){const key=name.toLowerCase();this.headers[key]=this.headers[key]?this.headers[key]+', '+value:value;}
    send(){
        const transport=this;
        const req=http.request(this.url,{method:this.method,headers:{...this.headers,Cookie:cookie,'Content-Length':'0'}},res=>{
            let body='';res.setEncoding('utf8');res.on('data',chunk=>body+=chunk);res.on('end',()=>{
                let result;try{result=JSON.parse(body);}catch{result={text:body.slice(0,300)};}
                process.stdout.write(JSON.stringify({status:res.statusCode,method:transport.method,tokenCopies:(transport.headers['x-csrf-token']||'').split(',').length,result}));
            });
        });req.on('error',error=>{console.error(error.message);process.exitCode=1;});req.end();
    }
}
const root=url.pathname.slice(0,url.pathname.indexOf('/manager/'))+'/';
const window={msSecurity:{token,origins:[url.origin],admin:['console'],root},jQuery:{ajaxPrefilter(fn){prefilter=fn;}}};
const document={baseURI:url.origin+root,forms:[],addEventListener(name,fn){events[name]=fn;}};
vm.runInNewContext(fs.readFileSync(path.join(__dirname,'../../static/js/security.js'),'utf8'),{window,document,location:{href:document.baseURI},URL,XMLHttpRequest:XHR});
events.DOMContentLoaded();
const options={url:target,type:'GET'};prefilter(options);
const xhr=new XHR();xhr.open(options.type,target);
for(const [name,value] of Object.entries(options.headers||{}))xhr.setRequestHeader(name,mode==='invalid'?'invalid-token':value);
if(mode==='duplicate')xhr.setRequestHeader('X-CSRF-Token',token);
xhr.send();
