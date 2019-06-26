<?php
$preload = 'lib.php';
$format = array(
    'test' => '([A-Z][a-z]+)+',
);
$rule = array(
    array('/test/[test]', array('perCheck,3','routeTest'))
);