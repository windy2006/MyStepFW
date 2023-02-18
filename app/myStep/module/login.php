<?PHP
if(myReq::server('QUERY_STRING')=='out') {
    myReq::removeCookie('ms_auth');
    myReq::sessionEnd();
    myStep::info('login_logout', $app_root);
} elseif(myReq::session('ms_user')!='') {
    myStep::redirect($app_root);
} elseif(!is_null($captcha = myReq::post('captcha'))) {
    if(strtolower($captcha) == strtolower(myReq::session('captcha'))) {
        $usr = myReq::post('username');
        $pwd = myReq::post('password');
        if(($result = $mystep->ms_login($usr, $pwd))===true) {
            $expire = myReq::post('expire', 'int');
            myReq::setCookie('ms_auth', myStep::auth_code($usr, md5($pwd), $ms_setting->web->etag), 60*60*24*$expire);
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