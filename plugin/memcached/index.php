<?PHP
if(class_exists('Memcached')) {
    global $cache;
    require_once(__DIR__."/memoryCache.class.php");
    $setting = new myConfig(__DIR__.'/config.php');
    $cache = new myCache('memoryCache', $setting->get());
}