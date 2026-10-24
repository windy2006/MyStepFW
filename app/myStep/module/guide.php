<?PHP
/**
 * 文件：app/myStep/module/guide.php
 * 功能说明：旧后台文档阅读入口，与Admin共用现有Markdown目录及链接转换。
 * 方法目录：本文件不声明具名方法或函数。
 * 使用说明：框架后台鉴权后加载，读取doc标识；guide_group和guide模板承担展示。
 * 对应模板：template/guide.tpl、template/guide_group.tpl，沿用当前path/style及default回退。
 */
require_once APP.'myStep/lib.php';
$mystep->addCSS(APP.'myStep/asset/guide.css');
$mystep->addJS(STATICS.'js/checkForm.js');$mystep->addJS(APP.'myStep/asset/guide.js');
$guideHome=myStep::setURL(trim($ms_setting->gen->path_admin,'/').'/guide');
$reader=new MyStepGuide($guideHome);
// 沿用旧后台登录拦截，不为搜索另设公开接口。
$article=function($data)use($tpl_setting,$mystep){
    $setting=$tpl_setting;$setting['name']='guide_article';$part=new myTemplate($setting,false);
    $part->assign(['guide_title'=>$data['title'],'guide_path'=>$data['path'],'guide_html'=>$data['html']])->setLoop('outline',$data['outline'],true);
    return $mystep->render($part);
};
if(isset($_GET['_guide']))$reader->ajax($_GET['_guide'],$_GET['doc']??null,$_GET['q']??null,$article);
$setting=$tpl_setting;$setting['name']='guide';
$t=new myTemplate($setting,false);
try {
    $data=$reader->page(myReq::get('doc','!'));
    $t->assign(['guide_title'=>$data['title'],'guide_path'=>$data['path'],'guide_html'=>$data['html'],'guide_id'=>$data['id'],'guide_home'=>$guideHome,'guide_query'=>is_string($_GET['q']??null)?mb_substr($_GET['q'],0,80,'UTF-8'):'','guide_article'=>$article($data)]);
    $t->setLoop('outline',$data['outline'],true);
    foreach($data['groups'] as $group) {
        $groupSetting=$tpl_setting;$groupSetting['name']='guide_group';
        $groupTemplate=new myTemplate($groupSetting,false);
        $groupTemplate->assign('title',$group['title'])->setIf('open',$group['open'])->setLoop('links',$group['links'],true);
        $t->setLoop('groups',['content'=>$mystep->render($groupTemplate)]);
    }
} catch(OutOfBoundsException $error) {
    http_response_code(404);
    $t->assign(['guide_title'=>'文档不存在','guide_error'=>$error->getMessage(),'guide_home'=>$guideHome]);
    $t->setIf('guide_error',true);
}
$content=$mystep->render($t);
