<?PHP
global $db, $S, $cache, $news_cat_plat;
$website = \app\CMS\getCache('website');
if(($web_info = \app\CMS\checkVal($website, 'domain', myReq::server('HTTP_HOST'), true))===false) {
    $web_info = \app\CMS\checkVal($website, 'web_id', 1);
}
$tpl_setting = array(
    'name' => 'rss',
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

$url = (isHttps()?'https':'http').'://'.myReq::svr('HTTP_HOST');

$charset_tag = '<?xml version="1.0" encoding="'.$S->gen->charset.'"?>'."\n";
$tpl->assign('charset_tag', $charset_tag);
$tpl->assign('web_title', $S->web->title);
$tpl->assign('web_description', $S->web->description);
$tpl->assign('web_url', $url);
$tpl->assign('now', date("r"));

$record = array();
$db->connect($S->db->pconnect, $S->db->name);
$db->setCache($cache, 600);

$db->build($S->db->pre_sub.'news_show')
    ->field('news_id,cat_id,web_id,subject,tag,describe,original,add_date')
    ->order('news_id', true)->limit(20);
$db->build($S->db->pre.'news_cat', array(
    'mode' => 'left',
    'field' => 'cat_id'
))->field('name as cat_name');

$db->select();
while($record=$db->getRS()) {
    $record['link'] = $url.\app\CMS\getLink($record);
	$record['add_date'] = date("r", strtotime($record['add_date']));
    if(empty($record['original'])) $record['original'] = $S->web->title;
    $tpl->setLoop('record', $record);
}
$mystep->show($tpl);
myStep::$shown = false;
$mystep->end();
