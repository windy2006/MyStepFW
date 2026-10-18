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

function getUserInfo() {
    global $db, $S;
    $db_cache = $db->cache();
    $db->cache(0);
    $user_group = \app\CMS\getCache('user_group');
    if(!is_null(\r::s('ms_cms_op'))) {
        $user_info = \app\CMS\checkVal($user_group, 'group_id', 3);
        $user_info['group'] = $user_info['name'];
        $user_info['name'] = \r::s('ms_cms_op');
    } elseif(!is_null(\r::s('ms_cms_user_group'))) {
        $user_info = \app\CMS\checkVal($user_group, 'group_id', \r::s('ms_cms_user_group'));
        $user_info['group'] = $user_info['name'];
        $user_info['name'] = \r::s('ms_cms_user');
    } else {
        $user_info = null;
        if(is_null($user_info)) {
            $user_info = \app\CMS\checkVal($user_group, 'group_id', 1);
            $user_info['group'] = $user_info['name'];
            $user_info['name'] = '';
        }
    }
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
            $link .= 'article/';
            if(isset($cat)) $link .= rawurlencode($cat['idx']).'/';
            $link .= $data['news_id'];
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