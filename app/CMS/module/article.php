<?PHP
global $id, $cat_id;
$id = end($info_app['path']);
$id = rawurldecode((string)$id);

$page = r::g('page');
if($page!='all') {
    $page = intval($page);
    if($page<1) $page = 1;
}
if(!isset($S->db->pre_sub)) $S->db->pre_sub = $S->db->pre;
$article_web_id=(int)$web_info['web_id'];
$resolved_id=ctype_digit($id)?(int)$id:\app\CMS\resolveArticleAlias($id,$article_web_id);
if($resolved_id===null){
    // 兼容尚未迁移及旧版标题hash入口；先确定唯一文章，再连接正文，避免同名文章正文混合。
    $resolved_id=$db->fetchValue('SELECT news_id FROM '.$db->quoteIdentifier($S->db->pre_sub.'news_show').' WHERE web_id=? AND (idx=? OR idx=?) ORDER BY news_id LIMIT 1',[$article_web_id,md5($id),md5(str_replace(' ','_',$id))]);
}
$db->build($S->db->pre_sub.'news_show')
    ->field('news_id,web_id,idx,cat_id,add_date,subject,tag,image,describe,view_lvl,original,views,link,active,expire')
    ->where('news_id','n=', $resolved_id===false||$resolved_id===null?-1:(int)$resolved_id)
    ->where('web_id','n=',$article_web_id);

$db->build($S->db->pre_sub.'news_detail', array(
        'mode' => 'left',
        'field' => 'news_id'
    ))->field('sub_title,content')->order('page');

$records = $cache->getData($db->select(true), 'all', 1);

if(empty($records)) {
    myStep::info('page_article_missing', ROOT_APP);
}

$record = $records[0];
$id = $record['news_id'];
$cat_id = $record['cat_id'];
if(!is_null($record['active'])
    && ($record['active']=strtotime($record['active']))>0
    && $record['active'] > $S->info->time
) {
    myStep::info('page_article_missing', ROOT_APP);
}
if(!is_null($record['expire'])
    && ($record['expire']=strtotime($record['expire']))>0
    && $record['expire'] < $S->info->time
) {
    myStep::info('page_article_expired', ROOT_APP);
}
if(($cat_info = \app\CMS\getPara($news_cat_plat, 'cat_id', $record['cat_id']))===false) {
    myStep::info('page_article_missing', ROOT_APP);
}
if($record['view_lvl']>$user_info['view_lvl']) {
    myStep::info('page_nopower', ROOT_APP);
}
// 在权限/生效时间检查后归并旧入口；旧URL不重复计数，不能泄露无权阅读文章的标题。
$page_count=count($records);
if(is_numeric($page))$page=min(max(1,(int)$page),max(1,$page_count));
if(\app\CMS\getArticleAlias($id,$article_web_id)!==null){
    $canonical_path=\app\CMS\getLink($record);
    if($page==='all'||(is_numeric($page)&&$page>1))$canonical_path=\app\CMS\queryURL($canonical_path,['page'=>$page]);
    if(\app\CMS\articleRoutePath(myReq::svr('REQUEST_URI'))!==\app\CMS\articleRoutePath($canonical_path))myStep::redirect($canonical_path,301);
    $mystep->setAddedContent('start','<link rel="canonical" href="'.htmlspecialchars(\app\CMS\articleCanonicalURL($record,$page),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'" />');
}
$db->build($S->db->pre_sub.'news_show')->field(['views'=>'(views + 1)'])->where('news_id','n=',$id);
$db->update();
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

// 空设置在PHP8.5不能参与array_sum；复选值按位合并，避免重复值把文字水印变成图片水印。
$watermark_values = is_array($S->watermark->mode) ? $S->watermark->mode : explode(',', (string)$S->watermark->mode);
$watermark_mode = 0;
foreach($watermark_values as $watermark_value) {
    if((is_int($watermark_value) && $watermark_value >= 0 && $watermark_value <= 3)
        || (is_string($watermark_value) && preg_match('/\A[0-3]\z/D', trim($watermark_value)))) {
        $watermark_mode |= (int)$watermark_value;
    }
}
$S->watermark->mode = $watermark_mode;
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

// 异常栏目关系不能阻塞前台请求。
$path_list = \app\CMS\categoryPath($news_cat_plat,$cat_info['cat_id']);
foreach($path_list as $the_cat) $t->setLoop('cat_list', [
    'name'=>$the_cat['name'], 'link'=>\app\CMS\getLink($the_cat,'catalog')
]);
$t->assign('multi_page', ($page_count>1)?'':'d-none');
for($i=0,$m=count($sub_title); $i<$m; $i++) {
    $link = \app\CMS\queryURL(\app\CMS\getLink($record), ['page'=>$i+1>1?$i+1:null]);
    $t->setLoop('page', array('link'=>$link, 'no'=>($i+1), 'active'=>(($page==$i+1) ? 'active' : '')));
    if($page=='all') {
        $link = r::svr('REQUEST_URI').'#content_'.$i;
    }
    $t->setLoop('sub_title', array('selected'=>($page==$i+1?'selected':''), 'link'=>$link, 'name'=>$sub_title[$i]));
}
$t->setLoop('page', array(
    'link'=>\app\CMS\queryURL(\app\CMS\getLink($record), ['page'=>'all']),
    'no'=>$mystep->getLanguage('page_show_all'),
    'active'=>(($page=='all') ? 'active' : '')
));