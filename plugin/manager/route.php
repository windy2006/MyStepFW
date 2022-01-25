<?PHP
$rule = array(
    array('/manager/[any]', array('app\myStep\logCheck', 'plugin_manager::main')),
    array('/pack/[any]', 'plugin_manager::pack'),
);
$api = array(
    'check' => 'plugin_manager::remote',
    'update' => 'plugin_manager::remote',
    'download' => 'plugin_manager::remote',
    'plugin' => 'plugin_manager::remote',
    'app' => 'plugin_manager::remote',
);