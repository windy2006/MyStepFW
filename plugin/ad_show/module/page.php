<?php
/** 当前插件类作用域内包含的后台处理；由manage鉴权后调用。 */

        $fields=['idx'=>['label'=>'广告位标识','required'=>true],'ad_client'=>['label'=>'客户'],'ad_mode'=>['label'=>'广告类型','type'=>'select','default'=>1,'options'=>self::modes(),'help'=>'选择素材形式。视频使用MP4/WebM资源地址；HTML5页面和第三方脚本仅填写受信来源。'],
         'ad_url'=>['label'=>'点击目标URL'],'ad_file'=>['label'=>'资源URL','help'=>'HTTP/HTTPS或站点根路径。图片可在下面上传。HTML/JS为管理员受信资源。'],
         'ad_mobile'=>['label'=>'移动端图片URL','help'=>'可选；屏幕宽度不超过767px时使用。'],'ad_poster'=>['label'=>'视频封面URL'],
         'ad_text'=>['label'=>'标题／图片说明'],'ad_desc'=>['label'=>'图文说明','type'=>'textarea'],'ad_cta'=>['label'=>'按钮文字','default'=>'了解详情'],'ad_label'=>['label'=>'广告标识','default'=>'广告'],
         'video_autoplay'=>['label'=>'静音自动播放','type'=>'checkbox'],'video_loop'=>['label'=>'循环播放','type'=>'checkbox'],'ad_level'=>['label'=>'优先级','type'=>'number','default'=>0],'add_date'=>['label'=>'开始日期','type'=>'date'],'exp_date'=>['label'=>'结束日期（含当天）','type'=>'date'],'active'=>['label'=>'启用','type'=>'checkbox'],'comment'=>['label'=>'备注','type'=>'textarea']];
        $action=$_POST['action']??'';
        if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'&&$action==='export'){$state=PAdShowSupport::state();$state['plugin']='ad_show';$state['items']=array_values($state['items']);PAdShowSupport::export($state);}
        if(($_GET['action']??'')==='stats'){
            $id=PAdShowSupport::integer($_GET['id']??0,1,2147483647);$rows=array_values(array_filter(PAdShowSupport::state()['events']??[],fn($r)=>$r['id']===$id));
            return PAdShowSupport::shell(PAdShowSupport::table(['time'=>'时间','id'=>'广告','mode'=>'类型','ip'=>'IP（来自REMOTE_ADDR）'],array_slice(array_reverse($rows),0,200)));
        }
        if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'&&$action==='upload')return self::upload();
        $html=self::crud('广告编辑',$fields,['id'=>'ID','idx'=>'广告位','ad_client'=>'客户','ad_text'=>'说明','ad_mode'=>'类型','ad_level'=>'优先级','active'=>'启用','view'=>'展示','ip_view'=>'每日会话展示累计','click'=>'点击','ip_click'=>'每日会话点击累计']);
        if(!isset($_GET['action']))$html.=PAdShowSupport::render('upload',['url'=>PAdShowSupport::url(),'csrf'=>PAdShowSupport::csrf()]);return $html.PAdShowSupport::render('editor_script',['src'=>ROOT_WEB.'plugin/ad_show/asset/admin.js']);
