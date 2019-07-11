<?php
namespace app\cms;
\myStep::setPara();
global $mystep, $info_app, $s, $p, $q, $setting_tpl;
if(strpos($p, 'admin_cms/')===0) {
    $s->template->style = 'admin';
    $mystep->addCSS(PATH.'asset/'.$s->template->style.'/style.css');
    $mystep->setting->css = CACHE.'script/'.$info_app['app'].'_admin.css';
    $mystep->addJS(PATH.'asset'.$s->template->style.'/function.js');
    $mystep->setting->js = CACHE.'script/'.$info_app['app'].'_admin.js';
}
if(!is_array($info_app)) $info_app = array();
if(!isset($info_app['path'])) $info_app['path'] = explode('/', trim($p, '/'));
if(!isset($info_app['para'])) parse_str($q, $info_app['para']);
if(!isset($info_app['name'])) $info_app = array_merge($info_app, include(dirname(__FILE__).'/info.php'));

$setting_tpl = array(
    'name' => $s->template->name,
    'path' => PATH.$s->template->path,
    'style' => $s->template->style,
    'path_compile' => CACHE.'template/'.$info_app['app'].'/'
);

function logCheck($show = true) {
    $user = \r::s('ms_user');
    if(empty($user)) {
        if($show) myStep::getModule('login');
        return false;
    }
    return true;
}

function getData($idx) {
    $result = '{"err":"Login First!"}';
    if(logCheck(false)) {
        if(($result = getCache($idx))===false) {
            $result = '{"err":"Data Missing!"}';
        }
    }
    return $result;
}

function rss() {
    return array();
}

function buildCache($idx, $cache_para) {
    $detail = '<?php
return '.var_export($cache_para, true).';';
    $theFile = CACHE.'/app/cms/'.$idx.'.php';
    \myFile::saveFile($theFile, $detail);
    @chmod($theFile, 0666);
    return true;
}

function deleteCache($idx) {
    return \myFile::del(CACHE.'/app/cms/'.$idx.'.php');
}

function checkCache($idx) {
    return is_file(CACHE.'/app/cms/'.$idx.'.php');
}

function getCache($idx) {
    if(!checkCache($idx)) buildList($idx);
    debug_set();
    if(checkCache($idx)) {
        return include(CACHE.'/app/cms/'.$idx.'.php');
    } else {
        return false;
    }
}

function buildList($idx) {
    global $db, $s;
    if(empty($db)) return false;
    $cache_para = array();
    switch($idx) {
        case 'admin_cat':
            $theList = array();
            $db->build($s->db->pre.'admin_cat')
               ->where('pid','n=',0)
               ->order('order', 1)->order('id', 0);
            $db->select();
            while($record=$db->getRS()) {
                \myString::htmlTrans($record);
                $theList[] = $record;
            }
            for($i=0,$m=count($theList); $i<$m; $i++) {
                $theList[$i]['sub'] = array();
                $db->build('[reset]');
                $db->build($s->db->pre.'admin_cat')
                    ->where('pid','n=',$theList[$i]['id'])
                    ->order('order', 1)->order('id', 0);
                $db->select();
                while($record=$db->getRS()) {
                    \myString::htmlTrans($record);
                    $theList[$i]['sub'][] = $record;
                }
            }
            $cache_para[$idx] = $theList;

            $theList = array();
            $db->build('[reset]');
            $db->build($s->db->pre.'admin_cat')
               ->order('order', 1)->order('id', 0);
            $db->select();
            while($record=$db->getRS()) {
                \myString::htmlTrans($record);
                $theList[] = $record;
            }
            $cache_para[$idx.'_plat'] = $theList;
            break;
        case 'news_cat':
            $db->build($s->db->pre.'news_cat')
               ->field('cat_id,web_id,pid,name,order,layer')
               ->order('web_id', 0)->order('order', 0)->order('cat_id', 0);
            $cat = $db->records();
            $id_list = array_column($cat, 'cat_id');
            for($i=0,$m=count($cat);$i<$m;$i++) {
                if($cat[$i]['pid']==0) {
                    $cat[$i]['layer'] = 1;
                    $cat[$i]['order'] = $i;
                } else {
                    $p = $cat[array_search($cat[$i]['pid'], $id_list)];
                    $cat[$i]['layer'] = $p['layer']+1;
                    $cat[$i]['order'] = $p['order'].'.'.$cat[$i]['layer'];
                }
            }
            $cmp = function($a, $b) {
                if ($a['layer'] == $b['layer']) {
                    return 0;
                }
                return ($a['layer'] < $b['layer']) ? 1 : -1;
            };
            usort($cat, $cmp);
            $id_list = array_column($cat, 'cat_id');
            for($i=0,$m=count($cat);$i<$m;$i++) {
                if($cat[$i]['layer']>1) {
                    $pid = array_search($cat[$i]['pid'], $id_list);
                    $cat[$pid]['sub'] = $cat[$pid]['sub'] ?? array();
                    $cat[$pid]['sub'][] = $cat[$i];
                    unset($cat[$i]);
                }
            }
            $cmp = function($a, $b) {
                if ($a['order'] == $b['order']) {
                    return 0;
                }
                return ($a['order'] < $b['order']) ? -1 : 1;
            };
            usort($cat, $cmp);
            $cache_para = $cat;
            break;
        case 'link':
            $link_txt = array();
            $link_img = array();
            $db->build($s->db->pre.'links')
                ->order('level', 1)->order('id', 0);
            $db->select();
            while($record=$db->GetRS()) {
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
            $db->build($s->db->pre.'website')
                ->order('web_id', 0);
            $db->select();
            while($record=$db->GetRS()) {
                $cache_para[] = $record;
            }
            break;
        case 'user_group':
        case 'user_type':
        case 'user_power':
            $theIdx = str_replace('user_', '', $idx);
            $db->build($s->db->pre.$idx)
                ->order($theIdx.'web_id', 0);
            $db->select();
            while($record=$db->GetRS()) {
                $cache_para[] = $record;
            }
            break;
        case 'plugin':
            $db->build($s->db->pre.'plugin')
                ->order('order', 0)->order('id', 0);
            $db->select();
            while($record=$db->GetRS()) {
                $record['url'] = $record['path'].$record['file'];
                $cache_para[] = $record;
            }
            break;
        default:
            $db->build($idx)
                ->order('id', 0);
            $db->select();
            if($db->checkError()) return false;
            while($record=$db->GetRS()) {
                $cache_para[] = $record;
            }
    }
    $db->Free();
    if($cache_para) buildCache($idx, $cache_para);
    return $cache_para ? true : false;
}