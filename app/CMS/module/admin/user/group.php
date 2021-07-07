<?PHP
if(empty($id)) $id = r::r('group_id');
switch($method) {
    case 'add':
    case 'edit':
    case 'list':
        $content = build_page($method);
        break;
    case 'delete':
        if($id>3) {
            CMS::$log = $mystep->getLanguage('admin_user_group_delete');
            $db->build($S->db->pre.'users')->field(array('group_id'=>2))->where('group_id','n=',$id);
            $db->update();
            $db->build($S->db->pre.'user_group')->where('group_id','n=',$id);
            $db->delete();
            \app\CMS\deleteCache('user_group');
            CMS::redirect();
        } else {
            myStep::info('admin_user_group_error');
        }
        break;
    case 'add_ok':
    case 'edit_ok':
        if(!myReq::check('post')) {
            CMS::redirect();
        }
        $data = r::p('[ALL]');
        CMS::$log = $mystep->getLanguage($method=='add_ok'?'admin_user_group_add':'admin_user_group_edit');
        $db->build($S->db->pre.'user_group')->field($data);
        $db->replace();
        \app\CMS\deleteCache('user_group');
        myStep::$goto_url = preg_replace('#'.preg_quote($method).'$#', '', r::svr('REQUEST_URI'));
        break;
    default:
        $content = build_page('list');
}

function build_page($method) {
    global $mystep, $tpl_setting, $S, $db, $id, $power;

    $tpl_setting['name'] = 'user_group_'.($method=='list'?'list':'input');
    $tpl = new myTemplate($tpl_setting, false);
    $power = \app\CMS\getCache('user_power');

    if($method == 'list') {
        $db->build($S->db->pre.'user_group')->order('group_id');
        $db->select();
        while($record = $db->getRS()) {
            s::htmlTrans($record);
            $record['user_power'] = '';
            for($i=0,$m=count($power); $i<$m; $i++) {
                $record['user_power'] .= '<td>'.$record[$power[$i]['idx']].'</td>';
            }
            $tpl->setLoop('record', $record);
        }
        $tpl->assign('title', $mystep->getLanguage('admin_user_group_title'));
    } else {
        if($method == 'edit') {
            $db->build($S->db->pre.'user_group')->where(array('group_id','n=',$id));
            if(($record = $db->record())===false) {
                myStep::info('admin_user_group_error');
            }
        } else {
            $record['group_id'] = 0;
            $record['name'] = '';
        }
        $tpl->assign($record);
        $tpl->assign('method', $method);
        $tpl->assign('back_url', r::svr('HTTP_REFERER'));
        $tpl->assign('title', $mystep->getLanguage($method == 'add'?'admin_user_group_add':'admin_user_group_edit'));
    }
    for($i=0,$m=count($power); $i<$m; $i++) {
        if(isset($record[$power[$i]['idx']])) $power[$i]['value'] = $record[$power[$i]['idx']];
        $tpl->setLoop('user_power', $power[$i]);
    }
    $db->free();
    return $mystep->render($tpl);
}