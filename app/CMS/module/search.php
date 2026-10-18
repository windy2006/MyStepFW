<?PHP
global $k, $query, $count, $page, $page_size, $limit;
$k = r::r('k');
if(!is_string($k) || strlen($k)<4) myStep::info('page_search_error');

$tpl_setting['name'] = 'search';
$t = new myTemplate($tpl_setting);
// 总数和列表复用查询规则；多页正文采用 EXISTS，不重复计算同一文章。
$count = $db->count(\app\CMS\buildSQL(['keyword'=>$k]));
$query = http_build_query(['k'=>$k], '', '&', PHP_QUERY_RFC3986);
$page = r::g('page', 'int') ?? 1;
list($page_info,$record_start,$page_size) = \app\CMS\getPageList($count,$page,$S->list->txt,$query);
$page = $page_info['page_current'];
$limit = $record_start.','.$page_size;

$t->assign('keyword', $k);
$S->web->title = $mystep->getLanguage('page_search').'_'.$S->web->title;