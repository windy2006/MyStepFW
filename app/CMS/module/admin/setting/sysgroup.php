<?PHP
if(empty($id)) $id = r::r('group_id');
switch($method) {
    case 'add':
    case 'edit':
    case 'list':
        $content = build_page($method);
        break;
    case 'delete':
        cms::$log = $mystep->getLanguage('admin_sys_group_delete');
        if($id==1) {
            myStep::info('admin_sys_group_no_del');
        }
        $db->build($s->db->pre.'sys_group')
            ->where('group_id', 'n=', $id);
        $db->delete();
        \app\CMS\deleteCache('sys_group');
        cms::redirect();
        break;
    case 'add_ok':
    case 'edit_ok':
        if(myReq::check('post')) {
            $data = r::p('[ALL]');
            if($data['power_func'][0]=='all') {
                $data['power_func'] = 'all';
            } else {
                $data['power_func'] = join( ',', $data['power_func']);
            }
            if($data['power_web'][0]=='all') {
                $data['power_web'] = 'all';
            } else {
                $data['power_web'] = join(',', $data['power_web']);
            }
            cms::$log = $mystep->getLanguage($method=='add_ok'?'admin_sys_group_add':'admin_sys_group_edit');
            $db->build($s->db->pre.'sys_group')->field($data);
            $db->replace();
            \app\CMS\deleteCache('sys_group');
        }
        myStep::$goto_url = preg_replace('#'.preg_quote($method).'$#', '', r::env('REQUEST_URI'));
        break;
    default:
        $content = build_page('list');
}
function build_page($method) {
    global $mystep, $tpl_setting, $s, $db, $id, $website;
    if(($admin_cat=\app\CMS\getCache('admin_cat'))===false) {
        myStep::info('error_para');
    } else {
        $admin_cat = $admin_cat['admin_cat_plat'];
    }
    $tpl_setting['name'] = 'set_sysgroup_'.($method=='list'?'list':'input');
    $tpl = new myTemplate($tpl_setting, false);

    if($method == 'list') {
        $db->build($s->db->pre.'sys_group')
            ->order('group_id');
        $db->select();
        while($record = $db->getRS()) {
            s::HtmlTrans($record);
            if($record['power_func']=='all') {
                $record['power_func'] = $mystep->getLanguage('admin_sys_group_power_all');
            } elseif($record['power_func']=='') {
                $record['power_func'] = $mystep->getLanguage('admin_sys_group_power_none');
            } else {
                $thePowerFunc = explode(',', $record['power_func']);
                $record['power_func'] = '';
                for($i=0,$m=count($thePowerFunc); $i<$m; $i++) {
                    $theFunc = \app\CMS\getPara($admin_cat, 'id', $thePowerFunc[$i]);
                    $record['power_func'] .= $theFunc['name'].', ';
                }
                $record['power_func'] = substr($record['power_func'], 0, -2);
            }
            if($record['power_web']=='all') {
                $record['power_web'] = $mystep->getLanguage('admin_sys_group_web_all');
            } elseif($record['power_web']=='') {
                $record['power_web'] = $mystep->getLanguage('admin_sys_group_web_none');
            } else {
                $thePowerWeb = explode(',', $record['power_web']);
                $record['power_web'] = '';
                for($i=0,$m=count($thePowerWeb); $i<$m; $i++) {
                    $theWeb = \app\CMS\getPara($website, 'web_id', $thePowerWeb[$i]);
                    $record['power_web'] .= $theWeb['name'].', ';
                }
                $record['power_web'] = substr($record['power_web'], 0, -2);
            }
            $tpl->setLoop('record', $record);
        }
        $tpl->assign('title', $mystep->getLanguage('admin_sys_group_title'));
    } else {
        if($method == 'edit') {
            $db->build($s->db->pre.'sys_group')
                ->where('group_id','n=',$id);
            if(($record=$db->record()) === false) {
                myStep::info('admin_sys_group_error');
            }
        } else {
            $record['group_id'] = 0;
            $record['name'] = '';
            $record['power_func'] = '';
            $record['power_web'] = '';
        }
        $tpl->assign($record);

        $tpl->assign('power_web_all_checked', $record['power_web']=='all'?'checked':'');
        for($i=0,$m=count($website); $i<$m; $i++) {
            $tpl->setLoop('power_web', array('web_id'=>$website[$i]['web_id'], 'name'=>$website[$i]['name'], 'checked'=>strpos(','.$record['power_web'].',', ','.$website[$i]['web_id'].',')!==false?'checked':''));
        }
        $tpl->assign('power_func_all_checked', $record['power_func']=='all'?'checked':'');
        for($i=0,$m=count($admin_cat);$i<$m; $i++) {
            if($admin_cat[$i]['pid']==0) {
                $tpl->setLoop('power_func', array('key'=>$admin_cat[$i]['id'], 'value'=>$admin_cat[$i]['name'], 'pid'=>$admin_cat[$i]['pid'], 'checked'=>strpos(','.$record['power_func'].',', ','.$admin_cat[$i]['id'].',')!==false?'checked':''));
            } else {
                $tpl->setLoop('power_func', array('key'=>$admin_cat[$i]['id'], 'value'=>((isset($admin_cat[$i+1]) && $admin_cat[$i+1]['pid']>0)?'├ ':'└ ').$admin_cat[$i]['name'], 'pid'=>$admin_cat[$i]['pid'], 'checked'=>strpos(','.$record['power_func'].',', ','.$admin_cat[$i]['id'].',')!==false?'checked':''));
            }
        }
        $tpl->assign('title', $mystep->getLanguage($method == 'add'?'admin_sys_group_add':'admin_sys_group_edit'));
        $tpl->assign('back_url', r::svr('HTTP_REFERER'));
        $tpl->assign('method', $method);
    }
    $db->free();
    return $mystep->render($tpl);
}
