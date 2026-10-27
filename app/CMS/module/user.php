<?PHP
// 会员资料、授权草稿和CSRF均属于当前会话，不读写共享页面内容缓存。
header('Cache-Control: private, no-store');
if(isset($tpl) && $tpl instanceof myTemplate)$tpl->setCacheMode(false);
$db->cache(0);
$tpl_setting['name'] = $info_app['path'][1] ?? 'login';
if($tpl_setting['name'] === 'social') {
    if(!isset(myController::$modules['user_social']))myStep::header('404');
    $content='';myController::module('user_social','mystep,info_app,content');return;
}

if($tpl_setting['name']=='logout') {
    $mystep->logout();
    myStep::info('login_logout', ROOT_APP);
}
// 管理员和会员使用不同账号表；管理员的旧资料链接进入CMS后台，不落回前台首页。
$operator = r::s('ms_cms_op');
$hasOperator = is_string($operator) && $operator !== '';
if($tpl_setting['name'] === 'profile' && $hasOperator) {
    myStep::redirect(\app\CMS\getURL(\app\CMS\getProfilePath()), myReq::server('REQUEST_METHOD') === 'POST' ? '303' : '302');
}
if($tpl_setting['name']=='profile' && is_null(r::s('ms_cms_user'))) {
    $tpl_setting['name']='login';
}
if(!in_array($tpl_setting['name'],['login','register','profile'])
    ||
    $hasOperator
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
            // 标准会员扩展就绪后，昵称按文本存储；邮箱改变须重新验证第三方关联。
            $standardMember = in_array('password_enabled',$db->getFields($S->db->pre.'users'),true);
            if($standardMember) {
                $oldMember=$db->fetchOne('SELECT email,display_name FROM '.$db->quoteIdentifier($S->db->pre.'users').' WHERE username=?',[r::s('ms_cms_user')]);
                try {
                    $rawEmail=r::p('email','!')??'';$rawName=r::p('display_name','!')??($oldMember['display_name']?:r::s('ms_cms_user'));
                    if(!is_string($rawEmail)||!is_string($rawName))throw new InvalidArgumentException('邮箱或昵称类型无效。');
                    $data['email']=myUser::email($rawEmail);
                    $data['display_name']=myUser::displayName($rawName);
                } catch(InvalidArgumentException){myStep::info('error_para');}
                if(strcasecmp((string)$oldMember['email'],$data['email'])!==0)$data['email_verified_at']=0;
            }

            if($data['password'] !== '') {
                $data['password'] = myAuth::hashPassword($data['password']);
                if($standardMember)$data['password_enabled']=1;
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
$t = new myTemplate($tpl_setting, false);
$t->assign('captcha_url', myStep::setURL('captcha/?core=CMS'));
if($tpl_setting['name']=='login' && isset($info_app['path'][2])) {
    $db->build($S->db->pre.'users')->where('hash','=', $info_app['path'][2]);
    if($record = $db->record()) {
        \r::s('ms_cms_user', $record['username']);
        \r::s('ms_cms_user_version', hash('sha256', $record['password']));
        myAuth::renewSession();
        \r::s('ms_cms_user_group', $record['group_id']);
        $activation=['hash'=>''];if(isset($record['email_verified_at']))$activation['email_verified_at']=time();
        $db->build($S->db->pre.'users')->field($activation)->where('hash','=', $info_app['path'][2]);
        $db->update();
        myStep::info('page_user_activate', ROOT_APP);
    }
}
if($tpl_setting['name']=='profile') {
    $db->build($S->db->pre.'users')->where('username','=', r::s('ms_cms_user'));
    $profile=$db->record();if(!$profile)myStep::info('error_para');
    $t->setIf('member_name',array_key_exists('display_name',$profile));
    foreach(['username','email','display_name'] as $key)$t->assign($key,htmlspecialchars((string)($profile[$key]??($key==='display_name'?$profile['username']:'')),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8',false));

}

$t->assign('social_login','');
if(isset(myController::$modules['user_social']) && class_exists('plugin_social',false))$t->assign('social_login',plugin_social::buttons($mystep->users(),$tpl_setting['name']==='profile'?'bind':'login'));
