<?php
if(version_compare(PHP_VERSION,'7.0.0','<'))
    die('MyStep Framework can only run under PHP 7.0 or upper version!');

define('ROOT', str_replace('\\','/',dirname(__FILE__)).'/');
define('APP', ROOT.'app/');
define('LIB', ROOT.'lib/');
define('CACHE', ROOT.'cache/');
define('CONFIG', ROOT.'config/');
define('PLUGIN', ROOT.'plugin/');
define('STATICS', ROOT.'static/');
define('VENDOR', ROOT.'vendor/');
define('FILE', ROOT.'files/');

require_once(LIB.'function.php');
initConfig();

$router = new myRouter((array)$s->router);
$router->setRules(CONFIG.'route.php');
if(!$router->check($lib_list)) {
    $info_app = $router->parse();
    if(!empty($info_app)) {
        if(!is_dir(APP.$info_app['app'])) {
            array_unshift($info_app['path'], $info_app['app']);
            $info_app['app'] = $s->router->default_app;
        }
        if(is_file(APP.$info_app['app'].'/config.php')) {
            $s->merge(APP.$info_app['app'].'/config.php');
        }
        require(APP.$info_app['app'].'/index.php');
    } else {
        myController::redirect('/');
    }
}
