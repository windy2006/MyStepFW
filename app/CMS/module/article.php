<?PHP
global $id, $cat_id;
$id = end($info_app['path']);

$page = r::g('page');
if($page!='all') {
    $page = intval($page);
    if($page<1) $page = 1;
}
$db->build($S->db->pre_sub.'news_show')
    ->field('news_id,cat_id,add_date,subject,tag,image,describe,view_lvl,original,views,link,active,expire')
    ->where(is_numeric($id) ? ['news_id', 'n=', $id] : ['idx', '=', md5($id)]);

$db->build($S->db->pre_sub.'news_detail', array(
        'mode' => 'left',
        'field' => 'news_id'
    ))->field('sub_title,content')->order('page');

$records = $cache->getData($db->select(true),1);

if(empty($records)) {
    myStep::info('page_article_missing', ROOT_WEB.$info_app['app']);
}
$record = $records[0];
$id = $record['news_id'];
$cat_id = $record['cat_id'];
if(!is_null($record['active'])
    && ($record['active']=strtotime($record['active']))>0
    && $record['active'] > $S->info->time
) {
    myStep::info('page_article_missing', ROOT_WEB.$info_app['app']);
}
if(!is_null($record['expire'])
    && ($record['expire']=strtotime($record['expire']))>0
    && $record['expire'] < $S->info->time
) {
    myStep::info('page_article_expired', ROOT_WEB.$info_app['app']);
}
$db->build($S->db->pre_sub.'news_show')
    ->field(['views'=>'(views + 1)'])
    ->where('news_id','n=',$id);
$db->update();
if(($cat_info = \app\CMS\getPara($news_cat_plat, 'cat_id', $record['cat_id']))===false) {
    myStep::info('page_article_missing', ROOT_WEB.$info_app['app']);
}
if($record['view_lvl']>$user_info['view_lvl']) {
    myStep::info('page_nopower', ROOT_WEB.$info_app['app']);
}
$mystep->checkCache($tpl);
$page_count = count($records);
if(is_numeric($page)) {
    list($page_info, $record_start, $page_size) = \app\CMS\getPageList($page_count, $page, 1);
    if($page>$page_info['page_count']) $page = $page_info['page_count'];
    if($page < 1) $page = 1;
    $record = $records[$page-1];
    $sub_title = array_map(function($v){
        return $v['sub_title'];
    }, $records);
} else {
    $record = $records[0];
    $sub_title = [$record['sub_title']];
    $record['content'] = preg_replace('#<a id="p(.+?)"#', '<a id="p1-\1"', $record['content']);
    $record['content'] = '<div class="block"><a id="content_0"></a>
<div class="sub-title">'.$record['sub_title'].'</div>
'.$record['content'].'
</div>';
    for($i=1,$m=count($records);$i<$m;$i++) {
        $sub_title[] = $records[$i]['sub_title'];
        $records[$i]['content'] = preg_replace('#<a id="p(.+?)"#', '<a id="p'.($i+1).'-\1"', $records[$i]['content']);
        $record['content'] .= '<hr /><div class="block"><a id="content_'.$i.'"></a>
<div class="sub-title">'.$records[$i]['sub_title'].'</div>
'.$records[$i]['content'].'
</div>';
    }
    $record['sub_title'] = '';
}
unset($records);

$tpl_setting['name'] = 'article';
$t = new myTemplate($tpl_setting);
if(isset($page_info))$t->assign($page_info);

$S->web->title = $record['subject'].'_'.$cat_info['name'].'_'.$S->web->title;
$S->web->keyword = $record['tag'].','.$cat_info['name'];
$S->web->description = $record['describe'];

$S->watermark->mode = explode(',', $S->watermark->mode);
$S->watermark->mode = array_sum($S->watermark->mode);
if($S->watermark->mode & 1) $record['content'] = myString::watermark($record['content'], 2, 5, $S->watermark->credit);
if(empty($record['original'])) $record['original'] = $mystep->getLanguage('page_original');
if(empty($record['image'])) $record['image'] = ROOT_WEB.'static/images/dummy.png';
$t->assign('record', $record);
$t->assign('cat_id', $cat_id);
$t->assign('cat_name', $cat_info['name']);

global $tag;
$tag = explode(',', $record['tag']);
for($i=0,$m=count($tag); $i<$m; $i++) {
    $t->setLoop('tag', array('link'=>\app\CMS\getLink($tag[$i], 'tag'), 'name'=>$tag[$i]));
}
$tag = $record['tag'];

$path_list = [];
$pid = $cat_info['cat_id'];
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
$t->assign('multi_page', ($page_count>1)?'':'d-none');
for($i=0,$m=count($sub_title); $i<$m; $i++) {
    $link = preg_replace('#[&?].*$#','', r::svr('REQUEST_URI')).'&page='.($i+1);
    $t->setLoop('page', array('link'=>$link, 'no'=>($i+1), 'active'=>(($page==$i+1) ? 'active' : '')));
    if($page=='all') {
        $link = r::svr('REQUEST_URI').'#content_'.$i;
    }
    $t->setLoop('sub_title', array('selected'=>($page==$i+1?'selected':''), 'link'=>$link, 'name'=>$sub_title[$i]));
}
$t->setLoop('page', array(
    'link'=>preg_replace('#[&?].*$#','', r::svr('REQUEST_URI')).'&page=all',
    'no'=>$mystep->getLanguage('page_show_all'),
    'active'=>(($page=='all') ? 'active' : '')
));