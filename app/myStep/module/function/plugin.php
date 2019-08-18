<?php
global $path_admin;
$method = '';
if(isset($info_app['path'][3])) $method = $info_app['path'][3];

$mydb = new myDb('simpleDB', 'plugin', PLUGIN);
if(!$mydb->check()) {
    $mydb->create(array(
        array('order',2),
        array('active',1),
        array('idx',10),
        array('ver',10),
        array('name',40),
        array('intro',200),
    ));
}
$idx = myReq::get('idx');
if(!empty($idx)) {
    include_once(PLUGIN.'/interface_plugin.class.php');
    $flag = true;
    if(is_file(PLUGIN.$idx.'/class.php')) {
        include(PLUGIN.$idx.'/class.php');
        $class = 'plugin_'.$idx;
        if(class_exists($class)) {
            $reflect = new myReflection($class);
            if($reflect->implementsInterface('interface_plugin')) {
                if(is_file(PLUGIN.$idx.'/info.php')) {
                    $info = include(PLUGIN.$idx.'/info.php');
                    $flag = false;
                }
            }
        }
    }
    if($flag) myStep::info($mystep->getLanguage('page_error_plugin'));
}

switch($method) {
    case 'view':
        if(myReq::check('post')) {
            $config = new myConfig(PLUGIN.$idx.'/config.php');
            $config->set($_POST['setting']);
            $config->save('php');
            call_user_func(array($class, 'install'));
            $mydb->insert(array(
                'order' => 1,
                'active' => 1,
                'idx' => $idx,
                'ver' => $info['ver'],
                'name' => $info['name'],
                'intro' => $info['intro'],
            ), true);
            myStep::info($mystep->getLanguage('plugin_installed'), $path_admin.'function/plugin/');
        }
        $check = $class::check($check_info);
        $t->assign('check', $check_info);
        $t->assign('info', $mystep->getLanguage('plugin_check_'.($check?'ok':'fail')));
    case 'setting':
        if(myReq::check('post')) {
            $config = new myConfig(PLUGIN.$idx.'/config.php');
            $config->set($_POST['setting']);
            $config->save('php');
            myStep::redirect($path_admin.'function/plugin/');
        }
        $t->assign('', $info);
        if(!is_file(PLUGIN.$idx.'/config.php')) {
            $result = '<h4 class="text-center">'.$mystep->getLanguage('plugin_no_setting').'</h4>';
        } else {
            $config = new myConfig(PLUGIN.$idx.'/config.php');
            $list = $config->build(PLUGIN.$idx.'/config/'.$s->gen->language.'.php');
            $result = '';
            foreach($list as $v) {
                if(isset($v['idx'])) {
                    $result .= '<div class="font-weight-bold">'.$v['name'].'</div>'.chr(10);
                } else {
                    $v['html'] = str_replace(' name="', ' class="form-control" name="', $v['html']);
                    $v['html'] = str_replace('<label><input', '<label class="mr-3"><input class="mr-1"', $v['html']);
                    $result .= '
  <div class="form-group mb-2" data-toggle="tooltip" data-placement="bottom" title="'.$v['describe'].'">
    <label class="mr-3">'.$v['name'].'：</label>
    '.$v['html'].'
  </div>
'.chr(10);
                }
            }
        }
        $t->assign('setting', $result);
        $mystep->setAddedContent('end', '<script language="JavaScript" src="static/js/checkForm.js"></script>');
        break;
    case 'active':
        $active = $mydb->result('idx='.$idx, 'active');
        $mydb->update('idx='.$idx, array('active' => 1 - (int)$active));
        myStep::redirect($path_admin.'function/plugin/');
        break;
    case 'delete':
        myFile::del(PLUGIN.$idx);
        myStep::info($mystep->getLanguage('plugin_delete_done'));
        break;
    case 'uninstall':
        $mydb->delete('idx='.$idx);
        call_user_func(array($class, 'uninstall'));
        myStep::info($mystep->getLanguage('plugin_uninstalled'), $path_admin.'function/plugin/');
        break;
    case 'pack':
        if(!empty($idx) || is_dir(PLUGIN.$idx)) {
            $pack_file = ROOT."cache/tmp/pack/".$idx.".plugin";
            $mypack = $mystep->getInstance("myPacker", PLUGIN.$idx, $pack_file);
            $mypack->pack();
            unset($mypack);
            myStep::file($pack_file);
        }
        break;
    case "upload":
        if(myReq::check('post')){
            $path_upload = CACHE."tmp";
            $upload = new myUploader($path_upload, true);
            $upload->do(false);
            $result = $upload->getResult(0);
            if($result[0]['error'] == 0) {
                $theFile = $path_upload."/".$result[0]['new_name'];
                $mypack = $mystep->getInstance("myPacker", PLUGIN.strstr($result[0]['name'],'.', true).'/', $theFile);
                $mypack->unpack();
                unset($mypack);
                myFile::del($theFile);
                myStep::info($mystep->getLanguage('plugin_upload_done'));
            } else {
                myStep::info($mystep->getLanguage('plugin_upload_fail').'< br />< br />'.$result[0]['message']);
            }
            unset($upload);
        }
        break;
    default:
        if(myReq::check('post')) {
            $idx = myReq::post('idx');
            $order = myReq::post('order');
            $records = $mydb->records();
            foreach($records as $k => $v) {
                if(($key=array_search($v['idx'],$idx))!==false) {
                    $records[$k]['order'] = $order[$key];
                }
            }
            $records = $mydb->setOrder($records, 'order', 'desc');
            $mydb->empty();
            $mydb->insert($records, true);
        }
        $dirs = myFile::find('',PLUGIN,false, myFile::DIR);
        $dirs = array_map(function($v){return basename($v);} ,$dirs);

        $data = $mydb->records();
        $t->setIf('empty_1', empty($data));
        foreach($data as $v) {
            $v['active'] = $mystep->getLanguage('plugin_'.($v['active']==1 ? 'deactive' : 'active'));
            $t->setLoop('list_1', $v);
            if(($k = array_search($v['idx'], $dirs))!==false) unset($dirs[$k]);
        }

        $t->setIf('empty_2', count($dirs)==0);
        foreach($dirs as $v) {
            if(is_file(PLUGIN.$v.'/info.php')) {
                $info = include(PLUGIN.$v.'/info.php');
            } else {
                $info = array('name'=>$mystep->getLanguage('unknown'),'ver'=>$mystep->getLanguage('unknown'),'intro'=>$mystep->getLanguage('plugin_no_info'),'idx'=>$v);
            }
            $t->setLoop('list_2', $info);
        }
        $t->assign('max_size', myFile::getByte(ini_get('upload_max_filesize')));
}
$mydb->close();
$tpl->assign('path', 'manager/function/plugin');
