<?PHP
if($GLOBALS['info_app']['app']=='myStep') return;
if(class_exists('Memcached')) {
    global $cache;
    require_once(__DIR__."/memoryCache.class.php");
    $mc_setting = (new myConfig(__DIR__.'/config.php'))->get();
    if(isset($mc_setting['server'])) {
        $mc_setting['server'] = myString::fromJson($mc_setting['server']);
    }
    $cache = new myCache('memoryCache', $mc_setting);
}