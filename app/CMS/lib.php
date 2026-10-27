<?PHP
namespace app\CMS;

function installCheck($module = '') {
    global $tpl_setting, $tpl_cache;
    if(!is_file(PATH.'config.php') || $module=='install') {
        $tpl_setting = array(
            'name' => 'main',
            'path' => PATH.'install/template',
            'style' => '',
            'path_compile' => CACHE.'template/CMS/install/'
        );
        $tpl_cache = false;
        include(__DIR__.'/install/index.php');
        exit;
    }
    return true;
}

function getAttachment() {
    global $S, $info_app;
    if(!\CMS::checkPower('download')) {
        \myController::header('404');
    }
    $file = array_pop($info_app['path']);
    $record = \myAttachment::find(FILE, $file, $S->upload->path_mode);
    if($record) {
        $source = $record['file'];
        if(in_array(strtolower(pathinfo($source, PATHINFO_EXTENSION)), ['png','jpg','jpeg']) && is_null(\myReq::get('org'))) {
            $cache = CACHE.'app/CMS/img/'.hash('sha256', $source.filemtime($source).serialize((array)$S->watermark)).'.'.strtolower(pathinfo($source, PATHINFO_EXTENSION));
            if(!is_file($cache)) {
                $img = new \myImg($source);
                $size = $img->getSize();
                if($size[0]>$S->watermark->thumb) {
                    $height = ceil($S->watermark->thumb * ($size[1]/$size[0]));
                    $img->thumb($S->watermark->thumb, $height, $cache);
                    $img->destroy();
                    unset($img);
                } else {
                    \myFile::copy($source, $cache);
                }
                if(strpos($S->watermark->mode, '2')!==false && file_exists(ROOT.$S->watermark->img)) {
                    \myFile::rename($cache, $cache.'.bak');
                    $img = new \myImg($cache.'.bak');
                    $img->watermark(ROOT.$S->watermark->img, $S->watermark->position, $cache, [
                        'rate' => $S->watermark->img_rate,
                        'alpha' => $S->watermark->alpha,
                        'font' => ROOT.$S->watermark->txt_font,
                        'fontsize' => $S->watermark->txt_fontsize,
                        'fontcolor' => $S->watermark->txt_fontcolor,
                        'bgcolor' => $S->watermark->txt_bgcolor,
                    ]);
                    \myFile::del($cache.'.bak');
                    $img->destroy();
                    unset($img);
                }
            }
            \CMS::file($cache);
        } else {
            \CMS::download($file);
        }
    } else {
        \myController::header('404');
    }
}

/**
 * 功能说明：按当前CMS身份选择用户名入口的应用内相对路径，管理员进入CMS后台，会员进入个人资料页。
 * 参数说明：无显式参数；读取已由CMS登录流程校验的ms_cms_op会话及web.path_admin配置。
 * @return string CMS应用内相对路径；页面经getURL或前端应用前缀组装，本函数不登录、不授予权限
 * @example $url = \app\CMS\getProfilePath(); // 供前台用户名链接及旧profile入口重定向使用
 */
function getProfilePath() {
    global $S;
    $operator = \myReq::session('ms_cms_op');
    $path = is_string($operator) && $operator !== ''
        ? trim((string)($S->web->path_admin ?? 'admin_cms'), '/').'/'
        : 'user/profile';
    return $path;
}

/**
 * 功能说明：读取当前CMS用户的公开身份及用户名入口，不返回账号口令或认证凭据。
 * 参数说明：无显式参数。
 * @return array 用户组公开信息、name/group及profile_path；访客name为空，管理员地址指向CMS后台
 */
function getUserInfo() {
    global $db, $S;
    $db_cache = $db->cache();
    $db->cache(0);
    $user_group = \app\CMS\getCache('user_group');
    $operator = \r::s('ms_cms_op');
    if(is_string($operator) && $operator !== '') {
        $user_info = \app\CMS\checkVal($user_group, 'group_id', 3);
        $user_info['group'] = $user_info['name'];
        $user_info['name'] = \r::s('ms_cms_op');
    } elseif(!is_null(\r::s('ms_cms_user_group'))) {
        $user_info = \app\CMS\checkVal($user_group, 'group_id', \r::s('ms_cms_user_group'));
        $user_info['group'] = $user_info['name'];
        $user_info['name'] = \r::s('ms_cms_user');
        $member=$db->fetchOne('SELECT * FROM '.$db->quoteIdentifier($S->db->pre.'users').' WHERE username=?',[\r::s('ms_cms_user')]);
        if(is_array($member)&&is_string($member['display_name']??null)&&$member['display_name']!=='')$user_info['name']=$member['display_name'];

    } else {
        $user_info = null;
        if(is_null($user_info)) {
            $user_info = \app\CMS\checkVal($user_group, 'group_id', 1);
            $user_info['group'] = $user_info['name'];
            $user_info['name'] = '';
        }
    }
    $user_info['profile_path'] = getProfilePath();
    $db->cache($db_cache);
    return $user_info;
}

function logCheck($show = true) {
    $user = \r::s('ms_cms_op');
    if(empty($user)) {
        if($show) \myStep::getModule('login');
        return false;
    }
    return true;
}

function checkVal($para, $key, $val, $fuzz = false) {
    if(!is_array($para)) return false;
    for($i=0,$m=count($para); $i<$m; $i++) {
        foreach($para[$i] as $k => $v) {
            if($key != $k) continue;
            if($v == $val) return $para[$i];
            if($fuzz && strpos($v, $val)!==false) return $para[$i];
        }
    }
    return false;
}

/**
 * 功能说明：为guide风格取得当前子站可阅读的文章目录，按栏目树顺序分组，供首页和前后篇导航共用。
 *
 * 只读取当前站点、当前用户阅读等级允许且已生效、未过期的文章；不缓存个人权限结果。
 * 栏目必须来自当前请求已经生成的平铺树；无法定位到本子站根栏目的文章不混入导航。
 * 参数说明：无显式参数；使用当前请求的db、S、web_info、user_info、news_cat_plat。
 * @return array{groups:array,articles:array} groups含name、url及articles；articles含id、title、summary、url、cat_id
 * @throws \RuntimeException 当前站点文章表或数据库读取失败
 */
function guideContents(): array {
    global $db, $S, $web_info, $user_info, $news_cat_plat;
    $web_id = (int)$web_info['web_id'];
    $db->build($S->db->pre_sub.'news_show')->field('news_id,cat_id,subject,describe,view_lvl,active,expire')
        ->where('web_id', 'n=', $web_id)->order('news_id');
    $records = $db->records();
    primeArticleAliases($records);
    $by_cat = []; $now = time();
    foreach($records as $row) {
        if((int)$row['view_lvl'] > (int)($user_info['view_lvl'] ?? 0)) continue;
        $active = $row['active'] ? strtotime($row['active']) : false;
        $expire = $row['expire'] ? strtotime($row['expire']) : false;
        if(($active > 0 && $active > $now) || ($expire > 0 && $expire < $now)) continue;
        $by_cat[(int)$row['cat_id']][] = [
            'id'=>(int)$row['news_id'], 'cat_id'=>(int)$row['cat_id'],
            'title'=>$row['subject'], 'summary'=>$row['describe'],
            'url'=>getLink($row+['web_id'=>$web_id])
        ];
    }
    $categories = [];
    foreach($news_cat_plat as $cat) if((int)$cat['web_id'] === $web_id) $categories[(int)$cat['cat_id']] = $cat;
    $groups = []; $ordered = [];
    foreach($categories as $id=>$cat) {
        if(empty($by_cat[$id])) continue;
        $root = $id; $seen = [];
        while(isset($categories[$root]) && (int)$categories[$root]['pid'] !== 0) {
            if(isset($seen[$root])) continue 2;
            $seen[$root] = true; $root = (int)$categories[$root]['pid'];
        }
        if(!isset($categories[$root])) continue;
        if(!isset($groups[$root])) $groups[$root] = ['id'=>$root, 'name'=>$categories[$root]['name'], 'url'=>getLink($categories[$root], 'catalog'), 'articles'=>[]];
        foreach($by_cat[$id] as $article) { $groups[$root]['articles'][] = $article; $ordered[] = $article; }
    }
    return ['groups'=>array_values($groups), 'articles'=>$ordered];
}

function getData($idx) {
    $result = '{"error":1,"message":"Login First!"}';
    if(logCheck(false)) {
        if(($result = getCache($idx))===false) {
            $result = '{"error":2,"message":"Data Missing!"}';
        }
    }
    return $result;
}

function buildCache($idx, $cache_para) {
    $detail = '<?PHP
return '.var_export($cache_para, true).';';
    $theFile = CACHE.'/app/CMS/'.$idx.'.php';
    \myFile::saveFile($theFile, $detail);
    @chmod($theFile, 0666);
    return true;
}

function deleteCache($idx) {
    return \myFile::del(CACHE.'/app/CMS/'.$idx.'.php');
}

function checkCache($idx) {
    return is_file(CACHE.'/app/CMS/'.$idx.'.php');
}

function getCache($idx) {
    if(!checkCache($idx)) buildList($idx);
    if(checkCache($idx)) {
        return include(CACHE.'/app/CMS/'.$idx.'.php');
    } else {
        return false;
    }
}

function buildList($idx) {
    global $db, $S, $mystep;
    $mystep->setInstance();
    $cache_para = array();
    $db_cache = $db->cache();
    $db->cache(0);
    switch($idx) {
        case 'admin_cat':
            $theList = array();
            $plat = array();
            $db->build($S->db->pre.'admin_cat')
               ->where('pid', 'n=', 0)
               ->order('order', 1)->order('id', 0);
            $db->select();
            while($record=$db->getRS()) {
                \myString::htmlTrans($record);
                $theList[] = $record;
            }
            for($i=0,$m=count($theList); $i<$m; $i++) {
                $plat[] = $theList[$i];
                $theList[$i]['sub'] = array();
                $db->build('[reset]');
                $db->build($S->db->pre.'admin_cat')
                    ->where('pid', 'n=', $theList[$i]['id'])
                    ->order('order', 1)->order('id', 0);
                $db->select();
                while($record=$db->getRS()) {
                    \myString::htmlTrans($record);
                    $theList[$i]['sub'][] = $record;
                    $plat[] = $record;
                }
            }
            $cache_para[$idx] = $theList;
            $cache_para[$idx.'_plat'] = $plat;
            break;
        case 'news_cat':
            $db->build($S->db->pre.'news_cat')
               ->field('cat_id,web_id,pid,name,idx,show,order,type,link,layer,view_lvl,prefix,keyword,comment')
               ->order('web_id')->order('cat_id');
            $cat = $db->records();
            $cache_para = categoryTree($cat);
            break;
        case 'link':
            $link_txt = array();
            $link_img = array();
            $db->build($S->db->pre.'links')
                ->order('level', 1)->order('id', 0);
            $db->select();
            while($record=$db->getRS()) {
                if(empty($record['image'])) {
                    $link_txt[] = $record;
                } else {
                    $link_img[] = $record;
                }
            }
            $cache_para['txt'] = $link_txt;
            $cache_para['img'] = $link_img;
            break;
        case 'website':
            $db->build($S->db->pre.'website')
                ->order('web_id', 0);
            $db->select();
            while($record=$db->getRS()) {
                $cache_para[] = $record;
            }
            break;
        case 'sys_group':
        case 'user_group':
            $theIdx = preg_replace('/[a-z]+_/i', '', $idx);
            $db->build($S->db->pre.$idx)
               ->order($theIdx.'_id', 0);
            $db->select();
            while($record=$db->getRS()) {
                $cache_para[] = $record;
            }
            break;
        default:
            $db->build($S->db->pre.$idx)
                ->order('id', 0);
            $db->select();
            if($db->checkError()) return false;
            while($record=$db->getRS()) {
                $cache_para[] = $record;
            }
    }
    $db->free();
    $db->cache($db_cache);
    if($cache_para) buildCache($idx, $cache_para);
    return $cache_para ? true : false;
}

function setCatList($list, $layer=1, $order='') {
    $result = [];
    for($i=0,$m=count($list); $i<$m; $i++) {
        $list[$i]['layer'] = $layer;
        if(!empty($order)) {
            $list[$i]['order'] = $order.'.'.($i+1);
        } else {
            $list[$i]['order'] = $i+1;
        }
        if(isset($list[$i]['sub'])) {
            $sub = setCatList($list[$i]['sub'], $layer+1, $list[$i]['order']);
            unset($list[$i]['sub']);
            $result[] = $list[$i];
            $result = array_merge($result, $sub);
        } else {
            $result[] = $list[$i];
        }
    }
    return $result;
}

/**
 * 按字段查找记录，兼容关联键和稀疏数组；缺字段的记录直接跳过。
 * @param array $list 记录集合
 * @param string $key 字段名
 * @param mixed $val 匹配值，沿用历史宽松比较
 * @return array|false 首条匹配记录；不存在返回 false
 */
function getPara($list, $key, $val) {
    if(!is_array($list)) return false;
    foreach($list as $row) if(is_array($row) && isset($row[$key]) && $row[$key]==$val) return $row;
    return false;
}

/**
 * 从平面栏目记录构建有序树，不依赖父栏目的编号先后；孤儿和环在内存中断开为根。
 * @param array $rows 数据库栏目记录，含 cat_id、pid、web_id、order
 * @return array 有序根栏目，子节点写入 sub，layer 根据实际树深度重算；不修改数据库
 */
function categoryTree($rows) {
    $nodes = []; $done = []; $children = [];
    foreach($rows as $row) if(is_array($row) && isset($row['cat_id']) && $row['cat_id']>0) {
        unset($row['sub']); $nodes[(int)$row['cat_id']] = $row;
    }
    foreach($nodes as $id=>&$row) {
        $pid = (int)($row['pid'] ?? 0);
        if(!isset($nodes[$pid]) || $pid===$id || ($row['web_id'] ?? 0)!=($nodes[$pid]['web_id'] ?? 0)) $pid=0;
        $row['pid']=$pid;
    }
    unset($row);
    foreach($nodes as $id=>$row) {
        $seen=[]; $current=$id;
        while($current && !isset($done[$current])) {
            if(isset($seen[$current])) { $nodes[$current]['pid']=0; break; }
            $seen[$current]=true; $current=$nodes[$current]['pid'];
        }
        foreach($seen as $key=>$unused) $done[$key]=true;
    }
    foreach($nodes as $id=>$row) $children[$row['pid']][]=$id;
    foreach($children as &$ids) usort($ids, function($a,$b) use (&$nodes) {
        $x=$nodes[$a]; $y=$nodes[$b];
        $web=($x['web_id'] ?? 0)<=>($y['web_id'] ?? 0);
        return $web ?: (version_compare((string)($x['order'] ?? 0),(string)($y['order'] ?? 0)) ?: ($a<=>$b));
    });
    unset($ids);
    $make=function($pid,$layer) use (&$make,&$nodes,&$children) {
        $result=[];
        foreach($children[$pid] ?? [] as $id) {
            $row=$nodes[$id]; $row['layer']=$layer;
            if(isset($children[$id])) $row['sub']=$make($id,$layer+1);
            $result[]=$row;
        }
        return $result;
    };
    return $make(0,1);
}

/**
 * 获取从根到当前栏目的路径，遇到缺失父级或循环时返回空数组。
 * @param array $rows 平面栏目列表
 * @param int|string $id 当前栏目编号
 * @return array 根到当前栏目的记录列表；0、缺失或无效关系返回 []
 */
function categoryPath($rows,$id) {
    $map=[]; $seen=[]; $path=[];
    foreach($rows as $row) if(isset($row['cat_id'])) $map[(int)$row['cat_id']]=$row;
    $id=(int)$id;
    while($id>0) {
        if(isset($seen[$id]) || !isset($map[$id])) return [];
        $seen[$id]=true; $path[]=$map[$id]; $id=(int)$map[$id]['pid'];
    }
    return array_reverse($path);
}

/**
 * 统一分隔符、去掉空标签及纯数字标签，每篇文章的重复标签只计一次。
 * @param string $text 逗号/中文逗号/分号分隔的标签文本
 * @return array 去重后的标签；保留含数字的名称、单字中文和引号，最多50个字符
 */
function normalizeTags($text) {
    $tags=[];
    foreach(explode(',',str_replace(['，','；',';'],',',(string)$text)) as $tag) {
        $tag=trim(str_replace(' ','_',$tag),'_'."\t\r\n");
        if($tag==='' || preg_match('/\A[\d.]+\z/D',$tag)) continue;
        $tag=mb_substr($tag,0,50,'UTF-8');
        $tags[$tag]=$tag;
    }
    return array_values($tags);
}

function removeNewsCache($news_id, $web_id=0) {
    global $db, $S, $web_info;
    if(!$S->gen->cache_page) return false;
    if($web_id==0) $web_id=$web_info['web_id'];
    $db->build('[reset]');
    $db->build($S->db->pre_sub.'news_show')
        ->field('add_date,pages')
        ->where('news_id', 'n=', $news_id);
    $db->build($S->db->pre.'news_cat', array(
            'mode' => 'left',
            'field' => 'cat_id'
        ))->field('cat_idx')
        ->where('news_id', 'n=', $news_id);
    list($add_date, $page_count, $cat_idx)=$db->record();
    if(is_null($cat_idx) || is_null($add_date)) return false;
    $file_idx = CACHE.'app/CMS/'.$web_info['idx'].'/'.date('Y/md',strtotime($add_date)).'/'.$news_id;
    f::del($file_idx.'.html');
    for($i=2; $i<=$page_count; $i++) {
        f::del($file_idx.'_'.$i.'.html');
    }
    return true;
}

/**
 * 生成功能路径的完整应用地址，统一适配绑定域名、子目录及三种路由模式。
 * @param string|null $path 相对应用的路径；null 使用当前已解析路径，空串返回应用入口
 * @param array $query 查询参数字典，由 queryURL 统一编码（默认：[]）
 * @return string 可用于跳转或模板的原始 URL；写入 HTML 属性时需再做 HTML 转义
 */
function getURL($path=null, $query=[]) {
    global $info_app;
    if($path === null) $path = implode('/', $info_app['path'] ?? []);
    $prefix = defined('URL_FIX') ? '' : ($info_app['app'] ?? 'CMS').'/';
    return queryURL(\myStep::setURL(ROOT_WEB.$prefix.ltrim($path, '/')), $query);
}

/**
 * 合并 URL 查询参数，保留 index.php? 路由首段及锚点，避免重复问号或丢失路径。
 * @param string $url 原始 URL，可为完整地址或已转换的应用地址
 * @param array $params 待覆盖的查询参数；null 删除对应参数（默认：[]）
 * @return string 以 RFC 3986 编码参数后的 URL；不对完整 URL 作 HTML 转义
 */
function queryURL($url, $params=[]) {
    $url = (string)$url;
    $hash = '';
    if(($at = strpos($url, '#')) !== false) { $hash = substr($url, $at); $url = substr($url, 0, $at); }
    $parts = explode('?', $url, 2);
    $path = $parts[0];
    $query = $parts[1] ?? '';
    $route = '';
    if(preg_match('#/index\.php$#', $path) && $query !== '' && strpos(explode('&', $query, 2)[0], '=') === false) {
        $parts = explode('&', $query, 2); $route = $parts[0]; $query = $parts[1] ?? '';
    }
    parse_str($query, $values);
    foreach($params as $key => $value) {
        if($value === null) unset($values[$key]);
        else $values[$key] = $value;
    }
    $query = http_build_query($values, '', '&', PHP_QUERY_RFC3986);
    return $path.($route !== '' ? '?'.$route.($query === '' ? '' : '&'.$query) : ($query === '' ? '' : '?'.$query)).$hash;
}

/**
 * 计算分页位置并生成当前功能的分页地址；保留筛选条件，不依赖 GET 参数顺序。
 * @param int $total 记录数，负数按零处理
 * @param int $page 当前页，超出范围时归入有效页（默认：1）
 * @param int|null $page_size 每页记录数，空值或非正值回退 20（默认：20）
 * @param string|array $qstr 附加筛选参数，覆盖当前 GET 中同名参数（默认：''）
 * @return array 分页信息、起始记录偏移、每页条数；零记录时当前页仍为 1
 */
function getPageList($total, $page=1, $page_size=20, $qstr='') {
    $total = max(0, (int)$total);
    $page_size = (int)$page_size > 0 ? (int)$page_size : 20;
    $page_count = (int)ceil($total/$page_size);
    $page = min(max(1, (int)$page), max(1, $page_count));
    if(!is_array($qstr)) parse_str(ltrim((string)$qstr, '?&'), $qstr);
    $params = array_replace($_GET, $qstr);
    $page_arr = ['total'=>$total, 'page_current'=>$page, 'page_count'=>$page_count];
    foreach(['first'=>1, 'prev'=>max(1, $page-1), 'next'=>min(max(1, $page_count), $page+1), 'last'=>max(1, $page_count)] as $name => $number) {
        $page_arr['link_'.$name] = getURL(null, array_replace($params, ['page'=>$number]));
    }
    return [$page_arr, ($page-1)*$page_size, $page_size];
}

/**
 * 将 CMS 链接转换为绝对地址，供 RSS、Atom、站点地图等无页面基准的输出使用。
 * @param string $url 应用相对路径、根路径、协议相对或完整 HTTP(S) URL
 * @return string 带当前协议和目标主机的绝对 URL；调用方负责 XML/HTML 转义
 */
function absoluteURL($url) {
    $scheme = \isHttps() ? 'https:' : 'http:';
    if(strpos($url, '//') === 0) return $scheme.$url;
    if(preg_match('#^https?://#i', $url)) return $url;
    if(substr($url, 0, 1) !== '/') $url = getURL($url);
    return $scheme.'//'.\myReq::svr('HTTP_HOST').$url;
}

/**
 * 功能说明：规范化可读文章别名，保留中文，移除路径/查询分隔符并避开框架入口。
 * @param string $title 原始标题，允许HTML实体；按UTF-8处理
 * @return string 不含路径分隔符、最长120字符的标题别名
 */
function articleSlug($title): string {
    $title = html_entity_decode(strip_tags((string)$title), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $title = preg_replace('/[\x00-\x20\x7f\p{Z}\/\\\\?#%&=]+/u', '-', $title) ?? '';
    $title = trim(preg_replace('/-+/u', '-', $title) ?? '', "-. ");
    $title = mb_substr($title, 0, 120, 'UTF-8');
    if($title==='') $title='文章';
    static $reserved=null;
    if($reserved===null){$reserved=['api','asset','module','index.php','robots.txt','favicon.ico','admin_cms','sitemap','rss','atom','captcha','csrf','ms_setting','ms_language','error'];
        foreach(glob(APP.'myStep/module/*')?:[] as $file)$reserved[]=strtolower(pathinfo($file,PATHINFO_FILENAME));
        foreach(glob(APP.'CMS/module/*.php')?:[] as $file)$reserved[]=strtolower(basename($file,'.php'));
        foreach(glob(APP.'*',GLOB_ONLYDIR)?:[] as $file)$reserved[]=strtolower(basename($file));
    }
    global $S;
    if(ctype_digit($title)||in_array(strtolower($title),$reserved,true)||strtolower($title)===strtolower((string)($S->web->path_admin??''))||is_file(ROOT.$title)||is_dir(ROOT.$title))$title='文章-'.$title;
    $extension=strtolower(pathinfo($title,PATHINFO_EXTENSION));
    $static_extensions=explode(',',strtolower((string)($S->gen->static??'jpg,jpeg,png,gif,svg,ico,css,js,json,woff,woff2,ttf,webp,avif,pdf')));
    if(preg_match('/\A(?:php\d*|phtml|html?|js|css|xml|txt|json)\z/i',$extension)||in_array($extension,$static_extensions,true))$title=preg_replace('/\.([^.]*)$/','-$1',$title);
    return $title;
}
/**
 * 功能说明：返回当前数据库/前缀的文章别名请求缓存；用于批量读取与发布后失效。
 * @param bool $reset true清空当前连接缓存并重新检查表，默认false
 * @return array 引用返回的本请求缓存，不跨请求或用户保存
 */
function &articleAliasState($reset=false): array {
    global $db,$S;static $all=[];
    $key=(is_object($db)?spl_object_id($db):0).':'.($S->db->name??'').':'.($S->db->pre??'');
    if($reset||!isset($all[$key]))$all[$key]=['exists'=>null,'ids'=>[],'aliases'=>[]];
    return $all[$key];
}
/**
 * 功能说明：取得共享文章别名表名；旧站未迁移时返回null，不在阅读请求中建表。
 * @param bool $refresh 是否刷新存在性检查，默认false
 * @return string|null 已存在的未引用表名；连接未就绪或尚未安装时为null
 */
function articleAliasTable($refresh=false): ?string {
    global $db,$S;$state=&articleAliasState($refresh);
    if(!is_object($db)||empty($S->db->pre))return null;
    $table=$S->db->pre.'news_alias';
    if($state['exists']===null)$state['exists']=in_array($table,$db->getTbls(),true);
    return $state['exists']?$table:null;
}
/**
 * 功能说明：安装文章别名索引；仅由安装/显式升级调用，历史别名与当前别名分开保存。
 * 参数说明：无显式参数，使用当前CMS数据库连接和全局前缀。
 * @return void 创建表后刷新请求缓存；不改写文章数据
 * @throws RuntimeException 数据库不支持所需事务或执行DDL失败
 */
function installArticleAliases(): void {
    global $db,$S;$name=$db->quoteIdentifier($S->db->pre.'news_alias');
    $sql='CREATE TABLE IF NOT EXISTS '.$name.' (web_id INTEGER NOT NULL, alias_hash CHAR(64) NOT NULL, alias VARCHAR(255) NOT NULL, news_id INTEGER NOT NULL, current_news_id INTEGER NULL, PRIMARY KEY(web_id,alias_hash), UNIQUE(web_id,current_news_id))';
    if(strtolower((string)$S->db->type)==='mysql')$sql.=' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin';
    $db->execute($sql);articleAliasState(true);
}
/**
 * 功能说明：批量预读文章的规范别名，避免列表/订阅中逐文章查询。
 * @param array $records 含news_id及可选web_id的文章记录；缺站点时使用当前子站
 * @return void 请求内记录缺失结果；每200个未读取的ID执行一次索引查询
 */
function primeArticleAliases(array $records): void {
    global $db,$web_info;$table=articleAliasTable();if($table===null)return;
    $state=&articleAliasState();$groups=[];
    foreach($records as $r){$id=(int)($r['news_id']??0);$web=(int)($r['web_id']??$web_info['web_id']??1);
        if($id>0&&!array_key_exists($web.':'.$id,$state['ids'])){$groups[$web][$id]=$id;$state['ids'][$web.':'.$id]=null;}}
    foreach($groups as $web=>$ids)foreach(array_chunk(array_values($ids),200)as$chunk){
        $rows=$db->fetchAll('SELECT news_id,alias FROM '.$db->quoteIdentifier($table).' WHERE web_id=? AND current_news_id IN ('.implode(',',array_fill(0,count($chunk),'?')).')',array_merge([$web],$chunk));
        foreach($rows as$r)$state['ids'][$web.':'.(int)$r['news_id']]=$r['alias'];
    }
}
/**
 * 功能说明：读取文章的当前规范别名；不会在普通阅读请求中创建别名。
 * @param int $id 文章编号
 * @param int $web 文章所属子站编号
 * @return string|null 当前别名；旧站未迁移/文章不存在时为null
 */
function getArticleAlias($id,$web): ?string {
    primeArticleAliases([['news_id'=>(int)$id,'web_id'=>(int)$web]]);$s=&articleAliasState();return $s['ids'][(int)$web.':'.(int)$id]??null;
}
/**
 * 功能说明：按当前或历史别名解析文章，仅在指定子站内查询。
 * @param string $alias 已完成URL解码的单个路径段
 * @param int $web 子站编号
 * @return int|null 文章编号；未匹配时为null
 */
function resolveArticleAlias($alias,$web): ?int {
    global $db;$table=articleAliasTable();if($table===null)return null;$state=&articleAliasState();$key=(int)$web.':'.hash('sha256',(string)$alias);
    if(!array_key_exists($key,$state['aliases'])){
        $row=$db->fetchOne('SELECT alias,news_id FROM '.$db->quoteIdentifier($table).' WHERE web_id=? AND alias_hash=?',[(int)$web,hash('sha256',(string)$alias)]);
        $state['aliases'][$key]=$row!==false&&$row['alias']===(string)$alias?(int)$row['news_id']:null;
    }
    return $state['aliases'][$key];
}
/**
 * 功能说明：为新增/改标题的文章分配唯一中文别名；重名加文章ID后缀，旧别名永久保留。
 *
 * 同站点以数据库互斥锁串行分配，事务和唯一索引避免并发重名；删除文章后别名不分配给他文。
 * @param int $id 已存在的文章编号
 * @param int $web 子站编号
 * @param string $title 新标题
 * @return string|null 当前规范别名；尚未安装别名表时为null，维持旧站可读
 * @throws RuntimeException 等待发布锁超时或别名分配失败
 */
function saveArticleAlias($id,$web,$title): ?string {
    global $db,$S;$table=articleAliasTable();if($table===null)return null;$id=(int)$id;$web=(int)$web;
    if($id<1||$web<0)throw new \InvalidArgumentException('Invalid article identity');
    $base=articleSlug($title);$quoted=$db->quoteIdentifier($table);$mysql=strtolower((string)$S->db->type)==='mysql';$lock='cms_alias:'.substr(hash('sha256',($S->db->name??'').':'.$table.':'.$web),0,52);
    if($mysql&&(int)$db->fetchValue('SELECT GET_LOCK(?,10)',[$lock])!==1)throw new \RuntimeException('文章链接分配繁忙，请重试。');
    try{$alias=$db->transaction(function($tx)use($quoted,$web,$id,$base,$title){
        for($n=0;$n<1000;$n++){
            $candidate=$n===0?$base:$base.'-'.$id.($n===1?'':'-'.$n);$hash=hash('sha256',$candidate);
            $row=$tx->fetchOne('SELECT alias,news_id FROM '.$quoted.' WHERE web_id=? AND alias_hash=?',[$web,$hash]);
            if($row!==false&&((int)$row['news_id']!==$id||$row['alias']!==$candidate))continue;
            if($row===false)$tx->execute('INSERT INTO '.$quoted.'(web_id,alias_hash,alias,news_id,current_news_id) VALUES(?,?,?,?,NULL)',[$web,$hash,$candidate,$id]);
            $tx->execute('UPDATE '.$quoted.' SET current_news_id=NULL WHERE web_id=? AND news_id=?',[$web,$id]);
            $tx->execute('UPDATE '.$quoted.' SET current_news_id=? WHERE web_id=? AND alias_hash=?',[$id,$web,$hash]);
            // 兼容旧版以空格转下划线后取标题hash的入口；不覆盖已归属别人的地址。
            foreach(array_unique([(string)$title,str_replace(' ','_',(string)$title)])as$old){
                if($old===$candidate||$old===''||ctype_digit($old)||mb_strlen($old,'UTF-8')>255||preg_match('~[/\\\\?#\x00-\x1f]~',$old))continue;
                $oldHash=hash('sha256',$old);$owner=$tx->fetchOne('SELECT news_id FROM '.$quoted.' WHERE web_id=? AND alias_hash=?',[$web,$oldHash]);
                if($owner===false)$tx->execute('INSERT INTO '.$quoted.'(web_id,alias_hash,alias,news_id,current_news_id) VALUES(?,?,?,?,NULL)',[$web,$oldHash,$old,$id]);
            }
            return $candidate;
        }
        throw new \RuntimeException('无法分配唯一文章链接。');
    });}finally{if($mysql)$db->fetchValue('SELECT RELEASE_LOCK(?)',[$lock]);}
    $state=&articleAliasState();$state['ids']=[];$state['aliases']=[];return $alias;
}
/**
 * 功能说明：文章规范地址变化后，同步确实指向该文章的独立栏目链接。
 * @param array $article 含news_id、web_id、cat_id的文章记录
 * @param string $alias 已分配的规范别名
 * @param string|null $prefix 目标站文章表前缀，后台跨站编辑时显式传入；默认当前站前缀
 * @return bool 栏目链接是否实际变化；普通列表、外部链接和多文章栏目不修改
 */
function syncArticleCategory(array $article,$alias,$prefix=null): bool {
    global $db,$S,$news_cat_plat,$website;$id=(int)$article['news_id'];$web=(int)$article['web_id'];
    // 发布后栏目缓存可能尚未重建；同步操作读取真实记录，避免遗漏刚创建的独立栏目。
    $category_table=$db->quoteIdentifier($S->db->pre.'news_cat');
    $cat=$db->fetchOne('SELECT * FROM '.$category_table.' WHERE cat_id=? AND web_id=?',[(int)$article['cat_id'],$web]);
    if(!$cat||$cat['link']==='')return false;
    $host=parse_url($cat['link'],PHP_URL_HOST);$site=getPara($website,'web_id',$web);$domain=explode(',',(string)($site['domain']??''))[0];
    if($host!==null&&$host!==false&&strcasecmp($host,explode(':',$domain)[0])!==0)return false;
    $route=articleRoutePath($cat['link']);$matches=resolveArticleAlias($route,$web)===$id||preg_match('~^article/(?:[^/]+/)?'.preg_quote((string)$id,'~').'/?$~',$route);
    if(!$matches)return false;
    if((int)$db->fetchValue('SELECT COUNT(*) FROM '.$db->quoteIdentifier(($prefix??$S->db->pre_sub).'news_show').' WHERE cat_id=? AND web_id=?',[(int)$cat['cat_id'],$web])!==1)return false;
    if((int)$db->fetchValue('SELECT COUNT(*) FROM '.$category_table.' WHERE pid=?',[(int)$cat['cat_id']])>0)return false;
    $link=rawurlencode((string)$alias);if($link===$cat['link'])return false;
    $db->execute('UPDATE '.$db->quoteIdentifier($S->db->pre.'news_cat').' SET link=? WHERE cat_id=? AND web_id=? AND link=?',[$link,(int)$cat['cat_id'],$web,$cat['link']]);
    return true;
}

/**
 * 功能说明：抽取三种框架URL模式的实际路由路径，用于识别旧入口与规范入口。
 * @param string $url 当前请求URI或已生成的应用URL
 * @return string 解码一次、不含部署前缀及首尾斜线的路径
 */
function articleRoutePath($url): string {
    // 某些LC_CTYPE环境会让parse_url将UTF-8字节视作控制符；先编码非ASCII字节。
    $encoded=preg_replace_callback('/[\x80-\xff]+/',static function($match){return rawurlencode($match[0]);},(string)$url);
    $parts=parse_url($encoded);$path=$parts['path']??'';$query=$parts['query']??'';
    if(preg_match('~/index\.php$~',$path)&&$query!==''&&strpos(explode('&',$query,2)[0],'=')===false)$path=explode('&',$query,2)[0];
    else{$base=rtrim(ROOT_WEB,'/');if($base!==''&&\myRouter::hasPrefix($path,$base))$path=substr($path,strlen($base));$path=preg_replace('~^/index\.php(?=/|$)~','',$path);}
    return trim(rawurldecode($path),'/');
}
/**
 * 功能说明：生成当前文章/分页的绝对规范地址，优先使用子站绑定域名。
 * @param array $article 含news_id、web_id和cat_id的文章记录
 * @param int|string $page 页码；首页省略参数，all保留整篇阅读入口
 * @return string 可安全转义后放入head的绝对URL
 */
function articleCanonicalURL(array $article,$page=1): string {
    global $website;$path=getLink($article);if($page==='all'||(is_numeric($page)&&(int)$page>1))$path=queryURL($path,['page'=>$page]);
    $web=getPara($website,'web_id',$article['web_id']??0);$domain=trim(explode(',',(string)($web['domain']??''))[0]);
    if(!preg_match('~^(?:https?:)?//~i',$path)&&$domain!==''&&preg_match('/\A[a-z0-9.\-]+(?::\d+)?\z/i',$domain))$path='//'.$domain.$path;
    return absoluteURL($path);
}

/**
 * 生成文章、栏目或标签链接；路径段独立编码，跨站链接采用目标子站域名。
 * @param array|string $data 文章/栏目记录，或标签名称；记录可包含 cat_id、cat_idx、news_id、web_id
 * @param string $mode news 为文章、catalog 为栏目、tag 为标签（默认：'news'）
 * @return string 当前路由模式的应用地址；跨站返回 //domain/path，HTML 输出时需作属性转义
 */
function getLink($data, $mode='news') {
    global $news_cat_plat, $website, $web_info, $info_app, $S;
    $link = ROOT_WEB;
    if(!defined('URL_FIX') && $info_app['app']!=$S->router->default_app) $link .= $info_app['app'].'/';
    if(isset($data['cat_id']))  {
        if($cat = checkVal($news_cat_plat, 'cat_id', $data['cat_id'])) {
            $data['web_id'] = $cat['web_id'];
        } else {
            if(isset($data['cat_idx'])) {
                $cat = ['idx'=>$data['cat_idx']];
            } else {
                unset($cat);
            }
        }
    }
    switch($mode) {
        case 'news':
            $alias=getArticleAlias((int)$data['news_id'],(int)($data['web_id']??$web_info['web_id']??1));
            if($alias!==null)$link.=rawurlencode($alias);
            else {
                // 旧站未执行别名迁移时仍保持可访问；正常发布由saveArticleAlias分配唯一中文入口。
                $link .= 'article/';
                if(isset($cat)) $link .= rawurlencode($cat['idx']).'/';
                $link .= $data['news_id'];
            }
            break;
        case 'catalog':
            if(isset($cat)) {
                $link .= 'catalog/'.rawurlencode($cat['idx']).'/';
            }
            break;
        case 'tag':
            $link .= 'tag/'.rawurlencode(is_string($data)?$data:$data['tag']);
            break;
    }
    $link = \myStep::setURL($link);
    if(isset($data['web_id'])) {
        $web = checkVal($website, 'web_id', $data['web_id']);
        if(is_array($web) && $web['web_id']!=$web_info['web_id']) {
            if(strpos($web['domain'],',')) $web['domain'] = substr($web['domain'],0,strpos($web['domain'],','));
            $link = '//'.$web['domain'].$link;
        }
    }
    return $link;
}

function parseNews(\myTemplate &$tpl, &$tag_attrs = array()) {
    global $tpl_setting, $news_cat_plat, $mystep;
    $catalog = false;
    if(isset($tag_attrs['catalog']) && substr($tag_attrs['catalog'],0,1)!='$') {
        if(is_numeric($tag_attrs['catalog'])) {
            $catalog = checkVal($news_cat_plat, 'cat_id', $tag_attrs['catalog']);
        } elseif(is_string($tag_attrs['catalog'])) {
            $catalog = checkVal($news_cat_plat, 'idx', $tag_attrs['catalog']);
        }
        if($catalog==false) {
            $catalog = checkVal($news_cat_plat, 'cat_id', 1);
        }
        $tag_attrs['catalog'] = $catalog['cat_id'];
        $tag_attrs['web'] = $catalog['web_id'];
    }
    if(!isset($tag_attrs['pos_img'])) $tag_attrs['pos_img'] = '0';
    if(checkPara($tag_attrs) === false) {
        $tag_attrs['sql'] = addslashes(buildSQL($tag_attrs));
    } else {
        $tag_attrs['sql'] = '';
    }
    if(!isset($tag_attrs['template'])) $tag_attrs['template'] = 'classic';
    if(!isset($tag_attrs['loop'])) $tag_attrs['loop'] = 0;
    $tpl_content = $tpl->getTemplate($tpl_setting['path'].'/'.$tpl_setting['style'].'/block_news_'.$tag_attrs['template'].'.tpl');
    list($block, $tag_attrs['unit'], $tag_attrs['unit_blank'])= $tpl->getBlock($tpl_content, 'loop', 'news');
    if(!isset($tag_attrs['custom'])) $tag_attrs['custom'] = '';

    $tag_attrs['cat_link'] = getLink($catalog??[], 'catalog');
    $tag_attrs['cat_name'] = $catalog ? $catalog['name'] : $mystep->getLanguage('page_update');

    $result = <<<'mytpl'
<?PHP
$sql = '{myTemplate::sql}';
if(empty($sql)) {
    $sql = \app\CMS\buildSQL($tag_attrs);
}
$result = $cache->getData($sql, 'all', $S->expire->catalog);
\app\CMS\primeArticleAliases($result);
$n = 0;
foreach($result as $news) {
    if(!isset($tag_attrs['show_cat']) || $tag_attrs['show_cat']=='n') $news['catalog'] = '';
    if(isset($tag_attrs['date'])) {
        $news['add_date'] = formatDate($news['add_date'], $tag_attrs['date']);
    } else {
        $news['add_date'] = '';
    }
    $news['custom'] = $tag_attrs['custom'];
    if(preg_match_all('#\{(.+?)\}#', $news['custom'], $matches)) {
        for($i=0,$m=count($matches[1]);$i<$m;$i++) {
            $news['custom'] = str_replace('{'.$matches[1][$i].'}', '"'.$news[$matches[1][$i]].'"', $news['custom']);
        }
        $news['custom'] = myEval($news['custom'], true);
    }
    $news['style'] = explode(',', $news['style']);
    $news['subject_styled'] = $news['subject'];
    if(empty($news['link'])) $news['link'] = \app\CMS\getLink($news);
    switch({myTemplate::pos_img}) {
        case 1:
            $news['show_l'] = 'd-none';
            $news['show_r'] = '';
            break;
        case 2:
            $news['show_l'] = '';
            $news['show_r'] = 'd-none';
            break;
        case 3:
            if($n%2) {
                $news['show_l'] = 'd-none';
                $news['show_r'] = '';
            } else {
                $news['show_l'] = '';
                $news['show_r'] = 'd-none';
            }
            break;
        default:
            $news['show_l'] = 'd-none';
            $news['show_r'] = 'd-none';
    }
    $news['cat_link'] = \app\CMS\getLink($news,'catalog');
    $news['active'] = ($n==0?'active':'');
    $news['tags'] = '';
    $tags = explode(',', $news['tag']);
    for($i=0,$m=count($tags);$i<$m;$i++) {
        $news['tags'] .= '<a href="'.htmlspecialchars(\app\CMS\getLink($tags[$i],'tag'), ENT_QUOTES, 'UTF-8').'" target="_blank">'.htmlspecialchars($tags[$i], ENT_QUOTES, 'UTF-8').'</a>';
    }

    foreach($news['style'] as $k) {
        switch(strtolower($k)) {
            case 'b':
            case 'i':
                $news['subject_styled'] = '<'.$k.'>'.$news['subject_styled'].'</'.$k.'>';
                break;
            default:
                $news['subject_styled'] = '<span style="color:'.$k.'">'.$news['subject_styled'].'</span>';
        }
    }
    echo <<<content
{myTemplate::unit}
content;
    $n++;
    if({myTemplate::loop}!=0 && $n>={myTemplate::loop}) break;
}
for(; $n<{myTemplate::loop}; $n++) {
    echo <<<content
{myTemplate::unit_blank}
content;
}
?>
mytpl;
    return str_replace($block, $result, $tpl_content);
}

function parseNewsNext(\myTemplate &$tpl, &$tag_attrs = array()) {
    global $S, $db;
    if(!isset($tag_attrs['id'])) return '';
    if(!isset($tag_attrs['mode'])) $tag_attrs['mode'] = 'next';
    $result = <<<'mytpl'
<?php
$db->build($S->db->pre_sub.'news_show')
    ->field('news_id,subject')
    ->limit(1);
if(isset($tag_attrs['catalog'])) {
    $db->build($S->db->pre_sub.'news_show')->where('cat_id', 'n=', $tag_attrs['catalog']);
}
if($tag_attrs['mode']=='next') {
    $db->build($S->db->pre_sub.'news_show')->where('news_id', 'n>', $tag_attrs['id'])->order('news_id');
} else {
    $db->build($S->db->pre_sub.'news_show')->where('news_id', 'n<', $tag_attrs['id'])->order('news_id', true);
}
if($news = $db->record()) {
    $url = app\CMS\getLink($news);
    $name = $news['subject'];
} else {
    $url = app\CMS\getLink($tag_attrs, 'catalog');
    $name = $mystep->getLanguage('page_return');
}
echo '<a href="'.$url.'" id="article_'.$tag_attrs['mode'].'">'.$name.'</a>';
?>
mytpl;
    return $result;
}

function parseInfo(\myTemplate &$tpl, &$tag_attrs = array()) {
    global $S, $db, $web_info;
    $result = '';
    $condition = array();
    if(isset($tag_attrs['id'])) {
        $condition[] = array('id', 'n=', $tag_attrs['id']);
    } elseif(isset($tag_attrs['idx'])) {
        $condition[] = array('idx', '=', $tag_attrs['idx']);
    }
    if(!empty($condition)) {
        $condition[] = array(array('web_id', 'n=', $web_info['web_id']), array('web_id', 'n=', 0,'or'),'and');
        $db->build($S->db->pre.'info')->where($condition)->field('content')->order('web_id', true);
        $tag_attrs['sql'] = addslashes($db->select(true));
        $db->build('[reset]');
        $result = <<<'mytpl'
<?PHP
echo $cache->getData('{myTemplate::sql}', 'result', 3600*24);
?>
mytpl;
    }
    return $result;
}

function parseLink(\myTemplate &$tpl, &$tag_attrs = array()) {
    global $tpl_setting;
    if(!isset($tag_attrs['idx'])) $tag_attrs['idx'] = '';
    if(!isset($tag_attrs['type'])) $tag_attrs['type'] = 'all';
    if(!isset($tag_attrs['title'])) $tag_attrs['title'] = 'Links';
    if(!isset($tag_attrs['limit']) || !is_numeric($tag_attrs['limit'])) $tag_attrs['limit'] = 0;
    if($tag_attrs['type']=='image' || $tag_attrs['type']=='img') {
        $tag_attrs['type'] = 'img';
    } elseif($tag_attrs['type']=='text' || $tag_attrs['type']=='txt') {
        $tag_attrs['type'] = 'txt';
    }
    if($tag_attrs['type']=='all') $tag_attrs['type'] = 'txt';
    if(!isset($tag_attrs['template'])) $tag_attrs['template'] = $tag_attrs['type'];
    $tpl_content = $tpl->getTemplate($tpl_setting['path'].'/'.$tpl_setting['style'].'/block_link_'.$tag_attrs['template'].'.tpl');

    list($block, $tag_attrs['unit'], $tag_attrs['unit_blank'])= $tpl->getBlock($tpl_content, 'loop', 'show');
    $result = <<<'mytpl'
<?php
$link = \app\CMS\getCache('link');
$link_idx = '{myTemplate::idx}';
$link_type = '{myTemplate::type}';
$link_list = $link_type=='all' ? array_merge($link['txt'],$link['img']) : $link[$link_type];
foreach($link_list as $cur_link) {
    if($link_idx!=='' && $link_idx!==$cur_link['idx']) continue;
    $show = [
        'link' => $cur_link['url'],
        'image' => $cur_link['image'],
        'txt' => $cur_link['name'],
    ];
    echo <<<content
{myTemplate::unit}
content;
}
?>
mytpl;
    return str_replace($block, $result, $tpl_content);
}
function parseTag(\myTemplate &$tpl, &$tag_attrs = array()) {
    global $tpl_setting, $db, $S;
    if(!isset($tag_attrs['count'])) $tag_attrs['count'] = 0;
    if(!is_numeric($tag_attrs['count'])) $tag_attrs['count'] = 0;
    if(!isset($tag_attrs['limit'])) $tag_attrs['limit'] = 20;
    if(!is_numeric($tag_attrs['limit'])) $tag_attrs['limit'] = 20;
    if(!isset($tag_attrs['order'])) $tag_attrs['order'] = 'rand()';

    $tpl_content = $tpl->getTemplate($tpl_setting['path'].'/'.$tpl_setting['style'].'/block_tag.tpl');
    list($block, $tag_attrs['unit'], $tag_attrs['unit_blank'])= $tpl->getBlock($tpl_content, 'loop', 'tag');

    $db->build($S->db->pre_sub.'news_tag')->field('tag,count')->order($tag_attrs['order'])->limit($tag_attrs['limit']);
    if($tag_attrs['count']>0)  $db->build($S->db->pre_sub.'news_tag')->where('count', 'n>', $tag_attrs['count']);
    $tag_attrs['sql'] = addslashes($db->select(true));
    $db->build('[reset]');
    $result = <<<'mytpl'
<?php
$sql = '{myTemplate::sql}';
$base_size = 8;
$dyn_size = 32;
$count_max = 0;
$tag_list = array();
$result = $cache->getData($sql, 'all', $S->expire->tag);
for($i=0,$m=count($result); $i<$m; $i++) {
    $record = $result[$i];
    $record['link'] = \app\CMS\getLink($record, 'tag');
    $record['size'] = $base_size;
    if($count_max<$record['count']) $count_max = $record['count'];
    $tag_list[] = $record;
    unset($record);
}
unset($result);
for($i=0,$m=count($tag_list); $i<$m; $i++) {
    $tag_list[$i]['size'] = $base_size + round($dyn_size * $tag_list[$i]['count'] / $count_max);
    $tag = $tag_list[$i];
    echo <<<content
{myTemplate::unit}
content;
}
?>
mytpl;
    return str_replace($block, $result, $tpl_content);
}

function parseCatalog(\myTemplate &$tpl, &$tag_attrs = array()) {
    global $tpl_setting, $news_cat_plat;
    $result = '';
    if(!isset($tag_attrs['id'])) $tag_attrs['id'] = 0;
    if(!isset($tag_attrs['deep'])) $tag_attrs['deep'] = 1;
    if(!isset($tag_attrs['show'])) $tag_attrs['show'] = 0;
    if(!isset($tag_attrs['template'])) {
        $tag_attrs['template'] = 'catalog';
    } else {
        $tag_attrs['template'] = 'catalog_'.$tag_attrs['template'];
    }
    $tpl_content = $tpl->getTemplate($tpl_setting['path'].'/'.$tpl_setting['style'].'/block_'.$tag_attrs['template'].'.tpl');
    list($block, $tag_attrs['unit'], $tag_attrs['unit_blank'])= $tpl->getBlock($tpl_content, 'loop', 'cat');
    $result = <<<'mytpl'
<?php
$tag_attrs['id'] = myEval($tag_attrs['id'], true);
$list = \app\CMS\getSubcat($tag_attrs['id'], $cat_info);
$tag_attrs['name'] = $cat_info===false?$mystep->getLanguage('page_catalog'):$cat_info['name'];
/*split*/
if(!empty($list)) {
    for($i=0,$m=count($list); $i<$m; $i++) {
        if(($tag_attrs['show'] & $list[$i]['show'])!=$tag_attrs['show']) continue;
        if(($tag_attrs['name']??'')===$list[$i]['name']) continue;
        $cat = $list[$i];
        if(empty($list[$i]['link'])) {
            $cat['link'] = \app\CMS\getLink($list[$i], 'catalog');
        } else {
            $cat['link'] = $list[$i]['link'];
        }
        echo <<<content
{myTemplate::unit}
content;
    }
}
?>
mytpl;
    if(!empty($block)) {
        $result = str_replace($block, $result, $tpl_content);
    }
    return $result;
}
function parsePages(\myTemplate &$tpl, &$tag_attrs = array()){
    global $tpl_setting;
    $tpl_content = $tpl->getTemplate($tpl_setting['path'].'/'.$tpl_setting['style'].'/block_pages.tpl');
    list($block, $tag_attrs['unit'], $tag_attrs['unit_blank']) = $tpl->getBlock($tpl_content, 'if');
    $tag_attrs['unit'] = preg_replace('#<!--(link_\w+)-->#', '<?=htmlspecialchars($page_info[\'\1\'], ENT_QUOTES, \'UTF-8\')?>', $tag_attrs['unit']);
    $tag_attrs['unit'] = str_replace('<!--page_count-->', '<?=$page_info[\'page_count\']?>', $tag_attrs['unit']);
    $tag_attrs['unit'] = stripslashes($tag_attrs['unit']);
    $tag_attrs['unit_blank'] = stripslashes($tag_attrs['unit_blank']);
    $content = <<<'mytpl'
<?PHP
list($page_info) = \app\CMS\getPageList({myTemplate::count}, {myTemplate::page}, {myTemplate::size}, {myTemplate::query});
if($page_info['page_count']>1) {
?>
{myTemplate::unit}
<?PHP
} elseif({myTemplate::count}>0) {
?>
{myTemplate::unit_blank}
<?PHP
}
?>
mytpl;
    return str_replace($block, $content, $tpl_content);
}

function getSubcat($id='', &$cat_info=false) {
    global $news_cat, $news_cat_plat, $web_info;
    $id = intval($id);
    $cat_info = false;
    $news_cat_web = [];
    foreach($news_cat as $v) {
        if($v['web_id']==$web_info['web_id']) {
            $news_cat_web[] = $v;
        }
    }
    if($id>0) {
        if(($cat_info = checkVal($news_cat_plat, 'cat_id', $id))!==false) {
            if($cat_info['pid']>0) {
                $path = categoryPath($news_cat_plat,$id);
                if(!$path) { $cat_info=false; return []; }
                $pid_list = array_column($path,'cat_id');
                $cat_info = checkVal($news_cat_web, 'cat_id', $pid_list[0]);
                for($i=1,$m=count($pid_list);$i<$m;$i++) {
                    $cat_cur = checkVal($cat_info['sub'], 'cat_id', $pid_list[$i]);
                    if(isset($cat_cur['sub'])) {
                        $cat_info = $cat_cur;
                    } else {
                        break;
                    }
                }
            } else {
                $cat_info = checkVal($news_cat_web, 'cat_id', $id);
            }
        } else {
            return [];
        }
    }
    return $cat_info['sub']??$news_cat_web;
}

function buildSQL($paras) {
    global $S, $db, $website, $web_info;
    $web = false;
    if(isset($paras['web'])) {
        if(is_numeric($paras['web'])) {
            $web = checkVal($website, 'web_id', $paras['web']);
        } elseif(is_string($paras['web'])) {
            $web = checkVal($website, 'idx', $paras['web']);
        }
    }
    if($web==false) {
        $web = $web_info;
    }
    $web['setting'] = new \myConfig(PATH.'website/config_'.$web['idx'].'.php');
    if(!isset($web['setting']->db->pre)) $web['setting']->db->pre = $S->db->pre;
    $tbl = $web['setting']->db->pre.'news_show';
    $db->build($tbl);
    $db->build($S->db->pre.'news_cat', array(
        'mode' => 'left',
        'field' => 'cat_id'
    ))->field('idx as cat_idx','name as catalog');
    if(isset($paras['catalog']) && $paras['catalog']!=0) {
        $db->build($S->db->pre.'news_cat')->where([
                ['cat_id', 'n=', $paras['catalog']],
                ['pid', 'n=', $paras['catalog'], 'or'],
                ['pid', 'in', '(select cat_id from '.$S->db->pre.'news_cat where pid='.intval($paras['catalog']).')', 'or'],
            ], 'and'
        );
    } else {
        $db->build($tbl)->where('web_id', 'n=', $web['web_id'], 'and');
    }
    $db->build($tbl)->order('order', true);
    if(isset($paras['order'])) {
        $paras['order'] = preg_split('#[\s,]+#', $paras['order']);
        if(!isset($paras['order'][1])) $paras['order'][1] = false;
        $db->build($tbl)->order($paras['order'][0], $paras['order'][1]);
    } else {
        if(isset($paras['catalog']) && $paras['catalog']!=0) {
            $db->build($tbl)->order('order', true);
        }
        $db->build($tbl)->order('news_id', true);
    }
    if(isset($paras['setop'])) {
        $db->build($tbl)->where('(`setop` & '.intval($paras['setop']).')', 'n=', $paras['setop'], 'and');
    }
    if(isset($paras['image'])) {
        $db->build($tbl)->where('image', '!=', '', 'and');
    }
    if(isset($paras['expire'])) {
        $db->build($tbl)->where('expire', 'f>', 'now()', 'and');
    }
    if(isset($paras['xid'])) {
        $db->build($tbl)->where('news_id', 'nin', $paras['xid'], 'and');
    }
    if(isset($paras['condition'])) {
        $db->build($tbl)->where($paras['condition'], 'and');
    }
    if(isset($paras['prefix']) && !empty($paras['prefix'])) {
        $db->build($tbl)->where('subject', 'like', '['.$paras['prefix'].']%', 'and');
    }
    if(isset($paras['keyword']) && strlen($paras['keyword'])>=4) {
        $detail = $db->quoteIdentifier($web['setting']->db->pre.'news_detail');
        $keyword = $db->safeValue('%'.$paras['keyword'].'%');
        $db->build($tbl)->where("(t0.subject LIKE '".$keyword."' OR EXISTS (SELECT 1 FROM ".$detail." AS d WHERE d.news_id=t0.news_id AND d.content LIKE '".$keyword."'))", 'f=', 1);
    }
    $db->build($tbl)->where([
            ['expire', 'is', null],
            ['expire', '=', '0000-00-00', 'or'],
            ['expire', 'f>', 'now()', 'or']
        ], 'and');
    $db->build($tbl)->where([
            ['active', 'is', null],
            ['active', '=', '0000-00-00', 'or'],
            ['active', 'f<', 'now()', 'or']
        ], 'and');
    if(isset($paras['tag']) && !empty($paras['tag'])) {
        $tags = preg_split('#[\s,]+#', trim($paras['tag']));
        $list = [];
        for($i=0,$m=count($tags);$i<$m;$i++) {
            if(strlen($tags[$i])<2) continue;
            $list[] = array('tag', 'like', $tags[$i], 'or');
        }
        $list[] = 'and';
        $db->build($tbl)->where($list);
    }
    if(isset($paras['limit'])) {
        $db->build($tbl)->limit($paras['limit']);
    }
    $sql = $db->select(true);
    $db->build('[reset]');
    return $sql;
}
/**
 * 功能说明：CMS普通会员适配器，复用users表及ms_cms_user会话，不读写sys_op或框架管理员权限。
 * 方法目录：
 *   public MemberProvider::__construct(\myDb $db,string $prefix,string $driver,string $realm) — 接管受信CMS连接和作用域。
 *   public MemberProvider::scope(): string — 取得当前会员作用域。参数说明：无显式参数。
 *   public MemberProvider::database(): \myDb — 取得共享事务连接。参数说明：无显式参数。
 *   public MemberProvider::prefix(): string — 取得表前缀。参数说明：无显式参数。
 *   public MemberProvider::dialect(): string — 取得SQL方言。参数说明：无显式参数。
 *   public MemberProvider::ready(): bool — 只读检查必要字段与事务引擎，不隐式改表。参数说明：无显式参数。
 *   public MemberProvider::prepare(): void — 显式准备标准会员表，旧MyISAM站点转换为InnoDB后可与身份映射一同回滚。参数说明：无显式参数。
 *   public MemberProvider::allowsLogin(): bool — 拒绝将管理员会话混入普通会员认证。参数说明：无显式参数。
 *   private MemberProvider::row(string $id): ?array — 读取单个内部会员记录，密码不向外导出。
 *   private MemberProvider::publicProfile(array $row): array — 生成不含密码、激活码的标准公开资料。
 *   public MemberProvider::find(string $id): ?array — 读取会员公开资料。
 *   public MemberProvider::findByEmail(string $email,bool $lock=false): array — 以不区分邮箱大小写的方式查询，拒绝由调用方随意选择重复邮箱账号。
 *   public MemberProvider::create(array $profile): array — 只创建固定普通会员组，第三方账号默认没有可用本地密码。
 *   public MemberProvider::verifyEmail(string $id,string $email): array — 在邮箱挑战已完成后标记指定邮箱，不提升用户组或改变登录名。
 *   public MemberProvider::authenticate(string $username,string $password): ?array — 核对本地密码并按需升级摘要，不向第三方账号开放随机密码。
 *   public MemberProvider::login(string $id,int $remember=0): array — 为已认证普通会员轮换会话并建立CMS兼容状态。
 *   public MemberProvider::current(): ?array — 验证当前普通会员与当前数据库密码版本一致。参数说明：无显式参数。
 *   public MemberProvider::logout(): void — 删除普通会员状态并轮换ID，保留管理员相关字段。参数说明：无显式参数。
 * 使用说明：由CMS::userProvider构建；prepare仅在显式安装时执行，旧站补列/扩展邮箱并将会员表转换为InnoDB，保留用户主键与已有记录。
 */
class MemberProvider implements \interface_user {
    private \myDb $db;
    private string $pre;
    private string $driver;
    private string $realm;
    private ?bool $prepared = null;
    /**
     * 功能说明：接管受信CMS连接和作用域。
     * @param \myDb $db 会员连接
     * @param string $prefix 表前缀
     * @param string $driver mysql或sqlite
     * @param string $realm 不含凭据的稳定应用作用域
     * @return void 初始化完成
     */
    public function __construct(\myDb $db,string $prefix,string $driver,string $realm) {
        $driver=strtolower($driver);
        if(!preg_match('/\A[A-Za-z0-9_]{0,40}\z/D',$prefix) || !in_array($driver,['mysql','sqlite'],true))throw new \InvalidArgumentException('CMS会员接口需要MySQL或SQLite及合法表前缀。');
        $this->db=$db;$this->pre=$prefix;$this->driver=$driver;$this->realm=$realm;
    }
    /**
     * 功能说明：取得当前会员作用域。参数说明：无显式参数。
     * @return string 不含凭据的应用标识
     */
    public function scope(): string {return $this->realm;}
    /**
     * 功能说明：取得共享事务连接。参数说明：无显式参数。
     * @return \myDb 会员连接
     */
    public function database(): \myDb {return $this->db;}
    /**
     * 功能说明：取得表前缀。参数说明：无显式参数。
     * @return string 已校验前缀
     */
    public function prefix(): string {return $this->pre;}
    /**
     * 功能说明：取得SQL方言。参数说明：无显式参数。
     * @return string mysql或sqlite
     */
    public function dialect(): string {return $this->driver;}
    /**
     * 功能说明：只读检查必要字段与事务引擎，不隐式改表。参数说明：无显式参数。
     * @return bool 是否完成标准存储准备
     */
    public function ready(): bool {
        if($this->prepared!==null)return $this->prepared;
        if(!in_array($this->pre.'users',$this->db->getTbls(),true))return $this->prepared=false;
        $fields=$this->db->getFields($this->pre.'users');
        if(array_diff(['display_name','email_verified_at','password_enabled'],$fields))return $this->prepared=false;
        if($this->driver==='mysql') {
            $engine=$this->db->fetchValue('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?',[$this->pre.'users']);
            if(strcasecmp((string)$engine,'InnoDB')!==0)return $this->prepared=false;
        }
        return $this->prepared=true;
    }
    /**
     * 功能说明：显式准备标准会员表，旧MyISAM站点转换为InnoDB后可与身份映射一同回滚。参数说明：无显式参数。
     * @return void 失败抛异常，不删除会员
     */
    public function prepare(): void {
        $name=$this->pre.'users';$table=$this->db->quoteIdentifier($name);
        if(!in_array($name,$this->db->getTbls(),true))throw new \RuntimeException('请先完成CMS会员数据库安装。');
        if($this->driver==='mysql') {
            $engine=$this->db->fetchValue('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?',[$name]);
            if(strcasecmp((string)$engine,'InnoDB')!==0)$this->db->execute('ALTER TABLE '.$table.' ENGINE=InnoDB');
        }
        $fields=$this->db->getFields($name);
        $columns=['display_name'=>($this->driver==='mysql'?"VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci":"TEXT")." NOT NULL DEFAULT ''",'email_verified_at'=>'BIGINT NOT NULL DEFAULT 0','password_enabled'=>'INTEGER NOT NULL DEFAULT 1'];
        foreach($columns as $field=>$definition)if(!in_array($field,$fields,true))$this->db->execute('ALTER TABLE '.$table.' ADD COLUMN '.$this->db->quoteIdentifier($field).' '.$definition);
        if($this->driver==='mysql') {
            $length=$this->db->fetchValue('SELECT CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?',[$name,'email']);
            if((int)$length<254)$this->db->execute('ALTER TABLE '.$table.' MODIFY COLUMN email VARCHAR(254) NULL');
        }
        $this->prepared=null;if(!$this->ready())throw new \RuntimeException('会员存储准备未完成。');
    }
    /**
     * 功能说明：拒绝将管理员会话混入普通会员认证。参数说明：无显式参数。
     * @return bool 当前不包含管理员身份
     */
    public function allowsLogin(): bool {
        foreach(['ms_cms_op','ms_user'] as $key){$name=\myReq::session($key);if(is_string($name)&&$name!=='')return false;}return true;
    }
    /**
     * 功能说明：读取单个内部会员记录，密码不向外导出。
     * @param string $id 数字主键
     * @return array|null 原始受信记录
     */
    private function row(string $id): ?array {
        if(!preg_match('/\A[1-9][0-9]{0,19}\z/D',$id))return null;
        $row=$this->db->fetchOne('SELECT * FROM '.$this->db->quoteIdentifier($this->pre.'users').' WHERE user_id=?',[$id]);return is_array($row)?$row:null;
    }
    /**
     * 功能说明：生成不含密码、激活码的标准公开资料。
     * @param array $row 内部记录
     * @return array 标准会员资料
     */
    private function publicProfile(array $row): array {
        return ['id'=>(string)$row['user_id'],'username'=>(string)$row['username'],'display_name'=>(string)(($row['display_name']??'')!==''?$row['display_name']:$row['username']),'email'=>(string)($row['email']??''),'email_verified'=>(int)($row['email_verified_at']??0)>0 && empty($row['hash']),'active'=>(int)$row['group_id']>0,'has_password'=>(bool)($row['password_enabled']??1)&&is_string($row['password']??null)&&$row['password']!==''];
    }
    /**
     * 功能说明：读取会员公开资料。
     * @param string $id 数字主键
     * @return array|null 不存在返回null
     */
    public function find(string $id): ?array {$row=$this->row($id);return $row===null?null:$this->publicProfile($row);}
    /**
     * 功能说明：以不区分邮箱大小写的方式查询，拒绝由调用方随意选择重复邮箱账号。
     * @param string $email 邮箱
     * @param bool $lock 默认false；事务内是否锁定当前记录
     * @return array 公开资料列表
     */
    public function findByEmail(string $email,bool $lock=false): array {
        $sql='SELECT * FROM '.$this->db->quoteIdentifier($this->pre.'users').' WHERE LOWER(email)=LOWER(?)';
        if($lock&&$this->driver==='mysql')$sql.=' FOR UPDATE';
        return array_map([$this,'publicProfile'],$this->db->fetchAll($sql,[\myUser::email($email)]));
    }
    /**
     * 功能说明：只创建固定普通会员组，第三方账号默认没有可用本地密码。
     * @param array $profile email、display_name及可选username/password
     * @return array 未验证会员资料
     */
    public function create(array $profile): array {
        if(!$this->ready())throw new \RuntimeException('请先准备标准会员存储。');
        $email=\myUser::email((string)($profile['email']??''));$name=\myUser::displayName((string)($profile['display_name']??''));
        $username=(string)($profile['username']??('s'.bin2hex(random_bytes(7))));
        if(!preg_match('/\A[A-Za-z0-9_]{1,15}\z/D',$username))throw new \InvalidArgumentException('登录名须为1至15位字母、数字或下划线。');
        $enabled=array_key_exists('password',$profile);
        if($enabled&&(!is_string($profile['password'])||strlen($profile['password'])<8))throw new \InvalidArgumentException('本地密码至少8字节。');
        $hash=\myAuth::hashPassword($enabled?$profile['password']:bin2hex(random_bytes(32)));
        $this->db->execute('INSERT INTO '.$this->db->quoteIdentifier($this->pre.'users').' (group_id,username,password,email,reg_date,hash,display_name,email_verified_at,password_enabled) VALUES (2,?,?,?,?,?,?,0,?)',[$username,$hash,$email,time(),bin2hex(random_bytes(16)),$name,(int)$enabled]);
        return $this->find((string)$this->db->getInsertId());
    }
    /**
     * 功能说明：在邮箱挑战已完成后标记指定邮箱，不提升用户组或改变登录名。
     * @param string $id 会员ID
     * @param string $email 已证明邮箱
     * @return array 更新后公开资料
     */
    public function verifyEmail(string $id,string $email): array {
        $email=\myUser::email($email);$profile=$this->find($id);
        if(!$profile||!$profile['active']||strcasecmp($profile['email'],$email)!==0)throw new \DomainException('账号或邮箱已经改变，请重新认证。');
        $this->db->execute('UPDATE '.$this->db->quoteIdentifier($this->pre.'users')." SET email_verified_at=?,hash='' WHERE user_id=? AND email=?",[time(),$id,$profile['email']]);
        $updated=$this->find($id);if(!$updated||!$updated['email_verified']||strcasecmp($updated['email'],$email)!==0)throw new \DomainException('邮箱已经改变，请重新验证。');return $updated;
    }
    /**
     * 功能说明：核对本地密码并按需升级摘要，不向第三方账号开放随机密码。
     * @param string $username 登录名
     * @param string $password 原始密码
     * @return array|null 成功公开资料或失败
     */
    public function authenticate(string $username,string $password): ?array {
        $table=$this->db->quoteIdentifier($this->pre.'users');$row=$this->db->fetchOne('SELECT * FROM '.$table.' WHERE username=?',[$username]);
        if(!is_array($row)||!(bool)($row['password_enabled']??1)||(int)$row['group_id']<=0||!\myAuth::verifyPassword($password,(string)$row['password']))return null;
        if(\myAuth::needsUpgrade($row['password'])){
            $hash=\myAuth::hashPassword($password);$this->db->execute('UPDATE '.$table.' SET password=? WHERE user_id=? AND password=?',[$hash,$row['user_id'],$row['password']]);
            $row=$this->row((string)$row['user_id']);if(!$row||!\myAuth::verifyPassword($password,$row['password']))return null;
        }
        return $this->publicProfile($row);
    }
    /**
     * 功能说明：为已认证普通会员轮换会话并建立CMS兼容状态。
     * @param string $id 会员ID
     * @param int $remember 默认0；本地密码登录的记住时长（秒）
     * @return array 实际登录资料
     */
    public function login(string $id,int $remember=0): array {
        if(!$this->allowsLogin()||!\myReq::sessionCheck())throw new \DomainException('请退出管理员，并使用已启动的普通会员会话。');
        $row=$this->row($id);if(!$row||(int)$row['group_id']<=0)throw new \DomainException('会员不存在或已停用。');
        \myAuth::renewSession();\myReq::session('ms_cms_user',$row['username']);\myReq::session('ms_cms_user_version',hash('sha256',$row['password']));\myReq::session('ms_cms_user_group',empty($row['hash'])?(int)$row['group_id']:1);
        \myReq::removeCookie('ms_cms_user');
        if($remember>0){global $S;$scope='cms:'.($S->db->name??'').':'.$this->pre.'users';\myReq::setCookie('ms_cms_user',\myAuth::makeToken($row['username'],$row['password'],$scope,(string)($S->web->etag??''),min(31536000,$remember)),min(31536000,$remember));}
        return $this->publicProfile($row);
    }
    /**
     * 功能说明：验证当前普通会员与当前数据库密码版本一致。参数说明：无显式参数。
     * @return array|null 有效普通会员资料
     */
    public function current(): ?array {
        if(!$this->allowsLogin())return null;$name=\myReq::session('ms_cms_user');if(!is_string($name)||$name==='')return null;
        $row=$this->db->fetchOne('SELECT * FROM '.$this->db->quoteIdentifier($this->pre.'users').' WHERE username=?',[$name]);
        if(!is_array($row)||(int)$row['group_id']<=0||!hash_equals(hash('sha256',$row['password']),(string)\myReq::session('ms_cms_user_version')))return null;
        return $this->publicProfile($row);
    }
    /**
     * 功能说明：删除普通会员状态并轮换ID，保留管理员相关字段。参数说明：无显式参数。
     * @return void 无返回值
     */
    public function logout(): void {
        foreach(['ms_cms_user','ms_cms_user_version','ms_cms_user_group'] as $key)\myReq::removeSession($key);\myReq::removeCookie('ms_cms_user');if(\myReq::sessionCheck())\myAuth::renewSession();
    }
}
