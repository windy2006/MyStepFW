<?php
/** 当前插件类作用域内包含的后台处理；由manage鉴权后调用。 */

        $fields=['name'=>['label'=>'名称','required'=>true],'app'=>['label'=>'限制应用（留空所有已绑定宿主）'],'page'=>['label'=>'路径匹配','help'=>'留空全部；匹配实际info_app.route，例如/产品/*。独立域名通常不带应用名前缀，不含查询串。'],
            'http_method'=>['label'=>'请求方法','type'=>'select','default'=>'ALL','options'=>self::methods()],'position'=>['label'=>'执行阶段','type'=>'select','options'=>[0=>'start：会话与服务准备后',1=>'end：普通页面结束时']],
            'order'=>['label'=>'顺序（大值先执行）','type'=>'number','default'=>0],'code'=>['label'=>'PHP代码（不含PHP标签）','type'=>'code','help'=>'管理员受信代码，可使用$mystep、$ms_setting、$db、$cache、$info_app。'],'active'=>['label'=>'启用','type'=>'checkbox']];
        $html=self::crud('扩展代码',$fields,['id'=>'ID','name'=>'名称','app'=>'应用','page'=>'路径','http_method'=>'请求方法','position'=>'阶段','order'=>'顺序','active'=>'启用']);
        if(!isset($_GET['action']))$html.=PXcodeSupport::table(['time'=>'错误时间','id'=>'代码编号','message'=>'异常'],array_reverse(PXcodeSupport::state()['logs']));return $html;
