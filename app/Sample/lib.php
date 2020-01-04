<?php
//本文件为当前应用的预加载脚本，在route.php中声明
namespace app\sample;

//设置无需尝试数据库连接
global $no_db;
$no_db = 'y';

//以下为各类接口的执行函数

//应用api对应的函数，直接返回数据，框架会自动根据参数调整为所需格式
function api() {
    global $s;
    $para = func_get_args();
    //需要注意 $para 中的最后一个参数可能是格式变量（如json或xml）
    $result = \myConfig::o2a($s);
    $result = $result['setting'];
    foreach($para as $v) {
        if(isset($result[$v])) {
            $result = $result[$v];
        } else {
            break;
        }
    }
    return $result;
}

//自定义路由的处理函数，代码说明参考index.php，需要注意的是相关全局变量需要声明后才能使用
function route($para) {
    global $mystep, $router, $db, $cache, $s, $info_app, $tpl_setting, $tpl_cache;
    $tpl = new \myTemplate($tpl_setting, false);
    $tpl_setting['name'] = 'route';
    $sub_tpl = new \myTemplate($tpl_setting, false);
    $sub_tpl->allow_script = true;
    $sub_tpl->assign('code', htmlentities(\myFile::getLocal(__DIR__.'/lib.php')));
    $sub_tpl->assign('code2', htmlentities(\myFile::getLocal(PATH.'route.php')));
    $sub_tpl->assign('root', ROOT_WEB);
    $tpl->assign('main', $sub_tpl->display('', false));
    $mystep->setting->show = true;
    $mystep->show($tpl);
    $mystep->end();
}

//预检测函数
function preCheck($times = 5) {
    $counter = \myReq::c('counter');
    if(empty($counter)) $counter = 0;
    if($counter>$times) {
        if(!class_exists('myStep')) require_once APP.'myStep.class.php';
        \myStep::info("一分钟内访问不能超过 {$times} 次！");
        exit;
    } else {
        \myReq::setCookie('counter', ++$counter, 60);
        return '我是preCheck的返回值';
    }
}

//通过预检测函数后所执行的函数
function routeTest() {
    $paras = func_get_args();
    $first = reset($paras);
    $last = end($paras);
    $counter = \myReq::c('counter');
    if(!empty($counter)) {
        echo '一分钟内已访问 '.$counter.' 次<br /><br />';
    } else {
        echo '请尝试连续刷新，超过3次后将会报错<br /><br />';
    }
    echo '当前URL路径 ： '.$first.'<br /><br />';
    echo '上一个函数的返回值 ： '.$last.'<br />';
}