<?php
/**
 * 文件：app/myStep/api_admin.php (new)
 * 功能说明：旧后台API目录及逐行设置装配，复用框架API管理服务和AJAX交互。
 * 方法目录：本文件不声明具名方法或函数。
 * 使用说明：仅由已鉴权的module/function/api.php加载；框架先执行POST/CSRF检查。
 * 对应模板：function_api、api_endpoint、api_field；AJAX只返回JSON，普通提交重新渲染。
 */
require_once __DIR__.'/MyStepApiManager.class.php';
$apiBase=myStep::setURL(trim($ms_setting->gen->path_admin,'/').'/function/api');
$apiAjax=($_SERVER['REQUEST_METHOD']??'GET')==='POST' && ($_SERVER['HTTP_X_REQUESTED_WITH']??'')==='XMLHttpRequest';
$apiNotice='';$apiError='';
try {
    $apiManager=new MyStepApiManager();
    if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
        $action=$_POST['_action']??'';$version=$_POST['version']??null;$scope=$_POST['scope']??null;$name=$_POST['name']??null;
        if($action==='save' && is_array($_POST['policy']??null))$apiManager->saveEndpoint($scope,$name,$_POST['policy'],$version);
        elseif($action==='reset')$apiManager->resetEndpoint($scope,$name,$version);
        else throw new InvalidArgumentException('未知API管理操作。');
        if($apiAjax){header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: no-store');echo json_encode($apiManager->result($scope,$name),JSON_UNESCAPED_UNICODE);exit;}
        $apiNotice='API设置已应用。';
    }
    $apiRows=$apiManager->entries();
    foreach($apiRows as &$row){
        $fields=$row['registered']?$apiManager->fields('endpoint',$row['scope'],$row['name']):[
            ['name'=>'version','type'=>'hidden','value'=>$apiManager->version],['name'=>'scope','type'=>'hidden','value'=>$row['scope']],['name'=>'name','type'=>'hidden','value'=>$row['name']],
        ];
        $content='';
        foreach($fields as $field){
            $field+=['value'=>'','label'=>'','type'=>'text','help'=>''];
            $setting=$tpl_setting;$setting['name']='api_field';$part=new myTemplate($setting,false);
            $part->assign($field+['checked'=>$field['value']?'checked':'']);
            $part->setIf('hidden',$field['type']==='hidden')->setIf('checkbox',$field['type']==='checkbox');
            $content.=$mystep->render($part);
        }
        $setting=$tpl_setting;$setting['name']='api_endpoint';$part=new myTemplate($setting,false);
        $part->assign(['fields'=>$content,'url'=>$apiBase])->setIf('registered',$row['registered']);$row['editor']=$mystep->render($part);
    }unset($row);
    $t->setLoop('endpoints',$apiRows,true);
} catch(Throwable $error){
    $expected=$error instanceof InvalidArgumentException || $error instanceof UnexpectedValueException || $error->getCode()===409;
    http_response_code($error->getCode()===409?409:($expected?422:500));$apiError=$expected?$error->getMessage():'API管理失败，请检查服务器日志。';
    if(!$expected)error_log('API management: '.$error->getMessage());
    if($apiAjax){header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: no-store');echo json_encode(['ok'=>false,'message'=>$apiError],JSON_UNESCAPED_UNICODE);exit;}
}
$t->assign(['notice'=>$apiNotice,'error'=>$apiError,'settings_url'=>myStep::setURL(trim($ms_setting->gen->path_admin,'/').'/setting/').'#api']);
$t->setIf('notice',$apiNotice!=='')->setIf('error',$apiError!=='');
$mystep->addCSS(APP.'myStep/asset/api.css')->addJS(APP.'myStep/asset/api.js');
