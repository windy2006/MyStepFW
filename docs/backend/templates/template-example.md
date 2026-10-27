# 模板示例

本例完整建立两个模板，展示数据赋值、条件、循环、switch、子模板返回和主布局组合。

## 运行位置与准备

在已经加载框架类库的独立测试脚本中运行；也可放入独立测试应用模块。不要再次调用myStep::init或start。以下代码使用自己的随机临时目录或SQLite内存库，不读取站点数据库或私有配置。代码块包含完整处理与收尾，多个范例分别运行。

## 完整示例

```php
<?php
$directory = sys_get_temp_dir().'/mystep-template-'.bin2hex(random_bytes(6));
mkdir($directory, 0700);
try {
    file_put_contents($directory.'/main.tpl', '<h1><!--site|html--></h1><main><!--main--></main>');
    file_put_contents($directory.'/list.tpl', <<<'TPL'
<!--if:start key="visible"-->
<ul><!--loop:start key="items"--><li><!--items_name|html--></li><!--loop:end--></ul>
<!--else--><p>暂无内容</p><!--if:end-->
<!--switch:start key="type"--><!--(article)--><p>文章列表</p><!--switch:default--><p>其他类型</p><!--switch:end-->
TPL);
    $setting = ['name'=>'main','path'=>$directory,'style'=>'','path_compile'=>$directory.'/compiled'];
    $part = new myTemplate(array_replace($setting, ['name'=>'list']), false);
    $part->setIf('visible', true)->setSwitch('type', 'article')
        ->setLoop('items', [['name'=>'<em>A</em>'],['name'=>'B']], true);
    $content = $part->render('', false);
    $main = new myTemplate($setting, false);
    $main->assign('site', '示例 & 站点')->assign('main', $content);
    echo $main->render('', false);
} finally {
    myFile::del($directory);
}
```

## 预期结果

显示“示例 & 站点”、两条列表和“文章列表”；第一项的<em>A</em>作为文字显示，不变成斜体。输出HTML含&lt;em&gt;，不含实际em标签。

## 调用顺序与实际应用

原括号分支<!--(article)-->继续使用；兜底使用<!--switch:default-->。循环time是补足最低行数，不是查询LIMIT。示例先render子模板得到字符串，再assign给main；真实应用由原入口维护$tpl/$tpl_sub和$content，最终只show一次。主布局还应保留page_start/page_end，见[第一个应用](../../development/app/first-app.md)。
