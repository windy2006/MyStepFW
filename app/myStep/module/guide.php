<?PHP
/**
 * 文件：app/myStep/module/guide.php
 * 功能说明：旧后台文档阅读入口，与Admin共用现有Markdown目录及链接转换。
 * 方法目录：本文件不声明具名方法或函数。
 * 使用说明：框架后台鉴权后加载，读取doc标识；guide_group和guide模板承担展示。
 * 对应模板：template/guide.tpl、template/guide_group.tpl，沿用当前path/style及default回退。
 */
require_once APP.'myStep/MyStepGuide.class.php';
$mystep->addCSS(APP.'myStep/asset/guide.css');
$guideHome=myStep::setURL(trim($ms_setting->gen->path_admin,'/').'/guide');
$reader=new MyStepGuide($guideHome);
$setting=$tpl_setting;$setting['name']='guide';
$t=new myTemplate($setting,false);
try {
    $data=$reader->page(myReq::get('doc','!'));
    $t->assign(['guide_title'=>$data['title'],'guide_path'=>$data['path'],'guide_html'=>$data['html']]);
    $t->setLoop('outline',$data['outline'],true);
    foreach($data['groups'] as $group) {
        $groupSetting=$tpl_setting;$groupSetting['name']='guide_group';
        $groupTemplate=new myTemplate($groupSetting,false);
        $groupTemplate->assign('title',$group['title'])->setLoop('links',$group['links'],true);
        $t->setLoop('groups',['content'=>$mystep->render($groupTemplate)]);
    }
} catch(OutOfBoundsException $error) {
    http_response_code(404);
    $t->assign(['guide_title'=>'文档不存在','guide_error'=>$error->getMessage(),'guide_home'=>$guideHome]);
    $t->setIf('guide_error',true);
}
$content=$mystep->render($t);
