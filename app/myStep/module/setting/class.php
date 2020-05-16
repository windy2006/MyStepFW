<?PHP
if(myReq::check('post')) {
    $path = myReq::p('path');
    $ext = myReq::p('ext');
    $idx = myReq::p('idx');
    $m = count($path);
    $result = array();
    for($i=0;$i<$m;$i++) {
        $result[] = array(
            'path' => myFile::realPath($path[$i]),
            'ext' => $ext[$i],
            'idx' => empty($idx[$i]) ? array() : myEval($idx[$i], true)
        );
    }
    myFile::saveFile(CONFIG.'class.php', '<?PHP'.chr(10).'return '.var_export($result, true).';');
    $mystep->setAddedContent('end', '<script>alert("'.$mystep->getLanguage('setting_done').'");</script>');
}
$setting_class = include(CONFIG.'class.php');
foreach($setting_class as $k => $v) {
    $v['path'] = str_replace(ROOT, '', $v['path']);
    if(!isset($v['idx'])) $v['idx'] = array();
    $v['idx'] = empty($v['idx'])?'':var_export($v['idx'], true);
    $v['id'] = $k;
    $t->setLoop('class', $v);
}