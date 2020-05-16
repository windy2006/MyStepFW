<?PHP
$power = \app\cms\getCache('user_power');
switch($method) {
	case 'add':
	case 'edit':
	case 'list':
        $content = build_page($method);
		break;
	case 'delete':
        cms::$log = $mystep->getLanguage('admin_user_power_delete');
        if($power_info = \app\cms\getPara($power, 'id', $id)) {
            cms::$log = $mystep->getLanguage('admin_user_power_delete');
            $db->build($s->db->pre.'user_power')
                ->where('id','n=',$id);
            $db->delete();
            $db->query('alter table `'.$s->db->pre.'user_group` drop column `'.$power_info['idx'].'`');
            \app\cms\deleteCache('user_group');
            \app\cms\deleteCache('user_power');
        }
        cms::redirect();
		break;
	case 'add_ok':
	case 'edit_ok':
        if(!myReq::check('post')) {
            cms::redirect();
        }
        $format_list = array(
            'string' => ' Char(100) NOT NULL DEFAULT ""',
            'digital' => ' INT NOT NULL DEFAULT 0',
            'date' => ' Date NOT NULL DEFAULT "0000-00-00"',
            'time' => ' Time NOT NULL DEFAULT "00:00:00"',
        );
        $data = r::p('[ALL]');
        if(empty($data['format']) || !isset($format_list[$data['format']])) $data['format'] = 'string';
        $the_format = $format_list[$data['format']];

        $idx_org = $data['idx_org'];
        $format_org = $data['format_org'];
        unset($data['idx_org'], $data['format_org']);
        cms::$log = $mystep->getLanguage($method=='add_ok'?'admin_user_power_add':'admin_user_power_edit');

        $db->build($s->db->pre.'user_power')->field($data);
        $db->replace();
        if($method=='add_ok') {
            $db->query('alter table `'.$s->db->pre.'user_group` add `'.$data['idx'].'` '.$the_format);
            $db->build($s->db->pre.'user_group')->field(array($data['idx']=>$data['value']));
            $db->update();
        } else {
            if($idx_org!=$data['idx']) {
                $db->query('alter table `'.$s->db->pre.'user_group` change `'.$idx_org.'` `'.$data['idx'].'` '.$the_format);
            } elseif($format_org!=$data['format']) {
                $db->query('alter table `'.$s->db->pre.'user_group` modify `'.$data['idx'].'` '.$the_format);
            }
        }
        \app\cms\deleteCache('user_group');
        \app\cms\deleteCache('user_power');
        myStep::$goto_url = preg_replace('#'.preg_quote($method).'$#', '', r::env('REQUEST_URI'));
		break;
	default:
        $content = build_page('list');
}

function build_page($method) {
    global $mystep, $tpl_setting, $s, $db, $id;
	
    $tpl_setting['name'] = 'user_power_'.($method=='list'?'list':'input');
    $tpl = new myTemplate($tpl_setting, false);

	$format_list = array(
		'digital' => $mystep->getLanguage('checkform_item_digital'),
		'date' => $mystep->getLanguage('checkform_item_date'),
		'time' => $mystep->getLanguage('checkform_item_time'),
	);

	if($method == 'list') {
        $db->build($s->db->pre.'user_power')->order('id');
		$db->select();
		while($record = $db->getRS()) {
			s::htmlTrans($record);
			$record['format'] = $format_list[$record['format']]??$mystep->getLanguage('checkform_item_string');
			$tpl->setLoop('record', $record);
		}
		$tpl->assign('title', $mystep->getLanguage('admin_user_power_title'));
	} else {
		if($method == 'edit') {
            $db->build($s->db->pre.'user_power')->where('id','n=',$id);
            if(($record = $db->record())===false) {
                myStep::info('admin_user_power_error');
            }
			$record['idx_org'] = $record['idx'];
		} else {
			$record['id'] = 0;
			$record['idx'] = '';
			$record['idx_org'] = '';
			$record['name'] = '';
			$record['value'] = '';
			$record['format'] = '';
			$record['format_org'] = '';
			$record['comment'] = '';
		}
		$tpl->assign($record);
		foreach($format_list as $key => $value) {
			$tpl->setLoop('format', array('key'=>$key, 'value'=>$value, 'select'=>($record['format']==$key?'selected':'')));
		}
		$tpl->assign('method', $method);
		$tpl->assign('back_url', r::svr('HTTP_REFERER'));
        $tpl->assign('title', $mystep->getLanguage($method == 'add'?'admin_user_power_add':'admin_user_power_edit'));
	}
	$db->free();
    return $mystep->render($tpl);
}
