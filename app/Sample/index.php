<?php
//当前应用路由规则检测
$router->checkRoute(CONFIG.'route.php', PATH.'route.php', $info_app['app']);
//声明主模版类（直接调用默认模版设置）
$tpl = new myTemplate($tpl_setting, false);
//调整模版设置
$tpl_setting['name'] = 'Sample';
//声明子模版类
$sub_tpl = new myTemplate($tpl_setting, false);
//开启模版PHP代码执行权限（默认关闭，视需要，不建议开启）
$sub_tpl->allow_script = true;
//为子模版变量赋值
$root = ROOT_WEB;
if(!defined('URL_FIX')) $root = $info_app['app'].$root;
$sub_tpl->assign('root', $root);
$sub_tpl->assign('root2', ROOT_WEB);
$sub_tpl->assign('code', htmlentities(myFile::getLocal(__FILE__)));
//参数列表及模版循环赋值
foreach ([
    'info_app' => '当前app基本信息',
    'tpl_setting' => '模版设置变量，参见模版类【<a href="Document/myTemplate" target="_blank">使用样例</a>】 【<a href="Document/myTemplate/detail" target="_blank">方法说明</a>】',
    'tpl_cache' => '页面缓存设置，如设置为false则禁用缓存，参见模版类【<a href="Document/myTemplate" target="_blank">使用样例</a>】 【<a href="Document/myTemplate/detail" target="_blank">方法说明</a>】',
    's' => '设置信息调用对象，详见【<a href="/manager/setting/" target="_blank">框架设置</a>】',
    'mystep' => '核心控制函数，参见【<a href="Document/myStep" target="_blank">使用样例</a>】 【<a href="Document/myStep/detail" target="_blank">方法说明</a>】',
    'router' => '当前路由对象，参见【<a href="Document/myRouter" target="_blank">使用样例</a>】 【<a href="Document/myRouter/detail" target="_blank">方法说明</a>】',
    'db' => '数据库对象，参见MySQL【<a href="Document/mysql" target="_blank">使用样例</a>】 【<a href="Document/mysql/detail" target="_blank">方法说明</a>】',
    'cache' => '缓存对象，参见【<a href="Document/myCache" target="_blank">使用样例</a>】 【<a href="Document/myCache/detail" target="_blank">方法说明</a>】',
] as $k => $v) {
    $sub_tpl->setLoop('para', ['name'=>'$'.$k, 'comment'=>$v, 'detail'=>var_export($$k, true)]);
}
//编译子模版页面并赋值到主模版对应变量中
$tpl->assign('main', $sub_tpl->display('', false));
//是否显示页面信息（页脚下显示执行信息，默认关闭，可能会影响页面整体效果，视需要开启）
$mystep->setting->show = true;
//主页面编译
$mystep->show($tpl);
//结束页面并显示
$mystep->end();