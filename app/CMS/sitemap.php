<?PHP
global $db, $S, $cache, $news_cat_plat;
$website = \app\CMS\getCache('website');
if(($web_info = \app\CMS\checkVal($website, 'domain', myReq::server('HTTP_HOST'), true))===false) {
    $web_info = \app\CMS\checkVal($website, 'web_id', 1);
}
$tpl_setting = array(
    'name' => 'sitemap',
    'path' => APP.$app.'/template',
    'style' => 'default',
    'path_compile' => CACHE.'template/'.$app.'/'
);
$tpl_cache = array(
    'path' => CACHE.'app/'.$app.'/html/'.$web_info['idx'].'/',
    'expire' => 60*60*24*7
);
$tpl = new myTemplate($tpl_setting, $tpl_cache);
header('Content-Type: application/xml; charset='.$S->gen->charset);
$mystep->checkCache($tpl);

$S->merge(APP.$app.'/config.php');
$db_pre = $S->db->pre;
$S->merge(APP.$app.'/website/config_'.$web_info['idx'].'.php');
$S->db->pre_sub = $S->db->pre;
$S->db->pre = $db_pre;

$charset_tag = '<?xml version="1.0" encoding="'.$S->gen->charset.'"?>'."\n";
$tpl->assign('charset_tag', $charset_tag);
$tpl->assign('now', date("r"));
$from = array("&", "'", '"', ">", "<");
$to = array("&amp;", "&apos;", "&quot;", "&gt;", "&lt;");

$record = array();
$record['url'] = '//'.myReq::svr('HTTP_HOST');
$record['date'] = date("Y-m-d");
$record['priority'] = "1";
$tpl->setLoop("record", $record);

$db->connect($S->db->pconnect, $S->db->name);
$db->setCache($cache, 600);
$news_count_max = $db->result('select max(news_count) as cnt from (select count(*) as news_count from '.$S->db->pre_sub.'news_show where web_id='.$web_info['web_id'].' group by cat_id) as tmp');
if($news_count_max==0) $news_count_max = 1;
$news_cat = \app\CMS\getCache('news_cat');
$news_cat_plat = $cache->func('\app\CMS\setCatList', [$news_cat]);

for($i=0, $m=count($news_cat_plat); $i<$m; $i++) {
    if($news_cat_plat[$i]['web_id']!=$web_info['web_id']) continue;
    $record = array();
    $record['url'] = empty($news_cat_plat[$i]['link']) ? \app\CMS\getLink($news_cat_plat[$i], 'catalog') : $news_cat_plat[$i]['link'];
    $record['url'] = str_replace($from, $to, $record['url']);
    $record['date'] = substr($db->result('select max(add_date) from '.$S->db->pre_sub.'news_show where cat_id='.$news_cat_plat[$i]['cat_id']), 0, 10);
    if(empty($record['date'])) $record['date'] = date("Y-m-d");

    $news_count_current = $db->result('select count(*) from '.$S->db->pre_sub.'news_show where cat_id='.$news_cat_plat[$i]['cat_id']);
    $record['priority'] = $news_count_current/$news_count_max;
    if($news_count_current>0) $record['priority'] += 0.1;
    $record['priority'] = round(ceil($record['priority']*10)/10, 1);
    if($record['priority']>1) $record['priority'] = 1;
    $tpl->setLoop("record", $record);
}
$mystep->show($tpl);
myStep::$shown = false;
$mystep->end();