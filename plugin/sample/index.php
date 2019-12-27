<?php
//初始化程序一般在页面初始化程序之前执行，用于相关设置
require_once(dirname(__FILE__)."/class.php");
$this->regModule("test1", dirname(__FILE__)."/show.php");
$this->regTag('test_tag', function(){return 'Plugin tag test.';});
$this->setLanguage(['test_lng'=>'模版语言测试']);
$this->setFunction('page', 'plugin_test::setPage');