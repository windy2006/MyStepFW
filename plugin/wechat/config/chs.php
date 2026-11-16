<?php
/** 微信网页插件设置；只由既有框架管理员设置页面编辑。 */
$definition = ['name' => '网页设置', 'list' => [
    'enabled' => ['name'=>'启用网页功能', 'describe'=>'填好下方账号和接口后选开启并保存；后台先测试微信API，通过才生效，失败保留原配置；开启期间保存也会重新测试', 'type'=>['radio',['开启（测试通过后生效）'=>'true','关闭'=>'false']]],
    'account_type' => ['name'=>'账号类型', 'describe'=>'按微信账号后台实际类型选择，不是小程序或开放平台网站应用；不同类型权限不同', 'type'=>['select',['订阅号'=>'subscription','服务号'=>'service']]],
    'verified' => ['name'=>'微信认证', 'describe'=>'只有账号已完成有效微信认证才选是；普通注册或管理员实名不等于微信认证', 'type'=>['radio',['是'=>'true','否'=>'false']]],
    'appid' => ['name'=>'应用ID', 'describe'=>'复制微信账号开发信息中的AppID，如wx1234567890abcdef（格式示例），不要填写gh_开头原始ID', 'type'=>['text',false,'18']],
    'secret' => ['name'=>'应用密钥', 'name2'=>'重复密钥', 'describe'=>'首次在两个框填写相同AppSecret；修改其他设置时两框留空保留密钥。还须设置服务器出口IP白名单', 'type'=>['password','_','128']],
    'origins' => ['name'=>'网站来源', 'describe'=>'每行如https://cms.example.com，不加末尾斜杠、路径或端口；微信JS接口安全域名对应填写cms.example.com', 'type'=>['textarea','',4]],
    'exclude_paths' => ['name'=>'排除路径前缀', 'describe'=>'每行如/user/security/，排除其下页面；区分大小写、不支持通配符；单独填/会排除全站', 'type'=>['textarea','',4]],
    'apis' => ['name'=>'允许接口', 'describe'=>'英文逗号或换行分隔，如checkJsApi,chooseImage,scanQRCode；不加wx.、括号或引号；接口清单见填写说明', 'type'=>['textarea','',4]],
    'card_enabled' => ['name'=>'已开通卡券', 'describe'=>'没有卡券业务选否；实际已开通且已认证才选是，还须允许卡券接口并由业务服务器生成专项参数', 'type'=>['radio',['是'=>'true','否'=>'false']]],
    'pay_enabled' => ['name'=>'微信支付', 'describe'=>'没有支付业务选否；仅已认证服务号可用，还须允许chooseWXPay并由业务服务器提供订单和签名', 'type'=>['radio',['是'=>'true','否'=>'false']]],
    'share_title' => ['name'=>'分享标题', 'describe'=>'固定标题如春季新品预约；各页标题不同时留空取页面title。需认证账号及分享接口', 'type'=>['text',false,'200']],
    'share_desc' => ['name'=>'分享简介', 'describe'=>'如查看商品详情并预约到店体验；留空取页面meta description，主要用于分享给朋友', 'type'=>['textarea','',3]],
    'share_image' => ['name'=>'分享封面', 'describe'=>'如https://cms.example.com/images/share.jpg，须公开可访问；留空取页面og:image，不填磁盘路径', 'type'=>['text',false,'2048']],
    'toolbar' => ['name'=>'显示快捷按钮', 'describe'=>'初次测试可选显示；仅显示已允许且客户端支持的扫码、选图、定位按钮，结果由应用接收', 'type'=>['radio',['显示'=>'true','隐藏'=>'false']]],
    'html' => ['name'=>'专属内容', 'describe'=>'可留空；仅微信内在body末尾显示HTML，如按钮和提示，不含页面外壳。填写说明有配套示例', 'type'=>['textarea','',6]],
    'ready_script' => ['name'=>'初始化脚本', 'describe'=>'可留空；填写JavaScript函数体，不加script标签；提供wx和wechat变量，须与允许接口及HTML配套', 'type'=>['textarea','',8]],
]];

return $definition;
