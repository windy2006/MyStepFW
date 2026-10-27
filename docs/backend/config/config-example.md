# 配置实例：读取、合并、表单值和冲突

对应原手册“设置管理”和“应用设置”。不仅改变一个对象属性，还要区分只读探测、类型保留、局部合并与持久化。

## 运行位置与准备

在已经加载框架类库的独立测试脚本中运行；也可放入独立测试应用模块。不要再次调用myStep::init或start。以下代码使用自己的随机临时目录或SQLite内存库，不读取站点数据库或私有配置。代码块包含完整处理与收尾，多个范例分别运行。

## 完整示例

```php
<?php
$directory = sys_get_temp_dir().'/mystep-config-'.bin2hex(random_bytes(6));
mkdir($directory, 0700);
$file = $directory.'/settings.php';
try {
    file_put_contents($file, '<?php return ["web"=>["title"=>"原标题"],"feature"=>["enabled"=>true,"items"=>["a","b"]]];');
    $config = new myConfig($file);
    $missing = $config->value('optional.limit', 20); // 不创建optional分组。
    $config->merge(['web'=>['title'=>'新标题']]);
    $config->set(['enabled'=>false], 'feature', false); // 本例不是浏览器多选表单，不做缺省补齐。
    $config->save('php');
    $loaded = new myConfig($file);

    // 两个实例模拟两个管理员同时打开设置，避免覆盖另一个人的保存。
    $editorA = new myConfig($file);
    $editorB = new myConfig($file);
    $editorA->web->title = '管理员A的标题';
    $editorA->save();
    $editorB->web->title = '管理员B的标题';
    $conflict = false;
    try {
        $editorB->save();
    } catch (RuntimeException $error) {
        $conflict = true; // 实际后台提示刷新，重新读取后再由用户处理差异。
    }
    $final = new myConfig($file);
    echo json_encode([
        'default'=>$missing,
        'title'=>$loaded->web->title,
        'enabled'=>$loaded->feature->enabled,
        'items'=>$loaded->get(true)['feature']['items'],
        'has_optional'=>isset($loaded->optional),
        'conflict'=>$conflict,
        'final_title'=>$final->web->title,
    ], JSON_UNESCAPED_UNICODE);
} finally {
    myFile::del($directory); // 只清理本例自己建立的目录。
}
```

## 预期结果

输出包括default=20、title=新标题、enabled=false、items仍为a/b、has_optional=false、conflict=true；最终标题来自管理员A。

## 调用顺序与实际应用

先建立实例记录文件摘要，再通过value读取、merge覆盖、set更新明确分组，最后save写入。save不是每次读配置都应执行的动作。后台还需验证管理员身份、CSRF、允许修改的字段及业务范围。checkbox/switch的缺省补齐只针对本次提交分组；需要它时沿用默认check=true，见[配置表单结构](config-forms.md)。
