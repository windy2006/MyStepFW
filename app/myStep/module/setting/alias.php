<?php
if(myReq::check('post')) {
    foreach($_POST as $k => $v) {
        if(empty($v)) unset($_POST[$k]);
    }
    myFile::saveFile(CONFIG.'class_alias.php', '<?PHP'.chr(10).'return '.var_export($_POST, true).';');
    $mystep->setAddedContent('end', '<script>alert("'.$mystep->getLanguage('setting_done').'");</script>');
    $alias = $_POST;
} else {
    if(is_file(CONFIG.'class_alias.php')) {
        $alias = include(CONFIG.'class_alias.php');
    } else {
        $alias = array();
    }
}
$setting_class = include(CONFIG.'class.php');
$idx = $info_app['path'][3];
if(isset($setting_class[$idx])) {
    $info = $setting_class[$idx];
} else {
    myStep::redirect();
}
$filter = '*'.str_replace(',',',*',$info['ext']);
$filter = explode(',',$filter);
$files = array();
foreach($filter as $f) {
    if(myFile::find($f, myFile::realPath($info['path'])))
        $files += myFile::find($f, myFile::realPath($info['path']));
}
$i = 0;
foreach($files as $class) {
    $class = strstr(basename($class), '.', true);
    if(class_exists($class)) {
        $tr = $i++%2 ? '</tr><tr>':'';
        $t->setLoop('class', array('name'=>$class, 'alias'=>$alias[$class]??'', 'tr'=>$tr));
    }
}
$t->assign('dummy', ($i%2?'<td colspan="2"></td>':''));
$tpl->assign('path', 'manager/setting/class');