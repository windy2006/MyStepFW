<?PHP
//初始化程序一般在页面初始化程序之前执行，用于相关设置
$this->regModule("plugin_module", __DIR__."/show.php");
$this->regTag('plugin_tag', function() {return 'Plugin tag test.';});
$this->setLanguage(['plugin_lng'=>'模版语言测试']);