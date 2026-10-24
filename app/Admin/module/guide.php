<?php
/**
 * 文件：app/Admin/module/guide.php
 * 功能说明：本功能页面读取与动作处理，保持业务和对应模板独立维护。
 * 方法目录：guide（受信模块回调，功能及参数说明见各回调PHPDoc）。
 * 对应模板：guide。
 * 使用说明：由AdminService按lib.php中pages()的固定模块映射加载；$this为应用服务，ui为当前Admin。
 * 返回说明：视图回调返回模板HTML，动作返回反馈或下载描述；HTTP鉴权及参数校验由Admin统一完成。
 */
return [
    /**
     * 功能说明：分类展示现有框架文档，转换跨文档链接并生成章节目录。
     * 参数说明：无显式参数。
     * @return string guide模板HTML；非法标识或文档缺失时返回404阅读提示
     */
    'guide' => function() {
        require_once APP.'myStep/lib.php';
        $this->ui->addCSS(APP.'myStep/asset/guide.css');
        $this->ui->addJS(STATICS.'js/checkForm.js');$this->ui->addJS(APP.'myStep/asset/guide.js');
        $reader=new MyStepGuide($this->ui->url('guide'));
        // 局部请求仍经过Admin::authorized；片段沿用当前风格的myTemplate模板。
        $article=function($data){return $this->ui->view('guide_article',['guide_title'=>$data['title'],'guide_path'=>$data['path'],'guide_html'=>$data['html']],['outline'=>$data['outline']]);};
        if(isset($_GET['_guide']))$reader->ajax($_GET['_guide'],$_GET['doc']??null,$_GET['q']??null,$article);
        try {$data=$reader->page(myReq::get('doc','!'));}
        catch(OutOfBoundsException $error) {
            http_response_code(404);
            return $this->ui->view('guide',['guide_title'=>'文档不存在','guide_error'=>$error->getMessage(),'guide_home'=>$this->ui->url('guide')],[],['guide_error'=>true]);
        }
        $groups=[];
        foreach($data['groups'] as $group) $groups[]=['content'=>$this->ui->view('guide_group',['title'=>$group['title']],['links'=>$group['links']],['open'=>$group['open']])];
        return $this->ui->view('guide',['guide_title'=>$data['title'],'guide_path'=>$data['path'],'guide_html'=>$data['html'],'guide_id'=>$data['id'],'guide_home'=>$this->ui->url('guide'),'guide_query'=>is_string($_GET['q']??null)?mb_substr($_GET['q'],0,80,'UTF-8'):'','guide_article'=>$article($data)],['groups'=>$groups,'outline'=>$data['outline']]);
    },
];
