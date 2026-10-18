<?PHP
// 退出只处理通过框架CSRF校验的POST；兼容旧URL中的out及新表单隐藏字段。
// 使用解析后的参数，避免查询串路由或附加参数导致QUERY_STRING不再恰好为out。
if(myReq::server('REQUEST_METHOD') === 'POST' && (myReq::get('out') !== null || myReq::post('out') !== null)) {
    $mystep->logout();
    myStep::info('login_logout', $app_root);
} elseif(myReq::session('ms_user')!='') {
    myStep::redirect($app_root);
} elseif(!is_null($captcha = myReq::post('captcha'))) {
    if(strtolower($captcha) == strtolower(myReq::session('captcha'))) {
        $usr = myReq::post('username');
        $pwd = myReq::post('password', '!');
        if(($result = $mystep->ms_login($usr, $pwd))===true) {
            $expire = myReq::post('expire', 'int');
            $mystep->rememberAdmin(60*60*24*max(0, min(365, $expire)));
            $url = myReq::session('url');
            if(empty($url)) {
                $url = $app_root;
            } else {
                myReq::session('url', null);
            }
            myStep::info('login_ok', $url);
        }
    } else {
        myStep::info($mystep->getLanguage('login_error_captcha'));
    }
    myStep::info($mystep->getLanguage('login_error'), $app_root.'login');
}
$tpl_setting['name'] = 'login';
$t = new myTemplate($tpl_setting);
$t->assign('path_admin', $app_root);
$mystep->show($t);
$mystep->end();