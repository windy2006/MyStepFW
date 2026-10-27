# 模板渲染

## 本篇目标与调用场景

myTemplate是PHP后端模板机制，不是JavaScript模板库。模板数据、编译代码和最终HTML分别有自己的作用域和缓存。

## 基本使用

```php
$tpl = new myTemplate([
    'name' => 'main',
    'style' => 'default',
    'path' => __DIR__.'/template',
    'path_compile' => CACHE.'/template/example',
]);
$tpl->assign('title', '欢迎使用框架')
    ->assign('content', '<p>由应用产生的可信 HTML</p>')
    ->setIf('logged_in', true)
    ->setSwitch('kind', 'article')
    ->setLoop('items', [
        ['name'=>'第一条', 'id'=>1],
        ['name'=>'第二条', 'id'=>2],
    ], true);
$html = $tpl->render('', false); // false 只返回，不立即输出
```

`name` 和 `style` 可以是相对的多级子目录，不能含 `..`。`path`、`path_compile`、页面缓存路径由应用配置。找不到首选模板时仍尝试 `path/default/文件名`。后来创建首选文件后，旧默认模板缓存会失效。

## 主要方法：参数、返回和失败约定

### myTemplate::render

功能说明：获取最终 HTML；独立缓冲区保证异常时不清空调用者已有内容

```text
public function render($global_vars = '', $tpl_show = true, $minify = false);
```

| 参数说明 |
| --- |
| string\|array $global_vars 供可信扩展 PHP 使用的全局变量名，逗号分隔或数组（默认：''） |
| bool $tpl_show true 输出并返回，false 只返回（默认：true） |
| bool $minify 保守去除 HTML 行首缩进，保留 pre/textarea/script/style 内容（默认：false） |

**返回**：string 渲染后的内容

**异常**：Throwable 编译或扩展异常继续传播，渲染缓冲区始终清理

## 明确调用示例

```php
$template = new myTemplate([
    'name'=>'list', 'path'=>$templateDirectory, 'path_compile'=>$compileDirectory,
], false);
$template->assign('title', 'A&B')->setIf('visible', true)
    ->setLoop('items', [['id'=>1,'title'=>'中文']], true);
$html = $template->render('', false);
// list.tpl使用<!--title|html-->和<!--loop:start key="items"-->等标签。
```

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
