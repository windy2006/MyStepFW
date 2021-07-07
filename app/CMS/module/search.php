<?PHP
global $k, $query, $count, $page, $page_size, $limit;
$k = r::r('k');
if(strlen($k)<4) myStep::info('page_search_error');

$tpl_setting['name'] = 'search';
$t = new myTemplate($tpl_setting);
$db->build($S->db->pre_sub.'news_show')
    ->field('count(*)');
$db->build($S->db->pre_sub.'news_detail', array(
    'mode' => 'left',
    'field' => 'news_id'
))->where('content', 'like', $k);

$query = 'k='.$k;
$count = $db->result();
$page = r::g('page', 'int') ?? 1;
$page_size = $S->list->txt;
$limit = (($page-1)*$page_size).','.$page_size;

$t->assign('keyword', $k);
$S->web->title = $mystep->getLanguage('page_search').'_'.$S->web->title;