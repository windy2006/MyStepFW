<?php
global $page, $query, $count, $page_size;
$setting = new myConfig(__DIR__.'/../config.php');
$db = new myDb('sqlite', __DIR__.'/../data.db');
$list = include(__DIR__.'/../ban_ip.php');
$flag = false;

$m = r::g('m');
$ip = r::g('ip');
switch($m) {
    case 'ban':
        $ip = str_replace(' ', '', $ip);
        $ip = explode(',', $ip);
        $ip = $ip[0];
        if($ip==myReq::ip()) myStep::redirect();
        $msg = r::g('msg');
        $list[$ip] = empty($msg) ? $setting->forward : $msg;
        $flag = true;
        break;
    case 'unban':
        unset($list[$ip]);
        $flag = true;
    case 'del':
        if(strpos($ip, ',')) {
            $ip = str_replace(' ', '', $ip);
            $ip = explode(',', $ip);
        }
        $db->build('requests')->where('ip','=',$ip,'or');
        $db->delete();
        myStep::redirect();
        break;
    case 'reset':
        $db->close();
        myFile::del(__DIR__.'/../data.db');
        myFile::copy(__DIR__.'/../data - empty.db', __DIR__.'/../data.db');
        /*
        $db->query('DELETE FROM requests');
        $db->execute('VACUUM');
        */
        /*
        $db->query('DROP table requests');
        $db->execute('VACUUM');
        $db->query("CREATE TABLE if not exists requests(
            id INTEGER PRIMARY KEY autoincrement, 
            ip varchar(40),
            url varchar(255), 
            qry varchar(255), 
            ua varchar(255), 
            cnt integer,
            req DATETIME
        )");
        */
        myStep::redirect();
        break;
}

if($flag) {
    krsort($list, SORT_NUMERIC);
    $result = '<?PHP
return '.var_export($list, true).';';
    myFile::saveFile(__DIR__.'/../ban_ip.php', $result);
    $db->close();
    myStep::redirect();
}

function getPageList($total, $page=1, $page_size=20, $qstr='') {
    if(!is_numeric($page) || $page < 1) $page = 1;
    $page = (INT)$page;
    $page_count = ceil($total/$page_size);
    if($page < 1) $page = 1;
    if($page > $page_count) $page = $page_count;
    if(!empty($qstr)) $qstr .= '&';
    $keys = array_keys(\myReq::r('[ALL]'));
    $qstr = reset($keys).'?'.$qstr.'page=';
    $record_start = ($page-1) * $page_size;
    if($record_start < 0) $record_start = 0;
    $page_arr = array();
    $page_arr['total'] = $total;
    $page_arr['page_current'] = $page;
    $page_arr['page_count'] = $page_count;
    $page_arr['link_first'] = ($page<=1 ? 'javascript:' : $qstr.'1');
    $page_arr['link_prev'] = ($page<=1 ? 'javascript:' : $qstr.($page-1));
    $page_arr['link_next'] = ($page==$page_count ? 'javascript:' : $qstr.($page+1));
    $page_arr['link_last'] = ($page==$page_count ? 'javascript:' : $qstr.$page_count);
    return array($page_arr, $record_start, $page_size);
}

function parsePages(\myTemplate &$tpl, &$tag_attrs = array()){
    global $tpl_setting;
    $tpl_content = $tpl->getTemplate(__DIR__.'/../template/block_pages.tpl');
    list($block, $tag_attrs['unit'], $tag_attrs['unit_blank']) = $tpl->getBlock($tpl_content, 'if');
    $tag_attrs['unit'] = preg_replace('#<!--(link_\w+)-->#', '<?=$page_info[\'\1\']?>', $tag_attrs['unit']);
    $tag_attrs['unit'] = str_replace('<!--page_count-->', '<?=$page_info[\'page_count\']?>', $tag_attrs['unit']);
    $tag_attrs['unit'] = stripslashes($tag_attrs['unit']);
    $tag_attrs['unit_blank'] = stripslashes($tag_attrs['unit_blank']);
    $content = <<<'mytpl'
<?PHP
list($page_info) = getPageList({myTemplate::count}, {myTemplate::page}, {myTemplate::size}, {myTemplate::query});
if($page_info['page_count']>1) {
?>
{myTemplate::unit}
<?PHP
} elseif({myTemplate::count}>0) {
?>
{myTemplate::unit_blank}
<?PHP
}
?>
mytpl;
    return str_replace($block, $content, $tpl_content);
}
$mystep->regTag('pages', 'parsePages');

$order = r::g('order');
if(empty($order)) $order='req';
$order_type = r::g('order_type');
if(empty($order_type)) $order_type = 'desc';
$keyword = r::g('keyword')??'';
$mode = r::g('mode');
if(empty($mode)) $mode='';

$condition = array();
if(!empty($keyword)) $condition = [
    array('ip','like',$keyword, 'or'),
    array('url','like',$keyword, 'or'),
    array('qry','like',$keyword, 'or'),
    array('ua','like',$keyword, 'or'),
];

$db->build('requests')->where($condition);
if(!empty($mode)) {
    $db->build('requests')
       ->group($mode)
       ->field('ip', 'url', 'qry', 'ua', 'req', 'count(*) as cnt');
    $order='cnt';
    $order_type = 'desc';
}
$sql = $db->select(1);

$count = $db->result('select count(*) from ('.$sql.')');
$page = r::g('page', 'int');
$query = 'mode='.$mode.'&keyword='.$keyword.'&order='.$order.'&order_type='.$order_type;
list($page_info, $record_start, $page_size) = getPageList($count, $page, 20, $query);

$db->build('requests')
    ->order($order, $order_type=='desc')
    ->limit($record_start, $page_size);
$db->select();
while($record = $db->getRS()) {
    if(strlen($record['url'])>40) {
        $record['url2'] = substr($record['url'],0, 25).'...'.substr($record['url'],-12);
    } else {
        $record['url2'] = $record['url'];
    }
    $record['req'] = date('Y-m-d H:i:s', $record['req']);
    $record['mode'] = isset($list[$record['ip']]) ? 'unban' : 'ban';
    $tpl_sub->setLoop('record', $record);
}
$db->free();

$tpl_sub->assign('mode', $mode);
$tpl_sub->assign('keyword', $keyword);
$tpl_sub->assign('order', $order);
$tpl_sub->assign('order_type_org', $order_type);
$tpl_sub->assign('order_type', $order_type=='asc'?'desc':'asc');
$tpl_sub->assign('msg', $setting->forward);
$db->close();