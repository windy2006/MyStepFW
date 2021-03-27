<?PHP
global $page, $query, $count, $page_size;
$tpl_setting['name'] = 'info_count';
$t = new myTemplate($tpl_setting);

$order = r::g('order');
if(empty($order)) $order='date';
$order_type = r::g('order_type');
if(empty($order_type)) $order_type = 'desc';

$db->build($S->db->pre.'counter')->field('count(*)');
$count = $db->result();

$page = r::g('page', 'int');
$query = 'order='.$order.'&order_type='.$order_type;
list($page_info, $record_start, $page_size) = \app\CMS\getPageList($count, $page, $S->list->txt, $query);

$db->build($S->db->pre.'counter')
    ->order($order, $order_type=='desc')
    ->limit($record_start, $page_size);
$db->select();
while($record = $db->getRS()) {
    s::htmlTrans($record);
    $t->setLoop('record', $record);
}

$db->free();
$t->assign('order', $order);
$t->assign('order_type_org', $order_type);
$t->assign('order_type', $order_type=='asc'?'desc':'asc');
$t->assign('title', $mystep->getLanguage('admin_info_count_title'));
$content = $mystep->render($t);