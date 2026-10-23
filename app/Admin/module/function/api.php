<?php
/**
 * 文件：app/Admin/module/function/api.php (new)
 * 功能说明：API目录、逐行展开设置和AJAX保存；仅管理代码已登记的接口。
 * 方法目录：apiEndpoints、saveApiEndpoint、resetApiEndpoint（详见回调PHPDoc）。
 * 对应模板：function_api、api_endpoint；字段沿用component_field。
 * 使用说明：Admin统一执行管理员、POST、CSRF和版本检查；AJAX返回JSON，普通提交保留303。
 */
require_once APP.'myStep/MyStepApiManager.class.php';
return [
    /**
     * 功能说明：在每条API下方装配独立设置表单，地址以不带格式的纯文本显示。
     * 参数说明：无显式参数。
     * @return string 当前应用模板生成的接口目录
     */
    'apiEndpoints'=>function(){
        $manager=new MyStepApiManager();$rows=[];
        foreach($manager->entries() as $entry){
            $fields=$entry['registered']?$manager->fields('endpoint',$entry['scope'],$entry['name']):[
                ['name'=>'version','type'=>'hidden','value'=>$manager->version],['name'=>'scope','type'=>'hidden','value'=>$entry['scope']],['name'=>'name','type'=>'hidden','value'=>$entry['name']],
            ];
            $content='';foreach($fields as $field)$content.=$this->ui->field($field);
            $entry['editor']=$this->ui->view('api_endpoint',['fields'=>$content,'url'=>$this->ui->url('function/api')],[],['registered'=>$entry['registered']]);$rows[]=$entry;
        }
        $this->ui->addCSS(APP.'myStep/asset/api.css')->addJS(APP.'myStep/asset/api.js');
        return $this->ui->view('function_api',['settings_url'=>$this->ui->url('setting').'#api'],['endpoints'=>$rows]);
    },
    /**
     * 功能说明：保存当前API的四项设置并返回实时状态和最新配置版本。
     * @param array $p scope/name/policy/version；字段已由Admin白名单校验
     * @return array AJAX反馈兼容普通POST跳转结果
     */
    'saveApiEndpoint'=>function($p){$m=new MyStepApiManager();$m->saveEndpoint($p['scope'],$p['name'],$p['policy'],$p['version']);return $m->result($p['scope'],$p['name']);},
    /**
     * 功能说明：移除当前API覆盖或清理历史设置，保留原业务权限。
     * @param array $p scope/name/version
     * @return array AJAX反馈兼容普通POST跳转结果
     */
    'resetApiEndpoint'=>function($p){$m=new MyStepApiManager();$m->resetEndpoint($p['scope'],$p['name'],$p['version']);return $m->result($p['scope'],$p['name']);},
];
