<?PHP
$app = $info_app['path'][2] ?? '';
if(!empty($app)) {
    $builder = APP.$app.'/config/'.$s->gen->language.'.php';
    if(!is_file($builder)) myStep::info('page_error_setting');
    $file = APP.$app.'/config.php';
    if(!is_file($file)) myFile::copy(CONFIG.'config.php', $file);
} else {
    $file = CONFIG.'config.php';
    $builder = CONFIG.'construction/'.$s->gen->language.'.php';
}
$config = new myConfig($file);
if(myReq::check('post')) {
    $config->set($_POST['setting'], true);
    $config->save('php');
    $mystep->setAddedContent('end', '<script>alert("'.$mystep->getLanguage('setting_done').'");</script>');
}

$dirs = myFile::find('', APP, false, myFile::DIR);
$dirs = array_map(function ($v) {return basename($v);}, $dirs);
foreach($dirs as $k) {
    $t->setLoop('app', array('name'=>$k, 'selected'=>($app==$k?'selected':'')));
}
$files = myFile::find('', PATH.'language', false, myFile::FILE);
$files = array_map(function($v){return str_replace('.php', '', basename($v));}, $files);
$ext_setting = array(
    'gen'=>['language'=>['select', $files]],
    'router'=>['default_app'=>['select', $dirs]]
);

$list = $config->build($builder, $ext_setting);
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
$tpl->assign('path', '/setting/');