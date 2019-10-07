<?php
if(myReq::check('post')) {
    $config = new myConfig(CONFIG.'config.php');
    $setting = myReq::post('setting');
    $setting['gen']['s_pwd'] = md5($setting['gen']['s_pwd']);
    $config->set($setting);
    $config->save('php');
    myController::redirect();
}
$tpl_setting = array(
    'name' => 'config',
    'path' => APP.'myStep/template',
    'style' => '',
    'path_compile' => CACHE.'template/myStep/'
);
$t = new myTemplate($tpl_setting, $tpl_cache);

$file = CONFIG.'config_default.php';
$builder = CONFIG.'construct/default.php';
$config = new myConfig($file);
$config->cookie->domain = myReq::server('HTTP_HOST');

$dirs = myFile::find('',APP,false, myFile::DIR);
$dirs = array_map(function($v){return basename($v);} ,$dirs);
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
$t->assign('path_root', str_replace(myFile::rootPath(), '/', ROOT));
$t->display();