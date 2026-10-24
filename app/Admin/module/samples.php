<?php
/**
 * 文件：app/Admin/module/samples.php
 * 功能说明：本功能页面读取与动作处理，保持业务和对应模板独立维护。
 * 方法目录：samples（受信模块回调，功能及参数说明见各回调PHPDoc）。
 * 对应模板：samples。
 * 使用说明：由AdminService按pages.php中的固定模块映射加载；$this为应用服务，ui为当前Admin。
 * 返回说明：视图回调返回模板HTML，动作返回反馈或下载描述；HTTP鉴权及参数校验由Admin统一完成。
 */
return [
    /**
     * 功能说明：共用原示例资源和索引，在新布局中展示。
     * 参数说明：无显式参数。
     * @return string 共用原示例资源和索引，在新布局中展示
     */
    'samples' => function() {
        $index = AdminStore::read(APP . 'myStep/sample/idx.php');
        $name = $this->ui->input('sample', '');
        if(!is_string($name))throw new InvalidArgumentException('示例名称无效。');
        $links = [];
        foreach($index as $id => $label) $links[] = ['name'=>$label,'url'=>$this->ui->url('samples',['sample'=>$id]),'current'=>$id === $name?'page':'false'];
        $body = '';
        if($name !== '' && is_string($name) && isset($index[$name])) {
            myReq::session('sign_upload', 'y');
            $t = new myTemplate(['name' => $name, 'path' => APP . 'myStep/sample/', 'style' => '', 'path_compile' => CACHE . 'template/Admin/samples/'], false);
            $t->assign('path_admin', $this->ui->url());
            $t->assign('sample_assets', myStep::setURL('myStep/asset/samples/'));
            $t->assign('sample_loading', myStep::setURL('static/images/loading_img.gif'));
            // 与原后台共用表格模板及路由地址，JSON编码适用于脚本字符串。
            $t->assign('sample_table_url', json_encode(myStep::setURL('api/myStep/data/?tbl=mystep_cms.cms_admin_cat'), JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT));
            $body = $this->ui->render($t);
        }
        return $this->ui->view('samples', ['sample_title'=>$index[$name]??'预览','sample_content'=>$body], ['samples'=>$links], ['selected'=>$body !== '']);
    },
];
