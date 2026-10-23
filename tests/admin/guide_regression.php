<?php
/**
 * 框架指南只读回归：在随机临时目录复制文档并渲染；不启动会话、访问数据库或修改正式站点。
 * 使用：PHP 7.4 CLI执行 php tests/admin/guide_regression.php。
 */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$source=dirname(__DIR__,2);$scratch=sys_get_temp_dir().'/mystep-guide-'.bin2hex(random_bytes(6));mkdir($scratch,0700);
define('ROOT',$scratch.'/');spl_autoload_register(function($name)use($source){$file=$source.'/lib/'.$name.'.class.php';if(is_file($file))require_once $file;});require $source.'/vendor/autoload.php';require $source.'/lib/myFile.class.php';require $source.'/app/myStep/MyStepGuide.class.php';
set_error_handler(function($n,$s,$f,$l){if(error_reporting()&$n)throw new ErrorException($s,0,$n,$f,$l);});
$passed=$failed=0;
function ensure($value,$message='Assertion failed'){if(!$value)throw new RuntimeException($message);}
function same($expected,$actual){ensure($expected===$actual,'Unexpected result: '.var_export($actual,true));}
function test($name,$run){global $passed,$failed;try{$run();$passed++;echo 'PASS '.$name.PHP_EOL;}catch(Throwable $e){$failed++;echo 'FAIL '.$name.': '.$e->getMessage().' line '.$e->getLine().PHP_EOL;}}
function document($html){$dom=new DOMDocument();@$dom->loadHTML('<?xml encoding="UTF-8">'.$html);return new DOMXPath($dom);}
try {
    mkdir(ROOT.'docs',0700,true);copy($source.'/docs/catalog.json',ROOT.'docs/catalog.json');
    foreach(MyStepGuide::catalog() as $entry){$target=ROOT.$entry['path'];if(!is_dir(dirname($target)))mkdir(dirname($target),0700,true);copy($source.'/'.$entry['path'],$target);}
    copy($source.'/readme.md',ROOT.'readme.md');
    test('catalog covers every curated document and every document renders',function(){
        $catalog=MyStepGuide::catalog();$paths=array_column($catalog,'path');same(count($paths),count(array_unique($paths)));ensure(count($catalog)>=180);foreach($paths as $path)ensure(strpos($path,'backup/')===false && strpos($path,'docs/guide/')!==0);
        $reader=new MyStepGuide('/sub/Admin/guide');same('index',$reader->page()['id']);
        foreach($catalog as $id=>$entry){$page=$reader->page($id);same($id,$page['id']);same($entry['path'],$page['path']);ensure($page['html']!=='',$id);$selected=0;foreach($page['groups'] as $group)foreach($group['links'] as $link)if($link['current']==='page')$selected++;same(1,$selected);}
    });
    test('browser scripts share one category and simplified titles keep existing aliases',function(){
        $catalog=MyStepGuide::catalog();$reader=new MyStepGuide('/sub/Admin/guide');
        foreach($catalog as $id=>$entry){
            if(strpos($entry['path'],'docs/frontend/')===0)same('浏览器前端',$entry['group']);
            if(strpos($entry['path'],'docs/backend/')===0)ensure($entry['group']!=='浏览器前端',$id);
        }
        foreach(['guide-core'=>'execution','guide-app'=>'app-development','guide-plugin'=>'plugin-development','guide-current'=>'management','admin'=>'app-info-admin','cms'=>'app-info-cms'] as $old=>$new)same($new,$reader->page($old)['id']);
        foreach(['execution','app-rules','first-app','plugin-rules','first-plugin','api','database','templates','frontend-forms'] as $id)same($id,$reader->page($id)['id']);
    });
    test('section directories lead their sections and removed categories are absent',function(){
        $catalog=MyStepGuide::catalog();$groups=[];
        foreach($catalog as $id=>$entry){$groups[$entry['group']][]=$id;ensure(strpos($entry['title'],'参考')===false,$id);ensure(strpos($entry['title'],'PHP后端')===false && strpos($entry['title'],'JavaScript前端')===false,$id);}
        foreach(['基础说明'=>'basics-index','变量与执行'=>'backend-index','浏览器前端'=>'frontend-index','应用开发'=>'app-development','插件开发'=>'plugin-development','现有应用介绍'=>'applications','现有插件介绍'=>'plugins','更新历史'=>'history-index'] as $group=>$id)same($id,$groups[$group][0]);
        foreach(array_keys($groups) as $group)ensure(strpos($group,'维护与发布')===false && strpos($group,'本地与同步')===false && strpos($group,'JavaScript前端')===false,$group);
        foreach(['maintenance','dependencies','phpdoc','tests','history-current','history-published'] as $id)ensure(!isset($catalog[$id]),$id);
        $reader=new MyStepGuide('/sub/Admin/guide');$page=$reader->page('frontend-index');ensure(strpos($page['html'],'jQuery')!==false && strpos($page['html'],'Bootstrap')!==false);ensure(strpos($page['html'],'3.6.1')!==false && strpos($page['html'],'4.6.2')!==false);
    });
    test('GitHub history renders Chinese headings and separate numbered updates',function(){
        $reader=new MyStepGuide('/sub/Admin/guide');$count=0;
        foreach(MyStepGuide::catalog() as $id=>$entry){if(!preg_match('/^history-\\d{4}$/',$id))continue;$page=$reader->page($id);$xpath=document($page['html']);same(0,$xpath->query('//pre')->length);foreach($xpath->query('//h2') as $heading){ensure(preg_match('/\\p{Han}/u',$heading->textContent));ensure(mb_strlen($heading->textContent,'UTF-8')<120);$count++;}if($id==='history-2023')same(5,$xpath->query('//ol/li')->length);}
        $record=json_decode(file_get_contents($GLOBALS['source'].'/docs/history/published.json'),true);same($record['commits'],$count);
    });
    test('every source file and direct PHP declaration has a matching detailed reference',function(){
        $source=$GLOBALS['source'];$coverage=json_decode(file_get_contents($source.'/docs/reference-coverage.json'),true);same(53,count($coverage));$count=0;
        foreach($coverage as $file=>$record){same(hash_file('sha256',$source.'/'.$file),$record['sha256']);$text=file_get_contents($source.'/'.$record['document']);foreach($record['methods'] as $method)ensure(strpos($text,'### '.$method."\n")!==false,$method);$count+=count($record['methods']);}
        same(899,$count);
    });
    test('topics contain meaningful workflows and current category is expanded',function(){
        $reader=new MyStepGuide('/sub/Admin/guide');foreach(['config-forms','database','api','templates','first-app','first-plugin','frontend-forms','js-ref-global','history-index'] as $id){$page=$reader->page($id);ensure(count($page['outline'])>=2,$id);$open=0;foreach($page['groups'] as $group)if($group['open'])$open++;same(1,$open);}
    });
    test('every curated internal document link and fragment resolves to a registered target',function(){
        $reader=new MyStepGuide('/sub/Admin/guide');$pages=[];$anchors=[];
        foreach(MyStepGuide::catalog() as $id=>$entry){$pages[$id]=$reader->page($id);$anchors[$id]=[];foreach(document($pages[$id]['html'])->query('//*[@id]') as $node)$anchors[$id][]=$node->getAttribute('id');}
        foreach($pages as $id=>$page)foreach(document($page['html'])->query('//a[@href]') as $link){$url=$link->getAttribute('href');if(strpos($url,'/sub/Admin/guide?doc=')!==0)continue;$parts=parse_url($url);parse_str($parts['query']??'',$query);$target=$query['doc']??'';ensure(isset($pages[$target]),$id.' -> '.$url);$fragment=rawurldecode($parts['fragment']??'');if($fragment!=='')ensure(in_array($fragment,$anchors[$target],true),$id.' -> '.$url);}
    });
    test('all guide URLs preserve the current backend and three routing modes',function(){
        foreach(['/sub/Admin/guide','/sub/index.php/Admin/guide','/sub/index.php?/Admin/guide','/sub/console/guide','/sub/index.php?/console/guide'] as $base){$reader=new MyStepGuide($base);same($base.(strpos($base,'?')===false?'?':'&').'doc=execution',$reader->url('execution'));$page=$reader->page('index');ensure(strpos(html_entity_decode($page['html'],ENT_QUOTES,'UTF-8'),$reader->url('app-rules'))!==false);}
    });
    test('partial reads preserve safe Markdown but omit redundant left navigation',function(){
        $reader=new MyStepGuide('/sub/index.php?/Admin/guide');$full=$reader->page('api');$partial=$reader->page('api',false);
        same($full['html'],$partial['html']);same($full['outline'],$partial['outline']);same([],$partial['groups']);
    });
    test('search covers titles and text with AND semantics case folding and bounded results',function(){
        $reader=new MyStepGuide('/sub/index.php?/Admin/guide');$result=$reader->search('myAuth');same('ref-myauth',$result['items'][0]['id']);
        $result=$reader->search('MYSTRING convertCharset');ensure($result['total']>0);ensure(in_array('ref-mystring',array_column($result['items'],'id'),true));
        foreach($result['items'] as $item){ensure(strpos($item['url'],'/sub/index.php?/Admin/guide&doc=')===0);ensure(mb_strlen($item['snippet'],'UTF-8')<230);ensure(!isset($item['score'],$item['order']));}
        $result=$reader->search('功能',2);ensure($result['total']>2);same(2,count($result['items']));same(['query'=>'','total'=>0,'items'=>[]],$reader->search('   '));
    });
    test('search never reads backups arbitrary files or invalid queries',function(){
        mkdir(ROOT.'docs/backup',0700,true);file_put_contents(ROOT.'docs/backup/only.md','backup_unique_search_891270');
        $reader=new MyStepGuide('/sub/Admin/guide');same(0,$reader->search('backup_unique_search_891270')['total']);
        same(0,$reader->search('../config/private_secret_891270.php')['total']);
        foreach([[],false,7,str_repeat('中',81),'a b c d e f g h i',chr(255)] as $query){try{$reader->search($query);throw new LogicException('Expected query rejection');}catch(InvalidArgumentException $error){}}
    });
    test('unknown identifiers arrays absolute paths and traversal cannot read arbitrary files',function(){
        $reader=new MyStepGuide('/sub/Admin/guide');foreach(['../config/config.php','config/config.php','/etc/passwd','php://filter','%2e%2e','docs/backup/20261023/docs/framework/api.md','backup',[],false,1] as $id){try{$reader->page($id);throw new LogicException('Unexpected access');}catch(OutOfBoundsException $e){}}
    });
    test('catalog rejects backup paths traversal duplicates and malformed identities',function(){
        $manifest=ROOT.'docs/catalog.json';$original=file_get_contents($manifest);$data=json_decode($original,true);
        try {
            foreach(['docs/backup/20261023/secret.md','docs/backend/../backup/secret.md','/etc/passwd','php://filter','docs/backend/%2e%2e/file.md'] as $path){$bad=$data;$bad['entries'][]=['id'=>'evil','path'=>$path,'title'=>'test','group'=>'test'];file_put_contents($manifest,json_encode($bad));try{MyStepGuide::catalog();throw new LogicException('Expected invalid manifest');}catch(RuntimeException $error){ensure(!($error instanceof LogicException));}}
            $bad=$data;$bad['entries'][]=$bad['entries'][0];file_put_contents($manifest,json_encode($bad));try{MyStepGuide::catalog();throw new LogicException('Expected duplicate rejection');}catch(RuntimeException $error){ensure(!($error instanceof LogicException));}
        } finally {file_put_contents($manifest,$original);}
    });
    test('same page fragments and cross-document links remain inside the active backend',function(){
        $reader=new MyStepGuide('/sub/index.php?/Admin/guide');file_put_contents(ROOT.'docs/basics/01-guide-basics.md',"# Fixture\n\n## 章节 A\n\n[本篇](#章节-a) [规范](../../app/README.md#目录) [源码](lib/myFile.class.php) [外链](https://example.test/doc)");
        $page=$reader->page('overview');$xpath=document($page['html']);same($reader->url('guide-basics','章节-a'),$xpath->query('//a')->item(0)->getAttribute('href'));same($reader->url('app-rules','目录'),$xpath->query('//a')->item(1)->getAttribute('href'));same(false,$xpath->query('//a')->item(2)->hasAttribute('href'));same('noopener noreferrer',$xpath->query('//a')->item(3)->getAttribute('rel'));
    });
    test('headings have matching unique Unicode anchors and preserve code blocks',function(){
        $reader=new MyStepGuide('/sub/console/guide');file_put_contents(ROOT.'docs/basics/01-guide-basics.md',"# Fixture\n\n## 参数与 `need`\n\n### 子项\n\n## 参数与 `need`\n\n```php\n\$x = '<tag>';\n  echo \$x;\n```\n");
        $page=$reader->page('overview');$xpath=document($page['html']);same('参数与-need',$xpath->query('//h2')->item(0)->getAttribute('id'));same('参数与-need-1',$xpath->query('//h2')->item(1)->getAttribute('id'));same($reader->url('guide-basics','参数与-need-1'),$page['outline'][2]['url']);ensure(strpos($xpath->query('//pre/code')->item(0)->textContent,"\$x = '<tag>';\n  echo \$x;")!==false);
    });
    test('safe Markdown blocks executable HTML and unsafe links',function(){
        $reader=new MyStepGuide('/sub/Admin/guide');file_put_contents(ROOT.'docs/basics/01-guide-basics.md',"# Fixture\n\n<script>alert(1)</script>\n\n[x](javascript:alert) [x](data:text/html,evil) [x](//external.test/doc) [x](../../config/config.php)");
        $page=$reader->page('overview');$xpath=document($page['html']);same(0,$xpath->query('//script|//a[starts-with(@href,"javascript:")]|//a[starts-with(@href,"data:")]|//a[starts-with(@href,"//")]')->length);
    });
    test('changes to canonical documents appear on the next read without cache clearing',function(){
        $reader=new MyStepGuide('/sub/Admin/guide');file_put_contents(ROOT.'docs/basics/01-guide-basics.md','# first');ensure(strpos($reader->page('overview')['html'],'first')!==false);file_put_contents(ROOT.'docs/basics/01-guide-basics.md','# second');ensure(strpos($reader->page('overview')['html'],'second')!==false);
    });
    test('missing documents give explicit failure and a minimal installation keeps its introduction',function(){
        $reader=new MyStepGuide('/sub/Admin/guide');unlink(ROOT.'docs/README.md');same('guide-basics',$reader->page()['id']);try{$reader->page('index');throw new LogicException('Expected missing file');}catch(OutOfBoundsException $e){}
        unlink(ROOT.'docs/basics/01-guide-basics.md');same('overview',$reader->page()['id']);
        copy($GLOBALS['source'].'/docs/basics/01-guide-basics.md',ROOT.'docs/basics/01-guide-basics.md');
    });
    test('a whitelisted path cannot follow a symlink outside the site',function()use($scratch){
        $file=tempnam(sys_get_temp_dir(),'mystep-guide-outside-');file_put_contents($file,'outside secret');unlink(ROOT.'docs/basics/01-guide-basics.md');symlink($file,ROOT.'docs/basics/01-guide-basics.md');try{$reader=new MyStepGuide('/sub/Admin/guide');try{$reader->page('guide-basics');throw new LogicException('Expected boundary check');}catch(OutOfBoundsException $e){}}finally{unlink($file);}
    });
} finally {restore_error_handler();myFile::del($scratch);}
echo "Passed: $passed; Failed: $failed\n";exit($failed?1:0);
