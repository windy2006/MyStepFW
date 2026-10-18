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
    foreach(MyStepGuide::catalog() as $entry){$target=ROOT.$entry['path'];if(!is_dir(dirname($target)))mkdir(dirname($target),0700,true);copy($source.'/'.$entry['path'],$target);}
    test('catalog covers every curated document and every document renders',function(){
        $catalog=MyStepGuide::catalog();$paths=array_column($catalog,'path');same(count($paths),count(array_unique($paths)));
        $reader=new MyStepGuide('/sub/Admin/guide');same('index',$reader->page()['id']);
        foreach($catalog as $id=>$entry){$page=$reader->page($id);same($id,$page['id']);same($entry['path'],$page['path']);ensure($page['html']!=='',$id);$selected=0;foreach($page['groups'] as $group)foreach($group['links'] as $link)if($link['current']==='page')$selected++;same(1,$selected);}
    });
    test('all guide URLs preserve the current backend and three routing modes',function(){
        foreach(['/sub/Admin/guide','/sub/index.php/Admin/guide','/sub/index.php?/Admin/guide','/sub/console/guide','/sub/index.php?/console/guide'] as $base){$reader=new MyStepGuide($base);same($base.(strpos($base,'?')===false?'?':'&').'doc=execution',$reader->url('execution'));$page=$reader->page('index');ensure(strpos(html_entity_decode($page['html'],ENT_QUOTES,'UTF-8'),$reader->url('app-rules'))!==false);}
    });
    test('unknown identifiers arrays absolute paths and traversal cannot read arbitrary files',function(){
        $reader=new MyStepGuide('/sub/Admin/guide');foreach(['../config/config.php','config/config.php','/etc/passwd','php://filter','%2e%2e',[],false,1] as $id){try{$reader->page($id);throw new LogicException('Unexpected access');}catch(OutOfBoundsException $e){}}
    });
    test('same page fragments and cross-document links remain inside the active backend',function(){
        $reader=new MyStepGuide('/sub/index.php?/Admin/guide');file_put_contents(ROOT.'readme.md',"# Fixture\n\n## 章节 A\n\n[本篇](#章节-a) [规范](app/README.md#目录) [源码](lib/myFile.class.php) [外链](https://example.test/doc)");
        $page=$reader->page('overview');$xpath=document($page['html']);same($reader->url('overview','章节-a'),$xpath->query('//a')->item(0)->getAttribute('href'));same($reader->url('app-rules','目录'),$xpath->query('//a')->item(1)->getAttribute('href'));same(false,$xpath->query('//a')->item(2)->hasAttribute('href'));same('noopener noreferrer',$xpath->query('//a')->item(3)->getAttribute('rel'));
    });
    test('headings have matching unique Unicode anchors and preserve code blocks',function(){
        $reader=new MyStepGuide('/sub/console/guide');file_put_contents(ROOT.'readme.md',"# Fixture\n\n## 参数与 `need`\n\n### 子项\n\n## 参数与 `need`\n\n```php\n\$x = '<tag>';\n  echo \$x;\n```\n");
        $page=$reader->page('overview');$xpath=document($page['html']);same('参数与-need',$xpath->query('//h2')->item(0)->getAttribute('id'));same('参数与-need-1',$xpath->query('//h2')->item(1)->getAttribute('id'));same($reader->url('overview','参数与-need-1'),$page['outline'][2]['url']);ensure(strpos($xpath->query('//pre/code')->item(0)->textContent,"\$x = '<tag>';\n  echo \$x;")!==false);
    });
    test('safe Markdown blocks executable HTML and unsafe links',function(){
        $reader=new MyStepGuide('/sub/Admin/guide');file_put_contents(ROOT.'readme.md',"# Fixture\n\n<script>alert(1)</script>\n\n[x](javascript:alert) [x](data:text/html,evil) [x](//external.test/doc) [x](../../config/config.php)");
        $page=$reader->page('overview');$xpath=document($page['html']);same(0,$xpath->query('//script|//a[starts-with(@href,"javascript:")]|//a[starts-with(@href,"data:")]|//a[starts-with(@href,"//")]')->length);
    });
    test('changes to canonical documents appear on the next read without cache clearing',function(){
        $reader=new MyStepGuide('/sub/Admin/guide');file_put_contents(ROOT.'readme.md','# first');ensure(strpos($reader->page('overview')['html'],'first')!==false);file_put_contents(ROOT.'readme.md','# second');ensure(strpos($reader->page('overview')['html'],'second')!==false);
    });
    test('missing documents give explicit failure and a minimal installation keeps its introduction',function(){
        $reader=new MyStepGuide('/sub/Admin/guide');unlink(ROOT.'docs/README.md');same('overview',$reader->page()['id']);try{$reader->page('index');throw new LogicException('Expected missing file');}catch(OutOfBoundsException $e){}
    });
    test('a whitelisted path cannot follow a symlink outside the site',function()use($scratch){
        $file=tempnam(sys_get_temp_dir(),'mystep-guide-outside-');file_put_contents($file,'outside secret');unlink(ROOT.'readme.md');symlink($file,ROOT.'readme.md');try{$reader=new MyStepGuide('/sub/Admin/guide');try{$reader->page('overview');throw new LogicException('Expected boundary check');}catch(OutOfBoundsException $e){}}finally{unlink($file);}
    });
} finally {restore_error_handler();myFile::del($scratch);}
echo "Passed: $passed; Failed: $failed\n";exit($failed?1:0);
