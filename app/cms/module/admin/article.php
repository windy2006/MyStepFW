<?PHP
$method = strtolower($info_app['path'][2]);
$file = dirname(__FILE__).'/article/'.$method.'.php';
if(!file_exists($file)) {
    myStep::info('module_missing');
}
include($file);