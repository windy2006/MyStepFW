<?php
if(is_file(PATH.'config.php')) {
    myStep::redirect('/cms/');
}
global $mystep, $s, $router, $info_app;
$router->checkRoute(CONFIG.'route.php', PATH.'route.php', $info_app['app']);
$tpl = new myTemplate($tpl_setting, $tpl_cache);
$info_app['path'][1] = 'install';
if(!isset($info_app['path'][2])) $info_app['path'][2] = 0;
reset:
$tpl_setting['name'] = implode('_', array_slice($info_app['path'], 1));
$t = new myTemplate($tpl_setting, false);
$t->allow_script = true;
switch ($info_app['path'][2]) {
    case 0:
        break;
    case 1:
        break;
    case 2:
        $s->merge(PATH.'config_default.php');
        $list = $s->build(PATH.'config/'.$s->gen->language.'.php');
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
            $c = new myConfig(PATH.'/config_default.php');
            $c->set($_POST['setting']);

            myException::init(array(
                'log_type' => 0,
                'callback_type' => 0,
                'exit_on_error' => false
            ));
            $db = new myDb($c->db->type, $c->db->host, $c->db->user, $c->db->password, $c->db->charset);
            if($db->connect($c->db->pconnect)===false) {
                myStep::info('db_connect_error');
            }
            $c->save('php', PATH.'/config.php');

            myException::init(array(
                'log_type' => E_ALL ^ E_NOTICE,
                'callback_type' => E_ALL ^ E_NOTICE,
                'exit_on_error' => true
            ));
            $charset_collate = $db->record('SHOW CHARACTER SET LIKE "'.$c->db->charset.'"');
            $strFind = array(' {db_name}', ' {pre}', ' {charset}', ' {host}', ' {charset_collate}', ' {web_name}');
            $strReplace = array($c->db->name, $c->db->pre, $c->db->charset, myReq::server('HTTP_HOST'), $charset_collate['Default collation'], $c->web->title);
            $result = $db->file(PATH.'/install/install.sql', $strFind, $strReplace);
            for($i=0, $m=count($result);$i<$m;$i++) {
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
        } else {
            $info_app['path'][1] = 2;
            unset($t);
            goto reset;
        }
        break;

}

$tpl->assign('main', $t->display('', false));
$mystep->show($tpl);
$mystep->end();
