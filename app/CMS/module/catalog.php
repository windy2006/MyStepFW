<?PHP
global $catalog, $prefix, $page, $limit, $loop, $query, $count, $page_size;
$catalog = $info_app['path'][1]??'';
$catalog = urldecode($catalog);
$cat_info = false;
$prefix = r::g('pre')??'';
if(!is_string($prefix)) $prefix='';
$tpl_setting['name'] = 'catalog';

if(!empty($catalog)) {
    if(is_numeric($catalog)) {
        $cat_info = \app\CMS\getPara($news_cat_plat, 'cat_id', $catalog);
    } else {
        $cat_info = \app\CMS\getPara($news_cat_plat, 'idx', $catalog);
    }
    if($cat_info!==false) {
        $catalog = $cat_info['cat_id'];
        if(!empty($cat_info['link'])) {
            myStep::redirect($cat_info['link']);
        }
    } else {
        $catalog = '';
    }
}
if($cat_info!==false && is_file(PATH.'template/custom/cat_'.$cat_info['cat_id'].'.tpl')) {
    $tpl_setting['style'] = 'custom';
    $tpl_setting['name'] = 'cat_'.$cat_info['cat_id'];
} elseif(!empty($cat_info['type'])) {
    $tpl_setting['name'] .= '_'.$cat_info['type'];
}
$t = new myTemplate($tpl_setting);
if(!empty($catalog)) {
    $S->web->title = $cat_info['name'].'_'.$S->web->title;
    $S->web->keyword = $cat_info['keyword'];
    $S->web->description = $cat_info['comment'];
    $list_limit = array_values((array)$S->list);
    if(isset($list_limit[$cat_info['type']]))    {
        $page_size = $list_limit[$cat_info['type']];
    } else {
        $page_size = $list_limit[0];
    }

    if(!empty($cat_info['prefix'])) {
        $cat_info['prefix'] = explode(',', $cat_info['prefix']);
    } else {
        $cat_info['prefix'] = [];
    }
    for($i=0,$m=count($cat_info['prefix']);$i<$m;$i++) {
        $t->setLoop('prefix', [
            'name' => $cat_info['prefix'][$i],
            'class' => ($cat_info['prefix'][$i]==$prefix)?'primary':'secondary'
        ]);
    }
    $path_list = \app\CMS\categoryPath($news_cat_plat,$cat_info['cat_id']);
    foreach($path_list as $the_cat) $t->setLoop('cat_list', [
        'name'=>$the_cat['name'], 'link'=>\app\CMS\getLink($the_cat,'catalog')
    ]);
} else {
    $page_size = $S->list->txt;
    $S->web->title = $mystep->getLanguage('page_update').'_'.$S->web->title;
    $t->setLoop('cat_list', [
        'name' => $mystep->getLanguage('page_show_all'),
        'link' => '#'
    ]);
}

$count = $db->count(\app\CMS\buildSQL(['catalog'=>$catalog,'prefix'=>$prefix]));
$page = r::g('page', 'int') ?? 1;
list($page_info,$record_start,$page_size) = \app\CMS\getPageList($count,$page,$page_size);
$page = $page_info['page_current'];
$query = '';
if(!empty($prefix)) $query = http_build_query(['pre'=>$prefix], '', '&', PHP_QUERY_RFC3986);
$t->assign('prefix', $prefix);
$t->assign('cat_name', $cat_info['name']??$mystep->getLanguage('page_update'));
$t->assign('cat_id', $catalog);
$t->assign('news_cat', myString::toJson($news_cat_plat));
$limit = $record_start.','.$page_size;
$loop = $S->list->txt;
