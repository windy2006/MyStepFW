<?php
/**
 * 应用公共逻辑回归：真实 CMS/Sample 辅助函数、栏目异常、标签及公开配置。
 * 用法：PHP 7.4 CLI 执行本文件；仅使用临时文件，不连接业务数据库。
 */
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
ob_start();session_start(['use_cookies'=>false,'cache_limiter'=>'']);
define('ROOT',dirname(__DIR__, 2).'/');define('APP',ROOT.'app/');define('LIB',ROOT.'lib/');define('CONFIG',ROOT.'config/');define('ROOT_WEB','/sub/');
$_SERVER['DOCUMENT_ROOT']='/';$_SERVER['REQUEST_TIME']=time();
spl_autoload_register(function($name) { foreach([LIB.$name.'.class.php',LIB.'database/'.$name.'.class.php'] as $file) if(is_file($file)) { require_once $file;return; } });
require_once LIB.'function.php';require_once APP.'CMS/lib.php';require_once APP.'myStep/lib.php';
set_error_handler(function($n,$s,$f,$l) { if(error_reporting() & $n) throw new ErrorException($s,0,$n,$f,$l); });
$passed=$failed=0;
function same($a,$b) { if($a!==$b) throw new RuntimeException('Unexpected result: '.var_export($a,true)); }
function test($name,$run) { global $passed,$failed;try { $run();$passed++;echo 'PASS '.$name.PHP_EOL; }catch(Throwable $e) { $failed++;echo 'FAIL '.$name.': '.$e->getMessage().PHP_EOL; } }
function row($id,$pid=0,$order=1,$web=1) { return ['cat_id'=>$id,'pid'=>$pid,'web_id'=>$web,'order'=>$order,'name'=>'cat'.$id]; }
test('lookup accepts sparse and associative lists',function() {
    same(['id'=>2],app\CMS\getPara(['bad'=>[],'target'=>['id'=>2]],'id','2'));
    same(false,app\CMS\getPara([null,['other'=>2]],'id',2));
});
test('tree does not depend on parent ID ordering',function() {
    $tree=app\CMS\categoryTree([row(1,9),row(9)]);
    same(9,$tree[0]['cat_id']);same(1,$tree[0]['layer']);same(1,$tree[0]['sub'][0]['cat_id']);same(2,$tree[0]['sub'][0]['layer']);
});
test('tree orders siblings and roots',function() {
    $tree=app\CMS\categoryTree([row(1,0,10),row(2,0,2),row(3,2,9),row(4,2,1)]);
    same([2,1],array_column($tree,'cat_id'));same([4,3],array_column($tree[0]['sub'],'cat_id'));
});
foreach(['self'=>[row(1,1)],'cycle'=>[row(1,2),row(2,1)],'orphan'=>[row(1,99)],'cross-web'=>[row(1,2),row(2,0,1,2)]] as $name=>$rows) {
    test('tree retains each node once: '.$name,function()use($rows) {
        $flat=app\CMS\setCatList(app\CMS\categoryTree($rows));$ids=array_column($flat,'cat_id');sort($ids);
        $wanted=array_column($rows,'cat_id');sort($wanted);same($wanted,$ids);
    });
}
test('breadcrumb is ordered and terminates on corrupt ancestors',function() {
    same([3,2,1],array_column(app\CMS\categoryPath([row(1,2),row(2,3),row(3)],1),'cat_id'));
    foreach([[row(1,1)],[row(1,2),row(2,1)],[row(1,99)]] as $rows) same([],app\CMS\categoryPath($rows,1));
    same([],app\CMS\categoryPath([row(1)],0));
});
test('sidebar rejects cyclic category parents',function() {
    global $news_cat,$news_cat_plat,$web_info;
    $news_cat=[row(1)];$news_cat_plat=[row(1,2),row(2,1)];$web_info=['web_id'=>1];
    same([],app\CMS\getSubcat(1,$info));same(false,$info);
});
test('tags retain alphanumeric and single Chinese names',function() {
    same(['PHP7','中',"O'Reilly"],app\CMS\normalizeTags("PHP7，PHP7；中; O'Reilly ,123,1.5,,"));
    same(['a_b'],app\CMS\normalizeTags(' a b '));
});
test('tags truncate at a character boundary and deduplicate afterwards',function() {
    $tag=str_repeat('文',50);same([$tag],app\CMS\normalizeTags($tag.'甲,'.$tag.'乙'));
    same([],app\CMS\normalizeTags(' , ;，；'));
});
$scratch=sys_get_temp_dir().'/mystep-app-'.bin2hex(random_bytes(6));mkdir($scratch,0700);
try {
    file_put_contents($scratch.'/config.php','<?php return '.var_export(['gen'=>['charset'=>'utf-8','s_usr'=>'hidden','s_pwd'=>'hidden'],'db'=>['password'=>'hidden'],'email'=>['password'=>'hidden','host'=>'mail.test'],'web'=>['etag'=>'hidden','title'=>'demo'],'cookie'=>['sign_secret'=>'hidden','api_key'=>'hidden','pass'=>'hidden']],true).';');
    $S=$ms_setting=new myConfig($scratch.'/config.php');
    $mystep=new class { public function regModule($name,$callback) {} };
    require_once APP.'Sample/lib.php';
    test('public configuration export uses get() and hides nested credentials',function()use($S) {
        $before=$S->get();$result=app\sample\api();same('utf-8',$result['gen']['charset']);same('mail.test',$result['email']['host']);same(false,isset($result['db']));same(false,isset($result['email']['password']));same(false,isset($result['web']['etag']));same(false,isset($result['cookie']['sign_secret']));same([], $result['cookie']);same($before,$S->get());
        same('utf-8',app\sample\api('gen','charset'));
    });
    test('autocomplete rejects traversal mode',function() { same([],app\myStep\autoComplete('../config','abc')['suggestions']); });
    test('data viewer checks identity table names and qualified database',function()use($ms_setting) {
        global $db;
        myReq::removeSession('ms_user');same([],app\myStep\getData('rows'));
        myReq::session('ms_user','fixture');$ms_setting->db->name='primary';
        $db=new class {
            public $table='';public $database='';public $paging=[];
            public function check(){return true;}
            public function getTbls($database){$this->database=$database;return ['rows'];}
            public function getFields($table,$database){return ['title'];}
            public function build($table){if($table!=='[reset]')$this->table=$table;return $this;}
            public function limit($offset,$size){$this->paging=[$offset,$size];return $this;}
            public function select($show){return 'fixture';}
            public function count($sql){return 30;}
            public function records($sql){return [['title'=>'fixture']];}
        };
        same(['total'=>0,'rows'=>[]],app\myStep\getData('../bad'));
        same(['total'=>0,'rows'=>[]],app\myStep\getData('absent'));
        same(['total'=>30,'rows'=>[['title'=>'fixture']]],app\myStep\getData('report.rows'));
        same('report',$db->database);same('report.rows',$db->table);same([0,20],$db->paging);
    });
    test('sample template escapes URL diagnostics',function()use($scratch) {
        $tpl=new myTemplate(['name'=>'sample','path'=>APP.'Sample/template','style'=>'','path_compile'=>$scratch.'/compiled'],false,true);
        $GLOBALS['info_app']=['path'=>['<img src=x onerror=app_review_xss()>'],'para'=>['a'=>'<img src=x onerror=app_review_xss()>'],'route'=>'<img src=x onerror=app_review_xss()>'];$_GET=['a'=>'<img src=x onerror=app_review_xss()>'];
        $html=$tpl->render('',false);same(false,strpos($html,'<img src=x onerror=app_review_xss()>')!==false);same(true,strpos($html,'&lt;img src=x onerror=app_review_xss()&gt;')!==false);
    });
} finally {
    $files=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($scratch,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
    foreach($files as $file) { if($file->isDir())rmdir($file->getPathname());else unlink($file->getPathname()); }rmdir($scratch);
}
session_destroy();echo "Passed: $passed; Failed: $failed\n";exit($failed?1:0);
