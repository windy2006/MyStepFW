<?PHP

/**
 * 文件：plugin/myBackup/module/myBackup.php
 * 功能说明：myBackup 插件的 myBackup 功能模块
 *
 * 方法目录：
 *   本文件不直接声明具名方法或函数。
 *
 * 回调目录：
 *   回调 1：在响应结束时关闭独立备份连接并删除本次随机工作目录。
 *
 * 使用说明：
 *   1. 准备数据库和表列表，处理 SQL/ZIP 导入、导出、修复与优化。
 *   2. 对应 template/myBackup.tpl；使用独立连接与随机工作目录，不覆盖宿主全局数据库。
 *   3. 应由已验证身份、方法及 CSRF 的入口装配；文件下载或错误分支可提前退出。
 *
 * 说明：PHPDoc 类型用于说明，不增加原生类型约束；源码保持 PHP 7.4 兼容。
 *       方法目录覆盖本文件直接声明；闭包参数及捕获变量说明见各回调旁 PHPDoc。
 */

set_time_limit(0);
ignore_user_abort("on");
$setting = new myConfig(__DIR__.'/../config.php');
if(empty($setting->host)) $setting->host = $S->db->host;
if(empty($setting->user)) $setting->user = $S->db->user;
if(empty($setting->password)) $setting->password = $S->db->password;
if(empty($setting->db)) $setting->db = $S->db->name;
if(empty($setting->charset)) $setting->charset = $S->db->charset;
$the_db = r::r('db','!') ?: $setting->db;
$the_tbl = r::r('table','!');
$method = r::r('method','!');
$op_info = '';
$backupDb = new myDb('MySQL',$setting->host,$setting->user,$setting->password,$setting->charset);
if($backupDb->connect(false,$setting->db)===false) myStep::info('db_connect_error');
$db_list=$backupDb->getDBs();
if(!is_string($the_db) || !in_array($the_db,$db_list,true)) myStep::info('error_para');
$backupDb->selectDB($the_db);
if(myReq::check('post') && in_array($method,['export','optimize','repair'],true)
    && $the_tbl!=='all' && (!is_string($the_tbl) || !in_array($the_tbl,$backupDb->getTbls(),true))) myStep::info('error_para');
// 随机目录隔离并发上传/导出，响应结束后清理衍生文件。
$work=CACHE.'tmp/backup_'.bin2hex(random_bytes(10)).'/';
myFile::mkdir($work);
register_shutdown_function(
/**
 * 功能说明：在响应结束时关闭独立备份连接并删除本次随机工作目录。
 *
 * 闭包依赖：通过 use 捕获 backupDb 和 work；用于覆盖常规响应及提前 exit。
 *
 * 参数说明：无显式参数。
 *
 * @return void 依次释放连接和临时文件，不关闭框架全局连接
 */
function() use ($work,$backupDb) { $backupDb->close();myFile::del($work); });

if(myReq::check('post')) {
    $content = '';
    switch($method) {
        case 'import':
            $result_exe = [];
            $path = $work;
            $upload = new myUploader($path, true, $S->upload->ban_ext);
            $upload->do(false);

            if(count($upload->result)>0) {
                if($upload->result[0]['error'] == 0) {
                    if (strpos($upload->result[0]['type'], 'zip')!==false) {
                        $zip = new myZip($path . $upload->result[0]['new_name']);
                        $dir = $path . date('/Ymd_') . rand(1000, 9999) . '/';
                        if(!$zip->unzip($dir, 1)) myStep::info('plugin_myBackup_import_failed');
                        $result_exe = array();
                        if (is_dir($dir) && ($handle = opendir($dir))) {
                            while (false !== ($file = readdir($handle))) {
                                $file = $dir.$file;
                                if (is_file($file) && pathinfo($file, PATHINFO_EXTENSION) == 'sql') {
                                    $executed = $backupDb->file($file);
                                    if($executed===false) myStep::info('plugin_myBackup_import_failed');
                                    $result_exe = array_merge($result_exe, $executed);
                                }
                            }
                            closedir($handle);
                        }
                        $result = $mystep->getLanguage(count($result_exe) > 0 ? 'plugin_myBackup_import_done' : 'plugin_myBackup_import_failed');
                        f::del($dir);
                    } else {
                        $result_exe = $backupDb->file($path.$upload->result[0]['new_name']);
                        if($result_exe===false) myStep::info('plugin_myBackup_import_failed');
                        $result = $mystep->getLanguage(count($result_exe) > 0 ? 'plugin_myBackup_import_done' : 'plugin_myBackup_import_failed');
                    }
                    f::del($path.$upload->result[0]['new_name']);
                } else {
                    $result = $mystep->getLanguage('plugin_myBackup_upload_failed').$upload->result[0]['message'];
                }
            } else {
                $result = $mystep->getLanguage('plugin_myBackup_upload_failed').$mystep->getLanguage('plugin_myBackup_upload_failed_msg1');
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
            $dir = $work;
            if($the_tbl == 'all') {
                $dir = $dir.date('Ymd').'_db_all/';
                $tbl_list = $backupDb->getTbls();
                $files = array();
                for($i=0,$m=count($tbl_list); $i<$m; $i++) {
                    $content = 'DROP TABLE IF EXISTS '.$backupDb->quoteIdentifier($tbl_list[$i]).';'.chr(10);
                    $content .= $backupDb->getCreateScript($tbl_list[$i]).chr(10).$backupDb->getDataScript($tbl_list[$i]);
                    $files[$i] = $dir.$tbl_list[$i].'.sql';
                    f::s($files[$i], $content);
                }
                $file = dirname($dir).'/'.date('Ymd').'_'.$the_db.'_all.zip';
                f::del($file);
                $zip = new myZip($file, $dir);
                if(!$zip->zip($files)) myStep::info('plugin_myBackup_import_failed');
                f::del($dir);
                myStep::file($file);
            } else {
                $content = 'DROP TABLE IF EXISTS '.$backupDb->quoteIdentifier($the_tbl).';'.chr(10);
                $content .= $backupDb->getCreateScript($the_tbl).''.chr(10).$backupDb->getDataScript($the_tbl);
                header('Content-type: text/plain');
                header('Accept-Ranges: bytes');
                header('Accept-Length: '.strlen($content));
                header("Content-Disposition: attachment; filename*=UTF-8''".rawurlencode(date('Ymd').'_'.$the_db.'_'.$the_tbl.'.sql'));
                echo $content;
            }
            exit();
            break;
        case 'optimize':
            $op_info = '<h4>Optimize Table Done! </h4>';
            if($the_tbl == 'all') {
                $tbl_list = $backupDb->getTbls('','', 1);
                $tables = '';
                for($i=0,$m=count($tbl_list); $i<$m; $i++) {
                    if($tbl_list[$i]['Engine'] == 'MyISAM')
                        $tables .= $backupDb->quoteIdentifier($tbl_list[$i]['Name']).',';
                }
                if(!empty($tables)) {
                    $i = 1;
                    $backupDb->query('optimize table '.substr($tables, 0,  -1));
                    while($record = $backupDb->getRS()) {
                        $op_info .= '<h6>'.($i++).'. '.$record['Table'].' - <i>'.$record['Msg_text'].'</i></h6>'.chr(10);
                    }
                }
            } else {
                $backupDb->query('optimize table '.$backupDb->quoteIdentifier($the_db).'.'.$backupDb->quoteIdentifier($the_tbl));
                $record = $backupDb->getRS();
                $op_info .= '<h6>'.$record['Table'].' - <i>'.$record['Msg_text'].'</i></h6>';
            }
            break;
        case 'repair':
            $op_info = '<h4>Repair Table Done! </h4>';
            if($the_tbl == 'all') {
                $tbl_list = $backupDb->getTbls('','', 1);
                $tables = '';
                for($i=0,$m=count($tbl_list); $i<$m; $i++) {
                    if($tbl_list[$i]['Engine'] == 'MyISAM')
                        $tables .= $backupDb->quoteIdentifier($tbl_list[$i]['Name']).',';
                }
                if(!empty($tables)) {
                    $i = 1;
                    $backupDb->query('repair table '.substr($tables, 0,  -1));
                    while($record = $backupDb->getRS()) {
                        $op_info .= '<h6>'.($i++).'. '.$record['Table'].' - <i>'.$record['Msg_text'].'</i></h6>'.chr(10);
                    }
                }
            } else {
                $backupDb->query('repair table '.$backupDb->quoteIdentifier($the_db).'.'.$backupDb->quoteIdentifier($the_tbl));
                $record = $backupDb->getRS();
                $op_info .= '<h6>'.$record['Table'].' - <i>'.$record['Msg_text'].'</i></h6>';
            }
            break;
        default:
            break;
    }
}

$db_list = $backupDb->getDBs();
for($i=0,$m=count($db_list); $i<$m; $i++) {
    $tpl_sub->setLoop('dbs', array('name'=>htmlspecialchars($db_list[$i],ENT_QUOTES,'UTF-8'),'sel'=>($the_db==$db_list[$i]?'selected':'')));
}
$tbl_list = $backupDb->getTbls('','', 1);
for($i=0,$m=count($tbl_list); $i<$m; $i++) {
    $tpl_sub->setLoop('tbls', array('name'=>htmlspecialchars($tbl_list[$i]['Name'],ENT_QUOTES,'UTF-8'), 'type'=>$tbl_list[$i]['Engine']));
}
$backupDb->free();

if(empty($result)) $result = $mystep->getLanguage('plugin_myBackup_question');
$tpl_sub->assign('title',$mystep->getLanguage('plugin_myBackup_info_name'));
$tpl_sub->assign('result', $result??'');
$tpl_sub->assign('op_info', $op_info);

$Max_size = plugin_myBackup::uploadLimit();
$tpl_sub->assign('max_size', myFile::formatSize($Max_size));
$tpl_sub->assign('upload_max_filesize', $Max_size);

$content = $mystep->render($tpl_sub);