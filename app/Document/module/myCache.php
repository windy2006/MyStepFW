<?php
$cache = new myCache();
$cache->regAlias(array(
            's' => 'set', 
            'g' => 'get', 
            'r' => 'remove', 
            'c' => 'clean', 
            'db' => 'getData_DB', 
            'func' => 'getData_func', 
));

$cache->change('myCache_File', PATH.'data/cache/');
$key = 'cache_test';
$cache->s($key, 'File_cache_test');
echo $cache->g($key).'<br />';
$cache->r($key);
$cache->c();

$cache->change('myCache_MySQL', array(
    'host' => $mystep->setting->db->host, 
    'user' => $mystep->setting->db->user, 
    'password' => $mystep->setting->db->password, 
    'name' => $mystep->setting->db->name, 
    'charset' => $mystep->setting->db->charset
));

if(class_exists(''))
$key = 'cache_test';
$cache->set($key, 'MySQL_cache_test');
echo $cache->get($key).'<br />';
$cache->remove($key);
$cache->clean();

if(class_exists('Memcached')) {
    $cache->change('memoryCache', array(
        'server' => '127.0.0.1:11211', 
        'expire' => 86400, 
        'persistant' => true, 
        'weight' => 5, 
        'timeout' => 1, 
        'retry_interval' => 10, 
    ));
    $key = 'cache_test';
    $cache->set($key, 'memCache_cache_test');
    echo $cache->get($key).'<br />';
    $cache->remove($key);
    $cache->clean();
}

unset($cache);