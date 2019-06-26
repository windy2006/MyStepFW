<?php
$minify = new myMinify('js', PATH.'data/minify/cache.js');
if(!$minify->check(60)) {
    $minify->add(PATH.'data/minify/test.js');
}
$code = $minify->get();
echo htmlspecialchars($code);

/*
//Or :
$minify->show();
*/