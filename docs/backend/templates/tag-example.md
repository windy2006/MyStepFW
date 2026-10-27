# 标签示例

沿用开发者getTemplate/getBlock与heredoc的讲解方式。标签回调在编译期生成PHP，实际列表在渲染期读取；用两次不同的数据渲染验证它不是编译时写死的内容。

## 运行位置与准备

在已经加载框架类库的独立测试脚本中运行；也可放入独立测试应用模块。不要再次调用myStep::init或start。以下代码使用自己的随机临时目录或SQLite内存库，不读取站点数据库或私有配置。代码块包含完整处理与收尾，多个范例分别运行。

## 完整示例

```php
<?php
$directory = sys_get_temp_dir().'/mystep-tag-'.bin2hex(random_bytes(6));
mkdir($directory, 0700);
try {
    file_put_contents($directory.'/main.tpl', '<!--recent items=\'$manual_rows\'-->');
    file_put_contents($directory.'/block_recent.tpl', '<ul><!--loop:start--><li><!--row_title--></li><!--loop:end--></ul>');
    $template = new myTemplate(['name'=>'main','path'=>$directory,'path_compile'=>$directory.'/compiled'], false);
    $template->regTag('recent', function (myTemplate &$current, &$attrs) use ($directory) {
        $fragment = $current->getTemplate($directory.'/block_recent.tpl');
        [$block, $attrs['unit'], $attrs['unit_blank']] = $current->getBlock($fragment, 'loop', 'row');
        $generated = <<<'GENERATED'
<?php
$rows = {myTemplate::items};
foreach (is_array($rows) ? $rows : [] as $row) {
    $row['title'] = htmlspecialchars((string)($row['title'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo <<<UNIT
{myTemplate::unit}
UNIT;
}
?>
GENERATED;
        return str_replace($block, $generated, $fragment);
    });
    global $manual_rows;
    $manual_rows = [['title'=>'第一轮 <b>文字</b>']];
    $first = $template->render('manual_rows', false);
    $manual_rows = [['title'=>'第二轮']];
    $second = $template->render('manual_rows', false);
    unset($manual_rows);
    echo json_encode(['first'=>$first, 'second'=>$second], JSON_UNESCAPED_UNICODE);
} finally {
    myFile::del($directory);
}
```

## 预期结果

first含第一轮及已转义的b标记；second只显示第二轮。两次使用同一个模板实例，说明真实数据在渲染时读取。

## 调用顺序与实际应用

getTemplate登记文件依赖；getBlock提取循环结构并将row字段改写为运行期表达式；unit属性再进入受信的PHP生成代码。单引号属性引用全局变量，render的global_vars显式声明该变量。真实项目回调放应用lib.php或插件类，块文件放对应template目录；路径不能取自任意请求输入。使用具名回调和compile_version时继续遵循[依赖与编译](template-tags.md)。
