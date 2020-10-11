<?PHP
$tpl_setting['name'] = 'user_online';
$t = new myTemplate($tpl_setting);

$order = r::g('order');
if(empty($order)) $order='refresh';
$order_type = r::g('order_type');
if(empty($order_type)) $order_type = 'desc';
$keyword = r::g('keyword')??'';

$condition = array();
if(!empty($keyword)) $condition[] = array('data','like',$keyword);
$db->build($s->db->pre.'user_online')->field('count(*)')->where($condition);
$counter = $db->result();

$page = r::g('page', 'int');
list($page_info, $record_start, $page_size) = \app\CMS\getPageList($counter, $page, $s->list->txt, 'keyword='.$keyword.'&order='.$order.'&order_type='.$order_type);
$t->assign($page_info);
$t->assign('record_count', $counter);

$db->build($s->db->pre.'user_online')
    ->order($order, $order_type=='desc')
    ->where($condition)
    ->limit($record_start, $page_size);
$db->select();
while($record = $db->getRS()) {
    $user = r::sessionDecode($record['data']);
    s::htmlTrans($record);
    $record['sid'] = 'Session ID: '.$record['sid'];
    $record['refresh'] = date('Y-m-d H:i:s', $record['refresh']);
    $record['url_simple'] = preg_replace('#&.+$#', '', $record['url']);
    if(strlen($record['url_simple'])>40) $record['url_simple'] = substr($record['url_simple'],0, 25).'...'.substr($record['url_simple'],-12);
    if(isset($user['ms_cms_op'])) {
        $record['username'] = $user['ms_cms_op'];
        $record['group'] = \app\CMS\checkVal($group, 'group_id', $user['ms_cms_group'])['name'];
    } else {
        $record['username'] = 'Guest';
        $record['group'] = 'Visitor';
    }
    $t->setLoop('record', $record);
}
$db->free();

$t->assign('keyword', $keyword);
$t->assign('order', $order);
$t->assign('order_type_org', $order_type);
$t->assign('order_type', $order_type=='asc'?'desc':'asc');
$t->assign('title', $mystep->getLanguage('admin_user_online_title'));
$content = $mystep->render($t);

