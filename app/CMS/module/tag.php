<?PHP
global $tag, $page, $limit, $loop, $query, $count, $page_size;
$tag = $info_app['path'][1]??'';
$tag = myString::setCharset($tag, $S->gen->charset);

$db->build($S->db->pre_sub.'news_tag')
    ->field(['click'=>'(click + 1)'])
    ->where('tag','=', $tag);
$db->update();

$mystep->checkCache($tpl);

$db->build($S->db->pre_sub.'news_show')
    ->field('count(*)')
    ->where('tag','like', '%'.$tag.'%');
$count = $db->result();

$tpl_setting['name'] = 'tag';
$t = new myTemplate($tpl_setting);

$page = r::g('page', 'int') ?? 1;
$page_size = $S->list->txt;
$limit = (($page-1)*$page_size).','.$page_size;
$loop = $S->list->txt;

$t->assign('tag', $tag);
