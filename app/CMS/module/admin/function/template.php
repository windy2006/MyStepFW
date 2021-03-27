<?PHP
global $tpl_path, $idx;
$idx = r::g('idx');
if(empty($idx)) $idx = 'default';
$tpl_path = PATH.'template/';

switch($method) {
    case 'add':
    case 'edit':
    case 'list':
    case 'show':
        $content = build_page($method);
        break;
    case 'delete':
        cms::$log = $mystep->getLanguage('admin_web_template_delete');
        f::del($tpl_path.$idx.'/'.r::g('file'));
        cms::redirect();
        break;
    case 'remove':
        cms::$log = $mystep->getLanguage('admin_web_template_remove');
        if(in_array($idx, ['admin', 'default', 'custom'])) {
            myStep::info('admin_web_template_remove_error');
        }
        f::del($tpl_path.$idx);
        f::del(PATH.'asset/'.$idx.'/');
        cms::redirect();
        break;
    case 'set':
        cms::$log = $mystep->getLanguage('admin_web_template_set');
        $data = r::p('[ALL]');
        for($i=0,$m=count($data['idx']);$i<$m;$i++) {
            $cfg = new myConfig(PATH.'website/config_'.$data['idx'][$i].'.php');
            $cfg->template->style = $data['tpl'][$i];
            $cfg->save('php');
        }
        cms::redirect();
        break;
    case 'export':
        cms::$log = $mystep->getLanguage('admin_web_template_export');
        $dir = CACHE.'tmp/';
        $file = $dir.'template_'.$idx.'.zip';
        $files = [];
        if(file_exists($tpl_path.$idx.'/')) $files[] = $tpl_path.$idx.'/';
        if(file_exists(PATH.'asset/'.$idx.'/')) $files[] = PATH.'asset/'.$idx.'/';
        if(empty($files)) {
            myStep::info('admin_web_template_export_error');
        }
        f::del($file);
        $zip = new myZip($file, PATH);
        if($zip->zip($files)) {
            myController::file($file);
        } else {
            myStep::info('admin_web_template_export_error');
        }
        break;
    case 'upload':
        cms::$log = $mystep->getLanguage('admin_web_template_upload');
        $result = [
            'error' => 1,
            'message' => $mystep->getLanguage('admin_web_template_upload_error')
        ];
        if(myReq::check('files')) {
            $path = CACHE.'tmp/';
            $upload = new myUploader($path, true);
            $upload->do(true);
            if($upload->result[0]['error'] == 0) {
                $file = $path.$upload->result[0]['new_name'];
                $zip = new myZip($file);
                if($zip->unzip(PATH, 1)) {
                    $result = [
                        'error' => 0,
                        'message' => $mystep->getLanguage('plugin_upload_done')
                    ];
                }
            }
            unset($upload, $zip);
        }
        echo myString::toJson($result, $S->gen->charset);
        break;
    case 'add_ok':
    case 'edit_ok':
        if(myReq::check('post')) {
            $data = r::p('[ALL]');
            $idx = $data['idx'];
            cms::$log = $mystep->getLanguage('admin_web_template_edit');
            if($data['file_name']=='style.css') {
                $file = PATH.'asset/'.$idx.'/style.css';
            } else {
                $ext = pathinfo($data['file_name'], PATHINFO_EXTENSION);
                if($ext!='tpl') $data['file_name'] .= '.tpl';
                $file = $tpl_path.$idx.'/'.$data['file_name'];
            }
            $data['file_content'] = str_replace('  ', chr(9), htmlspecialchars_decode($data['file_content']));
            f::s($file, $data['file_content']);
        }
        myStep::$goto_url = preg_replace('#'.preg_quote($method).'$#', 'list&idx='.$idx, r::svr('REQUEST_URI'));
        break;
    default:
        $content = build_page('show');
        break;
}

function build_page($method) {
    global $mystep, $tpl_setting, $S, $idx, $tpl_path, $website;

    $tpl_setting['name'] = 'func_template';
    if($method!='show') $tpl_setting['name'] .= '_'.($method=='list'?'list':'input');
    $tpl = new myTemplate($tpl_setting, false);

    if($method=='show') {
        $tpl->assign('title', $mystep->getLanguage('admin_web_template_title'));
        $tpl->assign('tpl_idx', $idx);

        $tpl_list = f::find('*', $tpl_path, false, f::DIR);
        $the_list = [];
        for($i=0,$m=count($tpl_list); $i<$m; $i++) {
            $tpl_list[$i] = basename($tpl_list[$i]);
            $sample = $tpl_path.$tpl_list[$i].'/sample.png';
            if(!is_file($sample)) $sample = $tpl_path.$tpl_list[$i].'/sample.jpg';
            if(is_file($sample)) {
                $sample = str_replace(ROOT, '/', $sample);
            } else {
                $sample = str_replace(ROOT,'/', STATICS).'images/noimage.gif';
            }
            $tpl->setLoop('tpl_list', array(
                'idx'=>$tpl_list[$i],
                'img'=>$sample,
            ));
            $the_list[] = $tpl_list[$i];
        }
        $tpl->assign('tpl_list', s::toJson($the_list, $S->gen->charset));

        for($i=0,$m=count($website); $i<$m; $i++) {
            if(!checkPower('web', $website[$i]['web_id'])) continue;
            $cfg = new myConfig(PATH.'website/config_'.$website[$i]['idx'].'.php');
            $website[$i]['tpl'] = $cfg->template->style;
            unset($cfg);
            $tpl->setLoop('website', $website[$i]);
        }
    } elseif($method == 'list') {
        $tpl->assign('title', $mystep->getLanguage('admin_web_template_title'));
        $tpl->assign('tpl_idx', $idx);

        $tpl_list = f::find('*', $tpl_path, false, f::DIR);
        for($i=0,$m=count($tpl_list); $i<$m; $i++) {
            $tpl_list[$i] = basename($tpl_list[$i]);
            $tpl->setLoop('tpl_list', array(
                    'idx'=>$tpl_list[$i],
                    'selected'=>$tpl_list[$i]==$idx?'selected':'')
            );
        }

        $css_file = PATH.'asset/'.$idx.'/style.css';
        if(is_file($css_file)) {
            $tpl->setLoop('file', array(
                'name'=>'style.css',
                'size'=>f::getSize($css_file, true),
                'attr'=>f::getAttrib($css_file, true),
                'time'=>date('Y/m/d H:i:s', filemtime($css_file)))
            );
        }

        $file_list = f::getTree($tpl_path.$idx, false);
        foreach($file_list as $key => $value) {
            if(pathinfo($key, PATHINFO_EXTENSION)!='tpl') continue;
            $curFile = $value;
            $curFile['name'] = $key;
            $tpl->setLoop('file', $curFile);
        }
    } else {
        $file = array();
        $file['idx'] = $idx;
        $file['content'] = '';
        $file['type'] = 'htmlmixed';
        if($method=='edit') {
            $file['name'] = r::g('file');
            if($file['name']=='style.css') {
                $the_file = PATH.'asset/'.$idx.'/style.css';
                $file['type'] = 'css';
            } else {
                $the_file = $tpl_path.$idx.'/'.$file['name'];
            }
            if(is_file($the_file)) {
                $file['content'] = f::g($the_file);
                $file['content'] = htmlspecialchars($file['content']);
                $file['content'] = str_replace(chr(9), '  ', $file['content']);
            }
            $tpl->assign('title', $mystep->getLanguage('admin_web_template_edit'));
        } else {
            $file['name'] = '';
            $tpl->assign('title', $mystep->getLanguage('admin_web_template_add'));
        }
        $tpl->assign('readonly', $method=='edit'?'readonly':'');
        $tpl->assign('file', $file);
    }

    $tpl->assign('back_url', r::svr('HTTP_REFERER'));
    $tpl->assign('method', $method);
    return $mystep->render($tpl);
}