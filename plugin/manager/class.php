<?PHP
set_time_limit(600);
class plugin_manager implements interface_plugin {
    const IGNORE = ['.svn/', '.log/', '.idea/', 'aspnet_client/', 'Thumbs.db', '.DS_Store', 'ignore','allow', '_bak', '.bak', 'config.php', 'plugin.php', 'construction.php'];
    public static function check(&$result = '') {
        $result = '';
        $theList = array(
            'version.php',
            'check_app.php',
            'check_file.php',
            'check_plugin.php',
            'update.db',
            'pack/',
            'update/',
            'rollback/',
        );
        $flag = true;
        foreach($theList as $cur) {
            if(myFile::rewritable(__DIR__.'/'.$cur)) {
                $result .= $cur . ' - <span style="color:green">Writable</span><br />';
            } else {
                $result .= $cur . ' - <span style="color:red">Readonly</span><br />';
                $flag = false;
            }
        }
        return $flag;
    }
    public static function install() {
        global $mystep;
        regPluginRoute('manager');
        myFile::mkdir(__DIR__.'/update');
        myFile::mkdir(__DIR__.'/pack');
        addPluginLink($mystep->getLanguage('plugin_manager_title'), 'manager');
    }
    public static function uninstall() {
        removePluginRoute('manager');
        myFile::del(__DIR__.'/update');
        myFile::del(__DIR__.'/pack');
        removePluginLink('manager');
    }
    public static function main() {
        global $mystep, $info_app, $ms_setting;
        if(!isset($info_app['path'][1])) $info_app['path'][1] = '';
        $header = array();
        $header['Referer'] = (isHttps()?'https':'http').'://'.myReq::server('HTTP_HOST');
        $header['ms_sign'] = time();
        $url = 'http://'.$ms_setting->web->update.'/api/plugin_manager/';
        $dir = __DIR__;
        switch($info_app['path'][1]) {
            case 'build':
                if(!self::checkFile(ROOT, true)) {
                    echo 'error';
                }
                break;
            case 'check':
                $ver = require(CONFIG.'version.php');
                $ver_remote = myString::fromJson(myFile::getRemote($url.'check/version?v='.$ver, $header));
                if(is_array($ver_remote)) {
                    if(!empty($ver_remote['app'])) {
                        myFile::saveFile(__DIR__.'/check_app.php', '<?php
return '.var_export($ver_remote['app'], true).';
');
                    }
                    if(!empty($ver_remote['plugin'])) {
                        myFile::saveFile(__DIR__.'/check_plugin.php', '<?php
return '.var_export($ver_remote['plugin'], true).';
');
                    }
                    if(isset($ver_remote['version']) && !empty($ver_remote['version'])) {
                        foreach($ver_remote['info'] as $k => $v) {
                            $ver_remote['info'][$k] = preg_replace('#\r\n\s+#', chr(10), trim($v));
                        }
                        echo '{"version":"'.$ver_remote['version'].'", "detail":'.myString::toJson($ver_remote['info'], $ms_setting->gen->charset).'}';
                    } else {
                        echo '{"version":""}';
                    }
                } else {
                    echo '{"version":""}';
                }
                break;
            case 'check_local':
                $result = self::checkFile();
                echo myString::toJson($result, $ms_setting->gen->charset);
                break;
            case 'check_server':
                $check_info = myFile::getRemote($url.'check', $header);
                if(!empty($check_info)) {
                    $check_info = json_decode($check_info);
                    if(empty($check_info) || isset($check_info->error)) {
                        echo '{"code":1, "error":"Cannot parse the message from the update server!"}';
                    } else {
                        $the_file = $dir.'/check_file.php';
                        if(file_exists($the_file)) rename($the_file, $the_file.'.bak');
                        $list_file = $check_info->list_file;
                        $list_file_md5 = $check_info->list_file_md5;
                        unset($check_info);
                        $content = "<?PHP\n";
                        $content .= '$list_file = '.var_export($list_file, true).";\n";
                        $content .= '$list_file_md5 = '.var_export($list_file_md5, true).";\n";
                        myFile::saveFile($the_file, $content);
                        $result = self::checkFile();
                        echo myString::toJson($result, $ms_setting->gen->charset);
                        @unlink($the_file);
                        if(file_exists($the_file.'.bak')) rename($the_file.'.bak', $the_file);
                    }
                } else {
                    echo '{"code":2, "error":"Cannot connect to the update server!"}';
                }
                break;
            case 'empty':
                myFile::del($dir.'/update.db');
                $dir .= '/update';
                myFile::del($dir);
                myFile::mkdir($dir);
                break;
            case 'export':
                $mydb = new myDb('simpleDB', 'update', __DIR__.'/');
                if($mydb->check()) {
                    $data = $mydb->select();
                    $xls = new MyExcel('update', 'info');
                    $xls->addRow();
                    $fields = ['date', 'idx', 'remote', 'local', 'ip', 'referer'];
                    $xls->addCells($fields);
                    for($i=0,$m=count($data);$i<$m;$i++) {
                        $xls->addRow();
                        $xls->addCells(array_values($data[$i]));
                    }
                    $mydb->close();
                    $xls->make();
                } else {
                    myStep::info($mystep->getLanguage('plugin_manager_update_data_empty'));
                }
                break;
            case 'upload':
                if(myReq::check('files')) {
                    $path_upload = CACHE.'tmp/';
                    $upload = new myUploader($path_upload, true);
                    $upload->do(false);
                    $result = $upload->result();
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
                        $result = [
                            'error' => 0,
                            'message' => $mystep->getLanguage('plugin_manager_done')
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
                break;
            case 'download':
                $ver = require(CONFIG.'version.php');
                $mode = $info_app['para']['m'];
                $detail = myFile::getRemote($url.'download?v='.$ver, $header);
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
                    myFile::saveFile($path_rollback.'_config.php', '<?PHP'.chr(10).myString::toScript($detail['setting'], 'setting').chr(10).'return $setting;');
                }
                if(isset($detail['code']) && count($detail['code'])>0) {
                    if($mode==1) {
                        for($i=0,$m=count($detail['code']); $i<$m; $i++) {
                            myEval($detail['code'][$i], false);
                        }
                    }
                    myFile::saveFile($path_rollback.'_code.php', '<?PHP'.chr(10).implode("\n/*------------------------------*/\n", $detail['code']));
                }

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
                            myFile::copy($file, dirname($path_rollback.$detail['file'][$i]), true);
                            myFile::saveFile($file, $detail['content'][$i]);
                        }
                    } else {
                        if(!empty($detail['content'][$i])) $list[] = $i;
                    }
                }
                $result = ['info'=>'', 'link'=>''];
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
                    if(count($detail['code'])>0) {
                        $script_update = "<?PHP\n";
                        $script_update .= join("\n/*------------------------------*/\n", $detail['code']);
                        $files[] = $dir.'_code.php';
                        myFile::saveFile($dir.'_code.php', $script_update);
                    }
                    if(count($detail['setting'])>0) {
                        $script_update = "<?PHP\n";
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
                    $result['info'] = $mystep->getLanguage('plugin_manager_error');
                } else {
                    $result['info'] = sprintf($mystep->getLanguage('plugin_manager_file'), count($detail['file']));
                }
                myFile::del(CACHE.'script');
                myFile::del(CACHE.'template');
                myFile::del(CACHE.'language');
                myFile::del(CACHE.'app');
                myFile::del(CACHE.'data');
                if($mode==1) self::checkFile(ROOT, true, 0);
                ob_clean();
                echo myString::toJson($result, $ms_setting->gen->charset);
                break;
            case 'plugin':
                $idx = end($info_app['path']);
                $file = PLUGIN.$idx.'/info.php';
                if(is_file($file)) {
                    $info = include($file);
                    $url .= 'plugin?p='.$idx.'&v='.$info['ver'];
                }
                $file = CACHE.'tmp/plugin.pack';
                myFile::getRemoteFile($url, $file);
                if(is_file($file) && filesize($file) > 1024) {
                    $mypack = $mystep->getInstance('myPacker', PLUGIN.$idx.'/' , $file);
                    $mypack->unpack();
                    unset($mypack);
                    myFile::del($file);
                    myStep::info('plugin_manager_update_succeed');
                } else {
                    myStep::info('plugin_manager_update_fail');
                }
                break;
            case 'app':
                $idx = end($info_app['path']);
                $file = APP.$idx.'/info.php';
                if(is_file($file)) {
                    $info = include($file);
                    $url .= 'app?a='.$idx.'&v='.$info['ver'];
                }
                $file = CACHE.'tmp/app.pack';
                myFile::getRemoteFile($url, $file);
                if(is_file($file) && filesize($file) > 1024) {
                    $mypack = $mystep->getInstance('myPacker', APP.$idx.'/' , $file);
                    $mypack->unpack();
                    unset($mypack);
                    myFile::del($file);
                    myStep::info('plugin_manager_update_succeed');
                } else {
                    myStep::info('plugin_manager_update_fail');
                }
                break;
            case 'pack':
                $type = $info_app['path'][2];
                $idx = $info_app['path'][3];
                if($type=='app') {
                    $dir = APP.$idx;
                } else {
                    $dir = PLUGIN.$idx;
                }
                $file = CACHE.'tmp/'.$type.'_'.$idx.'.pack';
                myFile::del($file);
                self::checkConfig($dir);
                $mypacker = $mystep->getInstance('myPacker', $dir, $file);
                $mypacker->addIgnore(self::IGNORE);
                $mypacker->pack();
                myFile::del($dir.'/config_new.php');
                myStep::file($file);
                break;
            default:
                showPluginPage('manager');
        }
        $mystep->end();
    }
    public static function remote() {
        global $ms_setting, $info_app;
        $setting = new myConfig(__DIR__.'/config.php');
        if(!$setting->update && !$ms_setting->gen->debug) myStep::header('404');
        $method = end($info_app['path']);
        switch($method) {
            case 'version':
                $ver = $info_app['para']['v'];
                $detail = require(__DIR__.'/version.php');
                $result = ['version'=>'', 'info'=>[]];
                foreach($detail as $k => $v) {
                    if(version_compare($k, $ver)>0) {
                        $result['info'][$k] = $v['info'];
                        $result['version'] = $k;
                    }
                }
                $apps = [];
                $dirs = myFile::find('*', APP, false, myFile::DIR);
                foreach($dirs as $k) {
                    $k .= 'info.php';
                    if(!is_file($k)) continue;
                    $info = include($k);
                    $apps[$info['app']] = $info['ver'];
                }
                $result['app'] = $apps;

                $plugins = [];
                $files = myFile::find('*', PLUGIN, false, myFile::DIR);
                foreach($files as $k) {
                    $k .= 'info.php';
                    if(!is_file($k)) continue;
                    $info = include($k);
                    $plugins[$info['idx']] = $info['ver'];
                }
                $result['plugin'] = $plugins;

                echo myString::toJson($result, $ms_setting->gen->charset);
                break;
            case 'download':
                $v = require(CONFIG.'version.php');
                $v_remote = $info_app['para']['v'];
                $version = require(__DIR__.'/version.php');
                $cache_file = __DIR__.'/update/'.md5($v.$v_remote);
                if(file_exists($cache_file)) {
                    $result = myFile::getLocal($cache_file);
                } else {
                    $list_file = array();
                    $list_setting = array();
                    $list_code = array();
                    foreach($version as $key => $value) {
                        if(version_compare($key, $v_remote)>0) {
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
                $mydb = new myDb('simpleDB', 'update', __DIR__.'/');
                if(!$mydb->check()) {
                    $mydb->create(array(
                        array('date', 10),
                        array('idx', 40),
                        array('ver_remote', 30),
                        array('ver_local', 30),
                        array('remote_ip', 50),
                        array('referer', 200)
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
            case 'plugin':
                $idx = myReq::get('p');
                $ver = myReq::get('v');
                $file = PLUGIN.$idx.'/info.php';
                if(is_file($file)) {
                    $info = include($file);
                    if(version_compare($ver, $info['ver'])===-1) {
                        $file = __DIR__.'/pack/plugin_'.$idx.'_'.$info['ver'].'.pack';
                        if(!file_exists($file)) {
                            self::checkConfig(PLUGIN.$idx);
                            $mypacker = $mystep->getInstance('myPacker', PLUGIN.$idx, $file);
                            $mypacker->addIgnore(self::IGNORE);
                            $mypacker->pack();
                            myFile::del(PLUGIN.$idx.'/config_new.php');
                        }
                        myController::file($file);
                    } else {
                        myController::header('404');
                    }
                } else {
                    myController::header('404');
                }
                break;
            case 'app':
                $idx = myReq::get('a');
                $ver = myReq::get('v');
                $file = APP.$idx.'/info.php';
                if(is_file($file)) {
                    $info = include($file);
                    if(version_compare($ver, $info['ver'])==-1) {
                        $file = __DIR__.'/pack/app_'.$idx.'_'.$info['ver'].'.pack';
                        if(!file_exists($file)) {
                            self::checkConfig(APP.$idx);
                            $mypacker = $mystep->getInstance('myPacker', APP.$idx, $file);
                            $mypacker->addIgnore(self::IGNORE);
                            $mypacker->pack();
                            myFile::del(APP.$idx.'/config_new.php');
                        }
                        myController::file($file);
                    } else {
                        myController::header('404');
                    }
                } else {
                    myController::header('404');
                }
                break;
            default:
                $the_file = __DIR__.'/check_file.php';
                $check_info = ['list_file'=>[], 'list_file_md5'=>[]];
                if(file_exists($the_file)) {
                    include($the_file);
                    $check_info['list_file'] = $list_file;
                    $check_info['list_file_md5'] = $list_file_md5;
                    unset($list_file, $list_file_md5);
                } else {
                    $check_info = ['error'=>'No verify data found from the update server!'];
                }
                echo myString::toJson($check_info, $ms_setting->gen->charset);
        }
    }
    public static function pack() {
        global $ms_setting;
        $setting = new myConfig(__DIR__.'/config.php');
        if(!$setting->pack && !$ms_setting->gen->debug) myStep::header('404');
        $ver = require(CONFIG.'version.php');
        $idx = 'mystep_v'.$ver;
        $dir = __DIR__.'/pack/';
        $dir = str_replace(DIRECTORY_SEPARATOR, '/', $dir);
        $log_file = $dir.'log.txt';
        $log = array(
            'time' => date('Y-m-d H:i:s'),
            'ip' => myReq::ip(),
            'agent' => myReq::server('HTTP_USER_AGENT'),
        );
        if(strpos($log['agent'], 'spider')!==false || strpos($log['agent'], 'bot')!==false) {
            myStep::header('404', '', true);
        }
        myFile::saveFile($log_file, implode(',', $log).chr(10), 'ab');
        if(!is_file($dir.$idx.'.zip')) {
            myFile::del($dir.$idx);
            myFile::mkdir($dir.$idx);
            error_reporting(0);
            set_time_limit(0);
            ini_set('memory_limit', '512M');
            myFile::move(APP.'myStep/menu.json', $dir);
            myFile::copy($dir.'../menu.json', APP.'myStep/menu.json');
            $mypacker = new myPacker(ROOT, $dir.$idx.'/mystep.pack');
            $mypacker->addIgnore(self::IGNORE);
            $mypacker->pack();
            myFile::del(APP.'myStep/menu.json');
            myFile::move($dir.'menu.json', APP.'myStep/menu.json');
            myFile::copy($dir.'../install.php', $dir.$idx.'/index.php');
            myFile::copy(ROOT.'readme.md', $dir.$idx.'/readme.md');
            $en = myStep::vendor('enphp');
            $en->encode($dir.$idx.'/index.php');
            $zip = new myZip($dir.$idx.'.zip', $dir);
            $zip->zip($dir.$idx);
            myFile::del($dir.$idx);
        }
        echo str_replace(ROOT, '/', $dir.$idx.'.zip');
    }
    public static function checkFile($dir='', $build=false, $layer=0) {
        global $list_file, $list_file_md5;
        if($layer==0) {
            $list_file = array();
            $list_file_md5 = array();
        }
        $the_dir = __DIR__.'/';
        $the_file = $the_dir.'check_file.php';
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
        $ignore = array_merge($ignore, self::IGNORE);
        $allow = array();
        if(is_file($dir.'/allow')) {
            $allow = file_get_contents($dir.'/allow');
            if(strlen($allow)==0) return;
            $allow = str_replace(chr(13), '', $allow);
            $allow = explode(chr(10), $allow);
        }
        if($build) {
            while (false !== ($file = readdir($handle))) {
                if(trim($file, '.') == '') continue;
                if(!empty($allow) && array_search($file, $allow)===false) continue;
                if(!empty($ignore) && array_search($file, $ignore)!==false) continue;
                if(strpos($file, '.bak')!==false || strpos($file, '_bak')!==false) continue;
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
                $content = '<?PHP
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
                if(trim($file, '.') == '') continue;
                if(!empty($allow) && array_search($file, $allow)===false) continue;
                if(!empty($ignore) && array_search($file, $ignore)!==false) continue;
                if(strpos($file, '.bak')!==false || strpos($file, '_bak')!==false) continue;
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
                    $result['miss'][] = $the_name;
                }
            }
        }
        closedir($handle);
        return $result;
    }

    public static function checkConfig($path, $name_fix='_new') {
        $path .= '/';
        if(is_file($path.'config.php') && !is_file($path.'config_default.php')) {
            if(is_file($path.'config/construction.php')) {
                $construction = include($path.'config/construction.php');
                if(isset($construction['password'])) {
                    $tmp = new myConfig($path.'config.php');
                    $tmp2 = &$tmp;
                    foreach($construction['password'] as $v) {
                        $v = explode('.', $v);
                        for($i=0,$m=count($v);$i<$m;$i++) {
                            $tmp2 = &$tmp2->{$v[$i]};
                        }
                        $tmp2 = md5('password');
                    }
                    $tmp->save('php', $path.'config'.$name_fix.'.php');
                    unset($tmp, $tmp2);
                } else {
                    myFile::copy($path.'config.php', $path.'config'.$name_fix.'.php');
                }
            }
        }
    }
}