<?php
$setting = new myConfig(__DIR__.'/../config.php');
if(myReq::check('post')) {
    $setting->detail = html_entity_decode(myReq::post('detail'));
    $setting->save();
    myFile::del(CACHE.'script');
}

$tpl_sub->assign('title', '应用前端框架调整');
$tpl_sub->assign('detail', ($setting->detail=='')?'{}':$setting->detail);

$dirs = myFile::find('', APP, false, myFile::DIR);
$dirs = array_map(function ($v) {return basename($v);} , $dirs);
foreach($dirs as $k) {
    if(!is_file(APP.$k.'/info.php')) continue;
    $tpl_sub->setLoop('app', include(APP.$k.'/info.php'));
}

$dirs = myFile::find('', __DIR__.'/../files/jquery/', false, myFile::FILE);
$dirs = array_map(function ($v) {return basename($v);} , $dirs);
foreach($dirs as $k) {
    if(strpos($k, 'migrate')) continue;
    $tpl_sub->setLoop('jq', ['ver'=>$k]);
}

$dirs = myFile::find('', __DIR__.'/../files/bootstrap/', false, myFile::DIR);
$dirs = array_map(function ($v) {return basename($v);} , $dirs);
foreach($dirs as $k) {
    $tpl_sub->setLoop('bs', ['ver'=>$k]);
}