<?PHP
global $page, $query, $count, $page_size;
if($method=='clean') {
    cms::$log = $mystep->getLanguage('admin_info_log_clean');
    $db->query('truncate table '.$db->safeName($web_info['setting']->db->pre.'sys_log'));
    cms::redirect();
} elseif($method=='download') {
    cms::$log = $mystep->getLanguage('admin_info_log_download');
    $db->build($S->db->pre.'sys_log')
        ->order('id', true);
    $db->select();
    $content = '';
    while($record = $db->getRS()) {
        $content .= join(',', $record).chr(10);
    }
    getOB();
    $content = preg_replace('/\n+/', chr(10), $content);
    $content = str_replace(chr(10), chr(13).chr(10), $content);
    header('Content-type: text/plain');
    header('Accept-Ranges: bytes');
    header('Accept-Length: '.strlen($content));
    header('Content-Disposition: attachment; filename='.date('Ymd').'_log.txt');
    echo $content;
    exit();
}

$tpl_setting['name'] = 'info_log';
$t = new myTemplate($tpl_setting);

$page = r::g('page', 'int');
$order = r::g('order');
if(empty($order)) $order='id';
$order_type = r::g('order_type');
if(empty($order_type)) $order_type = 'desc';

$db->build($S->db->pre.'sys_log')->field('count(*)');
$count = $db->result();
$t->setIf('empty', ($count==0));

$query = 'order='.$order.'&order_type='.$order_type;
list($page_info, $record_start, $page_size) = \app\CMS\getPageList($count, $page, $S->list->txt, $query);

$db->build($S->db->pre.'sys_log')
    ->order($order, $order_type=='desc')
    ->limit($record_start, $page_size);
if($order!='id') $db->build($S->db->pre.'sys_log')->order('id', true);
$db->select();
while($record = $db->getRS()) {
    s::htmlTrans($record);
    $record['time'] = date('Y-m-d H:i:s', $record['time']);
    $record['link_short'] = strlen($record['link'])>40 ? shortUrl($record['link']) : $record['link'];
    $t->setLoop('record', $record);
}
$t->assign('order', $order);
$t->assign('order_type_org', $order_type);
$t->assign('order_type', $order_type=='asc'?'desc':'asc');
$t->assign('title', $mystep->getLanguage('admin_info_log_title'));
$content = $mystep->render($t);
