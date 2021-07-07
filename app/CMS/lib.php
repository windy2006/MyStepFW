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
    if(!\myStep::checkPower('download')) return;
    $file = array_pop($info_app['path']);
    $idx = explode('.', $file);
    $path = FILE.date($S->upload->path_mode, intval($idx[0]));
    if(is_file($path.$file)) {
        if(strpos($S->watermark->mode, '2')!==false && in_array(strtolower($idx[2]), ['png','jpg'])) {
            $cache = CACHE.'app/CMS/img/'.date($S->upload->path_mode, intval($idx[0])).$file;
            if(!is_file($cache)) {
                $img = new \myImg($path.$file);
                $img->watermark(ROOT.$S->watermark->img, $S->watermark->position, $cache, [
                    'rate' => $S->watermark->img_rate,
                    'alpha' => $S->watermark->alpha,
                    'font' => ROOT.$S->watermark->txt_font,
                    'fontsize' => $S->watermark->txt_fontsize,
                    'fontcolor' => $S->watermark->txt_fontcolor,
                    'bgcolor' => $S->watermark->txt_bgcolor,
                ])->destory();
            }
            \myStep::file($cache);
        } else {
            \myStep::download($file);
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
        $ms_user = \r::cookie('ms_cms_user');
        $user_info = null;
        if(!is_null($ms_user)) {
            list($usr, $pwd) = explode(chr(9), $ms_user);
            $db->build($S->db->pre.'users')
                ->field('group_id,hash')
                ->where('username','=',$usr)
                ->where('password','=',$pwd);
            if($record=$db->record()) {
                \r::s('ms_cms_user', $usr);
                \r::s('ms_cms_user_group', empty($record['hash'])?$record['group_id']:1);
            }
        }
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

function checkVal($para, $key, $val) {
    if(!is_array($para)) return false;
    for($i=0,$m=count($para); $i<$m; $i++) {
        foreach($para[$i] as $k => $v) {
            if($key != $k) continue;
            if($v == $val) return $para[$i];
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
            $id_list = array_column($cat, 'cat_id');
            for($i=0,$m=count($cat);$i<$m;$i++) {
                if($cat[$i]['pid']==0) {
                    $cat[$i]['layer'] = 1;
                } else {
                    $p = $cat[array_search($cat[$i]['pid'], $id_list)];
                    $cat[$i]['layer'] = $p['layer']+1;
                    $cat[$i]['order'] = $p['order'].'.'.$cat[$i]['order'];
                }
            }
            usort($cat, function($a, $b) {
                if($a['layer'] == $b['layer']) {
                    return ($a['order'] == $b['order']) ? 0 : (
                        version_compare($a['order'], $b['order'])==-1 ? -1 : 1
                    );
                } else {
                    return ($a['layer'] < $b['layer']) ? 1 : -1;
                }
            });
            $id_list = array_column($cat, 'cat_id');
            for($i=0,$m=count($cat);$i<$m;$i++) {
                if($cat[$i]['layer']>1) {
                    $pid = array_search($cat[$i]['pid'], $id_list);
                    if(!isset($cat[$pid]['sub'])) $cat[$pid]['sub'] = [];
                    $cat[$pid]['sub'][] = $cat[$i];
                    unset($cat[$i]);
                }
            }
            $cat = array_values($cat);
            $cache_para = $cat;
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

function getPara($list, $key, $val) {
    if(!is_array($list)) return false;
    for($i=0,$m=count($list);$i<$m;$i++) {
        if(!isset($list[$i]) || !isset($list[$i][$key])) return false;
        if($list[$i][$key]==$val) return $list[$i];
    }
    return false;
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

function getPageList($total, $page=1, $page_size=20, $qstr='') {
    if(!is_numeric($page) || $page < 1) $page = 1;
    $page = (INT)$page;
    $page_count = ceil($total/$page_size);
    if($page < 1) $page = 1;
    if($page > $page_count) $page = $page_count;
    if(!empty($qstr)) $qstr .= '&';
    $keys = array_keys(\myReq::r('[ALL]'));
    $qstr = reset($keys).'?'.$qstr.'page=';
    $record_start = ($page-1) * $page_size;
    if($record_start < 0) $record_start = 0;
    $page_arr = array();
    $page_arr['total'] = $total;
    $page_arr['page_current'] = $page;
    $page_arr['page_count'] = $page_count;
    $page_arr['link_first'] = ($page<=1 ? 'javascript:' : $qstr.'1');
    $page_arr['link_prev'] = ($page<=1 ? 'javascript:' : $qstr.($page-1));
    $page_arr['link_next'] = ($page==$page_count ? 'javascript:' : $qstr.($page+1));
    $page_arr['link_last'] = ($page==$page_count ? 'javascript:' : $qstr.$page_count);
    return array($page_arr, $record_start, $page_size);
}

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
            if(isset($cat)) $link .= urlencode($cat['idx']).'/';
            $link .= $data['news_id'];
            break;
        case 'catalog':
            if(isset($cat)) {
                $link .= 'catalog/'.urlencode($cat['idx']).'/';
            }
            break;
        case 'tag':
            $link .= 'tag/'.(is_string($data)?$data:$data['tag']);
            break;
    }
    $link = \myStep::setURL($link);
    if(isset($data['web_id'])) $web = checkVal($website, 'web_id', $data['web_id']);
    if(isset($web) && is_array($web) && $web['web_id']!=$web_info['web_id']) {
        $link = '//'.$web['domain'].$link;
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
    if(!isset($tag_attrs['show_cat'])) $news['catalog'] = '';
    if(isset($tag_attrs['date'])) {
        $news['add_date'] = formatDate($news['add_date'], $tag_attrs['date']);
    } else {
        $news['add_date'] = '';
    }
    $news['custom'] = $tag_attrs['custom'];
    if(preg_match_all('#\{(.+?)\}#', $news['custom'], $matches)) {
        for($i=0,$m=count($matches[1]);$i<$m;$i++) {
            $news['custom'] = str_replace('{'.$matches[1][$i].'}', $news[$matches[1][$i]], $news['custom']);
        }
        $news['custom'] = myEval($news['custom'], true);
    }
    $news['style'] = explode(',', $news['style']);
    $news['subject_styled'] = $news['subject'];
    $news['link'] = \app\CMS\getLink($news);
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
        $news['tags'] .= '<a href="'.\app\CMS\getLink($tags[$i],'tag').'" target="_blank">'.$tags[$i].'</a>';
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
    if(!isset($tag_attrs['show'])) $tag_attrs['show'] = 1023;
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
    $tag_attrs['unit'] = preg_replace('#<!--(link_\w+)-->#', '<?=$page_info[\'\1\']?>', $tag_attrs['unit']);
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
                $pid_list = [$cat_info['pid'], $id];
                while($cat_info['pid']>0) {
                    $cat_info = checkVal($news_cat_plat, 'cat_id', $cat_info['pid']);
                    if($cat_info['pid']==0) break;
                    array_unshift($pid_list, $cat_info['pid']);
                }
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
    ))->field('idx','name as catalog');
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
        $db->build($web['setting']->db->pre.'news_detail', array(
            'mode' => 'left',
            'field' => 'news_id'
        ))->where('content', 'like', $paras['keyword']);
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