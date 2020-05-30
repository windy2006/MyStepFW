<?PHP
global $tag, $page, $limit, $loop;
$tag = $info_app['path'][1]??'';
$tag = myString::setCharset($tag, $s->gen->charset);

$db->build($s->db->pre_sub.'news_tag')
    ->field(['click'=>'(click + 1)'])
    ->where('tag','=', $tag);
$db->update();

$mystep->checkCache($tpl);

$db->build($s->db->pre_sub.'news_show')
    ->field('count(*)')
    ->where('tag','like', '%'.$tag.'%');
$counter = $db->result();

$tpl_setting['name'] = 'tag';
$t = new myTemplate($tpl_setting);

$page = r::g('page', 'int')??1;
if(!is_numeric($page) || $page < 1) $page = 1;
$page_size = $s->list->txt;
list($page_info, $record_start, $page_size) = \app\CMS\getPageList($counter, $page, $page_size);
if($page>$page_info['page_count']) $page = $page_info['page_count'];
if($page < 1) $page = 1;
$t->assign($page_info);
$t->assign('record_count', $counter);
$limit = (($page-1)*$page_size).','.$page_size;
$loop = $s->list->txt;

$t->assign('tag', $tag);
