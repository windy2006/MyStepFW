# 文件示例

先建立明确边界，再读取文件；JSON解析与HTML展示分别处理。

## 运行位置与准备

在已经加载框架类库的独立测试脚本中运行；也可放入独立测试应用模块。不要再次调用myStep::init或start。以下代码使用自己的随机临时目录或SQLite内存库，不读取站点数据库或私有配置。代码块包含完整处理与收尾，多个范例分别运行。

## 完整示例

```php
<?php
$directory = sys_get_temp_dir().'/mystep-file-'.bin2hex(random_bytes(6));
mkdir($directory, 0700);
try {
    mkdir($directory.'/data', 0700);
    $value = ['title'=>'中文 & <b>文本</b>','enabled'=>false,'count'=>0];
    file_put_contents($directory.'/data/example.json', json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    $resolved = myFile::resolveWithin($directory, 'data/example.json');
    if ($resolved === false) throw new RuntimeException('示例文件不在允许范围');
    $json = myFile::getLocal($resolved);
    $restored = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    echo json_encode([
        'round_trip'=>$restored === $value,
        'traversal_rejected'=>myFile::resolveWithin($directory, '../outside.json') === false,
        'html_title'=>htmlspecialchars($restored['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
    ], JSON_UNESCAPED_UNICODE);
} finally {
    myFile::del($directory);
}
```

## 预期结果

round_trip=true、traversal_rejected=true；html_title中的b标记与&已经转义，原JSON值仍为原始文本。

## 调用顺序与实际应用

配置中的文件路径存网站根相对值，运行时用ROOT/myPath解析；浏览器URL使用ROOT_WEB和框架URL工具。resolveWithin只说明已有普通文件在边界内，不自动判断某文件是否应该公开。原始下载保持字节，不使用会移除BOM的文本读取方法代替流式文件出口。
