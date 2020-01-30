<?php
$id = myReq::get('id', 'int');
if(!empty($id)) {
    if($id & 1) {
        myFile::del(CACHE.'script');
        myFile::mkdir(CACHE.'script');
    }
    if($id & 2) {
        myFile::del(CACHE.'template');
        myFile::mkdir(CACHE.'template');
    }
    if($id & 4) {
        myFile::del(CACHE.'language');
        myFile::mkdir(CACHE.'language');
    }
    if($id & 8) {
        myFile::del(CACHE.'op');
        myFile::mkdir(CACHE.'op');
    }
    if($id & 16) {
        myFile::del(CACHE.'session');
        myFile::mkdir(CACHE.'session');
    }
    if($id & 32) {
        myFile::del(CACHE.'tmp');
        myFile::mkdir(CACHE.'tmp');
    }
    if($id & 64) {
        myFile::del(CACHE.'app');
        myFile::mkdir(CACHE.'app');
    }
    if($id & 128) {
        myFile::del(CACHE.'setting');
        myFile::mkdir(CACHE.'setting');
    }
    if($id & 256) {
        myFile::del(CACHE.'data');
        myFile::mkdir(CACHE.'data');
    }
    if($id & 512) {
        myFile::del(CACHE.'page');
        myFile::mkdir(CACHE.'page');
    }
    $mystep->setAddedContent('end', '<script>alert("'.$mystep->getLanguage('setting_done').'");location.href=document.referrer;</script>');
}
$dirs = myFile::find('', CACHE, false, myFile::DIR);
$dirs = array_map(function ($v) {return basename($v);} , $dirs);
$t->assign('script', 0);
$t->assign('template', 0);
$t->assign('language', 0);
$t->assign('setting', 0);
$t->assign('op', 0);
$t->assign('session', 0);
$t->assign('tmp', 0);
$t->assign('app', 0);
$t->assign('data', 0);
$t->assign('page', 0);
foreach($dirs as $k) {
    $t->assign($k, myFile::getSize(CACHE.$k));
}