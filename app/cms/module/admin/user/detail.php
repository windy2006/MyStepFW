<?php
if(empty($id)) $id = r::r('user_id');
switch($method) {
	case 'add':
	case 'edit':
	case 'list':
        $content = build_page($method);
		break;
	case 'delete':
		cms::$log = $mystep->getLanguage('admin_user_detail_delete');
        $db->build($s->db->pre.'users')
            ->where('user_id','n=',$id);
        $db->delete();
        cms::redirect();
		break;
	case 'add_ok':
	case 'edit_ok':
        if(!myReq::check('post')) {
            cms::redirect();
        }
        $data = r::p('[ALL]');
        if($data['username'] != $data['username_org']) {
            $db->build($s->db->pre.'users')->field('user_id')->where('username','=',$data['username']);
            if($db->result()!==false) {
                myStep::info(sprintf($mystep->getLanguage('admin_user_detail_error2'), $data['username']));
            }
        }
        cms::$log = $mystep->getLanguage($method=='add_ok'?'admin_user_detail_add':'admin_user_detail_edit');
        if(empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = md5($data['password']);
        }
        unset($data['user_id'], $data['username_org'], $data['password_r']);
        if($method=='add_ok') {
            $data['reg_date'] = $s->info->time;
            $db->build($s->db->pre.'users')->field($data);
            $db->insert();
        } else {
            $db->build($s->db->pre.'users')->field($data)->where('user_id','n=',$id);
            $db->update();
        }
        myStep::$goto_url = preg_replace('#'.preg_quote($method).'$#', '', r::env('REQUEST_URI'));
		break;
	default:
        $content = build_page('list');
}

function build_page($method) {
    global $mystep, $tpl_setting, $s, $db, $id, $user_group;

    $tpl_setting['name'] = 'user_detail_'.($method=='list'?'list':'input');
    $tpl = new myTemplate($tpl_setting, false);
    $user_group = \app\cms\getCache('user_group');

	if($method == 'list') {
		$order = r::g('order');
        if(empty($order)) $order='user_id';
		$order_type = r::g('order_type');
		if(empty($order_type)) $order_type = 'desc';
		$keyword = r::g('keyword')??'';
		$group_id = r::g('group_id')??'';

		$condition = array();
		if(!empty($keyword)) $condition[] = array('username', 'like', $keyword);
		if(!empty($group_id)) $condition[] = array('group_id', 'n=', $group_id);

        $db->build($s->db->pre.'users')
            ->field('count(*)')->where($condition);
		$counter = $db->result();

		$page = r::g('page', 'int');
        list($page_info, $record_start, $page_size) = \app\cms\getPageList($counter, $page, $s->list->txt, 'keyword='.$keyword.'&group_id='.$group_id.'&order='.$order.'&order_type='.$order_type);
        $tpl->assign($page_info);
        $tpl->assign('record_count', $counter);

        $db->build($s->db->pre.'users')
            ->where($condition)
            ->order($order, $order_type=='desc')
            ->limit($record_start, $page_size);
        if($order!='user_id') $db->build($s->db->pre.'users')->order('user_id', true);
        $db->select();
		while($record = $db->getRS()) {
			s::htmlTrans($record);
			$record['reg_date'] = date('Y-m-d H:i:s', $record['reg_date']);
            $group_info = \app\cms\getPara($user_group, 'group_id', $record['group_id']);
			$record['group_name'] = $group_info['name'];
			$tpl->setLoop('record', $record);
		}

        $tpl->assign('group_id', $group_id);
        $tpl->assign('keyword', $keyword);
        $tpl->assign('order', $order);
        $tpl->assign('order_type_org', $order_type);
        $tpl->assign('order_type', $order_type=='asc'?'desc':'asc');
		$tpl->assign('title', $mystep->getLanguage('admin_user_detail_title'));
	} elseif($method=='edit') {
        $db->build($s->db->pre.'users')->where('user_id','n=',$id);
        if(($record = $db->record())===false) {
            myStep::info('admin_user_detail_error');
        }
		$group_id = $record['group_id'];
		$tpl->assign($record);
        $tpl->assign('title', $mystep->getLanguage('admin_user_detail_edit'));
	} else {
		$group_id = 0;
		$record['user_id'] = 0;
		$record['username'] = '';
		$record['email'] = '';
		$tpl->assign($record);
        $tpl->assign('title', $mystep->getLanguage('admin_user_detail_add'));
	}
	for($i=1,$m=count($user_group); $i<$m; $i++) {
		$user_group[$i]['selected'] = ($user_group[$i]['group_id']==$group_id?'selected':'');
		$tpl->setLoop('user_group', $user_group[$i]);
	}
	$tpl->assign('back_url', r::svr('HTTP_REFERER'));
	$tpl->assign('method', $method);
    $db->free();
    return $mystep->render($tpl);
}
