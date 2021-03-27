<?PHP
global $sys_group;
if(empty($id)) $id = r::r('id');
$sys_group = \app\CMS\getCache('sys_group');
switch($method) {
    case 'add':
    case 'edit':
    case 'list':
        $content = build_page($method);
        break;
    case 'delete':
        cms::$log = $mystep->getLanguage('admin_sys_op_delete');
        $db->build($S->db->pre.'sys_op')
            ->where('id', 'n=', $id);
        $db->delete();
        cms::redirect();
        break;
    case 'add_ok':
    case 'edit_ok':
        if(myReq::check('post')) {
            $data = r::p('[ALL]');
            $flag = false;
            if($data['username'] != $data['username_org']) {
                $db->build($S->db->pre.'sys_op')
                    ->where('username','=',$data['username']);
                $flag = $db->result();
            }
            if($flag!==false) {
                myStep::info(sprintf($mystep->getLanguage('admin_sys_op_error2'), $data['username']));
            }
            cms::$log = $mystep->getLanguage($method=='add_ok'?'admin_sys_op_add':'admin_sys_op_edit');
            if(empty($data['password'])) {
                unset($data['password']);
            } else {
                $data['password'] = md5($data['password']);
            }
            unset($data['username_org'], $data['password_c']);
            $db->build($S->db->pre.'sys_op')->field($data);
            if($method=='add_ok') {
                $db->insert();
            } else {
                $db->update();
            }
        }
        myStep::$goto_url = preg_replace('#'.preg_quote($method).'$#', '', r::svr('REQUEST_URI'));
        break;
    default:
        $content = build_page('list');
}

function build_page($method) {
    global $mystep, $tpl_setting, $S, $db;
    global $id, $web_info, $sys_group;
    
    $tpl_setting['name'] = 'set_sysop_'.($method=='list'?'list':'input');
    $tpl = new myTemplate($tpl_setting, false);

    if($method == 'list') {
        global $page, $query, $count, $page_size;
        $keyword = r::g('keyword')??'';
        $group_id = r::g('group_id')??'';
        $condition = array();
        if(!empty($keyword)) $condition[] = array('username', 'like', $keyword);
        if(!empty($group_id)) $condition[] = array('group_id', 'n=', $group_id);

        $db->build($S->db->pre.'sys_op')->field('count(*)')->where($condition);
        $count = $db->result();

        $page = r::g('page', 'int');
        $order = r::g('order');
        if(empty($order)) $order = 'id';
        $order_type = r::g('order_type');
        if(empty($order_type)) $order_type = 'desc';

        $query = 'keyword='.$keyword.'&web_id='.$web_info['web_id'].'&order='.$order.'&order_type='.$order_type;
        list($page_info, $record_start, $page_size) = \app\CMS\getPageList($count, $page, $S->list->txt, $query);

        $db->build($S->db->pre.'sys_op')
            ->where($condition)
            ->order($order, $order_type=='desc')
            ->limit($record_start, $page_size);
        if($order!='id') $db->build($S->db->pre.'sys_op')->order('id', true);
        $db->select();
        while($record = $db->getRS()) {
            s::htmlTrans($record);
            $record['group_name'] = '';
            if($group_info = \app\CMS\getPara($sys_group, 'group_id', $record['group_id'])) {
                $record['group_name'] = $group_info['name'];
            }
            $tpl->setLoop('record', $record);
        }
        $tpl->assign('group_id', $group_id);
        $tpl->assign('keyword', $keyword);
        $tpl->assign('order', $order);
        $tpl->assign('order_type_org', $order_type);
        $tpl->assign('order_type', $order_type=='asc'?'desc':'asc');
        $tpl->assign('title', $mystep->getLanguage('admin_sys_op_title'));
    } elseif($method=='edit') {
        $db->build($S->db->pre.'sys_op')
            ->where('id', 'n=', $id);
        if(($record = $db->record())===false) {
            myStep::info('admin_sys_op_error');
        }
        $group_id = $record['group_id'];
        $tpl->assign($record);
        $tpl->assign('title', $mystep->getLanguage('admin_sys_op_edit'));
    } else {
        $group_id = 0;
        $record['id'] = 0;
        $record['username'] = '';
        $record['email'] = '';
        $tpl->assign($record);
        $tpl->assign('title', $mystep->getLanguage('admin_sys_op_add'));
    }
    for($i=0, $m=count($sys_group); $i<$m; $i++) {
        $sys_group[$i]['selected'] = ($sys_group[$i]['group_id']==$group_id?'selected':'');
        $tpl->setLoop('sys_group', $sys_group[$i]);
    }
    $tpl->assign('back_url', r::svr('HTTP_REFERER'));
    $tpl->assign('method', $method);
    $db->free();
    return $mystep->render($tpl);
}
