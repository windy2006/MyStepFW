<?php
$minify = new myMinify('js', PATH.'data/minify/cache.js');
if(!$minify->check(60)) {
    $minify->add(PATH.'data/minify/test.js');
}
$js = $minify->get();
echo 'JS:<br />';
echo htmlspecialchars($js);
echo '<br /><br />-------------------<br /><br />';
$minify = new myMinify('css', PATH.'data/minify/cache.css');
if(!$minify->check(60)) {
    $minify->add(PATH.'data/minify/test.css');
}
$css = $minify->get();
echo 'CSS:<br />';
echo htmlspecialchars($css);