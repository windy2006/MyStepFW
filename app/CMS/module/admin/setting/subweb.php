<?PHP
if(empty($id)) $id = r::r('web_id');
$list = explode(',', $group_info['power_web']);
if(count($list)==1 && $list[0]!='all') {
    $id = $list[0];
    if($method!='edit_ok') $method = 'edit';
}
if(!empty($id)) {
    if(!checkPower('web', $id)) {
        myStep::info('admin_web_subweb_error');
    }
    $web_current = \app\CMS\checkVal($website, 'web_id', $id);
}
switch($method) {
	case 'add':
	case 'edit':
	case 'list':
        $content = build_page($method);
		break;
	case 'delete':
        cms::$log = $mystep->getLanguage('admin_web_subweb_delete');
        if(isset($web_current['idx'])) {
            $cfg_file = PATH.'website/config_'.$web_current['idx'].'.php';
            $config = new myConfig($cfg_file);
            if($s->db->pre!=$config->db->pre) {
                $db->drop($config->db->pre.'news_show', 'table');
                $db->drop($config->db->pre.'news_detail', 'table');
                $db->drop($config->db->pre.'news_tag', 'table');
                $db->query('delete from '.$s->db->pre.'news_cat where web_id='.intval($id));
            } else {
                $db->build($s->db->pre.'news_cat')
                    ->field(['web_id'=>1])
                    ->where('web_id','n=',$id);
                $db->update();
                $db->build($s->db->pre.'news_show')
                    ->field(['web_id'=>1])
                    ->where('web_id','n=',$id);
                $db->update();
            }
            f::del($cfg_file);
            f::del(CACHE.'app/'.$info_app['app']);
            f::del(CACHE.'template/'.$info_app['app']);
            $db->build($s->db->pre.'website')
                ->where('web_id','n=',$id);
            $db->delete();
            $domain = include(CONFIG.'domain.php');
            unset($domain[$web_current['domain']]);
            myFile::saveFile(CONFIG.'domain.php', '<?PHP'.chr(10).'return '.var_export($domain, 1).';');
            \app\CMS\deleteCache('website');
        }
        cms::redirect();
		break;
	case 'add_ok':
	case 'edit_ok':
        if(!myReq::check('post') || (!checkPower() && $method=='add_ok')) {
            cms::redirect();
        }
        cms::$log = $mystep->getLanguage($method=='add_ok'?'admin_web_subweb_add':'admin_web_subweb_edit');
        $data = r::p('[ALL]');
        $setting = $data['setting'];
        unset($data['setting']);
        $data['name'] = $setting['web']['title'];
        $domain = include(CONFIG.'domain.php');
        if($method=='add_ok') {
            if(($tmp = \app\CMS\checkVal($website, 'idx', $data['idx']))!==false) {
                myStep::info('admin_web_subweb_same_idx');
            }
            if($s->db->pre!=$setting['db']['pre']) {
                $strFind = array('{db_name}', '{pre}', '{charset}', '{domain}', '{idx}');
                $strReplace = array($s->db->name, $setting['db']['pre'], $s->db->charset, $data['domain'], $data['idx']);
                $info = $db->file(PATH.'module/admin/subweb.sql', $strFind, $strReplace);
            }
        } else {
            unset($domain[$web_current['domain']]);
        }
        if(isset($domain[$data['domain']])) {
            mystep::info('admin_web_subweb_domain');
        }
        $domain[$data['domain']] = 'CMS';
        myFile::saveFile(CONFIG.'domain.php', '<?PHP'.chr(10).'return '.var_export($domain, 1).';');
        $config = new myConfig(PATH.'website/config_'.$data['idx'].'.php');
        $config->set($setting);
        $config->save('php');
        $db->build($s->db->pre.'website')
            ->field($data);
        $db->replace();
        \app\CMS\deleteCache('website');
        f::del(CACHE.'app/'.$info_app['app']);
        f::del(CACHE.'template/'.$info_app['app']);
        myStep::$goto_url = preg_replace('#'.preg_quote($method).'$#', '', r::env('REQUEST_URI'));
		break;
	default:
        $content = build_page('list');
}

function build_page($method) {
    global $mystep, $tpl_setting, $s, $db, $id;

    $tpl_setting['name'] = 'set_subweb_'.($method=='list'?'list':'input');
    $tpl = new myTemplate($tpl_setting, false, true);

	if($method == 'list') {
        $db->build($s->db->pre.'website')
            ->order('web_id');
		$db->select();
		while($record = $db->getRS()) {
			myString::htmlTrans($record);
			$tpl->setLoop('record', $record);
		}
		$tpl->assign('title', $mystep->getLanguage('admin_web_subweb_title'));
	} else {
		$tpl->assign('title', $mystep->getLanguage($method == 'add'?'admin_web_subweb_add':'admin_web_subweb_edit'));
		if($method == 'edit') {
            $db->build($s->db->pre.'website')
                ->where('web_id', 'n=', $id);
            if(($record = $db->record())===false) {
                myStep::info('admin_web_subweb_error');
            }
            $cfg_file = PATH.'website/config_'.$record['idx'].'.php';
		} else {
			$record['web_id'] = 0;
			$record['idx'] = '';
			$record['domain'] = '';
            $cfg_file = PATH.'website/config_main.php';
		}
		$config = new myConfig($cfg_file);
        $builder = PATH.'website/config/'.$s->gen->language.'.php';

        $files = myFile::find('', PATH.'language', false, myFile::FILE);
        $files = array_map(function($v){return str_replace('.php', '', basename($v));}, $files);
        $ext_setting = array(
            'gen'=>['language'=>['select', $files]],
        );
        $list = $config->build($builder, $ext_setting);
        foreach($list as $v) {
            if(isset($v['idx'])) {
                $tpl->setLoop('setting', ['content'=> '</tbody>
                    <tbody id="'.$v['idx'].'" class="table-striped table-hover">
                    <tr class="font-weight-bold bg-secondary text-white">
                        <td colspan="2">'.$v['name'].'</td>
                    </tr>
                ']);
            } else {
                $v['html'] = str_replace(' name="', ' class="form-control" name="', $v['html']);
                $v['html'] = str_replace('<label><input', '<label class="mt-2"><input style="display:inline;width:14px;height:14px;"', $v['html']);
                $tpl->setLoop('setting', ['content'=> '
                    <tr data-toggle="tooltip" data-placement="bottom" title="'.$v['describe'].'">
                        <td style="vertical-align: middle;font-size:14px;" width="120">'.$v['name'].'</td>
                        <td style="vertical-align: middle">'.$v['html'].'</td>
                    </tr>
                ']);
            }
        }
		$tpl->assign($record);
		$tpl->assign('method', $method);
        $tpl->assign('back_url', r::svr('HTTP_REFERER'));
	}
	$db->free();
    return $mystep->render($tpl);
}