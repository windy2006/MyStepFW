<?PHP
//声明命名空间格式为“app\应用名”
namespace app\test;
echo $GLOBALS['the_order']++.' - app/test/lib.php<br />';

//定义命名空间下的常量（命名空间下不支持变量）
const test_var = 'some value';

//定义命名空间下的方法
function test() {
    echo 'hello world - test2!';
}

function api() {
    if(ob_get_level()>0) ob_clean();
    return [
        'aaa',
        'bbb',
        'ccc',
        'ddd' => 'xxx',
        'sub' => [1,2,3]
    ];
}

function logCheck() {
    return true;
}

function route() {
    global $mystep, $tpl_setting, $tpl_cache, $info_app, $ms_setting, $db, $cache, $router;
    echo 'lib.php -> route()<br />';
}

function f1($p) {
    echo '<br /><br />以下是各执行步骤中的输出文字：<br />'.chr(10);
    echo '111'.chr(10);
    return 'lib.php -> f1() - '.$p; //如返回 false，路由将在本函数后终止
}

function f2($p) {
    echo '222'.chr(10);
    return 'lib.php -> f2() - '.$p; //如返回 false，路由将在本函数后终止
}

function f3($p) {
    echo 'Final';
    echo '<br /><br />以下是 当前调用路径 和 各执行步骤反馈给最终执行函数的返回值，作为最终执行函数的两个输入变量：<br />'.chr(10);
    debug_show(func_get_args());
    echo '<br /><br />'.chr(10);
}