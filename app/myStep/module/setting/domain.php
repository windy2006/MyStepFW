<?PHP
if(myReq::check('post')) {
    $result = [];
    $list = myReq::post('[ALL]');
    for($i=0,$m=count($list['domain']);$i<$m;$i++) {
        if(empty($list['domain'][$i]) || empty($list['rule'][$i])) continue;
        $result[$list['domain'][$i]] = $list['rule'][$i];
    }
    myFile::saveFile(CONFIG.'domain.php', '<?PHP'.chr(10).'return '.var_export($result, 1).';');
    $mystep->setAddedContent('end', '<script>alert("'.$mystep->getLanguage('setting_done').'");location.href=document.referrer;</script>');
}
if(is_file(CONFIG.'domain.php')) {
    $list = include(CONFIG.'domain.php');
    $t->assign('rule_list', myString::toJson($list));
}
$list = [];
$dirs = myFile::find('', APP, false, myFile::DIR);
$dirs = array_map(function ($v) {return basename($v);} , $dirs);
foreach($dirs as $k) {
    $list[] = array('idx'=>'MS_APP', 'rule'=>$k);
}
foreach($router->rules as $k) {
    if(!preg_match('@^/\w+/.*@', $k['pattern'])) continue;
    $list[] = array('idx'=>$k['idx'], 'rule'=>$k['pattern']);
}
$t->assign('list', myString::toJson($list));