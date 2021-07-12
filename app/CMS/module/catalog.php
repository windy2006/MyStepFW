<?PHP
global $catalog, $prefix, $page, $limit, $loop, $query, $count, $page_size;
$catalog = $info_app['path'][1]??'';
if(preg_match('#(%[A-E0-9])#', $catalog)) $catalog = urldecode($catalog);
$cat_info = false;
$prefix = r::g('pre')??'';
$tpl_setting['name'] = 'catalog';
$t = new myTemplate($tpl_setting);

if(!empty($catalog)) {
    if(is_numeric($catalog)) {
        $cat_info = \app\CMS\getPara($news_cat_plat, 'cat_id', $catalog);
    } else {
        $catalog = str_replace('+', ' ', $catalog );
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
if(!empty($catalog)) {
    if(is_file(PATH.'template/custom/cat_'.$cat_info['cat_id'].'.tpl')) {
        $tpl_setting['style'] = 'custom';
        $tpl_setting['name'] = 'cat_'.$cat_info['cat_id'];
    } elseif(!empty($cat_info['type'])) {
        $tpl_setting['name'] .= '_'.$cat_info['type'];
    }
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
    $path_list = [];
    $pid = $cat_info['pid'];
    if($pid==0) $pid = $cat_info['cat_id'];
    while($pid>0) {
        $the_cat = \app\CMS\getPara($news_cat_plat, 'cat_id', $pid);
        $path_list[] = [
            'name' => $the_cat['name'],
            'link' => \app\CMS\getLink($the_cat, 'catalog')
        ];
        $pid = $the_cat['pid'];
    }
    for($i=count($path_list)-1;$i>=0;$i--) {
        $t->setLoop('cat_list', $path_list[$i]);
    }
    if($cat_info['name']!=$path_list[0]['name']) {
        $t->setLoop('cat_list', ['name'=>$cat_info['name'], 'link'=>\app\CMS\getLink($cat_info, 'catalog')]);
    }
} else {
    $page_size = $S->list->txt;
    $S->web->title = $mystep->getLanguage('page_update').'_'.$S->web->title;
    $t->setLoop('cat_list', [
        'name' => $mystep->getLanguage('page_show_all'),
        'link' => '#'
    ]);
}

$db->build($S->db->pre_sub.'news_show')
    ->field('count(*)');
if(!empty($prefix)) $db->build($S->db->pre_sub.'news_show')->where('subject','like','['.$prefix.']%');
if($catalog>0) {
    $db->build($S->db->pre.'news_cat', array(
        'mode' => 'left',
        'field' => 'cat_id'
    ))->where([
            ['cat_id', 'n=', $catalog],
            ['pid', 'n=', $catalog, 'or'],
            ['pid', 'in', '(select cat_id from '.$S->db->pre.'news_cat where pid='.intval($catalog).')', 'or'],
        ], 'and'
    );
}
$count = $db->result();
$page = r::g('page', 'int') ?? 1;
$query = '';
if(!empty($prefix)) $query = 'pre='.$prefix;
$t->assign('prefix', $prefix);
$t->assign('cat_name', $cat_info['name']??$mystep->getLanguage('page_update'));
$t->assign('cat_id', $catalog);
$t->assign('news_cat', myString::toJson($news_cat_plat));
$limit = (($page-1)*$page_size).','.$page_size;
$loop = $S->list->txt;
