<?PHP
// 独立示例对象：不替换框架全局$cache，不连接真实SQL缓存表，也不flush共享服务。
$demoCache = new myCache('File', [
    'path' => CACHE.'tmp/cache-demo',
    'namespace' => 'document',
    'allowed_classes' => false,
], 0);
$demoKey = 'sample:'.bin2hex(random_bytes(8));
try {
    $demoCache->set($demoKey, 'File_cache_test', 60);
    echo $demoCache->get($demoKey).'<br />';
    $demoCache->set($demoKey, false, 60);
    $demoValue = $demoCache->fetch($demoKey, $demoHit);
    echo 'false值命中：'.($demoHit && $demoValue === false ? 'yes' : 'no').'<br />';

    echo $demoCache->getData_func('strtoupper', ['cache_demo'], 60).'<br />';

    // 内置File/MySQL支持计算锁；其他后端先检查能力。
    if($demoCache->supports(interface_cache_lock::class)) {
        $demoValue = $demoCache->rememberLocked($demoKey, function () {
            return 'rememberLocked_cache_test';
        }, 60, 5);
        echo $demoValue.'<br />';
    }
} finally {
    $demoCache->remove($demoKey);
    $demoCache->getData_func('strtoupper', ['cache_demo'], 0); // 0表示删除函数缓存。
    // remember内部使用独立键，TTL较短；不通过clear清空其他请求的示例数据。
}
unset($demoCache, $demoKey, $demoValue, $demoHit);
