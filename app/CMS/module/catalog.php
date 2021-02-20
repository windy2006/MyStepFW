<?PHP
global $catalog, $prefix, $page, $limit, $loop;
$catalog = $info_app['path'][1]??'';
$cat_info = false;
if($catalog!='') {
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
$tpl_setting['name'] = 'catalog';
if(!empty($catalog)) {
    if(is_file(PATH.'template/custom/cat_'.$cat_info['cat_id'].'.tpl')) {
        $tpl_setting['style'] = 'custom';
        $tpl_setting['name'] = 'cat_'.$cat_info['cat_id'];
    } elseif(!empty($cat_info['type'])) {
        $tpl_setting['name'] .= '_'.$cat_info['type'];
    }
    $s->web->title = $cat_info['name'].'_'.$s->web->title;
    $s->web->keyword = $cat_info['keyword'];
    $s->web->description = $cat_info['comment'];
    $list_limit = array_values((array)$s->list);
    if(isset($list_limit[$cat_info['type']]))    {
        $page_size = $list_limit[$cat_info['type']];
    } else {
        $page_size = $list_limit[0];
    }
} else {
    $page_size = $s->list->txt;
    $s->web->title = $mystep->getLanguage('page_update').'_'.$s->web->title;
}
$page = r::g('page', 'int')??1;
if(!is_numeric($page) || $page < 1) $page = 1;
$prefix = r::g('pre')??'';
$t = new myTemplate($tpl_setting);

$db->build($s->db->pre_sub.'news_show')
    ->field('count(*)');
if(!empty($prefix)) $db->build($s->db->pre_sub.'news_show')->where('subject','like','['.$prefix.']%');
if($catalog>0) {
    $db->build($s->db->pre.'news_cat', array(
        'mode' => 'left',
        'field' => 'cat_id'
    ))->where([
            ['cat_id', 'n=', $catalog],
            ['pid', 'n=', $catalog, 'or']
        ], 'and'
    );
}
$counter = $db->result();
$qstr = '';
if(!empty($prefix)) $qstr = 'pre='.$prefix;
list($page_info, $record_start, $page_size) = \app\CMS\getPageList($counter, $page, $page_size, $qstr);
if($page>$page_info['page_count']) $page = $page_info['page_count'];
if($page < 1) $page = 1;
$t->assign($page_info);
$t->assign('record_count', $counter);
$t->assign('prefix', $prefix);
$t->assign('cat_name', $cat_info['name']??$mystep->getLanguage('page_update'));
$t->assign('cat_id', $catalog);

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
$limit = (($page-1)*$page_size).','.$page_size;
$loop = $s->list->txt;

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