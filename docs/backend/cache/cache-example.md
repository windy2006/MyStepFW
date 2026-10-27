# 缓存示例

缓存不是“读到假值就重算”，clean也不是“清空所有内容”。

## 运行位置与准备

在已经加载框架类库的独立测试脚本中运行；也可放入独立测试应用模块。不要再次调用myStep::init或start。以下代码使用自己的随机临时目录或SQLite内存库，不读取站点数据库或私有配置。代码块包含完整处理与收尾，多个范例分别运行。

## 完整示例

```php
<?php
$directory = sys_get_temp_dir().'/mystep-cache-'.bin2hex(random_bytes(6));
mkdir($directory, 0700);
try {
    $localCache = new myCache('File', $directory, 0); // 关闭概率回收，便于观察。
    $localCache->set('sample:false', false, 60);
    $value = $localCache->fetch('sample:false', $hit);
    $calls = 0;
    $producer = function () use (&$calls) { $calls++; return 0; };
    $first = $localCache->remember('sample:zero', $producer, 60);
    $second = $localCache->remember('sample:zero', $producer, 60);
    $localCache->clean(); // 两个条目尚未过期，仍然保留。
    $localCache->fetch('sample:false', $afterClean);
    $localCache->clear(); // 仅针对本例的独占后端目录。
    $localCache->fetch('sample:false', $afterClear);
    echo json_encode([
        'value'=>$value, 'hit'=>$hit, 'first'=>$first, 'second'=>$second,
        'producer_calls'=>$calls, 'after_clean'=>$afterClean, 'after_clear'=>$afterClear,
    ]);
    unset($localCache);
} finally {
    myFile::del($directory);
}
```

## 预期结果

value为false且hit=true；first和second都是0，producer_calls=1；after_clean=true，after_clear=false。

## 调用顺序与实际应用

fetch通过引用参数单独报告命中状态；remember为一个明确业务键复用结果。set/fetch与remember使用不同内部键空间，不能用fetch同名键去读取remember的封装；读取结果应继续用remember。业务键应包含站点、语言和必要的权限维度。实际页面不要调用全局$cache->clear来模拟本例；后台清理的范围必须明确，Session及上传文件不是可随意清理的页面缓存。
