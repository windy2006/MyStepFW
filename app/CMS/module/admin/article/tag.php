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
        $db->build($web_cur['setting']->db->pre.'news_tag')->field('tag')->where('id','n=',$id);
        if($tag = $db->result()) {
            $db->build($web_cur['setting']->db->pre.'news_show')
                ->field(['tag'=>"(REPLACE(tag,'".$tag.",',''))"])
                ->where('tag','like',$tag);
            $db->update();
            $db->build($web_cur['setting']->db->pre.'news_show')
                ->field(['tag'=>"(REPLACE(tag,',".$tag."',''))"])
                ->where('tag','like',$tag);
            $db->update();
            $db->build($web_cur['setting']->db->pre.'news_tag')->where('id','n=',$id);
            $db->delete();
        }
        CMS::redirect();
        break;
    case 'rebuild':
        set_time_limit(0);
        CMS::$log = $mystep->getLanguage('admin_art_tag_rebuild');
        $db2 = new myDB('mysql', $S->db->host, $S->db->user, $S->db->password, $S->db->charset);
        $db2->connect(false, $S->db->name);
        $db2->build($web_cur['setting']->db->pre.'news_tag')
            ->field(['count'=>0]);
        $db2->update();
        $db->reconnect(true, $S->db->name);

        $n = 1;
        $db->build($web_cur['setting']->db->pre.'news_show')
            ->field('news_id,tag')
            ->order('news_id');
        $db->select();
        while($record = $db->getRS()) {
            $the_tag = $record['tag'];
            $the_tag = str_replace('，', ',', $the_tag);
            $the_tag = str_replace('；', ',', $the_tag);
            $the_tag = str_replace(' ', '_', $the_tag);
            $the_tag = explode(',', $the_tag);
            for($n=0,$m=count($the_tag); $n<$m; $n++) {
                $the_tag[$n] = trim($the_tag[$n], '_');
                if(strlen($the_tag[$n])<4 || preg_match('/[\d\.]+/', $the_tag[$n])) {
                    $db2->build($web_cur['setting']->db->pre.'news_show')
                        ->field(['tag'=>"(replace(tag, '".$the_tag[$n].",', ''))"])
                        ->where('news_id', 'n=', $record['news_id']);
                    $db2->update();
                    $db2->build($web_cur['setting']->db->pre.'news_show')
                        ->field(['tag'=>"(replace(tag, ',".$the_tag[$n]."', ''))"])
                        ->where('news_id', 'n=', $record['news_id']);
                    $db2->update();
                    continue;
                }
                if(strlen($the_tag[$n]>50)) {
                    $the_tag[$n] = s::substr($the_tag[$n], 0, 50);
                }
                $db2->build($web_cur['setting']->db->pre.'news_tag')
                    ->field('id')
                    ->where('tag','=',$the_tag[$n]);
                if($db2->result()) {
                    $db2->build($web_cur['setting']->db->pre.'news_tag')
                        ->field([
                            'count'=>'+1',
                            'update_date'=>'(UNIX_TIMESTAMP())'
                        ])
                        ->where('tag','=',$the_tag[$n]);
                    $db2->update();
                } else {
                    $db2->build($web_cur['setting']->db->pre.'news_tag')
                        ->values(0, $the_tag[$n], 1, 0, 'UNIX_TIMESTAMP()', 'UNIX_TIMESTAMP()');
                    $db2->insert();
                }
            }
            if(++$n%50===0) {
                $db2->reconnect(false, $S->db->name);
            }
        }
        $db2->build($web_cur['setting']->db->pre.'news_tag')
            ->where([
                array('click','n<',5),
                array('add_date','f<','UNIX_TIMESTAMP()-60*60*24*10','and')
            ]);
        $db2->delete();
        $db->free();

        $n = 1;
        $db->build($web_cur['setting']->db->pre.'news_tag')->field('id,tag');
        $db->select();
        while($record = $db->getRS()) {
            $db2->build($web_cur['setting']->db->pre.'news_show')
                ->field('count(*)')
                ->where('tag','like',$record['tag']);
            $counter = $db2->result();
            $db2->build($web_cur['setting']->db->pre.'news_tag')
                ->field(['count'=>$counter])
                ->where('id','n=',$record['id']);
            $db2->update();
            if(++$n%50===0) {
                $db2->reconnect(false, $S->db->name);
            }
        }
        $db2->build($web_cur['setting']->db->pre.'news_tag')
            ->where('count','n=', 0);
        $db2->delete();
        $db2->close();
        unset($db2);
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

    $query = 'keyword='.$keyword.'&web_id='.$web_cur['web_id'].'&order='.$order.'&order_type='.$order_type;
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
