<?php
$rule = array(
    array('/api/[str]/[any]', 'myStep::api'), 
    array('/module/[str]/[any]', 'myStep::module'), 
    array('/language/[str]/[any]', 'myStep::language'), 
    array('/setting/[any]', 'myStep::setting'), 
    array('/captcha/[any]', 'myStep::captcha, 4, 3'), 
    array('/upload', 'myStep::upload'), 
    array('/download/[any]', 'myStep::download'), 
    array('/remove_ul/[any]', 'myStep::remove_ul'), 
    array('/manager/[any]', array('app\myStep\logCheck', 'myStep::getModule')), 
);
$api = array(
    'error' => 'app\myStep\getError', 
    'data' => 'app\myStep\getData', 
    'autoComplete' => 'app\myStep\autoComplete'
);