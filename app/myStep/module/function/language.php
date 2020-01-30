<?php
$app = myReq::r('app');
if(empty($app) || !is_dir(APP.$app)) {
    myStep::info('app_missing');
}
$dir = APP.$app."/language/";
$list = myFile::find('*.php', $dir, false, myFile::FILE);
if($list==false) {
    myStep::info('page_error_setting');
}
$list = array_map(function ($v) {return basename($v);} , $list);
$type = myReq::g('type');
if(empty($type)) $type = 'default';
if($type == 'default') {
    $content = myFile::getLocal($dir.'default.php');
    if(preg_match('#return include.+?(\w+)\.php#', $content, $match)) {
        $type = $match[1];
    }
}
if(myReq::check('post')) {
    $lng = myReq::p('language');
    $new = myReq::p('lng_new_idx');
    if(!empty($new)) {
        $type = $new;
        $list[] = $type.'.php';
    }
    $script = '<?php
return '.var_export($lng, true).';';
    myFile::saveFile($dir.$type.'.php', $script);
    $mystep->setAddedContent('end', '<script>alert("'.$mystep->getLanguage('setting_done').'");</script>');
} else {
    $lng = include($dir.$type.'.php');
}
foreach($list as $v) {
    $v = str_replace('.php', '', $v);
    $t->setLoop("type", array("selected"=>($v==$type?'selected':''), "name"=>$v));
}
$i=1;
foreach($lng as $k => $v) {
    $t->setLoop("item", array("idx"=>$i++, "key"=>$k, "value"=>htmlspecialchars($v)));
}