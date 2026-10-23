<?php
/**
 * 文件：tests/admin/guide_dom_fixture.php (new)
 * 功能说明：用真实指南模板生成DOM回归输入，只读指定源码及文档，在独占临时目录编译模板。
 * 方法目录：无具名函数。
 * 使用说明：PHP7.4 CLI；参数依次为源码根、指南URL、临时编译目录，由guide_javascript.js调用。
 */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$source=realpath($argv[1]);define('ROOT',$source.'/');
spl_autoload_register(function($name)use($source){$file=$source.'/lib/'.$name.'.class.php';if(is_file($file))require_once $file;});
require $source.'/vendor/autoload.php';require $source.'/app/myStep/MyStepGuide.class.php';
$base=$argv[2];$reader=new MyStepGuide($base);$data=$reader->page('api');
$settings=['path'=>$source.'/app/Admin/template/default/','path_compile'=>$argv[3].'/'];
$part=new myTemplate(['name'=>'guide_article']+$settings,false);
$part->assign(['guide_title'=>$data['title'],'guide_path'=>$data['path'],'guide_html'=>$data['html']])->setLoop('outline',$data['outline'],true);
$article=$part->render('',false);$groups=[];
foreach($data['groups'] as $group){$template=new myTemplate(['name'=>'guide_group']+$settings,false);$template->assign('title',$group['title'])->setIf('open',$group['open'])->setLoop('links',$group['links'],true);$groups[]=['content'=>$template->render('',false)];}
$template=new myTemplate(['name'=>'guide']+$settings,false);
$template->assign(['guide_title'=>$data['title'],'guide_home'=>$base,'guide_id'=>'api','guide_query'=>'','guide_article'=>$article])->setLoop('groups',$groups,true);
echo '<!doctype html><html><head><title>框架指南</title></head><body>'.$template->render('',false).'</body></html>';
