<?php
if(!file_exists(ROOT.'readme.md')) {
    myStep::info($mystep->getLanguage('page_error_module'));
}
$md = vendor('Parsedown');
$guide = myFile::getLocal(ROOT.'readme.md');
$guide = str_replace(chr(13),'', $guide);
$guide = preg_split('#[\n]{2,}#', $guide);
$detail = array();
for($i=0,$m=count($guide);$i<$m;$i++) {
    $detail[$i] = array();
    $guide[$i] = preg_replace('#\n[\-]{5,}#', '', $guide[$i]);
    $lines = explode(chr(10), $guide[$i]);
    $line = array();
    $detail[$i]['section'] = str_replace('：', '', $lines[0]);
    $detail[$i]['detail'] = array();
    for($j=1,$n=count($lines);$j<$n;$j++) {
        $lines[$j] = preg_replace('#^\- #', '', $lines[$j]);
        if($j==1 && strpos($lines[$j],' - ')===false) {
            $detail[$i]['describe'] = $lines[$j];
        } elseif(preg_match('#[\s]{3,}#', $lines[$j])) {
            $line[1] .= chr(10).$lines[$j];
        } elseif(strpos($lines[$j],' - ')) {
            if(!empty($line)) {
                //$detail[$i]['detail'][$line[0]] = str_replace('  ', '&nbsp; ', nl2br($line[1]));
                $detail[$i]['detail'][$line[0]] = $md->text($line[1]);
            }
            $line = explode(' - ', $lines[$j]);
        }
    }
    if(!empty($line)) {
        //$detail[$i]['detail'][$line[0]] = str_replace('  ', '&nbsp; ', nl2br($line[1]));
        $detail[$i]['detail'][$line[0]] = $md->text($line[1]);
    }
}
$setting_tpl['name'] = 'guide';
$t = new myTemplate($setting_tpl, false, true);
$t->assign('detail', myString::toJson($detail, $s->gen->charset));
$t->assign('path_root', ROOT_WEB);
$content = $t->display('s', false);