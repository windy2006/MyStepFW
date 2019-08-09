<?php
if(version_compare(PHP_VERSION,'7.0.0','<'))
    die('MyStep Framework can only run under PHP 7.0 or upper version!');

define('VER', '1.0.3');
define('ROOT', str_replace('\\','/',dirname(__FILE__)).'/');
define('LIB', ROOT.'lib/');
define('APP', ROOT.'app/');
define('CACHE', ROOT.'cache/');
define('CONFIG', ROOT.'config/');
define('PLUGIN', ROOT.'plugin/');
define('STATICS', ROOT.'static/');
define('VENDOR', ROOT.'vendor/');
define('FILE', ROOT.'files/');

require_once(LIB.'myStep.class.php');
myStep::init();
