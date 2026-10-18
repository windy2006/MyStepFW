<?PHP
$db->cache(0);
$tpl_setting['name'] = $info_app['path'][1] ?? 'login';
if($tpl_setting['name']=='logout') {
    $mystep->logout();
    myStep::info('login_logout', ROOT_APP);
}
if($tpl_setting['name']=='profile' && is_null(r::s('ms_cms_user'))) {
    $tpl_setting['name']='login';
}
if(!in_array($tpl_setting['name'],['login','register','profile'])
    ||
    !is_null(r::s('ms_cms_op'))
    ||
    ($tpl_setting['name']!=='profile' && (!is_null(r::s('ms_cms_user_group'))))
) {
    myStep::redirect(ROOT_APP);
}
if(myReq::check('post')) {
    $data = r::p('[ALL]');
    $data['password'] = r::p('password', '!') ?? '';
    if(!is_string($data['password'])) myStep::info('error_para');
    $captcha=r::p('captcha','!'); $expected=r::s('captcha');
    $captcha_ok=is_string($captcha) && $captcha!=='' && is_string($expected) && $expected!=='' && strcasecmp($captcha,$expected)===0;
    switch($tpl_setting['name']) {
        case 'login':
            if($captcha_ok) {
                $usr = r::p('username');
                $pwd = r::p('password', '!');
                if(($result = $mystep->login($usr, $pwd, max(0, (int)$data['expire'])))!==0) {
                    myStep::info('login_ok', ROOT_APP);
                }
            } else {
                myStep::info($mystep->getLanguage('login_error_captcha'));
            }
            myStep::info($mystep->getLanguage('login_error'), \app\CMS\getURL('user/login'));
            break;
        case 'register':
            if(!$captcha_ok) {
                myStep::info('login_error_captcha', ROOT_APP);
            }
            if(!is_string($data['password']) || $data['password'] !== r::p('password_r', '!')) myStep::info('checkform_err_password');
            $data = array_intersect_key($data, array_flip(['username', 'email', 'password']));
            $db->build($S->db->pre.'users')->field('user_id')->where('username','=',$data['username']);
            if($db->result()!==false) {
                myStep::info(sprintf($mystep->getLanguage('admin_user_detail_error2'), $data['username']));
            }
            $data['user_id'] = 0;
            $data['group_id'] = 2;
            $data['password'] = myAuth::hashPassword($data['password']);
            $data['reg_date'] = $S->info->time;
            $data['hash'] = bin2hex(random_bytes(16));
            $db->build($S->db->pre.'users')->field($data);
            if($db->insert()===false) myStep::info('error_para');
            r::s('ms_cms_user', $data['username']);
            r::s('ms_cms_user_group', 1);
            r::s('ms_cms_user_version', hash('sha256', $data['password']));
            myAuth::renewSession();
            $url = (isHttps()?'https':'http').'://'.r::svr('HTTP_HOST').\app\CMS\getURL('user/login/'.rawurlencode($data['hash']));
            $mail = new myEmail();
            $mail->init($S->email->user, $S->gen->charset);
            $mail->from($S->email->user, $S->web->title);
            $mail->subject($mystep->getLanguage('page_registered'));
            $mail->content($url);
            $mail->to([$data['username'] => $data['email']]);
            $result = $mail->send(myConfig::o2a($S->email), false, 1);
            myStep::info('page_registered');
            break;
        case 'profile':
            if($data['password'] !== '' && $data['password'] !== r::p('password_r', '!')) myStep::info('checkform_err_password');
            $data = array_intersect_key($data, array_flip(['email', 'password']));
            if($data['password'] !== '') {
                $data['password'] = myAuth::hashPassword($data['password']);
            } else {
                unset($data['password']);
            }
            $db->build($S->db->pre.'users')->field($data)->where('username','=', r::s('ms_cms_user'));
            $db->update();
            if(isset($data['password'])) {
                r::removeCookie('ms_cms_user');
                r::s('ms_cms_user_version', hash('sha256', $data['password']));
                myAuth::renewSession();
            }
            myStep::info('page_profile_changed');
            break;
    }
}
$mystep->checkCache($tpl);
$t = new myTemplate($tpl_setting);
$t->assign('captcha_url', myStep::setURL('captcha/?core=CMS'));
if($tpl_setting['name']=='login' && isset($info_app['path'][2])) {
    $db->build($S->db->pre.'users')->where('hash','=', $info_app['path'][2]);
    if($record = $db->record()) {
        \r::s('ms_cms_user', $record['username']);
        \r::s('ms_cms_user_version', hash('sha256', $record['password']));
        myAuth::renewSession();
        \r::s('ms_cms_user_group', $record['group_id']);
        $db->build($S->db->pre.'users')->field(['hash'=>''])->where('hash','=', $info_app['path'][2]);
        $db->update();
        myStep::info('page_user_activate', ROOT_APP);
    }
}
if($tpl_setting['name']=='profile') {
    $db->build($S->db->pre.'users')->where('username','=', r::s('ms_cms_user'));
    $t->assign($db->record());
}
