<?php
$preload = 'lib.php';
$rule = array(
    array('/api/[any]', 'myStep::getApi'),
    array('/module/[any]', 'myStep::module'),
    array('/language/[str]/[any]', 'myStep::language'),
    array('/setting/[any]', 'myStep::setting'),
    array('/captcha/[any]', 'myStep::captcha,4,3'),
    array('/manager/[any]', array('app\myStep\logCheck','getModule')),
    array('/upload', 'myStep::upload'),
    array('/download/[any]', 'myStep::download'),
    array('/remove_ul/[any]', 'myStep::remove_ul'),
);
$api = array(
    'error' => 'app\myStep\getError',
    'data' => 'app\myStep\getData',
    'autoComplete' => 'app\myStep\autoComplete'
);