<?PHP
if(r::svr('QUERY_STRING')=='out') {
    r::removeCookie('ms_user');
    r::sessionEnd();
    myStep::info('login_logout', $app_root);
} elseif(r::s('ms_user')!='') {
    myStep::redirect($app_root);
} elseif(!is_null($captcha = r::p('captcha'))) {
    if(strtolower($captcha) == strtolower(r::s('captcha'))) {
        $usr = r::p('username');
        $pwd = r::p('password');
        if(($result = $mystep->login($usr, $pwd))===true) {
            r::setCookie('ms_user', $usr.chr(9).md5($pwd), 60*60*24);
            $url = r::s('url');
            if(empty($url)) {
                $url = $app_root;
            } else {
                r::s('url', null);
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