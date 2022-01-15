<?PHP
$rule = array(
    array('/sample_route/[any]', array('plugin_sample::main')),
);
$api = array(
    'sample' => 'plugin_sample::api',
);