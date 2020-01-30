<?php
$rule = array(
    array('/admin_cms/[any]', array('app\\cms\\installCheck, index', 'app\cms\logCheck', 'myStep::getModule')), 
);
$api = array(
    'rss' => 'app\cms\rss', 
    'get' => 'app\cms\getData', 
);