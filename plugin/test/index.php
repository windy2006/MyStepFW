<?php
//初始化程序一般在页面初始化程序之前执行，用于相关设置
$this->regModule("test_module", dirname(__FILE__)."/show.php");
$this->regTag('test_tag', function(){return 'Plugin tag test.';});
$this->setLanguage(['test_lng'=>'模版语言测试']);
$this->setFunction('page', function($page) {
    return str_replace('<title>', '<title>插件调整(可关闭) - ', $page);
});