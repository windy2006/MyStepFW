# 功能脚本

## 本篇目标与调用场景

以Hello为教学名称，说明本环节的文件位置、调用前提和与其他模块的对应关系。

## 主要变量

| 变量 | 用途与约束 |
| --- | --- |
| `$mystep` | 当前应用核心实例 |
| `$info_app` | 当前入口与路径上下文 |
| `$tpl_setting` | 当前风格和模板根设置 |
| `$tpl_sub` | 入口先建立，再由对应模块赋值 |

## 5. 功能脚本与模板对应

默认页面入口用固定白名单选择模块，模块准备数据，对应模板负责展示。例如 `module/index.php` 配对 `template/index.tpl`；内容功能可拆成列表和输入模板，添加／编辑复用输入模板。

```php
// app/Hello/module/index.php：由入口先准备$tpl_sub。
$tpl_sub->assign('title', $ms_setting->value('web.title', 'Hello'));
$tpl_sub->setLoop('items', [
    ['id' => 1, 'title' => '第一条内容'],
], true);
```

```html
<!-- template/index.tpl -->
<h1><!--title|html--></h1>
<ul><!--loop:start key="items"-->
<li><!--items_id|html-->：<!--items_title|html--></li>
<!--loop:end--></ul>
```

当前目录名是 `module/`，不是建立一个约定外的 `model/` 就会自动加载。框架不会替所有应用推导多层模块与模板名，由入口／模块明确映射。用户传入的动作、文件名和模板名都需白名单，不直接拼接 `include`。

## 操作步骤与注意事项

本功能源码全部放在自己的app目录。同名核心继承myStep，module与template按功能对应。JS代码另按JavaScript前端专栏编写；应用入口不再次启动框架或Session。

## 本环节的文件与完整写法

### 5. 准备数据并渲染模板

#### app/Hello/module/index.php

```php
<?php
$name = myReq::get('name', '!');
if (!is_string($name) || $name === '') {
    $name = '世界';
}
$name = mb_substr($name, 0, 40, 'UTF-8');

$tpl_sub->assign(
    'greeting',
    htmlspecialchars('你好，'.$name.'！', ENT_QUOTES, 'UTF-8')
);
$tpl_sub->assign('description', '这是由 MyStepFW 调度和渲染的第一个页面。');
```

这里使用 `myReq::get('name', '!')` 取得原始值，验证类型后在 HTML 输出时转义。默认的 `str` 读取模式已做 HTML 转义，混用会导致重复转义。查询参数可能是数组，例如 `?name[]=a`，所以不能假定所有输入都是字符串。模板变量默认不自动执行 HTML 转义；外部文本放进 HTML 前要根据输出位置转义。

#### app/Hello/template/main.tpl

```html
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><!--web_title--></title>
    <!--page_start-->
</head>
<body>
    <main>
        <!--main-->
    </main>
    <!--page_end-->
</body>
</html>
```

#### app/Hello/template/index.tpl

```html
<h1><!--greeting--></h1>
<p><!--description--></p>
```

框架变量占位符使用 `<!--变量名-->`。主模板的 `<!--main-->` 接收已经渲染的子模板；`<!--page_start-->` 和 `<!--page_end-->` 接收控制器收集的资源与附加内容。

模板中不需要启用 PHP 脚本执行。业务数据放在 PHP 模块，展示结构放在模板，能够让后续修改更清晰。

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
