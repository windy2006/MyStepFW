<?php
if(count($_POST)>0) {
    $alias = array();
    foreach($_POST as $k => $v) {
        if(empty($v)) {
            unset($_POST[$k]);
        } else {
            $alias[$v] = $k;
        }
    }
    myFile::saveFile(CONFIG.'method_alias/'.$info_app['path'][3].'.php', '<?PHP'.chr(10).'return '.var_export($alias, true).';');
    $mystep->setAddedContent('end', '<script>alert("'.$mystep->getLanguage('setting_done').'");</script>');
} else {
    if(is_file(CONFIG.'method_alias/'.$info_app['path'][3].'.php')) {
        $alias = include(CONFIG.'method_alias/'.$info_app['path'][3].'.php');
    } else {
        $alias = array();
    }
}
$detail = new myReflection($info_app['path'][3]);
$methods = $detail->getFunc();
$t->assign('name', $info_app['path'][3]);
$t->assign('doc', $detail->getComment());
$t->assign('display', is_file(APP.'Document/module/'.$info_app['path'][3].'.php') ? 'inline' : 'none');

$n = 1;
$i = 0;
foreach($methods as $method) {
    $doc = $method->getDocComment();
    $doc = trim($doc, '/*');
    $name = $method->getName();
    if(empty($doc)) continue;
    $t->setLoop('item', ['no'=>$n++, 'name'=>$name, 'doc'=>$doc]);

    if(strpos($name, '__')===0) continue;
    $tr = $i++%2 ? '</tr><tr>':'';
    $k = array_search($name, $alias);
    if($k == false) $k = '';
    $t->setLoop('method', array('name'=>$name, 'alias'=>$k, 'tr'=>$tr));
}
$t->assign('dummy', ($i%2?'<td colspan="2"></td>':''));
$tpl->assign('path', 'manager/setting/class');