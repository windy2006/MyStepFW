<?PHP
if(myReq::server('QUERY_STRING')=='out') {
    myReq::removeCookie('ms_user');
    myReq::sessionEnd();
    myStep::info('login_logout', $app_root);
} elseif(myReq::session('ms_user')!='') {
    myStep::redirect($app_root);
} elseif(!is_null($captcha = myReq::post('captcha'))) {
    if(strtolower($captcha) == strtolower(myReq::session('captcha'))) {
        $usr = myReq::post('username');
        $pwd = myReq::post('password');
        if(($result = $mystep->login($usr, $pwd))===true) {
            myReq::setCookie('ms_user', $usr.chr(9).md5($pwd), 60*60*24);
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