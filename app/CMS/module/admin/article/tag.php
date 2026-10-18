<?PHP
global $web_cur;
if(($web_cur = \app\CMS\checkVal($website, 'web_id', $web_id))!==false) {
    $web_cur['setting'] = new myConfig(PATH.'website/config_'.$web_cur['idx'].'.php');
} else {
    $web_cur = $web_info;
}
switch($method) {
    case 'delete':
        CMS::$log = $mystep->getLanguage('admin_art_tag_delete');
        $pre = $web_cur['setting']->db->pre;
        $tagsTable = $db->quoteIdentifier($pre.'news_tag');
        $newsTable = $db->quoteIdentifier($pre.'news_show');
        $tag = $db->fetchValue('SELECT tag FROM '.$tagsTable.' WHERE id=?', [(int)$id]);
        if($tag!==false) {
            // 参数绑定保护引号；按完整标签筛选，兼容单标签、相邻重复标签及头尾位置。
            $articles=$db->fetchAll("SELECT news_id,tag FROM ".$newsTable." WHERE LOCATE(?,CONCAT(',',tag,','))>0", [','.$tag.',']);
            if($articles===false) myStep::info('error_para');
            foreach($articles as $article) {
                $remaining=array_filter(explode(',',$article['tag']),function($value) use ($tag) { return $value!==$tag; });
                $db->execute('UPDATE '.$newsTable.' SET tag=? WHERE news_id=?', [implode(',',$remaining),$article['news_id']]);
            }
            $db->execute('DELETE FROM '.$tagsTable.' WHERE id=?', [(int)$id]);
        }
        CMS::redirect();
        break;
    case 'rebuild':
        set_time_limit(0);
        CMS::$log = $mystep->getLanguage('admin_art_tag_rebuild');
        $pre = $web_cur['setting']->db->pre;
        $tagsTable = $db->quoteIdentifier($pre.'news_tag');
        $newsTable = $db->quoteIdentifier($pre.'news_show');
        // 先读完再写，避免覆盖活动结果集；按文章去重后一次统计，去掉逐标签全表 LIKE 扫描。
        $articles = $db->fetchAll('SELECT news_id,tag FROM '.$newsTable);
        $existing = $db->fetchAll('SELECT id,tag FROM '.$tagsTable);
        if($articles===false || $existing===false) myStep::info('error_para');
        $counts = []; $now = time();
        foreach($articles as $record) {
            $tags = \app\CMS\normalizeTags($record['tag']);
            $text = implode(',',$tags);
            if($text!==$record['tag']) $db->execute('UPDATE '.$newsTable.' SET tag=? WHERE news_id=?', [$text,$record['news_id']]);
            foreach($tags as $tag) $counts[$tag]=($counts[$tag] ?? 0)+1;
        }
        foreach($existing as $record) {
            $tag=$record['tag'];
            if(!isset($counts[$tag])) $db->execute('DELETE FROM '.$tagsTable.' WHERE id=?', [$record['id']]);
            else $db->execute('UPDATE '.$tagsTable.' SET count=?,update_date=? WHERE id=?', [$counts[$tag],$now,$record['id']]);
        }
        $known = array_fill_keys(array_column($existing,'tag'),true);
        foreach($counts as $tag=>$total) if(!isset($known[$tag])) {
            $db->execute('INSERT INTO '.$tagsTable.' (tag,count,click,add_date,update_date) VALUES (?,?,0,?,?)', [(string)$tag,$total,$now,$now]);
        }
        CMS::redirect();
        break;
    case 'list':
    default:
        $content = build_page();
        break;
}

function build_page() {
    global $mystep, $tpl_setting, $S, $db, $web_cur, $web_id;
    global $page, $query, $count, $page_size;

    $tpl_setting['name'] = 'art_tag';
    $tpl = new myTemplate($tpl_setting, false);

    $keyword = r::g('keyword')??'';
    $tpl->assign('keyword', $keyword);

    $condition = array();
    if(!empty($keyword)) $condition = array('tag','like',$keyword);

    $db->build($web_cur['setting']->db->pre.'news_tag')->field('count(*)')->where($condition);
    $count = $db->result();

    $page = r::g('page', 'int');
    $order = r::g('order');
    if(empty($order)) $order = 'id';
    $order_type = r::g('order_type');
    if(empty($order_type)) $order_type = 'desc';

    $query = http_build_query(['keyword'=>$keyword, 'web_id'=>$web_cur['web_id'], 'order'=>$order, 'order_type'=>$order_type], '', '&', PHP_QUERY_RFC3986);
    list($page_info, $record_start, $page_size) = \app\CMS\getPageList($count, $page, $S->list->txt, $query);

    $db->build($web_cur['setting']->db->pre.'news_tag')
        ->where($condition)
        ->order($order, $order_type=='desc')
        ->limit($record_start, $page_size);
    $db->select();
    while($record = $db->getRS()) {
        s::htmlTrans($record);
        $record['add_date'] = date('Y-m-d', $record['add_date']);
        $record['update_date'] = date('Y-m-d', $record['update_date']);
        $tpl->setLoop('record', $record);
    }
    $tpl->assign('order', $order);
    $tpl->assign('order_type_org', $order_type);
    $tpl->assign('order_type', $order_type=='asc'?'desc':'asc');
    $tpl->assign('title', $mystep->getLanguage('admin_art_tag_title'));
    $tpl->assign('web_id', $web_id??'');
    $tpl->assign('web_id_site', $web_cur['web_id']);
    setWeb($tpl, $web_id);
    return $mystep->render($tpl);
}
