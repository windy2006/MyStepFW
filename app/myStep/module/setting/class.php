<?PHP
if(myReq::check('post')) {
    $path = myReq::post('path');
    $ext = myReq::post('ext');
    $idx = myReq::post('idx');
    if(!is_array($path) || !is_array($ext) || !is_array($idx)) throw new InvalidArgumentException('类加载规则无效。');
    $result = [];
    foreach($path as $i => $directory) {
        if($directory === '') continue;
        $result[] = ['path'=>$directory, 'ext'=>$ext[$i]??'', 'idx'=>myClassLoader::mapping($idx[$i]??'')];
    }
    $result = myClassLoader::normalize($result, true);
    myClassLoader::save(CONFIG.'class.php', $result, is_file(CONFIG.'class.php')?hash_file('sha256', CONFIG.'class.php'):'missing');
    $mystep->setAddedContent('end', '<script>alert("'.$mystep->getLanguage('setting_done').'");</script>');
}
$setting_class = include(CONFIG.'class.php');
foreach($setting_class as $k => $v) {
    $v['path'] = htmlspecialchars(myPath::relative($v['path']), ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
    $v['idx'] = htmlspecialchars(var_export($v['idx']??[], true), ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
    $v['ext'] = htmlspecialchars($v['ext'], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
    $v['id'] = $k;
    $t->setLoop('class', $v);
}
