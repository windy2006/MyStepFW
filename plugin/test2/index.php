<?php
//初始化程序一般在页面初始化程序之前执行，用于相关设置
$this->regModule("test_module", dirname(__FILE__)."/show.php");
$this->regTag('test_tag', function(){return 'Plugin tag test.';});
$this->setLanguage(['test_lng'=>'模版语言测试']);