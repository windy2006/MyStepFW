<?PHP
$md = new Erusev\Parsedown\Parsedown();
$guide = myFile::getLocal(ROOT.'readme.md');
$guide = $md->toHtml($guide);
preg_match_all('#<h2>(.+?)</h2>#', $guide, $matches);
for($i=0,$m=count($matches[0]);$i<$m;$i++) {
$guide = str_replace($matches[0][$i], '<h3>'.str_replace('：','',$matches[1][$i]).'<a id="p'.($i+1).'"></a></h3>', $guide);
}
$t->assign('content', $guide);
unset($md);