<?php
// PHP 7.4 CLI: real CMS URL helpers and real templates; no database or business writes.
if(PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('ROOT', dirname(__DIR__, 2).'/'); define('LIB', ROOT.'lib/'); define('APP', ROOT.'app/');
define('VENDOR', ROOT.'vendor/'); define('CONFIG', ROOT.'config/');
define('ROOT_WEB', getenv('CMS_LINK_BASE') ?: '/');
if(getenv('CMS_LINK_BOUND') !== '0') define('URL_FIX', 'CMS');
$_SERVER['DOCUMENT_ROOT']='/'; $_SERVER['HTTP_HOST']='cms.example.test'; $_SERVER['REQUEST_TIME']=time(); $_SERVER['HTTPS']='on';
spl_autoload_register(function($class) { $file=LIB.$class.'.class.php'; if(is_file($file)) require_once $file; });
require_once LIB.'function.php'; require_once ROOT.'app/CMS/lib.php';
set_error_handler(function($n,$s,$f,$l) { if(error_reporting() & $n) throw new ErrorException($s,0,$n,$f,$l); });
$mode=getenv('CMS_LINK_MODE') ?: 'rewrite';
$ms_setting=(object)['router'=>(object)['mode'=>$mode,'default_app'=>'myStep']]; $S=$ms_setting;
$info_app=['app'=>'CMS','path'=>['admin_cms','article','content'],'para'=>[]];
$news_cat_plat=[['cat_id'=>5,'web_id'=>1,'idx'=>'A+B &中文?#/']];
$website=[['web_id'=>1,'domain'=>'cms.example.test'],['web_id'=>2,'domain'=>'other.example.test,alias.example.test']]; $web_info=['web_id'=>1];
$query=''; $count=81; $page=1; $page_size=20;
$passed=$failed=0; $scratch=sys_get_temp_dir().'/mystep-cms-links-'.bin2hex(random_bytes(6)); mkdir($scratch,0700);
function same($a,$b) { if($a!==$b) throw new RuntimeException('Expected '.var_export($a,true).'; got '.var_export($b,true)); }
function test($name,$run) { global $passed,$failed; try {$run();$passed++;echo 'PASS '.$name.PHP_EOL;} catch(Throwable $e) {$failed++;echo 'FAIL '.$name.': '.$e->getMessage().PHP_EOL;} }
function renderCMS($style,$name) {
    global $scratch,$tpl_setting;
    $tpl_setting=['path'=>ROOT.'app/CMS/template','style'=>$style,'name'=>$name,'path_compile'=>$scratch.'/'.$style];
    $t=new myTemplate($tpl_setting);
    $t->regTag('pages','app\CMS\parsePages');
    $t->assign(['web_id'=>1,'cat_id'=>'','web_id_site'=>1,'path_admin'=>app\CMS\getURL('admin_cms/'),'url_prefix'=>myStep::setURL(),'url_prefix_app'=>app\CMS\getURL(''),'path_root'=>ROOT_WEB,'path_app'=>ROOT_WEB.'app/CMS/','keyword'=>'A+B &中文?#"','idx'=>'A+B &中文?#','tpl_idx'=>'A+B &中文?#','method'=>'add','list_func'=>'all','list_web'=>'all','websites'=>myString::toJson($GLOBALS['website']),'captcha_url'=>myStep::setURL('captcha/?core=CMS')]);
    $t->setLoop('record',['news_id'=>7,'id'=>7,'web_id'=>1,'cat_id'=>5,'link'=>app\CMS\getLink(['news_id'=>7,'cat_id'=>5]),'url'=>'https://outside.test/a//b?x=1&y=2']);
    $t->setLoop('file',['name'=>'block/a+b &中文.tpl']);
    return $t->render('',false);
}
function cleanTree($path) { if(is_dir($path)) { foreach(new FilesystemIterator($path) as $p) cleanTree($p->getPathname()); rmdir($path); } else unlink($path); }
if(in_array('--fixtures',$argv,true)) {
    $fixtures=[];
    foreach(['art_content_list'=>'article/content/','func_link_list'=>'function/link/','art_custom_list'=>'article/custom/','func_template_list'=>'function/template/','func_template'=>'function/template/','art_tag'=>'article/tag/','user_detail_list'=>'user/detail/','set_sysop_list'=>'setting/sysop/','user_online'=>'user/online/','art_content_input'=>'article/content/','func_link_input'=>'function/link/','art_custom_input'=>'article/custom/','func_template_input'=>'function/template/','login'=>'','frame'=>''] as $name=>$path) {
        $fixtures[]=['name'=>$name,'prefix'=>app\CMS\getURL('admin_cms/'.$path),'html'=>renderCMS('admin',$name)];
    }
    foreach(['main','catalog','login','register'] as $name) $fixtures[]=['name'=>'default/'.$name,'prefix'=>app\CMS\getURL('catalog/news/'),'html'=>renderCMS('default',$name)];
    $result=['setting'=>['url_prefix'=>myStep::setURL(),'url_fix'=>defined('URL_FIX')?'CMS':'','url_prefix_app'=>app\CMS\getURL(''),'app'=>'CMS','path_root'=>ROOT_WEB], 'fixtures'=>$fixtures];
    cleanTree($scratch); echo json_encode($result,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); exit;
}
try {
    foreach(['rewrite','path_info','query_string'] as $value) {
        $ms_setting->router->mode=$value;
        test($value.' add route and parameters stay separate',function() {
            $url=app\CMS\getURL('admin_cms/article/content/add',['cat_id'=>'','web_id'=>1]);
            $router=new myRouter(['default_app'=>'CMS','url_fix'=>defined('URL_FIX')?'/CMS':'']);
            $_SERVER['SCRIPT_NAME']=ROOT_WEB.'index.php'; $_SERVER['REQUEST_URI']=$url; $_SERVER['QUERY_STRING']=parse_url($url,PHP_URL_QUERY) ?: ''; $_SERVER['PATH_INFO']='';
            $parsed=$router->parseQuery(); same('/'.(defined('URL_FIX')?'':'CMS/').'admin_cms/article/content/add/', $parsed['p']); same(['cat_id'=>'','web_id'=>'1'],$_GET);
        });
        test($value.' root prefix accepts concatenated API paths',function() {
            same(str_replace('?/', '?', myStep::setURL('captcha/')),myStep::setURL().'captcha/');
        });
        test($value.' pagination preserves filters and decoded values',function() {
            $_GET=['keyword'=>'A+B &中文?#','cat_id'=>'5','web_id'=>'2'];
            list($page,$offset,$size)=app\CMS\getPageList(81,2,20,'order=subject');
            $query=parse_url($page['link_next'],PHP_URL_QUERY); if(strpos($query,'/')===0) $query=explode('&',$query,2)[1]; parse_str($query,$params);
            same('A+B &中文?#',$params['keyword']); same('5',$params['cat_id']); same('2',$params['web_id']); same('3',$params['page']); same(20,$offset); same(20,$size);
            same(true,strpos($page['link_next'],'admin_cms/article/content')!==false);
        });
        test($value.' page boundaries stay valid',function() {
            list($p,$offset,$size)=app\CMS\getPageList(0,0,0);same(1,$p['page_current']);same(0,$offset);same(20,$size);
            list($p,$offset)=app\CMS\getPageList(41,999,20);same(3,$p['page_current']);same(40,$offset);
        });
        test($value.' tag and catalog special characters round trip',function() {
            $tag='A+B &中文?#/';$url=app\CMS\getLink($tag,'tag');same(true,strpos($url,'tag/'.rawurlencode($tag))!==false);
            $url=app\CMS\getLink(['cat_id'=>5],'catalog');same(true,strpos($url,'catalog/'.rawurlencode($tag).'/')!==false);
        });
    }
    test('query replacement preserves route and fragment',function() {
        same('/sub/index.php?/article/7&web_id=2&k=A%2BB%20%26%E4%B8%AD%E6%96%87#part',app\CMS\queryURL('/sub/index.php?/article/7&web_id=1&page=3#part',['page'=>null,'web_id'=>2,'k'=>'A+B &中文']));
        same('/article/7?web_id=1#part',app\CMS\queryURL('/article/7#part',['web_id'=>1]));
    });
    test('absolute feed URLs preserve cross-site origins',function() {
        same('https://other.example.test/a?x=1&y=2',app\CMS\absoluteURL('//other.example.test/a?x=1&y=2'));
        same('https://external.test/a',app\CMS\absoluteURL('https://external.test/a'));
        same('https://cms.example.test/sub/a',app\CMS\absoluteURL('/sub/a'));
    });
    test('real list templates encode keyword and file query values',function() {
        $html=renderCMS('admin','art_content_list');same(true,strpos($html,'keyword=A%2BB%20%26%E4%B8%AD%E6%96%87%3F%23%22')!==false);same(false,strpos($html,'href="edit&')!==false);
        $html=renderCMS('admin','func_template_list');same(true,strpos($html,'file=block%2Fa%2Bb%20%26%E4%B8%AD%E6%96%87.tpl')!==false);
    });
} finally { cleanTree($scratch); }
echo "Passed: $passed; Failed: $failed\n";exit($failed?1:0);
