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
        CMS::$log = $mystep->getLanguage('admin_sys_op_delete');
        $db->build($S->db->pre.'sys_op')
            ->where('id', 'n=', $id);
        $db->delete();
        CMS::redirect();
        break;
    case 'add_ok':
    case 'edit_ok':
        if(myReq::check('post')) {
            $data = r::p('[ALL]');
            $data['password'] = r::p('password', '!') ?? '';
            // 确认字段保留在前端，服务端独立检查并在写入前剔除；兼容旧页面省略该字段。
            $confirmation = r::p('password_r', '!') ?? r::p('password_c', '!');
            if($confirmation !== null && $data['password'] !== $confirmation) myStep::info('checkform_err_password');
            $flag = false;
            if($data['username'] != $data['username_org']) {
                $db->build($S->db->pre.'sys_op')
                    ->where('username','=',$data['username']);
                $flag = $db->result();
            }
            if($flag!==false) {
                myStep::info(sprintf($mystep->getLanguage('admin_sys_op_error2'), $data['username']));
            }
            CMS::$log = $mystep->getLanguage($method=='add_ok'?'admin_sys_op_add':'admin_sys_op_edit');
            if($data['password'] === '') {
                unset($data['password']);
            } else {
                $data['password'] = myAuth::hashPassword($data['password']);
            }
            unset($data['username_org'], $data['password_c'], $data['password_r']);
            $db->build($S->db->pre.'sys_op')->field($data);
            if($method=='add_ok') {
                $db->insert();
            } else {
                $db->build($S->db->pre.'sys_op')->where('id', 'n=', $id);
                $db->update();
            }
        }
        myStep::$goto_url = \app\CMS\getURL(($S->web->path_admin ?? 'admin_cms').'/setting/sysop/', array_diff_key($_GET, ['id'=>true, 'news_id'=>true]));
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

        $query = http_build_query(['keyword'=>$keyword, 'group_id'=>$group_id, 'order'=>$order, 'order_type'=>$order_type], '', '&', PHP_QUERY_RFC3986);
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
    $tpl->assign('back_url', r::svr('HTTP_REFERER') ?: \app\CMS\getURL($S->web->path_admin.'/setting/sysop/'));
    $tpl->assign('method', $method);
    $db->free();
    return $mystep->render($tpl);
}
