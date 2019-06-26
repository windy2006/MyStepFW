<?php
function routeTest($var) {
    $para = end(func_get_args());
    $counter = myReq::c('counter');
    if(!empty($counter)) {
        echo '一分钟内已访问 '.$counter.' 次<br />';
    } else {
        echo '请尝试连续刷新<br />';
    }
    echo 'Route Test - '.$var.'<br />';
    echo 'Pass by Parameter - '.$para.'<br />';
}

function perCheck($times = 5) {
    $counter = myReq::c('counter');
    if(empty($counter)) $counter = 0;
    if($counter>$times) {
        if(!class_exists('myStep')) require_once APP.'myStep.class.php';
        myStep::info("一分钟内访问不能超过 {$times} 次！");
        exit;
    } else {
        myReq::setCookie('counter', ++$counter, 60);
        return 'parameter';
    }
}