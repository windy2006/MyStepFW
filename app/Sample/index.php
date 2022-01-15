<?PHP
//声明主模版类（直接调用默认模版设置）
$tpl = new myTemplate($tpl_setting, false);
//调整模版设置
$tpl_setting['name'] = 'sample';
//声明子模版类
$tpl_sub = new myTemplate($tpl_setting, false, true);
//为子模版变量赋值
$root = ROOT_WEB;
if(!defined('URL_FIX')) $root = $root.$info_app['app'].'/';
$tpl_sub->assign('root', $root);
$tpl_sub->assign('root2', ROOT_WEB);
$tpl_sub->assign('code', htmlentities(myFile::getLocal(__FILE__)));
//参数列表及模版循环赋值
foreach ([
    'info_app' => '当前app基本信息',
    'tpl_setting' => '模版设置变量，参见模版类【<a href="/Document/myTemplate" target="_blank">使用样例</a>】 【<a href="/Document/myTemplate/detail" target="_blank">方法说明</a>】',
    'tpl_cache' => '页面缓存设置，如设置为false则禁用缓存，参见模版类【<a href="/Document/myTemplate" target="_blank">使用样例</a>】 【<a href="/Document/myTemplate/detail" target="_blank">方法说明</a>】',
    'ms_setting' => '设置信息调用对象，详见【<a href="/console/setting/" target="_blank">框架设置</a>】',
    'mystep' => '核心控制函数，参见【<a href="/Document/myStep" target="_blank">使用样例</a>】 【<a href="/Document/myStep/detail" target="_blank">方法说明</a>】',
    'router' => '当前路由对象，参见【<a href="/Document/myRouter" target="_blank">使用样例</a>】 【<a href="/Document/myRouter/detail" target="_blank">方法说明</a>】',
    'db' => '数据库对象，参见MySQL【<a href="/Document/mysql" target="_blank">使用样例</a>】 【<a href="/Document/mysql/detail" target="_blank">方法说明</a>】',
    'cache' => '缓存对象，参见【<a href="/Document/myCache" target="_blank">使用样例</a>】 【<a href="/Document/myCache/detail" target="_blank">方法说明</a>】',
] as $k => $v) {
    $tpl_sub->setLoop('para', ['name'=>'$'.$k, 'comment'=>$v, 'detail'=>var_export($$k, true)]);
}
//编译子模版页面并赋值到主模版对应变量中
$tpl->assign('main', $tpl_sub->render('', false));
//主页面编译和显示由主框架代码完成