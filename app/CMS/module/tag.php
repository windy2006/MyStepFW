<?PHP
global $tag, $page, $limit, $loop, $query, $count, $page_size;
$tag = urldecode($info_app['path'][1]??'');
$tag = myString::setCharset($tag, $S->gen->charset);

$db->build($S->db->pre_sub.'news_tag')
    ->field(['click'=>'(click + 1)'])
    ->where('tag','=', $tag);
$db->update();

$mystep->checkCache($tpl);

$count = $db->count(\app\CMS\buildSQL(['tag'=>$tag]));
$tpl_setting['name'] = 'tag';
$t = new myTemplate($tpl_setting);
$page = r::g('page', 'int') ?? 1;
list($page_info,$record_start,$page_size) = \app\CMS\getPageList($count,$page,$S->list->txt);
$page = $page_info['page_current'];
$limit = $record_start.','.$page_size;
$loop = $S->list->txt;

$t->assign('tag', $tag);
