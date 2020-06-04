<?PHP
$db->cache(0);
$tpl_setting['name'] = $info_app['path'][1];
if($tpl_setting['name']=='logout') {
    myReq::removeCookie('ms_cms_op');
    myReq::removeCookie('ms_cms_user');
    myReq::sessionEnd();
    myStep::info('login_logout', ROOT_WEB.$info_app['app']);
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
    myStep::redirect(ROOT_WEB.$info_app['app']);
}
if(myReq::check('post')) {
    $data = r::p('[ALL]');
    switch($tpl_setting['name']) {
        case 'login':
            if(strtolower($data['captcha']) == strtolower(r::s('captcha'))) {
                $usr = r::p('username');
                $pwd = r::p('password');
                if(($result = $mystep->login($usr, $pwd))===2) {
                    r::setCookie('ms_cms_user', $usr.chr(9).md5($pwd), $data['expire']);
                    myStep::info('login_ok', ROOT_WEB.$info_app['app']);
                }
            } else {
                myStep::info($mystep->getLanguage('login_error_captcha'));
            }
            myStep::info($mystep->getLanguage('login_error'), ROOT_WEB.$info_app['app'].'/user/login');
            break;
        case 'register':
            if(strtolower($data['captcha']) != strtolower(r::s('captcha'))) {
                myStep::info('login_error_captcha', ROOT_WEB.$info_app['app']);
            }
            unset($data['captcha']);
            $db->build($s->db->pre.'users')->field('user_id')->where('username','=',$data['username']);
            if($db->result()!==false) {
                myStep::info(sprintf($mystep->getLanguage('admin_user_detail_error2'), $data['username']));
            }
            $data['user_id'] = 0;
            $data['group_id'] = 2;
            $data['password'] = md5($data['password']);
            $data['reg_date'] = $s->info->time;
            $data['hash'] = md5(serialize($data));
            $db->build($s->db->pre.'users')->field($data);
            $db->insert();
            r::s('ms_cms_user', $data['username']);
            r::s('ms_cms_user_group', 1);
            r::setCookie('ms_cms_user', $data['username'].chr(9).$data['password'], 60*60*24);
            $url = (isHttps()?'https':'http').'://'.r::svr('HTTP_HOST').ROOT_WEB.$info_app['app'].'/user/login/'.$data['hash'];
            $mail = new myEmail();
            $mail->init($s->email->user, $s->gen->charset);
            $mail->from($s->email->user, $s->web->title);
            $mail->subject($mystep->getLanguage('page_registered'));
            $mail->content($url);
            $mail->to([$data['username'] => $data['email']]);
            $result = $mail->send(myConfig::o2a($s->email), false, 1);
            myStep::info('page_registered');
            break;
        case 'profile':
            unset($data['username'], $data['user_id']);
            if(!empty($data['password'])) {
                $data['password'] = md5($data['password']);
            } else {
                unset($data['password']);
            }
            $db->build($s->db->pre.'users')->field($data)->where('username','=', r::s('ms_cms_user'));
            $db->update();
            myStep::info('page_profile_changed');
            break;
    }
}
$mystep->checkCache($tpl);
$t = new myTemplate($tpl_setting);
if($tpl_setting['name']=='login' && isset($info_app['path'][2])) {
    $db->build($s->db->pre.'users')->where('hash','=', $info_app['path'][2]);
    if($record = $db->record()) {
        \r::s('ms_cms_user', $record['username']);
        \r::s('ms_cms_user_group', $record['group_id']);
        $db->build($s->db->pre.'users')->field(['hash'=>''])->where('hash','=', $info_app['path'][2]);
        $db->update();
        myStep::info('page_user_activate', ROOT_WEB.$info_app['app']);
    }
}
if($tpl_setting['name']=='profile') {
    $db->build($s->db->pre.'users')->where('username','=', r::s('ms_cms_user'));
    $t->assign($db->record());
}
