<?php
$path_admin = '/admin_cms/';
if(isset($_GET['out'])) {
    r::removeCookie('ms_user');
    r::sessionEnd();
    myStep::info('login_logout', $path_admin);
} elseif(r::s('ms_user')!='') {
    myStep::redirect($path_admin);
} elseif(myReq::check('post')) {
    $captcha = strtolower(r::p('captcha'));
    $err_no = 0;
    if(!empty($captcha) && $captcha == strtolower(r::s('captcha'))) {
        $usr = r::p('username');
        $pwd = md5(r::p('password'));
        if($s->gen->s_usr==r::p('username') && $s->gen->s_pwd==$pwd) {
            r::setCookie('ms_user', $usr.chr(9).$pwd, 60*60*24);
            r::s('ms_user', $usr);
            myStep::info('login_ok', $path_admin);
        } else {
        	$err_no = $s->gen->s_usr==r::p('username') ? 1 : 2;
        }
    } else {
        $err_no = 1;
    }
    myStep::info($mystep->getLanguage('login_error').'(Error No: '.$err_no.')', $path_admin.'login');
}

$tpl_setting['name'] = 'login';
$t = new myTemplate($tpl_setting, false);
$mystep->show($t);
$mystep->end();