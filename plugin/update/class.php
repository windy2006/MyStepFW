<?php
class plugin_update implements interface_plugin {
    public static function check(&$result = ''){
        $result = '';
        $theList = array(
            'version.php',
            'checkfile.php',
            'update.db',
            'pack/',
            'update/',
            'rollback/',
        );
        $flag = true;
        foreach($theList as $cur) {
            if(myFile::rewritable(dirname(__FILE__).'/'.$cur)) {
                $result .= $cur . ' - <span style="color:green">Writable</span><br />';
            } else {
                $result .= $cur . ' - <span style="color:red">Readonly</span><br />';
                $flag = false;
            }
        }
        return $flag;
    }
    public static function install(){
        global $router;
        $router->checkRoute(CONFIG.'route.php', dirname(__FILE__).'/route.php', 'plugin_update');
        myFile::mkdir(dirname(__FILE__).'/update');
        myFile::mkdir(dirname(__FILE__).'/pack');
        addPluginLink('在线更新', 'update/');
    }
    public static function uninstall(){
        global $router;
        $router->remove(CONFIG.'route.php', 'plugin_update');
        myFile::del(dirname(__FILE__).'/update');
        myFile::del(dirname(__FILE__).'/pack');
        removePluginLink('update/');
    }
    public static function update(){
        global $mystep, $info_app, $s;
        if(!isset($info_app['path'][1])) $info_app['path'][1] = '';
        $header = array();
        $header['Referer'] = 'http://'.myReq::server('HTTP_HOST');
        $header['ms_sign'] = time();
        $url = 'http://'.$s->web->update.'/myStep/api/plugin_update/check';
        $dir = dirname(__FILE__);
        switch($info_app['path'][1]) {
            case 'build':
                if(!self::checkFile(ROOT, true)) {
                    echo 'error';
                }
                break;
            case 'check':
                $ver = require(CONFIG.'version.php');
                $ver_remote = myString::fromJson(myFile::getRemote_curl($url.'/version?v='.$ver, $header));
                if(!empty($ver_remote['version'])) {
                    foreach($ver_remote['info'] as $k => $v) {
                        $ver_remote['info'][$k] = preg_replace('#\r\n\s+#',chr(10), trim($v));
                    }
                    echo '{"version":"'.$ver_remote['version'].'","detail":'.myString::toJson($ver_remote['info'], $s->gen->charset).'}';
                } else {
                    echo '{"version":""}';
                }
                break;
            case 'check_local':
                $result = self::checkFile();
                echo myString::toJson($result, $s->gen->charset);
                break;
            case 'check_server':
                $check_info = myFile::getRemote_curl($url, $header);
                if(!empty($check_info)) {
                    $check_info = json_decode($check_info);
                    $the_file = $dir.'/checkfile.php';
                    if(file_exists($the_file)) rename($the_file, $the_file.'.bak');
                    $list_file = $check_info->list_file;
                    $list_file_md5 = $check_info->list_file_md5;
                    unset($check_info);
                    $content = "<?php\n";
                    $content .= '$list_file = '.var_export($list_file, true).";\n";
                    $content .= '$list_file_md5 = '.var_export($list_file_md5, true).";\n";
                    myFile::saveFile($the_file, $content);
                    $result = self::checkFile();
                    echo myString::toJson($result, $s->gen->charset);
                    @unlink($the_file);
                    if(file_exists($the_file.'.bak')) rename($the_file.'.bak', $the_file);
                } else {
                    echo 'error';
                }
                break;
            case 'empty':
                myFile::del($dir.'/update.db');
                $dir .= '/update';
                myFile::del($dir);
                myFile::mkdir($dir);
                break;
            case 'export':
                $mydb = new myDb('simpleDB', 'update', dirname(__FILE__).'/');
                if($mydb->check()) {
                    $data = $mydb->select();
                    $xls = new MyExcel('update', 'info');
                    $xls->addRow();
                    $fields = ['date','idx','remote','local','ip','referer'];
                    $xls->addCells($fields);
                    for($i=0,$m=count($data);$i<$m;$i++) {
                        $xls->addRow();
                        $xls->addCells(array_values($data[$i]));
                    }
                    $mydb->close();
                    $xls->make();
                } else {
                    myStep::info($mystep->getLanguage('plugin_update_update_data_empty'));
                }
                break;
            case 'upload':
                if(myReq::check('post')){
                    $path_upload = CACHE.'tmp/';
                    $upload = new myUploader($path_upload, true);
                    $upload->do(false);
                    $result = $upload->getResult(0);
                    if($result[0]['error'] == 0) {
                        $theFile = $path_upload.'/'.$result[0]['new_name'];
                        $dir = $path_upload.'update/';
                        myFile::del($dir);
                        $zip = new myZip($theFile);
                        $zip->unzip($dir, 1);
                        myFile::del($theFile);
                        if(file_exists($dir.'_config.php')) {
                            $config = new myConfig(CONFIG.'config.php');
                            $config->web->etag = 'etag_'.date('Ymd');
                            $config->merge($dir.'_config.php');
                            $config->save();
                            myFile::del($dir.'_config.php');
                        }
                        if(file_exists($dir.'_code.php')) {
                            include($dir.'_code.php');
                            myFile::del($dir.'_code.php');
                        }
                        if($handle = opendir($dir)) {
                            while (false !== ($file = readdir($handle))) {
                                if(trim($file, '.') == '') continue;
                                myFile::copy($dir.$file, ROOT, true);
                            }
                        }
                        myFile::del($dir);
                        myStep::info('plugin_update_done');
                    } else {
                        myStep::info($mystep->getLanguage('plugin_update_upload_fail').'< br />< br />'.$result[0]['message']);
                    }
                    unset($upload);
                } else {
                    myStep::redirect();
                }
                break;
            case 'download':
                $ver = require(CONFIG.'version.php');
                $mode = $info_app['para']['m'];
                $detail = myFile::getRemote_curl($url.'/download?v='.$ver, $header);
                $detail = unserialize(gzinflate($detail));
                $path_rollback = $dir.'/rollback/'.$ver.'/';
                if(isset($detail['setting']) && count($detail['setting'])>0) {
                    if($mode==1) {
                        myFile::saveFile($path_rollback.'config/config.php', myFile::getLocal(CONFIG.'config.php'));
                        $config = new myConfig(CONFIG.'config.php');
                        $config->web->etag = 'etag_'.date('Ymd');
                        $config->merge($detail['setting']);
                        $config->save();
                    }
                    myFile::saveFile($path_rollback.'_config.php', '<?php'.chr(10).myString::toScript($detail['setting'], 'setting').chr(10).'return $setting;');
                }
                if(isset($detail['code']) && count($detail['code'])>0) {
                    if($mode==1) {
                        for($i=0,$m=count($detail['code']); $i<$m; $i++) {
                            myEval($detail['code'][$i]);
                        }
                    }
                    myFile::saveFile($path_rollback.'_code.php', '<?php'.chr(10).implode("\n/*------------------------------*/\n", $detail['code']));
                }

                $check_list_file = self::checkFile();
                if($check_list_file==false) $check_list_file = array();
                $list = array();
                for($i=0,$m=count($detail['file']); $i<$m; $i++) {
                    $file = myFile::realPath(ROOT.$detail['file'][$i]);
                    if(strpos(strtolower($file), 'config.php')!==false) continue;
                    if($mode==1 && myFile::rewritable($file)) {
                        if(empty($detail['content'][$i])) {
                            myFile::del($file);
                        } elseif($detail['content'][$i]=='.') {
                            myFile::mkdir($file);
                        } else {
                            myFile::copy($file, $path_rollback.$detail['file'][$i], true);
                            myFile::saveFile($file, $detail['content'][$i]);
                        }
                    } else {
                        if(!empty($detail['content'][$i])) $list[] = $i;
                    }
                }
                $result = ['info'=>'','link'=>''];

                $m = count($list);
                if($m>0) {
                    $dir = CACHE.'tmp/';
                    $zipfile = $dir.'update_'.date('Ymd').'.zip';
                    myFile::del($zipfile);
                    $dir = $dir.'update/'.date('Ymd/');
                    $files = array();
                    for($i=0; $i<$m; $i++) {
                        if($detail['content'][$list[$i]]=='.') continue;
                        $files[$i] = $dir.$detail['file'][$list[$i]];
                        myFile::saveFile($files[$i], $detail['content'][$list[$i]]);
                    }
                    if(isset($content)) {
                        $files[] = $dir.'include/config.php';
                        myFile::saveFile($dir.'include/config.php', $content);
                    }
                    if(count($detail['code'])>0) {
                        $script_update = "<?php\n";
                        $script_update .= join("\n/*------------------------------*/\n", $detail['code']);
                        $files[] = $dir.'_code.php';
                        myFile::saveFile($dir.'_code.php', $script_update);
                    }
                    if(count($detail['setting'])>0) {
                        $script_update = "<?php\n";
                        $script_update .= myString::toScript($detail['setting'], 'setting');
                        $script_update .= chr(10).'return $setting;';
                        $files[] = $dir.'_config.php';
                        myFile::saveFile($dir.'_config.php', $script_update);
                    }
                    $zip = new myZip($zipfile, $dir);
                    if($zip->zip($files)) {
                        $result['link'] = str_replace(ROOT, '/', $zipfile);
                    }
                    myFile::del($dir);
                    $result['info'] .= chr(10).$mystep->getLanguage('plugin_update_error');
                } else {
                    $result['info'] .= chr(10). sprintf($mystep->getLanguage('plugin_update_file'), count($detail['file']));
                }
                myFile::del(CACHE.'script');
                myFile::del(CACHE.'template');
                myFile::del(CACHE.'language');
                myFile::del(CACHE.'app');
                myFile::del(CACHE.'setting');
                myFile::del(CACHE.'data');
                myFile::del(CACHE.'page');
                if($mode==1) self::checkFile(ROOT, true, 0);
                ob_clean();
                echo myString::toJson($result, $s->gen->charset);
                break;
            default:
                list($tpl_main, $tpl_sub) = setPluginTemplate('update');
                $paras = [
                    'version' => include(CONFIG.'version.php'),
                    'link'=> $mystep->setting->web->update
                ];
                $tpl_sub->assign($paras);
                $tpl_sub->assign('max_size', myFile::getByte(ini_get('upload_max_filesize')));
                $tpl_main->assign('main', $mystep->parseTpl($tpl_sub, 's', false));
                $mystep->show($tpl_main);
        }
        $mystep->end();
    }
    public static function remote(){
        global $s,$info_app;
        $sign = myReq::server('MS_SIGN');
        if(!$s->gen->debug && empty($sign)) {
            header('HTTP/1.0 404 Not Found');
            exit;
        }
        $method = end($info_app['path']);
        switch($method) {
            case 'version':
                $ver = $info_app['para']['v'];
                $detail = require(dirname(__FILE__).'/version.php');
                $result = ['version'=>'','info'=>[]];
                foreach($detail as $k => $v) {
                    if(version_compare($k,$ver)>0) {
                        $result['info'][$k] = $v['info'];
                        $result['version'] = $k;
                    }
                }
                echo myString::toJson($result, $s->gen->charset);
                break;
            case 'download':
                $v = require(CONFIG.'version.php');
                $v_remote = $info_app['para']['v'];
                $version = require(dirname(__FILE__).'/version.php');
                $cache_file = dirname(__FILE__).'/update/'.md5($v.$v_remote);
                if(file_exists($cache_file)) {
                    $result = myFile::getLocal($cache_file);
                } else {
                    $list_file = array();
                    $list_setting = array();
                    $list_code = array();
                    foreach($version as $key => $value) {
                        if(version_compare($key,$v_remote)>0) {
                            $list_file = array_merge($list_file, $value['file']);
                            if(isset($value['setting'])) $list_setting = arrayMerge($list_setting, $value['setting']);
                            if(isset($value['code'])) $list_code[] = $value['code'];
                        }
                    }
                    $list_file = array_values(array_unique($list_file));
                    $result = array('file'=>$list_file, 'content'=>array(), 'setting'=>$list_setting, 'code'=>$list_code);
                    for($i=0,$m=count($result['file']); $i<$m; $i++) {
                        if(file_exists(ROOT.$result['file'][$i])) {
                            if(is_dir(ROOT.$result['file'][$i])) {
                                $result['content'][$i] = '.';
                            } else {
                                $result['content'][$i] = myFile::getLocal(myFile::realPath(ROOT.$result['file'][$i]));
                            }
                        } else {
                            $result['content'][$i] = '';
                        }
                    }
                    $result = serialize($result);
                    $result = gzdeflate($result, 9);
                    myFile::saveFile($cache_file, $result);
                }
                $mydb = new myDb('simpleDB', 'update', dirname(__FILE__).'/');
                if(!$mydb->check()) {
                    $mydb->create(array(
                        array('date',10),
                        array('idx',40),
                        array('ver_remote',30),
                        array('ver_local',30),
                        array('remote_ip',50),
                        array('referer',200)
                    ));
                }
                $data = array (
                    date('Y-m-d H:i:s'),
                    md5($v.$v_remote),
                    $v_remote,
                    $v,
                    myReq::ip(),
                    myReq::server('HTTP_REFERER')
                );
                $mydb->insert($data);
                $mydb->close();
                echo $result;
                break;
            default:
                $the_file = dirname(__FILE__).'/checkfile.php';
                if(file_exists($the_file)) {
                    include($the_file);
                    $check_info['list_file'] = $list_file;
                    $check_info['list_file_md5'] = $list_file_md5;
                    echo myString::toJson($check_info, $s->gen->charset);
                    unset($list_file, $list_file_md5);
                }
        }
    }
    public static function pack(){
        $ver = require(CONFIG.'version.php');
        $idx = 'mystep_v'.$ver;
        $dir = dirname(__FILE__).'/pack/';
        $log_file = $dir.'log.txt';
        $log = array(
            'time' => date('Y-m-d H:i:s'),
            'ip' => myReq::ip(),
            'agent' => myReq::server('HTTP_USER_AGENT'),
        );
        if(strpos($log['agent'], 'spider')!==false || strpos($log['agent'], 'bot')!==false) {
            myStep::header('404','', true);
        }
        myFile::saveFile($log_file, implode(',', $log).chr(10), 'ab');

        if(!is_file($dir.$idx.'.zip')) {
            myFile::del($dir.$idx);
            myFile::mkdir($dir.$idx);
            error_reporting(0);
            set_time_limit(0);
            ini_set('memory_limit', '512M');
            $mypacker = new myPacker(ROOT, $dir.$idx.'/mystep.pack');
            $mypacker->addIgnore('plugin/update/', '.svn/', '.log/', '.idea/', 'cache/', 'aspnet_client/', 'Thumbs.db', '_bak/', '.DS_Store');
            $mypacker->pack();
            myFile::copy($dir.'../install.php', $dir.$idx.'/install.php');
            myFile::copy(ROOT.'readme.md', $dir.$idx.'/readme.md');
            $zip = new myZip($dir.$idx.'.zip', $dir);
            $zip->zip($dir.$idx);
            myFile::del($dir.$idx);
        }
        myStep::redirect(str_replace(ROOT, '/', $dir.$idx.'.zip'));
    }
    public static function checkFile($dir='', $build=false, $layer=0) {
        global $list_file, $list_file_md5;
        if($layer==0) {
            $list_file = array();
            $list_file_md5 = array();
        }
        $the_dir = dirname(__FILE__).'/';
        $the_file = $the_dir.'checkfile.php';
        if(empty($dir)) $dir = ROOT;
        $dir = myFile::realPath($dir);
        if(($handle = opendir($dir))===false) return false;
        $ignore = array();
        if(is_file($dir.'/ignore')) {
            $ignore = file_get_contents($dir.'/ignore');
            if(strlen($ignore)==0) return;
            $ignore = str_replace(chr(13), '', $ignore);
            $ignore = explode(chr(10), $ignore);
        }
        if($build) {
            while (false !== ($file = readdir($handle))) {
                if(trim($file, '.') == '' || $file == 'ignore' || array_search($file, $ignore)!==false) continue;
                if(strpos($file,'.bak')!==false || strpos($file,'_bak')!==false) continue;
                $the_name = $dir.$file;
                if($the_name==$the_file) continue;
                if(is_dir($the_name)) {
                    self::checkFile($the_name, true, $layer+1);
                } else {
                    $list_file[] = str_replace(ROOT, '/', $the_name);
                    $list_file_md5[] = md5_file($the_name);
                }
            }
            if($layer==0) {
                $content = '<?php
$list_file = '.var_export($list_file, true).';
$list_file_md5 = '.var_export($list_file_md5, true).';
';
                myFile::saveFile($the_file, $content);
            }
            $result = true;
        } else {
            if($layer==0) {
                if(!file_exists($the_file)) return false;
                include($the_file);
            }
            $result = array(
                'new' => array(),
                'mod' => array(),
                'miss' => array()
            );
            while (false !== ($file = readdir($handle))) {
                if(trim($file, '.') == '' || $file == 'ignore' || array_search($file, $ignore)!==false) continue;
                $the_name = $dir.$file;
                if($the_name==$the_file) continue;
                if(is_dir($the_name)) {
                    $result_new = self::checkFile($the_name, false, $layer+1);
                    if($result_new==null) continue;
                    $result['new'] = array_merge($result['new'], $result_new['new']);
                    $result['mod'] = array_merge($result['mod'], $result_new['mod']);
                    $result['miss'] = array_merge($result['miss'], $result_new['miss']);
                } else {
                    $the_name = str_replace(ROOT, '/', $the_name);
                    if(strpos($the_name, '/config.php')!==false) continue;
                    if(strpos($the_name, '/plugin/')===0) {
                        if(
                            strpos(str_replace('/plugin/', '', $the_name), '/')!==false &&
                            strpos($the_name, '/plugin/sample/')!==0 &&
                            strpos($the_name, '/plugin/update/')!==0
                        ) continue;
                    }
                    if(false !== ($key = array_search($the_name, $list_file))) {
                        if(md5_file(ROOT.$the_name)!=$list_file_md5[$key]) {
                            $result['mod'][] = $the_name;
                        }
                        unset($list_file[$key]);
                    } else {
                        $result['new'][] = $the_name;
                    }
                }
            }
            if($layer==0) {
                foreach($list_file as $the_name) {
                    if(strpos($the_name, '/config.php')!==false) continue;
                    if(strpos($the_name, '/plugin/')===0) {
                        if(strpos(str_replace('/plugin/', '', $the_name), '/')!==false && strpos($the_name, '/plugin/sample/')!==0) continue;
                    }
                    $result['miss'][] = $the_name;
                }
            }
        }
        closedir($handle);
        return $result;
    }
}