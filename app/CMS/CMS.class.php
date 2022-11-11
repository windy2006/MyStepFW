<?PHP
class CMS extends myStep {
    public static $log = '';

    /**
     * 登录
     * @param string $usr
     * @param string $pwd
     * @return int
     */
    public function login($usr='', $pwd='') {
        $result = 0;
        if(parent::ms_login($usr, $pwd)) {
            $result = 1;
            if(empty($usr)) $usr = $this->setting->gen->s_usr;
            r::s('ms_cms_op', $usr);
            r::s('ms_cms_group', 1);
        } else {
            if(!empty($usr) && !empty($pwd)) {
                $pwd = md5($pwd);
                $ms_user = $usr.chr(9).$pwd;
            } else {
                $ms_user = myReq::cookie('ms_cms_op');
            }
            if(!empty($ms_user)) {
                list($usr, $pwd) = explode(chr(9), $ms_user);
                global $db, $S;
                $db->build($S->db->pre.'sys_op')
                    ->field('group_id')
                    ->where('username','=',$usr)
                    ->where('password','=',$pwd);
                if($group_id=$db->result()) {
                    r::s('ms_cms_op', $usr);
                    r::s('ms_cms_group', $group_id);
                    $result = 1;
                } else {
                    $db->build($S->db->pre.'users')
                        ->field('group_id')
                        ->where('username','=',$usr)
                        ->where('password','=',$pwd);
                    if($group_id=$db->result()) {
                        r::s('ms_cms_user', $usr);
                        r::s('ms_cms_user_group', $group_id);
                        $result = 2;
                    }
                }
            }
        }
        if($result===1) r::s('sysop', 'y');
        return $result;
    }

    /**
     * 登出
     * @return bool
     */
    public function logout() {
        myReq::removeCookie('ms_cms_op');
        myReq::sessionEnd();
        return true;
    }

    /**
     * 日志
     */
    public static function log() {
        global $db, $S, $group_info, $id;
        $link = 'http://'.r::svr('HTTP_HOST').r::svr('REQUEST_URI');
        if(strpos($link, '_ok')) $link = r::svr('REFERER');
        if(!empty($id)) {
            $link = str_replace('&id=' . $id, '', $link);
            if (!preg_match('#/' . $id . '$#', $link)) $link .= '&id=' . $id;
        }
        if(preg_match('/(%[\w]{2})+/', $link)) $link = urldecode($link);
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
            myReq::setCookie('cms_cnt_visitor', $ip,60*60*24);
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
        $db->build($S->db->pre.'counter')->field('pv,iv,online')->where('date','f=','curdate()');
        if($record = $db->record()) {
            $pv = $record['pv'] + 1;
            $iv = $record['iv'] + $new_ip;
            $online = max($record['online'], $count_online);
        }else{
            $pv = 1;
            $iv = 1;
            $online = 1;
        }
        $db->build($S->db->pre.'counter')->field([
            'date' => 'curdate()',
            'pv' => $pv,
            'iv' => $iv,
            'online' => $online
        ]);
        return $db->replace();
    }

    /**
     * 权限检测接口
     * @param $idx
     * @return bool
     */
    public static function checkPower($idx) {
        global $ms_setting;
        $op = myReq::cookie('ms_cms_op');
        switch($idx) {
            case 'upload':
            case 'remove_ul':
                $flag = !is_null($op);
                break;
            default:
                $referer = myReq::server('http_referer');
                $flag = (strpos($referer, myReq::server('http_host')) > 0) || $ms_setting->upload->free_dl || r::s('sysop');
        }
        return $flag;
    }
}
