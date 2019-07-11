<?php
$ctrl = new myController();

// etag 测试，如不变更参数此页面将不会改变，多用于js、css和图片显示
//$ctrl->etag('test');

// 调用模板类
$template = $ctrl->getInstance('myTemplate', array(
    "name" => 'test_mycontroller',
    "path" => PATH.'data/template/',
    'path_compile' => CACHE.'/template/'.$info_app['app'].'/test/'
), false);

// 页面附加内容添加
$ctrl->setAddedContent('start', 'content - start');
$ctrl->setAddedContent('anywhere', 'content - anywhere');
$ctrl->setAddedContent('end', 'content - end');

$test = 'test variant';

// 程序钩子测试
$ctrl->setFunction('start', function(){echo 'Start function!<br />';})  // 由于页面起始程序已执行，所以本段不会显示
       ->setFunction('end', function($controller)use($test){echo 'End function! ('.$test.')<br />';});

// 读取语言包
$ctrl->setLanguagePack(PATH.'data/controller/language');

// API 测试
$ctrl->regApi('test', function(){
    return func_get_args();
});
$template->assign('api', $ctrl->runApi('test', [1, 2, 3], 'c'));

// 模块调用
$ctrl->regModule('test',PATH.'data/controller/module.php');
$ctrl->module('test', '$test');

// 模板标签注册
$ctrl->regTag('test', function(myTemplate &$template, &$att_list = array()){
    return myString::fromAny($att_list);
});

// 自定义URL生成
$ctrl->regUrl('test', function(){
    return 'http://localhost/'.implode('/', func_get_args());
});
$template->assign('url', $ctrl->url('test','aaa','bbb','ccc'));

// 登陆函数测试
$ctrl->regLog(function(){return 'login';}, function(){return 'logout';},function(){return 'chg_psw';});

echo '<div><b>登陆函数测试: </b></div>';
debug_show(
    $ctrl->login('name', 'pwd'),
    $ctrl->logout(),
    $ctrl->chg_psw('id', 'psw_old', 'psw_new')
);

// JS、CSS组合测试
$ctrl->addCSS(PATH.'data/controller/css/bootstrap.css')
       ->addCSS(PATH.'data/controller/css/bootstrap_hack.css')
       ->removeCSS(PATH.'data/controller/css/bootstrap.css');
$ctrl->addJS(PATH.'data/controller/js/jquery.js')
       ->addJS(PATH.'data/controller/js/jquery.addon.js')
       ->removeJS(PATH.'data/controller/js/jquery.js');
$template->assign('css', $ctrl->CSS(false));
$template->assign('js', $ctrl->JS(false));

// 插件测试
$ctrl->regPlugin(PATH.'data/controller/plugin/', $info);
$template->assign('plugin_info', myString::fromAny($info));
$ctrl->plugin();
$ctrl->module('plugin_module');

echo '<div>============== 以上为测试显示内容，非模版控制！===============</div><div>&nbsp;</div>'.chr(10).chr(10);

$ctrl->show($template);
