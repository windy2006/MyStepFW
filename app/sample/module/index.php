<?php
//声明主模版类（直接调用默认模版设置）
$tpl = new \myTemplate($tpl_setting, false);
//调整模版设置
$tpl_setting['name'] = 'route2';
//声明子模版类
$sub_tpl = new \myTemplate($tpl_setting, false);
//开启模版PHP代码执行权限（默认关闭，视需要，不建议开启）
$sub_tpl->allow_script = true;
//为子模版变量赋值
$sub_tpl->assign('code', htmlentities(\myFile::getLocal(__FILE__)));
$sub_tpl->assign('code2', htmlentities(\myFile::getLocal(PATH.'route.php')));
$sub_tpl->assign('m', htmlentities($m));
//编译子模版页面并赋值到主模版对应变量中
$tpl->assign('main', $sub_tpl->display('', false));
//是否显示页面信息（页脚下显示执行信息，默认关闭，可能会影响页面整体效果，视需要开启）
$mystep->setting->show = true;
//主页面编译
$mystep->show($tpl);
//结束页面并显示
$mystep->end();