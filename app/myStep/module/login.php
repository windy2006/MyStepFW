<?PHP
if(r::svr('QUERY_STRING')=='out') {
    r::removeCookie('ms_user');
    r::sessionEnd();
    myStep::info('login_logout', $app_root);
} elseif(r::s('ms_user')!='') {
    myStep::redirect($app_root);
} elseif(!is_null($captcha = r::p('captcha'))) {
    $err_no = 0;
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
        } else {
            $err_no = 2;
        }
    } else {
        $err_no = 1;
    }
    myStep::info($mystep->getLanguage('login_error').'(Error No: '.$err_no.')', $app_root.'login');
}
$tpl_setting['name'] = 'login';
$t = new myTemplate($tpl_setting);
$mystep->show($t);
$mystep->end();