<?php
$rule = array(
    array('/update/[any]', array('app\myStep\logCheck', 'plugin_update::update')),
    array('/pack/[any]', 'plugin_update::pack'),
);
$api = array(
    'check' => 'plugin_update::remote',
    'update' => 'plugin_update::remote',
    'download' => 'plugin_update::remote',
);