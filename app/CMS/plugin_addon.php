<?php
$mystep->regTag('info', 'app\CMS\parseInfo');
for($i=0,$m=count($news_cat);$i<$m;$i++) {
    if(($news_cat[$i]['show'] & 1) == 0) continue;
    if($news_cat[$i]['web_id'] != $web_info['web_id']) continue;
    $news_cat[$i]['idx'] =  $i;
    if(empty($news_cat[$i]['link'])) $news_cat[$i]['link'] = \app\CMS\getLink($news_cat[$i], 'catalog');
    $tpl->setLoop('news_cat', $news_cat[$i]);
}
$tpl->assign('news_cat', myString::toJson($news_cat));

