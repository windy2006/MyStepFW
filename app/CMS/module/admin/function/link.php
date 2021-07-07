<?PHP
global $link, $idx;
$link = \app\CMS\getCache('link');
$idx = r::r('idx');
if(!empty($id)) {
    $link_info = \app\CMS\checkVal($link['txt'], 'id', $id);
    if($link_info==false) $link_info = \app\CMS\checkVal($link['img'], 'id', $id);
    if($link_info==false || !checkPower('web', $link_info['web_id'])) {
        myStep::info('admin_func_link_error');
    }
}
switch($method) {
    case 'add':
    case 'edit':
    case 'list':
        $content = build_page($method);
        break;
    case 'delete':
        CMS::$log = $mystep->getLanguage('admin_func_link_delete');
        $db->build($S->db->pre.'links')
            ->where('id', 'n=', $id);
        $db->delete();
        \app\CMS\deleteCache('link');
        CMS::redirect();
        break;
    case 'add_ok':
    case 'edit_ok':
        if(myReq::check('post')) {
            $data = r::p('[ALL]');
            if($method=='add_ok') {
                CMS::$log = $mystep->getLanguage('admin_func_link_add');
                $db->build($S->db->pre.'links')->field($data);
                $db->insert();
            } else {
                CMS::$log = $mystep->getLanguage('admin_func_link_edit');
                $db->build($S->db->pre.'links')->field($data)->where('id','n=',$id);
                $db->update();
            }
            \app\CMS\deleteCache('link');
        }
        myStep::$goto_url = preg_replace('#'.preg_quote($method).'$#', '', r::svr('REQUEST_URI'));
        break;
    default:
        $content = build_page('list');
        break;
}

function build_page($method) {
    global $mystep, $tpl_setting, $S, $db;
    global $id, $idx, $web_id, $website, $web_info;

    $tpl_setting['name'] = 'func_link_'.($method=='list'?'list':'input');
    $tpl = new myTemplate($tpl_setting, false);

    if($method == 'list') {
        global $page, $query, $count, $page_size;
        $condition = array();
        if(!empty($idx)) $condition[] = array('idx','=',$idx);
        if(!empty($web_id)) $condition[] = array('web_id','n=',$web_id);
        $db->build($S->db->pre.'links')
            ->field('count(*)')->where($condition);
        $count = $db->result();
        $order = r::g('order');
        if(empty($order)) $order = 'id';
        $order_type = r::g('order_type');
        if(empty($order_type)) $order_type = 'desc';

        $page = r::g('page', 'int');
        $query = 'web_id='.$web_info['web_id'].'&order='.$order.'&order_type='.$order_type;
        list($page_info, $record_start, $page_size) = \app\CMS\getPageList($count, $page, $S->list->txt, $query);

        $db->build($S->db->pre.'links')
            ->where($condition)
            ->order($order, $order_type=='desc')
            ->limit($record_start, $page_size);
        if($order!='id') $db->build($S->db->pre.'links')->order('id', true);
        $db->select();
        while($record = $db->getRS()) {
            s::htmlTrans($record);
            if(!empty($record['image'])) {
                $record['image'] = '<img width="88" height="31" src="'.$record['image'].'" />';
            } else {
                $record['image'] = '&nbsp;';
            }
            if(($web_cur = \app\CMS\checkVal($website, 'web_id', $record['web_id']))===false) {
                $record['web_id'] = $mystep->getLanguage('admin_all_web');
            } else {
                $record['web_id'] = $web_cur['name'];
            }
            $tpl->setLoop('record', $record);
        }
        $tpl->assign('order', $order);
        $tpl->assign('order_type_org', $order_type);
        $tpl->assign('order_type', $order_type=='asc'?'desc':'asc');
        $tpl->assign('title', $mystep->getLanguage('admin_func_link_title'));
        $tpl->assign('idx', $idx??'');
    } else {
        if($method == 'edit') {
            $db->build($S->db->pre.'links')->where('id','n=',$id);
            if(($record = $db->record())===false) {
                myStep::info('admin_func_link_error');
            }
            $web_id = $record['web_id'];
            $idx = $record['idx'];
            s::htmlTrans($record);
        } else {
            $record['id'] = '0';
            $record['web_id'] = $web_id;
            $record['idx'] = '';
            $record['name'] = '';
            $record['url'] = 'http://';
            $record['level'] = '1';
            $record['image'] = '';
        }
        $tpl->assign($record);
        $tpl->assign('title', $mystep->getLanguage($method == 'add'?'admin_func_link_add':'admin_func_link_edit'));
        $tpl->assign('method', $method);
        $tpl->assign('back_url', r::svr('HTTP_REFERER'));
        $idx = $record['idx'];
        $web_id = $record['web_id'];
    }
    $tpl->assign('web_id', $web_id??'');
    $tpl->assign('web_id_site', $web_info['web_id']);

    $db->build($S->db->pre.'links')->field('distinct')->field('idx');
    $db->select();
    while($record = $db->getRS()) {
        $record['selected'] = $record['idx']==$idx?'selected':'';
        $tpl->setLoop('idx', $record);
    }
    setWeb($tpl, $web_id);
    $db->free();
    return $mystep->render($tpl);
}
