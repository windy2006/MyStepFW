<?PHP
class CMS extends myStep {
    public static $log = '';

    /**
     * 登录
     * @param string $usr
     * @param string $pwd
     * @return int
     */
    public function login($usr='', $pwd='', $remember = 86400) {
        global $db, $S;
        $explicit = $usr !== '' || $pwd !== '';
        if(parent::ms_login($usr, $pwd)) {
            r::s('ms_cms_op', $this->setting->gen->s_usr);
            r::s('ms_cms_group', 1);
            r::s('sysop', 'y');
            if($explicit) $this->rememberAdmin($remember);
            return 1;
        }
        $cache_mode = $db->cache();
        $db->cache(0);
        try {
            foreach([['sys_op','ms_cms_op','ms_cms_group',1], ['users','ms_cms_user','ms_cms_user_group',2]] as $type) {
                list($table, $cookie, $group, $result) = $type;
                $token = $explicit ? false : myAuth::readToken(r::cookie($cookie));
                $name = $explicit ? $usr : ($token['u'] ?? r::s($cookie));
                if(!is_string($name) || $name === '') continue;
                $db->build($S->db->pre.$table)->where('username', '=', $name);
                $record = $db->record();
                if(!$record) continue;
                $scope = 'cms:'.$S->db->name.':'.$S->db->pre.$table;
                $version = hash('sha256', $record['password']);
                $active = !$explicit && r::s($cookie) === $name && r::s($cookie.'_version') === $version;
                $valid = $explicit ? myAuth::verifyPassword($pwd, $record['password'])
                    : ($active || myAuth::verifyToken(r::cookie($cookie), $name, $record['password'], $scope, $S->web->etag));
                if(!$valid) continue;
                if($explicit && myAuth::needsUpgrade($record['password'])) {
                    $hash = myAuth::hashPassword($pwd);
                    $db->build($S->db->pre.$table)->field(['password'=>$hash])->where('username','=',$name)->where('password','=',$record['password']);
                    if($db->update() !== 1) {
                        $db->build($S->db->pre.$table)->where('username', '=', $name);
                        $current = $db->record();
                        if(!$current || !myAuth::verifyPassword($pwd, $current['password'])) continue;
                        $hash = $current['password'];
                    }
                    $record['password'] = $hash;
                }
                if(r::s($cookie) !== $name) myAuth::renewSession();
                r::s($cookie, $name);
                r::s($cookie.'_version', hash('sha256', $record['password']));
                r::s($group, $table === 'users' && !empty($record['hash']) ? 1 : $record['group_id']);
                if($result === 1) r::s('sysop', 'y');
                if($explicit) {
                    if($remember > 0) r::setCookie($cookie, myAuth::makeToken($name, $record['password'], $scope, $S->web->etag, $remember), min(31536000, (int)$remember));
                    else r::removeCookie($cookie);
                }
                return $result;
            }
        } finally {
            $db->cache($cache_mode);
        }
        if(!$explicit) {
            foreach(['ms_cms_op', 'ms_cms_user'] as $cookie) {
                r::removeSession($cookie);
                r::removeSession($cookie.'_version');
                if(r::cookie($cookie) !== null) r::removeCookie($cookie);
            }
            r::removeSession('ms_cms_group');
            r::removeSession('ms_cms_user_group');
            r::removeSession('sysop');
        }
        return 0;
    }

    /**
     * 登出
     * @return bool
     */
    public function logout() {
        myReq::removeCookie('ms_cms_op');
        myReq::removeCookie('ms_cms_user');
        myReq::removeCookie('ms_auth');
        myReq::sessionEnd();
        return true;
    }

    /**
     * 日志
     */
    public static function log() {
        global $db, $S, $group_info, $id;
        $link = (isHttps()?'https':'http').'://'.r::svr('HTTP_HOST').r::svr('REQUEST_URI');
        if(strpos($link, '_ok')) $link = r::svr('REFERER') ?: $link;
        if(!empty($id)) {
            if (!preg_match('#/' . preg_quote((string)$id, '#') . '/?$#', parse_url($link, PHP_URL_PATH) ?? '')) $link = \app\CMS\queryURL($link, ['id'=>$id]);
        }
        if(strlen($link)>250) $link = s::substr($link, 0, 250);
        $db->build($S->db->pre.'sys_log')
            ->field([
                'id' => 0,
                'user' => r::s('ms_cms_op'),
                'group' => $group_info['name'],
                'time' => $S->info->time,
                'link' => $link,
                'comment' => self::$log
            ]);
        $db->insert();
    }

    /**
     * URL 重定位重写 （补充日志记录）
     * @param string $url
     * @param string $code
     */
    public static function redirect($url = '', $code = '302') {
        if(!empty(self::$log)) self::log();
        parent::redirect($url, $code);
    }

    /**
     * 预执行程序
     */
    public function preload() {
        global $S, $website, $web_info, $info_app;
        app\CMS\installCheck($info_app['path'][0] ?? '');
        if(($website = \app\CMS\getCache('website'))===false) {
            myStep::info('error_para');
        }
        if(($web_info = \app\CMS\checkVal($website, 'domain', myReq::server('HTTP_HOST'), true))===false) {
            $web_info = \app\CMS\checkVal($website, 'web_id', 1);
        }
        if(($info_app['path'][0]??'')!==$S->web->path_admin) {
            $db_pre = $S->db->pre;
            $S->merge(PATH.'website/config_'.$web_info['idx'].'.php');
            $S->db->pre_sub = $S->db->pre;
            $S->db->pre = $db_pre;
        } else {
            $S->db->pre_sub = $S->db->pre;
        }
        if(isset($info_app['path'][1]) && $info_app['path'][0]=='asset') {
            $file = APP.$info_app['app'].'/asset/'. (count($info_app['path'])==3?$info_app['path'][1]:$S->template->style).'/'.end($info_app['path']);
            if(is_file($file)) {
                myController::file($file);
                exit;
            }
        }
    }

    /**
     * 末尾执行流量统计
     */
    public function shutdown() {
        global $info_app, $db, $S;
        if(!is_file(PATH.'config.php') || is_null($db)) return false;
        $agent = strtolower(myReq::svr('HTTP_USER_AGENT'));
        if(
            strpos($agent, 'spider')!==false ||
            strpos($agent, 'bot')!==false ||
            (!empty($info_app['path']) && in_array($info_app['path'][0],['api','module','ms_language','ms_setting','captcha','pack']))
        ) return false;
        $ip = myReq::ip();
        $new_ip = 0;
        $cnt_visitor = myReq::c('cms_cnt_visitor');
        if(empty($cnt_visitor) || $cnt_visitor!=$ip) {
            myReq::setCookie('cms_cnt_visitor', $ip, 60*60*24);
            $new_ip = 1;
        }
        $db->build($S->db->pre.'user_online')->field('ip')->where('ip','=',$ip);
        if($new_ip==1 && $db->result()) {
            $new_ip = 0;
        } else {
            $db->build('[reset]');
        }
        $db->build($S->db->pre.'user_online')->field('count(distinct ip)');
        $count_online = $db->result();
        $table=$db->quoteIdentifier($S->db->pre.'counter');
        return $db->execute('INSERT INTO '.$table.' (`date`,pv,iv,online) VALUES (CURDATE(),1,?,?) '
            .'ON DUPLICATE KEY UPDATE pv=pv+1,iv=iv+VALUES(iv),online=GREATEST(online,VALUES(online))',
            [$new_ip,max(1,(int)$count_online)]);

    }

    /**
     * 权限检测接口
     * @param $idx
     * @return bool
     */
    public static function checkPower($idx) {
        global $ms_setting;
        $op = myReq::session('sysop');
        switch($idx) {
            case 'upload':
            case 'remove_ul':
                $flag = $op === 'y';
                break;
            default:
                $referer = myReq::server('http_referer');
                $flag = mySecurity::trustedReferer($referer) || $ms_setting->upload->free_dl || r::s('sysop') === 'y';
        }
        return $flag;
    }
}
