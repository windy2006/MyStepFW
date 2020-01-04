<?PHP
$method = strtolower($info_app['path'][2]);
$file = __DIR__.'/article/'.$method.'.php';
if(!file_exists($file)) {
    myStep::info('module_missing');
}
include($file);