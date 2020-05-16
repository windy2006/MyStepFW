<?PHP
$tpl_setting['name'] = 'info_count';
$t = new myTemplate($tpl_setting);

$order = r::g('order');
if(empty($order)) $order='date';
$order_type = r::g('order_type');
if(empty($order_type)) $order_type = 'desc';

$db->build($s->db->pre.'counter')->field('count(*)');
$counter = $db->result();

$page = r::g('page', 'int');
list($page_info, $record_start, $page_size) = \app\cms\getPageList($counter, $page, $s->list->txt, 'order='.$order.'&order_type='.$order_type);
$t->assign($page_info);
$t->assign('record_count', $counter);

$db->build($s->db->pre.'counter')
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