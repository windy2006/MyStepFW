<?PHP
if(myReq::check('post')) {
    $setting = myReq::post('setting');
    if(!empty($setting['db']['host']) && !empty($setting['db']['user']) && !empty($setting['db']['password'])) {
        $db = new myDb($setting['db']['type'], $setting['db']['host'], $setting['db']['user'], $setting['db']['password'], $setting['db']['charset']);
        $db->connect($setting['db']['pconnect']=='true');
        $db->create($setting['db']['name']);
    }
    $config = new myConfig(CONFIG.'config.php');
    $config->set($setting);
    $config->save('php');
    myController::redirect();
}
$tpl_setting = array(
    'name' => 'init',
    'path' => APP.'myStep/template',
    'style' => '',
    'path_compile' => CACHE.'template/myStep/'
);
$t = new myTemplate($tpl_setting);

$file = CONFIG.'config_default.php';
$builder = CONFIG.'construction/default.php';
$config = new myConfig($file);

$dirs = myFile::find('', APP, false, myFile::DIR);
$dirs = array_map(function ($v) {return basename($v);} , $dirs);
$ext_setting = array('router'=>['default_app'=>['select', $dirs]]);

ob_start();
$list = $config->build($builder, $ext_setting);
ob_clean();
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
$t->assign('server', myReq::server('SERVER_SOFTWARE'));
$t->assign('path_admin', $app_root);
$t->assign('path_root', ROOT_WEB);
$t->render();