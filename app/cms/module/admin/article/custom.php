<?PHP
global $web_id;
if(!empty($id)) {
    $db->build($s->db->pre.'info')
        ->field('web_id')
        ->where('id', 'n=', $id);
    $web_id = $db->result();
    if($web_id!==false) {
        if(!checkPower('web', $web_id=='0'?'all':$web_id)) {
            myStep::info('admin_art_custom_error');
        }
    } else {
        myStep::info('admin_art_custom_error');
    }
} else {
    $web_id = r::g('web_id');
}
switch($method) {
	case 'add':
	case 'edit':
	case 'list':
        $content = build_page($method);
		break;
	case 'delete':
		cms::$log = $mystep->getLanguage('admin_art_custom_delete');
        $db->build($s->db->pre.'info')
            ->where('id', 'n=', $id);
		$db->delete();
        cms::redirect();
		break;
	case 'add_ok':
	case 'edit_ok':
        if(!myReq::check('post') || !checkPower('web', r::p('web_id'))) {
            cms::redirect();
        }
        $data = r::p('[ALL]');
        $data['content'] = htmlspecialchars_decode($data['content']);
        if($method=='add_ok') {
            cms::$log = $mystep->getLanguage('admin_art_custom_add');
            $db->build($s->db->pre.'info')->field($data);
            $db->insert();
        } else {
            cms::$log = $mystep->getLanguage('admin_art_custom_edit');
            $db->build($s->db->pre.'info')
                ->field($data)
                ->where('id', 'n=', $id);
            $db->update();
        }
        myStep::$goto_url = preg_replace('#'.preg_quote($method).'$#', '', r::env('REQUEST_URI'));
		break;
	default:
        $content = build_page('list');
}

function build_page($method) {
    global $mystep, $tpl_setting, $s, $db;
	global $id, $web_id, $website, $group_info;;

    $tpl_setting['name'] = 'art_custom_'.($method=='list'?'list':'input');
    $tpl = new myTemplate($tpl_setting, false);
	
	if($method == 'list') {
        $db->build($s->db->pre.'info')->order('id');
        if(!empty($web_id)) $db->build($s->db->pre.'info')->where('web_id','n=',$web_id);
        if(!checkPower('web')) $db->build($s->db->pre.'info')->where('web_id', 'nin', $group_info['power_web'], 'and');
        $db->select();
		$n = 0;
		while($record = $db->getRS()) {
			$n++;
			if($web = \app\cms\checkVal($website, 'web_id', $record['web_id'])) {
				$record['web_id'] = $web['name'];
			} else {
				$record['web_id'] = $mystep->getLanguage('admin_all_web');
			}
			$tpl->setLoop('record', $record);
		}
		$tpl->setIf('empty', ($n==0));
		$tpl->assign('title', $mystep->getLanguage('admin_art_custom_title'));
	} else {
		if($method == 'edit') {
            $db->build($s->db->pre.'info')
                ->where('id', 'n=', $id);
			if(($record = $db->record())===false) {
                myStep::info('admin_art_custom_erro');
			}
			$web_id = $record['web_id'];
            s::htmlTrans($record);
		} else {
			$record = array();
			$record['id'] = 0;
			$record['web_id'] = $web_id;
			$record['idx'] = '';
			$record['content'] = '';
		}
		$tpl->assign('record', $record);
		
		$tpl->assign('title', ($method=='add'?$mystep->getLanguage('admin_art_custom_add'):$mystep->getLanguage('admin_art_custom_edit')));
		$tpl->assign('method', $method);
		$tpl->assign('back_url', r::svr('HTTP_REFERER'));
	}
    $db->free();
    $tpl->assign('web_id', $web_id??'');
    setWeb($tpl, $web_id);
    return $mystep->render($tpl);
}
