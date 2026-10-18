<?PHP
require 'inc.php';
if(r::g('out') !== null) {
    $mystep->logout();
    myStep::info('login_logout', $path_admin);
} elseif(r::s('ms_cms_op')!='') {
    CMS::redirect($path_admin);
} elseif(!is_null($captcha = r::p('captcha'))) {
    $err_no = 0;
    if(strtolower($captcha) == strtolower(r::s('captcha'))) {
        $usr = r::p('username');
        $pwd = r::p('password', '!');
        if(($result = $mystep->login($usr, $pwd))===1) {
            myStep::info('login_ok', $path_admin);
        } else {
            $err_no = 2;
        }
    } else {
        $err_no = 1;
    }
    myStep::info($mystep->getLanguage('login_error').'(Error No: '.$err_no.')', $path_admin.'login');
}
$tpl_setting['name'] = 'login';
$t = new myTemplate($tpl_setting);
$t->assign('captcha_url', myStep::setURL('captcha/?core=CMS'));
$t->assign('path_admin', $path_admin);
$content = $mystep->render($t);
$tpl->assign('path_admin', $path_admin);
$tpl->assign('list_func', '');
$tpl->assign('list_web', '');
