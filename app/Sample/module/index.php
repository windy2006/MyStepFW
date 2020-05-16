<?PHP
//声明主模版类（直接调用默认模版设置）
$tpl = new \myTemplate($tpl_setting, false);
//调整模版设置
$tpl_setting['name'] = 'route2';
//声明子模版类(参数1为设置，参数2为缓存，参数3为允许php代码)
$tpl_sub = new \myTemplate($tpl_setting, false, true);
//为子模版变量赋值
$tpl_sub->assign('root', ROOT_WEB);
$tpl_sub->assign('code', htmlentities(\myFile::getLocal(__FILE__)));
$tpl_sub->assign('code2', htmlentities(\myFile::getLocal(PATH.'route.php')));
$tpl_sub->assign('m', htmlentities($m));
//编译子模版页面并赋值到主模版对应变量中
$tpl->assign('main', $tpl_sub->render('', false));
//是否显示页面信息（页脚下显示执行信息，默认关闭，可能会影响页面整体效果，视需要开启）
$mystep->setting->show = true;
//主页面编译
$mystep->show($tpl);
//结束页面并显示
$mystep->end();