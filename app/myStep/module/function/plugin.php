<?PHP
$method = '';
if(isset($info_app['path'][3])) $method = $info_app['path'][3];

$mydb = new myDb('simpleDB', 'plugin', PLUGIN);
if(!$mydb->check()) {
    $mydb->create(array(
        array('order', 2),
        array('active', 1),
        array('idx', 10),
        array('ver', 10),
        array('name', 40),
        array('intro', 200),
    ));
}
$idx = myReq::get('idx');
if(!empty($idx)) {
    if(!interface_exists('interface_plugin')) require_once(PLUGIN.'/interface_plugin.class.php');
    $flag = true;
    $class = 'plugin_'.$idx;
    if(!class_exists($class) && is_file(PLUGIN.$idx.'/class.php')) include(PLUGIN.$idx.'/class.php');
    if(class_exists($class)) {
        $reflect = new myReflection($class);
        if($reflect->implementsInterface('interface_plugin')) {
            if(is_file(PLUGIN.$idx.'/info.php')) {
                $info = include(PLUGIN.$idx.'/info.php');
                $flag = false;
            }
        }
    }
    if($flag) myStep::info('page_error_plugin');
}
$installed = ($mydb->result('idx='.$idx, 'idx')!=='');
if($installed && $method=='view') {
    myStep::redirect(str_replace('view', 'setting', myReq::server('REQUEST_URI')));
} elseif (!$installed && $method=='setting') {
    myStep::redirect(str_replace('setting', 'view', myReq::server('REQUEST_URI')));
}
function checkApp($apps, $idx) {
    $dirs = myFile::find('', APP, false, myFile::DIR);
    $dirs = array_map(function ($v) {return basename($v);} , $dirs);
    foreach($dirs as $k) {
        $plugin = [];
        if(is_file(APP.$k.'/plugin.php')) $plugin = include(APP.$k.'/plugin.php');
        if(in_array($k, $apps)) {
            if(!in_array($idx, $plugin)) {
                $plugin[] = $idx;
            }
        } else {
            if(($n = array_search($idx, $plugin))!==false) {
                unset($plugin[$n]);
                $plunin = array_values($plugin);
            }
        }
        myFile::saveFile(APP.$k.'/plugin.php', '<?PHP'.chr(10).'return '.var_export($plugin, true).';');
    }
}

switch($method) {
    case 'view':
        if(myReq::check('post')) {
            if(isset($_POST['setting'])) {
                $config = new myConfig(PLUGIN.$idx.'/config.php');
                $config->set($_POST['setting']);
                $config->save('php');
            }
            if(isset($_POST['apps'])) checkApp($_POST['apps'], $idx);

            call_user_func(array($class, 'install'));
            $mydb->insert(array(
                'order' => 1,
                'active' => 1,
                'idx' => $idx,
                'ver' => $info['ver'],
                'name' => $info['name'],
                'intro' => $info['intro'],
            ), true);
            myStep::info('plugin_installed', $app_root.'/function/plugin/');
        }
        $check = $class::check($check_info);
        $t->assign('btn_install', $check?'':'d-none');
        $t->assign('check', $check_info);
        $t->assign('info', $mystep->getLanguage('plugin_check_'.($check?'ok':'fail')));
    case 'setting':
        if(myReq::check('post')) {
            $config = new myConfig(PLUGIN.$idx.'/config.php');
            $config->set($_POST['setting']);
            $config->save('php');
            if(isset($_POST['apps'])) checkApp($_POST['apps'], $idx);
            myStep::redirect($app_root.'/function/plugin/');
        }
        $t->assign($info);
        if(!is_file(PLUGIN.$idx.'/config.php')) {
            $result = '<h4 class="text-center">'.$mystep->getLanguage('plugin_no_setting').'</h4>';
        } else {
            $config = new myConfig(PLUGIN.$idx.'/config.php');
            $list = $config->build(PLUGIN.$idx.'/config/'.$ms_setting->gen->language.'.php');
            $result = '';
            foreach($list as $v) {
                if(isset($v['idx'])) {
                    $result .= '<div class="font-weight-bold">'.$v['name'].'</div>'.chr(10);
                } else {
                    if(strpos($v['html'], '<div type="switch">')===0) {
                        $v['html'] = str_replace('type="switch"', 'class="custom-control custom-switch"', $v['html']);
                        $v['html'] = str_replace('name="', 'class="custom-control-input" name="', $v['html']);
                        $v['html'] = str_replace('<label', '<label class="custom-control-label"', $v['html']);
                    } else {
                        $v['html'] = str_replace(' name="', ' class="form-control" name="', $v['html']);
                        $v['html'] = str_replace('<label><input', '<label class="mr-3"><input class="mr-1"', $v['html']);
                    }
                    $result .= '
  <div class="form-group mb-2" data-toggle="tooltip" data-placement="bottom" title="'.$v['describe'].'">
    <label class="mr-3" style="min-width:100px;">'.$v['name'].'：</label>
    '.$v['html'].'
  </div>
'.chr(10);
                }
            }
        }
        $t->assign('setting', $result);

        $dirs = myFile::find('', APP, false, myFile::DIR);
        $dirs = array_map(function ($v) {return basename($v);} , $dirs);
        if(!isset($info['app'])) $info['app'] = [];
        foreach($dirs as $k) {
            if(!is_file(APP.$k.'/info.php')) continue;
            $i = include(APP.$k.'/info.php');
            $i['plugin'] = '';
            $i['checked'] = '';
            if(in_array($i['app'], $info['app'])) {
                $i['checked'] = 'checked onclick="return false"';
            } elseif(is_file(APP.$k.'/plugin.php') && in_array($idx, include(APP.$k.'/plugin.php'))) {
                $i['checked'] = 'checked';
            }
            $t->setLoop('app', $i);
        }
        break;
    case 'active':
        $active = $mydb->result('idx='.$idx, 'active');
        $mydb->update('idx='.$idx, array('active' => 1 - (int)$active));
        myStep::redirect($app_root.'/function/plugin/');
        break;
    case 'delete':
        myFile::del(PLUGIN.$idx);
        myStep::info('plugin_delete_done');
        break;
    case 'uninstall':
        $mydb->delete('idx='.$idx);
        call_user_func(array($class, 'uninstall'));
        checkApp([], $idx);
        myStep::info('plugin_uninstalled', $app_root.'/function/plugin/');
        break;
    case 'pack':
        if(!empty($idx) || is_dir(PLUGIN.$idx)) {
            $pack_file = ROOT.'cache/tmp/pack/'.$idx.'.plugin';
            $mypack = $mystep->getInstance('myPacker', PLUGIN.$idx, $pack_file);
            $mypack->pack();
            unset($mypack);
            myStep::file($pack_file);
        }
        break;
    case 'upload':
        if(myReq::check('files')) {
            $path_upload = CACHE.'tmp';
            $upload = new myUploader($path_upload, true);
            $upload->do(false);
            $result = $upload->result();
            if($result[0]['error'] == 0) {
                $file = $path_upload.'/'.$result[0]['new_name'];
                $mypack = $mystep->getInstance('myPacker', PLUGIN.strstr($result[0]['name'], '.', true).'/', $file);
                $mypack->unpack();
                unset($mypack);
                myFile::del($file);
                $result = [
                    'error' => 0,
                    'message' => $mystep->getLanguage('plugin_upload_done')
                ];
            } else {
                $result = [
                    'error' => $result[0]['error'],
                    'message' => $result[0]['message']
                ];
            }
            unset($upload);
        } else {
            $result = [
                'error' => '-1',
                'message' => 'No file uploaded!'
            ];
        }
        echo myString::toJson($result, $ms_setting->gen->charset);
        $mystep->end();
        break;
    default:
        if(myReq::check('post')) {
            $idx = myReq::post('idx');
            $order = myReq::post('order');
            $records = $mydb->records();
            foreach($records as $k => $v) {
                if(($key=array_search($v['idx'], $idx))!==false) {
                    $records[$k]['order'] = $order[$key];
                }
            }
            $records = $mydb->setOrder($records, 'order', 'desc');
            $mydb->empty();
            $mydb->insert($records, true);
        }
        $dirs = myFile::find('', PLUGIN, false, myFile::DIR);
        $dirs = array_map(function ($v) {return basename($v);} , $dirs);

        $check_plugin = [];
        if(is_file(PLUGIN.'manager/check_plugin.php')) $check_plugin = include(PLUGIN.'manager/check_plugin.php');

        $data = $mydb->records();
        $t->setIf('empty_1', empty($data));
        foreach($data as $v) {
            $v['active'] = $mystep->getLanguage('plugin_'.($v['active']==1 ? 'deactive' : 'active'));
            $v['update'] = (version_compare($v['ver'], $check_plugin[$v['idx']]??'')<0) ? '<br />[<a href="'.ROOT_WEB.'manager/plugin/'.$v['idx'].'">Update to v'.$check_plugin[$v['idx']].'</a>]': '';
            $t->setLoop('list_1', $v);
            if(($k = array_search($v['idx'], $dirs))!==false) unset($dirs[$k]);
        }

        $t->setIf('empty_2', count($dirs)==0);
        foreach($dirs as $v) {
            if(is_file(PLUGIN.$v.'/info.php')) {
                $info = include(PLUGIN.$v.'/info.php');
            } else {
                $info = array('name'=>$mystep->getLanguage('unknown'), 'ver'=>$mystep->getLanguage('unknown'), 'intro'=>$mystep->getLanguage('plugin_no_info'), 'idx'=>$v);
            }
            $info['update'] = (version_compare($info['ver'], $check_plugin[$info['idx']]??'')<0) ? '<br />[<a href="'.ROOT_WEB.'manager/plugin/'.$info['idx'].'">Update to v'.$check_plugin[$info['idx']].'</a>]': '';
            $t->setLoop('list_2', $info);
        }
        $t->assign('max_size', myFile::getByte(ini_get('upload_max_filesize')));
}
$mydb->close();
$tpl->assign('path', '/function/plugin');
