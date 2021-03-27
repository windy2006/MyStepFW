<?PHP
if(is_file(PATH.'config.php')) {
    myStep::redirect('/CMS/');
}
global $mystep, $S, $info_app;
$tpl = new myTemplate($tpl_setting, $tpl_cache);
if($info_app['path'][0]!='install') {
    $info_app['path'][0] = 'install';
    $info_app['path'][1] = 0;
}
if(!isset($info_app['path'][1])) $info_app['path'][1] = 0;
if($info_app['path'][1]>0 && !myReq::check('post')) $info_app['path'][1] = 0;

reset:
$tpl_setting['name'] = implode('_', $info_app['path']);
$t = new myTemplate($tpl_setting, false, true);
switch ($info_app['path'][1]) {
    case 0:
    case 1:
        break;
    case 2:
        $S->merge(PATH.'config_default.php');
        $list = $S->build(PATH.'config/'.$S->gen->language.'.php');
        foreach($list as $v) {
            if(isset($v['idx'])) {
                $t->setLoop('setting', ['content'=> '</tbody>
                    <tbody id="'.$v['idx'].'" class="table-striped table-hover">
                    <tr class="font-weight-bold bg-secondary text-white">
                        <td colspan="2">'.$v['name'].'</td>
                    </tr>
                ']);
                $t->setLoop('item', $v);
            } else {
                $v['html'] = str_replace(' name="', ' class="form-control" name="', $v['html']);
                $v['html'] = str_replace('<label><input', '<label class="mt-2"><input style="display:inline;width:14px;height:14px;"', $v['html']);
                $t->setLoop('setting', ['content'=> '
                    <tr data-toggle="tooltip" data-placement="bottom" title="'.$v['describe'].'">
                        <td style="vertical-align: middle;font-size:14px;" width="120">'.$v['name'].'</td>
                        <td style="vertical-align: middle">'.$v['html'].'</td>
                    </tr>
                ']);
            }
        }
        break;
    case 3:
        if(myReq::check('post')) {
            $config = new myConfig(PATH.'/config_default.php');
            $app = $info_app['app'];
            include(PATH.'setting_expand.php');
            $config->set($_POST['setting']);
            myException::init(array(
                'log_type' => E_ALL ^ E_NOTICE,
                'callback_type' => E_ALL ^ E_NOTICE,
                'exit_on_error' => true
            ));
            $db = new myDb($config->db->type, $config->db->host, $config->db->user, $config->db->password, $config->db->charset);
            if($db->connect($config->db->pconnect)===false) {
                myStep::info('db_connect_error');
            }
            if($config->db->type=='mysql') {
                if($config->db->charset=='utf-8') $config->db->charset = 'utf8mb4';
                $charset_collate = $db->record('SHOW CHARACTER SET LIKE "'.$config->db->charset.'"');
                $charset_collate = $charset_collate['Default collation'];
            } else {
                $charset_collate = strtoupper($config->db->charset).'_CI_AS';
            }
            $strFind = array('{db_name}', '{pre}', '{charset}', '{host}', '{charset_collate}', '{web_name}');
            $strReplace = array($config->db->name, $config->db->pre, $config->db->charset, myReq::server('HTTP_HOST'), $charset_collate, $config->web->title);
            $result = $db->file(PATH.'/install/install.sql', $strFind, $strReplace);
            for($i=0,$m=count($result);$i<$m;$i++) {
                switch($result[$i][1]) {
                    case 'select':
                        $detail = ($i+1) . ' - 数据表 '.$result[$i][2].' 已生成！<br />'.chr(10);
                        break;
                    case 'create':
                        $detail =($i+1) . ' - 数据'.($result[$i][0]=='table'?'表':'库').' '.$result[$i][2].' 已生成！<br />'.chr(10);
                        break;
                    case 'drop':
                        $detail =($i+1) . ' - 数据'.($result[$i][0]=='table'?'表':'库').' '.$result[$i][2].' 已删除！<br />'.chr(10);
                        break;
                    case 'alter':
                        $detail =($i+1) . ' - 数据表 '.$result[$i][2].' 已变更！<br />'.chr(10);
                        break;
                    case 'delete':
                        $detail =($i+1) . ' - 数据表 '.$result[$i][2].' 已删除 '.$result[$i][3].' 条数据！<br />'.chr(10);
                        break;
                    case 'truncate':
                        $detail =($i+1) . ' - 数据表 '.$result[$i][2].' 已被清空！<br />'.chr(10);
                        break;
                    case 'insert':
                        $detail =($i+1) . ' - 数据表 '.$result[$i][2].' 已添加 '.$result[$i][3].' 条数据！<br />'.chr(10);
                        break;
                    case 'update':
                        $detail =($i+1) . ' - 数据表 '.$result[$i][2].' 已更新 '.$result[$i][3].' 条数据！<br />'.chr(10);
                        break;
                    default:
                        $detail =($i+1) . ' - 数据表 '.$result[$i][2].' 执行了操作（'.$result[$i][1].'）！<br />'.chr(10);
                        break;
                }
                $t->setLoop('result', ['content'=> $detail]);
            }
            $config->save('php', PATH.'/config.php');
        } else {
            $info_app['path'][1] = 2;
            unset($t);
            goto reset;
        }
        break;

}
$tpl->assign('main', $t->render('', false));
$mystep->show($tpl);
$mystep->end();
