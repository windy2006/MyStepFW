<?PHP
class CMS extends myStep {
    public static $log = '';

    /**
     * 功能说明：构造CMS普通会员适配器；使用独立、抛异常的会员连接，避免认证事务失败进入页面错误回调。
     * 参数说明：无显式参数。
     * @return interface_user 普通会员适配器，不创建另一个控制器或自动迁移表
     * @throws LogicException 不支持的数据库方言
     * @throws RuntimeException 会员专用连接无法建立
     */
    public static function userProvider(): interface_user {
        class_exists(myDb::class); // 数据库驱动使用myDb文件声明的共享trait。
        require_once __DIR__.'/lib.php';
        global $db,$S,$info_app;
        if(($info_app['app']??'')==='CMS' && isset($S->db))$settings=$S;
        else{$settings=new myConfig(CONFIG.'config.php');$settings->merge(__DIR__.'/config.php');}
        $options=(array)$settings->db;$kind=strtolower((string)$options['type']);
        if($kind==='mysql'){
            $driver=new class($options['host'],$options['user'],$options['password'],$options['charset']) extends MySQL {
                /**
                 * 功能说明：会员专用连接失败时抛异常，以便事务回滚。
                 * @param string $str 内部说明，不向浏览器输出
                 * @param bool $exit 默认false；继承占位，本实现始终抛异常
                 * @return never 始终抛异常
                 * @throws RuntimeException 会员数据库连接或操作失败
                 */
                protected function error($str,$exit=false){throw new RuntimeException('会员数据库操作失败，请稍后重试。');}
            };$driver->connect(false,$options['name']);
        }
        elseif($kind==='sqlite')$driver=new class($options['host']) extends SQLite {
            /**
                 * 功能说明：会员专用连接失败时抛异常，以便事务回滚。
                 * @param string $str 内部说明，不向浏览器输出
                 * @param bool $exit 默认false；继承占位，本实现始终抛异常
                 * @return never 始终抛异常
                 * @throws RuntimeException 会员数据库连接或操作失败
                 */
            protected function error($str,$exit=false){throw new RuntimeException('会员数据库操作失败，请稍后重试。');}
        };
        else throw new LogicException('普通会员接口只支持MySQL或SQLite。');
        $connection=new myDb($driver);
        $pre=(string)$settings->db->pre;$driver=strtolower((string)$settings->db->type);
        // 数据和映射同在适配器连接内；作用域不依赖主机/库名，数据库搬迁后仍可使用原绑定。
        $realm='CMS:'.$pre;
        return new \app\CMS\MemberProvider($connection,$pre,$driver,$realm);
    }


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
                if($table==='users' && $explicit && in_array('password_enabled',$db->getFields($S->db->pre.'users'),true)) {
                    return $this->users()->passwordLogin((string)$usr,(string)$pwd,max(0,(int)$remember))===null?0:2;
                }


                $token = $explicit ? false : myAuth::readToken(r::cookie($cookie));
                $name = $explicit ? $usr : ($token['u'] ?? r::s($cookie));
                if(!is_string($name) || $name === '') continue;
                $db->build($S->db->pre.$table)->where('username', '=', $name);
                $record = $db->record();
                if(!$record) continue;
                if($table==='users' && $explicit && isset($record['password_enabled']) && !(bool)$record['password_enabled'])continue;
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
     * 功能说明：显式安装/迁移所有已配置子站的文章别名，保留原文章、正文及历史别名。
     *
     * 使用独立数据库连接，借用应用辅助函数所需上下文后在finally还原，不在浏览请求自动执行DDL。
     * 参数说明：无显式参数；仅在安装后、manager代码迁移或本机维护脚本中调用。
     * @return array 处理站点数、文章数及同步的独立栏目数
     * @throws RuntimeException 子站配置/文章表不存在、别名分配失败或连接失败
     */
    public static function upgradeArticleAliases(): array {
        require_once __DIR__.'/lib.php';
        $config=new myConfig(CONFIG.'config.php');$config->merge(__DIR__.'/config.php');
        class_exists(myDb::class);
        if(strtolower((string)$config->db->type)!=='mysql')throw new LogicException('CMS现有子站升级仅支持MySQL。');
        $driver=new class($config->db->host,$config->db->user,$config->db->password,$config->db->charset) extends MySQL {
            /**
             * 功能说明：将迁移连接的数据库错误转为异常，避免更新程序误报成功。
             * @param string $str 内部数据库操作说明，不向浏览器输出连接凭据
             * @param bool $exit 继承占位参数；本实现始终抛异常
             * @return never 始终抛异常
             * @throws RuntimeException 数据库迁移失败
             */
            protected function error($str,$exit=false){throw new RuntimeException('CMS文章链接迁移失败，请检查数据库权限和表结构。');}
        };
        $driver->connect(false,$config->db->name);$local=new myDb($driver);
        $keys=['db','S','website','web_info','news_cat_plat'];$saved=[];$present=[];
        foreach($keys as$key){$present[$key]=array_key_exists($key,$GLOBALS);$saved[$key]=$GLOBALS[$key]??null;}
        try{
            $GLOBALS['db']=$local;$GLOBALS['S']=$config;
            $GLOBALS['website']=$local->fetchAll('SELECT * FROM '.$local->quoteIdentifier($config->db->pre.'website').' ORDER BY web_id');
            $GLOBALS['news_cat_plat']=$local->fetchAll('SELECT * FROM '.$local->quoteIdentifier($config->db->pre.'news_cat').' ORDER BY cat_id');
            \app\CMS\installArticleAliases();$tables=$local->getTbls();$sites=$articles=$categories=0;
            foreach($GLOBALS['website']as$web){
                if(!preg_match('/\A[A-Za-z0-9_-]+\z/',(string)$web['idx']))throw new RuntimeException('无效的子站配置标识。');
                $file=__DIR__.'/website/config_'.$web['idx'].'.php';if(!is_file($file))throw new RuntimeException('缺少子站配置：'.$web['idx']);
                $site=new myConfig($file);$prefix=$site->value('db.pre',$config->db->pre);
                if(!in_array($prefix.'news_show',$tables,true))throw new RuntimeException('缺少子站文章表：'.$web['idx']);
                $GLOBALS['web_info']=$web;$config->db->pre_sub=$prefix;
                $rows=$local->fetchAll('SELECT news_id,web_id,cat_id,subject FROM '.$local->quoteIdentifier($prefix.'news_show').' WHERE web_id=? ORDER BY news_id',[(int)$web['web_id']]);
                foreach($rows as$row){$alias=\app\CMS\saveArticleAlias($row['news_id'],$row['web_id'],$row['subject']);if($alias===null)throw new RuntimeException('文章别名索引未建立。');if(\app\CMS\syncArticleCategory($row,$alias))$categories++;$articles++;}
                $sites++;
            }
            \app\CMS\deleteCache('news_cat');
            return ['sites'=>$sites,'articles'=>$articles,'categories'=>$categories];
        }finally{
            $local->close();foreach($keys as$key){if($present[$key])$GLOBALS[$key]=$saved[$key];else unset($GLOBALS[$key]);}
        }
    }

    /**
     * 功能说明：识别子站并合并当前前台配置。
     * 参数说明：无显式参数。
     * @return void 资源分支可直接输出并结束请求
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
     * 末尾执行流量统计；缺少User-Agent时按空字符串处理，不触发PHP8空值弃用提示
     */
    public function shutdown() {
        global $info_app, $db, $S;
        if(!is_file(PATH.'config.php') || is_null($db)) return false;
        $agent = strtolower(myReq::svr('HTTP_USER_AGENT') ?? '');
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
