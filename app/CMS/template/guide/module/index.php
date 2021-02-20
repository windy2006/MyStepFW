<?PHP
$md = new Erusev\Parsedown\Parsedown();
$guide = myFile::getLocal(ROOT.'readme.md');
$guide = str_replace(['<','>',chr(13)],['&lt;','&gt;',''], $guide);
$guide = $md->toHtml($guide);

preg_match_all('#<h(\d)>(.+?)</h\1>#', $guide, $matches);
$m = count($matches[0]);
if($m>0) {
    $lvl_prev = $matches[1][0];
    $lvl_cur = $matches[1][0];
    $idx = [1];
    $guide = str_replace($matches[0][0], '<h'.$matches[1][0].'>'.$matches[2][0].'<a id="p1"></a></h'.$matches[1][0].'>', $guide);
    for($i=1;$i<$m;$i++) {
        if($lvl_cur < $matches[1][$i]) {
            array_push($idx, 1);
        } elseif($lvl_cur > $matches[1][$i]) {
            for($j=$lvl_cur-$matches[1][$i];$j>0;$j--) array_pop($idx);
            $idx[count($idx)-1]++;
        } else {
            $idx[count($idx)-1]++;
        }
        $lvl_cur = $matches[1][$i];
        $guide = str_replace($matches[0][$i], '<h'.$matches[1][$i].'>'.$matches[2][$i].'<a id="p'.join('.', $idx).'"></a></h'.$matches[1][$i].'>', $guide);
    }
}
/*
preg_match_all('#<h2>(.+?)</h2>#', $guide, $matches);
for($i=0,$m=count($matches[0]);$i<$m;$i++) {
    $guide = str_replace($matches[0][$i], '<h3>'.str_replace('：','',$matches[1][$i]).'<a id="p'.($i+1).'"></a></h3>', $guide);
}
*/
$t->assign('content', $guide);
unset($md);