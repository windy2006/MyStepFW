<?PHP
set_time_limit(0);
ignore_user_abort("on");
ini_set('memory_limit', '128M');
$the_tbl = r::r('table');
$web_idx = r::r('web_idx');
$op_info = '';
if(empty($web_idx)) {
    $web_idx = $website['0']['idx'];
}
$method = r::r('method');

if(myReq::check('post')) {
    $content = '';
    switch($method) {
        case 'import':
            cms::$log = $mystep->getLanguage('admin_func_backup_import');
            $path = CACHE.'tmp/';
            $upload = new myUploader($path, true, $S->upload->ban_ext);
            $upload->do(false);

            if(count($upload->result)>0) {
                if($upload->result[0]['error'] == 0) {
                    if (strpos($upload->result[0]['type'], 'zip')!==false) {
                        $zip = new myZip($path . $upload->result[0]['new_name']);
                        $dir = $path . date('/Ymd_') . rand(1000, 9999) . '/';
                        $zip->unzip($dir, 1);
                        $result_exe = array();
                        if ($handle = opendir($dir)) {
                            while (false !== ($file = readdir($handle))) {
                                $file = $dir.$file;
                                if (is_file($file) && pathinfo($file, PATHINFO_EXTENSION) == 'sql') {
                                    $result_exe = array_merge($result_exe, $db->file($file));
                                }
                            }
                            closedir($handle);
                        }
                        $result = $mystep->getLanguage(count($result_exe) > 0 ? 'admin_func_backup_import_done' : 'admin_func_backup_import_failed');
                        f::del($dir);
                    } else {
                        $result_exe = $db->file($path.$upload->result[0]['new_name']);
                        $result = $mystep->getLanguage(count($result_exe) > 0 ? 'admin_func_backup_import_done' : 'admin_func_backup_import_failed');
                    }
                    f::del($path.$upload->result[0]['new_name']);
                } else {
                    $result = $mystep->getLanguage('admin_func_backup_upload_failed').$upload->result[0]['message'];
                }
            } else {
                $result = $mystep->getLanguage('admin_func_backup_upload_failed').$mystep->getLanguage('admin_func_backup_upload_failed_msg1');
            }
            unset($upload);
            for($i=0,$m=count($result_exe); $i<$m; $i++) {
                switch($result_exe[$i][1]){
                    case 'select':
                        $op_info .=  ($i+1) . ' - '.sprintf($mystep->getLanguage('db_create_table'), $result_exe[$i][2]).'<br />'.chr(10);
                        break;
                    case 'create':
                        $op_info .= ($i+1) . ' - '.sprintf($mystep->getLanguage('db_create_done'), $mystep->getLanguage($result_exe[$i][0]=='table'?'db_table':'db_database'), $result_exe[$i][2]).'<br />'.chr(10);
                        break;
                    case 'drop':
                        $op_info .= ($i+1) . ' - '.sprintf($mystep->getLanguage('db_drop_done'), $mystep->getLanguage($result_exe[$i][0]=='table'?'db_table':'db_database'), $result_exe[$i][2]).'<br />'.chr(10);
                        break;
                    case 'alter':
                        $op_info .= ($i+1) . ' - '.sprintf($mystep->getLanguage('db_alter_done'), $result_exe[$i][2]).'<br />'.chr(10);
                        break;
                    case 'delete':
                        $op_info .= ($i+1) . ' - '.sprintf($mystep->getLanguage('db_delete_done'), $result_exe[$i][2], $result_exe[$i][3]).'<br />'.chr(10);
                        break;
                    case 'truncate':
                        $op_info .= ($i+1) . ' - '.sprintf($mystep->getLanguage('db_truncate_done'), $result_exe[$i][2]).'<br />'.chr(10);
                        break;
                    case 'insert':
                        $op_info .= ($i+1) . ' - '.sprintf($mystep->getLanguage('db_insert_done'), $result_exe[$i][2], $result_exe[$i][3]).'<br />'.chr(10);
                        break;
                    case 'update':
                        $op_info .= ($i+1) . ' - '.sprintf($mystep->getLanguage('db_update_done'), $result_exe[$i][2], $result_exe[$i][3]).'<br />'.chr(10);
                        break;
                    default:
                        $op_info .= ($i+1) . ' - '.sprintf($mystep->getLanguage('db_operate_done'), $result_exe[$i][2], $result_exe[$i][1]).'<br />'.chr(10);
                        break;
                }
            }
            break;
        case 'export':
            cms::$log = $mystep->getLanguage('admin_func_backup_export');
            $dir = CACHE.'tmp/';
            if($the_tbl == 'all') {
                $dir = $dir.date('Ymd').'_db_all/';
                $tbl_list = $db->getTbls();
                $files = array();
                for($i=0,$m=count($tbl_list); $i<$m; $i++) {
                    $content = 'DROP TABLE IF EXISTS `'.$tbl_list[$i].'`;'.chr(10);
                    $content .= $db->getCreateScript($tbl_list[$i]).chr(10).$db->getDataScript($tbl_list[$i]);
                    $files[$i] = $dir.$tbl_list[$i].'.sql';
                    f::s($files[$i], $content);
                }
                $file = dirname($dir).'/'.date('Ymd').'_db_all.zip';
                f::del($file);
                $zip = new myZip($file, $dir);
                $zip->zip($files);
                f::del($dir);
                myStep::file($file);
            } else {
                $content = 'DROP TABLE IF EXISTS `'.$the_tbl.'`;'.chr(10);
                $content .= $db->getCreateScript($the_tbl).''.chr(10).$db->getDataScript($the_tbl);
                header('Content-type: text/plain');
                header('Accept-Ranges: bytes');
                header('Accept-Length: '.strlen($content));
                header('Content-Disposition: attachment; filename='.date('Ymd').'_db_'.$the_tbl.'.sql');
                echo $content;
            }
            exit();
            break;
        case 'optimize':
            cms::$log = $mystep->getLanguage('admin_func_backup_optimize');
            $op_info = '<h4>Optimize Table Done! </h4>';
            if($the_tbl == 'all') {
                $tbl_list = $db->getTbls();
                $tables = '';
                for($i=0,$m=count($tbl_list); $i<$m; $i++) {
                    $tables .= $tbl_list[$i].',';
                }
                if(!empty($tables)) {
                    $i = 1;
                    $db->query('optimize table '.substr($tables, 0,  -1));
                    while($record = $db->getRS()) {
                        $op_info .= '<h6>'.($i++).'. '.$record['Table'].' - <i>'.$record['Msg_text'].'</i></h6>'.chr(10);
                    }
                }
            } else {
                $db->query('optimize table '.$S->db->name.'.'.$the_tbl);
                $record = $db->getRS();
                $op_info .= '<h6>'.$record['Table'].' - <i>'.$record['Msg_text'].'</i></h6>';
            }
            break;
        case 'repair':
            cms::$log = $mystep->getLanguage('admin_func_backup_repair');
            $op_info = '<h4>Repair Table Done! </h4>';
            if($the_tbl == 'all') {
                $tbl_list = $db->getTbls();
                $tables = '';
                for($i=0,$m=count($tbl_list); $i<$m; $i++) {
                    $tables .= $tbl_list[$i].',';
                }
                if(!empty($tables)) {
                    $i = 1;
                    $db->query('repair table '.substr($tables, 0,  -1));
                    while($record = $db->getRS()) {
                        $op_info .= '<h6>'.($i++).'. '.$record['Table'].' - <i>'.$record['Msg_text'].'</i></h6>'.chr(10);
                    }
                }
            } else {
                $record = $db->query('repair table '.$S->db->name.'.'.$the_tbl);
                $record = $db->getRS();
                $op_info .= '<h6>'.$record['Table'].' - <i>'.$record['Msg_text'].'</i></h6>';
            }
            break;
        default:
            break;
    }
}

$tpl_setting['name'] = 'func_backup';
$tpl_sub = new myTemplate($tpl_setting, false);

$tbl_list = $db->getTbls();
for($i=0,$m=count($tbl_list); $i<$m; $i++) {
    $tpl_sub->setLoop('tbls', array('name'=>$tbl_list[$i]));
}
$db->free();

if(empty($result)) $result = $mystep->getLanguage('admin_func_backup_question');
$tpl_sub->assign('title',$mystep->getLanguage('admin_func_backup_title'));
$tpl_sub->assign('result', $result);
$tpl_sub->assign('op_info', $op_info);

$Max_size = ini_get('upload_max_filesize');
$tpl_sub->assign('max_size', $Max_size);
$Max_size = myFile::getSize($Max_size);
if($Max_size==0) $Max_size = 1024*1024;
$tpl_sub->assign('upload_max_filesize', $Max_size);

$content = $mystep->render($tpl_sub);